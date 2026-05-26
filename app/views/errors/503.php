<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servicio No Disponible - Mayan World Travel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
            color: #f5576c;
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
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
        <div class="icon">🔧</div>
        <h1 class="error-code">503</h1>
        <h2 class="error-title">Servicio Temporalmente No Disponible</h2>
        <p class="error-message">
            Estamos realizando mantenimiento en nuestro sistema para mejorar su experiencia.
        </p>
        <p class="error-message">
            Por favor, inténtelo nuevamente en unos minutos.
        </p>
        <a href="javascript:location.reload();" class="btn-home">Recargar Página</a>
        <p style="margin-top: 30px; color: #999; font-size: 14px;">
            ¿Necesita asistencia inmediata?
            <br>
            WhatsApp: <a href="https://wa.me/50278675095" style="color: #f5576c;">+502 7867-5095</a>
        </p>
    </div>
</body>
</html>
