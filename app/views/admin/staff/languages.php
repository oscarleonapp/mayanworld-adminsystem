<?php
/**
 * Vista: Gestion de Idiomas
 */

$pageTitle = 'Idiomas';
require_once __DIR__ . '/../../layouts/admin_header.php';

use App\Core\Config;
$baseUrl = Config::getBaseUrl();
?>

<div class="admin-languages">
    <?php
        $actionTitle = 'Idiomas';
        $actionSubtitle = 'Configura los idiomas disponibles para asignar al personal';
        $actionButtons = [
            [
                'label' => 'Nuevo Idioma',
                'icon' => 'fas fa-plus',
                'variant' => 'primary',
                'attributes' => [
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#modalLang'
                ]
            ]
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Tabla de idiomas -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Codigo</th>
                            <th style="width: 120px;" class="text-center">Empleados</th>
                            <th style="width: 100px;">Estado</th>
                            <th style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($languages)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="fas fa-language fa-3x mb-3 opacity-25"></i>
                                    <p>No hay idiomas registrados. Crea el primero para comenzar.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($languages as $lang): ?>
                                <tr data-id="<?= $lang['id'] ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($lang['nombre']) ?></strong>
                                    </td>
                                    <td>
                                        <code class="text-muted"><?= htmlspecialchars($lang['codigo']) ?></code>
                                    </td>
                                    <td class="text-center">
                                        <?php if (($lang['empleados_count'] ?? 0) > 0): ?>
                                            <span class="badge bg-info">
                                                <?= $lang['empleados_count'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input toggle-active"
                                                   type="checkbox"
                                                   data-id="<?= $lang['id'] ?>"
                                                   <?= $lang['activo'] ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-edit"
                                                    data-id="<?= $lang['id'] ?>"
                                                    data-nombre="<?= htmlspecialchars($lang['nombre']) ?>"
                                                    data-codigo="<?= htmlspecialchars($lang['codigo']) ?>"
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" action="<?= $baseUrl ?>?route=admin/staff/languages/delete/<?= $lang['id'] ?>"
                                                  class="d-inline form-delete"
                                                  data-nombre="<?= htmlspecialchars($lang['nombre']) ?>"
                                                  data-count="<?= $lang['empleados_count'] ?? 0 ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm"
                                                        title="Eliminar"
                                                        <?= ($lang['empleados_count'] ?? 0) > 0 ? 'disabled' : '' ?>>
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

<!-- Modal: Crear/Editar Idioma -->
<div class="modal fade" id="modalLang" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-language me-2"></i>
                    <span id="modalTitle">Nuevo Idioma</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formLang" method="POST" action="<?= $baseUrl ?>?route=admin/staff/languages/create">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="id" id="langId">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control" name="nombre" id="langNombre" required
                                   placeholder="Ej: Español, Inglés, Q'eqchi'...">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Codigo</label>
                            <input type="text" class="form-control" name="codigo" id="langCodigo" readonly>
                            <small class="text-muted">Se genera automaticamente del nombre</small>
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
    const modal = new bootstrap.Modal(document.getElementById('modalLang'));
    const form = document.getElementById('formLang');
    const baseUrl = '<?= $baseUrl ?>';

    // Auto-generar codigo desde nombre
    document.getElementById('langNombre').addEventListener('input', function() {
        const codigo = this.value
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_|_$/g, '');
        document.getElementById('langCodigo').value = codigo;
    });

    // Reset modal al abrir para crear nuevo
    document.getElementById('modalLang').addEventListener('show.bs.modal', function(e) {
        if (!e.relatedTarget || !e.relatedTarget.classList.contains('btn-edit')) {
            document.getElementById('modalTitle').textContent = 'Nuevo Idioma';
            document.getElementById('btnSubmitText').textContent = 'Guardar';
            form.action = baseUrl + '?route=admin/staff/languages/create';
            document.getElementById('langId').value = '';
            document.getElementById('langNombre').value = '';
            document.getElementById('langCodigo').value = '';
        }
    });

    // Editar idioma
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            document.getElementById('modalTitle').textContent = 'Editar Idioma';
            document.getElementById('btnSubmitText').textContent = 'Actualizar';
            form.action = baseUrl + '?route=admin/staff/languages/edit/' + id;
            document.getElementById('langId').value = id;
            document.getElementById('langNombre').value = this.dataset.nombre;
            document.getElementById('langCodigo').value = this.dataset.codigo;

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
                showToast('No se puede eliminar: hay ' + count + ' empleado(s) con este idioma asignado', 'error');
                return;
            }

            if (!confirm('¿Eliminar el idioma "' + nombre + '"? Esta accion no se puede deshacer.')) {
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

                const response = await fetch(baseUrl + '?route=admin/staff/languages/toggle', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    showToast(checkbox.checked ? 'Idioma activado' : 'Idioma desactivado', 'success');
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
