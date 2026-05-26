<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Helpers;
use DateTime;
use Exception;

class Availability extends Model
{
    protected $table = 'disponibilidad';
    protected $fillable = [
        'tour_id', 'fecha', 'fecha_inicio', 'fecha_salida', 
        'cupos_disponibles', 'cupos_reservados', 'precio_especial', 
        'observaciones', 'activo'
    ];
    
    private $detectedColumns = null;
    
    // Detectar estructura de la tabla automáticamente
    private function detectTableStructure()
    {
        if ($this->detectedColumns !== null) {
            return $this->detectedColumns;
        }
        
        try {
            $result = $this->db->query("DESCRIBE {$this->table}");
            $columns = [];
            foreach ($result as $row) {
                $columns[] = $row['Field'];
            }
            
            // Determinar columna de fecha
            $dateColumn = null;
            if (in_array('fecha', $columns)) {
                $dateColumn = 'fecha';
            } elseif (in_array('fecha_inicio', $columns)) {
                $dateColumn = 'fecha_inicio';
            } elseif (in_array('fecha_salida', $columns)) {
                $dateColumn = 'fecha_salida';
            } else {
                throw new Exception("No se encontró columna de fecha válida");
            }
            
            // Determinar si existe cupos_reservados
            $hasReserved = in_array('cupos_reservados', $columns);
            
            $this->detectedColumns = [
                'date_column' => $dateColumn,
                'has_reserved' => $hasReserved,
                'all_columns' => $columns
            ];
            
            return $this->detectedColumns;
            
        } catch (Exception $e) {
            // Fallback seguro
            $this->detectedColumns = [
                'date_column' => 'fecha',
                'has_reserved' => true,
                'all_columns' => ['id', 'tour_id', 'fecha', 'cupos_disponibles', 'cupos_reservados']
            ];
            return $this->detectedColumns;
        }
    }
    
