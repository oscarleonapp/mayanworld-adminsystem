<?php
use App\Core\Config;
use App\Core\Helpers;
include __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <?php 
      $actionTitle = 'Gestión de Mensajes';
      $actionSubtitle = 'Administra consultas y mensajes de soporte';
      $newCount = array_sum(array_map(function($m){ return ($m['estado'] ?? '') === 'nuevo' ? 1 : 0; }, $messages ?? []));
      $actionButtons = [
        ['label' => 'Marcar Todos Leídos', 'icon' => 'fas fa-check-double', 'variant' => 'outline-secondary', 'onclick' => 'markAllAsRead()', 'badge' => $newCount, 'badgeClass' => 'bg-warning text-dark'],
        ['label' => 'Actualizar', 'icon' => 'fas fa-sync-alt', 'variant' => 'outline-primary', 'onclick' => 'refreshMessages()'],
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
                                Nuevos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= array_sum(array_map(function($m) { return $m['estado'] == 'nuevo' ? 1 : 0; }, $messages)) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
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
                                Leídos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= array_sum(array_map(function($m) { return $m['estado'] == 'leido' ? 1 : 0; }, $messages)) ?>
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
                                Resueltos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= array_sum(array_map(function($m) { return $m['estado'] == 'resuelto' ? 1 : 0; }, $messages)) ?>
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
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Mensajes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= count($messages) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-comments fa-2x text-gray-300"></i>
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
                <input type="hidden" name="route" value="admin/messages">
                
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
                               placeholder="Nombre, email, asunto..." 
                               value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label">Estado</label>
                    <select class="form-select" name="status" id="status">
                        <option value="">Todos los estados</option>
                        <option value="nuevo" <?= ($_GET['status'] ?? '') == 'nuevo' ? 'selected' : '' ?>>Nuevo</option>
                        <option value="en_proceso" <?= ($_GET['status'] ?? '') == 'en_proceso' ? 'selected' : '' ?>>En Proceso</option>
                        <option value="resuelto" <?= ($_GET['status'] ?? '') == 'resuelto' ? 'selected' : '' ?>>Resuelto</option>
                        <option value="cerrado" <?= ($_GET['status'] ?? '') == 'cerrado' ? 'selected' : '' ?>>Cerrado</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="priority" class="form-label">Prioridad</label>
                    <select class="form-select" name="priority" id="priority">
                        <option value="">Todas</option>
                        <option value="alta" <?= ($_GET['priority'] ?? '') == 'alta' ? 'selected' : '' ?>>Alta</option>
                        <option value="media" <?= ($_GET['priority'] ?? '') == 'media' ? 'selected' : '' ?>>Media</option>
                        <option value="baja" <?= ($_GET['priority'] ?? '') == 'baja' ? 'selected' : '' ?>>Baja</option>
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

    <!-- Messages Table -->
    <div class="card shadow">
        <div class="card-header bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    Lista de Mensajes (<?= number_format(count($messages)) ?>)
                </h6>
                <div class="d-flex gap-2">
                    <!-- Quick Filters -->
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/messages&status=nuevo" 
                           class="btn btn-<?= ($_GET['status'] ?? '') == 'nuevo' ? 'primary' : 'outline-primary' ?>">
                            Nuevos
                        </a>
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/messages&status=en_proceso" 
                           class="btn btn-<?= ($_GET['status'] ?? '') == 'en_proceso' ? 'primary' : 'outline-primary' ?>">
                            En Proceso
                        </a>
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/messages" 
                           class="btn btn-<?= empty($_GET['status']) ? 'primary' : 'outline-primary' ?>">
                            Todos
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body p-0">
            <?php if (!empty($messages)): ?>
            <div class="table-responsive">
                <table class="table table-hover table-sticky align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="40">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th width="40">Estado</th>
                            <th>Cliente</th>
                            <th>Asunto</th>
                            <th width="100">Reserva</th>
                            <th width="80">Prioridad</th>
                            <th width="120">Fecha</th>
                            <th width="120">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                        <tr class="message-row <?= $message['estado'] == 'nuevo' ? 'table-warning' : '' ?>" 
                            data-message-id="<?= $message['id'] ?>">
                            <td>
                                <div class="form-check">
                                    <input class="form-check-input message-checkbox" 
                                           type="checkbox" 
                                           value="<?= $message['id'] ?>">
                                </div>
                            </td>
                            <td>
                                <?php if ($message['estado'] == 'nuevo'): ?>
                                    <i class="fas fa-circle text-warning" title="Nuevo"></i>
                                <?php elseif ($message['estado'] == 'en_proceso'): ?>
                                    <i class="fas fa-clock text-info" title="En proceso"></i>
                                <?php elseif ($message['estado'] == 'resuelto'): ?>
                                    <i class="fas fa-check-circle text-success" title="Resuelto"></i>
                                <?php else: ?>
                                    <i class="fas fa-times-circle text-secondary" title="Cerrado"></i>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div>
                                    <strong class="d-block"><?= htmlspecialchars($message['nombre']) ?></strong>
                                    <small class="text-muted d-block"><?= htmlspecialchars($message['email']) ?></small>
                                    <?php if ($message['telefono']): ?>
                                    <small class="text-muted"><?= htmlspecialchars($message['telefono']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($message['asunto'] ?? '') ?></div>
                                    <div class="text-muted small">
                                        <?= htmlspecialchars(Helpers::truncate($message['mensaje'], 100)) ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ($message['reserva_id']): ?>
                                    <span class="badge bg-info">
                                        <?= htmlspecialchars($message['reserva_codigo'] ?? 'R-' . $message['reserva_id']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $priorityClass = 'secondary';
                                $priorityText = 'Media';
                                
                                // Determinar prioridad basada en palabras clave o tiempo
                                $urgentWords = ['urgente', 'problema', 'error', 'cancelar', 'reembolso'];
                                $messageText = strtolower($message['asunto'] . ' ' . $message['mensaje']);
                                
                                if (strpos($messageText, 'urgente') !== false) {
                                    $priorityClass = 'danger';
                                    $priorityText = 'Alta';
                                } elseif ($message['estado'] == 'nuevo' && strtotime($message['created_at']) < strtotime('-24 hours')) {
                                    $priorityClass = 'warning';
                                    $priorityText = 'Media';
                                } else {
                                    $priorityClass = 'success';
                                    $priorityText = 'Baja';
                                }
                                ?>
                                <span class="badge bg-<?= $priorityClass ?>"><?= $priorityText ?></span>
                            </td>
                            <td class="small text-muted">
                                <div><?= date('d/m/Y', strtotime($message['created_at'])) ?></div>
                                <div><?= date('H:i', strtotime($message['created_at'])) ?></div>
                                <div class="text-muted small">
                                    <?php 
                                    $timeAgo = time() - strtotime($message['created_at']);
                                    if ($timeAgo < 3600) {
                                        echo 'Hace ' . floor($timeAgo / 60) . ' min';
                                    } elseif ($timeAgo < 86400) {
                                        echo 'Hace ' . floor($timeAgo / 3600) . ' h';
                                    } else {
                                        echo 'Hace ' . floor($timeAgo / 86400) . ' días';
                                    }
                                    ?>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-info" 
                                            onclick="viewMessage(<?= $message['id'] ?>)" 
                                            title="Ver mensaje completo">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <button class="btn btn-outline-success" 
                                            onclick="replyMessage(<?= $message['id'] ?>)" 
                                            title="Responder">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary dropdown-toggle" 
                                                data-bs-toggle="dropdown" 
                                                title="Más acciones">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <?php if ($message['estado'] == 'nuevo'): ?>
                                            <li>
                                                <button class="dropdown-item" onclick="updateMessageStatus(<?= $message['id'] ?>, 'en_proceso')">
                                                    <i class="fas fa-play me-2 text-info"></i>Marcar En Proceso
                                                </button>
                                            </li>
                                            <?php endif; ?>
                                            
                                            <?php if ($message['estado'] != 'resuelto'): ?>
                                            <li>
                                                <button class="dropdown-item" onclick="updateMessageStatus(<?= $message['id'] ?>, 'resuelto')">
                                                    <i class="fas fa-check me-2 text-success"></i>Marcar Resuelto
                                                </button>
                                            </li>
                                            <?php endif; ?>
                                            
                                            <?php if ($message['reserva_id']): ?>
                                            <li>
                                                <a class="dropdown-item" 
                                                   href="<?= Config::getBaseUrl() ?>?route=admin/bookings&search=<?= urlencode($message['reserva_codigo'] ?? '') ?>">
                                                    <i class="fas fa-calendar me-2"></i>Ver Reserva
                                                </a>
                                            </li>
                                            <?php endif; ?>
                                            
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item text-danger" onclick="deleteMessage(<?= $message['id'] ?>)">
                                                    <i class="fas fa-trash me-2"></i>Eliminar
                                                </button>
                                            </li>
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
            <!-- No Messages Found -->
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-4"></i>
                <h4 class="text-muted mb-3">No hay mensajes</h4>
                <p class="text-muted mb-4">
                    <?php if (!empty($_GET['search']) || !empty($_GET['status'])): ?>
                        No hay mensajes que coincidan con los filtros aplicados.
                    <?php else: ?>
                        No hay mensajes de soporte en el sistema.
                    <?php endif; ?>
                </p>
                <?php if (!empty($_GET['search']) || !empty($_GET['status'])): ?>
                <a href="<?= Config::getBaseUrl() ?>?route=admin/messages" class="btn btn-outline-primary">
                    <i class="fas fa-times me-2"></i>Limpiar Filtros
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($messages) && $pagination['total_pages'] > 1): ?>
        <!-- Pagination -->
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Mostrando <?= count($messages) ?> de <?= number_format($pagination['total']) ?> mensajes
                </div>
                
                <nav aria-label="Navegación de mensajes">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($pagination['current_page'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/messages&page=<?= $pagination['current_page'] - 1 ?>">
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
                            <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/messages&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=admin/messages&page=<?= $pagination['current_page'] + 1 ?>">
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

<!-- Message Details Modal -->
<div class="modal fade" id="messageModal" tabindex="-1" aria-labelledby="messageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="messageModalLabel">Detalles del Mensaje</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="message-content">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" onclick="replyFromModal()">
                    <i class="fas fa-reply me-2" aria-hidden="true"></i>Responder
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="replyModalLabel">Responder Mensaje</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="reply-form">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reply-to" class="form-label">Para:</label>
                        <input type="text" class="form-control" id="reply-to" readonly>
                        <input type="hidden" id="reply-message-id" name="message_id">
                    </div>
                    
                    <div class="mb-3">
                        <label for="reply-subject" class="form-label">Asunto:</label>
                        <input type="text" class="form-control" id="reply-subject" name="subject" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reply-message" class="form-label">Respuesta:</label>
                        <textarea class="form-control" id="reply-message" name="response" rows="6" 
                                  placeholder="Escribe tu respuesta aquí..." required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="mark-resolved" name="mark_resolved" value="1" checked>
                            <label class="form-check-label" for="mark-resolved">
                                Marcar como resuelto después de enviar
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-paper-plane me-2" aria-hidden="true"></i>Enviar Respuesta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Custom CSS moved to public/assets/css/admin.css -->

<!-- JavaScript -->
<script>
let currentMessageId = null;

function viewMessage(messageId) {
    currentMessageId = messageId;
    const modal = new bootstrap.Modal(document.getElementById('messageModal'));
    const content = document.getElementById('message-content');
    
    content.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    fetch(`<?= Config::getBaseUrl() ?>?route=admin/message/${messageId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const message = data.message;
                content.innerHTML = `
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-bold">Información del Cliente</h6>
                            <div><strong>Nombre:</strong> ${message.nombre}</div>
                            <div><strong>Email:</strong> ${message.email}</div>
                            <div><strong>Teléfono:</strong> ${message.telefono || 'No proporcionado'}</div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-bold">Detalles del Mensaje</h6>
                            <div><strong>Fecha:</strong> ${new Date(message.created_at).toLocaleString('es-ES')}</div>
                            <div><strong>Estado:</strong> <span class="badge badge-status badge-status--${message.estado}">${message.estado.replace('_',' ')}</span></div>
                            ${message.reserva_id ? `<div><strong>Reserva:</strong> <span class="badge bg-info">${message.reserva_codigo || 'R-' + message.reserva_id}</span></div>` : ''}
                        </div>
                    </div>
                    <div class="mb-3">
                        <h6 class="fw-bold">Asunto</h6>
                        <div class="bg-light p-3 rounded preline">${message.asunto}</div>
                    </div>
                    <div class="mb-3">
                        <h6 class="fw-bold">Mensaje</h6>
                        <div class="bg-light p-3 rounded preline">${message.mensaje}</div>
                    </div>
                    ${message.respuesta ? `
                        <div class="mb-3">
                            <h6 class="fw-bold text-success">Respuesta Enviada</h6>
                            <div class="bg-success-subtle p-3 rounded preline">${message.respuesta}</div>
                            <small class="text-muted">Respondido el ${new Date(message.respuesta_fecha).toLocaleString('es-ES')}</small>
                        </div>
                    ` : ''}
                `;
                
                // Mark as read if it's new
                if (message.estado === 'nuevo') {
                    updateMessageStatus(messageId, 'leido', false);
                }
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error al cargar el mensaje: ${data.message}
                    </div>
                `;
            }
        })
        .catch(error => {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error al cargar el mensaje
                </div>
            `;
            console.error('Error:', error);
        });
}

function replyMessage(messageId) {
    currentMessageId = messageId;
    
    // Fetch message details for reply
    fetch(`<?= Config::getBaseUrl() ?>?route=admin/message/${messageId}/details`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const message = data.message;
                
                document.getElementById('reply-to').value = `${message.nombre} <${message.email}>`;
                document.getElementById('reply-subject').value = `Re: ${message.asunto}`;
                document.getElementById('reply-message-id').value = messageId;
                
                const modal = new bootstrap.Modal(document.getElementById('replyModal'));
                modal.show();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los datos del mensaje');
        });
}

function replyFromModal() {
    if (currentMessageId) {
        // Close current modal properly
        const messageModal = bootstrap.Modal.getInstance(document.getElementById('messageModal'));
        if (messageModal) {
            // Listen for modal fully hidden before opening reply modal
            const modalEl = document.getElementById('messageModal');
            modalEl.addEventListener('hidden.bs.modal', function onHidden() {
                modalEl.removeEventListener('hidden.bs.modal', onHidden);
                replyMessage(currentMessageId);
            });
            messageModal.hide();
        } else {
            replyMessage(currentMessageId);
        }
    }
}

function updateMessageStatus(messageId, newStatus, showConfirm = true) {
    if (showConfirm && !confirm(`¿Cambiar estado a "${newStatus.replace('_', ' ')}"?`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('message_id', messageId);
    formData.append('status', newStatus);
    
    fetch('<?= Config::getBaseUrl() ?>?route=admin/update-message-status', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (window.AdminUI) AdminUI.toast('Estado actualizado', 'success');
            if (showConfirm) setTimeout(() => location.reload(), 400);
        } else {
            if (window.AdminUI) AdminUI.toast('Error: ' + data.message, 'danger'); else alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.AdminUI) AdminUI.toast('Error al actualizar el estado', 'danger'); else alert('Error al actualizar el estado');
    });
}

function deleteMessage(messageId) {
    if (!confirm('¿Estás seguro de eliminar este mensaje?\n\nEsta acción no se puede deshacer.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('message_id', messageId);
    
    fetch('<?= Config::getBaseUrl() ?>?route=admin/delete-message', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (window.AdminUI) AdminUI.toast('Mensaje eliminado', 'success');
            setTimeout(() => location.reload(), 400);
        } else {
            if (window.AdminUI) AdminUI.toast('Error: ' + data.message, 'danger'); else alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.AdminUI) AdminUI.toast('Error al eliminar el mensaje', 'danger'); else alert('Error al eliminar el mensaje');
    });
}

function markAllAsRead() {
    if (!confirm('¿Marcar todos los mensajes como leídos?')) {
        return;
    }
    
    fetch('<?= Config::getBaseUrl() ?>?route=admin/messages/mark-all-read', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (window.AdminUI) AdminUI.toast('Todos los mensajes marcados como leídos', 'success');
            setTimeout(() => location.reload(), 400);
        } else {
            if (window.AdminUI) AdminUI.toast('Error: ' + data.message, 'danger'); else alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.AdminUI) AdminUI.toast('Error al actualizar los mensajes', 'danger'); else alert('Error al actualizar los mensajes');
    });
}

function refreshMessages() {
    location.reload();
}

// Reply form submission
document.getElementById('reply-form')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('<?= Config::getBaseUrl() ?>?route=admin/reply-message', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const replyModal = bootstrap.Modal.getInstance(document.getElementById('replyModal'));
            if (replyModal) {
                // Wait for modal to fully close before reloading
                const replyModalEl = document.getElementById('replyModal');
                replyModalEl.addEventListener('hidden.bs.modal', function onHidden() {
                    replyModalEl.removeEventListener('hidden.bs.modal', onHidden);
                    if (window.AdminUI) AdminUI.toast('Respuesta enviada exitosamente', 'success');
                    setTimeout(() => location.reload(), 500);
                });
                replyModal.hide();
            } else {
                alert('Respuesta enviada exitosamente');
                location.reload();
            }
        } else {
            if (window.AdminUI) AdminUI.toast('Error: ' + data.message, 'danger'); else alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.AdminUI) AdminUI.toast('Error al enviar la respuesta', 'danger'); else alert('Error al enviar la respuesta');
    });
});

// Row click to view message
document.querySelectorAll('.message-row').forEach(row => {
    row.addEventListener('click', function(e) {
        if (!e.target.closest('.btn') && !e.target.closest('.dropdown') && !e.target.closest('.form-check')) {
            const messageId = this.dataset.messageId;
            viewMessage(messageId);
        }
    });
});

// Select all functionality
document.getElementById('select-all')?.addEventListener('change', function() {
    document.querySelectorAll('.message-checkbox').forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Auto-refresh every 30 seconds for new messages
setInterval(function() {
    const newMessagesBadge = document.querySelector('.border-left-warning .h5');
    if (newMessagesBadge && parseInt(newMessagesBadge.textContent) > 0) {
        // Only refresh if we're on the first page and no filters
        const url = new URL(window.location);
        if (!url.searchParams.get('page') && !url.searchParams.get('search') && !url.searchParams.get('status')) {
            location.reload();
        }
    }
}, 30000);
</script>

<?php include __DIR__ . '/../layouts/admin_footer.php'; ?>
