<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Core\Config;
use Exception;

// Load helper classes manually (no autoloader)
require_once __DIR__ . '/../Helpers/AuditLogger.php';
require_once __DIR__ . '/../Helpers/NotificationHelper.php';

use App\Helpers\AuditLogger;
use App\Helpers\NotificationHelper;

/**
 * CategoryController
 *
 * Gestiona categorías de tours con drag & drop para reordenar
 * Incluye selector de iconos Font Awesome, color picker y slug auto-generado
 */
class CategoryController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    /**
     * Listar categorías con drag & drop para orden
     */
    public function list()
    {
        try {
            $search = $this->getInput('search');

            $sql = "SELECT c.*, COUNT(p.id) as tours_count
                    FROM categorias c
                    LEFT JOIN tours p ON c.id = p.categoria_id
                    WHERE 1=1";

            $params = [];

            if ($search) {
                $sql .= " AND (c.nombre LIKE :search OR c.descripcion LIKE :search)";
                $params['search'] = '%' . $search . '%';
            }

            $sql .= " GROUP BY c.id ORDER BY c.orden ASC, c.nombre ASC";

            $categories = $this->db->fetchAll($sql, $params);

            // Calcular estadísticas
            $totalCategories = count($categories);
            $activeCategories = 0;
            $withTours = 0;
            $totalTours = 0;

            foreach ($categories as $cat) {
                if ($cat['activo']) {
                    $activeCategories++;
                }
                if ($cat['tours_count'] > 0) {
                    $withTours++;
                    $totalTours += $cat['tours_count'];
                }
            }

            $stats = [
                'total' => $totalCategories,
                'activas' => $activeCategories,
                'con_tours' => $withTours,
                'total_tours' => $totalTours
            ];

            $this->view('admin/categories/list', [
                'title' => 'Categorías de Tours',
                'categories' => $categories,
                'stats' => $stats,
                'search' => $search
            ]);

        } catch (Exception $e) {
            $this->handleValidationErrors('Error al cargar categorías: ' . $e->getMessage(), 'admin');
        }
    }

    /**
     * Formulario para crear categoría
     */
    public function create()
    {
        if (Helpers::isPost()) {
            $this->validateCsrf();
            $this->storeCategory();
            return;
        }

        $this->view('admin/categories/form', [
            'title' => 'Crear Categoría',
            'category' => null,
            'isEdit' => false
        ]);
    }

    /**
     * Guardar nueva categoría
     */
    private function storeCategory()
    {
        try {
            $nombre = $this->sanitizeInput($this->getInput('nombre'));
            $descripcion = $this->sanitizeInput($this->getInput('descripcion'));
            $slug = $this->getInput('slug');
            $icono = $this->sanitizeInput($this->getInput('icono'));
            $color = $this->sanitizeInput($this->getInput('color', '#007bff'));
            $imagen = $this->sanitizeInput($this->getInput('imagen'));
            $activo = (int)$this->getInput('activo', 1);
            $orden = (int)$this->getInput('orden', 0);

            // Validaciones
            $errors = [];
            if (empty($nombre)) {
                $errors[] = 'El nombre es requerido';
            }

            // Auto-generar slug si está vacío
            if (empty($slug)) {
                $slug = Helpers::slug($nombre);
            } else {
                $slug = Helpers::slug($slug);
            }

            // Verificar que el slug no exista
            $existing = $this->db->fetch(
                "SELECT id FROM categorias WHERE slug = :slug",
                ['slug' => $slug]
            );

            if ($existing) {
                $errors[] = 'El slug ya está en uso. Por favor, elige otro.';
            }

            // Validar color hex
            if ($color && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                $errors[] = 'El color debe ser un código hexadecimal válido (ejemplo: #007bff)';
            }

            if (!empty($errors)) {
                $this->handleValidationErrors($errors);
                return;
            }

            // Manejar subida de imagen si existe
            if (isset($_FILES['imagen_file']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleFileUpload('imagen_file', 'uploads/categorias/', ['jpg', 'jpeg', 'png', 'webp']);

                if ($uploadResult['success']) {
                    $imagen = $uploadResult['path'];
                } else {
                    $errors[] = $uploadResult['error'];
                    $this->handleValidationErrors($errors);
                    return;
                }
            }

            // Si no hay orden especificado, ponerlo al final
            if ($orden === 0) {
                $maxOrden = $this->db->fetch("SELECT MAX(orden) as max_orden FROM categorias");
                $orden = ($maxOrden['max_orden'] ?? 0) + 1;
            }

            // Preparar datos
            $data = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'slug' => $slug,
                'icono' => $icono,
                'color' => $color,
                'imagen' => $imagen,
                'activo' => $activo,
                'orden' => $orden
            ];

            // Insertar
            $newId = $this->db->insert('categorias', $data);

            // Registrar en audit log (opcional)
            try {
                AuditLogger::log(
                    'crear',
                    'categorias',
                    $newId,
                    $nombre,
                    null,
                    $data
                );
            } catch (\Throwable $e) {
                // Audit log no disponible, continuar
            }

            // Notificar admins (opcional)
            try {
                NotificationHelper::notifyAllAdmins(
                    'sistema',
                    'Nueva Categoría',
                    "Se creó la categoría: {$nombre}",
                    Config::getBaseUrl() . '?route=admin/categories',
                    'fas fa-folder-plus',
                    'baja'
                );
            } catch (\Throwable $e) {
                // Notificaciones no disponibles, continuar
            }

            if (Helpers::isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Categoría creada correctamente',
                    'id' => $newId
                ]);
                return;
            }

            $this->redirect('admin/categories', 'Categoría creada correctamente', 'success');

        } catch (Exception $e) {
            $this->handleValidationErrors('Error al crear categoría: ' . $e->getMessage());
        }
    }

    /**
     * Formulario para editar categoría
     */
    public function edit($id)
    {
        if (Helpers::isPost()) {
            $this->validateCsrf();
            $this->updateCategory($id);
            return;
        }

        try {
            $category = $this->db->fetch("SELECT * FROM categorias WHERE id = :id", ['id' => $id]);

            if (!$category) {
                // Si es AJAX, devolver JSON
                if (Helpers::isAjax()) {
                    $this->json(['success' => false, 'message' => 'Categoría no encontrada'], 404);
                    return;
                }
                $this->redirect('admin/categories', 'Categoría no encontrada', 'error');
                return;
            }

            // Contar tours asociados
            $productsCount = $this->db->fetch(
                "SELECT COUNT(*) as total FROM tours WHERE categoria_id = :id",
                ['id' => $id]
            );
            $category['tours_count'] = $productsCount['total'] ?? 0;

            // Si es AJAX, devolver JSON
            if (Helpers::isAjax()) {
                $this->json($category);
                return;
            }

            // Si no es AJAX, mostrar formulario de edición
            $this->view('admin/categories/form', [
                'title' => 'Editar Categoría: ' . $category['nombre'],
                'category' => $category,
                'isEdit' => true
            ]);

        } catch (Exception $e) {
            // Si es AJAX, devolver JSON
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
                return;
            }
            $this->redirect('admin/categories', 'Error al cargar categoría: ' . $e->getMessage(), 'error');
        }
    }

    /**
     * Actualizar categoría
     */
    private function updateCategory($id)
    {
        try {
            $nombre = $this->sanitizeInput($this->getInput('nombre'));
            $descripcion = $this->sanitizeInput($this->getInput('descripcion'));
            $slug = $this->getInput('slug');
            $icono = $this->sanitizeInput($this->getInput('icono'));
            $color = $this->sanitizeInput($this->getInput('color', '#007bff'));
            $imagen = $this->sanitizeInput($this->getInput('imagen'));
            $activo = (int)$this->getInput('activo', 1);
            $orden = (int)$this->getInput('orden', 0);

            // Obtener datos anteriores
            $datosAnteriores = $this->db->fetch("SELECT * FROM categorias WHERE id = :id", ['id' => $id]);

            if (!$datosAnteriores) {
                $this->redirect('admin/categories', 'Categoría no encontrada', 'error');
                return;
            }

            // Validaciones
            $errors = [];
            if (empty($nombre)) {
                $errors[] = 'El nombre es requerido';
            }

            // Auto-generar slug si está vacío
            if (empty($slug)) {
                $slug = Helpers::slug($nombre);
            } else {
                $slug = Helpers::slug($slug);
            }

            // Verificar que el slug no exista (excepto en este registro)
            $existing = $this->db->fetch(
                "SELECT id FROM categorias WHERE slug = :slug AND id != :id",
                ['slug' => $slug, 'id' => $id]
            );

            if ($existing) {
                $errors[] = 'El slug ya está en uso. Por favor, elige otro.';
            }

            // Validar color hex
            if ($color && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
                $errors[] = 'El color debe ser un código hexadecimal válido (ejemplo: #007bff)';
            }

            if (!empty($errors)) {
                $this->handleValidationErrors($errors);
                return;
            }

            // Manejar subida de imagen si existe
            if (isset($_FILES['imagen_file']) && $_FILES['imagen_file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleFileUpload('imagen_file', 'uploads/categorias/', ['jpg', 'jpeg', 'png', 'webp']);

                if ($uploadResult['success']) {
                    // Eliminar imagen anterior si existe
                    if ($datosAnteriores['imagen'] && file_exists($datosAnteriores['imagen'])) {
                        @unlink($datosAnteriores['imagen']);
                    }
                    $imagen = $uploadResult['path'];
                } else {
                    $errors[] = $uploadResult['error'];
                    $this->handleValidationErrors($errors);
                    return;
                }
            } else {
                // Mantener imagen actual si no se subió una nueva
                $imagen = $datosAnteriores['imagen'];
            }

            // Preparar datos
            $data = [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'slug' => $slug,
                'icono' => $icono,
                'color' => $color,
                'imagen' => $imagen,
                'activo' => $activo,
                'orden' => $orden
            ];

            // Actualizar
            $updated = $this->db->update('categorias', $data, 'id = :id', ['id' => $id]);

            // Registrar en audit log (opcional)
            try {
                AuditLogger::log(
                    'editar',
                    'categorias',
                    $id,
                    $nombre,
                    $datosAnteriores,
                    $data
                );
            } catch (Exception $e) {
                // Audit log no disponible, continuar
            }

            // Respuesta según tipo de petición
            if (Helpers::isAjax()) {
                $this->json(['success' => true, 'message' => 'Categoría actualizada correctamente']);
            } else {
                $this->redirect('admin/categories', 'Categoría actualizada correctamente', 'success');
            }

        } catch (Exception $e) {
            $this->handleValidationErrors('Error al actualizar categoría: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar categoría (verificar que no tenga tours)
     */
    public function delete($id)
    {
        $payload = json_decode(file_get_contents('php://input'), true) ?: [];
        $csrfToken = $payload['csrf_token'] ?? $this->getInput('csrf_token');
        if (!Helpers::validateCsrfToken($csrfToken)) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Token de seguridad inválido'], 403);
            } else {
                $this->redirect('home', 'Token de seguridad inválido', 'error');
            }
            return;
        }

        try {
            $category = $this->db->fetch("SELECT * FROM categorias WHERE id = :id", ['id' => $id]);

            if (!$category) {
                if (Helpers::isAjax()) {
                    $this->json(['success' => false, 'message' => 'Categoría no encontrada'], 404);
                } else {
                    $this->redirect('admin/categories', 'Categoría no encontrada', 'error');
                }
                return;
            }

            // Verificar que no tenga tours asociados
            $productsCount = $this->db->fetch(
                "SELECT COUNT(*) as total FROM tours WHERE categoria_id = :id",
                ['id' => $id]
            );

            if (($productsCount['total'] ?? 0) > 0) {
                if (Helpers::isAjax()) {
                    $this->json([
                        'success' => false,
                        'message' => 'No se puede eliminar la categoría porque tiene ' . $productsCount['total'] . ' tour(s) asociado(s)'
                    ], 400);
                } else {
                    $this->redirect('admin/categories', 'No se puede eliminar la categoría porque tiene tours asociados', 'error');
                }
                return;
            }

            // Eliminar imagen si existe
            if ($category['imagen'] && file_exists($category['imagen'])) {
                @unlink($category['imagen']);
            }

            // Eliminar
            $deleted = $this->db->delete('categorias', 'id = :id', ['id' => $id]);

            if ($deleted) {
                // Registrar en audit log (opcional)
                try {
                    AuditLogger::log(
                        'eliminar',
                        'categorias',
                        $id,
                        $category['nombre'],
                        $category,
                        null
                    );
                } catch (Exception $e) {
                    // Audit log no disponible, continuar
                }

                if (Helpers::isAjax()) {
                    $this->json(['success' => true, 'message' => 'Categoría eliminada correctamente']);
                } else {
                    $this->redirect('admin/categories', 'Categoría eliminada correctamente', 'success');
                }
            } else {
                if (Helpers::isAjax()) {
                    $this->json(['success' => false, 'message' => 'Error al eliminar categoría'], 500);
                } else {
                    $this->redirect('admin/categories', 'Error al eliminar categoría', 'error');
                }
            }

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            } else {
                $this->redirect('admin/categories', 'Error al eliminar: ' . $e->getMessage(), 'error');
            }
        }
    }

    /**
     * AJAX: Actualizar orden de categorías (drag & drop)
     */
    public function updateOrder()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }

        try {
            $payload = json_decode(file_get_contents('php://input'), true) ?: [];
            $csrfToken = $payload['csrf_token'] ?? $this->getInput('csrf_token');
            if (!Helpers::validateCsrfToken($csrfToken)) {
                $this->json(['success' => false, 'message' => 'Token de seguridad inválido'], 403);
            }

            $order = $payload['orden'] ?? $payload['order'] ?? $this->getInput('orden', $this->getInput('order', []));

            if (empty($order) || !is_array($order)) {
                $this->json(['success' => false, 'message' => 'Orden inválido'], 400);
            }

            $this->db->beginTransaction();

            foreach ($order as $index => $categoryId) {
                $this->db->update(
                    'categorias',
                    ['orden' => $index + 1],
                    'id = :id',
                    ['id' => $categoryId]
                );
            }

            $this->db->commit();

            // Registrar en audit log (opcional)
            try {
                AuditLogger::log(
                    'editar',
                    'categorias',
                    null,
                    'Orden de categorías',
                    null,
                    ['nuevo_orden' => $order]
                );
            } catch (Exception $e) {
                // Audit log no disponible, continuar
            }

            $this->json([
                'success' => true,
                'message' => 'Orden actualizado correctamente'
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'message' => 'Error al actualizar orden: ' . $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Toggle activo
     */
    public function toggleActive()
    {
        // Leer datos JSON
        $jsonData = json_decode(file_get_contents('php://input'), true);

        // Validar CSRF
        $csrfToken = $jsonData['csrf_token'] ?? '';
        if (!Helpers::validateCsrfToken($csrfToken)) {
            $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
            return;
        }

        try {
            $id = $jsonData['id'] ?? null;

            if (!$id) {
                $this->json(['success' => false, 'message' => 'ID requerido'], 400);
                return;
            }

            $category = $this->db->fetch("SELECT activo, nombre FROM categorias WHERE id = :id", ['id' => $id]);

            if (!$category) {
                $this->json(['success' => false, 'message' => 'Categoría no encontrada'], 404);
            }

            $newStatus = $category['activo'] ? 0 : 1;

            $this->db->update('categorias', ['activo' => $newStatus], 'id = :id', ['id' => $id]);

            // Registrar en audit log (opcional)
            try {
                AuditLogger::log(
                    'editar',
                    'categorias',
                    $id,
                    $category['nombre'],
                    ['activo' => $category['activo']],
                    ['activo' => $newStatus]
                );
            } catch (Exception $e) {
                // Audit log no disponible, continuar
            }

            $this->json([
                'success' => true,
                'activo' => $newStatus,
                'message' => $newStatus ? 'Categoría activada' : 'Categoría desactivada'
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Generar slug automático desde nombre
     */
    public function generateSlug()
    {
        try {
            $nombre = $this->getInput('nombre');

            if (empty($nombre)) {
                $this->json(['success' => false, 'message' => 'Nombre requerido'], 400);
            }

            $slug = Helpers::slug($nombre);

            // Verificar si existe
            $existing = $this->db->fetch("SELECT id FROM categorias WHERE slug = :slug", ['slug' => $slug]);

            if ($existing) {
                // Agregar número al final
                $counter = 1;
                $originalSlug = $slug;
                while ($existing) {
                    $slug = $originalSlug . '-' . $counter;
                    $existing = $this->db->fetch("SELECT id FROM categorias WHERE slug = :slug", ['slug' => $slug]);
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

    /**
     * Manejo de errores de validación
     * Override del método de BaseController para soportar AJAX
     */
    protected function handleValidationErrors($errors, $redirectTo = 'admin/categories')
    {
        $message = is_array($errors) ? implode(', ', $errors) : $errors;

        if (Helpers::isAjax()) {
            $this->json(['success' => false, 'message' => $message], 400);
        } else {
            $this->redirect($redirectTo, $message, 'error');
        }
    }
}
