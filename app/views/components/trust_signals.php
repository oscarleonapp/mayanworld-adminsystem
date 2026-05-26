<?php
/**
 * Componente: Trust Signals (Señales de Confianza)
 * Muestra badges, certificaciones, garantías y social proof
 * para generar confianza en el visitante
 *
 * @param array $product - Datos del tour (opcional)
 * @param array $stats - Estadísticas generales (opcional)
 * @param string $context - 'hero', 'product', 'checkout' (contexto de uso)
 */

$context = $context ?? 'product';
$totalBookings = $stats['total_site_bookings'] ?? 5000;
$totalReviews = $stats['total_reviews'] ?? 1200;
$avgRating = $stats['avg_rating'] ?? 4.8;
$yearsExperience = $stats['years_experience'] ?? 15;
?>

<!-- Trust Signals Component -->
<div class="trust-signals-wrapper my-4">
    <?php if ($context === 'hero'): ?>
        <!-- Trust Signals para Hero Section (Homepage) -->
        <div class="trust-signals-hero">
            <div class="row g-3 align-items-center justify-content-center text-center">
                <div class="col-6 col-md-3">
                    <div class="trust-stat">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <div class="trust-stat-number"><?= number_format($totalBookings) ?>+</div>
                        <div class="trust-stat-label">Viajeros Felices</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="trust-stat">
                        <i class="fas fa-star fa-2x text-warning mb-2"></i>
                        <div class="trust-stat-number"><?= number_format($avgRating, 1) ?>/5</div>
                        <div class="trust-stat-label"><?= number_format($totalReviews) ?> Reseñas</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="trust-stat">
                        <i class="fas fa-award fa-2x text-success mb-2"></i>
                        <div class="trust-stat-number"><?= $yearsExperience ?>+</div>
                        <div class="trust-stat-label">Años de Experiencia</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="trust-stat">
                        <i class="fas fa-shield-alt fa-2x text-info mb-2"></i>
                        <div class="trust-stat-number">100%</div>
                        <div class="trust-stat-label">Seguro y Confiable</div>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif ($context === 'product'): ?>
        <!-- Trust Signals para Tour Page -->
        <div class="trust-signals-product">
            <!-- Badges de Confianza -->
            <div class="trust-badges-row">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="trust-badge-card text-center p-3">
                            <i class="fas fa-rotate-left fa-2x text-success mb-2"></i>
                            <h6 class="mb-1">Cancelación Gratis</h6>
                            <small class="text-muted">Hasta 48h antes</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="trust-badge-card text-center p-3">
                            <i class="fas fa-bolt fa-2x text-primary mb-2"></i>
                            <h6 class="mb-1">Confirmación Inmediata</h6>
                            <small class="text-muted">Reserva al instante</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="trust-badge-card text-center p-3">
                            <i class="fas fa-shield-alt fa-2x text-info mb-2"></i>
                            <h6 class="mb-1">Pago Seguro</h6>
                            <small class="text-muted">SSL Encriptado</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="trust-badge-card text-center p-3">
                            <i class="fas fa-headset fa-2x text-warning mb-2"></i>
                            <h6 class="mb-1">Soporte 24/7</h6>
                            <small class="text-muted">Estamos para ayudarte</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Garantías -->
            <div class="trust-guarantees mt-4 p-4 bg-light rounded">
                <h5 class="text-center mb-4">
                    <i class="fas fa-check-circle text-success me-2"></i>
                    Nuestra Garantía
                </h5>
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="guarantee-item">
                            <i class="fas fa-dollar-sign text-success me-2"></i>
                            <strong>Mejor Precio Garantizado</strong>
                            <p class="mb-0 small text-muted mt-1">Si encuentras un precio mejor, te igualamos</p>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0">
                        <div class="guarantee-item">
                            <i class="fas fa-certificate text-primary me-2"></i>
                            <strong>Guías Certificados</strong>
                            <p class="mb-0 small text-muted mt-1">Todos nuestros guías están certificados oficialmente</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="guarantee-item">
                            <i class="fas fa-heart text-danger me-2"></i>
                            <strong>100% Satisfacción</strong>
                            <p class="mb-0 small text-muted mt-1">O te devolvemos tu dinero</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Certificaciones y Partners -->
            <div class="certifications-section mt-4">
                <h6 class="text-center text-muted mb-3">Certificados y Partners</h6>
                <div class="certifications-logos d-flex justify-content-center align-items-center flex-wrap gap-4">
                    <div class="cert-logo" title="INGUAT Certificado">
                        <i class="fas fa-certificate fa-2x text-primary"></i>
                        <small class="d-block mt-1">INGUAT</small>
                    </div>
                    <div class="cert-logo" title="SafeTravels">
                        <i class="fas fa-shield-alt fa-2x text-success"></i>
                        <small class="d-block mt-1">SafeTravels</small>
                    </div>
                    <div class="cert-logo" title="TripAdvisor">
                        <i class="fab fa-tripadvisor fa-2x text-info"></i>
                        <small class="d-block mt-1">TripAdvisor</small>
                    </div>
                    <div class="cert-logo" title="Pagos Seguros">
                        <i class="fab fa-cc-stripe fa-2x text-primary"></i>
                        <small class="d-block mt-1">Stripe</small>
                    </div>
                    <div class="cert-logo" title="Visa">
                        <i class="fab fa-cc-visa fa-2x"></i>
                    </div>
                    <div class="cert-logo" title="Mastercard">
                        <i class="fab fa-cc-mastercard fa-2x"></i>
                    </div>
                    <div class="cert-logo" title="PayPal">
                        <i class="fab fa-cc-paypal fa-2x text-primary"></i>
                    </div>
                </div>
            </div>

            <!-- Social Proof Widget -->
            <div class="social-proof-widget mt-4 p-3 border rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="social-proof-icon me-3">
                                <i class="fas fa-users-check fa-2x text-primary"></i>
                            </div>
                            <div>
                                <strong class="d-block"><?= number_format($totalBookings) ?>+ viajeros</strong>
                                <small class="text-muted">han confiado en nosotros para sus aventuras</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end mt-2 mt-md-0">
                        <div class="tripadvisor-widget">
                            <i class="fab fa-tripadvisor text-success fa-2x"></i>
                            <div class="mt-1">
                                <strong><?= number_format($avgRating, 1) ?>/5</strong>
                                <div class="small text-muted"><?= number_format($totalReviews) ?> reviews</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    <?php elseif ($context === 'checkout'): ?>
        <!-- Trust Signals para Checkout Page -->
        <div class="trust-signals-checkout">
            <div class="checkout-trust-header text-center mb-4">
                <i class="fas fa-lock fa-3x text-success mb-3"></i>
                <h5>Reserva 100% Segura</h5>
                <p class="text-muted">Tu información está protegida con encriptación SSL de 256 bits</p>
            </div>

            <div class="checkout-trust-badges">
                <div class="row g-2">
                    <div class="col-4">
                        <div class="checkout-badge text-center p-2">
                            <i class="fas fa-shield-alt text-success"></i>
                            <small class="d-block mt-1">Pago Seguro</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="checkout-badge text-center p-2">
                            <i class="fas fa-user-lock text-primary"></i>
                            <small class="d-block mt-1">Datos Protegidos</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="checkout-badge text-center p-2">
                            <i class="fas fa-check-circle text-info"></i>
                            <small class="d-block mt-1">Garantía</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="payment-methods mt-3 text-center">
                <small class="text-muted d-block mb-2">Aceptamos:</small>
                <div class="payment-icons">
                    <i class="fab fa-cc-visa fa-2x me-2"></i>
                    <i class="fab fa-cc-mastercard fa-2x me-2"></i>
                    <i class="fab fa-cc-amex fa-2x me-2"></i>
                    <i class="fab fa-cc-paypal fa-2x"></i>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- Estilos -->
