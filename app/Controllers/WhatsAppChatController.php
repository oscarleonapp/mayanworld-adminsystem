<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Core\Config;
use App\Models\WhatsAppChat;
use Exception;

class WhatsAppChatController extends BaseController
{
    private $whatsappChatModel;

    public function __construct()
    {
        parent::__construct();
        $this->whatsappChatModel = new WhatsAppChat();
    }

    /**
     * Página principal del chat (cliente)
     */
    public function index()
    {
        // Verificar si hay una conversación activa para el usuario
        $activeConversation = null;
        $messages = [];
        
        if (isset($_SESSION['customer_phone'])) {
            $activeConversation = $this->chatModel->db->fetch(
                "SELECT * FROM chat_conversations 
                 WHERE customer_phone = ? 
                 AND status IN ('active', 'waiting_customer', 'pending')
                 ORDER BY last_activity_at DESC LIMIT 1",
                [$_SESSION['customer_phone']]
            );
            
            if ($activeConversation) {
                $messages = $this->chatModel->getConversationMessages($activeConversation['id'], 50);
            }
        }

        $this->view('chat/customer_chat', [
            'title' => 'Chat con Soporte',
            'conversation' => $activeConversation,
            'messages' => $messages,
            'customer_phone' => $_SESSION['customer_phone'] ?? null,
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }

    /**
     * Iniciar nueva conversación desde web
     */
    public function startWebConversation()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        try {
            $this->validateCsrf();

            $customerPhone = $this->getInput('phone');
            $customerName = $this->getInput('name');
            $customerEmail = $this->getInput('email');
            $initialMessage = $this->getInput('message');
            $productId = (int)$this->getInput('tour_id');
            
            if (!$customerPhone || !$initialMessage) {
                $this->json(['success' => false, 'error' => 'Teléfono y mensaje son requeridos']);
                return;
            }

            // Crear conversación
            $conversation = $this->chatModel->getOrCreateConversation($customerPhone, [
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'channel' => 'web',
                'source_page' => $_SERVER['HTTP_REFERER'] ?? null,
                'related_product_id' => $productId ?: null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            if (!$conversation) {
                $this->json(['success' => false, 'error' => 'Error creando conversación']);
                return;
            }

            // Guardar mensaje inicial del cliente
            $messageId = $this->chatModel->saveMessage([
                'conversation_id' => $conversation['id'],
                'message_type' => 'text',
                'sender_type' => 'customer',
                'message_content' => $initialMessage
            ]);

            // Procesar respuestas automáticas
            $this->processWebAutoResponses($conversation, $initialMessage);

            // Asignar agente si es necesario
            $specialization = $this->detectSpecializationFromContext($productId, $initialMessage);
            $assignedAgent = $this->chatModel->autoAssignAgent($conversation['id'], $specialization);

            // Guardar datos en sesión
            $_SESSION['customer_phone'] = $customerPhone;
            $_SESSION['customer_name'] = $customerName;
            $_SESSION['active_conversation_id'] = $conversation['id'];

            $this->json([
                'success' => true,
                'conversation_id' => $conversation['id'],
                'message_id' => $messageId,
                'assigned_agent' => $assignedAgent ? [
                    'name' => $assignedAgent['agent_name'],
                    'avatar' => $assignedAgent['avatar_url']
                ] : null
            ]);

        } catch (Exception $e) {
            error_log("Error starting web conversation: " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error interno del servidor']);
        }
    }

    /**
     * Enviar mensaje desde web
     */
    public function sendMessage()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        try {
            $this->validateCsrf();

            $conversationId = (int)$this->getInput('conversation_id');
            $message = $this->getInput('message');
            $messageType = $this->getInput('type', 'text');
            
            if (!$conversationId || !$message) {
                $this->json(['success' => false, 'error' => 'Datos incompletos']);
                return;
            }

            // Verificar que el usuario tenga acceso a esta conversación
            $conversation = $this->chatModel->find($conversationId);
            if (!$conversation || $conversation['customer_phone'] !== ($_SESSION['customer_phone'] ?? '')) {
                $this->json(['success' => false, 'error' => 'Conversación no encontrada']);
                return;
            }

            // Determinar el tipo de remitente
            $senderType = isset($_SESSION['agent_id']) ? 'agent' : 'customer';
            $senderId = $_SESSION['agent_id'] ?? null;

            // Guardar mensaje
            $messageId = $this->chatModel->saveMessage([
                'conversation_id' => $conversationId,
                'message_type' => $messageType,
                'sender_type' => $senderType,
                'sender_id' => $senderId,
                'message_content' => $message
            ]);

            // Actualizar última actividad de la conversación
            $this->chatModel->db->update('chat_conversations', [
                'last_activity_at' => date('Y-m-d H:i:s'),
                'status' => 'active'
            ], ['id' => $conversationId]);

            // Si es mensaje de cliente, notificar al agente
            if ($senderType === 'customer' && $conversation['assigned_agent_id']) {
                $this->notifyAgent($conversation['assigned_agent_id'], $conversationId, $messageId);
            }

            // Si el agente está respondiendo, enviar también por WhatsApp si corresponde
            if ($senderType === 'agent' && $conversation['channel'] === 'whatsapp') {
                try {
                    $this->chatModel->sendWhatsAppMessage($conversationId, $conversation['customer_phone'], [
                        'type' => 'text',
                        'text' => $message
                    ], $senderId);
                } catch (Exception $e) {
                    error_log("Error sending WhatsApp message: " . $e->getMessage());
                }
            }

            $this->json([
                'success' => true,
                'message_id' => $messageId,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

        } catch (Exception $e) {
            error_log("Error sending message: " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error interno del servidor']);
        }
    }

    /**
     * Obtener mensajes de conversación (AJAX)
     */
    public function getMessages()
    {
        if (!Helpers::isAjax()) {
            $this->json(['success' => false, 'error' => 'Solo AJAX']);
            return;
        }

        try {
            $conversationId = (int)$this->getInput('conversation_id');
            $lastMessageId = (int)$this->getInput('last_message_id', 0);
            
            if (!$conversationId) {
                $this->json(['success' => false, 'error' => 'ID de conversación requerido']);
                return;
            }

            // Verificar acceso
            $conversation = $this->chatModel->find($conversationId);
            $hasAccess = false;
            
            if (isset($_SESSION['agent_id'])) {
                // Agente: verificar que esté asignado o tenga permisos
                $hasAccess = ($conversation['assigned_agent_id'] == $_SESSION['agent_id']) || 
                           $this->hasPermission('chat_access_all');
            } else {
                // Cliente: verificar que sea su conversación
                $hasAccess = ($conversation['customer_phone'] === ($_SESSION['customer_phone'] ?? ''));
            }

            if (!$hasAccess) {
                $this->json(['success' => false, 'error' => 'Acceso denegado']);
                return;
            }

            // Obtener mensajes nuevos
            $messages = $this->chatModel->db->fetchAll(
                "SELECT m.*, a.agent_name, a.avatar_url,
                        CASE 
                            WHEN m.sender_type = 'customer' THEN ?
                            ELSE a.agent_name 
                        END as sender_display_name
                 FROM chat_messages m
                 LEFT JOIN chat_agents a ON m.sender_id = a.id
                 WHERE m.conversation_id = ? AND m.id > ?
                 ORDER BY m.sent_at ASC",
                [$conversation['customer_name'] ?? 'Cliente', $conversationId, $lastMessageId]
            );

            // Marcar mensajes como leídos si es agente
            if (isset($_SESSION['agent_id'])) {
                $this->chatModel->markMessagesAsRead($conversationId, $_SESSION['agent_id']);
            }

            $this->json([
                'success' => true,
                'messages' => $messages,
                'conversation_status' => $conversation['status']
            ]);

        } catch (Exception $e) {
            error_log("Error getting messages: " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error interno del servidor']);
        }
    }

    /**
     * Webhook de WhatsApp Business API
     */
    public function webhook()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if ($method === 'GET') {
            // Verificación del webhook
            $this->verifyWebhook();
        } elseif ($method === 'POST') {
            // Procesar webhook
            $this->processWebhook();
        } else {
            http_response_code(405);
            echo "Method not allowed";
        }
    }

    /**
     * Verificar webhook de WhatsApp
     */
    private function verifyWebhook()
    {
        $config = $this->chatModel->db->fetch("SELECT * FROM whatsapp_config WHERE is_active = 1 LIMIT 1");
        
        if (!$config) {
            http_response_code(500);
            echo "Configuration not found";
            return;
        }

        $hubMode = $_GET['hub_mode'] ?? '';
        $hubChallenge = $_GET['hub_challenge'] ?? '';
        $hubVerifyToken = $_GET['hub_verify_token'] ?? '';

        if ($hubMode === 'subscribe' && $hubVerifyToken === $config['webhook_verify_token']) {
            echo $hubChallenge;
        } else {
            http_response_code(403);
            echo "Verification failed";
        }
    }

    /**
     * Procesar webhook de WhatsApp
     */
    private function processWebhook()
    {
        try {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (!$data) {
                http_response_code(400);
                echo "Invalid JSON";
                return;
            }

            // Log del webhook para debugging
            error_log("WhatsApp Webhook received: " . $input);

            // Procesar el webhook
            $result = $this->chatModel->processWhatsAppWebhook($data);
            
            if ($result['success']) {
                http_response_code(200);
                echo "OK";
            } else {
                http_response_code(400);
                echo $result['error'];
            }

        } catch (Exception $e) {
            error_log("Webhook processing error: " . $e->getMessage());
            http_response_code(500);
            echo "Internal server error";
        }
    }

    /**
     * Panel de administración del chat
     */
    public function admin()
    {
        if (!$this->requireAuth()) {
            $this->redirect('login');
            return;
        }

        // Verificar que sea admin
        if (!isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'admin') {
            $this->redirect('admin', 'No tienes permisos para acceder al chat', 'error');
            return;
        }

        try {
            // Estadísticas generales
            $stats = $this->getChatStatistics();

            // Conversaciones pendientes
            $pendingConversations = $this->chatModel->db->fetchAll(
                "SELECT * FROM active_conversations
                 WHERE status IN ('pending', 'waiting_agent')
                 ORDER BY priority DESC, started_at ASC
                 LIMIT 20"
            );

            // Agentes activos
            $activeAgents = $this->chatModel->db->fetchAll(
                "SELECT *,
                        (SELECT COUNT(*) FROM chat_conversations WHERE assigned_agent_id = ca.id AND status IN ('active', 'waiting_customer')) as active_chats
                 FROM chat_agents ca
                 WHERE is_active = 1
                 ORDER BY status = 'online' DESC, priority_level DESC"
            );
        } catch (Exception $e) {
            // Si las tablas no existen, usar datos vacíos
            $stats = [
                'total_conversations' => 0,
                'active_conversations' => 0,
                'pending_conversations' => 0,
                'avg_response_time' => 0,
                'total_messages_today' => 0
            ];
            $pendingConversations = [];
            $activeAgents = [];
        }

        $this->view('admin/chat/dashboard', [
            'title' => 'Panel de Chat',
            'stats' => $stats,
            'pending_conversations' => $pendingConversations,
            'active_agents' => $activeAgents,
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }

    /**
     * Interfaz de chat para agentes
     */
    public function agentInterface()
    {
        if (!$this->requireAuth()) {
            $this->redirect('login');
            return;
        }

        // Verificar que sea admin
        if (!isset($_SESSION['user_tipo']) || $_SESSION['user_tipo'] !== 'admin') {
            $this->redirect('admin', 'No tienes permisos para acceder al chat', 'error');
            return;
        }

        $agentId = $_SESSION['user_id'] ?? null;
        
        // Obtener conversaciones asignadas al agente
        $conversations = $this->chatModel->getAgentConversations($agentId);
        
        // Obtener configuración del agente
        $agent = $this->chatModel->db->fetch(
            "SELECT * FROM chat_agents WHERE user_id = ? OR agent_email = ?",
            [$agentId, $_SESSION['user_email'] ?? '']
        );

        $this->view('admin/chat/agent_interface', [
            'title' => 'Chat - Interfaz de Agente',
            'conversations' => $conversations,
            'agent' => $agent,
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }

    /**
     * Asignar conversación manualmente
     */
    public function assignConversation()
    {
        if (!Helpers::isPost() || !$this->hasPermission('chat_assign')) {
            $this->json(['success' => false, 'error' => 'Acceso denegado']);
            return;
        }

        try {
            $this->validateCsrf();

            $conversationId = (int)$this->getInput('conversation_id');
            $agentId = (int)$this->getInput('agent_id');
            
            if (!$conversationId || !$agentId) {
                $this->json(['success' => false, 'error' => 'Datos incompletos']);
                return;
            }

            // Verificar que el agente está disponible
            $agent = $this->chatModel->db->fetch(
                "SELECT * FROM chat_agents WHERE id = ? AND is_active = 1 AND status = 'online'",
                [$agentId]
            );

            if (!$agent || $agent['current_chat_count'] >= $agent['max_concurrent_chats']) {
                $this->json(['success' => false, 'error' => 'Agente no disponible']);
                return;
            }

            // Asignar conversación
            $this->chatModel->db->update('chat_conversations', [
                'assigned_agent_id' => $agentId,
                'assigned_at' => date('Y-m-d H:i:s'),
                'assignment_method' => 'manual',
                'status' => 'active'
            ], ['id' => $conversationId]);

            // Actualizar contador del agente
            $this->chatModel->db->update('chat_agents', [
                'current_chat_count' => $agent['current_chat_count'] + 1
            ], ['id' => $agentId]);

            $this->json(['success' => true]);

        } catch (Exception $e) {
            error_log("Error assigning conversation: " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error interno del servidor']);
        }
    }

    /**
     * Transferir conversación
     */
    public function transferConversation()
    {
        if (!Helpers::isPost() || !$this->hasPermission('chat_transfer')) {
            $this->json(['success' => false, 'error' => 'Acceso denegado']);
            return;
        }

        try {
            $this->validateCsrf();

            $conversationId = (int)$this->getInput('conversation_id');
            $toAgentId = (int)$this->getInput('to_agent_id');
            $reason = $this->getInput('reason');
            $notes = $this->getInput('notes');
            $fromAgentId = $_SESSION['agent_id'] ?? null;
            
            if (!$conversationId || !$toAgentId || !$reason) {
                $this->json(['success' => false, 'error' => 'Datos incompletos']);
                return;
            }

            $result = $this->chatModel->transferConversation($conversationId, $fromAgentId, $toAgentId, $reason, $notes);
            
            $this->json($result);

        } catch (Exception $e) {
            error_log("Error transferring conversation: " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error interno del servidor']);
        }
    }

    /**
     * Cerrar conversación
     */
    public function closeConversation()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        try {
            $this->validateCsrf();

            $conversationId = (int)$this->getInput('conversation_id');
            $rating = (int)$this->getInput('rating');
            $feedback = $this->getInput('feedback');
            $agentId = $_SESSION['agent_id'] ?? null;
            
            if (!$conversationId) {
                $this->json(['success' => false, 'error' => 'ID de conversación requerido']);
                return;
            }

            $result = $this->chatModel->closeConversation($conversationId, $agentId, $feedback, $rating);
            
            $this->json($result);

        } catch (Exception $e) {
            error_log("Error closing conversation: " . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Error interno del servidor']);
        }
    }

    /**
     * Configuración de WhatsApp Business
     */
    public function whatsappConfig()
    {
        if (!$this->requireAuth() || !$this->hasPermission('chat_config')) {
            $this->redirect('login');
            return;
        }

        $config = $this->chatModel->db->fetch("SELECT * FROM whatsapp_config WHERE is_active = 1 LIMIT 1");

        if (Helpers::isPost()) {
            $this->validateCsrf();

            $configData = [
                'business_account_id' => $this->getInput('business_account_id'),
                'phone_number_id' => $this->getInput('phone_number_id'),
                'access_token' => $this->getInput('access_token'),
                'webhook_verify_token' => $this->getInput('webhook_verify_token'),
                'app_secret' => $this->getInput('app_secret'),
                'business_name' => $this->getInput('business_name'),
                'welcome_message' => $this->getInput('welcome_message'),
                'business_hours_message' => $this->getInput('business_hours_message'),
                'auto_reply_enabled' => $this->getInput('auto_reply_enabled') === '1',
                'business_hours_enabled' => $this->getInput('business_hours_enabled') === '1'
            ];

            if ($config) {
                $this->chatModel->db->update('whatsapp_config', $configData, ['id' => $config['id']]);
            } else {
                $this->chatModel->db->insert('whatsapp_config', $configData);
            }

            Helpers::setFlashMessage('success', 'Configuración de WhatsApp actualizada');
            $this->redirect('admin/chat/whatsapp-config');
            return;
        }

        $this->view('admin/chat/whatsapp_config', [
            'title' => 'Configuración WhatsApp Business',
            'config' => $config,
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }

    // Métodos auxiliares privados...
    
    private function processWebAutoResponses($conversation, $message)
    {
        // Implementar lógica similar al modelo pero para chat web
        $messageContent = strtolower($message);
        
        // Enviar mensaje de bienvenida automático
        if (!isset($_SESSION['welcome_sent'])) {
            $welcomeMessage = "¡Hola! Gracias por contactarnos. Un agente estará contigo en breve. 🏛️";
            
            $this->chatModel->saveMessage([
                'conversation_id' => $conversation['id'],
                'message_type' => 'text',
                'sender_type' => 'system',
                'message_content' => $welcomeMessage
            ]);
            
            $_SESSION['welcome_sent'] = true;
        }
    }

    private function detectSpecializationFromContext($productId, $message)
    {
        if ($productId) {
            return 'tours';
        }
        
        $messageContent = strtolower($message);
        
        if (strpos($messageContent, 'reserva') !== false || strpos($messageContent, 'booking') !== false) {
            return 'bookings';
        }
        
        if (strpos($messageContent, 'transporte') !== false || strpos($messageContent, 'shuttle') !== false) {
            return 'transport';
        }
        
        return null;
    }

    private function notifyAgent($agentId, $conversationId, $messageId)
    {
        // Implementar notificación en tiempo real (WebSocket, SSE, etc.)
        // Por ahora logging para desarrollo
        error_log("Notifying agent {$agentId} about new message {$messageId} in conversation {$conversationId}");
    }

    private function getChatStatistics()
    {
        return $this->chatModel->db->fetch(
            "SELECT 
                COUNT(*) as total_conversations,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_conversations,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_conversations,
                COUNT(CASE WHEN status = 'waiting_agent' THEN 1 END) as waiting_agent,
                AVG(CASE WHEN first_response_time IS NOT NULL THEN first_response_time END) as avg_first_response_time,
                (SELECT COUNT(*) FROM chat_agents WHERE status = 'online' AND is_active = 1) as online_agents,
                (SELECT COUNT(*) FROM chat_messages WHERE DATE(sent_at) = CURDATE()) as messages_today
             FROM chat_conversations
             WHERE DATE(started_at) = CURDATE()"
        );
    }
}
?>