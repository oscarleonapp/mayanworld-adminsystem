<?php
/**
 * Hero Section
 * Sección principal de bienvenida con imagen/video de fondo
 */

use App\Core\Config;
use App\Core\Helpers;
use App\Core\Database;

// Obtener configuración desde la sección
$config = json_decode($section['section_config'] ?? '{}', true);

// Obtener configuración global del hero desde configuraciones
$db = Database::getInstance();
$heroImage = $db->fetchOne("SELECT valor FROM configuraciones WHERE clave = 'hero_image'")['valor'] ?? '';
$heroType = $db->fetchOne("SELECT valor FROM configuraciones WHERE clave = 'hero_type'")['valor'] ?? 'image';
$heroAutoplay = $db->fetchOne("SELECT valor FROM configuraciones WHERE clave = 'hero_video_autoplay'")['valor'] ?? '1';
$heroLoop = $db->fetchOne("SELECT valor FROM configuraciones WHERE clave = 'hero_video_loop'")['valor'] ?? '1';

// Determinar si es video o imagen
$backgroundType = $heroType; // 'video' o 'image'
$backgroundVideo = '';
$backgroundImage = '';

if ($backgroundType === 'video') {
    // El valor ya incluye la carpeta (videos/), solo necesitamos agregar assets/
    $backgroundVideo = !empty($heroImage) ? Helpers::asset($heroImage) : '';
} else {
    // Es imagen
    $backgroundImage = !empty($heroImage) ? Helpers::asset($heroImage) : Helpers::asset('images/hero-default.jpg');
}

// Otras configuraciones
$title = $config['title'] ?? 'Descubre el Mundo Maya';
$subtitle = $config['subtitle'] ?? 'Explora las maravillas de Guatemala, Belice y México';
$ctaText = $config['cta_text'] ?? 'Explorar Destinos';

// IMPORTANTE: Forzar que la URL siempre incluya el BASE_URL completo
// Si viene configurado desde BD, asegurarse que sea una URL válida
$ctaLinkFromDb = $config['cta_link'] ?? '';
if (!empty($ctaLinkFromDb)) {
    // Si la URL de BD no es absoluta (no empieza con http), agregarle BASE_URL
    if (strpos($ctaLinkFromDb, 'http') !== 0) {
        $ctaLink = Config::getBaseUrl() . ltrim($ctaLinkFromDb, '/');
    } else {
        $ctaLink = $ctaLinkFromDb;
    }
} else {
    // Valor por defecto
    $ctaLink = Config::getBaseUrl() . '?route=tours';
}

$overlayOpacity = $config['overlay_opacity'] ?? '0.5';
?>

