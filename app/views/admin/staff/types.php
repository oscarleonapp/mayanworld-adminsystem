<?php
/**
 * Vista: Gestión de Tipos de Empleados
 */

$pageTitle = 'Tipos de Empleados';
require_once __DIR__ . '/../../layouts/admin_header.php';

use App\Core\Config;
$baseUrl = Config::getBaseUrl();
?>

<div class="admin-employee-types">
    <?php
        $actionTitle = 'Tipos de Empleados';
        $actionSubtitle = 'Configura los tipos de empleado disponibles para asignar al personal';
        $actionButtons = [
            [
                'label' => 'Nuevo Tipo',
                'icon' => 'fas fa-plus',
                'variant' => 'primary',
                'attributes' => [
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#modalType'
                ]
            ]
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Tabla de tipos -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Identificador</th>
                            <th>Descripcion</th>
                            <th style="width: 120px;" class="text-center">Empleados</th>
                            <th style="width: 100px;">Estado</th>
                            <th style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($types)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-id-badge fa-3x mb-3 opacity-25"></i>
                                    <p>No hay tipos de empleado. Crea el primero para comenzar.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($types as $type): ?>
                                <tr data-id="<?= $type['id'] ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($type['nombre']) ?></strong>
                                    </td>
                                    <td>
                                        <code class="text-muted"><?= htmlspecialchars($type['slug']) ?></code>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px;">
                                            <?= htmlspecialchars($type['descripcion'] ?? 'Sin descripcion') ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if (($type['empleados_count'] ?? 0) > 0): ?>
                                            <span class="badge bg-info">
                                                <?= $type['empleados_count'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input toggle-active"
                                                   type="checkbox"
                                                   data-id="<?= $type['id'] ?>"
                                                   <?= $type['activo'] ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-edit"
                                                    data-id="<?= $type['id'] ?>"
                                                    data-nombre="<?= htmlspecialchars($type['nombre']) ?>"
                                                    data-slug="<?= htmlspecialchars($type['slug']) ?>"
                                                    data-descripcion="<?= htmlspecialchars($type['descripcion'] ?? '') ?>"
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" action="<?= $baseUrl ?>?route=admin/staff/types/delete/<?= $type['id'] ?>"
                                                  class="d-inline form-delete"
                                                  data-nombre="<?= htmlspecialchars($type['nombre']) ?>"
                                                  data-count="<?= $type['empleados_count'] ?? 0 ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                                        title="Eliminar"
                                                        <?= ($type['empleados_count'] ?? 0) > 0 ? 'disabled' : '' ?>>
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Crear/Editar Tipo de Empleado -->
<div class="modal fade" id="modalType" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-id-badge me-2"></i>
                    <span id="modalTitle">Nuevo Tipo de Empleado</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formType" method="POST" action="<?= $baseUrl ?>?route=admin/staff/types/create">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="id" id="typeId">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control" name="nombre" id="typeNombre" required
                                   placeholder="Ej: Guia Turistico, Conductor...">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Identificador (slug)</label>
                            <input type="text" class="form-control" name="slug" id="typeSlug" readonly>
                            <small class="text-muted">Se genera automaticamente del nombre</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripcion</label>
                            <textarea class="form-control" name="descripcion" id="typeDescripcion" rows="3"
                                      placeholder="Descripcion opcional del tipo de empleado"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        <span id="btnSubmitText">Guardar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalType'));
    const form = document.getElementById('formType');
    const baseUrl = '<?= $baseUrl ?>';

    // Auto-generar slug desde nombre
    document.getElementById('typeNombre').addEventListener('input', function() {
        const slug = this.value
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_|_$/g, '');
        document.getElementById('typeSlug').value = slug;
    });

    // Reset modal al abrir para crear nuevo
    document.getElementById('modalType').addEventListener('show.bs.modal', function(e) {
        // Solo resetear si no fue activado por un boton de editar
        if (!e.relatedTarget || !e.relatedTarget.classList.contains('btn-edit')) {
            document.getElementById('modalTitle').textContent = 'Nuevo Tipo de Empleado';
            document.getElementById('btnSubmitText').textContent = 'Guardar';
            form.action = baseUrl + '?route=admin/staff/types/create';
            document.getElementById('typeId').value = '';
            document.getElementById('typeNombre').value = '';
            document.getElementById('typeSlug').value = '';
            document.getElementById('typeDescripcion').value = '';
        }
    });

    // Editar tipo
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            document.getElementById('modalTitle').textContent = 'Editar Tipo de Empleado';
            document.getElementById('btnSubmitText').textContent = 'Actualizar';
            form.action = baseUrl + '?route=admin/staff/types/edit/' + id;
            document.getElementById('typeId').value = id;
            document.getElementById('typeNombre').value = this.dataset.nombre;
            document.getElementById('typeSlug').value = this.dataset.slug;
            document.getElementById('typeDescripcion').value = this.dataset.descripcion;

            modal.show();
        });
    });

    // Confirmar eliminacion
    document.querySelectorAll('.form-delete').forEach(form => {
        form.addEventListener('submit', function(e) {
            const nombre = this.dataset.nombre;
            const count = parseInt(this.dataset.count);

            if (count > 0) {
                e.preventDefault();
                showToast('No se puede eliminar: hay ' + count + ' empleado(s) con este tipo asignado', 'error');
                return;
            }

            if (!confirm('¿Eliminar el tipo "' + nombre + '"? Esta accion no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });

    // Toggle activo
    document.querySelectorAll('.toggle-active').forEach(toggle => {
        toggle.addEventListener('change', async function() {
            const id = this.dataset.id;
            const checkbox = this;

            try {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');

                const response = await fetch(baseUrl + '?route=admin/staff/types/toggle', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    showToast(checkbox.checked ? 'Tipo activado' : 'Tipo desactivado', 'success');
                } else {
                    checkbox.checked = !checkbox.checked;
                    showToast(result.message || 'Error al cambiar estado', 'error');
                }
            } catch (error) {
                checkbox.checked = !checkbox.checked;
                showToast('Error de conexion', 'error');
            }
        });
    });

    function showToast(message, type) {
        if (typeof AdminUI !== 'undefined' && typeof AdminUI.toast === 'function') {
            const map = { success: 'success', error: 'danger', warning: 'warning', info: 'info' };
            AdminUI.toast(message, map[type] || 'info');
            return;
        }
        alert(message);
    }
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
