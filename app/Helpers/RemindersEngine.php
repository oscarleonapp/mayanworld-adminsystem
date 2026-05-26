<?php

namespace App\Helpers;

use App\Core\Database;
use App\Core\Config;
use App\Models\Review;
use DateTime;
use Exception;

/**
 * Engine de Recordatorios Automáticos
 * 
 * Sistema para gestionar y enviar recordatorios automáticos para:
 * - Pagos RNPL pendientes
 * - Invitaciones de reviews post-tour
 * - Confirmaciones de reserva
 * - Pagos vencidos
 */
class RemindersEngine
{
    private $db;
    private $config;
    private $logFile;
    private $debugMode;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->loadConfiguration();
        $this->logFile = '../logs/reminders_' . date('Y-m-d') . '.log';
        $this->debugMode = $this->getConfig('debug_mode', 'false') === 'true';
        
        // Crear directorio de logs si no existe
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Ejecutar procesamiento de recordatorios
     * Método principal llamado por cron job
     */
    public function processPendingReminders()
    {
        if (!$this->isEngineEnabled()) {
            $this->log('Engine deshabilitado, saltando procesamiento', 'INFO');
            return;
        }

        $startTime = microtime(true);
        $maxTime = (int)$this->getConfig('max_processing_time_minutes', 10) * 60;
        
        $this->log('Iniciando procesamiento de recordatorios', 'INFO');

        try {
            // Marcar recordatorios expirados
            $this->markExpiredReminders();
            
            // Obtener recordatorios listos para envío
            $reminders = $this->getPendingReminders();
            $batchSize = (int)$this->getConfig('email_batch_size', 50);
            $processed = 0;
            
            foreach (array_chunk($reminders, $batchSize) as $batch) {
                // Verificar tiempo límite
                if ((microtime(true) - $startTime) > $maxTime) {
                    $this->log("Tiempo límite alcanzado, procesados: $processed", 'WARNING');
                    break;
                }
                
                foreach ($batch as $reminder) {
                    try {
                        $result = $this->processReminder($reminder);
                        $processed++;
                        
                        if ($result) {
                            $this->log("Recordatorio #{$reminder['id']} enviado exitosamente", 'INFO');
                        } else {
                            $this->log("Error enviando recordatorio #{$reminder['id']}", 'ERROR');
                        }
                        
                    } catch (Exception $e) {
                        $this->log("Excepción procesando recordatorio #{$reminder['id']}: " . $e->getMessage(), 'ERROR');
                        $this->markReminderFailed($reminder['id'], $e->getMessage());
                    }
                }
                
                // Pequeña pausa entre lotes
                usleep(100000); // 100ms
            }

            $endTime = microtime(true);
            $processingTime = round($endTime - $startTime, 2);
            
            $this->log("Procesamiento completado. Procesados: $processed, Tiempo: {$processingTime}s", 'INFO');
            $this->updateProcessingStats($processed, $processingTime);

        } catch (Exception $e) {
            $this->log("Error crítico en procesamiento: " . $e->getMessage(), 'CRITICAL');
            throw $e;
        }
    }

