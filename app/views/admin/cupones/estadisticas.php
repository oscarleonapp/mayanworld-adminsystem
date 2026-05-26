<?php
use App\Core\Config;
/**
 * Vista: Estadísticas de Cupones
 * Dashboard con métricas de uso de cupones
 */
$pageTitle = 'Estadísticas de Cupones';
include __DIR__ . '/../../partials/admin_header.php';

// Calcular totales
$totalUsos = array_sum(array_column($topCupones, 'total_usos'));
$totalDescuentos = array_sum(array_column($topCupones, 'total_descuentos'));
?>

<div class="container-fluid py-4">
    <?php
        $actionTitle = 'Estadísticas de Cupones';
        $actionSubtitle = 'Análisis de rendimiento y uso de cupones de descuento';
        $actionButtons = [
            ['label' => 'Volver a Cupones', 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary', 'href' => Config::getBaseUrl() . '?route=admin/cupones'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Métricas generales -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                <i class="fas fa-ticket-alt fa-2x text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h2 class="mb-0"><?= number_format($totalUsos) ?></h2>
                            <small class="text-muted">Total Usos</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="fas fa-dollar-sign fa-2x text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h2 class="mb-0">$<?= number_format($totalDescuentos, 0) ?></h2>
                            <small class="text-muted">Total Descuentos</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                <i class="fas fa-calculator fa-2x text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h2 class="mb-0">$<?= $totalUsos > 0 ? number_format($totalDescuentos / $totalUsos, 2) : '0' ?></h2>
                            <small class="text-muted">Descuento Promedio</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                <i class="fas fa-trophy fa-2x text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h2 class="mb-0"><?= count($topCupones) ?></h2>
                            <small class="text-muted">Cupones Activos</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Top 10 cupones más usados -->
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-medal text-warning me-2"></i>
                        Top 10 Cupones Más Usados
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($topCupones)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay datos disponibles</h5>
                            <p class="text-muted">Crea cupones y espera a que sean usados</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="50">#</th>
                                        <th>Cupón</th>
                                        <th class="text-center">Usos</th>
                                        <th class="text-end">Total Descuentos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $position = 1;
                                    $maxUsos = max(array_column($topCupones, 'total_usos'));
                                    ?>
                                    <?php foreach ($topCupones as $cupon): ?>
                                        <?php if ($cupon['total_usos'] > 0): ?>
                                            <tr>
                                                <td class="text-center">
                                                    <?php if ($position === 1): ?>
                                                        <i class="fas fa-trophy text-warning fa-lg"></i>
                                                    <?php elseif ($position === 2): ?>
                                                        <i class="fas fa-trophy text-secondary fa-lg"></i>
                                                    <?php elseif ($position === 3): ?>
                                                        <i class="fas fa-trophy text-warning fa-lg" style="opacity: 0.6;"></i>
                                                    <?php else: ?>
                                                        <span class="text-muted"><?= $position ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong class="font-monospace"><?= htmlspecialchars($cupon['codigo']) ?></strong>
                                                    <br><small class="text-muted"><?= htmlspecialchars($cupon['nombre']) ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary"><?= number_format($cupon['total_usos']) ?></span>
                                                    <div class="progress mt-1" style="height: 4px;">
                                                        <?php $percentage = ($cupon['total_usos'] / $maxUsos) * 100; ?>
                                                        <div class="progress-bar" style="width: <?= $percentage ?>%"></div>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <strong class="text-success">$<?= number_format($cupon['total_descuentos'] ?? 0, 2) ?></strong>
                                                </td>
                                            </tr>
                                            <?php $position++; ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Usos por mes (últimos 12 meses) -->
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt text-info me-2"></i>
                        Uso por Mes (Últimos 12 meses)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($usosPorMes)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay datos históricos</h5>
                        </div>
                    <?php else: ?>
                        <?php
                        $mesesNombres = [
                            '01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr',
                            '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
                            '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'
                        ];
                        $maxUsosMes = max(array_column($usosPorMes, 'total_usos'));
                        ?>
                        <div class="chart-container">
                            <?php foreach ($usosPorMes as $dato): ?>
                                <?php
                                list($year, $month) = explode('-', $dato['mes']);
                                $mesNombre = $mesesNombres[$month] . ' ' . $year;
                                $percentage = $maxUsosMes > 0 ? ($dato['total_usos'] / $maxUsosMes) * 100 : 0;
                                ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small class="text-muted"><?= $mesNombre ?></small>
                                        <small class="fw-bold">
                                            <?= number_format($dato['total_usos']) ?> usos
                                            <span class="text-success">($<?= number_format($dato['total_descuentos'], 0) ?>)</span>
                                        </small>
                                    </div>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-gradient"
                                             style="width: <?= $percentage ?>%; background: linear-gradient(90deg, #0d6efd, #6610f2);">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Insights y recomendaciones -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        Insights y Recomendaciones
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="alert alert-info mb-0">
                                <h6 class="alert-heading">
                                    <i class="fas fa-chart-line me-2"></i>
                                    Rendimiento General
                                </h6>
                                <p class="mb-0 small">
                                    <?php if ($totalUsos > 0): ?>
                                        Has otorgado <strong>$<?= number_format($totalDescuentos, 0) ?></strong> en descuentos
                                        con un promedio de <strong>$<?= number_format($totalDescuentos / $totalUsos, 2) ?></strong> por uso.
                                    <?php else: ?>
                                        Aún no hay cupones usados. Promociona tus cupones para incrementar conversión.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="alert alert-success mb-0">
                                <h6 class="alert-heading">
                                    <i class="fas fa-trophy me-2"></i>
                                    Mejor Cupón
                                </h6>
                                <p class="mb-0 small">
                                    <?php if (!empty($topCupones) && $topCupones[0]['total_usos'] > 0): ?>
                                        El cupón <strong class="font-monospace"><?= htmlspecialchars($topCupones[0]['codigo']) ?></strong>
                                        tiene <strong><?= number_format($topCupones[0]['total_usos']) ?> usos</strong>.
                                        Considera crear cupones similares.
                                    <?php else: ?>
                                        Aún no hay suficientes datos para determinar el mejor cupón.
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3">
                            <div class="alert alert-warning mb-0">
                                <h6 class="alert-heading">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Recomendación
                                </h6>
                                <p class="mb-0 small">
                                    <?php if ($totalUsos < 10): ?>
                                        Pocos usos registrados. Promociona tus cupones en banners y redes sociales.
                                    <?php elseif ($totalDescuentos / $totalUsos > 50): ?>
                                        El descuento promedio es alto ($<?= number_format($totalDescuentos / $totalUsos, 0) ?>).
                                        Considera cupones con límites máximos.
                                    <?php else: ?>
                                        Excelente balance entre uso y descuentos. ¡Sigue así!
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chart-container {
    max-height: 450px;
    overflow-y: auto;
}

.progress-bar.bg-gradient {
    transition: width 0.6s ease;
}
</style>

<?php include __DIR__ . '/../../partials/admin_footer.php'; ?>
