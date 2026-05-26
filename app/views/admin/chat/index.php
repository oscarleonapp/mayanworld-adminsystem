<?php
use App\Core\Config;
if (!defined('BASE_PATH')) {
    http_response_code(403);
    exit('Direct access not allowed');
}

$title = $title ?? 'Panel de Chat - Admin';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="container-fluid">
    <?php
        $actionTitle = 'Panel de Chat';
        $actionSubtitle = 'Gestiona conversaciones activas en tiempo real';
        $actionButtons = [];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <div class="d-flex justify-content-end mb-4">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-outline-primary active" data-filter="activa">
                <i class="fas fa-circle text-success"></i> Activas
                <span class="badge bg-success ms-1" id="activeCount">0</span>
            </button>
            <button type="button" class="btn btn-outline-warning" data-filter="en_espera">
                <i class="fas fa-clock text-warning"></i> En Espera
                <span class="badge bg-warning ms-1" id="waitingCount">0</span>
            </button>
            <button type="button" class="btn btn-outline-secondary" data-filter="cerrada">
                <i class="fas fa-archive text-secondary"></i> Cerradas
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Lista de Conversaciones -->
        <div class="col-md-4 col-lg-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Conversaciones
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="conversationsList">
                        <!-- Conversaciones se cargarán aquí -->
                        <div class="text-center p-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                            <p class="mt-2 text-muted">Cargando conversaciones...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas Rápidas -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Estadísticas</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h4 class="text-success mb-0" id="totalActiveConversations">0</h4>
                            <small class="text-muted">Activas</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-warning mb-0" id="totalWaitingConversations">0</h4>
                            <small class="text-muted">En Espera</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operadores Disponibles -->
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="fas fa-users"></i> Operadores</h6>
                </div>
                <div class="card-body p-2" id="operatorsList">
                    <!-- Operadores se cargarán aquí -->
                </div>
            </div>
        </div>

        <!-- Chat Principal -->
        <div class="col-md-8 col-lg-9">
            <div class="card shadow-sm h-100">
                <!-- Estado de conversación no seleccionada -->
                <div id="noChatSelected" class="d-flex align-items-center justify-content-center h-100">
                    <div class="text-center text-muted">
                        <i class="fas fa-comments fa-3x mb-3"></i>
                        <h4>Selecciona una conversación</h4>
                        <p>Elige una conversación de la lista para comenzar a chatear</p>
                    </div>
                </div>

                <!-- Chat activo -->
                <div id="chatContainer" class="d-none">
                    <!-- Header del chat -->
                    <div class="card-header bg-light border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="avatar-circle bg-primary text-white me-3">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0" id="chatClientName">Cliente</h6>
                                    <small class="text-muted">
                                        <span id="chatStatus">Estado</span> • 
                                        <span id="chatTime">Tiempo</span>
                                    </small>
                                </div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-cog"></i> Acciones
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="transferConversation(); return false;">
                                        <i class="fas fa-exchange-alt"></i> Transferir
                                    </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="closeConversation(); return false;">
                                        <i class="fas fa-times-circle"></i> Cerrar
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" onclick="viewCustomerInfo(); return false;">
                                        <i class="fas fa-info-circle"></i> Info del Cliente
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Mensajes -->
                    <div class="card-body p-0 position-relative">
                        <div id="messagesContainer" class="chat-messages p-3" style="height: 400px; overflow-y: auto;">
                            <!-- Mensajes se cargarán aquí -->
                        </div>
                        
                        <!-- Indicador de escritura -->
                        <div id="typingIndicator" class="px-3 py-2 d-none">
                            <div class="typing-indicator">
                                <span class="typing-dot"></span>
                                <span class="typing-dot"></span>
                                <span class="typing-dot"></span>
                            </div>
                            <small class="text-muted ms-2">El cliente está escribiendo...</small>
                        </div>
                    </div>

                    <!-- Input de mensaje -->
                    <div class="card-footer">
                        <form id="messageForm" class="d-flex gap-2">
                            <input type="hidden" id="currentConversationId" value="">
                            <input type="text" 
                                   class="form-control" 
                                   id="messageInput" 
                                   placeholder="Escribe tu respuesta..."
                                   autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary" onclick="attachFile()">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                        <input type="file" id="fileInput" class="d-none" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para transferir conversación -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transferir Conversación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="transferForm">
                    <div class="mb-3">
                        <label for="targetOperator" class="form-label">Transferir a:</label>
                        <select class="form-select" id="targetOperator" required>
                            <option value="">Seleccionar operador...</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="transferReason" class="form-label">Motivo (opcional):</label>
                        <textarea class="form-control" id="transferReason" rows="3" placeholder="Explica el motivo de la transferencia..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="executeTransfer()">Transferir</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de información del cliente -->
