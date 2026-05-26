<?php
use App\Core\Config;
include __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid">
    <div class="row justify-content-center min-vh-100">
        <div class="col-lg-8 col-xl-6 p-0">
            <div class="chat-container">
                <!-- Chat Header -->
                <div class="chat-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar me-3">
                                <i class="fas fa-headset"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 text-white">Chat de Soporte</h5>
                                <small class="text-light opacity-75">
                                    <span class="online-indicator"></span>
                                    Disponible 24/7
                                </small>
                            </div>
                        </div>
                        <div class="connection-status" id="connectionStatus">
                            <i class="fas fa-circle text-success"></i>
                            <span class="d-none d-sm-inline">En línea</span>
                        </div>
                    </div>
                </div>

                <!-- Chat Messages -->
                <div class="chat-messages" id="chatMessages">
                    <?php if (empty($messages)): ?>
                        <div class="welcome-section text-center py-5">
                            <div class="mb-4">
                                <div class="welcome-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                            </div>
                            <h4 class="mb-3">¡Bienvenido al Chat de Soporte!</h4>
                            <p class="text-muted mb-4">
                                Estamos aquí para ayudarte con cualquier pregunta sobre nuestros tours.<br>
                                Un operador te atenderá en breve.
                            </p>
                            <div class="quick-actions">
                                <button class="quick-action-btn" onclick="insertQuickMessage('Hola, necesito información sobre tours a Tikal')">
                                    <i class="fas fa-map-marked-alt"></i>
                                    <span>Tours a Tikal</span>
                                </button>
                                <button class="quick-action-btn" onclick="insertQuickMessage('¿Cuáles son los precios y disponibilidad?')">
                                    <i class="fas fa-calendar-check"></i>
                                    <span>Precios</span>
                                </button>
                                <button class="quick-action-btn" onclick="insertQuickMessage('¿Cómo puedo hacer una reserva?')">
                                    <i class="fas fa-bookmark"></i>
                                    <span>Reservar</span>
                                </button>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <?php 
                                $isUser = $message['tipo_emisor'] === 'cliente';
                                $isSystem = $message['tipo_emisor'] === 'sistema';
                                $senderName = $isSystem ? 'Sistema' : ($isUser ? 'Tú' : ($message['emisor_nombre'] ?? 'Operador'));
                            ?>
                            <div class="message <?= $isUser ? 'user' : ($isSystem ? 'system' : 'operator') ?>" data-message-id="<?= $message['id'] ?>">
                                <div class="message-wrapper">
                                    <?php if (!$isUser && !$isSystem): ?>
                                        <div class="message-avatar">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                    <?php elseif ($isUser): ?>
                                        <div class="message-avatar user">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="message-content">
                                        <?php if (!$isSystem): ?>
                                            <div class="message-sender">
                                                <?= htmlspecialchars($senderName) ?>
                                                <span class="message-time">
                                                    <?= date('H:i', strtotime($message['enviado_en'])) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="message-bubble <?= $isUser ? 'user-bubble' : ($isSystem ? 'system-bubble' : 'operator-bubble') ?>">
                                            <?= nl2br(htmlspecialchars($message['mensaje'])) ?>
                                            
                                            <?php if (!empty($message['archivo_url'])): ?>
                                                <div class="message-attachment mt-2">
                                                    <a href="<?= htmlspecialchars($message['archivo_url']) ?>" 
                                                       target="_blank" 
                                                       class="attachment-link">
                                                        <i class="fas fa-paperclip me-1"></i>
                                                        <?= htmlspecialchars($message['archivo_nombre'] ?? 'Archivo adjunto') ?>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if ($isSystem): ?>
                                            <div class="system-time text-center">
                                                <?= date('H:i', strtotime($message['enviado_en'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Typing Indicator -->
                    <div class="typing-indicator" id="typingIndicator" style="display: none;">
                        <div class="message operator">
                            <div class="message-wrapper">
                                <div class="message-avatar">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="message-content">
                                    <div class="message-bubble operator-bubble">
                                        <div class="typing-animation">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Input -->
                <div class="chat-input-section">
                    <form id="chatForm" class="chat-input-form">
                        <div class="input-group">
                            <button type="button" class="btn btn-outline-secondary attach-btn" title="Adjuntar archivo">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <textarea 
                                id="messageInput" 
                                class="form-control message-textarea" 
                                placeholder="Escribe tu mensaje..."
                                rows="1"
                                maxlength="1000"></textarea>
                            <button type="submit" class="btn btn-primary send-btn" id="sendBtn">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        
                        <!-- Character counter -->
                        <div class="char-counter">
                            <small class="text-muted">
                                <span id="charCount">0</span>/1000
                            </small>
                        </div>
                        
                        <!-- File upload (hidden) -->
                        <input type="file" 
                               id="fileInput" 
                               accept="image/*,.pdf,.doc,.docx,.txt" 
                               style="display: none;">
                    </form>
                    
                    <!-- File preview -->
                    <div id="filePreview" class="file-preview" style="display: none;">
                        <div class="file-preview-content">
                            <div class="file-info">
                                <i class="fas fa-file"></i>
                                <span id="fileName"></span>
                                <small id="fileSize" class="text-muted"></small>
                            </div>
                            <button type="button" class="btn-close" onclick="removeFile()" aria-label="Quitar archivo"></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-container {
    height: 100vh;
    background: white;
    display: flex;
    flex-direction: column;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

.chat-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.avatar {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.online-indicator {
    width: 8px;
    height: 8px;
    background: #28a745;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
}

.connection-status {
    font-size: 0.875rem;
    color: rgba(255,255,255,0.9);
}

.connection-status.disconnected {
    color: #dc3545;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
    background: #f8f9fa;
    scroll-behavior: smooth;
}

.welcome-section {
    max-width: 400px;
    margin: 0 auto;
}

.welcome-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
    font-size: 2rem;
}

.quick-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 50px;
    color: #495057;
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 0.875rem;
    cursor: pointer;
}

.quick-action-btn:hover {
    background: #007bff;
    color: white;
    border-color: #007bff;
    transform: translateY(-1px);
}

.message {
    margin-bottom: 1rem;
    display: flex;
}

.message.user {
    justify-content: flex-end;
}

.message.system {
    justify-content: center;
}

.message-wrapper {
    display: flex;
    align-items: flex-end;
    max-width: 75%;
}

.message.user .message-wrapper {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 32px;
    height: 32px;
    background: #6c757d;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.75rem;
    margin: 0 0.5rem;
    flex-shrink: 0;
}

.message-avatar.user {
    background: #007bff;
}

.message-content {
    flex-grow: 1;
}

.message-sender {
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.message-time {
    font-size: 0.7rem;
    opacity: 0.7;
}

.system-time {
    font-size: 0.7rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.message-bubble {
    padding: 0.75rem 1rem;
    border-radius: 1rem;
    position: relative;
    word-wrap: break-word;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.operator-bubble {
    background: white;
    color: #2c3e50;
    border-bottom-left-radius: 0.25rem;
}

.user-bubble {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    border-bottom-right-radius: 0.25rem;
}

.system-bubble {
    background: #e9ecef;
    color: #6c757d;
    font-style: italic;
    text-align: center;
    border-radius: 1rem;
    font-size: 0.875rem;
}

.message-attachment {
    margin-top: 0.5rem;
}

.attachment-link {
    display: inline-flex;
    align-items: center;
    color: inherit;
    text-decoration: none;
    opacity: 0.8;
}

.attachment-link:hover {
    opacity: 1;
    text-decoration: underline;
}

.typing-animation {
    display: flex;
    gap: 0.25rem;
    align-items: center;
    padding: 0.25rem 0;
}

.typing-animation span {
    width: 6px;
    height: 6px;
    background: #6c757d;
    border-radius: 50%;
    animation: typing 1.4s infinite ease-in-out;
}

.typing-animation span:nth-child(1) { animation-delay: -0.32s; }
.typing-animation span:nth-child(2) { animation-delay: -0.16s; }

@keyframes typing {
    0%, 80%, 100% { transform: scale(0.8); opacity: 0.5; }
    40% { transform: scale(1); opacity: 1; }
}

.chat-input-section {
    padding: 1rem;
    background: white;
    border-top: 1px solid #e9ecef;
}

.chat-input-form .input-group {
    align-items: flex-end;
}

.message-textarea {
    border: 1px solid #dee2e6;
    border-radius: 1.5rem;
    padding: 0.75rem 1rem;
    resize: none;
    max-height: 120px;
    line-height: 1.4;
}

.message-textarea:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.attach-btn, .send-btn {
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    margin: 0 0.25rem;
}

.send-btn {
    background: linear-gradient(135deg, #007bff, #0056b3);
    color: white;
    transition: transform 0.2s ease;
}

.send-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,123,255,0.3);
}

.send-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.char-counter {
    text-align: right;
    margin-top: 0.25rem;
}

.file-preview {
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}

.file-preview-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.file-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Scrollbar */
.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .chat-container {
        height: 100vh;
    }
    
    .chat-messages {
        padding: 0.5rem;
    }
    
    .message-wrapper {
        max-width: 85%;
    }
    
    .message-avatar {
        width: 28px;
        height: 28px;
        font-size: 0.7rem;
    }
    
    .welcome-icon {
        width: 60px;
        height: 60px;
        font-size: 1.5rem;
    }
    
    .quick-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .quick-action-btn {
        justify-content: center;
        padding: 0.75rem;
    }
}
</style>

<script>
// Chat configuration
const chatConfig = {
    conversationId: <?= json_encode($conversation['id']) ?>,
    sessionId: <?= json_encode($sessionId) ?>,
    userId: <?= json_encode($user['id'] ?? null) ?>,
    baseUrl: '<?= Config::getBaseUrl() ?>',
    pollInterval: 3000,
    maxRetries: 3
};

let lastMessageId = <?= empty($messages) ? 0 : end($messages)['id'] ?>;
let isPolling = false;
let retryCount = 0;
let typingTimeout = null;

// Initialize chat
document.addEventListener('DOMContentLoaded', function() {
    initializeChat();
    setupEventListeners();
    startPolling();
});

function initializeChat() {
    scrollToBottom();
}

function setupEventListeners() {
    const form = document.getElementById('chatForm');
    const input = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const attachBtn = document.querySelector('.attach-btn');
    const fileInput = document.getElementById('fileInput');
    const charCount = document.getElementById('charCount');

    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });

    // Auto-resize textarea and character count
    input.addEventListener('input', function() {
        // Auto resize
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
        
        // Update character count
        const count = this.value.length;
        charCount.textContent = count;
        charCount.parentElement.style.color = count > 900 ? '#dc3545' : '#6c757d';
        
        // Update send button
        sendBtn.disabled = !this.value.trim();
    });

    // Send on Enter (Shift+Enter for new line)
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (!sendBtn.disabled) {
                sendMessage();
            }
        }
    });

    // File attachment
    attachBtn.addEventListener('click', function() {
        fileInput.click();
    });

    fileInput.addEventListener('change', handleFileUpload);
}

