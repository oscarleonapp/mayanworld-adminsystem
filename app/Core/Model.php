<?php

namespace App\Core;

abstract class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $timestamps = true;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    // Encontrar por ID
    public function find($id)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->fetch($sql, ['id' => $id]);
    }
    
    // Encontrar todos los registros
    public function findAll($conditions = [], $orderBy = null, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Encontrar el primero que coincida
    public function findWhere($conditions, $orderBy = null)
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $sql .= " LIMIT 1";
        
        return $this->db->fetch($sql, $params);
    }
    
    // Crear nuevo registro
    public function create($data)
    {
        // Filtrar solo campos permitidos
        $filteredData = $this->filterFillable($data);
        
        // Agregar timestamps si están habilitados
        if ($this->timestamps) {
            $filteredData['created_at'] = date('Y-m-d H:i:s');
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->insert($this->table, $filteredData);
    }
    
    // Actualizar registro
    public function update($id, $data)
    {
        // Filtrar solo campos permitidos
        $filteredData = $this->filterFillable($data);

        // Agregar timestamp de actualización si están habilitados
        if ($this->timestamps) {
            $filteredData['updated_at'] = date('Y-m-d H:i:s');
        }

        return $this->db->update(
            $this->table,
            $filteredData,
            "{$this->primaryKey} = :id",
            ['id' => $id]
        );
    }
    
    // Eliminar registro
    public function delete($id)
    {
        return $this->db->delete(
            $this->table, 
            "{$this->primaryKey} = :id", 
            ['id' => $id]
        );
    }
    
    // Conteo de registros
    public function count($conditions = [])
    {
        $params = [];
        $whereClause = '1=1';
        
        if (!empty($conditions)) {
            $conditionArray = [];
            foreach ($conditions as $field => $value) {
                $conditionArray[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
            $whereClause = implode(' AND ', $conditionArray);
        }
        
        return $this->db->count($this->table, $whereClause, $params);
    }
    
    // Paginación
    public function paginate($page = 1, $perPage = 10, $conditions = [], $orderBy = null)
    {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        $sql .= " LIMIT {$perPage} OFFSET {$offset}";
        
        $data = $this->db->fetchAll($sql, $params);
        $total = $this->count($conditions);
        $totalPages = ceil($total / $perPage);
        
        return [
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ];
    }
    
    // Buscar por campos específicos
    public function search($fields, $term, $orderBy = null, $limit = null)
    {
        $whereClause = [];
        $params = [];
        $searchTerm = '%' . $term . '%';

        // Crear parámetros únicos para cada campo
        $index = 0;
        foreach ($fields as $field) {
            $paramName = "term{$index}";
            $whereClause[] = "{$field} LIKE :{$paramName}";
            $params[$paramName] = $searchTerm;
            $index++;
        }

        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' OR ', $whereClause);

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit) {
            $sql .= " LIMIT :limit";
            $params['limit'] = (int)$limit;
        }

        return $this->db->fetchAll($sql, $params);
    }
    
    // Verificar si existe
    public function exists($conditions)
    {
        $params = [];
        $whereClause = [];
        
        foreach ($conditions as $field => $value) {
            $whereClause[] = "{$field} = :{$field}";
            $params[$field] = $value;
        }
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE " . implode(' AND ', $whereClause);
        $result = $this->db->fetch($sql, $params);
        
        return (int) $result['count'] > 0;
    }
    
    // Filtrar solo campos permitidos
    protected function filterFillable($data)
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        $filtered = [];
        foreach ($this->fillable as $field) {
            if (isset($data[$field])) {
                $filtered[$field] = $data[$field];
            }
        }
        
        return $filtered;
    }
    
    // Validar datos antes de guardar
    protected function validate($data, $rules = [])
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Required
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "El campo {$field} es requerido";
                continue;
            }
            
            // Skip validation if field is empty and not required
            if (empty($value)) {
                continue;
            }
            
            // Email
            if (isset($rule['email']) && $rule['email'] && !Helpers::validateEmail($value)) {
                $errors[$field] = "El campo {$field} debe ser un email válido";
            }
            
            // Min length
            if (isset($rule['min']) && strlen($value) < $rule['min']) {
                $errors[$field] = "El campo {$field} debe tener al menos {$rule['min']} caracteres";
            }
            
            // Max length
            if (isset($rule['max']) && strlen($value) > $rule['max']) {
                $errors[$field] = "El campo {$field} no puede tener más de {$rule['max']} caracteres";
            }
            
            // Numeric
            if (isset($rule['numeric']) && $rule['numeric'] && !is_numeric($value)) {
                $errors[$field] = "El campo {$field} debe ser numérico";
            }
            
            // Date
            if (isset($rule['date']) && $rule['date'] && !Helpers::validateDate($value)) {
                $errors[$field] = "El campo {$field} debe ser una fecha válida";
            }
        }
        
        return $errors;
    }
    
    // Query raw SQL
    public function query($sql, $params = [])
    {
        return $this->db->fetchAll($sql, $params);
    }
    
    // Query que devuelve un solo resultado
    public function queryOne($sql, $params = [])
    {
        return $this->db->fetch($sql, $params);
    }
}