<?php

namespace App\Helpers;

use App\Core\Database;
use DateTime;
use Exception;

class BadgeHelper 
{
    /**
     * Calcula y retorna los badges dinámicos para un tour
     * Basado en el benchmark de Viator, GetYourGuide, etc.
     */
    public static function getDynamicBadges($product, $availabilityData = null) 
    {
        $badges = [];
        $now = new DateTime();
        
        // 1. BADGE DE URGENCIA - "Se agota rápido"
        if (self::isSellingFast($product, $availabilityData)) {
            $badges[] = [
                'type' => 'urgency',
                'class' => 'bg-danger text-white',
                'icon' => 'fas fa-fire',
                'text' => 'Se agota rápido',
                'priority' => 1
            ];
        }
        
        // 2. BADGE BESTSELLER - Top ventas
        if (self::isBestseller($product)) {
            $badges[] = [
                'type' => 'bestseller', 
                'class' => 'bg-success text-white',
                'icon' => 'fas fa-trophy',
                'text' => 'Superventas',
                'priority' => 2
            ];
        }
        
        // 3. BADGE OF EXCELLENCE - Alta calificación + verificado
        if (self::hasExcellenceBadge($product)) {
            $badges[] = [
                'type' => 'excellence',
                'class' => 'bg-warning text-dark',
                'icon' => 'fas fa-award',
                'text' => 'Badge of Excellence',
                'priority' => 3
            ];
        }
        
        // 4. BADGE TRENDING - Muy visto recientemente
        if (self::isTrending($product)) {
            $badges[] = [
                'type' => 'trending',
                'class' => 'bg-info text-white', 
                'icon' => 'fas fa-trending-up',
                'text' => 'Trending',
                'priority' => 4
            ];
        }
        
        // 5. BADGE DE OFERTA - Descuento activo
        if (self::hasActiveDiscount($product)) {
            $badges[] = [
                'type' => 'discount',
                'class' => 'bg-danger text-white',
                'icon' => 'fas fa-percent',
                'text' => 'Oferta especial',
                'priority' => 5
            ];
        }
        
        // Ordenar por prioridad y retornar máximo 2 badges
        usort($badges, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        return array_slice($badges, 0, 2);
    }
    
    /**
     * Determina si un tour se está agotando rápidamente
     */
    private static function isSellingFast($product, $availabilityData) 
    {
        // Verificar cupos restantes críticos
        if (isset($product['remaining_spots']) && $product['remaining_spots'] <= 3 && $product['remaining_spots'] > 0) {
            return true;
        }
        
        // Verificar disponibilidad limitada en próximas fechas
        if ($availabilityData) {
            foreach ($availabilityData as $avail) {
                $remaining = ($avail['cupos_disponibles'] ?? 0) - ($avail['cupos_reservados'] ?? 0);
                if ($remaining <= ($avail['cupos_criticos'] ?? 3) && $remaining > 0) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Determina si es un bestseller
     */
    private static function isBestseller($product) 
    {
        // Top 20% en ventas totales y mínimo 10 ventas
        $ventas = $product['ventas_totales'] ?? 0;
        return $ventas >= 10 && ($product['badge_tipo'] ?? '') === 'bestseller';
    }
    
    /**
     * Determina si merece Badge of Excellence
     */
    private static function hasExcellenceBadge($product) 
    {
        // Operador verificado + alta calificación + mínimo reviews
        $verificado = !empty($product['operador_verificado']) || !empty($product['destacado']);
        $tieneReviews = isset($product['rating_avg']) && $product['rating_avg'] >= 4.5;
        $suficientesReviews = isset($product['rating_count']) && $product['rating_count'] >= 20;
        
        return $verificado && $tieneReviews && $suficientesReviews;
    }
    
    /**
     * Determina si está trending
     */
    private static function isTrending($product) 
    {
        // Muchas vistas recientes en las últimas 24-48h
        $vistasRecientes = $product['visto_recientemente'] ?? 0;
        return $vistasRecientes >= 50; // Threshold configurable
    }
    
    /**
     * Determina si tiene descuento activo
     */
    private static function hasActiveDiscount($product) 
    {
        $precio = $product['precio'] ?? 0;
        $precioDescuento = $product['precio_descuento'] ?? null;
        
        return $precioDescuento && $precioDescuento < $precio;
    }
    
    /**
     * Actualiza contadores de badges basándose en reservas recientes
     */
    public static function updateBadgeMetrics($productId) 
    {
        try {
            $db = Database::getInstance();
            
            // Actualizar ventas totales
            $ventas = $db->query(
                "SELECT COUNT(*) as total FROM reservas 
                 WHERE tour_id = ? AND estado IN ('confirmada', 'pagada')",
                [$productId]
            )[0]['total'] ?? 0;
            
            // Actualizar tour
            $db->query(
                "UPDATE tours SET ventas_totales = ? WHERE id = ?",
                [$ventas, $productId]
            );
            
            // Determinar badge automático basado en métricas
            self::assignAutomaticBadge($productId, $ventas);
            
        } catch (Exception $e) {
            error_log("Error updating badge metrics: " . $e->getMessage());
        }
    }
    
    /**
     * Asigna badge automáticamente basándose en métricas
     */
    private static function assignAutomaticBadge($productId, $ventas) 
    {
        $db = Database::getInstance();
        $badgeType = null;
        
        // Lógica para asignar badge automático
        if ($ventas >= 100) {
            $badgeType = 'bestseller';
        } else if ($ventas >= 50) {
            $badgeType = 'trending';
        }
        
        if ($badgeType) {
            $db->query(
                "UPDATE tours SET badge_tipo = ? WHERE id = ?",
                [$badgeType, $productId]
            );
        }
    }
    
    /**
     * Genera HTML para mostrar badges
     */
    public static function renderBadges($badges, $position = 'card') 
    {
        if (empty($badges)) return '';
        
        $html = '';
        $positionClass = $position === 'hero' ? 'badge-lg' : '';
        
        foreach ($badges as $badge) {
            $html .= sprintf(
                '<span class="badge %s %s position-absolute" style="%s" title="%s">
                    <i class="%s me-1"></i>%s
                </span>',
                $badge['class'],
                $positionClass,
                self::getBadgePosition($badge['type'], count($badges)),
                htmlspecialchars($badge['text']),
                $badge['icon'],
                htmlspecialchars($badge['text'])
            );
        }
        
        return $html;
    }
    
    /**
     * Calcula posición CSS para badges múltiples
     */
    private static function getBadgePosition($type, $total) 
    {
        static $positions = [
            'urgency' => 'top: 8px; left: 8px;',
            'bestseller' => 'top: 8px; right: 8px;',
            'excellence' => 'top: 40px; left: 8px;',
            'trending' => 'top: 40px; right: 8px;',
            'discount' => 'top: 72px; left: 8px;'
        ];
        
        return $positions[$type] ?? 'top: 8px; left: 8px;';
    }
}