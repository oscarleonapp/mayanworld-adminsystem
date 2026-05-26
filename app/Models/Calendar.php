<?php

namespace App\Models;

use App\Core\Model;
use DateTime;
use Exception;

class Calendar extends Model
{
    protected $table = 'eventos_calendario';
    protected $fillable = [
        'calendario_id', 'titulo', 'descripcion', 'fecha_inicio', 'fecha_fin',
        'todo_el_dia', 'ubicacion', 'tipo', 'recurso_id', 'recurso_tipo',
        'color', 'creado_por'
    ];
    
    // Crear evento
    public function createEvent($data)
    {
        return $this->create($data);
    }
    
    // Actualizar evento
    public function updateEvent($id, $data)
    {
        return $this->update($id, $data);
    }
    
    // Eliminar evento
    public function deleteEvent($id)
    {
        return $this->delete($id);
    }
    
    // Obtener evento específico
    public function getEvent($id)
    {
        $sql = "
            SELECT e.*, c.nombre as calendar_nombre, c.color as calendar_color
            FROM {$this->table} e
            INNER JOIN calendarios c ON e.calendario_id = c.id
            WHERE e.id = :id
        ";
        
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    // Obtener eventos en rango de fechas
    public function getEvents($start, $end, $calendarIds = [])
    {
        $sql = "
            SELECT e.*, c.nombre as calendar_nombre, c.color as calendar_color
            FROM {$this->table} e
            INNER JOIN calendarios c ON e.calendario_id = c.id
            WHERE (
                (e.fecha_inicio >= :start AND e.fecha_inicio <= :end) OR
                (e.fecha_fin >= :start AND e.fecha_fin <= :end) OR
                (e.fecha_inicio <= :start AND e.fecha_fin >= :end)
            )
        ";
        
        $params = [
            'start' => $start,
            'end' => $end
        ];
        
        if (!empty($calendarIds)) {
            $placeholders = implode(',', array_fill(0, count($calendarIds), '?'));
            $sql .= " AND c.id IN ($placeholders)";
            $params = array_merge($params, $calendarIds);
        }
        
        $sql .= " ORDER BY e.fecha_inicio ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Obtener calendarios del usuario
    public function getUserCalendars($userId)
    {
        $sql = "
            SELECT c.*, 
                   COUNT(e.id) as eventos_count,
                   u.nombre as propietario_nombre
            FROM calendarios c
            LEFT JOIN eventos_calendario e ON c.id = e.calendario_id
            LEFT JOIN usuarios u ON c.propietario_id = u.id
            WHERE c.propietario_id = :user_id OR c.tipo = 'publico'
            GROUP BY c.id
            ORDER BY c.nombre
        ";
        
        return $this->db->fetchAll($sql, ['user_id' => $userId]);
    }
    
    // Obtener calendarios públicos
    public function getPublicCalendars()
    {
        $sql = "
            SELECT c.*, 
                   COUNT(e.id) as eventos_count,
                   u.nombre as propietario_nombre
            FROM calendarios c
            LEFT JOIN eventos_calendario e ON c.id = e.calendario_id
            LEFT JOIN usuarios u ON c.propietario_id = u.id
            WHERE c.tipo = 'publico' AND c.activo = 1
            GROUP BY c.id
            ORDER BY c.nombre
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Obtener todos los calendarios (admin)
    public function getAllCalendars()
    {
        $sql = "
            SELECT c.*, 
                   COUNT(e.id) as eventos_count,
                   u.nombre as propietario_nombre
            FROM calendarios c
            LEFT JOIN eventos_calendario e ON c.id = e.calendario_id
            LEFT JOIN usuarios u ON c.propietario_id = u.id
            GROUP BY c.id
            ORDER BY c.tipo, c.nombre
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Crear calendario
    public function createCalendar($data)
    {
        return $this->db->insert('calendarios', $data);
    }
    
    // Actualizar calendario
    public function updateCalendar($id, $data)
    {
        return $this->db->update('calendarios', $id, $data);
    }
    
    // Eliminar calendario
    public function deleteCalendar($id)
    {
        // Eliminar eventos del calendario
        $this->db->query("DELETE FROM {$this->table} WHERE calendario_id = :id", ['id' => $id]);
        
        // Eliminar calendario
        return $this->db->delete('calendarios', $id);
    }
    
    // Verificar disponibilidad de recurso
    public function checkResourceAvailability($resourceId, $resourceType, $start, $end = null)
    {
        $sql = "
            SELECT COUNT(*) as conflicts
            FROM {$this->table}
            WHERE recurso_id = :resource_id 
            AND recurso_tipo = :resource_type
        ";
        
        $params = [
            'resource_id' => $resourceId,
            'resource_type' => $resourceType
        ];
        
        if ($end) {
            $sql .= " AND (
                (fecha_inicio <= :start AND fecha_fin >= :start) OR
                (fecha_inicio <= :end AND fecha_fin >= :end) OR
                (fecha_inicio >= :start AND fecha_fin <= :end)
            )";
            $params['start'] = $start;
            $params['end'] = $end;
        } else {
            $sql .= " AND fecha_inicio <= :start AND (fecha_fin >= :start OR fecha_fin IS NULL)";
            $params['start'] = $start;
        }
        
        $result = $this->db->fetch($sql, $params);
        
        return $result['conflicts'] == 0;
    }
    
    // Obtener buses disponibles
    public function getAvailableBuses()
    {
        $sql = "
            SELECT b.*, 
                   CONCAT(u1.nombre) as conductor_principal_nombre,
                   CONCAT(u2.nombre) as conductor_auxiliar_nombre
            FROM buses b
            LEFT JOIN usuarios u1 ON b.conductor_principal_id = u1.id
            LEFT JOIN usuarios u2 ON b.conductor_auxiliar_id = u2.id
            WHERE b.estado = 'disponible'
            ORDER BY b.numero_bus
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Obtener empleados disponibles
    public function getAvailableEmployees()
    {
        $sql = "
            SELECT id, nombre, puesto, departamento, rol
            FROM usuarios
            WHERE rol != 'cliente' 
            AND estado_empleado = 'activo'
            ORDER BY departamento, nombre
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Obtener rutas activas
    public function getActiveRoutes()
    {
        $sql = "
            SELECT r.*, 
                   COUNT(hb.id) as horarios_count
            FROM rutas r
            LEFT JOIN horarios_bus hb ON r.id = hb.ruta_id AND hb.activo = 1
            WHERE r.activa = 1
            GROUP BY r.id
            ORDER BY r.nombre
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Obtener disponibilidad de recurso en periodo
    public function getResourceAvailability($resourceId, $resourceType, $start, $end)
    {
        $sql = "
            SELECT e.*, c.nombre as calendar_nombre
            FROM {$this->table} e
            INNER JOIN calendarios c ON e.calendario_id = c.id
            WHERE e.recurso_id = :resource_id 
            AND e.recurso_tipo = :resource_type
            AND e.fecha_inicio >= :start 
            AND e.fecha_inicio <= :end
            ORDER BY e.fecha_inicio
        ";
        
        return $this->db->fetchAll($sql, [
            'resource_id' => $resourceId,
            'resource_type' => $resourceType,
            'start' => $start,
            'end' => $end
        ]);
    }
    
    // Obtener eventos de agenda para vista específica
    public function getAgendaEvents($date, $view = 'day')
    {
        switch ($view) {
            case 'week':
                $start = date('Y-m-d', strtotime('monday this week', strtotime($date)));
                $end = date('Y-m-d', strtotime('sunday this week', strtotime($date)));
                break;
            case 'month':
                $start = date('Y-m-01', strtotime($date));
                $end = date('Y-m-t', strtotime($date));
                break;
            default: // day
                $start = $date;
                $end = $date;
                break;
        }
        
        $sql = "
            SELECT e.*, c.nombre as calendar_nombre, c.color as calendar_color,
                   u.nombre as creado_por_nombre
            FROM {$this->table} e
            INNER JOIN calendarios c ON e.calendario_id = c.id
            LEFT JOIN usuarios u ON e.creado_por = u.id
            WHERE DATE(e.fecha_inicio) >= :start AND DATE(e.fecha_inicio) <= :end
            ORDER BY e.fecha_inicio ASC
        ";
        
        return $this->db->fetchAll($sql, [
            'start' => $start,
            'end' => $end
        ]);
    }
    
    // Obtener conflictos de horarios
    public function getScheduleConflicts($date)
    {
        $sql = "
            SELECT e1.*, e2.id as conflict_id, e2.titulo as conflict_titulo,
                   c1.nombre as calendar_nombre
            FROM {$this->table} e1
            INNER JOIN {$this->table} e2 ON e1.id != e2.id
            INNER JOIN calendarios c1 ON e1.calendario_id = c1.id
            WHERE DATE(e1.fecha_inicio) = :date
            AND e1.recurso_id = e2.recurso_id 
            AND e1.recurso_tipo = e2.recurso_tipo
            AND e1.recurso_id IS NOT NULL
            AND (
                (e1.fecha_inicio BETWEEN e2.fecha_inicio AND e2.fecha_fin) OR
                (e1.fecha_fin BETWEEN e2.fecha_inicio AND e2.fecha_fin) OR
                (e2.fecha_inicio BETWEEN e1.fecha_inicio AND e1.fecha_fin)
            )
            ORDER BY e1.fecha_inicio
        ";
        
        return $this->db->fetchAll($sql, ['date' => $date]);
    }
    
    // Obtener participantes del evento
    public function getEventParticipants($eventData)
    {
        $participants = [];
        
        if ($eventData['recurso_tipo'] === 'bus' && $eventData['recurso_id']) {
            $sql = "
                SELECT u.id, u.nombre, u.email, 'conductor' as rol_evento
                FROM buses b
                INNER JOIN usuarios u ON (u.id = b.conductor_principal_id OR u.id = b.conductor_auxiliar_id)
                WHERE b.id = :bus_id AND u.estado_empleado = 'activo'
            ";
            
            $participants = $this->db->fetchAll($sql, ['bus_id' => $eventData['recurso_id']]);
        }
        
        if ($eventData['recurso_tipo'] === 'empleado' && $eventData['recurso_id']) {
            $sql = "
                SELECT id, nombre, email, 'participante' as rol_evento
                FROM usuarios
                WHERE id = :employee_id AND estado_empleado = 'activo'
            ";
            
            $participants = $this->db->fetchAll($sql, ['employee_id' => $eventData['recurso_id']]);
        }
        
        return $participants;
    }
    
    // Crear notificación
    public function createNotification($data)
    {
        return $this->db->insert('notificaciones', $data);
    }
    
    // Obtener eventos próximos (dashboard)
    public function getUpcomingEvents($userId, $limit = 5)
    {
        $sql = "
            SELECT e.*, c.nombre as calendar_nombre
            FROM {$this->table} e
            INNER JOIN calendarios c ON e.calendario_id = c.id
            WHERE e.fecha_inicio >= NOW()
            AND (c.propietario_id = :user_id OR c.tipo = 'publico')
            ORDER BY e.fecha_inicio ASC
            LIMIT :limit
        ";
        
        return $this->db->fetchAll($sql, [
            'user_id' => $userId,
            'limit' => $limit
        ]);
    }
    
    // Obtener estadísticas del calendario
    public function getCalendarStats($calendarId = null)
    {
        $sql = "
            SELECT 
                COUNT(*) as total_eventos,
                COUNT(CASE WHEN fecha_inicio >= CURDATE() THEN 1 END) as eventos_futuros,
                COUNT(CASE WHEN DATE(fecha_inicio) = CURDATE() THEN 1 END) as eventos_hoy,
                COUNT(CASE WHEN tipo = 'viaje' THEN 1 END) as viajes,
                COUNT(CASE WHEN tipo = 'mantenimiento' THEN 1 END) as mantenimientos
            FROM {$this->table}
        ";
        
        $params = [];
        
        if ($calendarId) {
            $sql .= " WHERE calendario_id = :calendar_id";
            $params['calendar_id'] = $calendarId;
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    // Sincronizar eventos con viajes programados
    public function syncWithScheduledTrips()
    {
        $sql = "
            INSERT INTO {$this->table} (calendario_id, titulo, descripcion, fecha_inicio, fecha_fin, tipo, recurso_id, recurso_tipo, creado_por)
            SELECT 
                1 as calendario_id,
                CONCAT('Viaje: ', r.origen, ' - ', r.destino) as titulo,
                CONCAT('Bus ', b.numero_bus, ' - Conductor: ', u.nombre) as descripcion,
                CONCAT(v.fecha_viaje, ' ', hb.hora_salida) as fecha_inicio,
                CONCAT(v.fecha_viaje, ' ', hb.hora_llegada) as fecha_fin,
                'viaje' as tipo,
                v.bus_id as recurso_id,
                'bus' as recurso_tipo,
                1 as creado_por
            FROM viajes v
            INNER JOIN horarios_bus hb ON v.horario_id = hb.id
            INNER JOIN rutas r ON hb.ruta_id = r.id
            INNER JOIN buses b ON v.bus_id = b.id
            INNER JOIN usuarios u ON v.conductor_principal_id = u.id
            WHERE v.estado = 'programado'
            AND NOT EXISTS (
                SELECT 1 FROM {$this->table} e 
                WHERE e.recurso_id = v.bus_id 
                AND e.recurso_tipo = 'bus'
                AND DATE(e.fecha_inicio) = v.fecha_viaje
                AND e.tipo = 'viaje'
            )
        ";
        
        return $this->db->query($sql);
    }
}