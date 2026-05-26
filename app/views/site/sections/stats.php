<?php
/**
 * Stats Section
 * Muestra estadísticas clave del negocio
 */

use App\Core\Config;
use App\Core\Database;

$config = json_decode($section['section_config'] ?? '{}', true);

// Stats por defecto desde configuración
$stats = $config['stats'] ?? [
    ['icon' => 'fas fa-users', 'number' => '10000+', 'label' => 'Clientes Satisfechos'],
    ['icon' => 'fas fa-map-marked-alt', 'number' => '50+', 'label' => 'Destinos'],
    ['icon' => 'fas fa-star', 'number' => '4.9/5', 'label' => 'Calificación'],
    ['icon' => 'fas fa-award', 'number' => '15+', 'label' => 'Años de Experiencia']
];

// Si no hay stats personalizadas, obtener de la base de datos
if (empty($config['stats'])) {
    try {
        $db = Database::getInstance();

        // Contar clientes
        $clientesCount = $db->fetchOne("SELECT COUNT(*) as total FROM usuarios WHERE tipo = 'cliente'")['total'] ?? 0;

        // Contar tours activos
        $toursCount = $db->fetchOne("SELECT COUNT(*) as total FROM tours WHERE activo = 1")['total'] ?? 0;

        // Calcular calificación promedio
        $avgRating = $db->fetchOne("SELECT AVG(rating) as avg FROM reviews WHERE aprobado = 1")['avg'] ?? 0;
        $avgRating = number_format($avgRating, 1);

        // Contar reservas completadas
        $reservasCount = $db->fetchOne("SELECT COUNT(*) as total FROM reservas WHERE estado IN ('confirmada', 'pagada')")['total'] ?? 0;

        $stats = [
            ['icon' => 'fas fa-users', 'number' => number_format($clientesCount) . '+', 'label' => 'Clientes Satisfechos'],
            ['icon' => 'fas fa-map-marked-alt', 'number' => $toursCount . '+', 'label' => 'Destinos Disponibles'],
            ['icon' => 'fas fa-star', 'number' => $avgRating . '/5', 'label' => 'Calificación Promedio'],
            ['icon' => 'fas fa-check-circle', 'number' => number_format($reservasCount) . '+', 'label' => 'Reservas Completadas']
        ];
    } catch (Exception $e) {
        // Si hay error, usar stats por defecto
    }
}

$backgroundColor = $config['background_color'] ?? '#f8f9fa';
$textColor = $config['text_color'] ?? '#212529';
?>

<!-- Stats Section -->
<section class="stats-section py-5" style="background-color: <?= htmlspecialchars($backgroundColor) ?>; color: <?= htmlspecialchars($textColor) ?>;">
    <div class="container">
        <div class="row g-4">
            <?php foreach ($stats as $stat): ?>
                <div class="col-6 col-md-3">
                    <div class="stat-card text-center">
                        <div class="stat-icon mb-3">
                            <i class="<?= htmlspecialchars($stat['icon']) ?> fa-3x"></i>
                        </div>
                        <h3 class="stat-number fw-bold mb-2" data-target="<?= htmlspecialchars($stat['number']) ?>">
                            <?= htmlspecialchars($stat['number']) ?>
                        </h3>
                        <p class="stat-label text-muted mb-0">
                            <?= htmlspecialchars($stat['label']) ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
.stats-section {
    position: relative;
}

.stat-card {
    padding: 20px;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    color: var(--bs-primary, #0d6efd);
    opacity: 0.8;
}

.stat-number {
    font-size: 2.5rem;
    line-height: 1;
}

.stat-label {
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@media (max-width: 768px) {
    .stat-number {
        font-size: 1.8rem;
    }

    .stat-icon i {
        font-size: 2rem !important;
    }
}
</style>
