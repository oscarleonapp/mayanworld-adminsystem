<?php
use App\Core\Config;
use App\Core\Helpers;
include __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <?php 
      $actionTitle = 'Gestión de Transporte';
      $actionSubtitle = 'Administra vehículos y transportes para rutas turísticas';
      $actionButtons = [
        ['label' => 'Exportar', 'icon' => 'fas fa-download', 'variant' => 'outline-secondary', 'onclick' => 'exportTransports()'],
        ['label' => 'Nuevo Transporte', 'icon' => 'fas fa-plus', 'variant' => 'primary', 'onclick' => "new bootstrap.Modal(document.getElementById('addTransportModal')).show()"],
      ];
      include __DIR__ . '/../partials/admin_action_bar.php';
    ?>

    <!-- Search and Filter Bar -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 admin-filters" id="search-form">
                <input type="hidden" name="route" value="admin/transport">
                
                <div class="col-md-4">
                    <label for="search" class="form-label">Buscar transporte</label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" 
                               class="form-control" 
                               name="search" 
                               id="search"
                               placeholder="Nombre del vehículo..." 
                               value="<?= htmlspecialchars($search ?? '') ?>">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label for="type" class="form-label">Tipo de transporte</label>
                    <select class="form-select" name="type" id="type">
                        <option value="">Todos los tipos</option>
                        <?php foreach ($transport_types as $key => $label): ?>
                            <option value="<?= $key ?>" <?= ($type_filter === $key) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="status" class="form-label">Estado</label>
                    <select class="form-select" name="status" id="status">
                        <option value="">Todos</option>
                        <option value="1" <?= ($status_filter === '1') ? 'selected' : '' ?>>Activo</option>
                        <option value="0" <?= ($status_filter === '0') ? 'selected' : '' ?>>Inactivo</option>
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

    <!-- Transports Table -->
    <div class="card shadow">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    Lista de Transportes (<?= number_format($pagination['total']) ?>)
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
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="bulkAction('delete'); return false;">
                                <i class="fas fa-trash me-2"></i>Eliminar seleccionados
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <?php if (!empty($transports)): ?>
            <div class="table-responsive">
                <table class="table table-hover table-sticky align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th>Nombre</th>
                            <th width="120">Tipo</th>
                            <th width="100">Capacidad</th>
                            <th>Comodidades</th>
                            <th width="100">Estado</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transports as $transport): ?>
                        <tr>
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input transport-checkbox" 
                                           type="checkbox" 
                                           value="<?= $transport['id'] ?>">
                                </div>
                            </td>
                            <td>
                                <div>
                                    <h6 class="mb-1">
                                        <?= htmlspecialchars($transport['nombre']) ?>
                                    </h6>
                                    <small class="text-muted">#<?= $transport['id'] ?></small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    <?= htmlspecialchars($transport['tipo_formatted']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <strong><?= $transport['capacidad'] ?></strong> personas
                            </td>
                            <td>
                                <?php if (!empty($transport['comodidades_text'])): ?>
                                    <small class="text-muted">
                                        <?= htmlspecialchars(Helpers::truncate($transport['comodidades_text'], 50)) ?>
                                    </small>
                                <?php else: ?>
                                    <small class="text-muted">Sin especificar</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="status-<?= $transport['id'] ?>"
                                           <?= $transport['activo'] ? 'checked' : '' ?>
                                           onchange="toggleTransportStatus(<?= $transport['id'] ?>, this.checked)">
                                    <label class="form-check-label small" for="status-<?= $transport['id'] ?>">
                                        <?= $transport['status_text'] ?>
                                    </label>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary"
                                            onclick="editTransport(<?= $transport['id'] ?>)"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger"
                                            onclick="deleteTransport(<?= $transport['id'] ?>, '<?= htmlspecialchars($transport['nombre'], ENT_QUOTES) ?>')"
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
            
            <?php else: ?>
            <!-- No Transports Found -->
            <div class="text-center py-5">
                <i class="fas fa-bus fa-4x text-muted mb-4"></i>
                <h4 class="text-muted mb-3">No se encontraron transportes</h4>
                <p class="text-muted mb-4">
                    <?php if (!empty($search)): ?>
                        No hay transportes que coincidan con tu búsqueda.
                    <?php else: ?>
                        Aún no has registrado ningún transporte.
                    <?php endif; ?>
                </p>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransportModal">
                    <i class="fas fa-plus me-2"></i>Registrar Primer Transporte
                </button>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($transports) && $pagination['total_pages'] > 1): ?>
        <!-- Pagination -->
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Mostrando <?= count($transports) ?> de <?= number_format($pagination['total']) ?> transportes
                </div>
                
                <nav aria-label="Navegación de transportes">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($pagination['current_page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/transport&page=<?= $pagination['current_page'] - 1 ?>">
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
                            <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/transport&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/transport&page=<?= $pagination['current_page'] + 1 ?>">
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

<!-- Add Transport Modal -->
<div class="modal fade" id="addTransportModal" tabindex="-1" aria-labelledby="addTransportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTransportModalLabel">
                    <i class="fas fa-plus me-2"></i>Nuevo Transporte
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addTransportForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="nombre" class="form-label">Nombre del vehículo *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label for="tipo" class="form-label">Tipo de transporte *</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Seleccionar tipo...</option>
                                <?php foreach ($transport_types as $key => $label): ?>
                                    <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="capacidad" class="form-label">Capacidad (personas) *</label>
                            <input type="number" class="form-control" id="capacidad" name="capacidad" min="1" max="100" required>
                        </div>
                        <div class="col-md-6">
                            <label for="activo" class="form-label">Estado</label>
                            <select class="form-select" id="activo" name="activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="comodidades" class="form-label">Comodidades</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="comodidades[]" value="aire_acondicionado" id="ac">
                                        <label class="form-check-label" for="ac">Aire acondicionado</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="comodidades[]" value="wifi" id="wifi">
                                        <label class="form-check-label" for="wifi">Wi-Fi</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="comodidades[]" value="musica" id="musica">
                                        <label class="form-check-label" for="musica">Sistema de música</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="comodidades[]" value="asientos_comodos" id="asientos">
                                        <label class="form-check-label" for="asientos">Asientos cómodos</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="comodidades[]" value="ventanas_panoramicas" id="ventanas">
                                        <label class="form-check-label" for="ventanas">Ventanas panorámicas</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="comodidades[]" value="guia_turistico" id="guia">
                                        <label class="form-check-label" for="guia">Guía turístico</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="comodidades[]" value="agua_embotellada" id="agua">
                                        <label class="form-check-label" for="agua">Agua embotellada</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="comodidades[]" value="seguro_viaje" id="seguro">
                                        <label class="form-check-label" for="seguro">Seguro de viaje</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="comodidades[]" value="sanitarios" id="sanitarios">
                                        <label class="form-check-label" for="sanitarios">Sanitarios</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar Transporte
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom CSS moved to public/assets/css/admin.css -->

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all functionality
    const selectAll = document.getElementById('select-all');
    const transportCheckboxes = document.querySelectorAll('.transport-checkbox');
    const bulkActionsBtn = document.getElementById('bulk-actions-btn');
    
    selectAll?.addEventListener('change', function() {
        transportCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateBulkActionsButton();
    });
    
    transportCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateBulkActionsButton);
    });
    
    function updateBulkActionsButton() {
        const checkedBoxes = document.querySelectorAll('.transport-checkbox:checked');
        bulkActionsBtn.disabled = checkedBoxes.length === 0;
        
        // Update select all checkbox state
        if (selectAll) {
            selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < transportCheckboxes.length;
            selectAll.checked = checkedBoxes.length === transportCheckboxes.length && checkedBoxes.length > 0;
        }
    }
    
    // Add transport form
    const addTransportForm = document.getElementById('addTransportForm');
    addTransportForm?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        // Handle multiple checkbox values
        const comodidades = [];
        document.querySelectorAll('input[name="comodidades[]"]:checked').forEach(cb => {
            comodidades.push(cb.value);
        });
        formData.delete('comodidades[]');
        formData.append('comodidades', JSON.stringify(comodidades));
        
        fetch('<?= Config::getBaseUrl() ?>?route=admin/transport/add', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'No se pudo agregar el transporte'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al procesar la solicitud');
        });
    });
});

