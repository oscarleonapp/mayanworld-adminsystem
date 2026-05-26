<?php
use App\Core\Config;
/**
 * Vista: Listado de Bloques de Contenido
 * Permite gestionar todos los bloques editables del sitio web
 */

$pageTitle = 'Bloques de Contenido';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="admin-content-blocks">
    <?php
        $actionTitle = 'Bloques de Contenido';
        $actionSubtitle = 'Gestiona el contenido editable de cada sección del sitio web';
        $actionButtons = [
            [
                'label' => 'Nuevo Bloque',
                'icon' => 'fas fa-plus',
                'variant' => 'primary',
                'attributes' => [
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#modalCreateBlock'
                ]
            ]
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Filtros y búsqueda -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="searchBlocks" placeholder="Buscar por título...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sección</label>
                    <select class="form-select" id="filterSection">
                        <option value="">Todas las secciones</option>
                        <option value="hero">Hero</option>
                        <option value="trust_bar">Trust Bar</option>
                        <option value="features">Features</option>
                        <option value="newsletter">Newsletter</option>
                        <option value="footer">Footer</option>
                        <option value="general">General</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" id="filterTipo">
                        <option value="">Todos los tipos</option>
                        <option value="texto">Texto</option>
                        <option value="imagen">Imagen</option>
                        <option value="html">HTML</option>
                        <option value="video">Video</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select class="form-select" id="filterActivo">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Inactivos</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de bloques agrupados por sección -->
    <div id="blocksContainer">
        <?php if (empty($groupedBlocks)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No hay bloques de contenido. Crea el primero para comenzar.
            </div>
        <?php else: ?>
            <?php foreach ($groupedBlocks as $seccion => $bloques): ?>
                <div class="card mb-4 section-card" data-section="<?= htmlspecialchars($seccion) ?>">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-layer-group text-primary me-2"></i>
                            Sección: <strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $seccion))) ?></strong>
                            <span class="badge bg-secondary ms-2"><?= count($bloques) ?> bloques</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;"><i class="fas fa-grip-vertical text-muted"></i></th>
                                        <th>Título</th>
                                        <th>Tipo</th>
                                        <th>Contenido</th>
                                        <th style="width: 100px;">Orden</th>
                                        <th style="width: 100px;">Estado</th>
                                        <th style="width: 150px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="sortable-blocks" data-section="<?= htmlspecialchars($seccion) ?>">
                                    <?php foreach ($bloques as $bloque): ?>
                                        <tr class="block-row"
                                            data-id="<?= $bloque['id'] ?>"
                                            data-tipo="<?= htmlspecialchars($bloque['tipo'] ?? 'texto') ?>"
                                            data-activo="<?= $bloque['activo'] ?? 0 ?>">
                                            <td class="text-center">
                                                <i class="fas fa-grip-vertical text-muted sortable-handle" style="cursor: move;"></i>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($bloque['titulo'] ?? 'Sin título') ?></strong>
                                            </td>
                                            <td>
                                                <?php
                                                $tipo = $bloque['tipo'] ?? 'texto';
                                                $tipoIcons = [
                                                    'texto' => 'fa-font',
                                                    'imagen' => 'fa-image',
                                                    'html' => 'fa-code',
                                                    'video' => 'fa-video'
                                                ];
                                                $tipoColors = [
                                                    'texto' => 'primary',
                                                    'imagen' => 'success',
                                                    'html' => 'warning',
                                                    'video' => 'danger'
                                                ];
                                                $icon = $tipoIcons[$tipo] ?? 'fa-file';
                                                $color = $tipoColors[$tipo] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $color ?>">
                                                    <i class="fas <?= $icon ?> me-1"></i>
                                                    <?= htmlspecialchars(ucfirst($tipo)) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (($bloque['tipo'] ?? 'texto') === 'imagen'): ?>
                                                    <?php if (!empty($bloque['imagen'])): ?>
                                                        <img src="<?= Config::getBaseUrl() ?>uploads/blocks/<?= htmlspecialchars($bloque['imagen']) ?>"
                                                             alt="Preview"
                                                             class="img-thumbnail"
                                                             style="max-width: 100px; max-height: 60px;">
                                                    <?php else: ?>
                                                        <span class="text-muted">Sin imagen</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <div class="text-truncate" style="max-width: 300px;">
                                                        <?= htmlspecialchars(substr($bloque['contenido'] ?? '', 0, 80)) ?>
                                                        <?= strlen($bloque['contenido'] ?? '') > 80 ? '...' : '' ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-light text-dark"><?= $bloque['orden'] ?? 0 ?></span>
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input toggle-active"
                                                           type="checkbox"
                                                           data-id="<?= $bloque['id'] ?>"
                                                           <?= ($bloque['activo'] ?? 0) ? 'checked' : '' ?>>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary btn-edit"
                                                            data-id="<?= $bloque['id'] ?>"
                                                            title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-delete"
                                                            data-id="<?= $bloque['id'] ?>"
                                                            data-titulo="<?= htmlspecialchars($bloque['titulo'] ?? 'Sin título') ?>"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Crear/Editar Bloque -->
