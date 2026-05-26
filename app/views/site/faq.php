<?php 
use App\Core\Config;
include __DIR__ . '/../layouts/header.php'; ?>

<style>
.faq-hero {
    background: linear-gradient(135deg, var(--primary-color, #0d6efd) 0%, var(--primary-dark, #0949a8) 100%);
    color: white;
    padding: 4rem 0 3rem;
    margin-bottom: 3rem;
}

.faq-search-box {
    max-width: 600px;
    margin: 2rem auto 0;
}

.faq-category-card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    height: 100%;
}

.faq-category-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.faq-category-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.accordion-button:not(.collapsed) {
    background-color: rgba(13, 110, 253, 0.08);
    color: var(--primary-dark, #0949a8);
}

.accordion-button:focus {
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}

.faq-suggestion-list {
    position: absolute;
    z-index: 1000;
    width: 100%;
    max-height: 300px;
    overflow-y: auto;
    margin-top: 0.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.contact-cta {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 2.5rem;
    margin-top: 3rem;
}
</style>

<div class="faq-hero text-center">
    <div class="container">
        <h1 class="display-4 fw-bold mb-3">¿En qué podemos ayudarte?</h1>
        <p class="lead mb-0">Encuentra respuestas rápidas a tus preguntas sobre viajes, reservas y más</p>

        <div class="faq-search-box">
            <div class="input-group input-group-lg shadow-sm">
                <span class="input-group-text bg-white border-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input
                    type="text"
                    class="form-control border-0"
                    id="faqSearch"
                    placeholder="Buscar preguntas..."
                    aria-label="Buscar en preguntas frecuentes"
                    autocomplete="off"
                >
            </div>
            <div id="faqSuggestions" class="faq-suggestion-list list-group" style="display:none;"></div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <!-- Categorías de ayuda -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card faq-category-card">
                <div class="card-body p-4">
                    <div class="faq-category-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h5 class="card-title fw-bold">Reservas y Pagos</h5>
                    <p class="card-text text-muted">Todo sobre cómo reservar, pagar y modificar tus viajes</p>
                    <a href="#reservas" class="btn btn-sm btn-outline-primary">Ver preguntas</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card faq-category-card">
                <div class="card-body p-4">
                    <div class="faq-category-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h5 class="card-title fw-bold">Destinos y Tours</h5>
                    <p class="card-text text-muted">Información sobre nuestros destinos y actividades</p>
                    <a href="#destinos" class="btn btn-sm btn-outline-success">Ver preguntas</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card faq-category-card">
                <div class="card-body p-4">
                    <div class="faq-category-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h5 class="card-title fw-bold">Políticas y Seguridad</h5>
                    <p class="card-text text-muted">Cancelaciones, reembolsos y protección de datos</p>
                    <a href="#politicas" class="btn btn-sm btn-outline-warning">Ver preguntas</a>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQs Dinámicas desde Base de Datos -->
    <?php
    // Debug temporal
    echo "<!-- DEBUG: Variable faqs " . (isset($faqs) ? 'EXISTE' : 'NO EXISTE') . " -->";
    echo "<!-- DEBUG: Total FAQs: " . (isset($faqs) ? count($faqs) : 0) . " -->";
    ?>
    <?php if (isset($faqs) && !empty($faqs)): ?>
    <div class="mb-5">
        <h2 class="h3 fw-bold mb-4">
            <i class="fas fa-question-circle text-primary me-2"></i>
            Preguntas Frecuentes Actualizadas
        </h2>

        <?php
        // Agrupar FAQs por categoría
        $faqsByCategory = [];
        foreach ($faqs as $faq) {
            $categoria = $faq['categoria'] ?? 'General';
            if (!isset($faqsByCategory[$categoria])) {
                $faqsByCategory[$categoria] = [];
            }
            $faqsByCategory[$categoria][] = $faq;
        }

        // Mostrar FAQs agrupadas por categoría
        foreach ($faqsByCategory as $categoria => $categoriaFaqs):
        ?>
        <div class="mb-4">
            <h3 class="h5 fw-bold text-secondary mb-3"><?= htmlspecialchars(ucfirst($categoria)) ?></h3>
            <div class="accordion" id="faqAccordion<?= htmlspecialchars(str_replace(' ', '', $categoria)) ?>">
                <?php foreach ($categoriaFaqs as $index => $faq): ?>
                <div class="accordion-item mb-2">
                    <h4 class="accordion-header">
                        <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#faqDb<?= $faq['id'] ?>">
                            <?= htmlspecialchars($faq['pregunta']) ?>
                        </button>
                    </h4>
                    <div id="faqDb<?= $faq['id'] ?>"
                         class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>"
                         data-bs-parent="#faqAccordion<?= htmlspecialchars(str_replace(' ', '', $categoria)) ?>">
                        <div class="accordion-body">
                            <?= $faq['respuesta'] ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- FAQs: Reservas y Pagos -->
    <div id="reservas" class="mb-5">
        <h2 class="h3 fw-bold mb-4">
            <i class="fas fa-calendar-check text-primary me-2"></i>
            Reservas y Pagos
        </h2>
        <div class="accordion" id="faqReservas">
            <div class="accordion-item mb-2" data-keywords="reservar tour booking comprar">
                <h3 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                        ¿Cómo puedo reservar un tour?
                    </button>
                </h3>
                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqReservas">
                    <div class="accordion-body">
                        <p>Reservar es muy sencillo:</p>
                        <ol>
                            <li>Navega por nuestro catálogo de destinos y selecciona el tour que te interese</li>
                            <li>Haz clic en "Reservar ahora" y completa el formulario con tu información</li>
                            <li>Selecciona tu fecha preferida y número de personas</li>
                            <li>Elige tu método de pago y confirma la reserva</li>
                            <li>Recibirás un correo de confirmación con todos los detalles</li>
                        </ol>
                        <p class="mb-0">También puedes contactarnos por <a href="<?= Config::getBaseUrl() ?>?route=chat">WhatsApp</a> para reservar directamente con un agente.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2" data-keywords="pago tarjeta stripe visa mastercard">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                        ¿Qué métodos de pago aceptan?
                    </button>
                </h3>
                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqReservas">
                    <div class="accordion-body">
                        <p>Aceptamos múltiples formas de pago para tu comodidad:</p>
                        <ul>
                            <li><strong>Tarjetas de crédito/débito:</strong> Visa, Mastercard, American Express (procesado de forma segura por Stripe)</li>
                            <li><strong>Transferencia bancaria:</strong> Te proporcionamos los datos bancarios en el resumen de reserva</li>
                            <li><strong>Efectivo:</strong> Puedes pagar en nuestras oficinas en Flores, Petén</li>
                            <li><strong>Pago parcial:</strong> Anticipo del 30% y el resto antes del tour</li>
                        </ul>
                        <p class="mb-0">Todos los pagos con tarjeta están protegidos con tecnología 3D Secure para mayor seguridad.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2" data-keywords="anticipo deposito pago parcial">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                        ¿Puedo pagar solo un anticipo?
                    </button>
                </h3>
                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqReservas">
                    <div class="accordion-body">
                        <p><strong>Sí, ofrecemos la opción de pago parcial.</strong></p>
                        <p>Puedes reservar tu tour pagando un anticipo del <strong>30%</strong> del costo total. El saldo restante deberá pagarse al menos 48 horas antes de la fecha del tour.</p>
                        <p class="mb-0">Esta opción es ideal para asegurar tu lugar sin pagar el total de inmediato. Te enviaremos recordatorios automáticos sobre el pago pendiente.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2" data-keywords="confirmacion comprobante recibo voucher">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                        ¿Recibiré una confirmación de mi reserva?
                    </button>
                </h3>
                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqReservas">
                    <div class="accordion-body">
                        <p><strong>Absolutamente.</strong> Tras completar tu reserva recibirás:</p>
                        <ul>
                            <li>Un correo electrónico de confirmación con tu número de reserva</li>
                            <li>Los detalles completos del tour (fecha, hora de salida, punto de encuentro)</li>
                            <li>Recibo de pago y factura (si la solicitaste)</li>
                            <li>Información de contacto en caso de consultas</li>
                        </ul>
                        <p class="mb-0">Si no recibes el correo en 10 minutos, revisa tu carpeta de spam o contáctanos.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2" data-keywords="cambiar fecha modificar reprogramar">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                        ¿Puedo cambiar la fecha de mi reserva?
                    </button>
                </h3>
                <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqReservas">
                    <div class="accordion-body">
                        <p><strong>Sí, puedes modificar tu reserva con ciertas condiciones:</strong></p>
                        <ul>
                            <li>Cambios con <strong>más de 7 días de anticipación:</strong> Sin cargo adicional</li>
                            <li>Cambios con <strong>3-7 días de anticipación:</strong> Cargo del 10% del total</li>
                            <li>Cambios con <strong>menos de 3 días:</strong> Sujeto a disponibilidad y cargo del 20%</li>
                        </ul>
                        <p class="mb-0">Para solicitar un cambio, contacta a nuestro equipo por WhatsApp o correo electrónico con tu número de reserva.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQs: Destinos y Tours -->
    <div id="destinos" class="mb-5">
        <h2 class="h3 fw-bold mb-4">
            <i class="fas fa-map-marked-alt text-success me-2"></i>
            Destinos y Tours
        </h2>
        <div class="accordion" id="faqDestinos">
            <div class="accordion-item mb-2" data-keywords="destinos lugares visitar guatemal belize tikal">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq6">
                        ¿Qué destinos ofrecen?
                    </button>
                </h3>
                <div id="faq6" class="accordion-collapse collapse" data-bs-parent="#faqDestinos">
                    <div class="accordion-body">
                        <p>Nos especializamos en el <strong>Mundo Maya</strong>, cubriendo:</p>
                        <ul>
                            <li><strong>Guatemala:</strong> Tikal, Yaxhá, Semuc Champey, Flores, El Mirador</li>
                            <li><strong>Belice:</strong> Cayes, barreras de coral, sitios arqueológicos</li>
                            <li><strong>México:</strong> Palenque, Calakmul, zonas cercanas al Mundo Maya</li>
                        </ul>
                        <p class="mb-0">Explora nuestro <a href="<?= Config::getBaseUrl() ?>?route=tours">catálogo completo</a> para ver todos los tours disponibles.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2" data-keywords="incluye tour incluido transporte comida guia">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq7">
                        ¿Qué incluyen los tours?
                    </button>
                </h3>
                <div id="faq7" class="accordion-collapse collapse" data-bs-parent="#faqDestinos">
                    <div class="accordion-body">
                        <p>Cada tour es diferente, pero generalmente incluyen:</p>
                        <ul>
                            <li>Transporte ida y vuelta desde Flores o punto de encuentro designado</li>
                            <li>Guía turístico certificado (español/inglés)</li>
                            <li>Entradas a sitios arqueológicos o naturales</li>
                            <li>Algunas comidas (especificadas en cada tour)</li>
                            <li>Seguro de viajero básico</li>
                        </ul>
                        <p class="mb-0"><strong>Importante:</strong> Revisa la sección "Qué incluye" en cada página del tour para detalles específicos.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2" data-keywords="duracion tiempo cuanto dura">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq8">
                        ¿Cuánto duran los tours?
                    </button>
                </h3>
                <div id="faq8" class="accordion-collapse collapse" data-bs-parent="#faqDestinos">
                    <div class="accordion-body">
                        <p>La duración varía según el destino:</p>
                        <ul>
                            <li><strong>Tours de medio día:</strong> 4-5 horas (ej. Yaxhá atardecer)</li>
                            <li><strong>Tours de día completo:</strong> 8-10 horas (ej. Tikal)</li>
                            <li><strong>Tours de múltiples días:</strong> 2-5 días (ej. Semuc Champey + Tikal)</li>
                        </ul>
                        <p class="mb-0">Cada tour indica claramente su duración en la descripción del tour.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2" data-keywords="grupo privado personas minimo">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq9">
                        ¿Ofrecen tours privados?
                    </button>
                </h3>
                <div id="faq9" class="accordion-collapse collapse" data-bs-parent="#faqDestinos">
                    <div class="accordion-body">
                        <p><strong>Sí, ofrecemos tours privados y grupales.</strong></p>
                        <ul>
                            <li><strong>Tours compartidos:</strong> Grupos de hasta 12-15 personas con mejor precio</li>
                            <li><strong>Tours privados:</strong> Solo tu grupo con guía exclusivo (mínimo 2 personas)</li>
                        </ul>
                        <p class="mb-0">Para cotizar un tour privado, <a href="<?= Config::getBaseUrl() ?>?route=contact">contáctanos</a> con tus fechas y número de personas.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2" data-keywords="llevar que ropa equipaje recomendaciones">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq10">
                        ¿Qué debo llevar al tour?
                    </button>
                </h3>
                <div id="faq10" class="accordion-collapse collapse" data-bs-parent="#faqDestinos">
                    <div class="accordion-body">
                        <p><strong>Recomendaciones generales:</strong></p>
                        <ul>
                            <li>Ropa cómoda y ligera (clima tropical)</li>
                            <li>Zapatos cómodos para caminar (no sandalias)</li>
                            <li>Protector solar y repelente de insectos</li>
                            <li>Gorra o sombrero</li>
                            <li>Agua reutilizable</li>
                            <li>Cámara fotográfica</li>
                            <li>Efectivo para propinas y souvenirs</li>
                        </ul>
                        <p class="mb-0">Para tours específicos como Semuc Champey, enviaremos una lista detallada al confirmar tu reserva.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FAQs: Políticas y Seguridad -->
    <div id="politicas" class="mb-5">
        <h2 class="h3 fw-bold mb-4">
            <i class="fas fa-shield-alt text-warning me-2"></i>
            Políticas y Seguridad
        </h2>
        <div class="accordion" id="faqPoliticas">
            <div class="accordion-item mb-2" data-keywords="cancelar cancelacion reembolso devolucion">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq11">
                        ¿Cuál es la política de cancelación?
                    </button>
                </h3>
                <div id="faq11" class="accordion-collapse collapse" data-bs-parent="#faqPoliticas">
                    <div class="accordion-body">
                        <p><strong>Nuestra política de cancelación estándar es:</strong></p>
                        <ul>
                            <li><strong>Más de 15 días antes:</strong> Reembolso del 100% (menos gastos administrativos del 5%)</li>
                            <li><strong>7-14 días antes:</strong> Reembolso del 50%</li>
                            <li><strong>Menos de 7 días:</strong> Sin reembolso</li>
                        </ul>
                        <p><strong>Excepciones:</strong> Algunos tours tienen políticas especiales. Siempre revisa las "Condiciones de cancelación" en la página del tour.</p>
                        <p class="mb-0"><em>Nota: En caso de emergencias médicas comprobables, evaluamos cada caso individualmente.</em></p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2" data-keywords="seguro datos proteccion privacidad">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq12">
                        ¿Cómo protegen mis datos personales?
                    </button>
                </h3>
                <div id="faq12" class="accordion-collapse collapse" data-bs-parent="#faqPoliticas">
                    <div class="accordion-body">
                        <p><strong>La seguridad de tus datos es nuestra prioridad.</strong></p>
                        <ul>
                            <li>Usamos encriptación SSL en todo el sitio web</li>
                            <li>Los datos de pago son procesados por <strong>Stripe</strong> (certificado PCI DSS Nivel 1)</li>
                            <li>No almacenamos información completa de tarjetas de crédito</li>
                            <li>Cumplimos con las regulaciones de protección de datos</li>
                            <li>Nunca compartimos tu información con terceros sin tu consentimiento</li>
                        </ul>
                        <p class="mb-0">Lee nuestra <a href="<?= Config::getBaseUrl() ?>?route=privacy">Política de Privacidad</a> completa para más detalles.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2" data-keywords="seguro viaje accidente cobertura">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq13">
                        ¿Los tours incluyen seguro de viaje?
                    </button>
                </h3>
                <div id="faq13" class="accordion-collapse collapse" data-bs-parent="#faqPoliticas">
                    <div class="accordion-body">
                        <p><strong>Sí, todos nuestros tours incluyen un seguro básico que cubre:</strong></p>
                        <ul>
                            <li>Accidentes durante el tour</li>
                            <li>Asistencia médica de emergencia</li>
                            <li>Responsabilidad civil</li>
                        </ul>
                        <p><strong>No cubre:</strong> Condiciones médicas preexistentes, cancelaciones por decisión personal, objetos personales perdidos.</p>
                        <p class="mb-0">Para mayor tranquilidad, recomendamos contratar un seguro de viaje completo por tu cuenta.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2" data-keywords="clima lluvia mal tiempo cancelacion">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq14">
                        ¿Qué pasa si hay mal clima?
                    </button>
                </h3>
                <div id="faq14" class="accordion-collapse collapse" data-bs-parent="#faqPoliticas">
                    <div class="accordion-body">
                        <p><strong>Nuestra prioridad es tu seguridad.</strong></p>
                        <ul>
                            <li><strong>Lluvia ligera:</strong> Los tours continúan normalmente (lleva impermeable)</li>
                            <li><strong>Condiciones peligrosas:</strong> Si hay riesgo para la seguridad, cancelamos o reprogramamos</li>
                            <li><strong>Cancelación por clima:</strong> Ofrecemos reembolso completo o cambio de fecha sin cargo</li>
                        </ul>
                        <p class="mb-0">Te contactaremos con anticipación si prevemos problemas climáticos en tu fecha.</p>
                    </div>
                </div>
            </div>

            <div class="accordion-item mb-2" data-keywords="covid coronavirus salud protocolo">
                <h3 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq15">
                        ¿Qué medidas sanitarias tienen?
                    </button>
                </h3>
                <div id="faq15" class="accordion-collapse collapse" data-bs-parent="#faqPoliticas">
                    <div class="accordion-body">
                        <p><strong>Mantenemos altos estándares de higiene y seguridad:</strong></p>
                        <ul>
                            <li>Vehículos desinfectados antes y después de cada tour</li>
                            <li>Grupos reducidos para mantener distancia cuando es necesario</li>
                            <li>Guías capacitados en primeros auxilios</li>
                            <li>Gel antibacterial disponible durante el recorrido</li>
                            <li>Seguimiento de protocolos de salud locales</li>
                        </ul>
                        <p class="mb-0">Si tienes condiciones de salud especiales, infórmanos al reservar para brindarte mejor atención.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact CTA -->
    <div class="contact-cta text-center">
        <h3 class="fw-bold mb-3">¿No encuentras lo que buscas?</h3>
        <p class="text-muted mb-4">Nuestro equipo está listo para ayudarte con cualquier consulta</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?= Config::getBaseUrl() ?>?route=contact" class="btn btn-primary btn-lg">
                <i class="fas fa-envelope me-2"></i>Enviar mensaje
            </a>
            <a href="<?= Config::getBaseUrl() ?>?route=chat" class="btn btn-success btn-lg">
                <i class="fab fa-whatsapp me-2"></i>Chat WhatsApp
            </a>
            <a href="tel:<?= preg_replace('/\D+/', '', Config::COMPANY_PHONE ?? '+50278675095') ?>" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-phone me-2"></i>Llamar ahora
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('faqSearch');
    const suggestionsBox = document.getElementById('faqSuggestions');
    const allAccordionItems = document.querySelectorAll('.accordion-item');
    let debounceTimer;

    // Search functionality
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            const query = this.value.toLowerCase().trim();
            filterFAQs(query);
            showSuggestions(query);
        }, 300);
    });

    function filterFAQs(query) {
        if (!query) {
            // Show all items if no search query
            allAccordionItems.forEach(item => {
                item.style.display = '';
            });
            return;
        }

        allAccordionItems.forEach(item => {
            const text = item.textContent.toLowerCase();
            const keywords = item.dataset.keywords || '';

            if (text.includes(query) || keywords.includes(query)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function showSuggestions(query) {
        suggestionsBox.innerHTML = '';

        if (!query) {
            suggestionsBox.style.display = 'none';
            return;
        }

        const matches = [];
        allAccordionItems.forEach(item => {
            const button = item.querySelector('.accordion-button');
            const text = button.textContent.trim().toLowerCase();
            const keywords = item.dataset.keywords || '';

            if ((text.includes(query) || keywords.includes(query)) && matches.length < 5) {
                matches.push({
                    text: button.textContent.trim(),
                    element: item
                });
            }
        });

        if (matches.length === 0) {
            suggestionsBox.style.display = 'none';
            return;
        }

        matches.forEach(match => {
            const suggestion = document.createElement('a');
            suggestion.href = '#';
            suggestion.className = 'list-group-item list-group-item-action';
            suggestion.innerHTML = `<i class="fas fa-search text-muted me-2"></i>${match.text}`;

            suggestion.addEventListener('click', function(e) {
                e.preventDefault();
                const collapse = match.element.querySelector('.accordion-collapse');
                const bsCollapse = new bootstrap.Collapse(collapse, { toggle: false });
                bsCollapse.show();

                setTimeout(() => {
                    match.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);

                suggestionsBox.style.display = 'none';
                searchInput.value = '';
                filterFAQs('');
            });

            suggestionsBox.appendChild(suggestion);
        });

        suggestionsBox.style.display = 'block';
    }

    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
            suggestionsBox.style.display = 'none';
        }
    });

    // Smooth scroll for category links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = this.getAttribute('href');
            if (target !== '#') {
                e.preventDefault();
                const element = document.querySelector(target);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }
        });
    });
});
</script>
