<?php

namespace App\Core;

use App\Core\Config;
use App\Core\Logger;
use App\Core\Helpers;
use Exception;

/**
 * RateLimiter - Protección contra abuso y ataques de fuerza bruta
 *
 * Limita la cantidad de solicitudes que puede hacer un usuario/IP
 * en un período de tiempo determinado.
 *
 * Uso:
 *   RateLimiter::check('login', 5, 300); // 5 intentos en 5 minutos
 *   RateLimiter::check('api', 60, 60);   // 60 requests por minuto
 */
class RateLimiter
{
    private static $storageDir = null;

    /**
     * Inicializar directorio de almacenamiento
     */
    private static function init()
    {
        if (self::$storageDir === null) {
            self::$storageDir = dirname(__DIR__, 2) . '/storage/rate_limit';

            if (!is_dir(self::$storageDir)) {
                mkdir(self::$storageDir, 0755, true);
            }
        }
    }

    /**
     * Verificar rate limit
     *
     * @param string $action Acción a limitar (login, api, etc.)
     * @param int $maxRequests Número máximo de requests permitidos
     * @param int $window Ventana de tiempo en segundos
     * @param string|null $identifier Identificador único (por defecto: IP)
     * @return bool True si está permitido, False si excede el límite
     */
    public static function check($action, $maxRequests = 60, $window = 60, $identifier = null)
    {
        self::init();

        // Usar IP como identificador por defecto
        if ($identifier === null) {
            $identifier = self::getClientIP();
        }

        // Crear clave única
        $key = self::generateKey($action, $identifier);
        $filePath = self::$storageDir . '/' . $key . '.json';

        // Obtener datos actuales
        $data = self::load($filePath);

        // Limpiar solicitudes antiguas
        $currentTime = time();
        $data = array_filter($data, function ($timestamp) use ($currentTime, $window) {
            return ($currentTime - $timestamp) < $window;
        });

        // Verificar si excede el límite
        if (count($data) >= $maxRequests) {
            // Loguear intento de rate limit exceeded
            Logger::logSecurityEvent("Rate limit exceeded for $action", [
                'identifier' => $identifier,
                'action' => $action,
                'count' => count($data),
                'max' => $maxRequests,
                'window' => $window
            ]);

            return false;
        }

        // Agregar solicitud actual
        $data[] = $currentTime;

        // Guardar
        self::save($filePath, $data);

        return true;
    }

    /**
     * Obtener número de intentos restantes
     *
     * @param string $action
     * @param int $maxRequests
     * @param int $window
     * @param string|null $identifier
     * @return int Número de intentos restantes
     */
    public static function remaining($action, $maxRequests = 60, $window = 60, $identifier = null)
    {
        self::init();

        if ($identifier === null) {
            $identifier = self::getClientIP();
        }

        $key = self::generateKey($action, $identifier);
        $filePath = self::$storageDir . '/' . $key . '.json';

        $data = self::load($filePath);

        $currentTime = time();
        $data = array_filter($data, function ($timestamp) use ($currentTime, $window) {
            return ($currentTime - $timestamp) < $window;
        });

        return max(0, $maxRequests - count($data));
    }

    /**
     * Obtener tiempo restante hasta que se reinicie el límite
     *
     * @param string $action
     * @param int $window
     * @param string|null $identifier
     * @return int Segundos restantes
     */
    public static function resetIn($action, $window = 60, $identifier = null)
    {
        self::init();

        if ($identifier === null) {
            $identifier = self::getClientIP();
        }

        $key = self::generateKey($action, $identifier);
        $filePath = self::$storageDir . '/' . $key . '.json';

        $data = self::load($filePath);

        if (empty($data)) {
            return 0;
        }

        $oldestRequest = min($data);
        $currentTime = time();

        return max(0, $window - ($currentTime - $oldestRequest));
    }

    /**
     * Limpiar límite para una acción específica
     *
     * @param string $action
     * @param string|null $identifier
     * @return void
     */
    public static function clear($action, $identifier = null)
    {
        self::init();

        if ($identifier === null) {
            $identifier = self::getClientIP();
        }

        $key = self::generateKey($action, $identifier);
        $filePath = self::$storageDir . '/' . $key . '.json';

        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    /**
     * Limpiar todos los archivos antiguos (limpieza de mantenimiento)
     *
     * @param int $olderThan Segundos
     * @return int Número de archivos eliminados
     */
    public static function cleanup($olderThan = 3600)
    {
        self::init();

        $files = glob(self::$storageDir . '/*.json');
        $deleted = 0;
        $cutoffTime = time() - $olderThan;

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Generar clave única para la acción e identificador
     *
     * @param string $action
     * @param string $identifier
     * @return string
     */
    private static function generateKey($action, $identifier)
    {
        return md5($action . ':' . $identifier);
    }

    /**
     * Cargar datos desde archivo
     *
     * @param string $filePath
     * @return array
     */
    private static function load($filePath)
    {
        if (!file_exists($filePath)) {
            return [];
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        return is_array($data) ? $data : [];
    }

    /**
     * Guardar datos en archivo
     *
     * @param string $filePath
     * @param array $data
     * @return void
     */
    private static function save($filePath, $data)
    {
        $content = json_encode($data, JSON_PRETTY_PRINT);
        file_put_contents($filePath, $content, LOCK_EX);
    }

    /**
     * Obtener IP real del cliente (considerando proxies)
     *
     * @return string
     */
    private static function getClientIP()
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy estándar
            'HTTP_X_REAL_IP',            // Nginx
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // Si hay múltiples IPs (X-Forwarded-For), tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }

                // Validar que sea una IP válida
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Middleware para proteger rutas
     *
     * @param string $action
     * @param int $maxRequests
     * @param int $window
     * @return void
     * @throws Exception
     */
    public static function protect($action, $maxRequests = 60, $window = 60)
    {
        if (!self::check($action, $maxRequests, $window)) {
            $resetIn = self::resetIn($action, $window);

            http_response_code(429);
            header('Retry-After: ' . $resetIn);
            header('X-RateLimit-Limit: ' . $maxRequests);
            header('X-RateLimit-Remaining: 0');
            header('X-RateLimit-Reset: ' . (time() + $resetIn));

            if (Helpers::isAjax()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'Demasiadas solicitudes. Por favor, intenta de nuevo en ' . $resetIn . ' segundos.',
                    'retry_after' => $resetIn
                ]);
            } else {
                echo "<h1>429 - Too Many Requests</h1>";
                echo "<p>Has excedido el límite de solicitudes. Por favor, intenta de nuevo en $resetIn segundos.</p>";
            }

            exit;
        }

        // Agregar headers informativos
        $remaining = self::remaining($action, $maxRequests, $window);
        header('X-RateLimit-Limit: ' . $maxRequests);
        header('X-RateLimit-Remaining: ' . $remaining);
    }
}
