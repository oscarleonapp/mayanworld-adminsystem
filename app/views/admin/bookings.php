<?php 
use App\Core\Config;
use App\Core\Helpers;
include __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <?php 
      $actionTitle = 'Gestión de Reservas';
      $actionSubtitle = 'Administra las reservas y solicitudes de viaje';
      $pendingCount = array_sum(array_map(function($b) { return ($b['estado'] ?? '') === 'pendiente' ? 1 : 0; }, $bookings ?? []));
      $actionButtons = [
        ['label' => 'Exportar', 'icon' => 'fas fa-download', 'variant' => 'outline-secondary', 'onclick' => 'exportBookings()'],
        ['label' => 'Pendientes', 'icon' => 'fas fa-clock', 'variant' => 'outline-warning', 'href' => Config::getBaseUrl() . '?route=admin/bookings&status=pendiente', 'badge' => $pendingCount, 'badgeClass' => 'bg-warning text-dark'],
        ['label' => 'Nueva Reserva', 'icon' => 'fas fa-plus', 'variant' => 'primary', 'onclick' => "new bootstrap.Modal(document.getElementById('newBookingModal')).show()"],
      ];
      include __DIR__ . '/../partials/admin_action_bar.php';
    ?>

    <!-- Quick Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Pendientes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= array_sum(array_map(function($b) { return $b['estado'] == 'pendiente' ? 1 : 0; }, $bookings)) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Confirmadas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= array_sum(array_map(function($b) { return $b['estado'] == 'confirmada' ? 1 : 0; }, $bookings)) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Ingresos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= Helpers::formatPrice(array_sum(array_map(function($b) { 
                                    return in_array($b['estado'], ['confirmada', 'pagada']) ? $b['precio_total'] : 0; 
                                }, $bookings))) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Reservas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= count($bookings) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter Bar -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 admin-filters" id="filter-form">
                <input type="hidden" name="route" value="admin/bookings">
                
                <div class="col-md-3">
                    <label for="search" class="form-label">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               id="search"
                               placeholder="Código, cliente, email..." 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label">Estado</label>
                    <select class="form-select" name="status" id="status">
                        <option value="">Todos los estados</option>
                        <option value="pendiente" <?= ($_GET['status'] ?? '') == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="confirmada" <?= ($_GET['status'] ?? '') == 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                        <option value="pagada" <?= ($_GET['status'] ?? '') == 'pagada' ? 'selected' : '' ?>>Pagada</option>
                        <option value="cancelada" <?= ($_GET['status'] ?? '') == 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                        <option value="completada" <?= ($_GET['status'] ?? '') == 'completada' ? 'selected' : '' ?>>Completada</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="date_from" class="form-label">Desde</label>
                    <input type="date" 
                           class="form-control" 
                           name="date_from" 
                           id="date_from"
                           value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="date_to" class="form-label">Hasta</label>
                    <input type="date" 
                           class="form-control" 
                           name="date_to" 
                           id="date_to"
                           value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="sort" class="form-label">Ordenar por</label>
                    <select class="form-select" name="sort" id="sort">
                        <option value="created_at_desc" <?= ($_GET['sort'] ?? '') == 'created_at_desc' ? 'selected' : '' ?>>Más recientes</option>
                        <option value="created_at_asc" <?= ($_GET['sort'] ?? '') == 'created_at_asc' ? 'selected' : '' ?>>Más antiguos</option>
                        <option value="fecha_salida_asc" <?= ($_GET['sort'] ?? '') == 'fecha_salida_asc' ? 'selected' : '' ?>>Fecha salida ↑</option>
                        <option value="fecha_salida_desc" <?= ($_GET['sort'] ?? '') == 'fecha_salida_desc' ? 'selected' : '' ?>>Fecha salida ↓</option>
                        <option value="precio_desc" <?= ($_GET['sort'] ?? '') == 'precio_desc' ? 'selected' : '' ?>>Precio mayor</option>
                        <option value="precio_asc" <?= ($_GET['sort'] ?? '') == 'precio_asc' ? 'selected' : '' ?>>Precio menor</option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Bookings Table -->
    <div class="card shadow">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    Lista de Reservas (<?= number_format(count($bookings)) ?>)
                </h6>
                <div class="d-flex gap-2">
                    <!-- Quick Filters -->
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/bookings&status=pendiente" 
                           class="btn btn-<?= ($_GET['status'] ?? '') == 'pendiente' ? 'primary' : 'outline-primary' ?>">
                            Pendientes
                        </a>
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/bookings&status=confirmada" 
                           class="btn btn-<?= ($_GET['status'] ?? '') == 'confirmada' ? 'primary' : 'outline-primary' ?>">
                            Confirmadas
                        </a>
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/bookings" 
                           class="btn btn-<?= empty($_GET['status']) ? 'primary' : 'outline-primary' ?>">
                            Todas
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <?php if (!empty($bookings)): ?>
            <div class="table-responsive-lg">
                <table class="table table-hover table-sticky align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="100">Código</th>
                            <th>Cliente</th>
                            <th>Tour</th>
                            <th width="100">Fechas</th>
                            <th width="80">Personas</th>
                            <th width="100">Total</th>
                            <th width="100">Estado</th>
                            <th width="80">Pago</th>
                            <th width="100">Fecha</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bookings as $booking): ?>
                        <tr class="booking-row" data-booking-id="<?= $booking['id'] ?>">
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="badge bg-secondary mb-1"><?= htmlspecialchars($booking['codigo_reserva']) ?></span>
                                    <small class="text-muted">ID: <?= $booking['id'] ?></small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <strong class="d-block"><?= htmlspecialchars($booking['cliente_nombre']) ?></strong>
                                    <small class="text-muted d-block"><?= htmlspecialchars($booking['cliente_email']) ?></small>
                                    <small class="text-muted"><?= htmlspecialchars($booking['cliente_telefono']) ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="small">
                                    <?php if (!empty($booking['tour_nombre'])): ?>
                                        <a href="<?= Config::getBaseUrl() ?>?route=tour/<?= $booking['tour_id'] ?>" 
                                           target="_blank" 
                                           class="text-decoration-none">
                                            <?= htmlspecialchars(Helpers::truncate($booking['tour_nombre'], 40)) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">Sin tour</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="small">
                                <div><strong>Salida:</strong></div>
                                <div class="text-muted"><?= date('d/m/Y', strtotime($booking['fecha_salida'])) ?></div>
                                <div><strong>Regreso:</strong></div>
                                <div class="text-muted"><?= date('d/m/Y', strtotime($booking['fecha_regreso'])) ?></div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info"><?= $booking['numero_personas'] ?></span>
                            </td>
                            <td>
                                <strong class="text-success"><?= Helpers::formatPrice($booking['precio_total']) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-status badge-status--<?= htmlspecialchars($booking['estado']) ?>">
                                    <?= ucfirst($booking['estado']) ?>
                                </span>
                            </td>
                            <td class="small">
                                <?= $booking['metodo_pago'] ? ucfirst(str_replace('_', ' ', $booking['metodo_pago'])) : '-' ?>
                            </td>
                            <td class="small text-muted">
                                <?= date('d/m/Y', strtotime($booking['created_at'])) ?>
                                <div><?= date('H:i', strtotime($booking['created_at'])) ?></div>
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
                                    
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle" 
                                                data-bs-toggle="dropdown" 
                                                title="Más acciones">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <button class="dropdown-item" onclick="editBooking(<?= $booking['id'] ?>)">
                                                    <i class="fas fa-edit me-2"></i>Editar
                                                </button>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" 
                                                   href="<?= Config::getBaseUrl() ?>?route=booking/confirm&code=<?= urlencode($booking['codigo_reserva']) ?>" 
                                                   target="_blank">
                                                    <i class="fas fa-external-link-alt me-2"></i>Ver confirmación
                                                </a>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" onclick="sendConfirmationEmail(<?= $booking['id'] ?>)">
                                                    <i class="fas fa-envelope me-2"></i>Reenviar email
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item" onclick="duplicateBooking(<?= $booking['id'] ?>)">
                                                    <i class="fas fa-copy me-2"></i>Duplicar
                                                </button>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <?php if ($booking['estado'] != 'cancelada'): ?>
                                            <li>
                                                <button class="dropdown-item text-danger" 
                                                        onclick="updateBookingStatus(<?= $booking['id'] ?>, 'cancelada')">
                                                    <i class="fas fa-times me-2"></i>Cancelar
                                                </button>
                                            </li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php else: ?>
            <!-- No Bookings Found -->
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                <h4 class="text-muted mb-3">No se encontraron reservas</h4>
                <p class="text-muted mb-4">
                    <?php if (!empty($_GET['search']) || !empty($_GET['status'])): ?>
                        No hay reservas que coincidan con los filtros aplicados.
                    <?php else: ?>
                        Aún no hay reservas registradas en el sistema.
                    <?php endif; ?>
                </p>
                <div class="d-flex gap-2 justify-content-center">
                    <?php if (!empty($_GET['search']) || !empty($_GET['status'])): ?>
                    <a href="<?= Config::getBaseUrl() ?>?route=admin/bookings" class="btn btn-outline-primary">
                        <i class="fas fa-times me-2"></i>Limpiar Filtros
                    </a>
                    <?php endif; ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newBookingModal">
                        <i class="fas fa-plus me-2"></i>Nueva Reserva
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($bookings) && $pagination['total_pages'] > 1): ?>
        <!-- Pagination -->
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Mostrando <?= count($bookings) ?> de <?= number_format($pagination['total']) ?> reservas
                </div>
                
                <nav aria-label="Navegación de reservas">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($pagination['current_page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/bookings&page=<?= $pagination['current_page'] - 1 ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        $start = max(1, $pagination['current_page'] - 2);
                        $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
                        ?>
                        
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                        <li class="page-item <?= ($i == $pagination['current_page']) ? 'active' : '' ?>">
                            <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/bookings&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/bookings&page=<?= $pagination['current_page'] + 1 ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
        <?php endif; ?>
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

<!-- New Booking Modal -->
<div class="modal fade" id="newBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Nueva Reserva Manual
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="new-booking-form" data-no-loading="true">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Esta funcionalidad permite crear reservas directamente desde el panel admin
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="tour_id" class="form-label">Tour *</label>
                            <select class="form-select" id="tour_id" name="tour_id" required>
                                <option value="">Seleccionar tour...</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= $product['id'] ?>" data-price="<?= htmlspecialchars($product['precio']) ?>">
                                        <?= htmlspecialchars($product['nombre']) ?> - $<?= number_format($product['precio'], 2) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="new-disponibilidad_id" class="form-label">Disponibilidad</label>
                            <select class="form-select" id="new-disponibilidad_id" name="disponibilidad_id">
                                <option value="">Sin disponibilidad</option>
                            </select>
                            <small class="text-muted">Opcional. Al elegir una fecha se ajustan las fechas del viaje.</small>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="cliente_nombre" class="form-label">Nombre del Cliente *</label>
                            <input type="text" class="form-control" id="cliente_nombre" name="cliente_nombre" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="cliente_email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="cliente_email" name="cliente_email" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="cliente_telefono" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="cliente_telefono" name="cliente_telefono">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="numero_personas" class="form-label">Número de Personas *</label>
                            <input type="number" class="form-control" id="numero_personas" name="numero_personas" min="1" max="20" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="fecha_salida" class="form-label">Fecha Salida *</label>
                            <input type="date" class="form-control" id="fecha_salida" name="fecha_salida" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="fecha_regreso" class="form-label">Fecha Regreso *</label>
                            <input type="date" class="form-control" id="fecha_regreso" name="fecha_regreso" required>
                        </div>

                        <div class="col-md-4">
                            <label for="new-descuento" class="form-label">Descuento</label>
                            <input type="number" class="form-control" id="new-descuento" name="descuento" min="0" step="0.01">
                        </div>
                        
                        <div class="col-md-4">
                            <label for="metodo_pago" class="form-label">Método de Pago</label>
                            <select class="form-select" id="metodo_pago" name="metodo_pago">
                                <option value="transferencia">Transferencia</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="estado" class="form-label">Estado Inicial</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="pendiente">Pendiente</option>
                                <option value="confirmada">Confirmada</option>
                                <option value="pagada">Pagada</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="p-3 bg-light border rounded">
                                <div class="row g-2 small">
                                    <div class="col-md-3">
                                        <div class="text-muted">Precio unitario</div>
                                        <div class="fw-semibold" id="new-price-unit">$0.00</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-muted">Subtotal</div>
                                        <div class="fw-semibold" id="new-price-subtotal">$0.00</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-muted">Descuento</div>
                                        <div class="fw-semibold" id="new-price-discount">$0.00</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-muted">Total</div>
                                        <div class="fw-bold" id="new-price-total">$0.00</div>
                                    </div>
                                </div>
                                <div class="small text-muted mt-2" id="new-availability-meta"></div>
                                <div class="small text-danger mt-1" id="new-availability-warning" style="display: none;"></div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label for="notas_admin" class="form-label">Notas Administrativas</label>
                            <textarea class="form-control" id="notas_admin" name="notas_admin" rows="3" 
                                      placeholder="Notas internas (no visibles para el cliente)"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Crear Reserva
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Booking Modal -->
<div class="modal fade" id="editBookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Editar Reserva</h5>
                    <small class="text-muted">Código: <span id="edit-booking-code">—</span></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="edit-booking-form" data-no-loading="true">
                <div class="modal-body">
                    <input type="hidden" name="booking_id" id="edit-booking-id">
                    <div class="alert alert-info small">
                        El precio se recalcula automáticamente según el tour y el número de personas.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="edit-tour_id" class="form-label">Tour *</label>
                            <select class="form-select" id="edit-tour_id" name="tour_id" required>
                                <option value="">Seleccionar tour...</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= $product['id'] ?>" data-price="<?= htmlspecialchars($product['precio']) ?>">
                                        <?= htmlspecialchars($product['nombre']) ?> - $<?= number_format($product['precio'], 2) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-disponibilidad_id" class="form-label">Disponibilidad</label>
                            <select class="form-select" id="edit-disponibilidad_id" name="disponibilidad_id">
                                <option value="">Sin disponibilidad</option>
                            </select>
                            <small class="text-muted">Opcional. Al elegir una fecha se ajustan las fechas del viaje.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-estado" class="form-label">Estado *</label>
                            <select class="form-select" id="edit-estado" name="estado" required>
                                <option value="pendiente">Pendiente</option>
                                <option value="confirmada">Confirmada</option>
                                <option value="pagada">Pagada</option>
                                <option value="cancelada">Cancelada</option>
                                <option value="completada">Completada</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-cliente_nombre" class="form-label">Nombre del Cliente *</label>
                            <input type="text" class="form-control" id="edit-cliente_nombre" name="cliente_nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-cliente_email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="edit-cliente_email" name="cliente_email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-cliente_telefono" class="form-label">Teléfono *</label>
                            <input type="tel" class="form-control" id="edit-cliente_telefono" name="cliente_telefono" required>
                        </div>
                        <div class="col-md-3">
                            <label for="edit-numero_personas" class="form-label">Personas *</label>
                            <input type="number" class="form-control" id="edit-numero_personas" name="numero_personas" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label for="edit-descuento" class="form-label">Descuento</label>
                            <input type="number" class="form-control" id="edit-descuento" name="descuento" min="0" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label for="edit-fecha_salida" class="form-label">Fecha Salida *</label>
                            <input type="date" class="form-control" id="edit-fecha_salida" name="fecha_salida" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-fecha_regreso" class="form-label">Fecha Regreso *</label>
                            <input type="date" class="form-control" id="edit-fecha_regreso" name="fecha_regreso" required>
                        </div>
                        <div class="col-md-6">
                            <label for="edit-metodo_pago" class="form-label">Método de Pago</label>
                            <select class="form-select" id="edit-metodo_pago" name="metodo_pago">
                                <option value="">Sin definir</option>
                                <option value="transferencia">Transferencia</option>
                                <option value="efectivo">Efectivo</option>
                                <option value="tarjeta">Tarjeta</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="p-3 bg-light border rounded">
                                <div class="row g-2 small">
                                    <div class="col-md-3">
                                        <div class="text-muted">Precio unitario</div>
                                        <div class="fw-semibold" id="edit-price-unit">$0.00</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-muted">Subtotal</div>
                                        <div class="fw-semibold" id="edit-price-subtotal">$0.00</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-muted">Descuento</div>
                                        <div class="fw-semibold" id="edit-price-discount">$0.00</div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-muted">Total</div>
                                        <div class="fw-bold" id="edit-price-total">$0.00</div>
                                    </div>
                                </div>
                                <div class="small text-muted mt-2" id="edit-availability-meta"></div>
                                <div class="small text-danger mt-1" id="edit-availability-warning" style="display: none;"></div>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="edit-notas_cliente" class="form-label">Notas del Cliente</label>
                            <textarea class="form-control" id="edit-notas_cliente" name="notas_cliente" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label for="edit-notas_admin" class="form-label">Notas Administrativas</label>
                            <textarea class="form-control" id="edit-notas_admin" name="notas_admin" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <div class="border rounded p-3 bg-white">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Historial de cambios</h6>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="refresh-booking-history">
                                        <i class="fas fa-sync-alt me-1"></i>Actualizar
                                    </button>
                                </div>
                                <div id="edit-booking-history" class="small text-muted" style="max-height: 220px; overflow: auto;">
                                    Cargando historial...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar Cambios
                    </button>
                </div>
            </form>
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
    
    // Fetch booking details (same as dashboard)
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
                    ${booking.cliente_direccion ? `
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info d-flex align-items-center gap-2 mb-2" style="font-size:.93rem;">
                                    <i class="fas fa-hotel fa-lg text-primary"></i>
                                    <span><strong>Hotel / Lugar de recogida:</strong> ${booking.cliente_direccion}</span>
                                </div>
                            </div>
                        </div>
                    ` : ''}
                    ${booking.notas_cliente ? `
                        <div class="row mt-3">
                            <div class="col-12">
                                <h6 class="fw-bold mb-2">Notas del Cliente</h6>
                                <div class="bg-light p-3 rounded">${booking.notas_cliente}</div>
                            </div>
                        </div>
                    ` : ''}
                `;
                
                // Add action buttons
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
            // Close modal if open with proper focus management
            const modalElement = document.getElementById('bookingModal');
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                // Move focus to body before hiding to avoid aria-hidden conflict
                if (document.activeElement && modalElement.contains(document.activeElement)) {
                    document.activeElement.blur();
                }
                modal.hide();
            }
            // Toast y recarga breve
            if (window.AdminUI) AdminUI.toast('Estado actualizado', 'success');
            setTimeout(() => location.reload(), 400);
        } else {
            if (window.AdminUI) AdminUI.toast('Error: ' + data.message, 'danger'); else alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.AdminUI) AdminUI.toast('Error al actualizar el estado', 'danger'); else alert('Error al actualizar el estado');
    });
}

function editBooking(bookingId) {
    const modalEl = document.getElementById('editBookingModal');
    const formEl = document.getElementById('edit-booking-form');
    if (!modalEl || !formEl) {
        if (window.AdminUI) AdminUI.toast('No se pudo abrir el editor', 'danger');
        return;
    }

    fetch(`<?= Config::getBaseUrl() ?>?route=admin/booking/${bookingId}/details`)
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.booking) {
                if (window.AdminUI) AdminUI.toast(data.message || 'Error al cargar la reserva', 'danger');
                return;
            }

            const booking = data.booking;
            document.getElementById('edit-booking-id').value = booking.id;
            document.getElementById('edit-booking-code').textContent = booking.codigo_reserva || '—';
            document.getElementById('edit-cliente_nombre').value = booking.cliente_nombre || '';
            document.getElementById('edit-cliente_email').value = booking.cliente_email || '';
            document.getElementById('edit-cliente_telefono').value = booking.cliente_telefono || '';
            document.getElementById('edit-numero_personas').value = booking.numero_personas || 1;
            document.getElementById('edit-fecha_salida').value = booking.fecha_salida ? booking.fecha_salida.substring(0, 10) : '';
            document.getElementById('edit-fecha_regreso').value = booking.fecha_regreso ? booking.fecha_regreso.substring(0, 10) : '';
            document.getElementById('edit-estado').value = booking.estado || 'pendiente';
            document.getElementById('edit-metodo_pago').value = booking.metodo_pago || '';
            document.getElementById('edit-notas_admin').value = booking.notas_admin || '';
            document.getElementById('edit-notas_cliente').value = booking.notas_cliente || '';
            document.getElementById('edit-descuento').value = booking.descuento ?? '';

            const productSelect = document.getElementById('edit-tour_id');
            if (productSelect) {
                const currentProductId = String(booking.tour_id || '');
                const hasOption = Array.from(productSelect.options).some(opt => opt.value === currentProductId);
                if (!hasOption && currentProductId) {
                    const option = document.createElement('option');
                    option.value = currentProductId;
                    option.dataset.price = booking.precio_unitario || 0;
                    option.textContent = `${booking.tour_nombre || 'Tour'} (no disponible)`;
                    productSelect.appendChild(option);
                }
                productSelect.value = currentProductId;
            }

            editBookingState = {
                id: booking.id,
                availabilityId: booking.disponibilidad_id ? String(booking.disponibilidad_id) : null,
                people: Number(booking.numero_personas || 0),
                status: booking.estado || 'pendiente'
            };

            loadAvailabilityOptions(booking.tour_id, 'edit-disponibilidad_id', booking.disponibilidad_id, true)
                .then(() => {
                    updateAvailabilityMeta({
                        availabilitySelectId: 'edit-disponibilidad_id',
                        metaId: 'edit-availability-meta',
                        startInputId: 'edit-fecha_salida',
                        endInputId: 'edit-fecha_regreso',
                        applyDates: false
                    });
                    updatePriceSummary({
                        productSelectId: 'edit-tour_id',
                        availabilitySelectId: 'edit-disponibilidad_id',
                        peopleInputId: 'edit-numero_personas',
                        discountInputId: 'edit-descuento',
                        unitElId: 'edit-price-unit',
                        subtotalElId: 'edit-price-subtotal',
                        discountElId: 'edit-price-discount',
                        totalElId: 'edit-price-total',
                        warningId: 'edit-availability-warning',
                        extraAvailabilityPeople: editBookingState?.availabilityId ? editBookingState.people : 0,
                        extraAvailabilityActive: editBookingState?.status !== 'cancelada',
                        extraAvailabilityId: editBookingState?.availabilityId,
                        skipWhenInactive: editBookingState?.status === 'cancelada'
                    });
                    loadBookingHistory(booking.id);
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                });
        })
        .catch(error => {
            console.error('Error:', error);
            if (window.AdminUI) AdminUI.toast('Error al cargar la reserva', 'danger');
        });
}

let editBookingState = null;

const priceFormatter = new Intl.NumberFormat('es-GT', {
    style: 'currency',
    currency: 'USD',
    minimumFractionDigits: 2
});

function formatDateLabel(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    if (Number.isNaN(date.getTime())) return dateStr;
    return date.toLocaleDateString('es-GT', { day: '2-digit', month: 'short', year: 'numeric' });
}

function getSelectedProductPrice(selectId) {
    const productSelect = document.getElementById(selectId);
    if (!productSelect) return 0;
    const option = productSelect.options[productSelect.selectedIndex];
    return option ? Number(option.dataset.price || 0) : 0;
}

function getSelectedAvailabilityPrice(selectId) {
    const availabilitySelect = document.getElementById(selectId);
    if (!availabilitySelect || !availabilitySelect.value) return null;
    const option = availabilitySelect.options[availabilitySelect.selectedIndex];
    const special = option ? option.dataset.price : null;
    if (special === undefined || special === null || special === '') return null;
    return Number(special);
}

function getSelectedAvailabilityRemaining(selectId) {
    const availabilitySelect = document.getElementById(selectId);
    if (!availabilitySelect || !availabilitySelect.value) return null;
    const option = availabilitySelect.options[availabilitySelect.selectedIndex];
    if (!option) return null;
    return Number(option.dataset.remaining || 0);
}

function updateAvailabilityMeta({ availabilitySelectId, metaId, startInputId, endInputId, applyDates }) {
    const availabilitySelect = document.getElementById(availabilitySelectId);
    const metaEl = document.getElementById(metaId);
    if (!availabilitySelect || !metaEl) return;

    if (!availabilitySelect.value) {
        metaEl.textContent = '';
        return;
    }

    const option = availabilitySelect.options[availabilitySelect.selectedIndex];
    const start = option?.dataset.start || '';
    const end = option?.dataset.end || start;
    const remaining = option?.dataset.remaining || '';
    const specialPrice = option?.dataset.price || '';

    const dateLabel = start ? `${formatDateLabel(start)}${end && end !== start ? ' - ' + formatDateLabel(end) : ''}` : '';
    const priceLabel = specialPrice ? `· Precio especial ${priceFormatter.format(Number(specialPrice))}` : '';
    const remainingLabel = remaining !== '' ? `· Cupos disponibles ${remaining}` : '';
    metaEl.textContent = `${dateLabel} ${remainingLabel} ${priceLabel}`.trim();

    if (applyDates && start) {
        const startInput = document.getElementById(startInputId);
        const endInput = document.getElementById(endInputId);
        if (startInput) startInput.value = start;
        if (endInput) endInput.value = end || start;
    }
}

function updateAvailabilityWarning({ availabilitySelectId, warningId, peopleInputId, extraAvailabilityPeople = 0, extraAvailabilityActive = false, extraAvailabilityId = null, skipWhenInactive = false }) {
    const warningEl = document.getElementById(warningId);
    if (!warningEl) return true;

    if (skipWhenInactive) {
        warningEl.style.display = 'none';
        warningEl.textContent = '';
        return true;
    }

    const remaining = getSelectedAvailabilityRemaining(availabilitySelectId);
    if (remaining === null) {
        warningEl.style.display = 'none';
        warningEl.textContent = '';
        return true;
    }

    const people = Number(document.getElementById(peopleInputId)?.value || 0);
    const selectEl = document.getElementById(availabilitySelectId);
    const selectedId = selectEl?.value ? String(selectEl.value) : null;
    const extraAllowed = extraAvailabilityActive && extraAvailabilityId && selectedId === String(extraAvailabilityId)
        ? Number(extraAvailabilityPeople || 0)
        : 0;
    const available = remaining + extraAllowed;

    if (people > available) {
        warningEl.textContent = `Cupos insuficientes para ${people} persona(s). Disponibles: ${available}.`;
        warningEl.style.display = 'block';
        return false;
    }

    warningEl.style.display = 'none';
    warningEl.textContent = '';
    return true;
}

function updatePriceSummary({
    productSelectId,
    availabilitySelectId,
    peopleInputId,
    discountInputId,
    unitElId,
    subtotalElId,
    discountElId,
    totalElId,
    warningId,
    extraAvailabilityPeople = 0,
    extraAvailabilityActive = false,
    extraAvailabilityId = null,
    skipWhenInactive = false
}) {
    const unitEl = document.getElementById(unitElId);
    const subtotalEl = document.getElementById(subtotalElId);
    const discountEl = document.getElementById(discountElId);
    const totalEl = document.getElementById(totalElId);

    if (!unitEl || !subtotalEl || !discountEl || !totalEl) return;

    const people = Number(document.getElementById(peopleInputId)?.value || 1);
    const discount = Number(document.getElementById(discountInputId)?.value || 0);
    const availabilityPrice = getSelectedAvailabilityPrice(availabilitySelectId);
    const unitPrice = availabilityPrice !== null ? availabilityPrice : getSelectedProductPrice(productSelectId);
    const subtotal = unitPrice * people;
    const total = Math.max(subtotal - discount, 0);

    unitEl.textContent = priceFormatter.format(unitPrice || 0);
    subtotalEl.textContent = priceFormatter.format(subtotal || 0);
    discountEl.textContent = priceFormatter.format(discount || 0);
    totalEl.textContent = priceFormatter.format(total || 0);

    if (warningId) {
        updateAvailabilityWarning({
            availabilitySelectId,
            warningId,
            peopleInputId,
            extraAvailabilityPeople,
            extraAvailabilityActive,
            extraAvailabilityId,
            skipWhenInactive
        });
    }
}

function loadAvailabilityOptions(productId, selectId, selectedId = null, includeInactiveSelected = false) {
    const availabilitySelect = document.getElementById(selectId);
    if (!availabilitySelect) return Promise.resolve();

    availabilitySelect.innerHTML = '<option value="">Sin disponibilidad</option>';

    if (!productId) {
        availabilitySelect.disabled = true;
        return Promise.resolve();
    }

    availabilitySelect.disabled = true;
    availabilitySelect.innerHTML = '<option value="">Cargando...</option>';

    return fetch(`<?= Config::getBaseUrl() ?>?route=admin/availability/list&tour_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            availabilitySelect.innerHTML = '<option value="">Sin disponibilidad</option>';
            availabilitySelect.disabled = false;

            if (!data.success || !Array.isArray(data.dates)) {
                return;
            }

            let hasSelected = false;
            data.dates.forEach(item => {
                if (item.activo !== undefined && Number(item.activo) !== 1) {
                    if (!includeInactiveSelected || String(item.id) !== String(selectedId)) {
                        return;
                    }
                }
                const start = item.fecha_salida || item.fecha_inicio || item.fecha || '';
                const end = item.fecha_regreso || item.fecha_salida || item.fecha_inicio || item.fecha || '';
                const disponibles = Number(item.cupos_disponibles || 0);
                const reservados = Number(item.cupos_reservados || 0);
                const remaining = Math.max(disponibles - reservados, 0);
                const label = `${formatDateLabel(start)}${end && end !== start ? ' - ' + formatDateLabel(end) : ''} · Cupos ${remaining}`;
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.precio_especial ? `${label} · Precio ${priceFormatter.format(Number(item.precio_especial))}` : label;
                option.dataset.start = start;
                option.dataset.end = end;
                option.dataset.remaining = remaining;
                option.dataset.price = item.precio_especial ?? '';
                availabilitySelect.appendChild(option);

                if (String(item.id) === String(selectedId)) {
                    hasSelected = true;
                }
            });

            if (selectedId && !hasSelected) {
                const option = document.createElement('option');
                option.value = selectedId;
                option.textContent = includeInactiveSelected ? 'Disponibilidad actual (no disponible)' : 'Disponibilidad actual';
                availabilitySelect.appendChild(option);
            }

            if (selectedId) {
                availabilitySelect.value = String(selectedId);
            } else {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                let closestOption = null;
                let closestTime = null;
                let earliestOption = null;
                let earliestTime = null;

                for (let i = 0; i < availabilitySelect.options.length; i += 1) {
                    const option = availabilitySelect.options[i];
                    if (!option.value) continue;
                    const start = option.dataset.start || '';
                    if (!start) continue;
                    const date = new Date(`${start}T00:00:00`);
                    if (Number.isNaN(date.getTime())) continue;
                    const time = date.getTime();

                    if (earliestTime === null || time < earliestTime) {
                        earliestTime = time;
                        earliestOption = option;
                    }

                    if (time >= today.getTime() && (closestTime === null || time < closestTime)) {
                        closestTime = time;
                        closestOption = option;
                    }
                }

                const defaultOption = closestOption || earliestOption;
                if (defaultOption) {
                    availabilitySelect.value = defaultOption.value;
                }
            }
        })
        .catch(() => {
            availabilitySelect.innerHTML = '<option value="">Sin disponibilidad</option>';
            availabilitySelect.disabled = false;
        });
}

