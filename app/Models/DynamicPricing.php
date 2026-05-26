<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class DynamicPricing extends Model
{
    protected $table = 'dynamic_prices';
    protected $fillable = [
        'tour_id', 'tenant_id', 'calculation_date', 'valid_from', 'valid_to',
        'base_price', 'calculated_price', 'price_multiplier', 'algorithm_used',
        'confidence_score', 'input_variables', 'adjustments'
    ];
    
    /**
     * Calcular precio dinámico para un tour
     */
    public static function calculatePrice($productId, $targetDate = null, $tenantId = null)
    {
        $targetDate = $targetDate ?: date('Y-m-d');
        $db = Database::getInstance();
        
        try {
            // Llamar al stored procedure para calcular precio
            $result = $db->query("CALL CalculateDynamicPrice(?, ?, ?)", [
                $productId, $tenantId, $targetDate
            ]);
            
            $priceData = $result->fetch();
            
            if ($priceData) {
                // Log del cálculo de precio para analytics
                self::logPricingCalculation($productId, $priceData, $tenantId);
                
                return [
                    'success' => true,
                    'base_price' => floatval($priceData['base_price']),
                    'calculated_price' => floatval($priceData['calculated_price']),
                    'multiplier' => floatval($priceData['multiplier']),
                    'algorithm' => $priceData['algorithm'],
                    'savings' => $priceData['base_price'] - $priceData['calculated_price'],
                    'discount_percentage' => round((1 - $priceData['multiplier']) * 100, 1)
                ];
            }
            
            return ['success' => false, 'message' => 'Could not calculate dynamic price'];
            
        } catch (Exception $e) {
            error_log("Dynamic pricing error: " . $e->getMessage());
            
            // Fallback al precio estático
            return self::getStaticPrice($productId);
        }
    }
    
    /**
     * Obtener precio estático (fallback)
     */
    private static function getStaticPrice($productId)
    {
        $db = Database::getInstance();
        
        $product = $db->fetch("SELECT precio FROM tours WHERE id = ?", [$productId]);
        
        if ($product) {
            return [
                'success' => true,
                'base_price' => floatval($product['precio']),
                'calculated_price' => floatval($product['precio']),
                'multiplier' => 1.0,
                'algorithm' => 'static',
                'savings' => 0,
                'discount_percentage' => 0
            ];
        }
        
        return ['success' => false, 'message' => 'Tour not found'];
    }
    
    /**
     * Obtener precios dinámicos para múltiples tours
     */
    public static function calculateMultiplePrices($productIds, $targetDate = null, $tenantId = null)
    {
        $prices = [];
        
        foreach ($productIds as $productId) {
            $prices[$productId] = self::calculatePrice($productId, $targetDate, $tenantId);
        }
        
        return $prices;
    }
    
    /**
     * Obtener precio optimizado usando ML (simulado)
     */
    public static function calculateMLOptimizedPrice($productId, $targetDate = null, $contextData = [])
    {
        $targetDate = $targetDate ?: date('Y-m-d');
        $db = Database::getInstance();
        
        // Obtener modelo ML activo
        $model = $db->fetch("
            SELECT * FROM ml_pricing_models 
            WHERE is_active = TRUE 
            ORDER BY performance_score DESC 
            LIMIT 1
        ");
        
        if (!$model) {
            // Fallback a pricing básico
            return self::calculatePrice($productId, $targetDate);
        }
        
        // Recopilar variables para ML
        $variables = self::collectMLVariables($productId, $targetDate, $contextData);
        
        // Simular predicción ML (en producción conectaría con TensorFlow/Scikit-learn)
        $mlMultiplier = self::simulateMLPrediction($variables, $model);
        
        // Obtener precio base
        $product = $db->fetch("SELECT precio FROM tours WHERE id = ?", [$productId]);
        $basePrice = $product['precio'];
        $calculatedPrice = $basePrice * $mlMultiplier;
        
        // Guardar precio calculado
        $priceId = self::saveDynamicPrice([
            'tour_id' => $productId,
            'calculation_date' => $targetDate,
            'base_price' => $basePrice,
            'calculated_price' => $calculatedPrice,
            'price_multiplier' => $mlMultiplier,
            'algorithm_used' => 'ml_optimized',
            'confidence_score' => $variables['confidence_score'] ?? 0.85,
            'input_variables' => json_encode($variables)
        ]);
        
        return [
            'success' => true,
            'base_price' => $basePrice,
            'calculated_price' => $calculatedPrice,
            'multiplier' => $mlMultiplier,
            'algorithm' => 'ml_optimized',
            'confidence' => $variables['confidence_score'] ?? 0.85,
            'model_used' => $model['model_name'],
            'price_id' => $priceId
        ];
    }
    
    /**
     * Recopilar variables para ML
     */
    private static function collectMLVariables($productId, $targetDate, $contextData)
    {
        $db = Database::getInstance();
        
        // Variables de demanda
        $demandData = $db->fetch("
            SELECT 
                COUNT(*) as current_bookings,
                (SELECT capacidad_maxima FROM tours WHERE id = ?) as total_capacity
            FROM reservas 
            WHERE tour_id = ? AND fecha_salida = ?
            AND estado IN ('confirmada', 'pagada')
        ", [$productId, $productId, $targetDate]);
        
        $capacityUtilization = $demandData['total_capacity'] > 0 
            ? $demandData['current_bookings'] / $demandData['total_capacity'] 
            : 0;
        
        // Variables temporales
        $daysUntil = (strtotime($targetDate) - time()) / (24 * 3600);
        $isWeekend = in_array(date('N', strtotime($targetDate)), [6, 7]);
        $month = date('n', strtotime($targetDate));
        $isHighSeason = in_array($month, [12, 1, 2, 7, 8]); // Navidad y verano
        
        // Variables históricas
        $historical = $db->fetch("
            SELECT 
                AVG(CASE WHEN estado IN ('confirmada', 'pagada') THEN 1 ELSE 0 END) as avg_conversion,
                COUNT(*) as total_views
            FROM user_events ue
            LEFT JOIN reservas r ON ue.tour_id = r.tour_id
            WHERE ue.tour_id = ? AND ue.event_type = 'product_view'
            AND ue.event_timestamp >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
        ", [$productId]);
        
        // Variables de mercado (simuladas)
        $marketVariables = [
            'competitor_avg_price' => $contextData['competitor_price'] ?? 0,
            'market_demand_index' => $contextData['market_demand'] ?? 1.0,
            'weather_forecast_score' => $contextData['weather_score'] ?? 8.0,
            'tourism_index' => 1.1, // Guatemala tiene índice alto de turismo
            'usd_rate' => $contextData['exchange_rate'] ?? 7.85
        ];
        
        return [
            // Demand variables
            'capacity_utilization' => $capacityUtilization,
            'current_bookings' => $demandData['current_bookings'],
            'total_capacity' => $demandData['total_capacity'],
            
            // Time variables
            'days_until_departure' => max(0, $daysUntil),
            'is_weekend' => $isWeekend,
            'is_high_season' => $isHighSeason,
            'month' => $month,
            
            // Historical performance
            'historical_conversion_rate' => $historical['avg_conversion'] ?? 0,
            'historical_views' => $historical['total_views'] ?? 0,
            
            // Market variables
            'competitor_price_ratio' => $marketVariables['competitor_avg_price'] > 0 
                ? $marketVariables['competitor_avg_price'] / 100 : 1.0,
            'market_demand_index' => $marketVariables['market_demand_index'],
            'weather_forecast_score' => $marketVariables['weather_forecast_score'],
            'tourism_index' => $marketVariables['tourism_index'],
            'currency_rate' => $marketVariables['usd_rate'],
            
            // Meta
            'confidence_score' => 0.85,
            'calculation_timestamp' => time()
        ];
    }
    
    /**
     * Simular predicción ML (placeholder para integración real)
     */
    private static function simulateMLPrediction($variables, $model)
    {
        // Obtener importancia de features del modelo
        $featureImportance = json_decode($model['feature_importance'], true);
        
        // Calcular score basado en variables y sus importancias
        $score = 1.0; // Multiplicador base
        
        // Ajustar por utilización de capacidad
        $capacityImpact = $featureImportance['capacity_utilization'] ?? 0.25;
        if ($variables['capacity_utilization'] > 0.8) {
            $score += 0.3 * $capacityImpact; // Alta demanda
        } elseif ($variables['capacity_utilization'] < 0.2) {
            $score -= 0.15 * $capacityImpact; // Baja demanda
        }
        
        // Ajustar por proximidad de fecha
        $timeImpact = $featureImportance['days_until_departure'] ?? 0.20;
        if ($variables['days_until_departure'] <= 2) {
            $score += 0.25 * $timeImpact; // Last minute premium
        } elseif ($variables['days_until_departure'] >= 30) {
            $score -= 0.1 * $timeImpact; // Early bird discount
        }
        
        // Ajustar por estacionalidad
        $seasonalImpact = $featureImportance['seasonal_factor'] ?? 0.15;
        if ($variables['is_high_season']) {
            $score += 0.2 * $seasonalImpact;
        }
        
        // Ajustar por fin de semana
        if ($variables['is_weekend']) {
            $score += 0.1 * ($featureImportance['weekend_factor'] ?? 0.05);
        }
        
        // Ajustar por conversión histórica
        $conversionImpact = $featureImportance['historical_conversion'] ?? 0.15;
        if ($variables['historical_conversion_rate'] > 0.1) {
            $score += 0.1 * $conversionImpact; // Tour popular
        }
        
        // Ajustar por variables de mercado
        $marketImpact = $featureImportance['market_demand'] ?? 0.07;
        $score *= $variables['market_demand_index'] * $marketImpact + (1 - $marketImpact);
        
        // Limitar multiplicadores extremos
        return max(0.5, min(3.0, $score));
    }
    
    /**
     * Crear experimento A/B para pricing
     */
    public static function createPricingExperiment($data)
    {
        $db = Database::getInstance();
        
        $experimentData = [
            'experiment_name' => $data['name'],
            'tour_id' => $data['tour_id'] ?? null,
            'tenant_id' => $data['tenant_id'] ?? null,
            'control_strategy' => $data['control_strategy'],
            'test_strategy' => $data['test_strategy'],
            'traffic_split' => $data['traffic_split'] ?? 0.5,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'primary_metric' => $data['primary_metric'] ?? 'revenue',
            'created_by' => $data['created_by'],
            'status' => 'draft'
        ];
        
        return $db->insert('pricing_experiments', $experimentData);
    }
    
    /**
     * Participar en experimento de pricing
     */
    public static function participateInExperiment($productId, $sessionId, $userId = null)
    {
        $db = Database::getInstance();
        
        // Buscar experimentos activos para este tour
        $experiment = $db->fetch("
            SELECT * FROM pricing_experiments 
            WHERE (tour_id = ? OR tour_id IS NULL)
            AND status = 'running'
            AND start_date <= CURDATE() 
            AND end_date >= CURDATE()
            ORDER BY tour_id DESC -- Priorizar experimentos específicos del tour
            LIMIT 1
        ", [$productId]);
        
        if (!$experiment) {
            return null; // No hay experimento activo
        }
        
        // Verificar si ya participó
        $existing = $db->fetch("
            SELECT group_assigned FROM pricing_experiment_participants 
            WHERE experiment_id = ? AND session_id = ?
        ", [$experiment['id'], $sessionId]);
        
        if ($existing) {
            return $existing['group_assigned']; // Ya asignado
        }
        
        // Asignar grupo (A/B split)
        $groupAssigned = (mt_rand() / mt_getrandmax()) < $experiment['traffic_split'] ? 'test' : 'control';
        
        // Calcular precio según el grupo
        if ($groupAssigned === 'test') {
            $priceResult = self::calculatePrice($productId, date('Y-m-d'));
        } else {
            $priceResult = self::getStaticPrice($productId);
        }
        
        $priceShown = $priceResult['calculated_price'];
        
        // Registrar participación
        $db->insert('pricing_experiment_participants', [
            'experiment_id' => $experiment['id'],
            'session_id' => $sessionId,
            'user_id' => $userId,
            'group_assigned' => $groupAssigned,
            'price_shown' => $priceShown,
            'tour_id' => $productId
        ]);
        
        return [
            'group' => $groupAssigned,
            'price' => $priceShown,
            'experiment_name' => $experiment['experiment_name']
        ];
    }
    
    /**
     * Analizar performance de pricing
     */
    public static function analyzePricingPerformance($productId = null, $days = 30)
    {
        $db = Database::getInstance();
        
        $whereClause = $productId ? "WHERE dp.tour_id = ?" : "";
        $params = $productId ? [$productId, $days] : [$days];
        
        $sql = "
            SELECT 
                dp.algorithm_used,
                COUNT(*) as calculations,
                AVG(dp.price_multiplier) as avg_multiplier,
                SUM(dp.views_count) as total_views,
                SUM(dp.bookings_count) as total_bookings,
                SUM(dp.revenue_generated) as total_revenue,
                AVG(dp.conversion_rate) as avg_conversion_rate,
                p.nombre as product_name
            FROM dynamic_prices dp
            JOIN tours p ON dp.tour_id = p.id
            {$whereClause}
            AND dp.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)
            GROUP BY dp.algorithm_used, p.id
            ORDER BY total_revenue DESC
        ";
        
        return $db->fetchAll($sql, $params);
    }
    
    /**
     * Obtener resultados de experimento A/B
     */
    public static function getExperimentResults($experimentId)
    {
        $db = Database::getInstance();
        
        // Datos básicos del experimento
        $experiment = $db->fetch("SELECT * FROM pricing_experiments WHERE id = ?", [$experimentId]);
        
        if (!$experiment) {
            return null;
        }
        
        // Resultados por grupo
        $results = $db->fetchAll("
            SELECT 
                group_assigned,
                COUNT(*) as participants,
                COUNT(CASE WHEN booked = 1 THEN 1 END) as bookings,
                SUM(booking_amount) as total_revenue,
                AVG(CASE WHEN booked = 1 THEN 1 ELSE 0 END) as conversion_rate,
                AVG(booking_amount) as avg_order_value
            FROM pricing_experiment_participants
            WHERE experiment_id = ?
            GROUP BY group_assigned
        ", [$experimentId]);
        
        // Calcular significancia estadística (simplificado)
        $controlGroup = null;
        $testGroup = null;
        
        foreach ($results as $result) {
            if ($result['group_assigned'] === 'control') {
                $controlGroup = $result;
            } else {
                $testGroup = $result;
            }
        }
        
        $significance = null;
        if ($controlGroup && $testGroup && $controlGroup['participants'] > 30 && $testGroup['participants'] > 30) {
            $significance = self::calculateStatisticalSignificance($controlGroup, $testGroup);
        }
        
        return [
            'experiment' => $experiment,
            'results' => $results,
            'significance' => $significance,
            'control_group' => $controlGroup,
            'test_group' => $testGroup
        ];
    }
    
    /**
     * Calcular significancia estadística (Z-test simplificado)
     */
    private static function calculateStatisticalSignificance($controlGroup, $testGroup)
    {
        $p1 = $controlGroup['conversion_rate'];
        $n1 = $controlGroup['participants'];
        $p2 = $testGroup['conversion_rate'];
        $n2 = $testGroup['participants'];
        
        if ($n1 == 0 || $n2 == 0) {
            return ['significant' => false, 'p_value' => 1, 'confidence' => 0];
        }
        
        // Pooled proportion
        $pPool = (($p1 * $n1) + ($p2 * $n2)) / ($n1 + $n2);
        
        // Standard error
        $se = sqrt($pPool * (1 - $pPool) * ((1/$n1) + (1/$n2)));
        
        if ($se == 0) {
            return ['significant' => false, 'p_value' => 1, 'confidence' => 0];
        }
        
        // Z-score
        $z = ($p2 - $p1) / $se;
        
        // P-value (approximation)
        $pValue = 2 * (1 - self::normalCDF(abs($z)));
        
        $isSignificant = $pValue < 0.05;
        $confidence = (1 - $pValue) * 100;
        
        return [
            'significant' => $isSignificant,
            'p_value' => $pValue,
            'confidence' => round($confidence, 2),
            'z_score' => $z,
            'effect_size' => $p2 - $p1
        ];
    }
    
    /**
     * Aproximación de CDF normal (para cálculo de p-value)
     */
    private static function normalCDF($x)
    {
        return 0.5 * (1 + self::erf($x / sqrt(2)));
    }
    
    /**
     * Función error (aproximación)
     */
    private static function erf($x)
    {
        $a = 0.3275911;
        $a1 = 0.254829592;
        $a2 = -0.284496736;
        $a3 = 1.421413741;
        $a4 = -1.453152027;
        $a5 = 1.061405429;
        
        $sign = $x < 0 ? -1 : 1;
        $x = abs($x);
        
        $t = 1.0 / (1.0 + $a * $x);
        $y = 1.0 - ((($a5 * $t + $a4) * $t + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);
        
        return $sign * $y;
    }
    
    /**
     * Guardar precio dinámico calculado
     */
    private static function saveDynamicPrice($data)
    {
        $db = Database::getInstance();
        return $db->insert('dynamic_prices', $data);
    }
    
    /**
     * Log de cálculo de precio para analytics
     */
    private static function logPricingCalculation($productId, $priceData, $tenantId = null)
    {
        $db = Database::getInstance();
        
        $logData = [
            'tour_id' => $productId,
            'tenant_id' => $tenantId,
            'user_session_id' => session_id(),
            'original_price' => $priceData['base_price'],
            'calculated_price' => $priceData['calculated_price'],
            'algorithm_used' => $priceData['algorithm'],
            'input_snapshot' => json_encode([
                'timestamp' => time(),
                'multiplier' => $priceData['multiplier']
            ])
        ];
        
        $db->insert('pricing_logs', $logData);
    }
    
    /**
     * Actualizar performance de precio cuando hay una reserva
     */
    public static function trackPricePerformance($productId, $sessionId, $bookingAmount)
    {
        $db = Database::getInstance();
        
        // Actualizar log de pricing
        $db->query("
            UPDATE pricing_logs 
            SET price_accepted = TRUE, booking_created = TRUE
            WHERE tour_id = ? AND user_session_id = ?
            ORDER BY created_at DESC LIMIT 1
        ", [$productId, $sessionId]);
        
        // Actualizar dynamic prices
        $db->query("
            UPDATE dynamic_prices 
            SET bookings_count = bookings_count + 1,
                revenue_generated = revenue_generated + ?,
                conversion_rate = bookings_count / NULLIF(views_count, 0)
            WHERE tour_id = ? 
            AND valid_from <= CURRENT_TIMESTAMP 
            AND valid_to >= CURRENT_TIMESTAMP
        ", [$bookingAmount, $productId]);
        
        // Actualizar experimento si participa
        $db->query("
            UPDATE pricing_experiment_participants 
            SET booked = TRUE, booked_at = CURRENT_TIMESTAMP, booking_amount = ?
            WHERE session_id = ? AND tour_id = ?
        ", [$bookingAmount, $sessionId, $productId]);
    }
    
    /**
     * Obtener recomendaciones de precio para admin
     */
    public static function getPriceRecommendations($productId, $days = 30)
    {
        $performance = self::analyzePricingPerformance($productId, $days);
        $recommendations = [];
        
        if (empty($performance)) {
            $recommendations[] = [
                'type' => 'info',
                'title' => 'Enable Dynamic Pricing',
                'message' => 'Start using dynamic pricing to optimize revenue automatically.',
                'action' => 'Enable'
            ];
        } else {
            foreach ($performance as $algo) {
                if ($algo['avg_conversion_rate'] < 0.05) {
                    $recommendations[] = [
                        'type' => 'warning',
                        'title' => 'Low Conversion Rate',
                        'message' => "Algorithm '{$algo['algorithm_used']}' has low conversion ({$algo['avg_conversion_rate']}%). Consider adjusting parameters.",
                        'action' => 'Adjust'
                    ];
                }
                
                if ($algo['avg_multiplier'] > 2.0) {
                    $recommendations[] = [
                        'type' => 'caution',
                        'title' => 'High Price Multiplier',
                        'message' => "Average multiplier is {$algo['avg_multiplier']}x. Monitor for customer resistance.",
                        'action' => 'Monitor'
                    ];
                }
            }
        }
        
        return $recommendations;
    }
}