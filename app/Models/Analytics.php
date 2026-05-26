<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class Analytics extends Model
{
    protected $table = 'user_events';
    protected $fillable = [
        'user_id', 'session_id', 'event_type', 'event_category', 'event_action', 
        'event_label', 'event_value', 'tour_id', 'category_id', 'booking_id',
        'url', 'referrer', 'user_agent', 'ip_address', 'device_type', 
        'browser', 'os', 'country_code', 'city'
    ];
    
    /**
     * Registrar evento de usuario
     */
    public static function trackEvent($eventType, $eventCategory, $eventAction, $data = [])
    {
        $analytics = new self();
        
        // Obtener información del usuario/sesión
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = session_id();
        
        // Preparar datos del evento
        $eventData = [
            'user_id' => $userId,
            'session_id' => $sessionId,
            'event_type' => $eventType,
            'event_category' => $eventCategory,
            'event_action' => $eventAction,
            'event_label' => $data['label'] ?? null,
            'event_value' => $data['value'] ?? null,
            'tour_id' => $data['tour_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'booking_id' => $data['booking_id'] ?? null,
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'referrer' => $_SERVER['HTTP_REFERER'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'ip_address' => self::getUserIP()
        ];
        
        // Detectar device info
        $deviceInfo = self::parseUserAgent($_SERVER['HTTP_USER_AGENT'] ?? '');
        $eventData = array_merge($eventData, $deviceInfo);
        
        // Detectar ubicación geográfica
        $geoInfo = self::getGeoLocation($eventData['ip_address']);
        $eventData = array_merge($eventData, $geoInfo);
        
        try {
            return $analytics->create($eventData);
        } catch (Exception $e) {
            error_log("Analytics tracking error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener métricas del dashboard
     */
    public static function getDashboardMetrics($dateRange = 30)
    {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                COUNT(DISTINCT session_id) as unique_sessions,
                COUNT(DISTINCT user_id) as unique_users,
                COUNT(CASE WHEN event_type = 'page_view' THEN 1 END) as page_views,
                COUNT(CASE WHEN event_type = 'product_view' THEN 1 END) as product_views,
                COUNT(CASE WHEN event_type = 'booking_start' THEN 1 END) as booking_starts,
                COUNT(CASE WHEN event_type = 'booking_complete' THEN 1 END) as booking_completions,
                ROUND(
                    COUNT(CASE WHEN event_type = 'booking_complete' THEN 1 END) / 
                    NULLIF(COUNT(CASE WHEN event_type = 'booking_start' THEN 1 END), 0) * 100, 2
                ) as conversion_rate
            FROM user_events
            WHERE event_timestamp >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ? DAY)
        ";
        
        return $db->fetch($sql, [$dateRange]);
    }
    
    /**
     * Obtener métricas de tours más populares
     */
    public static function getPopularProducts($limit = 10, $dateRange = 30)
    {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                p.id,
                p.nombre,
                p.precio,
                COUNT(ue.id) as views,
                COUNT(r.id) as bookings,
                ROUND(COUNT(r.id) / NULLIF(COUNT(ue.id), 0) * 100, 2) as conversion_rate,
                COALESCE(SUM(r.precio_final), 0) as revenue
            FROM tours p
            LEFT JOIN user_events ue ON p.id = ue.tour_id 
                AND ue.event_type = 'product_view'
                AND ue.event_timestamp >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ? DAY)
            LEFT JOIN reservas r ON p.id = r.tour_id 
                AND r.estado IN ('confirmada', 'pagada')
                AND r.created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ? DAY)
            WHERE p.activo = 1
            GROUP BY p.id
            ORDER BY views DESC, bookings DESC
            LIMIT ?
        ";
        
        return $db->fetchAll($sql, [$dateRange, $dateRange, $limit]);
    }
    
    /**
     * Obtener tendencias temporales
     */
    public static function getTimeSeriesData($metric = 'page_views', $period = 'daily', $days = 30)
    {
        $db = Database::getInstance();
        
        $groupBy = match($period) {
            'hourly' => 'DATE_FORMAT(event_timestamp, "%Y-%m-%d %H:00")',
            'daily' => 'DATE(event_timestamp)',
            'weekly' => 'YEARWEEK(event_timestamp)',
            'monthly' => 'DATE_FORMAT(event_timestamp, "%Y-%m")',
            default => 'DATE(event_timestamp)'
        };
        
        $metricSql = match($metric) {
            'sessions' => 'COUNT(DISTINCT session_id)',
            'users' => 'COUNT(DISTINCT user_id)',
            'page_views' => 'COUNT(CASE WHEN event_type = "page_view" THEN 1 END)',
            'product_views' => 'COUNT(CASE WHEN event_type = "product_view" THEN 1 END)',
            'bookings' => 'COUNT(CASE WHEN event_type = "booking_complete" THEN 1 END)',
            default => 'COUNT(*)'
        };
        
        $sql = "
            SELECT 
                {$groupBy} as period,
                {$metricSql} as value
            FROM user_events
            WHERE event_timestamp >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ? DAY)
            GROUP BY {$groupBy}
            ORDER BY period ASC
        ";
        
        return $db->fetchAll($sql, [$days]);
    }
    
    /**
     * Obtener datos de audiencia
     */
    public static function getAudienceData($dateRange = 30)
    {
        $db = Database::getInstance();
        
        // Device types
        $deviceSql = "
            SELECT 
                COALESCE(device_type, 'unknown') as device,
                COUNT(DISTINCT session_id) as sessions
            FROM user_events
            WHERE event_timestamp >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ? DAY)
            GROUP BY device_type
            ORDER BY sessions DESC
        ";
        
        // Countries
        $countrySql = "
            SELECT 
                COALESCE(country_code, 'unknown') as country,
                COUNT(DISTINCT session_id) as sessions
            FROM user_events
            WHERE event_timestamp >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ? DAY)
            GROUP BY country_code
            ORDER BY sessions DESC
            LIMIT 10
        ";
        
        // Browsers
        $browserSql = "
            SELECT 
                COALESCE(browser, 'unknown') as browser,
                COUNT(DISTINCT session_id) as sessions
            FROM user_events
            WHERE event_timestamp >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ? DAY)
            GROUP BY browser
            ORDER BY sessions DESC
            LIMIT 10
        ";
        
        return [
            'devices' => $db->fetchAll($deviceSql, [$dateRange]),
            'countries' => $db->fetchAll($countrySql, [$dateRange]),
            'browsers' => $db->fetchAll($browserSql, [$dateRange])
        ];
    }
    
    /**
     * Obtener funnel de conversión
     */
    public static function getConversionFunnel($dateRange = 30)
    {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                COUNT(DISTINCT CASE WHEN event_type = 'page_view' THEN session_id END) as visitors,
                COUNT(DISTINCT CASE WHEN event_type = 'product_view' THEN session_id END) as product_viewers,
                COUNT(DISTINCT CASE WHEN event_type = 'booking_start' THEN session_id END) as booking_initiators,
                COUNT(DISTINCT CASE WHEN event_type = 'booking_complete' THEN session_id END) as converters
            FROM user_events
            WHERE event_timestamp >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL ? DAY)
        ";
        
        $data = $db->fetch($sql, [$dateRange]);
        
        // Calculate conversion rates
        $visitors = $data['visitors'] ?: 1;
        
        return [
            'visitors' => $data['visitors'],
            'product_viewers' => $data['product_viewers'],
            'booking_initiators' => $data['booking_initiators'],
            'converters' => $data['converters'],
            'rates' => [
                'visitor_to_product' => round(($data['product_viewers'] / $visitors) * 100, 2),
                'product_to_booking' => round(($data['booking_initiators'] / ($data['product_viewers'] ?: 1)) * 100, 2),
                'booking_to_conversion' => round(($data['converters'] / ($data['booking_initiators'] ?: 1)) * 100, 2),
                'overall_conversion' => round(($data['converters'] / $visitors) * 100, 2)
            ]
        ];
    }
    
    /**
     * Obtener perfil de usuario para recomendaciones
     */
    public static function getUserProfile($userId = null, $sessionId = null)
    {
        if (!$userId && !$sessionId) {
            return null;
        }
        
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM user_profiles WHERE ";
        $params = [];
        
        if ($userId) {
            $sql .= "user_id = ?";
            $params[] = $userId;
        } else {
            $sql .= "session_id = ?";
            $params[] = $sessionId;
        }
        
        return $db->fetch($sql, $params);
    }
    
    /**
     * Actualizar perfil de usuario
     */
    public static function updateUserProfile($userId = null, $sessionId = null)
    {
        if (!$userId && !$sessionId) {
            return false;
        }
        
        $db = Database::getInstance();
        
        // Llamar al stored procedure para calcular métricas
        if ($userId) {
            $db->query("CALL CalculateUserMetrics(?)", [$userId]);
        }
        
        return true;
    }
    
    /**
     * Generar recomendaciones para usuario
     */
    public static function generateRecommendations($userId = null, $sessionId = null, $context = 'homepage', $limit = 6)
    {
        $db = Database::getInstance();
        
        // Obtener perfil del usuario
        $profile = self::getUserProfile($userId, $sessionId);
        
        if (!$profile) {
            // Para usuarios nuevos, recomendar tours populares
            return self::getPopularProductRecommendations($limit);
        }
        
        // Para usuarios con historial, usar recomendaciones personalizadas
        return self::getPersonalizedRecommendations($profile, $context, $limit);
    }
    
    /**
     * Recomendaciones de tours populares (para usuarios nuevos)
     */
    private static function getPopularProductRecommendations($limit = 6)
    {
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                p.id,
                p.nombre,
                p.precio,
                p.imagen_principal,
                pf.total_views,
                pf.conversion_rate,
                'popular' as algorithm_used
            FROM tours p
            JOIN product_features pf ON p.id = pf.tour_id
            WHERE p.activo = 1 AND p.destacado = 1
            ORDER BY pf.total_views DESC, pf.conversion_rate DESC
            LIMIT ?
        ";
        
        return $db->fetchAll($sql, [$limit]);
    }
    
    /**
     * Recomendaciones personalizadas basadas en perfil
     */
    private static function getPersonalizedRecommendations($profile, $context, $limit = 6)
    {
        $db = Database::getInstance();
        
        // Estrategia basada en preferencias del usuario
        $conditions = ["p.activo = 1"];
        $params = [];
        
        // Filtrar por categoría preferida
        if ($profile['preferred_category_id']) {
            $conditions[] = "p.categoria_id = ?";
            $params[] = $profile['preferred_category_id'];
        }
        
        // Filtrar por rango de precio preferido
        if ($profile['preferred_price_range']) {
            switch ($profile['preferred_price_range']) {
                case 'budget':
                    $conditions[] = "p.precio <= 100";
                    break;
                case 'mid-range':
                    $conditions[] = "p.precio BETWEEN 100 AND 300";
                    break;
                case 'luxury':
                    $conditions[] = "p.precio > 300";
                    break;
            }
        }
        
        // Filtrar por dificultad preferida
        if ($profile['preferred_difficulty']) {
            $conditions[] = "p.dificultad = ?";
            $params[] = $profile['preferred_difficulty'];
        }
        
        $whereClause = implode(' AND ', $conditions);
        $params[] = $limit;
        
        $sql = "
            SELECT 
                p.id,
                p.nombre,
                p.precio,
                p.imagen_principal,
                p.categoria_id,
                p.dificultad,
                pf.avg_rating,
                pf.conversion_rate,
                'personalized' as algorithm_used
            FROM tours p
            LEFT JOIN product_features pf ON p.id = pf.tour_id
            WHERE {$whereClause}
            ORDER BY pf.avg_rating DESC, pf.conversion_rate DESC, RAND()
            LIMIT ?
        ";
        
        $recommendations = $db->fetchAll($sql, $params);
        
        // Si no hay suficientes recomendaciones personalizadas, complementar con populares
        if (count($recommendations) < $limit) {
            $remaining = $limit - count($recommendations);
            $popularRecommendations = self::getPopularProductRecommendations($remaining);
            $recommendations = array_merge($recommendations, $popularRecommendations);
        }
        
        return array_slice($recommendations, 0, $limit);
    }
    
    /**
     * Guardar recomendaciones generadas
     */
    public static function saveRecommendations($userId, $sessionId, $context, $recommendations, $algorithm = 'hybrid')
    {
        $db = Database::getInstance();
        
        $recommendedProducts = json_encode(array_map(function($rec) {
            return [
                'tour_id' => $rec['id'],
                'score' => $rec['conversion_rate'] ?? 0,
                'reason' => $rec['algorithm_used'] ?? 'unknown'
            ];
        }, $recommendations));
        
        $sql = "
            INSERT INTO recommendations 
            (user_id, session_id, context_type, recommended_products, algorithm_used, products_shown)
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        
        return $db->query($sql, [
            $userId, 
            $sessionId, 
            $context, 
            $recommendedProducts, 
            $algorithm, 
            count($recommendations)
        ]);
    }
    
    /**
     * Parsear user agent para extraer información del dispositivo
     */
    private static function parseUserAgent($userAgent)
    {
        $deviceType = 'desktop';
        $browser = 'unknown';
        $os = 'unknown';
        
        // Detectar device type
        if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
            if (preg_match('/iPad/', $userAgent)) {
                $deviceType = 'tablet';
            } else {
                $deviceType = 'mobile';
            }
        }
        
        // Detectar browser
        if (preg_match('/Chrome/', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox/', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari/', $userAgent) && !preg_match('/Chrome/', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Edge/', $userAgent)) {
            $browser = 'Edge';
        }
        
        // Detectar OS
        if (preg_match('/Windows/', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Macintosh|Mac OS X/', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Linux/', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/iPhone|iPad/', $userAgent)) {
            $os = 'iOS';
        }
        
        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'os' => $os
        ];
    }
    
    /**
     * Obtener IP del usuario
     */
    private static function getUserIP()
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Obtener información geográfica por IP
     */
    private static function getGeoLocation($ip)
    {
        // Para desarrollo local
        if ($ip === '127.0.0.1' || strpos($ip, '192.168.') === 0) {
            return ['country_code' => 'GT', 'city' => 'Guatemala City'];
        }
        
        // Usar servicio gratuito para geolocalización
        try {
            $context = stream_context_create([
                'http' => ['timeout' => 3]
            ]);
            
            $response = @file_get_contents("https://ip-api.com/json/{$ip}?fields=countryCode,city", false, $context);
            
            if ($response) {
                $data = json_decode($response, true);
                return [
                    'country_code' => $data['countryCode'] ?? null,
                    'city' => $data['city'] ?? null
                ];
            }
        } catch (Exception $e) {
            // Silently fail
        }
        
        return ['country_code' => null, 'city' => null];
    }
}