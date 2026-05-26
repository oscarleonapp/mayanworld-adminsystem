<?php
/**
 * Featured Section
 * Muestra tours destacados
 */

use App\Core\Config;
use App\Core\Helpers;
use App\Core\Database;

$config = json_decode($section['section_config'] ?? '{}', true);
$sectionTitle = $config['title'] ?? 'Destinos Destacados';
$sectionSubtitle = $config['subtitle'] ?? 'Los tours más populares seleccionados para ti';
$limit = $config['limit'] ?? 6;

// Obtener tours destacados de la base de datos
$featuredProducts = [];
try {
    $db = Database::getInstance();
    $featuredProducts = $db->fetchAll(
        "SELECT p.*, c.nombre as categoria_nombre
         FROM tours p
         LEFT JOIN categorias c ON p.categoria_id = c.id
         WHERE p.activo = 1 AND p.destacado = 1
         ORDER BY p.created_at DESC
         LIMIT ?",
        [$limit]
    );
} catch (Exception $e) {
    // Si hay error, continuar sin tours
}
?>

<!-- Featured Products Section -->
<section class="featured-section py-5">
    <div class="container">
        <!-- Section Header -->
        <div class="text-center mb-5">
            <h2 class="h1 fw-bold mb-3"><?= htmlspecialchars($sectionTitle) ?></h2>
            <?php if (!empty($sectionSubtitle)): ?>
                <p class="lead text-muted"><?= htmlspecialchars($sectionSubtitle) ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($featuredProducts)): ?>
            <!-- Products Grid -->
            <div class="row g-4">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="product-card h-100">
                            <!-- Tour Image -->
                            <div class="product-image position-relative">
                                <?php if (!empty($product['imagen_principal'])): ?>
                                    <img src="<?= htmlspecialchars(Helpers::tourImage($product['imagen_principal'] ?? null, 'images/default-destination.jpg')) ?>"
                                         alt="<?= htmlspecialchars($product['nombre']) ?>"
                                         class="w-100"
                                         loading="lazy"
                                         decoding="async">
                                <?php else: ?>
                                    <div class="placeholder-image">
                                        <i class="fas fa-image fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>

                                <!-- Category Badge -->
                                <?php if (!empty($product['categoria_nombre'])): ?>
                                    <span class="category-badge">
                                        <?= htmlspecialchars($product['categoria_nombre']) ?>
                                    </span>
                                <?php endif; ?>

                                <!-- Featured Badge -->
                                <span class="featured-badge">
                                    <i class="fas fa-star"></i> Destacado
                                </span>
                            </div>

                            <!-- Tour Info -->
                            <div class="product-body">
                                <h3 class="product-title h5 mb-2">
                                    <a href="<?= Config::getBaseUrl() ?>?route=tour/<?= $product['id'] ?>"
                                       class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($product['nombre']) ?>
                                    </a>
                                </h3>

                                <?php if (!empty($product['descripcion_corta'])): ?>
                                    <p class="product-description text-muted mb-3">
                                        <?= htmlspecialchars(substr($product['descripcion_corta'], 0, 100)) ?>
                                        <?= strlen($product['descripcion_corta']) > 100 ? '...' : '' ?>
                                    </p>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="product-price">
                                        <span class="h4 mb-0 text-primary fw-bold">
                                            <?= Helpers::formatPrice($product['precio']) ?>
                                        </span>
                                        <?php if (!empty($product['duracion_dias'])): ?>
                                            <small class="text-muted d-block">
                                                <i class="far fa-clock"></i> <?= $product['duracion_dias'] ?> días
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?= Config::getBaseUrl() ?>?route=tour/<?= $product['id'] ?>"
                                       class="btn btn-primary">
                                        Ver más
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- View All Button -->
            <div class="text-center mt-5">
                <a href="<?= Config::getBaseUrl() ?>?route=tours"
                   class="btn btn-outline-primary btn-lg px-5">
                    Ver Todos los Destinos
                    <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        <?php else: ?>
            <!-- No Products Message -->
            <div class="text-center py-5">
                <i class="fas fa-map-marked-alt fa-4x text-muted mb-3"></i>
                <p class="text-muted">No hay tours destacados disponibles en este momento.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.featured-section {
    background: linear-gradient(to bottom, #ffffff 0%, #f8f9fa 100%);
}

.product-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.product-image {
    height: 250px;
    overflow: hidden;
    background: #f8f9fa;
}

.product-image img {
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.1);
}

.placeholder-image {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.category-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: rgba(255,255,255,0.95);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #212529;
    text-transform: uppercase;
}

.featured-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.product-body {
    padding: 20px;
}

.product-title {
    font-weight: 700;
    line-height: 1.4;
}

.product-title a:hover {
    color: var(--bs-primary) !important;
}

.product-description {
    font-size: 0.9rem;
    line-height: 1.6;
}

.product-price {
    flex: 1;
}

@media (max-width: 768px) {
    .product-image {
        height: 200px;
    }
}
</style>
