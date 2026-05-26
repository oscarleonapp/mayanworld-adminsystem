<?php
use App\Core\Config;
require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <!-- Progreso de la reserva -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 66%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-success"><i class="fas fa-check-circle"></i> Selección</small>
                        <small class="text-primary"><strong><i class="fas fa-credit-card"></i> Datos y Pago</strong></small>
                        <small class="text-muted"><i class="far fa-circle"></i> Confirmación</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Formulario de checkout -->
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0"><i class="fas fa-user-edit"></i> Información del Cliente</h4>
                        </div>
                        <div class="card-body">
                            <?php if (isset($_SESSION['form_errors'])): ?>
                                <div class="alert alert-danger">
                                    <h6><i class="fas fa-exclamation-triangle"></i> Corrige los siguientes errores:</h6>
                                    <ul class="mb-0">
                                        <?php foreach ($_SESSION['form_errors'] as $field => $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form action="/?route=booking/checkout" method="POST" id="checkoutForm">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                
                                <!-- Datos personales -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cliente_nombre" class="form-label">Nombre Completo *</label>
                                            <input type="text" 
                                                   class="form-control <?= isset($_SESSION['form_errors']['cliente_nombre']) ? 'is-invalid' : '' ?>" 
                                                   id="cliente_nombre" 
                                                   name="cliente_nombre" 
                                                   value="<?= htmlspecialchars($_SESSION['form_data']['cliente_nombre'] ?? '') ?>"
                                                   required>
                                            <div class="form-text">Nombre como aparece en documento de identidad</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cliente_email" class="form-label">Email *</label>
                                            <input type="email" 
                                                   class="form-control <?= isset($_SESSION['form_errors']['cliente_email']) ? 'is-invalid' : '' ?>" 
                                                   id="cliente_email" 
                                                   name="cliente_email" 
                                                   value="<?= htmlspecialchars($_SESSION['form_data']['cliente_email'] ?? '') ?>"
                                                   required>
                                            <div class="form-text">Para envío de confirmación y detalles</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cliente_telefono" class="form-label">Teléfono *</label>
                                            <input type="tel" 
                                                   class="form-control <?= isset($_SESSION['form_errors']['cliente_telefono']) ? 'is-invalid' : '' ?>" 
                                                   id="cliente_telefono" 
                                                   name="cliente_telefono" 
                                                   placeholder="+502 1234-5678"
                                                   value="<?= htmlspecialchars($_SESSION['form_data']['cliente_telefono'] ?? '') ?>"
                                                   required>
                                            <div class="form-text">Incluye código de país</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cliente_direccion" class="form-label">Dirección</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="cliente_direccion" 
                                                   name="cliente_direccion" 
                                                   value="<?= htmlspecialchars($_SESSION['form_data']['cliente_direccion'] ?? '') ?>"
                                                   placeholder="Ciudad, País">
                                        </div>
                                    </div>
                                </div>

                                <!-- Notas adicionales -->
                                <div class="mb-4">
                                    <label for="notas_cliente" class="form-label">Comentarios o Solicitudes Especiales</label>
                                    <textarea class="form-control" 
                                              id="notas_cliente" 
                                              name="notas_cliente" 
                                              rows="3" 
                                              placeholder="Alergias, restricciones dietéticas, necesidades especiales..."><?= htmlspecialchars($_SESSION['form_data']['notas_cliente'] ?? '') ?></textarea>
                                </div>

                                <!-- Método de pago -->
                                <div class="card border-info mb-4">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-credit-card"></i> Método de Pago</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="metodo_pago" id="transferencia" value="transferencia" checked>
                                                    <label class="form-check-label" for="transferencia">
                                                        <i class="fas fa-university text-primary"></i> Transferencia Bancaria
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="metodo_pago" id="efectivo" value="efectivo">
                                                    <label class="form-check-label" for="efectivo">
                                                        <i class="fas fa-money-bill-wave text-success"></i> Efectivo
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="metodo_pago" id="tarjeta" value="tarjeta">
                                                    <label class="form-check-label" for="tarjeta">
                                                        <i class="fas fa-credit-card text-info"></i> Tarjeta de Crédito
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Información de pago -->
                                        <div class="mt-3 p-3 bg-light rounded" id="payment-info">
                                            <div id="info-transferencia">
                                                <h6><i class="fas fa-info-circle text-primary"></i> Transferencia Bancaria</h6>
                                                <p class="mb-1"><strong>Banco:</strong> Banco Industrial</p>
                                                <p class="mb-1"><strong>Cuenta:</strong> 123-456789-0</p>
                                                <p class="mb-1"><strong>Nombre:</strong> Travel Mayan World</p>
                                                <small class="text-muted">Enviar comprobante por WhatsApp al confirmar la reserva</small>
                                            </div>
                                            <div id="info-efectivo" style="display: none;">
                                                <h6><i class="fas fa-info-circle text-success"></i> Pago en Efectivo</h6>
                                                <p class="mb-1"><strong>Ubicación:</strong> Flores, Petén</p>
                                                <p class="mb-1"><strong>Horario:</strong> Lunes a Domingo 8:00 AM - 6:00 PM</p>
                                                <small class="text-muted">Se debe realizar el pago completo antes del tour</small>
                                            </div>
                                            <div id="info-tarjeta" style="display: none;">
                                                <h6><i class="fas fa-info-circle text-info"></i> Tarjeta de Crédito (Stripe)</h6>
                                                <p class="mb-1"><strong>Procesamiento:</strong> Redirección segura a Stripe Checkout</p>
                                                <small class="text-muted">Podrás pagar el total o solo un anticipo para confirmar</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Términos y condiciones -->
                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" id="terms" required>
                                    <label class="form-check-label" for="terms">
                                        Acepto los <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">términos y condiciones</a> *
                                    </label>
                                </div>

                                <!-- Tipo de pago (monto) -->
                                <div class="row mb-4" id="pagoTipoRow" style="display:none;">
                                    <div class="col-md-6">
                                        <label for="pago_tipo" class="form-label">Monto a pagar ahora</label>
                                        <select class="form-select" name="pago_tipo" id="pago_tipo">
                                            <option value="full" selected>Pago total</option>
                                            <option value="deposit">Anticipo <?= (int)(Config::DEPOSIT_RATE*100) ?>%</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <div class="alert alert-secondary w-100 mb-0" id="pagoAhoraBox">
                                            <div><strong>Pagar ahora:</strong> <span id="pagoAhoraMonto">$0</span> USD</div>
                                            <small class="text-muted d-block">Resto: <span id="pagoRestoMonto">$0</span> USD (48h antes del tour)</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Botones de acción -->
                                <div class="d-flex justify-content-between">
                                    <a href="/?route=tour/<?= $tour['id'] ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> Volver al Tour
                                    </a>
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fas fa-check"></i> Confirmar reserva
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Resumen de la reserva -->
                <div class="col-lg-4">
                    <div class="card shadow-lg sticky-top" style="top: 20px;">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Resumen de la Reserva</h5>
                        </div>
                        <div class="card-body">
                            <!-- Tour -->
                            <div class="d-flex mb-3">
                                <img src="<?= Helpers::tourImage($tour['imagen_principal'] ?? null, 'images/default-destination.jpg') ?>"
                                     class="rounded skeleton"
                                     style="width: 60px; height: 60px; object-fit: cover;"
                                     loading="lazy" decoding="async"
                                     alt="<?= htmlspecialchars($tour['nombre']) ?>">
                                <div class="ms-3">
                                    <h6 class="mb-1"><?= htmlspecialchars($tour['nombre']) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($tour['categoria_nombre'] ?? '') ?></small>
                                </div>
                            </div>

                            <!-- Detalles de la reserva -->
                            <hr>
                            <div class="booking-details">
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="fas fa-calendar text-primary"></i> Fecha:</span>
                                    <span><?= date('d/m/Y', strtotime($booking_data['fecha_salida'])) ?></span>
                                </div>
                                <?php if ($booking_data['fecha_salida'] !== $booking_data['fecha_regreso']): ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span><i class="fas fa-calendar text-primary"></i> Regreso:</span>
                                        <span><?= date('d/m/Y', strtotime($booking_data['fecha_regreso'])) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="fas fa-users text-primary"></i> Personas:</span>
                                    <span><?= $booking_data['numero_personas'] ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="fas fa-clock text-primary"></i> Duración:</span>
                                    <span><?= htmlspecialchars($tour['duracion']) ?></span>
                                </div>
                            </div>

                            <!-- Cálculo de precios -->
                            <hr>
                            <div class="price-breakdown">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Precio por persona:</span>
                                    <span>$<?= number_format($booking_data['precio_unitario'], 0) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?= $booking_data['numero_personas'] ?> persona(s):</span>
                                    <span>$<?= number_format($booking_data['precio_total'], 0) ?></span>
                                </div>
                                <?php $descuento = max(0, (int)$booking_data['precio_total'] - (int)$booking_data['precio_final']); ?>
                                <div class="d-flex justify-content-between mb-2 text-success" id="couponRow" style="<?= $descuento > 0 ? '' : 'display:none;' ?>">
                                    <span>Descuento:</span>
                                    <span id="couponAmount">- $<?= number_format($descuento, 0) ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 text-muted">
                                    <span>Impuestos y tarifas:</span>
                                    <span>$0</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-1">
                                    <strong>Total a pagar:</strong>
                                    <strong class="text-success h5" id="displayTotal">$<?= number_format($booking_data['precio_final'], 0) ?> USD</strong>
                                </div>
                                <small class="text-muted">Sin costos ocultos. El total se mantiene al confirmar.</small>
                            </div>

                            <!-- Cupón de descuento -->
                            <div class="mt-3">
                                <label for="couponCode" class="form-label">¿Tienes un cupón?</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="couponCode" name="coupon_code" placeholder="Ej. BIENVENIDO10">
                                    <button class="btn btn-outline-primary" type="button" id="applyCoupon">Aplicar</button>
                                </div>
                                <small class="text-muted">Ejemplos demo: BIENVENIDO10 (10%), RNPL5 (5%).</small>
                                <input type="hidden" name="coupon_discount" id="couponDiscount" value="0">
                            </div>

                            <!-- Información de contacto -->
                            <div class="border-top pt-3">
                                <h6><i class="fas fa-headset"></i> ¿Dudas?</h6>
                                <p class="mb-1">
                                    <i class="fas fa-phone text-primary"></i> 
                                    <a href="tel:<?= Config::COMPANY_PHONE ?>"><?= Config::COMPANY_PHONE ?></a>
                                </p>
                                <a href="https://wa.me/<?= str_replace(['+', ' ', '-'], '', Config::SOCIAL_WHATSAPP) ?>?text=Tengo%20dudas%20sobre%20mi%20reserva" 
                                   target="_blank" 
                                   class="btn btn-success btn-sm w-100">
                                    <i class="fab fa-whatsapp"></i> WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Términos y Condiciones -->
<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Términos y Condiciones</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <h6>1. Reservas y Pagos</h6>
                <p>Las reservas deben confirmarse con un pago mínimo del 50%. El saldo restante debe pagarse antes del inicio del tour.</p>
                
                <h6>2. Cancelaciones</h6>
                <p>Cancelaciones con más de 7 días de anticipación: reembolso del 80%. Menos de 7 días: no hay reembolso.</p>
                
                <h6>3. Cambios de Fecha</h6>
                <p>Los cambios de fecha están sujetos a disponibilidad y pueden generar costos adicionales.</p>
                
                <h6>4. Responsabilidades</h6>
                <p>Travel Mayan World actúa como intermediario. Los participantes viajan bajo su propia responsabilidad.</p>
                
                <h6>5. Condiciones Climáticas</h6>
                <p>Los tours pueden modificarse o cancelarse por condiciones climáticas adversas, ofreciendo alternativas o reembolso total.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<script>
// Mostrar información de pago según método seleccionado
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('input[name="metodo_pago"]');
    const paymentInfos = {
        'transferencia': document.getElementById('info-transferencia'),
        'efectivo': document.getElementById('info-efectivo'),
        'tarjeta': document.getElementById('info-tarjeta')
    };

    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            // Ocultar todos
            Object.values(paymentInfos).forEach(info => info.style.display = 'none');
            // Mostrar seleccionado
            if (paymentInfos[this.value]) {
                paymentInfos[this.value].style.display = 'block';
            }
        });
    });

    // Validación del formulario
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const terms = document.getElementById('terms');
        if (!terms.checked) {
            e.preventDefault();
            alert('Debes aceptar los términos y condiciones para continuar.');
            return;
        }
    });
});
</script>

