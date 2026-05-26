<?php

use App\Core\Config;
// Vista genérica para mostrar páginas estáticas dinámicas
include __DIR__ . '/../layouts/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <!-- Contenido de la página estática -->
            <div class="static-page-content">
                <?= $page['content'] ?>
            </div>
        </div>
    </div>

    <!-- Botón de regreso -->
    <div class="row mt-5">
        <div class="col-12">
            <a href="<?= Config::getBaseUrl() ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i>Volver al Inicio
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<!-- Estilos adicionales para páginas estáticas -->
<style>
.static-page-content {
    max-width: 900px;
    margin: 0 auto;
    font-size: 1.05rem;
    line-height: 1.7;
}

.static-page-content h1 {
    color: #333;
    margin-bottom: 1.5rem;
    border-bottom: 3px solid var(--bs-primary, #007bff);
    padding-bottom: 0.5rem;
}

.static-page-content h2 {
    color: #444;
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-size: 1.75rem;
}

.static-page-content h3 {
    color: #555;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    font-size: 1.4rem;
}

.static-page-content p {
    margin-bottom: 1.2rem;
}

.static-page-content ul,
.static-page-content ol {
    margin-bottom: 1.5rem;
    padding-left: 2rem;
}

.static-page-content li {
    margin-bottom: 0.5rem;
}

.static-page-content a {
    color: var(--bs-primary, #007bff);
    text-decoration: underline;
}

.static-page-content a:hover {
    text-decoration: none;
}

.static-page-content .text-muted {
    color: #6c757d !important;
}

.static-page-content .lead {
    font-size: 1.25rem;
    font-weight: 300;
}

.static-page-content .card {
    margin-bottom: 1.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .static-page-content {
        font-size: 1rem;
    }

    .static-page-content h1 {
        font-size: 1.75rem;
    }

    .static-page-content h2 {
        font-size: 1.5rem;
    }

    .static-page-content h3 {
        font-size: 1.25rem;
    }
}

/* Print styles */
@media print {
    .static-page-content .btn,
    .navbar,
    .footer,
    .whatsapp-float {
        display: none !important;
    }

    .static-page-content {
        max-width: 100%;
        font-size: 12pt;
    }
}
</style>
