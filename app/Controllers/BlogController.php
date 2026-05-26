<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\Helpers;
use App\Core\Config;
use App\Models\BlogPost;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use Exception;


/**
 * Controller: BlogController
 * Gestión del blog con funcionalidades SEO Enterprise
 * Incluye métodos públicos (frontend) y admin (backend)
 */
class BlogController extends BaseController
{
    private $blogPost;
    private $blogCategory;
    private $blogTag;
    public function __construct()
    {
        parent::__construct();
        $this->blogPost = new BlogPost();
        $this->blogCategory = new BlogCategory();
        $this->blogTag = new BlogTag();
    }
    // ========================================================================
    // MÉTODOS PÚBLICOS (FRONTEND)
    // ========================================================================
    /**
     * Listado principal del blog con paginación
     * URL: ?route=blog o ?route=blog/page/2
     */
    public function list($page = 1)
    {
        $page = max(1, (int)$page);
        $perPage = Config::ITEMS_PER_PAGE;
        $offset = ($page - 1) * $perPage;
        // Obtener posts publicados
        $posts = $this->blogPost->getPublished($perPage, $offset);
        // Contar total para paginación
        $totalPosts = $this->db->fetchOne("
            SELECT COUNT(*) as total
            FROM blog_posts
            WHERE estado = 'published'
              AND (fecha_publicacion IS NULL OR fecha_publicacion <= NOW())
        ")['total'];
        $totalPages = ceil($totalPosts / $perPage);
        // Sidebar data
        $categories = $this->blogCategory->getWithPostCount(true);
        $archiveMonths = $this->blogPost->getArchiveMonths();
        $popularTags = $this->blogTag->getPopular(10);
        $recentPosts = $this->blogPost->getRecent(5);
        $this->view('blog/list', [
            'pageTitle' => 'Blog - ' . Config::APP_NAME,
            'metaDescription' => 'Descubre guías de viaje, consejos y artículos sobre destinos del mundo maya',
            'posts' => $posts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'categories' => $categories,
            'archiveMonths' => $archiveMonths,
            'popularTags' => $popularTags,
            'recentPosts' => $recentPosts
        ]);
    }
    /**
     * Detalle de post por slug
     * URL: ?route=blog/{slug}
     */
    public function detail($slug)
    {
        $post = $this->blogPost->getBySlug($slug);
        if (!$post) {
            $this->view('errors/404', ['pageTitle' => 'Post no encontrado']);
            return;
        }
        // Verificar si está publicado (excepto para admins)
        if ($post['estado'] !== 'published' && !Auth::hasRole('admin')) {
            $this->view('errors/404', ['pageTitle' => 'Post no encontrado']);
            return;
        }
        // Incrementar vistas
        $this->blogPost->incrementViews($post['id']);
        // Obtener tags del post
        $tags = $this->blogPost->getTags($post['id']);
        // Obtener posts relacionados
        $relatedPosts = [];
        if ($post['categoria_id']) {
            $relatedPosts = $this->blogPost->getRelated($post['id'], $post['categoria_id'], 3);
        }
        // Sidebar data
        $recentPosts = $this->blogPost->getRecent(5);
        $categories = $this->blogCategory->getWithPostCount(true);
        $this->view('blog/detail', [
            'title' => $post['meta_title'] ?: $post['titulo'],
            'metaDescription' => $post['meta_description'] ?: $post['descripcion_corta'],
            'metaKeywords' => $post['meta_keywords'],
            'canonicalUrl' => $post['canonical_url'],
            'metaImage' => $post['og_image'] ?: ($post['imagen_destacada'] ? Config::getBaseUrl() . 'public' . $post['imagen_destacada'] : ''),
            'post' => $post,
            'tags' => $tags,
            'relatedPosts' => $relatedPosts,
            'recentPosts' => $recentPosts,
            'categories' => $categories
        ]);
    }
    /**
     * Posts por categoría
     * URL: ?route=blog/categoria/{slug}
     */
    public function category($slug)
    {
        $category = $this->blogCategory->getBySlug($slug);
        if (!$category || !$category['activo']) {
            $this->view('errors/404', ['pageTitle' => 'Categoría no encontrada']);
            return;
        }
        // Obtener posts de la categoría
        $posts = $this->blogPost->getByCategory($category['id']);
        // Sidebar data
        $categories = $this->blogCategory->getWithPostCount(true);
        $recentPosts = $this->blogPost->getRecent(5);
        $this->view('blog/category', [
            'pageTitle' => $category['meta_title'] ?: $category['nombre'] . ' - Blog',
            'metaDescription' => $category['meta_description'] ?: $category['descripcion'],
            'category' => $category,
            'posts' => $posts,
            'categories' => $categories,
            'recentPosts' => $recentPosts
        ]);
    }
    /**
     * Archivo por fecha
     * URL: ?route=blog/archivo/{year} o ?route=blog/archivo/{year}/{month}
     */
    public function archive($year, $month = null)
    {
        $year = (int)$year;
        $month = $month ? (int)$month : null;
        $posts = $this->blogPost->getArchived($year, $month);
        // Título dinámico
        if ($month) {
            $monthNames = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];
            $pageTitle = $monthNames[$month] . ' ' . $year . ' - Archivo del Blog';
        } else {
            $pageTitle = 'Archivo ' . $year . ' - Blog';
        }
        // Sidebar data
        $categories = $this->blogCategory->getWithPostCount(true);
        $archiveMonths = $this->blogPost->getArchiveMonths();
        $recentPosts = $this->blogPost->getRecent(5);
        $this->view('blog/archive', [
            'pageTitle' => $pageTitle,
            'year' => $year,
            'month' => $month,
            'posts' => $posts,
            'categories' => $categories,
            'archiveMonths' => $archiveMonths,
            'recentPosts' => $recentPosts
        ]);
    }
    /**
     * Búsqueda de posts
     * URL: ?route=blog/buscar&q=termino
     */
    public function search()
    {
        $query = $this->getInput('q');
        if (empty($query)) {
            $this->redirect('blog');
            return;
        }
        $posts = $this->blogPost->searchPosts($query);
        // Sidebar data
        $categories = $this->blogCategory->getWithPostCount(true);
        $recentPosts = $this->blogPost->getRecent(5);
        $this->view('blog/search', [
            'pageTitle' => 'Buscar: ' . htmlspecialchars($query) . ' - Blog',
            'query' => $query,
            'posts' => $posts,
            'categories' => $categories,
            'recentPosts' => $recentPosts
        ]);
    }
    /**
     * Feed RSS del blog
     * URL: ?route=blog/rss
     */
    public function rss()
    {
        $posts = $this->blogPost->getPublished(20);
        header('Content-Type: application/rss+xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">';
        echo '<channel>';
        echo '<title>' . htmlspecialchars(Config::APP_NAME . ' - Blog') . '</title>';
        echo '<link>' . htmlspecialchars(Config::getBaseUrl()) . '</link>';
        echo '<description>' . htmlspecialchars(Config::APP_DESCRIPTION) . '</description>';
        echo '<language>es</language>';
        echo '<atom:link href="' . htmlspecialchars(Config::getBaseUrl() . '?route=blog/rss') . '" rel="self" type="application/rss+xml" />';
        foreach ($posts as $post) {
            echo '<item>';
            echo '<title>' . htmlspecialchars($post['titulo']) . '</title>';
            echo '<link>' . htmlspecialchars(Config::getBaseUrl() . '?route=blog/' . $post['slug']) . '</link>';
            echo '<description>' . htmlspecialchars($post['descripcion_corta']) . '</description>';
            echo '<pubDate>' . date('r', strtotime($post['fecha_publicacion'] ?: $post['created_at'])) . '</pubDate>';
            echo '<guid>' . htmlspecialchars(Config::getBaseUrl() . '?route=blog/' . $post['slug']) . '</guid>';
            if ($post['categoria_nombre']) {
                echo '<category>' . htmlspecialchars($post['categoria_nombre']) . '</category>';
            }
            echo '</item>';
        }
        echo '</channel>';
        echo '</rss>';
    }
    // ========================================================================
    // MÉTODOS ADMIN (BACKEND)
    // ========================================================================
    /**
     * Listado de posts en admin
     * URL: ?route=admin/blog
     */
    public function adminList()
    {
        Auth::requireRole('admin');
        // Filtros
        $filters = [
            'estado' => $this->getInput('estado'),
            'categoria' => $this->getInput('categoria'),
            'buscar' => $this->getInput('buscar')
        ];
        // Query base
        $sql = "
            SELECT
                p.*,
                c.nombre as categoria_nombre,
                u.nombre as autor_nombre
            FROM blog_posts p
            LEFT JOIN blog_categories c ON p.categoria_id = c.id
            LEFT JOIN usuarios u ON p.autor_id = u.id
            WHERE 1=1
        ";
        $params = [];
        // Aplicar filtros
        if (!empty($filters['estado'])) {
            $sql .= " AND p.estado = :estado";
            $params['estado'] = $filters['estado'];
        }
        if (!empty($filters['categoria'])) {
            $sql .= " AND p.categoria_id = :categoria";
            $params['categoria'] = $filters['categoria'];
        }
        if (!empty($filters['buscar'])) {
            $sql .= " AND (p.titulo LIKE :buscar OR p.contenido LIKE :buscar)";
            $params['buscar'] = '%' . $filters['buscar'] . '%';
        }
        $sql .= " ORDER BY p.updated_at DESC";
        $posts = $this->db->fetchAll($sql, $params);
        // Estadísticas
        $stats = $this->db->fetchOne("
            SELECT
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'published' THEN 1 ELSE 0 END) as published,
                SUM(CASE WHEN estado = 'draft' THEN 1 ELSE 0 END) as drafts,
                SUM(CASE WHEN destacado = 1 THEN 1 ELSE 0 END) as featured
            FROM blog_posts
        ");
        $categories = $this->blogCategory->getAll();
        $this->view('admin/blog/list', [
            'pageTitle' => 'Gestión del Blog',
            'posts' => $posts,
            'stats' => $stats,
            'categories' => $categories,
            'filters' => $filters
        ]);
    }
    /**
     * Formulario para crear nuevo post
     * URL: ?route=admin/blog/crear
     */
    public function adminCreate()
    {
        Auth::requireRole('admin');

        // Si es POST, procesar creación
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->adminStore();
            return;
        }

        // Si es GET, mostrar formulario
        $categories = $this->blogCategory->getActive();
        $allTags = $this->blogTag->getAll();
        $this->view('admin/blog/form', [
            'pageTitle' => 'Nuevo Post - Blog',
            'post' => null,
            'categories' => $categories,
            'allTags' => $allTags
        ]);
    }
    /**
     * Guardar nuevo post
     * URL: POST ?route=admin/blog/crear
     */
    public function adminStore()
    {
        Auth::requireRole('admin');
        $this->validateCsrf();
        $data = [
            'titulo' => $this->getInput('titulo'),
            'slug' => $this->getInput('slug'),
            'descripcion_corta' => $this->getInput('descripcion_corta'),
            'contenido' => $this->getInput('contenido'),
            'imagen_destacada' => $this->getInput('imagen_destacada'),
            'imagen_alt' => $this->getInput('imagen_alt'),
            'categoria_id' => $this->getInput('categoria_id') ?: null,
            'autor_id' => Auth::getUser()['id'],
            'meta_title' => $this->getInput('meta_title'),
            'meta_description' => $this->getInput('meta_description'),
            'meta_keywords' => $this->getInput('meta_keywords'),
            'canonical_url' => $this->getInput('canonical_url'),
            'og_image' => $this->getInput('og_image'),
            'focus_keyword' => $this->getInput('focus_keyword'),
            'estado' => $this->getInput('estado') ?: 'draft',
            'fecha_publicacion' => $this->getInput('fecha_publicacion') ?: null,
            'destacado' => $this->getInput('destacado') ? 1 : 0,
            'tiempo_lectura' => 0,
            'seo_score' => 0,
            'readability_score' => 0
        ];
        // Validar
        $validation = $this->blogPost->validatePost($data, false);
        if (!$validation['valid']) {
            Helpers::setFlashMessage('error', implode('<br>', $validation['errors']));
            $this->redirect('admin/blog/crear');
            return;
        }
        // Calcular métricas
        $data['tiempo_lectura'] = $this->blogPost->calculateReadingTime($data['contenido']);
        $seoAnalysis = $this->blogPost->calculateSeoScore($data);
        $data['seo_score'] = $seoAnalysis['score'];
        $readabilityAnalysis = $this->blogPost->calculateReadabilityScore($data['contenido']);
        $data['readability_score'] = $readabilityAnalysis['score'];
        // Auto-generar canonical URL si está vacío
        if (empty($data['canonical_url'])) {
            $data['canonical_url'] = Config::getBaseUrl() . '?route=blog/' . $data['slug'];
        }
        // Crear post
        $postId = $this->blogPost->create($data);
        // Sincronizar tags
        $tagIds = $this->getInput('tag_ids');
        if ($tagIds && is_array($tagIds)) {
            $this->blogPost->syncTags($postId, $tagIds);
        }
        Helpers::setFlashMessage('success', 'Post creado exitosamente');
        $this->redirect('admin/blog/editar/' . $postId);
    }
    /**
     * Formulario para editar post
     * URL: ?route=admin/blog/editar/{id}
     */
    public function adminEdit($id)
    {
        Auth::requireRole('admin');

        // Si es POST, procesar actualización
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->adminUpdate($id);
            return;
        }

