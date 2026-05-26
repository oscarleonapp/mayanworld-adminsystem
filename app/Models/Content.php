<?php

namespace App\Models;

use App\Core\Model;
use Exception;

class Content extends Model
{
    protected $table = 'contenido_web';
    protected $fillable = [
        'seccion', 'tipo', 'titulo', 'contenido', 'contenido_html',
        'imagen_url', 'galeria', 'configuracion', 'orden', 'activo',
        'publicado', 'fecha_publicacion', 'creado_por', 'modificado_por'
    ];
    
    // Obtener todas las secciones disponibles
    public function getAllSections()
    {
        $sql = "
            SELECT 
                seccion,
                COUNT(*) as total_elementos,
                COUNT(CASE WHEN publicado = 1 THEN 1 END) as elementos_publicados,
                COUNT(CASE WHEN activo = 1 THEN 1 END) as elementos_activos,
                MAX(updated_at) as ultima_modificacion
            FROM {$this->table}
            WHERE seccion != 'media'
            GROUP BY seccion
            ORDER BY seccion
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Obtener contenido de una sección específica
    public function getSectionContent($sectionName, $publishedOnly = true)
    {
        $sql = "
            SELECT c.*, u.nombre as creado_por_nombre, u2.nombre as modificado_por_nombre
            FROM {$this->table} c
            LEFT JOIN usuarios u ON c.creado_por = u.id
            LEFT JOIN usuarios u2 ON c.modificado_por = u2.id
            WHERE c.seccion = :seccion AND c.activo = 1
        ";
        
        $params = ['seccion' => $sectionName];
        
        if ($publishedOnly) {
            $sql .= " AND c.publicado = 1";
        }
        
        $sql .= " ORDER BY c.orden ASC, c.id ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Obtener todo el contenido del sitio
    public function getAllContent($publishedOnly = true)
    {
        $sql = "
            SELECT c.*, u.nombre as creado_por_nombre
            FROM {$this->table} c
            LEFT JOIN usuarios u ON c.creado_por = u.id
            WHERE c.activo = 1 AND c.seccion != 'media'
        ";
        
        if ($publishedOnly) {
            $sql .= " AND c.publicado = 1";
        }
        
        $sql .= " ORDER BY c.seccion, c.orden ASC";
        
        $content = $this->db->fetchAll($sql);
        
        // Agrupar por sección
        $grouped = [];
        foreach ($content as $item) {
            $grouped[$item['seccion']][] = $item;
        }
        
        return $grouped;
    }
    
    // Obtener cambios recientes
    public function getRecentChanges($limit = 10)
    {
        $sql = "
            SELECT c.*, u.nombre as modificado_por_nombre
            FROM {$this->table} c
            LEFT JOIN usuarios u ON c.modificado_por = u.id
            WHERE c.seccion != 'media'
            ORDER BY c.updated_at DESC
            LIMIT :limit
        ";
        
        return $this->db->fetchAll($sql, ['limit' => $limit]);
    }
    
    // Obtener estadísticas del contenido
    public function getContentStats()
    {
        $sql = "
            SELECT 
                COUNT(*) as total_contenido,
                COUNT(CASE WHEN publicado = 1 THEN 1 END) as contenido_publicado,
                COUNT(CASE WHEN publicado = 0 THEN 1 END) as borradores,
                COUNT(CASE WHEN tipo = 'imagen' THEN 1 END) as imagenes,
                COUNT(DISTINCT seccion) as total_secciones,
                COUNT(CASE WHEN DATE(updated_at) = CURDATE() THEN 1 END) as cambios_hoy
            FROM {$this->table}
            WHERE seccion != 'media'
        ";
        
        return $this->db->fetch($sql);
    }
    
    // Obtener archivos multimedia
    public function getMediaFiles($page = 1, $perPage = 20, $conditions = [])
    {
        $sql = "
            SELECT c.*, u.nombre as creado_por_nombre
            FROM {$this->table} c
            LEFT JOIN usuarios u ON c.creado_por = u.id
            WHERE c.seccion = 'media'
        ";
        
        $params = [];
        
        if (!empty($conditions['tipo'])) {
            $sql .= " AND c.tipo = :tipo";
            $params['tipo'] = $conditions['tipo'];
        }
        
        $sql .= " ORDER BY c.created_at DESC";
        
        // Contar total
        $countSql = preg_replace('/SELECT.*FROM/', 'SELECT COUNT(*) as total FROM', $sql);
        $total = $this->db->fetch($countSql, $params)['total'];
        
        // Paginación
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT $perPage OFFSET $offset";
        
        $data = $this->db->fetchAll($sql, $params);
        
        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => ceil($total / $perPage),
            'has_next' => $page < ceil($total / $perPage),
            'has_prev' => $page > 1
        ];
    }
    
    // Actualizar contenido específico
    public function updateContent($id, $data)
    {
        return $this->update($id, $data);
    }
    
    // Publicar sección completa
    public function publishSection($sectionName)
    {
        $sql = "
            UPDATE {$this->table} 
            SET publicado = 1, fecha_publicacion = NOW() 
            WHERE seccion = :seccion AND activo = 1
        ";
        
        return $this->db->query($sql, ['seccion' => $sectionName]);
    }
    
    // Obtener configuración del sitio web
    public function getWebsiteSettings()
    {
        $sql = "
            SELECT titulo, contenido
            FROM {$this->table}
            WHERE seccion = 'configuracion' AND tipo = 'configuracion' AND activo = 1
        ";
        
        $settings = $this->db->fetchAll($sql);
        
        // Convertir a array asociativo
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['titulo']] = $setting['contenido'];
        }
        