const bookingFieldLabels = {
    tour_id: 'Tour',
    disponibilidad_id: 'Disponibilidad',
    cliente_nombre: 'Cliente',
    cliente_email: 'Email',
    cliente_telefono: 'Teléfono',
    numero_personas: 'Personas',
    fecha_salida: 'Fecha salida',
    fecha_regreso: 'Fecha regreso',
    precio_unitario: 'Precio unitario',
    precio_total: 'Subtotal',
    descuento: 'Descuento',
    precio_final: 'Total',
    metodo_pago: 'Método de pago',
    estado: 'Estado',
    notas_admin: 'Notas admin',
    notas_cliente: 'Notas cliente'
};

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatHistoryValue(value) {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'object') {
        return JSON.stringify(value);
    }
    return String(value);
}

function renderHistoryItem(log) {
    const before = log.datos_anteriores || {};
    const after = log.datos_nuevos || {};
    const keys = new Set([...Object.keys(before || {}), ...Object.keys(after || {})]);
    const changes = [];

    keys.forEach(key => {
        if (before?.[key] !== after?.[key]) {
            const label = bookingFieldLabels[key] || key;
            const fromValue = escapeHtml(formatHistoryValue(before?.[key]));
            const toValue = escapeHtml(formatHistoryValue(after?.[key]));
            changes.push(`<li><strong>${escapeHtml(label)}:</strong> ${fromValue} → ${toValue}</li>`);
        }
    });

    const date = log.created_at ? new Date(log.created_at).toLocaleString('es-GT') : '—';
    const user = escapeHtml(log.usuario_nombre || 'Sistema');
    const action = escapeHtml(log.accion || 'editar');

    return `
        <div class="border rounded p-2 mb-2">
            <div class="small text-muted">${escapeHtml(date)} · ${user} · ${action}</div>
            ${changes.length ? `<ul class="mb-0 mt-1">${changes.join('')}</ul>` : '<div class="small text-muted mt-1">Sin detalles del cambio.</div>'}
        </div>
    `;
}

