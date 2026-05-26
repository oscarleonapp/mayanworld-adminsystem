<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Core\Config;
use App\Core\Auth;
use App\Models\Booking;
use App\Models\Tour;
use App\Models\Availability;
use App\Controllers\RnplController;
use DateTime;
use Exception;

class BookingController extends BaseController
{
    private $bookingModel;
    private $tourModel;
    private $availabilityModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->bookingModel = new Booking();
        $this->tourModel = new Tour();
        $this->availabilityModel = new Availability();
    }

    private function getFormDataForCurrentUser(): array
    {
        $formData = $_SESSION['form_data'] ?? [];
        if (empty($formData)) {
            return [];
        }

        $currentUser = $this->currentUser ?? null;
        if (!$currentUser) {
            return $formData;
        }

        $formUserId = $_SESSION['form_data_user_id'] ?? null;
        if ($formUserId && (int)$formUserId !== (int)$currentUser['id']) {
            unset($_SESSION['form_data'], $_SESSION['form_data_user_id'], $_SESSION['form_errors']);
            return [];
        }

        $formEmail = $formData['email'] ?? $formData['cliente_email'] ?? null;
        if ($formEmail && !empty($currentUser['email']) && strcasecmp($formEmail, $currentUser['email']) !== 0) {
            unset($_SESSION['form_data'], $_SESSION['form_data_user_id'], $_SESSION['form_errors']);
            return [];
        }

        return $formData;
    }

    // Helper para debug
    private function debugLog($message) {
        $logFile = __DIR__ . '/../../debug.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    }

    // Formulario de reserva
    public function form()
    {
        $tourId = $this->getInput('tour_id');
        $availabilityId = $this->getInput('availability_id');
        
        if (!$tourId) {
            $this->redirect('tours', 'Selecciona un tour para reservar', 'warning');
            return;
        }
        
        // Obtener información del tour
        $tour = $this->tourModel->getWithCategory($tourId);
        if (!$tour) {
            $this->redirect('tours', 'Tour no encontrado', 'error');
            return;
        }
        
        // Obtener disponibilidad si se especificó
        $availability = null;
        if ($availabilityId) {
            $availability = $this->availabilityModel->find($availabilityId);
        }
        
        // Si es POST, procesar reserva
        if (Helpers::isPost()) {
            $this->processBooking($tour, $availability);
            return;
        }
        
        // Obtener datos del usuario si está logueado
        $auth = Auth::getInstance();
        $currentUser = $auth->getUser();

        $this->view('booking/form', [
            'title' => 'Reservar: ' . $tour['nombre'],
            'tour' => $tour,
            'availability' => $availability,
            'pricing' => $this->tourModel->formatPrice($tour),
            'csrf_token' => Helpers::generateCsrfToken(),
            'currentUser' => $currentUser
        ]);
    }
    
    // Procesar reserva directa desde tour
    public function process()
    {
        if (!Helpers::isPost()) {
            $this->redirect('tours', 'Método no permitido', 'error');
            return;
        }
        // CSRF
        $this->validateCsrf();

        $tourId = $this->getInput('tour_id');
        $availabilityId = $this->getInput('disponibilidad_id');
        $horarioSeleccionado = $this->getInput('horario');

        // Obtener información del tour
        $tour = $this->tourModel->find($tourId);
        if (!$tour) {
            $this->redirect('tours', 'Tour no encontrado', 'error');
            return;
        }

        // Obtener disponibilidad
        $availability = null;
        if ($availabilityId) {
            $availability = $this->availabilityModel->find($availabilityId);
            if (!$availability) {
                $this->redirect('tour/' . $tourId, 'Fecha no disponible', 'error');
                return;
            }
        }

        // Calcular fechas y precios
        $numeroPersonas = max(1, (int)$this->getInput('numero_personas', 1));
        $fechaSalida = $availability['fecha_salida'] ?? date('Y-m-d');
        $fechaRegreso = $availability['fecha_regreso'] ?? date('Y-m-d');

        // Validar capacidad y fechas
        if ($availability) {
            $remaining = (int)$availability['cupos_disponibles'] - (int)$availability['cupos_reservados'];
            if ($numeroPersonas > $remaining) {
                $this->redirect('tour/' . $tourId, 'No hay suficientes cupos disponibles para esa fecha', 'error');
                return;
            }
            if (strtotime($availability['fecha_salida']) < strtotime(date('Y-m-d'))) {
                $this->redirect('tour/' . $tourId, 'La fecha seleccionada ya no está disponible', 'error');
                return;
            }
        }
        
        // Precio base: tramos por grupo > precio especial fecha > precio descuento > precio base
        $precioUnitario = $tour['precio_descuento'] ?? $tour['precio'];
        if (!empty($tour['precios_grupo'])) {
            $tramos = json_decode($tour['precios_grupo'], true);
            if (is_array($tramos)) {
                foreach ($tramos as $t) {
                    $desde = (int)($t['desde'] ?? 1);
                    $hasta = isset($t['hasta']) && $t['hasta'] !== null ? (int)$t['hasta'] : PHP_INT_MAX;
                    if ($numeroPersonas >= $desde && $numeroPersonas <= $hasta) {
                        $precioUnitario = (float)$t['precio'];
                        break;
                    }
                }
            }
        } elseif (!empty($availability['precio_especial'])) {
            $precioUnitario = (float)$availability['precio_especial'];
        }

        $data = [
            'tour_id' => $tourId,
            'disponibilidad_id' => $availabilityId,
            'numero_personas' => $numeroPersonas,
            'fecha_salida' => $fechaSalida,
            'fecha_regreso' => $fechaRegreso,
            'horario_seleccionado' => $horarioSeleccionado ?: null,
            'precio_unitario' => $precioUnitario,
            'precio_total' => $precioUnitario * $numeroPersonas,
            'precio_final' => $precioUnitario * $numeroPersonas,
            'hotel_nombre' => trim($this->getInput('hotel_nombre') ?? '')
        ];

        // Redirigir al formulario de checkout (2 pasos)
        $_SESSION['booking_data'] = $data;
        $this->redirect('booking/checkout-step1');
    }

    // Checkout - formulario de pago
    public function checkout()
    {
        if (empty($_SESSION['booking_data'])) {
            $this->redirect('tours', 'Sesión expirada. Selecciona un tour nuevamente.', 'warning');
            return;
        }

        $bookingData = $_SESSION['booking_data'];
        
        // Obtener información completa del tour
        $tour = $this->tourModel->getWithCategory($bookingData['tour_id']);
        $availability = null;
        
        if ($bookingData['disponibilidad_id']) {
            $availability = $this->availabilityModel->find($bookingData['disponibilidad_id']);
        }

        // Si es POST, procesar el checkout
        if (Helpers::isPost()) {
            $this->processCheckout($bookingData, $tour);
            return;
        }

        $formData = $this->getFormDataForCurrentUser();
        if (!empty($this->currentUser) && empty($formData)) {
            $formData = [
                'cliente_nombre' => $this->currentUser['nombre'] ?? '',
                'cliente_email' => $this->currentUser['email'] ?? '',
                'cliente_telefono' => $this->currentUser['telefono'] ?? ''
            ];
        }
        $_SESSION['form_data'] = $formData;
        if (!empty($this->currentUser['id'])) {
            $_SESSION['form_data_user_id'] = $this->currentUser['id'];
        }

        $this->view('booking/checkout', [
            'title' => 'Completar Reserva',
            'tour' => $tour,
            'availability' => $availability,
            'booking_data' => $bookingData,
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }

    // Checkout Step 1 - Información del viajero
    public function checkoutStep1()
    {
        // Debug
        $this->debugLog('📝 CheckoutStep1 iniciado');
        $this->debugLog('Method: ' . $_SERVER['REQUEST_METHOD']);
        $this->debugLog('booking_data exists: ' . (isset($_SESSION['booking_data']) ? 'YES' : 'NO'));

        if (empty($_SESSION['booking_data'])) {
            $this->debugLog('❌ booking_data vacía, redirigiendo a tours');
            $this->redirect('tours', 'Sesión expirada. Selecciona un tour nuevamente.', 'warning');
            return;
        }

        $bookingData = $_SESSION['booking_data'];
        $tour = $this->tourModel->getWithCategory($bookingData['tour_id']);
        $availability = null;

        if ($bookingData['disponibilidad_id']) {
            $availability = $this->availabilityModel->find($bookingData['disponibilidad_id']);
        }

        // Si es POST, procesar paso 1 y avanzar a paso 2
        if (Helpers::isPost()) {
            $this->debugLog('✉️ Es POST, procesando step1');
            $this->processCheckoutStep1($bookingData, $tour, $availability);
            return;
        }

        $this->debugLog('👁️ Es GET, mostrando formulario step1');

        // Establecer paso 1
        $_SESSION['checkout_step'] = 1;

        // Calcular precios
        $precioUnitario = $bookingData['precio_unitario'] ?? 0;
        $numeroPersonas = $bookingData['numero_personas'] ?? 1;
        $precioTotal = $precioUnitario * $numeroPersonas;
        $precioFinal = $bookingData['precio_final'] ?? $precioTotal;
        $precioDescuento = $precioTotal - $precioFinal;

        // Obtener datos del usuario si está logueado
        $auth = Auth::getInstance();
        $currentUser = $auth->getUser();
        $datos = $this->getFormDataForCurrentUser();

        // Pre-llenar hotel desde el paso anterior si el cliente lo ingresó
        if (!empty($bookingData['hotel_nombre']) && empty($datos['hotel_nombre'])) {
            $datos['hotel_nombre'] = $bookingData['hotel_nombre'];
        }

        $this->view('booking/checkout-step1', [
            'title' => 'Información del Viajero',
            'tour' => $tour,
            'disponibilidad' => $availability,  // Cambiar a nombre español
            'availability' => $availability,    // Mantener para compatibilidad
            'booking_data' => $bookingData,
            'numero_personas' => $numeroPersonas,
            'horario_seleccionado' => $bookingData['horario_seleccionado'] ?? null,
            'precio_unitario' => $precioUnitario,
            'precio_total' => $precioTotal,
            'precio_final' => $precioFinal,
            'precio_descuento' => $precioDescuento,
            'csrf_token' => Helpers::generateCsrfToken(),
            'currentUser' => $currentUser,
            'datos' => $datos
        ]);
    }

    // Procesar Step 1 y avanzar a Step 2
    private function processCheckoutStep1($bookingData, $tour, $availability)
    {
        // Debug: Log que se está procesando
        $this->debugLog('🔄 ProcessCheckoutStep1 iniciado');
        $this->debugLog('POST data: ' . print_r($_POST, true));

        $this->validateCsrf();

        // Recoger todos los datos del formulario (soporta ambos formatos)
        $checkoutData = [
            'tour_id' => $bookingData['tour_id'],
            'disponibilidad_id' => $bookingData['disponibilidad_id'],
            'numero_personas' => $bookingData['numero_personas'],
            'fecha_salida' => $bookingData['fecha_salida'],
            'fecha_regreso' => $bookingData['fecha_regreso'],
            'precio_unitario' => $bookingData['precio_unitario'],
            'precio_total' => $bookingData['precio_total'],
            'precio_final' => $bookingData['precio_final'],
            'horario_seleccionado' => $bookingData['horario_seleccionado'] ?? $this->getInput('horario_seleccionado'),

            // Información personal (soporta cliente_* y nombre_completo/email/telefono)
            'nombre_completo' => trim($this->getInput('cliente_nombre') ?: $this->getInput('nombre_completo')),
            'email' => trim($this->getInput('cliente_email') ?: $this->getInput('email')),
            'telefono' => trim($this->getInput('cliente_telefono') ?: $this->getInput('telefono')),
            'pais' => $this->getInput('cliente_pais') ?: $this->getInput('pais'),
            'fecha_nacimiento' => $this->getInput('fecha_nacimiento'),
            'documento_tipo' => $this->getInput('documento_tipo') ?: 'pasaporte',
            'documento_numero' => trim($this->getInput('documento_numero')) ?: 'N/A',

            // Contacto de emergencia
            'emergencia_nombre' => trim($this->getInput('emergencia_nombre')) ?: '',
            'emergencia_telefono' => trim($this->getInput('emergencia_telefono')) ?: '',
            'emergencia_relacion' => $this->getInput('emergencia_relacion') ?: '',

            // Requerimientos especiales
            'requerimientos_dieta' => $this->getInput('requerimientos_dieta') ?: '',
            'requerimientos_movilidad' => $this->getInput('requerimientos_movilidad') ?: '',
            'requerimientos_medicos' => trim($this->getInput('requerimientos_medicos')) ?: '',
            'notas_adicionales' => trim($this->getInput('notas_adicionales')) ?: trim($this->getInput('requerimientos')) ?: '',

            // Preferencias
            'idioma_guia' => $this->getInput('idioma_guia') ?: 'es',
            'pickup_hotel' => $this->getInput('pickup_hotel') ?: '',
            'hotel_nombre' => trim($this->getInput('hotel_nombre')) ?: '',
            'hotel_direccion' => trim($this->getInput('hotel_direccion')) ?: ''
        ];

        // Validación básica (solo campos realmente obligatorios)
        $errors = [];
        if (empty($checkoutData['nombre_completo'])) {
            $errors['nombre_completo'] = 'El nombre completo es requerido';
        }
        if (empty($checkoutData['email']) || !filter_var($checkoutData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email válido es requerido';
        }
        if (empty($checkoutData['telefono'])) {
            $errors['telefono'] = 'El teléfono es requerido';
        }
        // Campos de emergencia y documento son opcionales

        if (!empty($errors)) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'errors' => $errors]);
                return;
            }
            
            Helpers::setFlashMessage('error', 'Por favor corrige los errores en el formulario');
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $checkoutData;
            $_SESSION['form_data_user_id'] = $this->currentUser['id'] ?? null;
            $this->redirect('booking/checkout-step1');
            return;
        }

        // Guardar datos en sesión
        $_SESSION['checkout_data'] = $checkoutData;
        $_SESSION['checkout_step'] = 2;

        // Debug: Verificar que se guardó
        $this->debugLog('✅ Datos guardados en sesión');
        $this->debugLog('checkout_step: ' . $_SESSION['checkout_step']);
        $this->debugLog('checkout_data keys: ' . implode(', ', array_keys($_SESSION['checkout_data'])));

        if (Helpers::isAjax()) {
            $this->json(['success' => true, 'redirect_url' => '?route=booking/checkout-step2']);
        } else {
            $this->redirect('booking/checkout-step2');
        }
    }

    // Checkout Step 2 - Información de pago
    public function checkoutStep2()
    {
        // Debug: Verificar estado de la sesión
        $this->debugLog('🔍 CheckoutStep2 iniciado');
        $this->debugLog('checkout_data exists: ' . (isset($_SESSION['checkout_data']) ? 'YES' : 'NO'));
        $this->debugLog('checkout_step exists: ' . (isset($_SESSION['checkout_step']) ? 'YES' : 'NO'));
        $this->debugLog('checkout_step value: ' . ($_SESSION['checkout_step'] ?? 'NOT SET'));

        if (!isset($_SESSION['checkout_data']) || !isset($_SESSION['checkout_step']) || $_SESSION['checkout_step'] !== 2) {
            $this->debugLog('❌ Validación falló, redirigiendo a step1');
            $this->redirect('booking/checkout-step1', 'Por favor completa el paso anterior', 'warning');
            return;
        }

        $this->debugLog('✅ Validación pasó, mostrando step2');

        $checkoutData = $_SESSION['checkout_data'];
        $this->debugLog('Obteniendo tour ID: ' . $checkoutData['tour_id']);

        $tour = $this->tourModel->getWithCategory($checkoutData['tour_id']);
        $this->debugLog('Tour obtenido: ' . ($tour ? 'YES' : 'NO'));

        $availability = null;
        if ($checkoutData['disponibilidad_id']) {
            $availability = $this->availabilityModel->find($checkoutData['disponibilidad_id']);
            $this->debugLog('Disponibilidad obtenida: ' . ($availability ? 'YES' : 'NO'));
        }

        // NUEVO: Obtener pasarelas habilitadas para este tour
        $tourGateways = json_decode($tour['payment_gateways_enabled'] ?? '[]', true);
        if (empty($tourGateways)) {
            // Fallback a Stripe y RNPL si no hay configuración
            $tourGateways = ['stripe', 'rnpl'];
            $this->debugLog('⚠️ Usando pasarelas por defecto (stripe, rnpl)');
        } else {
            $this->debugLog('✅ Pasarelas configuradas: ' . implode(', ', $tourGateways));
        }

        // NUEVO: Construir información de cada pasarela
        $paymentOptions = [];
        foreach ($tourGateways as $gateway) {
            $info = \App\Core\PaymentGatewayFactory::getGatewayInfo($gateway);
            if ($info) {
                $paymentOptions[] = $info;
            }
        }
        $this->debugLog('✅ Opciones de pago preparadas: ' . count($paymentOptions));

        // NUEVO: Verificar elegibilidad para RNPL
        $rnplElegible = false;
        if ($availability && isset($availability['fecha_salida'])) {
            $fechaSalida = new \DateTime($availability['fecha_salida']);
            $ahora = new \DateTime();
            $horasRestantes = ($fechaSalida->getTimestamp() - $ahora->getTimestamp()) / 3600;
            $rnplElegible = $horasRestantes >= 72; // Mínimo 72 horas
            $this->debugLog('RNPL elegible: ' . ($rnplElegible ? 'YES' : 'NO') . ' (Horas restantes: ' . round($horasRestantes, 2) . ')');
        }

        $this->debugLog('Intentando renderizar vista checkout-step2');

        try {
            $this->view('booking/checkout-step2', [
                'title' => 'Información de Pago',
                'tour' => $tour,
                'availability' => $availability,
                'checkout_data' => $checkoutData,
                'payment_options' => $paymentOptions, // NUEVO
                'rnpl_elegible' => $rnplElegible,     // NUEVO
                'csrf_token' => Helpers::generateCsrfToken()
            ]);
            $this->debugLog('✅ Vista renderizada exitosamente');
        } catch (Exception $e) {
            $this->debugLog('❌ Error al renderizar vista: ' . $e->getMessage());
            throw $e;
        }
    }

    // Procesar pago final
    public function processPayment()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        if (!isset($_SESSION['checkout_data'])) {
            $this->json(['success' => false, 'error' => 'Sesión expirada']);
            return;
        }

        $this->validateCsrf();
        
        $checkoutData = $_SESSION['checkout_data'];
        $paymentMethod = $this->getInput('payment_method', 'immediate');
        $paymentMethodId = $this->getInput('payment_method_id');

        try {
            // Crear la reserva según el método de pago
            if ($paymentMethod === 'rnpl') {
                // Redirigir al controlador RNPL
                $rnplController = new RnplController();
                $rnplController->processBooking();
                return;
            } else {
                // Pago inmediato con Stripe
                $result = $this->processImmediatePayment($checkoutData, $paymentMethodId);
            }

            if ($result['success']) {
                // Limpiar datos de sesión
                unset($_SESSION['checkout_data']);
                unset($_SESSION['checkout_step']);
                unset($_SESSION['booking_data']);

                $this->json([
                    'success' => true,
                    'message' => 'Reserva procesada exitosamente',
                    'redirect_url' => '?route=booking/success&id=' . $result['booking_id']
                ]);
            } else {
                $this->json(['success' => false, 'error' => $result['error']]);
            }

        } catch (Exception $e) {
            $this->debugLog('Error processing payment: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error interno del servidor']);
        }
    }

    // Procesar pago inmediato
    private function processImmediatePayment($checkoutData, $paymentMethodId)
    {
        // Aquí iría la lógica de Stripe para pago inmediato
        // Por ahora, simulamos la creación de la reserva
        
        // Generar código de reserva
        $bookingCode = 'MW' . strtoupper(substr(uniqid(), -6));
        
        // Crear datos finales de reserva
        $finalBookingData = array_merge($checkoutData, [
            'codigo_reserva' => $bookingCode,
            'estado' => 'confirmada',
            'metodo_pago' => 'tarjeta',
            'payment_processor' => 'stripe',
            'stripe_payment_intent_id' => 'pi_' . uniqid() // Simular payment intent ID
        ]);

        $result = $this->bookingModel->createBooking($finalBookingData);
        
        return $result;
    }

    // Página de éxito
    public function success()
    {
        $bookingId = $this->getInput('id');
        
        if (!$bookingId) {
            $this->redirect('tours', 'Reserva no encontrada', 'error');
            return;
        }

        $booking = $this->bookingModel->getWithTour($bookingId);
        
        if (!$booking) {
            $this->redirect('tours', 'Reserva no encontrada', 'error');
            return;
        }

        $this->view('booking/success', [
            'title' => 'Reserva Exitosa',
            'booking' => $booking
        ]);
    }

    // Procesar checkout final
    private function processCheckout($bookingData, $tour)
    {
        // CSRF
        $this->validateCsrf();
        // Recoger datos del cliente
        $clientData = [
            'cliente_nombre' => $this->getInput('cliente_nombre'),
            'cliente_email' => $this->getInput('cliente_email'),
            'cliente_telefono' => $this->getInput('cliente_telefono'),
            'cliente_direccion' => $this->getInput('cliente_direccion'),
            'notas_cliente' => $this->getInput('notas_cliente'),
            'metodo_pago' => $this->getInput('metodo_pago', 'transferencia')
        ];

        // Validar datos básicos
        $errors = [];
        if (empty($clientData['cliente_nombre'])) {
            $errors['cliente_nombre'] = 'El nombre es requerido';
        }
        if (empty($clientData['cliente_email']) || !filter_var($clientData['cliente_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['cliente_email'] = 'Email válido es requerido';
        }
        if (empty($clientData['cliente_telefono'])) {
            $errors['cliente_telefono'] = 'El teléfono es requerido';
        }

        if (!empty($errors)) {
            Helpers::setFlashMessage('error', 'Por favor corrige los errores en el formulario');
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_data'] = $clientData;
            $_SESSION['form_data_user_id'] = $this->currentUser['id'] ?? null;
            $this->redirect('booking/checkout');
            return;
        }

        // Combinar datos
        $finalBookingData = array_merge($bookingData, $clientData);

        // Crear la reserva en estado pendiente
        $result = $this->bookingModel->createBooking($finalBookingData);

        if (!$result['success']) {
            Helpers::setFlashMessage('error', 'Error al procesar la reserva. Intenta nuevamente.');
            $_SESSION['form_errors'] = $result['errors'];
            $_SESSION['form_data'] = $clientData;
            $_SESSION['form_data_user_id'] = $this->currentUser['id'] ?? null;
            $this->redirect('booking/checkout');
            return;
        }

        // Limpiar datos de sesión del checkout
        unset($_SESSION['booking_data']);
        unset($_SESSION['form_errors']);
        unset($_SESSION['form_data']);
        unset($_SESSION['form_data_user_id']);

        // Si es tarjeta, redirigir a Stripe Checkout; de lo contrario, a confirmación
        if (($clientData['metodo_pago'] ?? '') === 'tarjeta') {
            $type = $this->getInput('pago_tipo', 'full');
            $suffix = $type === 'deposit' ? ('?type=deposit') : '';
            $this->redirect('payment/checkout/' . $result['booking_id'] . $suffix);
        } else {
            $this->redirect(
                'booking/confirmation?code=' . $result['booking_code'],
                'Reserva creada exitosamente',
                'success'
            );
        }
    }

    // Confirmación final con detalles de pago
    public function confirmation()
    {
        $code = $this->getInput('code');
        
        if (!$code) {
            $this->redirect('home', 'Código de reserva requerido', 'error');
            return;
        }

        $booking = $this->bookingModel->getWithTour(null, null);
        $booking = array_filter($booking, function($b) use ($code) {
            return $b['codigo_reserva'] === $code;
        });

        if (empty($booking)) {
            $this->redirect('home', 'Reserva no encontrada', 'error');
            return;
        }

        $booking = array_values($booking)[0];
        // Calcular pagos
        $paidRow = $this->db->fetch("SELECT COALESCE(SUM(monto),0) AS paid FROM pagos WHERE reserva_id = :rid AND estado = 'completado'", ['rid' => $booking['id']]);
        $paidAmount = (float)($paidRow['paid'] ?? 0);
        $pendingAmount = max(0.0, (float)$booking['precio_total'] - $paidAmount);

        $this->view('booking/confirmation', [
            'title' => 'Reserva Confirmada',
            'booking' => $booking,
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
            'company_info' => [
                'name' => Config::APP_NAME,
                'phone' => Config::COMPANY_PHONE,
                'email' => Config::COMPANY_EMAIL,
                'whatsapp' => Config::SOCIAL_WHATSAPP
            ]
        ]);
    }

    // Procesar reserva
    private function processBooking($tour, $availability)
    {
        $this->validateCsrf();
        
        $data = [
            'tour_id' => $tour['id'],
            'disponibilidad_id' => $availability ? $availability['id'] : null,
            'cliente_nombre' => $this->getInput('cliente_nombre'),
            'cliente_email' => $this->getInput('cliente_email'),
            'cliente_telefono' => $this->getInput('cliente_telefono'),
            'cliente_direccion' => $this->getInput('cliente_direccion'),
            'numero_personas' => $this->getInput('numero_personas'),
            'fecha_salida' => $this->getInput('fecha_salida'),
            'fecha_regreso' => $this->getInput('fecha_regreso'),
            'notas_cliente' => $this->getInput('notas_cliente'),
            'metodo_pago' => $this->getInput('metodo_pago')
        ];
        
        $result = $this->bookingModel->createBooking($data);
        
        if (Helpers::isAjax()) {
            $this->json($result);
        }
        
        if ($result['success']) {
            $this->redirect(
                'booking/confirm?code=' . $result['booking_code'], 
                'Reserva creada exitosamente. Código: ' . $result['booking_code'], 
                'success'
            );
        } else {
            $this->handleValidationErrors($result['errors'], 'booking/form?tour_id=' . $tour['id']);
        }
    }
    
    // Confirmación de reserva
    public function confirm()
    {
        $code = $this->getInput('code');
        
        if (!$code) {
            $this->redirect('home', 'Código de reserva requerido', 'error');
            return;
        }
        
        $booking = $this->bookingModel->findWhere(['codigo_reserva' => $code]);
        
        if (!$booking) {
            $this->redirect('home', 'Reserva no encontrada', 'error');
            return;
        }
        
        // Obtener información completa de la reserva
        $bookingDetails = $this->bookingModel->getWithTour($booking['id']);
        $paidRow = $this->db->fetch("SELECT COALESCE(SUM(monto),0) AS paid FROM pagos WHERE reserva_id = :rid AND estado = 'completado'", ['rid' => $booking['id']]);
        $paidAmount = (float)($paidRow['paid'] ?? 0);
        $pendingAmount = max(0.0, (float)$bookingDetails['precio_total'] - $paidAmount);

        $this->view('booking/confirm', [
            'title' => 'Reserva Confirmada',
            'booking' => $bookingDetails,
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
            'formatted_info' => $this->bookingModel->formatBookingInfo($bookingDetails)
        ]);
    }
    
    // Mis reservas (para usuarios logueados)
    public function myBookings()
    {
        $this->requireAuth();
        
        $userEmail = $this->currentUser['email'];
        $bookings = $this->bookingModel->getWithTour(null, $userEmail);
        
        $this->view('booking/my-bookings', [
            'title' => 'Mis Reservas',
            'bookings' => $bookings
        ]);
    }
    
    // Buscar reserva por código
    public function findBooking()
    {
        if (Helpers::isPost()) {
            $code = $this->getInput('booking_code');
            $email = $this->getInput('cliente_email');

            if ($code) {
                // Search by booking code
                $sql = "SELECT * FROM reservas WHERE codigo_reserva = ? LIMIT 1";
                $booking = $this->db->fetchOne($sql, [$code]);

                if ($booking) {
                    // Check if it's RNPL booking
                    if ($booking['payment_method'] === 'rnpl') {
                        $this->redirect('rnpl/confirmation/' . $booking['id']);
                    } else {
                        $this->redirect('booking/confirmation?id=' . $booking['id']);
                    }
                    return;
                } else {
                    Helpers::setFlashMessage('error', 'No se encontró una reserva con ese código');
                }
            } elseif ($email) {
                // Search by email
                $sql = "SELECT * FROM reservas WHERE cliente_email = ? ORDER BY created_at DESC";
                $bookings = $this->db->fetchAll($sql, [$email]);

                if (!empty($bookings)) {
                    // If only one booking, redirect directly
                    if (count($bookings) === 1) {
                        $booking = $bookings[0];
                        if ($booking['payment_method'] === 'rnpl') {
                            $this->redirect('rnpl/confirmation/' . $booking['id']);
                        } else {
                            $this->redirect('booking/confirmation?id=' . $booking['id']);
                        }
                        return;
                    } else {
                        // Multiple bookings, show list
                        $this->view('booking/list-by-email', [
                            'title' => 'Mis Reservas',
                            'bookings' => $bookings,
                            'email' => $email
                        ]);
                        return;
                    }
                } else {
                    Helpers::setFlashMessage('error', 'No se encontraron reservas con ese email');
                }
            } else {
                Helpers::setFlashMessage('error', 'Ingresa un código de reserva o un email');
            }
        }

        $this->view('booking/find', [
            'title' => 'Buscar Reserva',
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }
}
