<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Models\Media;
use App\Helpers\AuditLogger;
use Exception;

/**
 * MediaController
 *
 * Controlador para gestionar la biblioteca de medios
 * Maneja upload, edición, eliminación y búsqueda de archivos
 *
 * @version 1.0.0
 * @date 2025-11-05
 */
class MediaController extends BaseController
{
    private $model;
    private $auditLogger;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->model = new Media();
        $this->auditLogger = new AuditLogger();
    }

    /**
     * Vista principal de la biblioteca de medios
     */
    public function index()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $folder = $_GET['folder'] ?? '';
        $search = $_GET['search'] ?? '';

        $filters = [
            'folder' => $folder,
            'search' => $search
        ];

        $result = $this->model->search($filters, $page, 24);
        $folders = $this->model->getFolders();
        $stats = $this->model->getStats();

        $this->view('admin/media/index', [
            'pageTitle' => 'Biblioteca de Medios',
            'items' => $result['items'],
            'pagination' => $result,
            'folders' => $folders,
            'stats' => $stats,
            'currentFolder' => $folder,
            'currentSearch' => $search
        ]);
    }

    /**
     * Upload de archivo(s)
     */
    public function upload()
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

        if (empty($_FILES['files'])) {
            echo json_encode(['success' => false, 'message' => 'No se recibieron archivos']);
            return;
        }

        $uploadedFiles = [];
        $errors = [];

        // Procesar cada archivo
        $files = $this->normalizeFilesArray($_FILES['files']);

        foreach ($files as $file) {
            $options = [
                'folder' => $_POST['folder'] ?? 'general',
                'uploaded_by' => $this->auth->getUser()['id'] ?? null,
                'alt_text' => $_POST['alt_text'] ?? '',
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? ''
            ];

            $result = $this->model->upload($file, $options);

            if (isset($result['error'])) {
                $errors[] = [
                    'filename' => $file['name'],
                    'error' => $result['error']
                ];
            } else {
                $uploadedFiles[] = $result;

                // Audit log
                $this->auditLogger->log(
                    'media_uploaded',
                    'media_library',
                    $result['id'],
                    null,
                    $result,
                    $options['uploaded_by']
                );
            }
        }

        if (count($uploadedFiles) > 0) {
            echo json_encode([
                'success' => true,
                'message' => count($uploadedFiles) . ' archivo(s) subido(s) correctamente',
                'files' => $uploadedFiles,
                'errors' => $errors
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No se pudo subir ningún archivo',
                'errors' => $errors
            ]);
        }
    }

    /**
     * Normalizar array de archivos múltiples
     *
     * @param array $files Array $_FILES
     * @return array Array normalizado
     */
    private function normalizeFilesArray($files)
    {
        $normalized = [];

        if (is_array($files['name'])) {
            foreach ($files['name'] as $key => $value) {
                $normalized[] = [
                    'name' => $files['name'][$key],
                    'type' => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error' => $files['error'][$key],
                    'size' => $files['size'][$key]
                ];
            }
        } else {
            $normalized[] = $files;
        }

        return $normalized;
    }

    /**
     * Obtener detalles de un archivo
     */
    public function getFile()
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        $media = $this->model->find($id);

        if (!$media) {
            echo json_encode(['success' => false, 'message' => 'Archivo no encontrado']);
            return;
        }

        // Obtener variantes
        $variants = $this->model->getVariants($id);

        // Obtener uso
        $usage = $this->model->getUsage($id);

        echo json_encode([
            'success' => true,
            'media' => $media,
            'variants' => $variants,
            'usage' => $usage
        ]);
    }

    /**
     * Actualizar metadata de archivo
     */
    public function updateMetadata()
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

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        $oldData = $this->model->find($id);

        $data = [
            'alt_text' => $_POST['alt_text'] ?? '',
            'title' => $_POST['title'] ?? '',
            'description' => $_POST['description'] ?? '',
            'folder' => $_POST['folder'] ?? 'general'
        ];

        $success = $this->model->updateMetadata($id, $data);

        if ($success) {
            $this->auditLogger->log(
                'media_metadata_updated',
                'media_library',
                $id,
                $oldData,
                $data,
                $this->auth->getUser()['id'] ?? null
            );

            echo json_encode([
                'success' => true,
                'message' => 'Metadata actualizada correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al actualizar metadata'
            ]);
        }
    }

    /**
     * Eliminar archivo
     */
    public function delete()
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

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        $media = $this->model->find($id);

        // Verificar si está en uso
        $usage = $this->model->getUsage($id);
        if (count($usage) > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'El archivo está en uso y no puede ser eliminado',
                'usage' => $usage
            ]);
            return;
        }

        $success = $this->model->deleteMedia($id);

        if ($success) {
            $this->auditLogger->log(
                'media_deleted',
                'media_library',
                $id,
                $media,
                null,
                $this->auth->getUser()['id'] ?? null
            );

            echo json_encode([
                'success' => true,
                'message' => 'Archivo eliminado correctamente'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al eliminar archivo'
            ]);
        }
    }

    /**
     * Eliminación masiva
     */
    public function bulkDelete()
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        // CSRF protection
        if (!isset($input['csrf_token']) || $input['csrf_token'] !== $_SESSION['csrf_token']) {
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido']);
            return;
        }

        $ids = $input['ids'] ?? [];

        if (empty($ids)) {
            echo json_encode(['success' => false, 'message' => 'No se especificaron archivos']);
            return;
        }

        $result = $this->model->bulkDelete($ids);

        $this->auditLogger->log(
            'media_bulk_deleted',
            'media_library',
            null,
            ['ids' => $ids],
            $result,
            $this->auth->getUser()['id'] ?? null
        );

        echo json_encode([
            'success' => true,
            'message' => "{$result['success']} archivo(s) eliminado(s) correctamente",
            'result' => $result
        ]);
    }

    /**
     * Búsqueda avanzada
     */
    public function search()
    {
        header('Content-Type: application/json; charset=utf-8');

        $page = (int)($_GET['page'] ?? 1);
        $perPage = (int)($_GET['per_page'] ?? 24);

        $filters = [
            'folder' => $_GET['folder'] ?? '',
            'mime_type' => $_GET['mime_type'] ?? '',
            'search' => $_GET['search'] ?? '',
            'used' => $_GET['used'] ?? null
        ];

        $result = $this->model->search($filters, $page, $perPage);

        echo json_encode([
            'success' => true,
            'result' => $result
        ]);
    }

    /**
     * Obtener carpetas
     */
    public function getFolders()
    {
        header('Content-Type: application/json; charset=utf-8');

        $folders = $this->model->getFolders();

        echo json_encode([
            'success' => true,
            'folders' => $folders
        ]);
    }

    /**
     * Obtener estadísticas
     */
    public function getStats()
    {
        header('Content-Type: application/json; charset=utf-8');

        $stats = $this->model->getStats();

        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    }

    /**
     * Modal selector de medios (para uso en otros formularios)
     */
    public function picker()
    {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $folder = $_GET['folder'] ?? '';
        $search = $_GET['search'] ?? '';
        $filter = $_GET['filter'] ?? 'images'; // images, documents, all

        $filters = [
            'folder' => $folder,
            'search' => $search
        ];

        // Filtrar por tipo
        if ($filter === 'images') {
            $filters['mime_type'] = 'image';
        } elseif ($filter === 'documents') {
            $filters['mime_type'] = 'application';
        }

        $result = $this->model->search($filters, $page, 12);
        $folders = $this->model->getFolders();

        $this->view('admin/media/picker', [
            'pageTitle' => 'Seleccionar Media',
            'items' => $result['items'],
            'pagination' => $result,
            'folders' => $folders,
            'currentFolder' => $folder,
            'currentSearch' => $search,
            'currentFilter' => $filter
        ]);
    }

    /**
     * API: Obtener URL de un archivo
     */
    public function getUrl()
    {
        header('Content-Type: application/json; charset=utf-8');

        $id = (int)($_GET['id'] ?? 0);
        $variant = $_GET['variant'] ?? 'original';

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID inválido']);
            return;
        }

        $media = $this->model->find($id);

        if (!$media) {
            echo json_encode(['success' => false, 'message' => 'Archivo no encontrado']);
            return;
        }

        $url = $media['url'];

        // Si se solicita una variante
        if ($variant !== 'original') {
            $variants = $this->model->getVariants($id);
            foreach ($variants as $v) {
                if ($v['variant_type'] === $variant) {
                    $url = $v['url'];
                    break;
                }
            }
        }

        echo json_encode([
            'success' => true,
            'url' => $url,
            'media' => $media
        ]);
    }
}
