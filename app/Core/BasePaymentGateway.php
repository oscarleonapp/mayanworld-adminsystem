<?php

namespace App\Core;

use Exception;

/**
 * Base Payment Gateway Abstract Class
 *
 * Provides common functionality for all payment gateway implementations
 *
 * @package App\Core
 */
abstract class BasePaymentGateway implements PaymentGatewayInterface
{
    protected $db;
    protected $gatewayName;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Make HTTP request to gateway API
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE, PATCH)
     * @param string $url Full URL to request
     * @param array $data Request payload
     * @param array $headers Additional headers
     * @return array [
     *   'success' => bool,
     *   'http_code' => int,
     *   'data' => array (decoded JSON),
     *   'raw_response' => string
     * ]
     */
    protected function makeRequest(string $method, string $url, array $data = [], array $headers = []): array
    {
        $ch = curl_init();

        $defaultHeaders = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $allHeaders = array_merge($defaultHeaders, $headers);

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => !Config::isDevelopment(), // Disable SSL verify only in dev
        ]);

        if ($method !== 'GET' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
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

    /**
     * Log transaction to database
     *
     * @param int $reservaId Reservation ID
     * @param string $type Transaction type
     * @param array $data Transaction data
     * @return int Transaction ID
     */
    protected function logTransaction(int $reservaId, string $type, array $data): int
    {
        $transactionData = [
            'reserva_id' => $reservaId,
            'gateway' => $this->gatewayName,
            'transaction_type' => $type,
            'gateway_transaction_id' => $data['transaction_id'] ?? null,
            'amount' => $data['amount'] ?? 0,
            'currency' => $data['currency'] ?? 'USD',
            'status' => $data['status'] ?? 'pending',
            'payment_link' => $data['payment_link'] ?? null,
            'link_expires_at' => $data['link_expires_at'] ?? null,
            'customer_email' => $data['customer_email'] ?? null,
            'customer_name' => $data['customer_name'] ?? null,
            'request_payload' => isset($data['request']) ? json_encode($data['request']) : null,
            'response_payload' => isset($data['response']) ? json_encode($data['response']) : null,
            'error_message' => $data['error'] ?? null,
            'metadata' => isset($data['metadata']) ? json_encode($data['metadata']) : null,
            'processed_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $query = "INSERT INTO payment_gateway_transactions (
                reserva_id, gateway, transaction_type, gateway_transaction_id,
                amount, currency, status, payment_link, link_expires_at,
                customer_email, customer_name, request_payload, response_payload,
                error_message, metadata, processed_at
            ) VALUES (
                :reserva_id, :gateway, :transaction_type, :gateway_transaction_id,
                :amount, :currency, :status, :payment_link, :link_expires_at,
                :customer_email, :customer_name, :request_payload, :response_payload,
                :error_message, :metadata, :processed_at
            )";

            $stmt = $this->db->prepare($query);
            $stmt->execute($transactionData);

            return (int)$this->db->lastInsertId();
        } catch (Exception $e) {
            $this->logError("Failed to log transaction: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Update transaction status
     *
     * @param string $transactionId Gateway transaction ID
     * @param array $data Data to update
     * @return void
     */
    protected function updateTransaction(string $transactionId, array $data): void
    {
        try {
            $allowedFields = ['status', 'transaction_type', 'response_payload', 'webhook_payload', 'error_message', 'verified_at'];
            $updates = [];
            $params = ['gateway_transaction_id' => $transactionId, 'gateway' => $this->gatewayName];

            foreach ($data as $field => $value) {
                if (in_array($field, $allowedFields)) {
                    $updates[] = "$field = :$field";
                    $params[$field] = $value;
                }
            }

            if (empty($updates)) {
                return;
            }

            $query = "UPDATE payment_gateway_transactions
                      SET " . implode(', ', $updates) . "
                      WHERE gateway_transaction_id = :gateway_transaction_id
                      AND gateway = :gateway";

            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
        } catch (Exception $e) {
            $this->logError("Failed to update transaction: " . $e->getMessage());
        }
    }

    /**
     * Log error message
     *
     * @param string $message Error message
     * @param array $context Additional context
     * @return void
     */
    protected function logError(string $message, array $context = []): void
    {
        $logMessage = "[{$this->gatewayName}] $message";
        if (!empty($context)) {
            $logMessage .= " | Context: " . json_encode($context);
        }
        error_log($logMessage);
    }

    /**
     * Get gateway name
     *
     * @return string
     */
    public function getGatewayName(): string
    {
        return $this->gatewayName;
    }

    /**
     * Format amount for gateway API
     * Some gateways use decimals (10.50), others use cents (1050)
     *
     * @param float $amount Amount in decimal format
     * @param bool $useCents Whether to convert to cents
     * @return float|int
     */
    protected function formatAmount(float $amount, bool $useCents = false)
    {
        if ($useCents) {
            return (int)($amount * 100);
        }
        return round($amount, 2);
    }

    /**
     * Convert cents to decimal amount
     *
     * @param int $cents Amount in cents
     * @return float
     */
    protected function centsToDecimal(int $cents): float
    {
        return round($cents / 100, 2);
    }
}
