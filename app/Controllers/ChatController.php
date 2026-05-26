<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Models\Chat;
use App\Models\Message;
use Exception;

class ChatController extends BaseController
{
    private $chatModel;
    private $messageModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->chatModel = new Chat();
        $this->messageModel = new Message();
    }
    
    // Panel de chat para operadores (requiere login)
    public function admin()
    {
        if (!$this->auth->isEmployee()) {
            $this->redirect('home');
        }
        
        $activeChats = $this->chatModel->getActiveConversations();
        $waitingChats = $this->chatModel->getWaitingConversations();
        
        $this->view('admin/chat/index', [
            'activeChats' => $activeChats,
            'waitingChats' => $waitingChats
        ]);
    }
    
    // API para obtener conversaciones por estado
    public function conversations()
    {
        if (!$this->auth->isEmployee()) {
            $this->jsonResponse(['success' => false, 'message' => 'Acceso denegado'], 403);
            return;
        }
        
        $status = $_GET['status'] ?? 'activa';
        $operatorId = $_GET['operator_id'] ?? null;
        
        try {
            $conversations = $this->chatModel->getConversationsList($status, $operatorId);
            $stats = $this->chatModel->getChatStats();
            
            $this->jsonResponse([
                'success' => true,
                'conversations' => $conversations,
                'stats' => $stats
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al obtener conversaciones: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // API para obtener operadores disponibles
    public function operators()
    {
        if (!$this->auth->isEmployee()) {
            $this->jsonResponse(['success' => false, 'message' => 'Acceso denegado'], 403);
            return;
        }
        
        try {
            $operators = $this->chatModel->getAvailableOperators();
            
            $this->jsonResponse([
                'success' => true,
                'operators' => $operators
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al obtener operadores: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // API para obtener detalles de una conversación
    public function conversation()
    {
        if (!$this->auth->isEmployee()) {
            $this->jsonResponse(['success' => false, 'message' => 'Acceso denegado'], 403);
            return;
        }
        
        $conversationId = $_GET['id'] ?? '';
        
        if (empty($conversationId)) {
            $this->jsonResponse(['success' => false, 'message' => 'ID de conversación requerido'], 400);
            return;
        }
        
        try {
            $conversation = $this->chatModel->getConversation($conversationId);
            
            if (!$conversation) {
                $this->jsonResponse(['success' => false, 'message' => 'Conversación no encontrada'], 404);
                return;
            }
            
            $this->jsonResponse([
                'success' => true,
                'conversation' => $conversation
            ]);
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al obtener conversación: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // API para obtener mensajes de una conversación
    public function messages()
    {
        $conversationId = $_GET['conversation_id'] ?? '';

        if (empty($conversationId)) {
            $this->json(['success' => false, 'message' => 'ID de conversación requerido'], 400);
            return;
        }

        try {
            // Obtener mensajes directamente de la base de datos
            $messages = $this->db->fetchAll(
                "SELECT * FROM messages WHERE conversation_id = :conversation_id ORDER BY sent_at ASC",
                ['conversation_id' => $conversationId]
            );

            $this->json([
                'success' => true,
                'messages' => $messages
            ]);
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Error al obtener mensajes: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // API para enviar mensaje desde el chat flotante
    public function send()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $message = $data['message'] ?? '';
            $conversationId = $data['conversation_id'] ?? null;

            if (empty($message)) {
                $this->json(['success' => false, 'message' => 'Mensaje vacío'], 400);
                return;
            }

            // Si no hay conversación, crear una nueva
            if (!$conversationId) {
                $sessionId = session_id();
                $auth = $this->auth;
                $userId = $auth->isLoggedIn() ? $auth->getUser()['id'] : null;

                // Verificar si ya existe una conversación activa
                $existing = $this->db->fetch(
                    "SELECT id FROM conversations WHERE (session_id = :session_id OR client_id = :user_id)
                     AND status = 'activa' ORDER BY created_at DESC LIMIT 1",
                    ['session_id' => $sessionId, 'user_id' => $userId]
                );

                if ($existing) {
                    $conversationId = $existing['id'];
                } else {
                    // Crear nueva conversación
                    $conversationId = $this->db->insert('conversations', [
                        'session_id' => $sessionId,
                        'client_id' => $userId,
                        'client_name' => $auth->isLoggedIn() ? $auth->getUser()['nombre'] : 'Invitado',
                        'client_email' => $auth->isLoggedIn() ? $auth->getUser()['email'] : null,
                        'status' => 'activa',
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            // Guardar mensaje
            $messageId = $this->db->insert('messages', [
                'conversation_id' => $conversationId,
                'sender_type' => 'cliente',
                'sender_id' => $this->auth->isLoggedIn() ? $this->auth->getUser()['id'] : null,
                'message' => trim($message),
                'sent_at' => date('Y-m-d H:i:s')
            ]);

            $this->json([
                'success' => true,
                'message_id' => $messageId,
                'conversation_id' => $conversationId
            ]);

        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Error al enviar mensaje: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // API para obtener nuevos mensajes
    public function new_messages()
    {
        if (!$this->auth->isEmployee()) {
            $this->jsonResponse(['success' => false, 'message' => 'Acceso denegado'], 403);
            return;
        }
        
        $conversationId = $_GET['conversation_id'] ?? '';
        $lastMessageId = (int)($_GET['last_message_id'] ?? 0);
        
        if (empty($conversationId)) {
            $this->jsonResponse(['success' => false, 'message' => 'ID de conversación requerido'], 400);
            return;
        }
        
        try {
            $newMessages = $this->chatModel->getNewMessages($conversationId, $lastMessageId);
            
            $this->jsonResponse([
                'success' => true,
                'messages' => $newMessages,
                'count' => count($newMessages)
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al obtener mensajes: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // API para marcar mensajes como leídos
    public function mark_read()
    {
        if (!$this->auth->isEmployee()) {
            $this->jsonResponse(['success' => false, 'message' => 'Acceso denegado'], 403);
            return;
        }
        
        $conversationId = $_GET['conversation_id'] ?? '';
        
        if (empty($conversationId)) {
            $this->jsonResponse(['success' => false, 'message' => 'ID de conversación requerido'], 400);
            return;
        }
        
        try {
            $result = $this->chatModel->markMessagesAsRead($conversationId, 'operador');
            
            $this->jsonResponse([
                'success' => true,
                'marked' => $result
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al marcar mensajes: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // API para cerrar conversación
    public function close()
    {
        if (!$this->auth->isEmployee()) {
            $this->jsonResponse(['success' => false, 'message' => 'Acceso denegado'], 403);
            return;
        }
        
        $conversationId = $_GET['conversation_id'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        if (empty($conversationId)) {
            $this->jsonResponse(['success' => false, 'message' => 'ID de conversación requerido'], 400);
            return;
        }
        
        try {
            $result = $this->chatModel->closeConversation($conversationId, $reason);
            
            $this->jsonResponse([
                'success' => $result,
                'message' => $result ? 'Conversación cerrada' : 'Error al cerrar conversación'
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al cerrar conversación: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Chat público para clientes (sin login requerido)
    public function index()
    {
        // Obtener o crear session ID para usuarios no registrados
        $sessionId = $_SESSION['chat_session_id'] ?? null;
        if (!$sessionId) {
            $sessionId = uniqid('chat_', true);
            $_SESSION['chat_session_id'] = $sessionId;
        }
        
        // Obtener conversación existente o crear nueva
        $clientId = $this->auth->isLoggedIn() ? $this->auth->getCurrentUser()['id'] : null;
        $conversation = $this->chatModel->getOrCreateConversation($clientId, $sessionId);
        
        // Obtener mensajes de la conversación
        $messages = $this->chatModel->getConversationMessages($conversation['id']);
        
        $this->view('chat/index', [
            'title' => 'Chat de Soporte',
            'conversation' => $conversation,
            'messages' => $messages,
            'user' => $this->auth->getCurrentUser(),
            'sessionId' => $sessionId
        ]);
    }
    
    // API para enviar mensaje (AJAX)
    public function send_message()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        $conversationId = $_POST['conversation_id'] ?? '';
        $message = trim($_POST['message'] ?? '');
        $sessionId = $_POST['session_id'] ?? '';
        
        if (empty($conversationId) || empty($message)) {
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Conversación y mensaje son requeridos'
            ], 400);
            return;
        }
        
        // Verificar que la conversación existe y pertenece al usuario
        $conversation = $this->chatModel->getConversation($conversationId);
        if (!$conversation) {
            $this->jsonResponse(['success' => false, 'message' => 'Conversación no encontrada'], 404);
            return;
        }
        
        // Determinar el emisor
        $userId = null;
        $senderType = 'cliente';
        
        if ($this->auth->isLoggedIn()) {
            $user = $this->auth->getCurrentUser();
            $userId = $user['id'];
            
            // Si es empleado, es operador
            if ($this->auth->isEmployee()) {
                $senderType = 'operador';
            }
        } else {
            // Usuario anónimo - verificar session ID
            if ($conversation['session_id'] !== $sessionId) {
                $this->jsonResponse(['success' => false, 'message' => 'Sesión inválida'], 403);
                return;
            }
        }
        
        try {
            // Guardar mensaje
            $messageId = $this->chatModel->sendMessage([
                'conversacion_id' => $conversationId,
                'emisor_id' => $userId,
                'tipo_emisor' => $senderType,
                'mensaje' => $message,
                'tipo_mensaje' => 'texto'
            ]);
            
            // Si es cliente y no hay operador asignado, asignar automáticamente
            if ($senderType === 'cliente' && !$conversation['operador_id']) {
                $this->chatModel->assignOperator($conversationId);
            }
            
            // Actualizar estado de conversación
            $this->chatModel->updateConversationStatus($conversationId, 'activa');
            
            $this->jsonResponse([
                'success' => true,
                'message_id' => $messageId,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al enviar mensaje: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // API para obtener mensajes nuevos (polling)
    public function get_messages()
    {
        $conversationId = $_GET['conversation_id'] ?? '';
        $lastMessageId = (int)($_GET['last_message_id'] ?? 0);
        
        if (empty($conversationId)) {
            $this->jsonResponse(['success' => false, 'message' => 'ID de conversación requerido'], 400);
            return;
        }
        
        // Verificar acceso a la conversación
        if (!$this->canAccessConversation($conversationId)) {
            $this->jsonResponse(['success' => false, 'message' => 'Acceso denegado'], 403);
            return;
        }
        
        try {
            $newMessages = $this->chatModel->getNewMessages($conversationId, $lastMessageId);
            
            // Marcar mensajes como leídos si es operador
            if ($this->auth->isEmployee() && !empty($newMessages)) {
                $this->chatModel->markMessagesAsRead($conversationId, 'operador');
            }
            
            $this->jsonResponse([
                'success' => true,
                'messages' => $newMessages,
                'count' => count($newMessages)
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al obtener mensajes: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Métodos heredados para compatibilidad
    public function sendMessage()
    {
        return $this->send_message();
    }
    
    public function getMessages()
    {
        $reservaId = $this->getInput('reserva_id');
        
        if ($reservaId) {
            $messages = $this->messageModel->getConversation($reservaId);
        } else {
            $messages = [];
        }
        
        $this->json([
            'success' => true,
            'messages' => $messages
        ]);
    }
    
    private function canAccessConversation($conversationId)
    {
        $conversation = $this->chatModel->getConversation($conversationId);
        
        if (!$conversation) {
            return false;
        }
        
        // Si es empleado, puede acceder a cualquier conversación
        if ($this->auth->isEmployee()) {
            return true;
        }
        
        // Si es cliente registrado, debe ser su conversación
        if ($this->auth->isLoggedIn()) {
            return $conversation['cliente_id'] === $this->auth->getCurrentUser()['id'];
        }
        
        // Si es usuario anónimo, verificar session ID
        return $conversation['session_id'] === ($_SESSION['chat_session_id'] ?? '');
    }

    /**
     * Panel de administración de chats
     */
    public function adminConversations()
    {
        // Requerir autenticación de admin
        if (!$this->auth->isLoggedIn() || !$this->auth->isAdmin()) {
            $this->redirect('admin/login');
            return;
        }

        $this->view('admin/chat/conversations', [
            'title' => 'Chat - Conversaciones'
        ]);
    }

    /**
     * API: Obtener lista de conversaciones para admin
     */
    public function apiConversations()
    {
        if (!$this->auth->isLoggedIn() || !$this->auth->isAdmin()) {
            $this->json(['success' => false, 'message' => 'No autorizado'], 403);
            return;
        }

        try {
            $conversations = $this->db->fetchAll(
                "SELECT c.*,
                        (SELECT message FROM messages WHERE conversation_id = c.id ORDER BY sent_at DESC LIMIT 1) as last_message,
                        (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_type = 'cliente' AND read_at IS NULL) as unread_count
                 FROM conversations c
                 WHERE c.status = 'activa'
                 ORDER BY c.updated_at DESC"
            );

            $this->json([
                'success' => true,
                'conversations' => $conversations
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al cargar conversaciones'], 500);
        }
    }

    /**
     * API: Enviar mensaje desde admin
     */
    public function sendMessageAdmin()
    {
        if (!$this->auth->isLoggedIn() || !$this->auth->isAdmin()) {
            $this->json(['success' => false, 'message' => 'No autorizado'], 403);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $conversationId = $data['conversation_id'] ?? null;
            $message = $data['message'] ?? '';

            if (!$conversationId || empty($message)) {
                $this->json(['success' => false, 'message' => 'Datos incompletos'], 400);
                return;
            }

            // Guardar mensaje
            $messageId = $this->db->insert('messages', [
                'conversation_id' => $conversationId,
                'sender_type' => 'operator',
                'sender_id' => $this->auth->getUser()['id'],
                'message' => trim($message),
                'sent_at' => date('Y-m-d H:i:s')
            ]);

            // Actualizar conversación
            $this->db->update('conversations', [
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = :id', ['id' => $conversationId]);

            $this->json([
                'success' => true,
                'message_id' => $messageId
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al enviar mensaje'], 500);
        }
    }

    /**
     * API: Cerrar conversación
     */
    public function closeConversation()
    {
        if (!$this->auth->isLoggedIn() || !$this->auth->isAdmin()) {
            $this->json(['success' => false, 'message' => 'No autorizado'], 403);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        try {
            $data = json_decode(file_get_contents('php://input'), true);
            $conversationId = $data['conversation_id'] ?? null;

            if (!$conversationId) {
                $this->json(['success' => false, 'message' => 'ID requerido'], 400);
                return;
            }

            // Cerrar conversación
            $this->db->update('conversations', [
                'status' => 'cerrada',
                'closed_at' => date('Y-m-d H:i:s'),
                'closed_by' => $this->auth->getUser()['id']
            ], 'id = :id', ['id' => $conversationId]);

            $this->json([
                'success' => true,
                'message' => 'Conversación cerrada'
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al cerrar conversación'], 500);
        }
    }
}