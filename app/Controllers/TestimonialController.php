<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Core\Config;
use App\Models\Testimonial;
use Exception;

class TestimonialController extends BaseController
{
    private $testimonialModel;

    public function __construct()
    {
        parent::__construct();
        $this->testimonialModel = new Testimonial();
    }

    /**
     * Listado de testimoniales (Admin)
     */
    public function index()
    {
        $this->requireAdmin();

        $page = (int)$this->getInput('page', 1);
        $perPage = 20;

        $testimonials = $this->testimonialModel->findAll([], 'orden ASC, fecha_resena DESC');
        $stats = $this->testimonialModel->getStats();

        $this->view('admin/testimonials/index', [
            'title' => 'Gestión de Reseñas',
            'testimonials' => $testimonials,
            'stats' => $stats
        ]);
    }

    /**
     * Formulario para crear/editar testimonial
     */
    public function form($id = null)
    {
        $this->requireAdmin();

        $testimonial = null;
        if ($id) {
            $testimonial = $this->testimonialModel->find($id);
            if (!$testimonial) {
                Helpers::setFlashMessage('error', 'Reseña no encontrada');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/testimonials');
                exit;
            }
        }

        if (Helpers::isPost()) {
            $data = [
                'nombre' => Helpers::sanitizeString($_POST['nombre']),
                'calificacion' => (int)$_POST['calificacion'],
                'comentario' => Helpers::sanitizeString($_POST['comentario']),
                'fuente' => Helpers::sanitizeString($_POST['fuente']),
                'url_fuente' => !empty($_POST['url_fuente']) ? Helpers::sanitizeString($_POST['url_fuente']) : null,
                'fecha_resena' => Helpers::sanitizeString($_POST['fecha_resena']),
                'activo' => isset($_POST['activo']) ? 1 : 0,
                'destacado' => isset($_POST['destacado']) ? 1 : 0,
                'orden' => (int)$_POST['orden']
            ];

            // Validar
            $errors = $this->testimonialModel->validateTestimonial($data);

            if (empty($errors)) {
                // Manejar avatar si se sube
                if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = $this->handleAvatarUpload($_FILES['avatar']);
                    if ($uploadResult['success']) {
                        $data['avatar'] = $uploadResult['path'];
                    }
                }

                if ($id) {
                    // Actualizar
                    $this->testimonialModel->update($data, 'id = :id', ['id' => $id]);
                    Helpers::setFlashMessage('success', 'Reseña actualizada correctamente');
                } else {
                    // Crear
                    $this->testimonialModel->insert($data);
                    Helpers::setFlashMessage('success', 'Reseña creada correctamente');
                }

                header('Location: ' . Config::getBaseUrl() . '?route=admin/testimonials');
                exit;
            } else {
                Helpers::setFlashMessage('error', implode('<br>', $errors));
            }
        }

        $this->view('admin/testimonials/form', [
            'title' => $id ? 'Editar Reseña' : 'Nueva Reseña',
            'testimonial' => $testimonial
        ]);
    }

    /**
     * Eliminar testimonial
     */
    public function delete($id)
    {
        $this->requireAdmin();

        if ($this->testimonialModel->delete($id)) {
            Helpers::setFlashMessage('success', 'Reseña eliminada correctamente');
        } else {
            Helpers::setFlashMessage('error', 'Error al eliminar la reseña');
        }

        header('Location: ' . Config::getBaseUrl() . '?route=admin/testimonials');
        exit;
    }

    /**
     * Alternar estado activo
     */
    public function toggleActive($id)
    {
        $this->requireAdmin();

        if ($this->testimonialModel->toggleActive($id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al cambiar estado']);
        }
        exit;
    }

    /**
     * Alternar destacado
     */
    public function toggleFeatured($id)
    {
        $this->requireAdmin();

        if ($this->testimonialModel->toggleFeatured($id)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al cambiar destacado']);
        }
        exit;
    }

    /**
     * Importar desde Google (placeholder para implementación futura)
     */
    public function importFromGoogle()
    {
        $this->requireAdmin();

        // Aquí se implementaría la lógica para importar desde Google My Business API
        // Por ahora es un placeholder

        $this->view('admin/testimonials/import', [
            'title' => 'Importar Reseñas de Google'
        ]);
    }

    /**
     * Manejar subida de avatar
     */
    private function handleAvatarUpload($file)
    {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Tipo de archivo no permitido'];
        }

        if ($file['size'] > 2 * 1024 * 1024) { // 2MB max
            return ['success' => false, 'message' => 'Archivo demasiado grande'];
        }

        $uploadDir = __DIR__ . '/../../public/assets/images/avatars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'avatar-' . time() . '-' . uniqid() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => true, 'path' => 'images/avatars/' . $filename];
        }

        return ['success' => false, 'message' => 'Error al guardar archivo'];
    }
}
