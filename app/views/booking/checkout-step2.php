<?php
use App\Core\Config;
use App\Core\Helpers;
// Verificar que venimos del paso 1
if (!isset($_SESSION['checkout_data']) || !isset($_SESSION['checkout_step']) || $_SESSION['checkout_step'] !== 2) {
    header('Location: ?route=booking/checkout-step1');
    exit;
}

$checkoutData = $_SESSION['checkout_data'];
// Las variables ya vienen del controlador
$tour = $tour ?? null;
$disponibilidad = $availability ?? null;

if (!$tour) {
    error_log('❌ Tour no encontrado, redirigiendo a tours');
    header('Location: ?route=tours');
    exit;
}

// Disponibilidad es opcional para algunos tours
if (!$disponibilidad) {
    error_log('⚠️ Warning: No hay disponibilidad específica');
}

// Calcular precios
$numeroPersonas = $checkoutData['numero_personas'];
$precioUnitario = ($disponibilidad['precio_especial'] ?? null) ?: ($tour['precio'] ?? 0);
$subtotal = $precioUnitario * $numeroPersonas;
$descuento = 0; // Calcular descuentos si aplican
$impuestos = $subtotal * 0.12; // IVA 12%
$total = $subtotal - $descuento + $impuestos;

// Verificar si califica para RNPL
$rnplElegible = false;
if ($disponibilidad && isset($disponibilidad['fecha_salida'])) {
    $fechaSalida = new DateTime($disponibilidad['fecha_salida']);
    $ahora = new DateTime();
    $horasRestantes = ($fechaSalida->getTimestamp() - $ahora->getTimestamp()) / 3600;
    $rnplElegible = $horasRestantes >= 72;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago - <?= htmlspecialchars($tour['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .checkout-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem 0;
            color: white;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 2rem;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin: 0 1rem;
            position: relative;
        }
        .step.completed {
            background-color: #28a745;
            color: white;
        }
        .step.active {
            background-color: #007bff;
            color: white;
        }
        .step.inactive {
            background-color: #e9ecef;
            color: #6c757d;
        }
        .step::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 100%;
            width: 60px;
            height: 2px;
            background-color: #e9ecef;
            margin-left: 1rem;
        }
        .step:last-child::after {
            display: none;
        }
        .step.completed::after {
            background-color: #28a745;
        }
        .payment-option {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        .payment-option:hover {
            border-color: #007bff;
            box-shadow: 0 4px 8px rgba(0,123,255,0.15);
        }
        .payment-option.selected {
            border-color: #007bff;
            background-color: rgba(0,123,255,0.05);
        }
        .payment-option.selected::before {
            content: '\f058';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 15px;
            right: 15px;
            color: #007bff;
            font-size: 1.2rem;
        }
        .rnpl-badge {
            background: linear-gradient(45deg, #ff6b6b, #ff8e53);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        .price-breakdown {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            border-left: 4px solid #007bff;
        }
        .digital-wallet-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .wallet-button {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .wallet-button:hover {
            border-color: #007bff;
            transform: translateY(-2px);
        }
        .wallet-button img {
            height: 30px;
        }
        #card-element {
            padding: 1rem;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            background: white;
        }
        .security-badges {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        .security-badge {
            display: flex;
            align-items: center;
            font-size: 0.8rem;
            color: #6c757d;
        }
        .security-badge i {
            margin-right: 0.5rem;
            color: #28a745;
        }
        .booking-summary {
            position: sticky;
            top: 2rem;
        }
        @media (max-width: 768px) {
            .booking-summary {
                position: static;
                margin-top: 2rem;
            }
            .digital-wallet-buttons {
                flex-direction: column;
            }
        }
        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Checkout Header -->
    <div class="checkout-header">
        <div class="container">
            <div class="step-indicator">
                <div class="step completed">
                    <i class="fas fa-check"></i>
                </div>
                <div class="step active">2</div>
                <div class="step inactive">3</div>
            </div>
            <h2 class="text-center mb-0">Información de Pago</h2>
        </div>
    </div>

    <div class="container my-5">
        <div class="row">
            <!-- Formulario de Pago -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <h4 class="mb-4">
                            <i class="fas fa-credit-card text-primary me-2"></i>
                            Selecciona tu método de pago
                        </h4>

                        <form id="payment-form">
                            <h5 class="mb-3">Métodos de Pago Disponibles</h5>

                            <!-- Opciones de Pago Dinámicas -->
                            <div class="payment-options mb-4">
                                <?php
                                $isFirst = true;
                                foreach ($payment_options as $option):
                                    // Skip RNPL if not eligible
                                    if ($option['name'] === 'rnpl' && !$rnpl_elegible) {
                                        continue;
                                    }
                                ?>
                                <div class="payment-option" data-method="<?= $option['name'] ?>">
                                    <div class="d-flex align-items-start">
                                        <input type="radio"
                                               name="payment_gateway"
                                               value="<?= $option['name'] ?>"
                                               id="payment-<?= $option['name'] ?>"
                                               <?= $isFirst ? 'checked' : '' ?>
                                               class="form-check-input me-3 mt-1">
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1">
                                                <i class="fas <?= $option['icon'] ?> me-2" style="color: <?= $option['color'] ?>"></i>
                                                <?= htmlspecialchars($option['display_name']) ?>
                                                <?php if ($option['name'] === 'rnpl'): ?>
                                                    <span class="rnpl-badge">Popular</span>
                                                <?php endif; ?>
                                            </h5>
                                            <p class="mb-2 text-muted small">
                                                <?= htmlspecialchars($option['description']) ?>
                                            </p>
                                            <div class="d-flex gap-2">
                                                <?php foreach ($option['currencies'] as $currency): ?>
                                                    <span class="badge bg-secondary"><?= $currency ?></span>
                                                <?php endforeach; ?>
                                                <?php foreach ($option['countries'] as $country): ?>
                                                    <span class="badge bg-info text-dark"><?= $country ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <div class="ms-3 text-end">
                                            <?php if ($option['name'] === 'rnpl'): ?>
                                                <div class="fw-bold text-success">Hoy: $<?= number_format($total * 0.1, 2) ?></div>
                                                <div class="text-muted small">Después: $<?= number_format($total * 0.9, 2) ?></div>
                                            <?php else: ?>
                                                <div class="fw-bold">$<?= number_format($total, 2) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                $isFirst = false;
                                endforeach;
                                ?>
                            </div>

                            <!-- Stripe Card Element (solo se muestra si Stripe está seleccionado) -->
                            <div class="mb-4 payment-method-card-section" id="stripe-card-section" style="display: none;">
                                <label class="form-label fw-semibold">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Información de la Tarjeta
                                </label>
                                <div id="card-element"></div>
                                <div id="card-errors" role="alert" class="text-danger mt-2"></div>
                            </div>

                            <!-- Términos y Condiciones -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="accept-terms" name="accept_terms" required>
                                    <label class="form-check-label" for="accept-terms">
                                        Acepto los <a href="<?= Config::getBaseUrl() ?>?route=terms" class="text-primary" target="_blank" rel="noopener">términos y condiciones</a>
                                        y la <a href="<?= Config::getBaseUrl() ?>?route=privacy" class="text-primary" target="_blank" rel="noopener">política de privacidad</a>
                                    </label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="marketing-emails" name="accept_marketing">
                                    <label class="form-check-label" for="marketing-emails">
                                        Quiero recibir ofertas especiales y noticias por email
                                    </label>
                                </div>
                            </div>

                            <!-- Botón de Pago -->
                            <button type="submit" id="submit-payment" class="btn btn-primary btn-lg w-100" disabled>
                                <span class="spinner me-2"></span>
                                <i class="fas fa-lock me-2"></i>
                                <span id="button-text">Completar Reserva</span>
                            </button>

                            <!-- Badges de Seguridad -->
                            <div class="security-badges mt-3">
                                <div class="security-badge">
                                    <i class="fas fa-shield-alt"></i>
                                    Conexión Segura SSL
                                </div>
                                <div class="security-badge">
                                    <i class="fas fa-credit-card"></i>
                                    Stripe Secure
                                </div>
                                <div class="security-badge">
                                    <i class="fas fa-undo"></i>
                                    Cancelación Gratuita
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Resumen de Reserva -->
            <div class="col-lg-4">
                <div class="booking-summary">
                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="mb-3">
                                <i class="fas fa-file-invoice text-primary me-2"></i>
                                Resumen de Reserva
                            </h5>

                            <!-- Tour -->
                            <div class="d-flex mb-3">
                                <img src="<?= htmlspecialchars(Helpers::tourImage($tour['imagen_principal'] ?? null, 'images/default-destination.jpg')) ?>" 
                                     alt="<?= htmlspecialchars($tour['nombre']) ?>" 
                                     class="rounded me-3" style="width: 80px; height: 60px; object-fit: cover;"
                                     loading="lazy"
                                     decoding="async">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($tour['nombre']) ?></h6>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('d M Y', strtotime($disponibilidad['fecha_salida'])) ?>
                                    </small>
                                </div>
                            </div>

                            <hr>

                            <!-- Detalles del Viajero -->
                            <div class="mb-3">
                                <h6 class="fw-semibold">Información del Viajero</h6>
                                <p class="mb-1">
                                    <i class="fas fa-user me-2 text-muted"></i>
                                    <?= htmlspecialchars($checkoutData['nombre_completo']) ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-envelope me-2 text-muted"></i>
                                    <?= htmlspecialchars($checkoutData['email']) ?>
                                </p>
                                <p class="mb-1">
                                    <i class="fas fa-users me-2 text-muted"></i>
                                    <?= $numeroPersonas ?> persona<?= $numeroPersonas > 1 ? 's' : '' ?>
                                </p>
                            </div>

                            <hr>

                            <!-- Desglose de Precio -->
                            <div class="price-breakdown">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal (<?= $numeroPersonas ?>x $<?= number_format($precioUnitario, 2) ?>)</span>
                                    <span>$<?= number_format($subtotal, 2) ?></span>
                                </div>
                                <?php if ($descuento > 0): ?>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>Descuento</span>
                                    <span>-$<?= number_format($descuento, 2) ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Impuestos</span>
                                    <span>$<?= number_format($impuestos, 2) ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold fs-5" id="total-amount">
                                    <span>Total</span>
                                    <span>$<?= number_format($total, 2) ?></span>
                                </div>
                                <div id="rnpl-breakdown" style="display: none;">
                                    <hr>
                                    <div class="d-flex justify-content-between text-primary">
                                        <span>Hoy</span>
                                        <span>$<?= number_format($total * 0.1, 2) ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between text-muted">
                                        <span>48h antes del tour</span>
                                        <span>$<?= number_format($total * 0.9, 2) ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Políticas -->
                            <div class="mt-3">
                                <div class="d-flex align-items-center text-success mb-2">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <small>Confirmación inmediata</small>
                                </div>
                                <div class="d-flex align-items-center text-success mb-2">
                                    <i class="fas fa-undo me-2"></i>
                                    <small>Cancelación gratuita hasta 24h antes</small>
                                </div>
                                <div class="d-flex align-items-center text-success">
                                    <i class="fas fa-headset me-2"></i>
                                    <small>Soporte 24/7</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Inicializar Stripe
        // Variables globales
        let paymentMethod = 'immediate';
        let isProcessing = false;
        let stripe = null;
        let elements = null;
        let cardElement = null;

        // Inicializar Stripe solo si está disponible
        try {
            if (typeof Stripe !== 'undefined' && '<?= Config::STRIPE_PUBLIC_KEY ?>') {
                stripe = Stripe('<?= Config::STRIPE_PUBLIC_KEY ?>');
                elements = stripe.elements();

                // Crear elemento de tarjeta
                cardElement = elements.create('card', {
                    style: {
                        base: {
                            fontSize: '16px',
                            color: '#424770',
                            '::placeholder': {
                                color: '#aab7c4',
                            },
                        },
                    },
                });
                cardElement.mount('#card-element');
                console.log('✅ Stripe inicializado correctamente');
            } else {
                console.warn('⚠️ Stripe no disponible - solo RNPL funcionará');
            }
        } catch (error) {
            console.error('❌ Error al inicializar Stripe:', error);
            console.log('💡 RNPL seguirá funcionando sin Stripe');
        }

        // Elementos DOM
        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('submit-payment');
        const buttonText = document.getElementById('button-text');
        const spinner = document.querySelector('.spinner');
        const acceptTerms = document.getElementById('accept-terms');
        const totalAmount = document.getElementById('total-amount');
        const rnplBreakdown = document.getElementById('rnpl-breakdown');

        // Validación de términos
        acceptTerms.addEventListener('change', function() {
            submitButton.disabled = !this.checked;
        });

        // Manejo de opciones de pago
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remover selección anterior
                document.querySelectorAll('.payment-option').forEach(opt => 
                    opt.classList.remove('selected')
                );
                
                // Seleccionar nueva opción
                this.classList.add('selected');
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                paymentMethod = radio.value;

                // Actualizar UI según método
                updatePaymentUI();
            });
        });

        // Actualizar UI según método de pago
        function updatePaymentUI() {
            const total = <?= $total ?>;
            const selectedGateway = document.querySelector('input[name="payment_gateway"]:checked')?.value || 'stripe';
            const cardSection = document.getElementById('stripe-card-section');

            console.log('🔄 Updating UI for gateway:', selectedGateway);

            // Mostrar/ocultar el campo de tarjeta de Stripe
            if (selectedGateway === 'stripe') {
                if (cardSection) {
                    cardSection.style.display = 'block';
                }
                buttonText.textContent = `Pagar $<?= number_format($total, 2) ?>`;
            } else if (selectedGateway === 'rnpl') {
                if (cardSection) {
                    cardSection.style.display = 'none';
                }
                buttonText.textContent = `Pagar $<?= number_format($total * 0.1, 2) ?> ahora`;
                if (totalAmount) totalAmount.style.display = 'none';
                if (rnplBreakdown) rnplBreakdown.style.display = 'block';
            } else {
                // Paggo o Recurrente - no necesitan tarjeta
                if (cardSection) {
                    cardSection.style.display = 'none';
                }
                buttonText.textContent = `Continuar al Pago`;
            }

            // Actualizar paymentMethod para compatibilidad con código existente
            paymentMethod = selectedGateway;
        }

        // Manejar cambios en los radio buttons de pasarelas
        document.querySelectorAll('input[name="payment_gateway"]').forEach(radio => {
            radio.addEventListener('change', updatePaymentUI);
        });

        // Manejo de errores de tarjeta (solo si Stripe está disponible)
        if (cardElement) {
            cardElement.on('change', ({error}) => {
                const displayError = document.getElementById('card-errors');
                if (error) {
                    displayError.textContent = error.message;
                } else {
                    displayError.textContent = '';
                }
            });
        }

        // Apple Pay
        if (window.ApplePaySession) {
            const applePayButton = document.getElementById('apple-pay-button');
            applePayButton.addEventListener('click', function() {
                processApplePay();
            });
        } else {
            document.getElementById('apple-pay-button').style.opacity = '0.5';
        }

        // Google Pay
        function initializeGooglePay() {
            const paymentsClient = new google.payments.api.PaymentsClient({
                environment: 'TEST' // Cambiar a 'PRODUCTION' en producción
            });

            const googlePayButton = document.getElementById('google-pay-button');
            googlePayButton.addEventListener('click', function() {
                processGooglePay(paymentsClient);
            });
        }

        // Procesar Apple Pay
        async function processApplePay() {
            if (!window.ApplePaySession || !ApplePaySession.canMakePayments()) {
                alert('Apple Pay no está disponible en este dispositivo');
                return;
            }

            const session = new ApplePaySession(3, {
                countryCode: 'GT',
                currencyCode: 'USD',
                supportedNetworks: ['visa', 'masterCard', 'amex'],
                merchantCapabilities: ['supports3DS'],
                total: {
                    label: '<?= addslashes($tour['nombre']) ?>',
                    amount: paymentMethod === 'rnpl' ? '<?= $total * 0.1 ?>' : '<?= $total ?>'
                }
            });

            session.onvalidatemerchant = function(event) {
                // Validar merchant con Stripe
                validateApplePayMerchant(event.validationURL);
            };

            session.onpaymentauthorized = function(event) {
                processPayment('apple_pay', event.payment.token);
                session.completePayment(ApplePaySession.STATUS_SUCCESS);
            };

            session.begin();
        }

        // Procesar Google Pay
        async function processGooglePay(paymentsClient) {
            const paymentDataRequest = {
                apiVersion: 2,
                apiVersionMinor: 0,
                allowedPaymentMethods: [{
                    type: 'CARD',
                    parameters: {
                        allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                        allowedCardNetworks: ['AMEX', 'DISCOVER', 'INTERAC', 'JCB', 'MASTERCARD', 'VISA']
                    }
                }],
                transactionInfo: {
                    totalPriceStatus: 'FINAL',
                    totalPrice: paymentMethod === 'rnpl' ? '<?= $total * 0.1 ?>' : '<?= $total ?>',
                    currencyCode: 'USD'
                }
            };

            try {
                const paymentData = await paymentsClient.loadPaymentData(paymentDataRequest);
                processPayment('google_pay', paymentData.paymentMethodData.tokenizationData.token);
            } catch (error) {
                console.error('Error con Google Pay:', error);
            }
        }

        // Envío del formulario
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (isProcessing) return;
            if (!acceptTerms.checked) return;

            const selectedGateway = document.querySelector('input[name="payment_gateway"]:checked')?.value;
            console.log('🚀 Procesando pago con gateway:', selectedGateway);

            setLoading(true);

            try {
                // NUEVO: Manejar Paggo y Recurrente
                if (selectedGateway === 'paggo' || selectedGateway === 'recurrente') {
                    console.log(`📦 Redirigiendo a ${selectedGateway} payment processor`);
                    // Redireccionar al endpoint unificado que creará el pago
                    window.location.href = `<?= Config::getBaseUrl() ?>?route=payment/process&gateway=${selectedGateway}`;
                    return;
                }

                // Si es RNPL, procesar directamente sin Stripe
                if (selectedGateway === 'rnpl') {
                    console.log('📦 Procesando RNPL (sin Stripe)');
                    await processPayment('rnpl', null);
                    return;
                }

                // Para Stripe, crear PaymentMethod con Stripe
                if (selectedGateway === 'stripe') {
                    if (!stripe || !cardElement) {
                        throw new Error('Stripe no está disponible. Por favor selecciona otro método de pago.');
                    }

                    console.log('💳 Creando PaymentMethod con Stripe');
                    const {error, paymentMethod: pm} = await stripe.createPaymentMethod({
                        type: 'card',
                        card: cardElement,
                        billing_details: {
                            name: '<?= addslashes($checkoutData['nombre_completo']) ?>',
                            email: '<?= addslashes($checkoutData['email']) ?>',
                        },
                    });

                    if (error) {
                        throw new Error(error.message);
                    }

                    // Procesar pago
                    await processPayment('card', pm.id);
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Error al procesar el pago: ' + error.message);
                setLoading(false);
            }
        });

        // Procesar pago
        async function processPayment(type, paymentMethodId) {
            const endpoint = paymentMethod === 'rnpl' ? '?route=rnpl/process' : '?route=booking/process-payment';
            
            const formData = new FormData();
            formData.append('payment_method_type', type);
            formData.append('payment_method_id', paymentMethodId);
            formData.append('payment_method', paymentMethod);
            formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');

            // Agregar datos de checkout
            Object.keys(<?= json_encode($checkoutData) ?>).forEach(key => {
                formData.append(key, <?= json_encode($checkoutData) ?>[key]);
            });

            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    // Redirigir a confirmación
                    window.location.href = result.redirect_url || '?route=booking/success';
                } else {
                    throw new Error(result.error || 'Error desconocido');
                }

            } catch (error) {
                console.error('Error:', error);
                alert('Error al procesar la reserva: ' + error.message);
                setLoading(false);
            }
        }

        // Controlar estado de carga
        function setLoading(loading) {
            isProcessing = loading;
            submitButton.disabled = loading || !acceptTerms.checked;
            
            if (loading) {
                spinner.style.display = 'inline-block';
                buttonText.textContent = 'Procesando...';
            } else {
                spinner.style.display = 'none';
                updatePaymentUI();
            }
        }

        // Validar merchant de Apple Pay
        async function validateApplePayMerchant(validationURL) {
            try {
                const response = await fetch('?route=payment/apple-pay-validate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ validationURL })
                });

                const merchantSession = await response.json();
                session.completeMerchantValidation(merchantSession);
            } catch (error) {
                console.error('Error validando merchant:', error);
                session.abort();
            }
        }

        // Inicializar
        updatePaymentUI();

        // Cargar Google Pay API si está disponible
        if (window.google && window.google.payments) {
            initializeGooglePay();
        }
    </script>
    
    <!-- Google Pay API -->
    <script async src="https://pay.google.com/gp/p/js/pay.js" onload="initializeGooglePay()"></script>
</body>
</html>
