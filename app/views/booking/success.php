<?php
use App\Core\Config;
$booking = $data['booking'] ?? null;

if (!$booking) {
    header('Location: ?route=tours');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva Exitosa - <?= Config::APP_NAME ?></title>
    <link href="<?= Config::getBaseUrl() ?>assets/css/main.css?v=<?= urlencode(Config::getAssetVersion()) ?>" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .success-header {
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
        @media (prefers-reduced-motion: reduce) { .success-icon { animation: none !important; } }
        .booking-details {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .booking-code {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 2rem;
        }
        .next-steps {
            background: white;
            border-left: 4px solid #007bff;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        .contact-options {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 2rem;
        }
        .contact-option {
            flex: 1;
            min-width: 200px;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        .contact-option:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,123,255,0.15);
            color: inherit;
        }
        .contact-option:focus-visible { outline: 2px solid #0d6efd; outline-offset: 2px; }
        .social-proof {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <!-- Header de Éxito -->
    <div class="success-header">
        <div class="container">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="mb-3">¡Reserva Confirmada!</h1>
            <p class="lead">Tu aventura maya está asegurada</p>
        </div>
    </div>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Código de Reserva -->
                <div class="booking-code">
                    <h3 class="mb-2">Código de Reserva</h3>
                    <h2 class="fw-bold"><?= htmlspecialchars($booking['codigo_reserva']) ?></h2>
                    <p class="mb-0"><i class="fas fa-info-circle me-2"></i>Guarda este código para futuras consultas</p>
                </div>

                <!-- Detalles de la Reserva -->
                <div class="booking-details">
                    <h4 class="mb-4">
                        <i class="fas fa-file-alt text-primary me-2"></i>
                        Detalles de tu Reserva
                    </h4>

                    <!-- Información del Tour -->
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-semibold">Tour:</div>
                        <div class="col-sm-8"><?= htmlspecialchars($booking['tour_nombre'] ?? 'N/A') ?></div>
                    </div>
                    
                    <?php if ($booking['fecha_salida']): ?>
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-semibold">Fecha:</div>
                        <div class="col-sm-8">
                            <i class="fas fa-calendar text-primary me-1"></i>
                            <?= date('d \d\e F, Y', strtotime($booking['fecha_salida'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-sm-4 fw-semibold">Viajeros:</div>
                        <div class="col-sm-8">
                            <i class="fas fa-users text-primary me-1"></i>
                            <?= $booking['numero_personas'] ?> persona<?= $booking['numero_personas'] > 1 ? 's' : '' ?>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-sm-4 fw-semibold">Total Pagado:</div>
                        <div class="col-sm-8">
                            <span class="fw-bold text-success fs-5">
                                $<?= number_format($booking['precio_final'], 2) ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($booking['metodo_pago'] === 'rnpl'): ?>
                    <div class="row mb-3">
                        <div class="col-sm-4 fw-semibold">Método de Pago:</div>
                        <div class="col-sm-8">
                            <span class="badge bg-info">Reserva Ahora, Paga Después</span>
                            <br><small class="text-muted">Completa el pago 48h antes del tour</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-sm-4 fw-semibold">Estado:</div>
                        <div class="col-sm-8">
                            <span class="badge bg-success">Confirmada</span>
                        </div>
                    </div>

                    <!-- Información del Viajero -->
                    <hr class="my-4">
                    <h5 class="mb-3">
                        <i class="fas fa-user text-primary me-2"></i>
                        Información del Viajero Principal
                    </h5>

                    <div class="row mb-2">
                        <div class="col-sm-4 fw-semibold">Nombre:</div>
                        <div class="col-sm-8"><?= htmlspecialchars($booking['cliente_nombre'] ?? $booking['nombre_completo'] ?? 'N/A') ?></div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-sm-4 fw-semibold">Email:</div>
                        <div class="col-sm-8"><?= htmlspecialchars($booking['cliente_email'] ?? $booking['email'] ?? 'N/A') ?></div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-sm-4 fw-semibold">Teléfono:</div>
                        <div class="col-sm-8"><?= htmlspecialchars($booking['cliente_telefono'] ?? $booking['telefono'] ?? 'N/A') ?></div>
                    </div>
                </div>

                <!-- Próximos Pasos -->
                <div class="next-steps">
                    <h5 class="mb-3">
                        <i class="fas fa-list-check text-primary me-2"></i>
                        Próximos Pasos
                    </h5>
                    
                    <ol class="mb-0">
                        <li class="mb-2">
                            <strong>Confirmación por Email:</strong> 
                            Recibirás un email de confirmación con todos los detalles en los próximos minutos.
                        </li>
                        
                        <li class="mb-2">
                            <strong>Información de Pickup:</strong> 
                            Te contactaremos 24h antes del tour con los detalles exactos de recogida.
                        </li>

                        <?php if ($booking['metodo_pago'] === 'rnpl'): ?>
                        <li class="mb-2">
                            <strong>Pago Final:</strong> 
                            Completa el pago restante máximo 48h antes de la fecha del tour.
                        </li>
                        <?php endif; ?>
                        
                        <li class="mb-2">
                            <strong>Preparativos:</strong> 
                            Revisa nuestra guía de preparación que incluiremos en el email de confirmación.
                        </li>
                    </ol>
                </div>

                <!-- Opciones de Contacto -->
                <h5 class="mt-4 mb-3">
                    <i class="fas fa-headset text-primary me-2"></i>
                    ¿Necesitas Ayuda?
                </h5>

                <div class="contact-options">
                    <a href="https://wa.me/<?= Config::SOCIAL_WHATSAPP ?? '+50212345678' ?>?text=Hola, tengo una consulta sobre mi reserva <?= htmlspecialchars($booking['codigo_reserva']) ?>" 
                       class="contact-option" target="_blank">
                        <i class="fab fa-whatsapp text-success fs-3 mb-2"></i>
                        <div class="fw-semibold">WhatsApp</div>
                        <div class="text-muted small">Respuesta inmediata</div>
                    </a>

                    <a href="tel:<?= Config::COMPANY_PHONE ?? '+502 1234-5678' ?>" class="contact-option">
                        <i class="fas fa-phone text-primary fs-3 mb-2"></i>
                        <div class="fw-semibold">Llamar</div>
                        <div class="text-muted small">Atención 24/7</div>
                    </a>

                    <a href="mailto:<?= Config::COMPANY_EMAIL ?? 'info@mayanworldtravelagency.com' ?>?subject=Consulta reserva <?= htmlspecialchars($booking['codigo_reserva']) ?>" 
                       class="contact-option">
                        <i class="fas fa-envelope text-info fs-3 mb-2"></i>
                        <div class="fw-semibold">Email</div>
                        <div class="text-muted small">Respuesta en 2h</div>
                    </a>
                </div>

                <!-- Social Proof -->
                <div class="social-proof">
                    <h4 class="mb-3">¡Te vas a divertir increíble!</h4>
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="fs-2 fw-bold">20+</div>
                            <div>Años de experiencia</div>
                        </div>
                        <div class="col-4">
                            <div class="fs-2 fw-bold">50,000+</div>
                            <div>Viajeros felices</div>
                        </div>
                        <div class="col-4">
                            <div class="fs-2 fw-bold">4.9★</div>
                            <div>Calificación promedio</div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="text-center mt-4">
                    <a href="?route=tours" class="btn btn-outline-primary btn-lg me-3">
                        <i class="fas fa-search me-2"></i>
                        Explorar Más Tours
                    </a>
                    
                    <a href="?route=booking/find" class="btn btn-primary btn-lg">
                        <i class="fas fa-search me-2"></i>
                        Consultar mi Reserva
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto-scroll suave al cargar
        window.addEventListener('load', function() {
            document.querySelector('.booking-code').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        });

        // Mostrar notificación de éxito adicional
        setTimeout(function() {
            // Simulamos una pequeña celebración
            console.log('🎉 ¡Reserva confirmada exitosamente!');
        }, 1000);
    </script>
</body>
</html>
