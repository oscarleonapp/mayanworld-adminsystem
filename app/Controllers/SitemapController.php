<?php
/**
 * SitemapController
 * Genera sitemaps XML para SEO (blog, categorías, tours, páginas estáticas)
 */

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Config;
use App\Core\Database;
use App\Models\BlogPost;
use App\Models\BlogCategory;

class SitemapController extends BaseController {

    /**
     * Sitemap index principal que referencia todos los sitemaps
     */
    public function index() {
        header('Content-Type: application/xml; charset=utf-8');

        $baseUrl = Config::getBaseUrl();
        $lastmod = date('c');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Sitemap del blog
        echo "  <sitemap>\n";
        echo "    <loc>{$baseUrl}sitemap-blog.xml</loc>\n";
        echo "    <lastmod>{$lastmod}</lastmod>\n";
        echo "  </sitemap>\n";

        // Sitemap de categorías del blog
        echo "  <sitemap>\n";
        echo "    <loc>{$baseUrl}sitemap-blog-categories.xml</loc>\n";
        echo "    <lastmod>{$lastmod}</lastmod>\n";
        echo "  </sitemap>\n";

        // Sitemap de tours
        echo "  <sitemap>\n";
        echo "    <loc>{$baseUrl}sitemap-tours.xml</loc>\n";
        echo "    <lastmod>{$lastmod}</lastmod>\n";
        echo "  </sitemap>\n";

        // Sitemap de páginas estáticas
        echo "  <sitemap>\n";
        echo "    <loc>{$baseUrl}sitemap-pages.xml</loc>\n";
        echo "    <lastmod>{$lastmod}</lastmod>\n";
        echo "  </sitemap>\n";

        echo '</sitemapindex>';
        exit;
    }

    /**
     * Sitemap del blog (posts publicados)
     */
    public function blog() {
        header('Content-Type: application/xml; charset=utf-8');

        $blogModel = new BlogPost();

        $baseUrl = Config::getBaseUrl();
        $posts = $blogModel->getPublished();

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($posts as $post) {
            $url = $baseUrl . '?route=blog/' . htmlspecialchars($post['slug']);
            $lastmod = date('c', strtotime($post['updated_at']));

            echo "  <url>\n";
            echo "    <loc>{$url}</loc>\n";
            echo "    <lastmod>{$lastmod}</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.8</priority>\n";

            // Incluir imagen destacada si existe
            if (!empty($post['imagen_destacada'])) {
                $imageUrl = $baseUrl . 'public' . htmlspecialchars($post['imagen_destacada']);
                $imageTitle = htmlspecialchars($post['imagen_alt'] ?: $post['titulo']);

                echo "    <image:image>\n";
                echo "      <image:loc>{$imageUrl}</image:loc>\n";
                echo "      <image:title>{$imageTitle}</image:title>\n";
                echo "    </image:image>\n";
            }

            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }

    /**
     * Sitemap de categorías del blog
     */
    public function blogCategories() {
        header('Content-Type: application/xml; charset=utf-8');

        $categoryModel = new BlogCategory();

        $baseUrl = Config::getBaseUrl();
        $categories = $categoryModel->getActive();

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($categories as $category) {
            $url = $baseUrl . '?route=blog/categoria/' . htmlspecialchars($category['slug']);
            $lastmod = date('c', strtotime($category['updated_at']));

            echo "  <url>\n";
            echo "    <loc>{$url}</loc>\n";
            echo "    <lastmod>{$lastmod}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.6</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }

    /**
     * Sitemap de tours
     */
    public function tours() {
        header('Content-Type: application/xml; charset=utf-8');

        $db = Database::getInstance();
        $baseUrl = Config::getBaseUrl();

        // Obtener tours activos
        $tours = $db->fetchAll("
            SELECT id, nombre, slug, updated_at, imagen_principal
            FROM tours
            WHERE activo = 1
            ORDER BY id DESC
        ");

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        echo '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($tours as $tour) {
            $slug = $tour['slug'] ?: $tour['id'];
            $url = $baseUrl . '?route=tour/' . htmlspecialchars($slug);
            $lastmod = date('c', strtotime($tour['updated_at']));

            echo "  <url>\n";
            echo "    <loc>{$url}</loc>\n";
            echo "    <lastmod>{$lastmod}</lastmod>\n";
            echo "    <changefreq>weekly</changefreq>\n";
            echo "    <priority>0.9</priority>\n";

            // Incluir imagen principal si existe
            if (!empty($tour['imagen_principal'])) {
                $imageUrl = $baseUrl . 'public' . htmlspecialchars($tour['imagen_principal']);
                $imageTitle = htmlspecialchars($tour['nombre']);

                echo "    <image:image>\n";
                echo "      <image:loc>{$imageUrl}</image:loc>\n";
                echo "      <image:title>{$imageTitle}</image:title>\n";
                echo "    </image:image>\n";
            }

            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }

    /**
     * Sitemap de páginas estáticas
     */
    public function pages() {
        header('Content-Type: application/xml; charset=utf-8');

        $db = Database::getInstance();
        $baseUrl = Config::getBaseUrl();

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Página principal
        echo "  <url>\n";
        echo "    <loc>{$baseUrl}</loc>\n";
        echo "    <lastmod>" . date('c') . "</lastmod>\n";
        echo "    <changefreq>daily</changefreq>\n";
        echo "    <priority>1.0</priority>\n";
        echo "  </url>\n";

        // Páginas estáticas de la base de datos
        $staticPages = $db->fetchAll("
            SELECT slug, updated_at
            FROM static_pages
            WHERE activo = 1
            ORDER BY slug
        ");

        foreach ($staticPages as $page) {
            $url = $baseUrl . '?route=page/' . htmlspecialchars($page['slug']);
            $lastmod = date('c', strtotime($page['updated_at']));

            echo "  <url>\n";
            echo "    <loc>{$url}</loc>\n";
            echo "    <lastmod>{$lastmod}</lastmod>\n";
            echo "    <changefreq>monthly</changefreq>\n";
            echo "    <priority>0.5</priority>\n";
            echo "  </url>\n";
        }

        // Páginas principales del sitio
        $mainPages = [
            ['route' => 'tours', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['route' => 'blog', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['route' => 'about', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['route' => 'contact', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['route' => 'faq', 'priority' => '0.6', 'changefreq' => 'monthly'],
        ];

        foreach ($mainPages as $page) {
            $url = $baseUrl . '?route=' . $page['route'];

            echo "  <url>\n";
            echo "    <loc>{$url}</loc>\n";
            echo "    <lastmod>" . date('c') . "</lastmod>\n";
            echo "    <changefreq>{$page['changefreq']}</changefreq>\n";
            echo "    <priority>{$page['priority']}</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>';
        exit;
    }
}
