<?php
namespace App\Helpers;

use App\Models\NavigationMenu;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Helpers;

/**
 * NavigationHelper
 *
 * Helper para renderizar menús de navegación dinámicos
 * Genera HTML optimizado con Bootstrap 5 y soporte para submenús
 */
class NavigationHelper
{
    private static $model = null;
    private static $currentRoute = null;

    /**
     * Inicializar helper
     */
    private static function init()
    {
        if (self::$model === null) {
            self::$model = new NavigationMenu();
        }

        if (self::$currentRoute === null) {
            self::$currentRoute = $_GET['route'] ?? 'home';
        }
    }

    /**
     * Renderizar menú completo por nombre
     *
     * @param string $menuName Nombre del menú (main, footer, user)
     * @param array $options Opciones de renderizado
     * @return string HTML del menú
     */
    public static function renderMenu($menuName = 'main', $options = [])
    {
        self::init();

        // Obtener menú
        $menu = self::$model->getByName($menuName);
        if (!$menu) {
            return '<!-- Menu "' . htmlspecialchars($menuName) . '" no encontrado -->';
        }

        // Obtener estado de autenticación
        $auth = Auth::getInstance();
        $isAuthenticated = $auth->isLoggedIn();
        $userRole = $isAuthenticated ? ($auth->getUser()['tipo'] ?? null) : null;

        // Obtener items visibles
        $items = self::$model->getVisibleItems($menu['id'], $isAuthenticated, $userRole);
        $items = self::customizeMenuItems($menuName, $items);

        if (empty($items)) {
            return '<!-- Sin items visibles en menú "' . htmlspecialchars($menuName) . '" -->';
        }

        // Opciones por defecto
        $defaults = [
            'class' => 'navbar-nav',
            'item_class' => 'nav-item',
            'link_class' => 'nav-link',
            'dropdown_class' => 'dropdown-menu',
            'show_icons' => true,
            'format' => 'bootstrap5', // bootstrap5, simple, list
        ];

        $options = array_merge($defaults, $options);

        // Renderizar según formato
        switch ($options['format']) {
            case 'bootstrap5':
                return self::renderBootstrap5Menu($items, $options);
            case 'simple':
                return self::renderSimpleMenu($items, $options);
            case 'list':
                return self::renderListMenu($items, $options);
            default:
                return self::renderBootstrap5Menu($items, $options);
        }
    }

    /**
     * Renderizar menú con Bootstrap 5
     */
    private static function renderBootstrap5Menu($items, $options)
    {
        $html = '<ul class="' . htmlspecialchars($options['class']) . '">';

        foreach ($items as $item) {
            $html .= self::renderBootstrap5Item($item, $options);
        }

        $html .= '</ul>';
        return $html;
    }

    /**
     * Renderizar item individual de Bootstrap 5
     */
    private static function renderBootstrap5Item($item, $options, $depth = 0)
    {
        $hasChildren = !empty($item['children']);
        $isActive = self::isActive($item);

        // Clases del item
        $itemClass = $options['item_class'];
        if ($hasChildren) {
            $itemClass .= ' dropdown';
        }

        $html = '<li class="' . htmlspecialchars($itemClass) . '">';

        // Link
        $linkClass = $options['link_class'];
        if ($isActive) {
            $linkClass .= ' active';
        }
        if ($hasChildren) {
            $linkClass .= ' dropdown-toggle';
        }
        if (!empty($item['css_class'])) {
            $linkClass .= ' ' . $item['css_class'];
        }

        $url = self::getItemUrl($item);
        $target = $item['target'] ?? '_self';

        if ($hasChildren) {
            // Item con dropdown
            $html .= '<a class="' . htmlspecialchars($linkClass) . '" ';
            $html .= 'href="#" ';
            $html .= 'role="button" ';
            $html .= 'data-bs-toggle="dropdown" ';
            $html .= 'aria-expanded="false">';
        } else {
            // Item simple
            $html .= '<a class="' . htmlspecialchars($linkClass) . '" ';
            $html .= 'href="' . htmlspecialchars($url) . '" ';
            $html .= 'target="' . htmlspecialchars($target) . '"';
            if ($isActive) {
                $html .= ' aria-current="page"';
            }
            $html .= '>';
        }

        // Ícono
        if ($options['show_icons'] && !empty($item['icon'])) {
            $html .= '<i class="' . htmlspecialchars($item['icon']) . ' me-1" aria-hidden="true"></i>';
        }

        // Label
        $label = self::normalizeLabel($item['label'] ?? '');
        $html .= htmlspecialchars($label);
        $html .= '</a>';

        // Submenú si tiene hijos
        if ($hasChildren) {
            $html .= '<ul class="' . htmlspecialchars($options['dropdown_class']) . '">';
            foreach ($item['children'] as $child) {
                $html .= self::renderBootstrap5DropdownItem($child, $options);
            }
            $html .= '</ul>';
        }

        $html .= '</li>';
        return $html;
    }

