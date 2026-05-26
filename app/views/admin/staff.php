<?php
use App\Core\Config;
use App\Core\Helpers;
include __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <?php 
              $actionTitle = 'Administrar Personal';
              $actionSubtitle = null;
              $actionButtons = [
                ['label' => 'Agregar Empleado', 'icon' => 'fas fa-plus', 'variant' => 'primary', 'onclick' => "new bootstrap.Modal(document.getElementById('addStaffModal')).show()"],
              ];
              include __DIR__ . '/../partials/admin_action_bar.php';
            ?>

            <!-- Estadísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <h4><?= $stats['total_empleados'] ?? 0 ?></h4>
                            <small>Total Empleados</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-user-check fa-2x mb-2"></i>
                            <h4><?= $stats['activos'] ?? 0 ?></h4>
                            <small>Activos</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-route fa-2x mb-2"></i>
                            <h4><?= $stats['total_guias'] ?? 0 ?></h4>
                            <small>Guías</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-bus fa-2x mb-2"></i>
                            <h4><?= $stats['total_conductores'] ?? 0 ?></h4>
                            <small>Conductores</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3 admin-filters">
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="search" placeholder="Buscar empleado..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="tipo">
                                <option value="">Todos los tipos</option>
                                <?php foreach ($employeeTypes ?? [] as $et): ?>
                                    <option value="<?= htmlspecialchars($et['slug']) ?>" <?= ($_GET['tipo'] ?? '') === $et['slug'] ? 'selected' : '' ?>><?= htmlspecialchars($et['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="estado">
                                <option value="">Todos los estados</option>
                                <option value="activo" <?= ($_GET['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= ($_GET['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                                <option value="suspendido" <?= ($_GET['estado'] ?? '') === 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-outline-primary me-2">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <a href="<?= Config::getBaseUrl() ?>?route=admin/staff" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de empleados -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-sticky align-middle">
                            <thead>
                                <tr>
                                    <th>Foto</th>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Puesto</th>
                                    <th>Contacto</th>
                                    <th>Experiencia</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($staff)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <h5>No hay empleados registrados</h5>
                                        <p class="text-muted">Agrega el primer empleado usando el botón de arriba</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($staff as $employee): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= $employee['foto'] ? Config::getBaseUrl() . 'uploads/staff/' . htmlspecialchars($employee['foto']) : Helpers::asset('img/default-avatar.png') ?>" 
                                                 alt="Foto" class="rounded-circle skeleton" width="40" height="40" loading="lazy" decoding="async">
                                        </td>
                                        <td>
                                            <strong><?= htmlspecialchars($employee['nombre'] . ' ' . $employee['apellido']) ?></strong>
                                            <?php if ($employee['email']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($employee['email']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= ucfirst(htmlspecialchars($employee['tipo_empleado'])) ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($employee['puesto']) ?></td>
                                        <td>
                                            <small>
                                                <i class="fas fa-phone"></i> <?= htmlspecialchars($employee['telefono']) ?>
                                            </small>
                                        </td>
                                        <td><?= $employee['experiencia_anios'] ?> años</td>
                                        <td>
                                            <span class="badge bg-<?= $employee['estado'] === 'activo' ? 'success' : ($employee['estado'] === 'suspendido' ? 'danger' : 'secondary') ?>">
                                                <?= ucfirst($employee['estado']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?= Config::getBaseUrl() ?>?route=admin/staff/edit/<?= $employee['id'] ?>"
                                               class="btn btn-sm btn-outline-primary me-1" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button class="btn btn-sm btn-outline-info me-1" 
                                                    onclick="viewStaff(<?= $employee['id'] ?>)" title="Ver detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($employee['estado'] !== 'suspendido'): ?>
                                            <button class="btn btn-sm btn-outline-warning" 
                                                    onclick="toggleStaffStatus(<?= $employee['id'] ?>, 'suspendido')" title="Suspender">
                                                <i class="fas fa-user-slash"></i>
                                            </button>
                                            <?php else: ?>
                                            <button class="btn btn-sm btn-outline-success" 
                                                    onclick="toggleStaffStatus(<?= $employee['id'] ?>, 'activo')" title="Activar">
                                                <i class="fas fa-user-check"></i>
                                            </button>
                                            <?php endif; ?>
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

<!-- Modal Agregar Empleado -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Agregar Nuevo Empleado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="addStaffForm" method="POST" action="<?= Config::getBaseUrl() ?>?route=admin/staff/add" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre *</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellido *</label>
                            <input type="text" class="form-control" name="apellido" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono *</label>
                            <input type="text" class="form-control" name="telefono" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Empleado *</label>
                            <select class="form-select" name="tipo_empleado" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($employeeTypes ?? [] as $et): ?>
                                    <option value="<?= htmlspecialchars($et['slug']) ?>"><?= htmlspecialchars($et['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Puesto *</label>
                            <input type="text" class="form-control" name="puesto" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Salario</label>
                            <input type="number" class="form-control" name="salario" step="0.01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Años de Experiencia</label>
                            <input type="number" class="form-control" name="experiencia_anios" min="0" value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Dirección</label>
                            <textarea class="form-control" name="direccion" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">DPI</label>
                            <input type="text" class="form-control" name="dpi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Idiomas</label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($languages ?? [] as $lang): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="idiomas[]"
                                               value="<?= htmlspecialchars($lang['nombre']) ?>"
                                               id="addLang_<?= $lang['id'] ?>">
                                        <label class="form-check-label" for="addLang_<?= $lang['id'] ?>">
                                            <?= htmlspecialchars($lang['nombre']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Certificaciones (separadas por coma)</label>
                            <input type="text" class="form-control" name="certificaciones" placeholder="Guía Certificado INGUAT, Primeros Auxilios">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Foto</label>
                            <input type="file" class="form-control" name="foto" accept="image/*">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <textarea class="form-control" name="notas" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar Empleado</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const baseUrl = '<?= Config::getBaseUrl() ?>';

function editStaff(id) {
    // Cargar datos del empleado y mostrar modal de edición
    fetch(`${baseUrl}?route=admin/staff/edit&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Poblar modal de edición con datos
                showEditModal(data.employee);
            }
        });
}

function viewStaff(id) {
    fetch(`${baseUrl}?route=admin/staff/details/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showStaffDetailsModal(data.employee);
            } else {
                alert('Error al cargar los detalles del empleado');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los detalles');
        });
}

function showStaffDetailsModal(employee) {
    // Preparar idiomas
    let idiomas = '';
    if (employee.idiomas) {
        const idiomasArray = typeof employee.idiomas === 'string'
            ? employee.idiomas.split(',').map(i => i.trim())
            : employee.idiomas;
        idiomas = idiomasArray.map(i => `<span class="badge bg-secondary me-1">${i}</span>`).join('');
    }

    // Preparar certificaciones
    let certificaciones = '';
    if (employee.certificaciones) {
        const certsArray = typeof employee.certificaciones === 'string'
            ? employee.certificaciones.split(',').map(c => c.trim())
            : employee.certificaciones;
        certificaciones = certsArray.map(c => `<span class="badge bg-success me-1 mb-1"><i class="fas fa-certificate me-1"></i>${c}</span>`).join('');
    }

    // Calcular edad si hay fecha de nacimiento
    let edadText = '';
    if (employee.fecha_nacimiento) {
        const birthDate = new Date(employee.fecha_nacimiento);
        const age = Math.floor((new Date() - birthDate) / (365.25 * 24 * 60 * 60 * 1000));
        edadText = ` (${age} años)`;
    }

    const modalContent = `
        <div class="modal fade" id="viewStaffModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detalles del Empleado</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                ${employee.foto
                                    ? `<img src="${baseUrl}uploads/staff/${employee.foto}" class="img-thumbnail mb-3" style="max-width: 200px;" alt="Foto de ${employee.nombre} ${employee.apellido}">`
                                    : '<i class="fas fa-user-circle fa-5x text-muted mb-3"></i>'}
                                <h5>${employee.nombre} ${employee.apellido}</h5>
                                <span class="badge bg-${employee.estado === 'activo' ? 'success' : (employee.estado === 'suspendido' ? 'danger' : 'secondary')}">${employee.estado.toUpperCase()}</span>
                            </div>
                            <div class="col-md-8">
                                <h6 class="border-bottom pb-2 mb-3">Información Personal</h6>
                                ${employee.email ? `<p><i class="fas fa-envelope text-muted me-2"></i>${employee.email}</p>` : ''}
                                <p><i class="fas fa-phone text-muted me-2"></i>${employee.telefono}</p>
                                ${employee.dpi ? `<p><i class="fas fa-id-card text-muted me-2"></i>${employee.dpi}</p>` : ''}
                                ${employee.fecha_nacimiento ? `<p><i class="fas fa-birthday-cake text-muted me-2"></i>${new Date(employee.fecha_nacimiento).toLocaleDateString('es-GT')}${edadText}</p>` : ''}
                                ${employee.direccion ? `<p><i class="fas fa-map-marker-alt text-muted me-2"></i>${employee.direccion}</p>` : ''}

                                <h6 class="border-bottom pb-2 mb-3 mt-4">Información Laboral</h6>
                                <p><strong>Tipo:</strong> <span class="badge bg-info">${employee.tipo_empleado.toUpperCase()}</span></p>
                                <p><strong>Puesto:</strong> ${employee.puesto}</p>
                                ${employee.salario ? `<p><strong>Salario:</strong> Q ${parseFloat(employee.salario).toLocaleString('es-GT', {minimumFractionDigits: 2})}</p>` : ''}
                                <p><strong>Experiencia:</strong> ${employee.experiencia_anios || 0} años</p>
                                ${employee.fecha_contratacion ? `<p><strong>Fecha de Contratación:</strong> ${new Date(employee.fecha_contratacion).toLocaleDateString('es-GT')}</p>` : ''}

                                ${idiomas ? `<div class="mb-2"><strong>Idiomas:</strong><br>${idiomas}</div>` : ''}
                                ${certificaciones ? `<div class="mb-2"><strong>Certificaciones:</strong><br>${certificaciones}</div>` : ''}
                                ${employee.notas ? `<div class="mt-3"><strong>Notas:</strong><p class="text-muted">${employee.notas}</p></div>` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <a href="${baseUrl}?route=admin/staff/edit/${employee.id}" class="btn btn-primary">
                            <i class="fas fa-edit me-1"></i>Editar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Remover modal anterior si existe
    const oldModal = document.getElementById('viewStaffModal');
    if (oldModal) {
        oldModal.remove();
    }

    // Agregar modal al DOM
    document.body.insertAdjacentHTML('beforeend', modalContent);

    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('viewStaffModal'));
    modal.show();
}

function toggleStaffStatus(id, newStatus) {
    if (confirm(`¿Está seguro de ${newStatus === 'activo' ? 'activar' : 'suspender'} a este empleado?`)) {
        fetch(`${baseUrl}?route=admin/staff/status`, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `id=${id}&estado=${newStatus}`
        })
        .then(() => location.reload());
    }
}
</script>

<?php include __DIR__ . '/../layouts/admin_footer.php'; ?>
