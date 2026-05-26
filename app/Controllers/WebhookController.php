<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\PaymentGatewayFactory;
use App\Models\Booking;
use Exception;

class WebhookController extends BaseController
{
    private $bookingModel;

    public function __construct()
    {
        parent::__construct();
        $this->bookingModel = new Booking();
    }

    /**
     * Webhook de Recurrente
     * Endpoint: /public/?route=webhook/recurrente
     */
    public function recurrente()
    {
        // Leer payload crudo
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_RECURRENTE_SIGNATURE'] ?? '';

        // Log del webhook recibido
        $this->logWebhook('recurrente', $payload, $signature);

        // Verificar firma
        try {
            $gateway = PaymentGatewayFactory::create('recurrente');
            if (!$gateway->verifyWebhookSignature($payload, $signature)) {
                http_response_code(401);
                echo json_encode(['error' => 'Firma inválida']);
                $this->logError('Recurrente webhook: Firma inválida');
                exit;
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Error al verificar firma']);
            $this->logError('Recurrente webhook error: ' . $e->getMessage());
            exit;
        }

        // Decodificar evento
        $event = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'JSON inválido']);
            exit;
        }

        // Procesar según tipo de evento
        $eventType = $event['type'] ?? '';
        try {
            switch ($eventType) {
                case 'checkout.paid':
                    $this->handleCheckoutPaid($event['data'] ?? []);
                    break;
                case 'checkout.failed':
                    $this->handleCheckoutFailed($event['data'] ?? []);
                    break;
                case 'refund.created':
                    $this->handleRefundCreated($event['data'] ?? []);
                    break;
                default:
                    // Evento no manejado, pero lo aceptamos
                    $this->logInfo('Recurrente webhook: Evento no manejado - ' . $eventType);
                    break;
            }
        } catch (Exception $e) {
            $this->logError('Recurrente webhook processing error: ' . $e->getMessage());
            // Aún así respondemos 200 para evitar reintentos
        }

        // Responder siempre 200 para confirmar recepción
        http_response_code(200);
        echo json_encode(['received' => true, 'event_type' => $eventType]);
        exit;
    }

    /**
     * Manejar evento checkout.paid
     */
    private function handleCheckoutPaid(array $data)
    {
        $checkoutId = $data['id'] ?? null;
        $status = $data['status'] ?? '';
        $amount = $data['amount'] ?? 0;
        $metadata = $data['metadata'] ?? [];
        $reservaId = $metadata['reserva_id'] ?? null;

        if (!$reservaId || !$checkoutId) {
            $this->logError('Recurrente checkout.paid: Missing reserva_id or checkout_id');
            return;
        }

        // Verificar que la reserva existe
        $reserva = $this->db->fetch(
            "SELECT * FROM reservas WHERE id = :id",
            ['id' => $reservaId]
        );

        if (!$reserva) {
            $this->logError('Recurrente checkout.paid: Reserva no encontrada - ID: ' . $reservaId);
            return;
        }

        // Actualizar reserva a pagada
        $this->db->update('reservas', [
            'estado' => 'pagada',
            'payment_status' => 'captured',
            'fecha_pago' => date('Y-m-d H:i:s')
        ], ['id' => $reservaId]);

        // Registrar pago en tabla pagos
        $this->db->insert('pagos', [
            'reserva_id' => $reservaId,
            'monto' => $amount / 100, // Recurrente envía en centavos
            'metodo' => 'recurrente',
            'estado' => 'completado',
            'referencia_externa' => $checkoutId,
            'datos_pago' => json_encode([
                'webhook' => true,
                'checkout_id' => $checkoutId,
                'status' => $status
            ]),
            'fecha_pago' => date('Y-m-d H:i:s')
        ]);

        // Actualizar transacción en payment_gateway_transactions
        $this->db->query(
            "UPDATE payment_gateway_transactions
             SET status = 'captured',
                 transaction_type = 'payment_completed',
                 verified_at = NOW(),
                 webhook_payload = :payload
             WHERE gateway_transaction_id = :txn_id
             AND gateway = 'recurrente'",
            [
                'payload' => json_encode($data),
                'txn_id' => $checkoutId
            ]
        );

        // TODO: Enviar email de confirmación
        $this->logInfo('Recurrente checkout.paid: Reserva #' . $reservaId . ' marcada como pagada');
    }

    /**
     * Manejar evento checkout.failed
     */
    private function handleCheckoutFailed(array $data)
    {
        $checkoutId = $data['id'] ?? null;
        $metadata = $data['metadata'] ?? [];
        $reservaId = $metadata['reserva_id'] ?? null;

        if (!$reservaId || !$checkoutId) {
            $this->logError('Recurrente checkout.failed: Missing reserva_id or checkout_id');
            return;
        }

        // Actualizar reserva como fallida
        $this->db->update('reservas', [
            'estado' => 'cancelada',
            'payment_status' => 'failed'
        ], ['id' => $reservaId]);

        // Actualizar transacción
        $this->db->query(
            "UPDATE payment_gateway_transactions
             SET status = 'failed',
                 transaction_type = 'payment_failed',
                 verified_at = NOW(),
                 webhook_payload = :payload,
                 error_message = 'Pago rechazado por Recurrente'
             WHERE gateway_transaction_id = :txn_id
             AND gateway = 'recurrente'",
            [
                'payload' => json_encode($data),
                'txn_id' => $checkoutId
            ]
        );

        $this->logInfo('Recurrente checkout.failed: Reserva #' . $reservaId . ' marcada como fallida');
    }

    /**
     * Manejar evento refund.created
     */
    private function handleRefundCreated(array $data)
    {
        $refundId = $data['id'] ?? null;
        $checkoutId = $data['checkout_id'] ?? null;
        $amount = $data['amount'] ?? 0;

        if (!$checkoutId || !$refundId) {
            $this->logError('Recurrente refund.created: Missing checkout_id or refund_id');
            return;
        }

        // Buscar reserva por checkout ID
        $reserva = $this->db->fetch(
            "SELECT * FROM reservas WHERE payment_gateway_transaction_id = :txn_id",
            ['txn_id' => $checkoutId]
        );

        if (!$reserva) {
            $this->logError('Recurrente refund.created: Reserva no encontrada para checkout: ' . $checkoutId);
            return;
        }

        // Actualizar reserva
        $this->db->update('reservas', [
            'estado' => 'reembolsada',
            'payment_status' => 'refunded'
        ], ['id' => $reserva['id']]);

        // Registrar reembolso
        $this->db->insert('pagos', [
            'reserva_id' => $reserva['id'],
            'monto' => -($amount / 100), // Negativo para reembolso
            'metodo' => 'recurrente',
            'estado' => 'completado',
            'referencia_externa' => $refundId,
            'datos_pago' => json_encode([
                'type' => 'refund',
                'webhook' => true,
                'refund_id' => $refundId,
                'checkout_id' => $checkoutId
            ]),
            'fecha_pago' => date('Y-m-d H:i:s')
        ]);

        // Actualizar transacción
        $this->db->query(
            "UPDATE payment_gateway_transactions
             SET status = 'refunded',
                 transaction_type = 'payment_refunded',
                 verified_at = NOW(),
                 webhook_payload = :payload
             WHERE gateway_transaction_id = :txn_id
             AND gateway = 'recurrente'",
            [
                'payload' => json_encode($data),
                'txn_id' => $checkoutId
            ]
        );

        $this->logInfo('Recurrente refund.created: Reserva #' . $reserva['id'] . ' reembolsada');
    }

    /**
     * Log de webhooks recibidos
     */
    private function logWebhook(string $gateway, string $payload, string $signature)
    {
        try {
            $logDir = __DIR__ . '/../../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }

            $logFile = $logDir . '/webhooks.log';
            $logData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'gateway' => $gateway,
                'signature' => substr($signature, 0, 50) . '...',
                'payload' => json_decode($payload, true),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ];

            file_put_contents(
                $logFile,
                json_encode($logData, JSON_PRETTY_PRINT) . PHP_EOL . str_repeat('-', 80) . PHP_EOL,
                FILE_APPEND
            );
        } catch (Exception $e) {
            error_log('Error logging webhook: ' . $e->getMessage());
        }
    }

    /**
     * Log de errores
     */
    private function logError(string $message)
    {
        error_log('[WebhookController] ERROR: ' . $message);
    }

    /**
     * Log de información
     */
    private function logInfo(string $message)
    {
        error_log('[WebhookController] INFO: ' . $message);
    }
}
