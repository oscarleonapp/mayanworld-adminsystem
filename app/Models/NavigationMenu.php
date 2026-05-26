<?php
namespace App\Models;

use App\Core\Model;
use Exception;

/**
 * NavigationMenu Model
 *
 * Gestiona menús de navegación y sus items
 */
class NavigationMenu extends Model
{
    protected $table = 'navigation_menus';
    protected $fillable = ['name', 'display_name', 'description', 'location'];

    /**
     * Obtener un menú por su nombre
     */
    public function getByName($name)
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE name = :name",
            ['name' => $name]
        );
    }

    /**
     * Obtener todos los items de un menú con estructura jerárquica
     */
    public function getItemsHierarchical($menuId, $parentId = null)
    {
        $items = $this->db->fetchAll(
            "SELECT * FROM navigation_items
             WHERE menu_id = :menu_id AND parent_id " . ($parentId === null ? 'IS NULL' : '= :parent_id') . "
             ORDER BY orden ASC, id ASC",
            array_filter([
                'menu_id' => $menuId,
                'parent_id' => $parentId
            ], function($val) { return $val !== null; })
        );

        // Agregar hijos recursivamente
        foreach ($items as &$item) {
            $item['children'] = $this->getItemsHierarchical($menuId, $item['id']);
        }

        return $items;
    }

    /**
     * Obtener todos los items de un menú (planos, sin jerarquía)
     */
    public function getItemsFlat($menuId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM navigation_items
             WHERE menu_id = :menu_id
             ORDER BY orden ASC, id ASC",
            ['menu_id' => $menuId]
        );
    }

    /**
     * Obtener items visibles de un menú con filtros de autenticación
     */
    public function getVisibleItems($menuId, $isAuthenticated = false, $userRole = null, $parentId = null)
    {
        $sql = "SELECT * FROM navigation_items
                WHERE menu_id = :menu_id
                AND visible = TRUE
                AND parent_id " . ($parentId === null ? 'IS NULL' : '= :parent_id');

        // Filtrar por autenticación
        if (!$isAuthenticated) {
            $sql .= " AND auth_required = FALSE";
        }

        // Filtrar por rol si está especificado
        if ($userRole) {
            $sql .= " AND (role_required IS NULL OR role_required = :role)";
        } else {
            $sql .= " AND role_required IS NULL";
        }

        $sql .= " ORDER BY orden ASC, id ASC";

        $params = array_filter([
            'menu_id' => $menuId,
            'parent_id' => $parentId,
            'role' => $userRole
        ], function($val) { return $val !== null; });

        $items = $this->db->fetchAll($sql, $params);

        // Agregar hijos recursivamente
        foreach ($items as &$item) {
            $item['children'] = $this->getVisibleItems($menuId, $isAuthenticated, $userRole, $item['id']);
        }

        return $items;
    }

    /**
     * Agregar nuevo item al menú
     */
    public function addItem($data)
    {
        $allowedFields = [
            'menu_id', 'parent_id', 'label', 'url', 'route',
            'icon', 'target', 'css_class', 'orden', 'visible',
            'auth_required', 'role_required'
        ];

        $insertData = array_intersect_key($data, array_flip($allowedFields));

        return $this->db->insert('navigation_items', $insertData);
    }

    /**
     * Actualizar item del menú
     */
    public function updateItem($itemId, $data)
    {
        $allowedFields = [
            'parent_id', 'label', 'url', 'route',
            'icon', 'target', 'css_class', 'orden', 'visible',
            'auth_required', 'role_required'
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));

        return $this->db->update(
            'navigation_items',
            $updateData,
            'id = :id',
            ['id' => $itemId]
        );
    }

    /**
     * Eliminar item del menú
     */
    public function deleteItem($itemId)
    {
        // Los hijos se eliminan automáticamente por CASCADE
        return $this->db->delete('navigation_items', 'id = :id', ['id' => $itemId]);
    }

    /**
     * Reordenar items del menú
     */
    public function reorderItems($menuId, $itemsOrder)
    {
        $this->db->beginTransaction();

        try {
            foreach ($itemsOrder as $orden => $itemId) {
                $this->db->update(
                    'navigation_items',
                    ['orden' => $orden + 1], // Orden empieza en 1
                    'id = :id AND menu_id = :menu_id',
                    ['id' => $itemId, 'menu_id' => $menuId]
                );
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Obtener un item específico
     */
    public function getItem($itemId)
    {
        return $this->db->fetch(
            "SELECT * FROM navigation_items WHERE id = :id",
            ['id' => $itemId]
        );
    }

    /**
     * Verificar si un item tiene hijos
     */
    public function hasChildren($itemId)
    {
        $count = $this->db->fetch(
            "SELECT COUNT(*) as count FROM navigation_items WHERE parent_id = :parent_id",
            ['parent_id' => $itemId]
        );

        return $count && $count['count'] > 0;
    }

    /**
     * Clonar/duplicar un menú completo
     */
    public function cloneMenu($menuId, $newName)
    {
        $this->db->beginTransaction();

        try {
            // Obtener menú original
            $menu = $this->find($menuId);
            if (!$menu) {
                throw new Exception('Menú no encontrado');
            }

            // Crear nuevo menú
            $newMenuId = $this->db->insert($this->table, [
                'name' => $newName,
                'display_name' => $menu['display_name'] . ' (Copia)',
                'description' => $menu['description'],
                'location' => $menu['location']
            ]);

            // Clonar items
            $items = $this->getItemsFlat($menuId);
            $itemMap = []; // Mapeo de IDs antiguos a nuevos

            foreach ($items as $item) {
                $oldId = $item['id'];
                $oldParentId = $item['parent_id'];

                unset($item['id'], $item['created_at'], $item['updated_at']);
                $item['menu_id'] = $newMenuId;

                // Si tiene padre, usar el nuevo ID del padre
                if ($oldParentId && isset($itemMap[$oldParentId])) {
                    $item['parent_id'] = $itemMap[$oldParentId];
                }

                $newItemId = $this->db->insert('navigation_items', $item);
                $itemMap[$oldId] = $newItemId;
            }

            $this->db->commit();
            return $newMenuId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Obtener estadísticas de un menú
     */
    public function getMenuStats($menuId)
    {
        return $this->db->fetch(
            "SELECT
                COUNT(*) as total_items,
                SUM(CASE WHEN visible = TRUE THEN 1 ELSE 0 END) as visible_items,
                SUM(CASE WHEN parent_id IS NULL THEN 1 ELSE 0 END) as top_level_items,
                SUM(CASE WHEN parent_id IS NOT NULL THEN 1 ELSE 0 END) as submenu_items,
                SUM(CASE WHEN auth_required = TRUE THEN 1 ELSE 0 END) as auth_items
             FROM navigation_items
             WHERE menu_id = :menu_id",
            ['menu_id' => $menuId]
        );
    }

    /**
     * Validar estructura de item
     */
    public function validateItem($data)
    {
        $errors = [];

        if (empty($data['label'])) {
            $errors[] = 'El texto del enlace es requerido';
        }

        if (empty($data['url']) && empty($data['route'])) {
            $errors[] = 'Debe especificar una URL o una ruta';
        }

        if (!empty($data['parent_id'])) {
            $parent = $this->getItem($data['parent_id']);
            if (!$parent) {
                $errors[] = 'El item padre no existe';
            } elseif ($parent['menu_id'] != $data['menu_id']) {
                $errors[] = 'El item padre pertenece a otro menú';
            }
        }

        return $errors;
    }
}
