<?php
use App\Core\Helpers;
// Helpers already loaded by framework
$title = $title ?? 'Configuración de Lista de Deseos';
$wishlist = $wishlist ?? [];
$csrf_token = $csrf_token ?? '';

$flash_message = Helpers::getFlashMessage();
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
        .settings-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        .settings-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        .settings-section {
            padding: 2rem;
            border-bottom: 1px solid #e9ecef;
        }
        .settings-section:last-child {
            border-bottom: none;
        }
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f8f9fa;
        }
        .setting-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .setting-description {
            flex: 1;
            margin-right: 1rem;
        }
        .setting-description h6 {
            margin-bottom: 0.25rem;
            font-weight: 600;
        }
        .setting-description small {
            color: #6c757d;
            line-height: 1.4;
        }
        .form-switch .form-check-input {
            width: 3rem;
            height: 1.5rem;
        }
        .preview-box {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            margin: 1rem 0;
        }
        .notification-preview {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>
<body class="legacy-theme">
    <div class="settings-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="mb-2">
                        <i class="fas fa-cog me-3"></i><?= htmlspecialchars($title) ?>
                    </h1>
                    <p class="mb-0 opacity-75">Personaliza cómo y cuándo recibir notificaciones de tu wishlist</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <a href="?route=wishlist" class="btn btn-light">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Mi Lista
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Flash messages -->
        <?php if ($flash_message): ?>
        <div class="alert alert-<?= $flash_message['type'] ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($flash_message['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
        <?php endif; ?>

        <form method="POST" action="?route=wishlist/settings">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            
            <div class="settings-card">
                <!-- Notificaciones de Precio -->
                <div class="settings-section">
                    <h4 class="mb-4">
                        <i class="fas fa-dollar-sign text-success me-2"></i>
                        Notificaciones de Precio
                    </h4>
                    
                    <div class="setting-item">
                        <div class="setting-description">
                            <h6>Cambios de precio</h6>
                            <small>
                                Recibe notificaciones cuando cambien los precios de los tours en tu lista.
                                Te alertaremos tanto cuando suban como cuando bajen los precios.
                            </small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notify_price_changes" 
                                   name="notify_price_changes" value="1"
                                   <?= ($wishlist['notificar_cambios_precio'] ?? true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notify_price_changes"></label>
                        </div>
                    </div>

                    <div class="notification-preview">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-arrow-down text-success fs-4"></i>
                            </div>
                            <div>
                                <strong class="text-success">¡Precio reducido! 15% menos</strong><br>
                                <small class="text-muted">
                                    El precio del Tour Tikal bajó de $85.00 a $72.25. ¡No dejes pasar esta oportunidad!
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notificaciones de Disponibilidad -->
                <div class="settings-section">
                    <h4 class="mb-4">
                        <i class="fas fa-calendar-check text-info me-2"></i>
                        Notificaciones de Disponibilidad
                    </h4>
                    
                    <div class="setting-item">
                        <div class="setting-description">
                            <h6>Disponibilidad de fechas</h6>
                            <small>
                                Te notificaremos cuando haya nuevas fechas disponibles para tours que estén agotados,
                                o cuando queden pocos cupos disponibles.
                            </small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notify_availability" 
                                   name="notify_availability" value="1"
                                   <?= ($wishlist['notificar_disponibilidad'] ?? true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notify_availability"></label>
                        </div>
                    </div>

                    <div class="notification-preview">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-calendar-plus text-info fs-4"></i>
                            </div>
                            <div>
                                <strong class="text-info">¡Nueva fecha disponible!</strong><br>
                                <small class="text-muted">
                                    El Tour Semuc Champey ya tiene disponibilidad para el 15 de Marzo. ¡Reserva ahora!
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ofertas Especiales -->
                <div class="settings-section">
                    <h4 class="mb-4">
                        <i class="fas fa-gift text-warning me-2"></i>
                        Ofertas Especiales
                    </h4>
                    
                    <div class="setting-item">
                        <div class="setting-description">
                            <h6>Promociones y descuentos</h6>
                            <small>
                                Recibe notificaciones sobre ofertas especiales, promociones por tiempo limitado
                                y descuentos exclusivos para los tours de tu lista.
                            </small>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="notify_special_offers" 
                                   name="notify_special_offers" value="1"
                                   <?= ($wishlist['notificar_ofertas_especiales'] ?? true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="notify_special_offers"></label>
                        </div>
                    </div>

                    <div class="notification-preview">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-tag text-warning fs-4"></i>
                            </div>
                            <div>
                                <strong class="text-warning">🎉 Oferta especial por tiempo limitado</strong><br>
                                <small class="text-muted">
                                    Tour Yaxha con 25% de descuento solo por este fin de semana. Código: MAYA25
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información adicional -->
            <div class="settings-card">
                <div class="settings-section">
                    <h4 class="mb-4">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Información sobre las Notificaciones
                    </h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-start mb-3">
                                <i class="fas fa-envelope text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="mb-1">Entrega por Email</h6>
                                    <small class="text-muted">
                                        Las notificaciones se envían a tu email registrado: 
                                        <strong><?= htmlspecialchars($_SESSION['user_email'] ?? 'email@ejemplo.com') ?></strong>
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex align-items-start mb-3">
                                <i class="fas fa-clock text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="mb-1">Frecuencia</h6>
                                    <small class="text-muted">
                                        Verificamos cambios cada 6 horas y enviamos notificaciones inmediatamente.
                                        No enviaremos spam - máximo 1 email por tour por día.
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex align-items-start mb-3">
                                <i class="fas fa-bell text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="mb-1">Alertas Personalizadas</h6>
                                    <small class="text-muted">
                                        Puedes crear alertas específicas de precio desde cada tour en tu wishlist.
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex align-items-start mb-3">
                                <i class="fas fa-shield-alt text-primary me-3 mt-1"></i>
                                <div>
                                    <h6 class="mb-1">Privacidad</h6>
                                    <small class="text-muted">
                                        Nunca compartimos tu información. Puedes darte de baja en cualquier momento.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <a href="?route=wishlist" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Cancelar
                    </a>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save me-2"></i>Guardar Configuración
                    </button>
                </div>
            </div>
        </form>

        <!-- Testing section (solo para admin/desarrollo) -->
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
        <div class="settings-card border-warning">
            <div class="settings-section">
                <h4 class="mb-3 text-warning">
                    <i class="fas fa-wrench me-2"></i>Panel de Testing (Solo Admin)
                </h4>
                <p class="text-muted mb-3">
                    Herramientas para probar el sistema de notificaciones en desarrollo.
                </p>
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-warning" id="testPriceNotification">
                        <i class="fas fa-dollar-sign me-1"></i>Probar Notif. Precio
                    </button>
                    <button type="button" class="btn btn-outline-info" id="testAvailabilityNotification">
                        <i class="fas fa-calendar me-1"></i>Probar Notif. Disponibilidad
                    </button>
                    <button type="button" class="btn btn-outline-success" id="testSpecialOfferNotification">
                        <i class="fas fa-gift me-1"></i>Probar Notif. Oferta
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
    $(document).ready(function() {
        // Mostrar/ocultar previews basado en el estado de los switches
        $('.form-check-input').change(function() {
            const isChecked = $(this).is(':checked');
            const preview = $(this).closest('.settings-section').find('.notification-preview');
            
            if (isChecked) {
                preview.fadeIn();
            } else {
                preview.fadeOut();
            }
        });

        // Testing functions (solo para admin)
        $('#testPriceNotification').click(function() {
            $.post('?route=admin/test-notification', {
                type: 'price_change',
                csrf_token: '<?= $csrf_token ?>'
            })
            .done(function(response) {
                alert(response.success ? 'Notificación de prueba enviada!' : 'Error: ' + response.error);
            });
        });

        $('#testAvailabilityNotification').click(function() {
            $.post('?route=admin/test-notification', {
                type: 'availability',
                csrf_token: '<?= $csrf_token ?>'
            })
            .done(function(response) {
                alert(response.success ? 'Notificación de prueba enviada!' : 'Error: ' + response.error);
            });
        });

        $('#testSpecialOfferNotification').click(function() {
            $.post('?route=admin/test-notification', {
                type: 'special_offer',
                csrf_token: '<?= $csrf_token ?>'
            })
            .done(function(response) {
                alert(response.success ? 'Notificación de prueba enviada!' : 'Error: ' + response.error);
            });
        });

        // Confirmación antes de guardar
        $('form').submit(function(e) {
            const enabledNotifications = $('.form-check-input:checked').length;
            
            if (enabledNotifications === 0) {
                const confirm = window.confirm(
                    '¿Estás seguro de que quieres desactivar todas las notificaciones? ' +
                    'No recibirás alertas sobre cambios en los precios o disponibilidad de tus tours favoritos.'
                );
                
                if (!confirm) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });

        // Tooltips para mejor UX
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    });
    </script>
</body>
</html>
