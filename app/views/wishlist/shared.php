<?php

use App\Core\Config;
use App\Core\Helpers;
// Helpers already loaded by framework
$title = $title ?? 'Lista de Deseos Compartida';
$wishlist = $wishlist ?? [];
$tours = $tours ?? [];
$total_items = $total_items ?? 0;
$is_shared_view = $is_shared_view ?? true;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - Travel Mayan World</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <meta property="og:title" content="<?= htmlspecialchars($title) ?> - Lista de Deseos de <?= htmlspecialchars($wishlist['user_name'] ?? 'un viajero') ?>">
    <meta property="og:description" content="Descubre los tours favoritos de Maya en Guatemala, Belice y México. <?= $total_items ?> experiencias únicas seleccionadas.">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= Config::getBaseUrl() ?>assets/images/maya-tours-share.jpg">
    <style>
        .shared-header {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            padding: 3rem 0;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        .shared-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('assets/images/maya-pattern.png') repeat;
            opacity: 0.1;
        }
        .shared-content {
            position: relative;
            z-index: 2;
        }
        .share-badge {
            background: rgba(255,255,255,0.9);
            border-radius: 25px;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
            margin-bottom: 1rem;
            border: 2px solid rgba(252, 182, 159, 0.3);
        }
        .wishlist-item {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
        }
        .wishlist-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .tour-image {
            position: relative;
            overflow: hidden;
        }
        .tour-image img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .wishlist-item:hover .tour-image img {
            transform: scale(1.05);
        }
        .price-highlight {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
        }
        .recommendation-note {
            background: #e8f5e8;
            border-left: 4px solid #28a745;
            padding: 1rem;
            border-radius: 0 8px 8px 0;
            margin: 1rem 0;
        }
        .priority-indicator {
            position: absolute;
            top: 1rem;
            left: 1rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 2px solid white;
        }
        .priority-alta { background: #dc2626; }
        .priority-media { background: #d97706; }
        .priority-baja { background: #0284c7; }
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin: 3rem 0 0 0;
            text-align: center;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .social-share {
            margin: 2rem 0;
            text-align: center;
        }
        .social-share .btn {
            margin: 0.25rem;
            border-radius: 25px;
        }
        .empty-shared {
            text-align: center;
            padding: 4rem 2rem;
            background: #f8f9fa;
            border-radius: 15px;
            margin: 2rem 0;
        }
    </style>
</head>
<body class="legacy-theme">
    <div class="shared-header">
        <div class="container shared-content">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="share-badge">
                        <i class="fas fa-share-alt text-primary me-2"></i>
                        <span class="small fw-bold">Lista Compartida</span>
                    </div>
                    
                    <h1 class="display-5 fw-bold mb-3">
                        <?= htmlspecialchars($title) ?>
                    </h1>
                    
                    <p class="lead mb-3">
                        Una selección especial de <strong><?= htmlspecialchars($wishlist['user_name'] ?? 'un viajero experto') ?></strong>
                    </p>
                    
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <span class="badge bg-white text-dark fs-6">
                            <i class="fas fa-map-marked-alt text-success me-1"></i>
                            <?= $total_items ?> experiencias Maya
                        </span>
                        <span class="badge bg-white text-dark fs-6">
                            <i class="fas fa-heart text-danger me-1"></i>
                            Curadas personalmente
                        </span>
                    </div>
                </div>
                
                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                    <div class="social-share">
                        <p class="mb-3 fw-bold">¿Te gusta esta lista?</p>
                        <a href="https://wa.me/?text=<?= urlencode('¡Mira esta increíble lista de tours Maya! ' . $_SERVER['REQUEST_URI']) ?>" 
                           class="btn btn-success btn-sm" target="_blank">
                            <i class="fab fa-whatsapp me-1"></i>Compartir en WhatsApp
                        </a>
                        <button class="btn btn-primary btn-sm" onclick="copyCurrentUrl()">
                            <i class="fas fa-link me-1"></i>Copiar enlace
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Descripción de la lista -->
        <?php if (!empty($wishlist['descripcion'])): ?>
        <div class="recommendation-note mb-4">
            <h5><i class="fas fa-quote-left me-2"></i>Nota del creador</h5>
            <p class="mb-0"><?= nl2br(htmlspecialchars($wishlist['descripcion'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- Estadísticas rápidas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="h3 mb-1 text-primary"><?= $total_items ?></div>
                <div class="small text-muted">Tours seleccionados</div>
            </div>
            <div class="stat-card">
                <div class="h3 mb-1 text-success">
                    $<?= number_format(array_sum(array_column($tours, 'precio_actual')), 2) ?>
                </div>
                <div class="small text-muted">Valor total (USD)</div>
            </div>
            <div class="stat-card">
                <div class="h3 mb-1 text-warning">
                    <?= count(array_filter($tours, function($p) { return $p['disponible_ahora'] ?? false; })) ?>
                </div>
                <div class="small text-muted">Disponibles ahora</div>
            </div>
            <div class="stat-card">
                <div class="h3 mb-1 text-info">
                    <?= array_sum(array_map(function($p) { 
                        return (int)filter_var($p['duracion'] ?? '1', FILTER_SANITIZE_NUMBER_INT); 
                    }, $tours)) ?>
                </div>
                <div class="small text-muted">Días de aventura</div>
            </div>
        </div>

        <!-- Lista de tours -->
        <?php if (empty($tours)): ?>
        <div class="empty-shared">
            <i class="fas fa-heart-broken text-muted mb-3" style="font-size: 4rem;"></i>
            <h3 class="text-muted mb-3">Lista vacía</h3>
            <p class="text-muted mb-4">Esta lista aún no tiene tours agregados.</p>
            <a href="?route=tours" class="btn btn-primary btn-lg">
                <i class="fas fa-search me-2"></i>Explorar Tours Disponibles
            </a>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($tours as $index => $tour): ?>
            <div class="col-lg-6 mb-4">
                <div class="wishlist-item">
                    <div class="tour-image position-relative">
                        <!-- Priority indicator -->
                        <div class="priority-indicator priority-<?= htmlspecialchars($tour['prioridad'] ?? 'media') ?>"
                             title="Prioridad: <?= ucfirst($tour['prioridad'] ?? 'media') ?>"></div>
                        
                        <img src="<?= htmlspecialchars(Helpers::tourImage($tour['imagen_principal'] ?? null, 'images/tour-placeholder.jpg')) ?>" 
                             alt="<?= htmlspecialchars($tour['tour_nombre']) ?>"
                             loading="lazy"
                             decoding="async">
                        
                        <div class="price-highlight">
                            $<?= number_format($tour['precio_actual'], 2) ?> USD
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <h4 class="card-title mb-3">
                            <a href="?route=tour/<?= $tour['tour_id'] ?>" 
                               class="text-decoration-none text-dark">
                                <?= htmlspecialchars($tour['tour_nombre']) ?>
                            </a>
                        </h4>

                        <!-- Cambio de precio -->
                        <?php if (!empty($tour['cambio_precio_porcentaje'])): ?>
                        <div class="mb-3">
                            <?php if ($tour['cambio_precio_porcentaje'] < 0): ?>
                            <div class="alert alert-success py-2 mb-2">
                                <small>
                                    <i class="fas fa-arrow-down me-1"></i>
                                    <strong>¡Precio reducido!</strong> 
                                    <?= abs($tour['cambio_precio_porcentaje']) ?>% menos desde que se agregó
                                    <br>Antes: $<?= number_format($tour['precio_cuando_agregado'], 2) ?>
                                </small>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info py-2 mb-2">
                                <small>
                                    <i class="fas fa-arrow-up me-1"></i>
                                    Precio aumentó <?= $tour['cambio_precio_porcentaje'] ?>% 
                                    desde que se agregó (era $<?= number_format($tour['precio_cuando_agregado'], 2) ?>)
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Información del tour -->
                        <div class="mb-3">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <?php if ($tour['disponible_ahora']): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check me-1"></i>Disponible
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock me-1"></i>Consultar fechas
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($tour['duracion'])): ?>
                                <div class="col-auto">
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-calendar-days me-1"></i><?= htmlspecialchars($tour['duracion']) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($tour['categoria_nombre'])): ?>
                                <div class="col-auto">
                                    <span class="badge bg-info">
                                        <i class="fas fa-tag me-1"></i><?= htmlspecialchars($tour['categoria_nombre']) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Notas personales del creador -->
                        <?php if (!empty($tour['notas_personales'])): ?>
                        <div class="mb-3 p-3 bg-light rounded">
                            <h6 class="mb-1">
                                <i class="fas fa-user me-1"></i>
                                Nota de <?= htmlspecialchars($wishlist['user_name'] ?? 'el viajero') ?>:
                            </h6>
                            <p class="mb-0 small text-muted fst-italic">
                                "<?= nl2br(htmlspecialchars($tour['notas_personales'])) ?>"
                            </p>
                        </div>
                        <?php endif; ?>

                        <!-- Acciones -->
                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <small class="text-muted">
                                <i class="fas fa-heart me-1"></i>
                                Agregado el <?= date('d/m/Y', strtotime($tour['fecha_agregado'])) ?>
                            </small>
                            
                            <div class="btn-group">
                                <a href="?route=tour/<?= $tour['tour_id'] ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>Ver Tour
                                </a>
                                <button class="btn btn-outline-danger btn-sm add-to-my-wishlist" 
                                        data-tour-id="<?= $tour['tour_id'] ?>"
                                        title="Agregar a mi lista">
                                    <i class="fas fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- CTA Section -->
    <div class="cta-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2 class="mb-3">¿Te inspiraste con esta selección?</h2>
                    <p class="mb-0">
                        Explora más de 50+ tours únicos en Guatemala, Belice y México. 
                        Descubre la magia del mundo Maya con expertos locales.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <a href="?route=tours" class="btn btn-light btn-lg me-2">
                        <i class="fas fa-search me-2"></i>Explorar Tours
                    </a>
                    <a href="?route=contact" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-phone me-2"></i>Contactar
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer info -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="border-top pt-4">
                    <p class="text-muted mb-2">
                        <i class="fas fa-info-circle me-1"></i>
                        Esta lista fue creada el <?= date('d \\d\\e F, Y', strtotime($wishlist['created_at'] ?? 'now')) ?>
                        <?php if (!empty($wishlist['veces_compartida']) && $wishlist['veces_compartida'] > 1): ?>
                        y ha sido vista <?= $wishlist['veces_compartida'] ?> veces.
                        <?php endif; ?>
                    </p>
                    <p class="text-muted mb-0">
                        <strong>Travel Mayan World</strong> - 
                        Especialistas en turismo Maya desde hace 20+ años
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
    function copyCurrentUrl() {
        navigator.clipboard.writeText(window.location.href).then(function() {
            // Crear toast de confirmación
            const toastHtml = `
                <div class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">
                            <i class="fas fa-check me-2"></i>¡Enlace copiado al portapapeles!
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                    </div>
                </div>
            `;
            
            // Agregar toast al DOM
            const toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.innerHTML = toastHtml;
            document.body.appendChild(toastContainer);
            
            // Mostrar toast
            const toast = new bootstrap.Toast(toastContainer.querySelector('.toast'));
            toast.show();
            
            // Remover del DOM después de que se oculte
            toastContainer.querySelector('.toast').addEventListener('hidden.bs.toast', function () {
                document.body.removeChild(toastContainer);
            });
        }).catch(function(err) {
            alert('Error al copiar el enlace. Copia manualmente la URL de la página.');
        });
    }

    $(document).ready(function() {
        // Agregar a mi wishlist (si el usuario tiene sesión)
        $('.add-to-my-wishlist').click(function() {
            const tourId = $(this).data('tour-id');
            const $btn = $(this);

            <?php if (isset($_SESSION['user_email'])): ?>
            $.post('?route=wishlist/add', {
                tour_id: tourId,
                csrf_token: '<?= Helpers::generateCsrfToken() ?>'
            })
            .done(function(response) {
                if (response.success) {
                    $btn.removeClass('btn-outline-danger').addClass('btn-danger');
                    $btn.prop('disabled', true);
                    $btn.attr('title', 'Ya en tu lista');
                    
                    // Toast de confirmación
                    const toastHtml = `
                        <div class="toast align-items-center text-bg-success border-0" role="alert">
                            <div class="d-flex">
                                <div class="toast-body">
                                    <i class="fas fa-heart me-2"></i>¡Agregado a tu lista de deseos!
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
                            </div>
                        </div>
                    `;
                    
                    const toastContainer = document.createElement('div');
                    toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
                    toastContainer.innerHTML = toastHtml;
                    document.body.appendChild(toastContainer);
                    
                    const toast = new bootstrap.Toast(toastContainer.querySelector('.toast'));
                    toast.show();
                    
                    toastContainer.querySelector('.toast').addEventListener('hidden.bs.toast', function () {
                        document.body.removeChild(toastContainer);
                    });
                } else {
                    alert('Error: ' + response.error);
                }
            })
            .fail(function() {
                alert('Error de conexión. Intenta de nuevo.');
            });
            <?php else: ?>
            alert('Para agregar tours a tu lista de deseos, primero debes iniciar sesión.');
            window.location.href = '?route=login';
            <?php endif; ?>
        });

        // Smooth scroll para elementos ancla
        $('a[href^="#"]').on('click', function(event) {
            var target = $(this.getAttribute('href'));
            if( target.length ) {
                event.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 1000);
            }
        });

        // Lazy loading para imágenes
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        observer.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    });
    </script>
</body>
</html>
