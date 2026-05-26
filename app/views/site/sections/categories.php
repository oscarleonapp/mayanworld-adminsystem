<?php
/**
 * Categories Section
 * Muestra las categorías de tours disponibles
 */

use App\Core\Config;
use App\Core\Database;
use App\Core\Helpers;

$config = json_decode($section['section_config'] ?? '{}', true);
$sectionTitle = $config['title'] ?? 'Explora por Categoría';
$sectionSubtitle = $config['subtitle'] ?? 'Encuentra la experiencia perfecta para ti';

// Obtener categorías de la base de datos
$categories = [];
try {
    $db = Database::getInstance();
    $categories = $db->fetchAll(
        "SELECT c.*, COUNT(p.id) as product_count
         FROM categorias c
         LEFT JOIN tours p ON c.id = p.categoria_id AND p.activo = 1
         WHERE c.activo = 1
         GROUP BY c.id
         ORDER BY c.orden ASC, c.nombre ASC"
    );
} catch (Exception $e) {
    // Si hay error, continuar sin categorías
}

// Iconos por defecto por categoría
$defaultIcons = [
    'playas' => 'fas fa-umbrella-beach',
    'montana' => 'fas fa-mountain',
    'montaña' => 'fas fa-mountain',
    'ciudad' => 'fas fa-city',
    'aventura' => 'fas fa-hiking',
    'relax' => 'fas fa-spa',
    'cultura' => 'fas fa-landmark',
    'naturaleza' => 'fas fa-tree',
];
?>

<!-- Categories Section -->
<section class="categories-section py-5 bg-light">
    <div class="container">
        <!-- Section Header -->
        <div class="text-center mb-5">
            <h2 class="h1 fw-bold mb-3"><?= htmlspecialchars($sectionTitle) ?></h2>
            <?php if (!empty($sectionSubtitle)): ?>
                <p class="lead text-muted"><?= htmlspecialchars($sectionSubtitle) ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($categories)): ?>
            <!-- Categories Grid -->
            <div class="row g-4">
                <?php foreach ($categories as $category): ?>
                    <?php
                    $slug = $category['slug'] ?? strtolower($category['nombre']);
                    $icon = $category['icono'] ?? ($defaultIcons[$slug] ?? 'fas fa-map-marked-alt');
                    $categoryName = $category['nombre'] ?? '';
                    $categoryDescription = $category['descripcion'] ?? '';
                    ?>
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="<?= Config::getBaseUrl() ?>?route=tours&category=<?= $category['id'] ?>"
                           class="category-card text-decoration-none">
                            <div class="category-icon">
                                <i class="<?= htmlspecialchars($icon) ?> fa-3x"></i>
                            </div>
                            <h3 class="category-name h5 mb-2">
                                <?= htmlspecialchars($categoryName) ?>
                            </h3>
                            <?php if (!empty($categoryDescription)): ?>
                                <p class="category-description text-muted small">
                                    <?= htmlspecialchars(substr($categoryDescription, 0, 60)) ?>
                                    <?= strlen($categoryDescription) > 60 ? '...' : '' ?>
                                </p>
                            <?php endif; ?>
                            <div class="category-count">
                                <span class="badge bg-primary">
                                    <?= (int)$category['product_count'] ?> <?= $category['product_count'] == 1 ? 'tour' : 'tours' ?>
                                </span>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- No Categories Message -->
            <div class="text-center py-5">
                <i class="fas fa-tags fa-4x text-muted mb-3"></i>
                <p class="text-muted">No hay categorías disponibles en este momento.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.categories-section {
    position: relative;
}

.category-card {
    display: block;
    background: white;
    border-radius: 12px;
    padding: 30px 20px;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    height: 100%;
    color: inherit;
}

.category-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    color: inherit;
}

.category-icon {
    margin-bottom: 20px;
    color: var(--bs-primary, #0d6efd);
    transition: all 0.3s ease;
}

.category-card:hover .category-icon {
    transform: scale(1.1);
    color: var(--bs-primary, #0d6efd);
}

.category-name {
    font-weight: 700;
    color: #212529;
    margin-bottom: 10px;
    transition: color 0.3s ease;
}

.category-card:hover .category-name {
    color: var(--bs-primary, #0d6efd);
}

.category-description {
    font-size: 0.85rem;
    line-height: 1.5;
    margin-bottom: 15px;
    min-height: 40px;
}

.category-count {
    margin-top: 10px;
}

.category-count .badge {
    font-size: 0.75rem;
    padding: 6px 12px;
    font-weight: 600;
}

@media (max-width: 768px) {
    .category-card {
        padding: 20px 15px;
    }

    .category-icon i {
        font-size: 2rem !important;
    }

    .category-description {
        display: none;
    }
}
</style>
