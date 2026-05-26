<?php

use App\Core\Config;
use App\Core\Helpers;
use App\Helpers\PhysicalLevelHelper;
// Metadatos dinámicos por tour (vista alternativa)
$title = ($tour['nombre'] ?? 'Detalle') . ' | Travel Mayan World';
$metaDescription = isset($tour['descripcion_corta']) ? Helpers::truncate($tour['descripcion_corta'], 150) : 'Detalles del destino y opciones de reserva';
// Handle imagen_principal that might already be a full URL
$imagenPrincipal = $tour['imagen_principal'] ?? '';
if (!empty($imagenPrincipal) && (strpos($imagenPrincipal, 'http://') === 0 || strpos($imagenPrincipal, 'https://') === 0)) {
    $metaImage = $imagenPrincipal;
} else {
    $metaImage = !empty($imagenPrincipal) ? Config::getBaseUrl() . 'uploads/tours/' . $imagenPrincipal : Helpers::asset('images/default-destination.jpg');
}
$extraStyles = [
    'css/components/hero.css',
    'css/components/cards.css',
    'css/components/forms.css'
];
require_once __DIR__ . '/../layouts/header.php'; 
// Precio a mostrar (solo UI)
$__price_display = isset($tour) ? ($tour['precio_descuento'] ?: $tour['precio']) : null;
// Heurísticos UI (sin lógica de negocio) para bloques visuales
$__cat = strtolower($tour['categoria_nombre'] ?? '');
$__name = strtolower($tour['nombre'] ?? '');
$__is_transfer = (str_contains($__cat, 'transfer') || str_contains($__cat, 'traslado') || str_contains($__cat, 'transporte') || str_contains($__name, 'transfer'));
$__is_trek = (str_contains($__cat, 'trek') || str_contains($__cat, 'volc') || str_contains($__cat, 'sender') || str_contains($__name, 'trek')) || (strtolower($tour['dificultad'] ?? '') === 'dificil') || ((int)($tour['duracion_dias'] ?? 1) >= 2);
$__physical_level = PhysicalLevelHelper::calculateLevel($tour);
// Procesar galería de imágenes (soportar tanto JSON como CSV)
$gallery = [];
if (!empty($tour['galeria'])) {
    $galleryDecoded = json_decode($tour['galeria'], true);
    if (is_array($galleryDecoded)) {
        $gallery = $galleryDecoded;
    } else {
        // Si no es JSON, asumir que es string separado por comas
        $gallery = array_filter(array_map('trim', explode(',', $tour['galeria'])));
    }
}
// Próxima disponibilidad a partir de disponibilidad (fecha|fecha_salida|fecha_inicio)
$__next_date = null; $__next_date_str = null;
if (!empty($availability) && is_array($availability)) {
    $first = $availability[0] ?? null;
    if ($first) {
        foreach (['fecha', 'fecha_salida', 'fecha_inicio'] as $k) {
            if (!empty($first[$k])) { $__next_date = $first[$k]; break; }
        }
        if ($__next_date) { $__next_date_str = date('d/m/Y', strtotime($__next_date)); }
    }
}
// Pre-compute variables used across page
$mainImageUrl = Helpers::tourImage($tour['imagen_principal'] ?? null, 'images/default-destination.jpg') . '?v=' . time();
$dDias = (int)($tour['duracion_dias'] ?? 0);
$dHoras = (int)($tour['duracion_horas'] ?? 0);
$dParts = [];
if ($dDias > 0) $dParts[] = $dDias . ($dDias === 1 ? ' día' : ' días');
if ($dHoras > 0) $dParts[] = $dHoras . ($dHoras === 1 ? ' hora' : ' horas');
$duracionStr = !empty($dParts) ? implode(', ', $dParts) : htmlspecialchars($tour['duracion'] ?? '1 día');
$__precios_grupo = null;
if (!empty($tour['precios_grupo'])) {
    $__pgx = json_decode($tour['precios_grupo'], true);
    if (is_array($__pgx) && count($__pgx)) $__precios_grupo = $__pgx;
}
$coordsPdp = [];
if (!empty($tour['latitud']) && !empty($tour['longitud'])) {
    $coordsPdp['lat'] = $tour['latitud'];
    $coordsPdp['lng'] = $tour['longitud'];
}
$hasCoordsPdp = !empty($coordsPdp);
$__description_plain = trim(strip_tags((string)($tour['descripcion_corta'] ?? $tour['descripcion'] ?? '')));
$__description_intro = $__description_plain !== '' ? Helpers::truncate($__description_plain, 210) : 'Contáctanos para obtener más información sobre este tour.';
$__hero_intro = Helpers::truncate($__description_intro, 120);
$__includes_list = [];
if (!empty($tour['incluye'])) {
    $__includes_list = array_values(array_filter(array_map('trim', explode(',', (string)$tour['incluye']))));
}
$__excludes_list = [];
if (!empty($tour['no_incluye'])) {
    $__excludes_list = array_values(array_filter(array_map('trim', explode(',', (string)$tour['no_incluye']))));
}
$__hero_highlights = array_slice($__includes_list, 0, 3);
$__experience_pillars = [];
if (!empty($__hero_highlights)) {
    foreach ($__hero_highlights as $__item) {
        $__experience_pillars[] = [
            'icon' => 'fa-check-circle',
            'title' => $__item,
            'text' => 'Incluido para que la experiencia se sienta simple y bien resuelta.'
        ];
    }
}
if (count($__experience_pillars) < 3) {
        $__experience_pillars[] = [
            'icon' => 'fa-shield-alt',
        'title' => 'Reserva con confianza',
        'text' => 'Disponibilidad visible, confirmacion rapida y soporte humano si lo necesitas.'
    ];
}
if (count($__experience_pillars) < 4) {
    $__experience_pillars[] = [
        'icon' => 'fa-camera',
        'title' => 'Experiencia memorable',
        'text' => 'Disenada para que cada parada se sienta valiosa, fotogenica y facil de disfrutar.'
    ];
}
$__experience_pillars = array_slice($__experience_pillars, 0, 4);
$__booking_points = array_slice($__includes_list, 0, 4);
if (empty($__booking_points)) {
    $__booking_points = [
        'Reserva con confirmacion rapida',
        'Experiencia organizada de inicio a fin',
        'Soporte humano antes y durante el tour',
        'Informacion clara para comprar con confianza'
    ];
}
$__key_facts = [
    ['label' => 'Duracion', 'value' => $duracionStr],
    ['label' => 'Destino', 'value' => $tour['ubicacion'] ?? 'Guatemala'],
    ['label' => 'Nivel', 'value' => ucfirst((string)($tour['dificultad'] ?? 'Moderado'))],
    ['label' => 'Edad minima', 'value' => !empty($tour['edad_min']) ? ((int)$tour['edad_min'] . ' anos') : 'Todo publico']
];
?>
<style>
.pdp-editorial { font-family: Georgia, 'Cambria', 'Times New Roman', serif; }
</style>
<script type="application/ld+json">
<?php
$__json = [
  '@context' => 'https://schema.org',
  '@type' => 'Tour',
  'name' => $tour['nombre'],
  'image' => [$metaImage],
  'description' => $metaDescription,
  'category' => $tour['categoria_nombre'] ?? 'Turismo',
  'brand' => ['@type' => 'Brand', 'name' => 'Travel Mayan World'],
  'offers' => [
    '@type' => 'Offer',
    'priceCurrency' => 'USD',
    'price' => number_format(($tour['precio_descuento'] ?: $tour['precio']), 2, '.', ''),
    'availability' => !empty($availability) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
    'url' => Helpers::getCurrentUrl(),
  ],
];
if (!empty($review_summary) && ($review_summary['count'] ?? 0) > 0) {
  $__json['aggregateRating'] = [
    '@type' => 'AggregateRating',
    'ratingValue' => (string)$review_summary['avg'],
    'reviewCount' => (int)$review_summary['count']
  ];
}
echo json_encode($__json, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
?>
</script>

<!-- SEO JSON-LD: BreadcrumbList -->
<script type="application/ld+json">
<?php
$base = rtrim(Config::getBaseUrl(), '/');
$breadcrumbs = [
  '@context' => 'https://schema.org',
  '@type' => 'BreadcrumbList',
  'itemListElement' => [
    [
      '@type' => 'ListItem',
      'position' => 1,
      'name' => 'Inicio',
      'item' => $base . '/'
    ],
    [
      '@type' => 'ListItem',
      'position' => 2,
      'name' => 'Destinos',
      'item' => $base . '/?route=tours'
    ],
  ]
];
if (!empty($category['nombre'])) {
  $breadcrumbs['itemListElement'][] = [
    '@type' => 'ListItem',
    'position' => 3,
    'name' => $category['nombre'],
    'item' => $base . '/?route=tours&category=' . urlencode((string)$category['id'] ?? '')
  ];
}
$breadcrumbs['itemListElement'][] = [
  '@type' => 'ListItem',
  'position' => !empty($category['nombre']) ? 4 : 3,
  'name' => $tour['nombre'],
  'item' => $base . '/?route=tour/' . (int)$tour['id']
];
echo json_encode($breadcrumbs, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
?>
</script>

<!-- SEO JSON-LD: FAQPage (si hay preguntas frecuentes) -->
<?php if (!empty($tour['itinerario']) || !empty($tour['politicas_cancelacion'])): ?>
<script type="application/ld+json">
<?php
$faqs = [
  '@context' => 'https://schema.org',
  '@type' => 'FAQPage',
  'mainEntity' => [
    [
      '@type' => 'Question',
      'name' => '¿Puedo cambiar la fecha?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => 'Sí, hasta 48 horas antes sin costo adicional, sujeto a disponibilidad.'
      ]
    ],
    [
      '@type' => 'Question',
      'name' => '¿Cuál es la política de cancelación?',
      'acceptedAnswer' => [
        '@type' => 'Answer',
        'text' => !empty($tour['politicas_cancelacion']) ? strip_tags($tour['politicas_cancelacion']) : 'La cancelación gratuita está disponible hasta 24 h antes en tours elegibles.'
      ]
    ]
  ]
];
echo json_encode($faqs, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
?>
</script>
<?php endif; ?>

<!-- PDP Hero v2 — full-bleed immersive -->
<section class="pdp-hero-v2" id="pdp-hero">
    <div class="pdp-hero-v2__bg" style="background-image:url('<?= htmlspecialchars($mainImageUrl) ?>')"></div>
    <div class="pdp-hero-v2__overlay"></div>
    <div class="container position-relative h-100">
        <div class="pdp-hero-v2__content">
            <!-- Breadcrumb -->
            <div class="pdp-hero-v2__breadcrumb">
                <a href="<?= Config::getBaseUrl() ?>?route=tours"><i class="fas fa-arrow-left me-1"></i>Todos los destinos</a>
                <?php if (!empty($category['nombre'])): ?>
                    <span class="mx-2 opacity-50">/</span>
                    <span><?= htmlspecialchars($category['nombre']) ?></span>
                <?php endif; ?>
            </div>
            <!-- Title -->
            <h1 class="pdp-hero-v2__title"><?= htmlspecialchars($tour['nombre'] ?? 'Detalle del tour') ?></h1>
            <!-- Stats row -->
            <div class="pdp-hero-v2__stats">
                <?php if ($duracionStr): ?>
                <div class="pdp-hero-v2__stat"><i class="fas fa-clock"></i><span><?= $duracionStr ?></span></div>
                <?php endif; ?>
                <?php if (!empty($tour['ubicacion'])): ?>
                <div class="pdp-hero-v2__stat"><i class="fas fa-map-marker-alt"></i><span><?= htmlspecialchars($tour['ubicacion']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($review_summary) && ($review_summary['count'] ?? 0) > 0): ?>
                <div class="pdp-hero-v2__stat"><i class="fas fa-star" style="color:#f5c518;"></i><span><?= number_format($review_summary['avg'],1) ?> (<?= (int)$review_summary['count'] ?> reseñas)</span></div>
                <?php endif; ?>
                <?php if (!empty($tour['es_privado'])): ?>
                <div class="pdp-hero-v2__stat pdp-hero-v2__stat--gold"><i class="fas fa-lock"></i><span>Tour Privado Exclusivo</span></div>
                <?php endif; ?>
            </div>
            <!-- Trust badges -->
            <div class="pdp-hero-v2__badges">
                <span><i class="fas fa-rotate-left me-1"></i>Cancelación gratis</span>
                <span><i class="fas fa-bolt me-1"></i>Confirmación inmediata</span>
                <span><i class="fas fa-shield-alt me-1"></i>Pago seguro</span>
            </div>
            <p class="pdp-hero-v2__lede"><?= htmlspecialchars($__hero_intro) ?></p>
            <!-- Price preview (private tour) -->
            <?php if ($__precios_grupo): ?>
            <div class="pdp-hero-v2__price-preview" id="private-total-label">
                <i class="fas fa-calculator me-1"></i>Selecciona el número de personas para ver el total
            </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Scroll cue -->
    <div class="pdp-hero-v2__scroll-cue" onclick="document.getElementById('pdp-main-content').scrollIntoView({behavior:'smooth'})">
        <i class="fas fa-chevron-down"></i>
    </div>
</section>

<section class="pdp-header-clean">
    <div class="container">
        <div class="pdp-header-clean__wrap">
            <div class="pdp-header-clean__main">
                <div class="pdp-header-clean__breadcrumb">
                    <a href="<?= Config::getBaseUrl() ?>?route=tours"><i class="fas fa-arrow-left me-1"></i>Todos los destinos</a>
                    <?php if (!empty($category['nombre'])): ?>
                        <span>/</span>
                        <span><?= htmlspecialchars($category['nombre']) ?></span>
                    <?php endif; ?>
                </div>
                <h1 class="pdp-header-clean__title"><?= htmlspecialchars($tour['nombre'] ?? 'Detalle del tour') ?></h1>
                <div class="pdp-header-clean__meta">
                    <?php if ($duracionStr): ?><span><i class="fas fa-clock"></i><?= $duracionStr ?></span><?php endif; ?>
                    <?php if (!empty($tour['ubicacion'])): ?><span><i class="fas fa-map-marker-alt"></i><?= htmlspecialchars($tour['ubicacion']) ?></span><?php endif; ?>
                    <?php if (!empty($review_summary) && ($review_summary['count'] ?? 0) > 0): ?><span><i class="fas fa-star"></i><?= number_format($review_summary['avg'],1) ?> (<?= (int)$review_summary['count'] ?>)</span><?php endif; ?>
                </div>
                <p class="pdp-header-clean__lede"><?= htmlspecialchars($__hero_intro) ?></p>
                <div class="pdp-header-clean__trust">
                    <span>Cancelacion gratis</span>
                    <span>Confirmacion inmediata</span>
                    <span>Pago seguro</span>
                </div>
            </div>
            <aside class="pdp-header-clean__side">
                <?php
                $navPrice = null;
                if ($__precios_grupo) {
                    $navPrice = '$' . number_format(min(array_column($__precios_grupo, 'precio')), 0) . ' USD';
                } elseif ($__price_display) {
                    $navPrice = Helpers::formatPrice($__price_display) . ' USD';
                }
                ?>
                <?php if ($navPrice): ?><div class="pdp-header-clean__price"><?= $navPrice ?></div><?php endif; ?>
                <button class="btn btn-success pdp-header-clean__cta" onclick="scrollToBookingForm()">
                    <i class="fas fa-calendar-check me-1"></i>Reservar ahora
                </button>
                <?php if ($__next_date_str): ?><div class="pdp-header-clean__hint">Próxima fecha: <?= $__next_date_str ?></div><?php endif; ?>
            </aside>
        </div>
    </div>
</section>

<!-- Sticky anchor nav v2 -->
<div class="pdp-nav-v2" id="pdpStickyNav">
    <div class="container">
        <div class="pdp-nav-v2__inner">
            <a href="#itinerary" class="pdp-nav-v2__link"><i class="fas fa-route me-1"></i>Itinerario</a>
            <a href="#includes" class="pdp-nav-v2__link"><i class="fas fa-list-check me-1"></i>Incluye</a>
            <a href="#policy" class="pdp-nav-v2__link"><i class="fas fa-rotate-left me-1"></i>Políticas</a>
            <a href="#reviews" class="pdp-nav-v2__link"><i class="fas fa-star me-1"></i>Reseñas</a>
            <a href="#location" class="pdp-nav-v2__link"><i class="fas fa-map-marker-alt me-1"></i>Ubicación</a>
            <div class="pdp-nav-v2__cta">
                <?php
                $navPrice = null;
                if ($__precios_grupo) {
                    $navPrice = '$' . number_format(min(array_column($__precios_grupo, 'precio')), 0) . ' USD';
                } elseif ($__price_display) {
                    $navPrice = Helpers::formatPrice($__price_display) . ' USD';
                }
                ?>
                <?php if ($navPrice): ?><span class="pdp-nav-v2__price"><?= $navPrice ?></span><?php endif; ?>
                <button class="btn btn-sm btn-success fw-semibold" onclick="scrollToBookingForm()">
                    <i class="fas fa-calendar-check me-1"></i>Reservar
                </button>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($reviews)): ?>
<div class="container mt-3 pdp-obsolete-block">
  <div class="review-highlights">
    <?php $__high = array_slice($reviews, 0, 2); foreach($__high as $rh): ?>
      <div class="review-highlight-item">
        <div class="review-highlight-rating" aria-label="Calificación <?= (int)$rh['rating'] ?> de 5">
          <?php for($i=1;$i<=5;$i++): ?><i class="<?= $i <= (int)$rh['rating'] ? 'fas' : 'far' ?> fa-star"></i><?php endfor; ?>
        </div>
        <?php if (!empty($rh['titulo'])): ?>
          <strong class="review-highlight-title"><?= htmlspecialchars($rh['titulo']) ?></strong>
        <?php endif; ?>
        <?php if (!empty($rh['comentario'])): ?>
          <div class="review-highlight-text text-muted">
            <?= htmlspecialchars(Helpers::truncate($rh['comentario'], 110)) ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php 
// Chips rápidos de beneficios (primeros ítems de "incluye")
$__benefit_chips = [];
if (!empty($tour['incluye'])) {
    $tmp = array_filter(array_map('trim', explode(',', $tour['incluye'])));
    $__benefit_chips = array_slice($tmp, 0, 4);
}
?>
<?php if (!empty($__benefit_chips)): ?>
<div class="container mt-2 pdp-obsolete-block">
  <div class="benefit-badges">
    <?php foreach ($__benefit_chips as $chip): ?>
      <span class="benefit-badge"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($chip) ?></span>
    <?php endforeach; ?>
  </div>
  <hr class="mt-3 mb-0">
  
</div>
<?php endif; ?>

<?php
// Build ordered unique gallery array: main image first, then the rest
$__galleryMain = Helpers::tourImage($tour['imagen_principal'] ?? null, 'images/default-destination.jpg');
$__allImgs = [$__galleryMain];
foreach ($gallery as $__gi) {
    $__gi = trim($__gi);
    if ($__gi !== '' && $__gi !== $__galleryMain) $__allImgs[] = $__gi;
}
$__totalImgs = count($__allImgs);
$__gridClass = 'pdp-gallery__grid--' . min($__totalImgs, 5);
?>
<!-- ══ Photo Gallery Grid ══ -->
<div class="pdp-gallery" id="pdpGallery">
    <div class="pdp-gallery__grid <?= $__gridClass ?>">
        <?php foreach (array_slice($__allImgs, 0, 5) as $__idx => $__src): ?>
        <div class="pdp-gallery__cell pdp-gallery__cell--<?= $__idx ?>"
             onclick="openLightbox(<?= $__idx ?>)"
             role="button" tabindex="0"
             aria-label="Ver foto <?= $__idx + 1 ?>">
            <img src="<?= htmlspecialchars($__src) ?>"
                 alt="<?= htmlspecialchars($tour['nombre']) ?> — foto <?= $__idx + 1 ?>"
                 loading="<?= $__idx === 0 ? 'eager' : 'lazy' ?>"
                 decoding="async"
                 onerror="this.closest('.pdp-gallery__cell').style.display='none'">
            <?php if ($__idx === 4 && $__totalImgs > 5): ?>
            <div class="pdp-gallery__more-overlay">
                <i class="fas fa-images me-2"></i>+<?= $__totalImgs - 4 ?> fotos
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($__totalImgs > 1): ?>
    <button class="pdp-gallery__see-all" onclick="openLightbox(0)">
        <i class="fas fa-table-cells-large me-2"></i>Ver todas las fotos (<?= $__totalImgs ?>)
    </button>
    <?php endif; ?>
</div>

<div class="container my-4 my-lg-5" id="pdp-main-content">
    <div class="row g-4">
        <!-- Content Column -->
        <div class="col-lg-7">

            <section class="pdp-redesign-stage">
                <div class="pdp-redesign-hero-card">
                    <div class="pdp-redesign-hero-card__copy">
                        <?php if (!empty($tour['categoria_nombre'])): ?>
                        <span class="pdp-section-kicker"><?= htmlspecialchars($tour['categoria_nombre']) ?></span>
                        <?php endif; ?>
                        <h2><?= htmlspecialchars($tour['nombre']) ?></h2>
                        <p><?= nl2br(htmlspecialchars($tour['descripcion'] ?? $__description_intro)) ?></p>
                    </div>
                    <div class="pdp-redesign-hero-card__stack">
                        <article class="pdp-redesign-stat-card">
                            <small>Duracion</small>
                            <strong><?= $duracionStr ?></strong>
                            <span><?= !empty($tour['ubicacion']) ? htmlspecialchars($tour['ubicacion']) : 'Guatemala' ?></span>
                        </article>
                        <article class="pdp-redesign-stat-card">
                            <small>Reserva</small>
                            <strong><?= $__next_date_str ?: 'Flexible' ?></strong>
                            <span><?= !empty($availability) ? 'Fechas disponibles' : 'Disponible bajo solicitud' ?></span>
                        </article>
                    </div>
                </div>

                <section class="pdp-redesign-benefits">
                    <?php foreach ($__booking_points as $__point): ?>
                    <article class="pdp-redesign-benefit">
                        <i class="fas fa-check"></i>
                        <span><?= htmlspecialchars($__point) ?></span>
                    </article>
                    <?php endforeach; ?>
                </section>

                <?php if (!empty($availability)): ?>
                <section class="pdp-redesign-section" id="availability">
                    <div class="pdp-redesign-section__head">
                        <span class="pdp-section-kicker">Disponibilidad</span>
                        <h3>Elige tu fecha</h3>
                    </div>
                    <?php $product = $tour; ?>
                    <?php include __DIR__ . '/../components/availability_calendar.php'; ?>
                </section>
                <?php endif; ?>

                <?php if (!empty($tour['itinerario'])): ?>
                <section class="pdp-redesign-section" id="itinerary">
                    <div class="pdp-redesign-section__head">
                        <span class="pdp-section-kicker">Itinerario</span>
                        <h3>¿Qué harás en este tour?</h3>
                    </div>
                    <?php $itineraryNew = json_decode($tour['itinerario'], true); ?>
                    <div class="pdp-redesign-timeline">
                        <?php if (is_array($itineraryNew)): ?>
                            <?php foreach ($itineraryNew as $time => $activity): ?>
                            <article class="pdp-redesign-timeline__item">
                                <div class="pdp-redesign-timeline__time"><?= htmlspecialchars((string)$time) ?></div>
                                <div class="pdp-redesign-timeline__content"><?= htmlspecialchars(is_array($activity) ? implode(', ', $activity) : (string)$activity) ?></div>
                            </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <article class="pdp-redesign-timeline__item">
                                <div class="pdp-redesign-timeline__content pdp-redesign-timeline__content--full"><?= nl2br(htmlspecialchars($tour['itinerario'])) ?></div>
                            </article>
                        <?php endif; ?>
                    </div>
                </section>
                <?php endif; ?>

                <section class="pdp-redesign-section" id="includes">
                    <div class="pdp-redesign-section__head">
                        <span class="pdp-section-kicker">Detalles del tour</span>
                        <h3>¿Qué incluye este tour?</h3>
                    </div>
                    <div class="pdp-redesign-columns">
                        <article class="pdp-redesign-list-card">
                            <h4>Incluye</h4>
                            <div class="pdp-redesign-list">
                                <?php foreach ($__includes_list as $__item): ?>
                                <div><i class="fas fa-check-circle"></i><span><?= htmlspecialchars($__item) ?></span></div>
                                <?php endforeach; ?>
                            </div>
                        </article>
                        <article class="pdp-redesign-list-card">
                            <h4>No incluye</h4>
                            <div class="pdp-redesign-list pdp-redesign-list--soft">
                                <?php if (!empty($__excludes_list)): ?>
                                    <?php foreach ($__excludes_list as $__item): ?>
                                    <div><i class="fas fa-minus-circle"></i><span><?= htmlspecialchars($__item) ?></span></div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div><i class="fas fa-info-circle"></i><span>Consulta con nosotros para más detalles.</span></div>
                                <?php endif; ?>
                            </div>
                        </article>
                    </div>
                    <?php if (!empty($tour['politicas_cancelacion'])): ?>
                    <div class="pdp-redesign-policy" id="policy">
                        <strong>Politicas</strong>
                        <p><?= nl2br(htmlspecialchars($tour['politicas_cancelacion'])) ?></p>
                    </div>
                    <?php endif; ?>
                </section>

                <section class="pdp-redesign-section" id="reviews">
                    <div class="pdp-redesign-section__head">
                        <span class="pdp-section-kicker">Reseñas</span>
                        <h3>Lo que dicen nuestros viajeros</h3>
                    </div>
                    <div class="pdp-redesign-columns">
                        <article class="pdp-redesign-review-summary">
                            <div class="pdp-redesign-review-score">
                                <?php if (!empty($review_summary) && ($review_summary['count'] ?? 0) > 0): ?>
                                    <?= number_format($review_summary['avg'],1) ?>
                                <?php else: ?>
                                    Nuevo
                                <?php endif; ?>
                            </div>
                            <p>
                                <?php if (!empty($review_summary) && ($review_summary['count'] ?? 0) > 0): ?>
                                    Basado en <?= (int)$review_summary['count'] ?> reseña<?= (int)$review_summary['count'] !== 1 ? 's' : '' ?> de viajeros verificados.
                                <?php else: ?>
                                    Sé el primero en dejar una reseña.
                                <?php endif; ?>
                            </p>
                        </article>
                        <div class="pdp-redesign-review-list">
                            <?php if (!empty($reviews)): ?>
                                <?php foreach (array_slice($reviews, 0, 3) as $rev): ?>
                                <article class="pdp-redesign-review">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong><?= htmlspecialchars($rev['nombre']) ?></strong>
                                        <span class="text-warning">
                                            <?php for ($i=1; $i<=5; $i++): ?>
                                                <i class="<?= $i <= (int)$rev['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </span>
                                    </div>
                                    <p><?= htmlspecialchars(Helpers::truncate($rev['comentario'] ?? '', 180)) ?></p>
                                </article>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <article class="pdp-redesign-review">
                                    <strong>Todavía no hay reseñas</strong>
                                    <p>Comparte tu experiencia después de disfrutar este tour y ayuda a otros viajeros a decidir.</p>
                                </article>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="pdp-redesign-section" id="location">
                    <div class="pdp-redesign-section__head">
                        <span class="pdp-section-kicker">Ubicación</span>
                        <h3>Punto de encuentro</h3>
                    </div>
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <?php if ($hasCoordsPdp): ?>
                                <div id="map" style="height: 320px; border-radius: 20px; overflow:hidden;"></div>
                            <?php else: ?>
                                <div class="p-4 text-muted">Mapa disponible proximamente para esta experiencia.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="pdp-redesign-location-meta">
                        <?php if (!empty($tour['ubicacion'])): ?><span><i class="fas fa-map-pin"></i><?= htmlspecialchars($tour['ubicacion']) ?></span><?php endif; ?>
                        <a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=<?= urlencode(($tour['ubicacion'] ?? '') ?: (($coordsPdp['lat'] ?? '') . ',' . ($coordsPdp['lng'] ?? ''))) ?>">
                            Abrir en Google Maps
                        </a>
                    </div>
                </section>

                <?php if (!empty($related_tours)): ?>
                <section class="pdp-redesign-section">
                    <div class="pdp-redesign-section__head">
                        <span class="pdp-section-kicker">Seguir explorando</span>
                        <h3>También te puede interesar</h3>
                    </div>
                    <div class="row g-3">
                        <?php foreach ($related_tours as $rel):
                            $relImg = Helpers::tourImage($rel['imagen_principal'] ?? null, 'images/default-destination.jpg') . '?v=' . time();
                            $relPrice = $rel['precio_descuento'] ?: $rel['precio'];
                        ?>
                        <div class="col-md-4">
                            <article class="pdp-redesign-related">
                                <img src="<?= htmlspecialchars($relImg) ?>" alt="<?= htmlspecialchars($rel['nombre']) ?>" loading="lazy" decoding="async">
                                <div class="pdp-redesign-related__body">
                                    <h4><?= htmlspecialchars($rel['nombre']) ?></h4>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>$<?= number_format($relPrice, 0) ?> USD</span>
                                        <a href="<?= Config::getBaseUrl() ?>?route=tour/<?= $rel['id'] ?>" class="btn btn-sm btn-outline-primary">Ver tour</a>
                                    </div>
                                </div>
                            </article>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
            </section>

            <section class="pdp-story-shell">
                <div class="pdp-story-shell__main">
                    <span class="pdp-section-kicker">Experiencia destacada</span>
                    <h2 class="pdp-story-shell__title"><?= htmlspecialchars($tour['nombre']) ?>, diseñada para inspirar confianza y empujar la reserva.</h2>
                    <p class="pdp-story-shell__text"><?= nl2br(htmlspecialchars($tour['descripcion'] ?? '')) ?></p>
                </div>
                <aside class="pdp-story-shell__aside">
                    <div class="pdp-story-shell__rating">
                        <strong>
                            <?php if (!empty($review_summary) && ($review_summary['count'] ?? 0) > 0): ?>
                                <?= number_format($review_summary['avg'],1) ?>/5
                            <?php else: ?>
                                Curada
                            <?php endif; ?>
                        </strong>
                        <span>
                            <?php if (!empty($review_summary) && ($review_summary['count'] ?? 0) > 0): ?>
                                <?= (int)$review_summary['count'] ?> reseñas verificadas
                            <?php else: ?>
                                Sin reseñas aún
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="pdp-story-shell__next">
                        <small>Proxima salida</small>
                        <strong><?= $__next_date_str ?: 'Consulta disponibilidad' ?></strong>
                    </div>
                </aside>
            </section>

            <section class="pdp-facts-band mt-4">
                <?php foreach ($__key_facts as $__fact): ?>
                <article class="pdp-fact-card">
                    <small><?= htmlspecialchars($__fact['label']) ?></small>
                    <strong><?= htmlspecialchars((string)$__fact['value']) ?></strong>
                </article>
                <?php endforeach; ?>
            </section>

            <section class="pdp-selling-points mt-4">
                <div class="pdp-section-heading">
                    <span class="pdp-section-kicker">Por que reservar</span>
                    <h3>Beneficios claros y visibles antes de que el usuario tenga que pensar demasiado.</h3>
                </div>
                <div class="pdp-selling-points__grid">
                    <?php foreach ($__booking_points as $__point): ?>
                    <article class="pdp-selling-point">
                        <i class="fas fa-check"></i>
                        <span><?= htmlspecialchars($__point) ?></span>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="pdp-premium-overview pdp-obsolete-block">
                <div class="pdp-premium-overview__intro">
                    <span class="pdp-section-eyebrow">Inspirada en fichas que convierten</span>
                    <h2>Mas claridad, mas confianza y una experiencia visual apta para vender tours.</h2>
                    <p><?= htmlspecialchars($__description_intro) ?></p>
                    <div class="pdp-premium-overview__trust">
                        <div class="pdp-trust-chip">
                            <i class="fas fa-award"></i>
                            <span>Beneficios visibles desde el primer scroll</span>
                        </div>
                        <div class="pdp-trust-chip">
                            <i class="fas fa-star"></i>
                            <span>Prueba social cerca del CTA</span>
                        </div>
                        <div class="pdp-trust-chip">
                            <i class="fas fa-calendar-check"></i>
                            <span>Reserva guiada sin friccion</span>
                        </div>
                    </div>
                </div>
                <div class="pdp-premium-overview__grid">
                    <article class="pdp-snapshot-card">
                        <small>Duracion</small>
                        <strong><?= $duracionStr ?></strong>
                        <span>El formato del tour se entiende rapido, sin obligar al usuario a investigar demasiado.</span>
                    </article>
                    <article class="pdp-snapshot-card">
                        <small>Ubicacion</small>
                        <strong><?= htmlspecialchars($tour['ubicacion'] ?? 'Destino confirmado') ?></strong>
                        <span>Contexto y punto de referencia claros, como en los marketplaces lideres.</span>
                    </article>
                    <article class="pdp-snapshot-card">
                        <small>Valoracion</small>
                        <strong>
                            <?php if (!empty($review_summary) && ($review_summary['count'] ?? 0) > 0): ?>
                                <?= number_format($review_summary['avg'],1) ?>/5
                            <?php else: ?>
                                Curada
                            <?php endif; ?>
                        </strong>
                        <span>
                            <?php if (!empty($review_summary) && ($review_summary['count'] ?? 0) > 0): ?>
                                Basado en <?= (int)$review_summary['count'] ?> reseñas verificadas.
                            <?php else: ?>
                                Disenada para inspirar mas confianza desde la primera impresion.
                            <?php endif; ?>
                        </span>
                    </article>
                    <article class="pdp-snapshot-card">
                        <small>Reserva</small>
                        <strong><?= !empty($availability) ? 'Fechas activas' : 'Bajo solicitud' ?></strong>
                        <span><?= $__next_date_str ? 'Proxima salida: ' . htmlspecialchars($__next_date_str) . '.' : 'Soporte disponible para ayudarte a elegir la mejor fecha.' ?></span>
                    </article>
                </div>
            </section>

            <section class="pdp-experience-strip mt-4 pdp-obsolete-block">
                <?php foreach ($__experience_pillars as $__pillar): ?>
                <article class="pdp-experience-card">
                    <div class="pdp-experience-card__icon">
                        <i class="fas <?= htmlspecialchars($__pillar['icon']) ?>"></i>
                    </div>
                    <div>
                        <h3><?= htmlspecialchars($__pillar['title']) ?></h3>
                        <p><?= htmlspecialchars($__pillar['text']) ?></p>
                    </div>
                </article>
                <?php endforeach; ?>
            </section>

            <!-- Descripción detallada -->
            <div class="card shadow-sm mt-4 pdp-editorial-card pdp-obsolete-block">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-info-circle"></i> Por que esta experiencia se siente premium</h4>
                </div>
                <div class="card-body">
                    <p class="lead mb-4"><?= nl2br(htmlspecialchars($tour['descripcion'] ?? '')) ?></p>
                    <div class="pdp-editorial-card__footer">
                        <div>
                            <span class="pdp-editorial-card__label">Ideal para</span>
                            <strong><?= !empty($tour['es_privado']) ? 'Viajeros que quieren privacidad y control' : 'Viajeros que quieren una compra clara y segura' ?></strong>
                        </div>
                        <div>
                            <span class="pdp-editorial-card__label">Lo que genera confianza</span>
                            <strong><?= !empty($__includes_list) ? htmlspecialchars($__includes_list[0]) : 'Precio visible, detalles completos y soporte directo' ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COMPONENTE: Indicadores de Urgencia -->
            <div class="pdp-obsolete-block">
                <?php include __DIR__ . '/../components/urgency_indicators.php'; ?>
            </div>

            <!-- COMPONENTE: Calendario Visual de Disponibilidad -->
            <?php if (!empty($availability)): ?>
                <?php $product = $tour; ?>
                <section class="pdp-framed-block">
                    <div class="pdp-section-heading mb-3">
                        <span class="pdp-section-kicker">Disponibilidad</span>
                        <h3>Fechas y cupos visibles para decidir rapido.</h3>
                    </div>
                    <?php include __DIR__ . '/../components/availability_calendar.php'; ?>
                </section>
            <?php endif; ?>

            <!-- COMPONENTE: Perfil del Guía (si existe) -->
            <?php if (!empty($guide)): ?>
                <?php include __DIR__ . '/../components/guide_profile.php'; ?>
            <?php endif; ?>

            <!-- Políticas de cancelación -->
            <?php if (!empty($tour['politicas_cancelacion'])): ?>
            <div class="card shadow-sm mt-4 pdp-policy-card" id="policy">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-rotate-left"></i> Políticas de Cancelación</h4>
                </div>
                <div class="card-body">
                    <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($tour['politicas_cancelacion'])) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Itinerario -->
            <?php if (!empty($tour['itinerario'])): ?>
            <div class="card shadow-sm mt-4 pdp-dup-card" id="itinerary-old">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-route"></i> Itinerario</h4>
                </div>
                <div class="card-body">
                    <?php 
                    $itinerary = json_decode($tour['itinerario'], true);
                    if ($itinerary): 
                    ?>
                        <div class="timeline">
                            <?php foreach($itinerary as $time => $activity): ?>
                                <div class="timeline-item mb-3">
                                    <div class="row">
                                        <div class="col-2">
                                            <span class="badge bg-primary"><?= htmlspecialchars($time) ?></span>
                                        </div>
                                        <div class="col-10">
                                            <p class="mb-0"><?= htmlspecialchars(is_array($activity) ? implode(', ', $activity) : $activity) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p><?= nl2br(htmlspecialchars($tour['itinerario'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Transfers highlights (UI only) -->
            <?php if ($__is_transfer): ?>
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-car-side text-primary me-2"></i>Traslados: beneficios clave</h5>
                </div>
                <div class="card-body">
                    <div class="transfer-highlights">
                        <span class="transfer-pill"><i class="fas fa-tag"></i> Precio fijo</span>
                        <span class="transfer-pill"><i class="fas fa-plane-arrival"></i> Monitoreo de vuelos</span>
                        <span class="transfer-pill"><i class="fas fa-handshake"></i> Meet & greet</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Treks / Nivel físico (UI only) -->
            <?php if ($__is_trek): ?>
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-mountain text-warning me-2"></i>Nivel Físico</h5>
                </div>
                <div class="card-body">
                    <?= PhysicalLevelHelper::renderDetailedInfo($__physical_level) ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($tour['es_privado'])): ?>
            <!-- Sección tour privado -->
            <div class="card shadow-sm mt-4" style="border:none;border-left:4px solid var(--primary-color);">
                <div class="card-body">
                    <h5 class="fw-bold mb-3 text-primary"><i class="fas fa-lock me-2"></i>¿Qué es un Tour Privado?</h5>
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-3">
                            <div class="text-center p-3 rounded bg-light">
                                <i class="fas fa-users fa-2x mb-2 text-primary"></i>
                                <div class="fw-semibold small">Solo tu grupo</div>
                                <div class="text-muted" style="font-size:.8rem;">Sin personas desconocidas</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="text-center p-3 rounded bg-light">
                                <i class="fas fa-clock fa-2x mb-2 text-primary"></i>
                                <div class="fw-semibold small">A tu ritmo</div>
                                <div class="text-muted" style="font-size:.8rem;">Horario personalizado</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="text-center p-3 rounded bg-light">
                                <i class="fas fa-map-marked-alt fa-2x mb-2 text-primary"></i>
                                <div class="fw-semibold small">Guía dedicado</div>
                                <div class="text-muted" style="font-size:.8rem;">Atención exclusiva</div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="text-center p-3 rounded bg-light">
                                <i class="fas fa-tag fa-2x mb-2 text-primary"></i>
                                <div class="fw-semibold small">Precio por grupo</div>
                                <div class="text-muted" style="font-size:.8rem;">Más personas = menos por persona</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- COMPONENTE: Qué Traer / Lista de Equipaje -->
            <?php include __DIR__ . '/../components/what_to_bring.php'; ?>

            <!-- COMPONENTE: Preguntas Frecuentes -->
            <?php include __DIR__ . '/../components/tour_faq.php'; ?>

            <!-- Reseñas -->
            <div class="card shadow-sm mt-4" id="reviews">
                <div class="card-header bg-white">
                    <h4 class="mb-0">
                        <i class="fas fa-star text-warning"></i> Reseñas
                        <?php if (!empty($review_summary) && $review_summary['count'] > 0): ?>
                            <small class="text-muted ms-2">
                                Promedio <?= number_format($review_summary['avg'],1) ?> (<?= (int)$review_summary['count'] ?>)
                            </small>
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-lg-7">
                            <?php if (!empty($reviews)): ?>
                                <?php foreach ($reviews as $rev): ?>
                                    <div class="border rounded p-3 mb-3">
                                        <div class="d-flex justify-content-between">
                                            <strong><?= htmlspecialchars($rev['nombre']) ?></strong>
                                            <span class="text-warning">
                                                <?php for ($i=1; $i<=5; $i++): ?>
                                                    <i class="<?= $i <= (int)$rev['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                                <?php endfor; ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($rev['comentario'])): ?>
                                        <p class="mb-0 text-muted mt-2"><?= nl2br(htmlspecialchars($rev['comentario'])) ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-info">Sé el primero en opinar sobre este tour.</div>
                            <?php endif; ?>
                        </div>
                        <div class="col-lg-5">
                            <div class="bg-light rounded p-3">
                                <h6 class="mb-2"><i class="fas fa-pen me-2"></i>Escribe una reseña</h6>

                                <?php if (!empty($canReview)): ?>
                                    <form method="POST" action="<?= Config::getBaseUrl() ?>?route=review/submit">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <input type="hidden" name="tour_id" value="<?= (int)$tour['id'] ?>">
                                        <div class="mb-2">
                                            <label class="form-label">Tu nombre</label>
                                            <input type="text" name="nombre" class="form-control" required>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label">Calificación</label>
                                            <select name="rating" class="form-select" required>
                                                <option value="">Selecciona</option>
                                                <?php for ($i=5; $i>=1; $i--): ?>
                                                    <option value="<?= $i ?>"><?= $i ?> estrella<?= $i>1?'s':'' ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Comentario</label>
                                            <textarea name="comentario" rows="3" class="form-control" placeholder="¿Qué te gustó? ¿Qué se puede mejorar?" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-paper-plane me-1"></i> Enviar reseña
                                        </button>
                                        <small class="text-muted d-block mt-2">Las reseñas se muestran tras aprobación.</small>
                                    </form>
                                <?php elseif (empty($isAuthenticated)): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Inicia sesión para dejar una reseña</strong>
                                        <p class="mb-2 mt-2 small">Solo los clientes que han disfrutado de este tour pueden opinar.</p>
                                        <a href="<?= Config::getBaseUrl() ?>?route=login" class="btn btn-sm btn-primary">
                                            <i class="fas fa-sign-in-alt me-1"></i>Iniciar Sesión
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-lock me-2"></i>
                                        <strong>Reseñas verificadas</strong>
                                        <p class="mb-0 mt-2 small">Solo puedes dejar una reseña si has completado este tour. Reserva ahora y comparte tu experiencia después de disfrutarlo.</p>
                                        <a href="#booking-form" class="btn btn-sm btn-success mt-2">
                                            <i class="fas fa-calendar-check me-1"></i>Reservar Ahora
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Qué incluye / No incluye - Mejorado según benchmark -->
            <div class="includes-excludes-section mt-4" id="includes">
                <div class="section-header mb-4 text-center">
                    <h3 class="mb-2">
                        <i class="fas fa-list-check text-primary me-2"></i>
                        ¿Qué está incluido en este tour?
                    </h3>
                    <p class="text-muted">Todo lo que necesitas saber antes de reservar</p>
                </div>
                
                <div class="row">
                    <?php if (!empty($tour['incluye'])): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="includes-card">
                            <div class="card-header-custom bg-success">
                                <div class="d-flex align-items-center">
                                    <div class="header-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 text-white">Incluye</h5>
                                        <small class="text-white opacity-75">Todo esto está incluido en el precio</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body-custom">
                                <div class="includes-grid">
                                    <?php 
                                    $includes = array_filter(array_map('trim', explode(',', $tour['incluye'])));
                                    foreach($includes as $item): 
                                        $item = trim($item);
                                        if (empty($item)) continue;
                                    ?>
                                        <div class="include-item">
                                            <div class="include-icon">
                                                <i class="fas fa-check"></i>
                                            </div>
                                            <div class="include-text">
                                                <?= htmlspecialchars($item) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($tour['no_incluye'])): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="excludes-card">
                            <div class="card-header-custom bg-orange">
                                <div class="d-flex align-items-center">
                                    <div class="header-icon">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0 text-white">No incluye</h5>
                                        <small class="text-white opacity-75">Gastos adicionales a considerar</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body-custom">
                                <div class="excludes-grid">
                                    <?php 
                                    $excludes = array_filter(array_map('trim', explode(',', $tour['no_incluye'])));
                                    foreach($excludes as $item): 
                                        $item = trim($item);
                                        if (empty($item)) continue;
                                    ?>
                                        <div class="exclude-item">
                                            <div class="exclude-icon">
                                                <i class="fas fa-times"></i>
                                            </div>
                                            <div class="exclude-text">
                                                <?= htmlspecialchars($item) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Additional info section -->
                <div class="additional-info-section mt-4 pdp-obsolete-block">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-highlight">
                                <i class="fas fa-shield-alt text-primary fs-4 mb-2"></i>
                                <h6 class="fw-bold">Pago seguro</h6>
                                <small class="text-muted">Transacciones protegidas con SSL</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-highlight">
                                <i class="fas fa-rotate-left text-success fs-4 mb-2"></i>
                                <h6 class="fw-bold">Cancelación flexible</h6>
                                <small class="text-muted">Cancela hasta 24h antes sin costo</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-highlight">
                                <i class="fas fa-headset text-info fs-4 mb-2"></i>
                                <h6 class="fw-bold">Soporte 24/7</h6>
                                <small class="text-muted">Asistencia antes, durante y después</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick FAQ -->
                <div class="quick-faq mt-4 p-3 bg-light rounded pdp-obsolete-block">
                    <h6 class="mb-2"><i class="fas fa-question-circle text-primary me-2"></i>Preguntas frecuentes</h6>
                    <div class="row small">
                        <div class="col-md-6">
                            <strong>¿Puedo cambiar la fecha?</strong><br>
                            <span class="text-muted">Sí, hasta 48h antes sin costo adicional</span>
                        </div>
                        <div class="col-md-6">
                            <strong>¿Qué pasa si llueve?</strong><br>
                            <span class="text-muted">El tour continúa. Se proporciona equipo impermeable</span>
                        </div>
                    </div>
                </div>
                <div class="pdp-reassurance-row mt-3">
                    <div><i class="fas fa-shield-alt"></i><span>Pago seguro</span></div>
                    <div><i class="fas fa-rotate-left"></i><span>Cancelacion flexible</span></div>
                    <div><i class="fas fa-headset"></i><span>Soporte humano</span></div>
                </div>
            </div>

            
            <!-- Partners row -->
            <div class="card shadow-sm mt-4 pdp-obsolete-block">
                <div class="card-body text-center">
                    <div class="partner-logos">
                        <i class="fab fa-cc-visa fa-2x partner-icon" aria-hidden="true"></i>
                        <i class="fab fa-cc-mastercard fa-2x partner-icon" aria-hidden="true"></i>
                        <i class="fab fa-cc-amex fa-2x partner-icon" aria-hidden="true"></i>
                        <i class="fab fa-cc-paypal fa-2x partner-icon" aria-hidden="true"></i>
                        <i class="fab fa-tripadvisor fa-2x partner-icon" aria-hidden="true"></i>
                    </div>
                </div>
            </div>

            <!-- Mapa -->
            <?php
            // Extract coordinates from product data
            $coords = [];
            if (!empty($tour['latitud']) && !empty($tour['longitud'])) {
                $coords['lat'] = $tour['latitud'];
                $coords['lng'] = $tour['longitud'];
            }
            $lat = isset($coords['lat']) ? floatval($coords['lat']) : 0;
            $lng = isset($coords['lng']) ? floatval($coords['lng']) : 0;
            $hasCoords = ($lat !== 0 && $lng !== 0);
            ?>
            <div class="card shadow-sm mt-4 pdp-dup-card" id="location-old">
                <div class="card-header bg-white">
                    <h4 class="mb-0"><i class="fas fa-map-marker-alt"></i> Ubicación</h4>
                </div>
                <div class="card-body">
                    <?php if ($hasCoords): ?>
                        <div id="map" style="height: 320px; border-radius: 8px; overflow:hidden;"></div>
                        <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                            <?php if (!empty($tour['ubicacion'])): ?>
                                <span class="text-muted"><i class="fas fa-map-pin me-1"></i> Punto de encuentro: <?= htmlspecialchars($tour['ubicacion']) ?></span>
                            <?php endif; ?>
                            <a class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=<?= urlencode(($tour['ubicacion'] ?? '') ?: ($lat . ',' . $lng)) ?>">
                                <i class="fas fa-external-link-alt me-1"></i> Abrir en Google Maps
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-2">
                            <i class="fas fa-info-circle"></i> Próximamente mapa para esta experiencia.
                        </div>
                        <?php if (!empty($tour['ubicacion'])): ?>
                            <a class="btn btn-outline-primary" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($tour['ubicacion']) ?>">
                                <i class="fas fa-external-link-alt me-1"></i> Abrir en Google Maps
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Tours relacionados -->
            <?php if (!empty($related_tours)): ?>
            <div class="card shadow-sm mt-4 pdp-dup-card">
                <div class="card-header bg-light">
                    <h4 class="mb-0"><i class="fas fa-th-large"></i> También te puede interesar</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($related_tours as $rel):
                            $relImg = Helpers::tourImage($rel['imagen_principal'] ?? null, 'images/default-destination.jpg');
                            $relPrice = $rel['precio_descuento'] ?: $rel['precio'];
                            // Agregar timestamp para evitar caché
                            $relImg = $relImg . '?v=' . time();
                        ?>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100 product-card">
                                <img src="<?= htmlspecialchars($relImg) ?>"
                                     class="card-img-top skeleton"
                                     alt="<?= htmlspecialchars($rel['nombre']) ?>"
                                     loading="lazy" decoding="async"
                                     width="800" height="450"
                                     style="height: 200px; object-fit: cover;"
                                     onerror="this.src='<?= Helpers::asset('images/default-destination.jpg') ?>'">
                                <div class="card-body">
                                    <h6 class="card-title mb-1">
                                        <a class="text-decoration-none" href="<?= Config::getBaseUrl() ?>?route=tour/<?= $rel['id'] ?>">
                                            <?= htmlspecialchars($rel['nombre']) ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted d-block mb-2"><?= htmlspecialchars($rel['categoria_nombre'] ?? '') ?></small>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-primary fw-bold">$<?= number_format($relPrice, 0) ?> USD</span>
                                        <a class="btn btn-sm btn-outline-primary" href="<?= Config::getBaseUrl() ?>?route=tour/<?= $rel['id'] ?>">
                                            Ver
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Panel lateral de reserva -->
        <div class="col-lg-5" id="bookingSidebarCol">
            <div class="pdp-booking-v2 sticky-top" style="top:80px;">
                <!-- Booking header: price -->
                <div class="pdp-booking-v2__header">
                    <?php if ($__precios_grupo): ?>
                        <div class="pdp-booking-v2__private-tag"><i class="fas fa-lock me-1"></i>Tour Exclusivo Privado</div>
                        <div class="pdp-booking-v2__price-label">Precio por persona según grupo</div>
                        <div class="pdp-booking-v2__tiers">
                            <?php foreach ($__precios_grupo as $__i => $__t):
                                $__d = (int)($__t['desde'] ?? 1);
                                $__h = isset($__t['hasta']) && $__t['hasta'] !== null ? (int)$__t['hasta'] : null;
                                $__tlabel = $__h === null ? $__d . ' pers. en adelante' : ($__d === $__h ? $__d . ' persona' . ($__d>1?'s':'') : $__d . '–' . $__h . ' pers.');
                            ?>
                            <div class="pdp-booking-v2__tier <?= $__i === 0 ? 'active' : '' ?>">
                                <span><i class="fas fa-users me-1"></i><?= $__tlabel ?></span>
                                <strong>$<?= number_format((float)$__t['precio'], 0) ?> <small>USD</small></strong>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="pdp-booking-v2__price-label">Desde</div>
                        <div class="pdp-booking-v2__price-amount">
                            <?php if (!empty($tour['precio_descuento']) && $tour['precio_descuento'] < $tour['precio']): ?>
                                <span class="pdp-booking-v2__price-old">$<?= Helpers::safeNumberFormat($tour['precio'], 0) ?></span>
                                $<?= Helpers::safeNumberFormat($tour['precio_descuento'], 0) ?>
                            <?php else: ?>
                                $<?= Helpers::safeNumberFormat($tour['precio'] ?? 0, 0) ?>
                            <?php endif; ?>
                            <small>USD<?= (!empty($tour['precio_nino']) && $tour['precio_nino'] > 0) ? ' adultos' : '/persona' ?></small>
                        </div>
                        <?php if (!empty($tour['precio_nino']) && $tour['precio_nino'] > 0): ?>
                        <div class="pdp-booking-v2__child-price"><i class="fas fa-child me-1"></i>Niños: $<?= Helpers::safeNumberFormat($tour['precio_nino'], 0) ?> USD</div>
                        <?php endif; ?>
                        <?php if ($__next_date_str): ?>
                        <div class="pdp-booking-v2__next-date"><i class="fas fa-calendar-check me-1"></i>Próxima fecha: <?= $__next_date_str ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <div class="pdp-booking-v2__meta">
                        <?php if (!empty($review_summary) && ($review_summary['count'] ?? 0) > 0): ?>
                        <div class="pdp-booking-v2__meta-pill">
                            <i class="fas fa-star"></i>
                            <span><?= number_format($review_summary['avg'],1) ?> de 5 con <?= (int)$review_summary['count'] ?> reseñas</span>
                        </div>
                        <?php endif; ?>
                        <div class="pdp-booking-v2__meta-pill">
                            <i class="fas fa-shield-alt"></i>
                            <span>Reserva segura y soporte rapido</span>
                        </div>
                    </div>
                </div>
                <!-- Quick info strip -->
                <div class="pdp-booking-v2__info-strip">
                    <div class="pdp-booking-v2__info-item">
                        <i class="fas fa-clock"></i>
                        <span><?= $duracionStr ?></span>
                    </div>
                    <div class="pdp-booking-v2__info-item">
                        <i class="fas fa-users"></i>
                        <span>Máx. <?= $tour['capacidad_maxima'] ?? '10' ?></span>
                    </div>
                    <?php
                    $diffMap = ['facil'=>['Fácil','success'],'moderado'=>['Moderado','warning'],'dificil'=>['Difícil','danger']];
                    $diffData = $diffMap[$tour['dificultad'] ?? 'facil'] ?? ['Fácil','success'];
                    ?>
                    <div class="pdp-booking-v2__info-item">
                        <i class="fas fa-mountain"></i>
                        <span class="badge bg-<?= $diffData[1] ?> fw-normal"><?= $diffData[0] ?></span>
                    </div>
                    <?php if (!empty($tour['edad_min']) && (int)$tour['edad_min'] > 0): ?>
                    <div class="pdp-booking-v2__info-item">
                        <i class="fas fa-child"></i>
                        <span>Mín. <?= (int)$tour['edad_min'] ?> años</span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="pdp-booking-v2__trust-list">
                    <div><i class="fas fa-check-circle"></i> Confirmación inmediata de disponibilidad</div>
                    <div><i class="fas fa-rotate-left"></i> Cancelación flexible según políticas</div>
                    <div><i class="fas fa-headset"></i> Soporte por WhatsApp, teléfono y correo</div>
                </div>
                <div class="pdp-booking-v2__body">

                    <!-- Formulario de reserva -->
                    <form action="<?= Config::getBaseUrl() ?>?route=booking/process" method="POST" id="bookingForm" onsubmit="return handleBookingSubmit(event)">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? Helpers::generateCsrfToken()) ?>">
                        <input type="hidden" name="tour_id" value="<?= $tour['id'] ?>">
                        
                        <!-- Selector de fecha -->
                        <div class="mb-3">
                            <label for="fecha_tour" class="form-label">Fecha del Tour</label>
                            <div class="d-flex gap-2">
                                <select name="disponibilidad_id" id="fecha_tour" class="form-select" required aria-describedby="fecha_help">
                                    <option value="">Selecciona una fecha</option>
                                    <?php if (!empty($availability)): ?>
                                        <?php
                                        // Agrupar por mes para mejor UX
                                        $byMonth = [];
                                        foreach ($availability as $date) {
                                            $month = date('F Y', strtotime($date['fecha_salida']));
                                            $byMonth[$month][] = $date;
                                        }
                                        foreach ($byMonth as $monthLabel => $dates): ?>
                                            <optgroup label="<?= htmlspecialchars(ucwords($monthLabel)) ?>">
                                                <?php foreach ($dates as $date): 
                                                    $available = $date['cupos_disponibles'] - $date['cupos_reservados'];
                                                    $basePrice = $tour['precio_descuento'] ?: $tour['precio'];
                                                    $unitPrice = !empty($date['precio_especial']) ? $date['precio_especial'] : $basePrice;
                                                    $priceDisplay = '$' . number_format($unitPrice, 0);
                                                ?>
                                                    <option value="<?= $date['id'] ?>" 
                                                            data-available="<?= $available ?>"
                                                            data-price="<?= $unitPrice ?>"
                                                            <?= $available <= 0 ? 'disabled' : '' ?>>
                                                        <?= date('d/m/Y', strtotime($date['fecha_salida'])) ?> — <?= $priceDisplay ?> USD (<?= $available ?> cupos)
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>No hay fechas disponibles</option>
                                    <?php endif; ?>
                                </select>
                                <?php if (!empty($availability)): ?>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#datePickerModal">
                                    <i class="fas fa-calendar-alt"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                            <div id="fecha_help" class="form-text">Fechas agrupadas por mes. Solo se muestran fechas con disponibilidad.</div>
                            <div class="form-text">Selecciona una fecha para ver el total actualizado.</div>
                        </div>

                        <!-- Selector de horario -->
                        <?php
                        $horarios = [];
                        if (!empty($tour['horarios'])) {
                            $horarios = json_decode($tour['horarios'], true);
                            if (!is_array($horarios)) $horarios = [];
                        }
                        ?>
                        <?php if (!empty($horarios)): ?>
                        <div class="mb-3">
                            <label for="horario" class="form-label"><i class="fas fa-clock me-1"></i>Horario de Salida</label>
                            <select name="horario" id="horario" class="form-select" required>
                                <option value="">Selecciona un horario</option>
                                <?php foreach ($horarios as $h):
                                    $hora24 = $h['hora'] ?? '';
                                    $label = $h['label'] ?? '';
                                    $parts = explode(':', $hora24);
                                    $hh = (int)($parts[0] ?? 0);
                                    $mm = $parts[1] ?? '00';
                                    $ampm = $hh >= 12 ? 'PM' : 'AM';
                                    $h12 = $hh % 12 ?: 12;
                                    $timeDisplay = $h12 . ':' . $mm . ' ' . $ampm;
                                    $optionText = $timeDisplay . ($label ? ' — ' . $label : '');
                                ?>
                                    <option value="<?= htmlspecialchars($hora24) ?>"><?= htmlspecialchars($optionText) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <!-- Personas + Hotel: compact row -->
                        <div class="bk-row-2col mb-3">
                            <div>
                                <label class="bk-label" for="numero_personas"><?= !empty($tour['es_privado']) ? '<i class="fas fa-users me-1"></i>Grupo' : 'Personas' ?></label>
                                <select name="numero_personas" id="numero_personas" class="form-select form-select-sm" required>
                                    <?php for($i = ($tour['grupo_min'] ?? 1); $i <= min($tour['capacidad_maxima'] ?? 10, 20); $i++): ?>
                                        <option value="<?= $i ?>"><?= $i ?> pers.</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div>
                                <label class="bk-label" for="hotel_nombre"><i class="fas fa-map-marker-alt me-1"></i>Hotel / Punto de recogida</label>
                                <input type="text" class="form-control form-control-sm" id="hotel_nombre" name="hotel_nombre"
                                       placeholder="Ej: Hotel Westin, Zona 10"
                                       autocomplete="off">
                            </div>
                        </div>
                        <?php if (!empty($tour['es_privado'])): ?>
                        <div class="bk-private-calc mb-3" id="private-price-info">
                            <i class="fas fa-tag me-1"></i>
                            <span id="private-price-per-person">—</span>
                            &nbsp;·&nbsp;
                            <strong>Total: <span id="private-price-total">—</span> USD</strong>
                        </div>
                        <?php endif; ?>

                        <!-- === PRICE SWAP ZONE: same height, noDateAlert ↔ totalCalculation === -->
                        <!-- Visible when NO date selected -->
                        <div class="bk-hint" id="noDateAlert">
                            <i class="fas fa-calendar-alt me-2"></i>Selecciona una fecha para ver el precio total
                        </div>
                        <!-- Visible when date IS selected — replaces hint above, same height -->
                        <div class="bk-total-line" id="totalCalculation" style="display: none;">
                            <div class="bk-total-breakdown" id="totalBreakdown"></div>
                            <div class="bk-total-amount">Total: <strong><span id="totalAmount">$0</span> USD</strong></div>
                        </div>

                        <!-- CTA — NEVER moves (always right below the swap zone) -->
                        <div id="bookingActionCard" class="bk-cta-area">
                            <button type="submit" class="bk-btn-reserve pulse-button" id="bookNowBtn" disabled>
                                <i class="fas fa-check-circle me-2"></i>COMPLETAR RESERVA
                            </button>
                            <button type="button" class="bk-btn-rnpl" id="bookRnplBtn" style="display: none;">
                                <i class="fas fa-calendar-plus me-2"></i>CONFIRMAR (Pagas Después)
                            </button>
                            <div class="bk-security">
                                <i class="fas fa-lock me-1"></i>Pago seguro &nbsp;·&nbsp; <i class="fas fa-shield-alt me-1"></i>Protección al cliente
                            </div>
                        </div>

                        <!-- RNPL — BELOW CTA so it NEVER shifts the button up -->
                        <div class="bk-rnpl-toggle" id="rnplSection" style="display: none;">
                            <div class="bk-rnpl-row">
                                <div class="bk-rnpl-text">
                                    <i class="fas fa-calendar-plus text-info me-2"></i>
                                    <div>
                                        <strong class="d-block" style="font-size:.85rem;">Reserva ahora, paga después</strong>
                                        <span style="font-size:.78rem;color:#6B6557;">Sin cargo ahora · Pagas <span id="rnplHoldAmount">$0</span> USD 48h antes</span>
                                    </div>
                                </div>
                                <div class="form-check form-switch mb-0 ms-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="enableRnpl" name="enable_rnpl">
                                </div>
                            </div>
                        </div>

                        <!-- Payment method (shown when RNPL enabled) -->
                        <div class="payment-method-section mt-2" id="paymentMethodSection" style="display: none;">
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="paymentCard" value="card" checked>
                                    <label class="form-check-label small" for="paymentCard"><i class="fas fa-credit-card text-primary me-1"></i>Tarjeta</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="paymentPaypal" value="paypal">
                                    <label class="form-check-label small" for="paymentPaypal"><i class="fab fa-paypal text-info me-1"></i>PayPal</label>
                                </div>
                            </div>
                        </div>

                        <!-- Contacto WhatsApp -->
                        <a href="https://wa.me/<?= str_replace(['+', ' ', '-'], '', Config::SOCIAL_WHATSAPP) ?>?text=Hola,%20me%20interesa%20el%20tour%20<?= urlencode($tour['nombre']) ?>" 
                           target="_blank" 
                           class="btn btn-success w-100">
                            <i class="fab fa-whatsapp"></i> Consultar por WhatsApp
                        </a>
                    </form>

                    <!-- Información de contacto -->
                    <div class="pdp-booking-v2__contact">
                        <div class="pdp-booking-v2__contact-title"><i class="fas fa-headset me-1"></i>¿Necesitas ayuda?</div>
                        <a href="tel:<?= Config::COMPANY_PHONE ?>" class="pdp-booking-v2__contact-link">
                            <i class="fas fa-phone"></i><?= Config::COMPANY_PHONE ?>
                        </a>
                        <a href="mailto:<?= Config::COMPANY_EMAIL ?>" class="pdp-booking-v2__contact-link">
                            <i class="fas fa-envelope"></i><?= Config::COMPANY_EMAIL ?>
                        </a>
                    </div>
                </div><!-- /pdp-booking-v2__body -->
            </div><!-- /pdp-booking-v2 -->
        </div><!-- /col-lg-5 -->
    </div><!-- /row -->
