<?php
use App\Core\Config;
include __DIR__ . '/../../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <?php
      $actionTitle = 'Editar Empleado';
      $actionSubtitle = 'Modifica la información del empleado';
      $actionButtons = [
        ['label' => 'Volver a Personal', 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary', 'href' => Config::getBaseUrl() . '?route=admin/staff'],
      ];
      include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

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

    <!-- Staff Form -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-user-edit me-2"></i>
                Información del Empleado
                <?php if (!empty($employee['id'])): ?>
                    <small class="text-muted">#<?= $employee['id'] ?></small>
                <?php endif; ?>
            </h6>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <!-- Información Personal -->
                    <div class="col-md-6">
                        <h5 class="mb-3">Información Personal</h5>

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre"
                                   value="<?= htmlspecialchars($employee['nombre'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="apellido" name="apellido"
                                   value="<?= htmlspecialchars($employee['apellido'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?= htmlspecialchars($employee['email'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="telefono" name="telefono"
                                   value="<?= htmlspecialchars($employee['telefono'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="dpi" class="form-label">DPI</label>
                            <input type="text" class="form-control" id="dpi" name="dpi"
                                   value="<?= htmlspecialchars($employee['dpi'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento"
                                   value="<?= htmlspecialchars($employee['fecha_nacimiento'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="direccion" class="form-label">Dirección</label>
                            <textarea class="form-control" id="direccion" name="direccion" rows="2"><?= htmlspecialchars($employee['direccion'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- Información Laboral -->
                    <div class="col-md-6">
                        <h5 class="mb-3">Información Laboral</h5>

                        <div class="mb-3">
                            <label for="tipo_empleado" class="form-label">Tipo de Empleado <span class="text-danger">*</span></label>
                            <select class="form-select" id="tipo_empleado" name="tipo_empleado" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($employeeTypes ?? [] as $et): ?>
                                    <option value="<?= htmlspecialchars($et['slug']) ?>" <?= (isset($employee['tipo_empleado']) && $employee['tipo_empleado'] == $et['slug']) ? 'selected' : '' ?>><?= htmlspecialchars($et['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="puesto" class="form-label">Puesto <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="puesto" name="puesto"
                                   value="<?= htmlspecialchars($employee['puesto'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="salario" class="form-label">Salario</label>
                            <div class="input-group">
                                <span class="input-group-text">Q</span>
                                <input type="number" step="0.01" class="form-control" id="salario" name="salario"
                                       value="<?= htmlspecialchars($employee['salario'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="experiencia_anios" class="form-label">Años de Experiencia</label>
                            <input type="number" min="0" class="form-control" id="experiencia_anios" name="experiencia_anios"
                                   value="<?= htmlspecialchars($employee['experiencia_anios'] ?? '0') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Idiomas</label>
                            <?php
                                $employeeIdiomas = [];
                                if (!empty($employee['idiomas'])) {
                                    $decoded = json_decode($employee['idiomas'], true);
                                    if (is_array($decoded)) {
                                        $employeeIdiomas = $decoded;
                                    } else {
                                        $employeeIdiomas = array_map('trim', explode(',', $employee['idiomas']));
                                    }
                                }
                            ?>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($languages ?? [] as $lang): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="idiomas[]"
                                               value="<?= htmlspecialchars($lang['nombre']) ?>"
                                               id="editLang_<?= $lang['id'] ?>"
                                               <?= in_array($lang['nombre'], $employeeIdiomas) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="editLang_<?= $lang['id'] ?>">
                                            <?= htmlspecialchars($lang['nombre']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="certificaciones" class="form-label">Certificaciones (separadas por coma)</label>
                            <input type="text" class="form-control" id="certificaciones" name="certificaciones"
                                   value="<?= htmlspecialchars($employee['certificaciones'] ?? '') ?>"
                                   placeholder="Guía Certificado INGUAT, Primeros Auxilios">
                        </div>

                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="activo" <?= (isset($employee['estado']) && $employee['estado'] == 'activo') ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= (isset($employee['estado']) && $employee['estado'] == 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                                <option value="suspendido" <?= (isset($employee['estado']) && $employee['estado'] == 'suspendido') ? 'selected' : '' ?>>Suspendido</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Foto -->
                <hr class="my-4">
                <h5 class="mb-3">Foto del Empleado</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="foto" class="form-label">Foto</label>
                            <?php if (!empty($employee['foto'])): ?>
                                <div class="mb-2">
                                    <img src="<?= Config::getBaseUrl() . 'uploads/staff/' . htmlspecialchars($employee['foto']) ?>"
                                         alt="Foto actual" class="img-thumbnail" style="max-height: 150px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                            <div class="form-text">Formatos: JPG, PNG. Tamaño máximo: 2MB</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="notas" class="form-label">Notas</label>
                            <textarea class="form-control" id="notas" name="notas" rows="5"><?= htmlspecialchars($employee['notas'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Metadatos -->
                <?php if (!empty($employee['fecha_contratacion']) || !empty($employee['created_at'])): ?>
                    <hr class="my-4">
                    <h5 class="mb-3">Información del Sistema</h5>
                    <div class="row">
                        <?php if (!empty($employee['fecha_contratacion'])): ?>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Contratación</label>
                                    <input type="text" class="form-control" value="<?= date('d/m/Y', strtotime($employee['fecha_contratacion'])) ?>" readonly>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($employee['created_at'])): ?>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Creación</label>
                                    <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($employee['created_at'])) ?>" readonly>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($employee['updated_at'])): ?>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Última Actualización</label>
                                    <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($employee['updated_at'])) ?>" readonly>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Botones de acción -->
                <hr class="my-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <?php if (!empty($employee['id'])): ?>
                            <button type="button" class="btn btn-outline-danger" onclick="deleteEmployee(<?= $employee['id'] ?>)">
                                <i class="fas fa-trash me-2"></i>Eliminar Empleado
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/staff" class="btn btn-outline-secondary">
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
function deleteEmployee(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este empleado? Esta acción no se puede deshacer.')) {
        fetch('<?= Config::getBaseUrl() ?>?route=admin/staff/delete/' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Empleado eliminado exitosamente');
                window.location.href = '<?= Config::getBaseUrl() ?>?route=admin/staff';
            } else {
                alert('Error al eliminar el empleado: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el empleado');
        });
    }
}
</script>

<?php include __DIR__ . '/../../layouts/admin_footer.php'; ?>
