<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Helpers;
use Exception;

class Bus extends Model
{
    protected $table = 'transportes';
    protected $fillable = [
        'nombre', 'tipo', 'capacidad', 'comodidades', 'activo'
    ];
    
    // Tipos de transporte
    const TYPE_BUS = 'autobus';
    const TYPE_VAN = 'van';
    const TYPE_PLANE = 'avion';
    const TYPE_OTHER = 'otro';
    
    // Obtener transportes activos
    public function getActive($type = null)
    {
        $conditions = ['activo' => 1];
        
        if ($type) {
            $conditions['tipo'] = $type;
        }
        
        return $this->findAll($conditions, 'nombre ASC');
    }
    
    // Obtener por tipo
    public function getByType($type)
    {
        return $this->getActive($type);
    }
    
    // Validar transporte
    public function validateTransport($data)
    {
        $rules = [
            'nombre' => ['required' => true, 'max' => 100],
            'tipo' => ['required' => true],
            'capacidad' => ['required' => true, 'numeric' => true]
        ];
        
        $errors = $this->validate($data, $rules);
        
        $validTypes = [self::TYPE_BUS, self::TYPE_VAN, self::TYPE_PLANE, self::TYPE_OTHER];
        if (!empty($data['tipo']) && !in_array($data['tipo'], $validTypes)) {
            $errors['tipo'] = 'Tipo de transporte inválido';
        }
        
        if (!empty($data['capacidad']) && $data['capacidad'] < 1) {
            $errors['capacidad'] = 'La capacidad debe ser al menos 1';
        }
        
        return $errors;
    }
    
    // Crear transporte
    public function createTransport($data)
    {
        $errors = $this->validateTransport($data);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $transportData = [
            'nombre' => Helpers::sanitizeString($data['nombre']),
            'tipo' => $data['tipo'],
            'capacidad' => (int)$data['capacidad'],
            'comodidades' => $data['comodidades'] ?? null,
            'activo' => true
        ];
        
        try {
            $id = $this->create($transportData);
            return ['success' => true, 'transport_id' => $id];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => ['general' => 'Error al crear transporte']];
        }
    }
    
    // Parsear comodidades JSON
    public function parseAmenities($transport)
    {
        if (empty($transport['comodidades'])) {
            return [];
        }
        
        $amenities = json_decode($transport['comodidades'], true);
        return is_array($amenities) ? $amenities : [];
    }
}