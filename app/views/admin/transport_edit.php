<?php
use App\Core\Config;
use App\Core\Database;
include __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <?php
      $actionTitle = 'Editar Transporte';
      $actionSubtitle = 'Modifica la información del vehículo';
      $actionButtons = [
        ['label' => 'Volver a Lista', 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary', 'href' => Config::getBaseUrl() . '?route=admin/transport'],
      ];
      include __DIR__ . '/../partials/admin_action_bar.php';
    ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Formulario de Edición -->
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-edit me-2"></i>Información del Transporte
                    </h6>
                </div>
                <div class="card-body">
                    <form id="editTransportForm">
                        <div class="row g-3">
                            <!-- Nombre -->
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre del vehículo *</label>
                                <input type="text"
                                       class="form-control"
                                       id="nombre"
                                       name="nombre"
                                       value="<?= htmlspecialchars($transport['nombre']) ?>"
                                       required>
                                <div class="form-text">Ej: Bus Turístico Premium #1</div>
                            </div>

                            <!-- Tipo -->
                            <div class="col-md-6">
                                <label for="tipo" class="form-label">Tipo de transporte *</label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <?php foreach ($transport_types as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= $transport['tipo'] === $key ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Capacidad -->
                            <div class="col-md-6">
                                <label for="capacidad" class="form-label">Capacidad (personas) *</label>
                                <input type="number"
                                       class="form-control"
                                       id="capacidad"
                                       name="capacidad"
                                       value="<?= $transport['capacidad'] ?>"
                                       min="1"
                                       max="100"
                                       required>
                                <div class="form-text">Número máximo de pasajeros</div>
                            </div>

                            <!-- Estado -->
                            <div class="col-md-6">
                                <label for="activo" class="form-label">Estado</label>
                                <select class="form-select" id="activo" name="activo">
                                    <option value="1" <?= $transport['activo'] ? 'selected' : '' ?>>Activo</option>
                                    <option value="0" <?= !$transport['activo'] ? 'selected' : '' ?>>Inactivo</option>
                                </select>
                                <div class="form-text">Solo transportes activos aparecen en rutas</div>
                            </div>

                            <!-- Comodidades -->
                            <div class="col-12">
                                <label class="form-label">Comodidades y servicios</label>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           name="comodidades[]"
                                                           value="aire_acondicionado"
                                                           id="ac"
                                                           <?= in_array('aire_acondicionado', $comodidades) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="ac">
                                                        <i class="fas fa-snowflake text-info me-1"></i>Aire acondicionado
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           name="comodidades[]"
                                                           value="wifi"
                                                           id="wifi"
                                                           <?= in_array('wifi', $comodidades) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="wifi">
                                                        <i class="fas fa-wifi text-primary me-1"></i>Wi-Fi
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           name="comodidades[]"
                                                           value="musica"
                                                           id="musica"
                                                           <?= in_array('musica', $comodidades) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="musica">
                                                        <i class="fas fa-music text-success me-1"></i>Sistema de música
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           name="comodidades[]"
                                                           value="asientos_comodos"
                                                           id="asientos"
                                                           <?= in_array('asientos_comodos', $comodidades) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="asientos">
                                                        <i class="fas fa-chair text-warning me-1"></i>Asientos cómodos
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           name="comodidades[]"
                                                           value="ventanas_panoramicas"
                                                           id="ventanas"
                                                           <?= in_array('ventanas_panoramicas', $comodidades) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="ventanas">
                                                        <i class="fas fa-eye text-info me-1"></i>Ventanas panorámicas
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           name="comodidades[]"
                                                           value="guia_turistico"
                                                           id="guia"
                                                           <?= in_array('guia_turistico', $comodidades) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="guia">
                                                        <i class="fas fa-user-tie text-primary me-1"></i>Guía turístico
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           name="comodidades[]"
                                                           value="agua_embotellada"
                                                           id="agua"
                                                           <?= in_array('agua_embotellada', $comodidades) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="agua">
                                                        <i class="fas fa-tint text-info me-1"></i>Agua embotellada
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           name="comodidades[]"
                                                           value="seguro_viaje"
                                                           id="seguro"
                                                           <?= in_array('seguro_viaje', $comodidades) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="seguro">
                                                        <i class="fas fa-shield-alt text-success me-1"></i>Seguro de viaje
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input"
                                                           type="checkbox"
                                                           name="comodidades[]"
                                                           value="sanitarios"
                                                           id="sanitarios"
                                                           <?= in_array('sanitarios', $comodidades) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="sanitarios">
                                                        <i class="fas fa-restroom text-secondary me-1"></i>Sanitarios
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <a href="<?= Config::getBaseUrl() ?>?route=admin/transport" class="btn btn-outline-secondary">
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
            <!-- Información adicional -->
            <div class="card shadow mb-4">
                <div class="card-header bg-white">
                    <h6 class="m-0 font-weight-bold text-secondary">
                        <i class="fas fa-info-circle me-2"></i>Información
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">ID del Transporte</label>
                        <div class="fw-bold">#<?= $transport['id'] ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Fecha de registro</label>
                        <div><?= date('d/m/Y H:i', strtotime($transport['created_at'])) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Última actualización</label>
                        <div><?= date('d/m/Y H:i', strtotime($transport['updated_at'])) ?></div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Estado actual</label>
                        <div>
                            <span class="badge <?= $transport['activo'] ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $transport['activo'] ? 'Activo' : 'Inactivo' ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rutas asignadas -->
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h6 class="m-0 font-weight-bold text-secondary">
                        <i class="fas fa-route me-2"></i>Rutas Asignadas
                    </h6>
                </div>
                <div class="card-body">
                    <?php
                    $db = Database::getInstance();
                    $routes = $db->fetchAll("SELECT * FROM rutas WHERE transporte_id = ? AND activo = 1", [$transport['id']]);
                    ?>
                    <?php if (!empty($routes)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($routes as $route): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($route['nombre']) ?></h6>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($route['origen']) ?> →
                                                <?= htmlspecialchars($route['destino']) ?>
                                            </small>
                                        </div>
                                        <a href="<?= Config::getBaseUrl() ?>?route=admin/routes/edit/<?= $route['id'] ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">
                            <i class="fas fa-info-circle me-1"></i>
                            Sin rutas asignadas
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editTransportForm');

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Deshabilitar botón
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';

        // Recopilar datos del formulario
        const formData = new FormData(form);

        // Procesar comodidades (checkboxes múltiples)
        const comodidades = [];
        document.querySelectorAll('input[name="comodidades[]"]:checked').forEach(cb => {
            comodidades.push(cb.value);
        });
        formData.delete('comodidades[]');
        formData.append('comodidades', JSON.stringify(comodidades));

        // Enviar petición AJAX
        fetch('<?= Config::getBaseUrl() ?>?route=admin/transport/update/<?= $transport['id'] ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Verificar si la respuesta es JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                // Si no es JSON, mostrar el HTML completo para debug
                return response.text().then(text => {
                    console.error('Respuesta no es JSON:', text);
                    throw new Error('El servidor no devolvió JSON. Revisa la consola para ver la respuesta completa.');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Mostrar mensaje de éxito
                alert('✓ ' + data.message);
                // Redirigir a la lista
                window.location.href = '<?= Config::getBaseUrl() ?>?route=admin/transport';
            } else {
                alert('✗ Error: ' + (data.message || 'No se pudo actualizar el transporte'));
                // Restaurar botón
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        })
        .catch(error => {
            console.error('Error completo:', error);
            alert('✗ Error al procesar la solicitud: ' + error.message);
            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        });
    });
});
</script>

<?php include __DIR__ . '/../layouts/admin_footer.php'; ?>
