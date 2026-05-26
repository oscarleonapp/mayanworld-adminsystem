<?php
use App\Core\Config;
/**
 * Componente: Preguntas Frecuentes del Tour
 * FAQ dinámico que responde las dudas más comunes antes de reservar
 *
 * @param array $product - Datos del tour/tour
 * @param array $faq_custom - FAQ personalizado del tour (opcional)
 */

// FAQs predeterminados según el tipo de tour
$defaultFaqs = [
    [
        'question' => '¿Qué incluye el precio del tour?',
        'answer' => !empty($product['incluye'])
            ? 'El precio incluye: ' . htmlspecialchars($product['incluye']) . '.'
            : 'El precio incluye transporte, guía certificado, y todo lo especificado en la descripción del tour.',
        'icon' => 'fa-dollar-sign',
        'category' => 'precio'
    ],
    [
        'question' => '¿Puedo cancelar mi reserva?',
        'answer' => !empty($product['politicas_cancelacion'])
            ? htmlspecialchars($product['politicas_cancelacion'])
            : 'Sí, ofrecemos cancelación gratuita hasta 48 horas antes del inicio del tour. Después de ese tiempo, se aplicarán cargos según nuestra política.',
        'icon' => 'fa-calendar-times',
        'category' => 'cancelacion'
    ],
    [
        'question' => '¿Es adecuado para niños?',
        'answer' => (($product['dificultad'] ?? 'facil') === 'facil')
            ? 'Sí, este tour es apto para toda la familia incluyendo niños. Recomendamos supervisión de adultos para menores de 8 años.'
            : 'Este tour tiene un nivel de dificultad ' . htmlspecialchars($product['dificultad'] ?? 'moderado') . '. Recomendamos consultar antes si viajas con niños pequeños.',
        'icon' => 'fa-child',
        'category' => 'familias'
    ],
    [
        'question' => '¿Qué pasa si llueve el día del tour?',
        'answer' => 'El tour se realiza con lluvia o sol. Proporcionamos ponchos impermeables si es necesario. En caso de condiciones climáticas extremas que pongan en riesgo la seguridad, reprogramaremos tu tour sin costo adicional.',
        'icon' => 'fa-cloud-rain',
        'category' => 'clima'
    ],
    [
        'question' => '¿Necesito estar en buena condición física?',
        'answer' => 'Nivel de exigencia física: <strong>' . ucfirst($product['dificultad'] ?? 'moderada') . '</strong>. ' .
            match($product['dificultad'] ?? 'moderado') {
                'facil' => 'Caminatas cortas y terreno mayormente plano. Accesible para la mayoría de personas.',
                'moderado' => 'Caminatas de duración media con algunas subidas. Se requiere un nivel básico de condición física.',
                'dificil' => 'Caminatas largas con terreno irregular. Se requiere buena condición física.',
                default => 'Consulta con nosotros si tienes alguna condición médica.'
            },
        'icon' => 'fa-running',
        'category' => 'fisico'
    ],
    [
        'question' => '¿Qué debo traer?',
        'answer' => 'Recomendamos: calzado cómodo, protector solar, sombrero/gorra, agua reutilizable, cámara, repelente de insectos, y dinero en efectivo para compras personales. ' .
            (($product['duracion_dias'] ?? 1) > 1 ? 'Como es un tour de varios días, incluye ropa de cambio y artículos de higiene personal.' : ''),
        'icon' => 'fa-backpack',
        'category' => 'preparacion'
    ],
    [
        'question' => '¿Hay descuentos para grupos?',
        'answer' => 'Sí, ofrecemos descuentos especiales para grupos de 6 personas o más. Contáctanos por WhatsApp o email para obtener una cotización personalizada.',
        'icon' => 'fa-users',
        'category' => 'grupos'
    ],
    [
        'question' => '¿El guía habla mi idioma?',
        'answer' => 'Nuestros guías hablan español e inglés. Si necesitas un guía en otro idioma (francés, alemán, italiano), avísanos con 48 horas de anticipación para hacer los arreglos necesarios.',
        'icon' => 'fa-language',
        'category' => 'idioma'
    ],
    [
        'question' => '¿Cuál es el punto de encuentro?',
        'answer' => !empty($product['ubicacion'])
            ? 'El punto de encuentro es: ' . htmlspecialchars($product['ubicacion']) . '. Te enviaremos la ubicación exacta y mapa por WhatsApp 24 horas antes.'
            : 'Te enviaremos el punto de encuentro exacto por email y WhatsApp 24 horas antes del tour. Ofrecemos pickup desde hoteles principales.',
        'icon' => 'fa-map-marker-alt',
        'category' => 'logistica'
    ],
    [
        'question' => '¿Incluye comida?',
        'answer' => stripos($product['incluye'] ?? '', 'comida') !== false || stripos($product['incluye'] ?? '', 'almuerzo') !== false
            ? 'Sí, el tour incluye comida como se especifica en la descripción.'
            : 'La comida no está incluida. Haremos paradas en lugares donde puedes comprar alimentos. También puedes traer snacks propios.',
        'icon' => 'fa-utensils',
        'category' => 'comida'
    ],
    [
        'question' => '¿Puedo tomar fotos?',
        'answer' => 'Por supuesto! Puedes tomar todas las fotos que quieras. En algunos sitios arqueológicos hay restricción para trípodes o equipo profesional. Tu guía te informará.',
        'icon' => 'fa-camera',
        'category' => 'fotos'
    ],
    [
        'question' => '¿Está incluido el seguro?',
        'answer' => 'Todos nuestros tours incluyen seguro básico de accidentes durante el recorrido. Te recomendamos contratar un seguro de viaje completo por tu cuenta para mayor cobertura.',
        'icon' => 'fa-shield-alt',
        'category' => 'seguro'
    ]
];

