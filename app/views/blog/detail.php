<?php
/**
 * Vista Frontend: Detalle de Post del Blog
 * Con Schema.org, Open Graph, Twitter Cards y SEO completo
 */

use App\Core\Config;
use App\Core\Helpers;

// Las variables SEO ya vienen del controlador: $title, $metaDescription, $metaImage, $canonicalUrl
// Solo establecemos la URL canónica si no viene
$canonicalUrl = $canonicalUrl ?? (Config::getBaseUrl() . '?route=blog/' . $post['slug']);

require_once __DIR__ . '/../layouts/header.php';
?>

<!-- Schema.org JSON-LD -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BlogPosting",
  "headline": "<?= htmlspecialchars($post['titulo']) ?>",
  "image": <?= json_encode(!empty($metaImage) ? [$metaImage] : []) ?>,
  "datePublished": "<?= date('c', strtotime($post['fecha_publicacion'] ?? $post['created_at'])) ?>",
  "dateModified": "<?= date('c', strtotime($post['updated_at'])) ?>",
  "author": {
    "@type": "Person",
    "name": "<?= htmlspecialchars($post['autor_nombre']) ?>",
    "email": "<?= htmlspecialchars($post['autor_email']) ?>"
  },
  "publisher": {
    "@type": "Organization",
    "name": "<?= Config::APP_NAME ?>",
    "logo": {
      "@type": "ImageObject",
      "url": "<?= Config::getBaseUrl() ?>public/assets/images/logo.png"
    }
  },
  "description": "<?= htmlspecialchars($metaDescription) ?>",
  "mainEntityOfPage": {
    "@type": "WebPage",
    "@id": "<?= $canonicalUrl ?>"
  },
  "wordCount": "<?= str_word_count(strip_tags($post['contenido'])) ?>",
  "timeRequired": "PT<?= $post['tiempo_lectura'] ?>M",
  "articleBody": "<?= htmlspecialchars(strip_tags($post['contenido'])) ?>"
  <?php if ($post['categoria_nombre']): ?>
  ,"articleSection": "<?= htmlspecialchars($post['categoria_nombre']) ?>"
  <?php endif; ?>
  <?php if (!empty($tags)): ?>
  ,"keywords": "<?= htmlspecialchars(implode(', ', array_column($tags, 'nombre'))) ?>"
  <?php endif; ?>
}
</script>

<!-- Breadcrumb Schema -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    {
      "@type": "ListItem",
      "position": 1,
      "name": "Inicio",
      "item": "<?= Config::getBaseUrl() ?>"
    },
    {
      "@type": "ListItem",
      "position": 2,
      "name": "Blog",
      "item": "<?= Config::getBaseUrl() ?>?route=blog"
    }
    <?php if ($post['categoria_nombre']): ?>
    ,{
      "@type": "ListItem",
      "position": 3,
      "name": "<?= htmlspecialchars($post['categoria_nombre']) ?>",
      "item": "<?= Config::getBaseUrl() ?>?route=blog/categoria/<?= htmlspecialchars($post['categoria_slug']) ?>"
    }
    <?php endif; ?>
    ,{
      "@type": "ListItem",
      "position": <?= $post['categoria_nombre'] ? 4 : 3 ?>,
      "name": "<?= htmlspecialchars($post['titulo']) ?>",
      "item": "<?= $canonicalUrl ?>"
    }
  ]
}
</script>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="bg-light py-3">
    <div class="container">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="?route=home">Inicio</a></li>
            <li class="breadcrumb-item"><a href="?route=blog">Blog</a></li>
            <?php if ($post['categoria_nombre']): ?>
                <li class="breadcrumb-item">
                    <a href="?route=blog/categoria/<?= htmlspecialchars($post['categoria_slug']) ?>">
                        <?= htmlspecialchars($post['categoria_nombre']) ?>
                    </a>
                </li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page">
                <?= htmlspecialchars($post['titulo']) ?>
            </li>
        </ol>
    </div>
</nav>

<!-- Hero Image -->
<?php if ($post['imagen_destacada']): ?>
    <section class="post-hero" style="background-image: url('<?= Config::getBaseUrl() ?>public<?= htmlspecialchars($post['imagen_destacada']) ?>'); background-size: cover; background-position: center; height: 400px; position: relative;">
        <div style="position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.7));"></div>
        <div class="container h-100 position-relative">
            <div class="row h-100 align-items-end">
                <div class="col-lg-8 pb-5">
                    <?php if ($post['categoria_nombre']): ?>
                        <a href="?route=blog/categoria/<?= htmlspecialchars($post['categoria_slug']) ?>"
                           class="badge mb-3 text-decoration-none"
                           style="background-color: <?= htmlspecialchars($post['categoria_color']) ?>; font-size: 1rem; padding: 0.5rem 1rem;">
                            <i class="fas <?= htmlspecialchars($post['categoria_icono'] ?: 'fa-folder') ?> me-2"></i>
                            <?= htmlspecialchars($post['categoria_nombre']) ?>
                        </a>
                    <?php endif; ?>
                    <h1 class="display-4 text-white fw-bold mb-0"><?= htmlspecialchars($post['titulo']) ?></h1>
                </div>
            </div>
        </div>
    </section>
