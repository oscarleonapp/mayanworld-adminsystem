<?php
use App\Core\Config;
/**
 * Componente: Reviews con Fotos y Videos
 * Sistema de reseñas mejorado que permite subir imágenes y videos
 *
 * @param array $product - Datos del tour
 * @param array $reviews - Reseñas del tour
 * @param array $review_summary - Resumen de calificaciones
 * @param string $csrf_token - Token CSRF
 */

$productId = $product['id'] ?? 0;
$reviewCount = $review_summary['count'] ?? 0;
$avgRating = $review_summary['avg'] ?? 0;

// Filtrar reviews con fotos
$reviewsWithPhotos = array_filter($reviews ?? [], function($r) {
    return !empty($r['fotos']) || !empty($r['media']);
});
?>

<!-- Sección: Reviews con Media -->
<div class="reviews-with-media-section mt-4" id="reviews">
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-0">
                        <i class="fas fa-star text-warning me-2"></i>
                        Opiniones de Viajeros
                    </h4>
                    <?php if ($reviewCount > 0): ?>
                        <small class="text-muted">
                            <?= $reviewCount ?> reseña<?= $reviewCount != 1 ? 's' : '' ?>
                            • Calificación <?= number_format($avgRating, 1) ?>/5
                        </small>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#writeReviewModal">
                        <i class="fas fa-pen me-1"></i>Escribir reseña
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body">
            <?php if ($reviewCount > 0): ?>
                <!-- Resumen de calificaciones -->
                <div class="review-summary-section mb-4 p-3 bg-light rounded">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center border-end">
                            <div class="overall-rating">
                                <div class="rating-number"><?= number_format($avgRating, 1) ?></div>
                                <div class="rating-stars mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?= $i <= round($avgRating) ? 'fas' : 'far' ?> fa-star text-warning"></i>
                                    <?php endfor; ?>
                                </div>
                                <small class="text-muted">Basado en <?= $reviewCount ?> opiniones</small>
                            </div>
                        </div>
                        <div class="col-md-8 ps-md-4 mt-3 mt-md-0">
                            <!-- Distribución de calificaciones -->
                            <div class="rating-distribution">
                                <?php
                                // Calcular distribución
                                $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
                                foreach ($reviews as $r) {
                                    $rating = (int)($r['rating'] ?? 0);
                                    if ($rating >= 1 && $rating <= 5) {
                                        $distribution[$rating]++;
                                    }
                                }

                                for ($star = 5; $star >= 1; $star--):
                                    $count = $distribution[$star];
                                    $percentage = $reviewCount > 0 ? ($count / $reviewCount) * 100 : 0;
                                ?>
                                    <div class="rating-bar-item mb-2">
                                        <div class="d-flex align-items-center">
                                            <span class="rating-label" style="width: 60px;">
                                                <?= $star ?> <i class="fas fa-star text-warning small"></i>
                                            </span>
                                            <div class="progress flex-grow-1 mx-2" style="height: 8px;">
                                                <div class="progress-bar bg-warning"
                                                     role="progressbar"
                                                     style="width: <?= $percentage ?>%"
                                                     aria-valuenow="<?= $percentage ?>"
                                                     aria-valuemin="0"
                                                     aria-valuemax="100"></div>
                                            </div>
                                            <span class="rating-count text-muted small" style="width: 40px;">
                                                (<?= $count ?>)
                                            </span>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Galería de fotos de reviews -->
                <?php if (!empty($reviewsWithPhotos)): ?>
                    <div class="reviews-photo-gallery mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-images text-primary me-2"></i>
                            Fotos de Viajeros (<?= count($reviewsWithPhotos) ?>)
                        </h6>
                        <div class="row g-2">
                            <?php
                            $photoCount = 0;
                            foreach ($reviewsWithPhotos as $review):
                                if ($photoCount >= 6) break; // Mostrar máximo 6
                                $fotos = !empty($review['fotos']) ? json_decode($review['fotos'], true) : [];
                                if (!is_array($fotos)) $fotos = [];

                                foreach ($fotos as $foto):
                                    if ($photoCount >= 6) break;
                                    $photoCount++;
                            ?>
                                    <div class="col-4 col-md-2">
                                        <div class="review-photo-thumb"
                                             onclick="openReviewPhotoGallery('<?= htmlspecialchars($foto) ?>')">
                                            <img src="<?= Helpers::asset('images/reviews/' . $foto) ?>"
                                                 alt="Foto de viajero"
                                                 class="img-fluid rounded"
                                                 loading="lazy"
                                                 decoding="async">
                                            <div class="photo-thumb-overlay">
                                                <i class="fas fa-search-plus"></i>
                                            </div>
                                        </div>
                                    </div>
                            <?php
                                endforeach;
                            endforeach;
                            ?>
                            <?php if ($photoCount < count($reviewsWithPhotos)): ?>
                                <div class="col-4 col-md-2">
                                    <div class="review-photo-thumb more-photos" data-bs-toggle="modal" data-bs-target="#allPhotosModal">
                                        <div class="more-photos-overlay">
                                            <i class="fas fa-images fa-2x mb-2"></i>
                                            <div>Ver todas</div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Filtros de reviews -->
                <div class="review-filters mb-3">
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check" name="reviewFilter" id="filterAll" autocomplete="off" checked>
                        <label class="btn btn-outline-primary" for="filterAll" onclick="filterReviews('all')">
                            Todas (<?= $reviewCount ?>)
                        </label>

                        <input type="radio" class="btn-check" name="reviewFilter" id="filterPhotos" autocomplete="off">
                        <label class="btn btn-outline-primary" for="filterPhotos" onclick="filterReviews('photos')">
                            <i class="fas fa-camera me-1"></i>Con fotos (<?= count($reviewsWithPhotos) ?>)
                        </label>

                        <input type="radio" class="btn-check" name="reviewFilter" id="filter5" autocomplete="off">
                        <label class="btn btn-outline-primary" for="filter5" onclick="filterReviews(5)">
                            5★ (<?= $distribution[5] ?>)
                        </label>

                        <input type="radio" class="btn-check" name="reviewFilter" id="filter4" autocomplete="off">
                        <label class="btn btn-outline-primary" for="filter4" onclick="filterReviews(4)">
                            4★+ (<?= $distribution[5] + $distribution[4] ?>)
                        </label>
                    </div>
                </div>

                <!-- Lista de reviews -->
                <div class="reviews-list" id="reviewsList">
                    <?php foreach ($reviews as $index => $review):
                        $fotos = !empty($review['fotos']) ? json_decode($review['fotos'], true) : [];
                        if (!is_array($fotos)) $fotos = [];
                        $hasPhotos = !empty($fotos);
                    ?>
                        <div class="review-item mb-4 p-3 border rounded"
                             data-rating="<?= $review['rating'] ?>"
                             data-has-photos="<?= $hasPhotos ? '1' : '0' ?>">
                            <div class="review-header d-flex justify-content-between align-items-start mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="review-avatar me-3">
                                        <div class="avatar-circle">
                                            <?= strtoupper(substr($review['nombre'] ?? 'A', 0, 1)) ?>
                                        </div>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($review['nombre']) ?></strong>
                                        <?php if (!empty($review['verified_purchase'])): ?>
                                            <span class="badge bg-success ms-2">
                                                <i class="fas fa-check-circle me-1"></i>Compra Verificada
                                            </span>
                                        <?php endif; ?>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="<?= $i <= (int)$review['rating'] ? 'fas' : 'far' ?> fa-star text-warning"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="far fa-clock me-1"></i>
                                            <?= date('d/m/Y', strtotime($review['created_at'] ?? 'now')) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($review['titulo'])): ?>
                                <h6 class="review-title mb-2"><?= htmlspecialchars($review['titulo']) ?></h6>
                            <?php endif; ?>

                            <div class="review-content mb-2">
                                <?= nl2br(htmlspecialchars($review['comentario'] ?? '')) ?>
                            </div>

                            <?php if ($hasPhotos): ?>
                                <div class="review-photos mt-3">
                                    <div class="row g-2">
                                        <?php foreach (array_slice($fotos, 0, 4) as $foto): ?>
                                            <div class="col-3">
                                                <img src="<?= Helpers::asset('images/reviews/' . $foto) ?>"
                                                     alt="Foto de reseña"
                                                     class="img-fluid rounded review-photo-small"
                                                     onclick="openReviewPhotoGallery('<?= htmlspecialchars($foto) ?>')"
                                                     style="cursor: pointer; height: 80px; object-fit: cover; width: 100%;"
                                                     loading="lazy"
                                                     decoding="async">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Respuesta del operador (si existe) -->
                            <?php if (!empty($review['respuesta_admin'])): ?>
                                <div class="admin-response mt-3 ms-4 p-3 bg-light rounded">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-reply text-primary me-2"></i>
                                        <strong class="text-primary">Respuesta del operador:</strong>
                                    </div>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($review['respuesta_admin'])) ?></p>
                                </div>
                            <?php endif; ?>

                            <!-- Botones de utilidad -->
                            <div class="review-actions mt-3 pt-2 border-top">
                                <small class="text-muted">
                                    ¿Te fue útil esta reseña?
                                    <button class="btn btn-sm btn-outline-success ms-2" onclick="voteReview(<?= $review['id'] ?>, 1)">
                                        <i class="fas fa-thumbs-up me-1"></i>Sí (<?= $review['votos_positivos'] ?? 0 ?>)
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="voteReview(<?= $review['id'] ?>, 0)">
                                        <i class="fas fa-thumbs-down me-1"></i>No (<?= $review['votos_negativos'] ?? 0 ?>)
                                    </button>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <!-- Sin reviews aún -->
                <div class="text-center py-5">
                    <i class="fas fa-star fa-3x text-muted mb-3"></i>
                    <h5>Sé el primero en opinar</h5>
                    <p class="text-muted">Comparte tu experiencia con otros viajeros</p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#writeReviewModal">
                        <i class="fas fa-pen me-2"></i>Escribir la primera reseña
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Escribir Reseña -->
<div class="modal fade" id="writeReviewModal" tabindex="-1" aria-labelledby="writeReviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="writeReviewModalLabel">
                    <i class="fas fa-star text-warning me-2"></i>
                    Escribe tu reseña
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="<?= Config::getBaseUrl() ?>?route=review/submit" enctype="multipart/form-data" id="reviewForm">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="tour_id" value="<?= $productId ?>">

                    <!-- Nombre -->
                    <div class="mb-3">
                        <label for="reviewName" class="form-label">Tu nombre *</label>
                        <input type="text" class="form-control" id="reviewName" name="nombre" required>
                    </div>

                    <!-- Email (opcional, para verificación) -->
                    <div class="mb-3">
                        <label for="reviewEmail" class="form-label">
                            Email (opcional)
                            <small class="text-muted">Para verificar tu compra</small>
                        </label>
                        <input type="email" class="form-control" id="reviewEmail" name="email">
                    </div>

                    <!-- Calificación -->
                    <div class="mb-3">
                        <label class="form-label">Calificación *</label>
                        <div class="star-rating-input">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" required>
                                <label for="star<?= $i ?>"><i class="fas fa-star"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Título (opcional) -->
                    <div class="mb-3">
                        <label for="reviewTitle" class="form-label">Título de la reseña</label>
                        <input type="text" class="form-control" id="reviewTitle" name="titulo" placeholder="Ej: ¡Experiencia increíble!">
                    </div>

                    <!-- Comentario -->
                    <div class="mb-3">
                        <label for="reviewComment" class="form-label">Tu opinión *</label>
                        <textarea class="form-control" id="reviewComment" name="comentario" rows="4" required
                                  placeholder="Cuéntanos sobre tu experiencia..."></textarea>
                        <div class="form-text">Mínimo 20 caracteres</div>
                    </div>

                    <!-- Upload de fotos -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-camera me-2"></i>
                            Agrega fotos (opcional)
                        </label>
                        <input type="file" class="form-control" name="fotos[]" id="reviewPhotos"
                               accept="image/*" multiple onchange="previewPhotos(this)">
                        <div class="form-text">
                            Máximo 5 fotos • JPG, PNG • Máximo 5MB cada una
                        </div>
                        <!-- Preview de fotos -->
                        <div id="photosPreview" class="row g-2 mt-2"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-1"></i>Enviar reseña
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Estilos -->
<style>
.overall-rating {
    padding: 1rem;
}

