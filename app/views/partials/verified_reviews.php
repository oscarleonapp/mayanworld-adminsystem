<?php
$product = $data['product'] ?? null;
$reviews = $data['reviews'] ?? [];
$stats = $data['stats'] ?? null;

if (!$product) return;
?>

<!-- Sección de Reviews Verificadas -->
<div class="verified-reviews-section mt-5">
    <div class="reviews-header d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">
            <i class="fas fa-star text-warning me-2"></i>
            Reviews Verificadas
        </h3>
        
        <?php if ($stats && $stats['reviews_publicadas'] > 0): ?>
        <div class="reviews-summary">
            <div class="d-flex align-items-center">
                <div class="rating-display me-3">
                    <span class="rating-number fs-4 fw-bold"><?= number_format($stats['promedio_general'], 1) ?></span>
                    <div class="stars">
                        <?php 
                        $rating = round($stats['promedio_general']);
                        for ($i = 1; $i <= 5; $i++): 
                        ?>
                            <i class="fas fa-star <?= $i <= $rating ? 'text-warning' : 'text-muted' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <small class="text-muted"><?= $stats['reviews_publicadas'] ?> review<?= $stats['reviews_publicadas'] > 1 ? 's' : '' ?></small>
                </div>
                
                <?php if ($stats['reviews_verificadas'] > 0): ?>
                <div class="verified-badge">
                    <i class="fas fa-certificate text-success me-1"></i>
                    <small class="text-success fw-bold"><?= $stats['reviews_verificadas'] ?> verificada<?= $stats['reviews_verificadas'] > 1 ? 's' : '' ?></small>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($reviews)): ?>
    <div class="reviews-list">
        <?php foreach ($reviews as $review): ?>
        <div class="review-item border rounded p-4 mb-3 <?= $review['verificado'] ? 'verified-review' : '' ?>">
            <!-- Header del Review -->
            <div class="review-header d-flex justify-content-between align-items-start mb-3">
                <div class="reviewer-info">
                    <h6 class="mb-1">
                        <?= htmlspecialchars($review['usuario_nombre']) ?>
                        <?php if ($review['verificado']): ?>
                            <span class="verified-badge-small ms-2">
                                <i class="fas fa-certificate text-success"></i>
                                <small class="text-success">Verificado</small>
                            </span>
                        <?php endif; ?>
                    </h6>
                    <div class="review-meta text-muted small">
                        <?php if ($review['fecha_tour']): ?>
                            <span class="me-3">
                                <i class="fas fa-calendar me-1"></i>
                                Tour: <?= date('M Y', strtotime($review['fecha_tour'])) ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($review['tipo_viajero']): ?>
                            <span class="me-3">
                                <i class="fas fa-user me-1"></i>
                                <?= ucfirst($review['tipo_viajero']) ?>
                            </span>
                        <?php endif; ?>
                        
                        <span>
                            <i class="fas fa-clock me-1"></i>
                            <?= date('d M Y', strtotime($review['created_at'])) ?>
                        </span>
                    </div>
                </div>

                <div class="review-rating">
                    <div class="stars mb-1">
                        <?php 
                        $rating = (int)$review['calificacion_general'];
                        for ($i = 1; $i <= 5; $i++): 
                        ?>
                            <i class="fas fa-star <?= $i <= $rating ? 'text-warning' : 'text-muted' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <small class="text-muted"><?= $rating ?>/5</small>
                </div>
            </div>

            <!-- Título del Review -->
            <h5 class="review-title mb-2"><?= htmlspecialchars($review['titulo']) ?></h5>

            <!-- Contenido del Review -->
            <div class="review-content">
                <p class="mb-3"><?= nl2br(htmlspecialchars($review['comentario'])) ?></p>
            </div>

            <!-- Calificaciones Específicas -->
            <?php if ($review['calificacion_guia'] || $review['calificacion_transporte'] || $review['calificacion_organizacion'] || $review['calificacion_valor']): ?>
            <div class="specific-ratings mt-3">
                <h6 class="text-muted small mb-2">Calificaciones Específicas:</h6>
                <div class="row text-center">
                    <?php if ($review['calificacion_guia']): ?>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="rating-item">
                            <div class="rating-value fw-bold"><?= $review['calificacion_guia'] ?>.0</div>
                            <div class="rating-label small text-muted">Guía</div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($review['calificacion_transporte']): ?>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="rating-item">
                            <div class="rating-value fw-bold"><?= $review['calificacion_transporte'] ?>.0</div>
                            <div class="rating-label small text-muted">Transporte</div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($review['calificacion_organizacion']): ?>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="rating-item">
                            <div class="rating-value fw-bold"><?= $review['calificacion_organizacion'] ?>.0</div>
                            <div class="rating-label small text-muted">Organización</div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($review['calificacion_valor']): ?>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="rating-item">
                            <div class="rating-value fw-bold"><?= $review['calificacion_valor'] ?>.0</div>
                            <div class="rating-label small text-muted">Calidad/Precio</div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Footer del Review -->
            <div class="review-footer mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                <div class="review-tags">
                    <?php if ($review['experiencia_previa']): ?>
                        <span class="badge bg-light text-dark me-2">
                            <?= ucfirst(str_replace('_', ' ', $review['experiencia_previa'])) ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($review['grupo_edad']): ?>
                        <span class="badge bg-light text-dark">
                            <?= $review['grupo_edad'] ?> años
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Votación de Utilidad -->
                <div class="review-utility">
                    <small class="text-muted me-3">¿Te pareció útil?</small>
                    <button class="btn btn-sm btn-outline-success me-2 vote-btn" 
                            data-review-id="<?= $review['id'] ?>" 
                            data-useful="true">
                        <i class="fas fa-thumbs-up me-1"></i>
                        Sí
                    </button>
                    <button class="btn btn-sm btn-outline-danger vote-btn" 
                            data-review-id="<?= $review['id'] ?>" 
                            data-useful="false">
                        <i class="fas fa-thumbs-down me-1"></i>
                        No
                    </button>

                    <?php if (isset($review['votos_utiles']) && $review['votos_totales'] > 0): ?>
                        <small class="text-muted ms-3">
                            <?= $review['votos_utiles'] ?> de <?= $review['votos_totales'] ?> encontraron esto útil
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Botón para Ver Más Reviews -->
    <?php if (count($reviews) >= 10): ?>
    <div class="text-center mt-4">
        <button class="btn btn-outline-primary" id="loadMoreReviews" 
                data-product-id="<?= $product['id'] ?>" 
                data-page="2">
            <i class="fas fa-chevron-down me-2"></i>
            Ver Más Reviews
        </button>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- Sin Reviews -->
    <div class="no-reviews text-center py-5">
        <i class="fas fa-star-half-alt text-muted mb-3" style="font-size: 3rem;"></i>
        <h5 class="text-muted">Aún no hay reviews verificadas</h5>
        <p class="text-muted">¡Sé el primero en compartir tu experiencia!</p>
    </div>
    <?php endif; ?>
