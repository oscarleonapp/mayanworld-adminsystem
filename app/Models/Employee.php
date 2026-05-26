<?php

namespace App\Models;

use App\Core\Model;
use Exception;

class Employee extends Model
{
    protected $table = 'usuarios';
    protected $fillable = [
        'nombre', 'email', 'password', 'telefono', 'fecha_nacimiento',
        'rol', 'departamento', 'puesto', 'fecha_ingreso', 'salario',
        'supervisor_id', 'estado_empleado', 'foto_perfil', 'direccion',
        'emergency_contacto', 'emergency_telefono', 'activo'
    ];
    
    // Crear nuevo empleado
    public function createEmployee($data)
    {
        // Verificar que el email no exista
        $existingUser = $this->findWhere(['email' => $data['email']]);
        if ($existingUser) {
            return ['success' => false, 'message' => 'El email ya está registrado'];
        }
        
        // Hash de la contraseña
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Establecer valores por defecto
        $data['activo'] = 1;
        $data['email_verificado'] = 1;
        
        try {
            $employeeId = $this->create($data);
            return [
                'success' => true,
                'message' => 'Empleado creado exitosamente',
                'employee_id' => $employeeId
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al crear empleado: ' . $e->getMessage()];
        }
    }
    
    // Actualizar empleado
    public function updateEmployee($id, $data)
    {
        // Si hay contraseña, hashearla
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // Remover password si está vacío para no actualizarlo
            unset($data['password']);
        }
        
        // Verificar email único (excepto el propio usuario)
        if (isset($data['email'])) {
            $existingUser = $this->db->fetch(
                "SELECT id FROM {$this->table} WHERE email = :email AND id != :id",
                ['email' => $data['email'], 'id' => $id]
            );
            
            if ($existingUser) {
                return ['success' => false, 'message' => 'El email ya está registrado por otro usuario'];
            }
        }
        
        try {
            $this->update($id, $data);
            return ['success' => true, 'message' => 'Empleado actualizado exitosamente'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al actualizar empleado: ' . $e->getMessage()];
        }
    }
    
    // Obtener detalles completos del empleado
    public function getEmployeeDetails($id)
    {
        $sql = "
            SELECT u.*, s.nombre as supervisor_nombre,
                   (SELECT COUNT(*) FROM usuarios WHERE supervisor_id = u.id) as subordinados_count
            FROM {$this->table} u
            LEFT JOIN {$this->table} s ON u.supervisor_id = s.id
            WHERE u.id = :id AND u.rol != 'cliente'
        ";
        
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    // Obtener estadísticas de empleados
    public function getEmployeeStats()
    {
        $sql = "
            SELECT 
                COUNT(*) as total_empleados,
                COUNT(CASE WHEN estado_empleado = 'activo' THEN 1 END) as empleados_activos,
                COUNT(CASE WHEN estado_empleado = 'inactivo' THEN 1 END) as empleados_inactivos,
                COUNT(CASE WHEN estado_empleado = 'vacaciones' THEN 1 END) as empleados_vacaciones,
                COUNT(CASE WHEN DATE(fecha_ingreso) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as nuevos_empleados,
                COUNT(DISTINCT departamento) as departamentos_count
            FROM {$this->table} 
            WHERE rol != 'cliente'
        ";
        
        return $this->db->fetch($sql);
    }
    
    // Obtener estadísticas de rendimiento de un empleado
    public function getEmployeePerformance($employeeId)
    {
        $stats = [];
        
        // Reservas gestionadas (si es vendedor)
        $sql = "
            SELECT COUNT(*) as total_reservas,
                   COUNT(CASE WHEN estado = 'confirmada' THEN 1 END) as reservas_confirmadas,
                   SUM(precio_total) as ventas_total
            FROM reservas 
            WHERE usuario_id IN (
                SELECT id FROM mensajes WHERE respondido_por = :employee_id
            )
        ";
        $reservaStats = $this->db->fetch($sql, ['employee_id' => $employeeId]);
        $stats['reservas'] = $reservaStats;
        
        // Viajes realizados (si es conductor)
        $sql = "
            SELECT COUNT(*) as viajes_totales,
                   COUNT(CASE WHEN estado = 'completado' THEN 1 END) as viajes_completados,
                   AVG(CASE 
                       WHEN hora_llegada_real IS NOT NULL AND hora_salida_real IS NOT NULL 
                       THEN TIME_TO_SEC(TIMEDIFF(hora_llegada_real, hora_salida_real)) 
                   END) / 3600 as promedio_horas_viaje
            FROM viajes 
            WHERE conductor_principal_id = :employee_id OR conductor_auxiliar_id = :employee_id
        ";
        $viajeStats = $this->db->fetch($sql, ['employee_id' => $employeeId]);
        $stats['viajes'] = $viajeStats;
        
        // Mensajes de chat respondidos (si es soporte)
        $sql = "
            SELECT COUNT(*) as mensajes_respondidos,
                   AVG(TIMESTAMPDIFF(MINUTE, m.created_at, m.respondido_en)) as tiempo_promedio_respuesta
            FROM mensajes m
            WHERE m.respondido_por = :employee_id
        ";
        $chatStats = $this->db->fetch($sql, ['employee_id' => $employeeId]);
        $stats['chat'] = $chatStats;
        
        return $stats;
    }
    
    // Obtener departamentos únicos
    public function getDepartments()
    {
        $sql = "
            SELECT DISTINCT departamento, COUNT(*) as empleados_count
            FROM {$this->table} 
            WHERE departamento IS NOT NULL AND departamento != '' AND rol != 'cliente'
            GROUP BY departamento
            ORDER BY departamento
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Obtener posibles supervisores
    public function getPotentialSupervisors($excludeId = null)
    {
        $sql = "
            SELECT id, nombre, puesto, departamento
            FROM {$this->table} 
            WHERE rol IN ('super_admin', 'admin', 'gerente') 
            AND estado_empleado = 'activo'
        ";
        
        $params = [];
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $sql .= " ORDER BY nombre";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Buscar empleados
    public function searchEmployees($term, $filters = [])
    {
        $sql = "
            SELECT id, nombre, email, rol, departamento, puesto, estado_empleado
            FROM {$this->table} 
            WHERE rol != 'cliente'
        ";
        
        $params = [];
        
        if ($term) {
            $searchTerm = "%$term%";
            $sql .= " AND (nombre LIKE :term1 OR email LIKE :term2 OR puesto LIKE :term3)";
            $params['term1'] = $searchTerm;
            $params['term2'] = $searchTerm;
            $params['term3'] = $searchTerm;
        }
        
        if (!empty($filters['departamento'])) {
            $sql .= " AND departamento = :departamento";
            $params['departamento'] = $filters['departamento'];
        }
        
        if (!empty($filters['rol'])) {
            $sql .= " AND rol = :rol";
            $params['rol'] = $filters['rol'];
        }
        
        if (!empty($filters['estado_empleado'])) {
            $sql .= " AND estado_empleado = :estado";
            $params['estado'] = $filters['estado_empleado'];
        }
        
        $sql .= " ORDER BY nombre";
        
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Obtener organigrama
    public function getOrganizationChart()
    {
        $sql = "
            SELECT id, nombre, puesto, departamento, supervisor_id, 
                   estado_empleado, rol, foto_perfil
            FROM {$this->table} 
            WHERE rol != 'cliente' AND estado_empleado = 'activo'
            ORDER BY 
                CASE rol 
                    WHEN 'super_admin' THEN 1
                    WHEN 'admin' THEN 2
                    WHEN 'gerente' THEN 3
                    ELSE 4
                END, supervisor_id, nombre
        ";
        
        $employees = $this->db->fetchAll($sql);
        
        // Organizar en estructura jerárquica
        return $this->buildHierarchy($employees);
    }
    
    private function buildHierarchy($employees, $parentId = null)
    {
        $branch = [];
        
        foreach ($employees as $employee) {
            if ($employee['supervisor_id'] == $parentId) {
                $children = $this->buildHierarchy($employees, $employee['id']);
                if ($children) {
                    $employee['subordinados'] = $children;
                }
                $branch[] = $employee;
            }
        }
        
        return $branch;
    }
    
    // Registrar actividad en logs
    public function logActivity($data)
    {
        return $this->db->insert('logs_actividad', $data);
    }
    
    // Obtener empleados por departamento
    public function getEmployeesByDepartment($department)
    {
        $sql = "
            SELECT id, nombre, puesto, email, telefono, estado_empleado
            FROM {$this->table} 
            WHERE departamento = :department AND rol != 'cliente'
            ORDER BY puesto, nombre
        ";
        
        return $this->db->fetchAll($sql, ['department' => $department]);
    }
    
    // Obtener subordinados de un supervisor
    public function getSubordinates($supervisorId)
    {
        $sql = "
            SELECT id, nombre, puesto, departamento, email, estado_empleado
            FROM {$this->table} 
            WHERE supervisor_id = :supervisor_id AND rol != 'cliente'
            ORDER BY nombre
        ";
        
        return $this->db->fetchAll($sql, ['supervisor_id' => $supervisorId]);
    }
    
    // Obtener cumpleañeros del mes
    public function getBirthdaysThisMonth()
    {
        $sql = "
            SELECT id, nombre, email, fecha_nacimiento, departamento, puesto
            FROM {$this->table} 
            WHERE MONTH(fecha_nacimiento) = MONTH(CURDATE()) 
            AND rol != 'cliente' 
            AND estado_empleado = 'activo'
            ORDER BY DAY(fecha_nacimiento)
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Obtener empleados con aniversario laboral
    public function getWorkAnniversaries()
    {
        $sql = "
            SELECT id, nombre, email, fecha_ingreso, departamento, puesto,
                   YEAR(CURDATE()) - YEAR(fecha_ingreso) as años_servicio
            FROM {$this->table} 
            WHERE MONTH(fecha_ingreso) = MONTH(CURDATE()) 
            AND DAY(fecha_ingreso) = DAY(CURDATE())
            AND rol != 'cliente' 
            AND estado_empleado = 'activo'
            ORDER BY años_servicio DESC
        ";
        
        return $this->db->fetchAll($sql);
    }
}