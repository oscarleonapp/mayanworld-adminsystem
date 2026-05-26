<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\Config;
use App\Models\PushNotification;
use Exception;

class PushNotificationController extends BaseController
{
    private $pushNotification;
    
    public function __construct()
    {
        parent::__construct();
        $this->pushNotification = new PushNotification();
    }
    
    /**
     * Endpoint público para suscribirse a push notifications
     */
    public function subscribe()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        try {
            // Obtener datos del request
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->jsonResponse(['success' => false, 'message' => 'Datos JSON inválidos'], 400);
                return;
            }
            
            // Añadir datos del usuario si está logueado
            if (Auth::isLoggedIn()) {
                $input['user_id'] = Auth::getUserId();
                $input['user_email'] = Auth::getUserEmail();
            }
            
            // Añadir información del dispositivo/navegador
            $input['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $input['language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ? 
                substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : 'es';
            $input['timezone'] = $input['timezone'] ?? 'America/Guatemala';
            
            // Procesar suscripción
            $result = $this->pushNotification->subscribe($input);
            
            $this->jsonResponse($result, $result['success'] ? 201 : 400);
            
        } catch (Exception $e) {
            error_log('Error in push subscription endpoint: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
    
    /**
     * Endpoint para cancelar suscripción
     */
    public function unsubscribe()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['endpoint'])) {
                $this->jsonResponse(['success' => false, 'message' => 'Endpoint requerido'], 400);
                return;
            }
            
