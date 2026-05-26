<?php
/**
 * Newsletter Section
 * Formulario de suscripción al newsletter
 */

use App\Core\Config;
use App\Core\Helpers;

$config = json_decode($section['section_config'] ?? '{}', true);
$sectionTitle = $config['title'] ?? '¡Mantente Informado!';
$sectionSubtitle = $config['subtitle'] ?? 'Suscríbete a nuestro newsletter y recibe ofertas exclusivas, consejos de viaje y las últimas novedades.';
$backgroundColor = $config['background_color'] ?? '#0d6efd';
$textColor = $config['text_color'] ?? '#ffffff';
$buttonText = $config['button_text'] ?? 'Suscribirme';
?>

<!-- Newsletter Section -->
<section class="newsletter-section py-5" style="background: <?= htmlspecialchars($backgroundColor) ?>; color: <?= htmlspecialchars($textColor) ?>;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="newsletter-content">
                    <h2 class="h1 fw-bold mb-3"><?= htmlspecialchars($sectionTitle) ?></h2>
                    <p class="lead mb-0"><?= htmlspecialchars($sectionSubtitle) ?></p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="newsletter-form">
                    <form id="newsletter-form" class="row g-3" onsubmit="return handleNewsletterSubmit(event)">
                        <input type="hidden" name="csrf_token" value="<?= Helpers::generateCsrfToken() ?>">

                        <div class="col-md-8">
                            <input type="email"
                                   name="email"
                                   class="form-control form-control-lg"
                                   placeholder="Tu correo electrónico"
                                   required
                                   id="newsletter-email">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-light btn-lg w-100" id="newsletter-submit">
                                <?= htmlspecialchars($buttonText) ?>
                            </button>
                        </div>
                    </form>

                    <!-- Success/Error Messages -->
                    <div id="newsletter-message" class="mt-3" style="display: none;"></div>

                    <!-- Privacy Notice -->
                    <p class="small mt-3 mb-0 opacity-75">
                        <i class="fas fa-lock me-1"></i>
                        Respetamos tu privacidad. Puedes darte de baja en cualquier momento.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.newsletter-section {
    position: relative;
    overflow: hidden;
}

.newsletter-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 400px;
    height: 400px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.newsletter-section::after {
    content: '';
    position: absolute;
    bottom: -30%;
    left: -5%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.newsletter-content,
.newsletter-form {
    position: relative;
    z-index: 1;
}

.newsletter-form .form-control {
    border: 2px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.15);
    color: white;
    backdrop-filter: blur(10px);
}

.newsletter-form .form-control::placeholder {
    color: rgba(255,255,255,0.7);
}

.newsletter-form .form-control:focus {
    border-color: rgba(255,255,255,0.6);
    background: rgba(255,255,255,0.25);
    box-shadow: 0 0 0 0.25rem rgba(255,255,255,0.1);
    color: white;
}

.newsletter-form .btn-light {
    font-weight: 600;
    border: none;
}

.newsletter-form .btn-light:hover {
    background: #f8f9fa;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
    .newsletter-section h2 {
        font-size: 1.8rem;
    }

    .newsletter-form .col-md-8,
    .newsletter-form .col-md-4 {
        width: 100%;
    }
}
</style>

<script>
function handleNewsletterSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const submitBtn = document.getElementById('newsletter-submit');
    const messageDiv = document.getElementById('newsletter-message');
    const emailInput = document.getElementById('newsletter-email');

    // Deshabilitar botón
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    // Enviar formulario
    const formData = new FormData(form);

    fetch('<?= Config::getBaseUrl() ?>?route=newsletter/subscribe', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        messageDiv.style.display = 'block';

        if (data.success) {
            messageDiv.className = 'alert alert-success mt-3';
            messageDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + (data.message || '¡Gracias por suscribirte!');
            form.reset();
        } else {
            messageDiv.className = 'alert alert-warning mt-3';
            messageDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + (data.message || 'Hubo un error. Por favor intenta de nuevo.');
        }
    })
    .catch(error => {
        messageDiv.style.display = 'block';
        messageDiv.className = 'alert alert-danger mt-3';
        messageDiv.innerHTML = '<i class="fas fa-times-circle me-2"></i>Error al procesar la solicitud. Por favor intenta más tarde.';
    })
    .finally(() => {
        // Rehabilitar botón
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<?= htmlspecialchars($buttonText) ?>';

        // Ocultar mensaje después de 5 segundos
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    });

    return false;
}
</script>