<?php endif; ?>

<!-- Main Content -->
<article class="post-content py-5">
    <div class="container">
        <div class="row">
            <!-- Article Content -->
            <div class="col-lg-8">
                <!-- Post Meta -->
                <div class="post-meta d-flex flex-wrap gap-3 mb-4 text-muted">
                    <div>
                        <i class="fas fa-user me-2"></i>
                        <strong><?= htmlspecialchars($post['autor_nombre']) ?></strong>
                    </div>
                    <div>
                        <i class="fas fa-calendar me-2"></i>
                        <?= date('d \d\e F \d\e Y', strtotime($post['fecha_publicacion'] ?? $post['created_at'])) ?>
                    </div>
                    <div>
                        <i class="fas fa-clock me-2"></i>
                        <?= $post['tiempo_lectura'] ?> min de lectura
                    </div>
                    <div>
                        <i class="fas fa-eye me-2"></i>
                        <?= number_format($post['vistas']) ?> vistas
                    </div>
                </div>

                <!-- Social Share -->
                <div class="post-share mb-4 p-3 bg-light rounded">
                    <div class="d-flex align-items-center gap-3">
                        <strong class="text-muted">Compartir:</strong>
                        <div class="d-flex gap-2">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($canonicalUrl) ?>"
                               target="_blank"
                               class="btn btn-sm btn-primary"
                               title="Compartir en Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($canonicalUrl) ?>&text=<?= urlencode($post['titulo']) ?>"
                               target="_blank"
                               class="btn btn-sm"
                               style="background-color: #1DA1F2; color: white;"
                               title="Compartir en Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://wa.me/?text=<?= urlencode($post['titulo'] . ' - ' . $canonicalUrl) ?>"
                               target="_blank"
                               class="btn btn-sm btn-success"
                               title="Compartir en WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?= urlencode($canonicalUrl) ?>&title=<?= urlencode($post['titulo']) ?>"
                               target="_blank"
                               class="btn btn-sm"
                               style="background-color: #0077B5; color: white;"
                               title="Compartir en LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <button type="button"
                                    class="btn btn-sm btn-secondary"
                                    onclick="copyToClipboard('<?= htmlspecialchars($canonicalUrl) ?>')"
                                    title="Copiar enlace">
                                <i class="fas fa-link"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Post Content -->
                <div class="post-body content-formatted mb-5">
                    <?= $post['contenido'] ?>
                </div>

                <!-- Tags -->
                <?php if (!empty($tags)): ?>
                    <div class="post-tags mb-5">
                        <h6 class="text-muted mb-3">
                            <i class="fas fa-tags me-2"></i>
                            Tags:
                        </h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($tags as $tag): ?>
                                <a href="?route=blog/buscar?q=<?= urlencode($tag['nombre']) ?>"
                                   class="badge bg-light text-dark text-decoration-none"
                                   style="font-size: 0.9rem; padding: 0.5rem 0.75rem;">
                                    #<?= htmlspecialchars($tag['nombre']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Author Bio (Opcional) -->
                <div class="author-bio card mb-5">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <div class="avatar-circle bg-primary text-white me-3"
                                 style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 2rem;">
                                <?= strtoupper(substr($post['autor_nombre'], 0, 1)) ?>
                            </div>
                            <div>
                                <h5 class="mb-1"><?= htmlspecialchars($post['autor_nombre']) ?></h5>
                                <p class="text-muted mb-0">
                                    Autor de <?= Config::APP_NAME ?>. Especialista en viajes al mundo maya.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Related Posts -->
                <?php if (!empty($relatedPosts)): ?>
                    <div class="related-posts">
                        <h3 class="mb-4">
                            <i class="fas fa-th-list text-primary me-2"></i>
                            Artículos Relacionados
                        </h3>
                        <div class="row g-4">
                            <?php foreach ($relatedPosts as $related): ?>
                                <div class="col-md-4">
                                    <div class="card h-100 shadow-sm hover-lift">
                                        <?php if ($related['imagen_destacada']): ?>
                                            <a href="?route=blog/<?= htmlspecialchars($related['slug']) ?>">
                                                <img src="<?= Config::getBaseUrl() ?>public<?= htmlspecialchars($related['imagen_destacada']) ?>"
                                                     class="card-img-top"
                                                     alt="<?= htmlspecialchars($related['titulo']) ?>"
                                                     style="height: 180px; object-fit: cover;"
                                                     loading="lazy"
                                                     decoding="async">
                                            </a>
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <span class="badge mb-2"
                                                  style="background-color: <?= htmlspecialchars($related['categoria_color'] ?? '#6c757d') ?>">
                                                <?= htmlspecialchars($related['categoria_nombre']) ?>
                                            </span>
                                            <h6 class="card-title">
                                                <a href="?route=blog/<?= htmlspecialchars($related['slug']) ?>"
                                                   class="text-dark text-decoration-none hover-primary">
                                                    <?= htmlspecialchars($related['titulo']) ?>
                                                </a>
                                            </h6>
                                            <p class="card-text text-muted small">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= $related['tiempo_lectura'] ?> min lectura
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Table of Contents (auto-generated from H2/H3) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list-ul text-primary me-2"></i>
                            Contenido
                        </h5>
                    </div>
                    <div class="card-body" id="tableOfContents">
                        <!-- Se genera dinámicamente con JavaScript -->
                        <p class="text-muted small">Cargando índice...</p>
                    </div>
                </div>

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
                            <?php foreach (array_slice($recentPosts, 0, 5) as $recent): ?>
                                <a href="?route=blog/<?= htmlspecialchars($recent['slug']) ?>"
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex align-items-start">
                                        <div class="flex-grow-1 small">
                                            <div class="fw-bold mb-1"><?= htmlspecialchars($recent['titulo']) ?></div>
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

                <!-- Categories -->
                <?php if (!empty($categories)): ?>
                    <div class="card shadow-sm">
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
            </div>
        </div>
    </div>
