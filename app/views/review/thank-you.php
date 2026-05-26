<?php

use App\Core\Config;
$reviewId = $data['review_id'] ?? null;
$autoApproved = $data['auto_approved'] ?? false;
$message = $data['message'] ?? '';
$tourName = $data['tour_name'] ?? 'el tour';
$tourId = $data['tour_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gracias por tu Review - <?= Config::APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .thank-you-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            padding: 4rem 0;
            color: white;
            text-align: center;
        }
        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }
        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                animation-timing-function: cubic-bezier(0.215, 0.610, 0.355, 1.000);
                transform: translate3d(0,0,0);
            }
            40%, 43% {
                animation-timing-function: cubic-bezier(0.755, 0.050, 0.855, 0.060);
                transform: translate3d(0, -30px, 0);
            }
            70% {
                animation-timing-function: cubic-bezier(0.755, 0.050, 0.855, 0.060);
                transform: translate3d(0, -15px, 0);
            }
        }
        .review-status {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            font-size: 1rem;
        }
        .status-published {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }
        .status-pending {
            background: linear-gradient(45deg, #ffc107, #fd7e14);
            color: white;
        }
        .benefits-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
        }
        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .benefit-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(45deg, #007bff, #6610f2);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        .social-sharing {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin: 2rem 0;
        }
        .social-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.75rem 1.5rem;
            margin: 0.5rem;
            border-radius: 25px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .social-btn:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .social-btn.facebook { background: #3b5998; }
        .social-btn.twitter { background: #1da1f2; }
        .social-btn.whatsapp { background: #25d366; }
        .action-buttons {
            text-align: center;
            margin-top: 3rem;
        }
    </style>
</head>
<body>
    <!-- Header de Agradecimiento -->
    <div class="thank-you-header">
        <div class="container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="mb-3">¡Gracias por tu Review!</h1>
            <p class="lead">Tu experiencia ha sido registrada exitosamente</p>
        </div>
    </div>

    <div class="container">
        <!-- Estado del Review -->
        <div class="review-status">
            <div class="text-center">
                <?php if ($autoApproved): ?>
                    <div class="status-badge status-published mb-3">
                        <i class="fas fa-thumbs-up me-2"></i>
                        Review Publicado Inmediatamente
                    </div>
                    <h3>¡Tu review ya está visible!</h3>
                    <p class="text-muted">
                        Gracias por compartir tu excelente experiencia con <strong><?= htmlspecialchars($tourName) ?></strong>. 
                        Tu review verificado ya está ayudando a otros viajeros a tomar su decisión.
                    </p>
                <?php else: ?>
                    <div class="status-badge status-pending mb-3">
                        <i class="fas fa-clock me-2"></i>
                        En Proceso de Moderación
                    </div>
                    <h3>Revisaremos tu experiencia</h3>
                    <p class="text-muted">
                        Tu review sobre <strong><?= htmlspecialchars($tourName) ?></strong> ha sido recibido. 
                        Nuestro equipo lo revisará y será publicado dentro de las próximas 24 horas.
                    </p>
                <?php endif; ?>

                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-certificate me-1"></i>
                        Review ID: #<?= $reviewId ?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Beneficios de Compartir -->
        <div class="benefits-section">
            <h4 class="text-center mb-4">¿Por qué son importantes tus reviews?</h4>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Ayudas a Otros Viajeros</h6>
                            <small class="text-muted">Tu experiencia guía a futuras personas a elegir el tour perfecto</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-medal"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Reconoces la Calidad</h6>
                            <small class="text-muted">Destacas el buen servicio y motivas a mantener altos estándares</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Review Verificado</h6>
                            <small class="text-muted">Tu opinión tiene mayor credibilidad por ser de un cliente real</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <div>
                            <h6 class="mb-1">Construyes Comunidad</h6>
                            <small class="text-muted">Formas parte de una comunidad de viajeros que se ayudan mutuamente</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compartir en Redes Sociales -->
        <div class="social-sharing">
            <h4 class="mb-3">¡Comparte tu Experiencia!</h4>
            <p class="mb-4">Ayuda a más personas a descubrir esta increíble aventura</p>

            <div class="social-buttons">
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(Config::getBaseUrl() . '?route=tour/' . ($tourId ?: 1)) ?>&quote=<?= urlencode('Acabo de hacer el tour ' . $tourName . ' ¡Fue increíble!') ?>" 
                   target="_blank" class="social-btn facebook">
                    <i class="fab fa-facebook-f me-2"></i>
                    Facebook
                </a>

                <a href="https://twitter.com/intent/tweet?text=<?= urlencode('Acabo de hacer el tour ' . $tourName . ' con @MayanWorldTravel ¡Fue increíble!') ?>&url=<?= urlencode(Config::getBaseUrl()) ?>" 
                   target="_blank" class="social-btn twitter">
                    <i class="fab fa-twitter me-2"></i>
                    Twitter
                </a>

                <a href="https://wa.me/?text=<?= urlencode('¡Acabo de hacer el tour ' . $tourName . '! Fue una experiencia increíble. Te recomiendo ' . Config::getBaseUrl()) ?>" 
                   target="_blank" class="social-btn whatsapp">
                    <i class="fab fa-whatsapp me-2"></i>
                    WhatsApp
                </a>
            </div>
        </div>

        <!-- Próximos Pasos -->
        <div class="text-center my-5">
            <h4 class="mb-4">¿Qué sigue?</h4>
            
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>¿Te gustó la experiencia?</strong> 
                        Explora nuestros otros tours y descubre más aventuras increíbles en el mundo maya.
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="action-buttons mb-5">
            <a href="?route=tours" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-compass me-2"></i>
                Explorar Más Tours
            </a>
            
            <a href="?route=home" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-home me-2"></i>
                Ir al Inicio
            </a>
        </div>

        <!-- Contacto -->
        <div class="text-center text-muted mb-4">
            <p class="mb-2">¿Alguna pregunta sobre tu review?</p>
            <p>
                <i class="fas fa-envelope me-1"></i> 
                <a href="mailto:<?= Config::COMPANY_EMAIL ?? 'info@mayanworldtravelagency.com' ?>">Contáctanos</a>
                <span class="mx-3">|</span>
                <i class="fab fa-whatsapp me-1"></i>
                <a href="https://wa.me/<?= Config::SOCIAL_WHATSAPP ?? '+50212345678' ?>">WhatsApp</a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animación de confetti al cargar (simple)
        window.addEventListener('load', function() {
            // Scroll suave al contenido principal
            document.querySelector('.review-status').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        });

        // Analytics o tracking si es necesario
        console.log('Review enviado exitosamente - ID: <?= $reviewId ?>');
    </script>
</body>
</html>