function loadBookingHistory(bookingId) {
    const container = document.getElementById('edit-booking-history');
    if (!container || !bookingId) return;
    container.textContent = 'Cargando historial...';

    fetch(`<?= Config::getBaseUrl() ?>?route=admin/booking/${bookingId}/history`)
        .then(response => response.json())
        .then(data => {
            if (!data.success || !Array.isArray(data.logs) || data.logs.length === 0) {
                container.textContent = 'Sin historial registrado.';
                return;
            }
            container.innerHTML = data.logs.map(renderHistoryItem).join('');
        })
        .catch(() => {
            container.textContent = 'No se pudo cargar el historial.';
        });
}

function setFormLoading(formEl, isLoading) {
    if (!formEl) return;
    const submitBtn = formEl.querySelector('button[type="submit"]');
    if (!submitBtn) return;

    if (!submitBtn.dataset.originalHtml) {
        submitBtn.dataset.originalHtml = submitBtn.innerHTML;
    }

    if (isLoading) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
    } else {
        submitBtn.disabled = false;
        submitBtn.innerHTML = submitBtn.dataset.originalHtml || submitBtn.innerHTML;
    }
}

function sendConfirmationEmail(bookingId) {
    if (!confirm('¿Reenviar email de confirmación al cliente?')) {
        return;
    }
    
    fetch(`<?= Config::getBaseUrl() ?>?route=admin/booking/${bookingId}/resend-email`, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Email enviado exitosamente');
        } else {
            if (window.AdminUI) AdminUI.toast('Error: ' + data.message, 'danger'); else alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.AdminUI) AdminUI.toast('Error al enviar el email', 'danger'); else alert('Error al enviar el email');
    });
}

