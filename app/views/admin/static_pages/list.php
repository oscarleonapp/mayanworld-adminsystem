<?php
/**
 * Vista: Gestión de Páginas Estáticas
 * Terms, Privacy, About, Contact, etc.
 * Editor: Quill.js
 */

$pageTitle = 'Páginas Estáticas';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="admin-static-pages">
    <?php
        $actionTitle = 'Páginas Estáticas';
        $actionSubtitle = 'Gestiona las páginas legales y de contenido del sitio';
        $actionButtons = [
            ['label' => 'Nueva Página', 'icon' => 'fas fa-plus', 'variant' => 'primary', 'id' => 'btnNewPage'],
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
                                <i class="fas fa-file-alt text-primary fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total Páginas</div>
                            <div class="h4 mb-0"><?= count($pages ?? []) ?></div>
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
                            <div class="text-muted small">Publicadas</div>
                            <div class="h4 mb-0">
                                <?= count(array_filter($pages ?? [], fn($p) => $p['status'] === 'published')) ?>
                            </div>
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
                                <i class="fas fa-bars text-info fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">En Menú</div>
                            <div class="h4 mb-0">
                                <?= count(array_filter($pages ?? [], fn($p) => $p['show_in_menu'])) ?>
                            </div>
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
                                <i class="fas fa-clock text-warning fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Última Edición</div>
                            <div class="small mb-0">
                                <?php
                                if (!empty($pages)) {
                                    $ultima = max(array_map(fn($p) => strtotime($p['updated_at']), $pages));
                                    echo date('d/m/Y', $ultima);
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de páginas -->
    <div class="card">
        <div class="card-header bg-light">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0">Todas las Páginas</h5>
                </div>
                <div class="col-auto">
                    <input type="text" class="form-control form-control-sm" id="searchPages" placeholder="Buscar...">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Título</th>
                            <th>Slug (URL)</th>
                            <th>Meta Título</th>
                            <th style="width: 100px;">Menú</th>
                            <th style="width: 100px;">Estado</th>
                            <th style="width: 120px;">Actualizada</th>
                            <th style="width: 200px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pages)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-file-alt fa-3x mb-3 opacity-25"></i>
                                    <p>No hay páginas. Crea la primera para comenzar.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pages as $pag): ?>
                                <tr class="page-row" data-id="<?= $pag['id'] ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($pag['title']) ?></strong>
                                        <?php if (!empty($pag['meta_keywords'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-tags fa-xs me-1"></i>
                                                <?= htmlspecialchars(substr($pag['meta_keywords'], 0, 50)) ?>...
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($pag['slug']) ?></code>
                                        <a href="?route=page/<?= htmlspecialchars($pag['slug']) ?>"
                                           target="_blank"
                                           class="btn btn-sm btn-link p-0 ms-2"
                                           title="Ver página">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;">
                                            <?= htmlspecialchars($pag['meta_title'] ?? $pag['title']) ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($pag['show_in_menu']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i> Sí
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input toggle-active"
                                                   type="checkbox"
                                                   data-id="<?= $pag['id'] ?>"
                                                   <?= $pag['status'] === 'published' ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($pag['updated_at'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?route=admin/pages/edit/<?= $pag['id'] ?>"
                                               class="btn btn-outline-primary"
                                               title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-outline-info btn-duplicate"
                                                    data-id="<?= $pag['id'] ?>"
                                                    title="Duplicar">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary btn-preview"
                                                    data-slug="<?= htmlspecialchars($pag['slug']) ?>"
                                                    title="Vista previa">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-delete"
                                                    data-id="<?= $pag['id'] ?>"
                                                    data-titulo="<?= htmlspecialchars($pag['title']) ?>"
                                                    title="Eliminar">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Nueva página
    document.getElementById('btnNewPage').addEventListener('click', function() {
        window.location.href = '?route=admin/pages/create';
    });

    // Duplicar página
    document.querySelectorAll('.btn-duplicate').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;

            if (!confirm('¿Duplicar esta página?')) return;

            try {
                const response = await fetch(`?route=admin/pages/duplicate/${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>' })
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Página duplicada correctamente', 'success');
                    location.reload();
                } else {
                    showToast(result.message || 'Error al duplicar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        });
    });

    // Vista previa
    document.querySelectorAll('.btn-preview').forEach(btn => {
        btn.addEventListener('click', function() {
            const slug = this.dataset.slug;
            window.open(`?route=page/${slug}`, '_blank');
        });
    });

    // Eliminar página
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            const titulo = this.dataset.titulo;

            if (!confirm(`¿Eliminar la página "${titulo}"?`)) return;

            try {
                const response = await fetch(`?route=admin/pages/delete/${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>' })
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Página eliminada', 'success');
                    this.closest('tr').remove();
                } else {
                    showToast(result.message || 'Error al eliminar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        });
    });

    // Toggle status (published/draft)
    document.querySelectorAll('.toggle-active').forEach(toggle => {
        toggle.addEventListener('change', async function() {
            const id = this.dataset.id;
            const isPublished = this.checked;

            try {
                const response = await fetch('?route=admin/pages/toggle', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: id,
                        csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showToast(isPublished ? 'Página publicada' : 'Página despublicada', 'success');
                } else {
                    this.checked = !this.checked;
                    showToast('Error al cambiar estado', 'error');
                }
            } catch (error) {
                this.checked = !this.checked;
                showToast('Error de conexión', 'error');
            }
        });
    });

    // Buscar páginas
    document.getElementById('searchPages').addEventListener('input', function() {
        const search = this.value.toLowerCase();

        document.querySelectorAll('.page-row').forEach(row => {
            const titulo = row.querySelector('strong').textContent.toLowerCase();
            const slug = row.querySelector('code').textContent.toLowerCase();

            row.style.display = titulo.includes(search) || slug.includes(search) ? '' : 'none';
        });
    });

    function showToast(message, type) {
        alert(message); // Implementar toast
    }
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
