<?php
/**
 * Cron Job: Verificar Pagos de Paggo
 *
 * Este script verifica el estado de los pagos pendientes de Paggo.
 * Paggo no tiene webhooks, por lo que usamos polling para verificar
 * el estado de los pagos.
 *
 * Configurar en crontab para ejecutar cada 5 minutos:
 * */5 * * * * php /ruta/completa/travel-agency-mvp/cron/verify-paggo-payments.php >> /ruta/logs/paggo-cron.log 2>&1
 *
 * O en Windows Task Scheduler:
 * Trigger: Cada 5 minutos
 * Action: php.exe "C:\path\to\travel-agency-mvp\cron\verify-paggo-payments.php"
 */

// Asegurar que solo se ejecute desde CLI
if (php_sapi_name() !== 'cli') {
    die('Este script solo puede ejecutarse desde la línea de comandos');
}

// Cargar autoloader
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Core\PaymentGatewayFactory;

// Función de log
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] [$level] $message" . PHP_EOL;
}

try {
    logMessage('========== INICIO VERIFICACIÓN DE PAGOS PAGGO ==========');

    // Obtener instancia de base de datos
    $db = Database::getInstance();

    // Obtener verificaciones pendientes que deben ser procesadas
    $pendingVerifications = $db->fetchAll(
        "SELECT * FROM payment_verification_queue
         WHERE status IN ('pending', 'verifying')
         AND next_verification_at <= NOW()
         AND link_expires_at > NOW()
         ORDER BY created_at ASC
         LIMIT 50"
    );

    if (empty($pendingVerifications)) {
        logMessage('No hay verificaciones pendientes');
    } else {
        logMessage('Procesando ' . count($pendingVerifications) . ' verificación(es) pendiente(s)');
    }

    // Crear gateway de Paggo
    $gateway = PaymentGatewayFactory::create('paggo');

    $successCount = 0;
    $failCount = 0;
    $pendingCount = 0;

    foreach ($pendingVerifications as $verification) {
        $reservaId = $verification['reserva_id'];
        $transactionId = $verification['gateway_transaction_id'];
        $attempts = (int)$verification['verification_attempts'];

        logMessage("Verificando reserva #{$reservaId}, transacción: {$transactionId}, intento: " . ($attempts + 1));

        // Marcar como verificando
        $db->update('payment_verification_queue', [
            'status' => 'verifying',
            'verification_attempts' => $attempts + 1,
            'last_verification_at' => date('Y-m-d H:i:s')
        ], ['id' => $verification['id']]);

        try {
            // Verificar pago con Paggo
            $result = $gateway->verifyPayment($transactionId);

            // Guardar respuesta del gateway
            $db->update('payment_verification_queue', [
                'last_gateway_response' => json_encode($result)
            ], ['id' => $verification['id']]);

            if ($result['paid']) {
                // ¡Pago confirmado!
                logMessage("✓ Pago confirmado para reserva #{$reservaId}");

                // Actualizar reserva
                $db->update('reservas', [
                    'estado' => 'pagada',
                    'payment_status' => 'captured',
                    'fecha_pago' => date('Y-m-d H:i:s')
                ], ['id' => $reservaId]);

                // Obtener datos de la reserva para registrar pago
                $reserva = $db->fetch("SELECT * FROM reservas WHERE id = :id", ['id' => $reservaId]);

                if ($reserva) {
                    // Registrar pago
                    $db->insert('pagos', [
                        'reserva_id' => $reservaId,
                        'monto' => $reserva['precio_total'],
                        'metodo' => 'paggo',
                        'estado' => 'completado',
                        'referencia_externa' => $transactionId,
                        'datos_pago' => json_encode([
                            'cron_verification' => true,
                            'verified_at' => date('Y-m-d H:i:s')
                        ]),
                        'fecha_pago' => date('Y-m-d H:i:s')
                    ]);
                }

                // Actualizar transacción en payment_gateway_transactions
                $db->query(
                    "UPDATE payment_gateway_transactions
                     SET status = 'captured',
                         transaction_type = 'payment_completed',
                         verified_at = NOW(),
                         response_payload = :payload
                     WHERE gateway_transaction_id = :txn_id
                     AND gateway = 'paggo'",
                    [
                        'payload' => json_encode($result),
                        'txn_id' => $transactionId
                    ]
                );

                // Marcar verificación como completada
                $db->update('payment_verification_queue', [
                    'status' => 'completed'
                ], ['id' => $verification['id']]);

                $successCount++;

                // TODO: Enviar email de confirmación

            } else {
                // Pago aún pendiente, programar siguiente verificación
                logMessage("○ Pago aún pendiente para reserva #{$reservaId}");

                // Calcular siguiente intervalo (exponential backoff)
                $newAttempts = $attempts + 1;
                $intervals = [5, 15, 30, 60, 120, 240, 360, 720]; // minutos
                $nextInterval = $intervals[min($newAttempts - 1, count($intervals) - 1)];

                $nextVerification = date('Y-m-d H:i:s', strtotime("+{$nextInterval} minutes"));

                $db->update('payment_verification_queue', [
                    'status' => 'pending',
                    'next_verification_at' => $nextVerification
                ], ['id' => $verification['id']]);

                logMessage("  → Próxima verificación en {$nextInterval} minutos ({$nextVerification})");

                $pendingCount++;
            }

        } catch (Exception $e) {
            // Error al verificar
            logMessage("✗ Error al verificar reserva #{$reservaId}: " . $e->getMessage(), 'ERROR');

            // Programar reintento
            $nextVerification = date('Y-m-d H:i:s', strtotime('+10 minutes'));
            $db->update('payment_verification_queue', [
                'status' => 'pending',
                'next_verification_at' => $nextVerification,
                'last_gateway_response' => json_encode(['error' => $e->getMessage()])
            ], ['id' => $verification['id']]);

            $failCount++;
        }
    }

    // Marcar verificaciones expiradas
    $expiredCount = $db->execute(
        "UPDATE payment_verification_queue
         SET status = 'expired'
         WHERE status IN ('pending', 'verifying')
         AND link_expires_at <= NOW()"
    );

    if ($expiredCount > 0) {
        logMessage("Links expirados: {$expiredCount}");

        // Marcar reservas con links expirados como canceladas
        $db->execute(
            "UPDATE reservas r
             INNER JOIN payment_verification_queue pvq ON r.id = pvq.reserva_id
             SET r.estado = 'cancelada',
                 r.payment_status = 'expired'
             WHERE pvq.status = 'expired'
             AND r.estado = 'pendiente'"
        );
    }

    // Resumen
    logMessage('---------- RESUMEN ----------');
    logMessage("Pagos confirmados: {$successCount}");
    logMessage("Aún pendientes: {$pendingCount}");
    logMessage("Errores: {$failCount}");
    logMessage("Expirados: {$expiredCount}");
    logMessage('========== FIN VERIFICACIÓN ==========');

} catch (Exception $e) {
    logMessage('ERROR FATAL: ' . $e->getMessage(), 'FATAL');
    logMessage('Stack trace: ' . $e->getTraceAsString(), 'DEBUG');
    exit(1);
}

exit(0);