    /**
     * Renderizar item de dropdown
     */
    private static function renderBootstrap5DropdownItem($item, $options)
    {
        $isActive = self::isActive($item);
        $url = self::getItemUrl($item);
        $target = $item['target'] ?? '_self';

        $linkClass = 'dropdown-item';
        if ($isActive) {
            $linkClass .= ' active';
        }
        if (!empty($item['css_class'])) {
            $linkClass .= ' ' . $item['css_class'];
        }

        $html = '<li>';
        $html .= '<a class="' . htmlspecialchars($linkClass) . '" ';
        $html .= 'href="' . htmlspecialchars($url) . '" ';
        $html .= 'target="' . htmlspecialchars($target) . '">';

        // Ícono
        if ($options['show_icons'] && !empty($item['icon'])) {
            $html .= '<i class="' . htmlspecialchars($item['icon']) . ' me-2" aria-hidden="true"></i>';
        }

        $label = self::normalizeLabel($item['label'] ?? '');
        $html .= htmlspecialchars($label);
        $html .= '</a>';
        $html .= '</li>';

        return $html;
    }

    /**
     * Renderizar menú simple (sin Bootstrap)
     */
    private static function renderSimpleMenu($items, $options)
    {
        $html = '<nav>';
        foreach ($items as $item) {
            $url = self::getItemUrl($item);
            $isActive = self::isActive($item);
            $activeClass = $isActive ? ' class="active"' : '';

            $html .= '<a href="' . htmlspecialchars($url) . '"' . $activeClass . '>';
            if ($options['show_icons'] && !empty($item['icon'])) {
                $html .= '<i class="' . htmlspecialchars($item['icon']) . '"></i> ';
            }
            $label = self::normalizeLabel($item['label'] ?? '');
            $html .= htmlspecialchars($label);
            $html .= '</a>';
        }
        $html .= '</nav>';
        return $html;
    }

