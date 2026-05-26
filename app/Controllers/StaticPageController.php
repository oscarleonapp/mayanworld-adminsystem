<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Core\Config;
use App\Helpers\AuditLogger;
use App\Helpers\NotificationHelper;
use App\Helpers\CompanyConfigHelper;
use Exception;

/**
 * StaticPageController
 *
 * Gestiona páginas estáticas del sitio (Sobre nosotros, Términos, Privacidad, etc.)
 * Incluye editor WYSIWYG (TinyMCE) para contenido HTML rico
 */
class StaticPageController extends BaseController
{
    private $requiresAuth = true;

    public function __construct()
    {
        parent::__construct();

        // Solo requerir admin para métodos administrativos
        // Los métodos públicos (view, viewBySlug) no requieren auth
        $publicMethods = ['view', 'viewBySlug'];
        $currentMethod = debug_backtrace()[1]['function'] ?? '';

        if (!in_array($currentMethod, $publicMethods)) {
            $this->requireAdmin();
        }
    }

    /**
     * Listar páginas estáticas
     */
    public function list()
    {
        try {
            $search = $this->getInput('search');

            $sql = "SELECT * FROM static_pages WHERE 1=1";
            $params = [];

            if ($search) {
                $sql .= " AND (title LIKE :search OR slug LIKE :search OR content LIKE :search)";
                $params['search'] = '%' . $search . '%';
            }

            $sql .= " ORDER BY title ASC";

            $pages = $this->db->fetchAll($sql, $params);

            $this->view('admin/static_pages/list', [
                'title' => 'Páginas Estáticas',
                'pages' => $pages,
                'search' => $search
            ]);

        } catch (Exception $e) {
            $this->handleValidationErrors('Error al cargar páginas: ' . $e->getMessage(), 'admin');
        }
    }

    /**
     * Crear nueva página
     */
    public function create()
    {
        if (Helpers::isPost()) {
            $this->validateCsrf();
            $this->storePage();
            return;
        }

        $this->view('admin/static_pages/form', [
            'title' => 'Crear Página Estática',
            'page' => null,
            'isEdit' => false
        ]);
    }

    /**
     * Guardar nueva página
     */
    private function storePage()
    {
        try {
            $title = $this->sanitizeInput($this->getInput('title'));
            $slug = $this->getInput('slug');
            $content = $this->getInput('content'); // No sanitizar HTML del editor
            $meta_title = $this->sanitizeInput($this->getInput('meta_title'));
            $meta_description = $this->sanitizeInput($this->getInput('meta_description'));
            $meta_keywords = $this->sanitizeInput($this->getInput('meta_keywords'));
            $status = $this->getInput('status', 'published');
            $show_in_menu = (int)$this->getInput('show_in_menu', 0);
            $menu_order = (int)$this->getInput('menu_order', 0);

            // Validaciones
            $errors = [];
            if (empty($title)) {
                $errors[] = 'El título es requerido';
            }

            // Auto-generar slug si está vacío
            if (empty($slug)) {
                $slug = Helpers::slug($title);
            } else {
                $slug = Helpers::slug($slug);
            }

            // Verificar que el slug no exista
            $existing = $this->db->fetch(
                "SELECT id FROM static_pages WHERE slug = :slug",
                ['slug' => $slug]
            );

            if ($existing) {
                $errors[] = 'El slug ya está en uso. Por favor, elige otro.';
            }

            if (!empty($errors)) {
                $this->handleValidationErrors($errors);
                return;
            }

            // Auto-generar meta_title si está vacío
            if (empty($meta_title)) {
                $meta_title = $title;
            }

            // Preparar datos
            $data = [
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'meta_keywords' => $meta_keywords,
                'status' => $status,
                'show_in_menu' => $show_in_menu,
                'menu_order' => $menu_order
            ];

            // Insertar
            $newId = $this->db->insert('static_pages', $data);

            // Registrar en audit log
            AuditLogger::log(
                'crear',
                'static_pages',
                $newId,
                $title,
                null,
                $data
            );

            // Notificar admins
            NotificationHelper::notifyAllAdmins(
                'sistema',
                'Nueva Página',
                "Se creó la página: {$title}",
                Config::getBaseUrl() . '?route=admin/pages/edit/' . $newId,
                'fas fa-file-alt',
                'baja'
            );

            $this->redirect('admin/pages/edit/' . $newId, 'Página creada correctamente', 'success');

        } catch (Exception $e) {
            $this->handleValidationErrors('Error al crear página: ' . $e->getMessage());
        }
    }

