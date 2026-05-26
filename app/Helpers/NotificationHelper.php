<?php

namespace App\Helpers;

use App\Core\Database;
use App\Core\Config;
use Exception;

/**
 * NotificationHelper
 *
 * Sistema de notificaciones en tiempo real para admin
 */
class NotificationHelper
{
    private static $db = null;

    private static function getDb()
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    /**
     * Crear una notificación
     *
     * @param int $usuarioId ID del usuario que recibirá la notificación
     * @param string $tipo Tipo de notificación
     * @param string $titulo Título breve
     * @param string $mensaje Mensaje descriptivo
     * @param string|null $url URL a la que debe redirigir
     * @param string|null $icono Icono (emoji o clase FA)
     * @param string $prioridad baja|media|alta|urgente
     */
    public static function create(
        int $usuarioId,
        string $tipo,
        string $titulo,
        string $mensaje = '',
        ?string $url = null,
        ?string $icono = null,
        string $prioridad = 'media'
    ): bool {
        try {
            $db = self::getDb();

            $sql = "INSERT INTO notificaciones (
                usuario_id, tipo, titulo, mensaje, url, icono, prioridad
            ) VALUES (
                :usuario_id, :tipo, :titulo, :mensaje, :url, :icono, :prioridad
            )";

            $db->query($sql, [
                'usuario_id' => $usuarioId,
                'tipo' => $tipo,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'url' => $url,
                'icono' => $icono ?? self::getDefaultIcon($tipo),
                'prioridad' => $prioridad
            ]);
            return true;

        } catch (\Throwable $e) {
            error_log('NotificationHelper Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Notificar a todos los admins
     */
    public static function notifyAllAdmins(
        string $tipo,
        string $titulo,
        string $mensaje = '',
        ?string $url = null,
        ?string $icono = null,
        string $prioridad = 'media'
    ): int {
        try {
            $db = self::getDb();

            // Obtener todos los usuarios admin
            $admins = $db->fetchAll(
                "SELECT id FROM usuarios WHERE tipo = 'admin' AND activo = 1"
            );

            $count = 0;
            foreach ($admins as $admin) {
                if (self::create(
                    $admin['id'],
                    $tipo,
                    $titulo,
                    $mensaje,
                    $url,
                    $icono,
                    $prioridad
                )) {
                    $count++;
                }
            }

            return $count;

        } catch (\Throwable $e) {
            error_log('NotificationHelper Error notifying admins: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener notificaciones no leídas de un usuario
     */
    public static function getUnread(int $usuarioId, int $limit = 10): array
    {
        try {
            $db = self::getDb();

            $sql = "SELECT * FROM notificaciones
                    WHERE usuario_id = :usuario_id AND leida = 0
                    ORDER BY
                        FIELD(prioridad, 'urgente', 'alta', 'media', 'baja'),
                        created_at DESC
                    LIMIT :limit";

            return $db->fetchAll($sql, [
                'usuario_id' => $usuarioId,
                'limit' => $limit
            ]);

        } catch (Exception $e) {
            error_log('NotificationHelper Error getting unread: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar notificaciones no leídas
     */
    public static function countUnread(int $usuarioId): int
    {
        try {
            $db = self::getDb();

            $result = $db->fetch(
                "SELECT COUNT(*) as total FROM notificaciones
                 WHERE usuario_id = :usuario_id AND leida = 0",
                ['usuario_id' => $usuarioId]
            );

            return (int)($result['total'] ?? 0);

        } catch (Exception $e) {
            error_log('NotificationHelper Error counting unread: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Marcar notificación como leída
     */
    public static function markAsRead(int $notificacionId): bool
    {
        try {
            $db = self::getDb();

            return $db->execute(
                "UPDATE notificaciones
                 SET leida = 1, leida_at = NOW()
                 WHERE id = :id",
                ['id' => $notificacionId]
            );

        } catch (Exception $e) {
            error_log('NotificationHelper Error marking as read: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Marcar todas como leídas
     */
    public static function markAllAsRead(int $usuarioId): bool
    {
        try {
            $db = self::getDb();

            return $db->execute(
                "UPDATE notificaciones
                 SET leida = 1, leida_at = NOW()
                 WHERE usuario_id = :usuario_id AND leida = 0",
                ['usuario_id' => $usuarioId]
            );

        } catch (Exception $e) {
            error_log('NotificationHelper Error marking all as read: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar notificación
     */
    public static function delete(int $notificacionId): bool
    {
        try {
            $db = self::getDb();

            return $db->execute(
                "DELETE FROM notificaciones WHERE id = :id",
                ['id' => $notificacionId]
            );

        } catch (Exception $e) {
            error_log('NotificationHelper Error deleting: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener icono por defecto según tipo
     */
    private static function getDefaultIcon(string $tipo): string
    {
        return match($tipo) {
            'nueva_reserva' => 'fas fa-calendar-check',
            'nuevo_mensaje' => 'fas fa-envelope',
            'nuevo_usuario' => 'fas fa-user-plus',
            'tour_sin_stock' => 'fas fa-exclamation-triangle',
            'pago_recibido' => 'fas fa-money-bill-wave',
            'reserva_cancelada' => 'fas fa-times-circle',
            'reseña_nueva' => 'fas fa-star',
            'sistema' => 'fas fa-cog',
            'alerta' => 'fas fa-bell',
            default => 'fas fa-info-circle'
        };
    }

    /**
     * Limpiar notificaciones antiguas
     */
    public static function cleanOld(int $dias = 30): int
    {
        try {
            $db = self::getDb();

            $fechaLimite = date('Y-m-d H:i:s', strtotime("-{$dias} days"));

            $result = $db->execute(
                "DELETE FROM notificaciones
                 WHERE created_at < :fecha_limite AND leida = 1",
                ['fecha_limite' => $fechaLimite]
            );

            return $db->rowCount();

        } catch (Exception $e) {
            error_log('NotificationHelper Error cleaning: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Helpers para crear notificaciones comunes
     */

    public static function nuevaReserva(int $reservaId, string $clienteNombre, string $tourNombre): void
    {
        self::notifyAllAdmins(
            'nueva_reserva',
            'Nueva Reserva',
            "{$clienteNombre} reservó {$tourNombre}",
            Config::getBaseUrl() . "?route=admin/bookings/view/{$reservaId}",
            'fas fa-calendar-check',
            'alta'
        );
    }

    public static function nuevoMensaje(int $mensajeId, string $remitente): void
    {
        self::notifyAllAdmins(
            'nuevo_mensaje',
            'Nuevo Mensaje',
            "Mensaje de {$remitente}",
            Config::getBaseUrl() . "?route=admin/messages",
            'fas fa-envelope',
            'media'
        );
    }

    public static function tourSinStock(int $tourId, string $tourNombre): void
    {
        self::notifyAllAdmins(
            'tour_sin_stock',
            'Tour sin Disponibilidad',
            "{$tourNombre} no tiene fechas disponibles",
            Config::getBaseUrl() . "?route=admin/tours/edit/{$tourId}",
            'fas fa-exclamation-triangle',
            'alta'
        );
    }

    public static function nuevoUsuario(string $nombre, string $email): void
    {
        self::notifyAllAdmins(
            'nuevo_usuario',
            'Nuevo Registro',
            "{$nombre} se registró ({$email})",
            Config::getBaseUrl() . "?route=admin/users",
            'fas fa-user-plus',
            'baja'
        );
    }
}