<?php 
// Limpiar errores de sesión después de mostrar
unset($_SESSION['form_errors']);
unset($_SESSION['form_data']);
?>

<script>
// Mostrar selector de tipo de pago cuando es tarjeta y calcular monto a pagar ahora
document.addEventListener('DOMContentLoaded', function(){
    const metodoInputs = document.querySelectorAll('input[name="metodo_pago"]');
    const row = document.getElementById('pagoTipoRow');
    const select = document.getElementById('pago_tipo');
    const box = document.getElementById('pagoAhoraBox');
    const montoSpan = document.getElementById('pagoAhoraMonto');
    const total = <?= json_encode((float)$booking_data['precio_final']) ?>;
    const rate = <?= json_encode((float)Config::DEPOSIT_RATE) ?>;

    function updateVisibility(){
        const method = document.querySelector('input[name="metodo_pago"]:checked')?.value;
        row.style.display = method === 'tarjeta' ? '' : 'none';
        if (method === 'tarjeta') updateAmount();
    }
    function updateAmount(){
        if (!select) return;
        const type = select.value;
        const amount = type === 'deposit' ? Math.max(1, total * rate) : total;
        montoSpan.textContent = '$' + Math.round(amount).toLocaleString('es-GT');
        const restoSpan = document.getElementById('pagoRestoMonto');
        if (restoSpan) {
            const resto = Math.max(0, Math.round(total - amount));
            restoSpan.textContent = '$' + resto.toLocaleString('es-GT');
        }
    }
    metodoInputs.forEach(inp => inp.addEventListener('change', updateVisibility));
    if (select) select.addEventListener('change', updateAmount);
    updateVisibility();
});
</script>

