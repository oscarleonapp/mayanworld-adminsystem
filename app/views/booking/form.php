<?php
use App\Core\Config;
use App\Core\Helpers;
$extraStyles = [
    'css/components/forms.css',
    'css/components/cards.css'
];
include __DIR__ . '/../layouts/header.php';

$imageUrl = $tour['imagen_principal'] ?: Helpers::asset('images/default-destination.jpg');

// Asegurar que currentUser esté definido
$currentUser = $currentUser ?? null;
?>
<!-- Breadcrumb -->
<div class="container py-3">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= Config::getBaseUrl() ?>">Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= Config::getBaseUrl() ?>?route=tours">Destinos</a></li>
            <li class="breadcrumb-item">
                <a href="<?= Config::getBaseUrl() ?>?route=tour/<?= $tour['id'] ?>">
                    <?= htmlspecialchars($tour['nombre']) ?>
                </a>
            </li>
            <li class="breadcrumb-item active">Reservar</li>
        </ol>
    </nav>
</div>

<div class="container pb-5">
    <div class="row">
        <!-- Left Column - Tour Summary -->
        <div class="col-lg-4 order-lg-2">
            <div class="sticky-top" style="top: 100px;">
                <div class="card border-0 shadow mb-4 reveal-on-scroll">
                    <img src="<?= htmlspecialchars($imageUrl) ?>" 
                         class="card-img-top skeleton" 
                         alt="<?= htmlspecialchars($tour['nombre']) ?>"
                         loading="lazy" decoding="async"
                         style="height: 200px; object-fit: cover;">
                    
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><?= htmlspecialchars($tour['nombre']) ?></h5>
                        <p class="card-text text-muted small">
                            <?= htmlspecialchars(Helpers::truncate($tour['descripcion_corta'], 120)) ?>
                        </p>
                        
                        <div class="bg-light rounded p-3 mb-3">
                            <div class="row text-center g-2">
                                <div class="col-6">
                                    <i class="fas fa-clock text-primary d-block mb-1"></i>
                                    <small class="text-muted"><?= htmlspecialchars($tour['duracion']) ?></span>
                                </div>
                                <div class="col-6">
                                    <i class="fas fa-users text-primary d-block mb-1"></i>
                                    <small class="text-muted">Max <?= $tour['max_personas'] ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Price Summary -->
                        <div class="price-summary">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Precio por persona:</span>
                                <span class="fw-bold"><?= $pricing['has_discount'] ? $pricing['discount'] : $pricing['original'] ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted">Número de personas:</span>
                                <span id="people-count-display" class="fw-bold">1</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h6 mb-0">Total estimado:</span>
                                <span id="total-price" class="h5 text-success fw-bold mb-0">
                                    <?= $pricing['has_discount'] ? $pricing['discount'] : $pricing['original'] ?>
                                </span>
                            </div>
                            <small class="text-muted d-block mt-2">*Precio final sujeto a disponibilidad y fechas seleccionadas</span>
                        </div>
                    </div>
                </div>
                
                <!-- Security Info -->
                <div class="card border-0 shadow">
                    <div class="card-body text-center">
                        <h6 class="mb-3">
                            <i class="fas fa-shield-alt text-success me-2"></i>
                            Reserva Segura
                        </h6>
                        <div class="small text-muted">
                            <div class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                SSL encriptado
                            </div>
                            <div class="mb-2">
                                <i class="fas fa-check text-success me-2"></i>
                                Cancelación gratuita
                            </div>
                            <div>
                                <i class="fas fa-check text-success me-2"></i>
                                Sin cargos ocultos
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Booking Form -->
        <div class="col-lg-8 order-lg-1">
            <div class="card border-0 shadow reveal-on-scroll">
                <div class="card-header bg-white">
                    <h3 class="card-title mb-0" id="booking-form-title">
                        <i class="fas fa-calendar-check text-primary me-2"></i>
                        Completa tu Reserva
                    </h3>
                    <p class="text-muted mb-0 mt-2">Llena todos los campos para procesar tu reserva</p>
                </div>
                
                <div class="card-body p-4">
                    <!-- Progress Steps -->
                    <div class="stepper mb-4 align-items-center">
                        <div class="step-item active">
                            <div class="step-circle">1</div>
                            <span class="step-label">Información</span>
                        </div>
                        <div class="step-line d-none d-md-block"></div>
                        <div class="step-item">
                            <div class="step-circle">2</div>
                            <span class="step-label">Fechas</span>
                        </div>
                        <div class="step-line d-none d-md-block"></div>
                        <div class="step-item">
                            <div class="step-circle">3</div>
                            <span class="step-label">Pago</span>
                        </div>
                    </div>
                    
                    <form method="POST" id="booking-form" class="form-shell" novalidate aria-labelledby="booking-form-title">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        
                        <!-- Personal Information Section -->
                        <div class="form-shell__section mb-5">
                            <h5 class="form-shell__title mb-4">
                                <i class="fas fa-user text-primary me-2"></i>
                                Información Personal
                            </h5>

                            <?php if ($currentUser): ?>
                                <!-- Usuario logueado - Mostrar info y permitir editar si es necesario -->
                                <div class="alert alert-info d-flex align-items-center mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <div>
                                        <strong>Reservando como:</strong> <?= htmlspecialchars($currentUser['nombre']) ?>
                                        <br>
                                        <small>Si necesitas cambiar tus datos, puedes editarlos en tu <a href="<?= Config::getBaseUrl() ?>?route=client/profile">perfil</a></small>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="form-grid form-grid--two">
                                <div class="form-field">
                                    <label for="cliente_nombre" class="form-label">Nombre Completo *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <input type="text"
                                               class="form-control"
                                               id="cliente_nombre"
                                               name="cliente_nombre"
                                               required
                                               placeholder="Tu nombre completo"
                                               <?= $currentUser ? 'readonly' : '' ?>
                                               value="<?= htmlspecialchars($_POST['cliente_nombre'] ?? $currentUser['nombre'] ?? '') ?>">
                                    </div>
                                    <?php if ($currentUser): ?>
                                        <small class="text-muted">Los datos se toman de tu perfil</small>
                                    <?php endif; ?>
                                </div>

                                <div class="form-field">
                                    <label for="cliente_email" class="form-label">Email *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email"
                                               class="form-control"
                                               id="cliente_email"
                                               name="cliente_email"
                                               required
                                               placeholder="tu@email.com"
                                               <?= $currentUser ? 'readonly' : '' ?>
                                               value="<?= htmlspecialchars($_POST['cliente_email'] ?? $currentUser['email'] ?? '') ?>">
                                    </div>
                                    <?php if ($currentUser): ?>
                                        <small class="text-muted">Email de tu cuenta</small>
                                    <?php endif; ?>
                                </div>

                                <div class="form-field">
                                    <label for="cliente_telefono" class="form-label">Teléfono *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-phone"></i>
                                        </span>
                                        <input type="tel"
                                               class="form-control"
                                               id="cliente_telefono"
                                               name="cliente_telefono"
                                               required
                                               placeholder="+502 1234-5678"
                                               value="<?= htmlspecialchars($_POST['cliente_telefono'] ?? $currentUser['telefono'] ?? '') ?>">
                                    </div>
                                    <?php if (!$currentUser): ?>
                                        <small class="text-muted">Incluye código de país (ej. +502 para Guatemala)</small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-field">
                                    <label for="numero_personas" class="form-label">Número de Personas *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-users"></i>
                                        </span>
                                        <select class="form-select" 
                                                id="numero_personas" 
                                                name="numero_personas" 
                                                required>
                                            <option value="">Seleccionar...</option>
                                            <?php for ($i = 1; $i <= min($tour['max_personas'], 10); $i++): ?>
                                                <option value="<?= $i ?>" <?= (($_POST['numero_personas'] ?? 1) == $i) ? 'selected' : '' ?>>
                                                    <?= $i ?> <?= $i == 1 ? 'persona' : 'personas' ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-field form-field--full">
                                    <label for="cliente_direccion" class="form-label">Dirección</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </span>
                                        <textarea class="form-control" 
                                                  id="cliente_direccion" 
                                                  name="cliente_direccion" 
                                                  rows="2" 
                                                  placeholder="Dirección completa (opcional)"><?= htmlspecialchars($_POST['cliente_direccion'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Travel Dates Section -->
                        <div class="form-shell__section mb-5">
                            <h5 class="form-shell__title mb-4">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>
                                Fechas del Viaje
                            </h5>
                            
                            <div class="form-grid form-grid--two">
                                <div class="form-field">
                                    <label for="fecha_salida" class="form-label">Fecha de Salida *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-calendar"></i>
                                        </span>
                                        <input type="date" 
                                               class="form-control" 
                                               id="fecha_salida" 
                                               name="fecha_salida" 
                                               required 
                                               min="<?= date('Y-m-d', strtotime($tour['fecha_inicio'])) ?>"
                                               max="<?= date('Y-m-d', strtotime($tour['fecha_fin'])) ?>"
                                               value="<?= htmlspecialchars($_POST['fecha_salida'] ?? '') ?>">
                                    </div>
                                    <span class="form-hint">
                                        Disponible desde <?= date('d/m/Y', strtotime($tour['fecha_inicio'])) ?> 
                                        hasta <?= date('d/m/Y', strtotime($tour['fecha_fin'])) ?>
                                    </span>
                                </div>
                                
                                <div class="form-field">
                                    <label for="fecha_regreso" class="form-label">Fecha de Regreso *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-calendar"></i>
                                        </span>
                                        <input type="date" 
                                               class="form-control" 
                                               id="fecha_regreso" 
                                               name="fecha_regreso" 
                                               required 
                                               value="<?= htmlspecialchars($_POST['fecha_regreso'] ?? '') ?>">
                                    </div>
                                    <span class="form-hint">Se calculará automáticamente según la duración</span>
                                </div>
                                
                                <?php if ($availability): ?>
                                <div class="form-field form-field--full">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Disponibilidad seleccionada:</strong> 
                                        <?= date('d/m/Y', strtotime($availability['fecha'])) ?> 
                                        (<?= $availability['espacios_disponibles'] ?> espacios disponibles)
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Additional Information -->
                        <div class="form-shell__section mb-5">
                            <h5 class="form-shell__title mb-4">
                                <i class="fas fa-comment text-primary me-2"></i>
                                Información Adicional
                            </h5>
                            
                            <div class="form-field form-field--full">
                                <label for="notas_cliente" class="form-label">Comentarios o Solicitudes Especiales</label>
                                <textarea class="form-control" 
                                          id="notas_cliente" 
                                          name="notas_cliente" 
                                          rows="4" 
                                          placeholder="Comparte cualquier información que consideres importante para tu viaje (alergias, necesidades especiales, celebraciones, etc.)"><?= htmlspecialchars($_POST['notas_cliente'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Payment Method -->
                        <div class="form-shell__section mb-5">
                            <h5 class="form-shell__title mb-4">
                                <i class="fas fa-credit-card text-primary me-2"></i>
                                Método de Pago
                            </h5>
                            
                            <div class="payment-methods">
                                <div class="form-check payment-option mb-3">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="metodo_pago" 
                                           id="transferencia" 
                                           value="transferencia" 
                                           <?= (($_POST['metodo_pago'] ?? 'transferencia') == 'transferencia') ? 'checked' : '' ?>>
                                    <label class="form-check-label d-flex align-items-center" for="transferencia">
                                        <div class="payment-icon me-3">
                                            <i class="fas fa-university fa-2x text-primary"></i>
                                        </div>
                                        <div>
                                            <strong>Transferencia Bancaria</strong>
                                            <div class="text-muted small">Transfiere directamente a nuestra cuenta</div>
                                        </div>
                                    </label>
                                </div>
                                
                                <div class="form-check payment-option mb-3">
                                    <input class="form-check-input" 
                                           type="radio" 
                                           name="metodo_pago" 
                                           id="deposito" 
                                           value="deposito" 
                                           <?= (($_POST['metodo_pago'] ?? '') == 'deposito') ? 'checked' : '' ?>>
                                    <label class="form-check-label d-flex align-items-center" for="deposito">
                                        <div class="payment-icon me-3">
                                            <i class="fas fa-hand-holding-usd fa-2x text-success"></i>
                                        </div>
                                        <div>
                                            <strong>Depósito/Efectivo</strong>
                                            <div class="text-muted small">Coordinar entrega en efectivo</div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Terms and Conditions -->
                        <div class="form-shell__section mb-4">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="accept_terms" 
                                       name="accept_terms" 
                                       value="1" 
                                       required>
                                <label class="form-check-label" for="accept_terms">
                                    He leído y acepto los 
                                    <a href="<?= Config::getBaseUrl() ?>?route=terms" target="_blank">términos y condiciones</a> 
                                    y la 
                                    <a href="<?= Config::getBaseUrl() ?>?route=privacy" target="_blank">política de privacidad</a>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-check-circle me-2"></i>
                                Confirmar reserva
                            </button>
                            <a href="<?= Config::getBaseUrl() ?>?route=tour/<?= $tour['id'] ?>" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>
                                Volver al Tour
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>



<!-- Estilos adicionales para campos readonly -->
<style>
.form-control[readonly] {
    background-color: #f8f9fa;
    cursor: not-allowed;
    opacity: 0.8;
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('booking-form');
    const peopleSelect = document.getElementById('numero_personas');
    const fechaSalida = document.getElementById('fecha_salida');
    const fechaRegreso = document.getElementById('fecha_regreso');
    const dateValidationMessage = 'La fecha de regreso no puede ser anterior a la fecha de salida';
    
    // Price calculation
    const basePrice = <?= $tour['precio_descuento'] ?: $tour['precio'] ?>;
    
    function updatePriceCalculation() {
        const people = parseInt(peopleSelect.value) || 1;
        const total = basePrice * people;
        
        document.getElementById('people-count-display').textContent = people;
        document.getElementById('total-price').textContent = new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN'
        }).format(total);
    }
    
    peopleSelect?.addEventListener('change', updatePriceCalculation);

    function validateDates() {
        if (!fechaSalida?.value || !fechaRegreso?.value) {
            fechaRegreso?.setCustomValidity('');
            return true;
        }

        const startDate = new Date(fechaSalida.value);
        const endDate = new Date(fechaRegreso.value);

        if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
            fechaRegreso.setCustomValidity('');
            return true;
        }

        if (startDate > endDate) {
            fechaRegreso.setCustomValidity(dateValidationMessage);
            return false;
        }

        fechaRegreso.setCustomValidity('');
        return true;
    }
    
    // Auto-calculate return date based on duration
    fechaSalida?.addEventListener('change', function() {
        if (this.value) {
            const startDate = new Date(this.value);
            const duration = '<?= $tour["duracion"] ?>';
            
            // Extract days from duration (assuming format like "3 días", "1 semana", etc.)
            let days = 1;
            if (duration.includes('día')) {
                days = parseInt(duration.match(/\d+/)[0]) || 1;
            } else if (duration.includes('semana')) {
                days = (parseInt(duration.match(/\d+/)[0]) || 1) * 7;
            }
            
            const endDate = new Date(startDate);
            endDate.setDate(startDate.getDate() + days);
            
            fechaRegreso.value = endDate.toISOString().split('T')[0];
            fechaRegreso.min = this.value;
            validateDates();
        }
    });

    fechaRegreso?.addEventListener('change', validateDates);
    
    // Form validation
    form?.addEventListener('submit', function(e) {
        e.preventDefault();

        if (!validateDates()) {
            this.classList.add('was-validated');
            return;
        }
        
        if (!this.checkValidity()) {
            e.stopPropagation();
            this.classList.add('was-validated');
            
            // Focus first invalid field
            const firstInvalid = this.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }
        
        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalContent = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
        submitBtn.disabled = true;
        
        // Submit form
        fetch(window.location.href, {
            method: 'POST',
            body: new FormData(this),
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to confirmation
                window.location.href = '<?= Config::getBaseUrl() ?>?route=booking/confirm&code=' + data.booking_code;
            } else {
                // Show errors
                alert(data.message || 'Error al procesar la reserva');
                submitBtn.innerHTML = originalContent;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la reserva');
            submitBtn.innerHTML = originalContent;
            submitBtn.disabled = false;
        });
    });
    
    // Initialize price calculation
    updatePriceCalculation();
    
    // Phone number formatting
    const phoneInput = document.getElementById('cliente_telefono');
    phoneInput?.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 10) {
            value = value.replace(/(\d{2})(\d{2})(\d{4})(\d{4})/, '+$1 $2 $3-$4');
        }
        e.target.value = value;
    });
    
    // Email validation
    const emailInput = document.getElementById('cliente_email');
    emailInput?.addEventListener('blur', function() {
        const email = this.value;
        if (email && !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            this.setCustomValidity('Por favor ingresa un email válido');
        } else {
            this.setCustomValidity('');
        }
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
