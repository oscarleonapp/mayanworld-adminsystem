<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Helpers;
use Exception;

class Staff extends Model
{
    protected $table = 'empleados';
    protected $fillable = [
        'nombre', 'apellido', 'email', 'telefono', 'direccion', 'dpi',
        'fecha_nacimiento', 'fecha_contratacion', 'puesto', 'salario',
        'tipo_empleado', 'idiomas', 'certificaciones', 'experiencia_anios',
        'estado', 'notas', 'foto'
    ];

    // Tipos de empleado
    const TYPE_GUIDE = 'guia';
    const TYPE_DRIVER = 'conductor';
    const TYPE_ADMIN = 'administrativo';
    const TYPE_MANAGEMENT = 'gerencia';

    // Estados
    const STATUS_ACTIVE = 'activo';
    const STATUS_INACTIVE = 'inactivo';
    const STATUS_SUSPENDED = 'suspendido';

    // Obtener empleados con información completa
    public function getAllWithDetails()
    {
        $sql = "
            SELECT e.*,
                   CONCAT(e.nombre, ' ', e.apellido) as nombre_completo,
                   CASE 
                       WHEN e.fecha_nacimiento IS NOT NULL 
                       THEN TIMESTAMPDIFF(YEAR, e.fecha_nacimiento, CURDATE()) 
                       ELSE NULL 
                   END as edad,
                   TIMESTAMPDIFF(YEAR, e.fecha_contratacion, CURDATE()) as años_empresa
            FROM {$this->table} e
            ORDER BY e.nombre, e.apellido
        ";
        
        return $this->db->fetchAll($sql);
    }

    // Obtener empleados por tipo
    public function getByType($type)
    {
        return $this->findAll(
            ['tipo_empleado' => $type, 'estado' => self::STATUS_ACTIVE],
            'nombre, apellido'
        );
    }

    // Obtener guías disponibles
    public function getAvailableGuides($fecha = null)
    {
        $fecha = $fecha ?: date('Y-m-d');
        
        $sql = "
            SELECT e.*, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo
            FROM {$this->table} e
            WHERE e.tipo_empleado = :type
            AND e.estado = :status
            ORDER BY e.experiencia_anios DESC, e.nombre
        ";

        return $this->db->fetchAll($sql, [
            'type' => self::TYPE_GUIDE,
            'status' => self::STATUS_ACTIVE
        ]);
    }

    // Obtener conductores disponibles
    public function getAvailableDrivers($fecha = null)
    {
        $sql = "
            SELECT e.*, CONCAT(e.nombre, ' ', e.apellido) as nombre_completo
            FROM {$this->table} e
            WHERE e.tipo_empleado = :type
            AND e.estado = :status
            ORDER BY e.experiencia_anios DESC, e.nombre
        ";

        return $this->db->fetchAll($sql, [
            'type' => self::TYPE_DRIVER,
            'status' => self::STATUS_ACTIVE
        ]);
    }

