<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Helpers;
use Exception;

class Tour extends Model
{
    protected $table = 'tours';
    protected $fillable = [
        'categoria_id', 'nombre', 'descripcion', 'descripcion_corta',
        'precio', 'precio_nino', 'precio_descuento', 'duracion', 'duracion_dias', 'duracion_horas', 'incluye', 'no_incluye',
        'itinerario', 'que_llevar', 'politicas', 'capacidad_maxima', 'edad_min', 'edad_min_nino', 'edad_max_nino', 'dificultad', 'imagen_principal',
        'galeria', 'activo', 'destacado', 'disponible_desde', 'disponible_hasta',
        'ubicacion', 'horarios'
    ];

    // Sobrescribir findAll para incluir categoría
    public function findAll($conditions = [], $orderBy = null, $limit = null)
    {
        $sql = "SELECT p.*, c.nombre as categoria_nombre, c.slug as categoria_slug
                FROM {$this->table} p
                LEFT JOIN categorias c ON p.categoria_id = c.id";
        $params = [];

        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                // Agregar prefijo de tabla para evitar ambigüedades
                $whereClause[] = "p.{$field} = :{$field}";
                $params[$field] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }

        if ($orderBy) {
            // Agregar prefijo si no lo tiene
            if (strpos($orderBy, '.') === false && strpos($orderBy, 'p.') !== 0) {
                $orderBy = 'p.' . $orderBy;
            }
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }

