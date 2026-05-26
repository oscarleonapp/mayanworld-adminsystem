<?php

namespace App\Helpers;

use App\Core\Database;
use Exception;

/**
 * AuditLogger Helper
 *
 * Sistema de registro de actividad de usuarios admin
 * Registra automáticamente todas las acciones importantes
 */
class AuditLogger
{
    private static $db = null;
    private static $auditColumns = null;
    private static $auditSchema = null;

    /**
     * Inicializar conexión a BD
     */
    private static function getDb()
    {
        if (self::$db === null) {
            self::$db = Database::getInstance();
        }
        return self::$db;
    }

    private static function getAuditColumns(): array
    {
        if (self::$auditColumns !== null) {
            return self::$auditColumns;
        }

        try {
            $db = self::getDb();
            $sql = "SELECT COLUMN_NAME
                    FROM INFORMATION_SCHEMA.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'audit_log'";
            $rows = $db->fetchAll($sql);
            $columns = [];
            foreach ($rows as $row) {
                if (!empty($row['COLUMN_NAME'])) {
                    $columns[$row['COLUMN_NAME']] = true;
                }
            }
            self::$auditColumns = $columns;
            return $columns;
        } catch (Exception $e) {
            error_log('AuditLogger Error loading columns: ' . $e->getMessage());
            self::$auditColumns = [];
            return [];
        }
    }

    private static function getAuditSchema(): array
    {
        if (self::$auditSchema !== null) {
            return self::$auditSchema;
        }

        $columns = self::getAuditColumns();
        $isEnglish = isset($columns['user_id']);

        if ($isEnglish) {
            $usuarioNombre = null;
            if (isset($columns['user_name'])) {
                $usuarioNombre = 'user_name';
            } elseif (isset($columns['user_email'])) {
                $usuarioNombre = 'user_email';
            }

            $schema = [
                'is_english' => true,
                'map' => [
                    'usuario_id' => 'user_id',
                    'usuario_nombre' => $usuarioNombre,
                    'usuario_email' => isset($columns['user_email']) ? 'user_email' : null,
                    'accion' => 'action',
                    'modulo' => 'table_name',
                    'registro_id' => 'record_id',
                    'registro_titulo' => isset($columns['record_title']) ? 'record_title' : null,
                    'datos_anteriores' => 'old_values',
                    'datos_nuevos' => 'new_values'
                ]
            ];
        } else {
            $schema = [
                'is_english' => false,
                'map' => [
                    'usuario_id' => 'usuario_id',
                    'usuario_nombre' => isset($columns['usuario_nombre']) ? 'usuario_nombre' : null,
                    'usuario_email' => isset($columns['usuario_email']) ? 'usuario_email' : null,
                    'accion' => 'accion',
                    'modulo' => 'modulo',
                    'registro_id' => 'registro_id',
                    'registro_titulo' => isset($columns['registro_titulo']) ? 'registro_titulo' : null,
                    'datos_anteriores' => 'datos_anteriores',
                    'datos_nuevos' => 'datos_nuevos'
                ]
            ];
        }

        self::$auditSchema = $schema;
        return $schema;
    }

    private static function normalizeAction(string $accion): string
    {
        $map = [
            'crear' => 'create',
            'editar' => 'update',
            'eliminar' => 'delete',
            'ver' => 'view',
            'login' => 'login',
            'logout' => 'logout'
        ];

        $key = strtolower($accion);
        return $map[$key] ?? $accion;
    }

