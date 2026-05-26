<?php 
use App\Core\Config;
include __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= Config::getBaseUrl() ?>">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Política de privacidad</li>
        </ol>
    </nav>

    <h1 class="mb-3">Política de privacidad</h1>
    <p class="text-muted">Cuidamos tu información personal. Para dudas escríbenos a <a href="mailto:<?= htmlspecialchars(Config::COMPANY_EMAIL) ?>"><?= htmlspecialchars(Config::COMPANY_EMAIL) ?></a>.</p>

    <ul class="mt-3">
        <li><a href="#datos">Datos que recopilamos</a></li>
        <li><a href="#uso">Cómo usamos tus datos</a></li>
        <li><a href="#derechos">Tus derechos</a></li>
        <li><a href="#contacto">Contacto</a></li>
    </ul>

    <h2 id="datos" class="h5 mt-4">Datos que recopilamos</h2>
    <p class="text-muted">Nombre, email, teléfono y datos necesarios para gestionar la reserva.</p>

    <h2 id="uso" class="h5 mt-4">Cómo usamos tus datos</h2>
    <p class="text-muted">Para gestionar reservas, soporte y comunicaciones relacionadas al servicio.</p>

    <h2 id="derechos" class="h5 mt-4">Tus derechos</h2>
    <p class="text-muted">Puedes solicitar acceso, corrección o eliminación de tus datos. Escríbenos para ejercer tus derechos.</p>

    <h2 id="contacto" class="h5 mt-4">Contacto</h2>
    <p class="text-muted">¿Preguntas? Visita el <a href="<?= Config::getBaseUrl() ?>?route=help">Centro de Ayuda</a> o <a href="<?= Config::getBaseUrl() ?>?route=contact">contáctanos</a>.</p>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