// Mezclar con FAQs personalizados si existen
$faqs = $faq_custom ?? $defaultFaqs;

// Mostrar solo las más relevantes (máx 8)
$faqs = array_slice($faqs, 0, 8);
?>

<!-- Sección: Preguntas Frecuentes -->
<div class="card shadow-sm mt-4 tour-faq-card" id="faq">
    <div class="card-header bg-info text-white">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div class="faq-header-icon me-3">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div>
                    <h4 class="mb-0">
                        Preguntas Frecuentes
                    </h4>
                    <small class="opacity-90">Resuelve tus dudas antes de reservar</small>
                </div>
            </div>
            <div class="text-end d-none d-md-block">
                <small class="opacity-75">
                    <i class="fas fa-headset me-1"></i>¿Más preguntas? <a href="https://wa.me/<?= str_replace(['+', ' ', '-'], '', Config::SOCIAL_WHATSAPP ?? '') ?>" target="_blank" class="text-white text-decoration-underline">Contáctanos</a>
                </small>
            </div>
        </div>
    </div>

    <div class="card-body p-0">
        <div class="accordion accordion-flush" id="faqAccordion">
            <?php foreach ($faqs as $index => $faq): ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="faqHeading<?= $index ?>">
                        <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?>"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#faqCollapse<?= $index ?>"
                                aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>"
                                aria-controls="faqCollapse<?= $index ?>">
                            <i class="fas <?= $faq['icon'] ?? 'fa-question' ?> faq-icon me-3"></i>
                            <span class="faq-question"><?= htmlspecialchars($faq['question']) ?></span>
                        </button>
                    </h2>
                    <div id="faqCollapse<?= $index ?>"
                         class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                         aria-labelledby="faqHeading<?= $index ?>"
                         data-bs-parent="#faqAccordion">
                        <div class="accordion-body">
                            <div class="faq-answer">
                                <?= nl2br($faq['answer']) ?>
                            </div>

                            <!-- Rating de utilidad (opcional) -->
                            <div class="faq-feedback mt-3 pt-2 border-top">
                                <small class="text-muted">
                                    ¿Te fue útil esta respuesta?
                                    <button class="btn btn-sm btn-outline-success ms-2" onclick="rateAnswer(<?= $index ?>, 1)">
                                        <i class="fas fa-thumbs-up"></i> Sí
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="rateAnswer(<?= $index ?>, 0)">
                                        <i class="fas fa-thumbs-down"></i> No
                                    </button>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- CTA al final del FAQ -->
    <div class="card-footer bg-light">
        <div class="row align-items-center">
            <div class="col-md-8">
                <p class="mb-0">
                    <i class="fas fa-info-circle text-info me-2"></i>
                    <strong>¿No encontraste tu respuesta?</strong>
                    Nuestro equipo está listo para ayudarte.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <a href="https://wa.me/<?= str_replace(['+', ' ', '-'], '', Config::SOCIAL_WHATSAPP ?? '') ?>?text=Tengo%20una%20pregunta%20sobre%20<?= urlencode($product['nombre'] ?? 'el tour') ?>"
                   target="_blank"
                   class="btn btn-success btn-sm">
                    <i class="fab fa-whatsapp me-1"></i>
                    Preguntar por WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Estilos del componente -->
