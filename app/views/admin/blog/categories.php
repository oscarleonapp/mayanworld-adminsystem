<?php
/**
 * Vista: Admin - Gestión de Categorías del Blog
 * Con drag & drop para reordenar y modal para crear/editar
 */

use App\Core\Config;
use App\Core\Helpers;

require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<!-- Sortable.js para drag & drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<div class="admin-blog-categories">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="?route=admin">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="?route=admin/blog">Blog</a></li>
            <li class="breadcrumb-item active">Categorías</li>
        </ol>
    </nav>

    <?php
        $actionTitle = 'Categorías del Blog';
        $actionSubtitle = 'Organiza las categorías y el orden de publicación';
        $actionButtons = [
            ['label' => 'Volver al Blog', 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary', 'href' => '?route=admin/blog'],
            [
                'label' => 'Nueva Categoría',
                'icon' => 'fas fa-plus',
                'variant' => 'primary',
                'attributes' => [
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#categoryModal',
                    'onclick' => 'openCategoryModal()'
                ]
            ],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Ayuda -->
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Tip:</strong> Arrastra las categorías para reordenarlas. El orden se guarda automáticamente.
    </div>

    <!-- Tabla de categorías -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($categories)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay categorías creadas</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="openCategoryModal()">
                        <i class="fas fa-plus me-2"></i>
                        Crear primera categoría
                    </button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="categoriesTable">
                        <thead>
                            <tr>
                                <th style="width: 40px">
                                    <i class="fas fa-grip-vertical text-muted" title="Arrastra para reordenar"></i>
                                </th>
                                <th style="width: 60px">Icono</th>
                                <th>Nombre</th>
                                <th>Slug</th>
                                <th class="text-center">Posts</th>
                                <th class="text-center">Color</th>
                                <th class="text-center">Activo</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="sortableCategoriesList">
                            <?php foreach ($categories as $cat): ?>
                                <tr data-category-id="<?= $cat['id'] ?>">
                                    <td class="sortable-handle" style="cursor: move;">
                                        <i class="fas fa-grip-vertical text-muted"></i>
                                    </td>

                                    <td>
                                        <div class="category-icon-preview" style="font-size: 24px; color: <?= htmlspecialchars($cat['color']) ?>">
                                            <i class="fas <?= htmlspecialchars($cat['icono'] ?: 'fa-folder') ?>"></i>
                                        </div>
                                    </td>

                                    <td>
                                        <strong><?= htmlspecialchars($cat['nombre']) ?></strong>
                                        <?php if ($cat['descripcion']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars(substr($cat['descripcion'], 0, 80)) ?><?= strlen($cat['descripcion']) > 80 ? '...' : '' ?></small>
                                        <?php endif; ?>
                                    </td>

                                    <td class="small text-muted">
                                        <i class="fas fa-link me-1"></i>
                                        /blog/categoria/<?= htmlspecialchars($cat['slug']) ?>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge bg-secondary">
                                            <?= $cat['post_count'] ?? 0 ?> posts
                                        </span>
                                        <br><small class="text-success"><?= $cat['published_count'] ?? 0 ?> publicados</small>
                                    </td>

                                    <td class="text-center">
                                        <div class="d-inline-block rounded"
                                             style="width: 30px; height: 30px; background-color: <?= htmlspecialchars($cat['color']) ?>"
                                             title="<?= htmlspecialchars($cat['color']) ?>">
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input toggle-active"
                                                   type="checkbox"
                                                   data-category-id="<?= $cat['id'] ?>"
                                                   <?= $cat['activo'] ? 'checked' : '' ?>
                                                   title="<?= $cat['activo'] ? 'Activa' : 'Inactiva' ?>">
                                        </div>
                                    </td>

                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button"
                                                    class="btn btn-outline-primary"
                                                    onclick='editCategory(<?= json_encode($cat) ?>)'
                                                    data-bs-toggle="tooltip"
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-outline-danger delete-category"
                                                    data-category-id="<?= $cat['id'] ?>"
                                                    data-category-name="<?= htmlspecialchars($cat['nombre']) ?>"
                                                    data-post-count="<?= $cat['post_count'] ?? 0 ?>"
                                                    data-bs-toggle="tooltip"
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
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para Crear/Editar Categoría -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">
                    <i class="fas fa-folder me-2"></i>
                    <span id="modalTitle">Nueva Categoría</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="categoryForm">
                <div class="modal-body">
                    <input type="hidden" id="categoryId" name="id">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nombre *</label>
                            <input type="text"
                                   class="form-control"
                                   id="categoryNombre"
                                   name="nombre"
                                   required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Color</label>
                            <input type="color"
                                   class="form-control form-control-color"
                                   id="categoryColor"
                                   name="color"
                                   value="#3b82f6">
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Slug *</label>
                            <input type="text"
                                   class="form-control"
                                   id="categorySlug"
                                   name="slug"
                                   pattern="[a-z0-9\-]+"
                                   title="Solo letras minúsculas, números y guiones"
                                   required>
                            <small class="text-muted">Se genera automáticamente del nombre</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Orden</label>
                            <input type="number"
                                   class="form-control"
                                   id="categoryOrden"
                                   name="orden"
                                   value="0"
                                   min="0">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control"
                                      id="categoryDescripcion"
                                      name="descripcion"
                                      rows="3"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Icono (FontAwesome)</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas" id="iconPreview"></i>
                                </span>
                                <input type="text"
                                       class="form-control"
                                       id="categoryIcono"
                                       name="icono"
                                       placeholder="fa-folder"
                                       value="fa-folder">
                            </div>
                            <small class="text-muted">Ejemplo: fa-mountain, fa-hiking, fa-landmark</small>
                            <div class="mt-2">
                                <a href="https://fontawesome.com/v5/search?m=free&s=solid" target="_blank" class="small">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    Ver iconos disponibles
                                </a>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="categoryActivo"
                                       name="activo"
                                       value="1"
                                       checked>
                                <label class="form-check-label" for="categoryActivo">
                                    Categoría activa
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <hr>
                            <h6>SEO (Opcional)</h6>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Meta Título</label>
                            <input type="text"
                                   class="form-control"
                                   id="categoryMetaTitle"
                                   name="meta_title"
                                   maxlength="60">
                            <small class="text-muted" id="metaTitleCount">0 / 60</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Meta Descripción</label>
                            <textarea class="form-control"
                                      id="categoryMetaDescription"
                                      name="meta_description"
                                      rows="2"
                                      maxlength="160"></textarea>
                            <small class="text-muted" id="metaDescCount">0 / 160</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        <span id="btnSubmitText">Crear Categoría</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';

    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(el => new bootstrap.Tooltip(el));

    // Inicializar Sortable.js para drag & drop
    const sortableList = document.getElementById('sortableCategoriesList');
    if (sortableList) {
        new Sortable(sortableList, {
            handle: '.sortable-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: async function(evt) {
                // Recopilar nuevo orden
                const rows = sortableList.querySelectorAll('tr');
                const order = {};

                rows.forEach((row, index) => {
                    const categoryId = row.dataset.categoryId;
                    order[categoryId] = index;
                });

                // Enviar al servidor
                try {
                    const formData = new FormData();
                    formData.append('csrf_token', csrfToken);
                    formData.append('order', JSON.stringify(order));

                    const response = await fetch('?route=admin/blog/categorias/reordenar', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showToast('Orden actualizado correctamente', 'success');
                    } else {
                        showToast(data.message || 'Error al actualizar orden', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    showToast('Error al actualizar orden', 'error');
                }
            }
        });
    }

    // Auto-generar slug desde nombre
    document.getElementById('categoryNombre').addEventListener('input', function() {
        const slug = this.value
            .toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
        document.getElementById('categorySlug').value = slug;
    });

    // Preview de icono
    document.getElementById('categoryIcono').addEventListener('input', function() {
        const iconClass = this.value.trim();
        const preview = document.getElementById('iconPreview');
        preview.className = 'fas ' + (iconClass || 'fa-folder');
    });

    // Contadores de caracteres
    document.getElementById('categoryMetaTitle').addEventListener('input', function() {
        document.getElementById('metaTitleCount').textContent = `${this.value.length} / 60`;
    });

    document.getElementById('categoryMetaDescription').addEventListener('input', function() {
        document.getElementById('metaDescCount').textContent = `${this.value.length} / 160`;
    });

    // Submit del formulario
    document.getElementById('categoryForm').addEventListener('submit', async function(e) {
        e.preventDefault();

        const categoryId = document.getElementById('categoryId').value;
        const isEdit = categoryId !== '';

        const formData = new FormData(this);
        formData.append('csrf_token', csrfToken);
        formData.append('activo', document.getElementById('categoryActivo').checked ? '1' : '0');

        const url = isEdit ?
            `?route=admin/blog/categorias/editar/${categoryId}` :
            '?route=admin/blog/categorias/crear';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                showToast(isEdit ? 'Categoría actualizada' : 'Categoría creada', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                const errors = data.errors ? data.errors.join('<br>') : data.message;
                showToast(errors || 'Error al guardar categoría', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('Error al guardar categoría', 'error');
        }
    });

    // Toggle activo
    document.querySelectorAll('.toggle-active').forEach(toggle => {
        toggle.addEventListener('change', async function() {
            const categoryId = this.dataset.categoryId;
            const checkbox = this;

            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('id', categoryId);

                const response = await fetch('?route=admin/blog/categorias/toggle-activo', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    checkbox.title = data.activo ? 'Activa' : 'Inactiva';
                    showToast('Estado actualizado', 'success');
                } else {
                    checkbox.checked = !checkbox.checked;
                    showToast(data.message || 'Error al actualizar estado', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                checkbox.checked = !checkbox.checked;
                showToast('Error al actualizar estado', 'error');
            }
        });
    });

    // Eliminar categoría
    document.querySelectorAll('.delete-category').forEach(btn => {
        btn.addEventListener('click', async function() {
            const categoryId = this.dataset.categoryId;
            const categoryName = this.dataset.categoryName;
            const postCount = parseInt(this.dataset.postCount);

            if (postCount > 0) {
                alert(`No se puede eliminar la categoría "${categoryName}" porque tiene ${postCount} posts asociados.\n\nPrimero elimina o reasigna los posts.`);
                return;
            }

            if (!confirm(`¿Estás seguro de eliminar la categoría "${categoryName}"?`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);

                const response = await fetch(`?route=admin/blog/categorias/eliminar/${categoryId}`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Categoría eliminada', 'success');
                    this.closest('tr').remove();
                } else {
                    showToast(data.message || 'Error al eliminar categoría', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error al eliminar categoría', 'error');
            }
        });
    });
});

// Abrir modal para crear (limpiar campos)
function openCategoryModal() {
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('modalTitle').textContent = 'Nueva Categoría';
    document.getElementById('btnSubmitText').textContent = 'Crear Categoría';
    document.getElementById('categoryColor').value = '#3b82f6';
    document.getElementById('categoryIcono').value = 'fa-folder';
    document.getElementById('iconPreview').className = 'fas fa-folder';
    document.getElementById('categoryActivo').checked = true;
}

// Abrir modal para editar (cargar datos)
function editCategory(category) {
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryNombre').value = category.nombre;
    document.getElementById('categorySlug').value = category.slug;
    document.getElementById('categoryDescripcion').value = category.descripcion || '';
    document.getElementById('categoryIcono').value = category.icono || 'fa-folder';
    document.getElementById('categoryColor').value = category.color || '#3b82f6';
    document.getElementById('categoryOrden').value = category.orden || 0;
    document.getElementById('categoryMetaTitle').value = category.meta_title || '';
    document.getElementById('categoryMetaDescription').value = category.meta_description || '';
    document.getElementById('categoryActivo').checked = category.activo == 1;

    document.getElementById('iconPreview').className = 'fas ' + (category.icono || 'fa-folder');
    document.getElementById('modalTitle').textContent = 'Editar Categoría';
    document.getElementById('btnSubmitText').textContent = 'Actualizar Categoría';

    // Abrir modal
    const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
    modal.show();
}

// Función auxiliar para mostrar mensajes
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : type} position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = '9999';
    toast.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>

<style>
.sortable-ghost {
    opacity: 0.4;
    background-color: #f3f4f6;
}
</style>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
