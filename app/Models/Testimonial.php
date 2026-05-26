<?php

namespace App\Models;

use App\Core\Model;

class Testimonial extends Model
{
    protected $table = 'testimonials';

    protected $fillable = [
        'nombre',
        'avatar',
        'calificacion',
        'comentario',
        'fuente',
        'url_fuente',
        'fecha_resena',
        'activo',
        'destacado',
        'orden'
    ];

    /**
     * Obtener testimonios activos ordenados
     */
    public function getActive($limit = null, $destacados = false)
    {
        $sql = "SELECT * FROM {$this->table} WHERE activo = 1";

        if ($destacados) {
            $sql .= " AND destacado = 1";
        }

        $sql .= " ORDER BY orden ASC, fecha_resena DESC";

        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
        }

        return $this->db->fetchAll($sql);
    }

    /**
     * Obtener testimonios destacados para la home
     */
    public function getFeatured($limit = 6)
    {
        return $this->getActive($limit, true);
    }

    /**
     * Alternar estado activo
     */
    public function toggleActive($id)
    {
        $testimonial = $this->find($id);
        if (!$testimonial) {
            return false;
        }

        $newStatus = $testimonial['activo'] ? 0 : 1;
        return $this->update([
            'activo' => $newStatus
        ], 'id = :id', ['id' => $id]);
    }

    /**
     * Alternar estado destacado
     */
    public function toggleFeatured($id)
    {
        $testimonial = $this->find($id);
        if (!$testimonial) {
            return false;
        }

        $newStatus = $testimonial['destacado'] ? 0 : 1;
        return $this->update([
            'destacado' => $newStatus
        ], 'id = :id', ['id' => $id]);
    }

    /**
     * Actualizar orden
     */
    public function updateOrder($id, $orden)
    {
        return $this->update([
            'orden' => (int)$orden
        ], 'id = :id', ['id' => $id]);
    }

    /**
     * Obtener estadísticas
     */
    public function getStats()
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    COUNT(CASE WHEN activo = 1 THEN 1 END) as activos,
                    COUNT(CASE WHEN destacado = 1 THEN 1 END) as destacados,
                    AVG(calificacion) as promedio_calificacion,
                    fuente,
                    COUNT(*) as count_por_fuente
                FROM {$this->table}
                GROUP BY fuente";

        $bySource = $this->db->fetchAll($sql);

        $sql = "SELECT
                    COUNT(*) as total,
                    COUNT(CASE WHEN activo = 1 THEN 1 END) as activos,
                    COUNT(CASE WHEN destacado = 1 THEN 1 END) as destacados,
                    ROUND(AVG(calificacion), 1) as promedio_calificacion
                FROM {$this->table}";

        $general = $this->db->fetch($sql);

        return [
            'general' => $general,
            'por_fuente' => $bySource
        ];
    }

    /**
     * Validar datos de testimonial
     */
    public function validateTestimonial($data)
    {
        $errors = [];

        if (empty($data['nombre'])) {
            $errors[] = 'El nombre es obligatorio';
        }

        if (empty($data['comentario'])) {
            $errors[] = 'El comentario es obligatorio';
        }

        if (empty($data['calificacion']) || $data['calificacion'] < 1 || $data['calificacion'] > 5) {
            $errors[] = 'La calificación debe estar entre 1 y 5';
        }

        if (empty($data['fecha_resena'])) {
            $errors[] = 'La fecha de la reseña es obligatoria';
        }

        return $errors;
    }
}
