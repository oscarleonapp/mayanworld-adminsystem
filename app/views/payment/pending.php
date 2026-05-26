<?php
use App\Core\Config;
$title = $title ?? 'Pago Pendiente';
include __DIR__ . '/../layouts/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <!-- Encabezado -->
            <div class="text-center mb-4">
                <div class="mb-3">
                    <i class="fas fa-clock fa-4x text-warning"></i>
                </div>
                <h2 class="mb-2">Pago Pendiente</h2>
                <p class="text-muted">Completa tu pago para confirmar la reserva</p>
            </div>

            <!-- Card de Información de Reserva -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-ticket-alt me-2"></i>
                        Información de Reserva
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Código:</div>
                        <div class="col-sm-8">
                            <strong><?= htmlspecialchars($reserva['codigo_reserva']) ?></strong>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Tour:</div>
                        <div class="col-sm-8">
                            <?= htmlspecialchars($reserva['tour_nombre']) ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Personas:</div>
                        <div class="col-sm-8">
                            <?= $reserva['numero_personas'] ?>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-sm-4 text-muted">Total:</div>
                        <div class="col-sm-8">
                            <h4 class="text-primary mb-0">
                                $<?= number_format($reserva['precio_total'], 2) ?>
                            </h4>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card de Instrucciones de Pago -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Instrucciones de Pago
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> Haz clic en el botón de abajo para ir a la plataforma de pago segura de Paggo.
                    </div>

                    <ol class="mb-4">
                        <li class="mb-2">Haz clic en "Ir a Pagar"</li>
                        <li class="mb-2">Serás redirigido a la plataforma segura de Paggo</li>
                        <li class="mb-2">Completa tu pago siguiendo las instrucciones</li>
                        <li class="mb-2">Recibirás una confirmación por email</li>
                    </ol>

                    <!-- Botón de Pago -->
                    <?php if (!empty($reserva['payment_link_url'])): ?>
                        <a href="<?= htmlspecialchars($reserva['payment_link_url']) ?>"
                           target="_blank"
                           class="btn btn-success btn-lg w-100 mb-3">
                            <i class="fas fa-external-link-alt me-2"></i>
                            Ir a Pagar
                        </a>
                    <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error: No se pudo generar el link de pago. Por favor contacta a soporte.
                        </div>
                    <?php endif; ?>

                    <!-- Expiración del Link -->
                    <?php if (!empty($reserva['payment_link_expires_at'])): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-clock me-2"></i>
                            <strong>Este link expira el:</strong><br>
                            <?= date('d/m/Y H:i', strtotime($reserva['payment_link_expires_at'])) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card de Ayuda -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="fas fa-question-circle me-2"></i>
                        ¿Necesitas ayuda?
                    </h6>
                    <p class="small text-muted mb-3">
                        Si tienes problemas para completar tu pago, contáctanos:
                    </p>
                    <div class="d-flex flex-column gap-2">
                        <a href="mailto:<?= Config::CONTACT_EMAIL ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-envelope me-2"></i>
                            Email: <?= Config::CONTACT_EMAIL ?>
                        </a>
                        <a href="tel:<?= Config::CONTACT_PHONE ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-phone me-2"></i>
                            Teléfono: <?= Config::CONTACT_PHONE ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Botón de Verificar Estado -->
            <div class="text-center">
                <button type="button" class="btn btn-outline-secondary" id="verifyPaymentBtn">
                    <i class="fas fa-sync-alt me-2"></i>
                    Verificar Estado del Pago
                </button>
                <div id="verificationResult" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('verifyPaymentBtn').addEventListener('click', function() {
    const btn = this;
    const resultDiv = document.getElementById('verificationResult');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verificando...';

    // Recargar la página para verificar estado
    setTimeout(() => {
        window.location.reload();
    }, 500);
});
</script>

<style>
.card {
    border-radius: 10px;
    overflow: hidden;
}

.card-header {
    border-bottom: 3px solid rgba(0,0,0,0.1);
}

ol li {
    padding-left: 10px;
}

.alert {
    border-left: 4px solid;
}

.alert-info {
    border-left-color: #0dcaf0;
}

.alert-warning {
    border-left-color: #ffc107;
}

.alert-danger {
    border-left-color: #dc3545;
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
