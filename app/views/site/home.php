<?php
use App\Core\Helpers;
use App\Core\Config;

$title = $title ?? 'Inicio | Travel Mayan World';
$metaDescription = $metaDescription ?? 'Explora destinos, reserva experiencias y planifica tu viaje con Travel Mayan World.';
$metaImage = $metaImage ?? Helpers::asset('images/hero-travel.jpg');
$homepage_sections = $homepage_sections ?? [];

// Helper function para obtener bloques fácilmente (usado por content_blocks)
function getBlock($blocks, $section, $index, $default = '') {
    return isset($blocks[$section][$index]['contenido'])
        ? htmlspecialchars($blocks[$section][$index]['contenido'])
        : $default;
}

// Indicar que es la página de inicio para header especial
$isHomepage = true;
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Navbar flotante sobre el hero en homepage -->
<style>
/* Navbar transparente sobre el hero */
.homepage-navbar-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1050;
    background: linear-gradient(to bottom, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.3) 50%, transparent 100%);
    transition: all 0.3s ease;
}

.homepage-navbar-overlay.scrolled {
    position: fixed;
    background: rgba(13, 110, 253, 0.95);
    backdrop-filter: blur(10px);
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.homepage-navbar-overlay .navbar {
    background: transparent !important;
    position: relative;
}

.homepage-navbar-overlay .navbar-brand,
.homepage-navbar-overlay .nav-link,
.homepage-navbar-overlay .dropdown-toggle {
    color: white !important;
    text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
}

.homepage-navbar-overlay .nav-link:hover {
    color: rgba(255,255,255,0.8) !important;
}

.homepage-navbar-overlay .navbar-toggler {
    border-color: rgba(255,255,255,0.5);
}

.homepage-navbar-overlay .navbar-toggler-icon {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.85%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
}

/* Dropdown en navbar transparente (solo desktop) */
@media (min-width: 992px) {
    .homepage-navbar-overlay .dropdown-menu {
        background: rgba(0,0,0,0.85);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.1);
    }

    .homepage-navbar-overlay .dropdown-item {
        color: white !important;
    }

    .homepage-navbar-overlay .dropdown-item:hover {
        background: rgba(255,255,255,0.1);
    }
}

/* Asegurar que el body no tenga padding cuando navbar está absolute */
body.homepage-body {
    padding-top: 0 !important;
    margin: 0 !important;
}

/* Forzar hero a pantalla completa - ancho completo del viewport */
#heroSection {
    width: 100vw !important;
    max-width: 100vw !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
    position: relative;
}

/* Resetear cualquier padding/margin del HTML y body en homepage */
html, body {
    margin: 0 !important;
    padding: 0 !important;
    overflow-x: hidden;
}

/* En pantallas pequeñas, asegurar que el hero sea visible */
@media (max-width: 768px) {
    .hero-fullscreen {
        min-height: 100vh !important;
        height: 100vh !important;
    }

    .hero-content {
        padding-top: 80px;
    }

    .homepage-navbar-overlay {
        position: fixed !important;
        background: rgba(0,0,0,0.85) !important;
    }
}
</style>

<script>
// Navbar scroll effect en homepage
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.homepage-navbar-overlay');
    if (navbar) {
        document.body.classList.add('homepage-body');

        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
});
</script>

<?php
/**
 * Homepage Dinámico - Renderizado desde homepage_sections
 * Las secciones se renderizan en el orden definido en la base de datos
 * Solo se muestran las secciones con is_visible = 1
 */

if (empty($homepage_sections)) {
    // Fallback: Si no hay secciones configuradas, mostrar mensaje
    ?>
    <div class="container py-5">
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Homepage no configurado.</strong>
            Ve al <a href="<?= Config::getBaseUrl() ?>?route=admin/homepage-editor" class="alert-link">Editor de Homepage</a> para configurar las secciones.
        </div>
    </div>
    <?php
} else {
    // Renderizar secciones dinámicamente
    foreach ($homepage_sections as $section) {
        $sectionType = $section['section_type'];
        $sectionFile = __DIR__ . "/sections/{$sectionType}.php";

        // Verificar si existe el archivo de la sección
        if (file_exists($sectionFile)) {
            // Incluir la sección (tiene acceso a todas las variables del scope actual)
            include $sectionFile;
        } else {
            // Sección no implementada - mostrar solo en desarrollo
            if (Config::isDevelopment()) {
                ?>
                <div class="alert alert-warning m-3">
                    <i class="fas fa-code me-2"></i>
                    <strong>Sección no implementada:</strong> <?= htmlspecialchars($sectionType) ?>
                    <br><small>Crear archivo: app/views/site/sections/<?= htmlspecialchars($sectionType) ?>.php</small>
                </div>
                <?php
            }
        }
    }
}
?>

