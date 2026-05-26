<?php

use App\Core\Config;
use App\Core\Helpers;
// Helpers already loaded by framework
$title = $title ?? 'Lista No Disponible';
$message = $message ?? 'Esta lista de deseos no está disponible o ha expirado';
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
        .error-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }
        .error-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('assets/images/maya-pattern.png') repeat;
            opacity: 0.1;
        }
        .error-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 3rem;
            text-align: center;
            position: relative;
            z-index: 2;
            max-width: 600px;
            margin: 0 auto;
        }
        .error-icon {
            font-size: 5rem;
            color: #6c757d;
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .error-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #495057;
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1.2rem;
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .suggestions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        .suggestion-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .suggestion-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
        }
        .suggestion-icon {
            font-size: 2rem;
            color: #007bff;
            margin-bottom: 1rem;
        }
        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
        }
        .floating-element {
            position: absolute;
            opacity: 0.1;
            animation: float 6s ease-in-out infinite;
        }
        .floating-element:nth-child(1) { top: 10%; left: 10%; animation-delay: 0s; }
        .floating-element:nth-child(2) { top: 20%; right: 10%; animation-delay: 2s; }
        .floating-element:nth-child(3) { bottom: 10%; left: 20%; animation-delay: 4s; }
        .floating-element:nth-child(4) { bottom: 20%; right: 15%; animation-delay: 1s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }
    </style>
