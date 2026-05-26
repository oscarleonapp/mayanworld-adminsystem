<?php
/**
 * Panel de Chat para Administradores
 * Vista moderna tipo WhatsApp Web para gestionar conversaciones
 */
use App\Core\Config;
$pageTitle = 'Chat - Conversaciones';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<style>
/* Chat Admin Styles */
.chat-admin-container {
    height: calc(100vh - 120px);
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
}

/* Lista de conversaciones */
.conversations-sidebar {
    width: 350px;
    border-right: 1px solid #e5e7eb;
    display: flex;
    flex-direction: column;
    background: #f9fafb;
}

.conversations-header {
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.conversations-header h5 {
    margin: 0 0 8px 0;
    font-weight: 600;
}

.conversations-search {
    padding: 12px 16px;
    border-bottom: 1px solid #e5e7eb;
    background: white;
}

.conversations-search input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    font-size: 14px;
}

.conversations-list {
    flex: 1;
    overflow-y: auto;
}

.conversation-item {
    padding: 16px;
    border-bottom: 1px solid #e5e7eb;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
}

.conversation-item:hover {
    background: #f3f4f6;
}

.conversation-item.active {
    background: #eef2ff;
    border-left: 3px solid #667eea;
}

.conversation-item.unread {
    background: #fef3c7;
}

.conversation-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.conversation-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 18px;
    flex-shrink: 0;
}

.conversation-info {
    flex: 1;
    min-width: 0;
}

.conversation-name {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 2px;
}

.conversation-preview {
    color: #6b7280;
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-meta {
    text-align: right;
}

.conversation-time {
    font-size: 12px;
    color: #9ca3af;
}

.unread-badge {
    background: #ef4444;
    color: white;
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: 600;
    margin-top: 4px;
    display: inline-block;
}

/* Área de chat */
.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #f8f9fa;
}

.chat-area-header {
    padding: 20px;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.chat-client-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.chat-client-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 18px;
}

.chat-client-details h5 {
    margin: 0;
    font-weight: 600;
    color: #1f2937;
}

.chat-client-details small {
    color: #6b7280;
}

.chat-actions {
    display: flex;
    gap: 8px;
}

.chat-action-btn {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    background: white;
    cursor: pointer;
    transition: all 0.2s;
}

.chat-action-btn:hover {
    background: #f3f4f6;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.chat-message {
    display: flex;
    gap: 12px;
    max-width: 70%;
}

.chat-message.operator {
    margin-left: auto;
    flex-direction: row-reverse;
}

.chat-message-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 16px;
    color: #6b7280;
}

.chat-message.operator .chat-message-avatar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.chat-message-content {
    flex: 1;
}

.chat-message-bubble {
    padding: 12px 16px;
    border-radius: 16px;
    background: white;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    word-wrap: break-word;
}

.chat-message.operator .chat-message-bubble {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.chat-message-time {
    font-size: 11px;
    color: #9ca3af;
    margin-top: 4px;
}

.chat-input-area {
    padding: 20px;
    background: white;
    border-top: 1px solid #e5e7eb;
}

.chat-input-form {
    display: flex;
    gap: 12px;
    align-items: flex-end;
}

.chat-input-wrapper {
    flex: 1;
    position: relative;
}

.chat-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    resize: none;
    font-size: 14px;
    font-family: inherit;
    min-height: 48px;
    max-height: 120px;
}

.chat-input:focus {
    outline: none;
    border-color: #667eea;
}

.chat-send-btn {
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s;
}

.chat-send-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.chat-send-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #9ca3af;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 16px;
}

/* Scrollbar */
.conversations-list::-webkit-scrollbar,
.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.conversations-list::-webkit-scrollbar-track,
.chat-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.conversations-list::-webkit-scrollbar-thumb,
.chat-messages::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 3px;
}

.conversations-list::-webkit-scrollbar-thumb:hover,
.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

/* Responsive */
@media (max-width: 768px) {
    .conversations-sidebar {
        width: 100%;
        display: none;
    }

    .conversations-sidebar.mobile-show {
        display: flex;
    }

    .chat-area {
        display: none;
    }

    .chat-area.mobile-show {
        display: flex;
    }
}
</style>

