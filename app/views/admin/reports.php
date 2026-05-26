<?php include __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <?php
      $actionTitle = 'Reportes y Estadísticas';
      $actionSubtitle = 'Análisis de rendimiento del negocio';
      $actionButtons = [];
      include __DIR__ . '/../partials/admin_action_bar.php';
    ?>

    <!-- Filtro de Fechas -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <input type="hidden" name="route" value="admin/reports">
                <div class="col-md-4">
                    <label class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" name="start_date"
                           value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" name="end_date"
                           value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block w-100">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Estadísticas de Reservas -->
    <h5 class="mb-3">Estadísticas de Reservas</h5>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                    <h3><?= number_format($booking_stats['total_reservas']) ?></h3>
                    <small>Total Reservas</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                    <h3><?= number_format($booking_stats['confirmadas']) ?></h3>
                    <small>Confirmadas</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-clock fa-2x mb-2"></i>
                    <h3><?= number_format($booking_stats['pendientes']) ?></h3>
                    <small>Pendientes</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <i class="fas fa-times-circle fa-2x mb-2"></i>
                    <h3><?= number_format($booking_stats['canceladas']) ?></h3>
                    <small>Canceladas</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas de Ingresos -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                    <h3>$<?= number_format($booking_stats['ingresos_confirmados'] ?? 0, 2) ?></h3>
                    <small>Ingresos Confirmados</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                    <h3>$<?= number_format($booking_stats['ingresos_totales'] ?? 0, 2) ?></h3>
                    <small>Ingresos Totales</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-dark text-white">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                    <h3>$<?= number_format($booking_stats['ticket_promedio'] ?? 0, 2) ?></h3>
                    <small>Ticket Promedio</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tours Más Vendidos -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-trophy me-2"></i>Top 10 Tours Más Vendidos
            </h6>
        </div>
        <div class="card-body">
            <?php if (!empty($top_products)): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Tour</th>
                                <th>Reservas</th>
                                <th>Ingresos</th>
                                <th>Precio Promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_products as $index => $product): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($product['nombre']) ?></td>
                                    <td><?= number_format($product['total_reservas'] ?? 0) ?></td>
                                    <td>$<?= number_format($product['ingresos'] ?? 0, 2) ?></td>
                                    <td>$<?= number_format($product['precio_promedio'] ?? 0, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4">No hay datos de tours en el rango seleccionado</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Reservas por Día (Últimos 30 días) -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-calendar-alt me-2"></i>Reservas por Día (Últimos 30 días)
            </h6>
        </div>
        <div class="card-body">
            <?php if (!empty($daily_bookings)): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Total Reservas</th>
                                <th>Ingresos</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daily_bookings as $day): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($day['fecha'])) ?></td>
                                    <td><?= number_format($day['total'] ?? 0) ?></td>
                                    <td>$<?= number_format($day['ingresos'] ?? 0, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted text-center py-4">No hay datos de reservas en los últimos 30 días</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Estadísticas de Mensajes -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-envelope me-2"></i>Estadísticas de Mensajes
            </h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-primary"><?= number_format($message_stats['total_mensajes']) ?></h4>
                        <small class="text-muted">Total Mensajes</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-warning"><?= number_format($message_stats['nuevos']) ?></h4>
                        <small class="text-muted">Nuevos</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-info"><?= number_format($message_stats['leidos']) ?></h4>
                        <small class="text-muted">Leídos</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-center">
                        <h4 class="text-success"><?= number_format($message_stats['respondidos']) ?></h4>
                        <small class="text-muted">Respondidos</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/admin_footer.php'; ?>