</div><!-- /container -->

<!-- (old sticky-cta removed, replaced by pdp-sticky-mobile below) -->

<script>
// Add body padding when sticky CTA is present (mobile only)
document.addEventListener('DOMContentLoaded', function(){
  const el = document.querySelector('.sticky-cta');
  function apply(){
    if (!el) return;
    const isMobile = window.matchMedia('(max-width: 767.98px)').matches;
    document.body.classList.toggle('has-sticky-cta', isMobile);
  }
  apply();
  window.addEventListener('resize', apply);
});
</script>

<!-- WhatsApp Float Button -->
<a href="https://wa.me/<?= str_replace(['+', ' ', '-'], '', Config::SOCIAL_WHATSAPP) ?>?text=Hola,%20me%20interesa%20información%20sobre%20sus%20tours" 
   target="_blank" 
   class="whatsapp-float">
    <i class="fab fa-whatsapp"></i>
</a>

<script>
// PHP variable to JavaScript
const BASE_URL = <?= json_encode(Config::getBaseUrl()) ?>;

document.addEventListener('DOMContentLoaded', function() {
// Legacy – redirect to lightbox
window.changeMainImage = function(src) {
    if (typeof openLightbox === 'function' && window.images) {
        const idx = window.images.indexOf(src);
        openLightbox(idx >= 0 ? idx : 0);
    }
}

// Scroll to booking form
window.scrollToBookingForm = function() {
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Focus on first input
        const firstInput = document.getElementById('fecha_tour');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 500);
        }
    }
}