    /**
     * Programar recordatorio RNPL para una reserva
     */
    public function scheduleRnplReminders($reservaId, $email, $nombre, $fechaTour, $montoPendiente, $tourNombre)
    {
        try {
            $this->db->execute(
                "CALL ProgramarRecordatoriosRNPL(?, ?, ?, ?, ?, ?)",
                [$reservaId, $email, $nombre, $fechaTour, $montoPendiente, $tourNombre]
            );
            
            $this->log("Recordatorios RNPL programados para reserva #$reservaId", 'INFO');
            return true;
            
        } catch (Exception $e) {
            $this->log("Error programando recordatorios RNPL para reserva #$reservaId: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Programar invitación de review post-tour
     */
    public function scheduleReviewInvitation($reservaId, $tourId, $email, $nombre, $fechaTour, $tourNombre)
    {
        try {
            $this->db->execute(
                "CALL ProgramarInvitacionReview(?, ?, ?, ?, ?, ?)",
                [$reservaId, $tourId, $email, $nombre, $fechaTour, $tourNombre]
            );
            
            $this->log("Invitación de review programada para reserva #$reservaId", 'INFO');
            return true;
            
        } catch (Exception $e) {
            $this->log("Error programando invitación de review para reserva #$reservaId: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Programar recordatorio personalizado
     */
    public function scheduleCustomReminder($tipo, $referenciaId, $referenciaTipo, $email, $nombre, $fechaProgramada, $contexto = [], $prioridad = 'media')
    {
        try {
            $contextoJson = json_encode($contexto);
            
            $reminderId = $this->db->insert('scheduled_reminders', [
                'tipo' => $tipo,
                'referencia_id' => $referenciaId,
                'referencia_tipo' => $referenciaTipo,
                'destinatario_email' => $email,
                'destinatario_nombre' => $nombre,
                'contexto' => $contextoJson,
                'fecha_programada' => $fechaProgramada,
                'prioridad' => $prioridad,
                'estado' => 'programado'
            ]);
            
            $this->log("Recordatorio personalizado #$reminderId programado", 'INFO');
            return $reminderId;
            
        } catch (Exception $e) {
            $this->log("Error programando recordatorio personalizado: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Obtener recordatorios pendientes listos para envío
     */
    private function getPendingReminders()
    {
        $sql = "SELECT * FROM recordatorios_pendientes 
                WHERE estado_calculado = 'listo_para_envio' 
                AND intentos_envio < max_intentos
                ORDER BY prioridad DESC, fecha_programada ASC 
                LIMIT ?";
        
        $limit = (int)$this->getConfig('email_batch_size', 50);
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Procesar un recordatorio individual
     */
    private function processReminder($reminder)
    {
        $reminderId = $reminder['id'];
        
        try {
            // Incrementar contador de intentos
            $this->db->update('scheduled_reminders', 
                [
                    'intentos_envio' => $reminder['intentos_envio'] + 1,
                    'fecha_ultimo_intento' => date('Y-m-d H:i:s')
                ],
                ['id' => $reminderId]
            );

            // Preparar contexto del mensaje
            $contexto = json_decode($reminder['contexto'], true) ?? [];
            
            // Determinar template y contenido según tipo
            $emailData = $this->prepareEmailData($reminder, $contexto);
            
            // Enviar email
            $success = $this->sendReminderEmail($reminder, $emailData);
            
            if ($success) {
                // Marcar como enviado
                $this->markReminderSent($reminderId);
                $this->logReminderEvent($reminderId, 'enviado', 'Email enviado exitosamente', $contexto);
                return true;
            } else {
                // Verificar si debe reintentar o marcar como fallido
                if ($reminder['intentos_envio'] >= $reminder['max_intentos'] - 1) {
                    $this->markReminderFailed($reminderId, 'Máximo número de intentos alcanzado');
                } else {
                    // Programar reintento
                    $this->scheduleRetry($reminderId, $reminder['intentos_envio'] + 1);
                }
                return false;
            }
            
        } catch (Exception $e) {
            $this->logReminderEvent($reminderId, 'error', $e->getMessage(), ['exception' => get_class($e)]);
            throw $e;
        }
    }

    /**
     * Preparar datos del email según tipo de recordatorio
     */
    private function prepareEmailData($reminder, $contexto)
    {
        $baseData = [
            'destinatario_nombre' => $reminder['destinatario_nombre'],
            'destinatario_email' => $reminder['destinatario_email']
        ];

        switch ($reminder['tipo']) {
            case 'rnpl_payment':
                return array_merge($baseData, [
                    'subject' => '⏰ Recordatorio: Completa tu pago RNPL - ' . ($contexto['tour_nombre'] ?? 'Tour'),
                    'template' => 'rnpl_payment_reminder',
                    'tour_nombre' => $contexto['tour_nombre'] ?? 'Tour',
                    'fecha_tour' => $contexto['fecha_tour'] ?? null,
                    'monto_pendiente' => $contexto['monto_pendiente'] ?? 0,
                    'moneda' => $contexto['moneda'] ?? 'USD',
                    'payment_url' => Config::getBaseUrl() . '?route=rnpl/payment/' . $contexto['reserva_id'],
                    'days_remaining' => $this->calculateDaysUntilTour($contexto['fecha_tour'] ?? null)
                ]);

            case 'review_invitation':
                return array_merge($baseData, [
                    'subject' => '⭐ ¡Comparte tu experiencia! - ' . ($contexto['tour_nombre'] ?? 'Tour'),
                    'template' => 'review_invitation',
                    'tour_nombre' => $contexto['tour_nombre'] ?? 'Tour',
                    'fecha_tour' => $contexto['fecha_tour'] ?? null,
                    'review_url' => $this->generateReviewInvitationUrl($reminder['referencia_id'])
                ]);

            case 'payment_overdue':
                return array_merge($baseData, [
                    'subject' => '🚨 Pago Vencido - Acción Requerida',
                    'template' => 'payment_overdue',
                    'tour_nombre' => $contexto['tour_nombre'] ?? 'Tour',
                    'monto_pendiente' => $contexto['monto_pendiente'] ?? 0,
                    'grace_period_hours' => $this->getConfig('rnpl_grace_period_hours', 48)
                ]);

            default:
                throw new Exception("Tipo de recordatorio no soportado: {$reminder['tipo']}");
        }
    }

    /**
     * Enviar email de recordatorio
     */
    private function sendReminderEmail($reminder, $emailData)
    {
        // Esta sería la implementación real del envío de email
        // Por ahora simulamos el envío
        
        if ($this->debugMode) {
            $this->log("DEBUG - Email que se enviaría: " . json_encode($emailData), 'DEBUG');
        }
        
        // Aquí iría la integración con servicio de email (SendGrid, Mailgun, etc.)
        // return $this->emailService->send($emailData);
        
        // Simulación: 95% de éxito
        return mt_rand(1, 100) <= 95;
    }

    /**
     * Generar URL de invitación para review
     */
    private function generateReviewInvitationUrl($reservaId)
    {
        // Obtener o crear token de invitación
        $reviewModel = new Review();
        
        // Buscar invitación existente
        $invitation = $this->db->fetch(
            "SELECT token_verificacion FROM review_invitaciones WHERE reserva_id = ?",
            [$reservaId]
        );
        
        if ($invitation) {
            return Config::getBaseUrl() . '?route=review/form&token=' . $invitation['token_verificacion'];
        }
        
        // Si no existe, crear nueva invitación
        // (En un caso real, esto debería hacerse al completar el tour)
        return Config::getBaseUrl() . '?route=review/expired';
    }

    /**
     * Marcar recordatorio como enviado
     */
    private function markReminderSent($reminderId)
    {
        $this->db->update('scheduled_reminders',
            [
                'estado' => 'enviado',
                'fecha_enviado' => date('Y-m-d H:i:s')
            ],
            ['id' => $reminderId]
        );
    }

    /**
     * Marcar recordatorio como fallido
     */
    private function markReminderFailed($reminderId, $error)
    {
        $this->db->update('scheduled_reminders',
            [
                'estado' => 'fallido',
                'mensaje_error' => $error
            ],
            ['id' => $reminderId]
        );
    }

    /**
     * Marcar recordatorios expirados
     */
    private function markExpiredReminders()
    {
        $affected = $this->db->execute(
            "UPDATE scheduled_reminders 
             SET estado = 'expirado' 
             WHERE estado = 'programado' 
             AND fecha_limite IS NOT NULL 
             AND NOW() > fecha_limite"
        );

        if ($affected > 0) {
            $this->log("$affected recordatorios marcados como expirados", 'INFO');
        }
    }

    /**
     * Programar reintento
     */
    private function scheduleRetry($reminderId, $attemptNumber)
    {
        $delayConfig = $this->getConfig('email_retry_delay_minutes', '30,120,360');
        $delays = explode(',', $delayConfig);
        $delayMinutes = isset($delays[$attemptNumber - 1]) ? (int)$delays[$attemptNumber - 1] : 60;
        
        $nextAttempt = date('Y-m-d H:i:s', strtotime("+$delayMinutes minutes"));
        
        $this->db->update('scheduled_reminders',
            ['fecha_programada' => $nextAttempt],
            ['id' => $reminderId]
        );
        
        $this->log("Reintento programado para recordatorio #$reminderId en $delayMinutes minutos", 'INFO');
    }

    /**
     * Calcular días hasta el tour
     */
    private function calculateDaysUntilTour($fechaTour)
    {
        if (!$fechaTour) return null;
        
        $tourDate = new DateTime($fechaTour);
        $now = new DateTime();
        $diff = $now->diff($tourDate);
        
        return $diff->days;
    }

    /**
     * Registrar evento de recordatorio
     */
    private function logReminderEvent($reminderId, $evento, $mensaje, $detalles = [])
    {
        $this->db->insert('reminder_logs', [
            'reminder_id' => $reminderId,
            'evento' => $evento,
            'mensaje' => $mensaje,
            'detalles' => json_encode($detalles)
        ]);
    }

    /**
     * Actualizar estadísticas de procesamiento
     */
    private function updateProcessingStats($processed, $processingTime)
    {
        $today = date('Y-m-d');
        $processingTimeSeconds = round($processingTime);
        
        $this->db->execute(
            "INSERT INTO reminder_estadisticas (
                fecha_proceso, recordatorios_procesados, tiempo_procesamiento_segundos
            ) VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                recordatorios_procesados = recordatorios_procesados + ?,
                tiempo_procesamiento_segundos = tiempo_procesamiento_segundos + ?",
            [$today, $processed, $processingTimeSeconds, $processed, $processingTimeSeconds]
        );
    }

    /**
     * Cargar configuración
     */
    private function loadConfiguration()
    {
        $configRows = $this->db->fetchAll("SELECT clave, valor FROM reminder_configuracion");
        $this->config = [];
        
        foreach ($configRows as $row) {
            $this->config[$row['clave']] = $row['valor'];
        }
    }

    /**
     * Obtener valor de configuración
     */
    private function getConfig($key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Verificar si el engine está habilitado
     */
    private function isEngineEnabled()
    {
        return $this->getConfig('engine_enabled', 'true') === 'true';
    }

    /**
     * Registrar mensaje en log
     */
    private function log($message, $level = 'INFO')
    {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        if ($this->debugMode || $level === 'ERROR' || $level === 'CRITICAL') {
            error_log("RemindersEngine [$level]: $message");
        }
    }

    /**
     * Obtener estadísticas del engine
     */
    public function getStats($days = 7)
    {
        $startDate = date('Y-m-d', strtotime("-$days days"));
        
        return $this->db->fetchAll(
            "SELECT * FROM reminder_estadisticas 
             WHERE fecha_proceso >= ? 
             ORDER BY fecha_proceso DESC",
            [$startDate]
        );
    }

    /**
     * Limpiar recordatorios antiguos
     */
    public function cleanup($daysOld = 90)
    {
        $cutoffDate = date('Y-m-d', strtotime("-$daysOld days"));
        
        // Eliminar recordatorios completados antiguos
        $deletedReminders = $this->db->execute(
            "DELETE FROM scheduled_reminders 
             WHERE estado IN ('enviado', 'fallido', 'cancelado', 'expirado') 
             AND created_at < ?",
            [$cutoffDate]
        );

        // Eliminar logs antiguos
        $deletedLogs = $this->db->execute(
            "DELETE FROM reminder_logs WHERE created_at < ?",
            [$cutoffDate]
        );

        $this->log("Cleanup completado: $deletedReminders recordatorios y $deletedLogs logs eliminados", 'INFO');
        
        return ['reminders' => $deletedReminders, 'logs' => $deletedLogs];
    }
}
