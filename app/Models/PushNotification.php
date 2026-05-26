<?php

namespace App\Models;

use App\Core\Model;
use DateTime;
use Exception;

/**
 * Modelo para Sistema de Push Notifications Avanzadas
 * Maneja suscripciones, envío de notificaciones, campañas y analytics
 */
class PushNotification extends Model
{
    protected $table = 'push_notifications';
    
    // Configuración de VAPID keys (en producción usar variables de entorno)
    private const VAPID_PUBLIC_KEY = 'BHqG7JzTgQKOZiRfSITfvCOOm2GFqVCGGXGOj-XYGy8_PVJeCv6yJhLGRRGdaT4_ueHH8wDVvYKLyHLz7EiLpBc';
    private const VAPID_PRIVATE_KEY = 'YqGb7J4TgQKOZiRfSITfvCOOm2GFqVCGGXGOj-XYGy8';
    private const VAPID_SUBJECT = 'mailto:notifications@mayanworldtravelagency.com';
    
    /**
     * Suscribir un dispositivo a push notifications
     */
    public function subscribe($subscriptionData)
    {
        try {
            // Validar datos de suscripción
            if (!$this->validateSubscriptionData($subscriptionData)) {
                return ['success' => false, 'message' => 'Datos de suscripción inválidos'];
            }
            
            // Verificar si ya existe la suscripción
            $existing = $this->findExistingSubscription($subscriptionData['endpoint']);
            
            if ($existing) {
                // Actualizar suscripción existente
                $subscriptionId = $this->updateSubscription($existing['id'], $subscriptionData);
            } else {
                // Crear nueva suscripción
                $subscriptionId = $this->createSubscription($subscriptionData);
            }
            
            if ($subscriptionId) {
                // Crear preferencias por defecto si no existen
                $this->createDefaultPreferences($subscriptionId, $subscriptionData['user_id'] ?? null);
                
                // Enviar notificación de bienvenida
                $this->sendWelcomeNotification($subscriptionId);
                
                return [
                    'success' => true,
                    'subscription_id' => $subscriptionId,
                    'message' => 'Suscripción creada exitosamente'
                ];
            }
            
            return ['success' => false, 'message' => 'Error al crear suscripción'];
            
        } catch (Exception $e) {
            error_log('Error in push subscription: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }
    
    /**
     * Cancelar suscripción de push notifications
     */
    public function unsubscribe($endpoint)
    {
        try {
            $subscription = $this->findExistingSubscription($endpoint);
            
            if (!$subscription) {
                return ['success' => false, 'message' => 'Suscripción no encontrada'];
            }
            
            // Marcar como inactiva en lugar de eliminar (para analytics)
            $updated = $this->query(
                "UPDATE push_subscriptions SET is_active = FALSE, updated_at = NOW() WHERE endpoint = ?",
                [$endpoint]
            );
            
            if ($updated) {
                return ['success' => true, 'message' => 'Suscripción cancelada exitosamente'];
            }
            
            return ['success' => false, 'message' => 'Error al cancelar suscripción'];
            
        } catch (Exception $e) {
            error_log('Error in push unsubscribe: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }
    
    /**
     * Enviar notificación individual
     */
    public function sendNotification($subscriptionId, $notificationData)
    {
        try {
            // Obtener datos de la suscripción
            $subscription = $this->getSubscriptionDetails($subscriptionId);
            
            if (!$subscription || !$subscription['is_active']) {
                return ['success' => false, 'message' => 'Suscripción no válida o inactiva'];
            }
            
            // Verificar preferencias del usuario
            if (!$this->shouldSendNotification($subscriptionId, $notificationData['category'] ?? 'marketing')) {
                return ['success' => false, 'message' => 'Notificación bloqueada por preferencias del usuario'];
            }
            
            // Crear registro de notificación
            $notificationId = $this->createNotificationRecord($subscriptionId, $notificationData);
            
            if (!$notificationId) {
                return ['success' => false, 'message' => 'Error al crear registro de notificación'];
            }
            
            // Preparar payload de la notificación
            $payload = $this->buildNotificationPayload($notificationData);
            
            // Enviar notificación usando Web Push
            $result = $this->sendWebPush($subscription, $payload);
            
            // Actualizar estado de la notificación
            $this->updateNotificationStatus($notificationId, $result);
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'notification_id' => $notificationId,
                    'message' => 'Notificación enviada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al enviar notificación: ' . ($result['error'] ?? 'Unknown error')
                ];
            }
            
        } catch (Exception $e) {
            error_log('Error sending push notification: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }
    
    /**
     * Enviar notificación masiva usando template
     */
    public function sendBulkNotification($templateId, $audienceFilter = [], $variables = [])
    {
        try {
            // Obtener template
            $template = $this->getTemplate($templateId);
            if (!$template) {
                return ['success' => false, 'message' => 'Template no encontrado'];
            }
            
            // Obtener suscripciones que coinciden con el filtro
            $subscriptions = $this->getFilteredSubscriptions($audienceFilter);
            
            if (empty($subscriptions)) {
                return ['success' => false, 'message' => 'No se encontraron suscriptores que coincidan con el filtro'];
            }
            
            // Crear campaña
            $campaignId = $this->createCampaign([
                'name' => 'Bulk Notification ' . date('Y-m-d H:i:s'),
                'type' => 'marketing',
                'title' => $template['title'],
                'body' => $template['body'],
                'recipients_count' => count($subscriptions)
            ]);
            
            $results = [
                'campaign_id' => $campaignId,
                'total_recipients' => count($subscriptions),
                'sent' => 0,
                'failed' => 0,
                'errors' => []
            ];
            
            // Enviar notificaciones en lotes para evitar timeout
            $batchSize = 100;
            $batches = array_chunk($subscriptions, $batchSize);
            
            foreach ($batches as $batch) {
                foreach ($batch as $subscription) {
                    // Personalizar notificación con variables del usuario
                    $personalizedData = $this->personalizeNotification($template, $variables, $subscription);
                    $personalizedData['campaign_id'] = $campaignId;
                    
                    $result = $this->sendNotification($subscription['id'], $personalizedData);
                    
                    if ($result['success']) {
                        $results['sent']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = [
                            'subscription_id' => $subscription['id'],
                            'error' => $result['message']
                        ];
                    }
                    
                    // Pequeña pausa para no saturar el servidor
                    usleep(10000); // 10ms
                }
                
                // Pausa entre lotes
                sleep(1);
            }
            
            // Actualizar estadísticas de la campaña
            $this->updateCampaignStats($campaignId);
            
            return [
                'success' => true,
                'results' => $results,
                'message' => "Campaña completada: {$results['sent']} enviadas, {$results['failed']} fallidas"
            ];
            
        } catch (Exception $e) {
            error_log('Error in bulk notification: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error en envío masivo'];
        }
    }
    
    /**
     * Crear campaña programada
     */
    public function createScheduledCampaign($campaignData)
    {
        try {
            $campaignId = $this->insert('push_campaigns', [
                'name' => $campaignData['name'],
                'description' => $campaignData['description'] ?? null,
                'type' => $campaignData['type'] ?? 'marketing',
                'title' => $campaignData['title'],
                'body' => $campaignData['body'],
                'icon' => $campaignData['icon'] ?? '/assets/images/notification-icon.png',
                'image' => $campaignData['image'] ?? null,
                'click_action' => $campaignData['click_action'] ?? '/',
                'actions' => json_encode($campaignData['actions'] ?? []),
                'target_audience' => json_encode($campaignData['target_audience'] ?? []),
                'scheduled_at' => $campaignData['scheduled_at'] ?? null,
                'timezone' => $campaignData['timezone'] ?? 'America/Guatemala',
                'status' => 'scheduled',
                'created_by' => $campaignData['created_by'] ?? null
            ]);
            
            if ($campaignId) {
                return [
                    'success' => true,
                    'campaign_id' => $campaignId,
                    'message' => 'Campaña programada exitosamente'
                ];
            }
            
            return ['success' => false, 'message' => 'Error al crear campaña'];
            
        } catch (Exception $e) {
            error_log('Error creating scheduled campaign: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno del servidor'];
        }
    }
    
    /**
     * Ejecutar campañas programadas
     */
    public function processPendingCampaigns()
    {
        try {
            $pendingCampaigns = $this->query(
                "SELECT * FROM push_campaigns 
                 WHERE status = 'scheduled' 
                 AND scheduled_at <= NOW() 
                 ORDER BY scheduled_at ASC 
                 LIMIT 10"
            );
            
            $results = [];
            
            foreach ($pendingCampaigns as $campaign) {
                // Marcar como enviando
                $this->query(
                    "UPDATE push_campaigns SET status = 'sending', started_at = NOW() WHERE id = ?",
                    [$campaign['id']]
                );
                
                // Obtener audiencia objetivo
                $targetAudience = json_decode($campaign['target_audience'], true) ?: [];
                
                // Enviar campaña
                $result = $this->sendCampaignNotifications($campaign, $targetAudience);
                
                $results[] = [
                    'campaign_id' => $campaign['id'],
                    'campaign_name' => $campaign['name'],
                    'result' => $result
                ];
                
                // Actualizar estado final
                $finalStatus = $result['success'] ? 'sent' : 'paused';
                $this->query(
                    "UPDATE push_campaigns SET status = ?, completed_at = NOW() WHERE id = ?",
                    [$finalStatus, $campaign['id']]
                );
            }
            
            return [
                'success' => true,
                'processed_campaigns' => count($results),
                'results' => $results
            ];
            
        } catch (Exception $e) {
            error_log('Error processing pending campaigns: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error procesando campañas'];
        }
    }
    
    /**
     * Obtener analytics de notificaciones
     */
    public function getAnalytics($dateRange = '30days', $filters = [])
    {
        try {
            $dateFilter = $this->buildDateFilter($dateRange);
            $additionalFilters = $this->buildAnalyticsFilters($filters);
            
            // Métricas generales
            $overview = $this->query(
                "SELECT 
                    COUNT(*) as total_notifications,
                    SUM(CASE WHEN status IN ('sent', 'delivered', 'clicked') THEN 1 ELSE 0 END) as sent_count,
                    SUM(CASE WHEN status IN ('delivered', 'clicked') THEN 1 ELSE 0 END) as delivered_count,
                    SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as clicked_count,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count,
                    ROUND(AVG(delivery_time_ms), 2) as avg_delivery_time,
                    ROUND(AVG(click_delay_seconds), 2) as avg_click_delay
                 FROM push_notifications 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) {$additionalFilters}",
                [$dateFilter]
            )[0];
            
            // Calcular tasas
            $overview['delivery_rate'] = $overview['sent_count'] > 0 ? 
                round(($overview['delivered_count'] / $overview['sent_count']) * 100, 2) : 0;
            $overview['click_rate'] = $overview['delivered_count'] > 0 ? 
                round(($overview['clicked_count'] / $overview['delivered_count']) * 100, 2) : 0;
            
            // Tendencias diarias
            $dailyTrends = $this->query(
                "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
                    SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as clicked
                 FROM push_notifications 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) {$additionalFilters}
                 GROUP BY DATE(created_at)
                 ORDER BY date DESC
                 LIMIT 30",
                [$dateFilter]
            );
            
            // Performance por dispositivo
            $deviceStats = $this->query(
                "SELECT 
                    ps.device_type,
                    ps.browser,
                    COUNT(pn.id) as notification_count,
                    SUM(CASE WHEN pn.status = 'clicked' THEN 1 ELSE 0 END) as clicks,
                    ROUND(AVG(CASE WHEN pn.status = 'clicked' THEN 1 ELSE 0 END) * 100, 2) as click_rate
                 FROM push_notifications pn
                 JOIN push_subscriptions ps ON pn.subscription_id = ps.id
                 WHERE pn.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) {$additionalFilters}
                 GROUP BY ps.device_type, ps.browser
                 ORDER BY notification_count DESC",
                [$dateFilter]
            );
            
            // Top templates
            $templateStats = $this->query(
                "SELECT 
                    pt.name as template_name,
                    pt.category,
                    COUNT(pn.id) as usage_count,
                    SUM(CASE WHEN pn.status = 'clicked' THEN 1 ELSE 0 END) as clicks,
                    ROUND(AVG(CASE WHEN pn.status = 'clicked' THEN 1 ELSE 0 END) * 100, 2) as click_rate
                 FROM push_notifications pn
                 LEFT JOIN push_templates pt ON JSON_EXTRACT(pn.data, '$.template_id') = pt.id
                 WHERE pn.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) {$additionalFilters}
                 AND pt.id IS NOT NULL
                 GROUP BY pt.id, pt.name, pt.category
                 ORDER BY usage_count DESC
                 LIMIT 10",
                [$dateFilter]
            );
            
            return [
                'success' => true,
                'data' => [
                    'overview' => $overview,
                    'daily_trends' => $dailyTrends,
                    'device_stats' => $deviceStats,
                    'template_stats' => $templateStats,
                    'period' => $dateRange,
                    'generated_at' => date('Y-m-d H:i:s')
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Error getting push analytics: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error obteniendo analytics'];
        }
    }
    
    /**
     * Registrar evento de notificación (click, dismiss, etc.)
     */
    public function trackEvent($subscriptionId, $eventType, $eventData = [])
    {
        try {
            $this->insert('push_events', [
                'subscription_id' => $subscriptionId,
                'event_type' => $eventType,
                'event_data' => json_encode($eventData),
                'notification_id' => $eventData['notification_id'] ?? null,
                'page_url' => $eventData['page_url'] ?? null,
                'session_id' => $eventData['session_id'] ?? null
            ]);
            
            // Actualizar estado de notificación si aplica
            if (isset($eventData['notification_id'])) {
                $this->updateNotificationStatusFromEvent($eventData['notification_id'], $eventType);
            }
            
            return ['success' => true, 'message' => 'Evento registrado'];
            
        } catch (Exception $e) {
            error_log('Error tracking push event: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error registrando evento'];
        }
    }
    
    // ==================== MÉTODOS PRIVADOS ====================
    
    private function validateSubscriptionData($data)
    {
        return isset($data['endpoint']) && 
               isset($data['keys']['p256dh']) && 
               isset($data['keys']['auth']);
    }
    
    private function findExistingSubscription($endpoint)
    {
        $result = $this->query(
            "SELECT * FROM push_subscriptions WHERE endpoint = ? LIMIT 1",
            [$endpoint]
        );
        
        return $result ? $result[0] : null;
    }
    
    private function createSubscription($data)
    {
        return $this->insert('push_subscriptions', [
            'user_id' => $data['user_id'] ?? null,
            'user_email' => $data['user_email'] ?? null,
            'endpoint' => $data['endpoint'],
            'p256dh_key' => $data['keys']['p256dh'],
            'auth_token' => $data['keys']['auth'],
            'device_type' => $this->detectDeviceType($data['user_agent'] ?? ''),
            'browser' => $this->detectBrowser($data['user_agent'] ?? ''),
            'platform' => $data['platform'] ?? null,
            'user_agent' => $data['user_agent'] ?? null,
            'language' => $data['language'] ?? 'es',
            'timezone' => $data['timezone'] ?? 'America/Guatemala'
        ]);
    }
    
    private function updateSubscription($subscriptionId, $data)
    {
        $this->query(
            "UPDATE push_subscriptions SET 
             p256dh_key = ?, auth_token = ?, is_active = TRUE, updated_at = NOW()
             WHERE id = ?",
            [$data['keys']['p256dh'], $data['keys']['auth'], $subscriptionId]
        );
        
        return $subscriptionId;
    }
    
    private function createDefaultPreferences($subscriptionId, $userId)
    {
        // Crear preferencias por defecto si no existen
        $existing = $this->query(
            "SELECT id FROM user_notification_preferences WHERE subscription_id = ?",
            [$subscriptionId]
        );
        
        if (empty($existing)) {
            $this->insert('user_notification_preferences', [
                'user_id' => $userId,
                'subscription_id' => $subscriptionId
            ]);
        }
    }
    
    private function sendWelcomeNotification($subscriptionId)
    {
        $welcomeData = [
            'title' => '¡Bienvenido a Travel Mayan World!',
            'body' => 'Gracias por activar las notificaciones. Te mantendremos informado sobre ofertas exclusivas y tours increíbles.',
            'icon' => '/assets/images/welcome-icon.png',
            'click_action' => '/?route=home',
            'category' => 'welcome'
        ];
        
        // Enviar después de 5 segundos para no interferir con el proceso de suscripción
        $this->scheduleDelayedNotification($subscriptionId, $welcomeData, 5);
    }
    
    private function shouldSendNotification($subscriptionId, $category)
    {
        // Verificar preferencias del usuario
        $preferences = $this->query(
            "SELECT * FROM user_notification_preferences WHERE subscription_id = ? LIMIT 1",
            [$subscriptionId]
        )[0] ?? null;
        
        if (!$preferences) {
            return true; // Si no hay preferencias, permitir por defecto
        }
        
        // Verificar categoría específica
        $categoryField = $category . '_notifications';
        if (isset($preferences[$categoryField]) && !$preferences[$categoryField]) {
            return false;
        }
        
        // Verificar horas silenciosas
        if ($this->isInQuietHours($preferences)) {
            return false;
        }
        
        // Verificar límite diario
        if ($this->exceedsDailyLimit($subscriptionId, $preferences)) {
            return false;
        }
        
        return true;
    }
    
    private function buildNotificationPayload($data)
    {
        return [
            'title' => $data['title'],
            'body' => $data['body'],
            'icon' => $data['icon'] ?? '/assets/images/notification-icon.png',
            'badge' => $data['badge'] ?? '/assets/images/notification-badge.png',
            'image' => $data['image'] ?? null,
            'data' => [
                'url' => $data['click_action'] ?? '/',
                'timestamp' => time(),
                'category' => $data['category'] ?? 'general'
            ],
            'actions' => $data['actions'] ?? [],
            'requireInteraction' => $data['require_interaction'] ?? false,
            'silent' => $data['silent'] ?? false,
            'tag' => $data['tag'] ?? null,
            'vibrate' => json_decode($data['vibrate'] ?? '[200,100,200]')
        ];
    }
    
    private function sendWebPush($subscription, $payload)
    {
        try {
            // Usar biblioteca Web Push PHP para envío real
            // Por ahora simular el envío
            
            $success = true; // Simulated success
            
            if ($success) {
                return [
                    'success' => true,
                    'delivery_time_ms' => rand(100, 500)
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to send push notification'
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function createNotificationRecord($subscriptionId, $data)
    {
        return $this->insert('push_notifications', [
            'subscription_id' => $subscriptionId,
            'campaign_id' => $data['campaign_id'] ?? null,
            'title' => $data['title'],
            'body' => $data['body'],
            'icon' => $data['icon'] ?? '/assets/images/notification-icon.png',
            'badge' => $data['badge'] ?? '/assets/images/notification-badge.png',
            'image' => $data['image'] ?? null,
            'click_action' => $data['click_action'] ?? '/',
            'actions' => json_encode($data['actions'] ?? []),
            'data' => json_encode($data['data'] ?? []),
            'require_interaction' => $data['require_interaction'] ?? false,
            'vibrate' => $data['vibrate'] ?? '[200,100,200]',
            'silent' => $data['silent'] ?? false,
            'tag' => $data['tag'] ?? null,
            'status' => 'pending'
        ]);
    }
    
    private function updateNotificationStatus($notificationId, $result)
    {
        $status = $result['success'] ? 'sent' : 'failed';
        $errorMessage = $result['success'] ? null : ($result['error'] ?? 'Unknown error');
        $deliveryTime = $result['delivery_time_ms'] ?? null;
        
        $this->query(
            "UPDATE push_notifications SET 
             status = ?, 
             sent_at = NOW(), 
             error_message = ?,
             delivery_time_ms = ?,
             updated_at = NOW()
             WHERE id = ?",
            [$status, $errorMessage, $deliveryTime, $notificationId]
        );
    }
    
    private function detectDeviceType($userAgent)
    {
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            if (preg_match('/iPad|Tablet/', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }
        return 'desktop';
    }
    
    private function detectBrowser($userAgent)
    {
        if (preg_match('/Chrome/', $userAgent)) return 'Chrome';
        if (preg_match('/Firefox/', $userAgent)) return 'Firefox';
        if (preg_match('/Safari/', $userAgent)) return 'Safari';
        if (preg_match('/Edge/', $userAgent)) return 'Edge';
        return 'Unknown';
    }
    
    private function scheduleDelayedNotification($subscriptionId, $data, $delaySeconds)
    {
        // En un sistema real, usar un job queue como Redis/RabbitMQ
        // Por ahora simular con un registro en base de datos para procesamiento posterior
        $this->insert('push_notifications', array_merge([
            'subscription_id' => $subscriptionId,
            'status' => 'scheduled',
            'sent_at' => date('Y-m-d H:i:s', time() + $delaySeconds)
        ], $data));
    }
    
    private function isInQuietHours($preferences)
    {
        $currentTime = new DateTime('now', new DateTimeZone($preferences['timezone']));
        $currentHour = (int)$currentTime->format('H');
        
        $quietStart = (int)substr($preferences['quiet_hours_start'], 0, 2);
        $quietEnd = (int)substr($preferences['quiet_hours_end'], 0, 2);
        
        if ($quietStart > $quietEnd) { // Trasnocha (ej: 22:00 - 08:00)
            return $currentHour >= $quietStart || $currentHour < $quietEnd;
        } else { // Normal (ej: 08:00 - 22:00)
            return $currentHour >= $quietStart && $currentHour < $quietEnd;
        }
    }
    
    private function exceedsDailyLimit($subscriptionId, $preferences)
    {
        $todayCount = $this->query(
            "SELECT COUNT(*) as count FROM push_notifications 
             WHERE subscription_id = ? AND DATE(sent_at) = CURDATE() AND status = 'sent'",
            [$subscriptionId]
        )[0]['count'] ?? 0;
        
        return $todayCount >= ($preferences['max_daily_notifications'] ?? 5);
    }
    
    private function buildDateFilter($dateRange)
    {
        switch ($dateRange) {
            case '7days': return 7;
            case '30days': return 30;
            case '90days': return 90;
            case '1year': return 365;
            default: return 30;
        }
    }
    
    private function buildAnalyticsFilters($filters)
    {
        $conditions = [];
        
        if (!empty($filters['device_type'])) {
            $conditions[] = "ps.device_type = '" . $this->escape($filters['device_type']) . "'";
        }
        
        if (!empty($filters['browser'])) {
            $conditions[] = "ps.browser = '" . $this->escape($filters['browser']) . "'";
        }
        
        if (!empty($filters['campaign_id'])) {
            $conditions[] = "pn.campaign_id = " . (int)$filters['campaign_id'];
        }
        
        return empty($conditions) ? '' : ' AND ' . implode(' AND ', $conditions);
    }
}