.rating-number {
    font-size: 3rem;
    font-weight: 700;
    color: #ffc107;
    line-height: 1;
}

.rating-stars {
    font-size: 1.2rem;
}

.rating-distribution {
    font-size: 0.9rem;
}

.progress {
    background-color: #e9ecef;
}

.review-photo-thumb {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.2s ease;
}

.review-photo-thumb:hover {
    transform: scale(1.05);
}

.review-photo-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.photo-thumb-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.2s ease;
    color: white;
    font-size: 1.5rem;
}

.review-photo-thumb:hover .photo-thumb-overlay {
    opacity: 1;
}

.more-photos {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.more-photos-overlay {
    color: white;
    text-align: center;
    font-weight: 600;
}

.avatar-circle {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: 700;
}

.review-item {
    transition: box-shadow 0.2s ease;
}

.review-item:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.admin-response {
    border-left: 3px solid #0d6efd;
}

.star-rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 0.25rem;
}

.star-rating-input input {
    display: none;
}

.star-rating-input label {
    cursor: pointer;
    font-size: 2rem;
    color: #ddd;
    transition: color 0.2s ease;
}

.star-rating-input input:checked ~ label,
.star-rating-input label:hover,
.star-rating-input label:hover ~ label {
    color: #ffc107;
}

#photosPreview img {
    height: 100px;
    object-fit: cover;
    border-radius: 8px;
}
</style>

