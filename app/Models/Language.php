<?php

namespace App\Models;

use App\Core\Model;

class Language extends Model
{
    protected $table = 'idiomas';
    protected $fillable = ['nombre', 'codigo', 'activo', 'orden'];

    public function getAll()
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY orden ASC, nombre ASC"
        );
    }

    public function getActive()
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY orden ASC, nombre ASC"
        );
    }

    public function getByCodigo($codigo)
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE codigo = :codigo",
            ['codigo' => $codigo]
        );
    }

    public function codigoExists($codigo, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE codigo = :codigo";
        $params = ['codigo' => $codigo];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $result = $this->db->fetch($sql, $params);
        return (int)$result['count'] > 0;
    }

    /**
     * Contar empleados que hablan este idioma (busca en campo TEXT separado por coma)
     */
    public function countEmployeesByLanguage($nombre)
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM empleados WHERE FIND_IN_SET(:nombre, REPLACE(idiomas, ', ', ',')) > 0",
            ['nombre' => $nombre]
        );
        return (int)$result['count'];
    }

    public function toggleActive($id)
    {
        return $this->db->query(
            "UPDATE {$this->table} SET activo = NOT activo WHERE id = :id",
            ['id' => $id]
        );
    }

    public function getNextOrder()
    {
        $result = $this->db->fetch(
            "SELECT COALESCE(MAX(orden), 0) + 1 as next_order FROM {$this->table}"
        );
        return (int)$result['next_order'];
    }

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