function duplicateBooking(bookingId) {
    if (!confirm('¿Duplicar esta reserva?')) {
        return;
    }
    
    fetch(`<?= Config::getBaseUrl() ?>?route=admin/booking/${bookingId}/duplicate`, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (window.AdminUI) AdminUI.toast('Reserva duplicada', 'success');
            setTimeout(() => location.reload(), 400);
        } else {
            if (window.AdminUI) AdminUI.toast('Error: ' + data.message, 'danger'); else alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.AdminUI) AdminUI.toast('Error al duplicar la reserva', 'danger'); else alert('Error al duplicar la reserva');
    });
}

function exportBookings() {
    const params = new URLSearchParams(window.location.search);
    params.set('route', 'admin/bookings/export');
    const url = '<?= Config::getBaseUrl() ?>?' + params.toString();
    if (typeof AdminDownload === 'function') {
        AdminDownload(url, {
            filenameFallback: 'reservas.csv',
            startMessage: 'Generando CSV...',
            errorMessage: 'Error al exportar reservas'
        });
    }
}

// New booking field updates
document.getElementById('tour_id')?.addEventListener('change', function() {
    const productId = this.value;
    loadAvailabilityOptions(productId, 'new-disponibilidad_id')
        .then(() => {
            updateAvailabilityMeta({
                availabilitySelectId: 'new-disponibilidad_id',
                metaId: 'new-availability-meta',
                startInputId: 'fecha_salida',
                endInputId: 'fecha_regreso',
                applyDates: true
            });
            updatePriceSummary({
                productSelectId: 'tour_id',
                availabilitySelectId: 'new-disponibilidad_id',
                peopleInputId: 'numero_personas',
                discountInputId: 'new-descuento',
                unitElId: 'new-price-unit',
                subtotalElId: 'new-price-subtotal',
                discountElId: 'new-price-discount',
                totalElId: 'new-price-total',
                warningId: 'new-availability-warning'
            });
        });
});

