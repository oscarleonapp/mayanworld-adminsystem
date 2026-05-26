<?php
/**
 * Configuración de WhatsApp - Panel de Admin
 */
use App\Core\Config;
$pageTitle = $title ?? 'Configuración de WhatsApp';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="container-fluid px-4">
    <?php
        $actionTitle = 'Configuración de WhatsApp';
        $actionSubtitle = 'Configura el botón flotante de WhatsApp para el sitio web';
        $actionButtons = [];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <div class="row">
        <!-- Formulario de Configuración -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2"></i>
                        Ajustes del Botón
                    </h5>
                </div>
                <div class="card-body">
                    <form id="whatsappConfigForm">
                        <!-- Estado -->
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?= ($config['is_active'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold" for="is_active">
                                    Botón Activo
                                </label>
                                <small class="d-block text-muted">Mostrar el botón de WhatsApp en el sitio</small>
                            </div>
                        </div>

                        <hr>

                        <!-- Número de WhatsApp -->
                        <div class="mb-4">
                            <label for="phone_number" class="form-label fw-bold">
                                <i class="fas fa-phone me-1"></i>
                                Número de WhatsApp *
                            </label>
                            <input type="text"
                                   class="form-control form-control-lg"
                                   id="phone_number"
                                   name="phone_number"
                                   value="<?= htmlspecialchars($config['phone_number'] ?? '') ?>"
                                   placeholder="502XXXXXXXX"
                                   required>
                            <div class="form-text">
                                <strong>Formato:</strong> Código de país + número (sin espacios ni guiones)
                                <br>Ejemplo para Guatemala: <code>50212345678</code>
                            </div>
                        </div>

                        <!-- Mensaje de Bienvenida -->
                        <div class="mb-4">
                            <label for="welcome_message" class="form-label fw-bold">
                                <i class="fas fa-comment-alt me-1"></i>
                                Mensaje Predeterminado
                            </label>
                            <textarea class="form-control"
                                      id="welcome_message"
                                      name="welcome_message"
                                      rows="3"
                                      maxlength="500"><?= htmlspecialchars($config['welcome_message'] ?? '') ?></textarea>
                            <div class="form-text">
                                Este mensaje aparecerá automáticamente cuando el usuario haga click en el botón
                            </div>
                        </div>

                        <!-- Texto del Botón -->
                        <div class="mb-4">
                            <label for="button_text" class="form-label fw-bold">
                                <i class="fas fa-font me-1"></i>
                                Texto del Botón
                            </label>
                            <input type="text"
                                   class="form-control"
                                   id="button_text"
                                   name="button_text"
                                   value="<?= htmlspecialchars($config['button_text'] ?? 'Chatea con nosotros') ?>"
                                   maxlength="50">
                            <div class="form-text">
                                Texto que aparece al pasar el mouse sobre el botón
                            </div>
                        </div>

                        <!-- Posición del Botón -->
                        <div class="mb-4">
                            <label for="button_position" class="form-label fw-bold">
                                <i class="fas fa-map-pin me-1"></i>
                                Posición en la Pantalla
                            </label>
                            <select class="form-select" id="button_position" name="button_position">
                                <option value="bottom-right" <?= ($config['button_position'] ?? 'bottom-right') === 'bottom-right' ? 'selected' : '' ?>>
                                    Abajo a la Derecha (Recomendado)
                                </option>
                                <option value="bottom-left" <?= ($config['button_position'] ?? '') === 'bottom-left' ? 'selected' : '' ?>>
                                    Abajo a la Izquierda
                                </option>
                                <option value="top-right" <?= ($config['button_position'] ?? '') === 'top-right' ? 'selected' : '' ?>>
                                    Arriba a la Derecha
                                </option>
                                <option value="top-left" <?= ($config['button_position'] ?? '') === 'top-left' ? 'selected' : '' ?>>
                                    Arriba a la Izquierda
                                </option>
                            </select>
                        </div>

                        <hr>

                        <!-- Horario de Negocio -->
                        <div class="mb-4">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="business_hours_only" name="business_hours_only" <?= ($config['business_hours_only'] ?? 0) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold" for="business_hours_only">
                                    Solo en Horario Laboral
                                </label>
                                <small class="d-block text-muted">Ocultar el botón fuera del horario de atención</small>
                            </div>

                            <div id="business_hours_config" style="display: <?= ($config['business_hours_only'] ?? 0) ? 'block' : 'none' ?>;">
                                <!-- Horarios -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="business_hours_start" class="form-label">
                                            <i class="fas fa-clock me-1"></i>
                                            Hora de Inicio
                                        </label>
                                        <input type="time"
                                               class="form-control"
                                               id="business_hours_start"
                                               name="business_hours_start"
                                               value="<?= substr($config['business_hours_start'] ?? '08:00:00', 0, 5) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="business_hours_end" class="form-label">
                                            <i class="fas fa-clock me-1"></i>
                                            Hora de Fin
                                        </label>
                                        <input type="time"
                                               class="form-control"
                                               id="business_hours_end"
                                               name="business_hours_end"
                                               value="<?= substr($config['business_hours_end'] ?? '18:00:00', 0, 5) ?>">
                                    </div>
                                </div>

                                <!-- Días de la Semana -->
                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-calendar me-1"></i>
                                        Días Activos
                                    </label>
                                    <?php
                                    $businessDays = explode(',', $config['business_days'] ?? 'mon,tue,wed,thu,fri');
                                    $days = [
                                        'mon' => 'Lunes',
                                        'tue' => 'Martes',
                                        'wed' => 'Miércoles',
                                        'thu' => 'Jueves',
                                        'fri' => 'Viernes',
                                        'sat' => 'Sábado',
                                        'sun' => 'Domingo'
                                    ];
                                    ?>
                                    <div class="row">
                                        <?php foreach ($days as $value => $label): ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       type="checkbox"
                                                       name="business_days[]"
                                                       id="day_<?= $value ?>"
                                                       value="<?= $value ?>"
                                                       <?= in_array($value, $businessDays) ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="day_<?= $value ?>">
                                                    <?= $label ?>
                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Botones -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success px-4">
                                <i class="fas fa-save me-2"></i>
                                Guardar Cambios
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="location.reload()">
                                <i class="fas fa-redo me-2"></i>
                                Restablecer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Vista Previa -->
        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-eye me-2"></i>
                        Vista Previa
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>El botón aparece en la esquina de tu sitio web</small>
                    </div>

                    <!-- Simulación del botón -->
                    <div class="preview-container" style="position: relative; height: 300px; background: #f8f9fa; border-radius: 8px; overflow: hidden;">
                        <div class="position-absolute" id="preview-button" style="bottom: 20px; right: 20px;">
                            <a href="#" class="whatsapp-preview-btn" onclick="return false;">
                                <i class="fab fa-whatsapp"></i>
                                <span id="preview-text">Chatea con nosotros</span>
                            </a>
                        </div>
                    </div>

                    <hr>

                    <div class="mt-3">
                        <h6 class="fw-bold">Información:</h6>
                        <ul class="small text-muted mb-0">
                            <li>El botón se expande al pasar el mouse</li>
                            <li>Incluye animación pulsante para llamar la atención</li>
                            <li>Compatible con dispositivos móviles</li>
                            <li>Abre WhatsApp automáticamente con el mensaje predeterminado</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.whatsapp-preview-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: white;
    text-decoration: none;
    border-radius: 50px;
    box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
    transition: all 0.3s;
    font-size: 14px;
    font-weight: 600;
}

.whatsapp-preview-btn i {
    font-size: 24px;
}

.whatsapp-preview-btn:hover {
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(37, 211, 102, 0.5);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('whatsappConfigForm');
    const businessHoursToggle = document.getElementById('business_hours_only');
    const businessHoursConfig = document.getElementById('business_hours_config');
    const buttonPositionSelect = document.getElementById('button_position');
    const previewButton = document.getElementById('preview-button');
    const previewText = document.getElementById('preview-text');
    const buttonTextInput = document.getElementById('button_text');

    // Toggle business hours config
    businessHoursToggle.addEventListener('change', function() {
        businessHoursConfig.style.display = this.checked ? 'block' : 'none';
    });

    // Actualizar vista previa de posición
    buttonPositionSelect.addEventListener('change', function() {
        const position = this.value;
        previewButton.classList.remove('position-absolute');
        previewButton.style = '';

        setTimeout(() => {
            previewButton.classList.add('position-absolute');
            switch(position) {
                case 'bottom-right':
                    previewButton.style = 'bottom: 20px; right: 20px;';
                    break;
                case 'bottom-left':
                    previewButton.style = 'bottom: 20px; left: 20px;';
                    break;
                case 'top-right':
                    previewButton.style = 'top: 20px; right: 20px;';
                    break;
                case 'top-left':
                    previewButton.style = 'top: 20px; left: 20px;';
                    break;
            }
        }, 50);
    });

    // Actualizar vista previa de texto
    buttonTextInput.addEventListener('input', function() {
        previewText.textContent = this.value || 'Chatea con nosotros';
    });

    // Submit form
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';

        const formData = new FormData(form);

        fetch('<?= Config::getBaseUrl() ?>?route=admin/settings/whatsapp/save', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="fas fa-check-circle me-2"></i>
                    ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                form.insertBefore(alert, form.firstChild);

                // Auto-cerrar después de 3 segundos
                setTimeout(() => alert.remove(), 3000);
            } else {
                throw new Error(data.message || 'Error al guardar');
            }
        })
        .catch(error => {
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show';
            alert.innerHTML = `
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${error.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            form.insertBefore(alert, form.firstChild);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
