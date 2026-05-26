<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

/**
 * Modelo: BlogTag
 * Gestión de tags/etiquetas del blog
 */
class BlogTag extends Model
{
    protected $table = 'blog_tags';

    protected $fillable = ['nombre', 'slug'];

    /**
     * Obtener todos los tags
     *
     * @param string $orderBy Orden (default: 'nombre ASC')
     * @return array Tags
     */
    public function getAll($orderBy = 'nombre ASC')
    {
        return $this->db->fetchAll("
            SELECT *
            FROM {$this->table}
            ORDER BY {$orderBy}
        ");
    }

    /**
     * Obtener tags con conteo de posts
     *
     * @param int|null $limit Límite de resultados
     * @return array Tags con conteo
     */
    public function getWithPostCount($limit = null)
    {
        $sql = "
            SELECT
                t.*,
                COUNT(pt.post_id) as post_count
            FROM {$this->table} t
            LEFT JOIN blog_post_tags pt ON t.id = pt.tag_id
            GROUP BY t.id
            HAVING post_count > 0
            ORDER BY post_count DESC, t.nombre ASC
        ";

        if ($limit) {
            $sql .= " LIMIT :limit";
            return $this->db->fetchAll($sql, ['limit' => $limit]);
        }

        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener tag por slug
     *
     * @param string $slug Slug del tag
     * @return array|null Tag o null
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
     * Buscar o crear tag por nombre
     * Útil para auto-crear tags al escribir posts
     *
     * @param string $name Nombre del tag
     * @return int ID del tag (existente o nuevo)
     */
    public function findOrCreate($name)
    {
        $slug = $this->generateSlug($name);

        // Buscar existente
        $existing = $this->db->fetchOne("
            SELECT id
            FROM {$this->table}
            WHERE slug = :slug
        ", ['slug' => $slug]);

        if ($existing) {
            return $existing['id'];
        }

        // Crear nuevo
        return $this->db->insert($this->table, [
            'nombre' => $name,
            'slug' => $slug
        ]);
    }

    /**
     * Generar slug a partir del nombre
     *
     * @param string $name Nombre del tag
     * @return string Slug generado
     */
    private function generateSlug($name)
    {
        // Convertir a minúsculas
        $slug = mb_strtolower($name);

        // Normalizar caracteres (remover acentos)
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);

        // Reemplazar espacios y caracteres especiales con guiones
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        // Remover guiones al inicio y final
        $slug = trim($slug, '-');

        return $slug;
    }

    /**
     * Validar datos del tag
     *
     * @param array $data Datos a validar
     * @param bool $isUpdate Si es actualización
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateTag($data, $isUpdate = false)
    {
        $errors = [];

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es obligatorio';
        } elseif (mb_strlen($data['nombre']) > 50) {
            $errors[] = 'El nombre no debe exceder 50 caracteres';
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
                $errors[] = 'Ya existe un tag con este slug';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Obtener tags populares (más usados)
     *
     * @param int $limit Límite de resultados (default: 10)
     * @return array Tags populares
     */
    public function getPopular($limit = 10)
    {
        return $this->db->fetchAll("
            SELECT
                t.*,
                COUNT(pt.post_id) as post_count
            FROM {$this->table} t
            INNER JOIN blog_post_tags pt ON t.id = pt.tag_id
            INNER JOIN blog_posts p ON pt.post_id = p.id
            WHERE p.estado = 'published'
            GROUP BY t.id
            HAVING post_count > 0
            ORDER BY post_count DESC
            LIMIT :limit
        ", ['limit' => $limit]);
    }

    /**
     * Obtener posts que tienen un tag específico
     *
     * @param int $tagId ID del tag
     * @param int|null $limit Límite de resultados
     * @return array Posts con este tag
     */
    public function getPostsByTag($tagId, $limit = null)
    {
        $sql = "
            SELECT
                p.*,
                c.nombre as categoria_nombre,
                c.slug as categoria_slug
            FROM blog_posts p
            INNER JOIN blog_post_tags pt ON p.id = pt.post_id
            LEFT JOIN blog_categories c ON p.categoria_id = c.id
            WHERE pt.tag_id = :tag_id
              AND p.estado = 'published'
              AND (p.fecha_publicacion IS NULL OR p.fecha_publicacion <= NOW())
            ORDER BY p.fecha_publicacion DESC
        ";

        if ($limit) {
            $sql .= " LIMIT :limit";
            return $this->db->fetchAll($sql, ['tag_id' => $tagId, 'limit' => $limit]);
        }

        return $this->db->fetchAll($sql, ['tag_id' => $tagId]);
    }

    /**
     * Verificar si se puede eliminar un tag
     * (se puede eliminar incluso si tiene posts, solo se desvincula)
     *
     * @param int $id ID del tag
     * @return bool Siempre true (los tags pueden eliminarse libremente)
     */
    public function canDelete($id)
    {
        return true; // Los tags pueden eliminarse, solo se desvinculan de posts
    }

    /**
     * Buscar tags por término (autocompletado)
     * Compatible con la firma del método padre
     *
     * @param array|string $fields Campos a buscar o término si se usa como string
     * @param string|null $term Término de búsqueda
     * @param string|null $orderBy Orden de resultados
     * @param int|null $limit Límite de resultados
     * @return array Tags encontrados
     */
    public function search($fields = ['nombre', 'slug'], $term = null, $orderBy = 'nombre ASC', $limit = 10)
    {
        // Si $fields es un string, asumimos que es el término de búsqueda (retrocompatibilidad)
        if (is_string($fields)) {
            $term = $fields;
            $fields = ['nombre', 'slug'];
        }

        // Si no hay término, retornar array vacío
        if (empty($term)) {
            return [];
        }

        // Usar el método padre si se especifican campos personalizados
        if ($fields !== ['nombre', 'slug']) {
            return parent::search($fields, $term, $orderBy, $limit);
        }

        // Implementación optimizada para búsqueda por defecto
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE nombre LIKE :term
               OR slug LIKE :term
        ";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit) {
            $sql .= " LIMIT :limit";
        }

        return $this->db->fetchAll($sql, [
            'term' => '%' . $term . '%',
            'limit' => (int)$limit
        ]);
    }

    /**
     * Limpiar tags huérfanos (sin posts asociados)
     *
     * @return int Número de tags eliminados
     */
    public function cleanOrphans()
    {
        $orphans = $this->db->fetchAll("
            SELECT t.id
            FROM {$this->table} t
            LEFT JOIN blog_post_tags pt ON t.id = pt.tag_id
            WHERE pt.tag_id IS NULL
        ");

        $count = 0;
        foreach ($orphans as $orphan) {
            $this->db->delete($this->table, 'id = :id', ['id' => $orphan['id']]);
            $count++;
        }

        return $count;
    }

    /**
     * Crear múltiples tags desde una lista separada por comas
     *
     * @param string $tagString Tags separados por comas
     * @return array Array de IDs de tags
     */
    public function createFromString($tagString)
    {
        $tagNames = array_map('trim', explode(',', $tagString));
        $tagIds = [];

        foreach ($tagNames as $tagName) {
            if (!empty($tagName)) {
                $tagIds[] = $this->findOrCreate($tagName);
            }
        }

        return $tagIds;
    }

    /**
     * Obtener nube de tags con pesos
     * (para visualización)
     *
     * @param int $limit Límite de tags (default: 20)
     * @return array Tags con peso relativo
     */
    public function getTagCloud($limit = 20)
    {
        $tags = $this->db->fetchAll("
            SELECT
                t.*,
                COUNT(pt.post_id) as post_count
            FROM {$this->table} t
            INNER JOIN blog_post_tags pt ON t.id = pt.tag_id
            INNER JOIN blog_posts p ON pt.post_id = p.id
            WHERE p.estado = 'published'
            GROUP BY t.id
            HAVING post_count > 0
            ORDER BY post_count DESC
            LIMIT :limit
        ", ['limit' => $limit]);

        if (empty($tags)) {
            return [];
        }

        // Calcular pesos (1-5 basado en frecuencia)
        $maxCount = max(array_column($tags, 'post_count'));
        $minCount = min(array_column($tags, 'post_count'));

        foreach ($tags as &$tag) {
            if ($maxCount == $minCount) {
                $tag['weight'] = 3; // Peso medio si todos tienen la misma frecuencia
            } else {
                // Escala de 1 a 5
                $tag['weight'] = ceil(1 + (($tag['post_count'] - $minCount) / ($maxCount - $minCount)) * 4);
            }
        }

        return $tags;
    }
}