document.getElementById('new-disponibilidad_id')?.addEventListener('change', function() {
    updateAvailabilityMeta({
        availabilitySelectId: 'new-disponibilidad_id',
        metaId: 'new-availability-meta',
        startInputId: 'fecha_salida',
        endInputId: 'fecha_regreso',
        applyDates: true
    });
    updatePriceSummary({
        productSelectId: 'tour_id',
        availabilitySelectId: 'new-disponibilidad_id',
        peopleInputId: 'numero_personas',
        discountInputId: 'new-descuento',
        unitElId: 'new-price-unit',
        subtotalElId: 'new-price-subtotal',
        discountElId: 'new-price-discount',
        totalElId: 'new-price-total',
        warningId: 'new-availability-warning'
    });
});

document.getElementById('numero_personas')?.addEventListener('input', () => {
    updatePriceSummary({
        productSelectId: 'tour_id',
        availabilitySelectId: 'new-disponibilidad_id',
        peopleInputId: 'numero_personas',
        discountInputId: 'new-descuento',
        unitElId: 'new-price-unit',
        subtotalElId: 'new-price-subtotal',
        discountElId: 'new-price-discount',
        totalElId: 'new-price-total',
        warningId: 'new-availability-warning'
    });
});

document.getElementById('new-descuento')?.addEventListener('input', () => {
    updatePriceSummary({
        productSelectId: 'tour_id',
        availabilitySelectId: 'new-disponibilidad_id',
        peopleInputId: 'numero_personas',
        discountInputId: 'new-descuento',
        unitElId: 'new-price-unit',
        subtotalElId: 'new-price-subtotal',
        discountElId: 'new-price-discount',
        totalElId: 'new-price-total',
        warningId: 'new-availability-warning'
    });
});

