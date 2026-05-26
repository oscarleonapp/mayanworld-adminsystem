<?php
$pageTitle = 'Puntos de Encuentro';
use App\Core\Config;
use App\Core\Helpers;
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Puntos de Encuentro</h1>
            <p class="text-muted small">Gestiona el catálogo global de lugares de recogida</p>
        </div>
        <a href="<?= Config::getBaseUrl() ?>?route=admin/meeting-points/create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Nuevo Punto
        </a>
    </div>

    <!-- Messages -->
    <?php Helpers::displayFlashMessage(); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Listado de Puntos de Encuentro</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Dirección</th>
                            <th>Mapa</th>
                            <th>Estado</th>
                            <th style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($meetingPoints)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    No hay puntos de encuentro registrados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($meetingPoints as $mp): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($mp['title']) ?></strong>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 300px;">
                                            <?= htmlspecialchars($mp['address']) ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($mp['map_link'])): ?>
                                            <a href="<?= htmlspecialchars($mp['map_link']) ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Ver Mapa">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($mp['is_active']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <a href="<?= Config::getBaseUrl() ?>?route=admin/meeting-points/edit/<?= $mp['id'] ?>" class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="<?= Config::getBaseUrl() ?>?route=admin/meeting-points/delete/<?= $mp['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('¿Estás seguro de eliminar este punto de encuentro?');"
                                               title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </a>
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

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
