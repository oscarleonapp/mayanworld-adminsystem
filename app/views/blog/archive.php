<?php
/**
 * Vista Frontend: Archivo del Blog por Fecha
 * Listado de posts por año/mes
 */

use App\Core\Config;
use App\Core\Helpers;

$monthNames = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

$archiveTitle = $month ? $monthNames[$month] . ' ' . $year : 'Año ' . $year;

require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Hero Section -->
<section class="hero-section bg-gradient text-white py-5"
         style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb bg-transparent mb-0">
                <li class="breadcrumb-item"><a href="?route=home" class="text-white-50">Inicio</a></li>
                <li class="breadcrumb-item"><a href="?route=blog" class="text-white-50">Blog</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page">
                    Archivo <?= $year ?>
                </li>
            </ol>
        </nav>

        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-archive me-3"></i>
                    Archivo: <?= htmlspecialchars($archiveTitle) ?>
                </h1>
                <p class="lead mb-0 opacity-90">
                    <?= count($posts) ?> artículo<?= count($posts) != 1 ? 's' : '' ?> publicado<?= count($posts) != 1 ? 's' : '' ?>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="archive-posts py-5">
    <div class="container">
        <?php if (empty($posts)): ?>
            <!-- Empty State -->
            <div class="text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-4"></i>
                <h3 class="text-muted">No hay artículos en este período</h3>
                <p class="text-muted">Explora otros meses o vuelve al blog principal</p>
                <a href="?route=blog" class="btn btn-primary mt-3">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver al Blog
                </a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <!-- Posts Timeline -->
                <div class="col-lg-8">
                    <?php
                    // Agrupar posts por mes si estamos viendo un año completo
                    if (!$month) {
                        $postsByMonth = [];
                        foreach ($posts as $post) {
                            $postMonth = date('n', strtotime($post['fecha_publicacion']));
                            if (!isset($postsByMonth[$postMonth])) {
                                $postsByMonth[$postMonth] = [];
                            }
                            $postsByMonth[$postMonth][] = $post;
                        }
                        krsort($postsByMonth); // Ordenar por mes descendente
                    } else {
                        $postsByMonth = [$month => $posts];
                    }
                    ?>

                    <?php foreach ($postsByMonth as $m => $monthPosts): ?>
                        <?php if (!$month): // Solo mostrar header de mes si estamos viendo el año completo ?>
                            <h3 class="mb-4 pb-3 border-bottom">
                                <i class="fas fa-calendar-alt text-primary me-2"></i>
                                <?= $monthNames[$m] ?> <?= $year ?>
                                <span class="badge bg-secondary ms-2"><?= count($monthPosts) ?></span>
                            </h3>
                        <?php endif; ?>

                        <div class="posts-list mb-5">
                            <?php foreach ($monthPosts as $post): ?>
                                <article class="post-item card mb-4 shadow-sm hover-lift">
                                    <div class="row g-0">
                                        <?php if ($post['imagen_destacada']): ?>
                                            <div class="col-md-4">
                                                <a href="?route=blog/<?= htmlspecialchars($post['slug']) ?>">
                                                    <img src="<?= Config::getBaseUrl() ?>public<?= htmlspecialchars($post['imagen_destacada']) ?>"
                                                         class="img-fluid rounded-start h-100"
                                                         alt="<?= htmlspecialchars($post['imagen_alt'] ?: $post['titulo']) ?>"
                                                         style="object-fit: cover;"
                                                         loading="lazy"
                                                         decoding="async">
                                                </a>
                                            </div>
                                        <?php endif; ?>

                                        <div class="<?= $post['imagen_destacada'] ? 'col-md-8' : 'col-12' ?>">
                                            <div class="card-body">
                                                <!-- Date Badge -->
                                                <div class="mb-2">
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= date('d/m/Y', strtotime($post['fecha_publicacion'] ?? $post['created_at'])) ?>
                                                    </span>
                                                    <?php if ($post['categoria_nombre']): ?>
                                                        <a href="?route=blog/categoria/<?= htmlspecialchars($post['categoria_slug']) ?>"
                                                           class="badge text-decoration-none ms-2"
                                                           style="background-color: <?= htmlspecialchars($post['categoria_color']) ?>">
                                                            <?= htmlspecialchars($post['categoria_nombre']) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>

                                                <!-- Title -->
                                                <h5 class="card-title mb-3">
                                                    <a href="?route=blog/<?= htmlspecialchars($post['slug']) ?>"
                                                       class="text-dark text-decoration-none hover-primary">
                                                        <?= htmlspecialchars($post['titulo']) ?>
                                                    </a>
                                                </h5>

                                                <!-- Excerpt -->
                                                <p class="card-text text-muted">
                                                    <?= htmlspecialchars(substr($post['descripcion_corta'] ?? strip_tags($post['contenido']), 0, 200)) ?>...
                                                </p>

                                                <!-- Meta -->
                                                <div class="d-flex justify-content-between align-items-center text-muted small">
                                                    <div>
                                                        <i class="fas fa-user me-1"></i>
                                                        <?= htmlspecialchars($post['autor_nombre']) ?>
                                                    </div>
                                                    <div>
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?= $post['tiempo_lectura'] ?> min
                                                    </div>
                                                    <div>
                                                        <i class="fas fa-eye me-1"></i>
                                                        <?= number_format($post['vistas']) ?>
                                                    </div>
                                                </div>

                                                <!-- Read More -->
                                                <a href="?route=blog/<?= htmlspecialchars($post['slug']) ?>"
                                                   class="btn btn-outline-primary btn-sm mt-3">
                                                    Leer más
                                                    <i class="fas fa-arrow-right ms-2"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">
                    <!-- Archive Navigation -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-archive text-primary me-2"></i>
                                Archivo del Blog
                            </h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($archiveMonths, 0, 12) as $archive): ?>
                                <?php
                                $isActive = $archive['year'] == $year && (!$month || $archive['month'] == $month);
                                ?>
                                <a href="?route=blog/archivo/<?= $archive['year'] ?>/<?= $archive['month'] ?>"
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $isActive ? 'active' : '' ?>">
                                    <span>
                                        <?= $monthNames[$archive['month']] ?> <?= $archive['year'] ?>
                                    </span>
                                    <span class="badge <?= $isActive ? 'bg-white text-primary' : 'bg-secondary' ?> rounded-pill">
                                        <?= $archive['post_count'] ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Categories -->
                    <?php if (!empty($categories)): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-folder text-primary me-2"></i>
                                    Categorías
                                </h5>
                            </div>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($categories, 0, 6) as $cat): ?>
                                    <a href="?route=blog/categoria/<?= htmlspecialchars($cat['slug']) ?>"
                                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas <?= htmlspecialchars($cat['icono'] ?: 'fa-folder') ?> me-2"
                                               style="color: <?= htmlspecialchars($cat['color']) ?>"></i>
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </span>
                                        <span class="badge bg-secondary rounded-pill">
                                            <?= $cat['published_count'] ?? 0 ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

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
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.hover-primary:hover {
    color: var(--bs-primary) !important;
}

.post-item img {
    transition: transform 0.3s ease;
}

.post-item:hover img {
    transform: scale(1.05);
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