// New booking form
document.getElementById('new-booking-form')?.addEventListener('submit', function(e) {
    e.preventDefault();

    if (typeof this.reportValidity === 'function' && !this.reportValidity()) {
        setFormLoading(this, false);
        return;
    }

    const startInput = document.getElementById('fecha_salida');
    const endInput = document.getElementById('fecha_regreso');
    const peopleInput = document.getElementById('numero_personas');
    const startDate = startInput?.value ? new Date(startInput.value) : null;
    const endDate = endInput?.value ? new Date(endInput.value) : null;
    const people = Number(peopleInput?.value || 0);

    if (people < 1) {
        if (window.AdminUI) AdminUI.toast('El número de personas debe ser al menos 1', 'danger');
        if (peopleInput) peopleInput.focus();
        setFormLoading(this, false);
        return;
    }

    if (startDate && endDate && startDate > endDate) {
        if (window.AdminUI) AdminUI.toast('La fecha de regreso no puede ser anterior a la fecha de salida', 'danger');
        if (endInput) endInput.focus();
        setFormLoading(this, false);
        return;
    }

    const availabilityOk = updateAvailabilityWarning({
        availabilitySelectId: 'new-disponibilidad_id',
        warningId: 'new-availability-warning',
        peopleInputId: 'numero_personas'
    });
    if (!availabilityOk) {
        if (window.AdminUI) AdminUI.toast('Cupos insuficientes para la disponibilidad seleccionada', 'danger');
        setFormLoading(this, false);
        return;
    }

    const formData = new FormData(this);
    setFormLoading(this, true);

    fetch('<?= Config::getBaseUrl() ?>?route=admin/bookings/create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('newBookingModal')).hide();
            location.reload();
        } else {
            if (window.AdminUI) AdminUI.toast('Error: ' + data.message, 'danger'); else alert('Error: ' + data.message);
            setFormLoading(this, false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.AdminUI) AdminUI.toast('Error al crear la reserva', 'danger'); else alert('Error al crear la reserva');
        setFormLoading(this, false);
    });
});

