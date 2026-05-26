<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Core\Config;
use App\Helpers\AuditLogger;
use Exception;

class AuditLogController extends BaseController
{
    private $auditSchemaCache = null;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    private function getAuditSchema(): array
    {
        if ($this->auditSchemaCache !== null) {
            return $this->auditSchemaCache;
        }

        $isEnglish = $this->db->columnExists('audit_log', 'user_id');
        $map = $isEnglish
            ? [
                'usuario_id' => 'user_id',
                'usuario_nombre' => 'user_name',
                'usuario_email' => 'user_email',
                'accion' => 'action',
                'modulo' => 'table_name',
                'registro_id' => 'record_id',
                'registro_titulo' => 'record_title',
                'datos_anteriores' => 'old_values',
                'datos_nuevos' => 'new_values'
            ]
            : [
                'usuario_id' => 'usuario_id',
                'usuario_nombre' => 'usuario_nombre',
                'usuario_email' => 'usuario_email',
                'accion' => 'accion',
                'modulo' => 'modulo',
                'registro_id' => 'registro_id',
                'registro_titulo' => 'registro_titulo',
                'datos_anteriores' => 'datos_anteriores',
                'datos_nuevos' => 'datos_nuevos'
            ];

        $this->auditSchemaCache = [
            'is_english' => $isEnglish,
            'map' => $map
        ];

        return $this->auditSchemaCache;
    }

    private function normalizeActionFilter(?string $accion, bool $isEnglish): ?string
    {
        if (!$accion || !$isEnglish) {
            return $accion;
        }

        $map = [
            'crear' => 'create',
            'editar' => 'update',
            'eliminar' => 'delete',
            'ver' => 'view',
            'login' => 'login',
            'logout' => 'logout'
        ];

        return $map[$accion] ?? $accion;
    }

    private function actionSelectExpression(string $column, bool $isEnglish): string
    {
        if (!$isEnglish) {
            return $column;
        }

        return "CASE {$column}
            WHEN 'create' THEN 'crear'
            WHEN 'update' THEN 'editar'
            WHEN 'delete' THEN 'eliminar'
            WHEN 'view' THEN 'ver'
            ELSE {$column}
        END";
    }

    private function buildAuditSelect(array $map, bool $isEnglish): string
    {
        $baseColumns = ['id', 'created_at', 'ip_address', 'user_agent'];
        $selectParts = $baseColumns;

        foreach ($map as $alias => $column) {
            if ($this->db->columnExists('audit_log', $column)) {
                $expr = $column;
                if ($alias === 'accion') {
                    $expr = $this->actionSelectExpression($column, $isEnglish);
                }
                $selectParts[] = "{$expr} AS {$alias}";
            } else {
                $selectParts[] = "NULL AS {$alias}";
            }
        }

        return implode(",\n                    ", $selectParts);
    }

