<?php

use App\Core\Config;
$title = 'Checkout - Paso 1: Información del Viajero | Travel Mayan World';
$metaDescription = 'Completa la información del viajero para proceder con tu reserva.';
include __DIR__ . '/../layouts/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Progress Steps -->
            <div class="checkout-progress mb-5">
                <div class="progress-container">
                    <div class="progress-step active" data-step="1">
                        <div class="step-circle">
                            <span class="step-number">1</span>
                        </div>
                        <div class="step-label">Información del Viajero</div>
                    </div>
                    <div class="progress-line"></div>
                    <div class="progress-step" data-step="2">
                        <div class="step-circle">
                            <span class="step-number">2</span>
                        </div>
                        <div class="step-label">Pago y Confirmación</div>
                    </div>
                </div>
            </div>

            <!-- Booking Summary Card -->
            <div class="card shadow-lg border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-shopping-cart me-2"></i>
                        <h5 class="mb-0">Resumen de tu Reserva</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <h6 class="fw-bold"><?= htmlspecialchars($tour['nombre'] ?? 'Tour') ?></h6>
                            <div class="booking-details">
                                <?php if (!empty($disponibilidad)): ?>
                                <div class="detail-item">
                                    <i class="fas fa-calendar text-primary me-2"></i>
                                    <span><?= date('d/m/Y', strtotime($disponibilidad['fecha_salida'])) ?></span>
                                    <?php if ($disponibilidad['fecha_regreso'] !== $disponibilidad['fecha_salida']): ?>
                                        - <?= date('d/m/Y', strtotime($disponibilidad['fecha_regreso'])) ?>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($horario_seleccionado)): ?>
                                <div class="detail-item">
                                    <i class="fas fa-clock text-primary me-2"></i>
                                    <?php
                                    $hp = explode(':', $horario_seleccionado);
                                    $hh = (int)($hp[0] ?? 0);
                                    $mmm = $hp[1] ?? '00';
                                    $ap = $hh >= 12 ? 'PM' : 'AM';
                                    $h12 = $hh % 12 ?: 12;
                                    ?>
                                    <span>Horario: <?= $h12 . ':' . $mmm . ' ' . $ap ?></span>
                                </div>
                                <?php endif; ?>
                                <div class="detail-item">
                                    <i class="fas fa-users text-primary me-2"></i>
                                    <span><?= $numero_personas ?? 1 ?> persona<?= ($numero_personas ?? 1) > 1 ? 's' : '' ?></span>
                                </div>
                                <?php if (!empty($tour['duracion_dias'])): ?>
                                <div class="detail-item">
                                    <i class="fas fa-clock text-primary me-2"></i>
                                    <span><?= $tour['duracion_dias'] ?> día<?= $tour['duracion_dias'] > 1 ? 's' : '' ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="pricing-summary">
                                <?php if (($precio_descuento ?? 0) > 0): ?>
                                    <div class="original-price text-muted">
                                        <s>$<?= number_format($precio_total ?? 0, 0) ?></s>
                                    </div>
                                    <div class="discount-savings text-success small">
                                        Ahorras $<?= number_format($precio_descuento ?? 0, 0) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="final-price h4 text-primary mb-0">
                                    $<?= number_format($precio_final ?? 0, 0) ?> USD
                                </div>
                                <small class="text-muted">Precio total</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Traveler Information Form -->
            <div class="card shadow-lg border-0">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2 text-primary"></i>
                        Información del Viajero Principal
                    </h5>
                    <small class="text-muted">Persona responsable de la reserva</small>
                </div>
                <div class="card-body">
                    <form id="travelerForm" action="<?= Config::getBaseUrl() ?>?route=booking/checkout-step1" method="POST" data-step="1">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <input type="hidden" name="tour_id" value="<?= $tour['id'] ?? '' ?>">
                        <input type="hidden" name="disponibilidad_id" value="<?= $disponibilidad['id'] ?? '' ?>">
                        <input type="hidden" name="numero_personas" value="<?= $numero_personas ?? 1 ?>">
                        <input type="hidden" name="horario_seleccionado" value="<?= htmlspecialchars($horario_seleccionado ?? '') ?>">
                        <input type="hidden" name="precio_final" value="<?= $precio_final ?? 0 ?>">
                        
                        <?php
                        // Asegurar que currentUser esté definido
                        $currentUser = $currentUser ?? null;
                        $datos = $datos ?? [];
                        ?>

                        <?php if ($currentUser): ?>
                            <!-- Usuario logueado - Mostrar info -->
                            <div class="col-12">
                                <div class="alert alert-info d-flex align-items-center mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <div>
                                        <strong>Reservando como:</strong> <?= htmlspecialchars($currentUser['nombre']) ?> (<?= htmlspecialchars($currentUser['email']) ?>)
                                        <br>
                                        <small>Si necesitas cambiar tus datos, puedes editarlos en tu <a href="<?= Config::getBaseUrl() ?>?route=client/profile">perfil</a></small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="row g-3">
                            <!-- Personal Information -->
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre completo *</label>
                                <input type="text" class="form-control" id="nombre" name="cliente_nombre" required
                                       <?= $currentUser ? 'readonly' : '' ?>
                                       placeholder="Ej: Juan Pérez"
                                       value="<?= htmlspecialchars($datos['cliente_nombre'] ?? $currentUser['nombre'] ?? '') ?>">
                                <div class="invalid-feedback">Por favor ingresa tu nombre completo</div>
                                <?php if ($currentUser): ?>
                                    <small class="text-muted">Datos de tu perfil</small>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="cliente_email" required
                                       <?= $currentUser ? 'readonly' : '' ?>
                                       placeholder="tu@email.com"
                                       value="<?= htmlspecialchars($datos['cliente_email'] ?? $currentUser['email'] ?? '') ?>">
                                <div class="invalid-feedback">Ingresa un email válido</div>
                                <small class="form-text text-muted"><?= $currentUser ? 'Email de tu cuenta' : 'Aquí recibirás la confirmación' ?></small>
                            </div>

                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono *</label>
                                <input type="tel" class="form-control" id="telefono" name="cliente_telefono" required
                                       placeholder="+502 1234-5678"
                                       value="<?= htmlspecialchars($datos['cliente_telefono'] ?? $currentUser['telefono'] ?? '') ?>">
                                <div class="invalid-feedback">Ingresa un teléfono válido</div>
                                <small class="form-text text-muted">Para contacto durante el tour</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="pais" class="form-label">País de origen</label>
                                <select class="form-select" id="pais" name="cliente_pais">
                                    <option value="">Selecciona tu país</option>
                                    <option value="GT" <?= ($datos['cliente_pais'] ?? '') === 'GT' ? 'selected' : '' ?>>Guatemala</option>
                                    <option value="US" <?= ($datos['cliente_pais'] ?? '') === 'US' ? 'selected' : '' ?>>Estados Unidos</option>
                                    <option value="CA" <?= ($datos['cliente_pais'] ?? '') === 'CA' ? 'selected' : '' ?>>Canadá</option>
                                    <option value="MX" <?= ($datos['cliente_pais'] ?? '') === 'MX' ? 'selected' : '' ?>>México</option>
                                    <option value="ES" <?= ($datos['cliente_pais'] ?? '') === 'ES' ? 'selected' : '' ?>>España</option>
                                    <option value="other">Otro</option>
                                </select>
                            </div>
                            
                            <!-- Emergency Contact -->
                            <div class="col-12">
                                <div class="emergency-contact-section mt-3">
                                    <h6 class="text-primary">
                                        <i class="fas fa-user-shield me-2"></i>
                                        Contacto de Emergencia (Recomendado)
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="emergencia_nombre" class="form-label">Nombre completo</label>
                                            <input type="text" class="form-control" id="emergencia_nombre" name="emergencia_nombre"
                                                   placeholder="Nombre del contacto de emergencia" value="<?= htmlspecialchars($datos['emergencia_nombre'] ?? '') ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="emergencia_telefono" class="form-label">Teléfono</label>
                                            <input type="tel" class="form-control" id="emergencia_telefono" name="emergencia_telefono"
                                                   placeholder="+502 1234-5678" value="<?= htmlspecialchars($datos['emergencia_telefono'] ?? '') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hotel / Pickup Location -->
                            <div class="col-12">
                                <label for="hotel_nombre" class="form-label fw-semibold">
                                    <i class="fas fa-hotel text-primary me-2"></i>
                                    Hotel o lugar de recogida
                                    <span class="badge bg-success ms-1" style="font-size:.7rem;font-weight:500;">Recomendado</span>
                                </label>
                                <input type="text" class="form-control" id="hotel_nombre" name="hotel_nombre"
                                       placeholder="Ej: Hotel Westin Camino Real, Zona 10"
                                       value="<?= htmlspecialchars($datos['hotel_nombre'] ?? '') ?>">
                                <small class="form-text text-muted">
                                    <i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                    Escribe el nombre y zona de tu hotel. Nuestro guía pasará a recogerte ahí.
                                </small>
                            </div>

                            <!-- Special Requirements -->
                            <div class="col-12">
                                <label for="requerimientos" class="form-label">
                                    <i class="fas fa-info-circle text-info me-2"></i>
                                    Requerimientos especiales o comentarios
                                </label>
                                <textarea class="form-control" id="requerimientos" name="notas_cliente" rows="3"
                                          placeholder="Ej: Dieta vegetariana, movilidad reducida, celebración especial..."><?= htmlspecialchars($datos['notas_cliente'] ?? '') ?></textarea>
                                <small class="form-text text-muted">Cualquier información que nos ayude a personalizar tu experiencia</small>
                            </div>
                            
                            <!-- Terms and Marketing -->
                            <div class="col-12">
                                <div class="terms-section">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="acceptTerms" name="accept_terms" required>
                                        <label class="form-check-label" for="acceptTerms">
                                            Acepto los <a href="<?= Config::getBaseUrl() ?>?route=terms" class="text-primary" target="_blank" rel="noopener">términos y condiciones</a>
                                            y las <a href="<?= Config::getBaseUrl() ?>?route=faq#faq11" class="text-primary" target="_blank" rel="noopener">políticas de cancelación</a> *
                                        </label>
                                        <div class="invalid-feedback">Debes aceptar los términos para continuar</div>
                                    </div>
                                    
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="acceptMarketing" name="accept_marketing">
                                        <label class="form-check-label" for="acceptMarketing">
                                            Quiero recibir ofertas especiales y novedades por email
                                        </label>
                                    </div>
                                    
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="acceptWhatsapp" name="accept_whatsapp">
                                        <label class="form-check-label" for="acceptWhatsapp">
                                            Acepto recibir recordatorios y confirmaciones por WhatsApp
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="checkout-actions mt-4 pt-3 border-top">
                            <div class="text-end mb-2">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearFormData()">
                                    <i class="fas fa-eraser me-1"></i>Limpiar formulario
                                </button>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <a href="<?= Config::getBaseUrl() ?>?route=tour/<?= $tour['id'] ?>"
                                       class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-arrow-left me-2"></i>Volver al tour
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        Continuar al Pago
                                        <i class="fas fa-arrow-right ms-2"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Trust Signals -->
            <div class="trust-signals text-center mt-4">
                <div class="row">
                    <div class="col-md-4">
                        <i class="fas fa-shield-alt text-success fs-4 mb-2"></i>
                        <div class="small">
                            <strong>Pago 100% Seguro</strong><br>
                            <span class="text-muted">Certificado SSL</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-rotate-left text-primary fs-4 mb-2"></i>
                        <div class="small">
                            <strong>Cancelación Flexible</strong><br>
                            <span class="text-muted">Hasta 24h antes</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <i class="fas fa-headset text-info fs-4 mb-2"></i>
                        <div class="small">
                            <strong>Soporte 24/7</strong><br>
                            <span class="text-muted">Antes, durante y después</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* Checkout Progress Styles */