        return $result;
    }
    
    // Actualizar configuración del sitio
    public function updateSetting($key, $value, $userId)
    {
        $existing = $this->findWhere([
            'seccion' => 'configuracion',
            'titulo' => $key,
            'tipo' => 'configuracion'
        ]);
        
        if ($existing) {
            return $this->update($existing['id'], [
                'contenido' => $value,
                'modificado_por' => $userId
            ]);
        } else {
            return $this->create([
                'seccion' => 'configuracion',
                'tipo' => 'configuracion',
                'titulo' => $key,
                'contenido' => $value,
                'creado_por' => $userId,
                'activo' => 1,
                'publicado' => 1
            ]);
        }
    }
    
    // Obtener secciones disponibles para editor
    public function getAvailableSections()
    {
        return [
            'home_hero' => 'Hero Principal',
            'home_features' => 'Características Destacadas',
            'home_testimonials' => 'Testimonios',
            'about_us' => 'Acerca de Nosotros',
            'services' => 'Servicios',
            'destinations' => 'Destinos',
            'contact' => 'Contacto',
            'footer' => 'Pie de Página',
            'legal' => 'Legal (Términos, Privacidad)',
            'blog' => 'Blog/Noticias'
        ];
    }
    
    // Buscar contenido
    public function searchContent($term, $section = null)
    {
        $searchTerm = "%$term%";

        $sql = "
            SELECT c.*, u.nombre as creado_por_nombre
            FROM {$this->table} c
            LEFT JOIN usuarios u ON c.creado_por = u.id
            WHERE c.seccion != 'media'
            AND (c.titulo LIKE :term1 OR c.contenido LIKE :term2)
        ";

        $params = [
            'term1' => $searchTerm,
            'term2' => $searchTerm
        ];

        if ($section) {
            $sql .= " AND c.seccion = :seccion";
            $params['seccion'] = $section;
        }

        $sql .= " ORDER BY c.updated_at DESC";

        return $this->db->fetchAll($sql, $params);
    }
    
    // Obtener historial de versiones de contenido
    public function getContentHistory($contentId, $limit = 10)
    {
        // Nota: En una implementación completa, tendríamos una tabla de versiones
        // Por ahora, simulamos con los logs de actividad
        $sql = "
            SELECT la.*, u.nombre as usuario_nombre
            FROM logs_actividad la
            LEFT JOIN usuarios u ON la.usuario_id = u.id
            WHERE la.modulo = 'contenido'
            AND JSON_EXTRACT(la.datos_nuevos, '$.id') = :content_id
            ORDER BY la.created_at DESC
            LIMIT :limit
        ";
        
        return $this->db->fetchAll($sql, [
            'content_id' => $contentId,
            'limit' => $limit
        ]);
    }
    
    // Clonar contenido
    public function cloneContent($id, $userId)
    {
        $original = $this->find($id);
        
        if (!$original) {
            return false;
        }
        
        $cloned = $original;
        unset($cloned['id']);
        unset($cloned['created_at']);
        unset($cloned['updated_at']);
        
        $cloned['titulo'] = $cloned['titulo'] . ' (Copia)';
        $cloned['publicado'] = 0;
        $cloned['creado_por'] = $userId;
        $cloned['modificado_por'] = $userId;
        
        return $this->create($cloned);
    }
    
    // Exportar contenido de sección
    public function exportSection($sectionName)
    {
        $content = $this->getSectionContent($sectionName, false);
        
        return [
            'section' => $sectionName,
            'exported_at' => date('Y-m-d H:i:s'),
            'content' => $content
        ];
    }
    
    // Importar contenido de sección
    public function importSection($data, $userId, $overwrite = false)
    {
        if (!isset($data['section']) || !isset($data['content'])) {
            throw new Exception('Formato de importación inválido');
        }
        
        $sectionName = $data['section'];
        
        // Si overwrite es true, eliminar contenido existente
        if ($overwrite) {
            $this->db->query(
                "UPDATE {$this->table} SET activo = 0 WHERE seccion = :seccion",
                ['seccion' => $sectionName]
            );
        }
        
        $importedCount = 0;
        
        foreach ($data['content'] as $item) {
            unset($item['id']);
            unset($item['created_at']);
            unset($item['updated_at']);
            
            $item['seccion'] = $sectionName;
            $item['creado_por'] = $userId;
            $item['modificado_por'] = $userId;
            $item['publicado'] = 0; // Importar como borrador
            
            if ($this->create($item)) {
                $importedCount++;
            }
        }
        
        return $importedCount;
    }
    
    // Obtener widgets dinámicos
    public function getWidgets()
    {
        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE seccion = 'widgets' AND activo = 1 AND publicado = 1
            ORDER BY orden ASC
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Generar sitemap
    public function generateSitemap()
    {
        $sql = "
            SELECT DISTINCT seccion, MAX(updated_at) as lastmod
            FROM {$this->table}
            WHERE publicado = 1 AND activo = 1 AND seccion != 'media'
            GROUP BY seccion
            ORDER BY seccion
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    // Registrar actividad
    public function logActivity($data)
    {
        return $this->db->insert('logs_actividad', $data);
    }
    
    // Obtener contenido para API pública
    public function getPublicContent($section = null)
    {
        $sql = "
            SELECT seccion, tipo, titulo, contenido, imagen_url, orden
            FROM {$this->table}
            WHERE publicado = 1 AND activo = 1 AND seccion != 'media'
        ";
        
        $params = [];
        
        if ($section) {
            $sql .= " AND seccion = :seccion";
            $params['seccion'] = $section;
        }
        
        $sql .= " ORDER BY seccion, orden ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
}