<?php 
use App\Core\Config;
use App\Core\Helpers;
include __DIR__ . '/../../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <?php
      $actionTitle = 'Editar Ruta';
      $actionSubtitle = 'Modifica la información de la ruta de transporte';
      $actionButtons = [
        ['label' => 'Volver a Rutas', 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary', 'href' => Config::getBaseUrl() . '?route=admin/routes'],
      ];
      include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Flash Messages -->
    <?php
    $flashMessages = Helpers::getFlashMessages();
    foreach ($flashMessages as $flashMessage):
    ?>
        <div class="alert alert-<?= $flashMessage['type'] ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flashMessage['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endforeach; ?>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Route Form -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-route me-2"></i>
                Información de la Ruta
                <?php if (!empty($route['id'])): ?>
                    <small class="text-muted">#<?= $route['id'] ?></small>
                <?php endif; ?>
            </h6>
        </div>
        <div class="card-body">
            <form method="POST" id="editRouteForm" enctype="multipart/form-data">
                <div class="row g-3">
                    <!-- Información Básica -->
                    <div class="col-12">
                        <h5 class="mb-3">Información Básica</h5>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Nombre de la Ruta *</label>
                        <input type="text" class="form-control" name="nombre"
                               value="<?= htmlspecialchars($route['nombre'] ?? '') ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Imagen de la Ruta</label>
                        <input type="file" class="form-control" name="imagen" accept="image/jpeg,image/jpg,image/png,image/webp">
                        <small class="form-text text-muted">Formatos: JPG, PNG, WebP. Máximo 5MB. Recomendado: 1200x600px</small>
                        <?php if (!empty($route['imagen'])): ?>
                            <div class="mt-2">
                                <img src="<?= Helpers::asset($route['imagen']) ?>" alt="Imagen actual"
                                     class="img-thumbnail" style="max-height: 150px;">
                                <small class="d-block text-muted mt-1">Imagen actual</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Origen *</label>
                        <input type="text" class="form-control" name="origen"
                               value="<?= htmlspecialchars($route['origen'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Destino *</label>
                        <input type="text" class="form-control" name="destino"
                               value="<?= htmlspecialchars($route['destino'] ?? '') ?>" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="3"><?= htmlspecialchars($route['descripcion'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Distancia (km)</label>
                        <input type="number" step="0.01" class="form-control" name="distancia_km"
                               value="<?= htmlspecialchars($route['distancia_km'] ?? '') ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Duración Estimada *</label>
                        <input type="text" class="form-control" name="duracion_estimada"
                               value="<?= htmlspecialchars($route['duracion_estimada'] ?? '') ?>"
                               placeholder="ej: 3 horas" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Precio (Q) *</label>
                        <input type="number" step="0.01" class="form-control" name="precio"
                               value="<?= htmlspecialchars($route['precio'] ?? '') ?>" required>
                    </div>

                    <!-- Detalles Operativos -->
                    <div class="col-12 mt-4">
                        <h5 class="mb-3">Detalles Operativos</h5>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Transporte</label>
                        <select class="form-select" name="transporte_id">
                            <option value="">Sin asignar</option>
                            <?php foreach ($transportes as $transporte): ?>
                                <option value="<?= $transporte['id'] ?>"
                                        <?= (isset($route['transporte_id']) && $route['transporte_id'] == $transporte['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($transporte['nombre']) ?> - <?= htmlspecialchars($transporte['tipo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Conductor</label>
                        <select class="form-select" name="conductor_id">
                            <option value="">Sin asignar</option>
                            <?php foreach ($conductores as $conductor): ?>
                                <option value="<?= $conductor['id'] ?>"
                                        <?= (isset($route['conductor_id']) && $route['conductor_id'] == $conductor['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($conductor['nombre_completo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Días de Operación</label>
                        <div class="row">
                            <?php
                            $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
                            $diasSeleccionados = $route['dias_operacion'] ?? [];
                            foreach ($dias as $dia):
                            ?>
                            <div class="col-md-3 col-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="dias_operacion[]"
                                           value="<?= $dia ?>" id="dia_<?= $dia ?>"
                                           <?= in_array($dia, $diasSeleccionados) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="dia_<?= $dia ?>">
                                        <?= ucfirst($dia) ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Horarios de Salida</label>
                        <div id="horariosContainer">
                            <?php
                            $horarios = $route['horarios'] ?? [];
                            if (empty($horarios)):
                                $horarios = [['hora' => '', 'lugar' => '']];
                            endif;
                            foreach ($horarios as $index => $horario):
                            ?>
                            <div class="horario-item mb-2">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="time" class="form-control horario-hora"
                                               value="<?= htmlspecialchars($horario['hora'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control horario-lugar"
                                               value="<?= htmlspecialchars($horario['lugar'] ?? '') ?>"
                                               placeholder="Lugar (ej: Hotel pickup Flores)">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeHorario(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addHorario()">
                            <i class="fas fa-plus me-1"></i>Agregar Horario
                        </button>
                        <input type="hidden" name="horarios" id="horariosJSON">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Paradas Intermedias</label>
                        <div id="paradasContainer">
                            <?php
                            $paradas = $route['paradas_intermedias'] ?? [];
                            if (empty($paradas)):
                                $paradas = [''];
                            endif;
                            foreach ($paradas as $index => $parada):
                            ?>
                            <div class="parada-item mb-2">
                                <div class="row g-2">
                                    <div class="col-md-10">
                                        <input type="text" class="form-control parada-nombre"
                                               value="<?= htmlspecialchars($parada) ?>"
                                               placeholder="Nombre de la parada">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeParada(this)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addParada()">
                            <i class="fas fa-plus me-1"></i>Agregar Parada
                        </button>
                        <input type="hidden" name="paradas_intermedias" id="paradasJSON">
                    </div>

                    <!-- Información Adicional -->
                    <div class="col-12 mt-4">
                        <h5 class="mb-3">Información Adicional</h5>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Requisitos</label>
                        <textarea class="form-control" name="requisitos" rows="2"><?= htmlspecialchars($route['requisitos'] ?? '') ?></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notas Importantes</label>
                        <textarea class="form-control" name="notas_importantes" rows="2"><?= htmlspecialchars($route['notas_importantes'] ?? '') ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Estado</label>
                        <select class="form-select" name="estado">
                            <option value="activo" <?= (isset($route['estado']) && $route['estado'] == 'activo') ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= (isset($route['estado']) && $route['estado'] == 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                            <option value="mantenimiento" <?= (isset($route['estado']) && $route['estado'] == 'mantenimiento') ? 'selected' : '' ?>>Mantenimiento</option>
                        </select>
                    </div>
                </div>

                <!-- Botones de acción -->
                <hr class="my-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <?php if (!empty($route['id'])): ?>
                            <button type="button" class="btn btn-outline-danger" onclick="deleteRoute(<?= $route['id'] ?>)">
                                <i class="fas fa-trash me-2"></i>Eliminar Ruta
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/routes" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Funciones para manejar horarios
function addHorario() {
    const container = document.getElementById('horariosContainer');
    const newItem = document.createElement('div');
    newItem.className = 'horario-item mb-2';
    newItem.innerHTML = `
        <div class="row g-2">
            <div class="col-md-4">
                <input type="time" class="form-control horario-hora" placeholder="Hora">
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control horario-lugar" placeholder="Lugar (ej: Hotel pickup Flores)">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeHorario(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newItem);
}

function removeHorario(button) {
    const container = document.getElementById('horariosContainer');
    if (container.children.length > 1) {
        button.closest('.horario-item').remove();
    } else {
        alert('Debe haber al menos un horario');
    }
}

// Funciones para manejar paradas
function addParada() {
    const container = document.getElementById('paradasContainer');
    const newItem = document.createElement('div');
    newItem.className = 'parada-item mb-2';
    newItem.innerHTML = `
        <div class="row g-2">
            <div class="col-md-10">
                <input type="text" class="form-control parada-nombre" placeholder="Nombre de la parada">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeParada(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newItem);
}

function removeParada(button) {
    const container = document.getElementById('paradasContainer');
    if (container.children.length > 1) {
        button.closest('.parada-item').remove();
    }
}

// Convertir horarios y paradas a JSON antes de enviar
document.getElementById('editRouteForm').addEventListener('submit', function(e) {
    try {
        // Procesar horarios
        const horarios = [];
        const horariosItems = document.querySelectorAll('.horario-item');
        horariosItems.forEach(item => {
            const horaInput = item.querySelector('.horario-hora');
            const lugarInput = item.querySelector('.horario-lugar');
            if (horaInput && lugarInput) {
                const hora = horaInput.value;
                const lugar = lugarInput.value;
                if (hora && lugar) {
                    horarios.push({ hora, lugar });
                }
            }
        });

        const horariosField = document.getElementById('horariosJSON');
        if (horariosField) {
            horariosField.value = JSON.stringify(horarios);
        }

        // Procesar paradas
        const paradas = [];
        const paradasItems = document.querySelectorAll('.parada-item');
        paradasItems.forEach(item => {
            const nombreInput = item.querySelector('.parada-nombre');
            if (nombreInput) {
                const nombre = nombreInput.value.trim();
                if (nombre) {
                    paradas.push(nombre);
                }
            }
        });

        const paradasField = document.getElementById('paradasJSON');
        if (paradasField) {
            paradasField.value = JSON.stringify(paradas);
        }

        // Permitir que el formulario se envíe
        console.log('Formulario procesado, enviando...', {
            horarios: horarios,
            paradas: paradas
        });
    } catch (error) {
        console.error('Error al procesar formulario:', error);
        // Permitir que el formulario se envíe de todas formas
    }
});

function deleteRoute(id) {
    if (confirm('¿Estás seguro de que deseas eliminar esta ruta? Esta acción no se puede deshacer.')) {
        fetch('<?= Config::getBaseUrl() ?>?route=admin/routes/delete/' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Ruta eliminada exitosamente');
                window.location.href = '<?= Config::getBaseUrl() ?>?route=admin/routes';
            } else {
                alert('Error al eliminar la ruta: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar la ruta');
        });
    }
}
</script>

<?php include __DIR__ . '/../../layouts/admin_footer.php'; ?>
