<?php
use App\Core\Config;
$notification = $data['notification'] ?? [];
$product = $data['product'] ?? [];
$wishlistUrl = $data['wishlist_url'] ?? '#';
$productUrl = $data['product_url'] ?? '#';
$unsubscribeUrl = $data['unsubscribe_url'] ?? '#';
$userName = $data['user_name'] ?? 'Estimado viajero';

// Definir el tipo de notificación y sus propiedades
$notificationTypes = [
    'precio_bajo' => [
        'color' => '#10b981',
        'icon' => '📉',
        'title' => '¡Precio reducido!',
        'bg_color' => '#d1fae5'
    ],
    'precio_subio' => [
        'color' => '#f59e0b',
        'icon' => '📈',
        'title' => 'Cambio de precio',
        'bg_color' => '#fef3c7'
    ],
    'disponible' => [
        'color' => '#3b82f6',
        'icon' => '🎉',
        'title' => '¡Ya hay cupos!',
        'bg_color' => '#dbeafe'
    ],
    'oferta_especial' => [
        'color' => '#8b5cf6',
        'icon' => '🎁',
        'title' => 'Oferta especial',
        'bg_color' => '#ede9fe'
    ],
    'ultimo_cupo' => [
        'color' => '#ef4444',
        'icon' => '⚠️',
        'title' => 'Últimos cupos',
        'bg_color' => '#fee2e2'
    ]
];

