<?php
/**
 * Reviews/Testimonial Section for homepage
 */

use App\Core\Config;
use App\Core\Helpers;

$sectionConfig = json_decode($section['section_config'] ?? '{}', true);
$sectionTitle = $sectionConfig['title'] ?? $section['section_title'] ?? 'Lo que dicen nuestros viajeros';
$sectionSubtitle = $sectionConfig['subtitle'] ?? 'Experiencias reales de clientes satisfechos';
$limit = (int)($sectionConfig['limit'] ?? 6);
$showViewAll = ($sectionConfig['show_view_all'] ?? true) !== false;

$reviewsData = $reviews ?? [];
if ($limit > 0) {
    $reviewsData = array_slice($reviewsData, 0, $limit);
}
?>

<?php if (!empty($reviewsData)): ?>
<section class="reviews-section py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold mb-3"><?= htmlspecialchars($sectionTitle) ?></h2>
            <?php if (!empty($sectionSubtitle)): ?>
                <p class="text-muted lead mb-0"><?= htmlspecialchars($sectionSubtitle) ?></p>
            <?php endif; ?>
        </div>

        <div class="row g-4">
            <?php foreach ($reviewsData as $review): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="review-card h-100 p-4 shadow-sm border-0">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?= $i <= (int)$review['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="badge bg-primary"><?= (int)$review['rating'] ?>/5</span>
                        </div>
                        <?php if (!empty($review['tour_nombre'])): ?>
                            <span class="badge bg-light text-primary mb-3">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?= htmlspecialchars($review['tour_nombre']) ?>
                            </span>
                        <?php endif; ?>

                        <p class="text-muted mb-4">
                            <i class="fas fa-quote-left text-primary me-2"></i>
                            <?= htmlspecialchars($review['comentario']) ?>
                            <i class="fas fa-quote-right text-primary ms-2"></i>
                        </p>

                        <div class="d-flex align-items-center border-top pt-3">
                            <div class="review-avatar me-3">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <strong><?= htmlspecialchars($review['nombre']) ?></strong>
                                <div class="text-muted small">
                                    <?= date('d M Y', strtotime($review['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($showViewAll): ?>
        <div class="text-center mt-5">
            <a href="<?= Config::getBaseUrl() ?>?route=reviews" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-comments me-2"></i>Ver todas las reseñas
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.reviews-section .review-card {
    border-radius: 16px;
    background: #fff;
    transition: all 0.3s ease;
}
.reviews-section .review-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 1.25rem 3rem rgba(0,0,0,0.12) !important;
}
.reviews-section .review-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4e54c8 0%, #8f94fb 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>
<?php endif; ?>
