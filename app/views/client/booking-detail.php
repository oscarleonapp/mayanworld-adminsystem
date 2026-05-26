<?php
use App\Core\Helpers;
use App\Core\Config;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Detalle de Reserva') ?> | Travel Agency</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;
            --light-bg: #f8f9fa;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--light-bg);
            min-height: 100vh;
        }

        /* Navbar */
        .navbar-custom {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 12px 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 20px;
        }

        .nav-link {
            color: #6c757d !important;
            font-weight: 500;
            font-size: 14px;
            padding: 8px 16px !important;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background: rgba(13, 110, 253, 0.1);
            color: var(--primary-color) !important;
        }

        .nav-link.active {
            background: var(--primary-color);
            color: white !important;
        }

        /* Offcanvas Mobile Menu */
        @media (max-width: 991.98px) {
            .offcanvas {
                max-width: 300px;
                border-left: none;
                box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
            }

            .offcanvas-header {
                border-bottom: 1px solid #e5e7eb;
                padding: 20px 24px;
            }

            .offcanvas-title {
                font-weight: 700;
                color: var(--primary-color);
                font-size: 18px;
            }

            .offcanvas-body {
                padding: 16px;
                display: flex;
                flex-direction: column;
            }

            .offcanvas-body .navbar-nav {
                gap: 4px;
                flex-grow: 1;
                width: 100%;
                margin-left: 0 !important;
                flex-direction: column;
            }

            .offcanvas-body .nav-item {
                width: 100%;
            }

            .offcanvas-body .nav-link {
                display: block;
                width: 100%;
                padding: 12px 16px !important;
                font-size: 15px;
                border-radius: 10px;
            }

            .offcanvas-body .nav-link i {
                width: 22px;
                text-align: center;
                margin-right: 6px;
            }

            .offcanvas-body .nav-divider {
                width: 100%;
                border-top: 1px solid #e5e7eb;
                margin: 12px 0;
            }

            .offcanvas-body .nav-link.nav-logout {
                color: var(--danger-color) !important;
            }

            .offcanvas-body .nav-link.nav-logout:hover {
                background: rgba(239, 68, 68, 0.08);
                color: var(--danger-color) !important;
            }
        }

        /* Detail Card */
        .detail-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }

        .detail-header {
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 24px;
        }

        .booking-code-large {
            font-size: 14px;
            color: #6c757d;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        .booking-title-large {
            font-size: 28px;
            font-weight: 700;
            color: #212529;
            margin: 8px 0 12px;
        }

        .badge-status-large {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-pendiente { background: #fff3cd; color: #856404; }
        .badge-confirmada { background: #d1fae5; color: #065f46; }
        .badge-pagada { background: #d1fae5; color: #065f46; }
        .badge-completada { background: #dbeafe; color: #1e40af; }
        .badge-cancelada { background: #fee2e2; color: #991b1b; }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            font-size: 14px;
        }

        .info-value {
            color: #212529;
            font-size: 14px;
            text-align: right;
        }

        .price-total {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .alert-custom {
            border-left: 4px solid;
            border-radius: 8px;
        }

        @media (max-width: 768px) {
            .booking-title-large {
                font-size: 22px;
            }

            .info-row {
                flex-direction: column;
                gap: 4px;
            }

            .info-value {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <input type="hidden" id="csrf-token" value="<?= Helpers::generateCsrfToken() ?>">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="<?= Config::getBaseUrl() ?>">
                <i class="fas fa-plane"></i> Travel Agency
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileMenu" aria-controls="mobileMenu" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="offcanvas offcanvas-end" tabindex="-1" id="mobileMenu" aria-labelledby="mobileMenuLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="mobileMenuLabel">
                        <i class="fas fa-plane me-2"></i>Travel Agency
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
                </div>
                <div class="offcanvas-body">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="<?= Config::getBaseUrl() ?>?route=client/dashboard">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= Config::getBaseUrl() ?>?route=client/bookings">
                                <i class="fas fa-calendar-check"></i> Mis Reservas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= Config::getBaseUrl() ?>?route=tours">
                                <i class="fas fa-map-marked-alt"></i> Explorar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= Config::getBaseUrl() ?>?route=client/profile">
                                <i class="fas fa-user-circle"></i> Perfil
                            </a>
                        </li>
                        <li><div class="nav-divider"></div></li>
                        <li class="nav-item">
                            <a class="nav-link nav-logout" href="<?= Config::getBaseUrl() ?>?route=logout">
                                <i class="fas fa-sign-out-alt"></i> Salir
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-4">
        <!-- Back Button -->
        <a href="<?= Config::getBaseUrl() ?>?route=client/bookings" class="btn btn-outline-secondary mb-3">
            <i class="fas fa-arrow-left me-2"></i>Volver a Mis Reservas
        </a>

        <!-- Flash Messages -->
        <?php
        $flashMessages = Helpers::getFlashMessages();
        if (!empty($flashMessages)):
            foreach ($flashMessages as $msg):
        ?>
            <div class="alert alert-<?= $msg['type'] ?> alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($msg['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php
            endforeach;
        endif;
        ?>

        <div class="row">
            <!-- Main Details -->
            <div class="col-lg-8">
                <div class="detail-card">
                    <div class="detail-header">
                        <div class="booking-code-large">
                            Código de Reserva: #<?= htmlspecialchars($booking['codigo_reserva']) ?>
                        </div>
                        <div class="booking-title-large">
                            <?= htmlspecialchars($booking['tour_nombre'] ?? 'Tour') ?>
                        </div>
                        <span class="badge-status-large badge-<?= htmlspecialchars($booking['estado']) ?>">
                            <i class="fas fa-circle me-1" style="font-size: 8px;"></i>
                            <?= ucfirst(htmlspecialchars($booking['estado'])) ?>
                        </span>
                    </div>

                    <!-- Trip Information -->
                    <div class="mb-4">
                        <div class="section-title">
                            <i class="fas fa-calendar-alt"></i>
                            Información del Viaje
                        </div>
                        <div class="info-row">
                            <span class="info-label">Fecha de Salida</span>
                            <span class="info-value">
                                <i class="fas fa-calendar-day me-2"></i>
                                <?= date('d/m/Y', strtotime($booking['fecha_salida'])) ?>
                            </span>
                        </div>
                        <?php if (!empty($booking['fecha_regreso'])): ?>
                        <div class="info-row">
                            <span class="info-label">Fecha de Regreso</span>
                            <span class="info-value">
                                <i class="fas fa-calendar-check me-2"></i>
                                <?= date('d/m/Y', strtotime($booking['fecha_regreso'])) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-label">Número de Personas</span>
                            <span class="info-value">
                                <i class="fas fa-users me-2"></i>
                                <?= $booking['numero_personas'] ?? 1 ?> persona(s)
                            </span>
                        </div>
                        <?php if (!empty($tour['duracion_dias'])): ?>
                        <div class="info-row">
                            <span class="info-label">Duración</span>
                            <span class="info-value">
                                <i class="fas fa-clock me-2"></i>
                                <?= $tour['duracion_dias'] ?> día(s)
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Contact Information -->
                    <div class="mb-4">
                        <div class="section-title">
                            <i class="fas fa-user"></i>
                            Información de Contacto
                        </div>
                        <div class="info-row">
                            <span class="info-label">Nombre</span>
                            <span class="info-value"><?= htmlspecialchars($booking['cliente_nombre']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-value"><?= htmlspecialchars($booking['cliente_email']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Teléfono</span>
                            <span class="info-value"><?= htmlspecialchars($booking['cliente_telefono']) ?></span>
                        </div>
                    </div>

                    <!-- Notes -->
                    <?php if (!empty($booking['requerimientos_especiales'])): ?>
                    <div class="mb-4">
                        <div class="section-title">
                            <i class="fas fa-sticky-note"></i>
                            Requerimientos Especiales
                        </div>
                        <div class="alert alert-info alert-custom">
                            <?= nl2br(htmlspecialchars($booking['requerimientos_especiales'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Price Summary -->
                <div class="detail-card">
                    <div class="section-title">
                        <i class="fas fa-money-bill-wave"></i>
                        Resumen de Pago
                    </div>
                    <div class="info-row">
                        <span class="info-label">Precio Unitario</span>
                        <span class="info-value">$<?= number_format($booking['precio_unitario'], 2) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Personas</span>
                        <span class="info-value">× <?= $booking['numero_personas'] ?? 1 ?></span>
                    </div>
                    <?php if (!empty($booking['descuento']) && $booking['descuento'] > 0): ?>
                    <div class="info-row">
                        <span class="info-label">Descuento</span>
                        <span class="info-value text-success">-$<?= number_format($booking['descuento'], 2) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="info-row" style="border-top: 2px solid #e5e7eb; margin-top: 12px; padding-top: 12px;">
                        <span class="info-label" style="font-size: 16px;">Total</span>
                        <span class="price-total">$<?= number_format($booking['precio_total'], 2) ?></span>
                    </div>
                </div>

                <!-- Payment Info -->
                <div class="detail-card">
                    <div class="section-title">
                        <i class="fas fa-credit-card"></i>
                        Método de Pago
                    </div>
                    <div class="info-row">
                        <span class="info-label">Forma de Pago</span>
                        <span class="info-value"><?= ucfirst(htmlspecialchars($booking['forma_pago'] ?? 'N/A')) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Estado</span>
                        <span class="info-value">
                            <?php if ($booking['estado'] === 'pagada'): ?>
                                <span class="text-success"><i class="fas fa-check-circle"></i> Pagado</span>
                            <?php else: ?>
                                <span class="text-warning"><i class="fas fa-clock"></i> Pendiente</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <?php if (in_array($booking['estado'], ['pendiente', 'confirmada'])): ?>
                <div class="detail-card">
                    <div class="section-title">
                        <i class="fas fa-tasks"></i>
                        Acciones
                    </div>
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-danger" onclick="cancelBooking()">
                            <i class="fas fa-ban me-2"></i>Cancelar Reserva
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Contact Support -->
                <div class="detail-card">
                    <div class="section-title">
                        <i class="fas fa-headset"></i>
                        Ayuda
                    </div>
                    <p style="font-size: 13px; color: #6c757d; margin-bottom: 12px;">
                        ¿Tienes preguntas sobre tu reserva?
                    </p>
                    <a href="<?= Config::getBaseUrl() ?>?route=contact" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-envelope me-2"></i>Contactar Soporte
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function cancelBooking() {
            if (confirm('¿Estás seguro que deseas cancelar esta reserva? Esta acción no se puede deshacer.')) {
                const formData = new FormData();
                formData.append('csrf_token', document.getElementById('csrf-token').value);

                fetch('<?= Config::getBaseUrl() ?>?route=client/cancel-booking/<?= $booking['id'] ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Error al cancelar la reserva');
                    }
                })
                .catch(error => {
                    alert('Error al cancelar la reserva');
                    console.error(error);
                });
            }
        }
    </script>
</body>
</html>
