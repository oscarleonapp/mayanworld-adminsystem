<?php
use App\Core\Config;
use App\Core\Helpers;
/**
 * Vista: Lista de Banners
 * Gestiona banners promocionales del sitio
 */
$pageTitle = 'Gestor de Banners';
include __DIR__ . '/../../partials/admin_header.php';
?>

<div class="container-fluid py-4">
    <?php
        $actionTitle = 'Gestor de Banners Promocionales';
        $actionSubtitle = 'Crea y gestiona banners para promociones, ofertas y anuncios';
        $actionButtons = [
            ['label' => 'Crear Banner', 'icon' => 'fas fa-plus', 'variant' => 'primary', 'href' => Config::getBaseUrl() . '?route=admin/banners/create'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Alertas -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <!-- Estadísticas rápidas -->
    <div class="row mb-4">
        <?php
        $totalBanners = count($banners);
        $activosCount = count(array_filter($banners, fn($b) => $b['activo']));
        $totalVistas = array_sum(array_column($banners, 'vistas'));
        $totalClicks = array_sum(array_column($banners, 'clicks'));
        $ctr = $totalVistas > 0 ? ($totalClicks / $totalVistas) * 100 : 0;
        ?>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-bullhorn fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0"><?= $totalBanners ?></h3>
                            <small class="text-muted">Total Banners</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0"><?= $activosCount ?></h3>
                            <small class="text-muted">Activos</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-eye fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0"><?= number_format($totalVistas) ?></h3>
                            <small class="text-muted">Vistas Totales</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-mouse-pointer fa-2x text-warning"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0"><?= number_format($ctr, 1) ?>%</h3>
                            <small class="text-muted">CTR Promedio</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de banners -->
    <div class="card shadow-sm">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Lista de Banners
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($banners)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-bullhorn fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No hay banners creados</h4>
                    <p class="text-muted">Crea tu primer banner para promocionar ofertas y tours</p>
                    <a href="<?= Config::getBaseUrl() ?>?route=admin/banners/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Crear Primer Banner
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Banner</th>
                                <th>Tipo</th>
                                <th>Posición</th>
                                <th>Vigencia</th>
                                <th class="text-center">Métricas</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($banners as $banner): ?>
                                <?php
                                $now = date('Y-m-d H:i:s');
                                $vigente = $banner['fecha_inicio'] <= $now && (!$banner['fecha_fin'] || $banner['fecha_fin'] >= $now);
                                $ctrBanner = $banner['vistas'] > 0 ? ($banner['clicks'] / $banner['vistas']) * 100 : 0;
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($banner['imagen']): ?>
                                                <img src="<?= htmlspecialchars($banner['imagen']) ?>"
                                                     alt="Banner"
                                                     class="rounded me-2"
                                                     style="width: 60px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center"
                                                     style="width: 60px; height: 40px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?= htmlspecialchars($banner['nombre']) ?></strong>
                                                <?php if ($banner['titulo']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars(Helpers::truncate($banner['titulo'], 40)) ?></small>
                                                <?php endif; ?>
                                                <?php if ($banner['cupones_count'] > 0): ?>
                                                    <br><span class="badge bg-info"><?= $banner['cupones_count'] ?> cupón(es)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $tipoLabels = [
                                            'hero' => '<span class="badge bg-primary">Hero</span>',
                                            'strip' => '<span class="badge bg-info">Strip</span>',
                                            'popup' => '<span class="badge bg-warning">Popup</span>',
                                            'sidebar' => '<span class="badge bg-secondary">Sidebar</span>'
                                        ];
                                        echo $tipoLabels[$banner['tipo']] ?? htmlspecialchars($banner['tipo']);
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?= ucfirst($banner['posicion']) ?></span>
                                    </td>
                                    <td>
                                        <small>
                                            <strong>Inicio:</strong> <?= date('d/m/Y', strtotime($banner['fecha_inicio'])) ?><br>
                                            <strong>Fin:</strong> <?= $banner['fecha_fin'] ? date('d/m/Y', strtotime($banner['fecha_fin'])) : 'Sin límite' ?>
                                        </small>
                                        <?php if (!$vigente && $banner['activo']): ?>
                                            <br><span class="badge bg-warning">No vigente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <small>
                                            <i class="fas fa-eye text-info me-1"></i> <?= number_format($banner['vistas']) ?><br>
                                            <i class="fas fa-mouse-pointer text-warning me-1"></i> <?= number_format($banner['clicks']) ?>
                                            (<?= number_format($ctrBanner, 1) ?>%)
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-inline-block">
                                            <input class="form-check-input toggle-banner-status"
                                                   type="checkbox"
                                                   data-banner-id="<?= $banner['id'] ?>"
                                                   <?= $banner['activo'] ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= Config::getBaseUrl() ?>?route=admin/banners/edit/<?= $banner['id'] ?>"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger delete-banner-btn"
                                                data-banner-id="<?= $banner['id'] ?>"
                                                data-banner-nombre="<?= htmlspecialchars($banner['nombre']) ?>"
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const BASE_URL = '<?= Config::getBaseUrl() ?>';

    // Toggle banner status
    document.querySelectorAll('.toggle-banner-status').forEach(toggle => {
        toggle.addEventListener('change', function() {
            const bannerId = this.dataset.bannerId;
            const isChecked = this.checked;

            fetch(BASE_URL + '?route=banner/api-toggle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id: bannerId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast('success', data.message);
                } else {
                    this.checked = !isChecked; // Revertir
                    showToast('error', data.message);
                }
            })
            .catch(err => {
                this.checked = !isChecked; // Revertir
                showToast('error', 'Error al cambiar estado');
            });
        });
    });

    // Delete banner
    document.querySelectorAll('.delete-banner-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const bannerId = this.dataset.bannerId;
            const bannerNombre = this.dataset.bannerNombre;

            if (!confirm(`¿Estás seguro de eliminar el banner "${bannerNombre}"?\n\nEsta acción no se puede deshacer.`)) {
                return;
            }

            window.location.href = BASE_URL + '?route=admin/banners/delete/' + bannerId;
        });
    });

    // Toast notification
    function showToast(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const toast = document.createElement('div');
        toast.className = `alert ${alertClass} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(toast);

        setTimeout(() => toast.remove(), 5000);
    }
});
</script>

<?php include __DIR__ . '/../../partials/admin_footer.php'; ?>
