<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

/**
 * Modelo: BlogCategory
 * Gestión de categorías del blog
 */
class BlogCategory extends Model
{
    protected $table = 'blog_categories';

    protected $fillable = [
        'nombre', 'slug', 'descripcion', 'icono', 'color',
        'meta_title', 'meta_description', 'orden', 'activo'
    ];

    /**
     * Obtener categorías activas ordenadas
     *
     * @param string $orderBy Orden (default: 'orden ASC')
     * @return array Categorías activas
     */
    public function getActive($orderBy = 'orden ASC')
    {
        return $this->db->fetchAll("
            SELECT *
            FROM {$this->table}
            WHERE activo = 1
            ORDER BY {$orderBy}
        ");
    }

    /**
     * Obtener todas las categorías (admin)
     *
     * @return array Todas las categorías
     */
    public function getAll()
    {
        return $this->db->fetchAll("
            SELECT *
            FROM {$this->table}
            ORDER BY orden ASC, nombre ASC
        ");
    }

    /**
     * Obtener categorías con conteo de posts
     *
     * @param bool $onlyActive Solo categorías activas
     * @return array Categorías con conteo
     */
    public function getWithPostCount($onlyActive = true)
    {
        $whereClause = $onlyActive ? 'WHERE c.activo = 1' : '';

        return $this->db->fetchAll("
            SELECT
                c.*,
                COUNT(p.id) as post_count,
                COUNT(CASE WHEN p.estado = 'published' THEN 1 END) as published_count
            FROM {$this->table} c
            LEFT JOIN blog_posts p ON c.id = p.categoria_id
            {$whereClause}
            GROUP BY c.id
            ORDER BY c.orden ASC, c.nombre ASC
        ");
    }

    /**
     * Obtener categoría por slug
     *
     * @param string $slug Slug de la categoría
     * @return array|null Categoría o null
     */
    public function getBySlug($slug)
    {
        return $this->db->fetchOne("
            SELECT *
            FROM {$this->table}
            WHERE slug = :slug
        ", ['slug' => $slug]);
    }

    /**
     * Validar datos de categoría
     *
     * @param array $data Datos a validar
     * @param bool $isUpdate Si es actualización
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateCategory($data, $isUpdate = false)
    {
        $errors = [];

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es obligatorio';
        } elseif (mb_strlen($data['nombre']) > 100) {
            $errors[] = 'El nombre no debe exceder 100 caracteres';
        }

        // Validar slug
        if (empty($data['slug'])) {
            $errors[] = 'El slug es obligatorio';
        } elseif (!preg_match('/^[a-z0-9\-]+$/', $data['slug'])) {
            $errors[] = 'El slug solo debe contener letras minúsculas, números y guiones';
        } else {
            // Verificar slug único
            $existing = $this->db->fetchOne(
                "SELECT id FROM {$this->table} WHERE slug = :slug" . ($isUpdate ? " AND id != :id" : ""),
                $isUpdate ? ['slug' => $data['slug'], 'id' => $data['id']] : ['slug' => $data['slug']]
            );

            if ($existing) {
                $errors[] = 'Ya existe una categoría con este slug';
            }
        }

        // Validar color (hexadecimal)
        if (!empty($data['color']) && !preg_match('/^#[0-9a-f]{6}$/i', $data['color'])) {
            $errors[] = 'El color debe ser un código hexadecimal válido (ej: #3b82f6)';
        }

        // Validar orden
        if (isset($data['orden']) && !is_numeric($data['orden'])) {
            $errors[] = 'El orden debe ser un número';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Reordenar categorías
     *
     * @param array $order Array asociativo [id => orden]
     * @return bool Éxito
     */
    public function reorder($order)
    {
        foreach ($order as $id => $position) {
            $this->db->update(
                $this->table,
                ['orden' => $position],
                'id = :id',
                ['id' => $id]
            );
        }

        return true;
    }

    /**
     * Toggle estado activo/inactivo
     *
     * @param int $id ID de la categoría
     * @return bool Éxito
     */
    public function toggleActive($id)
    {
        $category = $this->find($id);

        if (!$category) {
            return false;
        }

        $newStatus = $category['activo'] ? 0 : 1;

        return $this->db->update(
            $this->table,
            ['activo' => $newStatus],
            'id = :id',
            ['id' => $id]
        );
    }

    /**
     * Obtener categoría con estadísticas
     *
     * @param int $id ID de la categoría
     * @return array|null Categoría con stats
     */
    public function getWithStats($id)
    {
        return $this->db->fetchOne("
            SELECT
                c.*,
                COUNT(p.id) as total_posts,
                COUNT(CASE WHEN p.estado = 'published' THEN 1 END) as published_posts,
                COUNT(CASE WHEN p.estado = 'draft' THEN 1 END) as draft_posts,
                MAX(p.fecha_publicacion) as last_post_date
            FROM {$this->table} c
            LEFT JOIN blog_posts p ON c.id = p.categoria_id
            WHERE c.id = :id
            GROUP BY c.id
        ", ['id' => $id]);
    }

    /**
     * Verificar si se puede eliminar una categoría
     * (no se puede eliminar si tiene posts)
     *
     * @param int $id ID de la categoría
     * @return bool Puede eliminarse
     */
    public function canDelete($id)
    {
        $postCount = $this->db->fetchOne("
            SELECT COUNT(*) as count
            FROM blog_posts
            WHERE categoria_id = :id
        ", ['id' => $id]);

        return $postCount['count'] == 0;
    }

    /**
     * Obtener próximo orden disponible
     *
     * @return int Próximo número de orden
     */
    public function getNextOrder()
    {
        $result = $this->db->fetchOne("
            SELECT MAX(orden) as max_orden
            FROM {$this->table}
        ");

        return ($result['max_orden'] ?? 0) + 1;
    }
}
