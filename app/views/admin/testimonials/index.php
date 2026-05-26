<?php 
use App\Core\Config;
use App\Core\Helpers;
require_once __DIR__ . '/../../layouts/admin_header.php'; ?>

<div class="container-fluid p-4">
    <?php
        $actionTitle = 'Gestión de Reseñas';
        $actionSubtitle = 'Administra las reseñas y testimonios de clientes';
        $actionButtons = [
            ['label' => 'Nueva Reseña', 'icon' => 'fas fa-plus', 'variant' => 'primary', 'href' => Config::getBaseUrl() . '?route=admin/testimonials/create'],
            ['label' => 'Importar de Google', 'icon' => 'fab fa-google', 'variant' => 'outline-primary', 'href' => Config::getBaseUrl() . '?route=admin/testimonials/import-google'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <?php if ($successMessage = Helpers::getFlashMessage('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage = Helpers::getFlashMessage('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Total Reseñas</p>
                            <h3 class="mb-0"><?= $stats['general']['total'] ?></h3>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-comment fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Activas</p>
                            <h3 class="mb-0"><?= $stats['general']['activos'] ?></h3>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Destacadas</p>
                            <h3 class="mb-0"><?= $stats['general']['destacados'] ?></h3>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-star fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">Calificación Prom.</p>
                            <h3 class="mb-0"><?= number_format($stats['general']['promedio_calificacion'], 1) ?> ⭐</h3>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de reseñas -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Todas las Reseñas</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($testimonials)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">Orden</th>
                                <th>Nombre</th>
                                <th width="100">Calificación</th>
                                <th>Comentario</th>
                                <th width="100">Fuente</th>
                                <th width="100">Fecha</th>
                                <th width="80">Estado</th>
                                <th width="80">Destacado</th>
                                <th width="150">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testimonials as $testimonial): ?>
                                <tr>
                                    <td><?= $testimonial['orden'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($testimonial['nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    </td>
                                    <td>
                                        <?php for ($i = 0; $i < $testimonial['calificacion']; $i++): ?>
                                            <i class="fas fa-star text-warning"></i>
                                        <?php endfor; ?>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars(Helpers::truncate($testimonial['comentario'], 80), ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $badges = [
                                            'google' => 'bg-danger',
                                            'tripadvisor' => 'bg-success',
                                            'facebook' => 'bg-primary',
                                            'manual' => 'bg-secondary'
                                        ];
                                        $badgeClass = $badges[$testimonial['fuente']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= ucfirst($testimonial['fuente']) ?></span>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($testimonial['fecha_resena'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm <?= $testimonial['activo'] ? 'btn-success' : 'btn-secondary' ?>"
                                                onclick="toggleActive(<?= $testimonial['id'] ?>)">
                                            <i class="fas fa-<?= $testimonial['activo'] ? 'check' : 'times' ?>"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm <?= $testimonial['destacado'] ? 'btn-warning' : 'btn-outline-warning' ?>"
                                                onclick="toggleFeatured(<?= $testimonial['id'] ?>)">
                                            <i class="fas fa-star"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <a href="<?= Config::getBaseUrl() ?>?route=admin/testimonials/edit/<?= $testimonial['id'] ?>"
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="deleteTestimonial(<?= $testimonial['id'] ?>)"
                                                class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-comment-slash fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">No hay reseñas registradas</h5>
                    <p class="text-muted">Crea tu primera reseña o importa desde Google</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleActive(id) {
    if (confirm('¿Cambiar el estado de esta reseña?')) {
        fetch('<?= Config::getBaseUrl() ?>?route=admin/testimonials/toggle-active/' + id)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error al cambiar el estado');
                }
            });
    }
}

function toggleFeatured(id) {
    fetch('<?= Config::getBaseUrl() ?>?route=admin/testimonials/toggle-featured/' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al cambiar destacado');
            }
        });
}

function deleteTestimonial(id) {
    if (confirm('¿Estás seguro de eliminar esta reseña? Esta acción no se puede deshacer.')) {
        window.location.href = '<?= Config::getBaseUrl() ?>?route=admin/testimonials/delete/' + id;
    }
}
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
