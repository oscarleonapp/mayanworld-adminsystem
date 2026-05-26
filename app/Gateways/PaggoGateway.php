<?php

namespace App\Gateways;

use App\Core\BasePaymentGateway;
use App\Core\Config;
use Exception;

/**
 * Paggo Payment Gateway Implementation
 *
 * Integrates with Paggo API for payment processing in Guatemala (National payments)
 * API Documentation: Paggo-collection-postman.json
 *
 * Features:
 * - Payment link generation
 * - Payment verification (polling-based, no webhooks)
 * - Automatic queue for payment verification
 *
 * @package App\Gateways
 */
class PaggoGateway extends BasePaymentGateway
{
    protected $apiKey;
    protected $baseUrl;
    protected $gatewayName = 'paggo';

    public function __construct()
    {
        parent::__construct();
        $this->apiKey = Config::getPaggoApiKey();
        $this->baseUrl = Config::getPaggoBaseUrl();

        if (empty($this->apiKey)) {
            throw new Exception('Paggo API key not configured');
        }
    }

    /**
     * Create payment link
     *
     * Endpoint: POST /center/transactions/create-link
     * Auth: X-API-KEY header
     *
     * @param array $data Payment data
     * @return array
     */
    public function createPayment(array $data): array
    {
        try {
            $reservaId = $data['reserva_id'];
            $amount = (float)$data['amount']; // Decimal format: 10.50
            $customerName = $data['customer_name'];
            $customerEmail = $data['customer_email'];
            $concept = $data['concept'] ?? 'Reserva de tour';

            // Prepare request payload
            $requestData = [
                'concept' => $concept,
                'amount' => $amount,
                'customerName' => $customerName,
                'email' => $customerEmail
            ];

            // Optional: Add metadata for tracking
            if (isset($data['metadata'])) {
                $requestData['metadata'] = $data['metadata'];
            }

            // Make API request
            $response = $this->makeRequest(
                'POST',
                $this->baseUrl . '/center/transactions/create-link',
                $requestData,
                ['X-API-KEY: ' . $this->apiKey]
            );

            // Check for errors
            if (!$response['success']) {
                $errorMsg = $response['data']['error'] ?? $response['error'] ?? 'Unknown error creating payment link';
                throw new Exception($errorMsg);
            }

            // Extract response data
            $result = $response['data']['result'] ?? null;
            if (!$result || empty($result['link'])) {
                throw new Exception('Invalid response from Paggo API - missing payment link');
            }

            $transactionId = (string)$result['id'];
            $paymentLink = $result['link'];
            $expiresAt = $result['expirationDate']; // ISO 8601 format

            // Log transaction to database
            $this->logTransaction($reservaId, 'payment_link_created', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'currency' => 'GTQ', // Paggo uses GTQ
                'status' => 'pending',
                'payment_link' => $paymentLink,
                'link_expires_at' => date('Y-m-d H:i:s', strtotime($expiresAt)),
                'customer_email' => $customerEmail,
                'customer_name' => $customerName,
                'request' => $requestData,
                'response' => $response['data']
            ]);

            // Add to verification queue (for cron job polling)
            $this->addToVerificationQueue($reservaId, $transactionId, $paymentLink, $expiresAt);

            return [
                'success' => true,
                'payment_link' => $paymentLink,
                'transaction_id' => $transactionId,
                'expires_at' => $expiresAt,
                'gateway' => 'paggo'
            ];

        } catch (Exception $e) {
            $this->logError('Create payment failed: ' . $e->getMessage(), $data);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify payment status by querying gateway
     *
     * Endpoint: GET /center/transactions/links/:linkId
     * Auth: X-API-KEY header
     *
     * @param string $transactionId Paggo link ID
     * @return array
     */
    public function verifyPayment(string $transactionId): array
    {
        try {
            $response = $this->makeRequest(
                'GET',
                $this->baseUrl . '/center/transactions/links/' . $transactionId,
                [],
                ['X-API-KEY: ' . $this->apiKey]
            );

            if (!$response['success']) {
                throw new Exception('Failed to verify payment status');
            }

            $result = $response['data']['result'] ?? $response['data'] ?? null;
            if (!$result) {
                throw new Exception('Invalid response from Paggo API');
            }

            $status = strtolower($result['status'] ?? 'pendiente');

            // Map Paggo status to internal status
            $statusMap = [
                'pagado' => 'completed',
                'pendiente' => 'pending',
                'cancelado' => 'cancelled',
                'expirado' => 'expired'
            ];

            $internalStatus = $statusMap[$status] ?? 'pending';
            $isPaid = ($status === 'pagado');

            // Update transaction record
            $this->updateTransaction($transactionId, [
                'status' => $isPaid ? 'captured' : $internalStatus,
                'response_payload' => json_encode($response['data']),
                'verified_at' => date('Y-m-d H:i:s')
            ]);

            return [
                'success' => true,
                'status' => $internalStatus,
                'paid' => $isPaid,
                'gateway_status' => $status,
                'details' => $result
            ];

        } catch (Exception $e) {
            $this->logError('Verify payment failed: ' . $e->getMessage(), ['transaction_id' => $transactionId]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get payment details
     * Same as verifyPayment for Paggo
     *
     * @param string $transactionId
     * @return array
     */
    public function getPaymentDetails(string $transactionId): array
    {
        return $this->verifyPayment($transactionId);
    }

    /**
     * Cancel payment link
     *
     * Endpoint: POST /center/transactions/links/:linkId/cancel
     * Note: Paggo links auto-expire, explicit cancel may not be necessary
     *
     * @param string $transactionId
     * @return array
     */
    public function cancelPayment(string $transactionId): array
    {
        try {
            $response = $this->makeRequest(
                'POST',
                $this->baseUrl . '/center/transactions/links/' . $transactionId . '/cancel',
                [],
                ['X-API-KEY: ' . $this->apiKey]
            );

            if (!$response['success']) {
                // If cancel endpoint doesn't exist or fails, links auto-expire anyway
                return [
                    'success' => true,
                    'message' => 'Payment link will expire automatically'
                ];
            }

            return [
                'success' => true,
                'message' => 'Payment link cancelled successfully'
            ];

        } catch (Exception $e) {
            // Non-critical error, links expire anyway
            return [
                'success' => true,
                'message' => 'Payment link will expire automatically'
            ];
        }
    }

    /**
     * Refund payment
     *
     * Note: Based on Postman collection, Paggo may not have a refund API endpoint
     * Refunds might need to be processed manually through Paggo dashboard
     *
     * @param string $transactionId
     * @param float $amount
     * @return array
     */
    public function refundPayment(string $transactionId, float $amount): array
    {
        // Check Paggo API documentation for refund endpoint
        // If not available, refunds must be processed manually
        $this->logError('Refund requested but not implemented via API', [
            'transaction_id' => $transactionId,
            'amount' => $amount
        ]);

        return [
            'success' => false,
            'error' => 'Refunds must be processed manually through Paggo dashboard. Please contact support.'
        ];
    }

    /**
     * Paggo doesn't support webhooks (based on Postman collection)
     * Payment verification must be done via polling
     *
     * @return bool
     */
    public function supportsWebhooks(): bool
    {
        return false;
    }

    /**
     * Add payment to verification queue for cron job polling
     *
     * @param int $reservaId
     * @param string $transactionId
     * @param string $paymentLink
     * @param string $expiresAt ISO 8601 datetime
     * @return void
     */
    private function addToVerificationQueue(int $reservaId, string $transactionId, string $paymentLink, string $expiresAt): void
    {
        try {
            // Schedule first verification in 5 minutes
            $nextVerification = date('Y-m-d H:i:s', strtotime('+5 minutes'));
            $linkExpiration = date('Y-m-d H:i:s', strtotime($expiresAt));

            $query = "INSERT INTO payment_verification_queue (
                reserva_id, gateway, gateway_transaction_id, payment_link,
                link_expires_at, next_verification_at, status
            ) VALUES (
                :reserva_id, :gateway, :gateway_transaction_id, :payment_link,
                :link_expires_at, :next_verification_at, :status
            ) ON DUPLICATE KEY UPDATE
                payment_link = VALUES(payment_link),
                link_expires_at = VALUES(link_expires_at),
                next_verification_at = VALUES(next_verification_at),
                status = VALUES(status)";

            $stmt = $this->db->prepare($query);
            $stmt->execute([
                'reserva_id' => $reservaId,
                'gateway' => 'paggo',
                'gateway_transaction_id' => $transactionId,
                'payment_link' => $paymentLink,
                'link_expires_at' => $linkExpiration,
                'next_verification_at' => $nextVerification,
                'status' => 'pending'
            ]);

        } catch (Exception $e) {
            $this->logError('Failed to add to verification queue: ' . $e->getMessage());
        }
    }
}
