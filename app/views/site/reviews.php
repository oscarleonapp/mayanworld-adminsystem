<?php
use App\Core\Config;
use App\Core\Helpers;
include __DIR__ . '/../layouts/header.php';
?>

<!-- Hero Section -->
<section class="bg-gradient-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">Reseñas de Nuestros Clientes</h1>
                <p class="lead mb-0">Experiencias reales de viajeros satisfechos</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="rating-summary bg-white text-dark rounded p-4 shadow">
                    <div class="display-3 fw-bold text-warning mb-2"><?= $avgRating ?></div>
                    <div class="mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?= $i <= round($avgRating) ? 'text-warning' : 'text-muted' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="mb-0 text-muted">Basado en <?= $totalReviews ?> reseñas</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Reviews Grid -->
<section class="py-5">
    <div class="container">
        <?php if (!empty($reviews)): ?>
        <div class="row g-4 mb-5">
            <?php foreach ($reviews as $review): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm hover-lift">
                    <div class="card-body p-4">
                        <!-- Rating -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?= $i <= $review['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="badge bg-primary"><?= $review['rating'] ?>/5</span>
                        </div>

                        <!-- Tour Badge (if applicable) -->
                        <?php if (!empty($review['tour_nombre'])): ?>
                        <div class="mb-3">
                            <span class="badge bg-info">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?= htmlspecialchars($review['tour_nombre']) ?>
                            </span>
                        </div>
                        <?php endif; ?>

                        <!-- Comment -->
                        <p class="card-text mb-4">
                            <i class="fas fa-quote-left text-primary me-2"></i>
                            <?= htmlspecialchars($review['comentario']) ?>
                            <i class="fas fa-quote-right text-primary ms-2"></i>
                        </p>

                        <!-- Author & Date -->
                        <div class="d-flex align-items-center mt-auto pt-3 border-top">
                            <div class="avatar-circle me-3">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <div>
                                <strong class="d-block"><?= htmlspecialchars($review['nombre']) ?></strong>
                                <small class="text-muted">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    <?= date('d M Y', strtotime($review['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Navegación de reseñas">
            <ul class="pagination justify-content-center">
                <?php if ($currentPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=reviews&page=<?= $currentPage - 1 ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php endif; ?>

                <?php
                $start = max(1, $currentPage - 2);
                $end = min($totalPages, $currentPage + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=reviews&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= Config::getBaseUrl() ?>?route=reviews&page=<?= $currentPage + 1 ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <?php else: ?>
        <!-- No Reviews -->
        <div class="text-center py-5">
            <i class="fas fa-star fa-4x text-muted mb-4"></i>
            <h3 class="text-muted mb-3">Aún no hay reseñas</h3>
            <p class="text-muted">Sé el primero en compartir tu experiencia</p>
            <a href="<?= Config::getBaseUrl() ?>?route=tours" class="btn btn-primary mt-3">
                <i class="fas fa-map-marked-alt me-2"></i>Explorar Destinos
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA Section -->
<section class="bg-light py-5">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">¿Listo para tu Próxima Aventura?</h2>
        <p class="lead text-muted mb-4">Únete a cientos de viajeros satisfechos</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?= Config::getBaseUrl() ?>?route=tours" class="btn btn-primary btn-lg">
                <i class="fas fa-map-marked-alt me-2"></i>Ver Destinos
            </a>
            <a href="<?= Config::getBaseUrl() ?>?route=contact" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-envelope me-2"></i>Contáctanos
            </a>
        </div>
    </div>
</section>

<style>
.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.hover-lift {
    transition: all 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-8px);
    box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.15) !important;
}

.avatar-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.rating-summary {
    max-width: 250px;
    margin-left: auto;
}

@media (max-width: 991px) {
    .rating-summary {
        margin: 2rem auto 0;
    }
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
