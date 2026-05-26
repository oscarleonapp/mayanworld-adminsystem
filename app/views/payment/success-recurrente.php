<?php
use App\Core\Config;
$title = $title ?? 'Pago Recibido';
include __DIR__ . '/../layouts/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <!-- Encabezado -->
            <div class="text-center mb-4">
                <div class="mb-3">
                    <i class="fas fa-check-circle fa-5x text-success"></i>
                </div>
                <h2 class="mb-2">¡Pago Recibido!</h2>
                <p class="text-muted">Estamos procesando tu pago</p>
            </div>

            <!-- Card de Información -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-ticket-alt me-2"></i>
                        Detalles de tu Reserva
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>¡Tu pago ha sido recibido exitosamente!</strong><br>
                        Estamos procesando tu reserva. Recibirás un email de confirmación en los próximos minutos.
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-5 text-muted">Código de Reserva:</div>
                        <div class="col-sm-7">
                            <strong class="text-primary"><?= htmlspecialchars($reserva['codigo_reserva']) ?></strong>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-5 text-muted">Tour:</div>
                        <div class="col-sm-7">
                            <?= htmlspecialchars($reserva['tour_nombre']) ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-5 text-muted">Personas:</div>
                        <div class="col-sm-7">
                            <?= $reserva['numero_personas'] ?>
                        </div>
                    </div>

                    <?php if (!empty($reserva['fecha_tour'])): ?>
                    <div class="row mb-3">
                        <div class="col-sm-5 text-muted">Fecha del Tour:</div>
                        <div class="col-sm-7">
                            <?= date('d/m/Y', strtotime($reserva['fecha_tour'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-sm-5 text-muted">Total Pagado:</div>
                        <div class="col-sm-7">
                            <h4 class="text-success mb-0">
                                $<?= number_format($reserva['precio_total'], 2) ?>
                            </h4>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-5 text-muted">Email de Contacto:</div>
                        <div class="col-sm-7">
                            <?= htmlspecialchars($reserva['cliente_email']) ?>
                        </div>
                    </div>

                    <?php if (!empty($reserva['cliente_telefono'])): ?>
                    <div class="row mb-3">
                        <div class="col-sm-5 text-muted">Teléfono:</div>
                        <div class="col-sm-7">
                            <?= htmlspecialchars($reserva['cliente_telefono']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Card de Próximos Pasos -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list-check me-2"></i>
                        Próximos Pasos
                    </h5>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-3">
                            <strong>Confirmación por Email:</strong><br>
                            <span class="text-muted small">
                                Recibirás un email con todos los detalles de tu reserva en los próximos minutos.
                            </span>
                        </li>
                        <li class="mb-3">
                            <strong>Guarda tu Código de Reserva:</strong><br>
                            <span class="text-muted small">
                                Tu código <strong class="text-primary"><?= htmlspecialchars($reserva['codigo_reserva']) ?></strong>
                                es importante para cualquier consulta.
                            </span>
                        </li>
                        <li class="mb-3">
                            <strong>Preparativos del Tour:</strong><br>
                            <span class="text-muted small">
                                Te enviaremos información detallada sobre el punto de encuentro, horarios y qué llevar.
                            </span>
                        </li>
                        <li class="mb-0">
                            <strong>¿Preguntas?</strong><br>
                            <span class="text-muted small">
                                Puedes contactarnos en cualquier momento usando los datos de abajo.
                            </span>
                        </li>
                    </ol>
                </div>
            </div>

            <!-- Card de Contacto -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h6 class="mb-3">
                        <i class="fas fa-headset me-2"></i>
                        ¿Necesitas ayuda?
                    </h6>
                    <p class="small text-muted mb-3">
                        Estamos aquí para ayudarte. Contáctanos:
                    </p>
                    <div class="d-flex flex-column gap-2">
                        <a href="mailto:<?= Config::CONTACT_EMAIL ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-envelope me-2"></i>
                            <?= Config::CONTACT_EMAIL ?>
                        </a>
                        <a href="tel:<?= Config::CONTACT_PHONE ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-phone me-2"></i>
                            <?= Config::CONTACT_PHONE ?>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="d-grid gap-2">
                <a href="<?= Config::getBaseUrl() ?>?route=booking/confirmation&code=<?= urlencode($reserva['codigo_reserva']) ?>"
                   class="btn btn-primary btn-lg">
                    <i class="fas fa-file-invoice me-2"></i>
                    Ver Detalles Completos de la Reserva
                </a>
                <a href="<?= Config::getBaseUrl() ?>?route=tours"
                   class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver a Explorar Tours
                </a>
            </div>
        </div>
    </div>
</div>

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

.alert-success {
    border-left-color: #198754;
}

.fa-check-circle {
    animation: scaleIn 0.5s ease-out;
}

@keyframes scaleIn {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.1);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
