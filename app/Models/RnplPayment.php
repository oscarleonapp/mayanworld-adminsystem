<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use App\Core\Config;
use DateTime;
use Exception;
use Stripe\StripeClient;

class RnplPayment extends Model
{
    protected $table = 'payment_transactions';
    
    /**
     * Crear una reserva RNPL con autorización inicial
     */
    public function createRnplReservation($bookingData, $paymentMethodId = null)
    {
        try {
            $this->db->beginTransaction();
            
            // Validar que RNPL esté habilitado
            if (!$this->isRnplEnabled()) {
                throw new Exception('Sistema RNPL no está disponible actualmente');
            }
            
            // Validar tiempo mínimo de anticipación
            $minHours = $this->getRnplSetting('min_advance_booking_hours', 72);
            $tourDateTime = new DateTime($bookingData['fecha_salida']);
            $now = new DateTime();
            $hoursDiff = ($tourDateTime->getTimestamp() - $now->getTimestamp()) / 3600;
            
            if ($hoursDiff < $minHours) {
                throw new Exception("Para usar 'Reserva Ahora, Paga Después' necesitas reservar con al menos {$minHours} horas de anticipación");
            }
            
            // Calcular fechas
            $paymentWindow = (int)$this->getRnplSetting('payment_window_hours', 48);
            $paymentDueDate = (clone $tourDateTime)->modify("-{$paymentWindow} hours");
            
            // Si la fecha de pago ya pasó, usar pago inmediato
            if ($paymentDueDate <= $now) {
                throw new Exception('El tiempo para usar RNPL ha expirado. Usa pago inmediato.');
            }
            
            // Calcular hold amount (porcentaje del total)
            $holdPercentage = (int)$this->getRnplSetting('hold_amount_percentage', 10);
            $holdAmount = ($bookingData['precio_final'] * $holdPercentage) / 100;
            
            // Crear reserva con datos RNPL
            $reservaData = array_merge($bookingData, [
                'payment_method' => 'rnpl',
                'payment_status' => 'pending',
                'payment_due_date' => $paymentDueDate->format('Y-m-d H:i:s'),
                'rnpl_hold_amount' => $holdAmount,
                'estado' => 'confirmada' // RNPL reservas se confirman inmediatamente
            ]);
            
            // Insertar reserva
            $reservaId = $this->db->insert('reservas', $reservaData);

            // NO crear autorización de pago - el pago se hará después
            // Solo guardar que es RNPL y cuándo debe pagar

            $this->db->commit();

            // Enviar confirmación RNPL (email puede ser implementado después)
            // $this->sendRnplConfirmation($reservaId);
            
            return [
                'success' => true,
                'reserva_id' => $reservaId,
                'payment_due_date' => $paymentDueDate->format('Y-m-d H:i:s'),
                'hold_amount' => $holdAmount,
                'message' => 'Reserva confirmada. Completar pago antes de ' . $paymentDueDate->format('d/m/Y H:i')
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Procesar pago final de reserva RNPL
     */
    public function processRnplPayment($reservaId, $paymentMethodId = null)
    {
        try {
            $this->db->beginTransaction();
            
            // Obtener datos de reserva
            $reserva = $this->db->query(
                "SELECT * FROM reservas WHERE id = ? AND payment_method = 'rnpl'", 
                [$reservaId]
            )[0] ?? null;
            
            if (!$reserva) {
                throw new Exception('Reserva RNPL no encontrada');
            }
            
            if ($reserva['payment_status'] !== 'pending') {
                throw new Exception('Esta reserva ya ha sido procesada');
            }
            
            // Verificar si no está vencida
            $dueDate = new DateTime($reserva['payment_due_date']);
            $now = new DateTime();
            
            if ($now > $dueDate) {
                $graceHours = (int)$this->getRnplSetting('grace_period_hours', 2);
                $graceEnd = (clone $dueDate)->modify("+{$graceHours} hours");
                
                if ($now > $graceEnd) {
                    throw new Exception('El tiempo para completar el pago ha expirado');
                }
            }
            
            // Procesar pago completo
            $finalAmount = $reserva['precio_final'] - $reserva['rnpl_hold_amount'];
            
            if ($paymentMethodId) {
                $paymentResult = $this->processStripePayment($reservaId, $finalAmount, $paymentMethodId);
                
                if (!$paymentResult['success']) {
                    throw new Exception('Error procesando pago: ' . $paymentResult['error']);
                }
            }
            
            // Actualizar reserva
            $this->db->update('reservas', [
                'payment_status' => 'captured',
                'estado' => 'pagada',
                'metodo_pago' => 'stripe_rnpl'
            ], ['id' => $reservaId]);
            
            // Cancelar recordatorios pendientes
            $this->db->update('payment_reminders', [
                'status' => 'canceled'
            ], ['reserva_id' => $reservaId, 'status' => 'pending']);
            
            $this->db->commit();
            
            // Enviar confirmación de pago
            $this->sendPaymentConfirmation($reservaId);
            
            return [
                'success' => true,
                'message' => 'Pago completado exitosamente'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener reservas RNPL pendientes
     */
    public function getPendingRnplPayments($limit = 50)
    {
        return $this->db->query(
            "SELECT * FROM rnpl_pending_payments 
             ORDER BY urgency_level DESC, hours_remaining ASC 
             LIMIT ?", 
            [$limit]
        );
    }
    
    /**
     * Procesar recordatorios de pago
     */
    public function processPaymentReminders()
    {
        $now = new DateTime();
        
        // Obtener recordatorios pendientes
        $reminders = $this->db->query(
            "SELECT pr.*, r.cliente_email, r.cliente_nombre, r.codigo_reserva, p.nombre as tour_nombre
             FROM payment_reminders pr
             JOIN reservas r ON pr.reserva_id = r.id
             JOIN tours p ON r.tour_id = p.id
             WHERE pr.status = 'pending' 
               AND pr.scheduled_at <= ?
             ORDER BY pr.scheduled_at ASC",
            [$now->format('Y-m-d H:i:s')]
        );
        
        foreach ($reminders as $reminder) {
            $this->sendPaymentReminder($reminder);
        }
    }
    
    /**
     * Auto-cancelar reservas RNPL vencidas
     */
    public function cancelOverdueRnpl()
    {
        if (!$this->getRnplSetting('auto_cancel_overdue', true)) {
            return;
        }
        
        $graceHours = (int)$this->getRnplSetting('grace_period_hours', 2);
        $cutoffTime = (new DateTime())->modify("-{$graceHours} hours");
        
        $overdueReservas = $this->db->query(
            "SELECT id, codigo_reserva, cliente_email 
             FROM reservas 
             WHERE payment_method = 'rnpl' 
               AND payment_status = 'pending'
               AND payment_due_date < ?",
            [$cutoffTime->format('Y-m-d H:i:s')]
        );
        
        foreach ($overdueReservas as $reserva) {
            $this->cancelRnplReservation($reserva['id'], 'Pago no completado en el tiempo establecido');
        }
        
        return count($overdueReservas);
    }
    
    /**
     * Crear autorización de pago (hold)
     */
    private function createPaymentAuthorization($reservaId, $amount, $paymentMethodId)
    {
        // Integración con Stripe para crear authorization
        $stripe = new \Stripe\StripeClient(Config::STRIPE_SECRET_KEY);
        
        try {
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => (int)($amount * 100), // Convertir a centavos
                'currency' => 'usd',
                'payment_method' => $paymentMethodId,
                'confirmation_method' => 'manual',
                'capture_method' => 'manual', // Solo autorizar, no capturar
                'confirm' => true,
                'metadata' => [
                    'reserva_id' => $reservaId,
                    'type' => 'rnpl_hold'
                ]
            ]);
            
            // Guardar transacción
            $this->db->insert('payment_transactions', [
                'reserva_id' => $reservaId,
                'transaction_type' => 'authorization',
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'status' => $paymentIntent->status,
                'processor_response' => json_encode($paymentIntent->toArray()),
                'processed_at' => date('Y-m-d H:i:s')
            ]);
            
            return $paymentIntent;
            
        } catch (Exception $e) {
            error_log("RNPL Authorization Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Procesar pago con Stripe
     */
    private function processStripePayment($reservaId, $amount, $paymentMethodId)
    {
        try {
            $stripe = new \Stripe\StripeClient(Config::STRIPE_SECRET_KEY);
            
            $paymentIntent = $stripe->paymentIntents->create([
                'amount' => (int)($amount * 100),
                'currency' => 'usd',
                'payment_method' => $paymentMethodId,
                'confirm' => true,
                'metadata' => [
                    'reserva_id' => $reservaId,
                    'type' => 'rnpl_final_payment'
                ]
            ]);
            
            // Guardar transacción
            $this->db->insert('payment_transactions', [
                'reserva_id' => $reservaId,
                'transaction_type' => 'capture',
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'status' => $paymentIntent->status,
                'processor_response' => json_encode($paymentIntent->toArray()),
                'processed_at' => date('Y-m-d H:i:s')
            ]);
            
            return ['success' => true, 'payment_intent' => $paymentIntent];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Verificar si RNPL está habilitado
     */
    private function isRnplEnabled()
    {
        return $this->getRnplSetting('rnpl_enabled', true) === 'true';
    }
    
    /**
     * Obtener configuración RNPL
     */
    private function getRnplSetting($key, $default = null)
    {
        $result = $this->db->fetchOne(
            "SELECT setting_value FROM rnpl_settings WHERE setting_key = ? AND is_active = 1",
            [$key]
        );

        return $result['setting_value'] ?? $default;
    }
    
    /**
     * Enviar confirmación RNPL
     */
    private function sendRnplConfirmation($reservaId)
    {
        // Implementar envío de email de confirmación RNPL
        // Incluir: fecha de pago, instrucciones, links de pago
    }
    
    /**
     * Enviar recordatorio de pago
     */
    private function sendPaymentReminder($reminder)
    {
        try {
            // Implementar envío de recordatorio
            // Email + WhatsApp si está configurado
            
            // Marcar como enviado
            $this->db->update('payment_reminders', [
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s'),
                'email_sent' => true
            ], ['id' => $reminder['id']]);
            
        } catch (Exception $e) {
            // Marcar como fallido
            $this->db->update('payment_reminders', [
                'status' => 'failed'
            ], ['id' => $reminder['id']]);
            
            error_log("Payment reminder failed: " . $e->getMessage());
        }
    }
    
    /**
     * Enviar confirmación de pago
     */
    private function sendPaymentConfirmation($reservaId)
    {
        // Implementar envío de confirmación final
    }
    
    /**
     * Cancelar reserva RNPL
     */
    private function cancelRnplReservation($reservaId, $reason)
    {
        $this->db->update('reservas', [
            'estado' => 'cancelada',
            'payment_status' => 'failed',
            'notas_admin' => $reason
        ], ['id' => $reservaId]);
        
        // Enviar notificación de cancelación
    }
}