    // Crear empleado con validación
    public function createStaff($data)
    {
        $errors = $this->validateStaff($data);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Procesar arrays JSON
        if (isset($data['idiomas'])) {
            if (is_array($data['idiomas'])) {
                $data['idiomas'] = json_encode($data['idiomas']);
            }
        }
        
        if (isset($data['certificaciones'])) {
            if (is_array($data['certificaciones'])) {
                $data['certificaciones'] = json_encode($data['certificaciones']);
            }
        }

        try {
            $staffId = $this->create($data);
            return ['success' => true, 'staff_id' => $staffId];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['general' => 'Error al crear empleado']];
        }
    }

    // Validar datos de empleado
    public function validateStaff($data, $staffId = null)
    {
        $rules = [
            'nombre' => ['required' => true, 'max' => 100],
            'apellido' => ['required' => true, 'max' => 100],
            'telefono' => ['required' => true, 'max' => 20],
            'puesto' => ['required' => true, 'max' => 100],
            'tipo_empleado' => ['required' => true]
        ];

        $errors = $this->validate($data, $rules);

        // Validar email único si se proporciona
        if (!empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email inválido';
            } else {
                if ($staffId) {
                    $existing = $this->db->fetch(
                        "SELECT id FROM {$this->table} WHERE email = :email AND id != :id",
                        ['email' => $data['email'], 'id' => $staffId]
                    );
                } else {
                    $existing = $this->findWhere(['email' => $data['email']]);
                }
                
                if ($existing) {
                    $errors['email'] = 'El email ya está registrado';
                }
            }
        }

        // Validar tipo de empleado contra la base de datos
        if (!empty($data['tipo_empleado'])) {
            $validType = $this->db->fetch(
                "SELECT id FROM tipos_empleado WHERE slug = :slug",
                ['slug' => $data['tipo_empleado']]
            );
            if (!$validType) {
                $errors['tipo_empleado'] = 'Tipo de empleado inválido';
            }
        }

        return $errors;
    }

    // Obtener estadísticas de empleados
    public function getStats()
    {
        $sql = "
            SELECT 
                COUNT(*) as total_empleados,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN tipo_empleado = 'guia' THEN 1 ELSE 0 END) as total_guias,
                SUM(CASE WHEN tipo_empleado = 'conductor' THEN 1 ELSE 0 END) as total_conductores,
                COALESCE(AVG(experiencia_anios), 0) as experiencia_promedio,
                COALESCE(AVG(salario), 0) as salario_promedio
            FROM {$this->table}
        ";

        return $this->db->fetch($sql);
    }

    // Formatear información del empleado
    public function formatStaffInfo($staff)
    {
        $idiomas = [];
        if (!empty($staff['idiomas'])) {
            $idiomasArray = json_decode($staff['idiomas'], true);
            $idiomas = is_array($idiomasArray) ? $idiomasArray : [$staff['idiomas']];
        }

        $certificaciones = [];
        if (!empty($staff['certificaciones'])) {
            $certArray = json_decode($staff['certificaciones'], true);
            $certificaciones = is_array($certArray) ? $certArray : [$staff['certificaciones']];
        }

        return [
            'id' => $staff['id'],
            'nombre_completo' => $staff['nombre'] . ' ' . $staff['apellido'],
            'tipo_empleado' => $staff['tipo_empleado'],
            'tipo_empleado_label' => $this->getTypeLabel($staff['tipo_empleado']),
            'puesto' => $staff['puesto'],
            'telefono' => $staff['telefono'],
            'email' => $staff['email'] ?? '',
            'experiencia' => ($staff['experiencia_anios'] ?? 0) . ' años',
            'idiomas' => $idiomas,
            'certificaciones' => $certificaciones,
            'estado' => $staff['estado'],
            'estado_class' => $this->getStatusClass($staff['estado'])
        ];
    }

    // Obtener etiqueta del tipo
    public function getTypeLabel($type)
    {
        $labels = [
            self::TYPE_GUIDE => 'Guía Turístico',
            self::TYPE_DRIVER => 'Conductor',
            self::TYPE_ADMIN => 'Administrativo',
            self::TYPE_MANAGEMENT => 'Gerencia'
        ];

        return $labels[$type] ?? 'Desconocido';
    }

    // Obtener clase CSS del estado
    public function getStatusClass($status)
    {
        $classes = [
            self::STATUS_ACTIVE => 'success',
            self::STATUS_INACTIVE => 'secondary',
            self::STATUS_SUSPENDED => 'danger'
        ];

        return $classes[$status] ?? 'secondary';
    }

    // Actualizar empleado
    public function updateStaff($id, $data)
    {
        $errors = $this->validateStaff($data, $id);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Procesar arrays JSON
        if (isset($data['idiomas']) && is_array($data['idiomas'])) {
            $data['idiomas'] = json_encode($data['idiomas']);
        }
        
        if (isset($data['certificaciones']) && is_array($data['certificaciones'])) {
            $data['certificaciones'] = json_encode($data['certificaciones']);
        }

        try {
            $this->update($id, $data);
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['general' => 'Error al actualizar empleado']];
        }
    }

    // Buscar empleados
    public function searchStaff($term, $type = null)
    {
        $sql = "
            SELECT *, CONCAT(nombre, ' ', apellido) as nombre_completo
            FROM {$this->table}
            WHERE 1=1
        ";
        
        $params = [];

        if ($term) {
            $searchTerm = "%$term%";
            $sql .= " AND (nombre LIKE :term1 OR apellido LIKE :term2 OR puesto LIKE :term3)";
            $params['term1'] = $searchTerm;
            $params['term2'] = $searchTerm;
            $params['term3'] = $searchTerm;
        }

        if ($type) {
            $sql .= " AND tipo_empleado = :type";
            $params['type'] = $type;
        }

        $sql .= " ORDER BY nombre, apellido";

        return $this->db->fetchAll($sql, $params);
    }

    // Obtener empleados de cumpleaños
    public function getBirthdaysThisMonth()
    {
        $sql = "
            SELECT *, CONCAT(nombre, ' ', apellido) as nombre_completo,
                   DAY(fecha_nacimiento) as dia_cumple
            FROM {$this->table}
            WHERE MONTH(fecha_nacimiento) = MONTH(CURDATE())
            AND fecha_nacimiento IS NOT NULL
            AND estado = :status
            ORDER BY DAY(fecha_nacimiento)
        ";

        return $this->db->fetchAll($sql, ['status' => self::STATUS_ACTIVE]);
    }
}