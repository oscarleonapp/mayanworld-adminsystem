<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error Interno - Mayan World Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .error-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 600px;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #667eea;
            margin: 0;
            line-height: 1;
        }
        .error-title {
            font-size: 32px;
            margin: 20px 0;
            color: #333;
        }
        .error-message {
            color: #666;
            font-size: 18px;
            margin-bottom: 30px;
        }
        .btn-home {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.3s;
            border: none;
            font-size: 16px;
        }
        .btn-home:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="icon">⚠️</div>
        <h1 class="error-code">500</h1>
        <h2 class="error-title">Error Interno del Servidor</h2>
        <p class="error-message">
            Lo sentimos, ha ocurrido un error inesperado.
            Nuestro equipo técnico ha sido notificado y está trabajando para resolverlo.
        </p>
        <p class="error-message">
            Por favor, inténtelo nuevamente en unos minutos.
        </p>
        <a href="/" class="btn-home">Volver al Inicio</a>
        <?php
        // Obtener email de configuración
        $contactEmail = 'info@mayanworldtravelagency.com';
        try {
            if (class_exists('App\Core\Config')) {
                $contactEmail = \App\Core\Config::get('company_email', $contactEmail);
            }
        } catch (Exception $e) {}
        ?>
        <p style="margin-top: 30px; color: #999; font-size: 14px;">
            Si el problema persiste, contáctenos:
            <a href="mailto:<?= htmlspecialchars($contactEmail) ?>" style="color: #667eea;"><?= htmlspecialchars($contactEmail) ?></a>
        </p>
    </div>
</body>
</html>
