<?php
use App\Core\Config;
use App\Core\Helpers;
$title = 'Buscar Mi Reserva | Travel Mayan World';
$metaDescription = 'Busca y consulta el estado de tu reserva ingresando tu código de reserva o email';
include __DIR__ . '/../layouts/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="text-center mb-5">
                <div class="mb-3">
                    <i class="fas fa-search-location text-primary" style="font-size: 3rem;"></i>
                </div>
                <h1 class="h2 mb-3">Buscar Mi Reserva</h1>
                <p class="text-muted lead">Consulta el estado de tu reserva ingresando tu código o email</p>
            </div>

            <!-- Search Card -->
            <div class="card shadow-lg border-0">
                <div class="card-body p-4 p-md-5">
                    <?php if (Helpers::getFlashMessage('error')): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= Helpers::getFlashMessage('error') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (Helpers::getFlashMessage('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= Helpers::getFlashMessage('success') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= Config::getBaseUrl() ?>?route=booking/find">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <!-- Search Method Tabs -->
                        <ul class="nav nav-pills nav-fill mb-4" id="searchTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="code-tab" data-bs-toggle="pill" data-bs-target="#code-search" type="button" role="tab">
                                    <i class="fas fa-ticket-alt me-2"></i>Por Código
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="email-tab" data-bs-toggle="pill" data-bs-target="#email-search" type="button" role="tab">
                                    <i class="fas fa-envelope me-2"></i>Por Email
                                </button>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="searchTabContent">
                            <!-- Search by Code -->
                            <div class="tab-pane fade show active" id="code-search" role="tabpanel">
                                <div class="mb-4">
                                    <label for="booking_code" class="form-label fw-semibold">
                                        <i class="fas fa-barcode text-primary me-2"></i>Código de Reserva
                                    </label>
                                    <input type="text"
                                           id="booking_code"
                                           name="booking_code"
                                           class="form-control form-control-lg"
                                           placeholder="Ej: RNPL123456 o BOOK789012"
                                           pattern="[A-Z0-9]{8,20}"
                                           title="Código de 8-20 caracteres alfanuméricos">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        El código está en tu email de confirmación
                                    </div>
                                </div>
                            </div>

                            <!-- Search by Email -->
                            <div class="tab-pane fade" id="email-search" role="tabpanel">
                                <div class="mb-4">
                                    <label for="cliente_email" class="form-label fw-semibold">
                                        <i class="fas fa-at text-primary me-2"></i>Email de Reserva
                                    </label>
                                    <input type="email"
                                           id="cliente_email"
                                           name="cliente_email"
                                           class="form-control form-control-lg"
                                           placeholder="tu@email.com">
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Te mostraremos todas tus reservas
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary btn-lg w-100 shadow">
                            <i class="fas fa-search me-2"></i>
                            Buscar Mi Reserva
                        </button>
                    </form>

                    <!-- Help Section -->
                    <div class="border-top mt-4 pt-4">
                        <h6 class="fw-semibold mb-3">
                            <i class="fas fa-question-circle text-muted me-2"></i>
                            ¿Necesitas ayuda?
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <i class="fas fa-envelope text-primary me-2 mt-1"></i>
                                    <div>
                                        <small class="fw-semibold d-block">Email de confirmación</small>
                                        <small class="text-muted">Revisa tu bandeja de entrada o spam</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex align-items-start">
                                    <i class="fab fa-whatsapp text-success me-2 mt-1"></i>
                                    <div>
                                        <small class="fw-semibold d-block">Contáctanos</small>
                                        <small class="text-muted">
                                            <a href="https://wa.me/50212345678" target="_blank" rel="noopener" class="text-decoration-none">
                                                WhatsApp: +502 1234-5678
                                            </a>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="text-center mt-4">
                <p class="text-muted mb-2">
                    <i class="fas fa-shield-alt text-success me-2"></i>
                    Tus datos están protegidos y seguros
                </p>
                <p class="text-muted small">
                    ¿No tienes una reserva aún?
                    <a href="<?= Config::getBaseUrl() ?>?route=tours" class="text-decoration-none">
                        Explora nuestros tours
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
.nav-pills .nav-link {
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.nav-pills .nav-link:not(.active) {
    background-color: #f8f9fa;
    color: #6c757d;
}

.nav-pills .nav-link:not(.active):hover {
    background-color: #e9ecef;
    color: #495057;
}

.nav-pills .nav-link.active {
    background: linear-gradient(135deg, #0d6efd, #0dcaf0);
}

.form-control-lg {
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-control-lg:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}

.btn-primary {
    background: linear-gradient(135deg, #0d6efd, #0dcaf0);
    border: none;
    border-radius: 8px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4);
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
