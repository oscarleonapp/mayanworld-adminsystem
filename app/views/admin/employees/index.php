<?php
use App\Core\Config;
use App\Core\Auth;
use App\Core\Helpers;
include __DIR__ . '/../../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <?php
        $actionTitle = 'Gestión de Empleados';
        $actionSubtitle = 'Administra tu equipo de trabajo';
        $actionButtons = [];
        if ($auth->hasPermission('empleados.crear')) {
            $actionButtons[] = [
                'label' => 'Nuevo Empleado',
                'icon' => 'fas fa-plus',
                'variant' => 'primary',
                'href' => Config::getBaseUrl() . '?route=admin/employees/create'
            ];
        }
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <div class="d-flex justify-content-end mb-4">
        <div class="btn-group">
            <button class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-download me-2"></i>Exportar
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#" onclick="exportEmployees('excel'); return false;">
                    <i class="fas fa-file-excel me-2"></i>Excel
                </a></li>
                <li><a class="dropdown-item" href="#" onclick="exportEmployees('pdf'); return false;">
                    <i class="fas fa-file-pdf me-2"></i>PDF
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
                                Total Empleados
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['total_empleados'] ?>
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
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Empleados Activos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['empleados_activos'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                                De Vacaciones
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['empleados_vacaciones'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-umbrella-beach fa-2x text-gray-300"></i>
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
                                Departamentos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['departamentos_count'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-building fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3" id="filtersForm">
                <input type="hidden" name="route" value="admin/employees">
                
                <div class="col-md-3">
                    <label class="form-label">Buscar</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Nombre, email, puesto..." 
                           value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Departamento</label>
                    <select name="department" class="form-select">
                        <option value="">Todos los departamentos</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= htmlspecialchars($dept['departamento']) ?>"
                                    <?= $filters['department'] === $dept['departamento'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['departamento']) ?> (<?= $dept['empleados_count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-select">
                        <option value="">Todos los estados</option>
                        <option value="activo" <?= $filters['status'] === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= $filters['status'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        <option value="vacaciones" <?= $filters['status'] === 'vacaciones' ? 'selected' : '' ?>>Vacaciones</option>
                        <option value="suspension" <?= $filters['status'] === 'suspension' ? 'selected' : '' ?>>Suspensión</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-search me-1"></i>Filtrar
                        </button>
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/employees" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Employees Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Lista de Empleados
            </h5>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" onclick="toggleView()">
                    <i class="fas fa-th" id="viewToggleIcon"></i>
                </button>
                <a href="<?= Config::getBaseUrl() ?>?route=admin/employees/organigrama" class="btn btn-sm btn-outline-info">
                    <i class="fas fa-sitemap me-1"></i>Organigrama
                </a>
            </div>
        </div>
        
        <div class="card-body p-0">
            <!-- Table View -->
            <div id="tableView" class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Empleado</th>
                            <th>Departamento</th>
                            <th>Puesto</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employees)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No se encontraron empleados</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($employees as $employee): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= $employee['foto_perfil'] ? Helpers::asset('uploads/profiles/' . $employee['foto_perfil']) : Helpers::asset('images/default-avatar.png') ?>" 
                                                 class="avatar-sm rounded-circle me-3 skeleton" alt="" loading="lazy" decoding="async">
                                            <div>
                                                <h6 class="mb-0"><?= htmlspecialchars($employee['nombre']) ?></h6>
                                                <small class="text-muted"><?= htmlspecialchars($employee['email']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?= htmlspecialchars($employee['departamento'] ?? 'Sin asignar') ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($employee['puesto'] ?? 'Sin asignar') ?></td>
                                    <td>
                                        <?php
                                        $roleColors = [
                                            'super_admin' => 'danger',
                                            'admin' => 'warning',
                                            'gerente' => 'info',
                                            'operador' => 'primary',
                                            'vendedor' => 'success',
                                            'conductor' => 'secondary',
                                            'soporte' => 'dark'
                                        ];
                                        $roleColor = $roleColors[$employee['rol']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $roleColor ?>">
                                            <?= ucfirst($employee['rol']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $statusColors = [
                                            'activo' => 'success',
                                            'inactivo' => 'secondary',
                                            'vacaciones' => 'warning',
                                            'suspension' => 'danger'
                                        ];
                                        $statusColor = $statusColors[$employee['estado_empleado']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?= $statusColor ?>">
                                            <?= ucfirst($employee['estado_empleado']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($auth->hasPermission('empleados.leer')): ?>
                                                <a href="<?= Config::getBaseUrl() ?>?route=admin/employees/view/<?= $employee['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($auth->hasPermission('empleados.actualizar')): ?>
                                                <a href="<?= Config::getBaseUrl() ?>?route=admin/employees/edit/<?= $employee['id'] ?>" 
                                                   class="btn btn-sm btn-outline-secondary" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                        data-bs-toggle="dropdown" title="Más acciones">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <?php if ($auth->hasPermission('empleados.actualizar')): ?>
                                                        <li><a class="dropdown-item" href="#" 
                                                               onclick="changeEmployeeStatus(<?= $employee['id'] ?>, 'activo'); return false;">
                                                            <i class="fas fa-check text-success me-2"></i>Activar
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" 
                                                               onclick="changeEmployeeStatus(<?= $employee['id'] ?>, 'inactivo'); return false;">
                                                            <i class="fas fa-pause text-warning me-2"></i>Desactivar
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" 
                                                               onclick="changeEmployeeStatus(<?= $employee['id'] ?>, 'vacaciones'); return false;">
                                                            <i class="fas fa-umbrella-beach text-info me-2"></i>Vacaciones
                                                        </a></li>
                                                    <?php endif; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Card View (Hidden by default) -->
            <div id="cardView" class="d-none">
                <div class="row p-3">
                    <?php foreach ($employees as $employee): ?>
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <img src="<?= $employee['foto_perfil'] ? Helpers::asset('uploads/profiles/' . $employee['foto_perfil']) : Helpers::asset('images/default-avatar.png') ?>" 
                                         class="rounded-circle mb-3 skeleton" width="60" height="60" alt="" loading="lazy" decoding="async">
                                    <h6 class="card-title mb-2"><?= htmlspecialchars($employee['nombre']) ?></h6>
                                    <p class="text-muted small mb-2"><?= htmlspecialchars($employee['puesto']) ?></p>
                                    <p class="text-muted small mb-3"><?= htmlspecialchars($employee['departamento']) ?></p>
                                    
                                    <div class="d-flex justify-content-center gap-1">
                                        <?php if ($auth->hasPermission('empleados.leer')): ?>
                                            <a href="<?= Config::getBaseUrl() ?>?route=admin/employees/view/<?= $employee['id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($auth->hasPermission('empleados.actualizar')): ?>
                                            <a href="<?= Config::getBaseUrl() ?>?route=admin/employees/edit/<?= $employee['id'] ?>" 
                                               class="btn btn-sm btn-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="card-footer">
                <nav aria-label="Employee pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($pagination['has_prev']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/employees&page=<?= $pagination['current_page'] - 1 ?>&<?= http_build_query($filters) ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/employees&page=<?= $i ?>&<?= http_build_query($filters) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagination['has_next']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/employees&page=<?= $pagination['current_page'] + 1 ?>&<?= http_build_query($filters) ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>

                <div class="text-center text-muted mt-2">
                    Mostrando <?= count($employees) ?> de <?= $pagination['total'] ?> empleados
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleView() {
    const tableView = document.getElementById('tableView');
    const cardView = document.getElementById('cardView');
    const icon = document.getElementById('viewToggleIcon');
    
    if (tableView.classList.contains('d-none')) {
        tableView.classList.remove('d-none');
        cardView.classList.add('d-none');
        icon.className = 'fas fa-th';
    } else {
        tableView.classList.add('d-none');
        cardView.classList.remove('d-none');
        icon.className = 'fas fa-list';
    }
}

function changeEmployeeStatus(employeeId, status) {
    if (!confirm('¿Estás seguro de cambiar el estado del empleado?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('status', status);
    formData.append('csrf_token', '<?= Helpers::getCSRFToken() ?>');
    
    fetch(`?route=admin/employees/change-status/${employeeId}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            location.reload();
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        showNotification('error', 'Error al cambiar estado del empleado');
        console.error('Error:', error);
    });
}

function exportEmployees(format) {
    const params = new URLSearchParams(window.location.search);
    params.set('export', format);
    const url = '?' + params.toString();
    if (typeof AdminDownload === 'function') {
        AdminDownload(url, {
            filenameFallback: `empleados.${format === 'excel' ? 'xlsx' : format}`,
            startMessage: 'Generando exportación...',
            errorMessage: 'Error al exportar empleados'
        });
    }
}
</script>

<!-- Custom CSS moved to public/assets/css/admin.css -->

<?php include __DIR__ . '/../../layouts/admin_footer.php'; ?>