// Edit booking field updates
document.getElementById('edit-tour_id')?.addEventListener('change', function() {
    const productId = this.value;
    loadAvailabilityOptions(productId, 'edit-disponibilidad_id')
        .then(() => {
            updateAvailabilityMeta({
                availabilitySelectId: 'edit-disponibilidad_id',
                metaId: 'edit-availability-meta',
                startInputId: 'edit-fecha_salida',
                endInputId: 'edit-fecha_regreso',
                applyDates: true
            });
            updatePriceSummary({
                productSelectId: 'edit-tour_id',
                availabilitySelectId: 'edit-disponibilidad_id',
                peopleInputId: 'edit-numero_personas',
                discountInputId: 'edit-descuento',
                unitElId: 'edit-price-unit',
                subtotalElId: 'edit-price-subtotal',
                discountElId: 'edit-price-discount',
                totalElId: 'edit-price-total',
                warningId: 'edit-availability-warning',
                extraAvailabilityPeople: editBookingState?.availabilityId ? editBookingState.people : 0,
                extraAvailabilityActive: editBookingState?.status !== 'cancelada',
                extraAvailabilityId: editBookingState?.availabilityId,
                skipWhenInactive: editBookingState?.status === 'cancelada'
            });
        });
});

document.getElementById('edit-disponibilidad_id')?.addEventListener('change', function() {
    updateAvailabilityMeta({
        availabilitySelectId: 'edit-disponibilidad_id',
        metaId: 'edit-availability-meta',
        startInputId: 'edit-fecha_salida',
        endInputId: 'edit-fecha_regreso',
        applyDates: true
    });
    updatePriceSummary({
        productSelectId: 'edit-tour_id',
        availabilitySelectId: 'edit-disponibilidad_id',
        peopleInputId: 'edit-numero_personas',
        discountInputId: 'edit-descuento',
        unitElId: 'edit-price-unit',
        subtotalElId: 'edit-price-subtotal',
        discountElId: 'edit-price-discount',
        totalElId: 'edit-price-total',
        warningId: 'edit-availability-warning',
        extraAvailabilityPeople: editBookingState?.availabilityId ? editBookingState.people : 0,
        extraAvailabilityActive: editBookingState?.status !== 'cancelada',
        extraAvailabilityId: editBookingState?.availabilityId,
        skipWhenInactive: editBookingState?.status === 'cancelada'
    });
});

