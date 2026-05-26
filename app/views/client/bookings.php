<?php
use App\Core\Helpers;
use App\Core\Config;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Mis Reservas') ?> | Travel Agency</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;
            --light-bg: #f8f9fa;
            --dark-text: #212529;
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

        /* Page Header */
        .page-header {
            background: white;
            padding: 24px 0;
            margin-bottom: 24px;
            border-bottom: 1px solid #e5e7eb;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 8px;
        }

        /* Filters */
        .filters-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }

        .filter-label {
            font-size: 13px;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1);
        }

        /* Booking Card */
        .booking-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 16px;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .booking-card.pending {
            border-left-color: var(--warning-color);
        }

        .booking-card.confirmada, .booking-card.pagada {
            border-left-color: var(--success-color);
        }

        .booking-card.completada {
            border-left-color: var(--info-color);
        }

        .booking-card.cancelada {
            border-left-color: var(--danger-color);
            opacity: 0.7;
        }

        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 12px;
        }

        .booking-code {
            font-size: 12px;
            color: #6c757d;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        .booking-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-text);
            margin: 4px 0;
        }

        .booking-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .booking-meta-item {
            font-size: 13px;
            color: #6c757d;
        }

        .booking-meta-item i {
            margin-right: 6px;
            color: var(--primary-color);
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-pendiente { background: #fff3cd; color: #856404; }
        .badge-confirmada { background: #d1fae5; color: #065f46; }
        .badge-pagada { background: #d1fae5; color: #065f46; }
        .badge-completada { background: #dbeafe; color: #1e40af; }
        .badge-cancelada { background: #fee2e2; color: #991b1b; }

        .booking-actions {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }

        .btn-custom {
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s;
            border: none;
        }

        .btn-primary-custom {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary-custom:hover {
            background: #0b5ed7;
        }

        .btn-danger-custom {
            background: white;
            color: var(--danger-color);
            border: 2px solid var(--danger-color);
        }

        .btn-danger-custom:hover {
            background: var(--danger-color);
            color: white;
        }

        /* Empty State */
        .empty-state {
            background: white;
            border-radius: 12px;
            padding: 60px 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .empty-icon {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 16px;
        }

        .empty-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
        }

        .empty-text {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 24px;
        }

        @media (max-width: 768px) {
            .booking-header {
                flex-direction: column;
            }

            .booking-meta {
                gap: 12px;
            }

            .booking-actions {
                flex-direction: column;
            }

            .btn-custom {
                width: 100%;
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
                            <a class="nav-link active" href="<?= Config::getBaseUrl() ?>?route=client/bookings">
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

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="page-title">Mis Reservas</h1>
            <p class="text-muted mb-0">Gestiona todas tus reservas de viaje</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mb-5">
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

        <!-- Filters -->
        <div class="filters-card">
            <form method="GET" action="<?= Config::getBaseUrl() ?>?route=client/bookings" class="row g-3">
                <div class="col-md-4">
                    <label class="filter-label">Buscar por código o nombre</label>
                    <input type="text"
                           class="form-control"
                           name="search"
                           placeholder="RES25ABC123 o Tikal..."
                           value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="filter-label">Estado</label>
                    <select class="form-select" name="status">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" <?= ($filters['status'] ?? '') === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="confirmada" <?= ($filters['status'] ?? '') === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                        <option value="pagada" <?= ($filters['status'] ?? '') === 'pagada' ? 'selected' : '' ?>>Pagada</option>
                        <option value="completada" <?= ($filters['status'] ?? '') === 'completada' ? 'selected' : '' ?>>Completada</option>
                        <option value="cancelada" <?= ($filters['status'] ?? '') === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    </select>
                </div>
                <div class="col-md-5 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary-custom me-2">
                        <i class="fas fa-search me-2"></i>Buscar
                    </button>
                    <a href="<?= Config::getBaseUrl() ?>?route=client/bookings" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- Results -->
        <?php if (!empty($bookings)): ?>
            <div class="mb-3">
                <small class="text-muted">
                    <i class="fas fa-list me-1"></i>
                    Mostrando <?= count($bookings) ?> reserva(s)
                </small>
            </div>

            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card <?= htmlspecialchars($booking['estado']) ?>">
                    <div class="booking-header">
                        <div>
                            <div class="booking-code">
                                #<?= htmlspecialchars($booking['codigo_reserva']) ?>
                            </div>
                            <div class="booking-title">
                                <?= htmlspecialchars($booking['tour_nombre'] ?? 'Tour') ?>
                            </div>
                        </div>
                        <span class="badge-status badge-<?= htmlspecialchars($booking['estado']) ?>">
                            <?= ucfirst(htmlspecialchars($booking['estado'])) ?>
                        </span>
                    </div>

                    <div class="booking-meta">
                        <div class="booking-meta-item">
                            <i class="fas fa-calendar"></i>
                            <?= date('d/m/Y', strtotime($booking['fecha_salida'])) ?>
                        </div>
                        <div class="booking-meta-item">
                            <i class="fas fa-users"></i>
                            <?= $booking['numero_personas'] ?? 1 ?> persona(s)
                        </div>
                        <div class="booking-meta-item">
                            <i class="fas fa-money-bill"></i>
                            $<?= number_format($booking['precio_total'], 2) ?>
                        </div>
                        <div class="booking-meta-item">
                            <i class="fas fa-credit-card"></i>
                            <?= ucfirst(htmlspecialchars($booking['metodo_pago'] ?? 'N/A')) ?>
                        </div>
                    </div>

                    <div class="booking-actions">
                        <a href="<?= Config::getBaseUrl() ?>?route=client/booking/<?= $booking['id'] ?>"
                           class="btn btn-primary-custom">
                            <i class="fas fa-eye me-1"></i>Ver Detalle
                        </a>

                        <?php if (in_array($booking['estado'], ['pendiente', 'confirmada'])): ?>
                            <button type="button"
                                    class="btn btn-danger-custom"
                                    onclick="cancelBooking(<?= $booking['id'] ?>, '<?= htmlspecialchars($booking['codigo_reserva']) ?>')">
                                <i class="fas fa-ban me-1"></i>Cancelar Reserva
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <div class="empty-title">No se encontraron reservas</div>
                <div class="empty-text">
                    <?php if (!empty($filters['search']) || !empty($filters['status'])): ?>
                        No hay reservas que coincidan con tu búsqueda. Intenta con otros filtros.
                    <?php else: ?>
                        Aún no tienes ninguna reserva. ¡Explora nuestros destinos y comienza tu aventura!
                    <?php endif; ?>
                </div>
                <a href="<?= Config::getBaseUrl() ?>?route=tours" class="btn btn-primary-custom">
                    <i class="fas fa-search me-2"></i>Explorar Destinos
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Cancel Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cancelar Reserva</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro que deseas cancelar la reserva <strong id="cancelBookingCode"></strong>?</p>
                    <p class="text-muted mb-0">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, mantener</button>
                    <button type="button" class="btn btn-danger" onclick="confirmCancel()">Sí, cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let bookingToCancel = null;
        const cancelModal = new bootstrap.Modal(document.getElementById('cancelModal'));

        function cancelBooking(id, code) {
            bookingToCancel = id;
            document.getElementById('cancelBookingCode').textContent = '#' + code;
            cancelModal.show();
        }

        async function confirmCancel() {
            if (!bookingToCancel) return;

            try {
                const formData = new FormData();
                formData.append('_method', 'POST');
                formData.append('csrf_token', document.getElementById('csrf-token').value);

                const response = await fetch('<?= Config::getBaseUrl() ?>?route=client/cancel-booking/' + bookingToCancel, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    window.location.reload();
                } else {
                    alert(result.message || 'Error al cancelar la reserva');
                }
            } catch (error) {
                alert('Error al cancelar la reserva');
                console.error(error);
            } finally {
                cancelModal.hide();
                bookingToCancel = null;
            }
        }
    </script>
</body>
</html>
