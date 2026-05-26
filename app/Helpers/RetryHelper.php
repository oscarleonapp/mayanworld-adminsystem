<?php

namespace App\Helpers;

use PDOException;
use RuntimeException;
use Exception;

/**
 * RetryHelper - Sistema de Reintentos con Exponential Backoff (PHP)
 *
 * Proporciona funciones para reintentar operaciones que pueden fallar temporalmente
 * con estrategia de backoff exponencial y jitter.
 *
 * Útil para:
 * - Llamadas a APIs externas
 * - Queries de base de datos
 * - Operaciones de red
 * - File operations con locks
 * - Integración con servicios de terceros (Stripe, WhatsApp, etc.)
 */

class RetryHelper
{
    /**
     * Ejecutar función con reintentos automáticos
     *
     * @param callable $fn - Función a ejecutar
     * @param array $options - Opciones de configuración
     *   - retries: int (default 4) - Número máximo de reintentos
     *   - baseDelay: int (default 300) - Delay base en ms
     *   - maxDelay: int (default 5000) - Delay máximo en ms
     *   - shouldRetry: callable (default null) - Función para determinar si reintentar
     *   - onRetry: callable (default null) - Callback en cada reintento
     * @return mixed - Resultado de la función
     * @throws Exception - Error final si se agotan los reintentos
     *
     * @example
     * $data = RetryHelper::withRetry(function() {
     *     return file_get_contents('https://api.example.com/data');
     * });
     *
     * @example
     * $result = RetryHelper::withRetry(
     *     fn() => $db->query("SELECT * FROM tours"),
     *     [
     *         'retries' => 3,
     *         'onRetry' => fn($attempt, $error) => error_log("Retry $attempt: $error")
     *     ]
     * );
     */
    public static function withRetry(callable $fn, array $options = [])
    {
        $retries = $options['retries'] ?? 4;
        $baseDelay = $options['baseDelay'] ?? 300; // milliseconds
        $maxDelay = $options['maxDelay'] ?? 5000;
        $shouldRetry = $options['shouldRetry'] ?? [self::class, 'defaultShouldRetry'];
        $onRetry = $options['onRetry'] ?? null;

        $attempt = 0;

        while (true) {
            try {
                return $fn();
            } catch (Exception $error) {
                $attempt++;

                // Determinar si el error es retriable
                $retriable = call_user_func($shouldRetry, $error, $attempt);

                // Si no es retriable o se acabaron los intentos, lanzar error
                if (!$retriable || $attempt > $retries) {
                    error_log("[Retry] Failed after $attempt attempts: " . $error->getMessage());
                    throw $error;
                }

                // Calcular delay con exponential backoff + jitter
                $jitter = mt_rand(0, 100);
                $exponentialDelay = $baseDelay * pow(2, $attempt - 1);
                $delay = min($maxDelay, $exponentialDelay) + $jitter;

                error_log("[Retry] Attempt $attempt/$retries failed. Retrying in {$delay}ms... Error: " . $error->getMessage());

                // Callback opcional
                if ($onRetry && is_callable($onRetry)) {
                    call_user_func($onRetry, $attempt, $error, $delay);
                }

                // Esperar antes de reintentar (convertir ms a microsegundos)
                usleep($delay * 1000);
            }
        }
    }

    /**
     * Función por defecto para determinar si un error es retriable
     */
    public static function defaultShouldRetry(Exception $error, int $attempt): bool
    {
        // Errores de conexión de base de datos
        if ($error instanceof PDOException) {
            $errorCode = $error->getCode();

            // MySQL connection errors que son retriables
            $retriableMySQLErrors = [
                '2002', // Connection refused
                '2006', // MySQL server has gone away
                '2013', // Lost connection during query
                'HY000', // General error (puede ser temporal)
            ];

            if (in_array($errorCode, $retriableMySQLErrors)) {
                return true;
            }

            // Lock wait timeout
            if (str_contains($error->getMessage(), 'Lock wait timeout')) {
                return true;
            }

            // Deadlock
            if (str_contains($error->getMessage(), 'Deadlock')) {
                return true;
            }
        }

        // Errores de cURL (APIs externas)
        if ($error instanceof RuntimeException && method_exists($error, 'getCode')) {
            $curlError = $error->getCode();

            // cURL errors retriables
            $retriableCurlErrors = [
                CURLE_OPERATION_TIMEDOUT, // 28 - Timeout
                CURLE_COULDNT_CONNECT, // 7 - Failed to connect
                CURLE_COULDNT_RESOLVE_HOST, // 6 - DNS lookup failed
                CURLE_GOT_NOTHING, // 52 - Empty reply
                CURLE_RECV_ERROR, // 56 - Receive error
                CURLE_SEND_ERROR, // 55 - Send error
            ];

            if (in_array($curlError, $retriableCurlErrors)) {
                return true;
            }
        }

        // Errores de file locking
        if (str_contains($error->getMessage(), 'failed to open stream') ||
            str_contains($error->getMessage(), 'Resource temporarily unavailable')) {
            return true;
        }

        // Por defecto, no reintentar
        return false;
    }

