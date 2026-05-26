<?php
use App\Core\Config;
use App\Core\Auth;
use App\Core\Helpers;
include __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <?php
        $actionTitle = 'Panel Administrativo';
        $actionSubtitle = 'Bienvenido de vuelta, ' . htmlspecialchars($auth->getCurrentUser()['nombre']);
        $actionButtons = [
            ['label' => 'Actualizar', 'icon' => 'fas fa-sync-alt', 'variant' => 'outline-primary', 'onclick' => 'location.reload()'],
        ];
        include __DIR__ . '/../partials/admin_action_bar.php';
    ?>

    <div class="d-flex justify-content-end mb-4">
        <div class="dropdown">
            <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-plus me-2"></i>Nuevo
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="<?= Config::getBaseUrl() ?>?route=admin/tours/create">
                    <i class="fas fa-map-marked-alt me-2"></i>Tour
                </a></li>
                <li><a class="dropdown-item" href="<?= Config::getBaseUrl() ?>?route=admin/bookings">
                    <i class="fas fa-calendar-plus me-2"></i>Reserva Manual
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= Config::getBaseUrl() ?>?route=admin/staff">
                    <i class="fas fa-users me-2"></i>Empleado
                </a></li>
                <li><a class="dropdown-item" href="<?= Config::getBaseUrl() ?>?route=admin/routes">
                    <i class="fas fa-route me-2"></i>Ruta de Bus
                </a></li>
            </ul>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Tours Activos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['total_products'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-map-marked-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Reservas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['total_bookings'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Reservas Pendientes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['pending_bookings'] ?? 0) ?>
                            </div>
                            <?php if (($stats['pending_bookings'] ?? 0) > 0): ?>
                            <div class="mt-2">
                                <a href="<?= Config::getBaseUrl() ?>?route=admin/bookings&status=pendiente" 
                                   class="btn btn-warning btn-sm">
                                    <i class="fas fa-eye me-1"></i>Revisar
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Ingresos Totales
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                $<?= number_format($stats['total_revenue'] ?? 0, 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Staff and Routes Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Personal Activo
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($staffStats['activos'] ?? 0) ?>
                            </div>
                            <div class="small text-success">
                                <i class="fas fa-users me-1"></i><?= $staffStats['total_empleados'] ?? 0 ?> total
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Guías Disponibles
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($staffStats['total_guias'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-route fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Conductores
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($staffStats['total_conductores'] ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-bus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Rutas Activas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($routeStats['rutas_activas'] ?? 0) ?>
                            </div>
                            <div class="small text-primary">
                                <i class="fas fa-route me-1"></i><?= $routeStats['total_rutas'] ?? 0 ?> total
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-route fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages Alert -->
    <?php if ($stats['new_messages'] > 0): ?>
    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-envelope fa-2x me-3"></i>
            <div class="flex-grow-1">
                <h5 class="alert-heading mb-1">Tienes <?= $stats['new_messages'] ?> mensajes nuevos</h5>
                <p class="mb-2">Hay consultas de clientes que requieren tu atención.</p>
                <a href="<?= Config::getBaseUrl() ?>?route=admin/messages&status=nuevo" class="btn btn-info btn-sm">
                    <i class="fas fa-reply me-1"></i>Responder Mensajes
                </a>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Recent Bookings -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Reservas Recientes
                    </h6>
                    <a href="<?= Config::getBaseUrl() ?>?route=admin/bookings" class="btn btn-primary btn-sm">
                        <i class="fas fa-list me-1"></i>Ver Todas
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($recent_bookings)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Cliente</th>
                                    <th>Tour</th>
                                    <th>Fecha</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recent_bookings, 0, 5) as $booking): ?>
                                <tr>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($booking['codigo_reserva']) ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?= htmlspecialchars($booking['cliente_nombre']) ?></strong>
                                            <div class="small text-muted"><?= htmlspecialchars($booking['cliente_email']) ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <?= htmlspecialchars(Helpers::truncate($booking['tour_nombre'] ?? 'N/A', 30)) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <?= date('d/m/Y', strtotime($booking['fecha_salida'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <strong><?= Helpers::formatPrice($booking['precio_total']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $booking['estado'] == 'confirmada' ? 'success' : ($booking['estado'] == 'pendiente' ? 'warning' : 'secondary') ?>">
                                            <?= ucfirst($booking['estado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-info" 
                                                    onclick="viewBooking(<?= $booking['id'] ?>)"
                                                    title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($booking['estado'] == 'pendiente'): ?>
                                            <button class="btn btn-outline-success" 
                                                    onclick="updateBookingStatus(<?= $booking['id'] ?>, 'confirmada')"
                                                    title="Confirmar">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay reservas recientes</h5>
                        <p class="text-muted">Las nuevas reservas aparecerán aquí</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Popular Products & Quick Actions -->
        <div class="col-xl-4 col-lg-5">
            <!-- Popular Products -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-star me-2"></i>
                        Tours Populares
                    </h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($popular_products)): ?>
                    <?php foreach (array_slice($popular_products, 0, 5) as $product): ?>
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?= htmlspecialchars($product['nombre']) ?></h6>
                            <div class="small text-muted">
                                <i class="fas fa-calendar-check me-1"></i>
                                <?= $product['total_reservas'] ?> reservas
                                <span class="mx-2">•</span>
                                <i class="fas fa-users me-1"></i>
                                <?= $product['total_personas'] ?> personas
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="progress progress-compact">
                                <div class="progress-bar bg-primary" 
                                     style="width: <?= min(100, ($product['total_reservas'] / max($popular_products[0]['total_reservas'], 1)) * 100) ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <div class="text-center py-3">
                        <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                        <p class="text-muted small mb-0">No hay datos suficientes</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-bolt me-2"></i>
                        Acciones Rápidas
                    </h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/bookings&status=pendiente" 
                           class="btn btn-warning">
                            <i class="fas fa-clock me-2"></i>
                            Revisar Pendientes (<?= $stats['pending_bookings'] ?>)
                        </a>
                        
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/messages&status=nuevo" 
                           class="btn btn-info">
                            <i class="fas fa-envelope me-2"></i>
                            Mensajes Nuevos (<?= $stats['new_messages'] ?>)
                        </a>
                        
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/tours" 
                           class="btn btn-primary">
                            <i class="fas fa-map-marked-alt me-2"></i>
                            Gestionar Tours
                        </a>
                        
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/staff" 
                           class="btn btn-info">
                            <i class="fas fa-users me-2"></i>
                            Administrar Personal
                        </a>
                        
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/routes" 
                           class="btn btn-success">
                            <i class="fas fa-route me-2"></i>
                            Rutas de Bus
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history me-2"></i>
                        Actividad Reciente
                    </h6>
                </div>
                <div class="card-body">
                    <div class="timeline-small">
                        <?php if (!empty($recent_bookings)): ?>
                        <?php foreach (array_slice($recent_bookings, 0, 3) as $booking): ?>
                        <div class="timeline-item-small">
                            <div class="timeline-marker-small bg-<?= $booking['estado'] == 'confirmada' ? 'success' : 'warning' ?>"></div>
                            <div class="timeline-content-small">
                                <div class="small">
                                    <strong>Nueva reserva</strong>
                                    <div class="text-muted">
                                        <?= htmlspecialchars($booking['cliente_nombre']) ?>
                                        <br>
                                        <?= date('d/m/Y H:i', strtotime($booking['created_at'])) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                            <p class="text-muted small mb-0">Sin actividad reciente</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalles de Reserva</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="booking-details-content">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <div id="booking-actions"></div>
            </div>
        </div>
    </div>
</div>

<!-- Custom CSS moved to public/assets/css/admin.css -->

<!-- JavaScript -->
<script>
function viewBooking(bookingId) {
    const modal = new bootstrap.Modal(document.getElementById('bookingModal'));
    const content = document.getElementById('booking-details-content');
    const actions = document.getElementById('booking-actions');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
    `;
    actions.innerHTML = '';
    
    modal.show();
    
    // Fetch booking details
    fetch(`<?= Config::getBaseUrl() ?>?route=admin/booking/${bookingId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const booking = data.booking;
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Información del Cliente</h6>
                            <div class="mb-2"><strong>Nombre:</strong> ${booking.cliente_nombre}</div>
                            <div class="mb-2"><strong>Email:</strong> ${booking.cliente_email}</div>
                            <div class="mb-2"><strong>Teléfono:</strong> ${booking.cliente_telefono}</div>
                            <div class="mb-3"><strong>Código:</strong> <span class="badge bg-secondary">${booking.codigo_reserva}</span></div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold mb-3">Detalles del Viaje</h6>
                            <div class="mb-2"><strong>Tour:</strong> ${booking.tour_nombre || 'N/A'}</div>
                            <div class="mb-2"><strong>Fecha salida:</strong> ${new Date(booking.fecha_salida).toLocaleDateString('es-ES')}</div>
                            <div class="mb-2"><strong>Fecha regreso:</strong> ${new Date(booking.fecha_regreso).toLocaleDateString('es-ES')}</div>
                            <div class="mb-2"><strong>Personas:</strong> ${booking.numero_personas}</div>
                            <div class="mb-3"><strong>Total:</strong> ${new Intl.NumberFormat('es-MX', {style: 'currency', currency: 'MXN'}).format(booking.precio_total)}</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="fw-bold mb-2">Estado Actual</h6>
                            <span class="badge bg-${booking.estado === 'confirmada' ? 'success' : booking.estado === 'pendiente' ? 'warning' : 'secondary'} fs-6">
                                ${booking.estado.charAt(0).toUpperCase() + booking.estado.slice(1)}
                            </span>
                        </div>
                    </div>
                    ${booking.notas_cliente ? `
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="fw-bold mb-2">Notas del Cliente</h6>
                                <div class="bg-light p-3 rounded">${booking.notas_cliente}</div>
                            </div>
                        </div>
                    ` : ''}
                `;
                
                // Add action buttons based on status
                if (booking.estado === 'pendiente') {
                    actions.innerHTML = `
                        <button type="button" class="btn btn-success" onclick="updateBookingStatus(${bookingId}, 'confirmada')">
                            <i class="fas fa-check me-2"></i>Confirmar
                        </button>
                        <button type="button" class="btn btn-danger" onclick="updateBookingStatus(${bookingId}, 'cancelada')">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </button>
                    `;
                }
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al cargar los detalles: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar los detalles
                </div>
            `;
            console.error('Error:', error);
        });
}

function updateBookingStatus(bookingId, newStatus) {
    if (!confirm(`¿Estás seguro de cambiar el estado a "${newStatus}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('booking_id', bookingId);
    formData.append('status', newStatus);
    
    fetch('<?= Config::getBaseUrl() ?>?route=admin/update-booking-status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal if open
            const modal = bootstrap.Modal.getInstance(document.getElementById('bookingModal'));
            if (modal) modal.hide();
            
            // Reload page to reflect changes
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar el estado');
    });
}

// Auto-refresh every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[title]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });
});
</script>

<?php include __DIR__ . '/../layouts/admin_footer.php'; ?>