    /**
     * Editar página con editor WYSIWYG
     */
    public function edit($id)
    {
        if (Helpers::isPost()) {
            $this->validateCsrf();
            $this->updatePage($id);
            return;
        }

        try {
            $page = $this->db->fetch("SELECT * FROM static_pages WHERE id = :id", ['id' => $id]);

            if (!$page) {
                $this->redirect('admin/pages', 'Página no encontrada', 'error');
                return;
            }

            $this->view('admin/static_pages/form', [
                'title' => 'Editar Página: ' . $page['title'],
                'page' => $page,
                'isEdit' => true
            ]);

        } catch (Exception $e) {
            $this->redirect('admin/pages', 'Error al cargar página: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Actualizar página
     */
    private function updatePage($id)
    {
        try {
            $title = $this->sanitizeInput($this->getInput('title'));
            $slug = $this->getInput('slug');
            $content = $this->getInput('content'); // No sanitizar HTML del editor
            $meta_title = $this->sanitizeInput($this->getInput('meta_title'));
            $meta_description = $this->sanitizeInput($this->getInput('meta_description'));
            $meta_keywords = $this->sanitizeInput($this->getInput('meta_keywords'));
            $status = $this->getInput('status', 'published');
            $show_in_menu = (int)$this->getInput('show_in_menu', 0);
            $menu_order = (int)$this->getInput('menu_order', 0);

            // Obtener datos anteriores
            $datosAnteriores = $this->db->fetch("SELECT * FROM static_pages WHERE id = :id", ['id' => $id]);

            if (!$datosAnteriores) {
                $this->redirect('admin/pages', 'Página no encontrada', 'error');
                return;
            }

            // Validaciones
            $errors = [];
            if (empty($title)) {
                $errors[] = 'El título es requerido';
            }

            // Auto-generar slug si está vacío
            if (empty($slug)) {
                $slug = Helpers::slug($title);
            } else {
                $slug = Helpers::slug($slug);
            }

            // Verificar que el slug no exista (excepto en este registro)
            $existing = $this->db->fetch(
                "SELECT id FROM static_pages WHERE slug = :slug AND id != :id",
                ['slug' => $slug, 'id' => $id]
            );

            if ($existing) {
                $errors[] = 'El slug ya está en uso. Por favor, elige otro.';
            }

            if (!empty($errors)) {
                $this->handleValidationErrors($errors);
                return;
            }

            // Auto-generar meta_title si está vacío
            if (empty($meta_title)) {
                $meta_title = $title;
            }

            // Preparar datos
            $data = [
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'meta_keywords' => $meta_keywords,
                'status' => $status,
                'show_in_menu' => $show_in_menu,
                'menu_order' => $menu_order
            ];

            // Actualizar
            $updated = $this->db->update('static_pages', $data, 'id = :id', ['id' => $id]);

            // Registrar en audit log
            AuditLogger::log(
                'editar',
                'static_pages',
                $id,
                $title,
                $datosAnteriores,
                $data
            );

            $this->redirect('admin/pages', 'Página actualizada correctamente', 'success');

        } catch (Exception $e) {
            $this->handleValidationErrors('Error al actualizar página: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar página
     */
    public function delete($id)
    {
        $this->validateCsrf();

        try {
            $page = $this->db->fetch("SELECT * FROM static_pages WHERE id = :id", ['id' => $id]);

            if (!$page) {
                if (Helpers::isAjax()) {
                    $this->json(['success' => false, 'message' => 'Página no encontrada'], 404);
                } else {
                    $this->redirect('admin/pages', 'Página no encontrada', 'error');
                }
                return;
            }

            // Eliminar
            $deleted = $this->db->delete('static_pages', 'id = :id', ['id' => $id]);

            if ($deleted) {
                // Registrar en audit log
                AuditLogger::log(
                    'eliminar',
                    'static_pages',
                    $id,
                    $page['title'],
                    $page,
                    null
                );

                if (Helpers::isAjax()) {
                    $this->json(['success' => true, 'message' => 'Página eliminada correctamente']);
                } else {
                    $this->redirect('admin/pages', 'Página eliminada correctamente', 'success');
                }
            } else {
                if (Helpers::isAjax()) {
                    $this->json(['success' => false, 'message' => 'Error al eliminar página'], 500);
                } else {
                    $this->redirect('admin/pages', 'Error al eliminar página', 'error');
                }
            }

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            } else {
                $this->redirect('admin/pages', 'Error al eliminar: ' . $e->getMessage(), 'error');
            }
        }
    }

    /**
     * Vista previa de página (frontend)
     */
    public function preview($id)
    {
        try {
            $page = $this->db->fetch("SELECT * FROM static_pages WHERE id = :id", ['id' => $id]);

            if (!$page) {
                $this->notFound('Página no encontrada');
                return;
            }

            // Registrar visualización en audit log
            AuditLogger::log(
                'ver',
                'static_pages',
                $id,
                $page['title'],
                null,
                ['accion' => 'preview']
            );

            $this->view('site/static_page', [
                'title' => $page['titulo'],
                'page' => $page,
                'meta_title' => $page['meta_title'] ?? $page['titulo'],
                'meta_description' => $page['meta_description'] ?? '',
                'meta_keywords' => $page['meta_keywords'] ?? '',
                'isPreview' => true
            ]);

        } catch (Exception $e) {
            $this->notFound('Error al cargar página: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar página pública por slug
     */
    public function viewPage($slug)
    {
        try {
            $page = $this->db->fetch("SELECT * FROM static_pages WHERE slug = :slug AND status = 'published'", ['slug' => $slug]);

            if (!$page) {
                $this->notFound('Página no encontrada');
                return;
            }

            if (($page['slug'] ?? $slug) === 'about') {
                $this->renderAboutPage($page);
            } else {
                $this->view('site/static_page', [
                    'title' => $page['titulo'] ?? $page['title'],
                    'page' => $page,
                    'meta_title' => $page['meta_title'] ?? ($page['titulo'] ?? $page['title']),
                    'meta_description' => $page['meta_description'] ?? '',
                    'meta_keywords' => $page['meta_keywords'] ?? '',
                    'isPreview' => false
                ]);
            }

        } catch (Exception $e) {
            $this->notFound('Error al cargar página: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Toggle activo
     */
    public function toggleActive()
    {
        $this->validateCsrf();

        try {
            $id = $this->getInput('id');

            $page = $this->db->fetch("SELECT status, title FROM static_pages WHERE id = :id", ['id' => $id]);

            if (!$page) {
                $this->json(['success' => false, 'message' => 'Página no encontrada'], 404);
            }

            $newStatus = $page['status'] === 'published' ? 'draft' : 'published';

            $this->db->update('static_pages', ['status' => $newStatus], 'id = :id', ['id' => $id]);

            // Registrar en audit log
            AuditLogger::log(
                'editar',
                'static_pages',
                $id,
                $page['title'],
                ['status' => $page['status']],
                ['status' => $newStatus]
            );

            $this->json([
                'success' => true,
                'status' => $newStatus,
                'message' => $newStatus === 'published' ? 'Página activada' : 'Página desactivada'
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Duplicar página
     */
    public function duplicate($id)
    {
        $this->validateCsrf();

        try {
            $page = $this->db->fetch("SELECT * FROM static_pages WHERE id = :id", ['id' => $id]);

            if (!$page) {
                $this->json(['success' => false, 'message' => 'Página no encontrada'], 404);
            }

            // Generar nuevo slug único
            $newSlug = $page['slug'] . '-copia';
            $counter = 1;
            while ($this->db->fetch("SELECT id FROM static_pages WHERE slug = :slug", ['slug' => $newSlug])) {
                $newSlug = $page['slug'] . '-copia-' . $counter;
                $counter++;
            }

            // Preparar datos para la copia
            $newData = [
                'title' => $page['title'] . ' (Copia)',
                'slug' => $newSlug,
                'content' => $page['content'],
                'meta_title' => $page['meta_title'],
                'meta_description' => $page['meta_description'],
                'meta_keywords' => $page['meta_keywords'],
                'status' => 'draft', // Crear como borrador
                'show_in_menu' => 0,
                'menu_order' => 0
            ];

            // Insertar copia
            $newId = $this->db->insert('static_pages', $newData);

            // Registrar en audit log
            AuditLogger::log(
                'crear',
                'static_pages',
                $newId,
                $newData['title'],
                null,
                array_merge($newData, ['duplicada_desde' => $id])
            );

            $this->json([
                'success' => true,
                'message' => 'Página duplicada correctamente',
                'new_id' => $newId,
                'new_slug' => $newSlug
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al duplicar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Generar slug automático desde título
     */
    public function generateSlug()
    {
        try {
            $title = $this->getInput('title');
            $currentId = $this->getInput('id');

            if (empty($title)) {
                $this->json(['success' => false, 'message' => 'Título requerido'], 400);
            }

            $slug = Helpers::slug($title);

            // Verificar si existe (excepto en el registro actual)
            $sql = "SELECT id FROM static_pages WHERE slug = :slug";
            $params = ['slug' => $slug];

            if ($currentId) {
                $sql .= " AND id != :id";
                $params['id'] = $currentId;
            }

            $existing = $this->db->fetch($sql, $params);

            if ($existing) {
                // Agregar número al final
                $counter = 1;
                $originalSlug = $slug;
                while ($existing) {
                    $slug = $originalSlug . '-' . $counter;
                    $params['slug'] = $slug;
                    $existing = $this->db->fetch($sql, $params);
                    $counter++;
                }
            }

            $this->json([
                'success' => true,
                'slug' => $slug
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // MÉTODOS PÚBLICOS (Frontend)
    // ==========================================

    /**
     * Ver página estática por ruta (público)
     * Mapea rutas específicas como 'about', 'terms', 'privacy' a sus slugs
     */
    public function showByRoute()
    {
        try {
            // Obtener la ruta actual para mapear al slug correcto
            $route = $_GET['route'] ?? 'about';

            // Mapeo de rutas a slugs
            $slugMap = [
                'about' => 'about',
                'terms' => 'terms',
                'privacy' => 'privacy'
            ];

            $slug = $slugMap[$route] ?? $route;

            // Obtener página por slug
            $page = $this->db->fetch(
                "SELECT * FROM static_pages WHERE slug = :slug AND status = 'published'",
                ['slug' => $slug]
            );

            if (!$page) {
                // Si no existe, mostrar 404
                http_response_code(404);
                $this->view('errors/404', [
                    'title' => 'Página no encontrada',
                    'message' => 'La página que buscas no existe o no está disponible.'
                ]);
                return;
            }

            if ($slug === 'about') {
                $this->renderAboutPage($page);
                return;
            }

            $this->view('site/static_page', [
                'title' => $page['meta_title'] ?? $page['title'],
                'metaDescription' => $page['meta_description'] ?? '',
                'metaKeywords' => $page['meta_keywords'] ?? '',
                'page' => $page
            ]);

        } catch (Exception $e) {
            error_log("Error loading static page: " . $e->getMessage());
            http_response_code(500);
            $this->view('errors/500', [
                'title' => 'Error del servidor',
                'message' => 'Ha ocurrido un error al cargar la página.'
            ]);
        }
    }

    /**
     * Ver página estática por slug (público)
     * Ruta genérica: /page/mi-pagina
     */
    public function viewBySlug($slug = null)
    {
        try {
            if (!$slug) {
                http_response_code(404);
                $this->view('errors/404', [
                    'title' => 'Página no encontrada',
                    'message' => 'La página que buscas no existe.'
                ]);
                return;
            }

            // Obtener página por slug
            $page = $this->db->fetch(
                "SELECT * FROM static_pages WHERE slug = :slug AND status = 'published'",
                ['slug' => $slug]
            );

            if (!$page) {
                http_response_code(404);
                $this->view('errors/404', [
                    'title' => 'Página no encontrada',
                    'message' => 'La página que buscas no existe o no está disponible.'
                ]);
                return;
            }

            // Si es la página "about", usar el renderizador especial
            if ($slug === 'about') {
                $this->renderAboutPage($page);
                return;
            }

            // Renderizar página estática normal
            $this->view('site/static_page', [
                'title' => $page['meta_title'] ?? $page['title'],
                'metaDescription' => $page['meta_description'] ?? '',
                'metaKeywords' => $page['meta_keywords'] ?? '',
                'page' => $page
            ]);

        } catch (Exception $e) {
            error_log("Error loading static page by slug: " . $e->getMessage());
            http_response_code(500);
            $this->view('errors/500', [
                'title' => 'Error del servidor',
                'message' => 'Ha ocurrido un error al cargar la página.'
            ]);
        }
    }

    /**
     * Entrypoint para rutas cortas (about, terms, privacy)
     */
    public function viewAlias()
    {
        $route = $_GET['route'] ?? '';
        $aliases = [
            '' => 'about',
            'about' => 'about',
            'terms' => 'terms',
            'privacy' => 'privacy'
        ];

        $slug = $aliases[$route] ?? $route;

        if (empty($slug)) {
            $slug = 'about';
        }

        $this->viewBySlug($slug);
    }

    private function renderAboutPage(array $page)
    {
        $aboutData = $this->buildAboutPageData();

        $this->view('site/about', [
            'title' => $page['meta_title'] ?? $page['title'] ?? 'Acerca de Nosotros',
            'metaDescription' => $page['meta_description'] ?? 'Conoce nuestra historia, valores y equipo.',
            'metaKeywords' => $page['meta_keywords'] ?? '',
            'about' => $aboutData,
            'page' => $page
        ]);
    }

    private function buildAboutPageData()
    {
        $stats = $this->mapKeyValuePairs(CompanyConfigHelper::get('about_stats'), 2);
        $missionPoints = $this->mapKeyValuePairs(CompanyConfigHelper::get('about_mission_points'), 2);
        $values = $this->mapKeyValuePairs(CompanyConfigHelper::get('about_values'), 2);
        $team = $this->mapKeyValuePairs(CompanyConfigHelper::get('about_team'), 3);

        return [
            'hero' => [
                'title' => CompanyConfigHelper::get('about_hero_title', 'Conecta con el Mundo Maya'),
                'subtitle' => CompanyConfigHelper::get('about_hero_subtitle', 'Creamos experiencias auténticas en Guatemala, Belice y México.'),
                'image' => CompanyConfigHelper::get('about_hero_image', 'assets/images/about-hero.jpg'),
                'cta_text' => CompanyConfigHelper::get('about_hero_cta_text', 'Conoce nuestros tours'),
                'cta_link' => CompanyConfigHelper::get('about_hero_cta_link', '?route=tours')
            ],
            'stats' => array_map(function ($item) {
                return [
                    'value' => $item[0] ?? '',
                    'label' => $item[1] ?? ''
                ];
            }, $stats ?: [['12+', 'Años guiando viajeros'], ['4800+', 'Clientes felices'], ['150+', 'Tours diseñados']]),
            'mission' => [
                'title' => CompanyConfigHelper::get('about_mission_title', 'Nuestra esencia'),
                'description' => CompanyConfigHelper::get('about_mission_description', 'Acompañamos a cada viajero para que viva el mundo maya de forma auténtica.'),
                'points' => array_map(function ($item) {
                    return [
                        'title' => $item[0] ?? '',
                        'description' => $item[1] ?? ''
                    ];
                }, $missionPoints ?: [['Turismo responsable', 'Respetamos comunidades y áreas protegidas']])
            ],
            'values' => array_map(function ($item) {
                return [
                    'title' => $item[0] ?? '',
                    'description' => $item[1] ?? ''
                ];
            }, $values ?: [['Pasión por la cultura', 'Compartimos el legado del mundo maya'], ['Excelencia en servicio', 'Te acompañamos antes, durante y después del viaje']]),
            'story' => [
                'title' => CompanyConfigHelper::get('about_story_title', 'Nuestra historia'),
                'content' => CompanyConfigHelper::get('about_story_content', 'Nacimos en Petén con el sueño de mostrar el patrimonio del mundo maya al mundo.'),
                'image' => CompanyConfigHelper::get('about_story_image', 'assets/images/about-story.jpg')
            ],
            'team' => array_map(function ($item) {
                return [
                    'name' => $item[0] ?? '',
                    'role' => $item[1] ?? '',
                    'bio' => $item[2] ?? ''
                ];
            }, $team ?: [['María González', 'Directora de Operaciones', '15 años diseñando experiencias en Guatemala']]),
            'cta' => [
                'title' => CompanyConfigHelper::get('about_cta_title', '¿Listo para planear tu próxima aventura?'),
                'subtitle' => CompanyConfigHelper::get('about_cta_subtitle', 'Cuéntanos qué tipo de experiencia buscas y diseñaremos un itinerario a tu medida.'),
                'button_text' => CompanyConfigHelper::get('about_cta_button_text', 'Agendar una llamada'),
                'button_link' => CompanyConfigHelper::get('about_cta_button_link', '?route=contact')
            ]
        ];
    }

    private function mapKeyValuePairs($raw, $minParts = 2)
    {
        $items = [];
        if (!$raw) {
            return $items;
        }

        $lines = preg_split('/\r?\n/', $raw);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < $minParts) {
                continue;
            }
            $items[] = $parts;
        }

        return $items;
    }
}
