<?php
use App\Core\Config;
use App\Core\Helpers;
$pageTitle = $title ?? 'Editar Menú';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="container-fluid py-4">
    <?php
        $actionTitle = htmlspecialchars($menu['display_name']);
        $actionSubtitle = 'Ubicación: ' . ($menu['location'] ?? '-') . ' · Código: ' . ($menu['name'] ?? '-');
        $actionButtons = [
            ['label' => 'Volver', 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary', 'href' => Config::getBaseUrl() . '?route=admin/navigation'],
            ['label' => 'Agregar Item', 'icon' => 'fas fa-plus', 'variant' => 'primary', 'id' => 'addItemBtn'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Estadísticas -->
    <?php if (isset($stats)): ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-primary mb-0"><?= $stats['total_items'] ?? 0 ?></h3>
                    <small class="text-muted">Total Items</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-success mb-0"><?= $stats['visible_items'] ?? 0 ?></h3>
                    <small class="text-muted">Visibles</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-info mb-0"><?= $stats['top_level_items'] ?? 0 ?></h3>
                    <small class="text-muted">Items Principales</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="text-warning mb-0"><?= $stats['submenu_items'] ?? 0 ?></h3>
                    <small class="text-muted">Subitems</small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Editor de Items -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Items del Menú
                        <small class="text-muted">(Arrastra para reordenar)</small>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($items)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Este menú no tiene items todavía. Haz clic en "Agregar Item" para comenzar.
                    </div>
                    <?php else: ?>
                    <div id="sortable-menu" class="menu-items-container" data-menu-id="<?= $menu['id'] ?>">
                        <?php
                        // Función recursiva para renderizar items
                        function renderMenuItem($item, $depth = 0) {
                            $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
                            $hasChildren = !empty($item['children']);
                            ?>
                            <div class="menu-item card mb-2 <?= $item['visible'] ? '' : 'item-hidden' ?>"
                                 data-id="<?= $item['id'] ?>"
                                 data-parent-id="<?= $item['parent_id'] ?? 'null' ?>">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <!-- Drag Handle -->
                                        <div class="drag-handle me-3" style="cursor: move;">
                                            <i class="fas fa-grip-vertical text-muted"></i>
                                        </div>

                                        <!-- Indent visual para jerarquía -->
                                        <?php if ($depth > 0): ?>
                                        <div class="me-2 text-muted">
                                            <?= $indent ?><i class="fas fa-level-up-alt fa-rotate-90"></i>
                                        </div>
                                        <?php endif; ?>

                                        <!-- Ícono -->
                                        <div class="me-3">
                                            <?php if (!empty($item['icon'])): ?>
                                            <i class="<?= htmlspecialchars($item['icon']) ?> text-primary fa-lg"></i>
                                            <?php else: ?>
                                            <i class="fas fa-link text-muted"></i>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Info del item -->
                                        <div class="flex-grow-1">
                                            <div class="fw-bold"><?= htmlspecialchars($item['label']) ?></div>
                                            <small class="text-muted">
                                                <?php if (!empty($item['route'])): ?>
                                                    <i class="fas fa-route"></i> <?= htmlspecialchars($item['route']) ?>
                                                <?php elseif (!empty($item['url'])): ?>
                                                    <i class="fas fa-external-link-alt"></i> <?= htmlspecialchars($item['url']) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>

                                        <!-- Badges -->
                                        <div class="me-3">
                                            <?php if ($item['auth_required']): ?>
                                            <span class="badge bg-warning" title="Requiere autenticación">
                                                <i class="fas fa-lock"></i>
                                            </span>
                                            <?php endif; ?>
                                            <?php if ($item['role_required']): ?>
                                            <span class="badge bg-info" title="Rol: <?= htmlspecialchars($item['role_required']) ?>">
                                                <?= htmlspecialchars($item['role_required']) ?>
                                            </span>
                                            <?php endif; ?>
                                            <?php if ($hasChildren): ?>
                                            <span class="badge bg-secondary" title="Tiene subitems">
                                                <i class="fas fa-sitemap"></i> <?= count($item['children']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Acciones -->
                                        <div class="btn-group btn-group-sm">
                                            <button type="button"
                                                    class="btn btn-outline-<?= $item['visible'] ? 'success' : 'secondary' ?> toggle-visible-btn"
                                                    data-id="<?= $item['id'] ?>"
                                                    title="<?= $item['visible'] ? 'Ocultar' : 'Mostrar' ?>">
                                                <i class="fas fa-eye<?= $item['visible'] ? '' : '-slash' ?>"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-outline-primary edit-item-btn"
                                                    data-id="<?= $item['id'] ?>"
                                                    title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-outline-danger delete-item-btn"
                                                    data-id="<?= $item['id'] ?>"
                                                    title="Eliminar"
                                                    <?= $hasChildren ? 'disabled' : '' ?>>
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                            // Renderizar hijos recursivamente
                            if ($hasChildren) {
                                foreach ($item['children'] as $child) {
                                    renderMenuItem($child, $depth + 1);
                                }
                            }
                        }

                        // Renderizar todos los items
                        foreach ($items as $item) {
                            renderMenuItem($item);
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Agregar/Editar Item -->
<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemModalTitle">Agregar Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="itemForm">
                <input type="hidden" name="csrf_token" value="<?= Helpers::generateCsrfToken() ?>">
                <input type="hidden" name="id" id="item_id">
                <input type="hidden" name="menu_id" value="<?= $menu['id'] ?>">

                <div class="modal-body">
                    <!-- Label -->
                    <div class="mb-3">
                        <label for="item_label" class="form-label">Texto del Enlace *</label>
                        <input type="text" class="form-control" id="item_label" name="label" required>
                    </div>

                    <!-- URL o Route -->
                    <div class="mb-3">
                        <label class="form-label">Destino del Enlace *</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="link_type" id="link_type_route" value="route" checked>
                                    <label class="form-check-label" for="link_type_route">
                                        Ruta Interna
                                    </label>
                                </div>
                                <select class="form-select" id="item_route" name="route">
                                    <option value="">Seleccionar...</option>
                                    <option value="home">Inicio</option>
                                    <option value="tours">Catálogo</option>
                                    <option value="destinations">Destinos</option>
                                    <option value="transfers">Traslados</option>
                                    <option value="faq">FAQ</option>
                                    <option value="contact">Contacto</option>
                                    <option value="about">Sobre Nosotros</option>
                                    <option value="terms">Términos</option>
                                    <option value="privacy">Privacidad</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="link_type" id="link_type_url" value="url">
                                    <label class="form-check-label" for="link_type_url">
                                        URL Externa
                                    </label>
                                </div>
                                <input type="url" class="form-control" id="item_url" name="url" placeholder="https://...">
                            </div>
                        </div>
                    </div>

                    <!-- Ícono -->
                    <div class="mb-3">
                        <label for="item_icon" class="form-label">Ícono Font Awesome</label>
                        <div class="input-group">
                            <span class="input-group-text"><i id="icon_preview" class="fas fa-link"></i></span>
                            <input type="text" class="form-control" id="item_icon" name="icon" placeholder="fas fa-home">
                        </div>
                        <small class="text-muted">Ejemplo: fas fa-home, fas fa-envelope, etc.</small>
                    </div>

                    <div class="row">
                        <!-- Parent -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="item_parent" class="form-label">Item Padre (Submenú)</label>
                                <select class="form-select" id="item_parent" name="parent_id">
                                    <option value="">Ninguno (Item principal)</option>
                                    <?php
                                    // Listar items principales como posibles padres
                                    function listParentOptions($items, $currentId = null, $depth = 0) {
                                        foreach ($items as $item) {
                                            if ($item['id'] != $currentId) {
                                                $indent = str_repeat('&nbsp;&nbsp;', $depth);
                                                echo '<option value="' . $item['id'] . '">' . $indent . htmlspecialchars($item['label']) . '</option>';
                                                if (!empty($item['children'])) {
                                                    listParentOptions($item['children'], $currentId, $depth + 1);
                                                }
                                            }
                                        }
                                    }
                                    if (!empty($items)) {
                                        listParentOptions($items);
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Target -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="item_target" class="form-label">Abrir en</label>
                                <select class="form-select" id="item_target" name="target">
                                    <option value="_self">Misma ventana</option>
                                    <option value="_blank">Nueva ventana</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Checkboxes -->
                    <div class="mb-3">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" id="item_visible" name="visible" checked>
                            <label class="form-check-label" for="item_visible">
                                Visible en el menú
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="item_auth_required" name="auth_required">
                            <label class="form-check-label" for="item_auth_required">
                                Requiere autenticación
                            </label>
                        </div>
                    </div>

                    <!-- Rol -->
                    <div class="mb-3">
                        <label for="item_role" class="form-label">Rol Requerido</label>
                        <select class="form-select" id="item_role" name="role_required">
                            <option value="">Todos</option>
                            <option value="admin">Solo Administradores</option>
                            <option value="client">Solo Clientes</option>
                        </select>
                    </div>

                    <!-- CSS Class -->
                    <div class="mb-3">
                        <label for="item_css_class" class="form-label">Clases CSS Adicionales</label>
                        <input type="text" class="form-control" id="item_css_class" name="css_class" placeholder="opcional">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Estilos adicionales -->
<style>
.menu-items-container {
    min-height: 100px;
}

.menu-item {
    transition: all 0.2s ease;
    border-left: 4px solid #007bff;
}

.menu-item.item-hidden {
    opacity: 0.5;
    border-left-color: #6c757d;
}

.menu-item:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.drag-handle {
    cursor: move;
}

.sortable-ghost {
    opacity: 0.4;
    background: #f8f9fa;
}

.sortable-drag {
    opacity: 1;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
}
</style>

<!-- Cargar Sortable.js y Navigation Editor -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="<?= Helpers::asset('js/navigation-editor.js') ?>"></script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
