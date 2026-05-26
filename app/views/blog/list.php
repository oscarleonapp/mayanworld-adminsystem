<?php
/**
 * Vista Frontend: Listado del Blog
 * Grid de posts con sidebar de filtros
 */

use App\Core\Config;
use App\Core\Helpers;

require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Hero Section -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-blog me-3"></i>
                    Blog de Viajes
                </h1>
                <p class="lead mb-0">
                    Descubre guías, consejos y artículos sobre los mejores destinos del mundo maya
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <form action="?route=blog/buscar" method="GET" class="search-form">
                    <div class="input-group input-group-lg">
                        <input type="text"
                               name="q"
                               class="form-control"
                               placeholder="Buscar artículos..."
                               value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
                        <button class="btn btn-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="blog-list-section py-5">
    <div class="container">
        <div class="row g-4">
            <!-- Main Column -->
            <div class="col-lg-8">
                <?php if (empty($posts)): ?>
                    <!-- Empty State -->
                    <div class="card text-center py-5">
                        <div class="card-body">
                            <i class="fas fa-inbox fa-4x text-muted mb-4"></i>
                            <h3 class="text-muted">No hay artículos disponibles</h3>
                            <p class="text-muted">Vuelve pronto para leer nuestros próximos artículos</p>
                            <a href="?route=home" class="btn btn-primary mt-3">
                                <i class="fas fa-home me-2"></i>
                                Volver al inicio
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Posts Grid -->
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
                                        <!-- Category Badge -->
                                        <?php if ($post['categoria_nombre']): ?>
                                            <div class="mb-2">
                                                <a href="?route=blog/categoria/<?= htmlspecialchars($post['categoria_slug']) ?>"
                                                   class="badge text-decoration-none"
                                                   style="background-color: <?= htmlspecialchars($post['categoria_color'] ?? '#6c757d') ?>">
                                                    <?= htmlspecialchars($post['categoria_nombre']) ?>
                                                </a>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Title -->
                                        <h5 class="card-title mb-3">
                                            <a href="?route=blog/<?= htmlspecialchars($post['slug']) ?>"
                                               class="text-dark text-decoration-none hover-primary">
                                                <?= htmlspecialchars($post['titulo']) ?>
                                            </a>
                                        </h5>

                                        <!-- Excerpt -->
                                        <p class="card-text text-muted mb-3 flex-grow-1">
                                            <?= htmlspecialchars(substr($post['descripcion_corta'] ?? strip_tags($post['contenido']), 0, 120)) ?>...
                                        </p>

                                        <!-- Meta Info -->
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
                                                <?= $post['tiempo_lectura'] ?> min lectura
                                            </div>
                                            <div>
                                                <i class="fas fa-eye me-1"></i>
                                                <?= number_format($post['vistas']) ?> vistas
                                            </div>
                                        </div>

                                        <!-- Read More -->
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

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Blog pagination" class="mt-5">
                            <ul class="pagination justify-content-center">
                                <!-- Previous -->
                                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                       href="?route=blog/page/<?= $currentPage - 1 ?>"
                                       aria-label="Anterior">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>

                                <?php
                                $start = max(1, $currentPage - 2);
                                $end = min($totalPages, $currentPage + 2);

                                if ($start > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?route=blog/page/1">1</a>
                                    </li>
                                    <?php if ($start > 2): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                        <a class="page-link" href="?route=blog/page/<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($end < $totalPages): ?>
                                    <?php if ($end < $totalPages - 1): ?>
                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                    <?php endif; ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?route=blog/page/<?= $totalPages ?>"><?= $totalPages ?></a>
                                    </li>
                                <?php endif; ?>

                                <!-- Next -->
                                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                                    <a class="page-link"
                                       href="?route=blog/page/<?= $currentPage + 1 ?>"
                                       aria-label="Siguiente">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
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
                            <?php foreach ($categories as $cat): ?>
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
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-clock text-primary me-2"></i>
                                Artículos Recientes
                            </h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentPosts as $recent): ?>
                                <a href="?route=blog/<?= htmlspecialchars($recent['slug']) ?>"
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <?php if ($recent['categoria_slug']): ?>
                                                <span class="badge"
                                                      style="background-color: <?= htmlspecialchars($recent['categoria_color'] ?? '#6c757d') ?>">
                                                    <?= htmlspecialchars($recent['categoria_nombre']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1 small">
                                            <div class="fw-bold"><?= htmlspecialchars($recent['titulo']) ?></div>
                                            <div class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= date('d M Y', strtotime($recent['fecha_publicacion'] ?? $recent['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Archive -->
                <?php if (!empty($archiveMonths)): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-archive text-primary me-2"></i>
                                Archivo
                            </h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php
                            $monthNames = [
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                            ];

                            foreach (array_slice($archiveMonths, 0, 12) as $archive):
                                ?>
                                <a href="?route=blog/archivo/<?= $archive['year'] ?>/<?= $archive['month'] ?>"
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <span>
                                        <?= $monthNames[$archive['month']] ?> <?= $archive['year'] ?>
                                    </span>
                                    <span class="badge bg-secondary rounded-pill">
                                        <?= $archive['post_count'] ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Popular Tags -->
                <?php if (!empty($popularTags)): ?>
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">
                                <i class="fas fa-tags text-primary me-2"></i>
                                Tags Populares
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($popularTags as $tag): ?>
                                    <a href="?route=blog/buscar?q=<?= urlencode($tag['nombre']) ?>"
                                       class="badge bg-light text-dark text-decoration-none"
                                       style="font-size: <?= 0.8 + ($tag['post_count'] / 10) ?>rem; padding: 0.5rem 0.75rem;">
                                        #<?= htmlspecialchars($tag['nombre']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
}

.blog-card:hover .card-img-top {
    transform: scale(1.05);
    overflow: hidden;
}

.search-form .form-control:focus {
    box-shadow: 0 0 0 0.25rem rgba(255,255,255,0.25);
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