        return $this->db->fetchAll($sql, $params);
    }

    // Sobrescribir find para incluir categoría
    public function find($id)
    {
        $sql = "SELECT p.*, c.nombre as categoria_nombre, c.slug as categoria_slug
                FROM {$this->table} p
                LEFT JOIN categorias c ON p.categoria_id = c.id
                WHERE p.id = :id";

        return $this->db->fetch($sql, ['id' => $id]);
    }

    // Obtener tours activos
    public function getActive($limit = null, $featured = false)
    {
        $conditions = ['activo' => 1];
        
        if ($featured) {
            $conditions['destacado'] = 1;
        }
        
        return $this->findAll($conditions, 'created_at DESC', $limit);
    }
    
    // Obtener tours por categoría
    public function getByCategory($categoryId, $limit = null)
    {
        $conditions = ['categoria_id' => $categoryId, 'activo' => 1];
        return $this->findAll($conditions, 'created_at DESC', $limit);
    }
    
    // Obtener tours con categoría (JOIN)
    public function getWithCategory($id = null)
    {
        $sql = "
            SELECT p.*, c.nombre as categoria_nombre, c.descripcion as categoria_descripcion
            FROM {$this->table} p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            WHERE p.activo = 1
        ";
        
        $params = [];
        
        if ($id) {
            $sql .= " AND p.id = :id";
            $params['id'] = $id;
            return $this->db->fetch($sql, $params);
        }
        
        $sql .= " ORDER BY p.destacado DESC, p.created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }
    
    // Buscar tours (sobrescribir método del padre)
    public function searchTours($term, $categoryId = null, $limit = null)
    {
        $searchTerm = '%' . $term . '%';

        $sql = "
            SELECT p.*, c.nombre as categoria_nombre
            FROM {$this->table} p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE p.activo = 1
            AND (
                p.nombre LIKE :term1
                OR p.descripcion LIKE :term2
                OR p.descripcion_corta LIKE :term3
            )
        ";

        $params = [
            'term1' => $searchTerm,
            'term2' => $searchTerm,
            'term3' => $searchTerm
        ];

        if ($categoryId) {
            $sql .= " AND p.categoria_id = :category_id";
            $params['category_id'] = $categoryId;
        }

        $sql .= " ORDER BY p.destacado DESC, p.created_at DESC";

        if ($limit) {
            $sql .= " LIMIT :limit";
            $params['limit'] = (int)$limit;
        }

        return $this->db->fetchAll($sql, $params);
    }
    
    // Obtener tours con disponibilidad
    public function getWithAvailability($id = null, $fromDate = null)
    {
        $sql = "
            SELECT p.*, c.nombre as categoria_nombre,
                   COUNT(d.id) as total_disponibilidad,
                   MIN(d.fecha_salida) as proxima_salida,
                   SUM(d.cupos_disponibles) as total_cupos
            FROM {$this->table} p 
            LEFT JOIN categorias c ON p.categoria_id = c.id 
            LEFT JOIN disponibilidad d ON p.id = d.tour_id AND d.activo = 1
        ";
        
        $params = [];
        $whereClause = ["p.activo = 1"];
        
        if ($fromDate) {
            $whereClause[] = 'd.fecha_salida >= :from_date';
            $params['from_date'] = $fromDate;
        }
        
        if ($id) {
            $whereClause[] = 'p.id = :id';
            $params['id'] = $id;
        }
        
        $sql .= " WHERE " . implode(' AND ', $whereClause);
        $sql .= " GROUP BY p.id";
        
        if ($id) {
            return $this->db->fetch($sql, $params);
        }
        
        $sql .= " ORDER BY p.destacado DESC, p.created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }
    
    // Validar datos del tour
    public function validateProduct($data, $isUpdate = false)
    {
        $rules = [
            'nombre' => ['required' => true, 'max' => 200],
            'descripcion_corta' => ['required' => true, 'max' => 500],
            'precio_base' => ['required' => true, 'numeric' => true],
            'duracion_dias' => ['numeric' => true],
            'capacidad_maxima' => ['numeric' => true],
            'dificultad' => ['required' => false]
        ];
        
        $errors = $this->validate($data, $rules);
        
        // Validaciones específicas
        if (!empty($data['precio_base']) && $data['precio_base'] <= 0) {
            $errors['precio_base'] = 'El precio debe ser mayor a 0';
        }
        
        if (!empty($data['precio_nino']) && !empty($data['precio_base']) && $data['precio_nino'] >= $data['precio_base']) {
            $errors['precio_nino'] = 'El precio para niños debe ser menor al precio base';
        }
        
        if (!empty($data['categoria_id'])) {
            $categoryExists = $this->db->fetch(
                "SELECT id FROM categorias WHERE id = :id AND activo = 1", 
                ['id' => $data['categoria_id']]
            );
            
            if (!$categoryExists) {
                $errors['categoria_id'] = 'La categoría seleccionada no es válida';
            }
        }
        
        // Validar dificultad
        if (!empty($data['dificultad']) && !in_array($data['dificultad'], ['facil', 'moderado', 'dificil'])) {
            $errors['dificultad'] = 'La dificultad debe ser: fácil, moderado o difícil';
        }
        
        return $errors;
    }
    
    // Crear tour con validación
    public function createProduct($data)
    {
        $errors = $this->validateProduct($data);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Sanitizar datos
        $sanitizedData = [
            'categoria_id' => $data['categoria_id'] ?? null,
            'nombre' => Helpers::sanitizeString($data['nombre']),
            'descripcion' => $data['descripcion'] ?? null,
            'descripcion_corta' => Helpers::sanitizeString($data['descripcion_corta']),
            'precio_base' => Helpers::sanitizeFloat($data['precio_base']),
            'precio_nino' => !empty($data['precio_nino']) ? Helpers::sanitizeFloat($data['precio_nino']) : null,
            'duracion_dias' => !empty($data['duracion_dias']) ? Helpers::sanitizeInt($data['duracion_dias']) : 1,
            'incluye' => $data['incluye'] ?? null,
            'no_incluye' => $data['no_incluye'] ?? null,
            'itinerario' => $data['itinerario'] ?? null,
            'capacidad_maxima' => !empty($data['capacidad_maxima']) ? Helpers::sanitizeInt($data['capacidad_maxima']) : 20,
            'edad_min_nino' => !empty($data['edad_min_nino']) ? Helpers::sanitizeInt($data['edad_min_nino']) : 1,
            'edad_max_nino' => !empty($data['edad_max_nino']) ? Helpers::sanitizeInt($data['edad_max_nino']) : 7,
            'dificultad' => $data['dificultad'] ?? 'facil',
            'imagen_principal' => $data['imagen_principal'] ?? null,
            'galeria' => $data['galeria'] ?? null,
            'activo' => isset($data['activo']) ? (bool)$data['activo'] : true,
            'destacado' => isset($data['destacado']) ? (bool)$data['destacado'] : false
        ];

        try {
            $productId = $this->create($sanitizedData);
            return ['success' => true, 'tour_id' => $productId];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['general' => 'Error al crear el tour']];
        }
    }
    
    // Actualizar tour
    public function updateProduct($id, $data)
    {
        $errors = $this->validateProduct($data, true);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Verificar que el tour existe
        $product = $this->find($id);
        if (!$product) {
            return ['success' => false, 'errors' => ['general' => 'Tour no encontrado']];
        }
        
        // Sanitizar datos
        $sanitizedData = [
            'categoria_id' => $data['categoria_id'] ?? null,
            'nombre' => Helpers::sanitizeString($data['nombre']),
            'descripcion' => $data['descripcion'] ?? null,
            'descripcion_corta' => Helpers::sanitizeString($data['descripcion_corta']),
            'precio_base' => Helpers::sanitizeFloat($data['precio_base']),
            'precio_nino' => !empty($data['precio_nino']) ? Helpers::sanitizeFloat($data['precio_nino']) : null,
            'duracion_dias' => !empty($data['duracion_dias']) ? Helpers::sanitizeInt($data['duracion_dias']) : null,
            'incluye' => $data['incluye'] ?? null,
            'no_incluye' => $data['no_incluye'] ?? null,
            'itinerario' => $data['itinerario'] ?? null,
            'capacidad_maxima' => !empty($data['capacidad_maxima']) ? Helpers::sanitizeInt($data['capacidad_maxima']) : 20,
            'edad_min_nino' => !empty($data['edad_min_nino']) ? Helpers::sanitizeInt($data['edad_min_nino']) : ($product['edad_min_nino'] ?? 1),
            'edad_max_nino' => !empty($data['edad_max_nino']) ? Helpers::sanitizeInt($data['edad_max_nino']) : ($product['edad_max_nino'] ?? 7),
            'dificultad' => $data['dificultad'] ?? 'facil',
            'imagen_principal' => $data['imagen_principal'] ?? $product['imagen_principal'],
            'galeria' => $data['galeria'] ?? $product['galeria'],
            'activo' => isset($data['activo']) ? (bool)$data['activo'] : $product['activo'],
            'destacado' => isset($data['destacado']) ? (bool)$data['destacado'] : $product['destacado']
        ];
        
        try {
            $updated = $this->update($id, $sanitizedData);
            return ['success' => true, 'updated' => $updated];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['general' => 'Error al actualizar el tour']];
        }
    }
    
    // Formatear precio para mostrar
    public function formatPrice($product)
    {
        // Usar precio o precio_base según lo que esté disponible
        $price = $product['precio'] ?? $product['precio_base'] ?? 0;
        $childPrice = $product['precio_nino'] ?? null;

        if ($childPrice && $childPrice < $price) {
            return [
                'adult' => Helpers::formatPrice($price),
                'child' => Helpers::formatPrice($childPrice),
                'has_child_price' => true,
                'savings' => Helpers::formatPrice($price - $childPrice)
            ];
        }

        return [
            'adult' => Helpers::formatPrice($price),
            'child' => null,
            'has_child_price' => false,
            'savings' => null
        ];
    }
    
    // Parsear galería JSON
    public function parseGallery($product)
    {
        if (empty($product['imagenes'])) {
            return [];
        }
        
        $gallery = json_decode($product['imagenes'], true);
        return is_array($gallery) ? $gallery : [];
    }
    
    // Estadísticas del tour
    public function getStats($id)
    {
        $sql = "
            SELECT 
                COUNT(DISTINCT r.id) as total_reservas,
                SUM(r.numero_personas) as total_personas,
                AVG(r.precio_total) as precio_promedio,
                COUNT(DISTINCT d.id) as fechas_disponibles
            FROM tours p
            LEFT JOIN reservas r ON p.id = r.tour_id
            LEFT JOIN disponibilidad d ON p.id = d.tour_id AND d.activo = 1
            WHERE p.id = :id
        ";
        
        return $this->db->fetch($sql, ['id' => $id]);
    }
}
