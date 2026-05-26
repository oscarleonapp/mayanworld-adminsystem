<?php

namespace App\Models;

use App\Core\Model;

class WhatsAppChat extends Model
{
    protected $table = 'chat_conversations';
    protected $fillable = [
        'whatsapp_conversation_id', 'customer_phone', 'customer_name', 'customer_email',
        'customer_language', 'assigned_agent_id', 'status', 'priority', 'category',
        'channel', 'source_page', 'related_product_id', 'related_booking_id',
        'inquiry_type', 'estimated_budget', 'travel_dates', 'group_size'
    ];

    private $whatsappConfig = null;
    private $apiBaseUrl = 'https://graph.facebook.com/v18.0';

    /**
     * Obtener configuración de WhatsApp Business
     */
    private function getWhatsAppConfig()
    {
        if ($this->whatsappConfig === null) {
            $this->whatsappConfig = $this->db->fetch("SELECT * FROM whatsapp_config WHERE is_active = 1 LIMIT 1");
        }
        return $this->whatsappConfig;
    }

    /**
     * Crear o obtener conversación existente
     */
    public function getOrCreateConversation($customerPhone, $customerData = [])
    {
        try {
            // Buscar conversación activa existente
            $conversation = $this->db->fetch(
                "SELECT * FROM chat_conversations 
                 WHERE customer_phone = ? 
                 AND status IN ('pending', 'active', 'waiting_agent', 'waiting_customer')
                 ORDER BY last_activity_at DESC LIMIT 1",
                [$customerPhone]
            );

            if ($conversation) {
                // Actualizar última actividad
                $this->db->update('chat_conversations', 
                    ['last_activity_at' => date('Y-m-d H:i:s')],
                    ['id' => $conversation['id']]
                );
                return $conversation;
            }

            // Crear nueva conversación
            $conversationData = array_merge([
                'customer_phone' => $customerPhone,
                'status' => 'pending',
                'channel' => 'whatsapp',
                'priority' => $this->calculatePriority($customerPhone, $customerData),
                'category' => $this->detectInquiryCategory($customerData),
                'customer_language' => $this->detectLanguage($customerData),
                'is_returning_customer' => $this->isReturningCustomer($customerPhone)
            ], $customerData);

            $conversationId = $this->create($conversationData);
            
            // Obtener la conversación creada
            $newConversation = $this->find($conversationId);
            
            // Actualizar estadísticas
            $this->updateDailyStats('new_conversations', 1);
            
            return $newConversation;

        } catch (Exception $e) {
            error_log("Error creating conversation: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Asignar agente automáticamente
     */
    public function autoAssignAgent($conversationId, $requiresSpecialization = null)
    {
        try {
            // Buscar agente disponible
            $sql = "SELECT * FROM chat_agents 
                    WHERE status = 'online' 
                    AND is_active = 1 
                    AND current_chat_count < max_concurrent_chats";
            
            $params = [];
            
            // Filtrar por especialización si es necesaria
            if ($requiresSpecialization) {
                $sql .= " AND (specialization = ? OR specialization = 'general')";
                $params[] = $requiresSpecialization;
            }
            
            $sql .= " ORDER BY 
                        priority_level DESC,
                        current_chat_count ASC,
                        RAND()
                      LIMIT 1";
            
            $agent = $this->db->fetch($sql, $params);
            
            if ($agent) {
                // Asignar agente
                $this->db->update('chat_conversations', [
                    'assigned_agent_id' => $agent['id'],
                    'assigned_at' => date('Y-m-d H:i:s'),
                    'assignment_method' => 'auto',
                    'status' => 'active'
                ], ['id' => $conversationId]);
                
                // Actualizar contador del agente
                $this->db->update('chat_agents', [
                    'current_chat_count' => $agent['current_chat_count'] + 1,
                    'total_conversations' => $agent['total_conversations'] + 1
                ], ['id' => $agent['id']]);
                
                // Enviar notificación al agente
                $this->notifyAgentNewConversation($agent['id'], $conversationId);
                
                return $agent;
            }
            
            return null;

        } catch (Exception $e) {
            error_log("Error auto-assigning agent: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Enviar mensaje vía WhatsApp Business API
     */
    public function sendWhatsAppMessage($conversationId, $recipientPhone, $messageData, $senderId = null)
    {
        $config = $this->getWhatsAppConfig();
        if (!$config) {
            throw new Exception("WhatsApp configuration not found");
        }

        try {
            $url = "{$this->apiBaseUrl}/{$config['phone_number_id']}/messages";
            
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $recipientPhone,
                'type' => $messageData['type'] ?? 'text'
            ];

            // Configurar contenido según el tipo de mensaje
            switch ($messageData['type']) {
                case 'text':
                    $payload['text'] = ['body' => $messageData['text']];
                    break;
                
                case 'template':
                    $payload['template'] = $messageData['template'];
                    break;
                
                case 'interactive':
                    $payload['interactive'] = $messageData['interactive'];
                    break;
                
                case 'image':
                case 'document':
                case 'audio':
                case 'video':
                    $payload[$messageData['type']] = $messageData['media'];
                    break;
            }

            $response = $this->makeWhatsAppRequest($url, $payload, $config['access_token']);
            
            if ($response['success']) {
                // Guardar mensaje en base de datos
                $messageRecord = [
                    'conversation_id' => $conversationId,
                    'whatsapp_message_id' => $response['data']['messages'][0]['id'] ?? null,
                    'message_type' => $messageData['type'],
                    'sender_type' => $senderId ? 'agent' : 'system',
                    'sender_id' => $senderId,
                    'message_content' => $messageData['text'] ?? json_encode($messageData),
                    'whatsapp_status' => 'sent',
                    'interactive_data' => isset($messageData['interactive']) ? json_encode($messageData['interactive']) : null,
                    'template_data' => isset($messageData['template']) ? json_encode($messageData['template']) : null
                ];
                
                $this->saveMessage($messageRecord);
                
                return $response;
            } else {
                throw new Exception("WhatsApp API error: " . $response['error']);
            }

        } catch (Exception $e) {
            error_log("Error sending WhatsApp message: " . $e->getMessage());
            
            // Guardar mensaje fallido
            $this->saveMessage([
                'conversation_id' => $conversationId,
                'message_type' => $messageData['type'],
                'sender_type' => $senderId ? 'agent' : 'system',
                'sender_id' => $senderId,
                'message_content' => $messageData['text'] ?? json_encode($messageData),
                'whatsapp_status' => 'failed',
                'whatsapp_error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Procesar webhook de WhatsApp
     */
    public function processWhatsAppWebhook($webhookData)
    {
        try {
            if (!isset($webhookData['entry'][0]['changes'][0]['value'])) {
                return ['success' => false, 'error' => 'Invalid webhook format'];
            }

            $value = $webhookData['entry'][0]['changes'][0]['value'];
            
            // Procesar mensajes recibidos
            if (isset($value['messages'])) {
                foreach ($value['messages'] as $message) {
                    $this->processIncomingMessage($message, $value['metadata'] ?? []);
                }
            }
            
            // Procesar estados de mensaje
            if (isset($value['statuses'])) {
                foreach ($value['statuses'] as $status) {
                    $this->processMessageStatus($status);
                }
            }
            
            return ['success' => true];

        } catch (Exception $e) {
            error_log("Error processing WhatsApp webhook: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Procesar mensaje entrante de WhatsApp
     */
    private function processIncomingMessage($messageData, $metadata)
    {
        try {
            $customerPhone = $messageData['from'];
            $messageType = $messageData['type'];
            $messageContent = $this->extractMessageContent($messageData);
            
            // Obtener o crear conversación
            $conversation = $this->getOrCreateConversation($customerPhone, [
                'customer_name' => $messageData['profile']['name'] ?? null,
                'whatsapp_conversation_id' => $metadata['phone_number_id'] ?? null
            ]);
            
            if (!$conversation) {
                throw new Exception("Could not create conversation");
            }

            // Guardar mensaje
            $messageRecord = [
                'conversation_id' => $conversation['id'],
                'whatsapp_message_id' => $messageData['id'],
                'message_type' => $messageType,
                'sender_type' => 'customer',
                'message_content' => $messageContent,
                'whatsapp_timestamp' => $messageData['timestamp'],
                'whatsapp_status' => 'delivered'
            ];

            // Procesar contenido específico del tipo de mensaje
            switch ($messageType) {
                case 'text':
                    $messageRecord['message_content'] = $messageData['text']['body'];
                    break;
                
                case 'image':
                case 'document':
                case 'audio':
                case 'video':
                    $messageRecord['media_url'] = $this->downloadWhatsAppMedia($messageData[$messageType]['id']);
                    $messageRecord['media_mime_type'] = $messageData[$messageType]['mime_type'] ?? null;
                    $messageRecord['media_filename'] = $messageData[$messageType]['filename'] ?? null;
                    $messageRecord['media_caption'] = $messageData[$messageType]['caption'] ?? null;
                    break;
                
                case 'location':
                    $messageRecord['location_data'] = json_encode([
                        'latitude' => $messageData['location']['latitude'],
                        'longitude' => $messageData['location']['longitude'],
                        'name' => $messageData['location']['name'] ?? null,
                        'address' => $messageData['location']['address'] ?? null
                    ]);
                    break;
            }

            $messageId = $this->saveMessage($messageRecord);

            // Procesamiento automático
            $this->processAutomaticResponses($conversation, $messageRecord, $messageData);
            
            // Asignar agente si es necesario
            if ($conversation['assigned_agent_id'] === null) {
                $specialization = $this->detectRequiredSpecialization($messageRecord['message_content']);
                $this->autoAssignAgent($conversation['id'], $specialization);
            }
            
            // Notificar agente asignado
            if ($conversation['assigned_agent_id']) {
                $this->notifyAgentNewMessage($conversation['assigned_agent_id'], $conversation['id'], $messageId);
            }

            return ['success' => true, 'message_id' => $messageId];

        } catch (Exception $e) {
            error_log("Error processing incoming message: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Procesar respuestas automáticas
     */
    private function processAutomaticResponses($conversation, $messageRecord, $originalMessage)
    {
        try {
            $messageContent = strtolower($messageRecord['message_content']);
            
            // Obtener respuestas automáticas activas
            $autoResponses = $this->db->fetchAll(
                "SELECT * FROM chat_auto_responses 
                 WHERE is_active = 1 
                 ORDER BY priority DESC"
            );

            foreach ($autoResponses as $response) {
                $shouldTrigger = false;
                
                switch ($response['trigger_type']) {
                    case 'keyword':
                        $keywords = json_decode($response['trigger_value'], true);
                        foreach ($keywords as $keyword) {
                            if (strpos($messageContent, strtolower($keyword)) !== false) {
                                $shouldTrigger = true;
                                break;
                            }
                        }
                        break;
                    
                    case 'no_agent':
                        $shouldTrigger = ($conversation['assigned_agent_id'] === null);
                        break;
                    
                    case 'business_hours':
                        $shouldTrigger = !$this->isBusinessHours();
                        break;
                }
                
                if ($shouldTrigger) {
                    $this->sendAutoResponse($conversation, $response);
                    break; // Solo enviar una respuesta automática
                }
            }

        } catch (Exception $e) {
            error_log("Error processing auto responses: " . $e->getMessage());
        }
    }

    /**
     * Enviar respuesta automática
     */
    private function sendAutoResponse($conversation, $autoResponse)
    {
        try {
            $messageData = [];
            
            switch ($autoResponse['response_type']) {
                case 'text':
                    $messageData = [
                        'type' => 'text',
                        'text' => $autoResponse['response_content']
                    ];
                    break;
                
                case 'template':
                    $template = $this->getTemplate($autoResponse['response_content']);
                    if ($template) {
                        $messageData = [
                            'type' => 'text',
                            'text' => $this->processTemplate($template, $conversation)
                        ];
                    }
                    break;
            }
            
            if (!empty($messageData)) {
                // Aplicar delay si está configurado
                if ($autoResponse['delay_seconds'] > 0) {
                    sleep($autoResponse['delay_seconds']);
                }
                
                $this->sendWhatsAppMessage(
                    $conversation['id'],
                    $conversation['customer_phone'],
                    $messageData
                );
                
                // Actualizar estadísticas
                $this->db->update('chat_auto_responses', [
                    'times_triggered' => $autoResponse['times_triggered'] + 1
                ], ['id' => $autoResponse['id']]);
            }

        } catch (Exception $e) {
            error_log("Error sending auto response: " . $e->getMessage());
        }
    }

    /**
     * Obtener template de mensaje
     */
    private function getTemplate($templateName)
    {
        return $this->db->fetch(
            "SELECT * FROM chat_templates WHERE template_name = ? AND is_active = 1",
            [$templateName]
        );
    }

    /**
     * Procesar template con variables
     */
    private function processTemplate($template, $conversation)
    {
        $content = $template['template_content'];
        $variables = json_decode($template['template_variables'] ?? '{}', true);
        
        // Reemplazar variables disponibles
        $replacements = [
            '{customer_name}' => $conversation['customer_name'] ?? 'estimado cliente',
            '{agent_name}' => 'nuestro equipo',
            '{business_name}' => 'Travel Mayan World'
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Guardar mensaje en base de datos
     */
    public function saveMessage($messageData)
    {
        try {
            return $this->db->insert('chat_messages', $messageData);
        } catch (Exception $e) {
            error_log("Error saving message: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener mensajes de conversación
     */
    public function getConversationMessages($conversationId, $limit = 50, $offset = 0)
    {
        return $this->db->fetchAll(
            "SELECT m.*, a.agent_name, a.avatar_url
             FROM chat_messages m
             LEFT JOIN chat_agents a ON m.sender_id = a.id
             WHERE m.conversation_id = ?
             ORDER BY m.sent_at ASC
             LIMIT ? OFFSET ?",
            [$conversationId, $limit, $offset]
        );
    }

    /**
     * Marcar mensajes como leídos
     */
    public function markMessagesAsRead($conversationId, $readById = null)
    {
        $this->db->update('chat_messages', [
            'read_at' => date('Y-m-d H:i:s')
        ], [
            'conversation_id' => $conversationId,
            'sender_type' => 'customer',
            'read_at' => null
        ]);
    }

    /**
     * Obtener conversaciones activas para agente
     */
    public function getAgentConversations($agentId, $status = null)
    {
        $sql = "SELECT c.*, 
                       COUNT(m.id) as unread_messages,
                       lm.message_content as last_message,
                       lm.sent_at as last_message_time
                FROM chat_conversations c
                LEFT JOIN chat_messages m ON c.id = m.conversation_id 
                    AND m.sender_type = 'customer' AND m.read_at IS NULL
                LEFT JOIN chat_messages lm ON lm.id = (
                    SELECT id FROM chat_messages 
                    WHERE conversation_id = c.id 
                    ORDER BY sent_at DESC LIMIT 1
                )
                WHERE c.assigned_agent_id = ?";
        
        $params = [$agentId];
        
        if ($status) {
            $sql .= " AND c.status = ?";
            $params[] = $status;
        } else {
            $sql .= " AND c.status IN ('active', 'waiting_customer')";
        }
        
        $sql .= " GROUP BY c.id ORDER BY c.last_activity_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Transferir conversación a otro agente
     */
    public function transferConversation($conversationId, $fromAgentId, $toAgentId, $reason, $notes = null)
    {
        try {
            $this->db->beginTransaction();
            
            // Crear registro de transferencia
            $transferId = $this->db->insert('chat_transfers', [
                'conversation_id' => $conversationId,
                'from_agent_id' => $fromAgentId,
                'to_agent_id' => $toAgentId,
                'transfer_reason' => $reason,
                'transfer_notes' => $notes,
                'status' => 'accepted'
            ]);
            
            // Actualizar conversación
            $this->db->update('chat_conversations', [
                'assigned_agent_id' => $toAgentId,
                'assignment_method' => 'transfer'
            ], ['id' => $conversationId]);
            
            // Actualizar contadores de agentes
            $this->db->execute("UPDATE chat_agents SET current_chat_count = current_chat_count - 1 WHERE id = ?", [$fromAgentId]);
            $this->db->execute("UPDATE chat_agents SET current_chat_count = current_chat_count + 1 WHERE id = ?", [$toAgentId]);
            
            $this->db->commit();
            
            // Notificar al nuevo agente
            $this->notifyAgentTransfer($toAgentId, $conversationId, $fromAgentId);
            
            return ['success' => true, 'transfer_id' => $transferId];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error transferring conversation: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Cerrar conversación
     */
    public function closeConversation($conversationId, $agentId, $customerFeedback = null, $rating = null)
    {
        try {
            $updateData = [
                'status' => 'closed',
                'closed_at' => date('Y-m-d H:i:s')
            ];
            
            if ($customerFeedback) {
                $updateData['customer_feedback'] = $customerFeedback;
            }
            
            if ($rating) {
                $updateData['customer_satisfaction_rating'] = $rating;
            }
            
            $this->db->update('chat_conversations', $updateData, ['id' => $conversationId]);
            
            // Liberar agente
            if ($agentId) {
                $this->db->execute("UPDATE chat_agents SET current_chat_count = current_chat_count - 1 WHERE id = ?", [$agentId]);
            }
            
            return ['success' => true];

        } catch (Exception $e) {
            error_log("Error closing conversation: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Métodos auxiliares privados...
    
    private function makeWhatsAppRequest($url, $data, $accessToken)
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return ['success' => false, 'error' => $error];
        }
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $decodedResponse];
        } else {
            return ['success' => false, 'error' => $decodedResponse['error']['message'] ?? 'Unknown API error'];
        }
    }

    private function calculatePriority($customerPhone, $customerData)
    {
        // Lógica para calcular prioridad basada en historial del cliente
        $existingBookings = $this->db->fetchAll(
            "SELECT COUNT(*) as booking_count FROM reservas WHERE telefono = ?",
            [$customerPhone]
        );
        
        if (!empty($existingBookings) && $existingBookings[0]['booking_count'] > 0) {
            return 'high'; // Cliente existente
        }
        
        return 'normal';
    }

    private function detectInquiryCategory($customerData)
    {
        // Lógica básica para detectar categoría
        return 'inquiry';
    }

    private function detectLanguage($customerData)
    {
        // Por defecto español, se puede mejorar con detección automática
        return 'es';
    }

    private function isReturningCustomer($customerPhone)
    {
        $existing = $this->db->fetch(
            "SELECT id FROM chat_conversations WHERE customer_phone = ? LIMIT 1",
            [$customerPhone]
        );
        
        return !empty($existing);
    }

    private function isBusinessHours()
    {
        $config = $this->getWhatsAppConfig();
        if (!$config || !$config['business_hours_enabled']) {
            return true;
        }
        
        $hours = json_decode($config['business_hours'], true);
        $currentDay = strtolower(date('l')); // monday, tuesday, etc.
        $currentTime = date('H:i');
        
        $dayConfig = $hours[substr($currentDay, 0, 6)] ?? null; // "lunes", "martes", etc.
        
        if (!$dayConfig || !$dayConfig['enabled']) {
            return false;
        }
        
        return $currentTime >= $dayConfig['open'] && $currentTime <= $dayConfig['close'];
    }

    private function extractMessageContent($messageData)
    {
        switch ($messageData['type']) {
            case 'text':
                return $messageData['text']['body'];
            case 'image':
                return '[Imagen enviada]';
            case 'document':
                return '[Documento enviado]';
            case 'audio':
                return '[Audio enviado]';
            case 'video':
                return '[Video enviado]';
            case 'location':
                return '[Ubicación enviada]';
            default:
                return '[Mensaje multimedia]';
        }
    }

    private function downloadWhatsAppMedia($mediaId)
    {
        // Implementar descarga de media de WhatsApp
        // Por ahora retornar el ID como placeholder
        return "media://{$mediaId}";
    }

    private function detectRequiredSpecialization($messageContent)
    {
        $content = strtolower($messageContent);
        
        if (strpos($content, 'reserva') !== false || strpos($content, 'booking') !== false) {
            return 'bookings';
        }
        
        if (strpos($content, 'transporte') !== false || strpos($content, 'shuttle') !== false) {
            return 'transport';
        }
        
        if (strpos($content, 'tour') !== false || strpos($content, 'tikal') !== false) {
            return 'tours';
        }
        
        return null;
    }

    private function updateDailyStats($metric, $increment = 1)
    {
        $this->db->execute(
            "INSERT INTO chat_statistics (date, {$metric}) VALUES (CURDATE(), ?) 
             ON DUPLICATE KEY UPDATE {$metric} = {$metric} + VALUES({$metric})",
            [$increment]
        );
    }

    private function notifyAgentNewConversation($agentId, $conversationId)
    {
        // Implementar notificación en tiempo real (WebSocket, Server-Sent Events, etc.)
        // Por ahora log para desarrollo
        error_log("New conversation {$conversationId} assigned to agent {$agentId}");
    }

    private function notifyAgentNewMessage($agentId, $conversationId, $messageId)
    {
        // Implementar notificación en tiempo real
        error_log("New message {$messageId} in conversation {$conversationId} for agent {$agentId}");
    }

    private function notifyAgentTransfer($toAgentId, $conversationId, $fromAgentId)
    {
        // Implementar notificación de transferencia
        error_log("Conversation {$conversationId} transferred from agent {$fromAgentId} to agent {$toAgentId}");
    }
}
?>
