<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Core\Config;
use App\Helpers\NotificationHelper;
use Exception;

class NotificationController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    /**
     * Ver todas las notificaciones del admin actual
     */
    public function index()
    {
        $page = (int)$this->getInput('page', 1);
        $filter = $this->getInput('filter', 'all'); // all | unread | read
        $tipo = $this->getInput('tipo');
        $prioridad = $this->getInput('prioridad');

        $userId = $this->currentUser['id'];

        // Construir query con filtros
        $where = ["usuario_id = :usuario_id"];
        $params = ['usuario_id' => $userId];

        if ($filter === 'unread') {
            $where[] = "leida = 0";
        } elseif ($filter === 'read') {
            $where[] = "leida = 1";
        }

        if ($tipo) {
            $where[] = "tipo = :tipo";
            $params['tipo'] = $tipo;
        }

        if ($prioridad) {
            $where[] = "prioridad = :prioridad";
            $params['prioridad'] = $prioridad;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        // Paginación
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        // Obtener notificaciones
        $sql = "SELECT * FROM notificaciones
                {$whereClause}
                ORDER BY
                    leida ASC,
                    FIELD(prioridad, 'urgente', 'alta', 'media', 'baja'),
                    created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $notifications = $this->db->fetchAll($sql, $params);

        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM notificaciones {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];
        $totalPages = ceil($total / $perPage);

        // Estadísticas
        $stats = [
            'total' => $this->db->fetch(
                "SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = :usuario_id",
                ['usuario_id' => $userId]
            )['total'],
            'unread' => NotificationHelper::countUnread($userId),
            'today' => $this->db->fetch(
                "SELECT COUNT(*) as total FROM notificaciones
                 WHERE usuario_id = :usuario_id AND DATE(created_at) = CURDATE()",
                ['usuario_id' => $userId]
            )['total']
        ];

        $this->view('admin/notifications/index', [
            'title' => 'Notificaciones',
            'notifications' => $notifications,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'filters' => [
                'filter' => $filter,
                'tipo' => $tipo,
                'prioridad' => $prioridad
            ]
        ]);
    }

    /**
     * Obtener notificaciones no leídas - AJAX
     */
    public function getUnread()
    {
        $limit = (int)$this->getInput('limit', 10);
        $userId = $this->currentUser['id'];

        $notifications = NotificationHelper::getUnread($userId, $limit);

        $this->json([
            'success' => true,
            'notifications' => $notifications,
            'total' => count($notifications),
            'unread_count' => NotificationHelper::countUnread($userId)
        ]);
    }

    /**
     * Contador de notificaciones no leídas - AJAX
     */
    public function countUnread()
    {
        $userId = $this->currentUser['id'];
        $count = NotificationHelper::countUnread($userId);

        $this->json([
            'success' => true,
            'count' => $count
        ]);
    }

    /**
     * Marcar notificación como leída - AJAX
     */
    public function markAsRead($id)
    {
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID requerido'], 400);
            return;
        }

        // Verificar que la notificación pertenece al usuario actual
        $notification = $this->db->fetch(
            "SELECT * FROM notificaciones WHERE id = :id AND usuario_id = :usuario_id",
            ['id' => $id, 'usuario_id' => $this->currentUser['id']]
        );

        if (!$notification) {
            $this->json(['success' => false, 'message' => 'Notificación no encontrada'], 404);
            return;
        }

        if ($notification['leida']) {
            $this->json(['success' => true, 'message' => 'Ya estaba marcada como leída']);
            return;
        }

        try {
            NotificationHelper::markAsRead($id);

            $this->json([
                'success' => true,
                'message' => 'Notificación marcada como leída',
                'unread_count' => NotificationHelper::countUnread($this->currentUser['id'])
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al marcar como leída'], 500);
        }
    }

    /**
     * Marcar todas las notificaciones como leídas - AJAX
     */
    public function markAllAsRead()
    {
        $this->validateCsrf();

        $userId = $this->currentUser['id'];

        try {
            NotificationHelper::markAllAsRead($userId);

            if (Helpers::isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Todas las notificaciones marcadas como leídas',
                    'unread_count' => 0
                ]);
            } else {
                $this->redirect('admin/notifications', 'Todas las notificaciones marcadas como leídas', 'success');
            }

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error al marcar notificaciones'], 500);
            } else {
                $this->redirect('admin/notifications', 'Error al marcar notificaciones', 'error');
            }
        }
    }

    /**
     * Eliminar notificación - AJAX
     */
    public function delete($id)
    {
        $this->validateCsrf();

        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID requerido'], 400);
            return;
        }

        // Verificar que la notificación pertenece al usuario actual
        $notification = $this->db->fetch(
            "SELECT * FROM notificaciones WHERE id = :id AND usuario_id = :usuario_id",
            ['id' => $id, 'usuario_id' => $this->currentUser['id']]
        );

        if (!$notification) {
            $this->json(['success' => false, 'message' => 'Notificación no encontrada'], 404);
            return;
        }

        try {
            NotificationHelper::delete($id);

            if (Helpers::isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Notificación eliminada',
                    'unread_count' => NotificationHelper::countUnread($this->currentUser['id'])
                ]);
            } else {
                $this->redirect('admin/notifications', 'Notificación eliminada', 'success');
            }

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error al eliminar notificación'], 500);
            } else {
                $this->redirect('admin/notifications', 'Error al eliminar notificación', 'error');
            }
        }
    }

    /**
     * Eliminar todas las notificaciones leídas - AJAX
     */
    public function deleteAll()
    {
        $this->validateCsrf();

        $userId = $this->currentUser['id'];

        try {
            // Eliminar solo las leídas
            $result = $this->db->execute(
                "DELETE FROM notificaciones WHERE usuario_id = :usuario_id AND leida = 1",
                ['usuario_id' => $userId]
            );

            $deletedCount = $this->db->rowCount();

            if (Helpers::isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => "Se eliminaron {$deletedCount} notificaciones leídas",
                    'deleted_count' => $deletedCount
                ]);
            } else {
                $this->redirect(
                    'admin/notifications',
                    "Se eliminaron {$deletedCount} notificaciones leídas",
                    'success'
                );
            }

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error al eliminar notificaciones'], 500);
            } else {
                $this->redirect('admin/notifications', 'Error al eliminar notificaciones', 'error');
            }
        }
    }

    /**
     * Limpiar notificaciones antiguas (solo admin principal)
     */
    public function clean()
    {
        $this->validateCsrf();

        // Solo admin principal puede ejecutar limpieza masiva
        if (!$this->can('admin_advanced')) {
            $this->forbidden('No tienes permisos para esta acción');
            return;
        }

        $dias = (int)$this->getInput('dias', 30);

        if ($dias < 7 || $dias > 365) {
            $this->redirect('admin/notifications', 'Días inválidos (mín: 7, máx: 365)', 'error');
            return;
        }

        try {
            $count = NotificationHelper::cleanOld($dias);

            $this->redirect(
                'admin/notifications',
                "Se eliminaron {$count} notificaciones antiguas (más de {$dias} días)",
                'success'
            );

        } catch (Exception $e) {
            $this->redirect('admin/notifications', 'Error al limpiar notificaciones', 'error');
        }
    }

    /**
     * Vista de configuración de notificaciones
     */
    public function settings()
    {
        // Obtener preferencias del usuario (futuro: tabla de preferencias)
        $preferences = [
            'email_notifications' => true,
            'push_notifications' => true,
            'notify_new_booking' => true,
            'notify_new_message' => true,
            'notify_new_user' => false,
            'notify_payment' => true
        ];

        $this->view('admin/notifications/settings', [
            'title' => 'Configuración de Notificaciones',
            'preferences' => $preferences
        ]);
    }

    /**
     * Actualizar preferencias de notificaciones
     */
    public function updateSettings()
    {
        $this->validateCsrf();

        // Futuro: guardar en tabla de preferencias de usuario
        // Por ahora, solo mostrar mensaje de confirmación

        $this->redirect('admin/notifications/settings', 'Preferencias actualizadas', 'success');
    }

    /**
     * Obtener tipos de notificación disponibles
     */
    public function getTypes()
    {
        $types = [
            'nueva_reserva' => 'Nueva Reserva',
            'nuevo_mensaje' => 'Nuevo Mensaje',
            'nuevo_usuario' => 'Nuevo Usuario',
            'tour_sin_stock' => 'Tour Sin Stock',
            'pago_recibido' => 'Pago Recibido',
            'reserva_cancelada' => 'Reserva Cancelada',
            'reseña_nueva' => 'Nueva Reseña',
            'sistema' => 'Sistema',
            'alerta' => 'Alerta'
        ];

        $this->json([
            'success' => true,
            'types' => $types
        ]);
    }

    /**
     * Crear notificación de prueba (solo development)
     */
    public function testNotification()
    {
        if (!Config::isDevelopment()) {
            $this->forbidden('Solo disponible en modo desarrollo');
            return;
        }

        NotificationHelper::create(
            $this->currentUser['id'],
            'sistema',
            'Notificación de Prueba',
            'Esta es una notificación de prueba del sistema',
            Config::getBaseUrl() . '?route=admin',
            'fas fa-flask',
            'media'
        );

        if (Helpers::isAjax()) {
            $this->json(['success' => true, 'message' => 'Notificación de prueba creada']);
        } else {
            $this->redirect('admin/notifications', 'Notificación de prueba creada', 'success');
        }
    }
}
