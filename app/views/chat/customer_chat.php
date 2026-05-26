<?php
use App\Core\Config;
use App\Core\Helpers;
$title = $title ?? 'Chat con Soporte';
$conversation = $conversation ?? null;
$messages = $messages ?? [];
$customer_phone = $customer_phone ?? null;
$csrf_token = $csrf_token ?? '';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Mayan World Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --chat-primary: #25d366;
            --chat-secondary: #128c7e;
            --chat-bg: #e5ddd5;
            --message-sent: #dcf8c6;
            --message-received: #ffffff;
            --message-system: #fff2cc;
        }

        body {
            background: var(--chat-bg);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
        }

        .chat-container {
            max-width: 800px;
            margin: 0 auto;
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: white;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .chat-header {
            background: var(--chat-secondary);
            color: white;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .agent-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .agent-info h6 {
            margin: 0;
            font-size: 1rem;
        }

        .agent-status {
            font-size: 0.8rem;
            opacity: 0.8;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4caf50;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            background: var(--chat-bg);
            scroll-behavior: smooth;
        }

        .message {
            margin-bottom: 1rem;
            display: flex;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.sent {
            justify-content: flex-end;
        }

        .message.received {
            justify-content: flex-start;
        }

        .message.system {
            justify-content: center;
        }

        .message-bubble {
            max-width: 70%;
            padding: 0.8rem 1rem;
            border-radius: 18px;
            position: relative;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .message.sent .message-bubble {
            background: var(--message-sent);
            border-bottom-right-radius: 4px;
        }

        .message.received .message-bubble {
            background: var(--message-received);
            border-bottom-left-radius: 4px;
        }

        .message.system .message-bubble {
            background: var(--message-system);
            border: 1px solid #f0c674;
            border-radius: 12px;
            max-width: 80%;
            text-align: center;
            font-size: 0.9rem;
            color: #8b6914;
        }

        .message-content {
            margin: 0;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .message-time {
            font-size: 0.7rem;
            opacity: 0.6;
            margin-top: 0.3rem;
            text-align: right;
        }

        .message.received .message-time {
            text-align: left;
        }

        .message-status {
            font-size: 0.7rem;
            opacity: 0.6;
            margin-left: 0.3rem;
        }

        .chat-input-container {
            background: white;
            border-top: 1px solid #e0e0e0;
            padding: 1rem;
            position: sticky;
            bottom: 0;
        }

        .input-group {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
        }

        .message-input {
            flex: 1;
            border: 1px solid #ccc;
            border-radius: 20px;
            padding: 0.8rem 1rem;
            resize: none;
            max-height: 100px;
            min-height: 40px;
            font-family: inherit;
            line-height: 1.4;
        }

        .message-input:focus {
            outline: none;
            border-color: var(--chat-primary);
            box-shadow: 0 0 0 2px rgba(37, 211, 102, 0.2);
        }

        .send-button {
            background: var(--chat-primary);
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            color: white;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-button:hover {
            background: var(--chat-secondary);
            transform: scale(1.05);
        }

        .send-button:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .typing-indicator {
            display: none;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            font-style: italic;
            color: #666;
            font-size: 0.9rem;
        }

        .typing-dots {
            display: flex;
            gap: 2px;
        }

        .typing-dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #666;
            animation: typingBounce 1.4s infinite ease-in-out both;
        }

        .typing-dot:nth-child(1) { animation-delay: -0.32s; }
        .typing-dot:nth-child(2) { animation-delay: -0.16s; }

        @keyframes typingBounce {
            0%, 80%, 100% { 
                transform: scale(0);
            } 40% { 
                transform: scale(1);
            }
        }

        .connection-status {
            padding: 0.5rem 1rem;
            text-align: center;
            font-size: 0.8rem;
            background: #fff3cd;
            border-bottom: 1px solid #ffeaa7;
            color: #856404;
        }

        .connection-status.connected {
            background: #d1edff;
            color: #0c5460;
            border-bottom-color: #b8daff;
        }

        .connection-status.error {
            background: #f8d7da;
            color: #721c24;
            border-bottom-color: #f5c6cb;
        }

        .welcome-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 2rem;
            text-align: center;
        }

        .welcome-icon {
            font-size: 4rem;
            color: var(--chat-primary);
            margin-bottom: 1rem;
        }

        .quick-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .quick-action {
            background: #f0f0f0;
            border: none;
            border-radius: 15px;
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .quick-action:hover {
            background: var(--chat-primary);
            color: white;
        }

        .chat-start-form {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }

        .form-floating {
            margin-bottom: 1rem;
        }

        .product-context {
            background: #e3f2fd;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border-left: 4px solid #2196f3;
        }

        .message-actions {
            display: none;
            position: absolute;
            top: -10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            border-radius: 15px;
            padding: 0.2rem;
        }

        .message:hover .message-actions {
            display: flex;
        }

        .message-action {
            background: none;
            border: none;
            color: white;
            padding: 0.3rem;
            cursor: pointer;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }

        .message-action:hover {
            background: rgba(255,255,255,0.2);
        }

        /* Mobile optimizations */
        @media (max-width: 768px) {
            .chat-container {
                height: 100vh;
                border-radius: 0;
            }
            
            .message-bubble {
                max-width: 85%;
            }
            
            .chat-input-container {
                padding: 0.8rem;
            }
        }

        /* Scroll to bottom button */
        .scroll-bottom-btn {
            position: absolute;
            bottom: 80px;
            right: 20px;
            background: var(--chat-primary);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            color: white;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .unread-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="legacy-theme">
    <div class="chat-container">
        <!-- Status de conexión -->
        <div class="connection-status" id="connectionStatus">
            <i class="fas fa-wifi"></i> Conectando al chat...
        </div>

        <!-- Header del chat -->
        <div class="chat-header" id="chatHeader" style="display: none;">
            <img src="assets/images/maya-support-avatar.jpg" alt="Soporte" class="agent-avatar" id="agentAvatar">
            <div class="agent-info">
                <h6 id="agentName">Soporte Mayan World</h6>
                <div class="agent-status">
                    <span class="status-indicator"></span>
                    <span id="agentStatus">En línea</span>
                </div>
            </div>
            <div class="ms-auto">
                <button class="btn btn-link text-white" onclick="minimizeChat()" title="Minimizar">
                    <i class="fas fa-minus"></i>
                </button>
                <button class="btn btn-link text-white" onclick="closeChat()" title="Cerrar chat">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Área de mensajes -->
        <div class="chat-messages" id="chatMessages">
            <?php if (!$conversation): ?>
            <!-- Pantalla de bienvenida -->
            <div class="welcome-screen" id="welcomeScreen">
                <div class="welcome-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h4>¡Bienvenido a nuestro chat!</h4>
                <p class="text-muted mb-3">
                    Estamos aquí para ayudarte con tus planes de viaje por el mundo Maya
                </p>
                
                <!-- Acciones rápidas -->
                <div class="quick-actions">
                    <button class="quick-action" onclick="setQuickMessage('Hola, quisiera información sobre tours')">
                        🏛️ Tours disponibles
                    </button>
                    <button class="quick-action" onclick="setQuickMessage('¿Cuáles son los precios?')">
                        💰 Precios
                    </button>
                    <button class="quick-action" onclick="setQuickMessage('Quiero hacer una reserva')">
                        📅 Reservar ahora
                    </button>
                    <button class="quick-action" onclick="setQuickMessage('Necesito información sobre transporte')">
                        🚌 Transporte
                    </button>
                </div>

                <!-- Formulario de inicio -->
                <form class="chat-start-form" id="chatStartForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="tour_id" value="<?= $_GET['tour_id'] ?? '' ?>">
                    
                    <?php if (!empty($_GET['tour_id'])): ?>
                    <div class="product-context">
                        <small><strong>Consultando sobre:</strong></small>
                        <div id="productInfo">Cargando información del tour...</div>
                    </div>
                    <?php endif; ?>

                    <div class="form-floating">
                        <input type="text" class="form-control" id="customerName" name="name" placeholder="Tu nombre" required>
                        <label for="customerName">Tu nombre</label>
                    </div>

                    <div class="form-floating">
                        <input type="tel" class="form-control" id="customerPhone" name="phone" placeholder="Tu teléfono" required>
                        <label for="customerPhone">Tu teléfono (WhatsApp)</label>
                        <div class="form-text">Incluye código de país (ej: +502 1234-5678)</div>
                    </div>

                    <div class="form-floating">
                        <input type="email" class="form-control" id="customerEmail" name="email" placeholder="Tu email">
                        <label for="customerEmail">Tu email (opcional)</label>
                    </div>

                    <div class="form-floating">
                        <textarea class="form-control" id="initialMessage" name="message" placeholder="¿En qué podemos ayudarte?" required style="height: 100px"></textarea>
                        <label for="initialMessage">¿En qué podemos ayudarte?</label>
                    </div>

                    <button type="submit" class="btn btn-success w-100 mt-3">
                        <i class="fas fa-comments me-2"></i>Iniciar Chat
                    </button>

                    <p class="text-muted small mt-2 text-center">
                        También puedes escribirnos directamente por WhatsApp: 
                        <a href="https://wa.me/<?= Config::SOCIAL_WHATSAPP ?? '+50212345678' ?>" class="text-success">
                            <i class="fab fa-whatsapp"></i> <?= Config::SOCIAL_WHATSAPP ?? '+502 1234-5678' ?>
                        </a>
                    </p>
                </form>
            </div>
            <?php else: ?>
            <!-- Mensajes existentes -->
            <?php foreach ($messages as $message): ?>
            <div class="message <?= $message['sender_type'] === 'customer' ? 'sent' : ($message['sender_type'] === 'system' ? 'system' : 'received') ?>" data-message-id="<?= $message['id'] ?>">
                <div class="message-bubble">
                    <div class="message-actions">
                        <button class="message-action" onclick="copyMessage(<?= $message['id'] ?>)" title="Copiar">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                    <p class="message-content"><?= nl2br(htmlspecialchars($message['message_content'])) ?></p>
                    <div class="message-time">
                        <?= date('H:i', strtotime($message['sent_at'])) ?>
                        <?php if ($message['sender_type'] === 'customer'): ?>
                        <span class="message-status">
                            <i class="fas fa-check-double <?= $message['read_at'] ? 'text-primary' : 'text-muted' ?>"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- Indicador de escritura -->
            <div class="typing-indicator" id="typingIndicator">
                <span>El agente está escribiendo</span>
                <div class="typing-dots">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            </div>
        </div>

        <!-- Botón para ir al final -->
        <button class="scroll-bottom-btn" id="scrollBottomBtn" onclick="scrollToBottom()">
            <i class="fas fa-chevron-down"></i>
            <span class="unread-badge" id="unreadBadge" style="display: none;">0</span>
        </button>

        <!-- Entrada de mensajes -->
        <div class="chat-input-container" id="chatInputContainer" style="display: <?= $conversation ? 'block' : 'none' ?>;">
            <form id="messageForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="conversation_id" value="<?= $conversation['id'] ?? '' ?>">
                
                <div class="input-group">
                    <textarea class="message-input" id="messageInput" name="message" placeholder="Escribe tu mensaje..." rows="1" maxlength="1000"></textarea>
                    <button type="submit" class="send-button" id="sendButton" disabled>
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
    class ChatApp {
        constructor() {
            this.conversationId = <?= $conversation['id'] ?? 'null' ?>;
            this.lastMessageId = <?= empty($messages) ? 0 : max(array_column($messages, 'id')) ?>;
            this.isConnected = false;
            this.pollInterval = null;
            this.typingTimeout = null;
            this.unreadCount = 0;
            
            this.init();
        }

        init() {
            this.setupEventListeners();
            this.setupAutoResize();
            
            if (this.conversationId) {
                this.startPolling();
                this.showChatInterface();
            }
            
            this.updateConnectionStatus('connected');
            this.scrollToBottom();
        }

        setupEventListeners() {
            // Formulario de inicio de chat
            $('#chatStartForm').on('submit', (e) => {
                e.preventDefault();
                this.startConversation();
            });

            // Formulario de mensaje
            $('#messageForm').on('submit', (e) => {
                e.preventDefault();
                this.sendMessage();
            });

            // Auto-resize del textarea
            $('#messageInput').on('input', () => {
                this.adjustTextareaHeight();
                this.toggleSendButton();
                this.showTypingIndicator();
            });

            // Enter para enviar (Shift+Enter para nueva línea)
            $('#messageInput').on('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    if (!$('#sendButton').prop('disabled')) {
                        this.sendMessage();
                    }
                }
            });

            // Scroll detection
            $('#chatMessages').on('scroll', () => {
                this.handleScroll();
            });

            // Detectar cuando la pestaña está activa
            $(document).on('visibilitychange', () => {
                if (!document.hidden && this.conversationId) {
                    this.markMessagesAsRead();
                }
            });

            // Cargar información del tour si está en contexto
            if ($('input[name="tour_id"]').val()) {
                this.loadProductContext($('input[name="tour_id"]').val());
            }
        }

        startConversation() {
            const formData = new FormData(document.getElementById('chatStartForm'));
            
            $('#chatStartForm button').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Iniciando chat...');
            
            $.post('?route=chat/start-web-conversation', formData)
                .done((response) => {
                    if (response.success) {
                        this.conversationId = response.conversation_id;
                        this.lastMessageId = 0;
                        
                        $('#welcomeScreen').fadeOut(() => {
                            this.showChatInterface();
                            this.startPolling();
                        });
                        
                        if (response.assigned_agent) {
                            this.updateAgentInfo(response.assigned_agent);
                        }
                    } else {
                        this.showError(response.error);
                        $('#chatStartForm button').prop('disabled', false).html('<i class="fas fa-comments me-2"></i>Iniciar Chat');
                    }
                })
                .fail(() => {
                    this.showError('Error de conexión. Intenta de nuevo.');
                    $('#chatStartForm button').prop('disabled', false).html('<i class="fas fa-comments me-2"></i>Iniciar Chat');
                });
        }

        sendMessage() {
            const message = $('#messageInput').val().trim();
            if (!message || !this.conversationId) return;

            const messageData = {
                conversation_id: this.conversationId,
                message: message,
                csrf_token: $('input[name="csrf_token"]').val()
            };

            // Mostrar mensaje inmediatamente (optimistic UI)
            this.addMessage({
                id: 'temp-' + Date.now(),
                message_content: message,
                sender_type: 'customer',
                sent_at: new Date().toISOString(),
                sending: true
            });

            $('#messageInput').val('').trigger('input');
            this.scrollToBottom();

            $.post('?route=chat/send-message', messageData)
                .done((response) => {
                    if (response.success) {
                        // Reemplazar mensaje temporal con el real
                        $('[data-message-id="temp-' + (response.message_id - 1) + '"]').attr('data-message-id', response.message_id);
                        $('.message-status .fa-spinner').removeClass('fa-spinner fa-spin').addClass('fa-check');
                    } else {
                        this.showError(response.error);
                        // Marcar mensaje como fallido
                        $('[data-message-id*="temp-"]').addClass('message-failed');
                    }
                })
                .fail(() => {
                    this.showError('Error enviando mensaje');
                    $('[data-message-id*="temp-"]').addClass('message-failed');
                });
        }

        startPolling() {
            if (this.pollInterval) clearInterval(this.pollInterval);
            
            this.pollInterval = setInterval(() => {
                this.fetchNewMessages();
            }, 2000); // Cada 2 segundos

            // Fetch inicial
            this.fetchNewMessages();
        }

        fetchNewMessages() {
            if (!this.conversationId) return;

            $.get('?route=chat/get-messages', {
                conversation_id: this.conversationId,
                last_message_id: this.lastMessageId
            })
            .done((response) => {
                if (response.success && response.messages.length > 0) {
                    response.messages.forEach(message => {
                        this.addMessage(message);
                        this.lastMessageId = Math.max(this.lastMessageId, message.id);
                    });
                    
                    this.scrollToBottom();
                    
                    if (!document.hidden) {
                        this.markMessagesAsRead();
                    } else {
                        this.unreadCount += response.messages.filter(m => m.sender_type !== 'customer').length;
                        this.updateUnreadBadge();
                    }
                }
                
                this.updateConnectionStatus('connected');
            })
            .fail(() => {
                this.updateConnectionStatus('error');
            });
        }

        addMessage(message) {
            // Evitar duplicados
            if ($('[data-message-id="' + message.id + '"]').length > 0) return;

            const messageClass = message.sender_type === 'customer' ? 'sent' : 
                                (message.sender_type === 'system' ? 'system' : 'received');
            
            const messageElement = $(`
                <div class="message ${messageClass}" data-message-id="${message.id}">
                    <div class="message-bubble">
                        <div class="message-actions">
                            <button class="message-action" onclick="chatApp.copyMessage(${message.id})" title="Copiar">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <p class="message-content">${this.formatMessageContent(message.message_content)}</p>
                        <div class="message-time">
                            ${this.formatTime(message.sent_at)}
                            ${message.sender_type === 'customer' ? this.getMessageStatus(message) : ''}
                        </div>
                    </div>
                </div>
            `);

            $('#typingIndicator').before(messageElement);
            
            // Animar entrada
            messageElement.hide().slideDown(200);
        }

        formatMessageContent(content) {
            // Convertir URLs en links
            content = content.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>');
            // Convertir saltos de línea
            content = content.replace(/\n/g, '<br>');
            return content;
        }

        formatTime(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString('es-GT', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: false 
            });
        }

        getMessageStatus(message) {
            if (message.sending) {
                return '<span class="message-status"><i class="fas fa-spinner fa-spin"></i></span>';
            }
            if (message.read_at) {
                return '<span class="message-status"><i class="fas fa-check-double text-primary"></i></span>';
            }
            return '<span class="message-status"><i class="fas fa-check-double text-muted"></i></span>';
        }

        showChatInterface() {
            $('#chatHeader').show();
            $('#chatInputContainer').show();
            this.updateConnectionStatus('connected');
        }

        updateAgentInfo(agent) {
            $('#agentName').text(agent.name);
            if (agent.avatar) {
                $('#agentAvatar').attr('src', agent.avatar);
            }
        }

        updateConnectionStatus(status) {
            const statusElement = $('#connectionStatus');
            statusElement.removeClass('connected error');
            
            switch (status) {
                case 'connected':
                    statusElement.addClass('connected')
                        .html('<i class="fas fa-check-circle"></i> Conectado al chat');
                    this.isConnected = true;
                    break;
                case 'error':
                    statusElement.addClass('error')
                        .html('<i class="fas fa-exclamation-triangle"></i> Error de conexión - Reintentando...');
                    this.isConnected = false;
                    break;
                default:
                    statusElement.html('<i class="fas fa-wifi"></i> Conectando al chat...');
                    break;
            }
        }

        adjustTextareaHeight() {
            const textarea = document.getElementById('messageInput');
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
        }

        toggleSendButton() {
            const message = $('#messageInput').val().trim();
            $('#sendButton').prop('disabled', !message);
        }

        scrollToBottom() {
            const messagesContainer = document.getElementById('chatMessages');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
            $('#scrollBottomBtn').hide();
            this.unreadCount = 0;
            this.updateUnreadBadge();
        }

        handleScroll() {
            const messagesContainer = document.getElementById('chatMessages');
            const isAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop <= messagesContainer.clientHeight + 50;
            
            if (isAtBottom) {
                $('#scrollBottomBtn').hide();
                this.unreadCount = 0;
                this.updateUnreadBadge();
            } else if (this.unreadCount > 0) {
                $('#scrollBottomBtn').show();
            }
        }

        updateUnreadBadge() {
            const badge = $('#unreadBadge');
            if (this.unreadCount > 0) {
                badge.text(this.unreadCount).show();
            } else {
                badge.hide();
            }
        }

        showTypingIndicator() {
            clearTimeout(this.typingTimeout);
            
            // Simular indicador de escritura por parte del agente (opcional)
            this.typingTimeout = setTimeout(() => {
                // Lógica para mostrar que el agente está escribiendo
            }, 1000);
        }

        markMessagesAsRead() {
            if (!this.conversationId) return;
            
            // Implementar marcado de mensajes como leídos
            $.post('?route=chat/mark-read', {
                conversation_id: this.conversationId,
                csrf_token: $('input[name="csrf_token"]').val()
            });
        }

        loadProductContext(productId) {
            $.get('?route=tour/info/' + productId)
                .done((response) => {
                    if (response.success) {
                        $('#productInfo').html(`
                            <strong>${response.product.nombre}</strong><br>
                            <small class="text-muted">$${response.product.precio} USD</small>
                        `);
                    }
                });
        }

        copyMessage(messageId) {
            const messageContent = $('[data-message-id="' + messageId + '"] .message-content').text();
            navigator.clipboard.writeText(messageContent).then(() => {
                this.showToast('Mensaje copiado');
            });
        }

        showError(message) {
            this.showToast(message, 'error');
        }

        showToast(message, type = 'info') {
            // Crear toast
            const toast = $(`
                <div class="toast align-items-center text-bg-${type === 'error' ? 'danger' : 'success'} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                    </div>
                </div>
            `);
            
            // Agregar al DOM y mostrar
            $('body').append(`<div class="toast-container position-fixed bottom-0 end-0 p-3">${toast[0].outerHTML}</div>`);
            
            const toastElement = new bootstrap.Toast($('.toast').last()[0]);
            toastElement.show();
            
            // Limpiar después
            setTimeout(() => {
                $('.toast-container').last().remove();
            }, 5000);
        }
    }

    // Funciones globales
    function setQuickMessage(message) {
        $('#initialMessage').val(message);
    }

    function minimizeChat() {
        $('.chat-container').toggleClass('minimized');
    }

    function closeChat() {
        if (confirm('¿Seguro que quieres cerrar el chat?')) {
            window.close();
        }
    }

    function scrollToBottom() {
        if (window.chatApp) {
            window.chatApp.scrollToBottom();
        }
    }

    // Inicializar aplicación
    $(document).ready(() => {
        window.chatApp = new ChatApp();
    });
    </script>
</body>
</html>
