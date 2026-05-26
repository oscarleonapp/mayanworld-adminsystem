<?php
/**
 * Vista: Registro de Actividad (Audit Log)
 * Con filtros avanzados, detalles JSON, y exportación CSV
 */

$pageTitle = 'Registro de Actividad';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="admin-audit-log">
    <?php
        $actionTitle = 'Registro de Actividad';
        $actionSubtitle = 'Historial completo de acciones realizadas en el sistema';
        $actionButtons = [
            ['label' => 'Exportar CSV', 'icon' => 'fas fa-file-csv', 'variant' => 'success', 'id' => 'btnExportCSV'],
            ['label' => 'Limpiar Antiguos', 'icon' => 'fas fa-trash-alt', 'variant' => 'outline-secondary', 'id' => 'btnCleanOld'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Filtros avanzados -->
    <div class="card mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>
                Filtros
            </h5>
        </div>
        <div class="card-body">
            <form id="formFilters" method="GET" action="?route=admin/audit-log">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Usuario</label>
                        <select class="form-select" name="usuario_id" id="filterUsuario">
                            <option value="">Todos los usuarios</option>
                            <?php foreach ($usuarios ?? [] as $user): ?>
                                <option value="<?= $user['id'] ?>"
                                        <?= ($filtros['usuario_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Módulo</label>
                        <select class="form-select" name="modulo" id="filterModulo">
                            <option value="">Todos los módulos</option>
                            <option value="tours" <?= ($filtros['modulo'] ?? '') == 'tours' ? 'selected' : '' ?>>Tours</option>
                            <option value="reservas" <?= ($filtros['modulo'] ?? '') == 'reservas' ? 'selected' : '' ?>>Reservas</option>
                            <option value="usuarios" <?= ($filtros['modulo'] ?? '') == 'usuarios' ? 'selected' : '' ?>>Usuarios</option>
                            <option value="categorias" <?= ($filtros['modulo'] ?? '') == 'categorias' ? 'selected' : '' ?>>Categorías</option>
                            <option value="content_blocks" <?= ($filtros['modulo'] ?? '') == 'content_blocks' ? 'selected' : '' ?>>Bloques</option>
                            <option value="static_pages" <?= ($filtros['modulo'] ?? '') == 'static_pages' ? 'selected' : '' ?>>Páginas</option>
                            <option value="company_config" <?= ($filtros['modulo'] ?? '') == 'company_config' ? 'selected' : '' ?>>Configuración</option>
                            <option value="faqs" <?= ($filtros['modulo'] ?? '') == 'faqs' ? 'selected' : '' ?>>FAQs</option>
                            <option value="auth" <?= ($filtros['modulo'] ?? '') == 'auth' ? 'selected' : '' ?>>Autenticación</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Acción</label>
                        <select class="form-select" name="accion" id="filterAccion">
                            <option value="">Todas las acciones</option>
                            <option value="crear" <?= ($filtros['accion'] ?? '') == 'crear' ? 'selected' : '' ?>>Crear</option>
                            <option value="editar" <?= ($filtros['accion'] ?? '') == 'editar' ? 'selected' : '' ?>>Editar</option>
                            <option value="eliminar" <?= ($filtros['accion'] ?? '') == 'eliminar' ? 'selected' : '' ?>>Eliminar</option>
                            <option value="login" <?= ($filtros['accion'] ?? '') == 'login' ? 'selected' : '' ?>>Login</option>
                            <option value="logout" <?= ($filtros['accion'] ?? '') == 'logout' ? 'selected' : '' ?>>Logout</option>
                            <option value="ver" <?= ($filtros['accion'] ?? '') == 'ver' ? 'selected' : '' ?>>Ver</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Desde</label>
                        <input type="date"
                               class="form-control"
                               name="fecha_desde"
                               value="<?= $filtros['fecha_desde'] ?? '' ?>">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Hasta</label>
                        <input type="date"
                               class="form-control"
                               name="fecha_hasta"
                               value="<?= $filtros['fecha_hasta'] ?? '' ?>">
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>
                            Aplicar Filtros
                        </button>
                        <a href="?route=admin/audit-log" class="btn btn-secondary">
                            <i class="fas fa-undo me-2"></i>
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Estadísticas rápidas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <i class="fas fa-history text-primary fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total Registros</div>
                            <div class="h4 mb-0"><?= number_format($stats['total'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded p-3">
                                <i class="fas fa-calendar text-info fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Últimas 24h</div>
                            <div class="h4 mb-0"><?= number_format($stats['ultimas_24h'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <i class="fas fa-users text-success fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Usuarios Activos</div>
                            <div class="h4 mb-0"><?= $stats['usuarios_activos'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded p-3">
                                <i class="fas fa-chart-line text-warning fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Acciones/Día</div>
                            <div class="h4 mb-0"><?= number_format($stats['promedio_dia'] ?? 0) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de logs -->
    <div class="card">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                Historial de Actividad
                <span class="badge bg-secondary ms-2"><?= count($logs ?? []) ?> registros</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 140px;">Fecha/Hora</th>
                            <th>Usuario</th>
                            <th style="width: 100px;">Acción</th>
                            <th style="width: 120px;">Módulo</th>
                            <th>Registro</th>
                            <th style="width: 120px;">IP</th>
                            <th style="width: 80px;">Detalles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="fas fa-search fa-3x mb-3 opacity-25"></i>
                                    <p>No hay registros que coincidan con los filtros</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <small class="text-nowrap">
                                            <?= date('d/m/Y', strtotime($log['created_at'])) ?><br>
                                            <span class="text-muted"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary bg-opacity-10 rounded-circle me-2"
                                                 style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user text-primary"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($log['usuario_nombre']) ?></div>
                                                <small class="text-muted">ID: <?= $log['usuario_id'] ?? 'N/A' ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $accionColors = [
                                            'crear' => 'success',
                                            'editar' => 'info',
                                            'eliminar' => 'danger',
                                            'login' => 'primary',
                                            'logout' => 'secondary',
                                            'ver' => 'warning'
                                        ];
                                        $accionIcons = [
                                            'crear' => 'fa-plus',
                                            'editar' => 'fa-edit',
                                            'eliminar' => 'fa-trash',
                                            'login' => 'fa-sign-in-alt',
                                            'logout' => 'fa-sign-out-alt',
                                            'ver' => 'fa-eye'
                                        ];
                                        $color = $accionColors[$log['accion']] ?? 'secondary';
                                        $icon = $accionIcons[$log['accion']] ?? 'fa-question';
                                        ?>
                                        <span class="badge bg-<?= $color ?>">
                                            <i class="fas <?= $icon ?> me-1"></i>
                                            <?= ucfirst($log['accion']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($log['modulo']) ?></code>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['registro_titulo'])): ?>
                                            <div class="text-truncate" style="max-width: 250px;">
                                                <?= htmlspecialchars($log['registro_titulo']) ?>
                                            </div>
                                            <?php if (!empty($log['registro_id'])): ?>
                                                <small class="text-muted">ID: <?= $log['registro_id'] ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted font-monospace">
                                            <?= htmlspecialchars($log['ip_address']) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary btn-view-details"
                                                data-id="<?= $log['id'] ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalLogDetails">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación -->
        <?php if (isset($totalPaginas) && $totalPaginas > 1): ?>
            <div class="card-footer">
                <nav aria-label="Paginación">
                    <ul class="pagination justify-content-center mb-0">
                        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                            <li class="page-item <?= $i == ($paginaActual ?? 1) ? 'active' : '' ?>">
                                <a class="page-link" href="?route=admin/audit-log&page=<?= $i ?><?= http_build_query($filtros ?? []) ? '&' . http_build_query($filtros) : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Detalles del Log -->
<div class="modal fade" id="modalLogDetails" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>
                    Detalles del Registro
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailsContent">
                <div class="text-center text-muted py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ver detalles
    document.querySelectorAll('.btn-view-details').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;

            try {
                const response = await fetch(`?route=admin/audit-log/view/${id}`);
                const log = await response.json();

                let html = `
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Usuario:</strong><br>
                            ${log.usuario_nombre}
                        </div>
                        <div class="col-md-6">
                            <strong>Fecha/Hora:</strong><br>
                            ${new Date(log.created_at).toLocaleString('es-GT')}
                        </div>
                        <div class="col-md-6">
                            <strong>Acción:</strong><br>
                            <span class="badge bg-secondary">${log.accion}</span>
                        </div>
                        <div class="col-md-6">
                            <strong>Módulo:</strong><br>
                            <code>${log.modulo}</code>
                        </div>
                        <div class="col-md-6">
                            <strong>IP:</strong><br>
                            ${log.ip_address}
                        </div>
                        <div class="col-md-6">
                            <strong>Registro ID:</strong><br>
                            ${log.registro_id || 'N/A'}
                        </div>
                        <div class="col-12">
                            <strong>Título del Registro:</strong><br>
                            ${log.registro_titulo || 'N/A'}
                        </div>
                `;

                if (log.datos_anteriores) {
                    html += `
                        <div class="col-12">
                            <strong>Datos Anteriores:</strong>
                            <pre class="bg-light p-3 rounded"><code>${JSON.stringify(JSON.parse(log.datos_anteriores), null, 2)}</code></pre>
                        </div>
                    `;
                }

                if (log.datos_nuevos) {
                    html += `
                        <div class="col-12">
                            <strong>Datos Nuevos:</strong>
                            <pre class="bg-light p-3 rounded"><code>${JSON.stringify(JSON.parse(log.datos_nuevos), null, 2)}</code></pre>
                        </div>
                    `;
                }

                html += `
                        <div class="col-12">
                            <strong>User Agent:</strong><br>
                            <small class="text-muted">${log.user_agent}</small>
                        </div>
                    </div>
                `;

                document.getElementById('logDetailsContent').innerHTML = html;
            } catch (error) {
                document.getElementById('logDetailsContent').innerHTML = `
                    <div class="alert alert-danger">
                        Error al cargar los detalles del registro
                    </div>
                `;
            }
        });
    });

    // Exportar CSV
    document.getElementById('btnExportCSV').addEventListener('click', function() {
        const params = new URLSearchParams(window.location.search);
        const url = `?route=admin/audit-log/export-csv&${params.toString()}`;
        if (typeof AdminDownload === 'function') {
            AdminDownload(url, {
                filenameFallback: 'auditoria.csv',
                startMessage: 'Generando CSV...',
                errorMessage: 'Error al exportar auditoría'
            });
        }
    });

    // Limpiar registros antiguos
    document.getElementById('btnCleanOld').addEventListener('click', async function() {
        const dias = prompt('¿Eliminar registros con más de cuántos días de antigüedad?', '90');

        if (!dias || isNaN(dias)) return;

        if (!confirm(`¿Eliminar todos los registros con más de ${dias} días?`)) return;

        try {
            const response = await fetch(`?route=admin/audit-log/clean?dias=${dias}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>' })
            });

            const result = await response.json();

            if (result.success) {
                alert(`${result.eliminados} registros eliminados`);
                location.reload();
            } else {
                alert('Error al limpiar registros');
            }
        } catch (error) {
            alert('Error de conexión');
        }
    });
});
</script>

<style>
.avatar-sm {
    font-size: 14px;
}

pre {
    max-height: 400px;
    overflow-y: auto;
}

.table-sm td {
    vertical-align: middle;
}
</style>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
