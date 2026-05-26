<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Config;
use App\Core\Auth;
use App\Models\RnplPayment;
use DateTime;
use Exception;
use Stripe\Webhook;

class RnplController extends BaseController
{
    private $rnplModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->rnplModel = new RnplPayment();
    }
    
    /**
     * Procesar reserva con RNPL
     */
    public function processBooking()
    {
        // Clean any previous output
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Start fresh output buffering
        ob_start();

        // Set JSON header FIRST before any output
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }

        try {
            // CSRF validation can be added later
            // if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
            //     throw new Exception('Token de seguridad inválido');
            // }

            // Validar datos requeridos
            $required = ['tour_id', 'disponibilidad_id', 'numero_personas', 'cliente_nombre', 'cliente_email', 'cliente_telefono'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Campo requerido: {$field}");
                }
            }
            
            // Obtener datos del tour y disponibilidad
            $tour = $this->getTourData($_POST['tour_id']);
            $disponibilidad = $this->getAvailabilityData($_POST['disponibilidad_id']);
            
            if (!$tour || !$disponibilidad) {
                throw new Exception('Tour o fecha no válida');
            }
            
            // Calcular precios
            $numeroPersonas = (int)$_POST['numero_personas'];
            $precioUnitario = $disponibilidad['precio_especial'] ?: $tour['precio'];
            $precioTotal = $precioUnitario * $numeroPersonas;
            $descuento = $this->calculateDiscount($tour, $numeroPersonas);
            $precioFinal = $precioTotal - $descuento;
            
            // Verificar disponibilidad
            if ($disponibilidad['cupos_disponibles'] < $numeroPersonas) {
                throw new Exception('No hay suficientes cupos disponibles');
            }
            
            // Obtener usuario_id si está logueado
            $usuario_id = null;
            if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
                $usuario_id = $_SESSION['user_id'];
            }

            // Preparar datos de reserva
            $bookingData = [
                'codigo_reserva' => $this->generateBookingCode(),
                'tour_id' => $tour['id'],
                'disponibilidad_id' => $disponibilidad['id'],
                'usuario_id' => $usuario_id,  // Agregar usuario_id si está logueado
                'cliente_nombre' => trim($_POST['cliente_nombre']),
                'cliente_email' => trim($_POST['cliente_email']),
                'cliente_telefono' => trim($_POST['cliente_telefono']),
                'cliente_direccion' => trim($_POST['cliente_direccion'] ?? ''),
                'numero_personas' => $numeroPersonas,
                'fecha_salida' => $disponibilidad['fecha_salida'],
                'fecha_regreso' => $disponibilidad['fecha_regreso'],
                'precio_unitario' => $precioUnitario,
                'precio_total' => $precioTotal,
                'descuento' => $descuento,
                'precio_final' => $precioFinal,
                'notas_cliente' => trim($_POST['notas_cliente'] ?? ''),
                'payment_processor' => 'stripe'
            ];
            
            // Procesar RNPL sin payment_method_id (no hay pago ahora)
            $result = $this->rnplModel->createRnplReservation(
                $bookingData,
                null // Sin método de pago - se pagará después
            );
            
            if ($result['success']) {
                // Actualizar disponibilidad
                $this->updateAvailability($_POST['disponibilidad_id'], $numeroPersonas);
                
                // Clean buffer and send only JSON
                ob_end_clean();

                // Respuesta exitosa
                echo json_encode([
                    'success' => true,
                    'reserva_id' => $result['reserva_id'],
                    'codigo_reserva' => $bookingData['codigo_reserva'],
                    'payment_due_date' => $result['payment_due_date'],
                    'hold_amount' => $result['hold_amount'],
                    'message' => $result['message'],
                    'redirect_url' => Config::getBaseUrl() . '?route=rnpl/confirmation/' . $result['reserva_id']
                ]);
                exit;
            } else {
                throw new Exception($result['error']);
            }

        } catch (Exception $e) {
            // Clean buffer and send only JSON
            if (ob_get_level()) ob_end_clean();

            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
            exit;
        }
    }
    
    /**
     * Mostrar página de confirmación RNPL
     */
    public function confirmation($reservaId = null)
    {
        if (!$reservaId) {
            header('Location: ' . Config::getBaseUrl());
            return;
        }
        
        // Obtener datos de la reserva
        $reserva = $this->getBookingData($reservaId);
        
        if (!$reserva || $reserva['payment_method'] !== 'rnpl') {
            $this->view('errors/404');
            return;
        }
        
        // Calcular tiempo restante
        $dueDate = new DateTime($reserva['payment_due_date']);
        $now = new DateTime();
        $timeRemaining = $this->calculateTimeRemaining($dueDate, $now);
        
        $data = [
            'reserva' => $reserva,
            'tour' => $this->getTourData($reserva['tour_id']),
            'due_date' => $dueDate,
            'time_remaining' => $timeRemaining,
            'is_urgent' => $timeRemaining['hours'] <= 12,
            'is_overdue' => $dueDate <= $now,
            'payment_url' => Config::getBaseUrl() . '?route=rnpl/payment&id=' . $reservaId
        ];
        
        $this->view('booking/rnpl-confirmation', $data);
    }
    
    /**
     * Página de pago final RNPL
     */
    public function payment($reservaId = null)
    {
        if (!$reservaId) {
            header('Location: ' . Config::getBaseUrl());
            return;
        }
        
        $reserva = $this->getBookingData($reservaId);
        
        if (!$reserva || $reserva['payment_method'] !== 'rnpl') {
            $this->view('errors/404');
            return;
        }
        
        if ($reserva['payment_status'] !== 'pending') {
            // Redirigir a página de estado
            header('Location: ' . Config::getBaseUrl() . '?route=booking/status&id=' . $reservaId);
            return;
        }
        
        // Verificar si no está vencido
        $dueDate = new DateTime($reserva['payment_due_date']);
        $now = new DateTime();
        
        if ($now > $dueDate) {
            $graceHours = 2; // Período de gracia
            $graceEnd = (clone $dueDate)->modify("+{$graceHours} hours");
            
            if ($now > $graceEnd) {
                $this->view('booking/rnpl-expired', ['reserva' => $reserva]);
                return;
            }
        }
        
        // Calcular monto final
        $holdAmount = (float)$reserva['rnpl_hold_amount'];
        $finalAmount = (float)$reserva['precio_final'] - $holdAmount;
        
        $data = [
            'reserva' => $reserva,
            'tour' => $this->getTourData($reserva['tour_id']),
            'hold_amount' => $holdAmount,
            'final_amount' => $finalAmount,
            'total_amount' => $reserva['precio_final'],
            'due_date' => $dueDate,
            'stripe_public_key' => Config::STRIPE_PUBLIC_KEY,
            'csrf_token' => $this->generateCsrfToken()
        ];
        
        $this->view('booking/rnpl-payment', $data);
    }
    
    /**
     * Procesar pago final RNPL
     */
    public function processPayment()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Método no permitido']);
            return;
        }
        
        try {
            // Validar CSRF
            if (!$this->validateCsrfToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token de seguridad inválido');
            }
            
            $reservaId = (int)($_POST['reserva_id'] ?? 0);
            $paymentMethodId = $_POST['payment_method_id'] ?? null;
            
            if (!$reservaId || !$paymentMethodId) {
                throw new Exception('Datos de pago incompletos');
            }
            
            // Procesar pago
            $result = $this->rnplModel->processRnplPayment($reservaId, $paymentMethodId);
            
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'],
                    'redirect_url' => Config::getBaseUrl() . '?route=booking/success&id=' . $reservaId
                ]);
            } else {
                throw new Exception($result['error']);
            }
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * API para obtener información RNPL de un tour
     */
    public function getTourRnplInfo($tourId = null)
    {
        if (!$tourId) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de tour requerido']);
            return;
        }
        
        try {
            $tour = $this->getTourData($tourId);
            if (!$tour) {
                throw new Exception('Tour no encontrado');
            }
            
            // Obtener próximas fechas disponibles
            $availability = $this->getTourAvailability($tourId);
            $rnplAvailable = false;
            $minAdvanceHours = 72;
            
            foreach ($availability as $date) {
                $tourDate = new DateTime($date['fecha_salida']);
                $now = new DateTime();
                $hoursDiff = ($tourDate->getTimestamp() - $now->getTimestamp()) / 3600;
                
                if ($hoursDiff >= $minAdvanceHours) {
                    $rnplAvailable = true;
                    break;
                }
            }
            
            // Calcular hold amount
            $holdPercentage = 10;
            $holdAmount = ($tour['precio'] * $holdPercentage) / 100;
            
            echo json_encode([
                'success' => true,
                'rnpl_available' => $rnplAvailable,
                'min_advance_hours' => $minAdvanceHours,
                'payment_window_hours' => 48,
                'hold_percentage' => $holdPercentage,
                'hold_amount' => $holdAmount,
                'benefits' => [
                    'Asegura tu lugar inmediatamente',
                    'Solo paga ' . $holdPercentage . '% ahora',
                    'Completa el pago 48h antes del tour',
                    'Cancelación gratuita hasta 24h antes'
                ]
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Webhook de Stripe para RNPL
     */
    public function stripeWebhook()
    {
        // Implementar webhook handler para eventos de Stripe
        // payment_intent.succeeded, payment_intent.payment_failed, etc.
        
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $endpoint_secret = Config::STRIPE_WEBHOOK_SECRET;
        
        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
            
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event['data']['object']);
                    break;
                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event['data']['object']);
                    break;
            }
            
            http_response_code(200);
            
        } catch (Exception $e) {
            http_response_code(400);
            error_log('Stripe webhook error: ' . $e->getMessage());
        }
    }
    
    // Métodos auxiliares privados
    private function getTourData($tourId) {
        $sql = "SELECT * FROM tours WHERE id = ? AND activo = 1 LIMIT 1";
        return $this->db->fetchOne($sql, [$tourId]);
    }

    private function getAvailabilityData($availabilityId) {
        $sql = "SELECT * FROM disponibilidad WHERE id = ? LIMIT 1";
        return $this->db->fetchOne($sql, [$availabilityId]);
    }

    private function getBookingData($reservaId) {
        $sql = "SELECT * FROM reservas WHERE id = ? LIMIT 1";
        return $this->db->fetchOne($sql, [$reservaId]);
    }

    private function getTourAvailability($tourId) {
        $sql = "SELECT * FROM disponibilidad
                WHERE tour_id = ?
                AND fecha_salida >= CURDATE()
                AND cupos_disponibles > 0
                ORDER BY fecha_salida ASC";
        return $this->db->fetchAll($sql, [$tourId]);
    }

    private function generateBookingCode() {
        return 'RNPL' . strtoupper(substr(uniqid(), -6));
    }

    private function calculateDiscount($tour, $persons) {
        return 0;
    }

    private function updateAvailability($availabilityId, $persons) {
        $sql = "UPDATE disponibilidad
                SET cupos_disponibles = cupos_disponibles - ?
                WHERE id = ? AND cupos_disponibles >= ?";
        return $this->db->query($sql, [$persons, $availabilityId, $persons]);
    }

    private function calculateTimeRemaining($dueDate, $now) {
        $diff = $dueDate->getTimestamp() - $now->getTimestamp();
        return [
            'hours' => max(0, floor($diff / 3600)),
            'minutes' => max(0, floor(($diff % 3600) / 60))
        ];
    }

    private function handlePaymentSucceeded($paymentIntent) {
        // Update booking status when payment succeeds
        $reservaId = $paymentIntent->metadata->reserva_id ?? null;
        if ($reservaId) {
            $sql = "UPDATE reservas SET payment_status = 'completed', estado = 'confirmed' WHERE id = ?";
            $this->db->query($sql, [$reservaId]);
        }
    }

    private function handlePaymentFailed($paymentIntent) {
        // Log payment failure
        $reservaId = $paymentIntent->metadata->reserva_id ?? null;
        if ($reservaId) {
            $sql = "UPDATE reservas SET payment_status = 'failed' WHERE id = ?";
            $this->db->query($sql, [$reservaId]);
        }
    }

    private function validateCsrfToken($token) {
        // Simple CSRF validation - in production use proper CSRF library
        return !empty($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    private function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