</head>
<body class="legacy-theme">
    <div class="error-container">
        <!-- Elementos flotantes decorativos -->
        <div class="floating-elements">
            <i class="fas fa-heart floating-element" style="font-size: 3rem;"></i>
            <i class="fas fa-map-marked-alt floating-element" style="font-size: 2rem;"></i>
            <i class="fas fa-share-alt floating-element" style="font-size: 2.5rem;"></i>
            <i class="fas fa-clock floating-element" style="font-size: 2rem;"></i>
        </div>

        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="error-card">
                        <div class="error-icon">
                            <i class="fas fa-hourglass-end"></i>
                        </div>
                        
                        <h1 class="error-title"><?= htmlspecialchars($title) ?></h1>
                        
                        <p class="error-message">
                            <?= htmlspecialchars($message) ?>
                        </p>

                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>¿Qué pudo haber pasado?</strong><br>
                            <small>
                                • El enlace ha expirado (las listas compartidas son válidas por tiempo limitado)<br>
                                • El creador de la lista la eliminó o cambió la configuración<br>
                                • El enlace no es correcto o está incompleto
                            </small>
                        </div>

                        <!-- Sugerencias -->
                        <h4 class="mb-4">¿Qué puedes hacer?</h4>
                        
                        <div class="suggestions-grid">
                            <div class="suggestion-card">
                                <div class="suggestion-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h6>Explorar Tours</h6>
                                <p class="small text-muted mb-3">
                                    Descubre nuestros tours disponibles y crea tu propia lista de favoritos
                                </p>
                                <a href="?route=tours" class="btn btn-primary btn-sm">
                                    Ver Tours
                                </a>
                            </div>
                            
                            <div class="suggestion-card">
                                <div class="suggestion-icon">
                                    <i class="fas fa-heart"></i>
                                </div>
                                <h6>Crear Tu Lista</h6>
                                <p class="small text-muted mb-3">
                                    Inicia sesión y crea tu propia lista de tours favoritos para compartir
                                </p>
                                <?php if (isset($_SESSION['user_email'])): ?>
                                <a href="?route=wishlist" class="btn btn-success btn-sm">
                                    Mi Lista
                                </a>
                                <?php else: ?>
                                <a href="?route=login" class="btn btn-success btn-sm">
                                    Iniciar Sesión
                                </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="suggestion-card">
                                <div class="suggestion-icon">
                                    <i class="fas fa-phone"></i>
                                </div>
                                <h6>Contactar</h6>
                                <p class="small text-muted mb-3">
                                    ¿Necesitas ayuda? Nuestro equipo está listo para asistirte
                                </p>
                                <a href="?route=contact" class="btn btn-info btn-sm">
                                    Contacto
                                </a>
                            </div>
                            
                            <div class="suggestion-card">
                                <div class="suggestion-icon">
                                    <i class="fas fa-star"></i>
                                </div>
                                <h6>Tours Populares</h6>
                                <p class="small text-muted mb-3">
                                    Ve las experiencias más reservadas y mejor valoradas
                                </p>
                                <a href="?route=tours?sort=popular" class="btn btn-warning btn-sm text-white">
                                    Ver Populares
                                </a>
                            </div>
                        </div>

                        <!-- Tours destacados como alternativa -->
                        <div class="mt-4 pt-4 border-top">
                            <h5 class="mb-3">Mientras tanto, conoce nuestros tours más populares:</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm">
                                        <img src="assets/images/tikal-thumb.jpg" class="card-img-top" style="height: 120px; object-fit: cover;" alt="Tikal" loading="lazy" decoding="async">
                                        <div class="card-body p-3">
                                            <h6 class="card-title mb-1">Tour Tikal</h6>
                                            <small class="text-muted">Desde $85 USD</small>
                                            <div class="mt-2">
                                                <a href="?route=tour/1" class="btn btn-outline-primary btn-sm">Ver más</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm">
                                        <img src="assets/images/semuc-thumb.jpg" class="card-img-top" style="height: 120px; object-fit: cover;" alt="Semuc Champey" loading="lazy" decoding="async">
                                        <div class="card-body p-3">
                                            <h6 class="card-title mb-1">Semuc Champey</h6>
                                            <small class="text-muted">Desde $95 USD</small>
                                            <div class="mt-2">
                                                <a href="?route=tour/2" class="btn btn-outline-primary btn-sm">Ver más</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm">
                                        <img src="assets/images/yaxha-thumb.jpg" class="card-img-top" style="height: 120px; object-fit: cover;" alt="Yaxha" loading="lazy" decoding="async">
                                        <div class="card-body p-3">
                                            <h6 class="card-title mb-1">Yaxha Sunset</h6>
                                            <small class="text-muted">Desde $75 USD</small>
                                            <div class="mt-2">
                                                <a href="?route=tour/3" class="btn btn-outline-primary btn-sm">Ver más</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones principales -->
                        <div class="mt-4 pt-4">
                            <div class="d-flex gap-3 justify-content-center flex-wrap">
                                <a href="?route=tours" class="btn btn-primary btn-lg">
                                    <i class="fas fa-map-marked-alt me-2"></i>
                                    Explorar Todos los Tours
                                </a>
                                <a href="<?= Config::getBaseUrl() ?>" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-home me-2"></i>
                                    Ir al Inicio
                                </a>
                            </div>
                        </div>

                        <!-- Información adicional -->
                        <div class="mt-4 pt-3 border-top">
                            <p class="text-muted small mb-0">
                                <i class="fas fa-shield-alt me-1"></i>
                                Si crees que esto es un error, puedes 
                                <a href="?route=contact" class="text-decoration-none">contactarnos</a> 
                                y te ayudaremos a resolverlo.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Añadir efectos de interacción
    document.addEventListener('DOMContentLoaded', function() {
        // Animación de entrada para la tarjeta principal
        const errorCard = document.querySelector('.error-card');
        errorCard.style.opacity = '0';
        errorCard.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            errorCard.style.transition = 'all 0.6s ease';
            errorCard.style.opacity = '1';
            errorCard.style.transform = 'translateY(0)';
        }, 100);

        // Animación de las sugerencias
        const suggestionCards = document.querySelectorAll('.suggestion-card');
        suggestionCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.4s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 300 + (index * 100));
        });

        // Efecto hover para las tarjetas de tours
        const tourCards = document.querySelectorAll('.card');
        tourCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });
    </script>
</body>
</html>
