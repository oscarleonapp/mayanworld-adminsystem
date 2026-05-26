<?php
use App\Core\Helpers;
// Helpers already loaded by framework
$title = $title ?? 'Mi Lista de Deseos';
$tours = $tours ?? [];
$recommendations = $recommendations ?? [];
$notifications = $notifications ?? [];
$total_items = $total_items ?? 0;
$csrf_token = $csrf_token ?? '';
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
    <style>
        .wishlist-header {
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        .wishlist-stats {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .wishlist-item {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        .wishlist-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .price-change-indicator {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .price-decrease {
            background: #d1edff;
            color: #0969da;
        }
        .price-increase {
            background: #ffecec;
            color: #d1242f;
        }
        .priority-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .priority-alta { background: #fee2e2; color: #dc2626; }
        .priority-media { background: #fef3c7; color: #d97706; }
        .priority-baja { background: #e0f2fe; color: #0284c7; }
        .empty-wishlist {
            text-align: center;
            padding: 4rem 2rem;
            background: #f8f9fa;
            border-radius: 15px;
            margin: 2rem 0;
        }
        .notification-badge {
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            top: -5px;
            right: -5px;
        }
        .wishlist-actions {
            background: #f8f9fa;
            padding: 1rem;
            border-top: 1px solid #e9ecef;
        }
        .btn-remove-wishlist {
            color: #dc3545;
            border: 1px solid #dc3545;
            background: transparent;
        }
        .btn-remove-wishlist:hover {
            background: #dc3545;
            color: white;
        }
        .recommendations-section {
            background: #f8f9fa;
            padding: 2rem 0;
            margin-top: 3rem;
        }
    </style>
</head>
<body class="legacy-theme">
    <div class="wishlist-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="mb-0"><i class="fas fa-heart text-danger me-2"></i><?= htmlspecialchars($title) ?></h1>
                    <p class="text-muted mt-2 mb-0">Tus experiencias favoritas en un solo lugar</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <div class="d-flex gap-2 justify-content-lg-end">
                        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#shareWishlistModal">
                            <i class="fas fa-share-alt me-1"></i> Compartir Lista
                        </button>
                        <a href="?route=wishlist/settings" class="btn btn-outline-primary">
                            <i class="fas fa-cog me-1"></i> Configurar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Estadísticas rápidas -->
        <div class="wishlist-stats">
            <div class="row text-center">
                <div class="col-md-3">
                    <div class="h3 mb-1 text-primary"><?= $total_items ?></div>
                    <div class="small text-muted">Tours guardados</div>
                </div>
                <div class="col-md-3">
                    <div class="h3 mb-1 text-success">
                        <?= count(array_filter($tours, function($p) { return ($p['cambio_precio_porcentaje'] ?? 0) < 0; })) ?>
                    </div>
                    <div class="small text-muted">Con descuentos</div>
                </div>
                <div class="col-md-3">
                    <div class="h3 mb-1 text-warning">
                        <?= count(array_filter($tours, function($p) { return $p['disponible_ahora'] ?? false; })) ?>
                    </div>
                    <div class="small text-muted">Disponibles ahora</div>
                </div>
                <div class="col-md-3 position-relative">
                    <div class="h3 mb-1 text-info"><?= count($notifications) ?></div>
                    <div class="small text-muted">
                        <i class="fas fa-bell"></i> Notificaciones
                        <?php if (count($notifications) > 0): ?>
                            <span class="notification-badge"><?= count($notifications) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notificaciones pendientes -->
        <?php if (!empty($notifications)): ?>
        <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
            <h6><i class="fas fa-bell me-2"></i>Tienes <?= count($notifications) ?> notificación(es) pendiente(s):</h6>
            <ul class="mb-0 mt-2">
                <?php foreach (array_slice($notifications, 0, 3) as $notification): ?>
                <li class="mb-1">
                    <strong><?= htmlspecialchars($notification['titulo']) ?></strong> - 
                    <?= htmlspecialchars($notification['tour_nombre']) ?>
                    <small class="text-muted">(<?= date('d/m/Y', strtotime($notification['fecha_creada'])) ?>)</small>
                    <button class="btn btn-sm btn-link p-0 ms-2 mark-notification-read" 
                            data-notification-id="<?= $notification['id'] ?>"
                            aria-label="Marcar notificación como leída">
                        <i class="fas fa-check"></i>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
        <?php endif; ?>

        <!-- Lista de tours -->
        <?php if (empty($tours)): ?>
        <div class="empty-wishlist">
            <i class="fas fa-heart-broken text-muted mb-3" style="font-size: 4rem;"></i>
            <h3 class="text-muted mb-3">Tu lista está vacía</h3>
            <p class="text-muted mb-4">¡Comienza a agregar tus tours favoritos para no perderlos de vista!</p>
            <a href="?route=tours" class="btn btn-primary btn-lg">
                <i class="fas fa-search me-2"></i>Explorar Tours
            </a>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($tours as $tour): ?>
            <div class="col-lg-6 mb-4">
                <div class="wishlist-item position-relative">
                    <!-- Priority badge -->
                    <span class="priority-badge priority-<?= htmlspecialchars($tour['prioridad'] ?? 'media') ?>">
                        <?= ucfirst($tour['prioridad'] ?? 'media') ?>
                    </span>

                    <div class="row g-0">
                        <div class="col-md-4">
                            <img src="<?= htmlspecialchars(Helpers::tourImage($tour['imagen_principal'] ?? null, 'images/tour-placeholder.jpg')) ?>" 
                                 class="img-fluid h-100 object-cover" 
                                 alt="<?= htmlspecialchars($tour['tour_nombre']) ?>"
                                 style="min-height: 200px;"
                                 loading="lazy"
                                 decoding="async">
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="?route=tour/<?= $tour['tour_id'] ?>" 
                                       class="text-decoration-none text-dark">
                                        <?= htmlspecialchars($tour['tour_nombre']) ?>
                                    </a>
                                </h5>
                                
                                <div class="mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-bold text-primary fs-5">
                                            $<?= Helpers::safeNumberFormat($tour['precio_actual'], 2) ?> USD
                                        </span>
                                        
                                        <?php if (!empty($tour['cambio_precio_porcentaje'])): ?>
                                        <span class="price-change-indicator <?= $tour['cambio_precio_porcentaje'] < 0 ? 'price-decrease' : 'price-increase' ?>">
                                            <?php if ($tour['cambio_precio_porcentaje'] < 0): ?>
                                                <i class="fas fa-arrow-down me-1"></i><?= abs($tour['cambio_precio_porcentaje']) ?>% menos
                                            <?php else: ?>
                                                <i class="fas fa-arrow-up me-1"></i>+<?= $tour['cambio_precio_porcentaje'] ?>%
                                            <?php endif; ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if (!empty($tour['precio_cuando_agregado']) && $tour['precio_cuando_agregado'] != $tour['precio_actual']): ?>
                                    <small class="text-muted">
                                        Precio al agregar: $<?= Helpers::safeNumberFormat($tour['precio_cuando_agregado'], 2) ?>
                                    </small>
                                    <?php endif; ?>
                                </div>

                                <!-- Información adicional -->
                                <div class="mb-3">
                                    <?php if ($tour['disponible_ahora']): ?>
                                    <span class="badge bg-success me-2">
                                        <i class="fas fa-check me-1"></i>Disponible
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-warning text-dark me-2">
                                        <i class="fas fa-clock me-1"></i>No disponible
                                    </span>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($tour['duracion'])): ?>
                                    <span class="badge bg-secondary me-2">
                                        <i class="fas fa-clock me-1"></i><?= htmlspecialchars($tour['duracion']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Notas personales -->
                                <?php if (!empty($tour['notas_personales'])): ?>
                                <div class="mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-sticky-note me-1"></i>
                                        <?= htmlspecialchars($tour['notas_personales']) ?>
                                    </small>
                                </div>
                                <?php endif; ?>

                                <small class="text-muted">
                                    <i class="fas fa-plus me-1"></i>
                                    Agregado el <?= date('d/m/Y', strtotime($tour['fecha_agregado'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="wishlist-actions">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="btn-group" role="group">
                                <a href="?route=tour/<?= $tour['tour_id'] ?>" 
                                   class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>Ver Detalles
                                </a>
                                <button class="btn btn-outline-info btn-sm create-price-alert" 
                                        data-item-id="<?= $tour['item_id'] ?>"
                                        data-current-price="<?= $tour['precio_actual'] ?>"
                                        data-tour-name="<?= htmlspecialchars($tour['tour_nombre']) ?>">
                                    <i class="fas fa-bell me-1"></i>Alerta Precio
                                </button>
                            </div>
                            
                            <button class="btn btn-remove-wishlist btn-sm remove-from-wishlist" 
                                    data-tour-id="<?= $tour['tour_id'] ?>">
                                <i class="fas fa-trash me-1"></i>Remover
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Recomendaciones -->
        <?php if (!empty($recommendations)): ?>
        <div class="recommendations-section">
            <div class="container">
                <h3 class="mb-4">
                    <i class="fas fa-magic me-2 text-warning"></i>
                    Recomendaciones basadas en tu lista
                </h3>
                <div class="row">
                    <?php foreach ($recommendations as $rec): ?>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card h-100 shadow-sm">
                            <img src="<?= htmlspecialchars(Helpers::tourImage($rec['imagen_principal'] ?? null, 'images/tour-placeholder.jpg')) ?>" 
                                 class="card-img-top" style="height: 200px; object-fit: cover;"
                                 alt="<?= htmlspecialchars($rec['nombre']) ?>"
                                 loading="lazy"
                                 decoding="async">
                            <div class="card-body">
                                <h6 class="card-title"><?= htmlspecialchars($rec['nombre']) ?></h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-primary">
                                        $<?= number_format($rec['precio'], 2) ?>
                                    </span>
                                    <button class="btn btn-outline-danger btn-sm add-to-wishlist" 
                                            data-tour-id="<?= $rec['id'] ?>"
                                            aria-label="Agregar a la lista de deseos">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal para compartir wishlist -->
    <div class="modal fade" id="shareWishlistModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-share-alt me-2"></i>Compartir Mi Lista de Deseos
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Genera un enlace para compartir tu lista de deseos con amigos y familiares.
                    </p>
                    <div class="mb-3">
                        <label for="shareExpiration" class="form-label">Válido por:</label>
                        <select class="form-select" id="shareExpiration">
                            <option value="7">7 días</option>
                            <option value="30" selected>30 días</option>
                            <option value="90">90 días</option>
                        </select>
                    </div>
                    <div id="shareResult" class="d-none">
                        <div class="alert alert-success">
                            <strong>¡Enlace generado!</strong>
                        </div>
                        <div class="input-group">
                            <input type="text" class="form-control" id="shareUrl" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="copyShareUrl">
                                <i class="fas fa-copy"></i> Copiar
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="generateShareLink">
                        <i class="fas fa-link me-2"></i>Generar Enlace
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para crear alerta de precio -->
    <div class="modal fade" id="priceAlertModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-bell me-2"></i>Crear Alerta de Precio
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="priceAlertForm">
                    <div class="modal-body">
                        <input type="hidden" id="alertItemId">
                        <input type="hidden" id="alertCurrentPrice">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        
                        <p class="mb-3">
                            <strong id="alertTourName"></strong><br>
                            <span class="text-muted">Precio actual: $<span id="displayCurrentPrice"></span></span>
                        </p>

                        <div class="mb-3">
                            <label for="alertType" class="form-label">Tipo de alerta:</label>
                            <select class="form-select" id="alertType" name="alert_type" required>
                                <option value="precio_menor">Cuando el precio sea menor o igual a:</option>
                                <option value="descuento_porcentaje">Cuando tenga un descuento de:</option>
                            </select>
                        </div>

                        <div class="mb-3" id="targetPriceGroup">
                            <label for="targetPrice" class="form-label">Precio objetivo (USD):</label>
                            <input type="number" class="form-control" id="targetPrice" name="target_price" 
                                   step="0.01" min="1" required>
                        </div>

                        <div class="mb-3 d-none" id="discountPercentageGroup">
                            <label for="discountPercentage" class="form-label">Porcentaje de descuento (%):</label>
                            <input type="number" class="form-control" id="discountPercentage" name="discount_percentage" 
                                   step="1" min="5" max="80">
                        </div>

                        <div class="alert alert-info">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                Te notificaremos por email cuando se cumpla tu condición de precio.
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-bell me-2"></i>Crear Alerta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Remover de wishlist
        $('.remove-from-wishlist').click(function() {
            if (!confirm('¿Estás seguro de que quieres remover este tour de tu lista?')) {
                return;
            }

            const tourId = $(this).data('tour-id');
            const $item = $(this).closest('.wishlist-item');

            $.post('?route=wishlist/remove', {
                tour_id: tourId,
                csrf_token: '<?= $csrf_token ?>'
            })
            .done(function(response) {
                if (response.success) {
                    $item.fadeOut(function() {
                        $item.remove();
                        // Update counters
                        location.reload();
                    });
                } else {
                    alert('Error: ' + response.error);
                }
            })
            .fail(function() {
                alert('Error de conexión. Intenta de nuevo.');
            });
        });

        // Agregar a wishlist (recomendaciones)
        $('.add-to-wishlist').click(function() {
            const tourId = $(this).data('tour-id');
            const $btn = $(this);

            $.post('?route=wishlist/add', {
                tour_id: tourId,
                csrf_token: '<?= $csrf_token ?>'
            })
            .done(function(response) {
                if (response.success) {
                    $btn.removeClass('btn-outline-danger').addClass('btn-danger');
                    $btn.html('<i class="fas fa-heart"></i> Agregado');
                    $btn.prop('disabled', true);
                } else {
                    alert('Error: ' + response.error);
                }
            });
        });

        // Generar enlace para compartir
        $('#generateShareLink').click(function() {
            const days = $('#shareExpiration').val();
            
            $.post('?route=wishlist/share', {
                expires_in_days: days,
                csrf_token: '<?= $csrf_token ?>'
            })
            .done(function(response) {
                if (response.success) {
                    $('#shareUrl').val(response.share_url);
                    $('#shareResult').removeClass('d-none');
                    $(this).prop('disabled', true);
                } else {
                    alert('Error: ' + response.error);
                }
            });
        });

        // Copiar enlace
        $('#copyShareUrl').click(function() {
            $('#shareUrl')[0].select();
            document.execCommand('copy');
            $(this).html('<i class="fas fa-check"></i> Copiado');
            setTimeout(() => {
                $(this).html('<i class="fas fa-copy"></i> Copiar');
            }, 2000);
        });

        // Modal de alerta de precio
        $('.create-price-alert').click(function() {
            const itemId = $(this).data('item-id');
            const currentPrice = $(this).data('current-price');
            const tourName = $(this).data('tour-name');

            $('#alertItemId').val(itemId);
            $('#alertCurrentPrice').val(currentPrice);
            $('#displayCurrentPrice').text(parseFloat(currentPrice).toFixed(2));
            $('#alertTourName').text(tourName);
            $('#targetPrice').val((currentPrice * 0.9).toFixed(2));

            $('#priceAlertModal').modal('show');
        });

        // Cambio de tipo de alerta
        $('#alertType').change(function() {
            if ($(this).val() === 'descuento_porcentaje') {
                $('#targetPriceGroup').addClass('d-none');
                $('#discountPercentageGroup').removeClass('d-none');
                $('#targetPrice').prop('required', false);
                $('#discountPercentage').prop('required', true);
            } else {
                $('#targetPriceGroup').removeClass('d-none');
                $('#discountPercentageGroup').addClass('d-none');
                $('#targetPrice').prop('required', true);
                $('#discountPercentage').prop('required', false);
            }
        });

        // Enviar formulario de alerta
        $('#priceAlertForm').submit(function(e) {
            e.preventDefault();
            
            $.post('?route=wishlist/createAlert', {
                item_id: $('#alertItemId').val(),
                target_price: $('#targetPrice').val(),
                alert_type: $('#alertType').val(),
                discount_percentage: $('#discountPercentage').val(),
                csrf_token: '<?= $csrf_token ?>'
            })
            .done(function(response) {
                if (response.success) {
                    $('#priceAlertModal').modal('hide');
                    alert('¡Alerta creada! Te notificaremos cuando se cumpla tu condición.');
                } else {
                    alert('Error: ' + response.error);
                }
            });
        });

        // Marcar notificación como leída
        $('.mark-notification-read').click(function() {
            const notificationId = $(this).data('notification-id');
            const $notification = $(this).closest('li');

            $.post('?route=wishlist/markNotificationRead', {
                notification_id: notificationId,
                csrf_token: '<?= $csrf_token ?>'
            })
            .done(function(response) {
                if (response.success) {
                    $notification.fadeOut();
                }
            });
        });
    });
    </script>
</body>
</html>