<!-- JavaScript -->
<script>
function filterReviews(filter) {
    const reviews = document.querySelectorAll('.review-item');

    reviews.forEach(review => {
        let show = false;

        if (filter === 'all') {
            show = true;
        } else if (filter === 'photos') {
            show = review.dataset.hasPhotos === '1';
        } else if (typeof filter === 'number') {
            const rating = parseInt(review.dataset.rating);
            show = filter === 5 ? rating === 5 : rating >= filter;
        }

        review.style.display = show ? 'block' : 'none';
    });
}

function voteReview(reviewId, helpful) {
    fetch('/?route=review/vote', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            review_id: reviewId,
            helpful: helpful
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar contador (opcional: recargar página o actualizar dinámicamente)
            alert('¡Gracias por tu feedback!');
        }
    })
    .catch(error => console.error('Error:', error));
}

function previewPhotos(input) {
    const preview = document.getElementById('photosPreview');
    preview.innerHTML = '';

    if (input.files) {
        const files = Array.from(input.files).slice(0, 5); // Máximo 5

        files.forEach((file, index) => {
            const reader = new FileReader();

            reader.onload = function(e) {
                const col = document.createElement('div');
                col.className = 'col-4 col-md-2';
                col.innerHTML = `
                    <div class="position-relative">
                        <img src="${e.target.result}" class="img-fluid rounded" alt="Foto seleccionada">
                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1"
                                onclick="removePhoto(${index})" aria-label="Eliminar foto" title="Eliminar foto">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                `;
                preview.appendChild(col);
            };

            reader.readAsDataURL(file);
        });
    }
}

function removePhoto(index) {
    const input = document.getElementById('reviewPhotos');
    const dt = new DataTransfer();

    Array.from(input.files).forEach((file, i) => {
        if (i !== index) dt.items.add(file);
    });

    input.files = dt.files;
    previewPhotos(input);
}

function openReviewPhotoGallery(photo) {
    // Implementar lightbox o modal para ver foto en grande
    alert('Abrir foto: ' + photo);
}
</script>
