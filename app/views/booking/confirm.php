<?php
use App\Core\Config;
use App\Core\Helpers;
include __DIR__ . '/../layouts/header.php'; ?>

<!-- Hero Success Section -->
<div class="success-hero-section">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <!-- Success Animation -->
                <div class="success-animation mb-4">
                    <div class="success-circle">
                        <div class="success-checkmark">
                            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                                <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                                <path class="checkmark-check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <h1 class="display-3 fw-bold gradient-text mb-3 animate-fade-in">¡Reserva Confirmada!</h1>
                <p class="lead text-white-50 mb-4 animate-fade-in-delay">Tu aventura en el Mundo Maya está a punto de comenzar</p>

                <!-- Quick Info Pills -->
                <div class="d-flex flex-wrap justify-content-center gap-3 mb-4 animate-fade-in-delay-2">
                    <div class="info-pill">
                        <i class="fas fa-calendar-alt me-2"></i>
                        <span><?= date('d M Y', strtotime($booking['fecha_salida'])) ?></span>
                    </div>
                    <div class="info-pill">
                        <i class="fas fa-users me-2"></i>
                        <span><?= $booking['numero_personas'] ?> <?= $booking['numero_personas'] == 1 ? 'persona' : 'personas' ?></span>
                    </div>
                    <div class="info-pill">
                        <i class="fas fa-ticket-alt me-2"></i>
                        <span class="booking-code-badge" data-code="<?= htmlspecialchars($booking['codigo_reserva']) ?>" title="Click para copiar">
                            <?= htmlspecialchars($booking['codigo_reserva']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <!-- Booking Details Card -->
            <div class="glass-card mb-4 slide-up">
                <div class="glass-card-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <h4 class="mb-1 fw-bold">
                                <i class="fas fa-ticket-alt me-2 text-primary"></i>
                                Detalles de tu Reserva
                            </h4>
                            <p class="mb-0 small text-muted">Información completa de tu viaje</p>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn-icon-action" onclick="shareBooking()" title="Compartir">
                                <i class="fas fa-share-alt"></i>
                            </button>
                            <button class="btn-icon-action" onclick="downloadPDF()" title="Descargar PDF">
                                <i class="fas fa-download"></i>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="glass-card-body">
                    <div class="row g-4">
                        <!-- Left Column - Trip Details -->
                        <div class="col-lg-6">
                            <div class="section-badge mb-3">
                                <i class="fas fa-map-marked-alt me-2"></i>
                                Tu Destino
                            </div>

                            <div class="destination-card">
                                <h5 class="fw-bold mb-2"><?= htmlspecialchars($booking['tour_nombre']) ?></h5>
                                <p class="text-muted small mb-3">
                                    <?= htmlspecialchars($booking['tour_descripcion_corta'] ?? 'Experiencia única en el Mundo Maya') ?>
                                </p>

                                <div class="info-grid">
                                    <div class="info-item">
                                        <div class="info-icon bg-primary-soft">
                                            <i class="fas fa-calendar-alt text-primary"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Salida</span>
                                            <span class="info-value"><?= date('d M Y', strtotime($booking['fecha_salida'])) ?></span>
                                        </div>
                                    </div>

                                    <div class="info-item">
                                        <div class="info-icon bg-success-soft">
                                            <i class="fas fa-calendar-check text-success"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Regreso</span>
                                            <span class="info-value"><?= date('d M Y', strtotime($booking['fecha_regreso'])) ?></span>
                                        </div>
                                    </div>

                                    <div class="info-item">
                                        <div class="info-icon bg-info-soft">
                                            <i class="fas fa-users text-info"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Viajeros</span>
                                            <span class="info-value"><?= $booking['numero_personas'] ?> <?= $booking['numero_personas'] == 1 ? 'persona' : 'personas' ?></span>
                                        </div>
                                    </div>

                                    <div class="info-item">
                                        <div class="info-icon bg-warning-soft">
                                            <i class="fas fa-clock text-warning"></i>
                                        </div>
                                        <div class="info-content">
                                            <span class="info-label">Duración</span>
                                            <span class="info-value">
                                                <?php
                                                $dias = ceil((strtotime($booking['fecha_regreso']) - strtotime($booking['fecha_salida'])) / 86400);
                                                echo $dias . ' ' . ($dias == 1 ? 'día' : 'días');
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column - Customer & Payment Details -->
                        <div class="col-lg-6">
                            <div class="section-badge mb-3">
                                <i class="fas fa-user me-2"></i>
                                Información del Viajero
                            </div>

                            <div class="customer-card mb-3">
                                <div class="info-item-horizontal">
                                    <i class="fas fa-user-circle text-primary"></i>
                                    <div>
                                        <span class="info-label">Nombre</span>
                                        <span class="info-value"><?= htmlspecialchars($booking['cliente_nombre']) ?></span>
                                    </div>
                                </div>

                                <div class="info-item-horizontal">
                                    <i class="fas fa-envelope text-success"></i>
                                    <div>
                                        <span class="info-label">Email</span>
                                        <span class="info-value"><?= htmlspecialchars($booking['cliente_email']) ?></span>
                                    </div>
                                </div>

                                <div class="info-item-horizontal">
                                    <i class="fas fa-phone text-info"></i>
                                    <div>
                                        <span class="info-label">Teléfono</span>
                                        <span class="info-value"><?= htmlspecialchars($booking['cliente_telefono']) ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="payment-summary-card">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="payment-icon-large">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold">Resumen de Pago</h6>
                                        <small class="text-muted">Detalles de la transacción</small>
                                    </div>
                                </div>

                                <div class="payment-detail-item">
                                    <span>Método de pago</span>
                                    <span class="badge-custom badge-info">
                                        <?php
                                        $metodo = $booking['metodo_pago'] ?? 'pendiente';
                                        $icon = $metodo == 'transferencia' ? 'exchange-alt' : ($metodo == 'stripe' ? 'credit-card' : 'money-bill-wave');
                                        ?>
                                        <i class="fas fa-<?= $icon ?> me-1"></i>
                                        <?= ucfirst(str_replace('_', ' ', $metodo)) ?>
                                    </span>
                                </div>

                                <div class="payment-detail-item">
                                    <span>Estado</span>
                                    <span class="badge-custom badge-<?= $booking['estado'] == 'confirmada' ? 'success' : ($booking['estado'] == 'pendiente' ? 'warning' : 'secondary') ?>">
                                        <i class="fas fa-<?= $booking['estado'] == 'confirmada' ? 'check-circle' : ($booking['estado'] == 'pendiente' ? 'clock' : 'info-circle') ?> me-1"></i>
                                        <?= ucfirst($booking['estado']) ?>
                                    </span>
                                </div>

                                <?php if (isset($paid_amount) && isset($pending_amount)): ?>
                                <div class="payment-amounts mt-3">
                                    <div class="amount-row">
                                        <span class="amount-label">
                                            <i class="fas fa-check-circle text-success me-1"></i>
                                            Pagado
                                        </span>
                                        <span class="amount-value text-success"><?= Helpers::formatPrice($paid_amount) ?></span>
                                    </div>
                                    <?php if ($pending_amount > 0): ?>
                                    <div class="amount-row">
                                        <span class="amount-label">
                                            <i class="fas fa-exclamation-circle text-warning me-1"></i>
                                            Pendiente
                                        </span>
                                        <span class="amount-value text-warning"><?= Helpers::formatPrice($pending_amount) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="divider-line"></div>
                                <?php endif; ?>

                                <div class="total-amount">
                                    <span>Total del Viaje</span>
                                    <span class="price-large"><?= Helpers::formatPrice($booking['precio_total']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Notes -->
                    <?php if (!empty($booking['notas_cliente'])): ?>
                    <div class="mt-4 pt-4 border-top">
                        <h6 class="fw-bold mb-2">
                            <i class="fas fa-comment me-2"></i>
                            Notas adicionales
                        </h6>
                        <div class="bg-light rounded p-3">
                            <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($booking['notas_cliente'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Payment Instructions -->
            <?php if ($booking['estado'] == 'pendiente'): ?>
            <div class="card border-warning shadow mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Instrucciones de Pago
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-secondary">
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-info-circle me-2"></i>
                                También puedes completar el pago con tarjeta de crédito de forma segura.
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-outline-primary" href="<?= Config::getBaseUrl() ?>?route=payment/checkout/<?= $booking['id'] ?>&type=deposit">
                                    <i class="fas fa-credit-card me-1"></i> Pagar anticipo <?= (int)(Config::DEPOSIT_RATE*100) ?>%
                                </a>
                                <a class="btn btn-primary" href="<?= Config::getBaseUrl() ?>?route=payment/checkout/<?= $booking['id'] ?>">
                                    <i class="fas fa-credit-card me-1"></i> Pagar total
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php if ($booking['metodo_pago'] == 'transferencia'): ?>
                        <h6 class="fw-bold mb-3">Datos para Transferencia Bancaria</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="bank-info bg-light rounded p-3">
                                    <div class="mb-2">
                                        <strong>Banco:</strong> BBVA
                                    </div>
                                    <div class="mb-2">
                                        <strong>Nombre:</strong> Agencia de Viajes MVP
                                    </div>
                                    <div class="mb-2">
                                        <strong>Cuenta:</strong> 0123456789
                                    </div>
                                    <div class="mb-2">
                                        <strong>CLABE:</strong> 012345678901234567
                                    </div>
                                    <div>
                                        <strong>Concepto:</strong> <?= htmlspecialchars($booking['codigo_reserva']) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-info">
                                    <h6 class="fw-bold mb-2">Importante:</h6>
                                    <ul class="mb-0 small">
                                        <li>Realiza la transferencia por el monto exacto</li>
                                        <li>Incluye tu código de reserva como concepto</li>
                                        <li>Envía el comprobante por WhatsApp al +52 55 1234-5678</li>
                                        <li>Tu reserva se confirmará en un máximo de 24 horas</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <div class="alert alert-info">
                            <h6 class="fw-bold mb-2">Contacto para Coordinar Pago</h6>
                            <p class="mb-2">Nos pondremos en contacto contigo para coordinar el pago en efectivo.</p>
                            <p class="mb-0">
                                <strong>WhatsApp:</strong> +52 55 1234-5678<br>
                                <strong>Email:</strong> pagos@agenciamvp.com
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Next Steps -->
            <div class="card border-0 shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list-check me-2"></i>
                        Próximos Pasos
                    </h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item <?= $booking['estado'] != 'pendiente' ? 'completed' : '' ?>">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="fw-bold">
                                    <?php if ($booking['estado'] == 'pendiente'): ?>
                                        <i class="fas fa-clock text-warning me-2"></i>
                                        Realizar Pago
                                    <?php else: ?>
                                        <i class="fas fa-check text-success me-2"></i>
                                        Pago Confirmado
                                    <?php endif; ?>
                                </h6>
                                <p class="text-muted small mb-0">
                                    <?= $booking['estado'] == 'pendiente' 
                                        ? 'Completa tu pago siguiendo las instrucciones arriba'
                                        : 'Tu pago ha sido procesado exitosamente' ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="fw-bold">
                                    <i class="fas fa-phone text-info me-2"></i>
                                    Confirmación por Llamada
                                </h6>
                                <p class="text-muted small mb-0">
                                    Te contactaremos 48 horas antes del viaje para confirmar detalles
                                </p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="fw-bold">
                                    <i class="fas fa-envelope text-secondary me-2"></i>
                                    Documentación
                                </h6>
                                <p class="text-muted small mb-0">
                                    Recibirás por email la documentación completa del viaje
                                </p>
                            </div>
                        </div>
                        
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6 class="fw-bold">
                                    <i class="fas fa-plane text-primary me-2"></i>
                                    ¡A Disfrutar!
                                </h6>
                                <p class="text-muted small mb-0">
                                    Día del viaje: <?= date('d/m/Y', strtotime($booking['fecha_salida'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($booking['estado'] == 'confirmada' && isset($pending_amount) && $pending_amount > 0): ?>
            <div class="card border-info shadow mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-hand-holding-usd me-2"></i>
                        Saldo pendiente
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-3">Tu reserva está confirmada por anticipo. Paga el saldo restante antes del tour para completar el pago.</p>
                    <div class="alert alert-info d-flex justify-content-between align-items-center">
                        <strong>Monto pendiente:</strong>
                        <span class="h5 mb-0 text-danger"><?= Helpers::formatPrice($pending_amount) ?></span>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="<?= Config::getBaseUrl() ?>?route=payment/checkout/<?= $booking['id'] ?>&type=balance">
                            <i class="fas fa-credit-card me-1"></i> Pagar saldo con tarjeta
                        </a>
                        <a class="btn btn-outline-secondary" href="mailto:<?= Config::COMPANY_EMAIL ?>?subject=Pago%20de%20saldo%20reserva%20<?= $booking['codigo_reserva'] ?>">
                            <i class="fas fa-envelope me-1"></i> Coordinar por email
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="action-buttons-grid slide-up-delay">
                <button class="action-btn action-btn-primary" onclick="window.print()">
                    <div class="action-btn-icon">
                        <i class="fas fa-print"></i>
                    </div>
                    <div class="action-btn-content">
                        <span class="action-btn-title">Imprimir</span>
                        <span class="action-btn-subtitle">Guarda una copia</span>
                    </div>
                </button>

                <a href="https://wa.me/525512345678?text=Hola, tengo una consulta sobre mi reserva <?= urlencode($booking['codigo_reserva']) ?>"
                   class="action-btn action-btn-success"
                   target="_blank">
                    <div class="action-btn-icon">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div class="action-btn-content">
                        <span class="action-btn-title">WhatsApp</span>
                        <span class="action-btn-subtitle">Contacto directo</span>
                    </div>
                </a>

                <a href="<?= Config::getBaseUrl() ?>?route=chat&reserva_id=<?= $booking['id'] ?>"
                   class="action-btn action-btn-info">
                    <div class="action-btn-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="action-btn-content">
                        <span class="action-btn-title">Chat Soporte</span>
                        <span class="action-btn-subtitle">Ayuda en línea</span>
                    </div>
                </a>

                <a href="<?= Config::getBaseUrl() ?>"
                   class="action-btn action-btn-outline">
                    <div class="action-btn-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="action-btn-content">
                        <span class="action-btn-title">Inicio</span>
                        <span class="action-btn-subtitle">Volver al sitio</span>
                    </div>
                </a>
            </div>
            
            <!-- Contact Information -->
            <div class="text-center mt-5 pt-4 border-top">
                <h6 class="fw-bold mb-3">¿Necesitas ayuda?</h6>
                <div class="row justify-content-center">
                    <div class="col-md-3 mb-3">
                        <div class="contact-item">
                            <i class="fas fa-phone fa-2x text-primary mb-2"></i>
                            <div>
                                <strong>Teléfono</strong>
                                <div class="text-muted small">+52 55 1234-5678</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="contact-item">
                            <i class="fab fa-whatsapp fa-2x text-success mb-2"></i>
                            <div>
                                <strong>WhatsApp</strong>
                                <div class="text-muted small">+52 55 1234-5678</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="contact-item">
                            <i class="fas fa-envelope fa-2x text-info mb-2"></i>
                            <div>
                                <strong>Email</strong>
                                <div class="text-muted small">info@agenciamvp.com</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="contact-item">
                            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                            <div>
                                <strong>Horarios</strong>
                                <div class="text-muted small">Lun-Vie 9:00-18:00</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS -->
<style>
:root {
    --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --gradient-success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --gradient-info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --gradient-warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --glass-bg: rgba(255, 255, 255, 0.85);
    --glass-border: rgba(255, 255, 255, 0.3);
    --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
    --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
    --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.16);
    --transition-smooth: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hero Success Section */
.success-hero-section {
    background: var(--gradient-primary);
    position: relative;
    overflow: hidden;
    min-height: 400px;
}

.success-hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background:
        radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
    pointer-events: none;
}

/* Success Animation */
.success-animation {
    position: relative;
    z-index: 1;
}

.success-circle {
    width: 120px;
    height: 120px;
    margin: 0 auto;
    position: relative;
    animation: scaleIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.success-checkmark {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: block;
    stroke-width: 3;
    stroke: #fff;
    stroke-miterlimit: 10;
    box-shadow: inset 0 0 0 #38ef7d;
    animation: fillGreen 0.4s ease-in-out 0.4s forwards, scale 0.3s ease-in-out 0.9s both;
}

.checkmark {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: block;
    stroke-width: 3;
    stroke: #fff;
    stroke-miterlimit: 10;
    box-shadow: inset 0 0 0 #38ef7d;
}

.checkmark-circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    stroke-width: 3;
    stroke-miterlimit: 10;
    stroke: #fff;
    fill: none;
    animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}

.checkmark-check {
    transform-origin: 50% 50%;
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    stroke: #fff;
    stroke-width: 3;
    animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
}

@keyframes stroke {
    100% {
        stroke-dashoffset: 0;
    }
}

@keyframes fillGreen {
    100% {
        box-shadow: inset 0 0 0 60px #38ef7d;
    }
}

@keyframes scale {
    0%, 100% {
        transform: none;
    }
    50% {
        transform: scale3d(1.1, 1.1, 1);
    }
}

@keyframes scaleIn {
    0% {
        opacity: 0;
        transform: scale(0.5);
    }
    100% {
        opacity: 1;
        transform: scale(1);
    }
}

/* Text Animations */
.gradient-text {
    background: linear-gradient(135deg, #fff 0%, #f0f0f0 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.animate-fade-in {
    animation: fadeIn 0.6s ease-out;
}

.animate-fade-in-delay {
    animation: fadeIn 0.6s ease-out 0.2s both;
}

.animate-fade-in-delay-2 {
    animation: fadeIn 0.6s ease-out 0.4s both;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Info Pills */
.info-pill {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    color: white;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    transition: var(--transition-smooth);
}

.info-pill:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.booking-code-badge {
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
    cursor: pointer;
    transition: var(--transition-smooth);
}

.booking-code-badge:hover {
    text-decoration: underline;
}

/* Glass Card System */
.glass-card {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    border: 1px solid var(--glass-border);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    transition: var(--transition-smooth);
}

.glass-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 48px rgba(0, 0, 0, 0.2);
}

.glass-card-header {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
    padding: 1.5rem 2rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.glass-card-body {
    padding: 2rem;
}

.btn-icon-action {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    border: 1px solid rgba(0, 0, 0, 0.1);
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition-smooth);
    cursor: pointer;
}

.btn-icon-action:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

/* Section Badge */
.section-badge {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-size: 0.875rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

/* Destination Card */
.destination-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

/* Info Grid */
.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.info-item {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    border: 1px solid rgba(0, 0, 0, 0.05);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition-smooth);
}

.info-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.info-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

.bg-primary-soft {
    background: rgba(102, 126, 234, 0.1);
}

.bg-success-soft {
    background: rgba(56, 239, 125, 0.1);
}

.bg-info-soft {
    background: rgba(79, 172, 254, 0.1);
}

.bg-warning-soft {
    background: rgba(255, 193, 7, 0.1);
}

.info-content {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

.info-value {
    font-size: 0.95rem;
    font-weight: 600;
    color: #212529;
    margin-top: 0.25rem;
}

/* Customer Card */
.customer-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 16px;
    padding: 1.5rem;
    border: 1px solid rgba(0, 0, 0, 0.05);
}

.info-item-horizontal {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.info-item-horizontal:last-child {
    border-bottom: none;
}

.info-item-horizontal i {
    font-size: 1.25rem;
    margin-top: 0.25rem;
}

.info-item-horizontal > div {
    display: flex;
    flex-direction: column;
    flex: 1;
}

/* Payment Summary Card */
.payment-summary-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 1.5rem;
    color: white;
}

.payment-icon-large {
    width: 48px;
    height: 48px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-right: 1rem;
}

.payment-detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.badge-custom {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.875rem;
}

.badge-info {
    background: rgba(79, 172, 254, 0.3);
    color: white;
}

.badge-success {
    background: rgba(56, 239, 125, 0.3);
    color: white;
}

.badge-warning {
    background: rgba(255, 193, 7, 0.3);
    color: white;
}

.badge-secondary {
    background: rgba(108, 117, 125, 0.3);
    color: white;
}

.payment-amounts {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1rem;
}

.amount-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
}

.amount-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

.amount-value {
    font-weight: 600;
    font-size: 1rem;
}

.divider-line {
    height: 1px;
    background: rgba(255, 255, 255, 0.2);
    margin: 1rem 0;
}

.total-amount {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    font-size: 1.125rem;
    font-weight: 700;
}

.price-large {
    font-size: 2rem;
    font-weight: 800;
}

/* Action Buttons Grid */
.action-buttons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

.action-btn {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    border-radius: 16px;
    border: none;
    text-decoration: none;
    transition: var(--transition-smooth);
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.action-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.2) 100%);
    opacity: 0;
    transition: var(--transition-smooth);
}

.action-btn:hover::before {
    opacity: 1;
}

.action-btn:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.action-btn-primary {
    background: var(--gradient-primary);
    color: white;
}

.action-btn-success {
    background: var(--gradient-success);
    color: white;
}

.action-btn-info {
    background: var(--gradient-info);
    color: white;
}

.action-btn-outline {
    background: white;
    color: #667eea;
    border: 2px solid #667eea;
}

.action-btn-icon {
    width: 56px;
    height: 56px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.action-btn-outline .action-btn-icon {
    background: rgba(102, 126, 234, 0.1);
}

.action-btn-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
}

.action-btn-title {
    font-size: 1.125rem;
    font-weight: 700;
    display: block;
}

.action-btn-subtitle {
    font-size: 0.875rem;
    opacity: 0.8;
    display: block;
    margin-top: 0.25rem;
}

/* Slide Up Animation */
.slide-up {
    animation: slideUp 0.6s ease-out;
}

.slide-up-delay {
    animation: slideUp 0.6s ease-out 0.3s both;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(40px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Timeline improvements */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 3px;
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}

.timeline-item {
    position: relative;
    padding-bottom: 2rem;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -38px;
    top: 5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: white;
    border: 3px solid #e9ecef;
    box-shadow: var(--shadow-sm);
    z-index: 1;
    transition: var(--transition-smooth);
}

.timeline-item.completed .timeline-marker {
    background: var(--gradient-success);
    border-color: #38ef7d;
    box-shadow: 0 0 0 4px rgba(56, 239, 125, 0.2);
}

.timeline-content {
    background: white;
    padding: 1.25rem;
    border-radius: 12px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: var(--shadow-sm);
    transition: var(--transition-smooth);
}

.timeline-content:hover {
    box-shadow: var(--shadow-md);
    transform: translateX(4px);
}

.contact-item {
    text-align: center;
    padding: 1rem;
    transition: var(--transition-smooth);
}

.contact-item:hover {
    transform: translateY(-4px);
}

.contact-item i {
    transition: var(--transition-smooth);
}

.contact-item:hover i {
    transform: scale(1.1);
}

@media print {
    .btn, .contact-item, .timeline-item:nth-child(n+2) {
        display: none !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
}

@media (max-width: 992px) {
    .info-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .action-buttons-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .success-hero-section {
        min-height: 300px;
    }

    .success-circle {
        width: 90px;
        height: 90px;
    }

    .checkmark, .success-checkmark {
        width: 90px;
        height: 90px;
    }

    .gradient-text {
        font-size: 2rem !important;
    }

    .info-pill {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
    }

    .glass-card-header,
    .glass-card-body {
        padding: 1.25rem;
    }

    .info-grid {
        grid-template-columns: 1fr;
    }

    .action-buttons-grid {
        grid-template-columns: 1fr;
    }

    .action-btn {
        padding: 1rem;
    }

    .action-btn-icon {
        width: 48px;
        height: 48px;
        font-size: 1.25rem;
    }

    .price-large {
        font-size: 1.5rem;
    }

    .timeline {
        padding-left: 0;
    }

    .timeline::before {
        left: 8px;
    }

    .timeline-marker {
        left: -20px;
    }
}
</style>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize
    initializeTimeline();
    initializeCopyFeatures();
    initializeShareFeatures();
    initializeIntersectionObserver();

    // Auto-update timeline based on booking status
    function initializeTimeline() {
        const status = '<?= $booking["estado"] ?>';

        if (status === 'confirmada' || status === 'pagada') {
            const firstItem = document.querySelector('.timeline-item');
            if (firstItem) {
                firstItem.classList.add('completed');
            }
        }

        // Smooth scroll to payment instructions if pending
        if (status === 'pendiente') {
            setTimeout(() => {
                const paymentCard = document.querySelector('.card.border-warning');
                if (paymentCard) {
                    paymentCard.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            }, 1500);
        }
    }

    // Enhanced copy to clipboard with toast notification
    function copyToClipboard(text, element, successMessage = '¡Copiado!') {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                showCopySuccess(element, successMessage);
            }).catch(err => {
                console.error('Error al copiar:', err);
                fallbackCopyToClipboard(text);
            });
        } else {
            fallbackCopyToClipboard(text);
        }
    }

    function fallbackCopyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showToast('Copiado al portapapeles', 'success');
        } catch (err) {
            console.error('Error al copiar:', err);
        }
        document.body.removeChild(textArea);
    }

    function showCopySuccess(element, message) {
        const originalHTML = element.innerHTML;
        element.innerHTML = `<i class="fas fa-check me-1"></i>${message}`;
        element.style.transition = 'all 0.3s ease';

        // Add pulse animation
        element.style.transform = 'scale(1.05)';

        setTimeout(() => {
            element.style.transform = 'scale(1)';
        }, 200);

        setTimeout(() => {
            element.innerHTML = originalHTML;
        }, 2000);
    }

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                ${message}
            </div>
        `;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: ${type === 'success' ? 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)' : 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            z-index: 9999;
            animation: slideInUp 0.3s ease-out;
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOutDown 0.3s ease-out';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }

    // Initialize copy features
    function initializeCopyFeatures() {
        // Booking code copy
        const bookingCodeBadges = document.querySelectorAll('.booking-code-badge, .badge.bg-light');
        bookingCodeBadges.forEach(badge => {
            badge.style.cursor = 'pointer';
            badge.addEventListener('click', function(e) {
                e.preventDefault();
                const code = this.dataset.code || this.textContent.trim();
                copyToClipboard(code, this);
            });
        });

        // Bank info copy
        document.querySelectorAll('.bank-info div').forEach(div => {
            if (div.innerHTML.includes('<strong>')) {
                div.style.cursor = 'pointer';
                div.title = 'Click para copiar';

                div.addEventListener('click', function() {
                    const text = this.textContent.split(': ')[1];
                    if (text) {
                        copyToClipboard(text, this);
                    }
                });
            }
        });

        // Customer info copy
        document.querySelectorAll('.info-value').forEach(el => {
            el.style.cursor = 'pointer';
            el.title = 'Click para copiar';
            el.addEventListener('click', function() {
                copyToClipboard(this.textContent.trim(), this);
            });
        });
    }

    // Share booking functionality
    function initializeShareFeatures() {
        window.shareBooking = function() {
            const bookingCode = '<?= $booking["codigo_reserva"] ?>';
            const bookingUrl = window.location.href;
            const shareData = {
                title: 'Mi Reserva - Mayan World',
                text: `Reserva confirmada: ${bookingCode}`,
                url: bookingUrl
            };

            if (navigator.share) {
                navigator.share(shareData)
                    .then(() => showToast('Compartido exitosamente', 'success'))
                    .catch((err) => console.log('Error sharing:', err));
            } else {
                copyToClipboard(bookingUrl, event.target, 'URL copiada');
            }
        };

        window.downloadPDF = function() {
            showToast('Generando PDF...', 'info');
            setTimeout(() => {
                window.print();
            }, 500);
        };
    }

    // Intersection Observer for scroll animations
    function initializeIntersectionObserver() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe elements
        document.querySelectorAll('.glass-card, .action-btn, .timeline-item').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
            observer.observe(el);
        });
    }

    // Add confetti effect on load (optional celebration)
    if ('<?= $booking["estado"] ?>' === 'confirmada' || '<?= $booking["estado"] ?>' === 'pagada') {
        setTimeout(() => {
            createConfetti();
        }, 1200);
    }

    function createConfetti() {
        const colors = ['#667eea', '#764ba2', '#38ef7d', '#11998e', '#4facfe'];
        const confettiCount = 50;
        const container = document.querySelector('.success-hero-section');

        for (let i = 0; i < confettiCount; i++) {
            setTimeout(() => {
                const confetti = document.createElement('div');
                confetti.style.cssText = `
                    position: absolute;
                    width: 10px;
                    height: 10px;
                    background: ${colors[Math.floor(Math.random() * colors.length)]};
                    top: -10px;
                    left: ${Math.random() * 100}%;
                    opacity: 0;
                    border-radius: ${Math.random() > 0.5 ? '50%' : '0'};
                    animation: confettiFall ${2 + Math.random() * 2}s linear forwards;
                `;
                container.appendChild(confetti);

                setTimeout(() => {
                    confetti.remove();
                }, 4000);
            }, i * 30);
        }
    }

    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes confettiFall {
            0% {
                opacity: 1;
                transform: translateY(0) rotate(0deg);
            }
            100% {
                opacity: 0;
                transform: translateY(100vh) rotate(720deg);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideOutDown {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(20px);
            }
        }
    `;
    document.head.appendChild(style);
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
