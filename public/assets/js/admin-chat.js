// Admin Chat JavaScript - Funcionalidad avanzada de chat para administradores
class AdminChat {
    constructor() {
        this.currentConversationId = null;
        this.lastMessageId = 0;
        this.conversationsData = [];
        this.operatorsData = [];
        this.pollingInterval = null;
        this.isPolling = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadConversations();
        this.loadOperators();
        this.startPolling();
        this.setupKeyboardShortcuts();
    }
    
    bindEvents() {
        // Event delegation para elementos dinámicos
        document.addEventListener('click', this.handleClick.bind(this));
        document.addEventListener('submit', this.handleSubmit.bind(this));
        
        // Eventos específicos
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('keydown', this.handleKeydown.bind(this));
            messageInput.addEventListener('input', this.handleTyping.bind(this));
        }
        
        // Manejar visibilidad de la página
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
        
        // Manejar resize de ventana
        window.addEventListener('resize', this.handleResize.bind(this));
        
        // Prevenir salir sin guardar
        window.addEventListener('beforeunload', this.handleBeforeUnload.bind(this));
    }
    
    handleClick(event) {
        const target = event.target.closest('[data-action]');
        if (!target) return;
        
        const action = target.dataset.action;
        const conversationId = target.dataset.conversationId;
        
        switch (action) {
            case 'select-conversation':
                this.selectConversation(conversationId);
                break;
            case 'filter-conversations':
                this.filterConversations(target.dataset.filter);
                break;
            case 'transfer-conversation':
                this.showTransferModal();
                break;
            case 'close-conversation':
                this.closeConversation();
                break;
            case 'assign-operator':
                this.assignOperator(conversationId);
                break;
            case 'refresh-conversations':
                this.loadConversations();
                break;
        }
    }
    
    handleSubmit(event) {
        if (event.target.id === 'messageForm') {
            event.preventDefault();
            this.sendMessage();
        } else if (event.target.id === 'transferForm') {
            event.preventDefault();
            this.executeTransfer();
        }
    }
    
    handleKeydown(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            this.sendMessage();
        } else if (event.key === 'Escape') {
            this.clearCurrentConversation();
        }
    }
    
    handleTyping(event) {
        // TODO: Implementar indicador de escritura en tiempo real
        const isTyping = event.target.value.length > 0;
        this.broadcastTypingStatus(isTyping);
    }
    
    handleVisibilityChange() {
        if (document.hidden) {
            this.pausePolling();
        } else {
            this.resumePolling();
        }
    }
    
    handleResize() {
        this.adjustChatHeight();
    }
    
    handleBeforeUnload(event) {
        if (this.hasUnsavedChanges()) {
            event.preventDefault();
            event.returnValue = '';
        }
        this.cleanup();
    }
    
    async loadConversations(status = 'activa', operatorId = null) {
        try {
            const params = new URLSearchParams({ status });
            if (operatorId) params.append('operator_id', operatorId);
            
            const response = await this.fetchWithRetry(`?route=admin/chat/conversations&${params}`, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.conversationsData = data.conversations || [];
                this.renderConversations(this.conversationsData);
                this.updateStats(data.stats || {});
                this.resetReconnectAttempts();
            } else {
                throw new Error(data.message || 'Error al cargar conversaciones');
            }
            
        } catch (error) {
            console.error('Error cargando conversaciones:', error);
            this.handleError('Error al cargar las conversaciones', error);
            this.incrementReconnectAttempts();
        }
    }
    
    renderConversations(conversations) {
        const container = document.getElementById('conversationsList');
        if (!container) return;
        
        if (!conversations || conversations.length === 0) {
            container.innerHTML = this.getEmptyStateHTML();
            return;
        }
        
        const html = conversations.map(conv => this.getConversationHTML(conv)).join('');
        container.innerHTML = html;
        
        this.highlightCurrentConversation();
    }
    
    getConversationHTML(conv) {
        const unreadBadge = conv.mensajes_no_leidos > 0 
            ? `<span class="unread-badge">${conv.mensajes_no_leidos}</span>` 
            : '';
        
        const lastMessage = this.truncateText(conv.ultimo_mensaje || 'Sin mensajes', 50);
        const formattedTime = this.formatTime(conv.ultimo_mensaje_tiempo);
        const statusColor = this.getStatusColor(conv.estado);
        const clientName = this.escapeHtml(conv.cliente_nombre || 'Cliente Anónimo');
        
        return `
            <div class="list-group-item list-group-item-action conversation-item" 
                 data-conversation-id="${conv.id}"
                 data-action="select-conversation"
                 data-conversation-id="${conv.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="mb-0 conversation-name">${clientName}</h6>
                            ${unreadBadge}
                        </div>
                        <p class="mb-1 text-muted small last-message">${lastMessage}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted time">${formattedTime}</small>
                            <span class="badge bg-${statusColor} rounded-pill status">${conv.estado}</span>
                        </div>
                    </div>
                </div>
                <div class="conversation-preview d-none">
                    <small class="text-muted">
                        ${conv.operador_nombre ? `Operador: ${conv.operador_nombre}` : 'Sin asignar'}
                    </small>
                </div>
            </div>
        `;
    }
    
    getEmptyStateHTML() {
        return `
            <div class="text-center p-4">
                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                <p class="text-muted">No hay conversaciones</p>
                <button class="btn btn-sm btn-outline-primary" data-action="refresh-conversations">
                    <i class="fas fa-refresh"></i> Actualizar
                </button>
            </div>
        `;
    }
    
    async loadOperators() {
        try {
            const response = await this.fetchWithRetry('?route=admin/chat/operators', {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.operatorsData = data.operators || [];
                this.renderOperators(this.operatorsData);
            } else {
                throw new Error(data.message || 'Error al cargar operadores');
            }
            
        } catch (error) {
            console.error('Error cargando operadores:', error);
            this.handleError('Error al cargar operadores', error);
        }
    }
    
    renderOperators(operators) {
        const container = document.getElementById('operatorsList');
        const transferSelect = document.getElementById('targetOperator');
        
        if (!container) return;
        
        if (!operators || operators.length === 0) {
            container.innerHTML = '<p class="text-muted small">No hay operadores disponibles</p>';
            return;
        }
        
        const operatorsHTML = operators.map(op => `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center">
                    <span class="operator-status ${this.getOperatorStatus(op)} me-2"></span>
                    <span class="small operator-name">${this.escapeHtml(op.nombre)}</span>
                </div>
                <span class="badge bg-primary rounded-pill small">${op.conversaciones_activas || 0}</span>
            </div>
        `).join('');
        
        container.innerHTML = operatorsHTML;
        
        // Actualizar select de transferencia
        if (transferSelect) {
            const optionsHTML = '<option value="">Seleccionar operador...</option>' +
                operators.map(op => 
                    `<option value="${op.id}">${op.nombre} (${op.conversaciones_activas || 0} activas)</option>`
                ).join('');
            transferSelect.innerHTML = optionsHTML;
        }
    }
    
    async selectConversation(conversationId) {
        if (this.currentConversationId === conversationId) return;
        
        this.setLoadingState(true);
        
        try {
            // Actualizar UI
            this.highlightConversation(conversationId);
            this.currentConversationId = conversationId;
            
            // Mostrar contenedor de chat
            this.showChatContainer();
            
            // Cargar datos de la conversación
            await Promise.all([
                this.loadConversationDetails(conversationId),
                this.loadMessages(conversationId)
            ]);
            
            // Marcar mensajes como leídos
            this.markAsRead(conversationId);
            
            // Enfocar input de mensaje
            this.focusMessageInput();
            
        } catch (error) {
            console.error('Error seleccionando conversación:', error);
            this.handleError('Error al cargar la conversación', error);
        } finally {
            this.setLoadingState(false);
        }
    }
    
    highlightConversation(conversationId) {
        // Remover clase active de todas las conversaciones
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
        });
        
        // Agregar clase active a la conversación seleccionada
        const selectedItem = document.querySelector(`[data-conversation-id="${conversationId}"]`);
        if (selectedItem) {
            selectedItem.classList.add('active');
        }
    }
    
    showChatContainer() {
        const noChat = document.getElementById('noChatSelected');
        const chatContainer = document.getElementById('chatContainer');
        
        if (noChat) noChat.classList.add('d-none');
        if (chatContainer) chatContainer.classList.remove('d-none');
    }
    
    async loadConversationDetails(conversationId) {
        try {
            const response = await this.fetchWithRetry(`?route=admin/chat/conversation&id=${conversationId}`);
            const data = await response.json();
            
            if (data.success && data.conversation) {
                this.updateConversationHeader(data.conversation);
            }
        } catch (error) {
            console.error('Error cargando detalles:', error);
        }
    }
    
    updateConversationHeader(conversation) {
        const elements = {
            chatClientName: conversation.cliente_nombre || 'Cliente Anónimo',
            chatStatus: conversation.estado,
            chatTime: this.formatTime(conversation.creada_en)
        };
        
        Object.entries(elements).forEach(([id, value]) => {
            const element = document.getElementById(id);
            if (element) element.textContent = value;
        });
    }
    
    async loadMessages(conversationId) {
        try {
            const response = await this.fetchWithRetry(`?route=admin/chat/messages&conversation_id=${conversationId}`);
            const data = await response.json();
            
            if (data.success && data.messages) {
                this.renderMessages(data.messages);
                if (data.messages.length > 0) {
                    this.lastMessageId = Math.max(...data.messages.map(m => parseInt(m.id)));
                }
            }
        } catch (error) {
            console.error('Error cargando mensajes:', error);
            this.handleError('Error al cargar mensajes', error);
        }
    }
    
    renderMessages(messages) {
        const container = document.getElementById('messagesContainer');
        if (!container) return;
        
        const messagesHTML = messages.map(msg => this.getMessageHTML(msg)).join('');
        container.innerHTML = messagesHTML;
        
        this.scrollToBottom();
        this.processMessageMedia();
    }
    
    getMessageHTML(msg) {
        const messageClass = `message-${msg.tipo_emisor}`;
        const senderName = msg.emisor_nombre ? `• ${this.escapeHtml(msg.emisor_nombre)}` : '';
        const time = this.formatTime(msg.enviado_en);
        const message = this.processMessageContent(msg.mensaje);
        
        return `
            <div class="message ${messageClass}" data-message-id="${msg.id}">
                <div class="message-content">
                    ${message}
                    <div class="message-time text-end">
                        ${time} ${senderName}
                    </div>
                </div>
            </div>
        `;
    }
    
    processMessageContent(content) {
        // Procesar URLs
        content = this.linkify(content);
        
        // Procesar menciones
        content = this.processMentions(content);
        
        // Escapar HTML
        return this.escapeHtml(content);
    }
    
    async sendMessage() {
        const input = document.getElementById('messageInput');
        if (!input) return;
        
        const message = input.value.trim();
        if (!message || !this.currentConversationId) return;
        
        const sendButton = document.querySelector('#messageForm button[type="submit"]');
        const originalButtonContent = sendButton?.innerHTML;
        
        try {
            // UI feedback
            input.disabled = true;
            if (sendButton) {
                sendButton.disabled = true;
                sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }
            
            const formData = new FormData();
            formData.append('conversation_id', this.currentConversationId);
            formData.append('message', message);
            
            const response = await this.fetchWithRetry('?route=admin/chat/send', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                input.value = '';
                this.addMessageToUI({
                    id: data.message_id,
                    mensaje: message,
                    tipo_emisor: 'operador',
                    enviado_en: data.timestamp,
                    emisor_nombre: 'Tú'
                });
                this.updateLastMessageId(data.message_id);
            } else {
                throw new Error(data.message || 'Error al enviar mensaje');
            }
            
        } catch (error) {
            console.error('Error enviando mensaje:', error);
            this.handleError('Error al enviar el mensaje', error);
        } finally {
            // Restaurar UI
            input.disabled = false;
            if (sendButton) {
                sendButton.disabled = false;
                sendButton.innerHTML = originalButtonContent;
            }
            input.focus();
        }
    }
    
    addMessageToUI(messageData) {
        const container = document.getElementById('messagesContainer');
        if (!container) return;
        
        const messageHTML = this.getMessageHTML(messageData);
        container.insertAdjacentHTML('beforeend', messageHTML);
        this.scrollToBottom();
    }
    
    startPolling() {
        if (this.isPolling) return;
        
        this.isPolling = true;
        this.pollingInterval = setInterval(() => {
            if (this.currentConversationId && document.visibilityState === 'visible') {
                this.pollForNewMessages();
            }
            
            // Actualizar lista de conversaciones cada 30 segundos
            if (Date.now() % 30000 < 3000) {
                this.loadConversations();
            }
        }, 3000);
    }
    
    async pollForNewMessages() {
        if (!this.currentConversationId) return;
        
        try {
            const url = `?route=admin/chat/new_messages&conversation_id=${this.currentConversationId}&last_message_id=${this.lastMessageId}`;
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success && data.messages && data.messages.length > 0) {
                this.handleNewMessages(data.messages);
            }
            
        } catch (error) {
            console.error('Error en polling:', error);
        }
    }
    
    handleNewMessages(messages) {
        const container = document.getElementById('messagesContainer');
        if (!container) return;
        
        const wasAtBottom = this.isScrolledToBottom();
        
        messages.forEach(msg => {
            this.addMessageToUI(msg);
            this.lastMessageId = Math.max(this.lastMessageId, parseInt(msg.id));
        });
        
        if (wasAtBottom) {
            this.scrollToBottom();
        } else {
            this.showNewMessagesIndicator(messages.length);
        }
        
        // Notificación de sonido para mensajes de cliente
        if (messages.some(m => m.tipo_emisor === 'cliente')) {
            this.playNotificationSound();
        }
    }
    
    // Utility methods
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (event) => {
            if (event.ctrlKey || event.metaKey) {
                switch (event.key) {
                    case 'Enter':
                        event.preventDefault();
                        this.sendMessage();
                        break;
                    case 'k':
                        event.preventDefault();
                        this.focusSearch();
                        break;
                    case 'Escape':
                        this.clearCurrentConversation();
                        break;
                }
            }
        });
    }
    
    async fetchWithRetry(url, options, retries = 3) {
        for (let i = 0; i < retries; i++) {
            try {
                const response = await fetch(url, options);
                if (!response.ok && i === retries - 1) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response;
            } catch (error) {
                if (i === retries - 1) throw error;
                await this.sleep(1000 * Math.pow(2, i)); // Exponential backoff
            }
        }
    }
    
    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    formatTime(timestamp) {
        if (!timestamp) return '';
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) return 'Hace un momento';
        if (diff < 3600000) return `Hace ${Math.floor(diff/60000)} min`;
        if (diff < 86400000) return date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
        return date.toLocaleDateString('es-ES');
    }
    
    truncateText(text, length) {
        return text && text.length > length ? text.substring(0, length) + '...' : text;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
    
    linkify(text) {
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        return text.replace(urlRegex, '<a href="$1" target="_blank" rel="noopener">$1</a>');
    }
    
    processMentions(text) {
        const mentionRegex = /@(\w+)/g;
        return text.replace(mentionRegex, '<span class="mention">@$1</span>');
    }
    
    getStatusColor(status) {
        const colors = {
            'activa': 'success',
            'en_espera': 'warning',
            'cerrada': 'secondary'
        };
        return colors[status] || 'secondary';
    }
    
    getOperatorStatus(operator) {
        // TODO: Implementar lógica de estado real del operador
        return 'status-online';
    }
    
    scrollToBottom() {
        const container = document.getElementById('messagesContainer');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }
    
    isScrolledToBottom() {
        const container = document.getElementById('messagesContainer');
        if (!container) return false;
        return container.scrollTop + container.clientHeight >= container.scrollHeight - 10;
    }
    
    focusMessageInput() {
        const input = document.getElementById('messageInput');
        if (input) {
            setTimeout(() => input.focus(), 100);
        }
    }
    
    setLoadingState(loading) {
        const container = document.getElementById('messagesContainer');
        if (!container) return;
        
        if (loading) {
            container.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            `;
        }
    }
    
    handleError(message, error) {
        console.error(message, error);
        this.showToast(message, 'error');
    }
    
    showToast(message, type = 'info') {
        // TODO: Implementar sistema de toast notifications
        console.log(`[${type.toUpperCase()}] ${message}`);
    }
    
    playNotificationSound() {
        // TODO: Implementar sonido de notificación
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('Nuevo mensaje', {
                body: 'Has recibido un nuevo mensaje de chat',
                icon: '/assets/images/logo.png'
            });
        }
    }
    
    pausePolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.isPolling = false;
        }
    }
    
    resumePolling() {
        if (!this.isPolling) {
            this.startPolling();
        }
    }
    
    resetReconnectAttempts() {
        this.reconnectAttempts = 0;
    }
    
    incrementReconnectAttempts() {
        this.reconnectAttempts++;
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            this.handleMaxReconnectAttemptsReached();
        }
    }
    
    handleMaxReconnectAttemptsReached() {
        this.showToast('Se perdió la conexión. Por favor, recarga la página.', 'error');
        this.pausePolling();
    }
    
    hasUnsavedChanges() {
        const input = document.getElementById('messageInput');
        return input && input.value.trim().length > 0;
    }
    
    cleanup() {
        this.pausePolling();
    }
    
    // Métodos públicos para integración con HTML
    filterConversations(status) {
        this.loadConversations(status);
    }
    
    showTransferModal() {
        const modal = new bootstrap.Modal(document.getElementById('transferModal'));
        modal.show();
    }
    
    async closeConversation() {
        if (!this.currentConversationId || !confirm('¿Estás seguro de cerrar esta conversación?')) {
            return;
        }
        
        try {
            const response = await this.fetchWithRetry(`?route=admin/chat/close&conversation_id=${this.currentConversationId}`, {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showToast('Conversación cerrada exitosamente');
                this.loadConversations();
                this.clearCurrentConversation();
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            this.handleError('Error al cerrar la conversación', error);
        }
    }
    
    clearCurrentConversation() {
        this.currentConversationId = null;
        this.lastMessageId = 0;
        
        document.getElementById('noChatSelected')?.classList.remove('d-none');
        document.getElementById('chatContainer')?.classList.add('d-none');
        
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
        });
    }
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    window.adminChat = new AdminChat();
});

// Exponer métodos globales para compatibilidad con HTML inline
window.selectConversation = function(id) {
    window.adminChat?.selectConversation(id);
};

window.filterConversations = function(status) {
    window.adminChat?.filterConversations(status);
};

window.transferConversation = function() {
    window.adminChat?.showTransferModal();
};

window.closeConversation = function() {
    window.adminChat?.closeConversation();
};