<!-- Estilos globales del homepage -->
<style>
/* Hero Section Base - Full Screen */
.hero-fullscreen {
    position: relative;
    overflow: hidden;
    min-height: 100vh !important;
    height: 100vh !important;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 !important;
    padding: 0 !important;
    width: 100vw;
    /* Importante: que empiece desde arriba de la página */
    top: 0;
    left: 0;
}

/* Contenido del hero debe tener margen superior para no quedar detrás del navbar */
.hero-content {
    z-index: 10;
    position: relative;
    padding-top: 70px; /* Espacio para el navbar */
    width: 100%;
}

.hero-overlay {
    z-index: 5;
    backdrop-filter: blur(1px);
}

/* Hero Image Background */
.hero-image-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    z-index: 1;
}

.hero-fullscreen.hero-image .hero-image-bg {
    background-attachment: fixed; /* Parallax effect */
}

/* Scroll Indicator */
.scroll-indicator {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    cursor: pointer;
    transition: all 0.3s ease;
}

.scroll-indicator:hover {
    transform: translateX(-50%) translateY(5px);
}

.animate-bounce {
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {
        transform: translateX(-50%) translateY(0);
    }
    40% {
        transform: translateX(-50%) translateY(-10px);
    }
    60% {
        transform: translateX(-50%) translateY(-5px);
    }
}

/* Hero Video Background */
.hero-video-bg {
    position: absolute;
    top: 50%;
    left: 50%;
    min-width: 100%;
    min-height: 100%;
    width: auto;
    height: auto;
    transform: translate(-50%, -50%);
    z-index: 1;
    object-fit: cover;
}

.hero-video-fallback {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
}

/* Hero YouTube Background */
.hero-youtube-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    overflow: hidden;
    z-index: 1;
}

.hero-youtube-bg iframe {
    position: absolute;
    top: 50%;
    left: 50%;
    width: 100vw;
    height: 56.25vw;
    min-height: 100vh;
    min-width: 177.77vh;
    transform: translate(-50%, -50%);
    pointer-events: none;
}

/* Hero Text Styles */
.hero-fullscreen h1,
.hero-fullscreen p {
    text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.7);
}

.hero-subtitle {
    max-width: 900px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
    font-weight: 400;
}

.hero-fullscreen .btn {
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
}

.hero-fullscreen .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
}

/* Animations */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fadeIn 0.8s ease-out;
}

.animate-fade-in-delay {
    animation: fadeIn 0.8s ease-out 0.3s both;
}

.animate-fade-in-delay-2 {
    animation: fadeIn 0.8s ease-out 0.6s both;
}

/* Smooth scroll */
html {
    scroll-behavior: smooth;
}

#content-start {
    scroll-margin-top: 20px;
}

/* Cards hover effect */
.hover-shadow {
    transition: box-shadow 0.3s ease;
}
.hover-shadow:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .hero-fullscreen {
        min-height: 100vh !important;
        height: 100vh !important;
    }

    .hero-fullscreen.hero-image .hero-image-bg {
        background-attachment: scroll; /* Better performance on mobile */
    }

    .hero-fullscreen h1 {
        font-size: 2.5rem !important;
    }

    .hero-fullscreen .btn {
        width: 100%;
        margin-bottom: 10px;
    }

    .hero-subtitle {
        font-size: 1.1rem !important;
    }

    .scroll-indicator {
        bottom: 20px;
    }

    /* Hide video on mobile, show poster or fallback image */
    .hero-video-bg {
        display: none;
    }

    .hero-youtube-bg {
        display: none;
    }

    .hero-video-bg + .hero-overlay::before,
    .hero-youtube-bg + .hero-overlay::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: var(--mobile-fallback-image, url('<?= Helpers::asset("images/hero-travel.jpg") ?>'));
        background-size: cover;
        background-position: center;
        z-index: -1;
    }
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
