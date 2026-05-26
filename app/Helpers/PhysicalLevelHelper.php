<?php

namespace App\Helpers;

use DateTime;

class PhysicalLevelHelper 
{
    /**
     * Mapeo de niveles físicos según estándar de la industria
     * Basado en Viator, GetYourGuide, y otros operadores
     */
    private static $levels = [
        1 => [
            'label' => 'Muy Fácil',
            'description' => 'Apropiado para todas las edades y niveles de condición física',
            'icon' => 'fas fa-child',
            'color' => 'success',
            'examples' => 'Caminatas cortas, tours en vehículo'
        ],
        2 => [
            'label' => 'Fácil',
            'description' => 'Actividad ligera, caminatas menores a 2km',
            'icon' => 'fas fa-walking',
            'color' => 'info',
            'examples' => 'Recorridos urbanos, senderos planos'
        ],
        3 => [
            'label' => 'Moderado',
            'description' => 'Requiere condición física básica, caminatas de 2-5km',
            'icon' => 'fas fa-hiking',
            'color' => 'warning',
            'examples' => 'Senderos con pendientes, tours de día completo'
        ],
        4 => [
            'label' => 'Desafiante',
            'description' => 'Buena condición física requerida, caminatas de 5-10km',
            'icon' => 'fas fa-mountain',
            'color' => 'orange',
            'examples' => 'Ascensos pronunciados, trekking de altura'
        ],
        5 => [
            'label' => 'Extremo',
            'description' => 'Excelente condición física, experiencia previa recomendada',
            'icon' => 'fas fa-flag-checkered',
            'color' => 'danger',
            'examples' => 'Alpinismo, trekking multi-día'
        ]
    ];
    
    /**
     * Obtiene información del nivel físico
     */
    public static function getLevelInfo($level) 
    {
        $level = (int)$level;
        if ($level < 1 || $level > 5) {
            $level = 1; // Default to easiest level
        }
        
        return self::$levels[$level];
    }
    
    /**
     * Calcula nivel físico basándose en características del tour
     */
    public static function calculateLevel($product) 
    {
        $level = 1; // Default
        
        // Basarse en dificultad existente
        $difficulty = strtolower($product['dificultad'] ?? 'facil');
        switch($difficulty) {
            case 'facil':
                $level = rand(1, 2);
                break;
            case 'moderado':
            case 'medio':
                $level = rand(2, 3);
                break;
            case 'dificil':
                $level = rand(3, 5);
                break;
        }
        
        // Ajustar basándose en duración
        $duracion = (int)($product['duracion_dias'] ?? 1);
        if ($duracion >= 3) $level = min(5, $level + 1);
        if ($duracion >= 7) $level = min(5, $level + 1);
        
        // Ajustar basándose en tipo/categoría
        $categoria = strtolower($product['categoria_nombre'] ?? '');
        if (strpos($categoria, 'trek') !== false || strpos($categoria, 'volcan') !== false) {
            $level = min(5, $level + 1);
        }
        
        return max(1, min(5, $level));
    }
    
    /**
     * Renderiza el badge de nivel físico
     */
    public static function renderBadge($level, $size = 'normal') 
    {
        $info = self::getLevelInfo($level);
        $sizeClass = $size === 'small' ? 'badge-sm' : '';
        $sizeClass = $size === 'large' ? 'badge-lg' : $sizeClass;
        
        return sprintf(
            '<span class="physical-level-badge badge bg-%s %s" data-bs-toggle="tooltip" title="%s">
                <i class="%s me-1"></i>
                Nivel %d: %s
            </span>',
            $info['color'],
            $sizeClass,
            htmlspecialchars($info['description']),
            $info['icon'],
            $level,
            htmlspecialchars($info['label'])
        );
    }
    
    /**
     * Renderiza información detallada del nivel físico
     */
    public static function renderDetailedInfo($level) 
    {
        $info = self::getLevelInfo($level);
        
        return sprintf(
            '<div class="physical-level-info">
                <div class="level-header d-flex align-items-center mb-2">
                    <i class="%s text-%s me-2 fs-4"></i>
                    <div>
                        <h6 class="mb-0">Nivel Físico %d: %s</h6>
                        <small class="text-muted">%s</small>
                    </div>
                </div>
                <div class="level-scale mb-2">
                    <div class="d-flex justify-content-between small">
                        <span>Muy Fácil</span>
                        <span>Extremo</span>
                    </div>
                    <div class="level-bar">
                        %s
                    </div>
                </div>
                <div class="level-examples">
                    <strong>Ejemplos:</strong> %s
                </div>
            </div>',
            $info['icon'],
            $info['color'],
            $level,
            $info['label'],
            $info['description'],
            self::renderLevelScale($level),
            $info['examples']
        );
    }
    
    /**
     * Renderiza escala visual del nivel
     */
    private static function renderLevelScale($currentLevel) 
    {
        $html = '<div class="progress" style="height: 6px;">';
        
        for ($i = 1; $i <= 5; $i++) {
            $width = 20; // 100% / 5 levels
            $active = $i <= $currentLevel;
            $color = $active ? 'bg-' . self::$levels[$currentLevel]['color'] : 'bg-light';
            
            $html .= sprintf(
                '<div class="progress-bar %s" style="width: %s%%" role="progressbar"></div>',
                $color,
                $width
            );
        }
        
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Obtiene recomendaciones basándose en el nivel físico
     */
    public static function getRecommendations($level) 
    {
        $recommendations = [
            1 => [
                'age' => 'Todas las edades',
                'preparation' => 'No se requiere preparación especial',
                'equipment' => 'Ropa cómoda y calzado para caminar',
                'restrictions' => 'Ninguna restricción médica específica'
            ],
            2 => [
                'age' => 'Niños de 6+ años',
                'preparation' => 'Capacidad de caminar 1-2 horas',
                'equipment' => 'Calzado deportivo, agua, protector solar',
                'restrictions' => 'No recomendado para movilidad muy limitada'
            ],
            3 => [
                'age' => 'Adolescentes y adultos',
                'preparation' => 'Actividad física regular recomendada',
                'equipment' => 'Botas de trekking, bastones, mochila',
                'restrictions' => 'Consultar con problemas cardíacos o articulares'
            ],
            4 => [
                'age' => 'Adultos en buena forma',
                'preparation' => 'Entrenar 4-6 semanas antes',
                'equipment' => 'Equipo de montaña, GPS, kit primeros auxilios',
                'restrictions' => 'No recomendado embarazo, problemas cardíacos'
            ],
            5 => [
                'age' => 'Adultos experimentados',
                'preparation' => 'Experiencia previa y entrenamiento específico',
                'equipment' => 'Equipo técnico completo, seguro de alta montaña',
                'restrictions' => 'Certificado médico requerido'
            ]
        ];
        
        return $recommendations[$level] ?? $recommendations[1];
    }
}