<?php
/**
 * Vista de Audit Log - Registro de Auditoría
 * Muestra un historial completo de acciones del sistema
 */
require_once __DIR__ . '/../layouts/admin_header.php';
?>

<div class="container-fluid mt-4">
    <?php
        $actionTitle = 'Registro de Auditoría';
        $actionSubtitle = 'Historial completo de acciones del sistema';
        $actionButtons = [];
        include __DIR__ . '/../partials/admin_action_bar.php';
    ?>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Total de Registros</h6>
                            <h3 class="mb-0"><?= number_format($stats['total_logs']) ?></h3>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-database fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Hoy</h6>
                            <h3 class="mb-0"><?= number_format($stats['today_logs']) ?></h3>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-calendar-day fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Usuarios Activos</h6>
                            <h3 class="mb-0"><?= number_format($stats['unique_users']) ?></h3>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-users fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="text-muted mb-1">Página Actual</h6>
                            <h3 class="mb-0"><?= $pagination['page'] ?> / <?= $pagination['total_pages'] ?></h3>
                        </div>
                        <div class="ms-3">
                            <i class="fas fa-file-alt fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>Filtros de Búsqueda
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="route" value="admin/audit">

                <div class="col-md-3">
                    <label class="form-label">Acción</label>
                    <input type="text" name="action" class="form-control"
                           value="<?= htmlspecialchars($filters['action']) ?>"
                           placeholder="login, create, update...">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="user" class="form-control"
                           value="<?= htmlspecialchars($filters['user']) ?>"
                           placeholder="Email o ID">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Desde</label>
                    <input type="date" name="date_from" class="form-control"
                           value="<?= htmlspecialchars($filters['date_from']) ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Hasta</label>
                    <input type="date" name="date_to" class="form-control"
                           value="<?= htmlspecialchars($filters['date_to']) ?>">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>Buscar
                    </button>
                    <a href="?route=admin/audit" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i>Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Logs -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Registros de Auditoría</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="60">ID</th>
                            <th width="140">Fecha/Hora</th>
                            <th width="200">Usuario</th>
                            <th width="120">Acción</th>
                            <th width="120">Tabla</th>
                            <th width="80">Registro</th>
                            <th width="120">IP</th>
                            <th>Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                <p class="mb-0">No hay registros de auditoría con los filtros aplicados</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><span class="badge bg-secondary">#<?= $log['id'] ?></span></td>
                                <td>
                                    <small>
                                        <?= date('d/m/Y', strtotime($log['created_at'])) ?><br>
                                        <span class="text-muted"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($log['user_name']): ?>
                                        <strong><?= htmlspecialchars($log['user_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($log['user_email']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">Sistema</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $actionColors = [
                                        'login' => 'success',
                                        'logout' => 'secondary',
                                        'create' => 'primary',
                                        'update' => 'info',
                                        'delete' => 'danger',
                                        'view' => 'light',
                                        'crear' => 'primary',
                                        'editar' => 'info',
                                        'eliminar' => 'danger',
                                        'ver' => 'light'
                                    ];
                                    $actionIcons = [
                                        'login' => 'sign-in-alt',
                                        'logout' => 'sign-out-alt',
                                        'create' => 'plus-circle',
                                        'update' => 'edit',
                                        'delete' => 'trash-alt',
                                        'view' => 'eye',
                                        'crear' => 'plus-circle',
                                        'editar' => 'edit',
                                        'eliminar' => 'trash-alt',
                                        'ver' => 'eye'
                                    ];
                                    $action = strtolower($log['action']);
                                    $color = $actionColors[$action] ?? 'secondary';
                                    $icon = $actionIcons[$action] ?? 'question-circle';
                                    ?>
                                    <span class="badge bg-<?= $color ?>">
                                        <i class="fas fa-<?= $icon ?> me-1"></i>
                                        <?= htmlspecialchars($log['action']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['table_name']): ?>
                                        <code><?= htmlspecialchars($log['table_name']) ?></code>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['record_id']): ?>
                                        <span class="badge bg-light text-dark">#<?= $log['record_id'] ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="font-monospace"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></small>
                                </td>
                                <td>
                                    <?php if ($log['old_values'] || $log['new_values']): ?>
                                        <button class="btn btn-sm btn-outline-primary"
                                                onclick="showDetails(<?= htmlspecialchars(json_encode([
                                                    'old' => $log['old_values'],
                                                    'new' => $log['new_values'],
                                                    'user_agent' => $log['user_agent']
                                                ])) ?>)">
                                            <i class="fas fa-info-circle me-1"></i>Ver
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination pagination-sm mb-0 justify-content-center">
                    <?php if ($pagination['page'] > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?route=admin/audit&page=<?= $pagination['page'] - 1 ?><?= !empty($filters['action']) ? '&action=' . urlencode($filters['action']) : '' ?><?= !empty($filters['user']) ? '&user=' . urlencode($filters['user']) : '' ?><?= !empty($filters['date_from']) ? '&date_from=' . $filters['date_from'] : '' ?><?= !empty($filters['date_to']) ? '&date_to=' . $filters['date_to'] : '' ?>">
                            <i class="fas fa-chevron-left"></i> Anterior
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php for ($i = max(1, $pagination['page'] - 2); $i <= min($pagination['total_pages'], $pagination['page'] + 2); $i++): ?>
                    <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                        <a class="page-link" href="?route=admin/audit&page=<?= $i ?><?= !empty($filters['action']) ? '&action=' . urlencode($filters['action']) : '' ?><?= !empty($filters['user']) ? '&user=' . urlencode($filters['user']) : '' ?><?= !empty($filters['date_from']) ? '&date_from=' . $filters['date_from'] : '' ?><?= !empty($filters['date_to']) ? '&date_to=' . $filters['date_to'] : '' ?>">
                            <?= $i ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                    <li class="page-item">
                        <a class="page-link" href="?route=admin/audit&page=<?= $pagination['page'] + 1 ?><?= !empty($filters['action']) ? '&action=' . urlencode($filters['action']) : '' ?><?= !empty($filters['user']) ? '&user=' . urlencode($filters['user']) : '' ?><?= !empty($filters['date_from']) ? '&date_from=' . $filters['date_from'] : '' ?><?= !empty($filters['date_to']) ? '&date_to=' . $filters['date_to'] : '' ?>">
                            Siguiente <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <p class="text-center text-muted mt-2 mb-0">
                Mostrando <?= count($logs) ?> de <?= number_format($pagination['total']) ?> registros
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para Ver Detalles -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Detalles del Registro
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Se llenará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function showDetails(data) {
    let html = '';

    if (data.old) {
        html += '<h6 class="mb-2"><i class="fas fa-history me-2"></i>Valores Anteriores:</h6>';
        html += '<pre class="bg-light p-3 rounded mb-3"><code>' + escapeHtml(data.old) + '</code></pre>';
    }

    if (data.new) {
        html += '<h6 class="mb-2"><i class="fas fa-edit me-2"></i>Valores Nuevos:</h6>';
        html += '<pre class="bg-light p-3 rounded mb-3"><code>' + escapeHtml(data.new) + '</code></pre>';
    }

    if (data.user_agent) {
        html += '<h6 class="mb-2"><i class="fas fa-desktop me-2"></i>User Agent:</h6>';
        html += '<p class="text-muted small mb-0">' + escapeHtml(data.user_agent) + '</p>';
    }

    document.getElementById('detailsContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