// Calcular total automáticamente
    const fechaSelect = document.getElementById('fecha_tour');
    const personasSelect = document.getElementById('numero_personas');
    const totalDiv = document.getElementById('totalCalculation');
    const totalAmount = document.getElementById('totalAmount');
    const totalBreakdown = document.getElementById('totalBreakdown');
    const bookNowBtn = document.getElementById('bookNowBtn');
    const noDateAlert = document.getElementById('noDateAlert');
    const bookingActionCard = document.getElementById('bookingActionCard');

    function calculateTotal() {
        const selectedDate = fechaSelect.options[fechaSelect.selectedIndex];
        const personas = parseInt(personasSelect.value) || 1;

        if (selectedDate && selectedDate.dataset.price && fechaSelect.value) {
            const pricePerPerson = parseFloat(selectedDate.dataset.price);
            const total = pricePerPerson * personas;

            totalAmount.textContent = '$' + total.toLocaleString('es-GT');
            totalBreakdown.textContent = `${personas} persona${personas > 1 ? 's' : ''} × $${pricePerPerson.toLocaleString('es-GT')}`;
            totalDiv.style.display = 'block';

            // Habilitar botón de reserva con efectos visuales
            bookNowBtn.disabled = false;
            bookNowBtn.classList.remove('btn-secondary');
            bookNowBtn.classList.add('btn-success');
            bookingActionCard.classList.add('ready');
            noDateAlert.style.display = 'none';

            // Scroll suave al botón para que el usuario lo vea
            setTimeout(() => {
                bookNowBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 300);
        } else {
            totalDiv.style.display = 'none';

            // Deshabilitar botón de reserva
            bookNowBtn.disabled = true;
            bookNowBtn.classList.remove('btn-success');
            bookNowBtn.classList.add('btn-secondary');
            bookingActionCard.classList.remove('ready');
            noDateAlert.style.display = 'block';
        }
    }

    // Calcular al cargar y cuando cambien los valores
    calculateTotal();
    fechaSelect.addEventListener('change', calculateTotal);
    personasSelect.addEventListener('change', calculateTotal);

// Función para manejar el envío del formulario
window.handleBookingSubmit = function(e) {
    const fechaSelect = document.getElementById('fecha_tour');
    const personasSelect = document.getElementById('numero_personas');
    const submitBtn = document.getElementById('bookNowBtn');
    const form = document.getElementById('bookingForm');

    // Validar que se seleccionó una fecha
    if (!fechaSelect.value || fechaSelect.value === '') {
        e.preventDefault();
        alert('⚠️ Por favor selecciona una fecha para el tour.');
        fechaSelect.focus();
        return false;
    }

    // Validar cupos disponibles
    const selectedOption = fechaSelect.options[fechaSelect.selectedIndex];
    const available = parseInt(selectedOption.dataset.available) || 0;
    const requested = parseInt(personasSelect.value) || 1;

    if (requested > available) {
        e.preventDefault();
        alert(`⚠️ Solo hay ${available} cupo(s) disponible(s) para esta fecha.\nPor favor selecciona ${available} o menos personas.`);
        personasSelect.focus();
        return false;
    }

    // Mostrar indicador de carga
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando reserva...';

    // Debug: Mostrar datos que se enviarán
    console.log('📤 Enviando reserva:', {
        tour_id: form.tour_id.value,
        disponibilidad_id: fechaSelect.value,
        numero_personas: personasSelect.value,
        action: form.action
    });

    // El formulario se enviará normalmente
    return true;
}

// Función para enviar el formulario de reserva desde botones externos (modal, etc)
window.submitBookingForm = function() {
    const fechaSelect = document.getElementById('fecha_tour');
    const personasSelect = document.getElementById('numero_personas');
    const bookNowBtn = document.getElementById('bookNowBtn');
    const form = document.getElementById('bookingForm');

    // Validar que se seleccionó una fecha
    if (!fechaSelect || !fechaSelect.value || fechaSelect.value === '') {
        alert('⚠️ Por favor selecciona una fecha para el tour primero.');

        // Cerrar modal si está abierto
        const modal = document.querySelector('.modal.show');
        if (modal && typeof bootstrap !== 'undefined') {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
        }

        // Hacer scroll a la selección de fecha
        fechaSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
        fechaSelect.focus();
        return false;
    }

    // Validar cupos disponibles
    const selectedOption = fechaSelect.options[fechaSelect.selectedIndex];
    const available = parseInt(selectedOption.dataset.available) || 0;
    const requested = parseInt(personasSelect.value) || 1;

    if (requested > available) {
        alert(`⚠️ Solo hay ${available} cupo(s) disponible(s) para esta fecha.\nPor favor selecciona ${available} o menos personas.`);
        personasSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
        personasSelect.focus();
        return false;
    }

    // Cerrar modal si está abierto
    const modal = document.querySelector('.modal.show');
    if (modal && typeof bootstrap !== 'undefined') {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) bsModal.hide();
    }

    // Mostrar indicador de carga en el botón
    if (bookNowBtn) {
        bookNowBtn.disabled = true;
        bookNowBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando reserva...';
    }

    // Debug
    console.log('📤 Enviando reserva desde botón externo:', {
        tour_id: form.tour_id.value,
        disponibilidad_id: fechaSelect.value,
        numero_personas: personasSelect.value,
        action: form.action
    });

    // Enviar el formulario
    form.submit();
    return true;
}
});
</script>

