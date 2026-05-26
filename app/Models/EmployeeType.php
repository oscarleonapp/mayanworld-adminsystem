<?php

namespace App\Models;

use App\Core\Model;

class EmployeeType extends Model
{
    protected $table = 'tipos_empleado';
    protected $fillable = ['nombre', 'slug', 'descripcion', 'activo', 'orden'];

    /**
     * Obtener todos los tipos ordenados
     */
    public function getAll()
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY orden ASC, nombre ASC"
        );
    }

    /**
     * Obtener solo tipos activos (para dropdowns)
     */
    public function getActive()
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY orden ASC, nombre ASC"
        );
    }

    /**
     * Buscar por slug
     */
    public function getBySlug($slug)
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE slug = :slug",
            ['slug' => $slug]
        );
    }

    /**
     * Verificar si el slug ya existe (excluyendo un ID opcional)
     */
    public function slugExists($slug, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE slug = :slug";
        $params = ['slug' => $slug];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $result = $this->db->fetch($sql, $params);
        return (int)$result['count'] > 0;
    }

    /**
     * Contar empleados que usan un tipo específico
     */
    public function countEmployeesByType($slug)
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM empleados WHERE tipo_empleado = :slug",
            ['slug' => $slug]
        );
        return (int)$result['count'];
    }

    /**
     * Toggle estado activo/inactivo
     */
    public function toggleActive($id)
    {
        return $this->db->query(
            "UPDATE {$this->table} SET activo = NOT activo WHERE id = :id",
            ['id' => $id]
        );
    }

    /**
     * Obtener el siguiente orden disponible
     */
    public function getNextOrder()
    {
        $result = $this->db->fetch(
            "SELECT COALESCE(MAX(orden), 0) + 1 as next_order FROM {$this->table}"
        );
        return (int)$result['next_order'];
    }

    /**
     * Generar slug desde nombre
     */
    public function generateSlug($nombre)
    {
        $slug = mb_strtolower($nombre, 'UTF-8');
        $slug = preg_replace('/[áàäâ]/u', 'a', $slug);
        $slug = preg_replace('/[éèëê]/u', 'e', $slug);
        $slug = preg_replace('/[íìïî]/u', 'i', $slug);
        $slug = preg_replace('/[óòöô]/u', 'o', $slug);
        $slug = preg_replace('/[úùüû]/u', 'u', $slug);
        $slug = preg_replace('/ñ/u', 'n', $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '_', $slug);
        $slug = trim($slug, '_');
        return $slug;
    }
}
