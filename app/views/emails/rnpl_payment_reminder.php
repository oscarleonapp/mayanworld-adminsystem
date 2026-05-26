<?php
use App\Core\Config;
$nombre = $data['destinatario_nombre'] ?? 'Estimado viajero';
$tourNombre = $data['tour_nombre'] ?? 'su tour';
$fechaTour = $data['fecha_tour'] ?? null;
$montoPendiente = $data['monto_pendiente'] ?? 0;
$moneda = $data['moneda'] ?? 'USD';
$paymentUrl = $data['payment_url'] ?? '#';
$daysRemaining = $data['days_remaining'] ?? 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recordatorio de Pago RNPL</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-left: 4px solid #007bff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .urgent-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .amount {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            text-align: center;
        }
        .cta-button {
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 20px auto;
            padding: 15px 30px;
            background: linear-gradient(45deg, #28a745, #20c997);
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
    </style>
</head>
<body>
    <div class="header">
        <h1>⏰ Recordatorio de Pago</h1>
        <p>Tu aventura maya te está esperando</p>
    </div>

    <div class="content">
        <p>Hola <strong><?= htmlspecialchars($nombre) ?></strong>,</p>

        <p>Te recordamos que tienes un pago pendiente para tu reserva de <strong><?= htmlspecialchars($tourNombre) ?></strong>.</p>

        <div class="tour-info">
            <h3>📅 Detalles de tu Tour</h3>
            <p><strong>Tour:</strong> <?= htmlspecialchars($tourNombre) ?></p>
            <?php if ($fechaTour): ?>
            <p><strong>Fecha:</strong> <?= date('d \d\e F, Y', strtotime($fechaTour)) ?></p>
            <?php endif; ?>
            <p><strong>Monto pendiente:</strong> <?= $moneda ?> <?= number_format($montoPendiente, 2) ?></p>
        </div>

        <?php if ($daysRemaining <= 3): ?>
        <div class="urgent-box">
            <h4>🚨 ¡Acción Urgente Requerida!</h4>
            <p>Tu tour es en solo <strong><?= $daysRemaining ?> día<?= $daysRemaining > 1 ? 's' : '' ?></strong>. Para garantizar tu lugar, completa el pago antes de las <strong>48 horas</strong> previas al tour.</p>
        </div>
        <?php else: ?>
        <div class="highlight-box">
            <h4>💡 ¿Por qué elegiste "Reserva Ahora, Paga Después"?</h4>
            <p>Esta opción te permitió asegurar tu lugar con solo el 10% del costo total. Ahora es momento de completar tu pago para disfrutar de esta increíble experiencia.</p>
        </div>
        <?php endif; ?>

        <div class="amount">
            Monto a pagar: <?= $moneda ?> <?= number_format($montoPendiente, 2) ?>
        </div>

        <a href="<?= htmlspecialchars($paymentUrl) ?>" class="cta-button">
            💳 Completar Pago Ahora
        </a>

        <div class="highlight-box">
            <h4>✅ ¿Qué incluye tu pago?</h4>
            <ul>
                <li>Acceso completo al tour según itinerario</li>
                <li>Guía especializado en cultura maya</li>
                <li>Transporte desde/hacia tu hotel</li>
                <li>Seguro de viajero</li>
                <li>Soporte 24/7 durante tu experiencia</li>
            </ul>
        </div>

        <p><strong>¿Tienes preguntas?</strong> Nuestro equipo está listo para ayudarte:</p>
        <ul>
            <li>📱 WhatsApp: <a href="https://wa.me/<?= Config::SOCIAL_WHATSAPP ?? '+50212345678' ?>">Click aquí para chatear</a></li>
            <li>📧 Email: <a href="mailto:<?= Config::COMPANY_EMAIL ?? 'info@mayanworldtravelagency.com' ?>"><?= Config::COMPANY_EMAIL ?? 'info@mayanworldtravelagency.com' ?></a></li>
            <li>☎️ Teléfono: <?= Config::COMPANY_PHONE ?? '+502 1234-5678' ?></li>
        </ul>

        <p>¡Esperamos verte pronto para esta increíble aventura!</p>

        <p>Saludos cordiales,<br>
        <strong>Equipo de Travel Mayan World</strong></p>
    </div>

    <div class="footer">
        <p><small>
            Este es un recordatorio automático para tu reserva con método de pago "Reserva Ahora, Paga Después".<br>
            Si ya completaste tu pago, puedes ignorar este mensaje.
        </small></p>
        <p><small>
            © <?= date('Y') ?> Travel Mayan World - Todos los derechos reservados<br>
            Guatemala • Belice • México
        </small></p>
    </div>
</body>
</html>
