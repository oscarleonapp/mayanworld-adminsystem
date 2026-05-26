<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Helpers;
use Exception;

class Message extends Model
{
    protected $table = 'mensajes';
    protected $fillable = [
        'reserva_id', 'nombre', 'email', 'telefono', 'asunto', 
        'mensaje', 'respuesta', 'estado', 'es_admin'
    ];
    
    // Estados de mensajes
    const STATUS_NEW = 'nuevo';
    const STATUS_READ = 'leido';
    const STATUS_IN_PROCESS = 'en_proceso';
    const STATUS_REPLIED = 'respondido';
    const STATUS_RESOLVED = 'resuelto';
    const STATUS_CLOSED = 'cerrado';
    
    // Crear nuevo mensaje
    public function createMessage($data)
    {
        $rules = [
            'nombre' => ['required' => true, 'max' => 100],
            'email' => ['required' => true, 'email' => true, 'max' => 150],
            'mensaje' => ['required' => true]
        ];
        
        $errors = $this->validate($data, $rules);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $messageData = [
            'reserva_id' => $data['reserva_id'] ?? null,
            'nombre' => Helpers::sanitizeString($data['nombre']),
            'email' => strtolower(trim($data['email'])),
            'telefono' => $data['telefono'] ? Helpers::sanitizeString($data['telefono']) : null,
            'asunto' => $data['asunto'] ? Helpers::sanitizeString($data['asunto']) : null,
            'mensaje' => Helpers::sanitizeString($data['mensaje']),
            'estado' => self::STATUS_NEW,
            'es_admin' => 0
        ];
        
        try {
            $id = $this->create($messageData);
            return ['success' => true, 'message_id' => $id];
        } catch (Exception $e) {
            // In development, show the actual error
            if (defined('APP_ENV') && APP_ENV === 'development') {
                return ['success' => false, 'errors' => ['general' => 'Error al enviar mensaje: ' . $e->getMessage()]];
            }
            return ['success' => false, 'errors' => ['general' => 'Error al enviar mensaje']];
        }
    }
    
    // Responder mensaje
    public function replyMessage($id, $response, $adminId = null)
    {
        $message = $this->find($id);

        if (!$message) {
            return ['success' => false, 'message' => 'Mensaje no encontrado'];
        }

        $updateData = [
            'respuesta' => Helpers::sanitizeString($response),
            'estado' => self::STATUS_REPLIED
        ];

        try {
            $updated = $this->update($id, $updateData);

            if ($updated) {
                return ['success' => true, 'message' => 'Respuesta enviada correctamente'];
            } else {
                return ['success' => false, 'message' => 'No se pudo actualizar el mensaje'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al responder mensaje: ' . $e->getMessage()];
        }
    }
    
    // Marcar como leído
    public function markAsRead($id)
    {
        return $this->update($id, ['estado' => self::STATUS_READ]);
    }
    
    // Obtener mensajes por estado
    public function getByStatus($status, $limit = null)
    {
        return $this->findAll(['estado' => $status], 'created_at DESC', $limit);
    }
    
    // Obtener conversación de una reserva
    public function getConversation($reservaId)
    {
        return $this->findAll(['reserva_id' => $reservaId], 'created_at ASC');
    }
}