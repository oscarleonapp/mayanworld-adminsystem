<?php
use App\Core\Helpers;
// Vista de cambio forzado de contraseña
$title = $title ?? 'Cambiar Contraseña';
$forced = $forced ?? false;
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-5">
                    <?php if ($forced): ?>
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Cambio de contraseña requerido</strong>
                            <p class="mb-0 mt-2">Por razones de seguridad, debes cambiar tu contraseña antes de continuar.</p>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mb-4">
                        <i class="fas fa-lock fa-3x text-primary mb-3"></i>
                        <h2 class="h4 mb-2">Cambiar Contraseña</h2>
                        <p class="text-muted small">Asegúrate de usar una contraseña segura</p>
                    </div>

                    <form action="<?= Helpers::url('change-password') ?>" method="POST" id="changePasswordForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                        <!-- Contraseña actual -->
                        <div class="mb-3">
                            <label for="current_password" class="form-label">
                                Contraseña Actual <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-key"></i>
                                </span>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye" id="current_password_icon"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Nueva contraseña -->
                        <div class="mb-3">
                            <label for="new_password" class="form-label">
                                Nueva Contraseña <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye" id="new_password_icon"></i>
                                </button>
                            </div>
                            <small class="form-text text-muted">
                                Mínimo 8 caracteres. Recomendado: combinar mayúsculas, minúsculas, números y símbolos.
                            </small>

                            <!-- Password strength indicator -->
                            <div class="mt-2">
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" id="passwordStrengthBar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="passwordStrengthText" class="form-text"></small>
                            </div>
                        </div>

                        <!-- Confirmar nueva contraseña -->
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">
                                Confirmar Nueva Contraseña <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="confirm_password_icon"></i>
                                </button>
                            </div>
                            <div id="passwordMatchMessage" class="form-text"></div>
                        </div>

                        <!-- Botón submit -->
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-check me-2"></i>
                            Cambiar Contraseña
                        </button>

                        <?php if (!$forced): ?>
                            <div class="text-center mt-3">
                                <a href="<?= Helpers::url('profile') ?>" class="text-muted small">
                                    <i class="fas fa-arrow-left me-1"></i>
                                    Volver al perfil
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>

                    <!-- Requisitos de contraseña -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <h6 class="small mb-2"><i class="fas fa-info-circle me-1"></i> Requisitos de Contraseña:</h6>
                        <ul class="small mb-0 ps-3">
                            <li>Mínimo 8 caracteres</li>
                            <li>Se recomienda usar mayúsculas y minúsculas</li>
                            <li>Se recomienda incluir números</li>
                            <li>Se recomienda incluir símbolos especiales (@, #, $, etc.)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '_icon');

    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength checker
const newPasswordField = document.getElementById('new_password');
const strengthBar = document.getElementById('passwordStrengthBar');
const strengthText = document.getElementById('passwordStrengthText');

newPasswordField.addEventListener('input', function() {
    const password = this.value;
    const strength = calculatePasswordStrength(password);

    strengthBar.style.width = strength.score + '%';
    strengthBar.className = 'progress-bar ' + strength.colorClass;
    strengthText.textContent = strength.text;
    strengthText.className = 'form-text ' + strength.textClass;
});

function calculatePasswordStrength(password) {
    let score = 0;
    let messages = [];

    if (password.length === 0) {
        return { score: 0, text: '', colorClass: '', textClass: '' };
    }

    // Length
    if (password.length >= 8) {
        score += 25;
    } else {
        messages.push('muy corta');
    }

    // Uppercase
    if (/[A-Z]/.test(password)) {
        score += 20;
    }

    // Lowercase
    if (/[a-z]/.test(password)) {
        score += 20;
    }

    // Numbers
    if (/[0-9]/.test(password)) {
        score += 20;
    }

    // Special chars
    if (/[^A-Za-z0-9]/.test(password)) {
        score += 15;
    }

    let text, colorClass, textClass;

    if (score >= 80) {
        text = 'Muy fuerte';
        colorClass = 'bg-success';
        textClass = 'text-success';
    } else if (score >= 60) {
        text = 'Fuerte';
        colorClass = 'bg-info';
        textClass = 'text-info';
    } else if (score >= 40) {
        text = 'Media';
        colorClass = 'bg-warning';
        textClass = 'text-warning';
    } else {
        text = 'Débil';
        colorClass = 'bg-danger';
        textClass = 'text-danger';
    }

    if (messages.length > 0) {
        text += ' (' + messages.join(', ') + ')';
    }

    return { score, text, colorClass, textClass };
}

// Password match checker
const confirmPasswordField = document.getElementById('confirm_password');
const matchMessage = document.getElementById('passwordMatchMessage');

confirmPasswordField.addEventListener('input', function() {
    const newPassword = newPasswordField.value;
    const confirmPassword = this.value;

    if (confirmPassword.length === 0) {
        matchMessage.textContent = '';
        matchMessage.className = 'form-text';
        return;
    }

    if (newPassword === confirmPassword) {
        matchMessage.textContent = 'Las contraseñas coinciden ✓';
        matchMessage.className = 'form-text text-success';
    } else {
        matchMessage.textContent = 'Las contraseñas no coinciden ✗';
        matchMessage.className = 'form-text text-danger';
    }
});

// Form validation
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const newPassword = newPasswordField.value;
    const confirmPassword = confirmPasswordField.value;

    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }

    if (newPassword.length < 8) {
        e.preventDefault();
        alert('La contraseña debe tener al menos 8 caracteres');
        return false;
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
