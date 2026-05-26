<?php

namespace App\Models;

use App\Core\Model;
use Exception;

class Chat extends Model
{
    protected $conversationTable = 'chat_conversaciones';
    protected $messageTable = 'chat_mensajes';
    
    // Obtener conversaciones activas
    public function getActiveConversations()
    {
        $sql = "
            SELECT c.*, u.nombre as cliente_nombre, u.email as cliente_email,
                   o.nombre as operador_nombre,
                   m.mensaje as ultimo_mensaje,
                   m.enviado_en as ultimo_mensaje_tiempo,
                   COUNT(CASE WHEN cm.leido = 0 AND cm.tipo_emisor = 'cliente' THEN 1 END) as mensajes_no_leidos
            FROM {$this->conversationTable} c
            LEFT JOIN usuarios u ON c.cliente_id = u.id
            LEFT JOIN usuarios o ON c.operador_id = o.id
            LEFT JOIN {$this->messageTable} m ON c.id = m.conversacion_id
            LEFT JOIN {$this->messageTable} cm ON c.id = cm.conversacion_id
            WHERE c.estado = 'activa'
            GROUP BY c.id
            ORDER BY m.enviado_en DESC
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Obtener conversaciones en espera
    public function getWaitingConversations()
    {
        $sql = "
            SELECT c.*, u.nombre as cliente_nombre, u.email as cliente_email,
                   m.mensaje as ultimo_mensaje,
                   m.enviado_en as ultimo_mensaje_tiempo
            FROM {$this->conversationTable} c
            LEFT JOIN usuarios u ON c.cliente_id = u.id
            LEFT JOIN {$this->messageTable} m ON c.id = m.conversacion_id
            WHERE c.estado = 'en_espera'
            GROUP BY c.id
            ORDER BY c.creada_en ASC
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Obtener o crear conversación
    public function getOrCreateConversation($clientId, $sessionId)
    {
        // Buscar conversación existente
        $sql = "
            SELECT * FROM {$this->conversationTable}
            WHERE (cliente_id = :client_id OR session_id = :session_id)
            AND estado IN ('activa', 'en_espera')
            ORDER BY creada_en DESC
            LIMIT 1
        ";
        
        $params = [
            'client_id' => $clientId,
            'session_id' => $sessionId
        ];
        
        $conversation = $this->db->fetch($sql, $params);
        
        if ($conversation) {
            return $conversation;
        }
        
        // Crear nueva conversación
        $conversationId = $this->db->insert($this->conversationTable, [
            'cliente_id' => $clientId,
            'session_id' => $sessionId,
            'estado' => 'en_espera',
            'prioridad' => 'normal',
            'creada_en' => date('Y-m-d H:i:s')
        ]);
        
        // Enviar mensaje de bienvenida
        $this->sendMessage([
            'conversacion_id' => $conversationId,
            'emisor_id' => null,
            'tipo_emisor' => 'sistema',
            'mensaje' => '¡Hola! Gracias por contactarnos. Un operador te atenderá pronto.',
            'tipo_mensaje' => 'sistema'
        ]);
        
        return $this->db->fetch(
            "SELECT * FROM {$this->conversationTable} WHERE id = :id",
            ['id' => $conversationId]
        );
    }
    
    // Obtener conversación específica
    public function getConversation($id)
    {
        $sql = "
            SELECT c.*, u.nombre as cliente_nombre, u.email as cliente_email,
                   o.nombre as operador_nombre
            FROM {$this->conversationTable} c
            LEFT JOIN usuarios u ON c.cliente_id = u.id
            LEFT JOIN usuarios o ON c.operador_id = o.id
            WHERE c.id = :id
        ";
        
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    // Obtener mensajes de conversación
    public function getConversationMessages($conversationId)
    {
        $sql = "
            SELECT m.*, u.nombre as emisor_nombre
            FROM {$this->messageTable} m
            LEFT JOIN usuarios u ON m.emisor_id = u.id
            WHERE m.conversacion_id = :conversation_id
            ORDER BY m.enviado_en ASC
        ";
        
        return $this->db->fetchAll($sql, ['conversation_id' => $conversationId]);
    }
    
    // Enviar mensaje
    public function sendMessage($data)
    {
        return $this->db->insert($this->messageTable, [
            'conversacion_id' => $data['conversacion_id'],
            'emisor_id' => $data['emisor_id'] ?? null,
            'tipo_emisor' => $data['tipo_emisor'],
            'mensaje' => $data['mensaje'],
            'tipo_mensaje' => $data['tipo_mensaje'] ?? 'texto',
            'archivo_url' => $data['archivo_url'] ?? null,
            'archivo_nombre' => $data['archivo_nombre'] ?? null,
            'archivo_tamaño' => $data['archivo_tamaño'] ?? null,
            'leido' => 0,
            'enviado_en' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Obtener mensajes nuevos
    public function getNewMessages($conversationId, $lastMessageId = 0)
    {
        $sql = "
            SELECT m.*, u.nombre as emisor_nombre
            FROM {$this->messageTable} m
            LEFT JOIN usuarios u ON m.emisor_id = u.id
            WHERE m.conversacion_id = :conversation_id
            AND m.id > :last_message_id
            ORDER BY m.enviado_en ASC
        ";
        
        return $this->db->fetchAll($sql, [
            'conversation_id' => $conversationId,
            'last_message_id' => $lastMessageId
        ]);
    }
    
    // Asignar operador automáticamente
    public function assignOperator($conversationId, $specificOperatorId = null)
    {
        if ($specificOperatorId) {
            // Asignar operador específico
            $operatorId = $specificOperatorId;
        } else {
            // Encontrar operador disponible con menos conversaciones activas
            $sql = "
                SELECT u.id, COUNT(c.id) as active_conversations
                FROM usuarios u
                LEFT JOIN {$this->conversationTable} c ON u.id = c.operador_id AND c.estado = 'activa'
                WHERE u.rol IN ('soporte', 'operador', 'admin')
                AND u.estado_empleado = 'activo'
                GROUP BY u.id
                ORDER BY active_conversations ASC
                LIMIT 1
            ";
            
            $operator = $this->db->fetch($sql);
            
            if (!$operator) {
                return false; // No hay operadores disponibles
            }
            
            $operatorId = $operator['id'];
        }
        
        // Verificar que la conversación no esté ya asignada
        $conversation = $this->getConversation($conversationId);
        if ($conversation['operador_id']) {
            return false; // Ya tiene operador
        }
        
        // Asignar operador
        $result = $this->db->update($this->conversationTable, $conversationId, [
            'operador_id' => $operatorId,
            'estado' => 'activa'
        ]);
        
        return $result;
    }
    
    // Actualizar estado de conversación
    public function updateConversationStatus($conversationId, $status)
    {
        return $this->db->update($this->conversationTable, $conversationId, [
            'estado' => $status
        ]);
    }
    
    // Marcar mensajes como leídos
    public function markMessagesAsRead($conversationId, $readerType)
    {
        $sql = "
            UPDATE {$this->messageTable}
            SET leido = 1
            WHERE conversacion_id = :conversation_id
            AND tipo_emisor != :reader_type
            AND leido = 0
        ";
        
        return $this->db->query($sql, [
            'conversation_id' => $conversationId,
            'reader_type' => $readerType
        ]);
    }
    
    // Obtener operadores disponibles
    public function getAvailableOperators()
    {
        $sql = "
            SELECT u.id, u.nombre, u.email, u.puesto,
                   COUNT(c.id) as conversaciones_activas
            FROM usuarios u
            LEFT JOIN {$this->conversationTable} c ON u.id = c.operador_id AND c.estado = 'activa'
            WHERE u.rol IN ('soporte', 'operador', 'admin', 'gerente')
            AND u.estado_empleado = 'activo'
            GROUP BY u.id
            ORDER BY conversaciones_activas ASC, u.nombre ASC
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Obtener estadísticas del operador
    public function getOperatorStats($operatorId)
    {
        $sql = "
            SELECT 
                COUNT(*) as total_conversaciones,
                COUNT(CASE WHEN estado = 'activa' THEN 1 END) as conversaciones_activas,
                COUNT(CASE WHEN estado = 'cerrada' THEN 1 END) as conversaciones_cerradas,
                COUNT(CASE WHEN DATE(creada_en) = CURDATE() THEN 1 END) as conversaciones_hoy
            FROM {$this->conversationTable}
            WHERE operador_id = :operator_id
        ";
        
        $conversationStats = $this->db->fetch($sql, ['operator_id' => $operatorId]);
        
        $sql = "
            SELECT 
                COUNT(*) as total_mensajes,
                COUNT(CASE WHEN DATE(enviado_en) = CURDATE() THEN 1 END) as mensajes_hoy,
                AVG(TIMESTAMPDIFF(MINUTE, c.creada_en, m.enviado_en)) as tiempo_promedio_respuesta
            FROM {$this->messageTable} m
            INNER JOIN {$this->conversationTable} c ON m.conversacion_id = c.id
            WHERE c.operador_id = :operator_id
            AND m.tipo_emisor = 'operador'
        ";
        
        $messageStats = $this->db->fetch($sql, ['operator_id' => $operatorId]);
        
        return array_merge($conversationStats, $messageStats);
    }
    
    // Obtener conversaciones por estado
    public function getConversationsList($status = 'activa', $operatorId = null)
    {
        $sql = "
            SELECT c.*, u.nombre as cliente_nombre, u.email as cliente_email,
                   o.nombre as operador_nombre,
                   (SELECT COUNT(*) FROM {$this->messageTable} WHERE conversacion_id = c.id AND leido = 0 AND tipo_emisor = 'cliente') as mensajes_no_leidos,
                   (SELECT mensaje FROM {$this->messageTable} WHERE conversacion_id = c.id ORDER BY enviado_en DESC LIMIT 1) as ultimo_mensaje,
                   (SELECT enviado_en FROM {$this->messageTable} WHERE conversacion_id = c.id ORDER BY enviado_en DESC LIMIT 1) as ultimo_mensaje_tiempo
            FROM {$this->conversationTable} c
            LEFT JOIN usuarios u ON c.cliente_id = u.id
            LEFT JOIN usuarios o ON c.operador_id = o.id
            WHERE c.estado = :status
        ";
        
        $params = ['status' => $status];
        
        if ($operatorId) {
            $sql .= " AND c.operador_id = :operator_id";
            $params['operator_id'] = $operatorId;
        }
        
        $sql .= " ORDER BY c.creada_en DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Transferir conversación
    public function transferConversation($conversationId, $fromOperatorId, $toOperatorId, $reason = '')
    {
        $result = $this->db->update($this->conversationTable, $conversationId, [
            'operador_id' => $toOperatorId
        ]);
        
        if ($result) {
            // Enviar mensaje del sistema
            $this->sendMessage([
                'conversacion_id' => $conversationId,
                'emisor_id' => null,
                'tipo_emisor' => 'sistema',
                'mensaje' => 'La conversación ha sido transferida a otro operador.' . ($reason ? " Motivo: $reason" : ''),
                'tipo_mensaje' => 'sistema'
            ]);
        }
        
        return $result;
    }
    
    // Cerrar conversación
    public function closeConversation($conversationId, $reason = '')
    {
        $result = $this->db->update($this->conversationTable, $conversationId, [
            'estado' => 'cerrada',
            'cerrada_en' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            // Enviar mensaje del sistema
            $this->sendMessage([
                'conversacion_id' => $conversationId,
                'emisor_id' => null,
                'tipo_emisor' => 'sistema',
                'mensaje' => 'La conversación ha sido cerrada.' . ($reason ? " Motivo: $reason" : ''),
                'tipo_mensaje' => 'sistema'
            ]);
        }
        
        return $result;
    }
    
    // Crear notificación
    public function createNotification($data)
    {
        return $this->db->insert('notificaciones', $data);
    }
    
    // Obtener notificaciones para operador (SSE)
    public function getOperatorNotifications($operatorId, $since)
    {
        $sql = "
            SELECT m.*, c.cliente_id, u.nombre as cliente_nombre
            FROM {$this->messageTable} m
            INNER JOIN {$this->conversationTable} c ON m.conversacion_id = c.id
            LEFT JOIN usuarios u ON c.cliente_id = u.id
            WHERE c.operador_id = :operator_id
            AND m.tipo_emisor = 'cliente'
            AND UNIX_TIMESTAMP(m.enviado_en) > :since
            ORDER BY m.enviado_en DESC
        ";
        
        return $this->db->fetchAll($sql, [
            'operator_id' => $operatorId,
            'since' => $since
        ]);
    }
    
    // Obtener estadísticas generales del chat
    public function getChatStats()
    {
        $sql = "
            SELECT 
                COUNT(*) as total_conversaciones,
                COUNT(CASE WHEN estado = 'activa' THEN 1 END) as conversaciones_activas,
                COUNT(CASE WHEN estado = 'en_espera' THEN 1 END) as conversaciones_espera,
                COUNT(CASE WHEN estado = 'cerrada' THEN 1 END) as conversaciones_cerradas,
                COUNT(CASE WHEN DATE(creada_en) = CURDATE() THEN 1 END) as conversaciones_hoy,
                AVG(TIMESTAMPDIFF(MINUTE, creada_en, cerrada_en)) as duracion_promedio
            FROM {$this->conversationTable}
        ";
        
        return $this->db->fetch($sql);
    }
}