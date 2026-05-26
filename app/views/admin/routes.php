<?php
use App\Core\Config;
include __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <?php 
              $actionTitle = 'Administrar Rutas de Bus';
              $actionSubtitle = null;
              $actionButtons = [
                ['label' => 'Agregar Ruta', 'icon' => 'fas fa-plus', 'variant' => 'primary', 'onclick' => "new bootstrap.Modal(document.getElementById('addRouteModal')).show()"],
              ];
              include __DIR__ . '/../partials/admin_action_bar.php';
            ?>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-route fa-2x mb-2"></i>
                            <h4><?= $stats['total_rutas'] ?? 0 ?></h4>
                            <small>Total Rutas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h4><?= $stats['rutas_activas'] ?? 0 ?></h4>
                            <small>Activas</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-map-marker-alt fa-2x mb-2"></i>
                            <h4><?= $stats['origenes_unicos'] ?? 0 ?></h4>
                            <small>Orígenes</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                            <h4>$<?= number_format($stats['precio_promedio'] ?? 0, 0) ?></h4>
                            <small>Precio Promedio</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 admin-filters">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="search" placeholder="Buscar ruta..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" name="origen" placeholder="Origen" value="<?= htmlspecialchars($_GET['origen'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <input type="text" class="form-control" name="destino" placeholder="Destino" value="<?= htmlspecialchars($_GET['destino'] ?? '') ?>">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="estado">
                                <option value="">Todos los estados</option>
                                <option value="activo" <?= ($_GET['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= ($_GET['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                <option value="mantenimiento" <?= ($_GET['estado'] ?? '') === 'mantenimiento' ? 'selected' : '' ?>>Mantenimiento</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="<?= Config::getBaseUrl() ?>?route=admin/routes" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de rutas -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sticky align-middle">
                            <thead>
                                <tr>
                                    <th>Ruta</th>
                                    <th>Origen → Destino</th>
                                    <th>Duración</th>
                                    <th>Precio</th>
                                    <th>Días Operación</th>
                                    <th>Transporte</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($routes)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-route fa-3x text-muted mb-3"></i>
                                        <h5>No hay rutas registradas</h5>
                                        <p class="text-muted">Agrega la primera ruta usando el botón de arriba</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($routes as $route): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($route['nombre']) ?></strong>
                                            <?php if ($route['distancia_km']): ?>
                                            <br><small class="text-muted"><?= number_format($route['distancia_km'], 1) ?> km</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="text-primary"><?= htmlspecialchars($route['origen']) ?></span>
                                            <i class="fas fa-arrow-right mx-2"></i>
                                            <span class="text-success"><?= htmlspecialchars($route['destino']) ?></span>
                                        </td>
                                        <td>
                                            <small><?= htmlspecialchars($route['duracion_estimada']) ?></small>
                                        </td>
                                        <td>
                                            <strong class="text-success">$<?= number_format($route['precio'], 0) ?></strong>
                                        </td>
                                        <td>
                                            <?php 
                                            $dias = json_decode($route['dias_operacion'] ?? '[]', true);
                                            if (is_array($dias) && !empty($dias)):
                                                $diasAbrev = array_map(function($dia) {
                                                    return strtoupper(substr($dia, 0, 3));
                                                }, $dias);
                                                echo '<small>' . implode(', ', $diasAbrev) . '</small>';
                                            else:
                                                echo '<small class="text-muted">No definido</small>';
                                            endif;
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($route['transporte_nombre']): ?>
                                                <small><?= htmlspecialchars($route['transporte_nombre']) ?></small>
                                                <br><small class="text-muted"><?= htmlspecialchars($route['conductor_nombre'] ?? 'Sin conductor') ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Sin asignar</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $route['estado'] === 'activo' ? 'success' : ($route['estado'] === 'mantenimiento' ? 'warning' : 'secondary') ?>">
                                                <?= ucfirst($route['estado']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="editRoute(<?= $route['id'] ?>)" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-info me-1" 
                                                    onclick="viewRoute(<?= $route['id'] ?>)" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                        data-bs-toggle="dropdown" title="Cambiar estado">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="#" onclick="toggleRouteStatus(<?= $route['id'] ?>, 'activo'); return false;">
                                                        <i class="fas fa-check text-success me-2"></i>Activar</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="toggleRouteStatus(<?= $route['id'] ?>, 'inactivo'); return false;">
                                                        <i class="fas fa-pause text-secondary me-2"></i>Desactivar</a></li>
                                                    <li><a class="dropdown-item" href="#" onclick="toggleRouteStatus(<?= $route['id'] ?>, 'mantenimiento'); return false;">
                                                        <i class="fas fa-wrench text-warning me-2"></i>Mantenimiento</a></li>
                                                </ul>
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
    </div>
</div>

<!-- Modal Agregar Ruta -->
<div class="modal fade" id="addRouteModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Nueva Ruta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="addRouteForm" method="POST" action="<?= Config::getBaseUrl() ?>?route=admin/routes/add">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nombre de la Ruta *</label>
                            <input type="text" class="form-control" name="nombre" required 
                                   placeholder="Ej: Shuttle Flores - Belice City">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Origen *</label>
                            <input type="text" class="form-control" name="origen" required 
                                   placeholder="Ej: Flores, Petén">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Destino *</label>
                            <input type="text" class="form-control" name="destino" required 
                                   placeholder="Ej: Belice City, Belice">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" name="descripcion" rows="3" 
                                      placeholder="Descripción detallada del servicio"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Distancia (km)</label>
                            <input type="number" class="form-control" name="distancia_km" step="0.1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Duración Estimada *</label>
                            <input type="text" class="form-control" name="duracion_estimada" required 
                                   placeholder="Ej: 4-5 horas">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Precio (USD) *</label>
                            <input type="number" class="form-control" name="precio" step="0.01" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Transporte</label>
                            <select class="form-select" name="transporte_id">
                                <option value="">Seleccionar transporte...</option>
                                <?php foreach ($transports ?? [] as $transport): ?>
                                    <option value="<?= $transport['id'] ?>">
                                        <?= htmlspecialchars($transport['nombre'] . ' - ' . $transport['tipo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Conductor</label>
                            <select class="form-select" name="conductor_id">
                                <option value="">Seleccionar conductor...</option>
                                <?php foreach ($drivers ?? [] as $driver): ?>
                                    <option value="<?= $driver['id'] ?>">
                                        <?= htmlspecialchars($driver['nombre_completo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Días de Operación</label>
                            <div class="row">
                                <?php 
                                $dias = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
                                foreach ($dias as $dia): 
                                ?>
                                <div class="col-md-3 mb-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="dias_operacion[]" 
                                               value="<?= $dia ?>" id="dia_<?= $dia ?>">
                                        <label class="form-check-label" for="dia_<?= $dia ?>">
                                            <?= ucfirst($dia) ?>
                                        </label>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Horarios de Salida</label>
                            <div id="horariosContainer">
                                <div class="horario-item mb-2">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <input type="time" class="form-control horario-hora" placeholder="Hora">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control horario-lugar" placeholder="Lugar (ej: Hotel pickup Flores)">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeHorario(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addHorario()">
                                <i class="fas fa-plus me-1"></i>Agregar Horario
                            </button>
                            <input type="hidden" name="horarios" id="horariosJSON">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Paradas Intermedias</label>
                            <div id="paradasContainer">
                                <div class="parada-item mb-2">
                                    <div class="row g-2">
                                        <div class="col-md-10">
                                            <input type="text" class="form-control parada-nombre" placeholder="Nombre de la parada">
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeParada(this)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="addParada()">
                                <i class="fas fa-plus me-1"></i>Agregar Parada
                            </button>
                            <input type="hidden" name="paradas_intermedias" id="paradasJSON">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Requisitos</label>
                            <textarea class="form-control" name="requisitos" rows="2" 
                                      placeholder="Pasaporte vigente, visa si aplica, etc."></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notas Importantes</label>
                            <textarea class="form-control" name="notas_importantes" rows="2" 
                                      placeholder="Información adicional para los pasajeros"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Ruta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const baseUrl = '<?= Config::getBaseUrl() ?>';

// Funciones para manejar horarios
function addHorario() {
    const container = document.getElementById('horariosContainer');
    const newItem = document.createElement('div');
    newItem.className = 'horario-item mb-2';
    newItem.innerHTML = `
        <div class="row g-2">
            <div class="col-md-4">
                <input type="time" class="form-control horario-hora" placeholder="Hora">
            </div>
            <div class="col-md-6">
                <input type="text" class="form-control horario-lugar" placeholder="Lugar (ej: Hotel pickup Flores)">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeHorario(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newItem);
}

function removeHorario(button) {
    const container = document.getElementById('horariosContainer');
    if (container.children.length > 1) {
        button.closest('.horario-item').remove();
    } else {
        alert('Debe haber al menos un horario');
    }
}

// Funciones para manejar paradas
function addParada() {
    const container = document.getElementById('paradasContainer');
    const newItem = document.createElement('div');
    newItem.className = 'parada-item mb-2';
    newItem.innerHTML = `
        <div class="row g-2">
            <div class="col-md-10">
                <input type="text" class="form-control parada-nombre" placeholder="Nombre de la parada">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeParada(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newItem);
}

function removeParada(button) {
    const container = document.getElementById('paradasContainer');
    if (container.children.length > 1) {
        button.closest('.parada-item').remove();
    }
}

// Convertir horarios y paradas a JSON antes de enviar
document.getElementById('addRouteForm')?.addEventListener('submit', function(e) {
    // Procesar horarios
    const horarios = [];
    document.querySelectorAll('.horario-item').forEach(item => {
        const hora = item.querySelector('.horario-hora').value;
        const lugar = item.querySelector('.horario-lugar').value;
        if (hora && lugar) {
            horarios.push({ hora, lugar });
        }
    });
    document.getElementById('horariosJSON').value = JSON.stringify(horarios);

    // Procesar paradas
    const paradas = [];
    document.querySelectorAll('.parada-item').forEach(item => {
        const nombre = item.querySelector('.parada-nombre').value.trim();
        if (nombre) {
            paradas.push(nombre);
        }
    });
    document.getElementById('paradasJSON').value = JSON.stringify(paradas);
});

function editRoute(id) {
    // Redirigir a la página de edición
    window.location.href = `${baseUrl}?route=admin/routes/edit/${id}`;
}

function viewRoute(id) {
    fetch(`${baseUrl}?route=admin/routes/details/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showRouteDetailsModal(data.route);
            } else {
                alert('Error al cargar los detalles de la ruta');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los detalles');
        });
}

function showRouteDetailsModal(route) {
    // Preparar días de operación
    let dias = '';
    if (route.dias_operacion) {
        const diasArray = typeof route.dias_operacion === 'string'
            ? JSON.parse(route.dias_operacion)
            : route.dias_operacion;
        dias = diasArray.map(d => `<span class="badge bg-primary me-1">${d}</span>`).join('');
    }

    // Preparar horarios
    let horarios = '';
    if (route.horarios) {
        const horariosArray = typeof route.horarios === 'string'
            ? JSON.parse(route.horarios)
            : route.horarios;
        horarios = horariosArray.map(h => `
            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                <span><i class="fas fa-clock me-2"></i>${h.hora}</span>
                <span class="text-muted">${h.lugar}</span>
            </div>
        `).join('');
    }

    // Preparar paradas
    let paradas = '';
    if (route.paradas_intermedias) {
        const paradasArray = typeof route.paradas_intermedias === 'string'
            ? JSON.parse(route.paradas_intermedias)
            : route.paradas_intermedias;
        paradas = paradasArray.map(p => `<li>${p}</li>`).join('');
    }

    const modalContent = `
        <div class="modal fade" id="viewRouteModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-route me-2"></i>${route.nombre}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2 mb-3">Información de la Ruta</h6>
                                <p><strong>Origen:</strong> ${route.origen}</p>
                                <p><strong>Destino:</strong> ${route.destino}</p>
                                <p><strong>Distancia:</strong> ${route.distancia_km ? route.distancia_km + ' km' : 'N/A'}</p>
                                <p><strong>Duración:</strong> ${route.duracion_estimada || route.duracion_horas + ' horas' || 'N/A'}</p>
                                <p><strong>Precio:</strong> Q ${parseFloat(route.precio).toLocaleString('es-GT', {minimumFractionDigits: 2})}</p>
                                <p><strong>Estado:</strong>
                                    <span class="badge bg-${route.estado === 'activo' ? 'success' : (route.estado === 'mantenimiento' ? 'warning' : 'secondary')}">
                                        ${route.estado ? route.estado.toUpperCase() : (route.activo ? 'ACTIVO' : 'INACTIVO')}
                                    </span>
                                </p>

                                ${route.descripcion ? `
                                    <div class="mt-3">
                                        <strong>Descripción:</strong>
                                        <p class="text-muted">${route.descripcion}</p>
                                    </div>
                                ` : ''}
                            </div>

                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2 mb-3">Detalles Operativos</h6>

                                ${route.transporte_nombre ? `<p><strong>Transporte:</strong> ${route.transporte_nombre}</p>` : ''}
                                ${route.conductor_nombre ? `<p><strong>Conductor:</strong> ${route.conductor_nombre}</p>` : ''}

                                ${dias ? `
                                    <div class="mb-3">
                                        <strong>Días de Operación:</strong><br>
                                        ${dias}
                                    </div>
                                ` : ''}

                                ${horarios ? `
                                    <div class="mb-3">
                                        <strong>Horarios:</strong>
                                        <div class="mt-2">${horarios}</div>
                                    </div>
                                ` : ''}

                                ${paradas ? `
                                    <div class="mb-3">
                                        <strong>Paradas Intermedias:</strong>
                                        <ul class="mt-2">${paradas}</ul>
                                    </div>
                                ` : ''}
                            </div>
                        </div>

                        ${route.requisitos || route.notas_importantes ? `
                            <hr class="my-3">
                            <div class="row">
                                ${route.requisitos ? `
                                    <div class="col-md-6">
                                        <h6>Requisitos</h6>
                                        <p class="text-muted">${route.requisitos}</p>
                                    </div>
                                ` : ''}
                                ${route.notas_importantes ? `
                                    <div class="col-md-6">
                                        <h6>Notas Importantes</h6>
                                        <p class="text-muted">${route.notas_importantes}</p>
                                    </div>
                                ` : ''}
                            </div>
                        ` : ''}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button class="btn btn-primary" onclick="editRoute(${route.id}); bootstrap.Modal.getInstance(document.getElementById('viewRouteModal')).hide();">
                            <i class="fas fa-edit me-1"></i>Editar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remover modal anterior si existe
    const oldModal = document.getElementById('viewRouteModal');
    if (oldModal) {
        const oldModalInstance = bootstrap.Modal.getInstance(oldModal);
        if (oldModalInstance) {
            oldModalInstance.dispose(); // Limpiar la instancia de Bootstrap
        }
        oldModal.remove();
    }

    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalContent);

    // Mostrar modal
    const modalElement = document.getElementById('viewRouteModal');
    const modal = new bootstrap.Modal(modalElement);

    // Manejar el evento de cierre para limpiar correctamente
    modalElement.addEventListener('hidden.bs.modal', function () {
        this.remove();
    }, { once: true });

    modal.show();
}

function toggleRouteStatus(id, newStatus) {
    const statusLabels = {
        'activo': 'activar',
        'inactivo': 'desactivar',
        'mantenimiento': 'poner en mantenimiento'
    };

    if (confirm(`¿Está seguro de ${statusLabels[newStatus]} esta ruta?`)) {
        fetch(`${baseUrl}?route=admin/routes/status`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}&estado=${newStatus}`
        })
        .then(() => location.reload());
    }
}
</script>

<?php include __DIR__ . '/../layouts/admin_footer.php'; ?>
