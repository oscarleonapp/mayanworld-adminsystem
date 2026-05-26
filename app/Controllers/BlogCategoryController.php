<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\Helpers;
use App\Models\BlogCategory;
use Exception;


/**
 * Controller: BlogCategoryController
 * Gestión de categorías del blog (solo admin)
 */
class BlogCategoryController extends BaseController
{
    private $blogCategory;
    public function __construct()
    {
        parent::__construct();
        $this->blogCategory = new BlogCategory();
    }
    /**
     * Listado de categorías
     * URL: ?route=admin/blog/categorias
     */
    public function list()
    {
        Auth::requireRole('admin');
        $categories = $this->blogCategory->getWithPostCount(false);
        $this->view('admin/blog/categories', [
            'pageTitle' => 'Categorías del Blog',
            'categories' => $categories
        ]);
    }
    /**
     * Crear nueva categoría (AJAX o POST)
     * URL: POST ?route=admin/blog/categorias/crear
     */
    public function create()
    {
        Auth::requireRole('admin');
        $this->validateCsrf();
        $data = [
            'nombre' => $this->getInput('nombre'),
            'slug' => $this->getInput('slug'),
            'descripcion' => $this->getInput('descripcion'),
            'icono' => $this->getInput('icono'),
            'color' => $this->getInput('color') ?: '#3b82f6',
            'meta_title' => $this->getInput('meta_title'),
            'meta_description' => $this->getInput('meta_description'),
            'orden' => $this->getInput('orden') ?: $this->blogCategory->getNextOrder(),
            'activo' => $this->getInput('activo') ? 1 : 1 // Default activo
        ];
        // Validar
        $validation = $this->blogCategory->validateCategory($data, false);
        if (!$validation['valid']) {
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'errors' => $validation['errors']
                ], 400);
            } else {
                Helpers::setFlashMessage('error', implode('<br>', $validation['errors']));
                $this->redirect('admin/blog/categorias');
            }
            return;
        }
        // Crear categoría
        $id = $this->blogCategory->create($data);
        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'category_id' => $id
            ]);
        } else {
            Helpers::setFlashMessage('success', 'Categoría creada exitosamente');
            $this->redirect('admin/blog/categorias');
        }
    }
    /**
     * Editar categoría (AJAX o POST)
     * URL: POST ?route=admin/blog/categorias/editar/{id}
     */
    public function edit($id)
    {
        Auth::requireRole('admin');
        $this->validateCsrf();
        $category = $this->blogCategory->find($id);
        if (!$category) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Categoría no encontrada'], 404);
            } else {
                Helpers::setFlashMessage('error', 'Categoría no encontrada');
                $this->redirect('admin/blog/categorias');
            }
            return;
        }
        $data = [
            'id' => $id,
            'nombre' => $this->getInput('nombre'),
            'slug' => $this->getInput('slug'),
            'descripcion' => $this->getInput('descripcion'),
            'icono' => $this->getInput('icono'),
            'color' => $this->getInput('color') ?: '#3b82f6',
            'meta_title' => $this->getInput('meta_title'),
            'meta_description' => $this->getInput('meta_description'),
            'orden' => $this->getInput('orden') !== '' ? $this->getInput('orden') : $category['orden'],
            'activo' => $this->getInput('activo') ? 1 : 0
        ];
        // Validar
        $validation = $this->blogCategory->validateCategory($data, true);
        if (!$validation['valid']) {
            if ($this->isAjax()) {
                $this->json([
                    'success' => false,
                    'errors' => $validation['errors']
                ], 400);
            } else {
                Helpers::setFlashMessage('error', implode('<br>', $validation['errors']));
                $this->redirect('admin/blog/categorias');
            }
            return;
        }
        // Actualizar
        $this->blogCategory->update($id, $data);
        if ($this->isAjax()) {
            $this->json([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente'
            ]);
        } else {
            Helpers::setFlashMessage('success', 'Categoría actualizada exitosamente');
            $this->redirect('admin/blog/categorias');
        }
    }
    /**
     * Eliminar categoría
     * URL: POST ?route=admin/blog/categorias/eliminar/{id}
     */
    public function delete($id)
    {
        Auth::requireRole('admin');
        $this->validateCsrf();
        $category = $this->blogCategory->find($id);
        if (!$category) {
            $this->json(['success' => false, 'message' => 'Categoría no encontrada'], 404);
            return;
        }
        // Verificar si se puede eliminar (no tiene posts)
        if (!$this->blogCategory->canDelete($id)) {
            $this->json([
                'success' => false,
                'message' => 'No se puede eliminar esta categoría porque tiene posts asociados. Primero elimina o reasigna los posts.'
            ], 400);
            return;
        }
        // Eliminar
        $this->db->delete('blog_categories', 'id = :id', ['id' => $id]);
        $this->json([
            'success' => true,
            'message' => 'Categoría eliminada exitosamente'
        ]);
    }
    /**
     * Reordenar categorías vía drag & drop (AJAX)
     * URL: POST ?route=admin/blog/categorias/reordenar
     */
    public function reorder()
    {
        Auth::requireRole('admin');
        $this->validateCsrf();
        $order = $this->getInput('order'); // Array: [id => orden]
        if (!is_array($order)) {
            $this->json(['success' => false, 'message' => 'Datos inválidos'], 400);
            return;
        }
        $this->blogCategory->reorder($order);
        $this->json([
            'success' => true,
            'message' => 'Orden actualizado exitosamente'
        ]);
    }
    /**
     * Toggle estado activo/inactivo (AJAX)
     * URL: POST ?route=admin/blog/categorias/toggle-activo
     */
    public function toggleActive()
    {
        Auth::requireRole('admin');
        $this->validateCsrf();
        $id = $this->getInput('id');
        $category = $this->blogCategory->find($id);
        if (!$category) {
            $this->json(['success' => false, 'message' => 'Categoría no encontrada'], 404);
            return;
        }

        // Calcular nuevo estado ANTES de hacer el toggle
        $newStatus = $category['activo'] ? 0 : 1;

        // Hacer el toggle
        try {
            $this->blogCategory->toggleActive($id);
            $this->json([
                'success' => true,
                'message' => 'Estado actualizado',
                'activo' => $newStatus
            ]);
        } catch (Exception $e) {
            error_log("Error toggling category active: " . $e->getMessage());
            $this->json([
                'success' => false,
                'message' => 'Error al actualizar estado: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Generar slug automáticamente (AJAX)
     * URL: POST ?route=admin/blog/categorias/generar-slug
     */
    public function generateSlug()
    {
        Auth::requireRole('admin');
        $nombre = $this->getInput('nombre');
        if (empty($nombre)) {
            $this->json(['success' => false, 'message' => 'Nombre requerido'], 400);
            return;
        }
        // Generar slug
        $slug = $this->createSlug($nombre);
        // Verificar unicidad
        $counter = 1;
        $originalSlug = $slug;
        while ($this->slugExists($slug, $this->getInput('category_id'))) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        $this->json([
            'success' => true,
            'slug' => $slug
        ]);
    }
    /**
     * Obtener estadísticas de una categoría (AJAX)
     * URL: GET ?route=admin/blog/categorias/stats/{id}
     */
    public function stats($id)
    {
        Auth::requireRole('admin');
        $stats = $this->blogCategory->getWithStats($id);
        if (!$stats) {
            $this->json(['success' => false, 'message' => 'Categoría no encontrada'], 404);
            return;
        }
        $this->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
    // ========================================================================
    // MÉTODOS AUXILIARES
    // ========================================================================
    /**
     * Generar slug a partir de nombre
     */
    private function createSlug($text)
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
        $sql = "SELECT id FROM blog_categories WHERE slug = :slug";
        $params = ['slug' => $slug];
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        $result = $this->db->fetchOne($sql, $params);
        return !empty($result);
    }
    /**
     * Verificar si la petición es AJAX
     */
    private function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}