<style>
.tour-faq-card {
    border: none;
}

.faq-header-icon {
    background: rgba(255, 255, 255, 0.2);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.accordion-item {
    border: none !important;
    border-bottom: 1px solid #e9ecef !important;
}

.accordion-item:last-child {
    border-bottom: none !important;
}

.accordion-button {
    padding: 1.25rem 1.5rem;
    background-color: #fff;
    font-weight: 500;
    transition: all 0.3s ease;
}

.accordion-button:not(.collapsed) {
    background-color: #f8f9fa;
    color: #0d6efd;
    box-shadow: none;
}

.accordion-button:focus {
    box-shadow: none;
    border-color: transparent;
}

.accordion-button::after {
    flex-shrink: 0;
    width: 1.5rem;
    height: 1.5rem;
    margin-left: auto;
    background-size: 1.5rem;
}

.faq-icon {
    color: #0d6efd;
    font-size: 1.1rem;
    min-width: 25px;
}

.faq-question {
    font-size: 1rem;
    line-height: 1.5;
}

.accordion-body {
    padding: 1.5rem;
    background-color: #f8f9fa;
}

.faq-answer {
    color: #495057;
    line-height: 1.7;
    font-size: 0.95rem;
}

.faq-answer strong {
    color: #212529;
}

.faq-feedback button {
    font-size: 0.8rem;
    padding: 0.25rem 0.75rem;
    transition: all 0.2s ease;
}

.faq-feedback button:hover {
    transform: scale(1.05);
}

/* Animación suave del acordeón */
.accordion-collapse {
    transition: height 0.35s ease;
}

/* Responsive */
@media (max-width: 767.98px) {
    .accordion-button {
        padding: 1rem;
        font-size: 0.95rem;
    }

    .faq-icon {
        font-size: 1rem;
        min-width: 20px;
        margin-right: 0.75rem !important;
    }

    .accordion-body {
        padding: 1rem;
    }
}
</style>

<!-- JavaScript para rating de respuestas -->
<script>
function rateAnswer(index, helpful) {
    // Enviar feedback al servidor (opcional)
    const data = {
        tour_id: <?= (int)($product['id'] ?? 0) ?>,
        faq_index: index,
        helpful: helpful
    };

    // Mostrar feedback visual
    const button = event.target.closest('button');
    const feedbackDiv = button.closest('.faq-feedback');

    feedbackDiv.innerHTML = '<small class="text-success"><i class="fas fa-check-circle me-1"></i>¡Gracias por tu feedback!</small>';

    // Opcional: Enviar al servidor para analytics
    fetch('/?route=api/faq-feedback', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    }).catch(err => console.log('Feedback registrado localmente'));
}

// Tracking de FAQs abiertos (para analytics)
document.addEventListener('DOMContentLoaded', function() {
    const accordionButtons = document.querySelectorAll('.accordion-button');

    accordionButtons.forEach((button, index) => {
        button.addEventListener('click', function() {
            // Registrar qué FAQs son más consultados
            if (typeof gtag !== 'undefined') {
                gtag('event', 'faq_click', {
                    'event_category': 'engagement',
                    'event_label': this.textContent.trim(),
                    'value': index
                });
            }
        });
    });
});
</script>