    /**
     * Registrar una acción en el audit log
     *
     * @param string $accion crear|editar|eliminar|login|logout|ver
     * @param string $modulo tours|reservas|usuarios|configuracion|etc
     * @param int|null $registroId ID del registro afectado
     * @param string|null $registroTitulo Título descriptivo del registro
     * @param array|null $datosAnteriores Datos antes del cambio
     * @param array|null $datosNuevos Datos después del cambio
     */
    public static function log(
        string $accion,
        string $modulo,
        ?int $registroId = null,
        ?string $registroTitulo = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null
    ): bool {
        try {
            $db = self::getDb();
            $columns = self::getAuditColumns();

            // Obtener información del usuario desde la sesión
            $usuarioId = $_SESSION['user_id'] ?? null;
            $usuarioNombre = $_SESSION['user_name'] ?? 'Sistema';
            $usuarioEmail = $_SESSION['user_email'] ?? null;

            // Obtener IP y User Agent
            $ipAddress = self::getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            // Convertir arrays a JSON
            $datosAnterioresJson = $datosAnteriores ? json_encode($datosAnteriores, JSON_UNESCAPED_UNICODE) : null;
            $datosNuevosJson = $datosNuevos ? json_encode($datosNuevos, JSON_UNESCAPED_UNICODE) : null;

            $data = [];

            if (isset($columns['user_id'])) {
                $data = [
                    'user_id' => $usuarioId,
                    'user_name' => $usuarioNombre,
                    'user_email' => $usuarioEmail,
                    'action' => self::normalizeAction($accion),
                    'table_name' => $modulo,
                    'record_id' => $registroId,
                    'record_title' => $registroTitulo,
                    'old_values' => $datosAnterioresJson,
                    'new_values' => $datosNuevosJson,
                    'ip_address' => $ipAddress,
                    'user_agent' => substr($userAgent, 0, 500)
                ];
            } else {
                $data = [
                    'usuario_id' => $usuarioId,
                    'usuario_nombre' => $usuarioNombre,
                    'accion' => $accion,
                    'modulo' => $modulo,
                    'registro_id' => $registroId,
                    'registro_titulo' => $registroTitulo,
                    'datos_anteriores' => $datosAnterioresJson,
                    'datos_nuevos' => $datosNuevosJson,
                    'ip_address' => $ipAddress,
                    'user_agent' => substr($userAgent, 0, 500)
                ];
            }

            // Filtrar columnas que realmente existen en la tabla
            $data = array_filter(
                $data,
                function ($value, $key) use ($columns) {
                    return isset($columns[$key]);
                },
                ARRAY_FILTER_USE_BOTH
            );

            if (empty($data)) {
                return false;
            }

            $fields = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            $sql = "INSERT INTO audit_log ({$fields}) VALUES ({$placeholders})";

            $result = $db->query($sql, $data);

            return $result !== false;

        } catch (Exception $e) {
            // Log error pero no detener ejecución
            error_log('AuditLogger Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener IP del cliente
     */
    private static function getClientIP(): string
    {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Si hay múltiples IPs, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Obtener logs recientes
     *
     * @param int $limit Cantidad de registros
     * @param array $filtros Filtros opcionales
     */
    public static function getRecentLogs(int $limit = 50, array $filtros = []): array
    {
        try {
            $db = self::getDb();
            $schema = self::getAuditSchema();
            $map = $schema['map'];

            $where = [];
            $params = [];

            if (!empty($filtros['usuario_id'])) {
                if (!empty($map['usuario_id'])) {
                    $where[] = "{$map['usuario_id']} = :usuario_id";
                }
                $params['usuario_id'] = $filtros['usuario_id'];
            }

            if (!empty($filtros['modulo'])) {
                if (!empty($map['modulo'])) {
                    $where[] = "{$map['modulo']} = :modulo";
                }
                $params['modulo'] = $filtros['modulo'];
            }

            if (!empty($filtros['accion'])) {
                if (!empty($map['accion'])) {
                    $where[] = "{$map['accion']} = :accion";
                }
                $params['accion'] = $filtros['accion'];
            }

            if (!empty($filtros['fecha_desde'])) {
                $where[] = "created_at >= :fecha_desde";
                $params['fecha_desde'] = $filtros['fecha_desde'];
            }

            if (!empty($filtros['fecha_hasta'])) {
                $where[] = "created_at <= :fecha_hasta";
                $params['fecha_hasta'] = $filtros['fecha_hasta'];
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $sql = "SELECT * FROM audit_log
                    {$whereClause}
                    ORDER BY created_at DESC
                    LIMIT {$limit}";

            return $db->fetchAll($sql, $params);

        } catch (Exception $e) {
            error_log('AuditLogger Error getting logs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener estadísticas de actividad
     */
    public static function getStats(string $periodo = '30days'): array
    {
        try {
            $db = self::getDb();
            $schema = self::getAuditSchema();
            $map = $schema['map'];

            // Calcular fecha desde
            $fechaDesde = match($periodo) {
                '24h' => date('Y-m-d H:i:s', strtotime('-24 hours')),
                '7days' => date('Y-m-d H:i:s', strtotime('-7 days')),
                '30days' => date('Y-m-d H:i:s', strtotime('-30 days')),
                '90days' => date('Y-m-d H:i:s', strtotime('-90 days')),
                default => date('Y-m-d H:i:s', strtotime('-30 days'))
            };

            // Total de acciones
            $totalAcciones = $db->fetch(
                "SELECT COUNT(*) as total FROM audit_log WHERE created_at >= :fecha",
                ['fecha' => $fechaDesde]
            )['total'] ?? 0;

            // Acciones por módulo
            $accionesPorModulo = [];
            if (!empty($map['modulo'])) {
                $accionesPorModulo = $db->fetchAll(
                    "SELECT {$map['modulo']} as modulo, COUNT(*) as total
                     FROM audit_log
                     WHERE created_at >= :fecha
                     GROUP BY {$map['modulo']}
                     ORDER BY total DESC",
                    ['fecha' => $fechaDesde]
                );
            }

            // Usuarios más activos
            $usuariosActivos = [];
            if (!empty($map['usuario_id'])) {
                $usuarioNombreExpr = null;
                if (!empty($map['usuario_nombre'])) {
                    $usuarioNombreExpr = $map['usuario_nombre'];
                } elseif (!empty($map['usuario_email'])) {
                    $usuarioNombreExpr = $map['usuario_email'];
                }

                $nombreSelect = $usuarioNombreExpr ? "{$usuarioNombreExpr} as usuario_nombre" : "{$map['usuario_id']} as usuario_nombre";
                $groupBy = $usuarioNombreExpr ? "{$map['usuario_id']}, {$usuarioNombreExpr}" : $map['usuario_id'];

                $usuariosActivos = $db->fetchAll(
                    "SELECT {$nombreSelect}, COUNT(*) as total
                     FROM audit_log
                     WHERE created_at >= :fecha AND {$map['usuario_id']} IS NOT NULL
                     GROUP BY {$groupBy}
                     ORDER BY total DESC
                     LIMIT 5",
                    ['fecha' => $fechaDesde]
                );
            }

            return [
                'total_acciones' => $totalAcciones,
                'acciones_por_modulo' => $accionesPorModulo,
                'usuarios_activos' => $usuariosActivos,
                'periodo' => $periodo
            ];

        } catch (Exception $e) {
            error_log('AuditLogger Error getting stats: ' . $e->getMessage());
            return [
                'total_acciones' => 0,
                'acciones_por_modulo' => [],
                'usuarios_activos' => [],
                'periodo' => $periodo
            ];
        }
    }

    /**
     * Limpiar logs antiguos
     *
     * @param int $dias Días de antigüedad a mantener
     */
    public static function cleanOldLogs(int $dias = 90): int
    {
        try {
            $db = self::getDb();

            $fechaLimite = date('Y-m-d H:i:s', strtotime("-{$dias} days"));

            $stmt = $db->query(
                "DELETE FROM audit_log WHERE created_at < :fecha_limite",
                ['fecha_limite' => $fechaLimite]
            );

            return $stmt ? $stmt->rowCount() : 0;

        } catch (Exception $e) {
            error_log('AuditLogger Error cleaning logs: ' . $e->getMessage());
            return 0;
        }
    }
}
