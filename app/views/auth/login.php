<?php
use App\Core\Config;
use App\Core\Helpers;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Iniciar Sesión') ?> | Travel Agency</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #0a58ca;
            --accent-color: #0052cc;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --info-color: #06b6d4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            <?php if (!empty($login_background_image)): ?>
            <?php
            // Determinar si es URL externa o ruta local
            if (strpos($login_background_image, 'http') === 0) {
                // URL externa (ej: Unsplash)
                $bgImageUrl = $login_background_image;
            } else {
                // Archivo local en uploads/ - construir URL correctamente
                $bgImageUrl = Config::getBaseUrl() . ltrim($login_background_image, '/');
            }
            ?>
            background-image: url('<?= htmlspecialchars($bgImageUrl) ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            <?php else: ?>
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            <?php endif; ?>
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            position: relative;
            overflow-y: auto;
        }

        <?php if (!empty($login_background_image)): ?>
        /* Overlay oscuro para mejor legibilidad cuando hay imagen */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            z-index: 0;
        }
        <?php endif; ?>

        .login-container {
            width: 100%;
            max-width: 420px;
            position: relative;
            z-index: 1;
            margin: auto;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 24px 20px;
            text-align: center;
            position: relative;
        }

        .login-icon {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
        }

        .login-icon i {
            font-size: 24px;
            color: white;
        }

        .login-header h1 {
            color: white;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            margin: 0;
        }

        .login-body {
            padding: 24px 20px;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-label i {
            color: var(--accent-color);
            font-size: 14px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 16px;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            z-index: 10;
            font-size: 16px;
        }

        .form-control-custom {
            width: 100%;
            padding: 11px 14px 11px 42px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control-custom:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
        }

        .form-control-custom.is-invalid {
            border-color: var(--danger-color);
        }

        .form-control-custom.is-valid {
            border-color: var(--success-color);
        }

        .password-toggle {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 6px;
            transition: color 0.3s ease;
            z-index: 10;
        }

        .password-toggle:hover {
            color: var(--primary-color);
        }

        .form-check-custom {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 16px 0;
        }

        .form-check-custom input[type="checkbox"] {
            width: 18px;
            height: 18px;
            border: 2px solid #e5e7eb;
            border-radius: 4px;
            cursor: pointer;
            accent-color: var(--primary-color);
        }

        .form-check-custom label {
            font-size: 12px;
            color: #6b7280;
            cursor: pointer;
            margin: 0;
            line-height: 1.4;
        }

        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 3px 12px rgba(13, 110, 253, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 5px 16px rgba(13, 110, 253, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .divider {
            margin: 20px 0;
            text-align: center;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e5e7eb;
        }

        .divider span {
            background: white;
            padding: 0 12px;
            color: #9ca3af;
            font-size: 12px;
            position: relative;
        }

        .register-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 11px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            color: #374151;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .register-link:hover {
            border-color: var(--primary-color);
            background: rgba(13, 110, 253, 0.05);
            color: var(--primary-color);
        }

        .admin-link {
            text-align: center;
            margin-top: 16px;
        }

        .admin-link a {
            color: #6b7280;
            text-decoration: none;
            font-size: 12px;
            transition: color 0.3s ease;
        }

        .admin-link a:hover {
            color: var(--primary-color);
        }

        .demo-credentials {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-radius: 8px;
            padding: 12px;
            margin-top: 16px;
            border-left: 3px solid var(--primary-color);
        }

        .demo-credentials h6 {
            color: #1e40af;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }

        .demo-credentials code {
            background: rgba(255, 255, 255, 0.7);
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
            color: #1e40af;
        }

        .login-footer {
            background: #f9fafb;
            padding: 14px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }

        .login-footer i {
            color: var(--success-color);
        }

        .security-info {
            text-align: center;
            margin-top: 12px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .security-info i {
            color: rgba(255, 255, 255, 0.8);
        }

        @media (max-width: 576px) {
            body {
                padding: 10px;
            }

            .login-body {
                padding: 20px 16px;
            }

            .login-header {
                padding: 20px 16px;
            }

            .login-header h1 {
                font-size: 20px;
            }

            .login-header p {
                font-size: 12px;
            }

            .form-check-custom label {
                font-size: 11px;
            }
        }

        /* Flash Messages */
        .alert {
            border-radius: 8px;
            border: none;
            padding: 10px 12px;
            margin-bottom: 16px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert i {
            font-size: 16px;
            flex-shrink: 0;
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-left: 3px solid var(--success-color);
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-left: 3px solid var(--danger-color);
        }

        .alert-info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border-left: 3px solid var(--info-color);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <div class="login-icon">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h1>Iniciar Sesión</h1>
                <p>Accede a tu cuenta de cliente</p>
            </div>

            <!-- Body -->
            <div class="login-body">
                <!-- Flash Messages -->
                <?php
                $flashMessages = Helpers::getFlashMessages();
                if (!empty($flashMessages)):
                    foreach ($flashMessages as $flashMessage):
                ?>
                    <div class="alert alert-<?= htmlspecialchars($flashMessage['type']) ?>" role="alert">
                        <i class="fas fa-<?= $flashMessage['type'] === 'success' ? 'check-circle' : ($flashMessage['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                        <?= htmlspecialchars($flashMessage['message']) ?>
                    </div>
                <?php
                    endforeach;
                endif;
                ?>

                <!-- Login Form -->
                <form method="POST" action="<?= Config::getBaseUrl() ?>?route=login" id="loginForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                    <!-- Email Field -->
                    <div class="mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i>
                            Correo Electrónico
                        </label>
                        <div class="input-group-custom">
                            <i class="fas fa-at input-icon"></i>
                            <input
                                type="email"
                                class="form-control-custom"
                                id="email"
                                name="email"
                                required
                                autocomplete="email"
                                placeholder="tu@email.com"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            >
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>
                            Contraseña
                        </label>
                        <div class="input-group-custom">
                            <i class="fas fa-key input-icon"></i>
                            <input
                                type="password"
                                class="form-control-custom"
                                id="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('password')" aria-label="Mostrar/ocultar contraseña">
                                <i class="fas fa-eye" id="password-toggle"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="form-check-custom">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">
                            Recordarme en este dispositivo
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        Iniciar Sesión
                    </button>
                </form>

                <!-- Divider -->
                <div class="divider">
                    <span>¿Nuevo usuario?</span>
                </div>

                <!-- Register Link -->
                <a href="<?= Config::getBaseUrl() ?>?route=register" class="register-link">
                    <i class="fas fa-user-plus"></i>
                    Crear una Cuenta Nueva
                </a>

                <!-- Admin Link -->
                <div class="admin-link">
                    <a href="<?= Config::getBaseUrl() ?>?route=admin/login">
                        <i class="fas fa-shield-alt"></i>
                        Acceso Administrativo
                    </a>
                </div>

                <!-- Demo Credentials (Development Only) -->
                <!-- Demo Credentials Removed for Security -->
            </div>

            <!-- Footer -->
            <div class="login-footer">
                <i class="fas fa-lock"></i>
                Conexión segura y protegida
            </div>
        </div>

        <!-- Security Info -->
        <div class="security-info">
            <i class="fas fa-shield-check"></i>
            Tus datos están protegidos
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = document.getElementById(fieldId + '-toggle');

            if (field.type === 'password') {
                field.type = 'text';
                toggle.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                toggle.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Form validation and auto-focus
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const email = document.getElementById('email');
            const password = document.getElementById('password');

            // Auto-focus email field
            email.focus();

            function validateEmail() {
                const value = email.value.trim();
                const isValid = value !== '' && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                email.classList.toggle('is-invalid', !isValid && value !== '');
                email.classList.toggle('is-valid', isValid);
                return isValid;
            }

            function validatePassword() {
                const value = password.value;
                const isValid = value.length >= 6;
                password.classList.toggle('is-invalid', !isValid && value !== '');
                password.classList.toggle('is-valid', isValid);
                return isValid;
            }

            email.addEventListener('blur', validateEmail);
            email.addEventListener('input', validateEmail);
            password.addEventListener('blur', validatePassword);
            password.addEventListener('input', validatePassword);

            form.addEventListener('submit', function(e) {
                const emailValid = validateEmail();
                const passwordValid = validatePassword();

                if (!emailValid || !passwordValid) {
                    e.preventDefault();

                    // Focus first invalid field
                    if (!emailValid) {
                        email.focus();
                    } else if (!passwordValid) {
                        password.focus();
                    }
                }
            });

            // Enter key navigation
            email.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    password.focus();
                }
            });
        });
    </script>
</body>
</html>