<div class="container-fluid py-4">
    <?php
        $actionTitle = 'Conversaciones';
        $actionSubtitle = 'Gestiona los chats de clientes desde el panel';
        $actionButtons = [];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <div class="chat-admin-container">
    <!-- Lista de Conversaciones -->
    <div class="conversations-sidebar" id="conversationsSidebar">
        <div class="conversations-header">
            <h5><i class="fas fa-comments me-2"></i>Conversaciones</h5>
            <small>Gestiona los chats de clientes</small>
        </div>

        <div class="conversations-search">
            <input type="text" id="searchConversations" placeholder="Buscar conversaciones...">
        </div>

        <div class="conversations-list" id="conversationsList">
            <!-- Las conversaciones se cargarán aquí dinámicamente -->
            <div class="text-center p-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Área de Chat -->
    <div class="chat-area" id="chatArea">
        <div class="empty-state">
            <i class="fas fa-comments"></i>
            <h5>Selecciona una conversación</h5>
            <p>Elige una conversación de la lista para comenzar a responder</p>
        </div>
    </div>
    </div>
</div>

<script>
let currentConversationId = null;
let conversations = [];
let pollingInterval = null;
let lastMessageCount = 0;

document.addEventListener('DOMContentLoaded', function() {
    loadConversations();
    startPolling();

    // Buscar conversaciones
    document.getElementById('searchConversations').addEventListener('input', function(e) {
        filterConversations(e.target.value);
    });
});

