<?php 
use App\Core\Config;
include __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="<?= Config::getBaseUrl() ?>">Inicio</a></li>
            <li class="breadcrumb-item active" aria-current="page">Términos y condiciones</li>
        </ol>
    </nav>

    <h1 class="mb-3">Términos y condiciones</h1>
    <p class="text-muted">Última actualización: <?= date('d/m/Y') ?></p>

    <p>Estos términos regulan el uso del sitio y los servicios de <?= htmlspecialchars(Config::APP_NAME) ?>. Para más información, escríbenos a <a href="mailto:<?= htmlspecialchars(Config::COMPANY_EMAIL) ?>"><?= htmlspecialchars(Config::COMPANY_EMAIL) ?></a>.</p>

    <ul class="mt-3">
        <li><a href="#reservas">Reservas</a></li>
        <li><a href="#pagos">Pagos</a></li>
        <li><a href="#cancelaciones">Cancelaciones</a></li>
        <li><a href="#privacidad">Privacidad</a></li>
    </ul>

    <h2 id="reservas" class="h5 mt-4">Reservas</h2>
    <p class="text-muted">Las reservas se confirman al completar los datos solicitados y aceptar estos términos.</p>

    <h2 id="pagos" class="h5 mt-4">Pagos</h2>
    <p class="text-muted">Aceptamos transferencia, efectivo y tarjeta. Algunos tours permiten anticipo del <?= (int)(Config::DEPOSIT_RATE*100) ?>%.</p>

    <h2 id="cancelaciones" class="h5 mt-4">Cancelaciones</h2>
    <p class="text-muted">Las políticas pueden variar por tour. Consulta la sección “Políticas de Cancelación” del tour.</p>

    <h2 id="privacidad" class="h5 mt-4">Privacidad</h2>
    <p class="text-muted">Consulta nuestra <a href="<?= Config::getBaseUrl() ?>?route=privacy">Política de Privacidad</a> para más detalles.</p>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
