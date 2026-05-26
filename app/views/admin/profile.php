<?php
use App\Core\Config;
use App\Core\Helpers;
require_once __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid p-4">
    <?php
        $actionTitle = 'Mi Perfil';
        $actionSubtitle = 'Administra tu información personal';
        $actionButtons = [];
        include __DIR__ . '/../partials/admin_action_bar.php';
    ?>

    <?php if (Helpers::getFlashMessage('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars(Helpers::getFlashMessage('success'), ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <?php if (Helpers::getFlashMessage('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars(Helpers::getFlashMessage('error'), ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Información Personal</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= Config::getBaseUrl() ?>?route=admin/profile">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nombre" class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre"
                                       value="<?= htmlspecialchars($user['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?= htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="telefono" name="telefono"
                                   value="<?= htmlspecialchars($user['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="+52 55 1234-5678">
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3">Cambiar Contraseña</h6>
                        <p class="text-muted small">Déjalo en blanco si no deseas cambiar la contraseña</p>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Nueva Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Mínimo 6 caracteres">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password_confirm" class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm">
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <a href="<?= Config::getBaseUrl() ?>?route=admin" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información de Cuenta</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Tipo:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-primary"><?= ucfirst($user['tipo'] ?? 'N/A') ?></span>
                        </dd>

                        <dt class="col-sm-5">Estado:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-success"><?= $user['activo'] ? 'Activo' : 'Inactivo' ?></span>
                        </dd>

                        <dt class="col-sm-5">Registrado:</dt>
                        <dd class="col-sm-7 small"><?= date('d/m/Y', strtotime($user['created_at'])) ?></dd>

                        <dt class="col-sm-5">Última act.:</dt>
                        <dd class="col-sm-7 small"><?= date('d/m/Y H:i', strtotime($user['updated_at'])) ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Seguridad</h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        <i class="fas fa-lightbulb me-1"></i>
                        Consejos para una contraseña segura:
                    </p>
                    <ul class="small text-muted mb-0">
                        <li>Mínimo 8 caracteres</li>
                        <li>Combina mayúsculas y minúsculas</li>
                        <li>Incluye números y símbolos</li>
                        <li>No uses datos personales</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validar confirmación de contraseña
document.querySelector('form').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('password_confirm').value;

    if (password && password !== confirm) {
        e.preventDefault();
        alert('Las contraseñas no coinciden');
        return false;
    }

    if (password && password.length < 6) {
        e.preventDefault();
        alert('La contraseña debe tener al menos 6 caracteres');
        return false;
    }
});
</script>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