    /**
     * Wrapper para llamadas a APIs externas con retry
     *
     * @example
     * $response = RetryHelper::fetchWithRetry('https://api.stripe.com/v1/charges', [
     *     'method' => 'POST',
     *     'headers' => ['Authorization' => 'Bearer sk_test_xxx'],
     *     'body' => json_encode($data)
     * ]);
     */
    public static function fetchWithRetry(string $url, array $options = [], array $retryOptions = [])
    {
        return self::withRetry(function () use ($url, $options) {
            $context = stream_context_create([
                'http' => [
                    'method' => $options['method'] ?? 'GET',
                    'header' => $options['headers'] ?? [],
                    'content' => $options['body'] ?? null,
                    'timeout' => $options['timeout'] ?? 30,
                    'ignore_errors' => true
                ]
            ]);

            $response = @file_get_contents($url, false, $context);

            if ($response === false) {
                throw new RuntimeException("Failed to fetch: $url");
            }

            // Parse response headers
            $statusLine = $http_response_header[0] ?? '';
            preg_match('/\d{3}/', $statusLine, $matches);
            $statusCode = (int)($matches[0] ?? 0);

            // Check HTTP status
            if ($statusCode >= 400) {
                $error = new RuntimeException("HTTP $statusCode: " . substr($response, 0, 100));
                // Agregar info del status para shouldRetry
                $error->statusCode = $statusCode;
                throw $error;
            }

            return $response;
        }, array_merge([
            'shouldRetry' => function ($error, $attempt) {
                // Reintentar en server errors (5xx) y rate limits (429)
                if (property_exists($error, 'statusCode')) {
                    return $error->statusCode >= 500 || $error->statusCode === 429;
                }
                return self::defaultShouldRetry($error, $attempt);
            }
        ], $retryOptions));
    }

    /**
     * Ejecutar query de base de datos con retry
     *
     * @example
     * $results = RetryHelper::queryWithRetry($db, "SELECT * FROM tours WHERE id = ?", [123]);
     */
    public static function queryWithRetry($db, string $sql, array $params = [], array $retryOptions = [])
    {
        return self::withRetry(function () use ($db, $sql, $params) {
            if (method_exists($db, 'fetchAll')) {
                return $db->fetchAll($sql, $params);
            } elseif (method_exists($db, 'query')) {
                return $db->query($sql, $params);
            } else {
                throw new RuntimeException("Database object doesn't have fetchAll or query method");
            }
        }, array_merge([
            'retries' => 3,
            'baseDelay' => 100
        ], $retryOptions));
    }

    /**
     * Retry con timeout
     *
     * @example
     * $result = RetryHelper::withRetryAndTimeout(
     *     fn() => slow_operation(),
     *     ['timeout' => 5, 'retries' => 3]
     * );
     */
    public static function withRetryAndTimeout(callable $fn, array $options = [])
    {
        $timeout = $options['timeout'] ?? 10; // seconds
        unset($options['timeout']);

        return self::withRetry(function () use ($fn, $timeout) {
            $startTime = time();

            // Set PHP max execution time for this operation
            $oldMaxTime = ini_get('max_execution_time');
            set_time_limit($timeout + 5);

            try {
                $result = $fn();

                // Check if we exceeded timeout
                if (time() - $startTime > $timeout) {
                    throw new RuntimeException("Operation timed out after {$timeout}s");
                }

                return $result;
            } finally {
                // Restore original max execution time
                set_time_limit((int)$oldMaxTime);
            }
        }, $options);
    }

    /**
     * Circuit Breaker simple
     */
    public static function withCircuitBreaker(callable $fn, string $serviceName, array $options = [])
    {
        static $breakers = [];

        if (!isset($breakers[$serviceName])) {
            $breakers[$serviceName] = [
                'failures' => 0,
                'state' => 'closed', // 'closed', 'open', 'half-open'
                'nextAttempt' => time(),
                'threshold' => $options['failureThreshold'] ?? 5,
                'resetTimeout' => $options['resetTimeout'] ?? 60 // seconds
            ];
        }

        $breaker = &$breakers[$serviceName];

        // Si el circuito está abierto
        if ($breaker['state'] === 'open') {
            if (time() < $breaker['nextAttempt']) {
                throw new RuntimeException("Circuit breaker OPEN for service: $serviceName");
            }
            // Intentar medio-abrir
            $breaker['state'] = 'half-open';
        }

        try {
            $result = self::withRetry($fn, $options);
            // Success - cerrar circuito
            $breaker['failures'] = 0;
            $breaker['state'] = 'closed';
            return $result;
        } catch (Exception $error) {
            // Failure - incrementar contador
            $breaker['failures']++;

            if ($breaker['failures'] >= $breaker['threshold']) {
                $breaker['state'] = 'open';
                $breaker['nextAttempt'] = time() + $breaker['resetTimeout'];
                error_log("[Circuit Breaker] OPENED for $serviceName after {$breaker['failures']} failures");
            }

            throw $error;
        }
    }

    /**
     * Helper para medir tiempo de ejecución
     */
    public static function measureExecution(callable $fn): array
    {
        $start = microtime(true);
        $result = $fn();
        $duration = (microtime(true) - $start) * 1000; // ms

        return [
            'result' => $result,
            'duration_ms' => round($duration, 2)
        ];
    }
}
