<?php

use App\Core\Config;
use App\Core\Helpers;
$title = $title ?? 'Traslados y Transportes';
$metaDescription = $metaDescription ?? 'Servicios de traslado en Guatemala';
$extraStyles = ['css/components/transfers.css', 'css/components/cards.css', 'css/components/forms.css'];
include __DIR__ . '/../layouts/header.php';
?>

<!-- Hero Section -->
<section class="transfers-hero py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold mb-3">🚐 Traslados y Transportes</h1>
                <p class="lead mb-4">Viaja cómodo y seguro. Servicio de traslado privado y compartido a los mejores destinos de Guatemala.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="transfers-stats">
                    <div class="stat-item">
                        <h3 class="mb-0"><?= $stats['total_routes'] ?></h3>
                        <p class="mb-0">Rutas Disponibles</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Filters Section -->
<section class="filters-section py-4 bg-light border-bottom">
    <div class="container">
        <form method="GET" action="<?= Config::getBaseUrl() ?>" class="row g-3">
            <input type="hidden" name="route" value="transfers">

            <div class="col-md-3">
                <label for="origen" class="form-label">Origen</label>
                <select class="form-select" id="origen" name="origen">
                    <option value="">Todos los orígenes</option>
                    <?php foreach ($origenes as $o): ?>
                        <option value="<?= htmlspecialchars($o['origen']) ?>" <?= $filters['origen'] == $o['origen'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($o['origen']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="destino" class="form-label">Destino</label>
                <select class="form-select" id="destino" name="destino">
                    <option value="">Todos los destinos</option>
                    <?php foreach ($destinos as $d): ?>
                        <option value="<?= htmlspecialchars($d['destino']) ?>" <?= $filters['destino'] == $d['destino'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['destino']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label for="max_precio" class="form-label">Precio Máx.</label>
                <input type="number" class="form-control" id="max_precio" name="max_precio"
                       value="<?= $filters['max_precio'] ?>" placeholder="USD" min="0" step="10">
            </div>

            <div class="col-md-2">
                <label for="sort" class="form-label">Ordenar por</label>
                <select class="form-select" id="sort" name="sort">
                    <option value="precio_asc" <?= $filters['sort'] == 'precio_asc' ? 'selected' : '' ?>>Precio: Menor a Mayor</option>
                    <option value="precio_desc" <?= $filters['sort'] == 'precio_desc' ? 'selected' : '' ?>>Precio: Mayor a Menor</option>
                    <option value="distancia_asc" <?= $filters['sort'] == 'distancia_asc' ? 'selected' : '' ?>>Distancia: Corta</option>
                    <option value="distancia_desc" <?= $filters['sort'] == 'distancia_desc' ? 'selected' : '' ?>>Distancia: Larga</option>
                    <option value="nombre" <?= $filters['sort'] == 'nombre' ? 'selected' : '' ?>>Nombre A-Z</option>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Buscar
                </button>
            </div>
        </form>

        <?php if ($filters['origen'] || $filters['destino'] || $filters['max_precio']): ?>
            <div class="mt-3">
                <a href="<?= Config::getBaseUrl() ?>?route=transfers" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times me-1"></i>Limpiar filtros
                </a>
                <span class="text-muted ms-2">Mostrando <?= count($routes) ?> resultado(s)</span>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Routes List -->
<section class="routes-section py-5">
    <div class="container">
        <?php if (!empty($routes)): ?>
            <div class="row g-4">
                <?php foreach ($routes as $route): ?>
                    <div class="col-lg-6">
                        <div class="transfer-card h-100">
                            <?php if (!empty($route['imagen'])): ?>
                                <div class="transfer-card-image">
                                    <img src="<?= Helpers::asset($route['imagen']) ?>"
                                         alt="<?= htmlspecialchars($route['nombre']) ?>"
                                         class="img-fluid"
                                         style="width: 100%; height: 200px; object-fit: cover; border-radius: 16px 16px 0 0;"
                                         loading="lazy"
                                         decoding="async"
                                         onerror="this.parentElement.style.display='none'">
                                </div>
                            <?php else: ?>
                                <div class="transfer-card-image bg-gradient" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); height: 200px; display: flex; align-items: center; justify-content: center; border-radius: 16px 16px 0 0;">
                                    <i class="fas fa-bus fa-4x text-white opacity-50"></i>
                                </div>
                            <?php endif; ?>
                            <div class="transfer-card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <h5 class="fw-bold mb-0"><?= htmlspecialchars($route['nombre']) ?></h5>
                                    <?php if ($route['precio']): ?>
                                        <span class="badge bg-primary fs-6">$<?= number_format($route['precio'], 2) ?> USD</span>
                                    <?php endif; ?>
                                </div>

                                <div class="transfer-route mb-3">
                                    <div class="route-point">
                                        <i class="fas fa-map-marker-alt text-success"></i>
                                        <span class="fw-bold"><?= htmlspecialchars($route['origen']) ?></span>
                                    </div>
                                    <div class="route-line">
                                        <i class="fas fa-arrow-down text-muted"></i>
                                    </div>
                                    <div class="route-point">
                                        <i class="fas fa-map-marker-alt text-danger"></i>
                                        <span class="fw-bold"><?= htmlspecialchars($route['destino']) ?></span>
                                    </div>
                                </div>

                                <?php if ($route['descripcion']): ?>
                                    <p class="text-muted small mb-3">
                                        <?= htmlspecialchars(Helpers::truncate($route['descripcion'], 120)) ?>
                                    </p>
                                <?php endif; ?>

                                <div class="transfer-info">
                                    <!-- Información del Conductor -->
                                    <?php if (!empty($route['conductor_nombre'])): ?>
                                        <div class="driver-info mb-3 p-2 bg-light rounded d-flex align-items-center">
                                            <?php if (!empty($route['conductor_foto'])): ?>
                                                <img src="<?= Config::getBaseUrl() ?>uploads/staff/<?= htmlspecialchars($route['conductor_foto']) ?>"
                                                     alt="<?= htmlspecialchars($route['conductor_nombre'] ?? 'Conductor') ?>"
                                                     class="rounded-circle me-2"
                                                     style="width: 40px; height: 40px; object-fit: cover;"
                                                     loading="lazy"
                                                     decoding="async"
                                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div class="rounded-circle me-2 bg-secondary d-none align-items-center justify-content-center"
                                                     style="width: 40px; height: 40px; min-width: 40px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="rounded-circle me-2 bg-secondary d-flex align-items-center justify-content-center"
                                                     style="width: 40px; height: 40px; min-width: 40px;">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <small class="text-muted d-block" style="font-size: 0.7rem;">Conductor</small>
                                                <strong style="font-size: 0.85rem;"><?= htmlspecialchars($route['conductor_nombre']) ?></strong>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <div class="row g-2 mb-3">
                                        <?php if ($route['distancia_km']): ?>
                                            <div class="col-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-road me-1"></i>
                                                    <?= number_format($route['distancia_km'], 1) ?> km
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($route['duracion_estimada']): ?>
                                            <div class="col-6">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= htmlspecialchars($route['duracion_estimada']) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($route['dias_operacion']): ?>
                                            <div class="col-12">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php
                                                    $dias = json_decode($route['dias_operacion'], true);
                                                    if (is_array($dias) && !empty($dias)) {
                                                        $diasMap = [
                                                            'lunes' => 'Lun',
                                                            'martes' => 'Mar',
                                                            'miercoles' => 'Mié',
                                                            'jueves' => 'Jue',
                                                            'viernes' => 'Vie',
                                                            'sabado' => 'Sáb',
                                                            'domingo' => 'Dom'
                                                        ];
                                                        $diasAbreviados = array_map(function($dia) use ($diasMap) {
                                                            return $diasMap[strtolower($dia)] ?? $dia;
                                                        }, $dias);
                                                        echo implode(', ', $diasAbreviados);
                                                    } else {
                                                        echo htmlspecialchars($route['dias_operacion']);
                                                    }
                                                    ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <a href="<?= Config::getBaseUrl() ?>?route=transfer/<?= $route['id'] ?>"
                                           class="btn btn-outline-primary flex-grow-1">
                                            <i class="fas fa-info-circle me-2"></i>Ver Detalles
                                        </a>
                                        <button class="btn btn-primary flex-grow-1" onclick="openQuoteModal(<?= $route['id'] ?>, '<?= htmlspecialchars($route['nombre'], ENT_QUOTES) ?>')">
                                            <i class="fas fa-envelope me-2"></i>Cotizar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-bus fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No se encontraron traslados</h4>
                <p class="text-muted">Intenta con otros filtros de búsqueda</p>
                <a href="<?= Config::getBaseUrl() ?>?route=transfers" class="btn btn-primary mt-3">
                    Ver todos los traslados
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Quote Modal -->
<div class="modal fade" id="quoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Solicitar Cotización</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="quoteForm">
                    <input type="hidden" id="quoteRouteId" name="route_id">
                    <div class="mb-3">
                        <label class="form-label fw-bold" id="quoteRouteName"></label>
                    </div>
                    <div class="mb-3">
                        <label for="quoteName" class="form-label">Nombre completo *</label>
                        <input type="text" class="form-control" id="quoteName" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="quoteEmail" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="quoteEmail" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="quotePhone" class="form-label">Teléfono</label>
                        <input type="tel" class="form-control" id="quotePhone" name="telefono">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="quoteFecha" class="form-label">Fecha de viaje</label>
                            <input type="date" class="form-control" id="quoteFecha" name="fecha">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="quotePersonas" class="form-label">Personas</label>
                            <input type="number" class="form-control" id="quotePersonas" name="personas" min="1" value="1">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="quoteComentarios" class="form-label">Comentarios adicionales</label>
                        <textarea class="form-control" id="quoteComentarios" name="comentarios" rows="3"></textarea>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Enviar Cotización
                        </button>
                    </div>
                </form>
                <div id="quoteResult" class="mt-3" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
let quoteModal;

document.addEventListener('DOMContentLoaded', function() {
    const quoteModalEl = document.getElementById('quoteModal');
    if (quoteModalEl && quoteModalEl.parentElement !== document.body) {
        document.body.appendChild(quoteModalEl);
    }
    quoteModal = new bootstrap.Modal(quoteModalEl);

    document.getElementById('quoteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const routeId = document.getElementById('quoteRouteId').value;

        fetch('<?= Config::getBaseUrl() ?>?route=transfer/quote/' + routeId, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('quoteResult');
            resultDiv.style.display = 'block';
            if (data.success) {
                resultDiv.className = 'alert alert-success';
                resultDiv.textContent = data.message;
                document.getElementById('quoteForm').reset();
                setTimeout(() => quoteModal.hide(), 2000);
            } else {
                resultDiv.className = 'alert alert-danger';
                resultDiv.textContent = data.message;
            }
        });
    });
});

function openQuoteModal(routeId, routeName) {
    document.getElementById('quoteRouteId').value = routeId;
    document.getElementById('quoteRouteName').textContent = routeName;
    document.getElementById('quoteResult').style.display = 'none';
    quoteModal.show();
}
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