document.getElementById('edit-numero_personas')?.addEventListener('input', () => {
    updatePriceSummary({
        productSelectId: 'edit-tour_id',
        availabilitySelectId: 'edit-disponibilidad_id',
        peopleInputId: 'edit-numero_personas',
        discountInputId: 'edit-descuento',
        unitElId: 'edit-price-unit',
        subtotalElId: 'edit-price-subtotal',
        discountElId: 'edit-price-discount',
        totalElId: 'edit-price-total',
        warningId: 'edit-availability-warning',
        extraAvailabilityPeople: editBookingState?.availabilityId ? editBookingState.people : 0,
        extraAvailabilityActive: editBookingState?.status !== 'cancelada',
        extraAvailabilityId: editBookingState?.availabilityId,
        skipWhenInactive: editBookingState?.status === 'cancelada'
    });
});

document.getElementById('edit-descuento')?.addEventListener('input', () => {
    updatePriceSummary({
        productSelectId: 'edit-tour_id',
        availabilitySelectId: 'edit-disponibilidad_id',
        peopleInputId: 'edit-numero_personas',
        discountInputId: 'edit-descuento',
        unitElId: 'edit-price-unit',
        subtotalElId: 'edit-price-subtotal',
        discountElId: 'edit-price-discount',
        totalElId: 'edit-price-total',
        warningId: 'edit-availability-warning',
        extraAvailabilityPeople: editBookingState?.availabilityId ? editBookingState.people : 0,
        extraAvailabilityActive: editBookingState?.status !== 'cancelada',
        extraAvailabilityId: editBookingState?.availabilityId,
        skipWhenInactive: editBookingState?.status === 'cancelada'
    });
});

document.getElementById('edit-estado')?.addEventListener('change', function() {
    if (editBookingState) {
        editBookingState.status = this.value;
    }
    updatePriceSummary({
        productSelectId: 'edit-tour_id',
        availabilitySelectId: 'edit-disponibilidad_id',
        peopleInputId: 'edit-numero_personas',
        discountInputId: 'edit-descuento',
        unitElId: 'edit-price-unit',
        subtotalElId: 'edit-price-subtotal',
        discountElId: 'edit-price-discount',
        totalElId: 'edit-price-total',
        warningId: 'edit-availability-warning',
        extraAvailabilityPeople: editBookingState?.availabilityId ? editBookingState.people : 0,
        extraAvailabilityActive: editBookingState?.status !== 'cancelada',
        extraAvailabilityId: editBookingState?.availabilityId,
        skipWhenInactive: editBookingState?.status === 'cancelada'
    });
});

document.getElementById('refresh-booking-history')?.addEventListener('click', function() {
    if (editBookingState?.id) {
        loadBookingHistory(editBookingState.id);
    }
});

// Edit booking form
document.getElementById('edit-booking-form')?.addEventListener('submit', function(e) {
    e.preventDefault();

    if (typeof this.reportValidity === 'function' && !this.reportValidity()) {
        setFormLoading(this, false);
        return;
    }

    const startInput = document.getElementById('edit-fecha_salida');
    const endInput = document.getElementById('edit-fecha_regreso');
    const peopleInput = document.getElementById('edit-numero_personas');
    const startDate = startInput?.value ? new Date(startInput.value) : null;
    const endDate = endInput?.value ? new Date(endInput.value) : null;
    const people = Number(peopleInput?.value || 0);

    if (people < 1) {
        if (window.AdminUI) AdminUI.toast('El número de personas debe ser al menos 1', 'danger');
        if (peopleInput) peopleInput.focus();
        setFormLoading(this, false);
        return;
    }

    if (startDate && endDate && startDate > endDate) {
        if (window.AdminUI) AdminUI.toast('La fecha de regreso no puede ser anterior a la fecha de salida', 'danger');
        if (endInput) endInput.focus();
        setFormLoading(this, false);
        return;
    }

    const availabilityOk = updateAvailabilityWarning({
        availabilitySelectId: 'edit-disponibilidad_id',
        warningId: 'edit-availability-warning',
        peopleInputId: 'edit-numero_personas',
        extraAvailabilityPeople: editBookingState?.availabilityId ? editBookingState.people : 0,
        extraAvailabilityActive: editBookingState?.status !== 'cancelada',
        extraAvailabilityId: editBookingState?.availabilityId,
        skipWhenInactive: editBookingState?.status === 'cancelada'
    });
    if (!availabilityOk) {
        if (window.AdminUI) AdminUI.toast('Cupos insuficientes para la disponibilidad seleccionada', 'danger');
        setFormLoading(this, false);
        return;
    }

    const formData = new FormData(this);
    setFormLoading(this, true);
    fetch('<?= Config::getBaseUrl() ?>?route=admin/bookings/update', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('editBookingModal')).hide();
            if (window.AdminUI) AdminUI.toast('Reserva actualizada', 'success');
            setTimeout(() => location.reload(), 400);
        } else {
            if (window.AdminUI) AdminUI.toast('Error: ' + data.message, 'danger'); else alert('Error: ' + data.message);
            setFormLoading(this, false);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.AdminUI) AdminUI.toast('Error al actualizar la reserva', 'danger'); else alert('Error al actualizar la reserva');
        setFormLoading(this, false);
    });
});

// Row click to view details
document.querySelectorAll('.booking-row').forEach(row => {
    row.addEventListener('click', function(e) {
        if (!e.target.closest('.btn') && !e.target.closest('.dropdown')) {
            const bookingId = this.dataset.bookingId;
            viewBooking(bookingId);
        }
    });
});

// Fix accessibility: blur focus before modal hides to avoid aria-hidden conflict
document.getElementById('bookingModal')?.addEventListener('hide.bs.modal', function(e) {
    // If an element inside the modal has focus, blur it
    if (document.activeElement && this.contains(document.activeElement)) {
        document.activeElement.blur();
    }
});

document.getElementById('newBookingModal')?.addEventListener('hide.bs.modal', function(e) {
    // If an element inside the modal has focus, blur it
    if (document.activeElement && this.contains(document.activeElement)) {
        document.activeElement.blur();
    }
});
</script>

<?php include __DIR__ . '/../layouts/admin_footer.php'; ?>
