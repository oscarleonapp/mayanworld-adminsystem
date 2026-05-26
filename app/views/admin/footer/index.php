<?php
use App\Core\Config;
use App\Core\Helpers;
require_once __DIR__ . '/../../layouts/admin_header.php'; ?>

<div class="container-fluid px-4 py-3">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= Config::getBaseUrl() ?>?route=admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Editor de Footer</li>
        </ol>
    </nav>

    <?php
        $actionTitle = 'Editor de Footer';
        $actionSubtitle = 'Personaliza el footer con secciones arrastrables y configurables';
        $actionButtons = [
            ['label' => 'Configuración Global', 'icon' => 'fas fa-cog', 'variant' => 'outline-primary', 'id' => 'settingsBtn'],
            ['label' => 'Ver Sitio', 'icon' => 'fas fa-external-link-alt', 'variant' => 'outline-secondary', 'href' => Config::getBaseUrl(), 'attributes' => ['target' => '_blank']],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Estado del Footer Dinámico -->
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-4">
        <div>
            <i class="fas fa-info-circle me-2"></i>
            <strong>Footer Dinámico:</strong>
            <span id="footerStatus">
                <?= ($config['enabled'] ?? 'yes') === 'yes' ? 'Activado' : 'Desactivado' ?>
            </span>
        </div>
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="enableFooterToggle"
                   <?= ($config['enabled'] ?? 'yes') === 'yes' ? 'checked' : '' ?>>
            <label class="form-check-label" for="enableFooterToggle">
                Activar/Desactivar
            </label>
        </div>
    </div>

    <!-- Botones de Agregar Sección -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="card-title mb-3">
                <i class="fas fa-plus-circle me-2"></i>Agregar Nueva Sección
            </h6>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-primary add-section-btn"
                        data-type="company_info">
                    <i class="fas fa-building me-1"></i>Info Empresa
                </button>
                <button type="button" class="btn btn-sm btn-outline-success add-section-btn"
                        data-type="links">
                    <i class="fas fa-link me-1"></i>Enlaces
                </button>
                <button type="button" class="btn btn-sm btn-outline-info add-section-btn"
                        data-type="contact">
                    <i class="fas fa-address-book me-1"></i>Contacto
                </button>
                <button type="button" class="btn btn-sm btn-outline-warning add-section-btn"
                        data-type="social">
                    <i class="fas fa-share-alt me-1"></i>Redes Sociales
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary add-section-btn"
                        data-type="newsletter">
                    <i class="fas fa-envelope me-1"></i>Newsletter
                </button>
                <button type="button" class="btn btn-sm btn-outline-dark add-section-btn"
                        data-type="custom">
                    <i class="fas fa-code me-1"></i>HTML Personalizado
                </button>
            </div>
        </div>
    </div>

    <!-- Grid de Columnas del Footer -->
    <div class="row g-3" id="footerColumns">
        <?php
        $numColumns = (int)($config['num_columns'] ?? 4);
        for ($col = 1; $col <= $numColumns; $col++):
            $columnSections = $sectionsByColumn[$col] ?? [];
        ?>
        <div class="col-md-<?= 12 / $numColumns ?>">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-columns me-2"></i>Columna <?= $col ?>
                    </h6>
                    <span class="badge bg-primary"><?= count($columnSections) ?> secciones</span>
                </div>
                <div class="card-body p-2" style="min-height: 200px;">
                    <div class="sortable-column"
                         data-column="<?= $col ?>"
                         id="column-<?= $col ?>">
                        <?php if (empty($columnSections)): ?>
                        <div class="text-center text-muted py-5 empty-placeholder">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p class="small mb-0">Arrastra secciones aquí</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($columnSections as $section): ?>
                            <div class="section-card mb-2 <?= !$section['visible'] ? 'section-hidden' : '' ?>"
                                 data-id="<?= $section['id'] ?>"
                                 data-type="<?= $section['type'] ?>">
                                <div class="card">
                                    <div class="card-body p-2">
                                        <div class="d-flex align-items-center">
                                            <div class="drag-handle me-2" style="cursor: move;">
                                                <i class="fas fa-grip-vertical text-muted"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                    $iconMap = [
                                                        'company_info' => 'building',
                                                        'links' => 'link',
                                                        'contact' => 'address-book',
                                                        'social' => 'share-alt',
                                                        'newsletter' => 'envelope',
                                                        'custom' => 'code'
                                                    ];
                                                    $icon = $iconMap[$section['type']] ?? 'cube';
                                                    ?>
                                                    <i class="fas fa-<?= $icon ?> me-2 text-primary"></i>
                                                    <strong><?= htmlspecialchars($section['title']) ?></strong>
                                                </div>
                                                <small class="text-muted">
                                                    Tipo: <?= htmlspecialchars($section['type']) ?>
                                                </small>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-sm btn-outline-<?= $section['visible'] ? 'success' : 'secondary' ?> toggle-visible-btn"
                                                        data-id="<?= $section['id'] ?>"
                                                        title="<?= $section['visible'] ? 'Ocultar' : 'Mostrar' ?>">
                                                    <i class="fas fa-eye<?= $section['visible'] ? '' : '-slash' ?>"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary edit-section-btn"
                                                        data-id="<?= $section['id'] ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-info duplicate-section-btn"
                                                        data-id="<?= $section['id'] ?>">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger delete-section-btn"
                                                        data-id="<?= $section['id'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endfor; ?>
    </div>

</div>

<!-- Modal: Agregar/Editar Sección -->
<div class="modal fade" id="sectionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sectionModalTitle">
                    <i class="fas fa-plus-circle me-2"></i>Nueva Sección
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="sectionForm">
                    <input type="hidden" id="sectionId" name="id">
                    <input type="hidden" id="sectionType" name="type">

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Título de la Sección</label>
                            <input type="text" class="form-control" id="sectionTitle" name="title" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Columna</label>
                            <select class="form-select" id="sectionColumn" name="column_position" required>
                                <?php for ($i = 1; $i <= $numColumns; $i++): ?>
                                <option value="<?= $i ?>">Columna <?= $i ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="sectionVisible" name="visible" checked>
                            <label class="form-check-label" for="sectionVisible">Visible</label>
                        </div>
                    </div>

                    <hr>

                    <!-- Formularios específicos por tipo (se muestran dinámicamente) -->
                    <div id="typeSpecificFields"></div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveSectionBtn">
                    <i class="fas fa-save me-2"></i>Guardar Sección
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Configuración Global -->
<div class="modal fade" id="settingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cog me-2"></i>Configuración Global del Footer
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="settingsForm">
                    <div class="mb-3">
                        <label class="form-label">Número de Columnas</label>
                        <select class="form-select" id="numColumns" name="num_columns">
                            <option value="1" <?= ($config['num_columns'] ?? '4') == '1' ? 'selected' : '' ?>>1 Columna</option>
                            <option value="2" <?= ($config['num_columns'] ?? '4') == '2' ? 'selected' : '' ?>>2 Columnas</option>
                            <option value="3" <?= ($config['num_columns'] ?? '4') == '3' ? 'selected' : '' ?>>3 Columnas</option>
                            <option value="4" <?= ($config['num_columns'] ?? '4') == '4' ? 'selected' : '' ?>>4 Columnas</option>
                        </select>
                        <small class="form-text text-muted">Cambiar el número de columnas recargará la página</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Texto del Copyright</label>
                        <input type="text" class="form-control" id="copyrightText" name="copyright_text"
                               value="<?= htmlspecialchars($config['copyright_text'] ?? '') ?>">
                        <small class="form-text text-muted">Usa {year} para el año actual y {company_name} para el nombre de la empresa</small>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Color de Fondo</label>
                            <input type="color" class="form-control form-control-color" id="bgColor" name="bg_color"
                                   value="<?= $config['bg_color'] ?? '#f8f9fa' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Color del Texto</label>
                            <input type="color" class="form-control form-control-color" id="textColor" name="text_color"
                                   value="<?= $config['text_color'] ?? '#6c757d' ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="showBottomLinks" name="show_bottom_links"
                                   <?= ($config['show_bottom_links'] ?? 'yes') === 'yes' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="showBottomLinks">Mostrar Links Inferiores</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="showAdminLink" name="show_admin_link"
                                   <?= ($config['show_admin_link'] ?? 'yes') === 'yes' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="showAdminLink">Mostrar Link al Admin</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveSettingsBtn">
                    <i class="fas fa-save me-2"></i>Guardar Configuración
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="d-flex justify-content-center align-items-center h-100">
        <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Cargando...</span>
        </div>
    </div>
</div>

<!-- Sortable.js -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<!-- Footer Editor JS -->
<script>
const BASE_URL = '<?= Config::getBaseUrl() ?>';
const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?? '' ?>';

// Configuración de datos
const CONFIG = <?= json_encode($config) ?>;
</script>
<script src="<?= Helpers::asset('js/footer-editor.js') ?>"></script>

<style>
.section-card {
    transition: all 0.3s ease;
}

.section-hidden {
    opacity: 0.5;
}

.section-card:hover {
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
    opacity: 0.8;
}

.empty-placeholder {
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
}
</style>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
