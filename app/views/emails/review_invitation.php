<?php
use App\Core\Config;
$nombre = $data['destinatario_nombre'] ?? 'Estimado viajero';
$tourNombre = $data['tour_nombre'] ?? 'su tour';
$fechaTour = $data['fecha_tour'] ?? null;
$reviewUrl = $data['review_url'] ?? '#';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Comparte tu Experiencia!</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            text-align: center;
            padding: 30px 20px;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #fff;
            padding: 30px;
            border: 1px solid #e9ecef;
        }
        .highlight-box {
            background: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .benefits-box {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .cta-button {
            display: block;
            width: 100%;
            max-width: 350px;
            margin: 20px auto;
            padding: 15px 30px;
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 0 0 10px 10px;
            border: 1px solid #e9ecef;
            border-top: none;
        }
        .tour-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .stars {
            font-size: 24px;
            color: #ffc107;
            text-align: center;
            margin: 15px 0;
        }
        .verified-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="stars">⭐⭐⭐⭐⭐</div>
        <h1>¡Comparte tu Experiencia!</h1>
        <p>Tu opinión es muy valiosa para nosotros</p>
    </div>

    <div class="content">
        <p>Hola <strong><?= htmlspecialchars($nombre) ?></strong>,</p>

        <p>Esperamos que hayas disfrutado mucho de tu reciente experiencia con nosotros en <strong><?= htmlspecialchars($tourNombre) ?></strong>.</p>

        <div class="tour-info">
            <h3>🏛️ Tu Experiencia Maya</h3>
            <p><strong>Tour:</strong> <?= htmlspecialchars($tourNombre) ?></p>
            <?php if ($fechaTour): ?>
            <p><strong>Fecha:</strong> <?= date('d \d\e F, Y', strtotime($fechaTour)) ?></p>
            <?php endif; ?>
            <div class="verified-badge">
                <span>🏆 Review Verificado</span>
            </div>
        </div>

        <div class="highlight-box">
            <h4>✨ ¿Por qué es importante tu review?</h4>
            <p>Como cliente que realmente vivió esta experiencia, tu opinión tiene un valor especial. Los reviews verificados ayudan a otros viajeros a elegir la aventura perfecta y nos ayudan a seguir mejorando nuestros servicios.</p>
        </div>

        <div class="benefits-box">
            <h4>🎁 Al dejar tu review obtienes:</h4>
            <ul>
                <li><strong>Badge "Cliente Verificado"</strong> - Tu review aparece destacado</li>
                <li><strong>Descuento 5%</strong> en tu próxima reserva</li>
                <li><strong>Acceso prioritario</strong> a ofertas especiales</li>
                <li><strong>Reconocimiento</strong> como miembro de nuestra comunidad de viajeros</li>
            </ul>
        </div>

        <a href="<?= htmlspecialchars($reviewUrl) ?>" class="cta-button">
            ⭐ Escribir Mi Review Verificado
        </a>

        <div class="highlight-box">
            <h4>📝 ¿Qué puedes compartir?</h4>
            <ul>
                <li>¿Cómo fue tu experiencia general?</li>
                <li>¿Qué te gustó más del tour?</li>
                <li>¿Cómo calificarías a tu guía?</li>
                <li>¿Recomendarías esta experiencia?</li>
                <li>¿Algún consejo para futuros viajeros?</li>
            </ul>
            <p><small>Tu review solo te tomará 3-5 minutos y será muy valioso para otros aventureros.</small></p>
        </div>

        <p><strong>¿Tuviste algún inconveniente?</strong> Antes de escribir tu review, contáctanos directamente para que podamos solucionarlo:</p>
        <ul>
            <li>📱 WhatsApp: <a href="https://wa.me/<?= Config::SOCIAL_WHATSAPP ?? '+50212345678' ?>">Click aquí para chatear</a></li>
            <li>📧 Email: <a href="mailto:<?= Config::COMPANY_EMAIL ?? 'info@mayanworldtravelagency.com' ?>"><?= Config::COMPANY_EMAIL ?? 'info@mayanworldtravelagency.com' ?></a></li>
        </ul>

        <p>Muchas gracias por elegir Travel Mayan World para tu aventura. ¡Esperamos verte pronto en otra experiencia increíble!</p>

        <p>Con cariño,<br>
        <strong>Todo el equipo de Travel Mayan World</strong></p>
    </div>

    <div class="footer">
        <p><small>
            Esta invitación es válida por 30 días y solo está disponible para clientes verificados.<br>
            Tu review será marcado como "Compra Verificada" para mayor credibilidad.
        </small></p>
        <p><small>
            © <?= date('Y') ?> Travel Mayan World - Todos los derechos reservados<br>
            Especialistas en Turismo Maya desde hace 20+ años
        </small></p>
        <p><small>
            Si no deseas recibir invitaciones de review, <a href="mailto:<?= htmlspecialchars(Config::COMPANY_EMAIL) ?>?subject=<?= rawurlencode('Baja de invitaciones de review') ?>">haz clic aquí para darte de baja</a>
        </small></p>
    </div>
</body>
</html>
