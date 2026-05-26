<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Helpers;
use DateTime;
use Exception;

class Booking extends Model
{
    protected $table = 'reservas';
    protected $fillable = [
        'codigo_reserva', 'usuario_id', 'tour_id', 'disponibilidad_id',
        'cliente_nombre', 'cliente_email', 'cliente_telefono', 'cliente_direccion',
        'numero_personas', 'numero_niños', 'fecha_salida', 'fecha_regreso', 'horario_seleccionado',
        'precio_unitario', 'precio_ninos', 'precio_total', 'descuento', 'precio_final',
        'adultos_info', 'niños_info', 'preferencias',
        'requerimientos_especiales', 'restricciones_alimentarias',
        'estado', 'metodo_pago', 'forma_pago', 'monto_anticipo',
        'origen_reserva', 'notas_cliente', 'notas_admin',
        'cliente_documento', 'cliente_nacionalidad', 'cliente_fecha_nacimiento',
        'cliente_contacto_emergencia', 'cliente_telefono_emergencia'
    ];
    
    // Estados posibles de una reserva
    const STATUS_PENDING = 'pendiente';
    const STATUS_CONFIRMED = 'confirmada';
    const STATUS_PAID = 'pagada';
    const STATUS_CANCELLED = 'cancelada';
    
    // Generar código único de reserva
    public function generateBookingCode()
    {
        do {
            $code = 'RES' . date('y') . strtoupper(substr(uniqid(), -6));
        } while ($this->exists(['codigo_reserva' => $code]));
        
        return $code;
    }
    
