<?php
use App\Core\Config;
$pageTitle = $title ?? 'Gestión de Menús';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="container-fluid py-4">
    <?php
        $actionTitle = $pageTitle;
        $actionSubtitle = 'Gestiona los menús de navegación del sitio web';
        $actionButtons = [];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <div class="row">
        <?php foreach ($menus as $menu): ?>
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i><?= htmlspecialchars($menu['display_name']) ?>
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3"><?= htmlspecialchars($menu['description'] ?? '') ?></p>

                    <div class="mb-3">
                        <span class="badge bg-secondary me-2">
                            <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($menu['location']) ?>
                        </span>
                        <span class="badge bg-info">
                            <i class="fas fa-code"></i> <?= htmlspecialchars($menu['name']) ?>
                        </span>
                    </div>

                    <?php $stats = $menu['stats'] ?? []; ?>
                    <div class="row text-center mb-3">
                        <div class="col-4">
                            <div class="text-primary fw-bold h4 mb-0"><?= $stats['total_items'] ?? 0 ?></div>
                            <small class="text-muted">Total</small>
                        </div>
                        <div class="col-4">
                            <div class="text-success fw-bold h4 mb-0"><?= $stats['visible_items'] ?? 0 ?></div>
                            <small class="text-muted">Visibles</small>
                        </div>
                        <div class="col-4">
                            <div class="text-warning fw-bold h4 mb-0"><?= $stats['submenu_items'] ?? 0 ?></div>
                            <small class="text-muted">Submenús</small>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <a href="<?= Config::getBaseUrl() ?>?route=admin/navigation/edit/<?= $menu['id'] ?>"
                       class="btn btn-primary btn-sm w-100">
                        <i class="fas fa-edit me-2"></i>Editar Menú
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Información adicional -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>¿Cómo funciona?</h5>
                <ul class="mb-0">
                    <li><strong>Menú Principal:</strong> Se muestra en el header del sitio (barra de navegación superior)</li>
                    <li><strong>Menú Footer:</strong> Enlaces en el pie de página del sitio</li>
                    <li><strong>Menú Usuario:</strong> Opciones para usuarios autenticados (Mi Cuenta, etc.)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
