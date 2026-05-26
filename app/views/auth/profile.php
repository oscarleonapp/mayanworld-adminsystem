<?php
use App\Core\Config;
include __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Mi perfil</h1>

    <form method="POST" action="<?= Config::getBaseUrl() ?>?route=profile" class="row g-3" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

        <div class="col-md-6">
            <label class="form-label" for="profile_nombre">Nombre</label>
            <input type="text" id="profile_nombre" name="nombre" class="form-control" value="<?= htmlspecialchars($user['nombre'] ?? '') ?>" required autocomplete="name">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="profile_email">Email</label>
            <input type="email" id="profile_email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="profile_tel">Teléfono</label>
            <input type="text" id="profile_tel" name="telefono" class="form-control" value="<?= htmlspecialchars($user['telefono'] ?? '') ?>" placeholder="+52 55 1234 5678" pattern="[\+0-9\s\-]{7,}">
        </div>

        <div class="col-12">
            <hr>
            <h5 id="change-pass-title">Cambiar contraseña (opcional)</h5>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="current_password">Contraseña actual</label>
            <div class="input-group">
                <input type="password" id="current_password" name="current_password" class="form-control" autocomplete="current-password">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePass('current_password')" aria-pressed="false" aria-label="Mostrar u ocultar contraseña actual"><i class="fas fa-eye" id="current_password-toggle" aria-hidden="true"></i></button>
            </div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="new_password">Nueva contraseña</label>
            <div class="input-group">
                <input type="password" id="new_password" name="new_password" class="form-control" autocomplete="new-password" minlength="6" placeholder="Mínimo 6 caracteres">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePass('new_password')" aria-pressed="false" aria-label="Mostrar u ocultar nueva contraseña"><i class="fas fa-eye" id="new_password-toggle" aria-hidden="true"></i></button>
            </div>
            <div class="form-text">La contraseña debe tener al menos 6 caracteres.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="confirm_new_password">Confirmar nueva contraseña</label>
            <div class="input-group">
                <input type="password" id="confirm_new_password" name="confirm_password" class="form-control" autocomplete="new-password" minlength="6" placeholder="Confirma tu contraseña">
                <button class="btn btn-outline-secondary" type="button" onclick="togglePass('confirm_new_password')" aria-pressed="false" aria-label="Mostrar u ocultar confirmación"><i class="fas fa-eye" id="confirm_new_password-toggle" aria-hidden="true"></i></button>
            </div>
        </div>
        <div class="col-12">
            <button class="btn btn-primary"><i class="fas fa-save me-2" aria-hidden="true"></i>Guardar cambios</button>
        </div>
    </form>
</div>

<script>
function togglePass(id){
  const el = document.getElementById(id);
  const icon = document.getElementById(id + '-toggle');
  const btn = icon?.closest('button');
  if (!el) return;
  if (el.type === 'password') { el.type = 'text'; icon?.classList.replace('fa-eye','fa-eye-slash'); btn?.setAttribute('aria-pressed','true'); }
  else { el.type = 'password'; icon?.classList.replace('fa-eye-slash','fa-eye'); btn?.setAttribute('aria-pressed','false'); }
}
document.addEventListener('DOMContentLoaded', function(){
  const np = document.getElementById('new_password');
  const cp = document.getElementById('confirm_new_password');
  function sync(){ if (cp.value && np.value !== cp.value) { cp.classList.add('is-invalid'); } else { cp.classList.remove('is-invalid'); if (cp.value) cp.classList.add('is-valid'); } }
  np?.addEventListener('input', sync); cp?.addEventListener('input', sync);
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