<div class="modal fade" id="modalCreateBlock" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cube me-2"></i>
                    <span id="modalTitle">Nuevo Bloque de Contenido</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formBlock">
                <input type="hidden" name="id" id="blockId">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Título del Bloque *</label>
                            <input type="text" class="form-control" name="titulo" id="blockTitulo" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Orden</label>
                            <input type="number" class="form-control" name="orden" id="blockOrden" value="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Sección *</label>
                            <select class="form-select" name="seccion" id="blockSeccion" required>
                                <option value="hero">Hero</option>
                                <option value="trust_bar">Trust Bar</option>
                                <option value="features">Features</option>
                                <option value="newsletter">Newsletter</option>
                                <option value="footer">Footer</option>
                                <option value="general">General</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Tipo de Contenido *</label>
                            <select class="form-select" name="tipo" id="blockTipo" required>
                                <option value="texto">Texto</option>
                                <option value="imagen">Imagen</option>
                                <option value="html">HTML</option>
                                <option value="video">Video</option>
                            </select>
                        </div>

                        <div class="col-12" id="contenidoTextoContainer">
                            <label class="form-label">Contenido</label>
                            <textarea class="form-control" name="contenido" id="blockContenido" rows="4"></textarea>
                        </div>

                        <div class="col-12 d-none" id="contenidoImagenContainer">
                            <label class="form-label">Imagen</label>
                            <input type="file" class="form-control" id="blockImagen" accept="image/*">
                            <div id="imagePreview" class="mt-2"></div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="activo" id="blockActivo" checked>
                                <label class="form-check-label" for="blockActivo">Activo</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Guardar Bloque
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalCreateBlock'));
    const form = document.getElementById('formBlock');
    const tipoSelect = document.getElementById('blockTipo');

    // Cambiar campos según tipo
    tipoSelect.addEventListener('change', function() {
        const tipo = this.value;
        document.getElementById('contenidoTextoContainer').classList.toggle('d-none', tipo === 'imagen');
        document.getElementById('contenidoImagenContainer').classList.toggle('d-none', tipo !== 'imagen');
    });

    // Crear/Editar bloque
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const id = document.getElementById('blockId').value;
        const url = id
            ? '?route=admin/content-blocks/update'
            : '?route=admin/content-blocks/create';

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showToast('Bloque guardado correctamente', 'success');
                modal.hide();
                location.reload();
            } else {
                showToast(result.message || 'Error al guardar bloque', 'error');
            }
        } catch (error) {
            showToast('Error de conexión', 'error');
        }
    });

    // Editar bloque
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;

            try {
                const response = await fetch(`?route=admin/content-blocks/edit/${id}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Error response:', errorText);
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Response is not JSON:', text);
                    throw new Error('La respuesta del servidor no es JSON');
                }

                const bloque = await response.json();

                if (!bloque || !bloque.id) {
                    throw new Error('Datos del bloque inválidos');
                }

                document.getElementById('modalTitle').textContent = 'Editar Bloque';
                document.getElementById('blockId').value = bloque.id;
                document.getElementById('blockTitulo').value = bloque.titulo || '';
                document.getElementById('blockSeccion').value = bloque.seccion || 'general';
                document.getElementById('blockTipo').value = bloque.tipo || 'texto';
                document.getElementById('blockContenido').value = bloque.contenido || '';
                document.getElementById('blockOrden').value = bloque.orden || 0;
                document.getElementById('blockActivo').checked = bloque.activo == 1;

                tipoSelect.dispatchEvent(new Event('change'));
                modal.show();
            } catch (error) {
                console.error('Error completo:', error);
                showToast('Error al cargar bloque: ' + error.message, 'error');
            }
        });
    });

    // Eliminar bloque
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            const titulo = this.dataset.titulo;

            if (!confirm(`¿Eliminar el bloque "${titulo}"?`)) return;

            try {
                const response = await fetch(`?route=admin/content-blocks/delete/${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>' })
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Bloque eliminado', 'success');
                    this.closest('tr').remove();
                } else {
                    showToast(result.message || 'Error al eliminar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        });
    });

    // Toggle activo
    document.querySelectorAll('.toggle-active').forEach(toggle => {
        toggle.addEventListener('change', async function() {
            const id = this.dataset.id;
            const activo = this.checked ? 1 : 0;

            try {
                const response = await fetch('?route=admin/content-blocks/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        id: id,
                        activo: activo,
                        csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
                    })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Toggle error response:', errorText);
                    throw new Error(`Error ${response.status}: ${response.statusText}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Toggle response is not JSON:', text);
                    throw new Error('La respuesta del servidor no es JSON');
                }

                const result = await response.json();

                if (result.success) {
                    showToast(result.message || (activo ? 'Bloque activado' : 'Bloque desactivado'), 'success');
                } else {
                    this.checked = !this.checked;
                    showToast(result.message || 'Error al cambiar estado', 'error');
                }
            } catch (error) {
                console.error('Error completo toggle:', error);
                this.checked = !this.checked;
                showToast('Error: ' + error.message, 'error');
            }
        });
    });

    // Filtros
    ['searchBlocks', 'filterSection', 'filterTipo', 'filterActivo'].forEach(id => {
        document.getElementById(id).addEventListener('input', filterBlocks);
    });

    function filterBlocks() {
        const search = document.getElementById('searchBlocks').value.toLowerCase();
        const section = document.getElementById('filterSection').value;
        const tipo = document.getElementById('filterTipo').value;
        const activo = document.getElementById('filterActivo').value;

        document.querySelectorAll('.block-row').forEach(row => {
            const titulo = row.querySelector('strong').textContent.toLowerCase();
            const rowSection = row.closest('.section-card').dataset.section;
            const rowTipo = row.dataset.tipo;
            const rowActivo = row.dataset.activo;

            const matchSearch = search === '' || titulo.includes(search);
            const matchSection = section === '' || rowSection === section;
            const matchTipo = tipo === '' || rowTipo === tipo;
            const matchActivo = activo === '' || rowActivo === activo;

            row.style.display = matchSearch && matchSection && matchTipo && matchActivo ? '' : 'none';
        });
    }

    function showToast(message, type) {
        // Implementar toast notification
        alert(message);
    }
});
</script>

<style>
.sortable-handle {
    cursor: move;
}
.block-row:hover {
    background-color: #f8f9fa;
}
</style>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
