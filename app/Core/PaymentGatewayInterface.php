<?php

namespace App\Core;

/**
 * Payment Gateway Interface
 *
 * Defines the contract that all payment gateway implementations must follow.
 * Supports Paggo, Recurrente, Stripe, and RNPL payment gateways.
 *
 * @package App\Core
 */
interface PaymentGatewayInterface
{
    /**
     * Create a payment session/link
     *
     * @param array $data Booking and payment data
     *   Required keys:
     *   - reserva_id: int
     *   - amount: float
     *   - customer_name: string
     *   - customer_email: string
     *   - concept/tour_name: string
     *   Optional keys:
     *   - currency: string (USD, GTQ, etc.)
     *   - success_url: string
     *   - cancel_url: string
     *   - tour_image: string
     *
     * @return array [
     *   'success' => bool,
     *   'payment_link' => string (URL),
     *   'transaction_id' => string,
     *   'expires_at' => string (ISO 8601 datetime),
     *   'error' => string (if success=false)
     * ]
     */
    public function createPayment(array $data): array;

    /**
     * Verify payment status
     *
     * @param string $transactionId Gateway transaction ID
     * @return array [
     *   'success' => bool,
     *   'status' => string (pending, completed, failed, etc.),
     *   'paid' => bool,
     *   'error' => string (if success=false),
     *   'details' => array (gateway-specific data)
     * ]
     */
    public function verifyPayment(string $transactionId): array;

    /**
     * Get detailed payment information
     *
     * @param string $transactionId Gateway transaction ID
     * @return array Payment details or error
     */
    public function getPaymentDetails(string $transactionId): array;

    /**
     * Cancel/void a payment
     *
     * @param string $transactionId Gateway transaction ID
     * @return array [
     *   'success' => bool,
     *   'error' => string (if success=false)
     * ]
     */
    public function cancelPayment(string $transactionId): array;

    /**
     * Refund a payment
     *
     * @param string $transactionId Gateway transaction ID
     * @param float $amount Amount to refund
     * @return array [
     *   'success' => bool,
     *   'refund_id' => string,
     *   'error' => string (if success=false)
     * ]
     */
    public function refundPayment(string $transactionId, float $amount): array;

    /**
     * Get gateway name identifier
     *
     * @return string (e.g., 'paggo', 'recurrente', 'stripe', 'rnpl')
     */
    public function getGatewayName(): string;

    /**
     * Check if gateway supports webhooks
     *
     * @return bool True if webhooks are supported, false if polling is required
     */
    public function supportsWebhooks(): bool;
}