<!-- Hero Section -->
<section class="hero-section position-relative" style="min-height: 100vh; height: 100vh;">
    <!-- Background wrapper (overflow:hidden here, no en la section) -->
    <div class="hero-bg-wrap">
        <?php if ($backgroundType === 'video' && !empty($backgroundVideo)): ?>
            <video <?= $heroAutoplay ? 'autoplay' : '' ?> muted <?= $heroLoop ? 'loop' : '' ?> playsinline class="hero-background">
                <source src="<?= htmlspecialchars($backgroundVideo) ?>" type="video/mp4">
            </video>
        <?php else: ?>
            <div class="hero-background" style="background-image: url('<?= htmlspecialchars($backgroundImage) ?>');"></div>
        <?php endif; ?>
        <!-- Overlay -->
        <div class="hero-overlay" style="opacity: <?= htmlspecialchars($overlayOpacity) ?>;"></div>
    </div>

    <!-- Content -->
    <div class="container position-relative h-100 d-flex align-items-center justify-content-center">
        <div class="row w-100 justify-content-center">
            <div class="col-12 col-lg-10 text-white text-center">
                <h1 class="display-3 fw-bold mb-3 animate-fade-in">
                    <?= htmlspecialchars($title) ?>
                </h1>
                <p class="lead mb-4 animate-fade-in-delay-1">
                    <?= htmlspecialchars($subtitle) ?>
                </p>

                <!-- Search Widget -->
                <div class="hero-search-widget animate-fade-in-delay-2">
                    <form method="get" action="<?= Config::getBaseUrl() ?>" class="hero-search-form">
                        <input type="hidden" name="route" value="tours">
                        <div class="hero-search-inner">
                            <div class="hero-search-field hero-search-text">
                                <label><i class="fas fa-search me-1"></i> Destino</label>
                                <input type="text" name="search" placeholder="¿Qué destino buscas?" autocomplete="off">
                            </div>
                            <?php if (!empty($categories)): ?>
                            <div class="hero-search-divider"></div>
                            <div class="hero-search-field hero-search-category" style="position:relative;">
                                <label><i class="fas fa-tag me-1"></i> Categoría</label>
                                <input type="hidden" name="category" class="hero-cat-value">
                                <button type="button" class="hero-cat-btn"
                                    onclick="this.closest('.hero-search-category').querySelector('.hero-cat-dropdown').classList.toggle('hero-cat-open')">
                                    <span class="hero-cat-label">Todas las categorías</span>
                                    <i class="fas fa-chevron-down hero-cat-arrow"></i>
                                </button>
                                <div class="hero-cat-dropdown">
                                    <div class="hero-cat-option hero-cat-active" data-value="" onclick="heroPickCat(this)">Todas las categorías</div>
                                    <?php foreach ($categories as $cat): ?>
                                    <div class="hero-cat-option" data-value="<?= (int)$cat['id'] ?>" onclick="heroPickCat(this)"><?= htmlspecialchars($cat['nombre']) ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            <button type="submit" class="hero-search-btn">
                                <i class="fas fa-search me-1"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>

                <div class="mt-3 animate-fade-in-delay-2">
                    <a href="<?= htmlspecialchars($ctaLink) ?>" class="text-white-50 small">
                        <?= htmlspecialchars($ctaText) ?> <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll Indicator -->
    <div class="scroll-indicator">
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

<style>
.hero-section {
    position: relative;
    overflow: visible; /* permite que el dropdown no quede recortado */
    width: 100%;
    height: 100vh !important;
    min-height: 100vh !important;
}

/* El fondo sí necesita overflow:hidden para recortar el video/imagen */
.hero-bg-wrap {
    position: absolute;
    inset: 0;
    overflow: hidden;
    z-index: 0;
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    background-size: cover;
    background-position: center;
    z-index: 0;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.2) 100%);
    z-index: 1;
    pointer-events: none;
}

.hero-section .container {
    position: relative;
    z-index: 3;
}

.hero-section .btn {
    position: relative;
    z-index: 4;
    pointer-events: auto;
}

.hero-section h1,
.hero-section p,
.hero-section a {
    user-select: text;
    -webkit-user-select: text;
    -moz-user-select: text;
    -ms-user-select: text;
}

.scroll-indicator {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    color: white;
    font-size: 24px;
    animation: bounce 2s infinite;
    z-index: 3;
    cursor: pointer;
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

.animate-fade-in {
    animation: fadeIn 1s ease-in;
}

.animate-fade-in-delay-1 {
    animation: fadeIn 1s ease-in 0.3s both;
}

.animate-fade-in-delay-2 {
    animation: fadeIn 1s ease-in 0.6s both;
}

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

/* Hero Search Widget */
.hero-search-widget {
    max-width: 780px;
    margin: 0 auto;
    position: relative;
    z-index: 10;
}

.hero-search-form {
    width: 100%;
}

.hero-search-inner {
    display: flex;
    align-items: stretch;
    background: rgba(255, 255, 255, 0.97);
    border-radius: 60px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.25);
    /* Sin overflow:hidden para que el dropdown no quede recortado */
    padding: 6px 6px 6px 24px;
    gap: 0;
}

.hero-search-field {
    display: flex;
    flex-direction: column;
    justify-content: center;
    flex: 1;
    padding: 6px 12px;
    min-width: 0;
}

.hero-search-field label {
    font-size: 0.7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #333;
    margin-bottom: 2px;
}