        // Si es GET, mostrar formulario
        $post = $this->blogPost->find($id);
        if (!$post) {
            Helpers::setFlashMessage('error', 'Post no encontrado');
            $this->redirect('admin/blog');
            return;
        }
        $categories = $this->blogCategory->getActive();
        $allTags = $this->blogTag->getAll();
        $postTags = $this->blogPost->getTags($id);
        $this->view('admin/blog/form', [
            'pageTitle' => 'Editar Post - ' . $post['titulo'],
            'post' => $post,
            'categories' => $categories,
            'allTags' => $allTags,
            'postTags' => $postTags
        ]);
    }
    /**
     * Actualizar post
     * URL: POST ?route=admin/blog/editar/{id}
     */
    public function adminUpdate($id)
    {
        Auth::requireRole('admin');

        error_log("=== adminUpdate called for ID: {$id} ===");
        error_log("POST data: " . print_r($_POST, true));

        $this->validateCsrf();
        $post = $this->blogPost->find($id);
        if (!$post) {
            error_log("Post not found: {$id}");
            Helpers::setFlashMessage('error', 'Post no encontrado');
            $this->redirect('admin/blog');
            return;
        }

        $data = [
            'id' => $id, // Requerido para validación de slug único
            'titulo' => $this->getInput('titulo'),
            'slug' => $this->getInput('slug'),
            'descripcion_corta' => $this->getInput('descripcion_corta'),
            'contenido' => $this->getInput('contenido'),
            'imagen_destacada' => $this->getInput('imagen_destacada'),
            'imagen_alt' => $this->getInput('imagen_alt'),
            'categoria_id' => $this->getInput('categoria_id') ?: null,
            'autor_id' => $post['autor_id'], // Mantener el autor original
            'meta_title' => $this->getInput('meta_title'),
            'meta_description' => $this->getInput('meta_description'),
            'meta_keywords' => $this->getInput('meta_keywords'),
            'canonical_url' => $this->getInput('canonical_url'),
            'og_image' => $this->getInput('og_image'),
            'focus_keyword' => $this->getInput('focus_keyword'),
            'estado' => $this->getInput('estado') ?: 'draft',
            'fecha_publicacion' => $this->getInput('fecha_publicacion') ?: null,
            'destacado' => $this->getInput('destacado') ? 1 : 0
        ];

        error_log("Data to update: " . print_r($data, true));

        // Validar
        $validation = $this->blogPost->validatePost($data, true);
        if (!$validation['valid']) {
            error_log("Validation failed: " . print_r($validation['errors'], true));
            Helpers::setFlashMessage('error', implode('<br>', $validation['errors']));
            $this->redirect('admin/blog/editar/' . $id);
            return;
        }

        // Recalcular métricas
        $data['tiempo_lectura'] = $this->blogPost->calculateReadingTime($data['contenido']);
        $seoAnalysis = $this->blogPost->calculateSeoScore($data);
        $data['seo_score'] = $seoAnalysis['score'];
        $readabilityAnalysis = $this->blogPost->calculateReadabilityScore($data['contenido']);
        $data['readability_score'] = $readabilityAnalysis['score'];

        // Actualizar
        try {
            $result = $this->blogPost->update($id, $data);
            error_log("Update result: " . ($result ? 'success' : 'failed'));
        } catch (Exception $e) {
            error_log("Update exception: " . $e->getMessage());
            Helpers::setFlashMessage('error', 'Error al actualizar: ' . $e->getMessage());
            $this->redirect('admin/blog/editar/' . $id);
            return;
        }

        // Sincronizar tags
        $tagIds = $this->getInput('tag_ids');
        if (is_array($tagIds)) {
            $this->blogPost->syncTags($id, $tagIds);
        }

        error_log("=== adminUpdate completed successfully ===");
        Helpers::setFlashMessage('success', 'Post actualizado exitosamente');
        $this->redirect('admin/blog/editar/' . $id);
    }
    /**
     * Eliminar post
     * URL: POST ?route=admin/blog/eliminar/{id}
     */
    public function adminDelete($id)
    {
        Auth::requireRole('admin');
        $this->validateCsrf();
        $post = $this->blogPost->find($id);
        if (!$post) {
            $this->json(['success' => false, 'message' => 'Post no encontrado'], 404);
            return;
        }
        // Eliminar (también eliminará relaciones con tags por CASCADE)
        $this->db->delete('blog_posts', 'id = :id', ['id' => $id]);
        $this->json(['success' => true, 'message' => 'Post eliminado exitosamente']);
    }
    /**
     * Toggle estado (draft/published) vía AJAX
     * URL: POST ?route=admin/blog/toggle-status
     */
    public function adminToggleStatus()
    {
        Auth::requireRole('admin');
        $this->validateCsrf();
        $id = $this->getInput('id');
        $post = $this->blogPost->find($id);
        if (!$post) {
            $this->json(['success' => false, 'message' => 'Post no encontrado'], 404);
            return;
        }
        $newStatus = $post['estado'] === 'published' ? 'draft' : 'published';

        $updateData = [
            'estado' => $newStatus,
            'fecha_publicacion' => $newStatus === 'published' && !$post['fecha_publicacion'] ? date('Y-m-d H:i:s') : $post['fecha_publicacion']
        ];

        try {
            $this->blogPost->update($id, $updateData);
            $this->json([
                'success' => true,
                'message' => 'Estado actualizado',
                'newStatus' => $newStatus
            ]);
        } catch (Exception $e) {
            error_log("Error toggling status: " . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Error al actualizar estado: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Toggle destacado vía AJAX
     * URL: POST ?route=admin/blog/toggle-featured
     */
    public function adminToggleFeatured()
    {
        Auth::requireRole('admin');
        $this->validateCsrf();
        $id = $this->getInput('id');
        $post = $this->blogPost->find($id);
        if (!$post) {
            $this->json(['success' => false, 'message' => 'Post no encontrado'], 404);
            return;
        }
        $newFeatured = $post['destacado'] ? 0 : 1;

        try {
            $this->blogPost->update($id, ['destacado' => $newFeatured]);
            $this->json([
                'success' => true,
                'message' => 'Destacado actualizado',
                'featured' => $newFeatured
            ]);
        } catch (Exception $e) {
            error_log("Error toggling featured: " . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Error al actualizar destacado: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Analizar SEO en tiempo real vía AJAX
     * URL: POST ?route=admin/blog/analizar-seo
     */
    public function adminAnalyzeSeo()
    {
        Auth::requireRole('admin');
        $this->validateCsrf();
        $data = [
            'titulo' => $this->getInput('titulo'),
            'contenido' => $this->getInput('contenido'),
            'meta_title' => $this->getInput('meta_title'),
            'meta_description' => $this->getInput('meta_description'),
            'slug' => $this->getInput('slug'),
            'focus_keyword' => $this->getInput('focus_keyword'),
            'imagen_destacada' => $this->getInput('imagen_destacada'),
            'imagen_alt' => $this->getInput('imagen_alt')
        ];
        $seoAnalysis = $this->blogPost->calculateSeoScore($data);
        $readabilityAnalysis = $this->blogPost->calculateReadabilityScore($data['contenido']);
        $this->json([
            'success' => true,
            'seo' => $seoAnalysis,
            'readability' => $readabilityAnalysis,
            'suggestions' => array_merge(
                $seoAnalysis['suggestions'],
                $readabilityAnalysis['suggestions']
            )
        ]);
    }
    /**
     * Generar slug automáticamente vía AJAX
     * URL: POST ?route=admin/blog/generar-slug
     */
    public function adminGenerateSlug()
    {
        Auth::requireRole('admin');
        $titulo = $this->getInput('titulo');
        if (empty($titulo)) {
            $this->json(['success' => false, 'message' => 'Título requerido'], 400);
            return;
        }
        // Generar slug
        $slug = $this->generateSlug($titulo);
        // Verificar unicidad
        $counter = 1;
        $originalSlug = $slug;
        while ($this->slugExists($slug, $this->getInput('post_id'))) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        $this->json([
            'success' => true,
            'slug' => $slug
        ]);
    }
    /**
     * Subir imagen destacada vía AJAX
     * URL: POST ?route=admin/blog/subir-imagen
     */
    public function adminUploadImage()
    {
        Auth::requireRole('admin');

        // Log para debugging
        error_log("adminUploadImage called");
        error_log("FILES: " . print_r($_FILES, true));

        if (!isset($_FILES['image'])) {
            $this->json(['success' => false, 'message' => 'No se recibió ninguna imagen'], 400);
            return;
        }

        if ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = 'Error al subir imagen (código: ' . $_FILES['image']['error'] . ')';
            error_log($errorMsg);
            $this->json(['success' => false, 'message' => $errorMsg], 400);
            return;
        }

        $file = $_FILES['image'];

        // Validar tipo
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($file['type'], $allowedTypes)) {
            $this->json(['success' => false, 'message' => 'Tipo de archivo no permitido: ' . $file['type']], 400);
            return;
        }

        // Validar tamaño (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            $this->json(['success' => false, 'message' => 'La imagen no debe exceder 2MB'], 400);
            return;
        }

        // Crear directorio si no existe
        $uploadDir = __DIR__ . '/../../public/uploads/blog/';
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $this->json(['success' => false, 'message' => 'No se pudo crear el directorio de uploads'], 500);
                return;
            }
        }

        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'blog-' . time() . '-' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            error_log("Error moving file from {$file['tmp_name']} to {$filepath}");
            $this->json(['success' => false, 'message' => 'Error al guardar imagen en el servidor'], 500);
            return;
        }

        // Retornar ruta relativa
        $relativePath = '/uploads/blog/' . $filename;

        error_log("Image uploaded successfully: {$relativePath}");

        $this->json([
            'success' => true,
            'message' => 'Imagen subida exitosamente',
            'path' => $relativePath,
            'url' => Config::getBaseUrl() . 'public' . $relativePath
        ]);
    }
    // ========================================================================
    // MÉTODOS AUXILIARES
    // ========================================================================
    /**
     * Generar slug a partir de título
     */
    private function generateSlug($text)
    {
        $slug = mb_strtolower($text);
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT', $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug;
    }
    /**
     * Verificar si un slug ya existe
     */
    private function slugExists($slug, $excludeId = null)
    {
        $sql = "SELECT id FROM blog_posts WHERE slug = :slug";
        $params = ['slug' => $slug];
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        $result = $this->db->fetchOne($sql, $params);
        return !empty($result);
    }
}