</div>

<style>
.verified-review {
    border-left: 4px solid #28a745 !important;
    background: rgba(40, 167, 69, 0.03);
}

.verified-badge-small {
    display: inline-flex;
    align-items: center;
}

.rating-item {
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.rating-value {
    font-size: 1.2rem;
    color: #28a745;
}

.review-utility .vote-btn {
    font-size: 0.8rem;
    padding: 0.25rem 0.5rem;
}

.review-utility .vote-btn:hover {
    transform: translateY(-1px);
}

.review-utility .vote-btn.voted {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .review-footer {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .review-utility {
        margin-top: 1rem;
        width: 100%;
    }
    
    .specific-ratings .col-6 {
        margin-bottom: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Votación de utilidad
    document.querySelectorAll('.vote-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.classList.contains('voted')) return;

            const reviewId = this.dataset.reviewId;
            const isUseful = this.dataset.useful === 'true';

            fetch('?route=review/vote', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    review_id: reviewId,
                    is_useful: isUseful
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Marcar como votado
                    document.querySelectorAll(`[data-review-id="${reviewId}"]`).forEach(btn => {
                        btn.classList.add('voted');
                        btn.disabled = true;
                    });
                    
                    // Mostrar mensaje de agradecimiento
                    this.innerHTML = '<i class="fas fa-check me-1"></i>Gracias';
                }
            })
            .catch(error => {
                console.error('Error voting:', error);
            });
        });
    });

    // Cargar más reviews
    const loadMoreBtn = document.getElementById('loadMoreReviews');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const page = parseInt(this.dataset.page);

            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cargando...';

            fetch(`?route=review/get-product-reviews&tour_id=${productId}&page=${page}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.reviews.length > 0) {
                        // Agregar nuevas reviews al DOM
                        // (Implementar lógica para insertar nuevas reviews)
                        
                        // Incrementar página
                        this.dataset.page = page + 1;
                        
                        // Restaurar botón
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-chevron-down me-2"></i>Ver Más Reviews';
                        
                        // Ocultar si no hay más
                        if (!data.pagination.has_more) {
                            this.style.display = 'none';
                        }
                    } else {
                        this.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error loading more reviews:', error);
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Error al cargar';
                });
        });
    }
});
</script>