    /**
     * Listar logs con filtros
     */
    public function index()
    {
        $schema = $this->getAuditSchema();
        $map = $schema['map'];
        $isEnglish = $schema['is_english'];

        $page = (int)$this->getInput('page', 1);
        $usuarioId = $this->getInput('usuario_id');
        $modulo = $this->getInput('modulo');
        $accion = $this->getInput('accion');
        $fechaDesde = $this->getInput('fecha_desde');
        $fechaHasta = $this->getInput('fecha_hasta');
        $search = $this->getInput('search');

        // Construir query con filtros
        $where = [];
        $params = [];

        if ($usuarioId) {
            $where[] = "{$map['usuario_id']} = :usuario_id";
            $params['usuario_id'] = $usuarioId;
        }

        if ($modulo) {
            $where[] = "{$map['modulo']} = :modulo";
            $params['modulo'] = $modulo;
        }

        if ($accion) {
            $where[] = "{$map['accion']} = :accion";
            $params['accion'] = $this->normalizeActionFilter($accion, $isEnglish);
        }

        if ($fechaDesde) {
            $where[] = "DATE(created_at) >= :fecha_desde";
            $params['fecha_desde'] = $fechaDesde;
        }

        if ($fechaHasta) {
            $where[] = "DATE(created_at) <= :fecha_hasta";
            $params['fecha_hasta'] = $fechaHasta;
        }

        if ($search) {
            $searchParts = [];
            if ($this->db->columnExists('audit_log', $map['usuario_nombre'])) {
                $searchParts[] = "{$map['usuario_nombre']} LIKE :search";
            }
            if ($this->db->columnExists('audit_log', $map['registro_titulo'])) {
                $searchParts[] = "{$map['registro_titulo']} LIKE :search";
            }
            $searchParts[] = "ip_address LIKE :search";
            $where[] = '(' . implode(' OR ', $searchParts) . ')';
            $params['search'] = "%{$search}%";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Paginación
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        // Obtener logs
        $select = $this->buildAuditSelect($map, $isEnglish);
        $sql = "SELECT
                    {$select}
                FROM audit_log
                {$whereClause}
                ORDER BY created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $logs = $this->db->fetchAll($sql, $params);

        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM audit_log {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];
        $totalPages = ceil($total / $perPage);

        // Obtener usuarios únicos para filtro
        $usuarios = [];
        $usuarioNombreCol = $map['usuario_nombre'] ?? null;
        if ($usuarioNombreCol && $this->db->columnExists('audit_log', $usuarioNombreCol)) {
            $usuarios = $this->db->fetchAll(
                "SELECT DISTINCT {$map['usuario_id']} as id, {$usuarioNombreCol} as nombre
                 FROM audit_log
                 WHERE {$map['usuario_id']} IS NOT NULL
                 ORDER BY {$usuarioNombreCol}"
            );
        } else {
            $usuarioEmailCol = $map['usuario_email'] ?? null;
            $nombreExpr = $usuarioEmailCol && $this->db->columnExists('audit_log', $usuarioEmailCol)
                ? "COALESCE(u.nombre, al.{$usuarioEmailCol})"
                : "u.nombre";

            $usuarios = $this->db->fetchAll(
                "SELECT DISTINCT al.{$map['usuario_id']} as id, {$nombreExpr} as nombre
                 FROM audit_log al
                 LEFT JOIN usuarios u ON al.{$map['usuario_id']} = u.id
                 WHERE al.{$map['usuario_id']} IS NOT NULL
                 ORDER BY nombre"
            );
        }

        // Obtener módulos únicos para filtro
        $modulos = $this->db->fetchAll(
            "SELECT DISTINCT {$map['modulo']} as modulo FROM audit_log ORDER BY {$map['modulo']}"
        );

        // Obtener acciones únicas para filtro
        $acciones = $this->db->fetchAll(
            "SELECT DISTINCT {$map['accion']} as accion FROM audit_log ORDER BY {$map['accion']}"
        );

        // Estadísticas rápidas
        $ultimas24h = $this->db->fetch(
            "SELECT COUNT(*) as total FROM audit_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)"
        )['total'] ?? 0;
        $total30Dias = $this->db->fetch(
            "SELECT COUNT(*) as total FROM audit_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        )['total'] ?? 0;
        $stats = [
            'total' => $total,
            'ultimas_24h' => $ultimas24h,
            'usuarios_activos' => $this->db->fetch(
                "SELECT COUNT(DISTINCT {$map['usuario_id']}) as total FROM audit_log WHERE {$map['usuario_id']} IS NOT NULL"
            )['total'] ?? 0,
            'promedio_dia' => $total30Dias > 0 ? round($total30Dias / 30, 1) : 0
        ];

        $this->view('admin/audit_log/index', [
            'title' => 'Registro de Auditoría',
            'logs' => $logs,
            'usuarios' => $usuarios,
            'modulos' => array_column($modulos, 'modulo'),
            'acciones' => array_column($acciones, 'accion'),
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'paginaActual' => $page,
            'totalPaginas' => $totalPages,
            'total' => $total,
            'filters' => [
                'usuario_id' => $usuarioId,
                'modulo' => $modulo,
                'accion' => $accion,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'search' => $search
            ],
            'filtros' => [
                'usuario_id' => $usuarioId,
                'modulo' => $modulo,
                'accion' => $accion,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'search' => $search
            ]
        ]);
    }

    /**
     * Ver detalle de un log específico
     */
    public function viewLog($id)
    {
        if (!$id) {
            $this->redirect('admin/audit-log', 'ID de log requerido', 'error');
            return;
        }

        $schema = $this->getAuditSchema();
        $map = $schema['map'];
        $isEnglish = $schema['is_english'];
        $select = $this->buildAuditSelect($map, $isEnglish);
        $log = $this->db->fetch("SELECT {$select} FROM audit_log WHERE id = :id", ['id' => $id]);

        if (!$log) {
            $this->redirect('admin/audit-log', 'Log no encontrado', 'error');
            return;
        }

        $this->json($log);
    }

    /**
     * Obtener estadísticas para el dashboard - AJAX
     */
    public function getStats()
    {
        $schema = $this->getAuditSchema();
        $map = $schema['map'];

        $periodo = $this->getInput('periodo', '30days');

        $stats = AuditLogger::getStats($periodo);

        // Actividad por día (últimos 7 días)
        $actividadDiaria = $this->db->fetchAll(
            "SELECT DATE(created_at) as fecha, COUNT(*) as total
             FROM audit_log
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY DATE(created_at)
             ORDER BY fecha DESC"
        );

        // Top 5 acciones más comunes
        $topAcciones = $this->db->fetchAll(
            "SELECT {$map['accion']} as accion, {$map['modulo']} as modulo, COUNT(*) as total
             FROM audit_log
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY {$map['accion']}, {$map['modulo']}
             ORDER BY total DESC
             LIMIT 5"
        );

        $this->json([
            'success' => true,
            'stats' => $stats,
            'actividad_diaria' => $actividadDiaria,
            'top_acciones' => $topAcciones
        ]);
    }

    /**
     * Exportar logs a CSV
     */
    public function exportCSV()
    {
        $schema = $this->getAuditSchema();
        $map = $schema['map'];
        $isEnglish = $schema['is_english'];

        $usuarioId = $this->getInput('usuario_id');
        $modulo = $this->getInput('modulo');
        $accion = $this->getInput('accion');
        $fechaDesde = $this->getInput('fecha_desde');
        $fechaHasta = $this->getInput('fecha_hasta');

        // Construir query con filtros
        $where = [];
        $params = [];

        if ($usuarioId) {
            $where[] = "{$map['usuario_id']} = :usuario_id";
            $params['usuario_id'] = $usuarioId;
        }

        if ($modulo) {
            $where[] = "{$map['modulo']} = :modulo";
            $params['modulo'] = $modulo;
        }

        if ($accion) {
            $where[] = "{$map['accion']} = :accion";
            $params['accion'] = $this->normalizeActionFilter($accion, $isEnglish);
        }

        if ($fechaDesde) {
            $where[] = "DATE(created_at) >= :fecha_desde";
            $params['fecha_desde'] = $fechaDesde;
        }

        if ($fechaHasta) {
            $where[] = "DATE(created_at) <= :fecha_hasta";
            $params['fecha_hasta'] = $fechaHasta;
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Obtener todos los logs (sin límite)
        $select = $this->buildAuditSelect($map, $isEnglish);
        $sql = "SELECT
                    {$select}
                FROM audit_log
                {$whereClause}
                ORDER BY created_at DESC
                LIMIT 5000"; // Límite de seguridad

        $logs = $this->db->fetchAll($sql, $params);

        // Generar CSV
        $filename = 'audit_log_' . date('Y-m-d_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');

        // BOM para UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Encabezados
        fputcsv($output, [
            'ID',
            'Usuario',
            'Acción',
            'Módulo',
            'Registro ID',
            'Registro',
            'IP',
            'Fecha'
        ]);

        // Datos
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['usuario_nombre'],
                $log['accion'],
                $log['modulo'],
                $log['registro_id'] ?? '',
                $log['registro_titulo'] ?? '',
                $log['ip_address'],
                $log['created_at']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Limpiar logs antiguos
     */
    public function clean()
    {
        $this->validateCsrf();

        // Solo admin principal puede ejecutar limpieza
        if (!$this->can('admin_advanced')) {
            $this->forbidden('No tienes permisos para esta acción');
            return;
        }

        $dias = (int)$this->getInput('dias', 90);

        if ($dias < 30 || $dias > 365) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Días inválidos (mín: 30, máx: 365)'], 400);
            }
            $this->redirect('admin/audit-log', 'Días inválidos (mín: 30, máx: 365)', 'error');
            return;
        }

        try {
            $count = AuditLogger::cleanOldLogs($dias);

            if (Helpers::isAjax()) {
                $this->json(['success' => true, 'eliminados' => $count]);
            }

            $this->redirect(
                'admin/audit-log',
                "Se eliminaron {$count} logs antiguos (más de {$dias} días)",
                'success'
            );

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error al limpiar logs'], 500);
            }
            $this->redirect('admin/audit-log', 'Error al limpiar logs', 'error');
        }
    }

    /**
     * Dashboard de auditoría con gráficos
     */
    public function dashboard()
    {
        $schema = $this->getAuditSchema();
        $map = $schema['map'];

        // Estadísticas generales
        $stats = [
            'total_logs' => $this->db->fetch("SELECT COUNT(*) as total FROM audit_log")['total'],
            'today' => $this->db->fetch(
                "SELECT COUNT(*) as total FROM audit_log WHERE DATE(created_at) = CURDATE()"
            )['total'],
            'this_week' => $this->db->fetch(
                "SELECT COUNT(*) as total FROM audit_log WHERE YEARWEEK(created_at) = YEARWEEK(NOW())"
            )['total'],
            'this_month' => $this->db->fetch(
                "SELECT COUNT(*) as total FROM audit_log WHERE YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())"
            )['total']
        ];

        // Actividad por día (últimos 30 días)
        $actividadDiaria = $this->db->fetchAll(
            "SELECT DATE(created_at) as fecha, COUNT(*) as total
             FROM audit_log
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(created_at)
             ORDER BY fecha ASC"
        );

        // Acciones más comunes
        $topAcciones = $this->db->fetchAll(
            "SELECT {$map['accion']} as accion, COUNT(*) as total
             FROM audit_log
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY {$map['accion']}
             ORDER BY total DESC
             LIMIT 5"
        );

        // Módulos más activos
        $topModulos = $this->db->fetchAll(
            "SELECT {$map['modulo']} as modulo, COUNT(*) as total
             FROM audit_log
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY {$map['modulo']}
             ORDER BY total DESC
             LIMIT 5"
        );

        // Usuarios más activos
        $topUsuarios = $this->db->fetchAll(
            "SELECT {$map['usuario_nombre']} as usuario_nombre, COUNT(*) as total
             FROM audit_log
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND {$map['usuario_id']} IS NOT NULL
             GROUP BY {$map['usuario_id']}, {$map['usuario_nombre']}
             ORDER BY total DESC
             LIMIT 5"
        );

        // Actividad por hora del día
        $actividadPorHora = $this->db->fetchAll(
            "SELECT HOUR(created_at) as hora, COUNT(*) as total
             FROM audit_log
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY HOUR(created_at)
             ORDER BY hora ASC"
        );

        $this->view('admin/audit_log/dashboard', [
            'title' => 'Dashboard de Auditoría',
            'stats' => $stats,
            'actividad_diaria' => $actividadDiaria,
            'top_acciones' => $topAcciones,
            'top_modulos' => $topModulos,
            'top_usuarios' => $topUsuarios,
            'actividad_por_hora' => $actividadPorHora
        ]);
    }

    /**
     * Buscar en logs - AJAX
     */
    public function search()
    {
        $query = $this->getInput('q');
        $limit = (int)$this->getInput('limit', 20);
        $limit = $limit > 0 ? min($limit, 100) : 20;

        if (!$query || strlen($query) < 2) {
            $this->json(['success' => false, 'message' => 'Query muy corta'], 400);
            return;
        }

        $schema = $this->getAuditSchema();
        $map = $schema['map'];
        $isEnglish = $schema['is_english'];

        $searchParts = [];
        if (!empty($map['usuario_nombre']) && $this->db->columnExists('audit_log', $map['usuario_nombre'])) {
            $searchParts[] = "{$map['usuario_nombre']} LIKE :query";
        } elseif (!empty($map['usuario_email']) && $this->db->columnExists('audit_log', $map['usuario_email'])) {
            $searchParts[] = "{$map['usuario_email']} LIKE :query";
        }
        if (!empty($map['registro_titulo']) && $this->db->columnExists('audit_log', $map['registro_titulo'])) {
            $searchParts[] = "{$map['registro_titulo']} LIKE :query";
        }
        if (!empty($map['modulo']) && $this->db->columnExists('audit_log', $map['modulo'])) {
            $searchParts[] = "{$map['modulo']} LIKE :query";
        }
        $searchParts[] = "ip_address LIKE :query";

        if (empty($searchParts)) {
            $this->json(['success' => true, 'logs' => [], 'total' => 0]);
            return;
        }

        $select = $this->buildAuditSelect($map, $isEnglish);
        $sql = "SELECT {$select} FROM audit_log
                WHERE " . implode(' OR ', $searchParts) . "
                ORDER BY created_at DESC
                LIMIT {$limit}";

        $logs = $this->db->fetchAll($sql, [
            'query' => "%{$query}%"
        ]);

        $this->json([
            'success' => true,
            'logs' => $logs,
            'total' => count($logs)
        ]);
    }

    /**
     * Obtener actividad de un usuario específico
     */
    public function userActivity($usuarioId)
    {
        if (!$usuarioId) {
            $this->redirect('admin/audit-log', 'ID de usuario requerido', 'error');
            return;
        }

        $usuario = $this->db->fetch(
            "SELECT id, nombre, email, tipo FROM usuarios WHERE id = :id",
            ['id' => $usuarioId]
        );

        if (!$usuario) {
            $this->redirect('admin/audit-log', 'Usuario no encontrado', 'error');
            return;
        }

        $page = (int)$this->getInput('page', 1);
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $schema = $this->getAuditSchema();
        $map = $schema['map'];
        $isEnglish = $schema['is_english'];
        $select = $this->buildAuditSelect($map, $isEnglish);

        // Obtener logs del usuario
        $logs = $this->db->fetchAll(
            "SELECT {$select} FROM audit_log
             WHERE {$map['usuario_id']} = :usuario_id
             ORDER BY created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            ['usuario_id' => $usuarioId]
        );

        // Contar total
        $total = $this->db->fetch(
            "SELECT COUNT(*) as total FROM audit_log WHERE {$map['usuario_id']} = :usuario_id",
            ['usuario_id' => $usuarioId]
        )['total'];
        $totalPages = ceil($total / $perPage);

        // Estadísticas del usuario
        $userStats = [
            'total_acciones' => $total,
            'primera_accion' => $this->db->fetch(
                "SELECT created_at FROM audit_log WHERE {$map['usuario_id']} = :usuario_id ORDER BY created_at ASC LIMIT 1",
                ['usuario_id' => $usuarioId]
            )['created_at'] ?? null,
            'ultima_accion' => $this->db->fetch(
                "SELECT created_at FROM audit_log WHERE {$map['usuario_id']} = :usuario_id ORDER BY created_at DESC LIMIT 1",
                ['usuario_id' => $usuarioId]
            )['created_at'] ?? null,
            'modulos_mas_usados' => $this->db->fetchAll(
                "SELECT {$map['modulo']} as modulo, COUNT(*) as total
                 FROM audit_log
                 WHERE {$map['usuario_id']} = :usuario_id
                 GROUP BY {$map['modulo']}
                 ORDER BY total DESC
                 LIMIT 5",
                ['usuario_id' => $usuarioId]
            )
        ];

        $this->view('admin/audit_log/user_activity', [
            'title' => 'Actividad de Usuario - ' . $usuario['nombre'],
            'usuario' => $usuario,
            'logs' => $logs,
            'stats' => $userStats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total
        ]);
    }
}