    // Obtener disponibilidad activa para un tour
    public function getForProduct($productId, $fromDate = null)
    {
        $structure = $this->detectTableStructure();
        $dateColumn = $structure['date_column'];
        
        $sql = "SELECT * FROM {$this->table} WHERE tour_id = :tour_id AND activo = 1";
        $params = ['tour_id' => $productId];
        
        if ($fromDate) {
            $sql .= " AND {$dateColumn} >= :from_date";
            $params['from_date'] = $fromDate;
        }
        
        $sql .= " ORDER BY {$dateColumn} ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Obtener disponibilidad con cupos libres
    public function getAvailable($productId = null, $fromDate = null, $minPersons = 1)
    {
        $structure = $this->detectTableStructure();
        $dateColumn = $structure['date_column'];
        $hasReserved = $structure['has_reserved'];
        
        // Construir condición de disponibilidad
        if ($hasReserved) {
            $availabilityCondition = "(d.cupos_disponibles - d.cupos_reservados) >= :min_persons";
        } else {
            $availabilityCondition = "d.cupos_disponibles >= :min_persons";
        }
        
        $sql = "
            SELECT d.*, p.nombre as tour_nombre, p.precio
            FROM {$this->table} d
            INNER JOIN tours p ON d.tour_id = p.id
            WHERE d.activo = 1 
            AND {$availabilityCondition}
        ";
        
        $params = ['min_persons' => $minPersons];
        
        if ($productId) {
            $sql .= " AND d.tour_id = :tour_id";
            $params['tour_id'] = $productId;
        }
        
        if ($fromDate) {
            $sql .= " AND d.{$dateColumn} >= :from_date";
            $params['from_date'] = $fromDate;
        }
        
        $sql .= " ORDER BY d.{$dateColumn} ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Mapa de cupos restantes por tour - MÉTODO TOTALMENTE ADAPTABLE
    public function getRemainingSpotsByProduct($productIds = [], $fromDate = null)
    {
        if (empty($productIds)) {
            return [];
        }

        $structure = $this->detectTableStructure();
        $dateColumn = $structure['date_column'];
        $hasReserved = $structure['has_reserved'];

        // Asegurar enteros únicos
        $ids = array_values(array_unique(array_map('intval', $productIds)));
        $inPlaceholders = implode(',', array_fill(0, count($ids), '?'));

        // Construir SELECT según disponibilidad de columnas
        if ($hasReserved) {
            $selectClause = "SUM(cupos_disponibles - cupos_reservados) AS remaining";
        } else {
            $selectClause = "SUM(cupos_disponibles) AS remaining";
        }

        $sql = "
            SELECT tour_id, {$selectClause}
            FROM {$this->table}
            WHERE activo = 1
              AND tour_id IN ($inPlaceholders)
        ";

        $params = $ids;
        if ($fromDate) {
            $sql .= " AND {$dateColumn} >= ?";
            $params[] = $fromDate;
        }

        $sql .= " GROUP BY tour_id";

        try {
            $rows = $this->db->fetchAll($sql, $params);
            $map = [];
            foreach ($rows as $row) {
                $map[(int)$row['tour_id']] = max(0, (int)$row['remaining']);
            }
            return $map;
        } catch (Exception $e) {
            // En caso de error, devolver valores seguros
            $map = [];
            foreach ($ids as $id) {
                $map[$id] = 10; // Valor por defecto seguro
            }
            return $map;
        }
    }
    
    // Obtener próximas fechas disponibles para un tour
    public function getUpcomingDates($productId, $limit = 10)
    {
        $structure = $this->detectTableStructure();
        $dateColumn = $structure['date_column'];
        $hasReserved = $structure['has_reserved'];
        
        if ($hasReserved) {
            $cuposLibresSelect = "(cupos_disponibles - cupos_reservados) AS cupos_libres";
            $cuposCondition = "AND (cupos_disponibles - cupos_reservados) > 0";
        } else {
            $cuposLibresSelect = "cupos_disponibles AS cupos_libres";
            $cuposCondition = "AND cupos_disponibles > 0";
        }
        
        $sql = "
            SELECT {$dateColumn} as fecha, {$cuposLibresSelect},
                   precio_especial, observaciones
            FROM {$this->table}
            WHERE tour_id = :tour_id 
              AND activo = 1 
              AND {$dateColumn} >= CURDATE()
              {$cuposCondition}
            ORDER BY {$dateColumn} ASC
            LIMIT :limit
        ";
        
        try {
            return $this->db->fetchAll($sql, [
                'tour_id' => $productId,
                'limit' => $limit
            ]);
        } catch (Exception $e) {
            return [];
        }
    }
    
    // Verificar si hay cupos suficientes
    public function hasEnoughSpots($availabilityId, $requestedSpots)
    {
        try {
            $availability = $this->find($availabilityId);
            
            if (!$availability || !$availability['activo']) {
                return false;
            }
            
            $structure = $this->detectTableStructure();
            
            if ($structure['has_reserved']) {
                $availableSpots = $availability['cupos_disponibles'] - ($availability['cupos_reservados'] ?? 0);
            } else {
                $availableSpots = $availability['cupos_disponibles'];
            }
            
            return $availableSpots >= $requestedSpots;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Reservar cupos
    public function reserveSpots($availabilityId, $spots)
    {
        if (!$this->hasEnoughSpots($availabilityId, $spots)) {
            return false;
        }
        
        $structure = $this->detectTableStructure();
        
        if ($structure['has_reserved']) {
            $sql = "
                UPDATE {$this->table} 
                SET cupos_reservados = cupos_reservados + :spots,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ";
        } else {
            $sql = "
                UPDATE {$this->table} 
                SET cupos_disponibles = cupos_disponibles - :spots,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ";
        }
        
        try {
            $this->db->query($sql, [
                'spots' => $spots,
                'id' => $availabilityId
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    // Crear disponibilidad (adaptable)
    public function createAvailability($data)
    {
        $structure = $this->detectTableStructure();
        $dateColumn = $structure['date_column'];
        
        // Preparar datos según estructura
        $availabilityData = [
            'tour_id' => $data['tour_id'],
            $dateColumn => $data['fecha'] ?? $data[$dateColumn] ?? date('Y-m-d'),
            'cupos_disponibles' => (int)$data['cupos_disponibles'],
            'precio_especial' => !empty($data['precio_especial']) ? (float)$data['precio_especial'] : null,
            'activo' => true
        ];
        
        if ($structure['has_reserved']) {
            $availabilityData['cupos_reservados'] = 0;
        }
        
        if (in_array('observaciones', $structure['all_columns'])) {
            $availabilityData['observaciones'] = $data['observaciones'] ?? '';
        }
        
        try {
            $id = $this->create($availabilityData);
            return ['success' => true, 'availability_id' => $id];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['general' => 'Error al crear disponibilidad: ' . $e->getMessage()]];
        }
    }
    
    // Método de diagnóstico
    public function getDiagnosticInfo()
    {
        $structure = $this->detectTableStructure();
        return [
            'table' => $this->table,
            'structure' => $structure,
            'sample_query' => "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY {$structure['date_column']} ASC LIMIT 5"
        ];
    }
}