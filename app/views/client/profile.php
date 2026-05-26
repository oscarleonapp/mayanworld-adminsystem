<?php
use App\Core\Config;
use App\Core\Helpers;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Mi Perfil') ?> | Travel Agency</title>

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

        /* Profile Card */
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }

        .profile-header {
            text-align: center;
            padding-bottom: 24px;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 32px;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            margin: 0 auto 16px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .profile-name {
            font-size: 24px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 4px;
        }

        .profile-email {
            color: #6c757d;
            font-size: 14px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #212529;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
        }

        .form-text {
            font-size: 12px;
            color: #6c757d;
            margin-top: 4px;
        }

        .btn-custom {
            padding: 12px 24px;
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

        .password-toggle-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            transition: color 0.3s;
        }

        .password-toggle-icon:hover {
            color: var(--primary-color);
        }

        .divider {
            margin: 32px 0;
            border-top: 2px solid #e5e7eb;
        }

        @media (max-width: 768px) {
            .profile-card {
                padding: 24px 20px;
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
                            <a class="nav-link active" href="<?= Config::getBaseUrl() ?>?route=client/profile">
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

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="profile-card">
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?= strtoupper(substr($user['nombre'], 0, 1)) ?>
                        </div>
                        <div class="profile-name"><?= htmlspecialchars($user['nombre']) ?></div>
                        <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
                    </div>

                    <!-- Update Profile Form -->
                    <form method="POST" action="<?= Config::getBaseUrl() ?>?route=client/profile" id="profileForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <!-- Personal Information -->
                        <div class="section-title">
                            <i class="fas fa-user-edit"></i>
                            Información Personal
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">
                                    Nombre Completo <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                       class="form-control"
                                       id="nombre"
                                       name="nombre"
                                       value="<?= htmlspecialchars($user['nombre']) ?>"
                                       required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="telefono" class="form-label">
                                    Teléfono
                                </label>
                                <input type="tel"
                                       class="form-control"
                                       id="telefono"
                                       name="telefono"
                                       value="<?= htmlspecialchars($user['telefono'] ?? '') ?>"
                                       placeholder="+502 1234-5678">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="email" class="form-label">
                                Email <span class="badge bg-secondary">No editable</span>
                            </label>
                            <input type="email"
                                   class="form-control"
                                   id="email"
                                   value="<?= htmlspecialchars($user['email']) ?>"
                                   disabled>
                            <div class="form-text">
                                El email no se puede cambiar. Contacta a soporte si necesitas actualizarlo.
                            </div>
                        </div>

                        <!-- Divider -->
                        <div class="divider"></div>

                        <!-- Change Password -->
                        <div class="section-title">
                            <i class="fas fa-key"></i>
                            Cambiar Contraseña
                        </div>

                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            Deja estos campos vacíos si no deseas cambiar tu contraseña.
                        </div>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">
                                Contraseña Actual
                            </label>
                            <div class="position-relative">
                                <input type="password"
                                       class="form-control"
                                       id="current_password"
                                       name="current_password"
                                       placeholder="••••••••">
                                <i class="fas fa-eye password-toggle-icon" onclick="togglePassword('current_password')"></i>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">
                                    Nueva Contraseña
                                </label>
                                <div class="position-relative">
                                    <input type="password"
                                           class="form-control"
                                           id="new_password"
                                           name="new_password"
                                           placeholder="••••••••">
                                    <i class="fas fa-eye password-toggle-icon" onclick="togglePassword('new_password')"></i>
                                </div>
                                <div class="form-text">Mínimo 8 caracteres</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">
                                    Confirmar Nueva Contraseña
                                </label>
                                <div class="position-relative">
                                    <input type="password"
                                           class="form-control"
                                           id="confirm_password"
                                           name="confirm_password"
                                           placeholder="••••••••">
                                    <i class="fas fa-eye password-toggle-icon" onclick="togglePassword('confirm_password')"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary-custom">
                                <i class="fas fa-save me-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Account Information -->
                <div class="profile-card">
                    <div class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Información de la Cuenta
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tipo de Cuenta</label>
                            <div class="alert alert-light mb-0">
                                <i class="fas fa-user me-2"></i>
                                <strong><?= ucfirst(htmlspecialchars($user['tipo'] ?? 'cliente')) ?></strong>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Miembro Desde</label>
                            <div class="alert alert-light mb-0">
                                <i class="fas fa-calendar me-2"></i>
                                <strong><?= date('d/m/Y', strtotime($user['created_at'])) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling;

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Form validation
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            // Si está cambiando contraseña, validar
            if (newPassword || confirmPassword) {
                if (!currentPassword) {
                    e.preventDefault();
                    alert('Debes ingresar tu contraseña actual para cambiarla');
                    return false;
                }

                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Las contraseñas nuevas no coinciden');
                    return false;
                }

                if (newPassword.length < 8) {
                    e.preventDefault();
                    alert('La nueva contraseña debe tener al menos 8 caracteres');
                    return false;
                }
            }
        });
    </script>
</body>
</html>
