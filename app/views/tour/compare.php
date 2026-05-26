<?php

use App\Core\Config;
use App\Core\Helpers;
/**
 * Vista: Comparador de Tours
 * Permite comparar hasta 3 tours lado a lado
 */

$title = 'Comparar Tours | Travel Mayan World';
$metaDescription = 'Compara tours y encuentra el perfecto para ti';

require_once __DIR__ . '/../layouts/header.php';
?>

<div class="container my-5">
    <!-- Header -->
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold mb-3">
            <i class="fas fa-exchange-alt text-primary me-3"></i>
            Comparar Tours
        </h1>
        <p class="lead text-muted">
            Compara características, precios y beneficios para tomar la mejor decisión
        </p>
    </div>

    <?php if (empty($products) || count($products) < 2): ?>
        <!-- No hay suficientes tours para comparar -->
        <div class="alert alert-warning text-center">
            <i class="fas fa-info-circle me-2"></i>
            Necesitas seleccionar al menos 2 tours para comparar.
            <a href="<?= Config::getBaseUrl() ?>?route=tours" class="alert-link">Ir al catálogo</a>
        </div>
    <?php else: ?>
        <!-- Tabla de comparación -->
        <div class="comparison-table-wrapper">
            <div class="comparison-controls mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <span class="badge bg-primary">
                        Comparando <?= count($products) ?> tours
                    </span>
                </div>
                <div>
                    <a href="<?= Config::getBaseUrl() ?>?route=tours" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-plus me-1"></i>Agregar más tours
                    </a>
                </div>
            </div>

            <div class="table-responsive comparison-table-container">
                <table class="table table-bordered comparison-table">
                    <!-- Headers con imágenes -->
                    <thead>
                        <tr class="comparison-header-row">
                            <th class="comparison-feature-col sticky-col">
                                <div class="feature-header">
                                    <i class="fas fa-list me-2"></i>
                                    Características
                                </div>
                            </th>
                            <?php foreach ($products as $product): ?>
                                <th class="comparison-product-col">
                                    <div class="product-header-card">
                                        <!-- Imagen -->
                                        <div class="product-header-image">
                                            <?php
                                            $imgUrl = Helpers::tourImage($product['imagen_principal'] ?? null, 'images/default-destination.jpg');
                                            ?>
                                            <img src="<?= $imgUrl ?>" alt="<?= htmlspecialchars($product['nombre']) ?>" class="img-fluid">

                                            <!-- Badges -->
                                            <div class="product-header-badges">
                                                <?php if (!empty($product['destacado'])): ?>
                                                    <span class="badge bg-warning text-dark">Destacado</span>
                                                <?php endif; ?>
                                                <?php if (!empty($product['precio_descuento']) && $product['precio_descuento'] < ($product['precio'] ?? 0)): ?>
                                                    <span class="badge bg-danger">Oferta</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Nombre -->
                                        <h5 class="product-header-title mt-3 mb-2">
                                            <?= htmlspecialchars($product['nombre']) ?>
                                        </h5>

                                        <!-- Rating -->
                                        <?php if (!empty($product['rating']) && $product['rating'] > 0): ?>
                                            <div class="product-header-rating mb-2">
                                                <i class="fas fa-star text-warning"></i>
                                                <strong><?= number_format($product['rating'], 1) ?></strong>
                                                <small class="text-muted">(<?= $product['review_count'] ?? 0 ?>)</small>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Precio -->
                                        <div class="product-header-price mb-3">
                                            <?php if (!empty($product['precio_descuento']) && $product['precio_descuento'] < ($product['precio'] ?? 0)): ?>
                                                <div class="price-original text-muted text-decoration-line-through small">
                                                    $<?= number_format($product['precio'] ?? 0, 0) ?> USD
                                                </div>
                                                <div class="price-current text-success fw-bold h4 mb-0">
                                                    $<?= number_format($product['precio_descuento'], 0) ?> USD
                                                </div>
                                                <small class="text-muted">por persona</small>
                                            <?php else: ?>
                                                <div class="price-current text-primary fw-bold h4 mb-0">
                                                    $<?= number_format($product['precio'] ?? 0, 0) ?> USD
                                                </div>
                                                <small class="text-muted">por persona</small>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Botones de acción -->
                                        <div class="product-header-actions">
                                            <a href="<?= Config::getBaseUrl() ?>?route=tour/<?= $product['id'] ?>"
                                               class="btn btn-outline-primary btn-sm mb-2 w-100">
                                                <i class="fas fa-eye me-1"></i>Ver detalles
                                            </a>
                                            <a href="<?= Config::getBaseUrl() ?>?route=booking/process&tour_id=<?= $product['id'] ?>"
                                               class="btn btn-primary btn-sm w-100">
                                                <i class="fas fa-calendar-check me-1"></i>Reservar
                                            </a>
                                            <button class="btn btn-sm btn-outline-danger w-100 mt-2"
                                                    onclick="removeFromComparison(<?= $product['id'] ?>)">
                                                <i class="fas fa-times me-1"></i>Quitar
                                            </button>
                                        </div>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>

                    <tbody>
                        <!-- Duración -->
                        <tr>
                            <td class="feature-label sticky-col">
                                <i class="fas fa-clock text-primary me-2"></i>
                                <strong>Duración</strong>
                            </td>
                            <?php foreach ($products as $product): ?>
                                <td class="text-center">
                                    <span class="feature-value">
                                        <?= $product['duracion_dias'] ?? 1 ?> día<?= ($product['duracion_dias'] ?? 1) > 1 ? 's' : '' ?>
                                    </span>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Dificultad -->
                        <tr>
                            <td class="feature-label sticky-col">
                                <i class="fas fa-mountain text-primary me-2"></i>
                                <strong>Nivel de dificultad</strong>
                            </td>
                            <?php foreach ($products as $product): ?>
                                <td class="text-center">
                                    <?php
                                    $difficulty = $product['dificultad'] ?? 'moderado';
                                    $badgeClass = match($difficulty) {
                                        'facil' => 'bg-success',
                                        'moderado' => 'bg-warning text-dark',
                                        'dificil' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= ucfirst($difficulty) ?>
                                    </span>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Capacidad máxima -->
                        <tr>
                            <td class="feature-label sticky-col">
                                <i class="fas fa-users text-primary me-2"></i>
                                <strong>Capacidad máxima</strong>
                            </td>
                            <?php foreach ($products as $product): ?>
                                <td class="text-center">
                                    <span class="feature-value">
                                        <?= $product['capacidad_maxima'] ?? 'N/A' ?> personas
                                    </span>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Categoría -->
                        <tr>
                            <td class="feature-label sticky-col">
                                <i class="fas fa-tag text-primary me-2"></i>
                                <strong>Categoría</strong>
                            </td>
                            <?php foreach ($products as $product): ?>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark">
                                        <?= htmlspecialchars($product['categoria_nombre'] ?? 'Sin categoría') ?>
                                    </span>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Separador -->
                        <tr class="table-section-header">
                            <td colspan="<?= count($products) + 1 ?>" class="text-center bg-light">
                                <strong><i class="fas fa-check-circle me-2"></i>¿Qué incluye?</strong>
                            </td>
                        </tr>

                        <!-- Incluye -->
                        <tr>
                            <td class="feature-label sticky-col">
                                <i class="fas fa-check text-success me-2"></i>
                                <strong>Incluye</strong>
                            </td>
                            <?php foreach ($products as $product): ?>
                                <td>
                                    <?php if (!empty($product['incluye'])): ?>
                                        <ul class="feature-list">
                                            <?php
                                            $includes = explode(',', $product['incluye']);
                                            foreach (array_slice($includes, 0, 5) as $item):
                                                $item = trim($item);
                                                if (empty($item)) continue;
                                            ?>
                                                <li><i class="fas fa-check-circle text-success me-1"></i><?= htmlspecialchars($item) ?></li>
                                            <?php endforeach; ?>
                                            <?php if (count($includes) > 5): ?>
                                                <li class="text-muted"><small>+ <?= count($includes) - 5 ?> más...</small></li>
                                            <?php endif; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted">No especificado</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- No incluye -->
                        <tr>
                            <td class="feature-label sticky-col">
                                <i class="fas fa-times text-danger me-2"></i>
                                <strong>No incluye</strong>
                            </td>
                            <?php foreach ($products as $product): ?>
                                <td>
                                    <?php if (!empty($product['no_incluye'])): ?>
                                        <ul class="feature-list">
                                            <?php
                                            $excludes = explode(',', $product['no_incluye']);
                                            foreach (array_slice($excludes, 0, 5) as $item):
                                                $item = trim($item);
                                                if (empty($item)) continue;
                                            ?>
                                                <li><i class="fas fa-times-circle text-danger me-1"></i><?= htmlspecialchars($item) ?></li>
                                            <?php endforeach; ?>
                                            <?php if (count($excludes) > 5): ?>
                                                <li class="text-muted"><small>+ <?= count($excludes) - 5 ?> más...</small></li>
                                            <?php endif; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted">No especificado</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Separador -->
                        <tr class="table-section-header">
                            <td colspan="<?= count($products) + 1 ?>" class="text-center bg-light">
                                <strong><i class="fas fa-info-circle me-2"></i>Información adicional</strong>
                            </td>
                        </tr>

                        <!-- Cancelación -->
                        <tr>
                            <td class="feature-label sticky-col">
                                <i class="fas fa-rotate-left text-primary me-2"></i>
                                <strong>Cancelación</strong>
                            </td>
                            <?php foreach ($products as $product): ?>
                                <td class="text-center">
                                    <?php if (!empty($product['politicas_cancelacion'])): ?>
                                        <small class="text-muted">
                                            <?= htmlspecialchars(Helpers::truncate($product['politicas_cancelacion'], 80)) ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="badge bg-success">Flexible</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Ubicación -->
                        <tr>
                            <td class="feature-label sticky-col">
                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                <strong>Ubicación</strong>
                            </td>
                            <?php foreach ($products as $product): ?>
                                <td class="text-center">
                                    <small><?= htmlspecialchars($product['ubicacion'] ?? 'Por confirmar') ?></small>
                                </td>
                            <?php endforeach; ?>
                        </tr>

                        <!-- Acciones finales -->
                        <tr class="comparison-footer-row">
                            <td class="sticky-col"></td>
                            <?php foreach ($products as $product): ?>
                                <td class="text-center">
                                    <a href="<?= Config::getBaseUrl() ?>?route=booking/process&tour_id=<?= $product['id'] ?>"
                                       class="btn btn-success btn-lg w-100">
                                        <i class="fas fa-calendar-check me-2"></i>Reservar Ahora
                                    </a>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Opciones adicionales -->
        <div class="row mt-5">
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-question-circle fa-3x text-primary mb-3"></i>
                        <h5>¿Necesitas ayuda para decidir?</h5>
                        <p class="text-muted">Nuestro equipo puede ayudarte a elegir el tour perfecto</p>
                        <a href="https://wa.me/<?= str_replace(['+', ' ', '-'], '', Config::SOCIAL_WHATSAPP ?? '') ?>?text=Hola,%20necesito%20ayuda%20para%20elegir%20un%20tour"
                           target="_blank"
                           class="btn btn-success">
                            <i class="fab fa-whatsapp me-2"></i>Contactar por WhatsApp
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <i class="fas fa-th-large fa-3x text-primary mb-3"></i>
                        <h5>¿Quieres ver más opciones?</h5>
                        <p class="text-muted">Explora nuestro catálogo completo de tours</p>
                        <a href="<?= Config::getBaseUrl() ?>?route=tours" class="btn btn-primary">
                            <i class="fas fa-search me-2"></i>Ver todos los tours
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Estilos -->
<style>
.comparison-table-wrapper {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.comparison-table-container {
    overflow-x: auto;
}

.comparison-table {
    min-width: 800px;
    margin-bottom: 0;
}

.comparison-table th,
.comparison-table td {
    vertical-align: middle;
    padding: 1.25rem;
}

.sticky-col {
    position: sticky;
    left: 0;
    background: white !important;
    z-index: 10;
    box-shadow: 2px 0 5px rgba(0,0,0,0.05);
}

.comparison-feature-col {
    min-width: 200px;
    font-weight: 600;
}

.comparison-product-col {
    min-width: 280px;
    max-width: 300px;
}

.product-header-card {
    padding: 1rem;
}

.product-header-image {
    position: relative;
    border-radius: 8px;
    overflow: hidden;
    height: 150px;
}

.product-header-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-header-badges {
    position: absolute;
    top: 8px;
    right: 8px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.product-header-title {
    font-size: 1.1rem;
    line-height: 1.3;
    min-height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.product-header-rating {
    font-size: 0.9rem;
}

.product-header-price {
    padding: 1rem 0;
    border-top: 1px solid #e9ecef;
    border-bottom: 1px solid #e9ecef;
}

.feature-label {
    background: #f8f9fa;
}

.feature-value {
    font-weight: 600;
    color: #333;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: left;
}

.feature-list li {
    padding: 0.25rem 0;
    font-size: 0.9rem;
}

.table-section-header {
    background: #f8f9fa;
    font-weight: 600;
}

.comparison-footer-row td {
    padding: 1.5rem 1rem;
}

@media (max-width: 991.98px) {
    .comparison-table-wrapper {
        padding: 1rem;
    }

    .product-header-title {
        font-size: 1rem;
        min-height: 50px;
    }
}
</style>

<!-- JavaScript -->
<script>
function removeFromComparison(productId) {
    if (confirm('¿Quitar este tour de la comparación?')) {
        // Obtener IDs actuales
        const params = new URLSearchParams(window.location.search);
        let ids = params.get('ids') ? params.get('ids').split(',') : [];

        // Remover el ID
        ids = ids.filter(id => id != productId);

        if (ids.length < 2) {
            alert('Necesitas al menos 2 tours para comparar. Serás redirigido al catálogo.');
            window.location.href = '<?= Config::getBaseUrl() ?>?route=tours';
        } else {
            // Recargar con nuevos IDs
            window.location.href = '<?= Config::getBaseUrl() ?>?route=tour/compare&ids=' + ids.join(',');
        }
    }
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