function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (!message) return;

    // Add message to UI immediately
    addMessageToUI({
        id: 'temp-' + Date.now(),
        tipo_emisor: 'cliente',
        mensaje: message,
        enviado_en: new Date().toISOString()
    });

    // Clear input
    input.value = '';
    input.style.height = 'auto';
    document.getElementById('charCount').textContent = '0';
    document.getElementById('sendBtn').disabled = true;

    // Show typing indicator
    showTypingIndicator();

    // Send to server
    const formData = new FormData();
    formData.append('conversation_id', chatConfig.conversationId);
    formData.append('session_id', chatConfig.sessionId);
    formData.append('message', message);

    fetch(chatConfig.baseUrl + '?route=chat/send_message', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideTypingIndicator();
        if (data.success) {
            // Replace temp message with real one
            updateTempMessage('temp-' + data.message_id, data.message_id);
        } else {
            showError('Error al enviar mensaje: ' + (data.message || 'Error desconocido'));
            removeTempMessage();
        }
    })
    .catch(error => {
        hideTypingIndicator();
        showError('Error de conexión');
        removeTempMessage();
        console.error('Error:', error);
    });
}

function addMessageToUI(message) {
    const container = document.getElementById('chatMessages');
    const isUser = message.tipo_emisor === 'cliente';
    const isSystem = message.tipo_emisor === 'sistema';
    
    const messageEl = document.createElement('div');
    messageEl.className = `message ${isUser ? 'user' : (isSystem ? 'system' : 'operator')}`;
    messageEl.setAttribute('data-message-id', message.id);

    const time = new Date(message.enviado_en).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    });

    let avatarHtml = '';
    let senderHtml = '';
    
    if (!isSystem) {
        if (isUser) {
            avatarHtml = '<div class="message-avatar user"><i class="fas fa-user"></i></div>';
            senderHtml = `<div class="message-sender">Tú <span class="message-time">${time}</span></div>`;
        } else {
            avatarHtml = '<div class="message-avatar"><i class="fas fa-user-tie"></i></div>';
            senderHtml = `<div class="message-sender">${message.emisor_nombre || 'Operador'} <span class="message-time">${time}</span></div>`;
        }
    }

    messageEl.innerHTML = `
        <div class="message-wrapper">
            ${avatarHtml}
            <div class="message-content">
                ${senderHtml}
                <div class="message-bubble ${isUser ? 'user-bubble' : (isSystem ? 'system-bubble' : 'operator-bubble')}">
                    ${escapeHtml(message.mensaje).replace(/\n/g, '<br>')}
                </div>
                ${isSystem ? `<div class="system-time text-center">${time}</div>` : ''}
            </div>
        </div>
    `;

    // Insert before typing indicator
    const typingIndicator = document.getElementById('typingIndicator');
    container.insertBefore(messageEl, typingIndicator);
    
    // Update last message ID
    if (message.id && !message.id.toString().startsWith('temp-')) {
        lastMessageId = Math.max(lastMessageId, parseInt(message.id));
    }

    scrollToBottom();
}

