<?php
use App\Core\Config;
$title = 'Reserva Confirmada - Reserva Ahora, Paga Después | Travel Mayan World';
$metaDescription = 'Tu reserva ha sido confirmada. Completa el pago antes de la fecha límite para asegurar tu lugar.';
include __DIR__ . '/../layouts/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Success Header -->
            <div class="confirmation-header text-center mb-4">
                <div class="success-icon mb-3">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h1 class="h2 mb-2 text-success">¡Reserva Confirmada!</h1>
                <p class="lead text-muted">Tu lugar está asegurado. Solo queda completar el pago.</p>
                <div class="booking-code">
                    <span class="badge bg-primary fs-6 px-3 py-2">
                        Código: <?= htmlspecialchars($reserva['codigo_reserva']) ?>
                    </span>
                </div>
            </div>

            <!-- RNPL Explanation Card -->
            <div class="card shadow-lg border-0 mb-4">
                <div class="card-header bg-gradient text-white text-center py-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h3 class="mb-1">
                        <i class="fas fa-credit-card me-2"></i>
                        Reserva Ahora, Paga Después
                    </h3>
                    <p class="mb-0 opacity-90">¿Cómo funciona? Te lo explicamos paso a paso</p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="step-icon bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-check fs-4"></i>
                            </div>
                            <h6 class="fw-bold text-success">1. ¡Listo!</h6>
                            <small class="text-muted">Tu lugar está asegurado con solo $<?= number_format($reserva['rnpl_hold_amount'], 0) ?> USD</small>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="step-icon bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-clock fs-4"></i>
                            </div>
                            <h6 class="fw-bold text-warning">2. Relájate</h6>
                            <small class="text-muted">Te recordaremos cuándo completar el pago</small>
                        </div>
                        <div class="col-md-4 text-center mb-3">
                            <div class="step-icon bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                <i class="fas fa-credit-card fs-4"></i>
                            </div>
                            <h6 class="fw-bold text-primary">3. Paga el resto</h6>
                            <small class="text-muted">48 horas antes del tour</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Timeline -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt text-primary me-2"></i>
                        Cronograma de Pago
                    </h5>
                </div>
                <div class="card-body">
                    <div class="payment-timeline">
                        <!-- Current Status -->
                        <div class="timeline-item completed">
                            <div class="timeline-marker bg-success">
                                <i class="fas fa-check"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1 text-success">Reserva Confirmada</h6>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-bookmark me-1"></i>
                            <a href="<?= Config::getBaseUrl() ?>?route=booking/find" class="text-decoration-none">
                                Guarda este link para consultar tu reserva
                            </a>
                        </small>
                                        <small class="text-muted"><?= date('d/m/Y H:i') ?></small>
                                    </div>
                                    <span class="badge bg-success">Completado</span>
                                </div>
                                <p class="small text-muted mb-0">Hold de $<?= number_format($reserva['rnpl_hold_amount'], 0) ?> USD procesado exitosamente</p>
                            </div>
                        </div>

                        <!-- Payment Due -->
                        <div class="timeline-item <?= $is_urgent ? 'urgent' : ($is_overdue ? 'overdue' : 'pending') ?>">
                            <div class="timeline-marker <?= $is_overdue ? 'bg-danger' : ($is_urgent ? 'bg-warning' : 'bg-primary') ?>">
                                <i class="fas fa-<?= $is_overdue ? 'exclamation-triangle' : 'credit-card' ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1 <?= $is_overdue ? 'text-danger' : ($is_urgent ? 'text-warning' : 'text-primary') ?>">
                                            Completar Pago
                                        </h6>
                                        <small class="text-muted"><?= $due_date->format('d/m/Y H:i') ?></small>
                                    </div>
                                    <span class="badge <?= $is_overdue ? 'bg-danger' : ($is_urgent ? 'bg-warning' : 'bg-primary') ?>">
                                        <?= $is_overdue ? 'Vencido' : ($is_urgent ? 'Urgente' : 'Pendiente') ?>
                                    </span>
                                </div>
                                <p class="small text-muted mb-2">
                                    Pago restante: $<?= number_format($reserva['precio_final'] - $reserva['rnpl_hold_amount'], 0) ?> USD
                                </p>
                                
                                <?php if (!$is_overdue): ?>
                                <div class="countdown-timer mb-2" data-due="<?= $due_date->format('Y-m-d H:i:s') ?>">
                                    <div class="row text-center">
                                        <div class="col-3">
                                            <div class="countdown-block">
                                                <span class="countdown-number" data-unit="days">0</span>
                                                <small class="countdown-label">días</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="countdown-block">
                                                <span class="countdown-number" data-unit="hours">0</span>
                                                <small class="countdown-label">horas</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="countdown-block">
                                                <span class="countdown-number" data-unit="minutes">0</span>
                                                <small class="countdown-label">min</small>
                                            </div>
                                        </div>
                                        <div class="col-3">
                                            <div class="countdown-block">
                                                <span class="countdown-number" data-unit="seconds">0</span>
                                                <small class="countdown-label">seg</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="payment-actions">
                                    <?php if ($is_overdue): ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            El tiempo para completar el pago ha expirado. Contacta soporte.
                                        </div>
                                    <?php else: ?>
                                        <a href="<?= $payment_url ?>" class="btn btn-<?= $is_urgent ? 'warning' : 'primary' ?> btn-lg">
                                            <i class="fas fa-credit-card me-2"></i>
                                            Completar Pago Ahora
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Tour Date -->
                        <div class="timeline-item future">
                            <div class="timeline-marker bg-secondary">
                                <i class="fas fa-map-marked-alt"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">Día del Tour</h6>
                                        <small class="text-muted"><?= date('d/m/Y', strtotime($reserva['fecha_salida'])) ?></small>
                                    </div>
                                    <span class="badge bg-secondary">Próximo</span>
                                </div>
                                <p class="small text-muted mb-0"><?= htmlspecialchars($tour['nombre']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Detalles de tu Reserva
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Tour</h6>
                            <p class="mb-2"><?= htmlspecialchars($tour['nombre']) ?></p>
                            
                            <h6 class="text-primary">Fecha</h6>
                            <p class="mb-2"><?= date('d/m/Y', strtotime($reserva['fecha_salida'])) ?></p>
                            
                            <h6 class="text-primary">Personas</h6>
                            <p class="mb-2"><?= $reserva['numero_personas'] ?> persona<?= $reserva['numero_personas'] > 1 ? 's' : '' ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary">Precio Total</h6>
                            <p class="mb-2 h5 text-success">$<?= number_format($reserva['precio_final'], 0) ?> USD</p>
                            
                            <div class="payment-breakdown">
                                <div class="d-flex justify-content-between">
                                    <span class="text-success">Ya pagado (hold):</span>
                                    <span class="text-success">$<?= number_format($reserva['rnpl_hold_amount'], 0) ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span <?= $is_overdue ? 'class="text-danger"' : '' ?>>Restante por pagar:</span>
                                    <span <?= $is_overdue ? 'class="text-danger fw-bold"' : '' ?>>$<?= number_format($reserva['precio_final'] - $reserva['rnpl_hold_amount'], 0) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Support Section -->
            <?php
            // Obtener datos de contacto desde Config
            $supportEmail = Config::get('company_email', Config::COMPANY_EMAIL);
            $supportWhatsapp = preg_replace('/[^0-9]/', '', Config::get('whatsapp_phone', Config::SOCIAL_WHATSAPP));
            $supportPhone = Config::get('company_phone', Config::COMPANY_PHONE);
            ?>
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <h6 class="mb-3">¿Tienes preguntas?</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <a href="mailto:<?= htmlspecialchars($supportEmail) ?>" class="btn btn-outline-primary w-100 mb-2">
                                <i class="fas fa-envelope me-2"></i>Email
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="https://wa.me/<?= htmlspecialchars($supportWhatsapp) ?>" target="_blank" class="btn btn-outline-success w-100 mb-2">
                                <i class="fab fa-whatsapp me-2"></i>WhatsApp
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="tel:<?= htmlspecialchars($supportPhone) ?>" class="btn btn-outline-info w-100 mb-2">
                                <i class="fas fa-phone me-2"></i>Llamar
                            </a>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">Soporte disponible 24/7</small>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* RNPL Confirmation Styles */
.confirmation-header {
    padding: 2rem 0;
}

.success-icon {
    animation: successPulse 2s infinite;
}

@keyframes successPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.booking-code .badge {
    font-family: 'Courier New', monospace;
    letter-spacing: 1px;
}

/* Timeline Styles */
.payment-timeline {
    position: relative;
    padding-left: 2rem;
}

.timeline-item {
    position: relative;
    padding-bottom: 2rem;
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: -1.5rem;
    top: 2.5rem;
    bottom: -0.5rem;
    width: 2px;
    background: #e9ecef;
}

.timeline-marker {
    position: absolute;
    left: -2rem;
    top: 0.5rem;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.875rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    z-index: 2;
}

.timeline-item.urgent .timeline-marker {
    animation: urgentPulse 2s infinite;
}

@keyframes urgentPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); box-shadow: 0 0 20px rgba(255, 193, 7, 0.5); }
}

