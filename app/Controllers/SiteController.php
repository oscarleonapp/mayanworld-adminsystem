<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Models\Tour;
use App\Models\Message;
use App\Models\Review;
use App\Core\Helpers;
use App\Core\Config;
use Exception;

class SiteController extends BaseController
{
    private $tourModel;
    private $messageModel;
    private $reviewModel;

    public function __construct()
    {
        parent::__construct();
        $this->tourModel = new Tour();
        $this->messageModel = new Message();
        $this->reviewModel = new Review();
    }
    
    // Página principal
    public function home()
    {
        // Headers para evitar caché de la página
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

        try {
            // Obtener tours destacados
            $featuredProducts = $this->tourModel->getActive(6, true);

            // Obtener todas las categorías con iconos y colores
            $categories = $this->db->fetchAll("
                SELECT * FROM categorias
                WHERE activo = 1
                ORDER BY orden ASC, nombre ASC
            ");

            // Obtener reseñas aprobadas (máximo 6 para mostrar)
            $reviews = $this->db->fetchAll("
                SELECT * FROM reviews
                WHERE aprobado = 1
                ORDER BY created_at DESC
                LIMIT 6
            ");

            // Obtener bloques de contenido editables (CMS Premium)
            $contentBlocks = $this->getContentBlocks();

            // Obtener secciones del homepage desde homepage_sections (CMS Editor)
            $homepageSections = $this->db->fetchAll("
                SELECT * FROM homepage_sections
                WHERE is_visible = 1
                ORDER BY section_order ASC
            ");

            $hasReviewsSection = false;
            foreach ($homepageSections as $sectionRow) {
                if (($sectionRow['section_type'] ?? '') === 'reviews') {
                    $hasReviewsSection = true;
                    break;
                }
            }

            if (!$hasReviewsSection && !empty($reviews)) {
                $homepageSections[] = [
                    'section_type' => 'reviews',
                    'section_title' => 'Lo que dicen nuestros viajeros',
                    'section_config' => json_encode([
                        'title' => 'Lo que dicen nuestros viajeros',
                        'subtitle' => 'Experiencias reales de clientes satisfechos',
                        'limit' => 6,
                        'show_view_all' => true
                    ]),
                    'section_order' => count($homepageSections) + 1,
                    'is_visible' => 1
                ];
            }

            // Obtener configuración del hero (imagen/video/youtube)
            $heroImageConfig = $this->db->fetch("SELECT valor FROM configuraciones WHERE clave = 'hero_image'");
            $heroImage = $heroImageConfig ? $heroImageConfig['valor'] : 'images/hero-travel.jpg';

            $heroTypeConfig = $this->db->fetch("SELECT valor FROM configuraciones WHERE clave = 'hero_type'");
            $heroType = $heroTypeConfig ? $heroTypeConfig['valor'] : 'image';

            $heroAutoplayConfig = $this->db->fetch("SELECT valor FROM configuraciones WHERE clave = 'hero_video_autoplay'");
            $heroAutoplay = $heroAutoplayConfig ? $heroAutoplayConfig['valor'] : '1';

            $heroLoopConfig = $this->db->fetch("SELECT valor FROM configuraciones WHERE clave = 'hero_video_loop'");
            $heroLoop = $heroLoopConfig ? $heroLoopConfig['valor'] : '1';

            $heroPosterConfig = $this->db->fetch("SELECT valor FROM configuraciones WHERE clave = 'hero_poster'");
            $heroPoster = $heroPosterConfig ? $heroPosterConfig['valor'] : '';

            // Estadísticas básicas
            $stats = [
                'total_products' => $this->tourModel->count(['activo' => 1]),
                'total_categories' => count($categories),
                'total_bookings' => $this->db->count('reservas', 'estado != ?', ['cancelada'])
            ];

            // Obtener configuración de empresa
            $companyConfig = $this->getCompanyConfig();

            $this->view('site/home', [
                'title' => 'Inicio | Travel Mayan World',
                'metaDescription' => 'Explora destinos, reserva experiencias y planifica tu viaje con Travel Mayan World.',
                'metaImage' => Helpers::asset($heroImage),
                'heroImage' => $heroImage,
                'heroType' => $heroType,
                'heroAutoplay' => $heroAutoplay,
                'heroLoop' => $heroLoop,
                'heroPoster' => $heroPoster,
                'featured_products' => $featuredProducts,
                'categories' => $categories,
                'reviews' => $reviews,
                'stats' => $stats,
                'blocks' => $contentBlocks,
                'config' => $companyConfig,
                'homepage_sections' => $homepageSections
            ]);

        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                throw $e;
            }

            $this->view('site/home', [
                'title' => 'Travel Mayan World',
                'metaDescription' => 'Explora destinos, reserva experiencias y planifica tu viaje con Travel Mayan World.',
                'metaImage' => Helpers::asset('images/hero-travel.jpg'),
                'heroImage' => 'images/hero-travel.jpg',
                'heroType' => 'image',
                'heroAutoplay' => '1',
                'heroLoop' => '1',
                'heroPoster' => '',
                'featured_products' => [],
                'categories' => [],
                'reviews' => [],
                'stats' => ['total_products' => 0, 'total_categories' => 0, 'total_bookings' => 0],
                'blocks' => [],
                'config' => []
            ]);
        }
    }

    /**
     * Obtener bloques de contenido por sección
     */
    private function getContentBlocks()
    {
        $blocks = $this->db->fetchAll("
            SELECT * FROM content_blocks
            WHERE activo = 1
            ORDER BY seccion, orden ASC
        ");

        // Agrupar por sección
        $grouped = [];
        foreach ($blocks as $block) {
            $seccion = $block['seccion'];
            if (!isset($grouped[$seccion])) {
                $grouped[$seccion] = [];
            }
            $grouped[$seccion][] = $block;
        }

        return $grouped;
    }

    /**
     * Obtener configuración de empresa
     */
    private function getCompanyConfig()
    {
        $configs = $this->db->fetchAll("SELECT * FROM company_config");

        // Convertir a array asociativo key => value
        $configArray = [];
        foreach ($configs as $config) {
            $configArray[$config['config_key']] = $config['config_value'];
        }

        return $configArray;
    }
    
    // Página de contacto
    // Página de todas las reseñas
    public function reviews()
    {
        // Obtener todas las reseñas aprobadas con paginación
        $page = (int)($this->getInput('page', 1));
        $perPage = 12;
        $offset = ($page - 1) * $perPage;

        $totalReviews = $this->db->fetch("
            SELECT COUNT(*) as total FROM reviews WHERE aprobado = 1
        ")['total'];

        $reviews = $this->db->fetchAll("
            SELECT r.*, p.nombre as tour_nombre
            FROM reviews r
            LEFT JOIN tours p ON r.tour_id = p.id
            WHERE r.aprobado = 1
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ", [$perPage, $offset]);

        // Calcular rating promedio
        $avgRating = $this->db->fetch("
            SELECT AVG(rating) as avg_rating FROM reviews WHERE aprobado = 1
        ")['avg_rating'] ?? 5;

        $totalPages = ceil($totalReviews / $perPage);

        $this->view('site/reviews', [
            'title' => 'Reseñas de Clientes | Travel Mayan World',
            'metaDescription' => 'Lee las experiencias reales de nuestros clientes satisfechos',
            'reviews' => $reviews,
            'avgRating' => round($avgRating, 1),
            'totalReviews' => $totalReviews,
            'currentPage' => $page,
            'totalPages' => $totalPages
        ]);
    }

    public function contact()
    {
        if (Helpers::isPost()) {
            $this->processContactForm();
            return;
        }

        $this->view('site/contact', [
            'title' => 'Contacto | Travel Mayan World',
            'metaDescription' => 'Escríbenos para resolver dudas y coordinar tu viaje.',
            'metaImage' => Helpers::asset('images/hero-travel.jpg'),
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }

    // Centro de ayuda / FAQ
    public function help()
    {
        $this->view('site/help', [
            'title' => 'Centro de Ayuda | Travel Mayan World',
            'metaDescription' => 'Preguntas frecuentes, políticas y soporte de Travel Mayan World',
        ]);
    }

    // Preguntas frecuentes (FAQ detallado)
    public function faq()
    {
        // Obtener FAQs activas desde la base de datos
        $faqs = $this->db->fetchAll(
            "SELECT * FROM faqs WHERE activo = 1 ORDER BY orden ASC, id ASC"
        );

        $this->view('site/faq', [
            'title' => 'Preguntas Frecuentes | Travel Mayan World',
            'metaDescription' => 'Encuentra respuestas a todas tus preguntas sobre reservas, destinos, pagos, cancelaciones y más en Travel Mayan World',
            'metaImage' => Helpers::asset('images/hero-travel.jpg'),
            'faqs' => $faqs
        ]);
    }
    
    // Procesar formulario de contacto
    private function processContactForm()
    {
        $this->validateCsrf();
        
        $data = [
            'nombre' => $this->getInput('nombre'),
            'email' => $this->getInput('email'),
            'telefono' => $this->getInput('telefono'),
            'asunto' => $this->getInput('asunto'),
            'mensaje' => $this->getInput('mensaje')
        ];
        
        $result = $this->messageModel->createMessage($data);
        
        if (Helpers::isAjax()) {
            $this->json($result);
        }
        
        if ($result['success']) {
            $this->redirect('contact', 'Mensaje enviado correctamente. Te contactaremos pronto.', 'success');
        } else {
            $errorMessage = is_array($result['errors']) ? implode(', ', $result['errors']) : 'Error al enviar mensaje';
            $this->redirect('contact', $errorMessage, 'error');
        }
    }
    
    // Página "Acerca de"
    public function about()
    {
        $this->view('site/about', [
            'title' => 'Acerca de Nosotros'
        ]);
    }

    
    // Términos y condiciones
    public function terms()
    {
        $this->view('site/terms', [
            'title' => 'Términos y Condiciones'
        ]);
    }
    
    // Política de privacidad
    public function privacy()
    {
        $this->view('site/privacy', [
            'title' => 'Política de Privacidad'
        ]);
    }
    
    // Búsqueda global
    public function search()
    {
        $query = $this->getInput('q', '');
        $category = $this->getInput('category');
        $page = (int)$this->getInput('page', 1);
        
        $results = [];
        $totalResults = 0;
        
        if (!empty($query)) {
            // Buscar tours
            $products = $this->tourModel->searchTours($query, $category, 20);
            $results = $products;
            $totalResults = count($products);
        }
        
        if (Helpers::isAjax()) {
            $this->json([
                'success' => true,
                'results' => $results,
                'total' => $totalResults,
                'query' => $query
            ]);
        }
        
        // Obtener categorías para el filtro
        $categories = $this->db->fetchAll("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre");
        
        $this->view('site/search', [
            'title' => (trim($query) ? ("Buscar: '" . Helpers::sanitizeString($query) . "'") : 'Buscar') . ' | Travel Mayan World',
            'metaDescription' => (trim($query) ? ('Resultados de búsqueda para "' . Helpers::sanitizeString($query) . '" en destinos y experiencias de viaje.') : 'Busca destinos y experiencias por nombre y categoría.'),
            'metaImage' => Helpers::asset('images/hero-travel.jpg'),
            'results' => $results,
            'query' => $query,
            'category' => $category,
            'categories' => $categories,
            'total_results' => $totalResults
        ]);
    }
    
    // Página de error 404 personalizada
    public function notFoundPage()
    {
        $this->view('site/404', [
            'title' => 'Página no encontrada'
        ]);
    }
    
    // API para obtener tours destacados (AJAX)
    public function getFeaturedProducts()
    {
        try {
            $limit = (int)$this->getInput('limit', 6);
            $products = $this->tourModel->getActive($limit, true);
            
            // Formatear tours para JSON
            $formattedProducts = array_map(function($product) {
                $pricing = $this->tourModel->formatPrice($product);
                return [
                    'id' => $product['id'],
                    'nombre' => $product['nombre'],
                    'descripcion_corta' => $product['descripcion_corta'],
                    'imagen_principal' => $product['imagen_principal'],
                    'duracion' => $product['duracion_dias'] . ' días',
                    'dificultad' => $product['dificultad'],
                    'precio' => $pricing,
                    'url' => Config::getBaseUrl() . '?route=tour/' . $product['id']
                ];
            }, $products);
            
            $this->json([
                'success' => true,
                'products' => $formattedProducts
            ]);
            
        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Error al obtener tours'
            ], 500);
        }
    }
    
    // Información de la empresa (JSON para footer dinámico)
    public function getCompanyInfo()
    {
        try {
            $info = [
                'nombre' => $this->db->fetch("SELECT valor FROM configuracion WHERE clave = 'company_name'")['valor'] ?? Config::APP_NAME,
                'email' => $this->db->fetch("SELECT valor FROM configuracion WHERE clave = 'company_email'")['valor'] ?? Config::COMPANY_EMAIL,
                'telefono' => $this->db->fetch("SELECT valor FROM configuracion WHERE clave = 'company_phone'")['valor'] ?? Config::COMPANY_PHONE,
                'direccion' => $this->db->fetch("SELECT valor FROM configuracion WHERE clave = 'company_address'")['valor'] ?? Config::COMPANY_ADDRESS
            ];
        } catch (Exception $e) {
            $info = [
                'nombre' => Config::APP_NAME,
                'email' => Config::COMPANY_EMAIL,
                'telefono' => Config::COMPANY_PHONE,
                'direccion' => Config::COMPANY_ADDRESS
            ];
        }
        
        $this->json([
            'success' => true,
            'company' => $info
        ]);
    }
    
    // Newsletter subscription
    public function subscribeNewsletter()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }
        
        $email = $this->getInput('email');
        
        if (!Helpers::validateEmail($email)) {
            $this->json(['success' => false, 'message' => 'Email inválido'], 400);
        }
        
        try {
            // Verificar si ya está suscrito
            $existing = $this->db->fetch(
                "SELECT id FROM newsletter_subscribers WHERE email = :email",
                ['email' => $email]
            );
            
            if ($existing) {
                $this->json(['success' => false, 'message' => 'Ya estás suscrito a nuestro newsletter']);
            }
            
            // Insertar suscripción
            $this->db->insert('newsletter_subscribers', [
                'email' => $email,
                'subscribed_at' => date('Y-m-d H:i:s'),
                'active' => 1
            ]);
            
            $this->json([
                'success' => true, 
                'message' => '¡Gracias por suscribirte! Recibirás nuestras mejores ofertas.'
            ]);
            
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al procesar suscripción'], 500);
        }
    }
}
