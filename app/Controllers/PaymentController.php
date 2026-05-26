<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Config;
use App\Core\Helpers;
use App\Core\PaymentGatewayFactory;
use App\Models\Booking;
use App\Models\Tour;
use Exception;

class PaymentController extends BaseController
{
    private $bookingModel;
    private $tourModel;

    public function __construct()
    {
        parent::__construct();
        $this->bookingModel = new Booking();
        $this->tourModel = new Tour();
    }

    /**
     * Procesar pago con gateway seleccionado
     * Punto de entrada unificado para Paggo y Recurrente
     * (Stripe y RNPL tienen sus propios flujos)
     */
    public function process()
    {
        if (!isset($_SESSION['checkout_data'])) {
            $this->redirect('booking/checkout-step1', 'Sesión expirada', 'error');
            return;
        }

        $gateway = $this->getInput('gateway');
        if (!in_array($gateway, ['paggo', 'recurrente'])) {
            $this->redirect('booking/checkout-step2', 'Método de pago inválido', 'error');
            return;
        }

        try {
            // Crear reserva primero
            $checkoutData = $_SESSION['checkout_data'];
            $reservaId = $this->createReservation($checkoutData, $gateway);

            // Procesar según gateway
            switch ($gateway) {
                case 'paggo':
                    $this->processPaggo($reservaId, $checkoutData);
                    break;
                case 'recurrente':
                    $this->processRecurrente($reservaId, $checkoutData);
                    break;
            }
        } catch (Exception $e) {
            error_log('Payment error: ' . $e->getMessage());
            $this->redirect('booking/checkout-step2', 'Error al procesar el pago: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Crear reserva en base de datos
     */
    private function createReservation(array $data, string $gateway): int
    {
        $tour = $this->tourModel->find($data['tour_id']);
        $total = $data['precio_unitario'] * $data['numero_personas'];

        return $this->db->insert('reservas', [
            'codigo_reserva' => $this->bookingModel->generateBookingCode(),
            'tour_id' => $data['tour_id'],
            'cliente_nombre' => $data['cliente_nombre'],
            'cliente_email' => $data['cliente_email'],
            'cliente_telefono' => $data['cliente_telefono'],
            'cliente_pais' => $data['cliente_pais'] ?? null,
            'fecha_tour' => $data['fecha_tour'],
            'numero_personas' => $data['numero_personas'],
            'precio_unitario' => $data['precio_unitario'],
            'precio_total' => $total,
            'notas_especiales' => $data['notas_especiales'] ?? null,
            'estado' => 'pendiente',
            'payment_processor' => $gateway,
            'payment_status' => 'pending',
            'fecha_reserva' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Procesar pago con Paggo
     */
    private function processPaggo(int $reservaId, array $data)
    {
        $gateway = PaymentGatewayFactory::create('paggo');
        $tour = $this->tourModel->find($data['tour_id']);
        $total = $data['precio_unitario'] * $data['numero_personas'];

        $result = $gateway->createPayment([
            'reserva_id' => $reservaId,
            'amount' => $total,
            'customer_name' => $data['cliente_nombre'],
            'customer_email' => $data['cliente_email'],
            'concept' => 'Reserva: ' . $tour['nombre']
        ]);

        if (!$result['success']) {
            throw new Exception($result['error']);
        }

        // Actualizar reserva con link de pago
        $this->db->update('reservas', [
            'payment_link_url' => $result['payment_link'],
            'payment_link_expires_at' => $result['expires_at'],
            'payment_gateway_transaction_id' => $result['transaction_id']
        ], ['id' => $reservaId]);

        unset($_SESSION['checkout_data']);
        $this->redirect('payment/pending/' . $reservaId);
    }

    /**
     * Procesar pago con Recurrente
     */
    private function processRecurrente(int $reservaId, array $data)
    {
        $gateway = PaymentGatewayFactory::create('recurrente');
        $tour = $this->tourModel->find($data['tour_id']);
        $total = $data['precio_unitario'] * $data['numero_personas'];

        $result = $gateway->createPayment([
            'reserva_id' => $reservaId,
            'amount' => $total,
            'currency' => 'USD',
            'tour_name' => $tour['nombre'],
            'tour_image' => $tour['imagen_principal'] ?? '',
            'customer_email' => $data['cliente_email'],
            'success_url' => Config::getBaseUrl() . '?route=payment/success-recurrente&reserva=' . $reservaId,
            'cancel_url' => Config::getBaseUrl() . '?route=payment/cancel&reserva=' . $reservaId
        ]);

        if (!$result['success']) {
            throw new Exception($result['error']);
        }

        // Actualizar reserva con checkout ID
        $this->db->update('reservas', [
            'payment_link_url' => $result['payment_link'],
            'payment_gateway_transaction_id' => $result['transaction_id']
        ], ['id' => $reservaId]);

        unset($_SESSION['checkout_data']);

        // Redireccionar directamente a Recurrente
        header('Location: ' . $result['payment_link']);
        exit;
    }

    /**
     * Página de pago pendiente (Paggo)
     */
    public function pending($reservaId = null)
    {
        if (!$reservaId || !is_numeric($reservaId)) {
            $this->redirect('home', 'Reserva inválida', 'error');
            return;
        }

        $reserva = $this->db->fetch(
            "SELECT r.*, p.nombre as tour_nombre, p.imagen_principal
             FROM reservas r
             LEFT JOIN tours p ON r.tour_id = p.id
             WHERE r.id = :id",
            ['id' => $reservaId]
        );

        if (!$reserva) {
            $this->redirect('home', 'Reserva no encontrada', 'error');
            return;
        }

        // Si ya está pagada, redirigir a confirmación
        if ($reserva['estado'] === 'pagada') {
            $this->redirect('booking/confirmation&code=' . $reserva['codigo_reserva'],
                'Esta reserva ya fue pagada', 'success');
            return;
        }

        $this->view('payment/pending', [
            'title' => 'Pago Pendiente',
            'reserva' => $reserva
        ]);
    }

    /**
     * Callback de éxito de Recurrente
     */
    public function successRecurrente()
    {
        $reservaId = $_GET['reserva'] ?? null;
        if (!$reservaId || !is_numeric($reservaId)) {
            $this->redirect('home', 'Reserva inválida', 'error');
            return;
        }

        $reserva = $this->db->fetch(
            "SELECT r.*, p.nombre as tour_nombre
             FROM reservas r
             LEFT JOIN tours p ON r.tour_id = p.id
             WHERE r.id = :id",
            ['id' => $reservaId]
        );

        if (!$reserva) {
            $this->redirect('home', 'Reserva no encontrada', 'error');
            return;
        }

        $this->view('payment/success-recurrente', [
            'title' => 'Pago Recibido',
            'reserva' => $reserva
        ]);
    }

    // Webhook de Stripe para confirmar pagos asíncronos
    public function webhook()
    {
        // Leer cuerpo crudo y cabecera de firma
        $payload = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $secret = Config::STRIPE_WEBHOOK_SECRET;

        // Validación básica de firma (t=v1= estilo Stripe)
        if (!$this->verifyStripeSignature($payload, $sigHeader, $secret)) {
            http_response_code(400);
            echo json_encode(['error' => 'Firma inválida']);
            exit;
        }

        $event = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'JSON inválido']);
            exit;
        }

        $type = $event['type'] ?? '';
        switch ($type) {
            case 'checkout.session.completed':
                $session = $event['data']['object'] ?? [];
                if (($session['payment_status'] ?? '') === 'paid') {
                    $bookingId = $session['metadata']['booking_id'] ?? null;
                    $paymentType = $session['metadata']['payment_type'] ?? 'full';
                    if ($bookingId) {
                        $amount = (float)($session['amount_total'] ?? 0) / 100.0;
                        $this->db->insert('pagos', [
                            'reserva_id' => (int)$bookingId,
                            'monto' => $amount,
                            'metodo' => 'tarjeta',
                            'estado' => 'completado',
                            'referencia_externa' => $session['id'] ?? null,
                            'datos_pago' => json_encode(['type' => $paymentType, 'webhook' => true])
                        ]);
                        $bookingRow = $this->bookingModel->find((int)$bookingId);
                        $paidRow = $this->db->fetch("SELECT COALESCE(SUM(monto),0) AS paid FROM pagos WHERE reserva_id = :rid AND estado = 'completado'", ['rid' => $bookingId]);
                        $alreadyPaid = (float)($paidRow['paid'] ?? 0);
                        $newStatus = ($alreadyPaid + 0.01) >= (float)$bookingRow['precio_total'] ? Booking::STATUS_PAID : Booking::STATUS_CONFIRMED;
                        $this->bookingModel->updateStatus((int)$bookingId, $newStatus);
                    }
                }
                break;
            case 'payment_intent.succeeded':
                // Opcional: manejar si usas Payment Intents directos
                break;
            default:
                // Otros eventos no usados ahora
                break;
        }

        http_response_code(200);
        echo json_encode(['received' => true]);
        exit;
    }

    private function verifyStripeSignature($payload, $sigHeader, $secret)
    {
        if (empty($secret) || strpos($secret, 'whsec_') !== 0) {
            // Si no está configurado, aceptar solo en desarrollo
            return Config::isDevelopment();
        }
        if (!$sigHeader) return false;
        // Cabecera: t=timestamp,v1=signature
        $parts = [];
        foreach (explode(',', $sigHeader) as $kv) {
            [$k, $v] = array_map('trim', explode('=', $kv, 2));
            $parts[$k] = $v;
        }
        if (empty($parts['t']) || empty($parts['v1'])) return false;
        $signedPayload = $parts['t'] . '.' . $payload;
        $computed = hash_hmac('sha256', $signedPayload, $secret);
        // Tiempo seguro
        return hash_equals($computed, $parts['v1']);
    }
    // Crear sesión de Stripe Checkout y redirigir
    public function checkout($bookingId)
    {
        if (!$bookingId || !is_numeric($bookingId)) {
            $this->redirect('tours', 'Reserva inválida', 'error');
            return;
        }

        $booking = $this->bookingModel->getWithTour($bookingId);
        if (!$booking) {
            $this->redirect('tours', 'Reserva no encontrada', 'error');
            return;
        }

        if (empty(Config::STRIPE_SECRET_KEY) || strpos(Config::STRIPE_SECRET_KEY, 'sk_') !== 0) {
            $this->redirect('booking/checkout', 'Stripe no configurado. Define STRIPE_SECRET_KEY', 'error');
            return;
        }

        $paymentType = $_GET['type'] ?? 'full';
        $total = (float)$booking['precio_total'];
        // Calcular monto pagado previamente
        $paidRow = $this->db->fetch("SELECT COALESCE(SUM(monto),0) AS paid FROM pagos WHERE reserva_id = :rid AND estado = 'completado'", ['rid' => $booking['id']]);
        $alreadyPaid = (float)($paidRow['paid'] ?? 0);
        $amount = $total;
        if ($paymentType === 'deposit') {
            $amount = max(1, $total * Config::DEPOSIT_RATE);
        } elseif ($paymentType === 'balance') {
            $amount = max(0.0, $total - $alreadyPaid);
        }
        $amountCents = (int) round($amount * 100);
        $productName = 'Reserva: ' . ($booking['tour_nombre'] ?? 'Tour');
        $productDesc = 'Código: ' . $booking['codigo_reserva'] . ' • Personas: ' . $booking['numero_personas'] . ' • ' . ($paymentType === 'deposit' ? 'Anticipo ' . (int)(Config::DEPOSIT_RATE*100) . '%' : 'Pago total');
        $imageUrl = $booking['imagen_principal'] ? $booking['imagen_principal'] : Helpers::asset('images/hero-travel.jpg');

        // Construir payload x-www-form-urlencoded
        $payload = [
            'mode' => 'payment',
            'success_url' => Config::getBaseUrl() . '?route=payment/success&session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => Config::getBaseUrl() . '?route=payment/cancel&code=' . urlencode($booking['codigo_reserva']),
            'customer_email' => $booking['cliente_email'],
            'metadata[booking_id]' => $booking['id'],
            'metadata[booking_code]' => $booking['codigo_reserva'],
            'metadata[payment_type]' => $paymentType,
        ];

        // Line item (Price data)
        $payload['line_items[0][price_data][currency]'] = 'usd';
        $payload['line_items[0][price_data][unit_amount]'] = $amountCents;
        $payload['line_items[0][price_data][product_data][name]'] = $productName;
        $payload['line_items[0][price_data][product_data][description]'] = $productDesc;
        $payload['line_items[0][price_data][product_data][images][0]'] = $imageUrl;
        $payload['line_items[0][quantity]'] = 1;

        $response = $this->stripeRequest('POST', 'https://api.stripe.com/v1/checkout/sessions', $payload);
        if (!$response || empty($response['url'])) {
            $this->redirect('booking/checkout', 'No se pudo iniciar el pago con tarjeta', 'error');
            return;
        }

        header('Location: ' . $response['url']);
        exit;
    }

    // Éxito de Stripe: verificar y actualizar reserva
    public function success()
    {
        $sessionId = $_GET['session_id'] ?? null;
        if (!$sessionId) {
            $this->redirect('home', 'Pago inválido', 'error');
            return;
        }

        $session = $this->stripeRequest('GET', 'https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId));
        if (!$session || ($session['payment_status'] ?? '') !== 'paid') {
            $this->redirect('booking/checkout', 'El pago no se ha completado', 'warning');
            return;
        }

        $bookingId = $session['metadata']['booking_id'] ?? null;
        $bookingCode = $session['metadata']['booking_code'] ?? null;
        $paymentType = $session['metadata']['payment_type'] ?? 'full';
        if ($bookingId) {
            // Registrar pago y actualizar estado según saldo
            $amount = (float)($session['amount_total'] ?? 0) / 100.0;
            $this->db->insert('pagos', [
                'reserva_id' => (int)$bookingId,
                'monto' => $amount,
                'metodo' => 'tarjeta',
                'estado' => 'completado',
                'referencia_externa' => $sessionId,
                'datos_pago' => json_encode(['type' => $paymentType])
            ]);
            $bookingRow = $this->bookingModel->find((int)$bookingId);
            $paidRow = $this->db->fetch("SELECT COALESCE(SUM(monto),0) AS paid FROM pagos WHERE reserva_id = :rid AND estado = 'completado'", ['rid' => $bookingId]);
            $alreadyPaid = (float)($paidRow['paid'] ?? 0);
            $newStatus = ($alreadyPaid + 0.01) >= (float)$bookingRow['precio_total'] ? Booking::STATUS_PAID : Booking::STATUS_CONFIRMED;
            $this->bookingModel->updateStatus((int)$bookingId, $newStatus);
        }

        $code = $bookingCode ?: ($bookingId ? ($this->bookingModel->find($bookingId)['codigo_reserva'] ?? null) : null);
        if ($code) {
            $this->redirect('booking/confirmation&code=' . urlencode($code), 'Pago realizado con éxito', 'success');
        } else {
            $this->redirect('home', 'Pago realizado con éxito', 'success');
        }
    }

    // Cancelado en Stripe
    public function cancel()
    {
        $code = $_GET['code'] ?? null;
        if ($code) {
            $this->redirect('booking/confirm?code=' . urlencode($code), 'Pago cancelado. Puedes intentar nuevamente.', 'warning');
        } else {
            $this->redirect('booking/checkout', 'Pago cancelado. Puedes intentar nuevamente.', 'warning');
        }
    }

    private function stripeRequest($method, $url, $data = null)
    {
        $ch = curl_init();
        $headers = [
            'Authorization: Bearer ' . Config::STRIPE_SECRET_KEY,
        ];
        
        if ($method === 'GET' && !empty($data)) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($method !== 'GET' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }

        $result = curl_exec($ch);
        if ($result === false) {
            curl_close($ch);
            return null;
        }
        curl_close($ch);
        
        $decoded = json_decode($result, true);
        return $decoded;
    }
}
