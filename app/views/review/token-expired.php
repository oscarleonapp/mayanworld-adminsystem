<?php

use App\Core\Config;
$error = $data['error'] ?? 'Token inválido o expirado';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invitación Expirada - <?= Config::APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .expired-header {
            background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
            padding: 4rem 0;
            color: white;
            text-align: center;
        }
        .expired-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            margin: 2rem 0;
        }
        .solution-card {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            border-radius: 10px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        .contact-options {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 2rem;
        }
        .contact-btn {
            flex: 1;
            min-width: 200px;
            max-width: 300px;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            background: white;
        }
        .contact-btn:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.15);
            color: inherit;
        }
        .contact-btn i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="expired-header">
        <div class="container">
            <div class="expired-icon">
                <i class="fas fa-clock"></i>
            </div>
            <h1 class="mb-3">Invitación Expirada</h1>
            <p class="lead">La invitación para dejar tu review ha expirado</p>
        </div>
    </div>

    <div class="container">
        <!-- Información del Error -->
        <div class="info-card">
            <div class="text-center">
                <h3 class="text-danger mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No podemos procesar tu review
                </h3>
                <p class="text-muted fs-5 mb-4"><?= htmlspecialchars($error) ?></p>
                
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>¿Por qué sucede esto?</strong><br>
                    Por seguridad, los enlaces de invitación para reviews tienen un tiempo límite de validez para proteger tu información.
                </div>
            </div>
        </div>

        <!-- Soluciones -->
        <div class="solution-card">
            <h4 class="text-primary mb-3">
                <i class="fas fa-lightbulb me-2"></i>
                ¿Qué puedes hacer?
            </h4>
            <ul class="list-unstyled">
                <li class="mb-2">
                    <i class="fas fa-check text-success me-2"></i>
                    <strong>Contacta a nuestro equipo</strong> - Podemos generar una nueva invitación para ti
                </li>
                <li class="mb-2">
                    <i class="fas fa-check text-success me-2"></i>
                    <strong>Deja un review general</strong> - Puedes escribir un review desde la página del tour
                </li>
                <li class="mb-2">
                    <i class="fas fa-check text-success me-2"></i>
                    <strong>Comparte en redes sociales</strong> - Ayuda a otros viajeros contando tu experiencia
                </li>
            </ul>
        </div>

        <!-- Opciones de Contacto -->
        <div class="text-center mb-4">
            <h4 class="mb-4">Contáctanos para Ayudarte</h4>
            
            <div class="contact-options">
                <a href="https://wa.me/<?= Config::SOCIAL_WHATSAPP ?? '+50212345678' ?>?text=Hola, necesito ayuda con mi invitación de review que ha expirado" 
                   target="_blank" class="contact-btn">
                    <i class="fab fa-whatsapp text-success"></i>
                    <div class="fw-semibold">WhatsApp</div>
                    <div class="text-muted small">Respuesta inmediata</div>
                </a>

                <a href="mailto:<?= Config::COMPANY_EMAIL ?? 'info@mayanworldtravelagency.com' ?>?subject=Ayuda con review - Invitación expirada" 
                   class="contact-btn">
                    <i class="fas fa-envelope text-info"></i>
                    <div class="fw-semibold">Email</div>
                    <div class="text-muted small">Respuesta en 2h</div>
                </a>

                <a href="tel:<?= Config::COMPANY_PHONE ?? '+502 1234-5678' ?>" class="contact-btn">
                    <i class="fas fa-phone text-primary"></i>
                    <div class="fw-semibold">Llamar</div>
                    <div class="text-muted small">Atención 24/7</div>
                </a>
            </div>
        </div>

        <!-- Alternativa: Review General -->
        <div class="text-center mb-5">
            <div class="card border-success">
                <div class="card-body">
                    <h5 class="card-title text-success">
                        <i class="fas fa-star me-2"></i>
                        Alternativa: Review General
                    </h5>
                    <p class="card-text">
                        Mientras tanto, puedes dejar un review general desde nuestro catálogo de tours. 
                        Aunque no será marcado como "verificado", tu opinión sigue siendo muy valiosa.
                    </p>
                    <a href="?route=tours" class="btn btn-success">
                        <i class="fas fa-search me-2"></i>
                        Buscar el Tour y Dejar Review
                    </a>
                </div>
            </div>
        </div>

        <!-- FAQ Rápido -->
        <div class="row mb-5">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-question-circle text-warning me-2"></i>
                            ¿Por qué expiran las invitaciones?
                        </h6>
                        <p class="card-text small">
                            Por seguridad y para garantizar que solo clientes reales dejen reviews verificados. 
                            Esto mantiene la calidad y confiabilidad de nuestro sistema de reviews.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title">
                            <i class="fas fa-clock text-info me-2"></i>
                            ¿Cuánto tiempo tengo para usar la invitación?
                        </h6>
                        <p class="card-text small">
                            Normalmente las invitaciones son válidas por 30 días desde que se envían. 
                            Si necesitas más tiempo, contáctanos y podremos extender el plazo.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="text-center mb-5">
            <a href="?route=tours" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-home me-2"></i>
                Explorar Tours
            </a>
            
            <a href="?route=contact" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-envelope me-2"></i>
                Contactar Soporte
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
