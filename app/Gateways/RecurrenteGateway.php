<?php

namespace App\Gateways;

use App\Core\BasePaymentGateway;
use App\Core\Config;
use Exception;

/**
 * Recurrente Payment Gateway Implementation
 *
 * Integrates with Recurrente API for international payments (GTQ/USD)
 * API Documentation: Recurrente-DocumentaciónAPI.postman_collection.json
 *
 * Features:
 * - Checkout session creation
 * - Webhook support for payment notifications
 * - Refund API support
 * - Multi-currency (GTQ, USD)
 *
 * @package App\Gateways
 */
class RecurrenteGateway extends BasePaymentGateway
{
    protected $publicKey;
    protected $secretKey;
    protected $baseUrl;
    protected $gatewayName = 'recurrente';

    public function __construct()
    {
        parent::__construct();
        $this->publicKey = Config::getRecurrentePublicKey();
        $this->secretKey = Config::getRecurrenteSecretKey();
        $this->baseUrl = Config::getRecurrenteBaseUrl();

        if (empty($this->publicKey) || empty($this->secretKey)) {
            throw new Exception('Recurrente API keys not configured');
        }
    }

    /**
     * Create checkout session
     *
     * Endpoint: POST /api/checkouts/
     * Auth: X-PUBLIC-KEY and X-SECRET-KEY headers
     * Content-Type: application/x-www-form-urlencoded (form-data)
     *
     * @param array $data Payment data
     * @return array
     */
    public function createPayment(array $data): array
    {
        try {
            $reservaId = $data['reserva_id'];
            $amount = (float)$data['amount'];
            $currency = $data['currency'] ?? Config::getRecurrenteDefaultCurrency();
            $tourName = $data['tour_name'] ?? 'Tour Reserva';
            $tourImage = $data['tour_image'] ?? '';
            $customerEmail = $data['customer_email'] ?? '';
            $customerName = $data['customer_name'] ?? '';
            $successUrl = $data['success_url'] ?? Config::getBaseUrl() . '?route=payment/success-recurrente';
            $cancelUrl = $data['cancel_url'] ?? Config::getBaseUrl() . '?route=payment/cancel';

            // Convert amount to cents (Recurrente requires integer cents)
            $amountInCents = $this->formatAmount($amount, true);

            // Prepare form data (Recurrente expects form-data, NOT JSON)
            $formData = [
                'items[][name]' => $tourName,
                'items[][currency]' => $currency,
                'items[][amount_in_cents]' => $amountInCents,
                'items[][quantity]' => 1,
                'items[][has_dynamic_pricing]' => 'false',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => json_encode([
                    'reserva_id' => $reservaId,
                    'customer_email' => $customerEmail,
                    'customer_name' => $customerName
                ])
            ];

            // Add customer email if provided
            if (!empty($customerEmail)) {
                $formData['customer_email'] = $customerEmail;
            }

            // Add tour image if provided
            if (!empty($tourImage)) {
                $formData['items[][image_url]'] = $tourImage;
            }

            // Make API request with form-data
            $response = $this->makeFormDataRequest(
                'POST',
                $this->baseUrl . '/checkouts/',
                $formData
            );

            // Check for errors
            if (!$response['success']) {
                $errorMsg = $response['data']['error'] ?? $response['error'] ?? 'Unknown error creating checkout';
                throw new Exception($errorMsg);
            }

            // Extract response data
            $result = $response['data'];
            if (empty($result['checkout_url'])) {
                throw new Exception('Invalid response from Recurrente API - missing checkout URL');
            }

            $checkoutId = $result['id'];
            $checkoutUrl = $result['checkout_url'];
            $expiresAt = $result['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+24 hours'));

            // Log transaction to database
            $this->logTransaction($reservaId, 'payment_link_created', [
                'transaction_id' => $checkoutId,
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'pending',
                'payment_link' => $checkoutUrl,
                'link_expires_at' => date('Y-m-d H:i:s', strtotime($expiresAt)),
                'customer_email' => $customerEmail,
                'customer_name' => $customerName,
                'request' => $formData,
                'response' => $response['data']
            ]);

            return [
                'success' => true,
                'payment_link' => $checkoutUrl,
                'transaction_id' => $checkoutId,
                'expires_at' => $expiresAt,
                'gateway' => 'recurrente'
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
     * Verify payment status
     *
     * Endpoint: GET /api/checkouts/:checkoutId
     * Auth: X-PUBLIC-KEY and X-SECRET-KEY headers
     *
     * @param string $transactionId Checkout ID
     * @return array
     */
    public function verifyPayment(string $transactionId): array
    {
        try {
            $response = $this->makeFormDataRequest(
                'GET',
                $this->baseUrl . '/checkouts/' . $transactionId,
                []
            );

            if (!$response['success']) {
                throw new Exception('Failed to verify payment status');
            }

            $result = $response['data'];
            $status = $result['status'] ?? 'unpaid';
            $isPaid = ($status === 'paid');

            // Update transaction record
            $this->updateTransaction($transactionId, [
                'status' => $isPaid ? 'captured' : 'pending',
                'response_payload' => json_encode($response['data']),
                'verified_at' => date('Y-m-d H:i:s')
            ]);

            return [
                'success' => true,
                'status' => $status,
                'paid' => $isPaid,
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
     * Same as verifyPayment for Recurrente
     *
     * @param string $transactionId
     * @return array
     */
    public function getPaymentDetails(string $transactionId): array
    {
        return $this->verifyPayment($transactionId);
    }

    /**
     * Cancel checkout
     * Note: Recurrente checkouts auto-expire, explicit cancel may not be necessary
     *
     * @param string $transactionId
     * @return array
     */
    public function cancelPayment(string $transactionId): array
    {
        // Checkouts auto-expire based on expires_at timestamp
        return [
            'success' => true,
            'message' => 'Checkout will expire automatically'
        ];
    }

    /**
     * Refund payment
     *
     * Endpoint: POST /api/refunds/
     * Auth: X-PUBLIC-KEY and X-SECRET-KEY headers
     *
     * @param string $transactionId Payment intent ID
     * @param float $amount Amount to refund
     * @return array
     */
    public function refundPayment(string $transactionId, float $amount): array
    {
        try {
            $amountInCents = $this->formatAmount($amount, true);

            $formData = [
                'payment_intent_id' => $transactionId,
                'amount_in_cents' => $amountInCents,
                'reason' => 'requested_by_customer'
            ];

            $response = $this->makeFormDataRequest(
                'POST',
                $this->baseUrl . '/refunds/',
                $formData
            );

            if (!$response['success']) {
                $errorMsg = $response['data']['error'] ?? 'Refund failed';
                throw new Exception($errorMsg);
            }

            $result = $response['data'];

            return [
                'success' => true,
                'refund_id' => $result['id'] ?? null,
                'status' => $result['status'] ?? 'pending',
                'amount_refunded' => $this->centsToDecimal($result['customer_refunded_amount_in_cents'] ?? 0)
            ];

        } catch (Exception $e) {
            $this->logError('Refund failed: ' . $e->getMessage(), [
                'transaction_id' => $transactionId,
                'amount' => $amount
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Recurrente supports webhooks
     *
     * @return bool
     */
    public function supportsWebhooks(): bool
    {
        return true;
    }

    /**
     * Verify webhook signature
     *
     * Recurrente uses HMAC SHA256 signature verification
     *
     * @param string $payload Raw webhook payload
     * @param string $signature Signature from HTTP_X_RECURRENTE_SIGNATURE header
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $webhookSecret = Config::getRecurrenteWebhookSecret();

        // In development without webhook secret configured, allow webhooks
        if (empty($webhookSecret)) {
            if (Config::isDevelopment()) {
                $this->logError('WARNING: Webhook signature verification skipped in development mode');
                return true;
            }
            return false;
        }

        // Compute HMAC SHA256 signature
        $computed = hash_hmac('sha256', $payload, $webhookSecret);

        // Constant-time string comparison to prevent timing attacks
        return hash_equals($computed, $signature);
    }

    /**
     * Make form-data request (Recurrente requires this format, not JSON)
     *
     * @param string $method HTTP method
     * @param string $url Full URL
     * @param array $data Form data
     * @return array
     */
    private function makeFormDataRequest(string $method, string $url, array $data): array
    {
        $ch = curl_init();

        $headers = [
            'X-PUBLIC-KEY: ' . $this->publicKey,
            'X-SECRET-KEY: ' . $this->secretKey
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => !Config::isDevelopment(),
        ]);

        // For POST requests, send as form-data
        if ($method === 'POST' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            $this->logError("cURL Error: $error");
            return ['success' => false, 'error' => 'Network error: ' . $error];
        }

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logError("JSON decode error: " . json_last_error_msg() . " | Response: " . substr($response, 0, 500));
            return ['success' => false, 'error' => 'Invalid response from payment gateway'];
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'http_code' => $httpCode,
            'data' => $decoded,
            'raw_response' => $response
        ];
    }
}