<div class="modal fade" id="customerInfoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Información del Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="customerInfoContent">
                <!-- Información del cliente se cargará aquí -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.chat-messages {
    background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
}

.message {
    margin-bottom: 15px;
}

.message-operator {
    display: flex;
    justify-content: flex-end;
}

.message-cliente {
    display: flex;
    justify-content: flex-start;
}

.message-sistema {
    display: flex;
    justify-content: center;
}

.message-content {
    max-width: 70%;
    padding: 10px 15px;
    border-radius: 20px;
    word-wrap: break-word;
}

.message-operator .message-content {
    background: #007bff;
    color: white;
    border-bottom-right-radius: 5px;
}

.message-cliente .message-content {
    background: #e9ecef;
    color: #333;
    border-bottom-left-radius: 5px;
}

.message-sistema .message-content {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
    font-style: italic;
    border-radius: 10px;
    max-width: 90%;
    text-align: center;
}

.message-time {
    font-size: 11px;
    opacity: 0.7;
    margin-top: 5px;
}

.typing-indicator {
    display: inline-flex;
    align-items: center;
    gap: 3px;
}

.typing-dot {
    width: 6px;
    height: 6px;
    background: #007bff;
    border-radius: 50%;
    animation: typingBounce 1.4s infinite ease-in-out;
}

.typing-dot:nth-child(1) { animation-delay: -0.32s; }
.typing-dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes typingBounce {
    0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
    40% { transform: scale(1); opacity: 1; }
}

.conversation-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.conversation-item:hover {
    background-color: #f8f9fa !important;
}

.conversation-item.active {
    background-color: #e3f2fd !important;
    border-left: 4px solid #007bff;
}

.unread-badge {
    background: linear-gradient(45deg, #ff6b6b, #ee5a24);
    color: white;
    font-size: 10px;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.operator-status {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
}

.status-online { background-color: #28a745; }
.status-busy { background-color: #ffc107; }
.status-offline { background-color: #6c757d; }

@media (max-width: 768px) {
    .row {
        margin: 0;
    }
    
    .col-md-4 {
        display: none;
    }
    
    .col-md-4.show-mobile {
        display: block;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        background: white;
        z-index: 1050;
        overflow-y: auto;
    }
    
    .col-md-8 {
        padding: 0;
    }
}
</style>

<script>
// Variables globales
let currentConversationId = null;
let lastMessageId = 0;
let conversationsData = [];
let operatorsData = [];
let pollingInterval;

// Inicializar cuando el documento esté listo
document.addEventListener('DOMContentLoaded', function() {
    loadConversations();
    loadOperators();
    setupEventListeners();
    startPolling();
});

// Configurar event listeners
function setupEventListeners() {
    // Filtros de conversación
    document.querySelectorAll('[data-filter]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-filter]').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            filterConversations(this.dataset.filter);
        });
    });

    // Form de mensaje
    document.getElementById('messageForm').addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });

    // Input de mensaje - Enter para enviar
    document.getElementById('messageInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
}