<script>
// Lógica de cupones (demo, visual)
document.addEventListener('DOMContentLoaded', function(){
  const applyBtn = document.getElementById('applyCoupon');
  const input = document.getElementById('couponCode');
  const discountField = document.getElementById('couponDiscount');
  const row = document.getElementById('couponRow');
  const amountSpan = document.getElementById('couponAmount');
  const totalSpan = document.getElementById('displayTotal');
  const subtotal = <?= json_encode((int)$booking_data['precio_total']) ?>;
  const originalFinal = <?= json_encode((int)$booking_data['precio_final']) ?>;
  function calcDiscount(code){
    const c = (code||'').trim().toUpperCase();
    if (c === 'BIENVENIDO10') return Math.round(subtotal * 0.10);
    if (c === 'RNPL5') return Math.round(subtotal * 0.05);
    return 0;
  }
  function updateDisplay(){
    const code = input.value;
    const d = calcDiscount(code);
    discountField.value = d;
    if (d>0){
      row.style.display='flex';
      amountSpan.textContent = '- $' + d.toLocaleString('es-GT');
      totalSpan.textContent = '$' + Math.max(0, originalFinal - d).toLocaleString('es-GT') + ' USD';
    } else {
      row.style.display='none';
      amountSpan.textContent = '- $0';
      totalSpan.textContent = '$' + originalFinal.toLocaleString('es-GT') + ' USD';
    }
  }
  applyBtn?.addEventListener('click', updateDisplay);
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