<style>
/* Hero Trust Signals */
.trust-signals-hero .trust-stat {
    padding: 1rem;
    transition: transform 0.2s ease;
}

.trust-signals-hero .trust-stat:hover {
    transform: translateY(-5px);
}

.trust-stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
    line-height: 1;
    margin: 0.5rem 0;
}

.trust-stat-label {
    font-size: 0.9rem;
    color: #6c757d;
    font-weight: 500;
}

/* Tour Trust Signals */
.trust-badge-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    transition: all 0.3s ease;
    height: 100%;
}

.trust-badge-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.1);
    transform: translateY(-2px);
}

.trust-badge-card h6 {
    font-size: 0.95rem;
    font-weight: 600;
    color: #333;
}

.trust-guarantees {
    background: linear-gradient(135deg, rgba(40, 167, 69, 0.05), rgba(13, 110, 253, 0.05));
    border: 1px solid #e9ecef;
}

.guarantee-item {
    padding: 0.5rem;
}

.guarantee-item strong {
    display: block;
    margin-bottom: 0.25rem;
}

.cert-logo {
    text-align: center;
    opacity: 0.7;
    transition: opacity 0.2s ease;
    padding: 0.5rem;
}

.cert-logo:hover {
    opacity: 1;
}

.cert-logo small {
    font-size: 0.7rem;
    color: #6c757d;
}

.social-proof-widget {
    background: #f8f9fa;
}

.tripadvisor-widget {
    text-align: center;
}

/* Checkout Trust Signals */
.checkout-trust-header i {
    opacity: 0.8;
}

.checkout-badge {
    background: #f8f9fa;
    border-radius: 8px;
    font-size: 0.85rem;
}

.payment-icons i {
    opacity: 0.6;
}

/* Responsive */
@media (max-width: 767.98px) {
    .trust-stat-number {
        font-size: 1.5rem;
    }

    .trust-stat-label {
        font-size: 0.8rem;
    }

    .trust-badge-card {
        padding: 0.75rem !important;
    }

    .trust-badge-card i {
        font-size: 1.5rem !important;
    }

    .trust-badge-card h6 {
        font-size: 0.85rem;
    }
}
</style>
