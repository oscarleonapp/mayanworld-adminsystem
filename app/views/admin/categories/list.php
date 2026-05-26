<?php
/**
 * Vista: Gestión de Categorías
 * Con drag & drop, icon selector, color picker
 */

$pageTitle = 'Categorías de Tours';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="admin-categories">
    <?php
        $actionTitle = 'Categorías';
        $actionSubtitle = 'Organiza tus tours por categorías con iconos y colores personalizados';
        $actionButtons = [
            [
                'label' => 'Nueva Categoría',
                'icon' => 'fas fa-plus',
                'variant' => 'primary',
                'attributes' => [
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#modalCategory'
                ]
            ]
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Estadísticas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <i class="fas fa-tags text-primary fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total Categorías</div>
                            <div class="h4 mb-0"><?= $stats['total'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <i class="fas fa-check-circle text-success fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Activas</div>
                            <div class="h4 mb-0"><?= $stats['activas'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded p-3">
                                <i class="fas fa-box text-info fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Con Tours</div>
                            <div class="h4 mb-0"><?= $stats['con_tours'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded p-3">
                                <i class="fas fa-box-open text-warning fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Tours Totales</div>
                            <div class="h4 mb-0"><?= $stats['total_tours'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de categorías (sortable) -->
    <div class="card">
        <div class="card-header bg-light">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0">
                        <i class="fas fa-grip-vertical text-muted me-2"></i>
                        Arrastra para reordenar
                    </h5>
                </div>
                <div class="col-auto">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="showInactive">
                        <label class="form-check-label" for="showInactive">Mostrar inactivas</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;"></th>
                            <th style="width: 60px;">Icono</th>
                            <th>Nombre</th>
                            <th>Slug</th>
                            <th>Descripción</th>
                            <th style="width: 120px;">Tours</th>
                            <th style="width: 100px;">Estado</th>
                            <th style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="categoriesList" class="sortable-categories">
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">
                                    <i class="fas fa-tags fa-3x mb-3 opacity-25"></i>
                                    <p>No hay categorías. Crea la primera para comenzar.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr class="category-row"
                                    data-id="<?= $cat['id'] ?>"
                                    data-activo="<?= $cat['activo'] ?>"
                                    data-tours="<?= $cat['tours_count'] ?? 0 ?>">
                                    <td class="text-center">
                                        <i class="fas fa-grip-vertical text-muted sortable-handle" style="cursor: move;"></i>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $color = $cat['color'] ?? '#3b82f6'; // Azul por defecto
                                        $icono = $cat['icono'] ?? 'fas fa-tag'; // Icono por defecto
                                        ?>
                                        <div class="category-icon-preview"
                                             style="width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background-color: <?= htmlspecialchars($color) ?>15;">
                                            <i class="<?= htmlspecialchars($icono) ?>"
                                               style="color: <?= htmlspecialchars($color) ?>; font-size: 20px;"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($cat['nombre']) ?></strong>
                                    </td>
                                    <td>
                                        <code class="text-muted"><?= htmlspecialchars($cat['slug']) ?></code>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 250px;">
                                            <?= htmlspecialchars($cat['descripcion'] ?? 'Sin descripción') ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if (($cat['tours_count'] ?? 0) > 0): ?>
                                            <span class="badge bg-info">
                                                <?= $cat['tours_count'] ?> tours
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">Sin tours</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input toggle-active"
                                                   type="checkbox"
                                                   data-id="<?= $cat['id'] ?>"
                                                   <?= $cat['activo'] ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-edit"
                                                    data-id="<?= $cat['id'] ?>"
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-delete"
                                                    data-id="<?= $cat['id'] ?>"
                                                    data-nombre="<?= htmlspecialchars($cat['nombre']) ?>"
                                                    data-tours="<?= $cat['tours_count'] ?? 0 ?>"
                                                    title="Eliminar"
                                                    <?= ($cat['tours_count'] ?? 0) > 0 ? 'disabled' : '' ?>>
                                                <i class="fas fa-trash"></i>
                                            </button>
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

<!-- Modal: Crear/Editar Categoría -->
<div class="modal fade" id="modalCategory" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-tag me-2"></i>
                    <span id="modalTitle">Nueva Categoría</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formCategory" data-no-loading="true">
                <input type="hidden" name="id" id="catId">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nombre de la Categoría *</label>
                            <input type="text" class="form-control" name="nombre" id="catNombre" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Slug (URL)</label>
                            <input type="text" class="form-control" name="slug" id="catSlug" readonly>
                            <small class="text-muted">Se genera automáticamente</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" id="catDescripcion" rows="3"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Icono (Font Awesome) *</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i id="iconPreview" class="fas fa-tag"></i>
                                </span>
                                <input type="text"
                                       class="form-control"
                                       name="icono"
                                       id="catIcono"
                                       value="fas fa-tag"
                                       placeholder="fas fa-tag"
                                       required>
                                <button type="button" class="btn btn-outline-secondary" id="btnIconPicker">
                                    <i class="fas fa-icons"></i> Seleccionar
                                </button>
                            </div>
                            <small class="text-muted">Ejemplo: fas fa-umbrella-beach, fas fa-mountain</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Color *</label>
                            <div class="input-group">
                                <input type="color"
                                       class="form-control form-control-color"
                                       name="color"
                                       id="catColor"
                                       value="#3b82f6"
                                       required>
                                <input type="text"
                                       class="form-control"
                                       id="catColorHex"
                                       value="#3b82f6"
                                       pattern="^#[0-9A-Fa-f]{6}$">
                            </div>
                            <small class="text-muted">Color para badge e icono</small>
                        </div>

                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <label class="form-label">Vista Previa</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <div id="categoryPreview"
                                             style="width: 80px; height: 80px; border-radius: 12px; display: flex; align-items: center; justify-content: center; background-color: #3b82f615;">
                                            <i class="fas fa-tag" style="color: #3b82f6; font-size: 32px;"></i>
                                        </div>
                                        <div>
                                            <h5 id="previewNombre">Nombre de Categoría</h5>
                                            <p class="text-muted mb-0" id="previewDescripcion">Descripción...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="activo" id="catActivo" checked>
                                <label class="form-check-label" for="catActivo">Categoría activa</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Guardar Categoría
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Icon Picker (Simple) -->
<div class="modal fade" id="modalIconPicker" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Seleccionar Icono</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" class="form-control mb-3" id="iconSearch" placeholder="Buscar icono...">
                <div class="row g-2" id="iconGrid">
                    <!-- Icons populares para categorías de viajes -->
                    <?php
                    $iconos = [
                        'fas fa-umbrella-beach', 'fas fa-mountain', 'fas fa-city', 'fas fa-hiking',
                        'fas fa-spa', 'fas fa-water', 'fas fa-tree', 'fas fa-snowflake',
                        'fas fa-compass', 'fas fa-map-marked-alt', 'fas fa-camera', 'fas fa-binoculars',
                        'fas fa-campground', 'fas fa-hotel', 'fas fa-plane', 'fas fa-ship',
                        'fas fa-bicycle', 'fas fa-running', 'fas fa-swimmer', 'fas fa-skiing',
                        'fas fa-flag', 'fas fa-landmark', 'fas fa-monument', 'fas fa-church',
                        'fas fa-sun', 'fas fa-moon', 'fas fa-star', 'fas fa-heart',
                        'fas fa-fire', 'fas fa-bolt', 'fas fa-crown', 'fas fa-gem'
                    ];
                    foreach ($iconos as $icono):
                    ?>
                        <div class="col-2 text-center">
                            <button type="button"
                                    class="btn btn-outline-secondary w-100 icon-option"
                                    data-icon="<?= $icono ?>"
                                    style="aspect-ratio: 1;">
                                <i class="<?= $icono ?> fa-2x"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalCategory'));
    const iconPickerModal = new bootstrap.Modal(document.getElementById('modalIconPicker'));
    const form = document.getElementById('formCategory');
    const submitBtn = form.querySelector('button[type="submit"]');
    const submitLabel = submitBtn ? submitBtn.innerHTML : '';

    function setSubmitLoading(isLoading) {
        if (!submitBtn) return;
        if (!submitBtn.dataset.originalContent) {
            submitBtn.dataset.originalContent = submitLabel || submitBtn.innerHTML;
        }
        if (isLoading) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
        } else {
            submitBtn.disabled = false;
            submitBtn.innerHTML = submitBtn.dataset.originalContent;
        }
    }

    // Auto-generar slug desde nombre
    document.getElementById('catNombre').addEventListener('input', function() {
        const slug = this.value
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
        document.getElementById('catSlug').value = slug;
        updatePreview();
    });

    // Sincronizar color picker
    document.getElementById('catColor').addEventListener('input', function() {
        document.getElementById('catColorHex').value = this.value;
        updatePreview();
    });

    document.getElementById('catColorHex').addEventListener('input', function() {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
            document.getElementById('catColor').value = this.value;
            updatePreview();
        }
    });

    // Actualizar preview de icono
    document.getElementById('catIcono').addEventListener('input', function() {
        document.getElementById('iconPreview').className = this.value;
        updatePreview();
    });

    document.getElementById('catDescripcion').addEventListener('input', updatePreview);

    function updatePreview() {
        const nombre = document.getElementById('catNombre').value || 'Nombre de Categoría';
        const descripcion = document.getElementById('catDescripcion').value || 'Descripción...';
        const icono = document.getElementById('catIcono').value || 'fas fa-tag';
        const color = document.getElementById('catColor').value || '#3b82f6';

        document.getElementById('previewNombre').textContent = nombre;
        document.getElementById('previewDescripcion').textContent = descripcion;

        const preview = document.getElementById('categoryPreview');
        preview.style.backgroundColor = color + '15';
        preview.querySelector('i').className = icono;
        preview.querySelector('i').style.color = color;
    }

    // Icon Picker
    document.getElementById('btnIconPicker').addEventListener('click', () => iconPickerModal.show());

    document.querySelectorAll('.icon-option').forEach(btn => {
        btn.addEventListener('click', function() {
            const icon = this.dataset.icon;
            document.getElementById('catIcono').value = icon;
            document.getElementById('iconPreview').className = icon;
            updatePreview();
            iconPickerModal.hide();
        });
    });

    // Filtro de búsqueda de iconos
    document.getElementById('iconSearch').addEventListener('input', function() {
        const search = this.value.toLowerCase();
        document.querySelectorAll('.icon-option').forEach(btn => {
            const icon = btn.dataset.icon.toLowerCase();
            btn.closest('.col-2').style.display = icon.includes(search) ? '' : 'none';
        });
    });

    // Guardar categoría
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        formData.append('ajax', '1');
        const id = document.getElementById('catId').value;
        const url = id ? `?route=admin/categories/edit/${id}` : '?route=admin/categories/create';

        try {
            setSubmitLoading(true);
            const response = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const responseText = await response.text();
            let result = null;
            if (responseText) {
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.warn('Respuesta no JSON:', responseText);
                }
            }

            if (!response.ok) {
                const message = result?.message || `Error ${response.status}: ${response.statusText}`;
                showToast(message, 'error');
                setSubmitLoading(false);
                return;
            }

            if (!result) {
                throw new Error('Respuesta inválida del servidor');
            }

            console.log('Respuesta del servidor:', result);

            if (result.success) {
                showToast('Categoría guardada correctamente', 'success');
                modal.hide();
                setTimeout(() => location.reload(), 500);
            } else {
                showToast(result.message || 'Error al guardar', 'error');
                setSubmitLoading(false);
            }
        } catch (error) {
            console.error('Error al guardar:', error);
            showToast('Error: ' + error.message, 'error');
            setSubmitLoading(false);
        }
    });

    // Editar categoría
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;

            try {
                const response = await fetch(`?route=admin/categories/edit/${id}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const cat = await response.json();

                // Verificar si hay error en la respuesta
                if (cat.success === false) {
                    throw new Error(cat.message || 'Error al cargar categoría');
                }

                console.log('Categoría cargada:', cat);

                document.getElementById('modalTitle').textContent = 'Editar Categoría';
                document.getElementById('catId').value = cat.id;
                document.getElementById('catNombre').value = cat.nombre;
                document.getElementById('catSlug').value = cat.slug;
                document.getElementById('catDescripcion').value = cat.descripcion || '';
                document.getElementById('catIcono').value = cat.icono || 'fas fa-tag';
                document.getElementById('catColor').value = cat.color || '#3b82f6';
                document.getElementById('catColorHex').value = cat.color || '#3b82f6';
                document.getElementById('catActivo').checked = cat.activo == 1;

                updatePreview();
                modal.show();
            } catch (error) {
                console.error('Error al cargar categoría:', error);
                alert('Error al cargar categoría: ' + error.message);
            }
        });
    });

    // Eliminar categoría
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            const nombre = this.dataset.nombre;
            const tours = parseInt(this.dataset.tours);

            if (tours > 0) {
                showToast('No puedes eliminar una categoría con tours asignados', 'error');
                return;
            }

            if (!confirm(`¿Eliminar la categoría "${nombre}"?`)) return;

            try {
                const response = await fetch(`?route=admin/categories/delete/${id}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>' })
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Categoría eliminada', 'success');
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
                const response = await fetch('?route=admin/categories/toggle', {
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
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                console.log('Toggle estado respuesta:', result);

                if (result.success) {
                    showToast(activo ? 'Categoría activada' : 'Categoría desactivada', 'success');
                } else {
                    this.checked = !this.checked;
                    showToast(result.message || 'Error al cambiar estado', 'error');
                }
            } catch (error) {
                console.error('Error al cambiar estado:', error);
                this.checked = !this.checked;
                showToast('Error: ' + error.message, 'error');
            }
        });
    });

    // Sortable (drag & drop)
    new Sortable(document.getElementById('categoriesList'), {
        handle: '.sortable-handle',
        animation: 150,
        onEnd: async function(evt) {
            const orden = Array.from(evt.to.children).map(tr => tr.dataset.id);

            try {
                const response = await fetch('?route=admin/categories/update-order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        orden: orden,
                        csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
                    })
                });

                const result = await response.json();
                if (result.success) {
                    showToast('Orden actualizado', 'success');
                }
            } catch (error) {
                showToast('Error al actualizar orden', 'error');
            }
        }
    });

    // Mostrar/ocultar inactivas
    document.getElementById('showInactive').addEventListener('change', function() {
        document.querySelectorAll('.category-row').forEach(row => {
            if (row.dataset.activo === '0') {
                row.style.display = this.checked ? '' : 'none';
            }
        });
    });

    function showToast(message, type) {
        if (typeof AdminUI !== 'undefined' && typeof AdminUI.toast === 'function') {
            const map = {
                success: 'success',
                error: 'danger',
                warning: 'warning',
                info: 'info'
            };
            AdminUI.toast(message, map[type] || 'info');
            return;
        }
        alert(message);
    }
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
