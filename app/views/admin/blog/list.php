<?php
/**
 * Vista: Admin - Listado de Posts del Blog
 * Con filtros, estadísticas y acciones rápidas
 */

use App\Core\Config;
use App\Core\Helpers;

require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="admin-blog-list">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="?route=admin">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Blog</li>
        </ol>
    </nav>

    <?php
        $actionTitle = 'Gestión del Blog';
        $actionSubtitle = 'Administra publicaciones y categorías del blog';
        $actionButtons = [
            ['label' => 'Categorías', 'icon' => 'fas fa-folder', 'variant' => 'outline-secondary', 'href' => '?route=admin/blog/categorias'],
            ['label' => 'Nuevo Post', 'icon' => 'fas fa-plus', 'variant' => 'primary', 'href' => '?route=admin/blog/crear'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Header con estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Posts</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total'] ?></div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-blog fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card border-left-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Publicados</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['published'] ?></div>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card border-left-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Borradores</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['drafts'] ?></div>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-file-alt fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card stat-card border-left-info">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Destacados</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['featured'] ?></div>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-star fa-2x opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" method="GET" action="?route=admin/blog">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Buscar</label>
                        <input type="text"
                               name="buscar"
                               id="searchInput"
                               class="form-control"
                               placeholder="Título o contenido..."
                               value="<?= htmlspecialchars($filters['buscar'] ?? '') ?>">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select" id="filterEstado">
                            <option value="">Todos los estados</option>
                            <option value="published" <?= ($filters['estado'] ?? '') === 'published' ? 'selected' : '' ?>>Publicados</option>
                            <option value="draft" <?= ($filters['estado'] ?? '') === 'draft' ? 'selected' : '' ?>>Borradores</option>
                            <option value="scheduled" <?= ($filters['estado'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Programados</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Categoría</label>
                        <select name="categoria" class="form-select" id="filterCategoria">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= ($filters['categoria'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-filter me-2"></i>
                            Filtrar
                        </button>
                        <a href="?route=admin/blog" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de posts -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($posts)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No hay posts que mostrar</p>
                    <a href="?route=admin/blog/crear" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>
                        Crear primer post
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 40%">Título</th>
                                <th>Categoría</th>
                                <th>Autor</th>
                                <th class="text-center">SEO Score</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Destacado</th>
                                <th class="text-center">Vistas</th>
                                <th>Fecha</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($posts as $post): ?>
                                <tr data-post-id="<?= $post['id'] ?>">
                                    <td>
                                        <div class="d-flex align-items-start">
                                            <?php if ($post['imagen_destacada']): ?>
                                                <img src="<?= Config::getBaseUrl() ?>public<?= htmlspecialchars($post['imagen_destacada']) ?>"
                                                     alt="<?= htmlspecialchars($post['titulo']) ?>"
                                                     class="rounded me-2"
                                                     style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center"
                                                     style="width: 50px; height: 50px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <a href="?route=admin/blog/editar/<?= $post['id'] ?>"
                                                   class="fw-bold text-dark text-decoration-none"
                                                   data-bs-toggle="tooltip"
                                                   title="<?= htmlspecialchars($post['descripcion_corta'] ?? '') ?>">
                                                    <?= htmlspecialchars($post['titulo']) ?>
                                                </a>
                                                <div class="small text-muted">
                                                    <i class="fas fa-link me-1"></i>
                                                    /blog/<?= htmlspecialchars($post['slug']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <?php if ($post['categoria_nombre']): ?>
                                            <span class="badge" style="background-color: <?= htmlspecialchars($post['categoria_color'] ?? '#6c757d') ?>">
                                                <?= htmlspecialchars($post['categoria_nombre']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin categoría</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="small">
                                        <i class="fas fa-user me-1"></i>
                                        <?= htmlspecialchars($post['autor_nombre']) ?>
                                    </td>

                                    <td class="text-center">
                                        <?php
                                        $seoScore = $post['seo_score'];
                                        $seoClass = $seoScore >= 80 ? 'success' : ($seoScore >= 50 ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge bg-<?= $seoClass ?>"
                                              data-bs-toggle="tooltip"
                                              title="Puntuación SEO">
                                            <?= $seoScore ?>/100
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input toggle-status"
                                                   type="checkbox"
                                                   data-post-id="<?= $post['id'] ?>"
                                                   <?= $post['estado'] === 'published' ? 'checked' : '' ?>
                                                   title="<?= $post['estado'] === 'published' ? 'Publicado' : 'Borrador' ?>">
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <button class="btn btn-sm btn-link p-0 toggle-featured"
                                                data-post-id="<?= $post['id'] ?>"
                                                title="<?= $post['destacado'] ? 'Quitar de destacados' : 'Marcar como destacado' ?>">
                                            <i class="fas fa-star <?= $post['destacado'] ? 'text-warning' : 'text-muted' ?>"></i>
                                        </button>
                                    </td>

                                    <td class="text-center small">
                                        <i class="fas fa-eye me-1"></i>
                                        <?= number_format($post['vistas']) ?>
                                    </td>

                                    <td class="small">
                                        <div><?= date('d/m/Y', strtotime($post['fecha_publicacion'] ?? $post['created_at'])) ?></div>
                                        <div class="text-muted"><?= date('H:i', strtotime($post['fecha_publicacion'] ?? $post['created_at'])) ?></div>
                                    </td>

                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="?route=blog/<?= htmlspecialchars($post['slug']) ?>"
                                               class="btn btn-outline-secondary"
                                               target="_blank"
                                               data-bs-toggle="tooltip"
                                               title="Ver post">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?route=admin/blog/editar/<?= $post['id'] ?>"
                                               class="btn btn-outline-primary"
                                               data-bs-toggle="tooltip"
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-danger delete-post"
                                                    data-post-id="<?= $post['id'] ?>"
                                                    data-post-title="<?= htmlspecialchars($post['titulo']) ?>"
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

<style>
.stat-card {
    border-left-width: 4px;
}
.border-left-primary {
    border-left-color: #3b82f6 !important;
}
.border-left-success {
    border-left-color: #10b981 !important;
}
.border-left-warning {
    border-left-color: #f59e0b !important;
}
.border-left-info {
    border-left-color: #06b6d4 !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // CSRF Token
    const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';

    // Búsqueda con debounce
    let searchTimeout;
    const searchInput = document.getElementById('searchInput');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 500);
        });
    }

    // Auto-submit en cambio de filtros
    document.getElementById('filterEstado')?.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });

    document.getElementById('filterCategoria')?.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });

    // Toggle estado (published/draft)
    document.querySelectorAll('.toggle-status').forEach(toggle => {
        toggle.addEventListener('change', async function() {
            const postId = this.dataset.postId;
            const checkbox = this;

            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('id', postId);

                const response = await fetch('?route=admin/blog/toggle-status', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Mostrar mensaje de éxito
                    showToast('Estado actualizado correctamente', 'success');

                    // Actualizar título del checkbox
                    checkbox.title = data.newStatus === 'published' ? 'Publicado' : 'Borrador';
                } else {
                    // Revertir cambio
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

    // Toggle destacado
    document.querySelectorAll('.toggle-featured').forEach(button => {
        button.addEventListener('click', async function() {
            const postId = this.dataset.postId;
            const icon = this.querySelector('i');

            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);
                formData.append('id', postId);

                const response = await fetch('?route=admin/blog/toggle-featured', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    // Cambiar icono
                    if (data.featured) {
                        icon.classList.remove('text-muted');
                        icon.classList.add('text-warning');
                        this.title = 'Quitar de destacados';
                    } else {
                        icon.classList.remove('text-warning');
                        icon.classList.add('text-muted');
                        this.title = 'Marcar como destacado';
                    }

                    showToast('Destacado actualizado', 'success');
                } else {
                    showToast(data.message || 'Error al actualizar destacado', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error al actualizar destacado', 'error');
            }
        });
    });

    // Eliminar post
    document.querySelectorAll('.delete-post').forEach(button => {
        button.addEventListener('click', async function() {
            const postId = this.dataset.postId;
            const postTitle = this.dataset.postTitle;

            if (!confirm(`¿Estás seguro de eliminar el post "${postTitle}"?\n\nEsta acción no se puede deshacer.`)) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);

                const response = await fetch(`?route=admin/blog/eliminar/${postId}`, {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Post eliminado correctamente', 'success');

                    // Eliminar fila de la tabla
                    const row = this.closest('tr');
                    row.style.opacity = '0.5';
                    setTimeout(() => {
                        row.remove();

                        // Si no quedan más posts, recargar página
                        const tbody = document.querySelector('tbody');
                        if (tbody && tbody.children.length === 0) {
                            location.reload();
                        }
                    }, 300);
                } else {
                    showToast(data.message || 'Error al eliminar post', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Error al eliminar post', 'error');
            }
        });
    });

    // Función auxiliar para mostrar mensajes
    function showToast(message, type = 'info') {
        // Crear elemento de notificación
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
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