// Cargar conversaciones
function loadConversations() {
    console.log('[Chat] Cargando conversaciones...');
    fetch('<?= Config::getBaseUrl() ?>?route=admin/chat/api-conversations')
        .then(response => {
            console.log('[Chat] Respuesta recibida:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('[Chat] Datos recibidos:', data);
            if (data.success) {
                conversations = data.conversations || [];
                console.log('[Chat] Conversaciones cargadas:', conversations.length);
                renderConversations();
            } else {
                console.error('[Chat] Error en respuesta:', data.message);
                showError('Error al cargar conversaciones: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(error => {
            console.error('[Chat] Error al cargar conversaciones:', error);
            showError('Error de conexión. Verifica que Apache esté corriendo.');
        });
}

// Renderizar conversaciones
function renderConversations() {
    const list = document.getElementById('conversationsList');

    if (conversations.length === 0) {
        list.innerHTML = `
            <div class="text-center p-4 text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>No hay conversaciones activas</p>
            </div>
        `;
        return;
    }

    // Actualizar solo si es necesario (evitar parpadeo)
    const existingConvIds = Array.from(list.querySelectorAll('.conversation-item')).map(el => el.dataset.convId);
    const newConvIds = conversations.map(c => String(c.id));

    // Si hay cambios en las conversaciones, actualizar
    const hasChanges = existingConvIds.length !== newConvIds.length ||
                       existingConvIds.some((id, idx) => id !== newConvIds[idx]);

    if (hasChanges || existingConvIds.length === 0) {
        list.innerHTML = conversations.map(conv => {
            const initials = conv.client_name ? conv.client_name.substring(0, 2).toUpperCase() : 'AN';
            const isActive = conv.id == currentConversationId;
            const hasUnread = conv.unread_count > 0;

            return `
                <div class="conversation-item ${isActive ? 'active' : ''} ${hasUnread ? 'unread' : ''}"
                     data-conv-id="${conv.id}"
                     onclick="selectConversation(${conv.id})">
                    <div class="conversation-header">
                        <div class="conversation-avatar">${initials}</div>
                        <div class="conversation-info">
                            <div class="conversation-name">${conv.client_name || 'Invitado'}</div>
                            <div class="conversation-preview">
                                ${conv.last_message || 'Nueva conversación'}
                            </div>
                        </div>
                        <div class="conversation-meta">
                            <div class="conversation-time">${formatTime(conv.updated_at)}</div>
                            ${hasUnread ? `<span class="unread-badge">${conv.unread_count}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    } else {
        // Solo actualizar badges y estados
        conversations.forEach(conv => {
            const item = list.querySelector(`[data-conv-id="${conv.id}"]`);
            if (item) {
                const isActive = conv.id == currentConversationId;
                const hasUnread = conv.unread_count > 0;

                item.className = `conversation-item ${isActive ? 'active' : ''} ${hasUnread ? 'unread' : ''}`;

                // Actualizar badge
                const metaDiv = item.querySelector('.conversation-meta');
                const existingBadge = metaDiv.querySelector('.unread-badge');
                if (hasUnread && !existingBadge) {
                    metaDiv.insertAdjacentHTML('beforeend', `<span class="unread-badge">${conv.unread_count}</span>`);
                } else if (!hasUnread && existingBadge) {
                    existingBadge.remove();
                } else if (hasUnread && existingBadge) {
                    existingBadge.textContent = conv.unread_count;
                }
            }
        });
    }
}

// Seleccionar conversación
function selectConversation(conversationId) {
    currentConversationId = conversationId;
    const conversation = conversations.find(c => c.id == conversationId);

    if (!conversation) return;

    // Actualizar UI
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.classList.remove('active');
    });
    event.currentTarget.classList.add('active');

    // Cargar mensajes
    loadMessages(conversationId, conversation);
}

// Cargar mensajes
function loadMessages(conversationId, conversation) {
    console.log('[Chat] Cargando mensajes para conversación:', conversationId);
    const chatArea = document.getElementById('chatArea');

    return fetch(`<?= Config::getBaseUrl() ?>?route=chat/messages&conversation_id=${conversationId}`)
        .then(response => {
            console.log('[Chat] Respuesta mensajes:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('[Chat] Mensajes recibidos:', data);
            if (data.success) {
                renderChatArea(conversation, data.messages || []);
                return data.messages;
            }
            console.error('[Chat] Error en mensajes:', data.message);
            return [];
        })
        .catch(error => {
            console.error('[Chat] Error cargando mensajes:', error);
            showError('Error al cargar mensajes');
            return [];
        });
}

// Renderizar área de chat
function renderChatArea(conversation, messages) {
    const initials = conversation.client_name ? conversation.client_name.substring(0, 2).toUpperCase() : 'AN';
    const chatArea = document.getElementById('chatArea');

    // Si ya existe el área, solo actualizar mensajes (no recrear todo el HTML)
    const existingMessagesContainer = document.getElementById('chatMessages');
    if (existingMessagesContainer) {
        updateMessagesOnly(messages);
        return;
    }

    // Primera vez: crear toda la estructura
    chatArea.innerHTML = `
        <!-- Header -->
        <div class="chat-area-header">
            <div class="chat-client-info">
                <div class="chat-client-avatar">${initials}</div>
                <div class="chat-client-details">
                    <h5>${conversation.client_name || 'Invitado'}</h5>
                    <small>${conversation.client_email || 'Sin email'}</small>
                </div>
            </div>
            <div class="chat-actions">
                <button class="chat-action-btn" onclick="closeConversation(${conversation.id})" title="Cerrar conversación">
                    <i class="fas fa-check"></i> Resolver
                </button>
            </div>
        </div>

        <!-- Messages -->
        <div class="chat-messages" id="chatMessages">
            ${messages.map(msg => renderMessage(msg)).join('')}
        </div>

        <!-- Input -->
        <div class="chat-input-area">
            <form class="chat-input-form" onsubmit="sendMessage(event)">
                <div class="chat-input-wrapper">
                    <textarea id="chatInput" class="chat-input" placeholder="Escribe tu respuesta... (Enter para enviar, Shift+Enter para nueva línea)" rows="1"></textarea>
                </div>
                <button type="submit" class="chat-send-btn">
                    <i class="fas fa-paper-plane me-2"></i>Enviar
                </button>
            </form>
        </div>
    `;

    // Auto-resize textarea
    const textarea = document.getElementById('chatInput');
    textarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Enter para enviar, Shift+Enter para nueva línea
    textarea.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            const form = this.closest('form');
            if (form) {
                form.dispatchEvent(new Event('submit'));
            }
        }
    });

    // Focus en el textarea
    textarea.focus();

    // Scroll al final
    scrollToBottom();
}

// Actualizar solo los mensajes (sin recrear el HTML completo)
function updateMessagesOnly(messages) {
    const messagesContainer = document.getElementById('chatMessages');
    if (!messagesContainer) return;

    // Detectar nuevos mensajes
    const currentMessageIds = Array.from(messagesContainer.querySelectorAll('.chat-message')).map(el => el.dataset.messageId);
    const newMessages = messages.filter(msg => !currentMessageIds.includes(String(msg.id)));

    if (newMessages.length > 0) {
        // Hay mensajes nuevos
        const wasAtBottom = messagesContainer.scrollTop + messagesContainer.clientHeight >= messagesContainer.scrollHeight - 50;

        // Agregar solo los nuevos mensajes
        newMessages.forEach(msg => {
            const messageHTML = renderMessage(msg);
            messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
        });

        // Notificar si hay mensajes de cliente
        const hasClientMessage = newMessages.some(m => m.sender_type === 'cliente');
        if (hasClientMessage) {
            playNotificationSound();
            showDesktopNotification('Nuevo mensaje', newMessages[newMessages.length - 1].message);
        }

        // Scroll solo si estaba al final
        if (wasAtBottom) {
            scrollToBottom();
        } else {
            showNewMessagesIndicator();
        }
    }
}

// Renderizar mensaje
function renderMessage(msg) {
    const isOperator = msg.sender_type === 'operator' || msg.sender_type === 'admin';
    const time = new Date(msg.sent_at).toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });

    // Escapar HTML y convertir saltos de línea
    const safeMessage = escapeHtml(msg.message).replace(/\n/g, '<br>');

    return `
        <div class="chat-message ${isOperator ? 'operator' : 'client'}" data-message-id="${msg.id}">
            <div class="chat-message-avatar">
                <i class="fas fa-${isOperator ? 'headset' : 'user'}"></i>
            </div>
            <div class="chat-message-content">
                <div class="chat-message-bubble">
                    ${safeMessage}
                </div>
                <div class="chat-message-time">${time}</div>
            </div>
        </div>
    `;
}

