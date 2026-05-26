<?php

namespace App\Core;

use App\Gateways\PaggoGateway;
use App\Gateways\RecurrenteGateway;
use Exception;

/**
 * Payment Gateway Factory
 *
 * Creates payment gateway instances and provides gateway information for UI
 *
 * @package App\Core
 */
class PaymentGatewayFactory
{
    /**
     * Create payment gateway instance by name
     *
     * @param string $gatewayName Gateway identifier (paggo, recurrente)
     * @return PaymentGatewayInterface
     * @throws Exception If gateway is unknown or not configured
     */
    public static function create(string $gatewayName): PaymentGatewayInterface
    {
        switch (strtolower($gatewayName)) {
            case 'paggo':
                if (!Config::isPaggoEnabled()) {
                    throw new Exception('Paggo gateway is not enabled or configured');
                }
                return new PaggoGateway();

            case 'recurrente':
                if (!Config::isRecurrenteEnabled()) {
                    throw new Exception('Recurrente gateway is not enabled or configured');
                }
                return new RecurrenteGateway();

            default:
                throw new Exception("Unknown payment gateway: {$gatewayName}");
        }
    }

    /**
     * Get all enabled payment gateways
     *
     * Returns array of gateway names that are properly configured
     * Includes both new gateways (Paggo, Recurrente) and legacy (Stripe, RNPL)
     *
     * @return array ['stripe', 'rnpl', 'paggo', 'recurrente']
     */
    public static function getEnabledGateways(): array
    {
        $gateways = [];

        // Check new gateways
        if (Config::isPaggoEnabled()) {
            $gateways[] = 'paggo';
        }

        if (Config::isRecurrenteEnabled()) {
            $gateways[] = 'recurrente';
        }

        // Always include Stripe and RNPL (legacy gateways, always available)
        $gateways[] = 'stripe';
        $gateways[] = 'rnpl';

        return $gateways;
    }

    /**
     * Get gateway display information for UI
     *
     * Returns metadata for rendering payment options in checkout
     *
     * @param string $gatewayName Gateway identifier
     * @return array|null Gateway info or null if not found
     */
    public static function getGatewayInfo(string $gatewayName): ?array
    {
        $gatewayInfo = [
            'stripe' => [
                'name' => 'stripe',
                'display_name' => 'Tarjeta de Crédito/Débito',
                'description' => 'Paga con tarjeta de crédito o débito de forma segura',
                'icon' => 'fa-credit-card',
                'color' => '#635bff',
                'currencies' => ['USD'],
                'countries' => ['Internacional'],
                'payment_type' => 'immediate'
            ],

            'rnpl' => [
                'name' => 'rnpl',
                'display_name' => 'Reserva Ahora, Paga Después',
                'description' => 'Asegura tu lugar ahora y paga hasta 48 horas antes del tour',
                'icon' => 'fa-calendar-check',
                'color' => '#10b981',
                'currencies' => ['USD'],
                'countries' => ['Internacional'],
                'payment_type' => 'deferred',
                'requires_eligibility_check' => true
            ],

            'paggo' => [
                'name' => 'paggo',
                'display_name' => 'Paggo',
                'description' => 'Pago local seguro con Paggo - ideal para clientes en Guatemala',
                'icon' => 'fa-mobile-alt',
                'color' => '#f97316',
                'currencies' => ['GTQ', 'USD'],
                'countries' => ['Guatemala'],
                'payment_type' => 'link',
                'supports_webhooks' => false
            ],

            'recurrente' => [
                'name' => 'recurrente',
                'display_name' => 'Recurrente',
                'description' => 'Pagos internacionales en Quetzales o Dólares',
                'icon' => 'fa-globe',
                'color' => '#3b82f6',
                'currencies' => ['GTQ', 'USD'],
                'countries' => ['Internacional'],
                'payment_type' => 'redirect',
                'supports_webhooks' => true
            ],
        ];

        return $gatewayInfo[strtolower($gatewayName)] ?? null;
    }

    /**
     * Check if a gateway is enabled and available
     *
     * @param string $gatewayName Gateway identifier
     * @return bool
     */
    public static function isGatewayEnabled(string $gatewayName): bool
    {
        $enabledGateways = self::getEnabledGateways();
        return in_array(strtolower($gatewayName), $enabledGateways);
    }

    /**
     * Get all gateway information
     *
     * Returns array of all available gateways with their info
     *
     * @return array
     */
    public static function getAllGatewaysInfo(): array
    {
        $enabledGateways = self::getEnabledGateways();
        $info = [];

        foreach ($enabledGateways as $gateway) {
            $gatewayInfo = self::getGatewayInfo($gateway);
            if ($gatewayInfo) {
                $info[$gateway] = $gatewayInfo;
            }
        }

        return $info;
    }

    /**
     * Validate gateway configuration
     *
     * Check if a gateway has all required configuration
     *
     * @param string $gatewayName Gateway identifier
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateGatewayConfig(string $gatewayName): array
    {
        $errors = [];

        switch (strtolower($gatewayName)) {
            case 'paggo':
                if (empty(Config::getPaggoApiKey())) {
                    $errors[] = 'Paggo API key not configured';
                }
                if (empty(Config::getPaggoBaseUrl())) {
                    $errors[] = 'Paggo base URL not configured';
                }
                break;

            case 'recurrente':
                if (empty(Config::getRecurrentePublicKey())) {
                    $errors[] = 'Recurrente public key not configured';
                }
                if (empty(Config::getRecurrenteSecretKey())) {
                    $errors[] = 'Recurrente secret key not configured';
                }
                if (empty(Config::getRecurrenteBaseUrl())) {
                    $errors[] = 'Recurrente base URL not configured';
                }
                break;

            case 'stripe':
                // Stripe validation (legacy)
                if (empty(Config::getStripeSecretKey())) {
                    $errors[] = 'Stripe secret key not configured';
                }
                break;

            case 'rnpl':
                // RNPL has no external API configuration
                break;

            default:
                $errors[] = "Unknown gateway: {$gatewayName}";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
