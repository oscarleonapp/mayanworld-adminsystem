<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Models\NavigationMenu;
use App\Helpers\AuditLogger;
use Exception;

/**
 * NavigationController
 *
 * Gestiona menús de navegación desde el panel administrativo
 * Permite crear, editar, reordenar y eliminar items de menú
 */
class NavigationController extends BaseController
{
    private $navigationModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->navigationModel = new NavigationMenu();
    }

    /**
     * Listar menús disponibles
     */
    public function index()
    {
        try {
            $menus = $this->navigationModel->findAll();

            // Agregar estadísticas a cada menú
            foreach ($menus as &$menu) {
                $menu['stats'] = $this->navigationModel->getMenuStats($menu['id']);
            }

            $this->view('admin/navigation/index', [
                'title' => 'Gestión de Menús',
                'menus' => $menus
            ]);

        } catch (Exception $e) {
            $this->handleValidationErrors('Error al cargar menús: ' . $e->getMessage(), 'admin');
        }
    }

    /**
     * Editar items de un menú específico
     */
    public function edit($menuId = null)
    {
        if (!$menuId) {
            $this->redirect('admin/navigation', 'ID de menú requerido', 'error');
            return;
        }

        try {
            // Obtener menú
            $menu = $this->navigationModel->find($menuId);
            if (!$menu) {
                $this->redirect('admin/navigation', 'Menú no encontrado', 'error');
                return;
            }

            // Obtener items con jerarquía
            $items = $this->navigationModel->getItemsHierarchical($menuId);

            // Obtener estadísticas
            $stats = $this->navigationModel->getMenuStats($menuId);

            $this->view('admin/navigation/edit', [
                'title' => 'Editar Menú: ' . $menu['display_name'],
                'menu' => $menu,
                'items' => $items,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            $this->handleValidationErrors('Error al cargar menú: ' . $e->getMessage(), 'admin/navigation');
        }
    }

    /**
     * Agregar nuevo item al menú
     */
    public function addItem()
    {
        $this->validateCsrf();

        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }

        try {
            $data = [
                'menu_id' => $this->getInput('menu_id'),
                'parent_id' => $this->getInput('parent_id') ?: null,
                'label' => $this->sanitizeInput($this->getInput('label')),
                'url' => $this->sanitizeInput($this->getInput('url')),
                'route' => $this->sanitizeInput($this->getInput('route')),
                'icon' => $this->sanitizeInput($this->getInput('icon')),
                'target' => $this->sanitizeInput($this->getInput('target', '_self')),
                'css_class' => $this->sanitizeInput($this->getInput('css_class')),
                'orden' => (int)$this->getInput('orden', 0),
                'visible' => (bool)$this->getInput('visible', true),
                'auth_required' => (bool)$this->getInput('auth_required', false),
                'role_required' => $this->sanitizeInput($this->getInput('role_required')) ?: null
            ];

            // Validar
            $errors = $this->navigationModel->validateItem($data);
            if (!empty($errors)) {
                $this->json(['success' => false, 'message' => implode(', ', $errors)], 400);
            }

            // Insertar
            $itemId = $this->navigationModel->addItem($data);

            // Audit log
            AuditLogger::log('crear', 'navigation_items', $itemId, $data['label']);

            $this->json([
                'success' => true,
                'message' => 'Item agregado correctamente',
                'item_id' => $itemId
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Actualizar item existente
     */
    public function updateItem()
    {
        $this->validateCsrf();

        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }

        try {
            $itemId = $this->getInput('id');
            if (!$itemId) {
                $this->json(['success' => false, 'message' => 'ID requerido'], 400);
            }

            // Obtener item actual
            $currentItem = $this->navigationModel->getItem($itemId);
            if (!$currentItem) {
                $this->json(['success' => false, 'message' => 'Item no encontrado'], 404);
            }

            $data = [
                'menu_id' => $currentItem['menu_id'], // No se puede cambiar el menú
                'parent_id' => $this->getInput('parent_id') ?: null,
                'label' => $this->sanitizeInput($this->getInput('label')),
                'url' => $this->sanitizeInput($this->getInput('url')),
                'route' => $this->sanitizeInput($this->getInput('route')),
                'icon' => $this->sanitizeInput($this->getInput('icon')),
                'target' => $this->sanitizeInput($this->getInput('target', '_self')),
                'css_class' => $this->sanitizeInput($this->getInput('css_class')),
                'orden' => (int)$this->getInput('orden', 0),
                'visible' => (bool)$this->getInput('visible', true),
                'auth_required' => (bool)$this->getInput('auth_required', false),
                'role_required' => $this->sanitizeInput($this->getInput('role_required')) ?: null
            ];

            // Validar
            $errors = $this->navigationModel->validateItem($data);
            if (!empty($errors)) {
                $this->json(['success' => false, 'message' => implode(', ', $errors)], 400);
            }

            // Actualizar
            $this->navigationModel->updateItem($itemId, $data);

            // Audit log
            AuditLogger::log('editar', 'navigation_items', $itemId, $data['label'], $currentItem, $data);

            $this->json([
                'success' => true,
                'message' => 'Item actualizado correctamente'
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar item
     */
    public function deleteItem()
    {
        $this->validateCsrf();

        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }

        try {
            $itemId = $this->getInput('id');
            if (!$itemId) {
                $this->json(['success' => false, 'message' => 'ID requerido'], 400);
            }

            // Obtener item
            $item = $this->navigationModel->getItem($itemId);
            if (!$item) {
                $this->json(['success' => false, 'message' => 'Item no encontrado'], 404);
            }

            // Verificar si tiene hijos
            if ($this->navigationModel->hasChildren($itemId)) {
                $this->json([
                    'success' => false,
                    'message' => 'No se puede eliminar un item que tiene subitems. Elimine primero los subitems.'
                ], 400);
            }

            // Eliminar
            $this->navigationModel->deleteItem($itemId);

            // Audit log
            AuditLogger::log('eliminar', 'navigation_items', $itemId, $item['label']);

            $this->json([
                'success' => true,
                'message' => 'Item eliminado correctamente'
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reordenar items (drag & drop)
     */
    public function reorder()
    {
        $this->validateCsrf();

        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }

        try {
            $menuId = $this->getInput('menu_id');
            $itemsOrder = $this->getInput('items'); // Array de IDs en orden

            if (!$menuId || !is_array($itemsOrder)) {
                $this->json(['success' => false, 'message' => 'Datos inválidos'], 400);
            }

            // Reordenar
            $result = $this->navigationModel->reorderItems($menuId, $itemsOrder);

            if (!$result) {
                $this->json(['success' => false, 'message' => 'Error al reordenar'], 500);
            }

            // Audit log
            AuditLogger::log('editar', 'navigation_menus', $menuId, 'Reordenar items');

            $this->json([
                'success' => true,
                'message' => 'Items reordenados correctamente'
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Toggle visibilidad de item
     */
    public function toggleVisible()
    {
        $this->validateCsrf();

        try {
            $itemId = $this->getInput('id');
            if (!$itemId) {
                $this->json(['success' => false, 'message' => 'ID requerido'], 400);
            }

            $item = $this->navigationModel->getItem($itemId);
            if (!$item) {
                $this->json(['success' => false, 'message' => 'Item no encontrado'], 404);
            }

            $newVisible = !$item['visible'];

            $this->navigationModel->updateItem($itemId, ['visible' => $newVisible]);

            // Audit log
            AuditLogger::log(
                'editar',
                'navigation_items',
                $itemId,
                $item['label'],
                ['visible' => $item['visible']],
                ['visible' => $newVisible]
            );

            $this->json([
                'success' => true,
                'visible' => $newVisible,
                'message' => $newVisible ? 'Item visible' : 'Item oculto'
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtener item para edición (AJAX)
     */
    public function getItem()
    {
        try {
            $itemId = $this->getInput('id');
            if (!$itemId) {
                $this->json(['success' => false, 'message' => 'ID requerido'], 400);
            }

            $item = $this->navigationModel->getItem($itemId);
            if (!$item) {
                $this->json(['success' => false, 'message' => 'Item no encontrado'], 404);
            }

            $this->json([
                'success' => true,
                'item' => $item
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Clonar menú
     */
    public function cloneMenu()
    {
        $this->validateCsrf();

        if (!Helpers::isPost()) {
            $this->redirect('admin/navigation', 'Método no permitido', 'error');
            return;
        }

        try {
            $menuId = $this->getInput('menu_id');
            $newName = $this->sanitizeInput($this->getInput('new_name'));

            if (!$menuId || !$newName) {
                $this->redirect('admin/navigation', 'Datos incompletos', 'error');
                return;
            }

            // Verificar que el nuevo nombre no exista
            $existing = $this->navigationModel->getByName($newName);
            if ($existing) {
                $this->redirect('admin/navigation', 'Ya existe un menú con ese nombre', 'error');
                return;
            }

            $newMenuId = $this->navigationModel->cloneMenu($menuId, $newName);

            // Audit log
            AuditLogger::log('crear', 'navigation_menus', $newMenuId, 'Clonar menú');

            $this->redirect('admin/navigation/edit/' . $newMenuId, 'Menú clonado correctamente', 'success');

        } catch (Exception $e) {
            $this->redirect('admin/navigation', 'Error al clonar menú: ' . $e->getMessage(), 'error');
        }
    }
}