</article>

<!-- Call to Action -->
<section class="cta-section bg-primary text-white py-5">
    <div class="container text-center">
        <h2 class="mb-3">¿Listo para tu aventura?</h2>
        <p class="lead mb-4">Descubre nuestros tours y comienza a planificar tu viaje</p>
        <a href="?route=tours" class="btn btn-light btn-lg">
            <i class="fas fa-compass me-2"></i>
            Ver Tours Disponibles
        </a>
    </div>
</section>

<style>
.content-formatted {
    font-size: 1.1rem;
    line-height: 1.8;
}

.content-formatted h2 {
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-size: 1.8rem;
    font-weight: 700;
}

.content-formatted h3 {
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    font-size: 1.4rem;
    font-weight: 600;
}

.content-formatted p {
    margin-bottom: 1rem;
}

.content-formatted img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1.5rem 0;
}

.content-formatted ul,
.content-formatted ol {
    margin-bottom: 1rem;
    padding-left: 2rem;
}

.content-formatted li {
    margin-bottom: 0.5rem;
}

.content-formatted blockquote {
    border-left: 4px solid var(--bs-primary);
    padding-left: 1rem;
    margin-left: 0;
    font-style: italic;
    color: #6c757d;
}

.hover-lift {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.hover-lift:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 16px rgba(0,0,0,0.1) !important;
}

.hover-primary:hover {
    color: var(--bs-primary) !important;
}

#tableOfContents ul {
    list-style: none;
    padding-left: 0;
}

#tableOfContents li {
    margin-bottom: 0.5rem;
}

#tableOfContents a {
    color: #495057;
    text-decoration: none;
    display: block;
    padding: 0.25rem 0;
    transition: color 0.2s;
}

#tableOfContents a:hover {
    color: var(--bs-primary);
}

#tableOfContents li li a {
    padding-left: 1rem;
    font-size: 0.9rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Generate Table of Contents from H2 and H3
    const content = document.querySelector('.post-body');
    const toc = document.getElementById('tableOfContents');

    if (content && toc) {
        const headings = content.querySelectorAll('h2, h3');

        if (headings.length > 0) {
            let tocHTML = '<ul class="list-unstyled mb-0">';

            headings.forEach((heading, index) => {
                const id = 'heading-' + index;
                heading.id = id;

                const level = heading.tagName.toLowerCase();
                const text = heading.textContent;

                if (level === 'h2') {
                    tocHTML += `<li><a href="#${id}">${text}</a></li>`;
                } else {
                    tocHTML += `<li class="ms-3"><a href="#${id}">${text}</a></li>`;
                }
            });

            tocHTML += '</ul>';
            toc.innerHTML = tocHTML;
        } else {
            toc.innerHTML = '<p class="text-muted small mb-0">No hay encabezados disponibles</p>';
        }
    }

    // Smooth scroll para enlaces internos
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});

// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        alert('Enlace copiado al portapapeles');
    }, function(err) {
        console.error('Error al copiar:', err);
    });
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