// Escapar HTML para prevenir XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

// Enviar mensaje
function sendMessage(event) {
    event.preventDefault();

    const input = document.getElementById('chatInput');
    const message = input.value.trim();

    if (!message || !currentConversationId) return;

    // Deshabilitar input mientras se envía
    const sendBtn = event.target.querySelector('button[type="submit"]');
    input.disabled = true;
    sendBtn.disabled = true;
    const originalBtnText = sendBtn.innerHTML;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';

    fetch('<?= Config::getBaseUrl() ?>?route=admin/chat/send-message', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            conversation_id: currentConversationId,
            message: message
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            input.value = '';
            input.style.height = 'auto';

            // Agregar mensaje inmediatamente a la UI (optimistic update)
            const messagesContainer = document.getElementById('chatMessages');
            if (messagesContainer) {
                const newMessage = {
                    message: message,
                    sender_type: 'operator',
                    sent_at: new Date().toISOString()
                };
                messagesContainer.insertAdjacentHTML('beforeend', renderMessage(newMessage));
                scrollToBottom();
            }

            // Recargar mensajes después para sincronizar
            setTimeout(() => {
                const conversation = conversations.find(c => c.id == currentConversationId);
                if (conversation) {
                    loadMessages(currentConversationId, conversation);
                }
            }, 500);
        } else {
            alert('Error al enviar mensaje: ' + (data.message || 'Error desconocido'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al enviar mensaje');
    })
    .finally(() => {
        // Rehabilitar input
        input.disabled = false;
        sendBtn.disabled = false;
        sendBtn.innerHTML = originalBtnText;
        input.focus();
    });
}

// Cerrar conversación
function closeConversation(conversationId) {
    if (!confirm('¿Deseas marcar esta conversación como resuelta?')) return;

    fetch('<?= Config::getBaseUrl() ?>?route=admin/chat/close-conversation', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            conversation_id: conversationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadConversations();
            document.getElementById('chatArea').innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-check-circle text-success"></i>
                    <h5>Conversación resuelta</h5>
                    <p>La conversación ha sido marcada como resuelta</p>
                </div>
            `;
            currentConversationId = null;
        }
    })
    .catch(error => console.error('Error:', error));
}

// Filtrar conversaciones
function filterConversations(query) {
    const items = document.querySelectorAll('.conversation-item');
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(query.toLowerCase()) ? 'block' : 'none';
    });
}

// Formatear tiempo
function formatTime(datetime) {
    const date = new Date(datetime);
    const now = new Date();
    const diff = now - date;

    if (diff < 60000) return 'Ahora';
    if (diff < 3600000) return Math.floor(diff / 60000) + 'min';
    if (diff < 86400000) return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
    return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
}

// Scroll al final
function scrollToBottom() {
    const messages = document.getElementById('chatMessages');
    if (messages) {
        setTimeout(() => {
            messages.scrollTop = messages.scrollHeight;
        }, 100);
    }
}

// Polling para actualizaciones
function startPolling() {
    console.log('[Chat] Iniciando polling cada 3 segundos...');
    let messagesPollCount = 0;

    pollingInterval = setInterval(() => {
        console.log('[Chat] Polling tick #' + messagesPollCount);
        showPollingActivity();

        // Actualizar mensajes cada 3 segundos si hay conversación activa
        if (currentConversationId) {
            console.log('[Chat] Actualizando mensajes de conversación:', currentConversationId);
            const conversation = conversations.find(c => c.id == currentConversationId);
            if (conversation) {
                const messagesContainer = document.getElementById('chatMessages');
                const wasAtBottom = messagesContainer ?
                    (messagesContainer.scrollTop + messagesContainer.clientHeight >= messagesContainer.scrollHeight - 50) :
                    true;

                loadMessages(currentConversationId, conversation).then(() => {
                    // Solo hacer scroll automático si el usuario estaba al final
                    if (wasAtBottom) {
                        scrollToBottom();
                    } else {
                        // Mostrar indicador de nuevos mensajes
                        showNewMessagesIndicator();
                    }
                });
            }
        }

        // Actualizar lista de conversaciones cada 10 segundos (cada 3 ticks)
        messagesPollCount++;
        if (messagesPollCount % 3 === 0) {
            console.log('[Chat] Actualizando lista de conversaciones...');
            loadConversations();
        }
    }, 3000); // Cada 3 segundos

    console.log('[Chat] Polling iniciado con ID:', pollingInterval);
}

// Mostrar indicador de nuevos mensajes
function showNewMessagesIndicator() {
    const messagesContainer = document.getElementById('chatMessages');
    if (!messagesContainer) return;

    // Verificar si ya existe el indicador
    let indicator = document.getElementById('newMessagesIndicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'newMessagesIndicator';
        indicator.className = 'new-messages-indicator';
        indicator.innerHTML = '<i class="fas fa-arrow-down me-2"></i>Nuevos mensajes';
        indicator.style.cssText = 'position: absolute; bottom: 80px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 16px; border-radius: 20px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.2); z-index: 10;';
        indicator.onclick = () => {
            scrollToBottom();
            indicator.remove();
        };
        messagesContainer.parentElement.style.position = 'relative';
        messagesContainer.parentElement.appendChild(indicator);
    }
}

// Limpiar polling al salir
window.addEventListener('beforeunload', function() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});

// Reproducir sonido de notificación
function playNotificationSound() {
    try {
        // Crear un tono simple usando Web Audio API
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.value = 800;
        oscillator.type = 'sine';
        gainNode.gain.value = 0.1;

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.1);
    } catch (e) {
        console.log('No se pudo reproducir sonido:', e);
    }
}

// Mostrar notificación de escritorio
function showDesktopNotification(title, message) {
    if (!('Notification' in window)) {
        return;
    }

    if (Notification.permission === 'granted') {
        new Notification(title, {
            body: message.substring(0, 100),
            icon: '<?= Config::getBaseUrl() ?>assets/images/logo.png',
            badge: '<?= Config::getBaseUrl() ?>assets/images/logo.png',
            tag: 'chat-notification',
            renotify: false
        });
    } else if (Notification.permission !== 'denied') {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                showDesktopNotification(title, message);
            }
        });
    }
}

// Solicitar permiso de notificaciones al cargar
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

// Mostrar error en pantalla
function showError(message) {
    const list = document.getElementById('conversationsList');
    if (list && conversations.length === 0) {
        list.innerHTML = `
            <div class="text-center p-4">
                <i class="fas fa-exclamation-triangle text-warning fa-2x mb-3"></i>
                <p class="text-danger">${message}</p>
                <button class="btn btn-sm btn-primary" onclick="loadConversations()">
                    <i class="fas fa-sync-alt me-1"></i>Reintentar
                </button>
            </div>
        `;
    }
}

// Indicador de actividad de polling (debug)
let pollingIndicator = null;
function showPollingActivity() {
    if (!pollingIndicator) {
        pollingIndicator = document.createElement('div');
        pollingIndicator.style.cssText = 'position: fixed; top: 10px; right: 10px; background: #4CAF50; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px; z-index: 9999; opacity: 0.7;';
        pollingIndicator.innerHTML = '<i class="fas fa-sync-alt fa-spin me-1"></i>Actualizando...';
        document.body.appendChild(pollingIndicator);
    }
    pollingIndicator.style.display = 'block';
    setTimeout(() => {
        if (pollingIndicator) pollingIndicator.style.display = 'none';
    }, 1000);
}

console.log('[Chat] Sistema de chat inicializado');
console.log('[Chat] URL base:', '<?= Config::getBaseUrl() ?>');
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