.hero-search-field input[type="text"] {
    border: none;
    outline: none;
    background: transparent;
    color: #222;
    font-size: 0.95rem;
    width: 100%;
    padding: 0;
}

.hero-search-field input[type="text"]::placeholder {
    color: #aaa;
}

/* Dropdown custom de categoría */
.hero-cat-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    background: transparent;
    border: none;
    outline: none;
    padding: 0;
    width: 100%;
    color: #222;
    font-size: 0.95rem;
    font-family: inherit;
    cursor: pointer;
    text-align: left;
}

.hero-cat-btn span {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.hero-cat-arrow {
    font-size: 0.65rem;
    color: #888;
    flex-shrink: 0;
    transition: transform 0.2s;
}

.hero-cat-dropdown {
    display: none;
    position: absolute;
    top: calc(100% + 14px);
    left: -16px;
    min-width: 210px;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    border: 1px solid #ebebeb;
    z-index: 9999;
    overflow: hidden;
}

.hero-cat-dropdown.hero-cat-open {
    display: block;
    animation: catFadeIn 0.15s ease;
}

@keyframes catFadeIn {
    from { opacity: 0; transform: translateY(-4px); }
    to   { opacity: 1; transform: translateY(0); }
}

.hero-cat-option {
    padding: 10px 16px;
    font-size: 0.9rem;
    color: #333;
    cursor: pointer;
    transition: background 0.12s;
    white-space: nowrap;
}

.hero-cat-option:hover { background: #f0f4ff; color: #0d6efd; }
.hero-cat-option.hero-cat-active { color: #0d6efd; font-weight: 600; background: #eef2ff; }

.hero-search-divider {
    width: 1px;
    background: #e0e0e0;
    margin: 8px 0;
    flex-shrink: 0;
}

.hero-search-btn {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    color: white;
    border: none;
    border-radius: 50px;
    padding: 12px 28px;
    font-weight: 600;
    font-size: 0.95rem;
    cursor: pointer;
    white-space: nowrap;
    flex-shrink: 0;
    transition: all 0.2s ease;
    box-shadow: 0 2px 10px rgba(13,110,253,0.4);
}

.hero-search-btn:hover {
    background: linear-gradient(135deg, #0a58ca, #084298);
    transform: scale(1.03);
}

@media (max-width: 768px) {
    .hero-section {
        min-height: 100vh !important;
        height: 100vh !important;
    }

    .hero-section h1 {
        font-size: 2rem !important;
    }

    .hero-section .lead {
        font-size: 1rem !important;
    }

    .hero-search-inner {
        flex-direction: column;
        border-radius: 16px;
        padding: 16px;
        gap: 4px;
    }

    .hero-search-field {
        padding: 6px 0;
    }

    .hero-search-field input[type="text"],
    .hero-cat-btn {
        font-size: 1rem;
        padding: 4px 0;
        border-bottom: 1px solid #eee;
        width: 100%;
    }

    .hero-cat-dropdown {
        left: 0;
        right: 0;
        min-width: unset;
    }

    .hero-search-divider {
        display: none;
    }

    .hero-search-btn {
        border-radius: 10px;
        width: 100%;
        padding: 14px;
        margin-top: 8px;
        font-size: 1rem;
    }
}
</style>

<script>
function heroPickCat(el) {
    var field = el.closest('.hero-search-category');
    field.querySelectorAll('.hero-cat-option').forEach(function(o){ o.classList.remove('hero-cat-active'); });
    el.classList.add('hero-cat-active');
    field.querySelector('.hero-cat-value').value = el.dataset.value;
    field.querySelector('.hero-cat-label').textContent = el.textContent.trim();
    field.querySelector('.hero-cat-dropdown').classList.remove('hero-cat-open');
}
document.addEventListener('click', function(e){
    if (!e.target.closest('.hero-search-category')) {
        document.querySelectorAll('.hero-cat-dropdown.hero-cat-open').forEach(function(d){ d.classList.remove('hero-cat-open'); });
    }
});
</script>
