<?php

namespace App\Helpers;

use App\Core\Database;
use Exception;

/**
 * SecurityHelper
 * Helper para funciones de seguridad:
 * - Rate limiting
 * - Logging de actividades admin
 * - Validaciones de password
 * - Detección de ataques
 */
class SecurityHelper
{
    private static $db = null;
    private static $logger = null;

    /**
     * Inicializar dependencias
     */
    private static function init()
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        // Logger deshabilitado temporalmente - no existe la clase Logger
        // if (self::$logger === null) {
        //     self::$logger = Logger::getInstance();
        // }
    }

    /**
     * Verificar rate limiting
     * @param string $identifier - IP o user_id
     * @param string $action - 'login', 'register', 'api_call'
     * @param int $maxAttempts - Máximo de intentos permitidos
     * @param int $windowSeconds - Ventana de tiempo en segundos
     * @return bool - true si está permitido, false si excedió el límite
     */
    public static function checkRateLimit($identifier, $action, $maxAttempts = 5, $windowSeconds = 900)
    {
        self::init();

        // Limpiar rate limits expirados
        self::cleanExpiredRateLimits();

        $sql = "SELECT attempts, window_start, blocked_until
                FROM rate_limits
                WHERE identifier = :identifier AND action = :action";

        $record = self::$db->fetch($sql, [
            'identifier' => $identifier,
            'action' => $action
        ]);

        $now = time();

        if ($record) {
            // Si está bloqueado, verificar si ya expiró el bloqueo
            if ($record['blocked_until'] && strtotime($record['blocked_until']) > $now) {
                // Logger deshabilitado temporalmente
                // self::$logger->warning("Rate limit block active", [
                //     'identifier' => $identifier,
                //     'action' => $action,
                //     'blocked_until' => $record['blocked_until']
                // ]);
                return false;
            }

            $windowStart = strtotime($record['window_start']);
            $windowEnd = $windowStart + $windowSeconds;

            // Si estamos dentro de la ventana
            if ($now < $windowEnd) {
                if ($record['attempts'] >= $maxAttempts) {
                    // Bloquear
                    $blockedUntil = date('Y-m-d H:i:s', $now + $windowSeconds);
                    self::$db->update(
                        'rate_limits',
                        ['blocked_until' => $blockedUntil],
                        'identifier = :id AND action = :action',
                        ['id' => $identifier, 'action' => $action]
                    );

                    // Logger deshabilitado temporalmente
                    // self::$logger->warning("Rate limit exceeded, blocking", [
                    //     'identifier' => $identifier,
                    //     'action' => $action,
                    //     'attempts' => $record['attempts'],
                    //     'blocked_until' => $blockedUntil
                    // ]);

                    return false;
                }

                // Incrementar intentos
                self::$db->query(
                    "UPDATE rate_limits SET attempts = attempts + 1 WHERE identifier = :id AND action = :action",
                    ['id' => $identifier, 'action' => $action]
                );

                return true;
            } else {
                // Ventana expirada, reiniciar contador
                self::$db->update(
                    'rate_limits',
                    [
                        'attempts' => 1,
                        'window_start' => date('Y-m-d H:i:s'),
                        'blocked_until' => null
                    ],
                    'identifier = :id AND action = :action',
                    ['id' => $identifier, 'action' => $action]
                );

                return true;
            }
        } else {
            // Primer intento, crear registro
            self::$db->insert('rate_limits', [
                'identifier' => $identifier,
                'action' => $action,
                'attempts' => 1,
                'window_start' => date('Y-m-d H:i:s')
            ]);

            return true;
        }
    }

    /**
     * Limpiar rate limits expirados (más de 24 horas)
     */
    private static function cleanExpiredRateLimits()
    {
        self::init();

        $sql = "DELETE FROM rate_limits
                WHERE window_start < DATE_SUB(NOW(), INTERVAL 24 HOUR)";

        self::$db->query($sql);
    }

    /**
     * Registrar actividad admin
     * @param int $userId - ID del usuario
     * @param string $action - Acción realizada
     * @param string $entityType - Tipo de entidad ('tour', 'booking', etc.)
     * @param int|null $entityId - ID de la entidad
     * @param array $details - Detalles adicionales
     */
    public static function logAdminActivity($userId, $action, $entityType = null, $entityId = null, $details = [])
    {
        self::init();

        $user = self::$db->fetch("SELECT email FROM usuarios WHERE id = :id", ['id' => $userId]);

        self::$db->insert('admin_activity_log', [
            'user_id' => $userId,
            'user_email' => $user ? $user['email'] : 'unknown',
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => json_encode($details),
            'ip_address' => self::getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        // Logger deshabilitado temporalmente
        // self::$logger->info("Admin activity", [
        //     'user_id' => $userId,
        //     'action' => $action,
        //     'entity_type' => $entityType,
        //     'entity_id' => $entityId
        // ]);
    }

    /**
     * Validar fortaleza de contraseña
     * @param string $password
     * @param array $options - Opciones de validación
     * @return array - ['valid' => bool, 'message' => string, 'score' => int]
     */
    public static function validatePasswordStrength($password, $options = [])
    {
        $defaults = [
            'min_length' => 8,
            'require_uppercase' => false,
            'require_lowercase' => false,
            'require_numbers' => false,
            'require_special' => false
        ];

        $opts = array_merge($defaults, $options);

        $score = 0;
        $messages = [];

        // Longitud
        $length = strlen($password);
        if ($length < $opts['min_length']) {
            $messages[] = "Debe tener al menos {$opts['min_length']} caracteres";
        } else {
            $score += min(25, $length * 2);
        }

        // Mayúsculas
        if (preg_match('/[A-Z]/', $password)) {
            $score += 15;
        } elseif ($opts['require_uppercase']) {
            $messages[] = "Debe contener al menos una letra mayúscula";
        }

        // Minúsculas
        if (preg_match('/[a-z]/', $password)) {
            $score += 15;
        } elseif ($opts['require_lowercase']) {
            $messages[] = "Debe contener al menos una letra minúscula";
        }

        // Números
        if (preg_match('/[0-9]/', $password)) {
            $score += 15;
        } elseif ($opts['require_numbers']) {
            $messages[] = "Debe contener al menos un número";
        }

        // Caracteres especiales
        if (preg_match('/[^A-Za-z0-9]/', $password)) {
            $score += 20;
        } elseif ($opts['require_special']) {
            $messages[] = "Debe contener al menos un carácter especial";
        }

        // Variedad de caracteres
        $unique = count(array_unique(str_split($password)));
        if ($unique > $length * 0.6) {
            $score += 10;
        }

        $score = min(100, $score);

        $valid = count($messages) === 0;

        if ($valid) {
            if ($score >= 80) {
                $strength = 'Muy fuerte';
            } elseif ($score >= 60) {
                $strength = 'Fuerte';
            } elseif ($score >= 40) {
                $strength = 'Media';
            } else {
                $strength = 'Débil';
            }
        } else {
            $strength = 'Inválida';
        }

        return [
            'valid' => $valid,
            'score' => $score,
            'strength' => $strength,
            'messages' => $messages,
            'message' => count($messages) > 0 ? implode('. ', $messages) : "Contraseña {$strength}"
        ];
    }

    /**
     * Obtener IP del cliente (considera proxies)
     */
    public static function getClientIP()
    {
        $ip = 'UNKNOWN';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        // Si hay múltiples IPs (proxies), tomar la primera
        if (strpos($ip, ',') !== false) {
            $ips = explode(',', $ip);
            $ip = trim($ips[0]);
        }

        return $ip;
    }

    /**
     * Detectar comportamiento sospechoso
     * @return array - ['suspicious' => bool, 'reasons' => array]
     */
    public static function detectSuspiciousBehavior()
    {
        $suspicious = false;
        $reasons = [];

        // Verificar user agent
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($userAgent) || strlen($userAgent) < 10) {
            $suspicious = true;
            $reasons[] = 'User agent vacío o sospechoso';
        }

        // Detectar bots conocidos maliciosos
        $badBots = ['sqlmap', 'nikto', 'masscan', 'nmap', 'acunetix'];
        foreach ($badBots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                $suspicious = true;
                $reasons[] = "Bot malicioso detectado: {$bot}";
            }
        }

        // Verificar headers sospechosos
        $suspiciousHeaders = ['X-Scanner', 'X-Exploit'];
        foreach ($suspiciousHeaders as $header) {
            if (isset($_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $header))])) {
                $suspicious = true;
                $reasons[] = "Header sospechoso: {$header}";
            }
        }

        return [
            'suspicious' => $suspicious,
            'reasons' => $reasons
        ];
    }

    /**
     * Sanitizar input para prevenir XSS
     * @param mixed $data
     * @return mixed
     */
    public static function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }

        if (is_string($data)) {
            // Eliminar tags HTML
            $data = strip_tags($data);
            // Convertir caracteres especiales
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
            // Eliminar espacios extras
            $data = trim($data);
        }

        return $data;
    }

    /**
     * Verificar si una IP está en lista negra
     * (Para implementar con servicio externo como AbuseIPDB o lista propia)
     */
    public static function isIPBlacklisted($ip)
    {
        // TODO: Implementar verificación contra lista negra
        // Por ahora, solo retornar false
        return false;
    }

    /**
     * Generar token seguro
     * @param int $length
     * @return string
     */
    public static function generateSecureToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }
}