.timeline-item.overdue .timeline-marker {
    animation: overduePulse 1s infinite;
}

@keyframes overduePulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.15); box-shadow: 0 0 25px rgba(220, 53, 69, 0.7); }
}

.timeline-content {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-left: 1rem;
}

.timeline-item.completed .timeline-content {
    background: #f8f9fa;
    border-color: #28a745;
}

.timeline-item.urgent .timeline-content {
    border-color: #ffc107;
    background: #fff8e1;
}

.timeline-item.overdue .timeline-content {
    border-color: #dc3545;
    background: #fdf2f2;
}

/* Countdown Timer */
.countdown-timer {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
}

.countdown-block {
    text-align: center;
}

.countdown-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #495057;
}

.countdown-label {
    color: #6c757d;
    font-size: 0.75rem;
    text-transform: uppercase;
}

.payment-breakdown {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 6px;
    border-left: 4px solid #28a745;
}

/* Step Icons */
.step-icon {
    transition: transform 0.3s ease;
}

.step-icon:hover {
    transform: scale(1.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .confirmation-header {
        padding: 1rem 0;
    }
    
    .success-icon i {
        font-size: 3rem !important;
    }
    
    .timeline-item {
        padding-left: 0;
        margin-left: 1rem;
    }
    
    .countdown-number {
        font-size: 1.2rem;
    }
}
</style>

<script>
// Countdown Timer
document.addEventListener('DOMContentLoaded', function() {
    const countdownTimer = document.querySelector('.countdown-timer');
    if (countdownTimer) {
        const dueDate = new Date(countdownTimer.dataset.due).getTime();
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = dueDate - now;
            
            if (distance < 0) {
                countdownTimer.innerHTML = '<div class="alert alert-danger text-center">¡Tiempo agotado!</div>';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            document.querySelector('[data-unit="days"]').textContent = days;
            document.querySelector('[data-unit="hours"]').textContent = hours;
            document.querySelector('[data-unit="minutes"]').textContent = minutes;
            document.querySelector('[data-unit="seconds"]').textContent = seconds;
            
            // Change color when urgent (< 24 hours)
            if (distance < 24 * 60 * 60 * 1000) {
                countdownTimer.classList.add('urgent');
            }
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
