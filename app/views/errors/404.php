<?php
use App\Core\Config;
include __DIR__ . '/../layouts/header.php'; ?>

<div class="container error-page">
    <div class="empty-state__icon mx-auto mb-3">
        <i class="fas fa-compass fa-2x"></i>
    </div>
    <div class="error-page__code">404</div>
    <h1 class="error-page__title">Página no encontrada</h1>
    <p class="error-page__desc">La ruta solicitada no existe o fue movida.</p>
    <?php if (!empty($message)): ?>
        <p class="text-muted small mb-4">Detalle: <?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <div class="error-page__actions">
        <a class="btn btn-primary" href="<?= Config::getBaseUrl() ?>">
            <i class="fas fa-home me-1"></i> Ir al inicio
        </a>
        <a class="btn btn-outline-secondary" href="<?= Config::getBaseUrl() ?>?route=tours">
            <i class="fas fa-map-marked-alt me-1"></i> Ver destinos
        </a>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