            $result = $this->pushNotification->unsubscribe($input['endpoint']);
            $this->jsonResponse($result);
            
        } catch (Exception $e) {
            error_log('Error in push unsubscribe: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error interno'], 500);
        }
    }
    
    /**
     * Enviar notificación individual (para testing)
     */
    public function sendTest()
    {
        // Solo admins pueden enviar notificaciones de prueba
        if (!Auth::isLoggedIn() || !Auth::isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['subscription_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'subscription_id requerido'], 400);
                return;
            }
            
            $notificationData = [
                'title' => $input['title'] ?? 'Notificación de Prueba',
                'body' => $input['body'] ?? 'Esta es una notificación de prueba desde Travel Mayan World',
                'icon' => $input['icon'] ?? '/assets/images/notification-icon.png',
                'click_action' => $input['click_action'] ?? '/',
                'category' => 'test'
            ];
            
            $result = $this->pushNotification->sendNotification($input['subscription_id'], $notificationData);
            $this->jsonResponse($result);
            
        } catch (Exception $e) {
            error_log('Error in test notification: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error enviando prueba'], 500);
        }
    }
    
    /**
     * Registrar eventos de notificaciones (click, dismiss, etc.)
     */
    public function trackEvent()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['subscription_id']) || !isset($input['event_type'])) {
                $this->jsonResponse([
                    'success' => false, 
                    'message' => 'subscription_id y event_type requeridos'
                ], 400);
                return;
            }
            
            // Añadir información contextual
            $eventData = $input['event_data'] ?? [];
            $eventData['page_url'] = $_SERVER['HTTP_REFERER'] ?? null;
            $eventData['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $eventData['ip_address'] = $this->getClientIP();
            $eventData['timestamp'] = time();
            
            $result = $this->pushNotification->trackEvent(
                $input['subscription_id'],
                $input['event_type'],
                $eventData
            );
            
            $this->jsonResponse($result);
            
        } catch (Exception $e) {
            error_log('Error tracking push event: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error registrando evento'], 500);
        }
    }
    
    /**
     * Obtener configuración de notificaciones del usuario
     */
    public function getUserPreferences()
    {
        if (!Auth::isLoggedIn()) {
            $this->jsonResponse(['success' => false, 'message' => 'Debes iniciar sesión'], 401);
            return;
        }
        
        try {
            $userId = Auth::getUserId();
            
            $preferences = $this->pushNotification->query(
                "SELECT unp.*, ps.device_type, ps.browser 
                 FROM user_notification_preferences unp
                 JOIN push_subscriptions ps ON unp.subscription_id = ps.id
                 WHERE unp.user_id = ? AND ps.is_active = TRUE",
                [$userId]
            );
            
            $this->jsonResponse([
                'success' => true,
                'preferences' => $preferences
            ]);
            
        } catch (Exception $e) {
            error_log('Error getting user preferences: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error obteniendo preferencias'], 500);
        }
    }
    
    /**
     * Actualizar preferencias de notificaciones del usuario
     */
    public function updateUserPreferences()
    {
        if (!Auth::isLoggedIn()) {
            $this->jsonResponse(['success' => false, 'message' => 'Debes iniciar sesión'], 401);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = Auth::getUserId();
            
            if (!isset($input['subscription_id'])) {
                $this->jsonResponse(['success' => false, 'message' => 'subscription_id requerido'], 400);
                return;
            }
            
            // Campos actualizables
            $updateFields = [];
            $params = [];
            
            $allowedFields = [
                'booking_notifications',
                'payment_notifications', 
                'marketing_notifications',
                'referral_notifications',
                'wishlist_notifications',
                'chat_notifications',
                'quiet_hours_start',
                'quiet_hours_end',
                'max_daily_notifications',
                'min_hours_between'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateFields[] = "{$field} = ?";
                    $params[] = $input[$field];
                }
            }
            
            if (empty($updateFields)) {
                $this->jsonResponse(['success' => false, 'message' => 'No hay campos para actualizar'], 400);
                return;
            }
            
            $params[] = $userId;
            $params[] = $input['subscription_id'];
            
            $result = $this->pushNotification->query(
                "UPDATE user_notification_preferences 
                 SET " . implode(', ', $updateFields) . ", updated_at = NOW()
                 WHERE user_id = ? AND subscription_id = ?",
                $params
            );
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Preferencias actualizadas exitosamente'
            ]);
            
        } catch (Exception $e) {
            error_log('Error updating user preferences: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error actualizando preferencias'], 500);
        }
    }
    
    /**
     * Endpoint para obtener el VAPID public key
     */
    public function getVapidKey()
    {
        // VAPID public key necesaria para suscribirse desde el frontend
        $this->jsonResponse([
            'success' => true,
            'vapid_public_key' => 'BHqG7JzTgQKOZiRfSITfvCOOm2GFqVCGGXGOj-XYGy8_PVJeCv6yJhLGRRGdaT4_ueHH8wDVvYKLyHLz7EiLpBc'
        ]);
    }
    
    // ==================== MÉTODOS ADMIN ====================
    
    /**
     * Panel de administración de notificaciones
     */
    public function adminDashboard()
    {
        if (!Auth::isLoggedIn() || !Auth::isAdmin()) {
            header('Location: ?route=login');
            exit;
        }
        
        try {
            // Obtener estadísticas generales
            $analytics = $this->pushNotification->getAnalytics('30days');
            
            // Suscripciones activas
            $activeSubscriptions = $this->pushNotification->query(
                "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN device_type = 'mobile' THEN 1 ELSE 0 END) as mobile,
                    SUM(CASE WHEN device_type = 'desktop' THEN 1 ELSE 0 END) as desktop,
                    SUM(CASE WHEN device_type = 'tablet' THEN 1 ELSE 0 END) as tablet
                 FROM push_subscriptions 
                 WHERE is_active = TRUE"
            )[0];
            
            // Campañas recientes
            $recentCampaigns = $this->pushNotification->query(
                "SELECT * FROM push_campaigns 
                 ORDER BY created_at DESC 
                 LIMIT 10"
            );
            
            $this->view('admin/push/dashboard', [
                'analytics' => $analytics['success'] ? $analytics['data'] : null,
                'subscriptions' => $activeSubscriptions,
                'recent_campaigns' => $recentCampaigns
            ]);
            
        } catch (Exception $e) {
            error_log('Error in admin push dashboard: ' . $e->getMessage());
            $this->view('admin/push/dashboard', [
                'error' => 'Error cargando dashboard de notificaciones'
            ]);
        }
    }
    
    /**
     * Crear nueva campaña de notificaciones
     */
    public function adminCreateCampaign()
    {
        if (!Auth::isLoggedIn() || !Auth::isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado'], 403);
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                $campaignData = [
                    'name' => $input['name'] ?? '',
                    'description' => $input['description'] ?? '',
                    'type' => $input['type'] ?? 'marketing',
                    'title' => $input['title'] ?? '',
                    'body' => $input['body'] ?? '',
                    'icon' => $input['icon'] ?? '/assets/images/notification-icon.png',
                    'image' => $input['image'] ?? null,
                    'click_action' => $input['click_action'] ?? '/',
                    'actions' => $input['actions'] ?? [],
                    'target_audience' => $input['target_audience'] ?? [],
                    'scheduled_at' => $input['scheduled_at'] ?? null,
                    'timezone' => $input['timezone'] ?? 'America/Guatemala',
                    'created_by' => Auth::getUserId()
                ];
                
                if ($input['send_immediately'] ?? false) {
                    // Enviar inmediatamente
                    $result = $this->pushNotification->sendBulkNotification(
                        null, // Sin template
                        $campaignData['target_audience'],
                        $campaignData
                    );
                } else {
                    // Programar para envío posterior
                    $result = $this->pushNotification->createScheduledCampaign($campaignData);
                }
                
                $this->jsonResponse($result);
                
            } catch (Exception $e) {
                error_log('Error creating push campaign: ' . $e->getMessage());
                $this->jsonResponse(['success' => false, 'message' => 'Error creando campaña'], 500);
            }
        } else {
            // Mostrar formulario de creación
            $templates = $this->pushNotification->query(
                "SELECT * FROM push_templates WHERE is_active = TRUE ORDER BY category, name"
            );
            
            $this->view('admin/push/create_campaign', [
                'templates' => $templates
            ]);
        }
    }
    
    /**
     * Obtener analytics detallados
     */
    public function adminAnalytics()
    {
        if (!Auth::isLoggedIn() || !Auth::isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado'], 403);
            return;
        }
        
        try {
            $dateRange = $_GET['range'] ?? '30days';
            $filters = [
                'device_type' => $_GET['device_type'] ?? null,
                'browser' => $_GET['browser'] ?? null,
                'campaign_id' => $_GET['campaign_id'] ?? null
            ];
            
            // Filtrar valores nulos
            $filters = array_filter($filters);
            
            $analytics = $this->pushNotification->getAnalytics($dateRange, $filters);
            $this->jsonResponse($analytics);
            
        } catch (Exception $e) {
            error_log('Error getting push analytics: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error obteniendo analytics'], 500);
        }
    }
    
    /**
     * Procesar campañas pendientes (endpoint para cron job)
     */
    public function processPendingCampaigns()
    {
        // Verificar que se llama desde localhost o con token de autorización
        if (!$this->isValidCronRequest()) {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado'], 403);
            return;
        }
        
        try {
            $result = $this->pushNotification->processPendingCampaigns();
            $this->jsonResponse($result);
            
        } catch (Exception $e) {
            error_log('Error processing pending campaigns: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error procesando campañas'], 500);
        }
    }
    
    /**
     * Limpiar notificaciones antiguas
     */
    public function cleanupOldNotifications()
    {
        if (!$this->isValidCronRequest()) {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado'], 403);
            return;
        }
        
        try {
            $this->pushNotification->query("CALL CleanupOldNotifications()");
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Limpieza de notificaciones completada'
            ]);
            
        } catch (Exception $e) {
            error_log('Error in cleanup: ' . $e->getMessage());
            $this->jsonResponse(['success' => false, 'message' => 'Error en limpieza'], 500);
        }
    }
    
    // ==================== MÉTODOS PRIVADOS ====================
    
    private function getClientIP()
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    private function isValidCronRequest()
    {
        // Permitir desde localhost
        $allowedIPs = ['127.0.0.1', '::1'];
        if (in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIPs)) {
            return true;
        }
        
        // Verificar token de autorización
        $cronToken = $_SERVER['HTTP_X_CRON_TOKEN'] ?? $_GET['token'] ?? '';
        $validToken = Config::get('CRON_TOKEN', 'mayan-cron-2024');
        
        return $cronToken === $validToken;
    }
    
    private function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}