function startPolling() {
    if (isPolling) return;
    isPolling = true;
    pollForMessages();
}

function pollForMessages() {
    if (!isPolling) return;
    
    const url = `${chatConfig.baseUrl}?route=chat/get_messages&conversation_id=${chatConfig.conversationId}&last_message_id=${lastMessageId}`;
    
    fetch(url)
    .then(response => response.json())
    .then(data => {
        if (data.success && data.messages) {
            data.messages.forEach(addMessageToUI);
            updateConnectionStatus(true);
            retryCount = 0;
        }
    })
    .catch(error => {
        console.error('Polling error:', error);
        retryCount++;
        updateConnectionStatus(false);
        
        if (retryCount >= chatConfig.maxRetries) {
            showError('Conexión perdida. Recarga la página.');
            isPolling = false;
            return;
        }
    })
    .finally(() => {
        if (isPolling) {
            setTimeout(pollForMessages, chatConfig.pollInterval);
        }
    });
}

function showTypingIndicator() {
    document.getElementById('typingIndicator').style.display = 'block';
    scrollToBottom();
    
    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(hideTypingIndicator, 5000);
}

function hideTypingIndicator() {
    document.getElementById('typingIndicator').style.display = 'none';
    clearTimeout(typingTimeout);
}

function scrollToBottom() {
    const messages = document.getElementById('chatMessages');
    messages.scrollTop = messages.scrollHeight;
}

