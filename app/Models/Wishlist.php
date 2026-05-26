<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Config;

class Wishlist extends Model
{
    protected $table = 'wishlists';
    protected $fillable = [
        'user_email', 'user_name', 'session_id', 'nombre', 'descripcion',
        'es_publica', 'notificar_cambios_precio', 'notificar_disponibilidad', 
        'notificar_ofertas_especiales', 'share_token', 'share_expires_at'
    ];

    /**
     * Obtener o crear wishlist para un usuario
     */
    public function getOrCreateUserWishlist($userEmail, $userName, $sessionId = null)
    {
        try {
            // Buscar wishlist existente
            $wishlist = $this->db->fetch(
                "SELECT * FROM wishlists WHERE user_email = ? OR session_id = ? ORDER BY created_at DESC LIMIT 1",
                [$userEmail ?: 'anonymous', $sessionId]
            );

            if ($wishlist) {
                // Si encontramos por sesión pero ahora tenemos email, actualizar
                if (!$wishlist['user_email'] && $userEmail) {
                    $this->db->update('wishlists', 
                        ['user_email' => $userEmail, 'user_name' => $userName],
                        ['id' => $wishlist['id']]
                    );
                    $wishlist['user_email'] = $userEmail;
                    $wishlist['user_name'] = $userName;
                }
                return $wishlist;
            }

            // Crear nueva wishlist
            $wishlistId = $this->db->insert('wishlists', [
                'user_email' => $userEmail ?: 'anonymous',
                'user_name' => $userName ?: 'Usuario Anónimo',
                'session_id' => $sessionId,
                'nombre' => 'Mi Lista de Deseos'
            ]);

            return $this->find($wishlistId);

        } catch (Exception $e) {
            error_log("Error getting/creating wishlist: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Agregar tour a wishlist
     */
    public function addTour($wishlistId, $tourId, $notes = null, $priority = 'media')
    {
        try {
            // Verificar que el tour existe
            $tour = $this->db->fetch("SELECT * FROM tours WHERE id = ?", [$tourId]);
            if (!$tour) {
                return ['success' => false, 'error' => 'Tour no encontrado'];
            }

            // Verificar si ya está en la wishlist
            $existing = $this->db->fetch(
                "SELECT id FROM wishlist_items WHERE wishlist_id = ? AND tour_id = ?",
                [$wishlistId, $tourId]
            );

            if ($existing) {
                return ['success' => false, 'error' => 'Tour ya está en tu lista de deseos'];
            }

            // Agregar tour
            $itemId = $this->db->insert('wishlist_items', [
                'wishlist_id' => $wishlistId,
                'tour_id' => $tourId,
                'precio_cuando_agregado' => $tour['precio'],
                'ultimo_precio_visto' => $tour['precio'],
                'notas_personales' => $notes,
                'prioridad' => $priority
            ]);

            return [
                'success' => true,
                'item_id' => $itemId,
                'message' => 'Tour agregado a tu lista de deseos'
            ];

        } catch (Exception $e) {
            error_log("Error adding tour to wishlist: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error interno del servidor'];
        }
    }

    /**
     * Remover tour de wishlist
     */
    public function removeTour($wishlistId, $tourId)
    {
        try {
            $affected = $this->db->execute(
                "DELETE FROM wishlist_items WHERE wishlist_id = ? AND tour_id = ?",
                [$wishlistId, $tourId]
            );

            if ($affected > 0) {
                return ['success' => true, 'message' => 'Tour removido de tu lista de deseos'];
            } else {
                return ['success' => false, 'error' => 'Tour no encontrado en tu lista'];
            }

        } catch (Exception $e) {
            error_log("Error removing tour from wishlist: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error interno del servidor'];
        }
    }

    /**
     * Obtener tours de wishlist con información completa
     */
    public function getWishlistTours($wishlistId, $limit = 50, $offset = 0)
    {
        try {
            $sql = "SELECT * FROM wishlist_with_tours 
                    WHERE wishlist_id = ? 
                    ORDER BY fecha_agregado DESC 
                    LIMIT ? OFFSET ?";
                    
            return $this->db->fetchAll($sql, [$wishlistId, $limit, $offset]);

        } catch (Exception $e) {
            error_log("Error getting wishlist tours: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener wishlist por token de compartir
     */
    public function getByShareToken($token)
    {
        try {
            $wishlist = $this->db->fetch(
                "SELECT * FROM wishlists 
                 WHERE share_token = ? 
                 AND (share_expires_at IS NULL OR share_expires_at > NOW())",
                [$token]
            );

            if ($wishlist) {
                // Incrementar contador de veces compartida
                $this->db->execute(
                    "UPDATE wishlists SET veces_compartida = veces_compartida + 1 WHERE id = ?",
                    [$wishlist['id']]
                );
            }

            return $wishlist;

        } catch (Exception $e) {
            error_log("Error getting wishlist by share token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Generar token de compartir
     */
    public function generateShareToken($wishlistId, $expiresInDays = 30)
    {
        try {
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresInDays} days"));

            $this->db->update('wishlists',
                [
                    'share_token' => $token,
                    'share_expires_at' => $expiresAt,
                    'es_publica' => true
                ],
                ['id' => $wishlistId]
            );

            return [
                'success' => true,
                'token' => $token,
                'expires_at' => $expiresAt,
                'share_url' => Config::getBaseUrl() . '?route=wishlist/shared&token=' . $token
            ];

        } catch (Exception $e) {
            error_log("Error generating share token: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error generando enlace de compartir'];
        }
    }

    /**
     * Crear alerta de precio
     */
    public function createPriceAlert($wishlistItemId, $targetPrice, $alertType = 'precio_menor', $discountPercentage = null)
    {
        try {
            // Verificar que el item existe
            $item = $this->db->fetch("SELECT * FROM wishlist_items WHERE id = ?", [$wishlistItemId]);
            if (!$item) {
                return ['success' => false, 'error' => 'Item no encontrado'];
            }

            // Crear alerta
            $alertId = $this->db->insert('price_alerts', [
                'wishlist_item_id' => $wishlistItemId,
                'precio_objetivo' => $targetPrice,
                'tipo_alerta' => $alertType,
                'valor_descuento' => $discountPercentage,
                'expires_at' => date('Y-m-d H:i:s', strtotime('+6 months'))
            ]);

            return [
                'success' => true,
                'alert_id' => $alertId,
                'message' => 'Alerta de precio creada'
            ];

        } catch (Exception $e) {
            error_log("Error creating price alert: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error creando alerta'];
        }
    }

    /**
     * Obtener notificaciones pendientes para un usuario
     */
    public function getPendingNotifications($userEmail, $limit = 10)
    {
        try {
            $sql = "SELECT wn.*, wi.tour_id, p.nombre as tour_nombre, p.imagen_principal
                    FROM wishlist_notifications wn
                    JOIN wishlist_items wi ON wn.wishlist_item_id = wi.id
                    JOIN wishlists w ON wi.wishlist_id = w.id
                    JOIN tours p ON wi.tour_id = p.id
                    WHERE w.user_email = ? 
                    AND wn.estado = 'pendiente'
                    ORDER BY wn.fecha_creada DESC
                    LIMIT ?";

            return $this->db->fetchAll($sql, [$userEmail, $limit]);

        } catch (Exception $e) {
            error_log("Error getting pending notifications: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Marcar notificación como leída
     */
    public function markNotificationAsRead($notificationId)
    {
        try {
            return $this->db->update('wishlist_notifications',
                ['estado' => 'leida', 'fecha_leida' => date('Y-m-d H:i:s')],
                ['id' => $notificationId]
            );
        } catch (Exception $e) {
            error_log("Error marking notification as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Procesar cambios de precios (llamado por cron)
     */
    public function processExistingPriceChanges()
    {
        try {
            $this->db->execute("CALL DetectarCambiosPrecios()");
            return true;
        } catch (Exception $e) {
            error_log("Error processing price changes: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar alertas de precios (llamado por cron)
     */
    public function checkPriceAlerts()
    {
        try {
            $this->db->execute("CALL VerificarAlertasPrecios()");
            return true;
        } catch (Exception $e) {
            error_log("Error checking price alerts: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de wishlist
     */
    public function getWishlistStats($days = 30)
    {
        try {
            $startDate = date('Y-m-d', strtotime("-$days days"));
            
            return $this->db->fetchAll(
                "SELECT * FROM wishlist_stats 
                 WHERE fecha >= ? 
                 ORDER BY fecha DESC",
                [$startDate]
            );

        } catch (Exception $e) {
            error_log("Error getting wishlist stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si un tour está en la wishlist del usuario
     */
    public function isTourInWishlist($tourId, $userEmail, $sessionId = null)
    {
        try {
            $sql = "SELECT wi.id 
                    FROM wishlist_items wi
                    JOIN wishlists w ON wi.wishlist_id = w.id
                    WHERE wi.tour_id = ? 
                    AND (w.user_email = ? OR w.session_id = ?)
                    LIMIT 1";

            $result = $this->db->fetch($sql, [$tourId, $userEmail ?: 'anonymous', $sessionId]);
            return $result !== null;

        } catch (Exception $e) {
            error_log("Error checking if tour is in wishlist: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener tours similares en wishlists (para recomendaciones)
     */
    public function getSimilarWishlistTours($tourId, $limit = 5)
    {
        try {
            // Tours que están en wishlists junto con el tour actual
            $sql = "SELECT p.*, COUNT(*) as frecuencia
                    FROM tours p
                    JOIN wishlist_items wi ON p.id = wi.tour_id
                    WHERE wi.wishlist_id IN (
                        SELECT DISTINCT wishlist_id 
                        FROM wishlist_items 
                        WHERE tour_id = ?
                    )
                    AND p.id != ?
                    GROUP BY p.id
                    ORDER BY frecuencia DESC, p.created_at DESC
                    LIMIT ?";

            return $this->db->fetchAll($sql, [$tourId, $tourId, $limit]);

        } catch (Exception $e) {
            error_log("Error getting similar wishlist tours: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener wishlists públicas populares
     */
    public function getPopularPublicWishlists($limit = 10)
    {
        try {
            $sql = "SELECT w.*, COUNT(wi.id) as total_items
                    FROM wishlists w
                    LEFT JOIN wishlist_items wi ON w.id = wi.wishlist_id
                    WHERE w.es_publica = TRUE
                    AND w.share_token IS NOT NULL
                    GROUP BY w.id
                    HAVING total_items > 0
                    ORDER BY w.veces_compartida DESC, total_items DESC
                    LIMIT ?";

            return $this->db->fetchAll($sql, [$limit]);

        } catch (Exception $e) {
            error_log("Error getting popular public wishlists: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Actualizar configuración de notificaciones
     */
    public function updateNotificationSettings($wishlistId, $settings)
    {
        try {
            $allowedSettings = [
                'notificar_cambios_precio', 
                'notificar_disponibilidad', 
                'notificar_ofertas_especiales'
            ];

            $updateData = [];
            foreach ($allowedSettings as $setting) {
                if (isset($settings[$setting])) {
                    $updateData[$setting] = $settings[$setting] ? 1 : 0;
                }
            }

            if (empty($updateData)) {
                return ['success' => false, 'error' => 'No hay configuraciones válidas'];
            }

            $this->db->update('wishlists', $updateData, ['id' => $wishlistId]);

            return [
                'success' => true,
                'message' => 'Configuración de notificaciones actualizada'
            ];

        } catch (Exception $e) {
            error_log("Error updating notification settings: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error actualizando configuración'];
        }
    }

    /**
     * Enviar notificaciones por email (integración con RemindersEngine)
     */
    public function sendWishlistNotifications($limit = 50)
    {
        try {
            // Obtener notificaciones pendientes
            $notifications = $this->db->fetchAll(
                "SELECT wn.*, wi.tour_id, w.user_email, w.user_name,
                        p.nombre as tour_nombre, p.imagen_principal
                 FROM wishlist_notifications wn
                 JOIN wishlist_items wi ON wn.wishlist_item_id = wi.id
                 JOIN wishlists w ON wi.wishlist_id = w.id
                 JOIN tours p ON wi.tour_id = p.id
                 WHERE wn.estado = 'pendiente'
                 AND wn.canal = 'email'
                 ORDER BY wn.fecha_creada ASC
                 LIMIT ?",
                [$limit]
            );

            $sent = 0;
            foreach ($notifications as $notification) {
                // Aquí se integraría con el sistema de emails
                // Por ahora simulamos el envío
                
                $this->db->update('wishlist_notifications',
                    ['estado' => 'enviada', 'fecha_enviada' => date('Y-m-d H:i:s')],
                    ['id' => $notification['id']]
                );
                
                $sent++;
            }

            return ['success' => true, 'sent' => $sent];

        } catch (Exception $e) {
            error_log("Error sending wishlist notifications: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
