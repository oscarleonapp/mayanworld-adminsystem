<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Core\Config;
use App\Helpers\AuditLogger;
use App\Helpers\NotificationHelper;
use Exception;

/**
 * CompanyConfigController
 *
 * Gestiona configuraciones de la empresa (información de contacto, branding, logos, etc.)
 * Agrupa configuraciones por categorías para facilitar la administración
 */
class CompanyConfigController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    /**
     * Mostrar todas las configuraciones agrupadas por grupo
     */
    public function index()
    {
        try {
            $group = $this->getInput('group', 'general');

            // Obtener todas las configuraciones
            $sql = "SELECT * FROM company_config ORDER BY config_group ASC, config_order ASC, config_key ASC";
            $allConfigs = $this->db->fetchAll($sql);

            // Agrupar por grupo
            $groupedConfigs = [];
            $availableGroups = [];

            foreach ($allConfigs as $config) {
                $grp = $config['config_group'] ?? 'general';

                if (!isset($groupedConfigs[$grp])) {
                    $groupedConfigs[$grp] = [];
                    $availableGroups[] = $grp;
                }

                $groupedConfigs[$grp][] = $config;
            }

            // Si no hay configuraciones, crear las predeterminadas
            if (empty($allConfigs)) {
                $this->createDefaultConfigs();
                return $this->redirect('admin/company-config', 'Configuraciones inicializadas correctamente', 'success');
            }

            $this->view('admin/company_config/index', [
                'title' => 'Configuración de la Empresa',
                'groupedConfigs' => $groupedConfigs,
                'availableGroups' => $availableGroups,
                'currentGroup' => $group
            ]);

        } catch (Exception $e) {
            $this->handleValidationErrors('Error al cargar configuraciones: ' . $e->getMessage(), 'admin');
        }
    }

    /**
     * Crear configuraciones predeterminadas
     */
    private function createDefaultConfigs()
    {
        $defaultConfigs = [
            // Información General
            ['config_group' => 'general', 'config_key' => 'company_name', 'config_value' => 'Travel Mayan World', 'config_type' => 'text', 'config_label' => 'Nombre de la Empresa', 'config_order' => 1],
            ['config_group' => 'general', 'config_key' => 'company_tagline', 'config_value' => 'Descubre la magia del mundo maya', 'config_type' => 'text', 'config_label' => 'Eslogan', 'config_order' => 2],
            ['config_group' => 'general', 'config_key' => 'company_description', 'config_value' => 'Agencia de viajes especializada en tours al mundo maya', 'config_type' => 'textarea', 'config_label' => 'Descripción', 'config_order' => 3],
            ['config_group' => 'general', 'config_key' => 'company_about', 'config_value' => 'Somos una agencia especializada en tours y experiencias culturales en Guatemala, Belice y México. Ofrecemos paquetes personalizados para explorar las maravillas arqueológicas del mundo maya.', 'config_type' => 'textarea', 'config_label' => 'Acerca de Nosotros (Footer)', 'config_order' => 4],

            // Contacto
            ['config_group' => 'contacto', 'config_key' => 'company_phone', 'config_value' => '+502 7867-5095', 'config_type' => 'text', 'config_label' => 'Teléfono Principal', 'config_order' => 1],
            ['config_group' => 'contacto', 'config_key' => 'company_phone_secondary', 'config_value' => '', 'config_type' => 'text', 'config_label' => 'Teléfono Secundario', 'config_order' => 2],
            ['config_group' => 'contacto', 'config_key' => 'company_whatsapp', 'config_value' => '+50278675095', 'config_type' => 'text', 'config_label' => 'WhatsApp', 'config_order' => 3],
            ['config_group' => 'contacto', 'config_key' => 'company_email', 'config_value' => 'info@mayanworldtravelagency.com', 'config_type' => 'email', 'config_label' => 'Email de Contacto', 'config_order' => 4],
            ['config_group' => 'contacto', 'config_key' => 'company_email_sales', 'config_value' => 'ventas@mayanworldtravelagency.com', 'config_type' => 'email', 'config_label' => 'Email de Ventas', 'config_order' => 5],
            ['config_group' => 'contacto', 'config_key' => 'company_email_support', 'config_value' => 'soporte@mayanworldtravelagency.com', 'config_type' => 'email', 'config_label' => 'Email de Soporte', 'config_order' => 6],
            ['config_group' => 'contacto', 'config_key' => 'company_address', 'config_value' => 'Flores, Petén, Guatemala', 'config_type' => 'text', 'config_label' => 'Dirección', 'config_order' => 7],
            ['config_group' => 'contacto', 'config_key' => 'company_city', 'config_value' => 'Flores', 'config_type' => 'text', 'config_label' => 'Ciudad', 'config_order' => 8],
            ['config_group' => 'contacto', 'config_key' => 'company_state', 'config_value' => 'Petén', 'config_type' => 'text', 'config_label' => 'Estado/Departamento', 'config_order' => 9],
            ['config_group' => 'contacto', 'config_key' => 'company_country', 'config_value' => 'Guatemala', 'config_type' => 'text', 'config_label' => 'País', 'config_order' => 10],
            ['config_group' => 'contacto', 'config_key' => 'company_postal_code', 'config_value' => '', 'config_type' => 'text', 'config_label' => 'Código Postal', 'config_order' => 11],

            // Redes Sociales
            ['config_group' => 'redes_sociales', 'config_key' => 'social_facebook', 'config_value' => 'https://facebook.com/MayanWorldTravel', 'config_type' => 'url', 'config_label' => 'Facebook', 'config_order' => 1],
            ['config_group' => 'redes_sociales', 'config_key' => 'social_instagram', 'config_value' => 'https://instagram.com/mayanworldtravel', 'config_type' => 'url', 'config_label' => 'Instagram', 'config_order' => 2],
            ['config_group' => 'redes_sociales', 'config_key' => 'social_twitter', 'config_value' => '', 'config_type' => 'url', 'config_label' => 'Twitter/X', 'config_order' => 3],
            ['config_group' => 'redes_sociales', 'config_key' => 'social_youtube', 'config_value' => '', 'config_type' => 'url', 'config_label' => 'YouTube', 'config_order' => 4],
            ['config_group' => 'redes_sociales', 'config_key' => 'social_linkedin', 'config_value' => '', 'config_type' => 'url', 'config_label' => 'LinkedIn', 'config_order' => 5],
            ['config_group' => 'redes_sociales', 'config_key' => 'social_tiktok', 'config_value' => '', 'config_type' => 'url', 'config_label' => 'TikTok', 'config_order' => 6],
            ['config_group' => 'redes_sociales', 'config_key' => 'social_tripadvisor', 'config_value' => '', 'config_type' => 'url', 'config_label' => 'TripAdvisor', 'config_order' => 7],

            // Branding
            ['config_group' => 'branding', 'config_key' => 'logo_url', 'config_value' => '', 'config_type' => 'image', 'config_label' => 'Logo Principal', 'config_order' => 1],
            ['config_group' => 'branding', 'config_key' => 'logo_dark_url', 'config_value' => '', 'config_type' => 'image', 'config_label' => 'Logo (Versión Oscura)', 'config_order' => 2],
            ['config_group' => 'branding', 'config_key' => 'favicon_url', 'config_value' => '', 'config_type' => 'image', 'config_label' => 'Favicon', 'config_order' => 3],
            ['config_group' => 'branding', 'config_key' => 'primary_color', 'config_value' => '#007bff', 'config_type' => 'color', 'config_label' => 'Color Primario', 'config_order' => 4],
            ['config_group' => 'branding', 'config_key' => 'secondary_color', 'config_value' => '#6c757d', 'config_type' => 'color', 'config_label' => 'Color Secundario', 'config_order' => 5],
            ['config_group' => 'branding', 'config_key' => 'login_background_image', 'config_value' => '', 'config_type' => 'url_or_upload', 'config_label' => 'Imagen de Fondo - Login Clientes', 'config_order' => 6, 'config_description' => 'Imagen de fondo para la página de inicio de sesión de clientes. Puedes subir una imagen o usar una URL externa (ej: Unsplash)'],
            ['config_group' => 'branding', 'config_key' => 'admin_login_background_image', 'config_value' => '', 'config_type' => 'url_or_upload', 'config_label' => 'Imagen de Fondo - Login Admin', 'config_order' => 7, 'config_description' => 'Imagen de fondo para la página de inicio de sesión de administradores. Puedes subir una imagen o usar una URL externa (ej: Unsplash)'],

            // Hero/Homepage
            ['config_group' => 'homepage', 'config_key' => 'hero_title', 'config_value' => 'Descubre el Mundo Maya', 'config_type' => 'text', 'config_label' => 'Título del Hero', 'config_order' => 1],
            ['config_group' => 'homepage', 'config_key' => 'hero_subtitle', 'config_value' => 'Explora destinos únicos, reserva experiencias inolvidables y planifica tu próxima aventura con nosotros.', 'config_type' => 'textarea', 'config_label' => 'Subtítulo del Hero', 'config_order' => 2],
            ['config_group' => 'homepage', 'config_key' => 'featured_section_title', 'config_value' => 'Destinos Destacados', 'config_type' => 'text', 'config_label' => 'Título Sección Destacados', 'config_order' => 3],
            ['config_group' => 'homepage', 'config_key' => 'categories_section_title', 'config_value' => 'Explora por Categoría', 'config_type' => 'text', 'config_label' => 'Título Sección Categorías', 'config_order' => 4],
            ['config_group' => 'homepage', 'config_key' => 'reviews_section_title', 'config_value' => 'Lo que Dicen Nuestros Clientes', 'config_type' => 'text', 'config_label' => 'Título Sección Reseñas', 'config_order' => 5],
            ['config_group' => 'homepage', 'config_key' => 'reviews_section_subtitle', 'config_value' => 'Experiencias reales de viajeros satisfechos', 'config_type' => 'text', 'config_label' => 'Subtítulo Sección Reseñas', 'config_order' => 6],

            // SEO
            ['config_group' => 'seo', 'config_key' => 'meta_title', 'config_value' => 'Travel Mayan World - Tours al Mundo Maya', 'config_type' => 'text', 'config_label' => 'Meta Título', 'config_order' => 1],
            ['config_group' => 'seo', 'config_key' => 'meta_description', 'config_value' => 'Descubre la magia del mundo maya con nuestros tours personalizados', 'config_type' => 'textarea', 'config_label' => 'Meta Descripción', 'config_order' => 2],
            ['config_group' => 'seo', 'config_key' => 'meta_keywords', 'config_value' => 'tours maya, guatemala, tikal, yaxha, viajes', 'config_type' => 'text', 'config_label' => 'Meta Keywords', 'config_order' => 3],
            ['config_group' => 'seo', 'config_key' => 'google_analytics_id', 'config_value' => '', 'config_type' => 'text', 'config_label' => 'Google Analytics ID', 'config_order' => 4],
            ['config_group' => 'seo', 'config_key' => 'facebook_pixel_id', 'config_value' => '', 'config_type' => 'text', 'config_label' => 'Facebook Pixel ID', 'config_order' => 5],

            // Horarios
            ['config_group' => 'horarios', 'config_key' => 'business_hours', 'config_value' => 'Lun-Vie: 8:00 AM - 6:00 PM', 'config_type' => 'textarea', 'config_label' => 'Horarios de Atención', 'config_order' => 1],
            ['config_group' => 'horarios', 'config_key' => 'timezone', 'config_value' => 'America/Mexico_City', 'config_type' => 'text', 'config_label' => 'Zona Horaria', 'config_order' => 2],

            // Pagos
            ['config_group' => 'pagos', 'config_key' => 'deposit_percentage', 'config_value' => '30', 'config_type' => 'number', 'config_label' => 'Porcentaje de Anticipo (%)', 'config_order' => 1],
            ['config_group' => 'pagos', 'config_key' => 'currency', 'config_value' => 'USD', 'config_type' => 'text', 'config_label' => 'Moneda', 'config_order' => 2],
            ['config_group' => 'pagos', 'config_key' => 'payment_methods', 'config_value' => 'Tarjeta de crédito, Transferencia bancaria, Efectivo', 'config_type' => 'textarea', 'config_label' => 'Métodos de Pago Aceptados', 'config_order' => 3],

            // About Page
            ['config_group' => 'about_page', 'config_key' => 'about_hero_title', 'config_value' => 'Conecta con el Mundo Maya', 'config_type' => 'text', 'config_label' => 'Hero - Título Principal', 'config_order' => 1, 'config_description' => 'Título grande que aparece al inicio de la página'],
            ['config_group' => 'about_page', 'config_key' => 'about_hero_subtitle', 'config_value' => 'Creamos experiencias auténticas en Guatemala, Belice y México combinando cultura, historia y aventura.', 'config_type' => 'textarea', 'config_label' => 'Hero - Subtítulo', 'config_order' => 2],
            ['config_group' => 'about_page', 'config_key' => 'about_hero_image', 'config_value' => 'assets/images/about-hero.jpg', 'config_type' => 'text', 'config_label' => 'Hero - Imagen de Fondo', 'config_order' => 3, 'config_description' => 'Ruta relativa (ej. assets/images/about-hero.jpg)'],
            ['config_group' => 'about_page', 'config_key' => 'about_hero_cta_text', 'config_value' => 'Conoce nuestros tours', 'config_type' => 'text', 'config_label' => 'Hero - Texto Botón', 'config_order' => 4],
            ['config_group' => 'about_page', 'config_key' => 'about_hero_cta_link', 'config_value' => '?route=tours', 'config_type' => 'text', 'config_label' => 'Hero - Link Botón', 'config_order' => 5],
            ['config_group' => 'about_page', 'config_key' => 'about_stats', 'config_value' => "12+|Años guiando viajeros\n4800+|Clientes felices\n150+|Tours diseñados", 'config_type' => 'textarea', 'config_label' => 'Bloque de Estadísticas', 'config_order' => 6, 'config_description' => 'Una estadística por línea con el formato VALOR|Etiqueta'],
            ['config_group' => 'about_page', 'config_key' => 'about_mission_title', 'config_value' => 'Nuestra esencia', 'config_type' => 'text', 'config_label' => 'Misión - Título', 'config_order' => 7],
            ['config_group' => 'about_page', 'config_key' => 'about_mission_description', 'config_value' => 'Acompañamos a cada viajero para que viva el mundo maya de forma auténtica, responsable y memorable.', 'config_type' => 'textarea', 'config_label' => 'Misión - Descripción', 'config_order' => 8],
            ['config_group' => 'about_page', 'config_key' => 'about_mission_points', 'config_value' => "Guías locales certificados|Expertos arqueólogos y anfitriones bilingües\nTurismo responsable|Respetamos comunidades y áreas protegidas\nExperiencias a medida|Diseñamos cada itinerario según tus intereses", 'config_type' => 'textarea', 'config_label' => 'Misión - Puntos Clave', 'config_order' => 9, 'config_description' => 'Un punto por línea con el formato Título|Descripción'],
            ['config_group' => 'about_page', 'config_key' => 'about_values', 'config_value' => "Pasión por la cultura|Compartimos el legado del mundo maya\nExcelencia en servicio|Acompañamiento antes, durante y después del viaje\nInnovación constante|Actualizamos rutas y experiencias cada temporada", 'config_type' => 'textarea', 'config_label' => 'Valores', 'config_order' => 10, 'config_description' => 'Un valor por línea con el formato Título|Descripción'],
            ['config_group' => 'about_page', 'config_key' => 'about_story_title', 'config_value' => 'Nuestra historia', 'config_type' => 'text', 'config_label' => 'Historia - Título', 'config_order' => 11],
            ['config_group' => 'about_page', 'config_key' => 'about_story_content', 'config_value' => 'Nacimos en Petén con el sueño de mostrar el patrimonio del mundo maya al mundo. Hoy colaboramos con comunidades locales, artesanos y guardaparques para crear experiencias responsables.', 'config_type' => 'textarea', 'config_label' => 'Historia - Descripción', 'config_order' => 12],
            ['config_group' => 'about_page', 'config_key' => 'about_story_image', 'config_value' => 'assets/images/about-story.jpg', 'config_type' => 'text', 'config_label' => 'Historia - Imagen', 'config_order' => 13],
            ['config_group' => 'about_page', 'config_key' => 'about_team', 'config_value' => "María González|Directora de Operaciones|15 años diseñando experiencias en Guatemala\nCarlos Méndez|Coordinador de guías|Especialista en arqueología maya\nLaura Hernández|Experta en hospitalidad|Aliada de comunidades locales", 'config_type' => 'textarea', 'config_label' => 'Equipo Destacado', 'config_order' => 14, 'config_description' => 'Un miembro por línea con el formato Nombre|Rol|Descripción breve'],
            ['config_group' => 'about_page', 'config_key' => 'about_cta_title', 'config_value' => '¿Listo para planear tu próxima aventura?', 'config_type' => 'text', 'config_label' => 'CTA - Título', 'config_order' => 15],
            ['config_group' => 'about_page', 'config_key' => 'about_cta_subtitle', 'config_value' => 'Cuéntanos qué tipo de experiencia buscas y nuestro equipo diseñará un itinerario a tu medida.', 'config_type' => 'textarea', 'config_label' => 'CTA - Subtítulo', 'config_order' => 16],
            ['config_group' => 'about_page', 'config_key' => 'about_cta_button_text', 'config_value' => 'Agendar una llamada', 'config_type' => 'text', 'config_label' => 'CTA - Texto Botón', 'config_order' => 17],
            ['config_group' => 'about_page', 'config_key' => 'about_cta_button_link', 'config_value' => '?route=contact', 'config_type' => 'text', 'config_label' => 'CTA - Link Botón', 'config_order' => 18],
        ];

        try {
            foreach ($defaultConfigs as $config) {
                // Agregar placeholder si no existe
                if (!isset($config['placeholder'])) {
                    $config['placeholder'] = '';
                }
                if (!isset($config['requerido'])) {
                    $config['requerido'] = 0;
                }
                $this->db->insert('company_config', $config);
            }

            // Registrar en audit log
            AuditLogger::log(
                'crear',
                'company_config',
                null,
                'Configuraciones iniciales',
                null,
                ['configs_created' => count($defaultConfigs)]
            );

        } catch (Exception $e) {
            error_log('Error creating default configs: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar múltiples configuraciones
     */
    public function update()
    {
        $this->validateCsrf();

        if (!Helpers::isPost()) {
            $this->redirect('admin/company-config', 'Método no permitido', 'error');
            return;
        }

        try {
            $configs = $this->getInput('configs', []);

            if (empty($configs)) {
                $this->redirect('admin/company-config', 'No se recibieron configuraciones', 'warning');
                return;
            }

            $updatedCount = 0;
            $datosAnteriores = [];
            $datosNuevos = [];

            $this->db->beginTransaction();
            $legacyConfigTableExists = $this->db->tableExists('configuraciones');

            // Procesar archivos subidos primero
            if (!empty($_FILES)) {
                // Ruta absoluta al directorio de uploads
                $uploadDir = __DIR__ . '/../../public/uploads/branding/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                foreach ($_FILES as $fieldName => $file) {
                    // Verificar que sea un upload de config (upload_config_key)
                    if (strpos($fieldName, 'upload_') === 0 && $file['error'] === UPLOAD_ERR_OK) {
                        $configKey = str_replace('upload_', '', $fieldName);

                        // Validar imagen
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        if (!in_array($file['type'], $allowedTypes)) {
                            continue;
                        }

                        // Generar nombre único
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $filename = $configKey . '_' . time() . '.' . $extension;
                        $uploadPath = $uploadDir . $filename;

                        // Mover archivo
                        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                            // Guardar ruta relativa (no incluir BASE_URL)
                            // La vista usa Helpers::asset() que ya agrega el BASE_URL
                            $configs[$configKey] = 'uploads/branding/' . $filename;
                        } else {
                            error_log("Failed to upload file: {$filename} to {$uploadPath}");
                        }
                    }
                }
            }

            foreach ($configs as $key => $value) {
                // Obtener valor anterior
                $oldConfig = $this->db->fetch(
                    "SELECT config_value FROM company_config WHERE config_key = :key",
                    ['key' => $key]
                );

                if ($oldConfig) {
                    $datosAnteriores[$key] = $oldConfig['config_value'];
                    $datosNuevos[$key] = $value;

                    // Actualizar solo si cambió
                    if ($oldConfig['config_value'] !== $value) {
                        $this->db->update(
                            'company_config',
                            ['config_value' => $value],
                            'config_key = :key',
                            ['key' => $key]
                        );

                        // También actualizar en tabla configuraciones si existe
                        if ($legacyConfigTableExists) {
                            $this->db->query(
                                "UPDATE configuraciones SET valor = :value WHERE clave = :key",
                                ['value' => $value, 'key' => $key]
                            );
                        }

                        $updatedCount++;
                    }
                }
            }

            $this->db->commit();

            if ($updatedCount > 0) {
                // Registrar en audit log
                AuditLogger::log(
                    'editar',
                    'company_config',
                    null,
                    'Configuraciones de la empresa',
                    $datosAnteriores,
                    $datosNuevos
                );

                $this->redirect('admin/company-config', "{$updatedCount} configuración(es) actualizada(s) correctamente", 'success');
            } else {
                $this->redirect('admin/company-config', 'No se realizaron cambios', 'info');
            }

        } catch (Exception $e) {
            $this->db->rollBack();
            $this->redirect('admin/company-config', 'Error al actualizar: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Subir logo/imagen de branding
     */
    public function uploadLogo()
    {
        $this->validateCsrf();

        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }

        try {
            $configKey = $this->getInput('config_key');

            if (empty($configKey)) {
                $this->json(['success' => false, 'message' => 'config_key es requerido'], 400);
            }

            // Validar que sea una configuración de tipo imagen
            $config = $this->db->fetch(
                "SELECT * FROM company_config WHERE config_key = :key",
                ['key' => $configKey]
            );

            if (!$config) {
                $this->json(['success' => false, 'message' => 'Configuración no encontrada'], 404);
            }

            if ($config['config_type'] !== 'image') {
                $this->json(['success' => false, 'message' => 'Esta configuración no es de tipo imagen'], 400);
            }

            // Subir archivo
            $uploadResult = $this->handleFileUpload('file', 'uploads/branding/', ['jpg', 'jpeg', 'png', 'webp', 'svg', 'ico']);

            if (!$uploadResult['success']) {
                $this->json(['success' => false, 'message' => $uploadResult['error']], 400);
            }

            // Obtener URL anterior para eliminar archivo antiguo
            $oldValue = $config['config_value'];

            // Actualizar configuración con nueva URL
            $newPath = $uploadResult['path'];
            $this->db->update(
                'company_config',
                ['config_value' => $newPath],
                'config_key = :key',
                ['key' => $configKey]
            );

            // Eliminar archivo anterior si existe
            if ($oldValue && file_exists($oldValue)) {
                @unlink($oldValue);
            }

            // Registrar en audit log
            AuditLogger::log(
                'editar',
                'company_config',
                null,
                $config['config_label'],
                ['config_value' => $oldValue],
                ['config_value' => $newPath]
            );

            $this->json([
                'success' => true,
                'message' => 'Imagen subida correctamente',
                'path' => $newPath,
                'url' => Config::getBaseUrl() . $newPath
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al subir imagen: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Obtener una configuración específica
     */
    public function getConfig($key)
    {
        try {
            $config = $this->db->fetch(
                "SELECT * FROM company_config WHERE config_key = :key",
                ['key' => $key]
            );

            if (!$config) {
                $this->json(['success' => false, 'message' => 'Configuración no encontrada'], 404);
            }

            $this->json([
                'success' => true,
                'config' => $config
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Obtener todas las configuraciones de un grupo
     */
    public function getConfigsByGroup($group)
    {
        try {
            $configs = $this->db->fetchAll(
                "SELECT * FROM company_config WHERE config_group = :group ORDER BY config_order ASC",
                ['group' => $group]
            );

            $this->json([
                'success' => true,
                'group' => $group,
                'configs' => $configs,
                'count' => count($configs)
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Obtener todas las configuraciones (público)
     * Para usar en el frontend
     */
    public function getAllPublicConfigs()
    {
        try {
            $configs = $this->db->fetchAll("SELECT * FROM company_config ORDER BY config_group ASC, config_order ASC");

            // Convertir a formato key => value
            $configsArray = [];
            foreach ($configs as $config) {
                $configsArray[$config['config_key']] = $config['config_value'];
            }

            $this->json([
                'success' => true,
                'configs' => $configsArray
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear nueva configuración personalizada
     */
    public function createConfig()
    {
        $this->validateCsrf();

        if (!Helpers::isPost()) {
            $this->redirect('admin/company-config', 'Método no permitido', 'error');
            return;
        }

        try {
            $configKey = $this->sanitizeInput($this->getInput('config_key'));
            $configValue = $this->getInput('config_value');
            $configType = $this->sanitizeInput($this->getInput('config_type', 'text'));
            $configLabel = $this->sanitizeInput($this->getInput('config_label'));
            $configGroup = $this->sanitizeInput($this->getInput('config_group', 'general'));
            $configOrder = (int)$this->getInput('config_order', 0);
            $configDescription = $this->sanitizeInput($this->getInput('config_description'));

            // Validaciones
            $errors = [];
            if (empty($configKey)) {
                $errors[] = 'La clave (key) es requerida';
            }
            if (empty($configLabel)) {
                $errors[] = 'La etiqueta es requerida';
            }

            // Verificar que la clave no exista
            $existing = $this->db->fetch(
                "SELECT id FROM company_config WHERE config_key = :key",
                ['key' => $configKey]
            );

            if ($existing) {
                $errors[] = 'La clave ya existe. Por favor, elige otra.';
            }

            if (!empty($errors)) {
                $this->handleValidationErrors($errors);
                return;
            }

            // Preparar datos
            $data = [
                'config_key' => $configKey,
                'config_value' => $configValue,
                'config_type' => $configType,
                'config_label' => $configLabel,
                'config_group' => $configGroup,
                'config_order' => $configOrder,
                'config_description' => $configDescription
            ];

            // Insertar
            $newId = $this->db->insert('company_config', $data);

            // Registrar en audit log
            AuditLogger::log(
                'crear',
                'company_config',
                $newId,
                $configLabel,
                null,
                $data
            );

            $this->redirect('admin/company-config?group=' . $configGroup, 'Configuración creada correctamente', 'success');

        } catch (Exception $e) {
            $this->handleValidationErrors('Error al crear configuración: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar configuración personalizada
     */
    public function deleteConfig($id)
    {
        $this->validateCsrf();

        try {
            $config = $this->db->fetch("SELECT * FROM company_config WHERE id = :id", ['id' => $id]);

            if (!$config) {
                if (Helpers::isAjax()) {
                    $this->json(['success' => false, 'message' => 'Configuración no encontrada'], 404);
                } else {
                    $this->redirect('admin/company-config', 'Configuración no encontrada', 'error');
                }
                return;
            }

            // Eliminar archivo asociado si es de tipo imagen
            if ($config['config_type'] === 'image' && $config['config_value'] && file_exists($config['config_value'])) {
                @unlink($config['config_value']);
            }

            // Eliminar
            $deleted = $this->db->delete('company_config', 'id = :id', ['id' => $id]);

            if ($deleted) {
                // Registrar en audit log
                AuditLogger::log(
                    'eliminar',
                    'company_config',
                    $id,
                    $config['config_label'],
                    $config,
                    null
                );

                if (Helpers::isAjax()) {
                    $this->json(['success' => true, 'message' => 'Configuración eliminada correctamente']);
                } else {
                    $this->redirect('admin/company-config', 'Configuración eliminada correctamente', 'success');
                }
            } else {
                if (Helpers::isAjax()) {
                    $this->json(['success' => false, 'message' => 'Error al eliminar configuración'], 500);
                } else {
                    $this->redirect('admin/company-config', 'Error al eliminar configuración', 'error');
                }
            }

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            } else {
                $this->redirect('admin/company-config', 'Error al eliminar: ' . $e->getMessage(), 'error');
            }
        }
    }

    /**
     * Resetear configuraciones a valores predeterminados
     */
    public function resetToDefaults()
    {
        $this->validateCsrf();

        try {
            // Eliminar todas las configuraciones actuales
            $this->db->query("DELETE FROM company_config");

            // Crear configuraciones predeterminadas
            $this->createDefaultConfigs();

            // Registrar en audit log
            AuditLogger::log(
                'editar',
                'company_config',
                null,
                'Reseteo de configuraciones',
                null,
                ['accion' => 'reset_to_defaults']
            );

            $this->redirect('admin/company-config', 'Configuraciones restablecidas a valores predeterminados', 'success');

        } catch (Exception $e) {
            $this->redirect('admin/company-config', 'Error al restablecer: ' . $e->getMessage(), 'error');
        }
    }
}
