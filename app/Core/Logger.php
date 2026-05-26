<?php

namespace App\Core;

use App\Core\Config;

/**
 * Logger - Sistema de logging estructurado
 *
 * Registra eventos, errores y actividad del sistema con niveles de severidad.
 * Los logs se escriben en archivos rotados diariamente.
 *
 * Uso:
 *   Logger::debug('Mensaje de debug', ['contexto' => 'valor']);
 *   Logger::info('Usuario logueado', ['user_id' => 123]);
 *   Logger::warning('Intento de acceso no autorizado', ['ip' => $ip]);
 *   Logger::error('Error en base de datos', ['query' => $sql]);
 *   Logger::critical('Sistema caído', ['error' => $e->getMessage()]);
 */
class Logger
{
    // Niveles de log (PSR-3 compatible)
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
    const CRITICAL = 'critical';

    private static $logPath = null;
    private static $minLevel = self::DEBUG;

    // Jerarquía de niveles (menor a mayor severidad)
    private static $levels = [
        self::DEBUG => 0,
        self::INFO => 1,
        self::WARNING => 2,
        self::ERROR => 3,
        self::CRITICAL => 4
    ];

    /**
     * Inicializar logger
     */
    private static function init()
    {
        if (self::$logPath === null) {
            // Obtener configuración
            $config = Config::loadLocalConfig();
            self::$logPath = $config['LOG_PATH'] ?? __DIR__ . '/../../logs/app.log';

            $configLevel = $config['LOG_LEVEL'] ?? 'debug';
            self::$minLevel = self::$levels[$configLevel] ?? 0;

            // Crear directorio de logs si no existe
            $logDir = dirname(self::$logPath);
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
        }
    }

    /**
     * Escribir log
     */
    private static function log($level, $message, $context = [])
    {
        self::init();

        // Verificar si este nivel debe ser registrado
        $levelValue = self::$levels[$level] ?? 0;
        if ($levelValue < self::$minLevel) {
            return; // No registrar niveles menores al mínimo configurado
        }

        // Solo registrar en producción niveles WARNING o superiores, a menos que se configure DEBUG
        if (!Config::isDevelopment() && $levelValue < self::$levels[self::WARNING] && self::$minLevel < self::$levels[self::WARNING]) {
            return;
        }

        // Preparar mensaje
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);

        // Agregar información de contexto
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';

        // Información de request
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $uri = $_SERVER['REQUEST_URI'] ?? 'N/A';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';

        // Información de usuario si está logueado
        $userId = $_SESSION['user_id'] ?? 'guest';

        // Formato de log
        $logMessage = sprintf(
            "[%s] %s: %s | User: %s | IP: %s | %s %s | UA: %s%s\n",
            $timestamp,
            $levelUpper,
            $message,
            $userId,
            $ip,
            $method,
            $uri,
            substr($userAgent, 0, 100), // Limitar user agent
            $contextStr
        );

        // Escribir en archivo
        $logFile = self::getLogFile();
        error_log($logMessage, 3, $logFile);

        // En desarrollo, también mostrar en consola si es error crítico
        if (Config::isDevelopment() && $levelValue >= self::$levels[self::ERROR]) {
            error_log($logMessage); // PHP error log
        }
    }

    /**
     * Obtener archivo de log (rotación diaria)
     */
    private static function getLogFile()
    {
        $baseFile = self::$logPath;
        $date = date('Y-m-d');

        // Archivo con fecha: app-2025-10-23.log
        $pathInfo = pathinfo($baseFile);
        $logFile = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '-' . $date . '.log';

        // Crear archivo si no existe
        if (!file_exists($logFile)) {
            touch($logFile);
            chmod($logFile, 0644);
        }

        // Limpiar logs antiguos (más de 30 días)
        self::cleanOldLogs($pathInfo['dirname'], $pathInfo['filename']);

        return $logFile;
    }

    /**
     * Limpiar logs antiguos
     */
    private static function cleanOldLogs($logDir, $baseName, $daysToKeep = 30)
    {
        $files = glob($logDir . '/' . $baseName . '-*.log');
        $cutoffTime = time() - ($daysToKeep * 86400);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                @unlink($file);
            }
        }
    }

    /**
     * Métodos públicos por nivel
     */
    public static function debug($message, $context = [])
    {
        self::log(self::DEBUG, $message, $context);
    }

    public static function info($message, $context = [])
    {
        self::log(self::INFO, $message, $context);
    }

    public static function warning($message, $context = [])
    {
        self::log(self::WARNING, $message, $context);
    }

    public static function error($message, $context = [])
    {
        self::log(self::ERROR, $message, $context);
    }

    public static function critical($message, $context = [])
    {
        self::log(self::CRITICAL, $message, $context);
    }

    /**
     * Helpers específicos
     */
    public static function logLogin($userId, $email, $success = true)
    {
        if ($success) {
            self::info('Login exitoso', ['user_id' => $userId, 'email' => $email]);
        } else {
            self::warning('Intento de login fallido', ['email' => $email]);
        }
    }

    public static function logBooking($bookingId, $productId, $userId = null)
    {
        self::info('Nueva reserva creada', [
            'booking_id' => $bookingId,
            'tour_id' => $productId,
            'user_id' => $userId
        ]);
    }

    public static function logPayment($bookingId, $amount, $status)
    {
        self::info('Pago procesado', [
            'booking_id' => $bookingId,
            'amount' => $amount,
            'status' => $status
        ]);
    }

    public static function logException($exception, $context = [])
    {
        self::error('Exception: ' . $exception->getMessage(), [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context
        ]);
    }

    public static function logSecurityEvent($event, $context = [])
    {
        self::warning('Evento de seguridad: ' . $event, $context);
    }

    public static function logDatabaseError($query, $error, $params = [])
    {
        self::error('Database error', [
            'query' => $query,
            'error' => $error,
            'params' => $params
        ]);
    }
}
