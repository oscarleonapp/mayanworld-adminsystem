<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Models\FooterSection;
use App\Helpers\AuditLogger;
use Exception;

/**
 * FooterController
 *
 * Controlador para gestionar el editor de footer desde el panel admin
 *
 * @version 1.0.0
 * @date 2025-11-05
 */
class FooterController extends BaseController
{
    private $model;
    private $auditLogger;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->model = new FooterSection();
        $this->auditLogger = new AuditLogger();
    }

    /**
     * Vista principal del editor de footer
     */
    public function index()
    {
        $sectionsByColumn = $this->model->getSectionsByColumn();
        $config = $this->model->getFooterConfig();

        $this->view('admin/footer/index', [
            'pageTitle' => 'Editor de Footer',
            'sectionsByColumn' => $sectionsByColumn,
            'config' => $config
        ]);
    }

    /**
     * Agregar nueva sección
     */
    public function addSection()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // CSRF protection
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        try {
            $data = [
                'title' => $_POST['title'] ?? '',
                'type' => $_POST['type'] ?? '',
                'column_position' => (int)($_POST['column_position'] ?? 1),
                'visible' => isset($_POST['visible']) ? (bool)$_POST['visible'] : true
            ];

            // Procesar content según el tipo
            $data['content'] = $this->processContentByType($data['type'], $_POST);

            $id = $this->model->addSection($data);

            if ($id) {
                $this->auditLogger->log(
                    'footer_section_created',
                    'footer_sections',
                    $id,
                    null,
                    $data,
                    $this->auth->getUser()['id'] ?? null
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Sección agregada correctamente',
                    'id' => $id
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al agregar la sección'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar sección existente
     */
    public function updateSection()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // CSRF protection
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                return;
            }

            $oldData = $this->model->find($id);

            $data = [
                'title' => $_POST['title'] ?? '',
                'type' => $_POST['type'] ?? '',
                'column_position' => (int)($_POST['column_position'] ?? 1),
                'visible' => isset($_POST['visible']) ? (bool)$_POST['visible'] : true
            ];

            // Procesar content según el tipo
            $data['content'] = $this->processContentByType($data['type'], $_POST);

            $success = $this->model->updateSection($id, $data);

            if ($success) {
                $this->auditLogger->log(
                    'footer_section_updated',
                    'footer_sections',
                    $id,
                    $oldData,
                    $data,
                    $this->auth->getUser()['id'] ?? null
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Sección actualizada correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al actualizar la sección'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Eliminar sección
     */
    public function deleteSection()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // CSRF protection
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        try {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                echo json_encode(['success' => false, 'message' => 'ID inválido']);
                return;
            }

            $oldData = $this->model->find($id);
            $success = $this->model->deleteSection($id);

            if ($success) {
                $this->auditLogger->log(
                    'footer_section_deleted',
                    'footer_sections',
                    $id,
                    $oldData,
                    null,
                    $this->auth->getUser()['id'] ?? null
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Sección eliminada correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al eliminar la sección'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Reordenar secciones
     */
    public function reorder()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // CSRF protection
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        try {
            $columnPosition = (int)($input['column_position'] ?? 0);
            $orderedIds = $input['items'] ?? [];

            if ($columnPosition < 1 || $columnPosition > 4) {
                echo json_encode(['success' => false, 'message' => 'Columna inválida']);
                return;
            }

            $success = $this->model->reorderSections($columnPosition, $orderedIds);

            if ($success) {
                $this->auditLogger->log(
                    'footer_sections_reordered',
                    'footer_sections',
                    null,
                    ['column_position' => $columnPosition],
                    ['items' => $orderedIds],
                    $this->auth->getUser()['id'] ?? null
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Secciones reordenadas correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al reordenar secciones'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Mover sección a otra columna
     */
    public function moveSection()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // CSRF protection
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        try {
            $id = (int)($input['id'] ?? 0);
            $newColumn = (int)($input['new_column'] ?? 0);
            $newOrden = isset($input['new_orden']) ? (int)$input['new_orden'] : null;

            $oldData = $this->model->find($id);
            $success = $this->model->moveToColumn($id, $newColumn, $newOrden);

            if ($success) {
                $this->auditLogger->log(
                    'footer_section_moved',
                    'footer_sections',
                    $id,
                    ['column_position' => $oldData['column_position']],
                    ['column_position' => $newColumn],
                    $this->auth->getUser()['id'] ?? null
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Sección movida correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al mover la sección'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Alternar visibilidad de sección
     */
    public function toggleVisible()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // CSRF protection
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        try {
            $id = (int)($_POST['id'] ?? 0);
            $success = $this->model->toggleVisibility($id);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Visibilidad actualizada'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al actualizar visibilidad'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener datos de una sección
     */
    public function getSection()
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        $section = $this->model->find($id);

        if ($section) {
            // Decodificar content
            $section['content_decoded'] = !empty($section['content'])
                ? json_decode($section['content'], true)
                : [];

            echo json_encode([
                'success' => true,
                'section' => $section
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Sección no encontrada'
            ]);
        }
    }

    /**
     * Duplicar sección
     */
    public function duplicateSection()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // CSRF protection
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        try {
            $id = (int)($_POST['id'] ?? 0);
            $newId = $this->model->duplicateSection($id);

            if ($newId) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Sección duplicada correctamente',
                    'id' => $newId
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al duplicar sección'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar configuración global del footer
     */
    public function updateConfig()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        // CSRF protection
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        try {
            $key = $_POST['config_key'] ?? '';
            $value = $_POST['config_value'] ?? '';

            if (empty($key)) {
                echo json_encode(['success' => false, 'message' => 'Clave de configuración requerida']);
                return;
            }

            $success = $this->model->updateFooterConfig($key, $value);

            if ($success) {
                $this->auditLogger->log(
                    'footer_config_updated',
                    'footer_config',
                    null,
                    ['config_key' => $key],
                    ['config_value' => $value],
                    $this->auth->getUser()['id'] ?? null
                );

                echo json_encode([
                    'success' => true,
                    'message' => 'Configuración actualizada correctamente'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al actualizar configuración'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Procesar content según tipo de sección
     *
     * @param string $type Tipo de sección
     * @param array $post Datos POST
     * @return array Configuración procesada
     */
    private function processContentByType($type, $post)
    {
        switch ($type) {
            case FooterSection::TYPE_COMPANY_INFO:
                return [
                    'show_logo' => isset($post['show_logo']) && $post['show_logo'] === 'true',
                    'show_name' => isset($post['show_name']) && $post['show_name'] === 'true',
                    'show_tagline' => isset($post['show_tagline']) && $post['show_tagline'] === 'true',
                    'use_company_config' => true
                ];

            case FooterSection::TYPE_LINKS:
                return [
                    'source' => $post['links_source'] ?? 'navigation_menu',
                    'menu_name' => $post['menu_name'] ?? 'footer',
                    'show_icons' => isset($post['show_icons']) && $post['show_icons'] === 'true'
                ];

            case FooterSection::TYPE_CONTACT:
                $showFields = [];
                foreach (['email', 'phone', 'address', 'whatsapp'] as $field) {
                    if (isset($post['show_' . $field]) && $post['show_' . $field] === 'true') {
                        $showFields[] = $field;
                    }
                }
                return [
                    'use_company_config' => true,
                    'show_fields' => $showFields,
                    'show_icons' => isset($post['show_icons']) && $post['show_icons'] === 'true'
                ];

            case FooterSection::TYPE_SOCIAL:
                $platforms = [];
                foreach (['facebook', 'instagram', 'twitter', 'youtube', 'whatsapp'] as $platform) {
                    if (isset($post['platform_' . $platform]) && $post['platform_' . $platform] === 'true') {
                        $platforms[] = $platform;
                    }
                }
                return [
                    'use_company_config' => true,
                    'platforms' => $platforms,
                    'icon_size' => $post['icon_size'] ?? 'fa-lg',
                    'layout' => $post['layout'] ?? 'horizontal'
                ];

            case FooterSection::TYPE_NEWSLETTER:
                return [
                    'title' => $post['newsletter_title'] ?? 'Newsletter',
                    'description' => $post['newsletter_description'] ?? 'Recibe ofertas exclusivas',
                    'button_text' => $post['newsletter_button'] ?? 'Suscribirse',
                    'placeholder' => $post['newsletter_placeholder'] ?? 'Tu email'
                ];

            case FooterSection::TYPE_CUSTOM:
                return [
                    'html' => $post['custom_html'] ?? ''
                ];

            default:
                return [];
        }
    }
}
