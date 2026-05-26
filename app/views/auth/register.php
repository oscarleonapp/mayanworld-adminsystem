<?php
use App\Core\Config;
include __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow">
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                        <h3 class="card-title" id="register-title">Crear cuenta</h3>
                        <p class="text-muted">Únete a nuestra comunidad de viajeros</p>
                    </div>

                    <form method="POST" action="<?= Config::getBaseUrl() ?>?route=register" aria-labelledby="register-title" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre completo</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="nombre" 
                                    name="nombre" 
                                    required 
                                    autocomplete="name"
                                    placeholder="Tu nombre completo"
                                    value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                                >
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="email" 
                                    name="email" 
                                    required 
                                    autocomplete="email"
                                    placeholder="tu@email.com"
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                >
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono (opcional)</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-phone"></i>
                                </span>
                                <input 
                                    type="tel" 
                                    class="form-control" 
                                    id="telefono" 
                                    name="telefono" 
                                    autocomplete="tel"
                                    placeholder="+52 55 1234-5678"
                                    value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"
                                >
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="password" 
                                    name="password" 
                                    required 
                                    autocomplete="new-password"
                                    placeholder="Mínimo 6 caracteres"
                                    minlength="6"
                                >
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')" aria-pressed="false" aria-label="Mostrar u ocultar contraseña">
                                    <i class="fas fa-eye" id="password-toggle" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="form-text">
                                La contraseña debe tener al menos 6 caracteres
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    required 
                                    autocomplete="new-password"
                                    placeholder="Confirma tu contraseña"
                                    minlength="6"
                                >
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')" aria-pressed="false" aria-label="Mostrar u ocultar contraseña">
                                    <i class="fas fa-eye" id="confirm_password-toggle" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                Acepto los 
                                <a href="<?= Config::getBaseUrl() ?>?route=terms" target="_blank">términos y condiciones</a>
                                y la 
                                <a href="<?= Config::getBaseUrl() ?>?route=privacy" target="_blank">política de privacidad</a>
                            </label>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-user-plus me-2" aria-hidden="true"></i>Crear cuenta
                            </button>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div class="text-center">
                        <p class="mb-0">¿Ya tienes una cuenta?</p>
                        <a href="<?= Config::getBaseUrl() ?>?route=login" class="btn btn-outline-primary mt-2">
                            <i class="fas fa-sign-in-alt me-2" aria-hidden="true"></i>Iniciar sesión
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const toggle = document.getElementById(fieldId + '-toggle');
    const btn = toggle.closest('button');
    
    if (field.type === 'password') {
        field.type = 'text';
        toggle.classList.replace('fa-eye', 'fa-eye-slash');
        btn?.setAttribute('aria-pressed', 'true');
    } else {
        field.type = 'password';
        toggle.classList.replace('fa-eye-slash', 'fa-eye');
        btn?.setAttribute('aria-pressed', 'false');
    }
}

// Validación de contraseñas
document.addEventListener('DOMContentLoaded', function() {
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const form = document.querySelector('form[action$="?route=register"]');
    const status = document.createElement('div');
    status.id = 'register-status';
    status.className = 'mt-2 small';
    status.setAttribute('role','status');
    status.setAttribute('aria-live','polite');
    form?.parentNode?.appendChild(status);
    
    function validatePasswords() {
        if (password.value && confirmPassword.value) {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Las contraseñas no coinciden');
                confirmPassword.classList.add('is-invalid');
                status.textContent = 'Las contraseñas no coinciden';
            } else {
                confirmPassword.setCustomValidity('');
                confirmPassword.classList.remove('is-invalid');
                confirmPassword.classList.add('is-valid');
                status.textContent = '';
            }
        }
    }
    
    password.addEventListener('input', validatePasswords);
    confirmPassword.addEventListener('input', validatePasswords);
    
    // Validar antes de enviar
    form.addEventListener('submit', function(e) {
        if (password.value !== confirmPassword.value) {
            e.preventDefault();
            status.textContent = 'Las contraseñas no coinciden';
            confirmPassword.focus();
        }
    });
    
    // Auto-focus nombre
    document.getElementById('nombre').focus();
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
