<?php
use App\Core\Config;
use App\Core\Helpers;
/**
 * Vista: Lista de Cupones
 * Gestiona cupones de descuento
 */
$pageTitle = 'Gestor de Cupones';
include __DIR__ . '/../../partials/admin_header.php';
?>

<div class="container-fluid py-4">
    <?php
        $actionTitle = 'Gestor de Cupones de Descuento';
        $actionSubtitle = 'Crea y gestiona cupones promocionales con reglas de validación';
        $actionButtons = [
            ['label' => 'Estadísticas', 'icon' => 'fas fa-chart-bar', 'variant' => 'outline-info', 'href' => Config::getBaseUrl() . '?route=admin/cupones/estadisticas'],
            ['label' => 'Crear Cupón', 'icon' => 'fas fa-plus', 'variant' => 'primary', 'href' => Config::getBaseUrl() . '?route=admin/cupones/create'],
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
        $totalCupones = count($cupones);
        $activosCount = count(array_filter($cupones, fn($c) => $c['activo']));
        $now = date('Y-m-d H:i:s');
        $vigentesCount = count(array_filter($cupones, function($c) use ($now) {
            return $c['activo'] && $c['fecha_inicio'] <= $now && (!$c['fecha_fin'] || $c['fecha_fin'] >= $now);
        }));
        $totalUsos = array_sum(array_column($cupones, 'usos_actuales'));
        ?>
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-ticket-alt fa-2x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0"><?= $totalCupones ?></h3>
                            <small class="text-muted">Total Cupones</small>
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
                            <h3 class="mb-0"><?= $vigentesCount ?></h3>
                            <small class="text-muted">Vigentes Ahora</small>
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
                            <i class="fas fa-users fa-2x text-info"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0"><?= number_format($totalUsos) ?></h3>
                            <small class="text-muted">Usos Totales</small>
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
                            <i class="fas fa-power-off fa-2x text-secondary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h3 class="mb-0"><?= $totalCupones - $activosCount ?></h3>
                            <small class="text-muted">Desactivados</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de cupones -->
    <div class="card shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                Lista de Cupones
            </h5>
            <div>
                <button class="btn btn-sm btn-outline-secondary" id="filterActive">
                    <i class="fas fa-filter me-1"></i> Solo Activos
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($cupones)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-ticket-alt fa-4x text-muted mb-3"></i>
                    <h4 class="text-muted">No hay cupones creados</h4>
                    <p class="text-muted">Crea tu primer cupón para ofrecer descuentos a tus clientes</p>
                    <a href="<?= Config::getBaseUrl() ?>?route=admin/cupones/create" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> Crear Primer Cupón
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Código</th>
                                <th>Tipo</th>
                                <th>Descuento</th>
                                <th>Vigencia</th>
                                <th class="text-center">Usos</th>
                                <th>Restricciones</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cupones as $cupon): ?>
                                <?php
                                $vigente = $cupon['fecha_inicio'] <= $now && (!$cupon['fecha_fin'] || $cupon['fecha_fin'] >= $now);
                                $agotado = $cupon['usos_maximos'] && $cupon['usos_actuales'] >= $cupon['usos_maximos'];
                                $porcentajeUso = $cupon['usos_maximos'] ? ($cupon['usos_actuales'] / $cupon['usos_maximos']) * 100 : 0;
                                ?>
                                <tr class="cupon-row" data-activo="<?= $cupon['activo'] ?>">
                                    <td>
                                        <div>
                                            <strong class="font-monospace"><?= htmlspecialchars($cupon['codigo']) ?></strong>
                                            <br><small class="text-muted"><?= htmlspecialchars($cupon['nombre']) ?></small>
                                            <?php if ($cupon['banner_nombre']): ?>
                                                <br><span class="badge bg-info badge-sm">
                                                    <i class="fas fa-bullhorn me-1"></i><?= htmlspecialchars(Helpers::truncate($cupon['banner_nombre'], 20)) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($cupon['tipo_descuento'] === 'porcentaje'): ?>
                                            <span class="badge bg-primary">% Porcentaje</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">$ Fijo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($cupon['tipo_descuento'] === 'porcentaje'): ?>
                                            <strong class="text-primary"><?= number_format($cupon['valor_descuento'], 0) ?>%</strong>
                                        <?php else: ?>
                                            <strong class="text-success">$<?= number_format($cupon['valor_descuento'], 2) ?></strong>
                                        <?php endif; ?>
                                        <?php if ($cupon['monto_minimo']): ?>
                                            <br><small class="text-muted">Min: $<?= number_format($cupon['monto_minimo'], 0) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <i class="fas fa-calendar-alt me-1"></i><?= date('d/m/Y', strtotime($cupon['fecha_inicio'])) ?>
                                            <br>
                                            <i class="fas fa-calendar-check me-1"></i>
                                            <?= $cupon['fecha_fin'] ? date('d/m/Y', strtotime($cupon['fecha_fin'])) : 'Sin límite' ?>
                                        </small>
                                        <?php if (!$vigente && $cupon['activo']): ?>
                                            <br><span class="badge bg-warning badge-sm">No vigente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div>
                                            <strong><?= number_format($cupon['usos_actuales']) ?></strong>
                                            <?php if ($cupon['usos_maximos']): ?>
                                                / <?= number_format($cupon['usos_maximos']) ?>
                                            <?php else: ?>
                                                / ∞
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($cupon['usos_maximos']): ?>
                                            <div class="progress mt-1" style="height: 5px;">
                                                <div class="progress-bar <?= $agotado ? 'bg-danger' : 'bg-success' ?>"
                                                     style="width: <?= min($porcentajeUso, 100) ?>%"></div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($agotado): ?>
                                            <small class="text-danger">Agotado</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?php if ($cupon['solo_primera_compra']): ?>
                                                <span class="badge bg-warning badge-sm">1ª Compra</span>
                                            <?php endif; ?>
                                            <?php if ($cupon['usos_por_usuario'] > 1): ?>
                                                <span class="badge bg-info badge-sm"><?= $cupon['usos_por_usuario'] ?>x usuario</span>
                                            <?php endif; ?>
                                            <?php if ($cupon['tours_aplicables']): ?>
                                                <br><span class="badge bg-secondary badge-sm">Tours específicos</span>
                                            <?php endif; ?>
                                            <?php if ($cupon['categorias_aplicables']): ?>
                                                <br><span class="badge bg-secondary badge-sm">Categorías específicas</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($cupon['activo'] && $vigente && !$agotado): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php elseif ($cupon['activo'] && !$vigente): ?>
                                            <span class="badge bg-warning">No vigente</span>
                                        <?php elseif ($agotado): ?>
                                            <span class="badge bg-danger">Agotado</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Desactivado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= Config::getBaseUrl() ?>?route=admin/cupones/edit/<?= $cupon['id'] ?>"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger delete-cupon-btn"
                                                data-cupon-id="<?= $cupon['id'] ?>"
                                                data-cupon-codigo="<?= htmlspecialchars($cupon['codigo']) ?>"
                                                data-tiene-usos="<?= $cupon['usos_actuales'] > 0 ? '1' : '0' ?>"
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

    // Filter activos
    let showingOnlyActive = false;
    document.getElementById('filterActive')?.addEventListener('click', function() {
        showingOnlyActive = !showingOnlyActive;
        const rows = document.querySelectorAll('.cupon-row');

        rows.forEach(row => {
            if (showingOnlyActive) {
                row.style.display = row.dataset.activo === '1' ? '' : 'none';
            } else {
                row.style.display = '';
            }
        });

        this.innerHTML = showingOnlyActive
            ? '<i class="fas fa-times me-1"></i> Mostrar Todos'
            : '<i class="fas fa-filter me-1"></i> Solo Activos';
    });

    // Delete cupon
    document.querySelectorAll('.delete-cupon-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const cuponId = this.dataset.cuponId;
            const cuponCodigo = this.dataset.cuponCodigo;
            const tieneUsos = this.dataset.tieneUsos === '1';

            let mensaje = `¿Estás seguro de eliminar el cupón "${cuponCodigo}"?`;
            if (tieneUsos) {
                mensaje += '\n\nEste cupón tiene usos registrados y será DESACTIVADO en lugar de eliminarse.';
            } else {
                mensaje += '\n\nEsta acción no se puede deshacer.';
            }

            if (!confirm(mensaje)) {
                return;
            }

            window.location.href = BASE_URL + '?route=admin/cupones/delete/' + cuponId;
        });
    });
});
</script>

<style>
.badge-sm {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
}
</style>

<?php include __DIR__ . '/../../partials/admin_footer.php'; ?>
