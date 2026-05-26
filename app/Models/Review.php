<?php

namespace App\Models;

use App\Core\Model;
use Exception;

class Review extends Model
{
    protected $table = 'reviews';
    protected $fillable = [
        'reserva_id', 'tour_id', 'usuario_email', 'usuario_nombre',
        'calificacion_general', 'calificacion_guia', 'calificacion_transporte', 
        'calificacion_organizacion', 'calificacion_valor',
        'titulo', 'comentario', 'fecha_tour', 'experiencia_previa', 
        'tipo_viajero', 'grupo_edad', 'ip_address', 'user_agent',
        'verificado', 'estado', 'aprobado', 'moderado'
    ];
    
    private $tableExists = null;
    
    // Verificar si la tabla existe
    private function checkTableExists()
    {
        if ($this->tableExists !== null) {
            return $this->tableExists;
        }
        
        try {
            $result = $this->db->fetchAll("SHOW TABLES LIKE '{$this->table}'");
            $this->tableExists = count($result) > 0;
            return $this->tableExists;
        } catch (Exception $e) {
            $this->tableExists = false;
            return false;
        }
    }

    public function getApprovedByTour($productId, $limit = 20)
    {
        if (!$this->checkTableExists()) {
            return [];
        }
        
        try {
            $sql = "SELECT * FROM {$this->table} WHERE tour_id = :pid AND aprobado = 1 ORDER BY created_at DESC LIMIT {$limit}";
            return $this->db->fetchAll($sql, ['pid' => $productId]);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getSummary($productId)
    {
        if (!$this->checkTableExists()) {
            return ['count' => 0, 'avg' => 0.0];
        }
        
        try {
            $sql = "SELECT COUNT(*) AS cnt, AVG(rating) AS avg_rating FROM {$this->table} WHERE tour_id = :pid AND aprobado = 1";
            $row = $this->db->fetch($sql, ['pid' => $productId]);
            return [
                'count' => (int)($row['cnt'] ?? 0),
                'avg' => $row['avg_rating'] ? round((float)$row['avg_rating'], 1) : 0.0
            ];
        } catch (Exception $e) {
            return ['count' => 0, 'avg' => 0.0];
        }
    }

    public function getSummaryForProducts(array $ids)
    {
        if (empty($ids) || !$this->checkTableExists()) {
            // Devolver estructura vacía pero válida para todos los tours
            $map = [];
            foreach ($ids as $id) {
                $map[(int)$id] = ['count' => 0, 'avg' => 0.0];
            }
            return $map;
        }
        
        try {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT tour_id, COUNT(*) AS cnt, AVG(rating) AS avg_rating FROM {$this->table} WHERE aprobado = 1 AND tour_id IN ({$placeholders}) GROUP BY tour_id";
            $rows = $this->db->fetchAll($sql, $ids);
            
            $map = [];
            
            // Inicializar todos los tours con valores por defecto
            foreach ($ids as $id) {
                $map[(int)$id] = ['count' => 0, 'avg' => 0.0];
            }
            
            // Llenar con datos reales si existen
            foreach ($rows as $r) {
                $map[(int)$r['tour_id']] = [
                    'count' => (int)$r['cnt'],
                    'avg' => $r['avg_rating'] ? round((float)$r['avg_rating'], 1) : 0.0
                ];
            }
            
            return $map;
        } catch (Exception $e) {
            // En caso de error, devolver estructura segura
            $map = [];
            foreach ($ids as $id) {
                $map[(int)$id] = ['count' => 0, 'avg' => 0.0];
            }
            return $map;
        }
    }

    public function createReview($data)
    {
        if (!$this->checkTableExists()) {
            return ['success' => false, 'errors' => ['general' => 'Sistema de reseñas no disponible']];
        }
        
        $errors = [];
        if (empty($data['tour_id']) || !is_numeric($data['tour_id'])) $errors['tour_id'] = 'Tour inválido';
        if (empty($data['nombre'])) $errors['nombre'] = 'Nombre requerido';
        $rating = (int)($data['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) $errors['rating'] = 'Rating inválido';
        if (empty($data['comentario'])) $errors['comentario'] = 'Comentario requerido';
        
        if (!empty($errors)) return ['success' => false, 'errors' => $errors];

        $payload = [
            'tour_id' => (int)$data['tour_id'],
            'nombre' => htmlspecialchars(trim($data['nombre']), ENT_QUOTES, 'UTF-8'),
            'email' => !empty($data['email']) ? filter_var($data['email'], FILTER_SANITIZE_EMAIL) : null,
            'rating' => $rating,
            'titulo' => !empty($data['titulo']) ? htmlspecialchars(trim($data['titulo']), ENT_QUOTES, 'UTF-8') : null,
            'comentario' => htmlspecialchars(trim($data['comentario']), ENT_QUOTES, 'UTF-8'),
            'fecha_viaje' => !empty($data['fecha_viaje']) ? $data['fecha_viaje'] : null,
            'aprobado' => false, // Las reseñas necesitan aprobación
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        try {
            $id = $this->create($payload);
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['general' => 'Error al enviar la reseña']];
        }
    }

    public function moderate($id, $approved, $response = null)
    {
        if (!$this->checkTableExists()) {
            return false;
        }
        
        try {
            $data = ['aprobado' => $approved ? 1 : 0];
            if ($response) {
                $data['respuesta_admin'] = htmlspecialchars(trim($response), ENT_QUOTES, 'UTF-8');
            }
            return $this->update($id, $data);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getPendingReviews($limit = 50)
    {
        if (!$this->checkTableExists()) {
            return [];
        }
        
        try {
            $sql = "
                SELECT r.*, p.nombre as tour_nombre 
                FROM {$this->table} r
                LEFT JOIN tours p ON r.tour_id = p.id
                WHERE r.aprobado = 0 
                ORDER BY r.created_at DESC 
                LIMIT {$limit}
            ";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getRecentReviews($limit = 10)
    {
        if (!$this->checkTableExists()) {
            return [];
        }
        
        try {
            $sql = "
                SELECT r.*, p.nombre as tour_nombre 
                FROM {$this->table} r
                LEFT JOIN tours p ON r.tour_id = p.id
                WHERE r.aprobado = 1 
                ORDER BY r.created_at DESC 
                LIMIT {$limit}
            ";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function getStatistics()
    {
        if (!$this->checkTableExists()) {
            return [
                'total' => 0,
                'aprobadas' => 0,
                'pendientes' => 0,
                'promedio_rating' => 0.0,
                'rating_distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]
            ];
        }
        
        try {
            $stats = [];
            
            // Totales
            $result = $this->db->fetch("SELECT COUNT(*) as total FROM {$this->table}");
            $stats['total'] = (int)$result['total'];
            
            $result = $this->db->fetch("SELECT COUNT(*) as aprobadas FROM {$this->table} WHERE aprobado = 1");
            $stats['aprobadas'] = (int)$result['aprobadas'];
            
            $result = $this->db->fetch("SELECT COUNT(*) as pendientes FROM {$this->table} WHERE aprobado = 0");
            $stats['pendientes'] = (int)$result['pendientes'];
            
            // Promedio de rating
            $result = $this->db->fetch("SELECT AVG(rating) as promedio FROM {$this->table} WHERE aprobado = 1");
            $stats['promedio_rating'] = $result['promedio'] ? round((float)$result['promedio'], 2) : 0.0;
            
            // Distribución de ratings
            $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
            $results = $this->db->fetchAll("SELECT rating, COUNT(*) as count FROM {$this->table} WHERE aprobado = 1 GROUP BY rating");
            foreach ($results as $row) {
                $distribution[(int)$row['rating']] = (int)$row['count'];
            }
            $stats['rating_distribution'] = $distribution;
            
            return $stats;
        } catch (Exception $e) {
            return [
                'total' => 0,
                'aprobadas' => 0,
                'pendientes' => 0,
                'promedio_rating' => 0.0,
                'rating_distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]
            ];
        }
    }
    
    // Método de diagnóstico
    public function getDiagnosticInfo()
    {
        return [
            'table' => $this->table,
            'exists' => $this->checkTableExists(),
            'sample_query' => $this->checkTableExists() ? 
                "SELECT COUNT(*) FROM {$this->table}" : 
                "Tabla no existe"
        ];
    }

    // === SISTEMA DE REVIEWS VERIFICADAS ===

    /**
     * Crear invitación de review después del tour
     */
    public function createReviewInvitation($reservaId, $tourId, $userEmail, $userName)
    {
        try {
            // Verificar que la reserva existe y está completada
            $reserva = $this->db->fetch(
                "SELECT * FROM reservas WHERE id = ? AND estado IN ('completada', 'finalizada')",
                [$reservaId]
            );

            if (!$reserva) {
                return ['success' => false, 'error' => 'Reserva no encontrada o no completada'];
            }

            // Verificar que no existe ya una invitación
            $existingInvitation = $this->db->fetch(
                "SELECT id FROM review_invitaciones WHERE reserva_id = ? AND usuario_email = ?",
                [$reservaId, $userEmail]
            );

            if ($existingInvitation) {
                return ['success' => false, 'error' => 'Invitación ya existe'];
            }

            // Generar token único
            $token = bin2hex(random_bytes(32));
            $expira = date('Y-m-d H:i:s', strtotime('+30 days'));

            // Crear invitación
            $invitationId = $this->db->insert('review_invitaciones', [
                'reserva_id' => $reservaId,
                'tour_id' => $tourId,
                'usuario_email' => $userEmail,
                'usuario_nombre' => $userName,
                'token_verificacion' => $token,
                'token_expira' => $expira,
                'estado' => 'pendiente'
            ]);

            return [
                'success' => true,
                'invitation_id' => $invitationId,
                'token' => $token,
                'expires_at' => $expira
            ];

        } catch (Exception $e) {
            error_log("Error creating review invitation: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error interno del servidor'];
        }
    }

    /**
     * Validar token de invitación
     */
    public function validateInvitationToken($token)
    {
        try {
            $invitation = $this->db->fetch(
                "SELECT ri.*, r.fecha_salida, p.nombre as tour_nombre 
                 FROM review_invitaciones ri
                 JOIN reservas r ON ri.reserva_id = r.id
                 JOIN tours p ON ri.tour_id = p.id
                 WHERE ri.token_verificacion = ? 
                 AND ri.token_expira > NOW() 
                 AND ri.review_completado = FALSE",
                [$token]
            );

            if (!$invitation) {
                return ['valid' => false, 'error' => 'Token inválido o expirado'];
            }

            return [
                'valid' => true,
                'invitation' => $invitation
            ];

        } catch (Exception $e) {
            error_log("Error validating token: " . $e->getMessage());
            return ['valid' => false, 'error' => 'Error interno del servidor'];
        }
    }

    /**
     * Crear review verificado
     */
    public function createVerifiedReview($data, $invitationToken)
    {
        try {
            $this->db->beginTransaction();

            // Validar token de invitación
            $tokenValidation = $this->validateInvitationToken($invitationToken);
            if (!$tokenValidation['valid']) {
                throw new Exception($tokenValidation['error']);
            }

            $invitation = $tokenValidation['invitation'];

            // Validar datos del review
            $errors = $this->validateVerifiedReviewData($data);
            if (!empty($errors)) {
                throw new Exception('Datos inválidos: ' . implode(', ', $errors));
            }

            // Crear review
            $reviewData = [
                'reserva_id' => $invitation['reserva_id'],
                'tour_id' => $invitation['tour_id'],
                'usuario_email' => $invitation['usuario_email'],
                'usuario_nombre' => $invitation['usuario_nombre'],
                'calificacion_general' => (int)$data['calificacion_general'],
                'calificacion_guia' => !empty($data['calificacion_guia']) ? (int)$data['calificacion_guia'] : null,
                'calificacion_transporte' => !empty($data['calificacion_transporte']) ? (int)$data['calificacion_transporte'] : null,
                'calificacion_organizacion' => !empty($data['calificacion_organizacion']) ? (int)$data['calificacion_organizacion'] : null,
                'calificacion_valor' => !empty($data['calificacion_valor']) ? (int)$data['calificacion_valor'] : null,
                'titulo' => trim($data['titulo']),
                'comentario' => trim($data['comentario']),
                'fecha_tour' => $invitation['fecha_salida'],
                'experiencia_previa' => $data['experiencia_previa'] ?? 'primera_vez',
                'tipo_viajero' => $data['tipo_viajero'] ?? 'solo',
                'grupo_edad' => $data['grupo_edad'] ?? '26-35',
                'verificado' => true,
                'verificado_fecha' => date('Y-m-d H:i:s'),
                'estado' => 'verificado',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ];

            // Determinar si se aprueba automáticamente
            $autoApprove = $this->shouldAutoApprove($reviewData);
            if ($autoApprove) {
                $reviewData['aprobado'] = true;
                $reviewData['moderado'] = true;
                $reviewData['estado'] = 'publicado';
            }

            $reviewId = $this->db->insert($this->table, $reviewData);

            // Marcar invitación como completada
            $this->db->update('review_invitaciones', 
                ['review_completado' => true, 'review_id' => $reviewId, 'estado' => 'completada'],
                ['token_verificacion' => $invitationToken]
            );

            $this->db->commit();

            return [
                'success' => true,
                'review_id' => $reviewId,
                'auto_approved' => $autoApprove,
                'message' => $autoApprove ? 
                    'Review publicado exitosamente' : 
                    'Review enviado para moderación'
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error creating verified review: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validar datos del review verificado
     */
    private function validateVerifiedReviewData($data)
    {
        $errors = [];

        // Calificación general obligatoria
        if (empty($data['calificacion_general']) || !in_array($data['calificacion_general'], [1,2,3,4,5])) {
            $errors[] = 'Calificación general es requerida (1-5)';
        }

        // Título obligatorio
        if (empty(trim($data['titulo']))) {
            $errors[] = 'Título es requerido';
        } elseif (strlen(trim($data['titulo'])) > 200) {
            $errors[] = 'Título muy largo (máximo 200 caracteres)';
        }

        // Comentario obligatorio con longitud mínima
        $minChars = 50; // Default mínimo
        $maxChars = 2000; // Default máximo
        
        if (empty(trim($data['comentario']))) {
            $errors[] = 'Comentario es requerido';
        } elseif (strlen(trim($data['comentario'])) < $minChars) {
            $errors[] = "Comentario muy corto (mínimo {$minChars} caracteres)";
        } elseif (strlen(trim($data['comentario'])) > $maxChars) {
            $errors[] = "Comentario muy largo (máximo {$maxChars} caracteres)";
        }

        // Validar calificaciones específicas si se proporcionan
        $calificaciones = ['calificacion_guia', 'calificacion_transporte', 'calificacion_organizacion', 'calificacion_valor'];
        foreach ($calificaciones as $cal) {
            if (!empty($data[$cal]) && !in_array($data[$cal], [1,2,3,4,5])) {
                $errors[] = ucfirst(str_replace('calificacion_', '', $cal)) . ' debe ser entre 1-5';
            }
        }

        return $errors;
    }

    /**
     * Determinar si el review debe ser aprobado automáticamente
     */
    private function shouldAutoApprove($reviewData)
    {
        // Por ahora usamos configuración simple
        $minCalification = 4; // Calificación mínima para auto-aprobar
        
        // Auto-aprobar si:
        // 1. Calificación general >= 4
        // 2. Review está verificado
        // 3. No contiene contenido ofensivo
        
        return $reviewData['calificacion_general'] >= $minCalification && 
               $reviewData['verificado'] && 
               !$this->containsOffensiveContent($reviewData['comentario']);
    }

    /**
     * Detectar contenido ofensivo básico
     */
    private function containsOffensiveContent($text)
    {
        $offensiveWords = [
            'estafa', 'fraude', 'terrible', 'horrible', 'pésimo', 'robo', 'ladrones',
            'scam', 'fraud', 'awful', 'terrible', 'worst', 'steal'
        ];
        
        $lowerText = strtolower($text);
        foreach ($offensiveWords as $word) {
            if (strpos($lowerText, $word) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Obtener reviews públicos de un tour con nuevo sistema
     */
    public function getVerifiedTourReviews($productId, $limit = 10, $offset = 0)
    {
        if (!$this->checkTableExists()) {
            return [];
        }

        try {
            $sql = "SELECT r.*, 
                           CASE WHEN r.verificado = 1 THEN 'Compra verificada' ELSE 'Review estándar' END as badge_verificacion,
                           DATEDIFF(CURRENT_DATE, r.fecha_tour) as dias_desde_tour
                    FROM {$this->table} r
                    WHERE r.tour_id = ? 
                    AND r.estado = 'publicado' 
                    AND r.aprobado = TRUE
                    ORDER BY r.verificado DESC, r.created_at DESC 
                    LIMIT ? OFFSET ?";
            
            return $this->db->fetchAll($sql, [$productId, $limit, $offset]);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Obtener estadísticas de reviews de un tour
     */
    public function getVerifiedTourReviewStats($productId)
    {
        if (!$this->checkTableExists()) {
            return null;
        }

        try {
            $sql = "SELECT 
                        COUNT(*) as total_reviews,
                        COUNT(CASE WHEN verificado = 1 THEN 1 END) as reviews_verificadas,
                        COUNT(CASE WHEN estado = 'publicado' AND aprobado = 1 THEN 1 END) as reviews_publicadas,
                        AVG(CASE WHEN estado = 'publicado' AND aprobado = 1 THEN calificacion_general END) as promedio_general,
                        COUNT(CASE WHEN estado = 'publicado' AND aprobado = 1 AND calificacion_general = 5 THEN 1 END) as estrellas_5,
                        COUNT(CASE WHEN estado = 'publicado' AND aprobado = 1 AND calificacion_general = 4 THEN 1 END) as estrellas_4,
                        COUNT(CASE WHEN estado = 'publicado' AND aprobado = 1 AND calificacion_general = 3 THEN 1 END) as estrellas_3,
                        COUNT(CASE WHEN estado = 'publicado' AND aprobado = 1 AND calificacion_general = 2 THEN 1 END) as estrellas_2,
                        COUNT(CASE WHEN estado = 'publicado' AND aprobado = 1 AND calificacion_general = 1 THEN 1 END) as estrellas_1
                    FROM {$this->table} 
                    WHERE tour_id = ?";

            return $this->db->fetch($sql, [$productId]);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Generar invitación automática después de completar tour
     */
    public function generatePostTourInvitation($reservaId)
    {
        try {
            // Obtener datos de la reserva
            $reserva = $this->db->fetch(
                "SELECT r.*, p.nombre as tour_nombre 
                 FROM reservas r
                 JOIN tours p ON r.tour_id = p.id
                 WHERE r.id = ? AND r.estado = 'completada'",
                [$reservaId]
            );

            if (!$reserva) {
                return false;
            }

            // Crear invitación
            $result = $this->createReviewInvitation(
                $reservaId,
                $reserva['tour_id'],
                $reserva['cliente_email'],
                $reserva['cliente_nombre']
            );

            if ($result['success']) {
                // Programar envío de invitación (3 días después del tour)
                // Por ahora solo retornamos éxito, el sistema de recordatorios se encargará del envío
                return true;
            }

            return false;

        } catch (Exception $e) {
            error_log("Error generating post-tour invitation: " . $e->getMessage());
            return false;
        }
    }
}