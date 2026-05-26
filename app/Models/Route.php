<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Helpers;
use App\Core\Config;
use Exception;

class Route extends Model
{
    protected $table = 'rutas';
    protected $fillable = [
        'nombre', 'imagen', 'origen', 'destino', 'descripcion', 'distancia_km',
        'duracion_estimada', 'duracion_horas', 'precio', 'dias_operacion', 'horarios',
        'paradas_intermedias', 'transporte_id', 'conductor_id',
        'requisitos', 'notas_importantes', 'estado', 'activo'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ensureImagenColumnExists();
    }

    private function ensureImagenColumnExists()
    {
        try {
            if (!$this->db->columnExists($this->table, 'imagen')) {
                $this->db->query("ALTER TABLE {$this->table} ADD COLUMN imagen VARCHAR(255) NULL COMMENT 'Ruta de la imagen de la ruta' AFTER nombre");
            }
        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                throw $e;
            }
            error_log('No se pudo agregar columna imagen a rutas: ' . $e->getMessage());
        }
    }

    // Estados de ruta
    const STATUS_ACTIVE = 'activo';
    const STATUS_INACTIVE = 'inactivo';
    const STATUS_MAINTENANCE = 'mantenimiento';

    // Obtener rutas con información completa
    public function getAllWithDetails()
    {
        $sql = "
            SELECT r.*,
                   t.nombre as transporte_nombre,
                   t.tipo as transporte_tipo,
                   t.capacidad as transporte_capacidad,
                   CONCAT(e.nombre, ' ', e.apellido) as conductor_nombre,
                   e.foto as conductor_foto
            FROM {$this->table} r
            LEFT JOIN transportes t ON r.transporte_id = t.id
            LEFT JOIN empleados e ON r.conductor_id = e.id
            ORDER BY r.origen, r.destino
        ";

        return $this->db->fetchAll($sql);
    }

    // Obtener rutas activas
    public function getActiveRoutes()
    {
        $sql = "
            SELECT r.*,
                   t.nombre as transporte_nombre,
                   CONCAT(e.nombre, ' ', e.apellido) as conductor_nombre,
                   e.foto as conductor_foto
            FROM {$this->table} r
            LEFT JOIN transportes t ON r.transporte_id = t.id
            LEFT JOIN empleados e ON r.conductor_id = e.id
            WHERE r.activo = 1
            ORDER BY r.precio ASC
        ";

        return $this->db->fetchAll($sql, []);
    }

    // Buscar rutas por origen y destino
    public function searchRoutes($origen = null, $destino = null, $fecha = null)
    {
        $sql = "
            SELECT r.*,
                   t.nombre as transporte_nombre,
                   t.capacidad as transporte_capacidad,
                   CONCAT(e.nombre, ' ', e.apellido) as conductor_nombre,
                   e.foto as conductor_foto
            FROM {$this->table} r
            LEFT JOIN transportes t ON r.transporte_id = t.id
            LEFT JOIN empleados e ON r.conductor_id = e.id
            WHERE r.activo = 1
        ";
        
        $params = [];

        if ($origen) {
            $sql .= " AND r.origen LIKE :origen";
            $params['origen'] = "%$origen%";
        }

        if ($destino) {
            $sql .= " AND r.destino LIKE :destino";
            $params['destino'] = "%$destino%";
        }

        // Si se proporciona fecha, verificar días de operación
        if ($fecha) {
            $dayName = $this->getDayName($fecha);
            $sql .= " AND JSON_SEARCH(r.dias_operacion, 'one', :day_name) IS NOT NULL";
            $params['day_name'] = $dayName;
        }

        $sql .= " ORDER BY r.precio ASC";

        return $this->db->fetchAll($sql, $params);
    }

    // Crear ruta con validación
    public function createRoute($data)
    {
        $errors = $this->validateRoute($data);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Procesar arrays JSON
        $jsonFields = ['dias_operacion', 'horarios', 'paradas_intermedias'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            }
        }

        try {
            $routeId = $this->create($data);
            return ['success' => true, 'route_id' => $routeId];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['general' => 'Error al crear ruta']];
        }
    }

    // Validar datos de ruta
    public function validateRoute($data, $routeId = null)
    {
        $rules = [
            'nombre' => ['required' => true, 'max' => 200],
            'origen' => ['required' => true, 'max' => 100],
            'destino' => ['required' => true, 'max' => 100],
            'precio' => ['required' => true, 'numeric' => true],
            'duracion_estimada' => ['required' => true]
        ];

        $errors = $this->validate($data, $rules);

        // Validar que origen y destino no sean iguales
        if (!empty($data['origen']) && !empty($data['destino'])) {
            if (strtolower(trim($data['origen'])) === strtolower(trim($data['destino']))) {
                $errors['destino'] = 'El destino debe ser diferente al origen';
            }
        }

        // Validar precio positivo
        if (!empty($data['precio']) && $data['precio'] <= 0) {
            $errors['precio'] = 'El precio debe ser mayor a cero';
        }

        // Validar que el transporte existe si se especifica
        if (!empty($data['transporte_id'])) {
            $transport = $this->db->fetch(
                "SELECT id FROM transportes WHERE id = :id",
                ['id' => $data['transporte_id']]
            );
            if (!$transport) {
                $errors['transporte_id'] = 'Transporte no encontrado';
            }
        }

        // Validar que el conductor existe si se especifica
        if (!empty($data['conductor_id'])) {
            $driver = $this->db->fetch(
                "SELECT id FROM empleados WHERE id = :id AND tipo_empleado = 'conductor'",
                ['id' => $data['conductor_id']]
            );
            if (!$driver) {
                $errors['conductor_id'] = 'Conductor no encontrado';
            }
        }

        return $errors;
    }

    // Actualizar ruta
    public function updateRoute($id, $data)
    {
        $errors = $this->validateRoute($data, $id);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Procesar arrays JSON
        $jsonFields = ['dias_operacion', 'horarios', 'paradas_intermedias'];
        foreach ($jsonFields as $field) {
            if (isset($data[$field]) && is_array($data[$field])) {
                $data[$field] = json_encode($data[$field]);
            }
        }

        try {
            $result = $this->update($id, $data);

            // Log para debugging
            error_log("Route::updateRoute - ID: $id, Result: " . var_export($result, true));
            error_log("Route::updateRoute - Data: " . json_encode($data));

            return ['success' => true];
        } catch (Exception $e) {
            // Log del error real
            error_log("Route::updateRoute ERROR: " . $e->getMessage());
            error_log("Route::updateRoute TRACE: " . $e->getTraceAsString());

            return ['success' => false, 'errors' => ['general' => 'Error al actualizar ruta: ' . $e->getMessage()]];
        }
    }

    // Obtener estadísticas de rutas
    public function getRouteStats()
    {
        $sql = "
            SELECT 
                COUNT(*) as total_rutas,
                SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as rutas_activas,
                COUNT(DISTINCT origen) as origenes_unicos,
                COUNT(DISTINCT destino) as destinos_unicos,
                AVG(precio) as precio_promedio,
                AVG(distancia_km) as distancia_promedio
            FROM {$this->table}
        ";

        return $this->db->fetch($sql);
    }

    // Obtener rutas por día de la semana
    public function getRoutesByDay($day)
    {
        $sql = "
            SELECT r.*,
                   t.nombre as transporte_nombre,
                   CONCAT(e.nombre, ' ', e.apellido) as conductor_nombre,
                   e.foto as conductor_foto
            FROM {$this->table} r
            LEFT JOIN transportes t ON r.transporte_id = t.id
            LEFT JOIN empleados e ON r.conductor_id = e.id
            WHERE r.activo = 1
            AND JSON_SEARCH(r.dias_operacion, 'one', :day) IS NOT NULL
            ORDER BY r.nombre
        ";

        return $this->db->fetchAll($sql, [
            'status' => self::STATUS_ACTIVE,
            'day' => strtolower($day)
        ]);
    }

    // Obtener destinos populares
    public function getPopularDestinations($limit = 10)
    {
        $sql = "
            SELECT destino, COUNT(*) as rutas_count,
                   AVG(precio) as precio_promedio
            FROM {$this->table}
            WHERE estado = :status
            GROUP BY destino
            ORDER BY rutas_count DESC, destino
            LIMIT :limit
        ";

        return $this->db->fetchAll($sql, [
            'status' => self::STATUS_ACTIVE,
            'limit' => $limit
        ]);
    }

    // Formatear información de la ruta
    public function formatRouteInfo($route)
    {
        $diasOperacion = [];
        if (!empty($route['dias_operacion'])) {
            $diasArray = json_decode($route['dias_operacion'], true);
            $diasOperacion = is_array($diasArray) ? $diasArray : [$route['dias_operacion']];
        }

        $horarios = [];
        if (!empty($route['horarios'])) {
            $horariosArray = json_decode($route['horarios'], true);
            $horarios = is_array($horariosArray) ? $horariosArray : [];
        }

        $paradas = [];
        if (!empty($route['paradas_intermedias'])) {
            $paradasArray = json_decode($route['paradas_intermedias'], true);
            $paradas = is_array($paradasArray) ? $paradasArray : [];
        }

        return [
            'id' => $route['id'],
            'nombre' => $route['nombre'],
            'ruta_completa' => $route['origen'] . ' → ' . $route['destino'],
            'precio_formatted' => '$' . number_format($route['precio'], 0) . ' USD',
            'duracion' => $route['duracion_estimada'],
            'distancia' => $route['distancia_km'] ? number_format($route['distancia_km'], 1) . ' km' : 'N/A',
            'dias_operacion' => $diasOperacion,
            'horarios' => $horarios,
            'paradas' => $paradas,
            'transporte' => $route['transporte_nombre'] ?? 'Sin asignar',
            'conductor' => $route['conductor_nombre'] ?? 'Sin asignar',
            'estado' => $route['estado'],
            'estado_class' => $this->getStatusClass($route['estado'])
        ];
    }

    // Obtener clase CSS del estado
    public function getStatusClass($status)
    {
        $classes = [
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'secondary',
            self::STATUS_MAINTENANCE => 'warning'
        ];

        return $classes[$status] ?? 'secondary';
    }

    // Obtener nombre del día en español
    private function getDayName($fecha)
    {
        $timestamp = strtotime($fecha);
        $dayNumber = date('w', $timestamp); // 0 = domingo, 6 = sábado
        
        $days = [
            0 => 'domingo',
            1 => 'lunes',
            2 => 'martes',
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sabado'
        ];

        return $days[$dayNumber];
    }

    // Obtener transportes disponibles
    public function getAvailableTransports()
    {
        $sql = "
            SELECT id, nombre, tipo, capacidad
            FROM transportes
            WHERE activo = 1
            ORDER BY nombre
        ";

        return $this->db->fetchAll($sql);
    }

    // Obtener conductores disponibles
    public function getAvailableDrivers()
    {
        $sql = "
            SELECT id, CONCAT(nombre, ' ', apellido) as nombre_completo
            FROM empleados
            WHERE tipo_empleado = 'conductor'
            AND estado = 'activo'
            ORDER BY nombre, apellido
        ";

        return $this->db->fetchAll($sql);
    }
}