<style>
#noDateAlert {
    animation: fadeInOut 2s infinite;
}

@keyframes fadeInOut {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

/* Animación de pulse para el botón cuando está habilitado */
.pulse-button:not(:disabled) {
    animation: buttonPulse 2s infinite;
    box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7) !important;
}

@keyframes buttonPulse {
    0% {
        box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.7);
    }
    50% {
        box-shadow: 0 0 0 10px rgba(25, 135, 84, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(25, 135, 84, 0);
    }
}

#bookingActionCard {
    transition: all 0.3s ease;
}

#bookingActionCard.ready {
    border-width: 3px;
    box-shadow: 0 0 20px rgba(25, 135, 84, 0.3);
}

.btn:disabled {
    cursor: not-allowed;
    opacity: 0.65;
}

.timeline-item {
    border-left: 2px solid #dee2e6;
    padding-left: 1rem;
    margin-left: 1rem;
}

.timeline-item:last-child {
    border-left: none;
}

/* ===== Gallery Grid ===== */
.pdp-gallery {
  position: relative;
  background: #111;
  overflow: hidden;
  line-height: 0; /* remove inline gaps */
  border-radius: 0 0 24px 24px;
  max-width: 1180px;
  margin: -10px auto 0;
  box-shadow: 0 18px 40px rgba(17,24,39,0.12);
}
.pdp-gallery__grid {
  display: grid;
  gap: 3px;
}
/* 1 image */
.pdp-gallery__grid--1 {
  grid-template-columns: 1fr;
  grid-template-rows: 360px;
}
/* 2 images */
.pdp-gallery__grid--2 {
  grid-template-columns: 3fr 2fr;
  grid-template-rows: 360px;
}
/* 3 images */
.pdp-gallery__grid--3 {
  grid-template-columns: 3fr 2fr;
  grid-template-rows: 180px 180px;
}
.pdp-gallery__grid--3 .pdp-gallery__cell--0 { grid-row: 1 / 3; }
/* 4 images */
.pdp-gallery__grid--4 {
  grid-template-columns: 3fr 1.25fr 1.25fr;
  grid-template-rows: 180px 180px;
}
.pdp-gallery__grid--4 .pdp-gallery__cell--0 { grid-row: 1 / 3; }
.pdp-gallery__grid--4 .pdp-gallery__cell--3 { grid-column: 2 / 4; }
/* 5 images — Airbnb style */
.pdp-gallery__grid--5 {
  grid-template-columns: 3fr 1.25fr 1.25fr;
  grid-template-rows: 180px 180px;
}
.pdp-gallery__grid--5 .pdp-gallery__cell--0 { grid-row: 1 / 3; }
/* cells 1-4 auto-fill the right 2×2 */

.pdp-gallery__cell {
  overflow: hidden;
  position: relative;
  cursor: zoom-in;
  background: #1a1a1a;
}
.pdp-gallery__cell img {
  width: 100%; height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.4s ease;
  line-height: normal;
}
.pdp-gallery__cell:hover img { transform: scale(1.04); }

.pdp-gallery__more-overlay {
  position: absolute; inset: 0;
  background: rgba(0,0,0,0.52);
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.1rem; font-weight: 700;
  line-height: normal;
  letter-spacing: 0.02em;
}
.pdp-gallery__see-all {
  position: absolute;
  bottom: 0.85rem; right: 0.85rem;
  background: white;
  border: 1.5px solid #ccc;
  border-radius: 8px;
  padding: 0.38rem 0.8rem;
  font-size: 0.74rem;
  font-weight: 600;
  cursor: pointer;
  transition: box-shadow 0.2s, border-color 0.2s;
  z-index: 5;
  color: #111;
  line-height: normal;
}
.pdp-gallery__see-all:hover {
  box-shadow: 0 4px 18px rgba(0,0,0,0.2);
  border-color: #999;
}
@media (max-width: 767px) {
  .pdp-gallery {
    border-radius: 0 0 18px 18px;
    margin-top: -8px;
  }
  .pdp-gallery__grid--3,
  .pdp-gallery__grid--4,
  .pdp-gallery__grid--5 {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 170px 110px;
  }
  .pdp-gallery__grid--3 .pdp-gallery__cell--0,
  .pdp-gallery__grid--4 .pdp-gallery__cell--0,
  .pdp-gallery__grid--5 .pdp-gallery__cell--0 {
    grid-column: 1 / 3;
    grid-row: 1;
  }
  .pdp-gallery__grid--4 .pdp-gallery__cell--3 { grid-column: auto; }
  .pdp-gallery__grid--4 .pdp-gallery__cell--3,
  .pdp-gallery__grid--5 .pdp-gallery__cell--4 { display: none; }
}

/* ===== Fullscreen Lightbox ===== */
.pdp-lb-modal .modal-fullscreen { max-width: 100%; }
.pdp-lb-modal .modal-body { min-height: 100dvh; }
.pdp-lb-close {
  position: absolute; top: 1rem; right: 1rem; z-index: 10;
  background: rgba(255,255,255,0.1); border: none; color: white;
  width: 44px; height: 44px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem; cursor: pointer; transition: background 0.2s;
}
.pdp-lb-close:hover { background: rgba(255,255,255,0.25); }
.pdp-lb-counter {
  position: absolute; top: 1.1rem; left: 50%; transform: translateX(-50%);
  color: rgba(255,255,255,0.6); font-size: 0.82rem; z-index: 10;
  white-space: nowrap;
}
.pdp-lb-img-wrap {
  display: flex; align-items: center; justify-content: center;
  width: 100%;
  height: calc(100dvh - 110px);
  padding: 3rem 80px 0;
}
.pdp-lb-img-wrap img {
  max-width: 100%; max-height: 100%;
  object-fit: contain;
  display: block;
  border-radius: 4px;
}
.pdp-lb-btn {
  position: absolute; top: 50%; transform: translateY(-50%);
  background: rgba(255,255,255,0.1); border: none; color: white;
  width: 52px; height: 52px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem; cursor: pointer; transition: background 0.2s; z-index: 10;
}
.pdp-lb-btn:hover { background: rgba(255,255,255,0.28); }
.pdp-lb-btn--prev { left: 1rem; }
.pdp-lb-btn--next { right: 1rem; }
.pdp-lb-strip {
  position: absolute; bottom: 0.8rem; left: 50%; transform: translateX(-50%);
  display: flex; gap: 6px; z-index: 10;
  max-width: calc(100vw - 120px); overflow-x: auto; padding: 4px;
  scrollbar-width: none;
}
.pdp-lb-strip::-webkit-scrollbar { display: none; }
.pdp-lb-strip-thumb {
  width: 60px; height: 42px;
  border-radius: 5px; object-fit: cover; flex-shrink: 0;
  cursor: pointer; opacity: 0.45;
  border: 2px solid transparent;
  transition: opacity 0.2s, border-color 0.2s;
}
.pdp-lb-strip-thumb:hover { opacity: 0.8; }
.pdp-lb-strip-thumb.active { opacity: 1; border-color: white; }

