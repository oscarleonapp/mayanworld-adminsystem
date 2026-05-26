<?php
/**
 * Vista Frontend: Posts por Categoría
 * Listado de posts filtrados por categoría específica
 */

use App\Core\Config;
use App\Core\Helpers;

// Meta Tags SEO
$pageTitle = $category['meta_title'] ?: $category['nombre'] . ' - Blog';
$metaDescription = $category['meta_description'] ?: $category['descripcion'];

require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Hero Section -->
<section class="hero-section text-white py-5"
         style="background: linear-gradient(135deg, <?= htmlspecialchars($category['color']) ?> 0%, <?= adjustColor($category['color'], -30) ?> 100%);">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb bg-transparent mb-0">
                <li class="breadcrumb-item"><a href="?route=home" class="text-white-50">Inicio</a></li>
                <li class="breadcrumb-item"><a href="?route=blog" class="text-white-50">Blog</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">
                    <?= htmlspecialchars($category['nombre']) ?>
                </li>
            </ol>
        </nav>

        <div class="row align-items-center">
            <div class="col-lg-8">
                <div class="d-flex align-items-center mb-3">
                    <div class="category-icon me-3"
                         style="font-size: 3rem;">
                        <i class="fas <?= htmlspecialchars($category['icono'] ?: 'fa-folder') ?>"></i>
                    </div>
                    <div>
                        <h1 class="display-4 fw-bold mb-0">
                            <?= htmlspecialchars($category['nombre']) ?>
                        </h1>
                    </div>
                </div>

                <?php if ($category['descripcion']): ?>
                    <p class="lead mb-0 opacity-90">
                        <?= htmlspecialchars($category['descripcion']) ?>
                    </p>
                <?php endif; ?>

                <div class="mt-3">
                    <span class="badge bg-white bg-opacity-25 px-3 py-2">
                        <i class="fas fa-file-alt me-2"></i>
                        <?= count($posts) ?> artículo<?= count($posts) != 1 ? 's' : '' ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="category-posts py-5">
    <div class="container">
        <?php if (empty($posts)): ?>
            <!-- Empty State -->
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-4"></i>
                <h3 class="text-muted">No hay artículos en esta categoría</h3>
                <p class="text-muted">Vuelve pronto para leer nuestros próximos artículos</p>
                <a href="?route=blog" class="btn btn-primary mt-3">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver al Blog
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <!-- Posts List -->
                <div class="col-lg-8">
                    <div class="row g-4">
                        <?php foreach ($posts as $post): ?>
                            <div class="col-md-6">
                                <article class="blog-card card h-100 shadow-sm hover-lift">
                                    <?php if ($post['imagen_destacada']): ?>
                                        <a href="?route=blog/<?= htmlspecialchars($post['slug']) ?>">
                                            <img src="<?= Config::getBaseUrl() ?>public<?= htmlspecialchars($post['imagen_destacada']) ?>"
                                                 class="card-img-top"
                                                 alt="<?= htmlspecialchars($post['imagen_alt'] ?: $post['titulo']) ?>"
                                                 style="height: 250px; object-fit: cover;"
                                                 loading="lazy"
                                                 decoding="async">
                                        </a>
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                             style="height: 250px;">
                                            <i class="fas fa-image fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title mb-3">
                                            <a href="?route=blog/<?= htmlspecialchars($post['slug']) ?>"
                                               class="text-dark text-decoration-none hover-primary">
                                                <?= htmlspecialchars($post['titulo']) ?>
                                            </a>
                                        </h5>

                                        <p class="card-text text-muted mb-3 flex-grow-1">
                                            <?= htmlspecialchars(substr($post['descripcion_corta'] ?? strip_tags($post['contenido']), 0, 120)) ?>...
                                        </p>

                                        <div class="d-flex justify-content-between align-items-center text-muted small">
                                            <div>
                                                <i class="fas fa-user me-1"></i>
                                                <?= htmlspecialchars($post['autor_nombre']) ?>
                                            </div>
                                            <div>
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= date('d M Y', strtotime($post['fecha_publicacion'] ?? $post['created_at'])) ?>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center text-muted small mt-2">
                                            <div>
                                                <i class="fas fa-clock me-1"></i>
                                                <?= $post['tiempo_lectura'] ?> min
                                            </div>
                                            <div>
                                                <i class="fas fa-eye me-1"></i>
                                                <?= number_format($post['vistas']) ?>
                                            </div>
                                        </div>

                                        <a href="?route=blog/<?= htmlspecialchars($post['slug']) ?>"
                                           class="btn btn-outline-primary btn-sm mt-3 w-100">
                                            Leer más
                                            <i class="fas fa-arrow-right ms-2"></i>
                                        </a>
                                    </div>
                                </article>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- All Categories -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-folder text-primary me-2"></i>
                                Todas las Categorías
                            </h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($categories as $cat): ?>
                                <a href="?route=blog/categoria/<?= htmlspecialchars($cat['slug']) ?>"
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $cat['id'] == $category['id'] ? 'active' : '' ?>">
                                    <span>
                                        <i class="fas <?= htmlspecialchars($cat['icono'] ?: 'fa-folder') ?> me-2"
                                           style="color: <?= $cat['id'] == $category['id'] ? 'white' : htmlspecialchars($cat['color']) ?>"></i>
                                        <?= htmlspecialchars($cat['nombre']) ?>
                                    </span>
                                    <span class="badge <?= $cat['id'] == $category['id'] ? 'bg-white text-primary' : 'bg-secondary' ?> rounded-pill">
                                        <?= $cat['published_count'] ?? 0 ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Recent Posts -->
                    <?php if (!empty($recentPosts)): ?>
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-clock text-primary me-2"></i>
                                    Artículos Recientes
                                </h5>
                            </div>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($recentPosts, 0, 5) as $recent): ?>
                                    <a href="?route=blog/<?= htmlspecialchars($recent['slug']) ?>"
                                       class="list-group-item list-group-item-action">
                                        <div class="small">
                                            <div class="fw-bold mb-1"><?= htmlspecialchars($recent['titulo']) ?></div>
                                            <div class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= date('d M Y', strtotime($recent['fecha_publicacion'] ?? $recent['created_at'])) ?>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.hover-lift {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.hover-primary:hover {
    color: var(--bs-primary) !important;
}

.blog-card .card-img-top {
    transition: transform 0.3s ease;
    overflow: hidden;
}

.blog-card:hover .card-img-top {
    transform: scale(1.05);
}
</style>

<?php
// Helper function to adjust color brightness
function adjustColor($color, $percent) {
    $hex = str_replace('#', '', $color);
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $r = max(0, min(255, $r + $percent));
    $g = max(0, min(255, $g + $percent));
    $b = max(0, min(255, $b + $percent));

    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT)
           . str_pad(dechex($g), 2, '0', STR_PAD_LEFT)
           . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

require_once __DIR__ . '/../layouts/footer.php';
?>
