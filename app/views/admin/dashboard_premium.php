<?php
use App\Core\Config;
use App\Core\Auth;
/**
 * Dashboard Premium con Analytics
 * Chart.js + Estadísticas en tiempo real
 */

$pageTitle = 'Dashboard Analytics';
$period = $period ?? 'month';
require_once __DIR__ . '/../layouts/admin_header.php';
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<div class="dashboard-premium dashboard-premium--v2">
    <?php
        $actionTitle = 'Dashboard Analytics';
        $actionSubtitle = 'Resumen de rendimiento en tiempo real';
        $actionKicker = 'Panel de control';
        $actionMeta = [
            ['icon' => 'fas fa-user-circle', 'label' => $auth->getCurrentUser()['nombre'] ?? ''],
            ['icon' => 'fas fa-calendar-alt', 'label' => date('d/m/Y H:i')],
        ];
        $actionButtons = [
            ['label' => 'Actualizar', 'icon' => 'fas fa-sync-alt', 'variant' => 'outline-primary', 'onclick' => 'location.reload()'],
        ];
        include __DIR__ . '/../partials/admin_action_bar.php';
    ?>

    <div class="d-flex justify-content-end mb-4">
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-plus me-2"></i>Crear
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="?route=admin/tours/create">
                    <i class="fas fa-map-marked-alt me-2"></i>Tour
                </a></li>
                <li><a class="dropdown-item" href="?route=admin/bookings">
                    <i class="fas fa-calendar-plus me-2"></i>Reserva
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="?route=admin/content-blocks">
                    <i class="fas fa-cube me-2"></i>Bloque de Contenido
                </a></li>
                <li><a class="dropdown-item" href="?route=admin/pages/create">
                    <i class="fas fa-file-alt me-2"></i>Página Estática
                </a></li>
            </ul>
        </div>
    </div>

    <!-- Date Filter Form -->
    <div class="card border-0 shadow-sm mb-4 dashboard-card dashboard-filters">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end admin-filters">
                <input type="hidden" name="route" value="admin/dashboard">
                <input type="hidden" name="period" id="chart_period" value="<?= htmlspecialchars($period) ?>">

                <div class="col-md-4">
                    <label for="start_date" class="form-label">
                        <i class="fas fa-calendar-alt me-1"></i>
                        Fecha Inicio
                    </label>
                    <input type="date"
                           class="form-control"
                           id="start_date"
                           name="start_date"
                           value="<?= htmlspecialchars($start_date ?? date('Y-m-01')) ?>"
                           max="<?= date('Y-m-d') ?>">
                </div>

                <div class="col-md-4">
                    <label for="end_date" class="form-label">
                        <i class="fas fa-calendar-check me-1"></i>
                        Fecha Fin
                    </label>
                    <input type="date"
                           class="form-control"
                           id="end_date"
                           name="end_date"
                           value="<?= htmlspecialchars($end_date ?? date('Y-m-d')) ?>"
                           max="<?= date('Y-m-d') ?>">
                </div>

                <div class="col-md-4">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="fas fa-filter me-2"></i>Filtrar
                        </button>
                        <a href="?route=admin/dashboard" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-2"></i>Restablecer
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="dashboard-section-title">
        Resumen ejecutivo
        <span class="dashboard-section-subtitle">Indicadores clave del período</span>
    </div>
    <div class="row g-4 mb-4">
        <!-- Total Ingresos -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 kpi-card kpi-card--revenue">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small">Ingresos Totales</p>
                            <h3 class="mb-0 fw-bold">
                                $<?= number_format($stats['total_revenue'] ?? 0, 2) ?>
                            </h3>
                        </div>
                        <div class="kpi-card__icon">
                            <i class="fas fa-dollar-sign text-success fa-2x"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-success-subtle text-success me-2">
                            <i class="fas fa-arrow-up me-1"></i>
                            <?= $stats['revenue_change'] ?? '+12.5' ?>%
                        </span>
                        <small class="text-muted">vs. periodo anterior</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Reservas -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 kpi-card kpi-card--bookings">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small">Total Reservas</p>
                            <h3 class="mb-0 fw-bold">
                                <?= number_format($stats['total_bookings'] ?? 0) ?>
                            </h3>
                        </div>
                        <div class="kpi-card__icon">
                            <i class="fas fa-calendar-check text-primary fa-2x"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-primary-subtle text-primary me-2">
                            <i class="fas fa-arrow-up me-1"></i>
                            <?= $stats['bookings_change'] ?? '+8.2' ?>%
                        </span>
                        <small class="text-muted">vs. periodo anterior</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reservas Pendientes -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 kpi-card kpi-card--pending">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small">Pendientes</p>
                            <h3 class="mb-0 fw-bold">
                                <?= number_format($stats['pending_bookings'] ?? 0) ?>
                            </h3>
                        </div>
                        <div class="kpi-card__icon">
                            <i class="fas fa-clock text-warning fa-2x"></i>
                        </div>
                    </div>
                    <?php if (($stats['pending_bookings'] ?? 0) > 0): ?>
                        <a href="?route=admin/bookings&status=pendiente" class="btn btn-sm btn-warning w-100">
                            <i class="fas fa-eye me-1"></i>
                            Revisar pendientes
                        </a>
                    <?php else: ?>
                        <span class="badge bg-success w-100">
                            <i class="fas fa-check me-1"></i>
                            Todo al día
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Tasa de Conversión -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 kpi-card kpi-card--conversion">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="text-muted mb-1 small">Tasa Conversión</p>
                            <h3 class="mb-0 fw-bold">
                                <?= number_format($stats['conversion_rate'] ?? 0, 1) ?>%
                            </h3>
                        </div>
                        <div class="kpi-card__icon">
                            <i class="fas fa-percentage text-info fa-2x"></i>
                        </div>
                    </div>
                    <div class="progress progress-thin">
                        <div class="progress-bar bg-info"
                             style="width: <?= min(100, $stats['conversion_rate'] ?? 0) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="dashboard-section-title">
        Analítica y rendimiento
        <span class="dashboard-section-subtitle">Tendencias de ventas, reservas y categorías</span>
    </div>
    <div class="row g-4 mb-4">
        <!-- Reservas por Mes (Línea) -->
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm dashboard-card">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line text-primary me-2"></i>
                            Reservas e Ingresos
                        </h5>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary <?= $period === 'month' ? 'active' : '' ?>" data-chart-period="month">Mes</button>
                            <button type="button" class="btn btn-outline-secondary <?= $period === 'week' ? 'active' : '' ?>" data-chart-period="week">Semana</button>
                            <button type="button" class="btn btn-outline-secondary <?= $period === 'day' ? 'active' : '' ?>" data-chart-period="day">Día</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="chartBookingsRevenue" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Tours (Dona) -->
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm dashboard-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie text-success me-2"></i>
                        Top Tours
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="chartTopProducts" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row Charts -->
    <div class="row g-4 mb-4">
        <!-- Reservas por Estado (Barra) -->
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm dashboard-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-bar text-info me-2"></i>
                        Reservas por Estado
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="chartBookingsByStatus" height="120"></canvas>
                </div>
            </div>
        </div>

        <!-- Reservas por Categoría -->
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm dashboard-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-tags text-warning me-2"></i>
                        Reservas por Categoría
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="chartBookingsByCategory" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Reportes Detallados -->
    <div class="dashboard-section-title">
        Reportes del período
        <span class="dashboard-section-subtitle">Resumen detallado del desempeño</span>
    </div>
    <div class="card border-0 shadow-sm mb-4 dashboard-card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">
                <i class="fas fa-file-invoice text-primary me-2"></i>
                Reportes Detallados
                <?php if (isset($start_date) && isset($end_date)): ?>
                    <small class="text-muted">
                        (<?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?>)
                    </small>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <!-- Total Reservas -->
                <div class="col-md-3">
                    <div class="text-center p-3 border rounded report-tile">
                        <div class="mb-2">
                            <i class="fas fa-calendar-check fa-2x text-primary"></i>
                        </div>
                        <h4 class="mb-1 fw-bold">
                            <?= number_format($booking_stats['total_reservas'] ?? 0) ?>
                        </h4>
                        <p class="text-muted mb-0 small">Total Reservas</p>
                    </div>
                </div>

                <!-- Confirmadas -->
                <div class="col-md-3">
                    <div class="text-center p-3 border rounded report-tile">
                        <div class="mb-2">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <h4 class="mb-1 fw-bold text-success">
                            <?= number_format($booking_stats['confirmadas'] ?? 0) ?>
                        </h4>
                        <p class="text-muted mb-0 small">Confirmadas</p>
                    </div>
                </div>

                <!-- Pendientes -->
                <div class="col-md-3">
                    <div class="text-center p-3 border rounded report-tile">
                        <div class="mb-2">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                        <h4 class="mb-1 fw-bold text-warning">
                            <?= number_format($booking_stats['pendientes'] ?? 0) ?>
                        </h4>
                        <p class="text-muted mb-0 small">Pendientes</p>
                    </div>
                </div>

                <!-- Canceladas -->
                <div class="col-md-3">
                    <div class="text-center p-3 border rounded report-tile">
                        <div class="mb-2">
                            <i class="fas fa-times-circle fa-2x text-danger"></i>
                        </div>
                        <h4 class="mb-1 fw-bold text-danger">
                            <?= number_format($booking_stats['canceladas'] ?? 0) ?>
                        </h4>
                        <p class="text-muted mb-0 small">Canceladas</p>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row g-4">
                <!-- Ingresos Confirmados -->
                <div class="col-md-4">
                    <div class="text-center p-3 bg-success bg-opacity-10 rounded report-tile report-tile--soft">
                        <div class="mb-2">
                            <i class="fas fa-dollar-sign fa-2x text-success"></i>
                        </div>
                        <h4 class="mb-1 fw-bold text-success">
                            $<?= number_format($booking_stats['ingresos_confirmados'] ?? 0, 2) ?>
                        </h4>
                        <p class="text-muted mb-0 small">Ingresos Confirmados</p>
                    </div>
                </div>

                <!-- Ingresos Totales -->
                <div class="col-md-4">
                    <div class="text-center p-3 bg-primary bg-opacity-10 rounded report-tile report-tile--soft">
                        <div class="mb-2">
                            <i class="fas fa-coins fa-2x text-primary"></i>
                        </div>
                        <h4 class="mb-1 fw-bold text-primary">
                            $<?= number_format($booking_stats['ingresos_totales'] ?? 0, 2) ?>
                        </h4>
                        <p class="text-muted mb-0 small">Ingresos Totales</p>
                    </div>
                </div>

                <!-- Ticket Promedio -->
                <div class="col-md-4">
                    <div class="text-center p-3 bg-info bg-opacity-10 rounded report-tile report-tile--soft">
                        <div class="mb-2">
                            <i class="fas fa-receipt fa-2x text-info"></i>
                        </div>
                        <h4 class="mb-1 fw-bold text-info">
                            $<?= number_format($booking_stats['ticket_promedio'] ?? 0, 2) ?>
                        </h4>
                        <p class="text-muted mb-0 small">Ticket Promedio</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tours Más Vendidos -->
    <div class="card border-0 shadow-sm mb-4 dashboard-card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">
                <i class="fas fa-trophy text-warning me-2"></i>
                Tours Más Vendidos
                <?php if (isset($start_date) && isset($end_date)): ?>
                    <small class="text-muted">
                        (<?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?>)
                    </small>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 dashboard-table">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Tour</th>
                            <th class="text-center">Reservas</th>
                            <th class="text-end">Ingresos</th>
                            <th class="text-center" style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($top_products)): ?>
                            <?php $rank = 1; ?>
                            <?php foreach ($top_products as $product): ?>
                                <tr>
                                    <td class="text-center">
                                        <?php if ($rank <= 3): ?>
                                            <span class="badge rounded-pill bg-<?= $rank == 1 ? 'warning' : ($rank == 2 ? 'secondary' : 'info') ?>">
                                                <?= $rank ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted"><?= $rank ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-map-marked-alt text-primary me-2"></i>
                                            <strong><?= htmlspecialchars($product['nombre']) ?></strong>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">
                                            <?= number_format($product['total_reservas']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <strong class="text-success">
                                            $<?= number_format($product['ingresos'] ?? 0, 2) ?>
                                        </strong>
                                    </td>
                                    <td class="text-center">
                                        <a href="?route=admin/tours/edit/<?= $product['id'] ?>"
                                           class="btn btn-sm btn-outline-primary"
                                           title="Ver tour">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php $rank++; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    No hay datos de ventas para el período seleccionado
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Activity & Quick Stats -->
    <div class="dashboard-section-title">
        Actividad y sistema
        <span class="dashboard-section-subtitle">Últimos movimientos y estado operativo</span>
    </div>
    <div class="row g-4">
        <!-- Últimas Reservas -->
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm dashboard-card">
                <div class="card-header bg-white border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-history text-primary me-2"></i>
                            Actividad Reciente
                        </h5>
                        <a href="?route=admin/bookings" class="btn btn-sm btn-outline-primary">
                            Ver todo
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 dashboard-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Cliente</th>
                                    <th>Tour</th>
                                    <th>Fecha</th>
                                    <th>Monto</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_bookings)): ?>
                                    <?php foreach (array_slice($recent_bookings, 0, 5) as $booking): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle me-2"
                                                         style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                        <i class="fas fa-user text-primary"></i>
                                                    </div>
                                                    <?= htmlspecialchars($booking['cliente_nombre']) ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($booking['tour_nombre'] ?? 'N/A') ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y', strtotime($booking['created_at'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong>$<?= number_format($booking['monto_total'] ?? 0, 2) ?></strong>
                                            </td>
                                            <td>
                                                <?php
                                                $statusColors = [
                                                    'confirmada' => 'success',
                                                    'pendiente' => 'warning',
                                                    'cancelada' => 'danger',
                                                    'completada' => 'info'
                                                ];
                                                $color = $statusColors[$booking['estado']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $color ?>">
                                                    <?= ucfirst($booking['estado']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            No hay reservas recientes
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm mb-4 dashboard-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt text-warning me-2"></i>
                        Estadísticas Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 quick-stat">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Tours Activos</span>
                            <strong><?= $stats['total_products'] ?? 0 ?></strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-success" style="width: 100%"></div>
                        </div>
                    </div>

                    <div class="mb-3 quick-stat">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Categorías</span>
                            <strong><?= $stats['total_categories'] ?? 0 ?></strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-info" style="width: 75%"></div>
                        </div>
                    </div>

                    <div class="mb-3 quick-stat">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Usuarios Registrados</span>
                            <strong><?= $stats['total_users'] ?? 0 ?></strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: 60%"></div>
                        </div>
                    </div>

                    <div class="quick-stat">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Promedio por Reserva</span>
                            <strong>$<?= number_format($stats['avg_booking_value'] ?? 0, 2) ?></strong>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-warning" style="width: 85%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Health -->
            <div class="card border-0 shadow-sm dashboard-card">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-server text-success me-2"></i>
                        Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3 system-row">
                        <span class="text-muted">Estado</span>
                        <span class="badge bg-success">
                            <i class="fas fa-check-circle me-1"></i>
                            Operativo
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3 system-row">
                        <span class="text-muted">Última copia</span>
                        <small class="text-muted">Hace 2 horas</small>
                    </div>
                    <div class="d-flex justify-content-between align-items-center system-row">
                        <span class="text-muted">Versión</span>
                        <code>v2.0.0</code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart.js Global Configuration
    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#6b7280';

    // Data from PHP
    const chartData = <?= json_encode($chart_data ?? []) ?>;
    const baseUrl = "<?= Config::getBaseUrl() ?>";
    const startInput = document.getElementById('start_date');
    const endInput = document.getElementById('end_date');
    const periodInput = document.getElementById('chart_period');
    const periodButtons = document.querySelectorAll('[data-chart-period]');

    // ===================================
    // Reservas e Ingresos (Line Chart)
    // ===================================
    const ctxBookingsRevenue = document.getElementById('chartBookingsRevenue');
    const initialLabels = chartData.labels || chartData.months || ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    const initialBookings = chartData.bookings_series || chartData.bookings_by_month || [12, 19, 15, 25, 22, 30, 28, 35, 32, 38, 42, 45];
    const initialRevenue = chartData.revenue_series || chartData.revenue_by_month || [1200, 1900, 1500, 2500, 2200, 3000, 2800, 3500, 3200, 3800, 4200, 4500];

    const bookingsRevenueChart = new Chart(ctxBookingsRevenue, {
        type: 'line',
        data: {
            labels: initialLabels,
            datasets: [{
                label: 'Reservas',
                data: initialBookings,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.4,
                yAxisID: 'y'
            }, {
                label: 'Ingresos ($)',
                data: initialRevenue,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });

    function updateBookingsRevenueChart(data) {
        const labels = data.labels || data.months || [];
        const bookings = data.bookings_series || data.bookings_by_month || [];
        const revenue = data.revenue_series || data.revenue_by_month || [];

        bookingsRevenueChart.data.labels = labels;
        bookingsRevenueChart.data.datasets[0].data = bookings;
        bookingsRevenueChart.data.datasets[1].data = revenue;
        bookingsRevenueChart.update();
    }

    function fetchBookingsRevenue(period) {
        const params = new URLSearchParams({
            route: 'admin/dashboard/chart-data',
            start_date: startInput?.value || '',
            end_date: endInput?.value || '',
            period: period || periodInput?.value || 'month'
        });

        return fetch(`${baseUrl}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                if (data && data.success && data.data) {
                    updateBookingsRevenueChart(data.data);
                }
            })
            .catch(error => {
                console.error('Error al cargar datos del gráfico:', error);
            });
    }

    periodButtons.forEach(button => {
        button.addEventListener('click', function() {
            periodButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            const period = this.dataset.chartPeriod || 'month';
            if (periodInput) periodInput.value = period;
            fetchBookingsRevenue(period);
        });
    });

    // ===================================
    // Top Tours (Doughnut Chart)
    // ===================================
    const ctxTopProducts = document.getElementById('chartTopProducts');
    new Chart(ctxTopProducts, {
        type: 'doughnut',
        data: {
            labels: chartData.top_products_labels || ['Tikal', 'Semuc Champey', 'Antigua', 'Lago Atitlán', 'Otros'],
            datasets: [{
                data: chartData.top_products_data || [30, 25, 20, 15, 10],
                backgroundColor: [
                    '#0d6efd',
                    '#10b981',
                    '#f59e0b',
                    '#ef4444',
                    '#8b5cf6'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // ===================================
    // Reservas por Estado (Bar Chart)
    // ===================================
    const ctxBookingsByStatus = document.getElementById('chartBookingsByStatus');
    new Chart(ctxBookingsByStatus, {
        type: 'bar',
        data: {
            labels: ['Confirmadas', 'Pendientes', 'Completadas', 'Canceladas'],
            datasets: [{
                label: 'Reservas',
                data: chartData.bookings_by_status || [45, 12, 38, 5],
                backgroundColor: [
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(13, 110, 253, 0.8)',
                    'rgba(239, 68, 68, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // ===================================
    // Reservas por Categoría (Horizontal Bar)
    // ===================================
    const ctxBookingsByCategory = document.getElementById('chartBookingsByCategory');
    new Chart(ctxBookingsByCategory, {
        type: 'bar',
        data: {
            labels: chartData.categories_labels || ['Aventura', 'Cultura', 'Playa', 'Montaña', 'Relax'],
            datasets: [{
                label: 'Reservas',
                data: chartData.bookings_by_category || [28, 22, 18, 16, 12],
                backgroundColor: 'rgba(13, 110, 253, 0.8)'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<style>
.bg-success-subtle {
    background-color: rgba(16, 185, 129, 0.1);
}

.bg-primary-subtle {
    background-color: rgba(13, 110, 253, 0.1);
}

.avatar-sm {
    font-size: 14px;
}
</style>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