.col-lg-5 .sticky-top,
.col-lg-4 .sticky-top {
    z-index: 10;
}

@media (max-width: 991.98px) {
    .col-lg-5 .sticky-top,
    .col-lg-4 .sticky-top {
        position: relative !important;
        top: 0 !important;
    }
}

/* ── Limpiar secciones obsoletas y duplicadas ─────────────────── */
/* Bloques marcados como obsoletos */
.pdp-obsolete-block { display: none !important; }
/* Bloques decorativos que duplican contenido de pdp-redesign-stage */
.pdp-story-shell    { display: none !important; }
.pdp-facts-band     { display: none !important; }
.pdp-selling-points { display: none !important; }
/* Calendario y política duplicados */
.pdp-framed-block   { display: none !important; }
.pdp-policy-card    { display: none !important; }
/* Sección incluye/no-incluye antigua (ya cubierta por pdp-redesign-columns) */
.includes-excludes-section { display: none !important; }
/* Tarjetas antiguas Bootstrap que duplican pdp-redesign-stage */
.pdp-dup-card       { display: none !important; }
/* Ocultar mini-resumen de reseñas duplicado encima de la galería */
.review-highlights-wrap { display: none !important; }
/* ────────────────────────────────────────────────────────────── */
</style>

<?php if (!empty($hasCoords) && $hasCoords): ?>
<!-- Leaflet CSS/JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
window.__NEARBY__ = <?= json_encode($nearby_geo ?? []) ?>;
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const target = document.getElementById('location');
  let inited = false;
  function initMap(){
    if (inited || typeof L === 'undefined') return; inited = true;
    const map = L.map('map');
    const lat = parseFloat(<?= json_encode($lat) ?>);
    const lng = parseFloat(<?= json_encode($lng) ?>);
    const main = L.marker([lat, lng]);
    let cluster;
    const pts = (window.__NEARBY__||[]).filter(p=>p && p.lat && p.lng);
    if (pts.length && typeof L.markerClusterGroup === 'function') {
      cluster = L.markerClusterGroup({ showCoverageOnHover: false });
    }
    if (cluster) cluster.addLayer(main); else main.addTo(map);
    main.bindPopup('<?= htmlspecialchars($tour['nombre']) ?>');
    if (pts.length) {
      pts.forEach(p=>{
        const m = L.marker([p.lat,p.lng]);
        m.bindPopup(`<strong>${p.name}</strong><br>$${Math.round(p.price).toLocaleString('es-MX')} USD<br><a href=\"${p.url}\">Ver detalle</a>`);
        if (cluster) { cluster.addLayer(m); } else { m.addTo(map); }
      });
    }
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);
    if (cluster) { map.addLayer(cluster); }
    if (pts.length) {
      const bounds = L.latLngBounds([[lat,lng], ...pts.map(p=>[p.lat,p.lng])]);
      map.fitBounds(bounds.pad(0.2));
    } else {
      map.setView([lat, lng], 12);
    }
  }
  if (target && 'IntersectionObserver' in window){
    const io = new IntersectionObserver((entries)=>{
      entries.forEach(e=>{ if (e.isIntersecting){ initMap(); io.disconnect(); } });
    }, { rootMargin: '100px' });
    io.observe(target);
  } else {
    initMap();
  }
});
</script>
<?php endif; ?>

<!-- Lightbox Modal -->
<div class="modal fade pdp-lb-modal" id="lightboxModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen">
    <div class="modal-content bg-black border-0">
      <div class="modal-body p-0 d-flex flex-column align-items-center justify-content-center position-relative">
        <!-- Close -->
        <button type="button" class="pdp-lb-close" data-bs-dismiss="modal" aria-label="Cerrar">
          <i class="fas fa-times"></i>
        </button>
        <!-- Counter -->
        <div class="pdp-lb-counter" id="lightboxCounter">1 / 1</div>
        <!-- Main image -->
        <div class="pdp-lb-img-wrap">
          <img id="lightboxImage" src="" alt="Imagen">
        </div>
        <!-- Prev / Next -->
        <button type="button" class="pdp-lb-btn pdp-lb-btn--prev" id="lightboxPrev" aria-label="Anterior">
          <i class="fas fa-chevron-left"></i>
        </button>
        <button type="button" class="pdp-lb-btn pdp-lb-btn--next" id="lightboxNext" aria-label="Siguiente">
          <i class="fas fa-chevron-right"></i>
        </button>
        <!-- Thumbnail strip -->
        <div class="pdp-lb-strip" id="lightboxStrip"></div>
      </div>
    </div>
  </div>
</div>

<!-- DatePicker Modal -->
<div class="modal fade" id="datePickerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-calendar-alt me-2"></i>Selecciona una fecha</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="dpPrev" aria-label="Mes anterior" title="Mes anterior"><i class="fas fa-chevron-left"></i></button>
            <div id="dpMonthLabel" class="fw-bold"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="dpNext" aria-label="Mes siguiente" title="Mes siguiente"><i class="fas fa-chevron-right"></i></button>
        </div>
        <div id="dpGrid" class="calendar-grid"></div>
        <small class="text-muted d-block mt-2">Haz clic en un día disponible para seleccionarlo.</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<style>
