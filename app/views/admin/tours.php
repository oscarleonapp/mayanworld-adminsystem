<?php
use App\Core\Config;
use App\Core\Helpers;
include __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <?php 
      $actionTitle = 'Gestión de Tours';
      $actionSubtitle = 'Administra los destinos y experiencias de viaje';
      $unverifiedCount = $unverified_count ?? array_sum(array_map(function($p){ return empty($p['verified']) ? 1 : 0; }, $products ?? []));
      $noImageCount = $no_image_count ?? array_sum(array_map(function($p){ return empty($p['imagen_principal']) ? 1 : 0; }, $products ?? []));
      $actionButtons = [
        ['label' => 'Exportar', 'icon' => 'fas fa-download', 'variant' => 'outline-secondary', 'onclick' => 'exportProducts()'],
        ['label' => 'No verificados', 'icon' => 'fas fa-badge-check', 'variant' => 'outline-warning', 'href' => Config::getBaseUrl() . '?route=admin/tours&verified=no', 'badge' => $unverifiedCount, 'badgeClass' => 'bg-warning text-dark'],
        ['label' => 'Sin imagen', 'icon' => 'fas fa-image', 'variant' => 'outline-secondary', 'href' => Config::getBaseUrl() . '?route=admin/tours&with_image=no', 'badge' => $noImageCount, 'badgeClass' => 'bg-secondary'],
        ['label' => 'Sin imagen (rápido)', 'icon' => 'fas fa-bolt', 'variant' => 'outline-secondary', 'onclick' => 'filterNoImageProducts()'],
        ['label' => 'Nuevo Tour', 'icon' => 'fas fa-plus', 'variant' => 'primary', 'href' => Config::getBaseUrl() . '?route=admin/tours/create'],
      ];
      include __DIR__ . '/../partials/admin_action_bar.php';
    ?>

    <!-- Search and Filter Bar -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 admin-filters" id="search-form">
                <input type="hidden" name="route" value="admin/tours">
                
                <div class="col-md-4">
                    <label for="search" class="form-label">Buscar tour</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               id="search"
                               placeholder="Nombre del tour..." 
                               value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label for="category" class="form-label">Categoría</label>
                    <select class="form-select" name="category" id="category">
                        <option value="">Todas</option>
                        <!-- Aquí se cargarían las categorías desde la base de datos -->
                        <option value="1">Playas</option>
                        <option value="2">Montaña</option>
                        <option value="3">Ciudad</option>
                        <option value="4">Aventura</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label">Estado</label>
                    <select class="form-select" name="status" id="status">
                        <option value="">Todos</option>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="featured" class="form-label">Destacado</label>
                    <select class="form-select" name="featured" id="featured">
                        <option value="">Todos</option>
                        <option value="1">Sí</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="verified" class="form-label">Verificado</label>
                    <select class="form-select" name="verified" id="verified">
                        <option value="" <?= empty($verified_filter) ? 'selected' : '' ?>>Todos</option>
                        <option value="yes" <?= ($verified_filter ?? '') === 'yes' ? 'selected' : '' ?>>Sí</option>
                        <option value="no" <?= ($verified_filter ?? '') === 'no' ? 'selected' : '' ?>>No</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="with_image" class="form-label">Imagen principal</label>
                    <select class="form-select" name="with_image" id="with_image">
                        <option value="" <?= empty($with_image_filter) ? 'selected' : '' ?>>Todas</option>
                        <option value="yes" <?= ($with_image_filter ?? '') === 'yes' ? 'selected' : '' ?>>Con imagen</option>
                        <option value="no" <?= ($with_image_filter ?? '') === 'no' ? 'selected' : '' ?>>Sin imagen</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Buscar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card shadow">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    Lista de Tours (<?= number_format($pagination['total']) ?>)
                </h6>
                <div class="d-flex gap-2">
                    <!-- Bulk Actions -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" 
                                data-bs-toggle="dropdown"
                                id="bulk-actions-btn"
                                disabled>
                            Acciones en lote
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" onclick="bulkAction('activate'); return false;">
                                <i class="fas fa-toggle-on me-2 text-success"></i>Activar seleccionados
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="bulkAction('deactivate'); return false;">
                                <i class="fas fa-toggle-off me-2 text-warning"></i>Desactivar seleccionados
                            </a></li>
                            <li><a class="dropdown-item" href="#" onclick="bulkAction('feature'); return false;">
                                <i class="fas fa-star me-2 text-info"></i>Marcar como destacado
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="bulkAction('delete'); return false;">
                                <i class="fas fa-trash me-2"></i>Eliminar seleccionados
                            </a></li>
                        </ul>
                    </div>
                    
                    <!-- View Toggle -->
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="view-mode" id="table-view" checked>
                        <label class="btn btn-outline-secondary btn-sm" for="table-view" aria-label="Vista de lista" title="Vista de lista">
                            <i class="fas fa-list"></i>
                        </label>
                        <input type="radio" class="btn-check" name="view-mode" id="card-view">
                        <label class="btn btn-outline-secondary btn-sm" for="card-view" aria-label="Vista de tarjetas" title="Vista de tarjetas">
                            <i class="fas fa-th-large"></i>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <?php if (!empty($products)): ?>
            
            <!-- Table View -->
            <div id="table-view-content">
                <div class="table-responsive">
                    <table class="table table-hover table-sticky mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="select-all">
                                    </div>
                                </th>
                                <th width="80">Imagen</th>
                                <th>Tour</th>
                                <th width="120">Precio</th>
                                <th width="100">Estado</th>
                                <th width="120">Destacado</th>
                                <th width="120">Verificado</th>
                                <th width="120">Reservas</th>
                                <th width="100">Fecha</th>
                                <th width="120">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr class="product-row <?= !empty($product['verified']) ? 'is-verified' : 'is-unverified' ?> <?= !empty($product['imagen_principal']) ? 'has-image' : 'no-image' ?>">
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input product-checkbox" 
                                               type="checkbox" 
                                               value="<?= $product['id'] ?>">
                                    </div>
                                </td>
                                <td>
                                    <?php if ($product['imagen_principal']): ?>
                                    <img src="<?= htmlspecialchars($product['imagen_principal']) ?>" 
                                         alt="<?= htmlspecialchars($product['nombre']) ?>"
                                         class="rounded product-thumb thumb-60x45 skeleton"
                                         loading="lazy" decoding="async">
                                    <?php else: ?>
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center thumb-60x45">
                                        <i class="fas fa-image text-muted"></i>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div>
                                        <h6 class="mb-1">
                                            <a href="<?= Config::getBaseUrl() ?>?route=admin/tours/edit/<?= $product['id'] ?>" 
                                               class="text-decoration-none">
                                                <?= htmlspecialchars($product['nombre']) ?>
                                            </a>
                                        </h6>
                                        <div class="small text-muted">
                                            <i class="fas fa-tag me-1"></i>
                                            <?= htmlspecialchars($product['categoria_nombre'] ?? 'Sin categoría') ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-clock me-1"></i>
                                            <?= htmlspecialchars($product['duracion_dias'] ?? 'N/D') ?> días
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <?php if (isset($product['precio_nino']) && $product['precio_nino'] > 0): ?>
                                            <div class="fw-bold">
                                                <?= Helpers::formatPrice($product['precio']) ?>
                                            </div>
                                            <div class="text-muted small">
                                                Niños: <?= Helpers::formatPrice($product['precio_nino']) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="fw-bold">
                                                <?= Helpers::formatPrice($product['precio']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input"
                                               type="checkbox"
                                               id="status-<?= $product['id'] ?>"
                                               <?= $product['activo'] ? 'checked' : '' ?>
                                               onchange="toggleProductStatus(<?= $product['id'] ?>, this.checked)">
                                        <label class="form-check-label small" for="status-<?= $product['id'] ?>">
                                            <?= $product['activo'] ? 'Activo' : 'Inactivo' ?>
                                        </label>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-<?= $product['destacado'] ? 'warning' : 'outline-secondary' ?> btn-sm"
                                            onclick="toggleFeatured(<?= $product['id'] ?>, <?= $product['destacado'] ? 'false' : 'true' ?>)"
                                            title="<?= $product['destacado'] ? 'Quitar de destacados' : 'Marcar como destacado' ?>">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-<?= !empty($product['verified']) ? 'success' : 'outline-secondary' ?> btn-sm"
                                            onclick="toggleVerified(<?= $product['id'] ?>, <?= !empty($product['verified']) ? 'false' : 'true' ?>)"
                                            title="<?= !empty($product['verified']) ? 'Quitar verificado' : 'Marcar como verificado' ?>">
                                        <i class="fas fa-badge-check"></i>
                                    </button>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info">
                                        <?= $product['total_reservas'] ?? 0 ?>
                                    </span>
                                </td>
                                <td class="small text-muted">
                                    <?= date('d/m/Y', strtotime($product['created_at'])) ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= Config::getBaseUrl() ?>?route=tour/<?= $product['id'] ?>" 
                                           class="btn btn-outline-info" 
                                           title="Ver tour" 
                                           target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?= Config::getBaseUrl() ?>?route=admin/tours/edit/<?= $product['id'] ?>" 
                                           class="btn btn-outline-primary" 
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-outline-success"
                                                title="Ubicación"
                                                onclick="openCoordsModal(<?= (int)$product['id'] ?>, '<?= htmlspecialchars($product['nombre'], ENT_QUOTES) ?>', '<?= htmlspecialchars($product['latitud'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($product['longitud'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($product['ubicacion'] ?? '', ENT_QUOTES) ?>')">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </button>
                                        <button class="btn btn-outline-danger" 
                                                onclick="deleteProduct(<?= $product['id'] ?>, '<?= htmlspecialchars($product['nombre'], ENT_QUOTES) ?>')" 
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Card View (Hidden by default) -->
            <div id="card-view-content" class="d-none p-3">
                <div class="row">
                    <?php foreach ($products as $product): ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                        <div class="card product-card h-100">
                            <div class="position-relative">
                                <?php if ($product['imagen_principal']): ?>
                                <img src="<?= htmlspecialchars($product['imagen_principal']) ?>" 
                                     class="card-img-top skeleton img-200-cover" 
                                     alt="<?= htmlspecialchars($product['nombre']) ?>"
                                     loading="lazy" decoding="async">
                                <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center img-200">
                                     <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Status Badges -->
                                <div class="position-absolute top-0 start-0 p-2">
                                    <?php if (!$product['activo']): ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                    <?php endif; ?>
                                    <?php if ($product['destacado']): ?>
                                    <span class="badge bg-warning">Destacado</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Quick Actions -->
                                <div class="position-absolute top-0 end-0 p-2">
                                    <div class="form-check">
                                        <input class="form-check-input product-checkbox" 
                                               type="checkbox" 
                                               value="<?= $product['id'] ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <h6 class="card-title">
                                    <?= htmlspecialchars($product['nombre']) ?>
                                </h6>
                                <p class="card-text small text-muted">
                                    <?= htmlspecialchars(Helpers::truncate($product['descripcion_corta'], 80)) ?>
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <?php if (isset($product['precio_nino']) && $product['precio_nino'] > 0): ?>
                                            <div class="fw-bold">
                                                <?= Helpers::formatPrice($product['precio']) ?>
                                            </div>
                                            <div class="text-muted small">
                                                Niños: <?= Helpers::formatPrice($product['precio_nino']) ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="fw-bold">
                                                <?= Helpers::formatPrice($product['precio']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-info">
                                        <?= $product['total_reservas'] ?? 0 ?> reservas
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-footer bg-white border-top-0">
                                <div class="d-grid gap-2">
                                    <div class="btn-group">
                                        <a href="<?= Config::getBaseUrl() ?>?route=admin/tours/edit/<?= $product['id'] ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit me-1"></i>Editar
                                        </a>
                                        <a href="<?= Config::getBaseUrl() ?>?route=tour/<?= $product['id'] ?>" 
                                           class="btn btn-outline-info btn-sm" 
                                           target="_blank">
                                            <i class="fas fa-eye me-1"></i>Ver
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php else: ?>
            <!-- No Products Found -->
            <div class="text-center py-5">
                <i class="fas fa-map-marked-alt fa-4x text-muted mb-4"></i>
                <h4 class="text-muted mb-3">No se encontraron tours</h4>
                <p class="text-muted mb-4">
                    <?php if (!empty($search)): ?>
                        No hay tours que coincidan con tu búsqueda.
                    <?php else: ?>
                        Aún no has creado ningún tour.
                    <?php endif; ?>
                </p>
                <a href="<?= Config::getBaseUrl() ?>?route=admin/tours/create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Crear Primer Tour
                </a>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($products) && $pagination['total_pages'] > 1): ?>
        <!-- Pagination -->
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Mostrando <?= count($products) ?> de <?= number_format($pagination['total']) ?> tours
                </div>
                
                <nav aria-label="Navegación de tours">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($pagination['current_page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/tours&page=<?= $pagination['current_page'] - 1 ?>">
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
                            <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/tours&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/tours&page=<?= $pagination['current_page'] + 1 ?>">
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

<!-- Modal Coordenadas -->
<div class="modal fade" id="coordsModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-map-marker-alt me-2"></i> Coordenadas del tour</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="coordsForm">
          <input type="hidden" name="id" id="coords_id">
          <input type="hidden" name="address" id="coords_address_value">
          <div class="mb-3">
            <label class="form-label">Tour</label>
            <input type="text" id="coords_name" class="form-control" disabled>
          </div>
          <div class="mb-3">
            <label class="form-label">Buscar dirección</label>
            <div class="input-group">
              <span class="input-group-text"><i class="fas fa-search"></i></span>
              <input type="text" id="coords_query" class="form-control" placeholder="Dirección o lugar">
              <button type="button" class="btn btn-outline-primary" onclick="geocodeAddress()">Buscar</button>
            </div>
            <ul class="list-group mt-2" id="coords_results"></ul>
          </div>
          <div class="mb-3">
            <label class="form-label">Mapa interactivo</label>
            <div id="coords_map" class="map-embed h-250"></div>
            <small class="text-muted d-block mt-1">Haz clic en el mapa para fijar la ubicación.</small>
          </div>
          <div class="mb-3">
            <label class="form-label">
              <i class="fas fa-map-marker-alt me-1 text-success"></i>
              Dirección seleccionada
            </label>
            <div id="coords_address" class="small p-2 rounded border" style="min-height: 40px; background-color: #f8f9fa;"></div>
          </div>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Latitud</label>
              <input type="number" step="0.000001" name="lat" id="coords_lat" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Longitud</label>
              <input type="number" step="0.000001" name="lng" id="coords_lng" class="form-control" required>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="saveCoords()">Guardar</button>
      </div>
    </div>
  </div>
  </div>

<script>
function openCoordsModal(id, name, lat, lng, address){
  document.getElementById('coords_id').value = id;
  document.getElementById('coords_name').value = name;
  document.getElementById('coords_lat').value = lat || '';
  document.getElementById('coords_lng').value = lng || '';

  // Cargar dirección si existe
  const addrValue = document.getElementById('coords_address_value');
  const addrDisplay = document.getElementById('coords_address');
  if (address && address.trim()) {
    if (addrValue) addrValue.value = address;
    if (addrDisplay) {
      addrDisplay.textContent = address;
      addrDisplay.classList.add('text-success');
      addrDisplay.classList.remove('text-muted');
    }
  } else {
    if (addrValue) addrValue.value = '';
    if (addrDisplay) {
      addrDisplay.textContent = 'Selecciona una dirección de la búsqueda o haz clic en el mapa';
      addrDisplay.classList.add('text-muted');
      addrDisplay.classList.remove('text-success');
    }
  }

  const m = new bootstrap.Modal(document.getElementById('coordsModal'));
  m.show();
  setTimeout(initCoordsMap, 200);
}
async function saveCoords(){
  const id = document.getElementById('coords_id').value;
  const lat = document.getElementById('coords_lat').value;
  const lng = document.getElementById('coords_lng').value;
  const address = document.getElementById('coords_address_value').value;

  // Validar que haya coordenadas
  if (!lat || !lng) {
    alert('⚠️ Por favor selecciona una ubicación en el mapa o ingresa coordenadas manualmente.');
    return;
  }

  // Mostrar indicador de carga
  const saveBtn = event.target;
  const originalText = saveBtn.innerHTML;
  saveBtn.disabled = true;
  saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';

  try {
    const fd = new FormData();
    fd.append('id', id);
    fd.append('lat', lat);
    fd.append('lng', lng);
    if (address) {
      fd.append('address', address);
    }

    console.log('Enviando coordenadas:', { id, lat, lng, address });

    const res = await fetch('<?= Config::getBaseUrl() ?>?route=admin/tours/set-coords', {
      method: 'POST',
      body: fd
    });

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}: ${res.statusText}`);
    }

    const json = await res.json();
    console.log('Respuesta del servidor:', json);

    if (json.success) {
      // Mostrar mensaje de éxito
      const savedAddress = address ? ' con dirección' : '';
      saveBtn.innerHTML = `<i class="fas fa-check me-2"></i>¡Guardado${savedAddress}!`;
      saveBtn.classList.remove('btn-primary');
      saveBtn.classList.add('btn-success');

      // Recargar después de un momento
      setTimeout(() => {
        location.reload();
      }, 800);
    } else {
      throw new Error(json.message || 'Error desconocido al guardar');
    }
  } catch (error) {
    console.error('Error al guardar coordenadas:', error);
    alert('❌ Error al guardar coordenadas:\n' + error.message);

    // Restaurar botón
    saveBtn.disabled = false;
    saveBtn.innerHTML = originalText;
  }
}

// Geocoding + Mapa (Leaflet + Nominatim)
let __coordsMap, __coordsMarker;
async function ensureLeaflet(){
  if (window.L) return true;
  const css1 = document.createElement('link');
  css1.rel='stylesheet'; css1.href='https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
  document.head.appendChild(css1);
  await new Promise(r=>setTimeout(r,50));
  await new Promise((resolve)=>{
    const s=document.createElement('script'); s.src='https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    s.onload=resolve; document.body.appendChild(s);
  });
  return true;
}
async function initCoordsMap(){
  await ensureLeaflet();
  const mapEl = document.getElementById('coords_map');
  if (!mapEl) return;
  if (!__coordsMap){
    __coordsMap = L.map('coords_map');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(__coordsMap);
    __coordsMap.setView([19.4326,-99.1332], 5);
    __coordsMap.on('click', function(e){
      setLatLngFields(e.latlng.lat, e.latlng.lng);
      placeMarker(e.latlng.lat, e.latlng.lng);
    });
  }
  // Center to current
  const lat = parseFloat(document.getElementById('coords_lat').value);
  const lng = parseFloat(document.getElementById('coords_lng').value);
  if (!isNaN(lat) && !isNaN(lng)){
    __coordsMap.setView([lat,lng], 12);
    placeMarker(lat,lng);
    reverseGeocode(lat,lng);
  }
}
function placeMarker(lat,lng){
  if (!__coordsMap) return;
  if (!__coordsMarker){ __coordsMarker = L.marker([lat,lng]).addTo(__coordsMap); }
  __coordsMarker.setLatLng([lat,lng]);
}
function setLatLngFields(lat,lng){
  const latInput = document.getElementById('coords_lat');
  const lngInput = document.getElementById('coords_lng');

  latInput.value = Number(lat).toFixed(6);
  lngInput.value = Number(lng).toFixed(6);

  // Feedback visual: flash verde para mostrar que cambió
  [latInput, lngInput].forEach(input => {
    input.style.transition = 'background-color 0.3s';
    input.style.backgroundColor = '#d4edda';
    setTimeout(() => {
      input.style.backgroundColor = '';
    }, 1000);
  });

  console.log('Coordenadas establecidas:', { lat: latInput.value, lng: lngInput.value });
}
let __geoLastToken = 0; // para descartar respuestas viejas
async function geocodeAddress(){
  const q = (document.getElementById('coords_query').value||'').trim();
  const list = document.getElementById('coords_results');
  list.innerHTML='';
  if (!q) { list.style.display='none'; return; }
  list.style.display='block';
  list.innerHTML = '<li class="list-group-item text-muted"><i class="fas fa-spinner fa-spin me-2"></i>Buscando…</li>';
  const token = Date.now();
  __geoLastToken = token;
  const url = `<?= Config::getBaseUrl() ?>?route=admin/tours/geocode&q=${encodeURIComponent(q)}`;
  try{
    const res = await fetch(url);
    if (!res.ok) {
      list.innerHTML = '<li class="list-group-item text-danger">Servicio de geocodificación temporalmente no disponible. Intenta más tarde.</li>';
      return;
    }
    const response = await res.json();
    if (!response.success) {
      list.innerHTML = '<li class="list-group-item text-danger">Error en la búsqueda</li>';
      return;
    }
    const data = response.results;
    if (__geoLastToken !== token) return; // respuesta vieja
    list.innerHTML='';
    if (!Array.isArray(data) || data.length===0){
      list.innerHTML = '<li class="list-group-item text-muted">Sin resultados</li>';
      return;
    }
    data.forEach(item=>{
      const li = document.createElement('li');
      li.className='list-group-item list-group-item-action';
      li.style.cursor='pointer';
      li.textContent = `${item.display_name}`;
      li.onclick = ()=>{
        const lat = parseFloat(item.lat), lng = parseFloat(item.lon);
        setLatLngFields(lat,lng);
        placeMarker(lat,lng);
        __coordsMap.setView([lat,lng], 13);

        // Guardar la dirección en el campo oculto
        const addrValue = document.getElementById('coords_address_value');
        if (addrValue) addrValue.value = item.display_name;

        // Mostrar la dirección en el display
        const addr = document.getElementById('coords_address');
        if (addr) {
          addr.textContent = item.display_name;
          addr.classList.add('text-success');
          addr.classList.remove('text-muted');
        }

        list.style.display='none';
      };
      list.appendChild(li);
    });
  }catch(err){
    console.error(err);
    list.innerHTML = '<li class="list-group-item text-danger">Error de conexión. Revisa tu red e intenta de nuevo.</li>';
  }
}

// Debounce para input de búsqueda (rate limiting client-side)
let __geoDebounce;
document.getElementById('coords_query')?.addEventListener('input', function(){
  clearTimeout(__geoDebounce);
  __geoDebounce = setTimeout(geocodeAddress, 500);
});

async function reverseGeocode(lat,lng){
  // Reverse geocoding deshabilitado para evitar CORS
  // La funcionalidad principal (establecer coordenadas) funciona sin esto
  const addr = document.getElementById('coords_address');
  if (addr) addr.textContent = `Lat: ${lat}, Lng: ${lng}`;
}
</script>

<!-- Custom CSS moved to public/assets/css/admin.css -->

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // View toggle functionality
    const tableView = document.getElementById('table-view');
    const cardView = document.getElementById('card-view');
    const tableContent = document.getElementById('table-view-content');
    const cardContent = document.getElementById('card-view-content');
    
    tableView.addEventListener('change', function() {
        if (this.checked) {
            tableContent.classList.remove('d-none');
            cardContent.classList.add('d-none');
        }
    });
    
    cardView.addEventListener('change', function() {
        if (this.checked) {
            cardContent.classList.remove('d-none');
            tableContent.classList.add('d-none');
        }
    });
    
    // Select all functionality
    const selectAll = document.getElementById('select-all');
    const productCheckboxes = document.querySelectorAll('.product-checkbox');
    const bulkActionsBtn = document.getElementById('bulk-actions-btn');
    
    selectAll?.addEventListener('change', function() {
        productCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActionsButton();
    });
    
    productCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionsButton);
    });
    
    function updateBulkActionsButton() {
        const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
        bulkActionsBtn.disabled = checkedBoxes.length === 0;
        
        // Update select all checkbox state
        if (selectAll) {
            selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < productCheckboxes.length;
            selectAll.checked = checkedBoxes.length === productCheckboxes.length && checkedBoxes.length > 0;
        }
    }
    
    // Client-side filter: unverified products
    window.__showOnlyUnverified = false;
    window.filterUnverifiedProducts = function(){
        const rows = document.querySelectorAll('.product-row');
        window.__showOnlyUnverified = !window.__showOnlyUnverified;
        rows.forEach(r => {
            if (window.__showOnlyUnverified) {
                r.style.display = r.classList.contains('is-unverified') ? '' : 'none';
            } else {
                r.style.display = '';
            }
        });
        if (window.AdminUI){
            AdminUI.toast(window.__showOnlyUnverified ? 'Mostrando solo no verificados' : 'Mostrando todos', 'primary');
        }
    }

    // Client-side filter: products without main image
    window.__showOnlyNoImage = false;
    window.filterNoImageProducts = function(){
        const rows = document.querySelectorAll('.product-row');
        window.__showOnlyNoImage = !window.__showOnlyNoImage;
        rows.forEach(r => {
            if (window.__showOnlyNoImage) {
                r.style.display = r.classList.contains('no-image') ? '' : 'none';
            } else {
                r.style.display = '';
            }
        });
        if (window.AdminUI){
            AdminUI.toast(window.__showOnlyNoImage ? 'Mostrando solo sin imagen' : 'Mostrando todos', 'primary');
        }
    }
});

function toggleProductStatus(productId, isActive) {
    const formData = new FormData();
    formData.append('tour_id', productId);
    formData.append('active', isActive ? 1 : 0);

    const checkbox = document.getElementById(`status-${productId}`);
    const label = checkbox.nextElementSibling;

    fetch('<?= Config::getBaseUrl() ?>?route=admin/tours/toggle-status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar el label
            if (label) {
                label.textContent = isActive ? 'Activo' : 'Inactivo';
            }
            // Opcional: mostrar mensaje de éxito
            // showToast('success', 'Estado actualizado correctamente');
        } else {
            alert('Error: ' + data.message);
            // Revertir el estado del checkbox
            checkbox.checked = !isActive;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar el estado');
        // Revertir el estado del checkbox
        checkbox.checked = !isActive;
    });
}

function toggleFeatured(productId, featured) {
    const formData = new FormData();
    formData.append('tour_id', productId);
    formData.append('featured', featured ? 1 : 0);

    // Encontrar el botón
    const button = event.target.closest('button');

    fetch('<?= Config::getBaseUrl() ?>?route=admin/tours/toggle-featured', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar el botón visualmente
            if (button) {
                if (data.featured) {
                    button.className = 'btn btn-warning btn-sm';
                    button.title = 'Quitar de destacados';
                    button.onclick = function() { toggleFeatured(productId, false); };
                } else {
                    button.className = 'btn btn-outline-secondary btn-sm';
                    button.title = 'Marcar como destacado';
                    button.onclick = function() { toggleFeatured(productId, true); };
                }
            }
            // Opcional: mostrar toast
            // if (window.AdminUI) AdminUI.toast(data.message, 'success');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar el estado destacado');
    });
}

function toggleVerified(productId, verified) {
    const formData = new FormData();
    formData.append('tour_id', productId);
    formData.append('verified', verified ? 1 : 0);
    fetch('<?= Config::getBaseUrl() ?>?route=admin/tours/toggle-verified', {
        method: 'POST',
        body: formData
    })
    .then(r=>r.json())
    .then(json=>{
        if (json.success) { location.reload(); } else { alert(json.message || 'Error al actualizar verificado'); }
    })
    .catch(()=>alert('Error de red al actualizar verificado'));
}

function deleteProduct(productId, productName) {
    if (!confirm(`¿Estás seguro de eliminar el tour "${productName}"?\n\nEsta acción no se puede deshacer.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('tour_id', productId);
    
    fetch('<?= Config::getBaseUrl() ?>?route=admin/tours/delete', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al eliminar el tour');
    });
}

function bulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.product-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Selecciona al menos un tour');
        return;
    }
    
    const productIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    let confirmMessage = '';
    switch (action) {
        case 'activate':
            confirmMessage = `¿Activar ${productIds.length} tour(s)?`;
            break;
        case 'deactivate':
            confirmMessage = `¿Desactivar ${productIds.length} tour(s)?`;
            break;
        case 'feature':
            confirmMessage = `¿Marcar ${productIds.length} tour(s) como destacado?`;
            break;
        case 'delete':
            confirmMessage = `¿ELIMINAR ${productIds.length} tour(s)?\n\nEsta acción no se puede deshacer.`;
            break;
    }
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', action);
    formData.append('product_ids', JSON.stringify(productIds));
    
    fetch('<?= Config::getBaseUrl() ?>?route=admin/tours/bulk-action', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar la acción');
    });
}

function exportProducts() {
    const url = '<?= Config::getBaseUrl() ?>?route=admin/tours/export';
    if (typeof AdminDownload === 'function') {
        AdminDownload(url, {
            filenameFallback: 'tours.csv',
            startMessage: 'Generando CSV...',
            errorMessage: 'Error al exportar tours'
        });
    }
}
</script>

<?php include __DIR__ . '/../layouts/admin_footer.php'; ?>
