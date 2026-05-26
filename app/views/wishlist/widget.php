<?php
use App\Core\Helpers;
$tours = $tours ?? [];
$count = $count ?? 0;
$wishlist = $wishlist ?? null;
?>

<div class="wishlist-widget" id="wishlistWidget">
    <div class="widget-header">
        <h6 class="widget-title">
            <i class="fas fa-heart text-danger me-2"></i>
            Mi Lista de Deseos
            <?php if ($count > 0): ?>
                <span class="badge bg-primary ms-2"><?= $count ?></span>
            <?php endif; ?>
        </h6>
        <?php if ($count > 3): ?>
        <a href="?route=wishlist" class="btn btn-link btn-sm p-0">
            Ver todas (<?= $count ?>)
        </a>
        <?php endif; ?>
    </div>

    <div class="widget-content">
        <?php if (empty($tours)): ?>
        <div class="empty-widget text-center py-3">
            <i class="fas fa-heart-broken text-muted mb-2" style="font-size: 2rem;"></i>
            <p class="text-muted small mb-2">Tu lista está vacía</p>
            <a href="?route=tours" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-plus me-1"></i>Agregar tours
            </a>
        </div>
        <?php else: ?>
        <div class="widget-tours">
            <?php foreach ($tours as $tour): ?>
            <div class="widget-tour-item">
                <div class="row g-2 align-items-center">
                    <div class="col-3">
                        <img src="<?= htmlspecialchars(Helpers::tourImage($tour['imagen_principal'] ?? null, 'images/tour-placeholder.jpg')) ?>" 
                             class="img-fluid rounded" 
                             style="height: 50px; width: 100%; object-fit: cover;"
                             alt="<?= htmlspecialchars($tour['tour_nombre']) ?>"
                             loading="lazy"
                             decoding="async">
                    </div>
                    <div class="col-7">
                        <h6 class="mb-0 small">
                            <a href="?route=tour/<?= $tour['tour_id'] ?>" 
                               class="text-decoration-none text-dark">
                                <?= htmlspecialchars(substr($tour['tour_nombre'], 0, 35)) ?><?= strlen($tour['tour_nombre']) > 35 ? '...' : '' ?>
                            </a>
                        </h6>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="text-primary fw-bold small">
                                $<?= number_format($tour['precio_actual'], 0) ?>
                            </span>
                            <?php if (!empty($tour['cambio_precio_porcentaje']) && $tour['cambio_precio_porcentaje'] < 0): ?>
                            <span class="badge bg-success badge-sm">
                                <?= abs($tour['cambio_precio_porcentaje']) ?>% OFF
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-2 text-end">
                        <button class="btn btn-link btn-sm p-0 text-danger remove-from-widget-wishlist" 
                                data-tour-id="<?= $tour['tour_id'] ?>"
                                title="Remover de lista"
                                aria-label="Remover de lista">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($count > 0): ?>
        <div class="widget-footer mt-3 pt-2 border-top">
            <div class="row g-2">
                <div class="col-6">
                    <a href="?route=wishlist" class="btn btn-outline-primary btn-sm w-100">
                        <i class="fas fa-eye me-1"></i>Ver Lista
                    </a>
                </div>
                <div class="col-6">
                    <button class="btn btn-primary btn-sm w-100 share-wishlist-widget">
                        <i class="fas fa-share-alt me-1"></i>Compartir
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.wishlist-widget {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    max-width: 350px;
}

.widget-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.widget-title {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
}

.widget-tour-item {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f8f9fa;
}

.widget-tour-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.badge-sm {
    font-size: 0.7rem;
    padding: 0.2rem 0.4rem;
}

.empty-widget {
    background: #f8f9fa;
    border-radius: 8px;
    margin: 0.5rem 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .wishlist-widget {
        max-width: 100%;
    }
}

/* Animation for changes */
    .widget-tour-item {
    transition: all 0.3s ease;
}

    .widget-tour-item.removing {
    opacity: 0;
    transform: translateX(20px);
}
</style>

<script>
$(document).ready(function() {
    // Remover tour del widget
    $('.remove-from-widget-wishlist').click(function() {
        const tourId = $(this).data('tour-id');
        const $item = $(this).closest('.widget-tour-item');
        
        // Animación de salida
        $item.addClass('removing');
        
        setTimeout(() => {
            $.post('?route=wishlist/remove', {
                tour_id: tourId,
                csrf_token: '<?= Helpers::generateCsrfToken() ?>'
            })
            .done(function(response) {
                if (response.success) {
                    $item.remove();
                    
                    // Actualizar contador
                    const $badge = $('.widget-title .badge');
                    const currentCount = parseInt($badge.text()) || 0;
                    const newCount = currentCount - 1;
                    
                    if (newCount > 0) {
                        $badge.text(newCount);
                    } else {
                        // Recargar widget si está vacío
                        location.reload();
                    }
                    
                    // Mostrar mensaje
                    if (typeof showToast === 'function') {
                        showToast('Tour removido de tu lista', 'success');
                    }
                } else {
                    // Revertir animación en caso de error
                    $item.removeClass('removing');
                    alert('Error: ' + response.error);
                }
            })
            .fail(function() {
                $item.removeClass('removing');
                alert('Error de conexión');
            });
        }, 300);
    });
    
    // Compartir lista desde widget
    $('.share-wishlist-widget').click(function() {
        $.post('?route=wishlist/share', {
            expires_in_days: 30,
            csrf_token: '<?= Helpers::generateCsrfToken() ?>'
        })
        .done(function(response) {
            if (response.success) {
                // Copiar al portapapeles
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(response.share_url).then(function() {
                        if (typeof showToast === 'function') {
                            showToast('¡Enlace copiado! Ya puedes compartir tu lista', 'success');
                        } else {
                            alert('¡Enlace copiado al portapapeles!');
                        }
                    });
                } else {
                    // Fallback para navegadores antiguos
                    prompt('Copia este enlace para compartir:', response.share_url);
                }
            } else {
                alert('Error: ' + response.error);
            }
        })
        .fail(function() {
            alert('Error de conexión');
        });
    });
});

// Función para mostrar el widget (para uso externo)
function showWishlistWidget() {
    $('#wishlistWidget').slideDown();
}

// Función para ocultar el widget
function hideWishlistWidget() {
    $('#wishlistWidget').slideUp();
}

// Función para actualizar el contador del widget
function updateWishlistCount(newCount) {
    const $badge = $('.widget-title .badge');
    if (newCount > 0) {
        if ($badge.length) {
            $badge.text(newCount);
        } else {
            $('.widget-title').append('<span class="badge bg-primary ms-2">' + newCount + '</span>');
        }
    } else {
        $badge.remove();
    }
}
</script>