// Cargar lista de conversaciones
async function loadConversations(status = 'activa') {
    try {
        const response = await fetch(`?route=admin/chat/conversations&status=${status}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        if (!response.ok) {
            throw new Error('Error al cargar conversaciones');
        }
        
        const data = await response.json();
        conversationsData = data.conversations || [];
        renderConversations(conversationsData);
        updateStats(data.stats || {});
        
    } catch (error) {
        console.error('Error cargando conversaciones:', error);
        showError('Error al cargar las conversaciones');
    }
}

// Renderizar lista de conversaciones
function renderConversations(conversations) {
    const container = document.getElementById('conversationsList');
    
    if (!conversations || conversations.length === 0) {
        container.innerHTML = `
            <div class="text-center p-4">
                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                <p class="text-muted">No hay conversaciones</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = conversations.map(conv => `
        <div class="list-group-item list-group-item-action conversation-item" 
             data-conversation-id="${conv.id}"
             onclick="selectConversation(${conv.id})">
            <div class="d-flex justify-content-between align-items-start">
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-1">${conv.cliente_nombre || 'Cliente Anónimo'}</h6>
                        ${conv.mensajes_no_leidos > 0 ? 
                            `<span class="unread-badge">${conv.mensajes_no_leidos}</span>` : 
                            ''
                        }
                    </div>
                    <p class="mb-1 text-muted small">${truncateText(conv.ultimo_mensaje || 'Sin mensajes', 50)}</p>
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-muted">${formatTime(conv.ultimo_mensaje_tiempo)}</small>
                        <span class="badge bg-${getStatusColor(conv.estado)} rounded-pill">${conv.estado}</span>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

// Cargar operadores disponibles
async function loadOperators() {
    try {
        const response = await fetch('?route=admin/chat/operators', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
            }
        });
        
        if (!response.ok) {
            throw new Error('Error al cargar operadores');
        }
        
        const data = await response.json();
        operatorsData = data.operators || [];
        renderOperators(operatorsData);
        
    } catch (error) {
        console.error('Error cargando operadores:', error);
    }
}

// Renderizar lista de operadores
function renderOperators(operators) {
    const container = document.getElementById('operatorsList');
    const transferSelect = document.getElementById('targetOperator');
    
    if (!operators || operators.length === 0) {
        container.innerHTML = '<p class="text-muted small">No hay operadores disponibles</p>';
        return;
    }
    
    container.innerHTML = operators.map(op => `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="d-flex align-items-center">
                <span class="operator-status status-online"></span>
                <span class="small">${op.nombre}</span>
            </div>
            <span class="badge bg-primary rounded-pill small">${op.conversaciones_activas || 0}</span>
        </div>
    `).join('');
    
    // Actualizar select de transferencia
    transferSelect.innerHTML = '<option value="">Seleccionar operador...</option>' +
        operators.map(op => `<option value="${op.id}">${op.nombre} (${op.conversaciones_activas || 0} activas)</option>`).join('');
}

// Seleccionar conversación
async function selectConversation(conversationId) {
    if (currentConversationId === conversationId) return;
    
    // Marcar como activa en la lista
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-conversation-id="${conversationId}"]`).classList.add('active');
    
    currentConversationId = conversationId;
    document.getElementById('currentConversationId').value = conversationId;
    
    // Mostrar chat container
    document.getElementById('noChatSelected').classList.add('d-none');
    document.getElementById('chatContainer').classList.remove('d-none');
    
    // Cargar información de la conversación
    await loadConversationDetails(conversationId);
    await loadMessages(conversationId);
    
    // Marcar mensajes como leídos
    markAsRead(conversationId);
}

// Cargar detalles de la conversación
async function loadConversationDetails(conversationId) {
    try {
        const response = await fetch(`?route=admin/chat/conversation&id=${conversationId}`);
        const data = await response.json();
        
        if (data.success && data.conversation) {
            const conv = data.conversation;
            document.getElementById('chatClientName').textContent = conv.cliente_nombre || 'Cliente Anónimo';
            document.getElementById('chatStatus').textContent = conv.estado;
            document.getElementById('chatTime').textContent = formatTime(conv.creada_en);
        }
    } catch (error) {
        console.error('Error cargando detalles:', error);
    }
}

// Cargar mensajes de la conversación
async function loadMessages(conversationId) {
    try {
        const response = await fetch(`?route=admin/chat/messages&conversation_id=${conversationId}`);
        const data = await response.json();
        
        if (data.success && data.messages) {
            renderMessages(data.messages);
            if (data.messages.length > 0) {
                lastMessageId = Math.max(...data.messages.map(m => m.id));
            }
        }
    } catch (error) {
        console.error('Error cargando mensajes:', error);
    }
}

// Renderizar mensajes
function renderMessages(messages) {
    const container = document.getElementById('messagesContainer');
    
    container.innerHTML = messages.map(msg => {
        const messageClass = `message-${msg.tipo_emisor}`;
        return `
            <div class="message ${messageClass}">
                <div class="message-content">
                    ${escapeHtml(msg.mensaje)}
                    <div class="message-time text-end">
                        ${formatTime(msg.enviado_en)}
                        ${msg.emisor_nombre ? `• ${msg.emisor_nombre}` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    // Scroll al final
    container.scrollTop = container.scrollHeight;
}

// Enviar mensaje
async function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (!message || !currentConversationId) return;
    
    try {
        const formData = new FormData();
        formData.append('conversation_id', currentConversationId);
        formData.append('message', message);
        
        const response = await fetch('?route=admin/chat/send', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            input.value = '';
            await loadMessages(currentConversationId);
        } else {
            showError(data.message || 'Error al enviar mensaje');
        }
    } catch (error) {
        console.error('Error enviando mensaje:', error);
        showError('Error al enviar el mensaje');
    }
}

// Marcar mensajes como leídos
async function markAsRead(conversationId) {
    try {
        await fetch(`?route=admin/chat/mark_read&conversation_id=${conversationId}`, {
            method: 'POST'
        });
    } catch (error) {
        console.error('Error marcando como leído:', error);
    }
}

// Polling para nuevos mensajes
function startPolling() {
    pollingInterval = setInterval(async () => {
        if (currentConversationId) {
            try {
                const response = await fetch(`?route=admin/chat/new_messages&conversation_id=${currentConversationId}&last_message_id=${lastMessageId}`);
                const data = await response.json();
                
                if (data.success && data.messages && data.messages.length > 0) {
                    const container = document.getElementById('messagesContainer');
                    const wasAtBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 10;
                    
                    data.messages.forEach(msg => {
                        const messageClass = `message-${msg.tipo_emisor}`;
                        const messageHtml = `
                            <div class="message ${messageClass}">
                                <div class="message-content">
                                    ${escapeHtml(msg.mensaje)}
                                    <div class="message-time text-end">
                                        ${formatTime(msg.enviado_en)}
                                        ${msg.emisor_nombre ? `• ${msg.emisor_nombre}` : ''}
                                    </div>
                                </div>
                            </div>
                        `;
                        container.insertAdjacentHTML('beforeend', messageHtml);
                    });
                    
                    lastMessageId = Math.max(...data.messages.map(m => m.id));
                    
                    if (wasAtBottom) {
                        container.scrollTop = container.scrollHeight;
                    }
                }
            } catch (error) {
                console.error('Error en polling:', error);
            }
        }
        
        // Actualizar lista de conversaciones cada 30 segundos
        if (Date.now() % 30000 < 3000) {
            loadConversations();
        }
    }, 3000);
}

// Funciones auxiliares
function filterConversations(status) {
    loadConversations(status);
}

function updateStats(stats) {
    document.getElementById('activeCount').textContent = stats.activas || 0;
    document.getElementById('waitingCount').textContent = stats.en_espera || 0;
    document.getElementById('totalActiveConversations').textContent = stats.activas || 0;
    document.getElementById('totalWaitingConversations').textContent = stats.en_espera || 0;
}

function getStatusColor(status) {
    const colors = {
        'activa': 'success',
        'en_espera': 'warning',
        'cerrada': 'secondary'
    };
    return colors[status] || 'secondary';
}

function formatTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) return 'Hace un momento';
    if (diff < 3600000) return `Hace ${Math.floor(diff/60000)} min`;
    if (diff < 86400000) return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    return date.toLocaleDateString('es-ES');
}

function truncateText(text, length) {
    return text && text.length > length ? text.substring(0, length) + '...' : text;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showError(message) {
    // TODO: Implementar sistema de notificaciones toast
    console.error(message);
    alert(message);
}

// Funciones de modal
function transferConversation() {
    if (!currentConversationId) return;
    new bootstrap.Modal(document.getElementById('transferModal')).show();
}

function closeConversation() {
    if (!currentConversationId || !confirm('¿Estás seguro de cerrar esta conversación?')) return;
    
    // TODO: Implementar cierre de conversación
    fetch(`?route=admin/chat/close&conversation_id=${currentConversationId}`, {
        method: 'POST'
    }).then(() => {
        loadConversations();
        selectConversation(null);
    });
}

function viewCustomerInfo() {
    if (!currentConversationId) return;
    // TODO: Cargar información del cliente
    new bootstrap.Modal(document.getElementById('customerInfoModal')).show();
}

function executeTransfer() {
    const operatorId = document.getElementById('targetOperator').value;
    const reason = document.getElementById('transferReason').value;
    
    if (!operatorId) {
        alert('Selecciona un operador');
        return;
    }
    
    // TODO: Implementar transferencia
    console.log('Transferir conversación', currentConversationId, 'a operador', operatorId, 'por:', reason);
    bootstrap.Modal.getInstance(document.getElementById('transferModal')).hide();
}

function attachFile() {
    document.getElementById('fileInput').click();
}

// Limpiar polling al salir
window.addEventListener('beforeunload', function() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>

<!-- Incluir JavaScript específico para admin chat -->
<script src="<?= Config::getBaseUrl() ?>/assets/js/admin-chat.js"></script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