function insertQuickMessage(text) {
    const input = document.getElementById('messageInput');
    input.value = text;
    input.focus();
    input.dispatchEvent(new Event('input'));
}

function updateConnectionStatus(connected) {
    const status = document.getElementById('connectionStatus');
    if (connected) {
        status.innerHTML = '<i class="fas fa-circle text-success"></i><span class="d-none d-sm-inline"> En línea</span>';
        status.classList.remove('disconnected');
    } else {
        status.innerHTML = '<i class="fas fa-circle text-danger"></i><span class="d-none d-sm-inline"> Sin conexión</span>';
        status.classList.add('disconnected');
    }
}

function handleFileUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Check file size (5MB limit)
    if (file.size > 5 * 1024 * 1024) {
        showError('El archivo es demasiado grande (máximo 5MB)');
        return;
    }
    
    // Show preview
    const preview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const fileSize = document.getElementById('fileSize');
    
    fileName.textContent = file.name;
    fileSize.textContent = formatFileSize(file.size);
    preview.style.display = 'block';
}

function removeFile() {
    document.getElementById('fileInput').value = '';
    document.getElementById('filePreview').style.display = 'none';
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + ' KB';
    return Math.round(bytes / (1024 * 1024)) + ' MB';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showError(message) {
    // Simple toast notification
    console.error(message);
    // You could implement a proper toast system here
    alert(message);
}

function updateTempMessage(tempId, realId) {
    const tempEl = document.querySelector(`[data-message-id="${tempId}"]`);
    if (tempEl) {
        tempEl.setAttribute('data-message-id', realId);
    }
}

function removeTempMessage() {
    const tempMessages = document.querySelectorAll('[data-message-id^="temp-"]');
    tempMessages.forEach(el => el.remove());
}

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    isPolling = false;
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
