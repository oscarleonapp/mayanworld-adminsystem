<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Core\Auth;
use App\Core\Helpers;
use App\Core\Config;
use Exception;

/**
 * HomepageEditorController
 *
 * Controlador para el editor visual del homepage
 * Permite a los administradores personalizar secciones mediante drag & drop
 */
class HomepageEditorController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();

        // Solo admins pueden acceder
        $auth = Auth::getInstance();
        if (!$auth->isAdmin()) {
            $auth->requireAdmin();
        }
    }

    /**
     * Vista principal del editor
     */
    public function index()
    {
        $sections = $this->getSections();
        $blocks = $this->getBlocks();

        $this->view('admin/homepage_editor/index', [
            'sections' => $sections,
            'blocks' => $blocks,
            'pageTitle' => 'Editor de Homepage'
        ]);
    }

    /**
     * Obtener todas las secciones
     */
    public function getSections()
    {
        return $this->db->fetchAll(
            "SELECT * FROM homepage_sections ORDER BY section_order ASC"
        );
    }

    /**
     * Obtener todos los bloques custom
     */
    public function getBlocks()
    {
        return $this->db->fetchAll(
            "SELECT * FROM homepage_blocks ORDER BY created_at DESC"
        );
    }

    /**
     * API: Obtener secciones (JSON)
     */
    public function apiGetSections()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'sections' => $this->getSections()
        ]);
        exit;
    }

    /**
     * API: Guardar orden de secciones
     */
    public function apiSaveOrder()
    {
        header('Content-Type: application/json; charset=utf-8');

        $order = $this->getInput('order', []);

        if (empty($order) || !is_array($order)) {
            echo json_encode(['success' => false, 'message' => 'Orden inválido']);
            exit;
        }

        try {
            $this->db->beginTransaction();

            foreach ($order as $index => $sectionId) {
                $this->db->update(
                    'homepage_sections',
                    ['section_order' => $index + 1],
                    'id = :id',
                    ['id' => $sectionId]
                );
            }

            $this->db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Orden guardado correctamente'
            ]);
        } catch (Exception $e) {
            $this->db->rollBack();
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * API: Toggle visibilidad de sección
     */
    public function apiToggleVisibility()
    {
        header('Content-Type: application/json; charset=utf-8');

        // Debug: log received data
        error_log("apiToggleVisibility called - POST: " . print_r($_POST, true) . " GET: " . print_r($_GET, true));

        $sectionId = $this->getInput('id');

        if (!$sectionId) {
            echo json_encode([
                'success' => false,
                'message' => 'ID inválido',
                'debug' => [
                    'post' => $_POST,
                    'get' => $_GET,
                    'received_id' => $sectionId
                ]
            ]);
            exit;
        }

        try {
            // Get current visibility
            $section = $this->db->fetchOne(
                "SELECT is_visible FROM homepage_sections WHERE id = ?",
                [$sectionId]
            );

            if (!$section) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Sección no encontrada',
                    'debug' => ['section_id' => $sectionId]
                ]);
                exit;
            }

            // Toggle
            $newVisibility = $section['is_visible'] ? 0 : 1;

            $updated = $this->db->update(
                'homepage_sections',
                ['is_visible' => $newVisibility],
                'id = :id',
                ['id' => $sectionId]
            );

            echo json_encode([
                'success' => true,
                'is_visible' => (bool)$newVisibility,
                'message' => $newVisibility ? 'Sección visible' : 'Sección oculta',
                'debug' => [
                    'updated_rows' => $updated,
                    'old_value' => $section['is_visible'],
                    'new_value' => $newVisibility
                ]
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'debug' => [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ]);
        }
        exit;
    }

    /**
     * API: Actualizar configuración de una sección
     */
    public function apiUpdateSection()
    {
        header('Content-Type: application/json; charset=utf-8');

        $sectionId = $this->getInput('id');
        $config = $this->getInput('config');
        $title = $this->getInput('title');

        if (!$sectionId) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit;
        }

        try {
            $updateData = [];

            if ($config !== null) {
                // Validar que sea JSON válido
                if (is_array($config)) {
                    $config = json_encode($config);
                }
                $decoded = json_decode($config);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    echo json_encode(['success' => false, 'message' => 'Configuración JSON inválida']);
                    exit;
                }
                $updateData['section_config'] = $config;
            }

            if ($title !== null) {
                $updateData['section_title'] = $title;
            }

            if (empty($updateData)) {
                echo json_encode(['success' => false, 'message' => 'No hay datos para actualizar']);
                exit;
            }

            $this->db->update(
                'homepage_sections',
                $updateData,
                'id = :id',
                ['id' => $sectionId]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Sección actualizada correctamente'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * API: Crear nueva sección custom
     */
    public function apiCreateSection()
    {
        header('Content-Type: application/json; charset=utf-8');

        $sectionType = $this->getInput('section_type', 'custom');
        $title = $this->getInput('title', 'Nueva Sección');
        $config = $this->getInput('config', '{}');

        try {
            // Get max order
            $maxOrder = $this->db->fetchOne(
                "SELECT MAX(section_order) as max_order FROM homepage_sections"
            );
            $newOrder = ($maxOrder['max_order'] ?? 0) + 1;

            $sectionId = $this->db->insert('homepage_sections', [
                'section_type' => $sectionType,
                'section_title' => $title,
                'section_config' => is_array($config) ? json_encode($config) : $config,
                'section_order' => $newOrder,
                'is_visible' => 1
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Sección creada correctamente',
                'section_id' => $sectionId
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * API: Eliminar sección
     */
    public function apiDeleteSection()
    {
        header('Content-Type: application/json; charset=utf-8');

        $sectionId = $this->getInput('id');

        if (!$sectionId) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            exit;
        }

        try {
            // No permitir eliminar secciones básicas (hero, stats, etc.)
            $section = $this->db->fetchOne(
                "SELECT section_type FROM homepage_sections WHERE id = ?",
                [$sectionId]
            );

            $protectedTypes = ['trust_bar', 'hero', 'stats', 'partners', 'featured', 'categories', 'reviews', 'newsletter', 'cta'];

            if ($section && in_array($section['section_type'], $protectedTypes)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No se puede eliminar esta sección. Solo puedes ocultarla.'
                ]);
                exit;
            }

            $this->db->delete('homepage_sections', 'id = :id', ['id' => $sectionId]);

            echo json_encode([
                'success' => true,
                'message' => 'Sección eliminada correctamente'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Vista de previsualización del homepage
     */
    public function preview()
    {
        // Redirect al homepage con parámetro de preview
        header('Location: ' . Config::getBaseUrl() . '?preview=1');
        exit;
    }

    /**
     * API: Subir logo de partner
     */
    public function apiUploadPartnerLogo()
    {
        error_log("apiUploadPartnerLogo called");
        error_log("FILES: " . print_r($_FILES, true));
        error_log("POST: " . print_r($_POST, true));

        // Verificar que sea una petición POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json([
                'success' => false,
                'message' => 'Método no permitido. Usa POST.'
            ], 405);
            return;
        }

        if (!isset($_FILES['logo'])) {
            $this->json([
                'success' => false,
                'message' => 'No se recibió el archivo. Verifica que el campo se llame "logo"',
                'debug' => [
                    'files_received' => array_keys($_FILES),
                    'post_received' => array_keys($_POST)
                ]
            ], 400);
            return;
        }

        $file = $_FILES['logo'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por PHP',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en disco',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida'
            ];

            $this->json([
                'success' => false,
                'message' => 'Error al subir: ' . ($errorMessages[$file['error']] ?? 'Error desconocido'),
                'error_code' => $file['error']
            ], 400);
            return;
        }

        try {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];

            error_log("File type: " . $file['type']);

            if (!in_array($file['type'], $allowedTypes)) {
                $this->json([
                    'success' => false,
                    'message' => 'Tipo de archivo no permitido: ' . $file['type'] . '. Solo JPG, PNG, GIF, SVG y WEBP',
                    'allowed_types' => $allowedTypes
                ], 400);
                return;
            }

            // Límite de 2MB
            if ($file['size'] > 2 * 1024 * 1024) {
                $this->json([
                    'success' => false,
                    'message' => 'El archivo es demasiado grande (' . round($file['size'] / 1024 / 1024, 2) . 'MB). Máximo 2MB'
                ], 400);
                return;
            }

            // Directorio de destino
            $uploadDir = __DIR__ . '/../../public/assets/images/partners/';
            error_log("Upload dir: " . $uploadDir);

            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    $this->json([
                        'success' => false,
                        'message' => 'No se pudo crear el directorio de destino'
                    ], 500);
                    return;
                }
            }

            // Generar nombre único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'partner-' . time() . '-' . uniqid() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;

            error_log("Upload path: " . $uploadPath);

            // Mover archivo
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                $this->json([
                    'success' => false,
                    'message' => 'Error al guardar el archivo en: ' . $uploadPath,
                    'debug' => [
                        'tmp_name' => $file['tmp_name'],
                        'destination' => $uploadPath,
                        'dir_exists' => is_dir($uploadDir),
                        'dir_writable' => is_writable($uploadDir)
                    ]
                ], 500);
                return;
            }

            error_log("File saved successfully");

            // Retornar URL relativa
            $url = '/travel-agency-mvp/public/assets/images/partners/' . $filename;

            $this->json([
                'success' => true,
                'message' => 'Logo subido correctamente',
                'url' => $url,
                'filename' => $filename
            ]);

        } catch (Exception $e) {
            error_log("Exception uploading partner logo: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->json([
                'success' => false,
                'message' => 'Error al subir el logo: ' . $e->getMessage()
            ], 500);
        }
    }
}