    /**
     * Renderizar menú como lista simple
     */
    private static function renderListMenu($items, $options)
    {
        $html = '<ul class="' . htmlspecialchars($options['class']) . '">';
        foreach ($items as $item) {
            $url = self::getItemUrl($item);
            $isActive = self::isActive($item);
            $activeClass = $isActive ? ' class="active"' : '';

            $html .= '<li' . $activeClass . '>';
            $html .= '<a href="' . htmlspecialchars($url) . '">';
            if ($options['show_icons'] && !empty($item['icon'])) {
                $html .= '<i class="' . htmlspecialchars($item['icon']) . '"></i> ';
            }
            $label = self::normalizeLabel($item['label'] ?? '');
            $html .= htmlspecialchars($label);
            $html .= '</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * Obtener URL de un item
     */
    private static function getItemUrl($item)
    {
        // Si tiene URL completa, usarla
        if (!empty($item['url'])) {
            return $item['url'];
        }

        // Si tiene route, construir URL
        if (!empty($item['route'])) {
            $baseUrl = Config::getBaseUrl();
            if ($item['route'] === 'home' || $item['route'] === '') {
                return $baseUrl;
            }
            return $baseUrl . '?route=' . $item['route'];
        }

        // Fallback
        return '#';
    }

    /**
     * Verificar si un item está activo
     */
    private static function isActive($item)
    {
        // Si tiene route, comparar con ruta actual
        if (!empty($item['route'])) {
            $route = $item['route'];

            // Normalizar rutas
            $currentRoute = self::$currentRoute;
            if ($currentRoute === '' || $currentRoute === 'home') {
                $currentRoute = 'home';
            }
            if ($route === '' || $route === 'home') {
                $route = 'home';
            }

            return $currentRoute === $route;
        }

        // Si tiene URL, comparar con URL actual (básico)
        if (!empty($item['url'])) {
            $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
            return strpos($currentUrl, $item['url']) !== false;
        }

        return false;
    }

    /**
     * Renderizar breadcrumbs (migas de pan)
     */
    public static function renderBreadcrumbs($items)
    {
        if (empty($items)) {
            return '';
        }

        $html = '<nav aria-label="breadcrumb">';
        $html .= '<ol class="breadcrumb">';

        $total = count($items);
        foreach ($items as $index => $item) {
            $isLast = ($index === $total - 1);
            $label = self::normalizeLabel($item['label'] ?? '');

            if ($isLast) {
                $html .= '<li class="breadcrumb-item active" aria-current="page">';
                $html .= htmlspecialchars($label);
                $html .= '</li>';
            } else {
                $html .= '<li class="breadcrumb-item">';
                $html .= '<a href="' . htmlspecialchars($item['url']) . '">';
                $html .= htmlspecialchars($label);
                $html .= '</a>';
                $html .= '</li>';
            }
        }

        $html .= '</ol>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Obtener items de menú como array (para uso en JavaScript)
     */
    public static function getMenuJson($menuName)
    {
        self::init();

        $menu = self::$model->getByName($menuName);
        if (!$menu) {
            return json_encode([]);
        }

        $auth = Auth::getInstance();
        $isAuthenticated = $auth->isLoggedIn();
        $userRole = $isAuthenticated ? ($auth->getUser()['tipo'] ?? null) : null;

        $items = self::$model->getVisibleItems($menu['id'], $isAuthenticated, $userRole);

        // Simplificar estructura para JSON
        $simplified = self::simplifyForJson($items);

        return json_encode($simplified, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Simplificar items para JSON
     */
    private static function simplifyForJson($items)
    {
        $result = [];
        foreach ($items as $item) {
            $label = self::normalizeLabel($item['label'] ?? '');
            $result[] = [
                'id' => $item['id'],
                'label' => $label,
                'url' => self::getItemUrl($item),
                'icon' => $item['icon'] ?? null,
                'children' => !empty($item['children']) ? self::simplifyForJson($item['children']) : []
            ];
        }
        return $result;
    }

    /**
     * Normaliza etiquetas provenientes de la base de datos
     */
    private static function normalizeLabel($label)
    {
        if ($label === null || $label === '') {
            return '';
        }

        return $label;
    }

    /**
     * Reglas personalizadas para el menú principal (navbar)
     */
    private static function customizeMenuItems($menuName, $items)
    {
        if ($menuName !== 'main' || empty($items)) {
            return $items;
        }

        $filtered = [];

        foreach ($items as $item) {
            $route = $item['route'] ?? '';
            $label = mb_strtolower($item['label'] ?? '');

            // Eliminar "Destinos" del navbar
            if ($route === 'destinations' || $label === 'destinos') {
                continue;
            }

            // Asignar ícono de Destinos al enlace de Catálogo
            if ($route === 'tours' || $label === 'catálogo') {
                $item['icon'] = 'fas fa-map-marked-alt';
                $item['label'] = 'Destinos';
            }

            if (!empty($item['children'])) {
                $item['children'] = self::customizeMenuItems($menuName, $item['children']);
            }

            $filtered[] = $item;
        }

        return $filtered;
    }
}