.checkout-progress {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.progress-container {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    z-index: 2;
}

.step-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin-bottom: 0.5rem;
    transition: all 0.3s ease;
}

.progress-step.active .step-circle {
    background: #0d6efd;
    color: white;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.25);
}

.progress-step.completed .step-circle {
    background: #28a745;
    color: white;
}

.step-label {
    font-size: 0.9rem;
    font-weight: 500;
    color: #6c757d;
    max-width: 120px;
}

.progress-step.active .step-label {
    color: #0d6efd;
    font-weight: 600;
}

.progress-line {
    flex: 1;
    height: 2px;
    background: #e9ecef;
    margin: 0 1rem;
    position: relative;
    top: -25px;
}

.progress-step.active ~ .progress-line::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 0%;
    background: #0d6efd;
    animation: progressFill 0.5s ease-out forwards;
}

@keyframes progressFill {
    to { width: 100%; }
}

/* Form Enhancements */
.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.form-control.is-valid {
    border-color: #28a745;
}

.form-control.is-invalid {
    border-color: #dc3545;
}

/* Booking Details */
.booking-details {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 0.5rem;
}

.detail-item {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.detail-item:last-child {
    margin-bottom: 0;
}

/* Pricing Summary */
.pricing-summary {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid #0d6efd;
}

.original-price {
    font-size: 0.9rem;
}

.discount-savings {
    font-weight: 600;
    margin-bottom: 0.5rem;
}

/* Emergency Contact Section */
.emergency-contact-section {
    background: #f0f8ff;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #b3d9ff;
}

/* Terms Section */
.terms-section {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid #6c757d;
}

.terms-section .form-check {
    margin-bottom: 0.75rem;
}

.terms-section .form-check:last-child {
    margin-bottom: 0;
}

/* Trust Signals */
.trust-signals {
    margin-top: 2rem;
}

.trust-signals .col-md-4 {
    padding: 1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .checkout-progress {
        padding: 1rem;
    }
    
    .progress-container {
        flex-direction: column;
        gap: 1rem;
    }
    
    .progress-line {
        display: none;
    }
    
    .step-label {
        max-width: none;
    }
    
    .checkout-actions .row > div {
        margin-bottom: 0.5rem;
    }
    
    .pricing-summary {
        margin-top: 1rem;
        text-align: center !important;
    }
}

/* Form Validation Animations */
.was-validated .form-control:valid {
    animation: validInput 0.3s ease;
}

.was-validated .form-control:invalid {
    animation: invalidInput 0.3s ease;
}

@keyframes validInput {
    0% { border-color: #ced4da; }
    100% { border-color: #28a745; }
}

@keyframes invalidInput {
    0% { border-color: #ced4da; }
    100% { border-color: #dc3545; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('travelerForm');
    
    // Real-time validation
    const inputs = form.querySelectorAll('input[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (validateForm()) {
            proceedToPayment();
        }
    });

    function validateField(field) {
        const isValid = field.checkValidity();
        field.classList.toggle('is-valid', isValid);
        field.classList.toggle('is-invalid', !isValid);
        return isValid;
    }

    function validateForm() {
        let isValid = true;

        inputs.forEach(input => {
            if (!validateField(input)) {
                isValid = false;
            }
        });

        // Check terms acceptance if exists
        const acceptTerms = document.getElementById('acceptTerms');
        if (acceptTerms && !acceptTerms.checked) {
            acceptTerms.classList.add('is-invalid');
            isValid = false;
        } else if (acceptTerms) {
            acceptTerms.classList.remove('is-invalid');
        }

        return isValid;
    }

    function proceedToPayment() {
        console.log('📤 Enviando formulario al servidor...');

        // Mostrar indicador de carga
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...';
        }

        // IMPORTANTE: Enviar el formulario al servidor (no redirect manual)
        form.submit();
    }
    
    // Auto-save form data
    let saveTimeout;
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                const formData = new FormData(form);
                const data = {};
                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }
                localStorage.setItem('checkout_step1_draft', JSON.stringify(data));
            }, 1000);
        });
    });
    
    // Restore draft data
    try {
        const draftData = localStorage.getItem('checkout_step1_draft');
        if (draftData) {
            const data = JSON.parse(draftData);
            Object.keys(data).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field && field.type !== 'hidden') {
                    field.value = data[key];
                }
            });
        }
    } catch (e) {
        console.warn('Could not restore draft data');
    }
});

// Función para limpiar el formulario y localStorage
function clearFormData() {
    if (confirm('¿Estás seguro de que quieres limpiar todos los datos del formulario?')) {
        // Limpiar localStorage
        localStorage.removeItem('checkout_step1_draft');

        // Limpiar todos los campos del formulario
        const form = document.getElementById('travelerForm');
        const inputs = form.querySelectorAll('input:not([type="hidden"]), select, textarea');
        inputs.forEach(input => {
            if (input.type === 'checkbox' || input.type === 'radio') {
                input.checked = false;
            } else {
                input.value = '';
            }
            // Remover clases de validación
            input.classList.remove('is-valid', 'is-invalid');
        });

        alert('✅ Formulario limpiado correctamente');
    }
}
</script>

<!-- Estilos para campos readonly -->
<style>
.form-control[readonly],
.form-select[readonly] {
    background-color: #f8f9fa;
    cursor: not-allowed;
    opacity: 0.8;
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