.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 6px;
}
.calendar-grid .day-name { font-size: .75rem; color: #6c757d; text-align: center; }
.calendar-grid .day { height: 46px; display:flex; align-items:center; justify-content:center; border-radius:6px; cursor: default; font-size: .9rem; }
.calendar-grid .day.available { cursor: pointer; background:#f8f9fa; border:1px solid #e9ecef; flex-direction: column; padding:2px; }
.calendar-grid .day.available:hover { background:#e9f5ff; border-color:#b6e0fe; }
.calendar-grid .day.unavailable { color:#ced4da; }
.calendar-grid .day.today { border:1px dashed #0d6efd; }
.calendar-grid .price-hint { font-size: .65rem; color: #6c757d; line-height: 1; margin-top: 2px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
(function() {
  // Lightbox logic
  <?php
  // Prepare images array for lightbox
  $lightboxImages = [];
  if (!empty($tour['imagen_principal'])) {
      $lightboxImages[] = Helpers::tourImage($tour['imagen_principal'], 'images/default-destination.jpg');
  }
  // Usar la variable $gallery ya procesada al inicio del archivo
  if (!empty($gallery)) {
      foreach (array_slice($gallery, 0, 10) as $img) {
          if (!empty($img)) {
              // Las URLs ya vienen completas, usarlas directamente
              $lightboxImages[] = $img;
          }
      }
  }
  ?>
  const images = <?= json_encode(array_values(array_unique($lightboxImages))) ?>;
  let lbIndex = 0;
  const lbModalEl = document.getElementById('lightboxModal');
  const lbModal = (lbModalEl && typeof bootstrap !== 'undefined') ? new bootstrap.Modal(lbModalEl) : null;
  const lbImg = document.getElementById('lightboxImage');
  const lbPrev = document.getElementById('lightboxPrev');
  const lbNext = document.getElementById('lightboxNext');
  const lbCounter = document.getElementById('lightboxCounter');
  const lbStrip = document.getElementById('lightboxStrip');

  function updateLightboxDisplay() {
    lbImg.src = images[lbIndex];
    if (lbCounter) lbCounter.textContent = (lbIndex + 1) + ' / ' + images.length;
    if (lbStrip) {
      lbStrip.innerHTML = images.map((src, i) =>
        `<img src="${src}" class="pdp-lb-strip-thumb${i === lbIndex ? ' active' : ''}"
              onclick="lbJump(${i})" loading="lazy" draggable="false">`
      ).join('');
      // Scroll active thumb into view
      const active = lbStrip.querySelector('.active');
      if (active) active.scrollIntoView({ inline: 'center', behavior: 'smooth' });
    }
  }
  window.lbJump = function(i) { lbIndex = i; updateLightboxDisplay(); };
  function openLightbox(index) {
    lbIndex = Math.min(index, images.length - 1);
    updateLightboxDisplay();
    if (lbModal) lbModal.show();
  }
  function move(delta) {
    lbIndex = (lbIndex + delta + images.length) % images.length;
    updateLightboxDisplay();
  }
  if (lbPrev) lbPrev.addEventListener('click', () => move(-1));
  if (lbNext) lbNext.addEventListener('click', () => move(1));
  document.addEventListener('keydown', (e) => {
    if (!lbModalEl || !lbModalEl.classList.contains('show')) return;
    if (e.key === 'ArrowLeft') move(-1);
    if (e.key === 'ArrowRight') move(1);
    if (e.key === 'Escape' && lbModal) lbModal.hide();
  });

  // DatePicker logic
  const availability = <?php 
    $availJs = [];
    if (!empty($availability)) {
        foreach ($availability as $d) {
            $availJs[] = [
                'id' => $d['id'],
                'date' => $d['fecha_salida'],
                'available' => (int)$d['cupos_disponibles'] - (int)$d['cupos_reservados'],
                'price' => (float)($d['precio_especial'] ?: ($tour['precio_descuento'] ?: $tour['precio']))
            ];
        }
    }
    echo json_encode($availJs);
  ?>;

  if (availability.length) {
    const months = Array.from(new Set(availability.map(a => a.date.substring(0,7)))).sort();
    let monthIndex = 0;
    const dpGrid = document.getElementById('dpGrid');
    const dpMonthLabel = document.getElementById('dpMonthLabel');
    const dpPrev = document.getElementById('dpPrev');
    const dpNext = document.getElementById('dpNext');
    const fechaSelect = document.getElementById('fecha_tour');
    const totalDiv = document.getElementById('totalCalculation');
    const totalAmount = document.getElementById('totalAmount');
    const personasSelect = document.getElementById('numero_personas');

    function renderMonth() {
      const ym = months[monthIndex];
      const [y, m] = ym.split('-').map(n => parseInt(n,10));
      const first = new Date(y, m-1, 1);
      const startDay = (first.getDay() + 6) % 7; // Monday first
      const daysInMonth = new Date(y, m, 0).getDate();
      const availMap = {};
      availability.forEach(a => { if (a.date.startsWith(ym)) availMap[a.date]=a; });
      dpMonthLabel.textContent = first.toLocaleString('es-ES', { month: 'long', year: 'numeric' });
      dpGrid.innerHTML = '';
      // day names
      ['L','M','X','J','V','S','D'].forEach(n => {
        const dn = document.createElement('div'); dn.textContent=n; dn.className='day-name'; dpGrid.appendChild(dn);
      });
      // blanks
      for (let i=0;i<startDay;i++){ const b=document.createElement('div'); b.className='day'; dpGrid.appendChild(b); }
      // days
      for (let d=1; d<=daysInMonth; d++){
        const dateStr = `${y}-${String(m).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const cell = document.createElement('div');
        const label = document.createElement('div'); label.textContent = d;
        const today = new Date(); const cellDate = new Date(y, m-1, d);
        let cls = 'day';
        if (availMap[dateStr] && availMap[dateStr].available>0 && cellDate>=new Date(today.getFullYear(), today.getMonth(), today.getDate())) {
          cls += ' available';
          // price hint
          const hint = document.createElement('div');
          hint.className = 'price-hint';
          hint.textContent = '$' + Math.round(availMap[dateStr].price).toLocaleString('es-MX');
          cell.appendChild(label);
          cell.appendChild(hint);
          cell.addEventListener('click', () => {
            // select corresponding option
            if (fechaSelect) {
              fechaSelect.value = String(availMap[dateStr].id);
              // trigger total calc
              const pricePerPerson = availMap[dateStr].price;
              const personas = parseInt(personasSelect.value)||1;
              const total = pricePerPerson * personas;
              if (totalDiv && totalAmount){ totalDiv.style.display='block'; totalAmount.textContent = '$' + total.toLocaleString('es-GT'); }
              const modalEl = document.getElementById('datePickerModal');
              if (modalEl && typeof bootstrap !== 'undefined') {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
              }
            }
          });
        } else {
          cls += ' unavailable';
          cell.appendChild(label);
        }
        // today outline
        const now = new Date(); if (cellDate.toDateString() === new Date(now.getFullYear(), now.getMonth(), now.getDate()).toDateString()) cls += ' today';
        cell.className = cls;
        dpGrid.appendChild(cell);
      }
      // nav buttons
      dpPrev.disabled = monthIndex===0; dpNext.disabled = monthIndex===months.length-1;
    }
    renderMonth();
    dpPrev.addEventListener('click', ()=>{ if (monthIndex>0){ monthIndex--; renderMonth(); } });
    dpNext.addEventListener('click', ()=>{ if (monthIndex<months.length-1){ monthIndex++; renderMonth(); } });

    // Autoscroll + toast on date/person selection from native selects
    function showToast(msg){
      try{
        const containerId = 'public-toast-container';
        let c = document.getElementById(containerId);
        if (!c){
          c = document.createElement('div');
          c.id = containerId;
          c.className = 'toast-container position-fixed top-0 end-0 p-3';
          c.style.zIndex = 1080; document.body.appendChild(c);
        }
        const el = document.createElement('div');
        el.className = 'toast align-items-center text-bg-primary border-0';
        el.setAttribute('role','status'); el.setAttribute('aria-live','polite'); el.setAttribute('aria-atomic','true');
        el.innerHTML = '<div class="d-flex"><div class="toast-body">'+ msg +'</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button></div>';
        c.appendChild(el);
        if (typeof bootstrap !== 'undefined') {
          const t = new bootstrap.Toast(el, { autohide: true, delay: 2200 });
          t.show(); el.addEventListener('hidden.bs.toast', ()=> el.remove());
        }
      }catch(e){}
    }
    function smoothFocus(){ if (totalDiv){ totalDiv.style.display='block'; totalDiv.scrollIntoView({ behavior:'smooth', block:'center' }); } }
    fechaSelect?.addEventListener('change', function(){ if (this.value){ smoothFocus(); const txt = this.options[this.selectedIndex]?.textContent?.trim() || 'fecha seleccionada'; showToast('Fecha: ' + txt); } });
    personasSelect?.addEventListener('change', function(){ smoothFocus(); showToast('Personas: ' + this.value); });
  }
})();
});
</script>

<!-- Premium sticky mobile CTA -->
<div class="pdp-sticky-mobile d-lg-none" id="stickyCTAMobile">
    <div class="pdp-sticky-mobile__price">
        <?php if ($__precios_grupo): ?>
            <small>Desde</small>
            <strong>$<?= number_format(min(array_column($__precios_grupo, 'precio')), 0) ?> USD</strong>
        <?php elseif ($__price_display): ?>
            <small>Desde</small>
            <strong><?= Helpers::formatPrice($__price_display) ?> USD</strong>
        <?php endif; ?>
    </div>
    <button type="button" class="btn btn-success fw-semibold flex-grow-1" onclick="scrollToBookingForm()">
        <i class="fas fa-calendar-check me-1"></i><?= !empty($tour['es_privado']) ? 'Reservar grupo privado' : 'Reservar ahora' ?>
    </button>
    <a href="https://wa.me/<?= str_replace(['+', ' ', '-'], '', Config::SOCIAL_WHATSAPP ?? '+50212345678') ?>?text=Hola,%20me%20interesa%20el%20tour%20<?= urlencode($tour['nombre']) ?>"
       target="_blank" class="btn btn-outline-success pdp-sticky-mobile__wa">
        <i class="fab fa-whatsapp"></i>
    </a>
</div>

<style>
/* Sticky CTA Mobile Styles */
.sticky-cta-mobile {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-top: 1px solid #eee;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
    z-index: 1030;
    padding: 0.75rem 1rem 1rem;
    transition: transform 0.3s ease;
    transform: translateY(0);
}

.sticky-cta-mobile.hidden {
    transform: translateY(100%);
}

.sticky-cta-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.pricing-info {
    display: flex;
    flex-direction: column;
}

.pricing-info .price {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.original-price {
    text-decoration: line-through;
    color: #6c757d;
    font-size: 0.9rem;
}

.discount-price, .current-price {
    color: #0d6efd;
    font-weight: 700;
    font-size: 1.1rem;
}

.per-person {
    color: #6c757d;
    font-size: 0.75rem;
}

.cta-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-reserve {
    padding: 0.5rem 1.5rem;
    font-weight: 600;
    border-radius: 25px;
    flex: 1;
    max-width: 150px;
}

.btn-whatsapp {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.quick-info-badges {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.info-badge {
    background: rgba(13, 110, 253, 0.1);
    color: #0d6efd;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.info-badge i {
    font-size: 0.6rem;
}

/* Animation for showing/hiding */
@keyframes slideUp {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}

.sticky-cta-mobile.show {
    animation: slideUp 0.3s ease;
}

/* Avoid content being hidden behind sticky bar (mobile only) */
@media (max-width: 991.98px) {
    body {
        padding-bottom: 90px;
    }
}
</style>

<script>
// Sticky CTA Mobile functionality
document.addEventListener('DOMContentLoaded', function() {
    const stickyCTA = document.getElementById('stickyCTAMobile');
    const bookingSection = document.getElementById('bookingSidebarCol');
    
    if (stickyCTA && bookingSection) {
        let lastScrollY = window.scrollY;
        let ticking = false;
        
        function updateStickyBar() {
            const currentScrollY = window.scrollY;
            const bookingSectionRect = bookingSection.getBoundingClientRect();
            const isBookingSectionVisible = bookingSectionRect.top < window.innerHeight && bookingSectionRect.bottom > 0;
            
            // Hide sticky bar when booking section is visible or when scrolling up quickly
            if (isBookingSectionVisible || (currentScrollY < lastScrollY && currentScrollY < 100)) {
                stickyCTA.classList.add('hidden');
            } else if (currentScrollY > 200) {
                stickyCTA.classList.remove('hidden');
            }
            
            lastScrollY = currentScrollY;
            ticking = false;
        }
        
        function requestTick() {
            if (!ticking) {
                requestAnimationFrame(updateStickyBar);
                ticking = true;
            }
        }
        
        // Initial state - hide if booking section is visible
        updateStickyBar();
        
        // Listen for scroll events
        window.addEventListener('scroll', requestTick, { passive: true });
    }
});

// Scroll to booking section function
function scrollToBooking() {
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
        
        // Focus on first form element for better UX
        setTimeout(() => {
            const firstInput = bookingForm.querySelector('select, input');
            if (firstInput) firstInput.focus();
        }, 500);
    }
}

// RNPL Functionality
document.addEventListener('DOMContentLoaded', function() {
    const fechaSelect = document.getElementById('fecha_tour');
    const personasSelect = document.getElementById('numero_personas');
    const enableRnplCheckbox = document.getElementById('enableRnpl');
    const rnplSection = document.getElementById('rnplSection');
    const paymentMethodSection = document.getElementById('paymentMethodSection');
    const bookNowBtn = document.getElementById('bookNowBtn');
    const bookRnplBtn = document.getElementById('bookRnplBtn');
    const totalCalculation = document.getElementById('totalCalculation');
    const totalAmount = document.getElementById('totalAmount');
    const rnplHoldAmount = document.getElementById('rnplHoldAmount');
    const rnplHoldDisplay = document.getElementById('rnplHoldDisplay');
    
    let currentTotal = 0;
    let rnplEligible = false;
    
    // Check RNPL eligibility when date/persons change
    function checkRnplEligibility() {
        const selectedOption = fechaSelect.options[fechaSelect.selectedIndex];
        const selectedPersonas = parseInt(personasSelect.value) || 1;
        
        if (selectedOption && selectedOption.value) {
            const price = parseFloat(selectedOption.dataset.price) || 0;
            currentTotal = price * selectedPersonas;
            
            // Check if tour is at least 72 hours in advance
            const tourDate = new Date(selectedOption.textContent.split(' — ')[0].split('/').reverse().join('-'));
            const now = new Date();
            const hoursDiff = (tourDate.getTime() - now.getTime()) / (1000 * 60 * 60);
            
            rnplEligible = hoursDiff >= 72;
            
            // Update displays
            if (totalAmount) totalAmount.textContent = '$' + currentTotal.toLocaleString('en-US');
            if (totalCalculation) totalCalculation.style.display = 'block';
            
            // Show/hide RNPL section
            if (rnplEligible && currentTotal > 0) {
                rnplSection.style.display = 'block';
                // Mostrar el total completo, no el hold amount (no hay pago ahora)
                if (rnplHoldAmount) rnplHoldAmount.textContent = '$' + currentTotal.toLocaleString('en-US');
                if (rnplHoldDisplay) rnplHoldDisplay.textContent = '$' + currentTotal.toLocaleString('en-US');
            } else {
                rnplSection.style.display = 'none';
                enableRnplCheckbox.checked = false;
                toggleRnplMode(false);
            }
        } else {
            rnplSection.style.display = 'none';
            if (totalCalculation) totalCalculation.style.display = 'none';
        }
    }
    
    // Toggle RNPL mode
    function toggleRnplMode(enabled) {
        if (enabled && !rnplEligible) {
            enableRnplCheckbox.checked = false;
            alert('RNPL solo está disponible para tours con al menos 72 horas de anticipación.');
            return;
        }

        if (enabled) {
            // RNPL NO requiere pago inmediato, ocultar sección de métodos de pago
            paymentMethodSection.style.display = 'none';
            bookNowBtn.style.display = 'none';
            bookRnplBtn.style.display = 'block';
        } else {
            paymentMethodSection.style.display = 'none';
            bookNowBtn.style.display = 'block';
            bookRnplBtn.style.display = 'none';
            bookNowBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i> COMPLETAR RESERVA';
        }
    }
    
    // Event listeners
    fechaSelect.addEventListener('change', checkRnplEligibility);
    personasSelect.addEventListener('change', checkRnplEligibility);
    enableRnplCheckbox.addEventListener('change', function() {
        toggleRnplMode(this.checked);
    });
    
    // Handle RNPL booking
    bookRnplBtn.addEventListener('click', function() {
        if (!rnplEligible) {
            alert('RNPL no está disponible para esta fecha.');
            return;
        }

        // Show modal confirmation
        const holdAmount = Math.round(currentTotal * 0.1);
        const remaining = currentTotal - holdAmount;

        showRnplConfirmationModal(currentTotal);
    });
    
    // Process RNPL booking with contact data
    function processRnplBookingWithData(clienteNombre, clienteEmail, clienteTelefono) {
        const formData = new FormData();

        // Get form data
        const form = document.getElementById('bookingForm');
        const formInputs = form.querySelectorAll('input, select, textarea');

        formInputs.forEach(input => {
            if (input.name && input.value && input.name !== 'payment_method') {
                formData.append(input.name, input.value);
            }
        });

        // Add contact data
        formData.append('cliente_nombre', clienteNombre);
        formData.append('cliente_email', clienteEmail);
        formData.append('cliente_telefono', clienteTelefono);

        // Add RNPL specific data (NO payment method - no hay pago ahora)
        formData.append('payment_type', 'rnpl');
        formData.append('enable_rnpl', '1');
        formData.append('total_amount', currentTotal);

        // Show loading
        bookRnplBtn.disabled = true;
        bookRnplBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Confirmando reserva...';

        // Debug: Log what we're sending
        console.log('FormData entries:');
        for (let pair of formData.entries()) {
            console.log(pair[0] + ': ' + pair[1]);
        }

        // Send request al endpoint RNPL using BASE_URL
        const apiUrl = BASE_URL + '?route=rnpl/process';
        console.log('BASE_URL:', BASE_URL);
        console.log('API URL:', apiUrl);

        fetch(apiUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Check if response is JSON or HTML
            const contentType = response.headers.get('content-type');
            console.log('Response status:', response.status);
            console.log('Content-Type:', contentType);

            if (contentType && contentType.includes('application/json')) {
                // Even if content-type is JSON, response might still have PHP errors prepended
                return response.text().then(text => {
                    // Check if response starts with valid JSON
                    if (text.trim().startsWith('{') || text.trim().startsWith('[')) {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            console.error('Response text:', text.substring(0, 1000));
                            throw new Error('Error al parsear JSON: ' + e.message);
                        }
                    } else {
                        // Response has PHP errors/warnings before JSON
                        console.error('Response contains PHP errors/warnings:', text.substring(0, 1000));
                        throw new Error('El servidor devolvió errores de PHP. Revisa la consola para más detalles.');
                    }
                });
            } else {
                // Try to get response text for debugging
                return response.text().then(text => {
                    console.error('Non-JSON response:', text.substring(0, 500));
                    throw new Error('El servidor no devolvió JSON. Respuesta: ' + text.substring(0, 100));
                });
            }
        })
        .then(data => {
            console.log('Server response:', data);

            if (data.success) {
                // Show success toast
                showSuccessToast('✅ Reserva confirmada', 'Tu lugar ha sido reservado. Redirigiendo...');

                // Redirect to RNPL confirmation after a brief delay
                setTimeout(() => {
                    window.location.href = data.redirect_url;
                }, 1500);
            } else {
                const errorMsg = data.error || 'No se pudo procesar la reserva';
                console.error('Booking error:', errorMsg);
                showErrorToast('Error en reserva', errorMsg);
                bookRnplBtn.disabled = false;
                bookRnplBtn.innerHTML = '<i class="fas fa-calendar-plus me-2"></i> CONFIRMAR RESERVA (Pagas Después)';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showErrorToast('Error de conexión', error.message || 'No se pudo conectar con el servidor. Inténtalo de nuevo.');
            bookRnplBtn.disabled = false;
            bookRnplBtn.innerHTML = '<i class="fas fa-calendar-plus me-2"></i> CONFIRMAR RESERVA (Pagas Después)';
        });
    }
    
    // RNPL Confirmation Modal
    function showRnplConfirmationModal(totalAmount) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('rnplConfirmModal');
        if (!modal) {
            modal = createRnplConfirmModal();
        }

        // Update total amount in modal
        document.getElementById('rnplModalTotal').textContent = '$' + totalAmount.toLocaleString('en-US');

        // Show modal using Bootstrap's proper API
        const bsModal = new bootstrap.Modal(modal, {
            backdrop: 'static',
            keyboard: true,
            focus: false // Let us handle focus manually
        });

        // Handle focus management
        modal.addEventListener('shown.bs.modal', function() {
            // Focus on confirm button after modal is fully shown
            const confirmBtn = document.getElementById('confirmRnplBtn');
            if (confirmBtn) {
                setTimeout(() => confirmBtn.focus(), 150);
            }
        }, { once: true });

        // Remove focus before hiding to prevent aria-hidden warning
        modal.addEventListener('hide.bs.modal', function() {
            // Move focus back to body or trigger button
            if (document.activeElement && modal.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        }, { once: true });

        bsModal.show();
    }

    function createRnplConfirmModal() {
        const modal = document.createElement('div');
        modal.id = 'rnplConfirmModal';
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('aria-labelledby', 'rnplConfirmModalLabel');
        // Don't set aria-hidden manually - let Bootstrap handle it

        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-gradient text-white border-0" style="background: linear-gradient(135deg, #0d6efd, #0dcaf0);">
                        <h5 class="modal-title d-flex align-items-center" id="rnplConfirmModalLabel">
                            <i class="fas fa-calendar-check me-2" aria-hidden="true"></i>
                            Confirmar Reserva - Paga Después
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar modal de confirmación"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="text-center mb-4">
                            <div class="mb-3" aria-hidden="true">
                                <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                            </div>
                            <h6 class="fw-bold mb-3">¿Cómo funciona?</h6>
                        </div>

                        <div class="rnpl-features mb-4" role="list">
                            <div class="feature-item d-flex align-items-start mb-3" role="listitem">
                                <div class="feature-icon me-3" aria-hidden="true">
                                    <i class="fas fa-lock text-success fs-4"></i>
                                </div>
                                <div>
                                    <strong class="d-block mb-1">Tu lugar queda RESERVADO ahora</strong>
                                    <small class="text-muted">Aseguras tu lugar sin necesidad de pagar en este momento</small>
                                </div>
                            </div>

                            <div class="feature-item d-flex align-items-start mb-3" role="listitem">
                                <div class="feature-icon me-3" aria-hidden="true">
                                    <i class="fas fa-credit-card text-primary fs-4"></i>
                                </div>
                                <div>
                                    <strong class="d-block mb-1">Pagas el total más adelante</strong>
                                    <small class="text-muted">Total a pagar: <span class="text-primary fw-bold" id="rnplModalTotal">$0</span></small>
                                </div>
                            </div>

                            <div class="feature-item d-flex align-items-start mb-3" role="listitem">
                                <div class="feature-icon me-3" aria-hidden="true">
                                    <i class="fas fa-calendar-alt text-warning fs-4"></i>
                                </div>
                                <div>
                                    <strong class="d-block mb-1">Completa el pago 48h antes</strong>
                                    <small class="text-muted">Te enviaremos recordatorios para que no olvides completar tu reserva</small>
                                </div>
                            </div>

                            <div class="alert alert-info d-flex align-items-center mt-3 mb-0" role="alert">
                                <i class="fas fa-info-circle me-2 fs-5" aria-hidden="true"></i>
                                <div>
                                    <small class="mb-0"><strong>Sin cargos ahora:</strong> No se realizará ningún cargo a tu tarjeta en este momento</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Cancelar reserva">
                            <i class="fas fa-times me-1" aria-hidden="true"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" id="confirmRnplBtn" onclick="handleRnplConfirmation()" aria-label="Confirmar reserva con pago posterior">
                            <i class="fas fa-check-circle me-2" aria-hidden="true"></i> Confirmar Reserva
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        return modal;
    }

    // Handler for modal confirmation button
    window.handleRnplConfirmation = function() {
        // Close confirmation modal
        const confirmModal = bootstrap.Modal.getInstance(document.getElementById('rnplConfirmModal'));
        if (confirmModal) confirmModal.hide();

        // Show contact form modal
        setTimeout(() => {
            showContactFormModal();
        }, 300);
    };

    // Contact Form Modal
    function showContactFormModal() {
        let modal = document.getElementById('rnplContactFormModal');
        if (!modal) {
            modal = createContactFormModal();
        }

        const bsModal = new bootstrap.Modal(modal, {
            backdrop: 'static',
            keyboard: false,
            focus: false
        });

        // Handle focus management
        modal.addEventListener('shown.bs.modal', function() {
            // Focus on first input after modal is fully shown
            const firstInput = document.getElementById('rnpl_cliente_nombre');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 150);
            }
        }, { once: true });

        // Remove focus before hiding to prevent aria-hidden warning
        modal.addEventListener('hide.bs.modal', function() {
            if (document.activeElement && modal.contains(document.activeElement)) {
                document.activeElement.blur();
            }
        }, { once: true });

        bsModal.show();
    }

    function createContactFormModal() {
        const modal = document.createElement('div');
        modal.id = 'rnplContactFormModal';
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('aria-labelledby', 'rnplContactFormModalLabel');
        // Don't set aria-hidden manually - let Bootstrap handle it

        modal.innerHTML = `
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary text-white border-0">
                        <h5 class="modal-title" id="rnplContactFormModalLabel">
                            <i class="fas fa-user-circle me-2" aria-hidden="true"></i>
                            Tus Datos de Contacto
                        </h5>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted mb-4">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Por favor completa tus datos para confirmar tu reserva
                        </p>

                        <form id="rnplContactForm" novalidate>
                            <div class="mb-3">
                                <label for="rnpl_cliente_nombre" class="form-label">
                                    <i class="fas fa-user text-primary me-2"></i>Nombre Completo *
                                </label>
                                <input type="text"
                                       class="form-control form-control-lg"
                                       id="rnpl_cliente_nombre"
                                       name="cliente_nombre"
                                       placeholder="Ej: Juan Pérez"
                                       required
                                       autocomplete="name">
                                <div class="invalid-feedback">
                                    Por favor ingresa tu nombre completo
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="rnpl_cliente_email" class="form-label">
                                    <i class="fas fa-envelope text-primary me-2"></i>Correo Electrónico *
                                </label>
                                <input type="email"
                                       class="form-control form-control-lg"
                                       id="rnpl_cliente_email"
                                       name="cliente_email"
                                       placeholder="tu@email.com"
                                       required
                                       autocomplete="email">
                                <div class="invalid-feedback">
                                    Por favor ingresa un email válido
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="rnpl_cliente_telefono" class="form-label">
                                    <i class="fas fa-phone text-primary me-2"></i>Teléfono *
                                </label>
                                <input type="tel"
                                       class="form-control form-control-lg"
                                       id="rnpl_cliente_telefono"
                                       name="cliente_telefono"
                                       placeholder="+502 1234-5678"
                                       required
                                       autocomplete="tel">
                                <div class="invalid-feedback">
                                    Por favor ingresa tu número de teléfono
                                </div>
                            </div>

                            <div class="alert alert-light border d-flex align-items-start">
                                <i class="fas fa-shield-alt text-success fs-5 me-3 mt-1" aria-hidden="true"></i>
                                <small class="text-muted mb-0">
                                    <strong>Tus datos están seguros.</strong> Solo los usaremos para enviarte la confirmación de tu reserva y recordatorios de pago.
                                </small>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-0 bg-light">
                        <button type="button" class="btn btn-secondary" onclick="handleContactFormCancel()">
                            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Volver
                        </button>
                        <button type="button" class="btn btn-primary btn-lg" onclick="handleContactFormSubmit()">
                            <i class="fas fa-check-circle me-2" aria-hidden="true"></i> Confirmar y Reservar
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        return modal;
    }

    window.handleContactFormCancel = function() {
        const modal = bootstrap.Modal.getInstance(document.getElementById('rnplContactFormModal'));
        if (modal) modal.hide();

        // Reopen confirmation modal
        setTimeout(() => {
            showRnplConfirmationModal(currentTotal);
        }, 300);
    };

    window.handleContactFormSubmit = function() {
        const form = document.getElementById('rnplContactForm');

        // Validate form
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        // Get form values
        const nombre = document.getElementById('rnpl_cliente_nombre').value.trim();
        const email = document.getElementById('rnpl_cliente_email').value.trim();
        const telefono = document.getElementById('rnpl_cliente_telefono').value.trim();

        // Close modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('rnplContactFormModal'));
        if (modal) modal.hide();

        // Process booking with form data
        processRnplBookingWithData(nombre, email, telefono);
    };

    // Toast notification helpers
    function showSuccessToast(title, message) {
        showToastNotification(title, message, 'success');
    }

    function showErrorToast(title, message) {
        showToastNotification(title, message, 'danger');
    }

    function showInfoToast(title, message) {
        showToastNotification(title, message, 'info');
    }

    function showWarningToast(title, message) {
        showToastNotification(title, message, 'warning');
    }

    function showToastNotification(title, message, type = 'primary') {
        const toastContainer = document.getElementById('toastContainer') || createToastContainer();

        const toastId = 'toast-' + Date.now();
        const toast = document.createElement('div');
        toast.id = toastId;
        toast.className = `toast align-items-center text-bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        const iconMap = {
            'success': 'fa-check-circle',
            'danger': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle',
            'primary': 'fa-bell'
        };

        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <div class="d-flex align-items-start">
                        <i class="fas ${iconMap[type] || 'fa-bell'} me-3 mt-1" style="font-size: 1.5rem;"></i>
                        <div>
                            <strong class="d-block mb-1">${title}</strong>
                            <span>${message}</span>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        toastContainer.appendChild(toast);

        if (typeof bootstrap !== 'undefined') {
            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: 5000
            });

            toast.addEventListener('hidden.bs.toast', function() {
                toast.remove();
            });

            bsToast.show();
        }
    }

    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

    // Initial check
    checkRnplEligibility();
});
</script>

<!-- RNPL & Toast Styles -->
<style>
/* Toast Container */
#toastContainer {
    z-index: 9999 !important;
}

#toastContainer .toast {
    min-width: 300px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(10px);
}

#toastContainer .toast-body {
    padding: 1rem;
}

#toastContainer .toast-body i {
    opacity: 0.9;
}

/* Different toast types with better styling */
.toast.text-bg-success {
    background: linear-gradient(135deg, #198754, #20c997) !important;
}

.toast.text-bg-danger {
    background: linear-gradient(135deg, #dc3545, #e85d6a) !important;
}

.toast.text-bg-info {
    background: linear-gradient(135deg, #0dcaf0, #6edff6) !important;
}

.toast.text-bg-warning {
    background: linear-gradient(135deg, #ffc107, #ffcd39) !important;
    color: #000 !important;
}

.toast.text-bg-warning .btn-close {
    filter: invert(1);
}

/* RNPL Confirmation Modal */
#rnplConfirmModal .modal-dialog {
    display: flex !important;
    align-items: center !important;
    min-height: calc(100% - 1rem) !important;
    margin: 0.5rem auto !important;
}

@media (min-width: 576px) {
    #rnplConfirmModal .modal-dialog {
        min-height: calc(100% - 3.5rem) !important;
        margin: 1.75rem auto !important;
        max-width: 500px;
    }
}

#rnplConfirmModal .modal-content {
    border-radius: 16px;
    overflow: hidden;
    margin: auto;
}

#rnplConfirmModal .modal-header {
    padding: 1.5rem 1.5rem;
}

#rnplConfirmModal .modal-title {
    font-weight: 700;
    font-size: 1.25rem;
}

#rnplConfirmModal .modal-body {
    background: #ffffff;
}

#rnplConfirmModal .feature-item {
    padding: 0.75rem;
    border-radius: 10px;
    transition: all 0.2s ease;
}

#rnplConfirmModal .feature-item:hover {
    background: #f8f9fa;
    transform: translateX(5px);
}

#rnplConfirmModal .feature-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    border-radius: 12px;
    flex-shrink: 0;
}

#rnplConfirmModal .alert-info {
    border: none;
    background: linear-gradient(135deg, #e7f3ff, #cfe9ff);
    border-left: 4px solid #0dcaf0;
}

#rnplConfirmModal .modal-footer {
    padding: 1.25rem 1.5rem;
}

#rnplConfirmModal #confirmRnplBtn {
    background: linear-gradient(135deg, #0d6efd, #0dcaf0);
    border: none;
    font-weight: 600;
    padding: 0.75rem 2rem;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
    transition: all 0.3s ease;
}

#rnplConfirmModal #confirmRnplBtn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4);
}

#rnplConfirmModal .btn-secondary {
    background: #6c757d;
    border: none;
    font-weight: 500;
}

/* RNPL Contact Form Modal */
#rnplContactFormModal .modal-dialog {
    display: flex !important;
    align-items: center !important;
    min-height: calc(100% - 1rem) !important;
    margin: 0.5rem auto !important;
}

@media (min-width: 576px) {
    #rnplContactFormModal .modal-dialog {
        min-height: calc(100% - 3.5rem) !important;
        margin: 1.75rem auto !important;
        max-width: 540px;
    }
}

#rnplContactFormModal .modal-content {
    border-radius: 16px;
    overflow: hidden;
    margin: auto;
}

#rnplContactFormModal .modal-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, #0d6efd, #0dcaf0);
}

#rnplContactFormModal .form-control-lg {
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border-radius: 8px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

#rnplContactFormModal .form-control-lg:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
}

#rnplContactFormModal .form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

#rnplContactFormModal .invalid-feedback {
    display: none;
    font-size: 0.875rem;
    color: #dc3545;
    margin-top: 0.5rem;
}

#rnplContactFormModal .was-validated .form-control:invalid {
    border-color: #dc3545;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.5rem) center;
    background-size: calc(0.75em + 1rem) calc(0.75em + 1rem);
}

#rnplContactFormModal .was-validated .form-control:invalid ~ .invalid-feedback {
    display: block;
}

#rnplContactFormModal .btn-primary {
    background: linear-gradient(135deg, #0d6efd, #0dcaf0);
    border: none;
    font-weight: 600;
    padding: 0.75rem 2rem;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
    transition: all 0.3s ease;
}

#rnplContactFormModal .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4);
}

/* RNPL Section */
.rnpl-section .card {
    box-shadow: 0 4px 15px rgba(13, 202, 240, 0.15);
    transition: all 0.3s ease;
}

.rnpl-section .card:hover {
    box-shadow: 0 6px 20px rgba(13, 202, 240, 0.25);
    transform: translateY(-2px);
}

.rnpl-icon {
    background: rgba(13, 202, 240, 0.1);
    border-radius: 50%;
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.rnpl-benefits {
    padding: 0.5rem 0;
}

.form-check-input:checked {
    background-color: rgba(255, 255, 255, 0.9);
    border-color: rgba(255, 255, 255, 0.9);
}

.payment-methods .form-check {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    transition: all 0.2s ease;
}

.payment-methods .form-check:hover {
    border-color: #0d6efd;
    background: #f0f8ff;
}

.payment-methods .form-check-input:checked + .form-check-label {
    color: #0d6efd;
    font-weight: 500;
}

.booking-buttons {
    animation: fadeInUp 0.3s ease;
}

#bookRnplBtn {
    background: linear-gradient(135deg, #0d6efd, #0dcaf0);
    border: none;
    color: white;
    font-weight: 700;
    position: relative;
    box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
    overflow: hidden;
}

#bookRnplBtn:hover {
    background: linear-gradient(135deg, #0a58ca, #0bb5d4);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4);
}

#bookRnplBtn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.6s;
}

#bookRnplBtn:hover::before {
    left: 100%;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<?php if (!empty($tour['es_privado']) && !empty($tour['precios_grupo'])): ?>
<script>
(function() {
    const tramos = <?= json_encode(json_decode($tour['precios_grupo'], true)) ?>;

    function getPrecio(n) {
        for (const t of tramos) {
            const desde = t.desde ?? 1;
            const hasta = t.hasta ?? Infinity;
            if (n >= desde && n <= hasta) return t.precio;
        }
        return null;
    }

    function updatePrivatePrice() {
        const n = parseInt(document.getElementById('numero_personas')?.value) || 1;
        const precio = getPrecio(n);
        if (precio === null) return;
        const total = precio * n;

        // Info dentro del formulario
        const perEl = document.getElementById('private-price-per-person');
        const totEl = document.getElementById('private-price-total');
        if (perEl) perEl.textContent = '$' + precio.toLocaleString('es') + ' USD / persona';
        if (totEl) totEl.textContent = '$' + total.toLocaleString('es');

        // Estimado en el hero
        const heroLabel = document.getElementById('private-total-label');
        if (heroLabel) heroLabel.textContent =
            n + ' persona' + (n > 1 ? 's' : '') + ' × $' + precio.toLocaleString('es') +
            ' = $' + total.toLocaleString('es') + ' USD total';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const sel = document.getElementById('numero_personas');
        if (sel) {
            sel.addEventListener('change', updatePrivatePrice);
            updatePrivatePrice();
        }
    });
})();
</script>
<?php endif; ?>

<style>
/* =============================================
   PREMIUM TOUR DETAIL v2 — Design System
   Alineado con tokens del sitio (Inter + azul Bootstrap)
   ============================================= */
:root {
  --pdp-primary: #0d6efd;
  --pdp-primary-light: #0b5ed7;
  --pdp-accent: #f59e0b;
  --pdp-accent-light: #fff8e6;
  --pdp-bg: #fff;
  --pdp-card: #FFFFFF;
  --pdp-text: #212529;
  --pdp-muted: #6c757d;
  --pdp-border: #dee2e6;
  --pdp-shadow: 0 2px 8px rgba(0,0,0,0.08);
  --pdp-shadow-lg: 0 8px 32px rgba(0,0,0,0.14);
  --pdp-radius: 16px;
  --pdp-radius-sm: 10px;
}

/* ---- Hero ---- */
.pdp-hero-v2 {
  position: relative;
  height: 52vh;
  min-height: 320px;
  max-height: 460px;
  overflow: hidden;
}
.pdp-hero-v2__bg {
  position: absolute; inset: 0;
  background-size: cover;
  background-position: center;
}
.pdp-hero-v2__overlay {
  position: absolute; inset: 0;
  background: linear-gradient(
    to bottom,
    rgba(0,0,0,0.12) 0%,
    rgba(0,0,0,0.05) 25%,
    rgba(13,59,30,0.65) 65%,
    rgba(13,59,30,0.92) 100%
  );
}
.pdp-hero-v2__content {
  position: absolute;
  bottom: 0; left: 0; right: 0;
  padding: 1.35rem 1.25rem 1.75rem;
  color: white;
  max-width: 620px;
}
.pdp-hero-v2__breadcrumb {
  font-size: 0.72rem;
  color: rgba(255,255,255,0.7);
  margin-bottom: 0.55rem;
}
.pdp-hero-v2__breadcrumb a {
  color: rgba(255,255,255,0.8);
  text-decoration: none;
}
.pdp-hero-v2__breadcrumb a:hover { color: white; }
.pdp-hero-v2__title {
  font-family: 'Cormorant Garamond', Georgia, serif;
  font-size: clamp(1.6rem, 3.1vw, 2.7rem);
  font-weight: 600;
  line-height: 1.02;
  color: white;
  margin-bottom: 0.7rem;
  text-shadow: 0 2px 12px rgba(0,0,0,0.35);
}
.pdp-hero-v2__stats {
  display: flex; flex-wrap: wrap; gap: 0.8rem;
  margin-bottom: 0.7rem;
}
.pdp-hero-v2__stat {
  display: flex; align-items: center; gap: 0.4rem;
  color: rgba(255,255,255,0.9);
  font-size: 0.78rem;
}
.pdp-hero-v2__stat--gold {
  color: #F5C842;
  font-weight: 600;
}
.pdp-hero-v2__badges {
  display: flex; flex-wrap: wrap; gap: 0.45rem;
  margin-bottom: 0.55rem;
}
.pdp-hero-v2__badges span {
  background: rgba(255,255,255,0.15);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255,255,255,0.2);
  color: white;
  font-size: 0.68rem;
  padding: 0.28rem 0.65rem;
  border-radius: 20px;
}
.pdp-hero-v2__lede {
  max-width: 54ch;
  font-size: 0.84rem;
  line-height: 1.55;
  color: rgba(255,255,255,0.86);
  margin: 0 0 0.6rem;
  text-shadow: 0 2px 16px rgba(0,0,0,0.18);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.pdp-hero-v2__price-preview {
  display: inline-flex; align-items: center;
  background: rgba(191,155,48,0.9);
  color: #1A1A1A;
  font-size: 0.72rem;
  font-weight: 600;
  padding: 0.33rem 0.75rem;
  border-radius: 20px;
  margin-top: 0.25rem;
}
.pdp-hero-v2__thumbs {
  position: absolute;
  bottom: 1.5rem; right: 1.5rem;
  display: flex; gap: 0.5rem;
  display: none; /* hide on mobile, show on md+ */
}
@media (min-width: 768px) {
  .pdp-hero-v2__thumbs { display: flex; }
  .pdp-hero-v2__content { padding: 2.5rem; }
}
.pdp-hero-v2__thumb {
  width: 72px; height: 54px;
  border-radius: 8px;
  background-size: cover;
  background-position: center;
  border: 2px solid rgba(255,255,255,0.45);
  cursor: pointer;
  transition: border-color 0.2s, transform 0.2s;
}
.pdp-hero-v2__thumb:hover {
  border-color: var(--pdp-accent);
  transform: scale(1.06);
}
.pdp-hero-v2__scroll-cue {
  display: none;
}

/* ---- Sticky Nav ---- */
.pdp-nav-v2 {
  position: sticky; top: 0; z-index: 200;
  background: white;
  border-bottom: 1px solid var(--pdp-border);
  box-shadow: 0 2px 8px rgba(0,0,0,0.07);
}
.pdp-nav-v2__inner {
  display: flex; align-items: center; gap: 0.25rem;
  padding: 0.42rem 0;
  overflow-x: auto;
  scrollbar-width: none;
}
.pdp-nav-v2__inner::-webkit-scrollbar { display: none; }
.pdp-nav-v2__link {
  color: var(--pdp-muted);
  text-decoration: none;
  font-size: 0.75rem;
  font-weight: 500;
  white-space: nowrap;
  padding: 0.3rem 0.6rem;
  border-radius: 20px;
  transition: all 0.2s;
}
.pdp-nav-v2__link:hover {
  color: var(--pdp-primary);
  background: rgba(13,110,253,0.08);
}
.pdp-nav-v2__cta {
  display: flex; align-items: center; gap: 0.75rem;
  margin-left: auto;
  padding-left: 1rem;
  flex-shrink: 0;
}
.pdp-nav-v2__price {
  font-weight: 700;
  font-size: 0.8rem;
  color: var(--pdp-primary);
  white-space: nowrap;
}
.pdp-obsolete-block {
  display: none !important;
}
.pdp-hero-v2 {
  display: none !important;
}

.pdp-header-clean {
  padding: 1.2rem 0 0.8rem;
  background: #f8f9fa;
  border-bottom: 1px solid var(--pdp-border);
}
.pdp-header-clean__wrap {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(220px, 0.65fr);
  gap: 1rem;
  align-items: end;
}
.pdp-header-clean__breadcrumb {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  align-items: center;
  font-size: 0.78rem;
  color: #667085;
  margin-bottom: 0.5rem;
}
.pdp-header-clean__breadcrumb a {
  color: var(--pdp-text);
  text-decoration: none;
}
.pdp-header-clean__title {
  font-size: clamp(1.5rem, 3vw, 2.25rem);
  line-height: 1.1;
  color: var(--pdp-text);
  font-weight: 700;
  margin: 0;
}
.pdp-header-clean__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem 1rem;
  margin-top: 0.7rem;
}
.pdp-header-clean__meta span {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #475467;
  font-size: 0.82rem;
}
.pdp-header-clean__meta i {
  color: var(--pdp-primary);
}
.pdp-header-clean__lede {
  max-width: 64ch;
  margin: 0.7rem 0 0;
  color: #667085;
  font-size: 0.92rem;
  line-height: 1.65;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.pdp-header-clean__trust {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.85rem;
}
.pdp-header-clean__trust span {
  padding: 0.35rem 0.7rem;
  border-radius: 999px;
  background: white;
  border: 1px solid var(--pdp-border);
  color: var(--pdp-text);
  font-size: 0.72rem;
  font-weight: 600;
}
.pdp-header-clean__side {
  display: grid;
  gap: 0.55rem;
  justify-items: end;
}
.pdp-header-clean__price {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--pdp-text);
}
.pdp-header-clean__cta {
  border-radius: 999px !important;
  padding: 0.78rem 1.2rem !important;
  font-weight: 700 !important;
}
.pdp-header-clean__hint {
  font-size: 0.78rem;
  color: #667085;
}

/* ---- Sales layout refresh ---- */
.pdp-section-kicker {
  display: inline-flex;
  align-items: center;
  padding: 0.45rem 0.85rem;
  border-radius: 999px;
  background: rgba(13,110,253,0.08);
  color: var(--pdp-primary);
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}
.col-lg-7 > :not(.pdp-redesign-stage) {
  display: none !important;
}
.pdp-redesign-stage {
  display: grid;
  gap: 1.2rem;
}
.pdp-redesign-hero-card,
.pdp-redesign-section {
  background: #ffffff;
  border: 1px solid var(--pdp-border);
  border-radius: var(--border-radius-xl, 1rem);
  padding: 1.5rem;
  box-shadow: var(--shadow-sm, 0 2px 8px rgba(0,0,0,0.08));
}
.pdp-redesign-hero-card {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(240px, 0.8fr);
  gap: 1rem;
  align-items: stretch;
}
.pdp-redesign-hero-card__copy h2,
.pdp-redesign-section__head h3 {
  color: var(--pdp-text);
}
.pdp-redesign-hero-card__copy h2 {
  font-size: clamp(1.35rem, 2vw, 1.75rem);
  line-height: 1.2;
  font-weight: 700;
  margin: 0.85rem 0;
}
.pdp-redesign-hero-card__copy p {
  color: var(--pdp-muted);
  line-height: 1.75;
  margin: 0;
}
.pdp-redesign-hero-card__stack {
  display: grid;
  gap: 0.85rem;
}
.pdp-redesign-stat-card {
  padding: 1.15rem;
  border-radius: 16px;
  background: var(--pdp-primary);
  color: white;
}
.pdp-redesign-stat-card small {
  display: block;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.72rem;
  color: rgba(255,255,255,0.72);
  margin-bottom: 0.45rem;
}
.pdp-redesign-stat-card strong {
  display: block;
  font-size: 1.35rem;
  line-height: 1.15;
}
.pdp-redesign-stat-card span {
  display: block;
  margin-top: 0.45rem;
  color: rgba(255,255,255,0.78);
  font-size: 0.84rem;
}
.pdp-redesign-benefits {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.8rem;
}
.pdp-redesign-benefit {
  display: flex;
  align-items: flex-start;
  gap: 0.7rem;
  padding: 1rem;
  background: #fff;
  border: 1px solid rgba(17,32,23,0.08);
  border-radius: 18px;
}
.pdp-redesign-benefit i {
  color: var(--pdp-primary);
  margin-top: 0.2rem;
}
.pdp-redesign-section__head h3 {
  font-size: clamp(1.1rem, 1.8vw, 1.35rem);
  font-weight: 600;
  margin: 0.5rem 0 0;
}
.pdp-redesign-timeline {
  display: grid;
  gap: 0.85rem;
}
.pdp-redesign-timeline__item {
  display: grid;
  grid-template-columns: 110px minmax(0, 1fr);
  gap: 1rem;
  align-items: start;
  padding: 1rem;
  background: #fff;
  border: 1px solid rgba(17,32,23,0.08);
  border-radius: 18px;
}
.pdp-redesign-timeline__time {
  font-weight: 700;
  color: var(--pdp-primary);
}
.pdp-redesign-timeline__content {
  color: #475467;
  line-height: 1.7;
}
.pdp-redesign-timeline__content--full {
  grid-column: 1 / -1;
}
.pdp-redesign-columns {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}
.pdp-redesign-list-card {
  background: #fff;
  border: 1px solid rgba(17,32,23,0.08);
  border-radius: 20px;
  padding: 1.2rem;
}
.pdp-redesign-list-card h4 {
  font-size: 1rem;
  font-weight: 700;
  margin: 0 0 0.85rem;
  color: var(--pdp-text);
}
.pdp-redesign-list {
  display: grid;
  gap: 0.75rem;
}
.pdp-redesign-list div {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  color: #475467;
}
.pdp-redesign-list i {
  color: var(--pdp-primary);
  margin-top: 0.2rem;
}
.pdp-redesign-list--soft i {
  color: #c07a2b;
}
.pdp-redesign-policy {
  margin-top: 1rem;
  padding: 1rem 1.1rem;
  border-radius: 12px;
  background: #f8f9fa;
  color: var(--pdp-muted);
}
.pdp-redesign-policy strong {
  display: block;
  margin-bottom: 0.45rem;
  color: var(--pdp-text);
}
.pdp-redesign-review-summary {
  padding: 1.25rem;
  border-radius: 16px;
  background: var(--pdp-primary);
  color: white;
}
.pdp-redesign-review-score {
  font-size: 2.6rem;
  font-weight: 700;
  line-height: 1;
}
.pdp-redesign-review-summary p {
  margin: 0.8rem 0 0;
  color: rgba(255,255,255,0.82);
}
.pdp-redesign-review-list {
  display: grid;
  gap: 0.85rem;
}
.pdp-redesign-review {
  padding: 1rem 1.1rem;
  background: #fff;
  border: 1px solid rgba(17,32,23,0.08);
  border-radius: 18px;
}
.pdp-redesign-review p {
  margin: 0;
  color: #667085;
  line-height: 1.7;
}
.pdp-redesign-location-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.8rem;
  align-items: center;
  margin-top: 1rem;
}
.pdp-redesign-location-meta span {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  color: #475467;
}
.pdp-redesign-related {
  background: #fff;
  border: 1px solid rgba(17,32,23,0.08);
  border-radius: 20px;
  overflow: hidden;
  height: 100%;
}
.pdp-redesign-related img {
  width: 100%;
  height: 190px;
  object-fit: cover;
  display: block;
}
.pdp-redesign-related__body {
  padding: 1rem;
}
.pdp-redesign-related__body h4 {
  font-size: 1rem;
  margin: 0 0 0.75rem;
  color: var(--pdp-text);
}
.pdp-section-heading h3 {
  font-family: 'Cormorant Garamond', Georgia, serif;
  font-size: clamp(1.55rem, 2.4vw, 2.15rem);
  color: #112017;
  margin: 0.65rem 0 0;
}
.pdp-story-shell {
  display: grid;
  grid-template-columns: minmax(0, 1.3fr) minmax(280px, 0.7fr);
  gap: 1.2rem;
  padding: 1.6rem;
  border-radius: 28px;
  background:
    linear-gradient(135deg, rgba(13,59,30,0.97), rgba(20,84,44,0.92)),
    radial-gradient(circle at top right, rgba(191,155,48,0.28), transparent 35%);
  color: white;
  box-shadow: 0 24px 60px rgba(13,59,30,0.2);
}
.pdp-story-shell__title {
  font-family: 'Cormorant Garamond', Georgia, serif;
  font-size: clamp(2rem, 3.5vw, 3.1rem);
  line-height: 0.98;
  margin: 1rem 0;
}
.pdp-story-shell__text {
  margin: 0;
  font-size: 1rem;
  line-height: 1.85;
  color: rgba(255,255,255,0.84);
}
.pdp-story-shell__aside {
  display: grid;
  gap: 1rem;
}
.pdp-story-shell__rating,
.pdp-story-shell__next {
  padding: 1.25rem;
  border-radius: 20px;
  background: rgba(255,255,255,0.08);
  border: 1px solid rgba(255,255,255,0.12);
  backdrop-filter: blur(10px);
}
.pdp-story-shell__rating strong,
.pdp-story-shell__next strong {
  display: block;
  font-size: 1.55rem;
  line-height: 1.1;
}
.pdp-story-shell__rating span,
.pdp-story-shell__next small {
  display: block;
  margin-top: 0.45rem;
  color: rgba(255,255,255,0.72);
}
.pdp-facts-band {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.9rem;
}
.pdp-fact-card {
  padding: 1rem 1.1rem;
  border-radius: 18px;
  background: #fff;
  border: 1px solid rgba(17,32,23,0.08);
  box-shadow: var(--pdp-shadow);
}
.pdp-fact-card small {
  display: block;
  color: #667085;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 0.7rem;
  margin-bottom: 0.45rem;
}
.pdp-fact-card strong {
  display: block;
  color: #112017;
  font-size: 1rem;
}
.pdp-selling-points,
.pdp-framed-block {
  background: linear-gradient(180deg, #ffffff, #f8f8f3);
  border: 1px solid rgba(17,32,23,0.08);
  border-radius: 24px;
  padding: 1.4rem;
  box-shadow: var(--pdp-shadow);
}
.pdp-selling-points__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.85rem;
  margin-top: 1rem;
}
.pdp-selling-point {
  display: flex;
  align-items: flex-start;
  gap: 0.7rem;
  padding: 1rem;
  border-radius: 16px;
  background: #f4f8f4;
  color: #223127;
}
.pdp-selling-point i {
  margin-top: 0.2rem;
  color: var(--pdp-primary);
}
.pdp-reassurance-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.75rem;
}
.pdp-reassurance-row div {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.55rem;
  padding: 0.95rem;
  border-radius: 16px;
  background: #f7faf7;
  border: 1px solid rgba(17,32,23,0.08);
  color: #223127;
  font-size: 0.88rem;
}
.pdp-reassurance-row i {
  color: var(--pdp-primary);
}

/* ---- Premium story blocks ---- */
.pdp-section-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.4rem 0.8rem;
  border-radius: 999px;
  background: #eef5ef;
  color: var(--pdp-primary);
  font-size: 0.74rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  margin-bottom: 1rem;
}
.pdp-premium-overview {
  display: grid;
  grid-template-columns: minmax(0, 1.15fr) minmax(0, 0.95fr);
  gap: 1.25rem;
  padding: 1.5rem;
  background:
    radial-gradient(circle at top left, rgba(191,155,48,0.12), transparent 36%),
    linear-gradient(135deg, #fcfcf7, #f3f0e7);
  border: 1px solid rgba(191,155,48,0.22);
  border-radius: 24px;
  box-shadow: 0 24px 60px rgba(17, 24, 39, 0.08);
}
.pdp-premium-overview__intro h2 {
  font-family: 'Cormorant Garamond', Georgia, serif;
  font-size: clamp(1.8rem, 2.6vw, 2.7rem);
  line-height: 1.02;
  margin-bottom: 0.9rem;
  color: #112017;
}
.pdp-premium-overview__intro p {
  margin: 0;
  color: #475467;
  line-height: 1.8;
  font-size: 0.98rem;
}
.pdp-premium-overview__trust {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem;
  margin-top: 1.2rem;
}
.pdp-trust-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: rgba(255,255,255,0.75);
  border: 1px solid rgba(17,32,23,0.08);
  padding: 0.7rem 0.9rem;
  border-radius: 14px;
  color: #223127;
  font-size: 0.84rem;
}
.pdp-trust-chip i {
  color: var(--pdp-primary);
}
.pdp-premium-overview__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.9rem;
}
.pdp-snapshot-card {
  padding: 1.1rem;
  border-radius: 18px;
  background: rgba(255,255,255,0.82);
  border: 1px solid rgba(17,32,23,0.08);
  min-height: 158px;
}
.pdp-snapshot-card small {
  display: block;
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #667085;
  margin-bottom: 0.65rem;
}
.pdp-snapshot-card strong {
  display: block;
  font-size: 1.2rem;
  line-height: 1.2;
  color: #112017;
  margin-bottom: 0.55rem;
}
.pdp-snapshot-card span {
  display: block;
  color: #475467;
  font-size: 0.86rem;
  line-height: 1.65;
}
.pdp-experience-strip {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
}
.pdp-experience-card {
  display: flex;
  gap: 0.9rem;
  align-items: flex-start;
  padding: 1.15rem;
  border-radius: 20px;
  background: #fff;
  border: 1px solid var(--pdp-border);
  box-shadow: var(--pdp-shadow);
}
.pdp-experience-card__icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  background: linear-gradient(135deg, rgba(27,94,32,0.12), rgba(191,155,48,0.22));
  color: var(--pdp-primary);
}
.pdp-experience-card h3 {
  font-size: 1rem;
  margin: 0 0 0.35rem;
  color: #112017;
}
.pdp-experience-card p {
  margin: 0;
  color: #667085;
  font-size: 0.86rem;
  line-height: 1.7;
}
.pdp-editorial-card .card-body {
  padding: 1.5rem;
}
.pdp-editorial-card__footer {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(17,32,23,0.08);
}
.pdp-editorial-card__label {
  display: block;
  font-size: 0.72rem;
  font-weight: 700;
  color: #667085;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-bottom: 0.45rem;
}
.pdp-editorial-card__footer strong {
  color: #112017;
  font-size: 0.98rem;
  line-height: 1.5;
}

/* ---- Booking Sidebar v2 ---- */
.pdp-booking-v2 {
  background: var(--pdp-card);
  border-radius: var(--pdp-radius);
  box-shadow: var(--pdp-shadow-lg);
  overflow: hidden;
  border: 1px solid var(--pdp-border);
}
.pdp-booking-v2__header {
  background: var(--pdp-primary);
  color: white;
  padding: 1.5rem;
}
.pdp-booking-v2__private-tag {
  display: inline-flex; align-items: center;
  background: var(--pdp-accent);
  color: #1A1A1A;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.3rem 0.85rem;
  border-radius: 20px;
  margin-bottom: 0.75rem;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}
.pdp-booking-v2__price-label {
  font-size: 0.8rem;
  color: rgba(255,255,255,0.7);
  text-transform: uppercase;
  letter-spacing: 0.06em;
  margin-bottom: 0.25rem;
}
.pdp-booking-v2__price-amount {
  font-size: 2.4rem;
  font-weight: 600;
  line-height: 1;
  color: white;
}
.pdp-booking-v2__price-amount small {
  font-size: 1rem;
  font-family: 'Plus Jakarta Sans', sans-serif;
  font-weight: 400;
  color: rgba(255,255,255,0.7);
  margin-left: 0.25rem;
}
.pdp-booking-v2__price-old {
  font-size: 1.1rem;
  text-decoration: line-through;
  color: rgba(255,255,255,0.5);
  margin-right: 0.35rem;
}
.pdp-booking-v2__child-price,
.pdp-booking-v2__next-date {
  font-size: 0.82rem;
  color: rgba(255,255,255,0.8);
  margin-top: 0.5rem;
}
.pdp-booking-v2__meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.55rem;
  margin-top: 1rem;
}
.pdp-booking-v2__meta-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 0.8rem;
  border-radius: 999px;
  background: rgba(255,255,255,0.1);
  border: 1px solid rgba(255,255,255,0.14);
  font-size: 0.74rem;
  color: rgba(255,255,255,0.88);
}
.pdp-booking-v2__tiers {
  display: flex; flex-direction: column; gap: 0.4rem;
  margin-top: 0.5rem;
}
.pdp-booking-v2__tier {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.5rem 0.75rem;
  background: rgba(255,255,255,0.08);
  border-radius: 8px;
  font-size: 0.82rem;
  border: 1px solid rgba(255,255,255,0.1);
  transition: background 0.2s;
}
.pdp-booking-v2__tier.active,
.pdp-booking-v2__tier:hover {
  background: rgba(255,255,255,0.15);
  border-color: var(--pdp-accent);
}
.pdp-booking-v2__tier strong { color: var(--pdp-accent); }
.pdp-booking-v2__tier small { color: rgba(255,255,255,0.6); }
/* Info strip */
.pdp-booking-v2__info-strip {
  display: flex; flex-wrap: wrap; gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: #f8f9fa;
  border-bottom: 1px solid var(--pdp-border);
}
.pdp-booking-v2__info-item {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.78rem;
  color: var(--pdp-muted);
}
.pdp-booking-v2__info-item i { color: var(--pdp-primary); width: 14px; text-align: center; }
.pdp-booking-v2__trust-list {
  display: grid;
  gap: 0.55rem;
  padding: 0.9rem 1.25rem 0;
}
.pdp-booking-v2__trust-list div {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  font-size: 0.8rem;
  color: #425466;
}
.pdp-booking-v2__trust-list i {
  color: var(--pdp-primary);
}
/* Body */
.pdp-booking-v2__body {
  padding: 1.25rem;
}
/* Step labels */
.pdp-booking-v2__step { margin-bottom: 1.1rem; }
.pdp-booking-v2__step-label {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: var(--pdp-muted);
  margin-bottom: 0.4rem;
}
.pdp-booking-v2__step-num {
  width: 18px; height: 18px;
  background: var(--pdp-primary);
  color: white;
  border-radius: 50%;
  font-size: 0.6rem;
  font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
/* Total box */
.pdp-booking-v2__total {
  background: rgba(13,110,253,0.06);
  border: 1px solid rgba(13,110,253,0.18);
  border-radius: var(--pdp-radius-sm);
  padding: 0.75rem 1rem;
  margin-bottom: 1rem;
}
.pdp-booking-v2__total-breakdown {
  font-size: 0.78rem;
  color: var(--pdp-muted);
  margin-bottom: 0.25rem;
}
.pdp-booking-v2__total-amount {
  font-size: 1rem;
  color: var(--pdp-primary);
}
/* Private calc */
.pdp-booking-v2__private-calc {
  font-size: 0.8rem;
  background: var(--pdp-accent-light);
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
  margin-top: 0.5rem;
  color: #7A5A00;
}
/* CTA */
.pdp-booking-v2__cta { margin-bottom: 0.75rem; }
.pdp-booking-v2__no-date {
  font-size: 0.78rem;
  color: #856404;
  background: #fff3cd;
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
  margin-bottom: 0.75rem;
  text-align: center;
}
.pdp-booking-v2__submit {
  font-size: 1rem !important;
  font-weight: 700 !important;
  letter-spacing: 0.04em;
  padding: 0.9rem !important;
  border-radius: var(--pdp-radius-sm) !important;
  background: #198754 !important;
  border: none !important;
  box-shadow: 0 4px 16px rgba(25,135,84,0.35) !important;
  transition: transform 0.2s, box-shadow 0.2s !important;
}
.pdp-booking-v2__submit:not(:disabled):hover {
  background: #157347 !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 6px 22px rgba(25,135,84,0.45) !important;
}
.pdp-booking-v2__security {
  text-align: center;
  font-size: 0.72rem;
  color: var(--pdp-muted);
  margin-top: 0.5rem;
}
/* ---- BK Form classes (no-shift booking form) ---- */
.bk-row-2col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.65rem;
}
.bk-label {
  display: block;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: var(--pdp-muted);
  margin-bottom: 0.3rem;
}
.bk-private-calc {
  font-size: 0.8rem;
  background: var(--pdp-accent-light);
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
  margin-bottom: 0.75rem;
  color: #7A5A00;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
/* Swap zone: hint and total-line occupy the exact same height */
.bk-hint,
.bk-total-line {
  min-height: 44px;
  display: flex;
  align-items: center;
  border-radius: 8px;
  padding: 0.5rem 0.75rem;
  margin-bottom: 0.75rem;
  font-size: 0.8rem;
}
.bk-hint {
  background: #fff3cd;
  color: #856404;
  justify-content: center;
  text-align: center;
}
.bk-total-line {
  background: rgba(13,110,253,0.06);
  border: 1px solid rgba(13,110,253,0.18);
  flex-direction: column;
  align-items: stretch;
  gap: 0.2rem;
}
.bk-total-breakdown {
  font-size: 0.75rem;
  color: var(--pdp-muted);
}
.bk-total-amount {
  font-size: 0.92rem;
  color: var(--pdp-primary);
  font-weight: 600;
}
/* CTA area — anchored, never shifts */
.bk-cta-area {
  margin-bottom: 0.5rem;
}
.bk-btn-reserve {
  display: block;
  width: 100%;
  font-size: 1rem !important;
  font-weight: 700 !important;
  letter-spacing: 0.04em;
  padding: 0.9rem !important;
  border-radius: var(--pdp-radius-sm) !important;
  background: #198754 !important;
  border: none !important;
  color: white !important;
  box-shadow: 0 4px 16px rgba(25,135,84,0.35) !important;
  transition: transform 0.2s, box-shadow 0.2s !important;
  margin-bottom: 0.5rem;
}
.bk-btn-reserve:not(:disabled):hover {
  background: #157347 !important;
  transform: translateY(-2px) !important;
  box-shadow: 0 6px 22px rgba(25,135,84,0.45) !important;
}
.bk-btn-reserve:disabled {
  opacity: 0.65 !important;
  cursor: not-allowed !important;
}
.bk-btn-rnpl {
  display: block;
  width: 100%;
  font-size: 0.88rem !important;
  font-weight: 600 !important;
  padding: 0.7rem !important;
  border-radius: var(--pdp-radius-sm) !important;
  background: transparent !important;
  border: 2px solid var(--pdp-primary) !important;
  color: var(--pdp-primary) !important;
  transition: background 0.2s, color 0.2s !important;
  margin-bottom: 0.5rem;
}
.bk-btn-rnpl:hover {
  background: var(--pdp-primary) !important;
  color: white !important;
}
.bk-security {
  text-align: center;
  font-size: 0.7rem;
  color: var(--pdp-muted);
  margin-top: 0.35rem;
}
/* RNPL toggle — lives BELOW cta, shifts only WhatsApp link downward */
.bk-rnpl-toggle {
  border-top: 1px solid var(--pdp-border);
  padding-top: 0.75rem;
  margin-top: 0.5rem;
}
.bk-rnpl-row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.bk-rnpl-text {
  display: flex;
  align-items: flex-start;
  gap: 0.4rem;
  flex: 1;
  font-size: 0.78rem;
  color: var(--pdp-text);
}
.bk-rnpl-text > div {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}
.bk-rnpl-text span {
  font-size: 0.72rem;
  color: var(--pdp-muted);
}

/* WhatsApp button */
.pdp-booking-v2__whatsapp {
  background: #25D366 !important;
  color: white !important;
  border: none !important;
  font-weight: 600;
  font-size: 0.9rem;
  padding: 0.75rem !important;
  border-radius: var(--pdp-radius-sm) !important;
  text-align: center;
  text-decoration: none;
  display: flex !important;
  align-items: center;
  justify-content: center;
  margin-top: 0.75rem;
  transition: filter 0.2s;
}
.pdp-booking-v2__whatsapp:hover { filter: brightness(1.08); color: white !important; }
/* Contact */
.pdp-booking-v2__contact {
  padding: 1rem 1.25rem;
  border-top: 1px solid var(--pdp-border);
  background: #f8f9fa;
}
.pdp-booking-v2__contact-title {
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--pdp-primary);
  margin-bottom: 0.4rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.pdp-booking-v2__contact-link {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.82rem;
  color: var(--pdp-muted);
  text-decoration: none;
  padding: 0.2rem 0;
  transition: color 0.2s;
}
.pdp-booking-v2__contact-link:hover { color: var(--pdp-primary); }
.pdp-booking-v2__contact-link i { color: var(--pdp-primary); width: 14px; }

/* ---- Section cards ---- */
.card.shadow-sm {
  border-radius: var(--pdp-radius) !important;
  border: 1px solid var(--pdp-border) !important;
  box-shadow: var(--pdp-shadow) !important;
}
/* Card headers use Bootstrap's own bg-primary/bg-success colors */

/* ---- Premium Sticky Mobile ---- */
.pdp-sticky-mobile {
  position: fixed;
  bottom: 0; left: 0; right: 0;
  background: white;
  border-top: 1px solid var(--pdp-border);
  box-shadow: 0 -4px 20px rgba(0,0,0,0.12);
  z-index: 1030;
  padding: 0.75rem 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  transition: transform 0.3s ease;
}
.pdp-sticky-mobile.hidden { transform: translateY(100%); }
.pdp-sticky-mobile__price {
  display: flex; flex-direction: column;
  flex-shrink: 0;
}
.pdp-sticky-mobile__price small {
  font-size: 0.65rem;
  color: var(--pdp-muted);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.pdp-sticky-mobile__price strong {
  font-size: 1.05rem;
  color: var(--pdp-primary);
  font-weight: 700;
}
.pdp-sticky-mobile__wa {
  width: 42px; height: 42px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 50% !important;
  padding: 0 !important;
  flex-shrink: 0;
  border-color: #25D366 !important;
  color: #25D366 !important;
}
@media (max-width: 991.98px) {
  .col-lg-7 > :not(.pdp-redesign-stage) {
    display: none !important;
  }
  .pdp-redesign-hero-card,
  .pdp-redesign-columns,
  .pdp-redesign-benefits,
  .pdp-header-clean__wrap,
  .pdp-story-shell,
  .pdp-facts-band,
  .pdp-reassurance-row,
  .pdp-premium-overview,
  .pdp-experience-strip,
  .pdp-editorial-card__footer {
    grid-template-columns: 1fr;
  }
  .pdp-premium-overview__grid {
    grid-template-columns: 1fr 1fr;
  }
  .pdp-selling-points__grid {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 767.98px) {
  .pdp-redesign-hero-card,
  .pdp-redesign-section {
    padding: 1.1rem;
    border-radius: 22px;
  }
  .pdp-redesign-timeline__item {
    grid-template-columns: 1fr;
    gap: 0.5rem;
  }
  .pdp-header-clean {
    padding: 1rem 0 0.65rem;
  }
  .pdp-header-clean__side {
    justify-items: start;
  }
  .pdp-header-clean__price {
    font-size: 1.2rem;
  }
  .pdp-hero-v2__lede {
    font-size: 0.9rem;
    line-height: 1.6;
  }
  .pdp-story-shell,
  .pdp-selling-points,
  .pdp-framed-block {
    padding: 1.1rem;
    border-radius: 20px;
  }
  .pdp-premium-overview {
    padding: 1.15rem;
    border-radius: 20px;
  }
  .pdp-facts-band,
  .pdp-reassurance-row,
  .pdp-premium-overview__grid {
    grid-template-columns: 1fr;
  }
}

/* ---- Misc overrides ---- */

/* Image section */

/* Includes/Excludes */
.includes-card, .excludes-card { border-radius: var(--pdp-radius) !important; overflow: hidden; box-shadow: var(--pdp-shadow) !important; }
.card-header-custom.bg-success { background: #1B5E20 !important; }
.card-header-custom.bg-orange { background: #BF360C !important; }

/* Timeline */
.timeline { position: relative; padding-left: 1.5rem; }
.timeline-item { border-left: 2px solid var(--pdp-border) !important; padding-left: 1rem; margin-left: 0.5rem; }
.timeline-item .badge { background: var(--pdp-primary) !important; }

/* Related tour cards */
.product-card { border-radius: var(--pdp-radius) !important; border: 1px solid var(--pdp-border) !important; transition: transform 0.25s, box-shadow 0.25s; }
.product-card:hover { transform: translateY(-4px); box-shadow: var(--pdp-shadow-lg) !important; }

/* Scrollbar hide for nav */
.pdp-nav-v2__inner { scrollbar-width: none; }
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
