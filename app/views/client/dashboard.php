<?php
use App\Core\Config;
use App\Core\Helpers;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Mi Panel') ?> | Travel Agency</title>

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

        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
        }

        .stat-icon.primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-icon.success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon.warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-icon.danger { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--dark-text);
            margin: 8px 0 4px;
        }

        .stat-label {
            font-size: 13px;
            color: #6c757d;
            font-weight: 500;
        }

        /* Section Headers */
        .section-header {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark-text);
        }

        /* Booking Card */
        .booking-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 16px;
            transition: transform 0.3s;
        }

        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }

        .booking-code {
            font-size: 12px;
            color: #6c757d;
            font-weight: 600;
        }

        .booking-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--dark-text);
            margin: 4px 0;
        }

        .booking-date {
            font-size: 13px;
            color: #6c757d;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-confirmed { background: #d1fae5; color: #065f46; }
        .badge-completed { background: #dbeafe; color: #1e40af; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }

        /* Tour Card */
        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }

        .product-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }

        .product-body {
            padding: 16px;
        }

        .product-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
        }

        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
        }

        /* Buttons */
        .btn-custom {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary-custom {
            background: var(--primary-color);
            color: white;
            border: none;
        }

        .btn-primary-custom:hover {
            background: #0b5ed7;
            transform: translateY(-2px);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 16px;
        }

        .empty-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
        }

        .empty-text {
            font-size: 14px;
            color: #6c757d;
        }

        /* Tablet view (768px - 991px) */
        @media (min-width: 768px) and (max-width: 991.98px) {
            .stat-card {
                padding: 16px;
            }

            .stat-value {
                font-size: 24px;
            }

            .stat-icon {
                width: 42px;
                height: 42px;
                font-size: 18px;
            }

            .booking-card {
                padding: 16px;
            }

            .section-title {
                font-size: 18px;
            }

            .product-img {
                height: 160px;
            }
        }

        @media (max-width: 768px) {
            .stat-card {
                margin-bottom: 16px;
            }
        }
    </style>
</head>
<body>
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
                            <a class="nav-link active" href="<?= Config::getBaseUrl() ?>?route=client/dashboard">
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
    <div class="container mt-4 mb-5">
        <!-- Welcome Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1 class="section-title mb-1">Hola, <?= htmlspecialchars($user['nombre']) ?>! 👋</h1>
                <p class="text-muted">Bienvenido a tu panel de cliente</p>
            </div>
        </div>

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

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon primary">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value"><?= $stats['total_bookings'] ?></div>
                            <div class="stat-label">Total Reservas</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value"><?= $stats['pending_bookings'] ?></div>
                            <div class="stat-label">Pendientes</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value"><?= $stats['confirmed_bookings'] ?></div>
                            <div class="stat-label">Confirmadas</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon danger">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="ms-3 flex-grow-1">
                            <div class="stat-value"><?= $stats['cancelled_bookings'] ?></div>
                            <div class="stat-label">Canceladas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Próximas Reservas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="section-header d-flex justify-content-between align-items-center">
                    <h2 class="section-title">Próximas Reservas</h2>
                    <a href="<?= Config::getBaseUrl() ?>?route=client/bookings" class="btn btn-sm btn-outline-primary">
                        Ver Todas <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>

                <?php if (!empty($upcomingBookings)): ?>
                    <?php foreach ($upcomingBookings as $booking): ?>
                        <div class="booking-card">
                            <div class="row align-items-center">
                                <div class="col-lg-8 col-md-7">
                                    <div class="booking-code">
                                        #<?= htmlspecialchars($booking['codigo_reserva']) ?>
                                    </div>
                                    <div class="booking-title">
                                        <?= htmlspecialchars($booking['tour_nombre'] ?? 'Tour') ?>
                                    </div>
                                    <div class="booking-date">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?= date('d/m/Y', strtotime($booking['fecha_salida'])) ?>
                                        •
                                        <i class="fas fa-users me-1"></i>
                                        <?= $booking['numero_personas'] ?? 1 ?> personas
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-5 text-md-end mt-3 mt-md-0">
                                    <span class="badge-status badge-<?= $booking['estado'] ?>">
                                        <?= ucfirst($booking['estado']) ?>
                                    </span>
                                    <div class="mt-2">
                                        <a href="<?= Config::getBaseUrl() ?>?route=client/booking/<?= $booking['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            Ver Detalle
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="booking-card">
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <div class="empty-title">No tienes próximas reservas</div>
                            <div class="empty-text">¡Explora nuestros destinos y planea tu próxima aventura!</div>
                            <a href="<?= Config::getBaseUrl() ?>?route=tours" class="btn btn-primary-custom mt-3">
                                <i class="fas fa-search me-2"></i>Explorar Destinos
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tours Destacados -->
        <?php if (!empty($featuredProducts)): ?>
        <div class="row">
            <div class="col-12">
                <div class="section-header">
                    <h2 class="section-title">Destinos Destacados</h2>
                    <p class="text-muted">Descubre nuevas experiencias</p>
                </div>
            </div>

            <?php foreach ($featuredProducts as $product): ?>
                <div class="col-lg-4 col-md-6 col-sm-6 mb-4">
                    <div class="product-card">
                        <img src="<?= Helpers::tourImage($product['imagen_principal'] ?? null, 'images/default-destination.jpg') ?>"
                             alt="<?= htmlspecialchars($product['nombre']) ?>"
                             class="product-img"
                             loading="lazy"
                             decoding="async"
                             onerror="this.src='<?= Config::getBaseUrl() ?>placeholder.php?w=400&h=300&text=Sin+Imagen'">
                        <div class="product-body">
                            <div class="product-title"><?= htmlspecialchars($product['nombre']) ?></div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="product-price">$<?= number_format($product['precio'], 2) ?></div>
                                <a href="<?= Config::getBaseUrl() ?>?route=tour/<?= $product['id'] ?>"
                                   class="btn btn-sm btn-primary-custom">
                                    Ver Más
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
