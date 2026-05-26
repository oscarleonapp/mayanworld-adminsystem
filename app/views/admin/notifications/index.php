<?php
/**
 * Vista: Centro de Notificaciones
 * Sistema de notificaciones en tiempo real para admin
 */

$pageTitle = 'Notificaciones';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="admin-notifications">
    <?php
        $actionTitle = 'Centro de Notificaciones';
        $actionSubtitle = 'Gestiona y visualiza todas las notificaciones del sistema';
        $actionButtons = [
            ['label' => 'Marcar todo como leído', 'icon' => 'fas fa-check-double', 'variant' => 'outline-secondary', 'id' => 'btnMarkAllRead'],
            ['label' => 'Eliminar todo', 'icon' => 'fas fa-trash', 'variant' => 'outline-danger', 'id' => 'btnDeleteAll'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select class="form-select" id="filterTipo">
                        <option value="">Todos los tipos</option>
                        <option value="nueva_reserva">Nueva Reserva</option>
                        <option value="nuevo_mensaje">Nuevo Mensaje</option>
                        <option value="nuevo_usuario">Nuevo Usuario</option>
                        <option value="tour_sin_stock">Tour Sin Stock</option>
                        <option value="pago_recibido">Pago Recibido</option>
                        <option value="reserva_cancelada">Reserva Cancelada</option>
                        <option value="reseña_nueva">Nueva Reseña</option>
                        <option value="sistema">Sistema</option>
                        <option value="alerta">Alerta</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Prioridad</label>
                    <select class="form-select" id="filterPrioridad">
                        <option value="">Todas las prioridades</option>
                        <option value="urgente">Urgente</option>
                        <option value="alta">Alta</option>
                        <option value="media">Media</option>
                        <option value="baja">Baja</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select class="form-select" id="filterLeida">
                        <option value="">Todas</option>
                        <option value="0">No leídas</option>
                        <option value="1">Leídas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-secondary w-100" id="btnRefresh">
                        <i class="fas fa-sync-alt me-2"></i>
                        Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 rounded p-3">
                                <i class="fas fa-bell text-danger fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">No Leídas</div>
                            <div class="h4 mb-0" id="countUnread"><?= $stats['no_leidas'] ?? 0 ?></div>
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
                                <i class="fas fa-exclamation-triangle text-warning fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Alta Prioridad</div>
                            <div class="h4 mb-0"><?= $stats['alta_prioridad'] ?? 0 ?></div>
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
                            <div class="text-muted small">Hoy</div>
                            <div class="h4 mb-0"><?= $stats['hoy'] ?? 0 ?></div>
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
                                <i class="fas fa-inbox text-success fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total</div>
                            <div class="h4 mb-0"><?= $stats['total'] ?? 0 ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de notificaciones -->
    <div class="card">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Todas las Notificaciones</h5>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="toggleAutoRefresh" checked>
                    <label class="form-check-label" for="toggleAutoRefresh">
                        Auto-actualizar (30s)
                    </label>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="notificationsList">
                <?php if (empty($notificaciones)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-bell-slash fa-3x mb-3 opacity-25"></i>
                        <p>No tienes notificaciones</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notificaciones as $notif): ?>
                        <?php
                        $prioridadColors = [
                            'urgente' => 'danger',
                            'alta' => 'warning',
                            'media' => 'info',
                            'baja' => 'secondary'
                        ];
                        $color = $prioridadColors[$notif['prioridad']] ?? 'secondary';
                        ?>
                        <div class="notification-item border-bottom p-3 <?= $notif['leida'] ? 'bg-light' : 'bg-white' ?>"
                             data-id="<?= $notif['id'] ?>"
                             data-tipo="<?= htmlspecialchars($notif['tipo']) ?>"
                             data-prioridad="<?= htmlspecialchars($notif['prioridad']) ?>"
                             data-leida="<?= $notif['leida'] ?>">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="notification-icon bg-<?= $color ?> bg-opacity-10 rounded-circle p-3"
                                         style="width: 50px; height: 50px; display: flex; align-items: center; justify-content: center;">
                                        <i class="<?= htmlspecialchars($notif['icono']) ?> text-<?= $color ?>"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">
                                                <?= htmlspecialchars($notif['titulo']) ?>
                                                <?php if (!$notif['leida']): ?>
                                                    <span class="badge bg-primary rounded-pill ms-2">Nueva</span>
                                                <?php endif; ?>
                                            </h6>
                                            <p class="text-muted mb-2"><?= htmlspecialchars($notif['mensaje']) ?></p>
                                            <div class="d-flex align-items-center gap-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= date('d/m/Y H:i', strtotime($notif['created_at'])) ?>
                                                </small>
                                                <span class="badge bg-<?= $color ?>">
                                                    <?= ucfirst($notif['prioridad']) ?>
                                                </span>
                                                <span class="badge bg-secondary">
                                                    <?= ucfirst(str_replace('_', ' ', $notif['tipo'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="btn-group btn-group-sm">
                                            <?php if (!empty($notif['url'])): ?>
                                                <a href="<?= htmlspecialchars($notif['url']) ?>"
                                                   class="btn btn-outline-primary btn-view"
                                                   title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            <?php if (!$notif['leida']): ?>
                                                <button class="btn btn-outline-success btn-mark-read"
                                                        data-id="<?= $notif['id'] ?>"
                                                        title="Marcar como leída">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn btn-outline-danger btn-delete-notif"
                                                    data-id="<?= $notif['id'] ?>"
                                                    title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Paginación (si aplica) -->
            <?php if (isset($totalPaginas) && $totalPaginas > 1): ?>
                <div class="p-3 border-top">
                    <nav aria-label="Paginación de notificaciones">
                        <ul class="pagination justify-content-center mb-0">
                            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                                <li class="page-item <?= $i == ($paginaActual ?? 1) ? 'active' : '' ?>">
                                    <a class="page-link" href="?route=admin/notifications&page=<?= $i ?>">
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let autoRefreshInterval = null;

    // Marcar todas como leídas
    document.getElementById('btnMarkAllRead').addEventListener('click', async function() {
        if (!confirm('¿Marcar todas las notificaciones como leídas?')) return;

        try {
            const response = await fetch('?route=admin/notifications/mark-all-read', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>' })
            });

            const result = await response.json();

            if (result.success) {
                showToast('Todas las notificaciones marcadas como leídas', 'success');
                location.reload();
            } else {
                showToast(result.message || 'Error', 'error');
            }
        } catch (error) {
            showToast('Error de conexión', 'error');
        }
    });

    // Eliminar todas
    document.getElementById('btnDeleteAll').addEventListener('click', async function() {
        if (!confirm('¿Eliminar TODAS las notificaciones? Esta acción no se puede deshacer.')) return;

        try {
            const response = await fetch('?route=admin/notifications/delete-all', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>' })
            });

            const result = await response.json();

            if (result.success) {
                showToast('Todas las notificaciones eliminadas', 'success');
                location.reload();
            } else {
                showToast(result.message || 'Error', 'error');
            }
        } catch (error) {
            showToast('Error de conexión', 'error');
        }
    });

    // Marcar como leída individual
    document.querySelectorAll('.btn-mark-read').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.stopPropagation();
            const id = this.dataset.id;

            try {
                const response = await fetch(`?route=admin/notifications/mark-read/${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>' })
                });

                const result = await response.json();

                if (result.success) {
                    const item = this.closest('.notification-item');
                    item.classList.remove('bg-white');
                    item.classList.add('bg-light');
                    item.querySelector('.badge.bg-primary')?.remove();
                    this.remove();

                    updateUnreadCount();
                } else {
                    showToast('Error al marcar como leída', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        });
    });

    // Eliminar notificación individual
    document.querySelectorAll('.btn-delete-notif').forEach(btn => {
        btn.addEventListener('click', async function(e) {
            e.stopPropagation();
            const id = this.dataset.id;

            if (!confirm('¿Eliminar esta notificación?')) return;

            try {
                const response = await fetch(`?route=admin/notifications/delete/${id}`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>' })
                });

                const result = await response.json();

                if (result.success) {
                    this.closest('.notification-item').remove();
                    updateUnreadCount();
                } else {
                    showToast('Error al eliminar', 'error');
                }
            } catch (error) {
                showToast('Error de conexión', 'error');
            }
        });
    });

    // Filtros
    ['filterTipo', 'filterPrioridad', 'filterLeida'].forEach(id => {
        document.getElementById(id).addEventListener('change', filterNotifications);
    });

    function filterNotifications() {
        const tipo = document.getElementById('filterTipo').value;
        const prioridad = document.getElementById('filterPrioridad').value;
        const leida = document.getElementById('filterLeida').value;

        document.querySelectorAll('.notification-item').forEach(item => {
            const itemTipo = item.dataset.tipo;
            const itemPrioridad = item.dataset.prioridad;
            const itemLeida = item.dataset.leida;

            const matchTipo = tipo === '' || itemTipo === tipo;
            const matchPrioridad = prioridad === '' || itemPrioridad === prioridad;
            const matchLeida = leida === '' || itemLeida === leida;

            item.style.display = matchTipo && matchPrioridad && matchLeida ? '' : 'none';
        });
    }

    // Auto-refresh
    document.getElementById('toggleAutoRefresh').addEventListener('change', function() {
        if (this.checked) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    });

    document.getElementById('btnRefresh').addEventListener('click', function() {
        location.reload();
    });

    function startAutoRefresh() {
        autoRefreshInterval = setInterval(() => {
            updateNotifications();
        }, 30000); // 30 segundos
    }

    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    }

    async function updateNotifications() {
        try {
            const response = await fetch('?route=admin/notifications/get-unread');
            const result = await response.json();

            if (result.count > 0) {
                updateUnreadCount(result.count);
                // Opcionalmente, mostrar toast de nuevas notificaciones
            }
        } catch (error) {
            console.error('Error updating notifications:', error);
        }
    }

    function updateUnreadCount(count) {
        if (typeof count === 'undefined') {
            fetch('?route=admin/notifications/count-unread')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('countUnread').textContent = data.count;
                });
        } else {
            document.getElementById('countUnread').textContent = count;
        }
    }

    // Iniciar auto-refresh si está habilitado
    if (document.getElementById('toggleAutoRefresh').checked) {
        startAutoRefresh();
    }

    function showToast(message, type) {
        alert(message); // Implementar toast
    }
});
</script>

<style>
.notification-item {
    transition: all 0.2s ease;
}

.notification-item:hover {
    background-color: #f8f9fa !important;
}

.notification-icon {
    font-size: 20px;
}
</style>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