    // Obtener reservas con información del tour
    public function getWithTour($id = null, $clientEmail = null)
    {
        $sql = "
            SELECT r.*,
                   p.nombre as tour_nombre, p.imagen_principal,
                   p.duracion_dias, p.dificultad,
                   c.nombre as categoria_nombre
            FROM {$this->table} r
            INNER JOIN tours p ON r.tour_id = p.id
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($id) {
            $sql .= " AND r.id = :id";
            $params['id'] = $id;
        }
        
        if ($clientEmail) {
            // Buscar por email O por usuario_id del usuario con ese email
            // Primero intentar obtener usuario_id si está logueado
            $userId = null;
            if (isset($_SESSION['user_id'])) {
                $userId = $_SESSION['user_id'];
            }

            if ($userId) {
                $sql .= " AND (r.cliente_email = :client_email OR r.usuario_id = :user_id)";
                $params['client_email'] = $clientEmail;
                $params['user_id'] = $userId;
            } else {
                $sql .= " AND r.cliente_email = :client_email";
                $params['client_email'] = $clientEmail;
            }
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        if ($id) {
            return $this->db->fetch($sql, $params);
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Buscar reserva por código
    public function findByCode($code)
    {
        return $this->getWithTour(null, null)[0] ?? null;
    }
    
    // Obtener reservas por estado
    public function getByStatus($status, $limit = null)
    {
        $conditions = ['estado' => $status];
        return $this->findAll($conditions, 'created_at DESC', $limit);
    }

    // Obtener reservas por email del cliente
    public function getByClientEmail($email)
    {
        return $this->getWithTour(null, $email);
    }

    // Obtener reservas por rango de fechas
    public function getByDateRange($startDate, $endDate)
    {
        $sql = "
            SELECT r.*, 
                   p.nombre as tour_nombre,
                   c.nombre as categoria_nombre
            FROM {$this->table} r
            INNER JOIN tours p ON r.tour_id = p.id
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE r.fecha_salida BETWEEN :start_date AND :end_date
            ORDER BY r.fecha_salida ASC
        ";
        
        return $this->db->fetchAll($sql, [
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
    
    // Validar datos de reserva
    public function validateBooking($data, $isUpdate = false)
    {
        $rules = [
            'tour_id' => ['required' => true, 'numeric' => true],
            'cliente_nombre' => ['required' => true, 'max' => 150],
            'cliente_email' => ['required' => true, 'email' => true, 'max' => 150],
            'cliente_telefono' => ['required' => true, 'max' => 20],
            'numero_personas' => ['required' => true, 'numeric' => true],
            'fecha_salida' => ['required' => true, 'date' => true],
            'fecha_regreso' => ['required' => true, 'date' => true]
        ];
        
        $errors = $this->validate($data, $rules);
        
        // Validaciones específicas
        if (!empty($data['numero_personas']) && $data['numero_personas'] < 1) {
            $errors['numero_personas'] = 'Debe ser al menos 1 persona';
        }
        
        if (!empty($data['fecha_salida']) && !empty($data['fecha_regreso'])) {
            $startDate = new DateTime($data['fecha_salida']);
            $endDate = new DateTime($data['fecha_regreso']);
            
            if ($startDate > $endDate) {
                $errors['fecha_regreso'] = 'La fecha de regreso no puede ser anterior a la fecha de salida';
            }
            
            if ($startDate <= new DateTime()) {
                $errors['fecha_salida'] = 'La fecha de salida debe ser futura';
            }
        }
        
        // Verificar que el tour existe
        if (!empty($data['tour_id'])) {
            $product = $this->db->fetch(
                "SELECT * FROM tours WHERE id = :id AND activo = 1",
                ['id' => $data['tour_id']]
            );
            
            if (!$product) {
                $errors['tour_id'] = 'El tour seleccionado no es válido';
            }
        }
        
        // Validar disponibilidad si se proporciona
        if (!empty($data['disponibilidad_id'])) {
            $availability = $this->db->fetch(
                "SELECT * FROM disponibilidad WHERE id = :id AND activo = 1",
                ['id' => $data['disponibilidad_id']]
            );
            
            if (!$availability) {
                $errors['disponibilidad_id'] = 'La fecha seleccionada no está disponible';
            } elseif ($availability['cupos_disponibles'] < ($data['numero_personas'] ?? 1)) {
                $errors['numero_personas'] = 'No hay suficientes cupos disponibles';
            }
        }
        
        return $errors;
    }
    
    // Crear reserva con validaciones
    public function createBooking($data)
    {
        $errors = $this->validateBooking($data);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Obtener información del tour para calcular precios
        $product = $this->db->fetch(
            "SELECT * FROM tours WHERE id = :id",
            ['id' => $data['tour_id']]
        );
        
        // Calcular precios
        $precioUnitario = $product['precio_descuento'] ?: $product['precio'];
        $numeroPersonas = (int)$data['numero_personas'];
        $precioTotal = $precioUnitario * $numeroPersonas;
        $descuento = $data['descuento'] ?? 0;
        $precioFinal = $precioTotal - $descuento;
        
        // Preparar datos para insertar
        $bookingData = [
            'codigo_reserva' => $this->generateBookingCode(),
            'tour_id' => $data['tour_id'],
            'disponibilidad_id' => $data['disponibilidad_id'] ?? null,
            'cliente_nombre' => Helpers::sanitizeString($data['cliente_nombre']),
            'cliente_email' => strtolower(trim($data['cliente_email'])),
            'cliente_telefono' => Helpers::sanitizeString($data['cliente_telefono']),
            'cliente_direccion' => !empty($data['cliente_direccion'])
                ? Helpers::sanitizeString($data['cliente_direccion'])
                : (!empty($data['hotel_nombre']) ? Helpers::sanitizeString($data['hotel_nombre']) : null),
            'numero_personas' => $numeroPersonas,
            'fecha_salida' => $data['fecha_salida'],
            'fecha_regreso' => $data['fecha_regreso'],
            'horario_seleccionado' => $data['horario_seleccionado'] ?? null,
            'precio_unitario' => $precioUnitario,
            'precio_total' => $precioTotal,
            'descuento' => $descuento,
            'precio_final' => $precioFinal,
            'estado' => self::STATUS_PENDING,
            'metodo_pago' => $data['metodo_pago'] ?? null,
            'notas_cliente' => $data['notas_cliente'] ?? null,
            'notas_admin' => null
        ];
        
        try {
            // Iniciar transacción
            $this->db->beginTransaction();
            
            // Crear la reserva
            $bookingId = $this->create($bookingData);
            
            // Actualizar disponibilidad si se especificó
            if (!empty($data['disponibilidad_id'])) {
                $this->db->query(
                    "UPDATE disponibilidad 
                     SET cupos_reservados = cupos_reservados + :personas 
                     WHERE id = :id",
                    [
                        'personas' => $numeroPersonas,
                        'id' => $data['disponibilidad_id']
                    ]
                );
            }
            
            $this->db->commit();
            
            return [
                'success' => true, 
                'booking_id' => $bookingId,
                'booking_code' => $bookingData['codigo_reserva']
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'errors' => ['general' => 'Error al crear la reserva']];
        }
    }
    
    // Actualizar estado de reserva
    public function updateStatus($id, $newStatus, $adminNotes = null)
    {
        $validStatuses = [self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_PAID, self::STATUS_CANCELLED];
        
        if (!in_array($newStatus, $validStatuses)) {
            return ['success' => false, 'errors' => ['estado' => 'Estado inválido']];
        }
        
        // Obtener reserva actual
        $booking = $this->find($id);
        if (!$booking) {
            return ['success' => false, 'errors' => ['general' => 'Reserva no encontrada']];
        }
        
        $updateData = ['estado' => $newStatus];
        
        if ($adminNotes) {
            $updateData['notas_admin'] = $adminNotes;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Si se cancela, liberar cupos
            if ($newStatus === self::STATUS_CANCELLED && $booking['disponibilidad_id']) {
                $this->db->query(
                    "UPDATE disponibilidad 
                     SET cupos_reservados = cupos_reservados - :personas 
                     WHERE id = :id AND cupos_reservados >= :personas",
                    [
                        'personas' => $booking['numero_personas'],
                        'id' => $booking['disponibilidad_id']
                    ]
                );
            }
            
            $updated = $this->update($id, $updateData);
            
            $this->db->commit();
            
            return ['success' => true, 'updated' => $updated];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'errors' => ['general' => 'Error al actualizar la reserva']];
        }
    }
    
    // Estadísticas de reservas
    public function getStats($startDate = null, $endDate = null)
    {
        $sql = "
            SELECT 
                COUNT(*) as total_reservas,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas,
                SUM(CASE WHEN estado = 'pagada' THEN 1 ELSE 0 END) as pagadas,
                SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas,
                SUM(numero_personas) as total_personas,
                SUM(precio_total) as ingresos_total,
                AVG(precio_total) as ticket_promedio
            FROM {$this->table}
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($startDate) {
            $sql .= " AND fecha_salida >= :start_date";
            $params['start_date'] = $startDate;
        }
        
        if ($endDate) {
            $sql .= " AND fecha_salida <= :end_date";
            $params['end_date'] = $endDate;
        }
        
        return $this->db->fetch($sql, $params);
    }
    
    // Reservas próximas a vencer (para recordatorios)
    public function getUpcoming($days = 7)
    {
        $sql = "
            SELECT r.*, 
                   p.nombre as tour_nombre,
                   c.nombre as categoria_nombre
            FROM {$this->table} r
            INNER JOIN tours p ON r.tour_id = p.id
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE r.fecha_salida BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
            AND r.estado IN ('confirmada', 'pagada')
            ORDER BY r.fecha_salida ASC
        ";
        
        return $this->db->fetchAll($sql, ['days' => $days]);
    }
    
    // Formatear información de la reserva para mostrar
    public function formatBookingInfo($booking)
    {
        return [
            'codigo' => $booking['codigo_reserva'],
            'tour' => $booking['tour_nombre'] ?? 'N/A',
            'cliente' => $booking['cliente_nombre'],
            'personas' => $booking['numero_personas'],
            'fechas' => Helpers::formatDate($booking['fecha_salida']) . ' - ' . Helpers::formatDate($booking['fecha_regreso']),
            'precio_total' => Helpers::formatPrice($booking['precio_total']),
            'estado' => $this->getStatusLabel($booking['estado']),
            'estado_class' => $this->getStatusClass($booking['estado'])
        ];
    }
    
    // Obtener etiqueta del estado
    public function getStatusLabel($status)
    {
        $labels = [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_CONFIRMED => 'Confirmada',
            self::STATUS_PAID => 'Pagada',
            self::STATUS_CANCELLED => 'Cancelada'
        ];
        
        return $labels[$status] ?? 'Desconocido';
    }
    
    // Obtener clase CSS del estado
    public function getStatusClass($status)
    {
        $classes = [
            self::STATUS_PENDING => 'warning',
            self::STATUS_CONFIRMED => 'info',
            self::STATUS_PAID => 'success',
            self::STATUS_CANCELLED => 'danger'
        ];
        
        return $classes[$status] ?? 'secondary';
    }
}