$type = $notification['tipo'] ?? 'precio_bajo';
$typeConfig = $notificationTypes[$type] ?? $notificationTypes['precio_bajo'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de tu Lista de Deseos</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .email-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, <?= $typeConfig['color'] ?> 0%, <?= $typeConfig['color'] ?>dd 100%);
            color: white;
            text-align: center;
            padding: 2rem;
        }
        .header-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
        .header h1 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }
        .header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 1rem;
        }
        .content {
            padding: 2rem;
        }
        .notification-box {
            background: <?= $typeConfig['bg_color'] ?>;
            border-left: 4px solid <?= $typeConfig['color'] ?>;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1.5rem 0;
        }
        .notification-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        .notification-message {
            font-size: 1rem;
            color: #4b5563;
            margin: 0;
        }
        .product-card {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin: 1.5rem 0;
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .product-info {
            padding: 1.5rem;
        }
        .product-name {
            font-size: 1.3rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 1rem;
        }
        .price-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .current-price {
            font-size: 1.8rem;
            font-weight: bold;
            color: <?= $typeConfig['color'] ?>;
        }
        .old-price {
            font-size: 1.2rem;
            color: #6b7280;
            text-decoration: line-through;
        }
        .price-change {
            background: <?= $typeConfig['color'] ?>;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .cta-button {
            display: inline-block;
            background: <?= $typeConfig['color'] ?>;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1rem;
            margin: 1rem 0;
            transition: all 0.3s ease;
        }
        .cta-button:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .secondary-button {
            display: inline-block;
            background: transparent;
            color: <?= $typeConfig['color'] ?>;
            border: 2px solid <?= $typeConfig['color'] ?>;
            padding: 10px 25px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            margin: 0.5rem 0.5rem 0.5rem 0;
        }
        .urgency-banner {
            background: linear-gradient(45deg, #ef4444, #f97316);
            color: white;
            text-align: center;
            padding: 1rem;
            font-weight: 600;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        .footer {
            background: #f9fafb;
            padding: 1.5rem;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 0.5rem 0;
            font-size: 0.875rem;
            color: #6b7280;
        }
        .social-links {
            margin: 1rem 0;
        }
        .social-links a {
            display: inline-block;
            margin: 0 0.5rem;
            padding: 0.5rem;
            background: #e5e7eb;
            border-radius: 50%;
            text-decoration: none;
            color: #6b7280;
            width: 40px;
            height: 40px;
            line-height: 30px;
            text-align: center;
        }
        .unsubscribe {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        .unsubscribe a {
            color: #6b7280;
            text-decoration: underline;
            font-size: 0.8rem;
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            body {
                padding: 10px;
            }
            .content {
                padding: 1rem;
            }
            .header {
                padding: 1.5rem;
            }
            .price-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <?php if ($type === 'ultimo_cupo'): ?>
        <div class="urgency-banner">
            ⚡ ¡ATENCIÓN! Quedan muy pocos cupos disponibles
        </div>
        <?php endif; ?>
        
        <div class="header">
            <span class="header-icon"><?= $typeConfig['icon'] ?></span>
            <h1><?= htmlspecialchars($notification['titulo'] ?? $typeConfig['title']) ?></h1>
            <p>Actualización de tu Lista de Deseos</p>
        </div>

        <div class="content">
            <p>Hola <strong><?= htmlspecialchars($userName) ?></strong>,</p>

            <div class="notification-box">
                <div class="notification-title">
                    <?= htmlspecialchars($notification['titulo'] ?? $typeConfig['title']) ?>
                </div>
                <p class="notification-message">
                    <?= htmlspecialchars($notification['mensaje'] ?? 'Hay una actualización importante para uno de tus tours favoritos.') ?>
                </p>
            </div>

            <div class="product-card">
                <?php if (!empty($product['imagen_principal'])): ?>
                <img src="<?= htmlspecialchars($product['imagen_principal']) ?>" 
                     alt="<?= htmlspecialchars($product['nombre'] ?? 'Tour') ?>"
                     class="product-image">
                <?php endif; ?>
                
                <div class="product-info">
                    <h2 class="product-name">
                        <?= htmlspecialchars($product['nombre'] ?? 'Tour en tu Lista') ?>
                    </h2>

                    <?php if ($type === 'precio_bajo' || $type === 'precio_subio'): ?>
                    <div class="price-info">
                        <span class="current-price">
                            $<?= number_format($notification['precio_nuevo'] ?? $product['precio'] ?? 0, 2) ?> USD
                        </span>
                        
                        <?php if (!empty($notification['precio_anterior'])): ?>
                        <span class="old-price">
                            $<?= number_format($notification['precio_anterior'], 2) ?> USD
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($notification['porcentaje_cambio'])): ?>
                        <span class="price-change">
                            <?php if ($notification['porcentaje_cambio'] < 0): ?>
                                <?= abs($notification['porcentaje_cambio']) ?>% menos
                            <?php else: ?>
                                +<?= $notification['porcentaje_cambio'] ?>%
                            <?php endif; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php elseif ($type === 'disponible'): ?>
                    <div class="price-info">
                        <span class="current-price">
                            $<?= number_format($product['precio'] ?? 0, 2) ?> USD
                        </span>
                        <span class="price-change">¡Ya disponible!</span>
                    </div>
                    <?php endif; ?>

                    <?php if ($type === 'precio_bajo' || $type === 'disponible' || $type === 'ultimo_cupo'): ?>
                    <div style="margin: 1.5rem 0;">
                        <a href="<?= htmlspecialchars($productUrl) ?>" class="cta-button">
                            <?php if ($type === 'precio_bajo'): ?>
                                🎯 Aprovechar Descuento
                            <?php elseif ($type === 'disponible'): ?>
                                📅 Ver Fechas Disponibles  
                            <?php elseif ($type === 'ultimo_cupo'): ?>
                                ⚡ Reservar Ahora
                            <?php else: ?>
                                👁️ Ver Tour
                            <?php endif; ?>
                        </a>
                    </div>
                    <?php endif; ?>

                    <div>
                        <a href="<?= htmlspecialchars($productUrl) ?>" class="secondary-button">
                            Ver Detalles del Tour
                        </a>
                        <a href="<?= htmlspecialchars($wishlistUrl) ?>" class="secondary-button">
                            Mi Lista de Deseos
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($type === 'precio_bajo'): ?>
            <div class="notification-box" style="background: #fff7ed; border-left-color: #f59e0b;">
                <div class="notification-title" style="color: #92400e;">
                    💡 Consejo del experto
                </div>
                <p class="notification-message" style="color: #78350f;">
                    Los precios pueden cambiar rápidamente. Si este tour está en tus planes, 
                    te recomendamos reservar pronto para asegurar tanto el precio como la disponibilidad.
                </p>
            </div>
            <?php elseif ($type === 'ultimo_cupo'): ?>
            <div class="notification-box" style="background: #fef2f2; border-left-color: #ef4444;">
                <div class="notification-title" style="color: #991b1b;">
                    ⏰ Disponibilidad limitada
                </div>
                <p class="notification-message" style="color: #7f1d1d;">
                    Cuando quedan pocos cupos, suelen agotarse en las próximas horas. 
                    No esperes demasiado si quieres asegurar tu lugar en esta experiencia.
                </p>
            </div>
            <?php endif; ?>

            <div style="margin: 2rem 0; padding: 1rem; background: #f0f9ff; border-radius: 8px; border-left: 4px solid #0ea5e9;">
                <h4 style="margin: 0 0 0.5rem 0; color: #0c4a6e;">
                    🔔 ¿Quieres personalizar tus notificaciones?
                </h4>
                <p style="margin: 0; color: #0c4a6e; font-size: 0.9rem;">
                    Puedes configurar qué tipo de alertas recibir y crear alertas de precio personalizadas desde 
                    <a href="<?= htmlspecialchars($wishlistUrl) ?>/settings" style="color: #0ea5e9;">tu configuración de wishlist</a>.
                </p>
            </div>
        </div>

        <div class="footer">
            <p><strong>Travel Mayan World</strong></p>
            <p>Especialistas en turismo Maya desde hace 20+ años</p>
            
            <div class="social-links">
                <a href="https://wa.me/<?= Config::SOCIAL_WHATSAPP ?? '+50212345678' ?>" title="WhatsApp">📱</a>
                <a href="https://facebook.com/mayanworldtravel" title="Facebook">📘</a>
                <a href="https://instagram.com/mayanworldtravel" title="Instagram">📷</a>
                <a href="mailto:<?= Config::COMPANY_EMAIL ?? 'info@mayanworldtravelagency.com' ?>" title="Email">📧</a>
            </div>
            
            <p>Guatemala, Belice y México | Experiencias auténticas del mundo Maya</p>
            
            <div class="unsubscribe">
                <p>
                    <small>
                        Has recibido este email porque tienes este tour en tu Lista de Deseos.<br>
                        <a href="<?= htmlspecialchars($unsubscribeUrl) ?>">Cancelar notificaciones de wishlist</a> |
                        <a href="<?= htmlspecialchars($wishlistUrl) ?>/settings">Configurar notificaciones</a>
                    </small>
                </p>
            </div>
        </div>
    </div>

    <!-- Analytics tracking (solo para emails abiertos) -->
    <img src="<?= Config::getBaseUrl() ?>api/track-email-open?notification_id=<?= $notification['id'] ?? '' ?>&type=wishlist" 
         width="1" height="1" style="display:none;" alt="">
</body>
</html>