function toggleTransportStatus(transportId, isActive) {
    const formData = new FormData();
    formData.append('transport_id', transportId);
    formData.append('active', isActive ? 1 : 0);
    
    fetch('<?= Config::getBaseUrl() ?>?route=admin/transport/toggle-status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Error: ' + data.message);
            // Revert checkbox state
            const checkbox = document.getElementById(`status-${transportId}`);
            checkbox.checked = !isActive;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al actualizar el estado');
        // Revert checkbox state
        const checkbox = document.getElementById(`status-${transportId}`);
        checkbox.checked = !isActive;
    });
}

function editTransport(transportId) {
    // TODO: Implement edit transport modal/page
    window.location.href = `<?= Config::getBaseUrl() ?>?route=admin/transport/edit/${transportId}`;
}

function deleteTransport(transportId, transportName) {
    if (!confirm(`¿Estás seguro de eliminar el transporte "${transportName}"?\n\nEsta acción no se puede deshacer.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('transport_id', transportId);
    
    fetch('<?= Config::getBaseUrl() ?>?route=admin/transport/delete', {
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
        alert('Error al eliminar el transporte');
    });
}

function bulkAction(action) {
    const checkedBoxes = document.querySelectorAll('.transport-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Selecciona al menos un transporte');
        return;
    }
    
    const transportIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    let confirmMessage = '';
    switch (action) {
        case 'activate':
            confirmMessage = `¿Activar ${transportIds.length} transporte(s)?`;
            break;
        case 'deactivate':
            confirmMessage = `¿Desactivar ${transportIds.length} transporte(s)?`;
            break;
        case 'delete':
            confirmMessage = `¿ELIMINAR ${transportIds.length} transporte(s)?\n\nEsta acción no se puede deshacer.`;
            break;
    }
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', action);
    formData.append('transport_ids', JSON.stringify(transportIds));
    
    fetch('<?= Config::getBaseUrl() ?>?route=admin/transport/bulk-action', {
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

function exportTransports() {
    const url = '<?= Config::getBaseUrl() ?>?route=admin/transport/export';
    if (typeof AdminDownload === 'function') {
        AdminDownload(url, {
            filenameFallback: 'transportes.csv',
            startMessage: 'Generando CSV...',
            errorMessage: 'Error al exportar transportes'
        });
    }
}
</script>

<?php include __DIR__ . '/../layouts/admin_footer.php'; ?>
