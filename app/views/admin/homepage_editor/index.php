<?php
use App\Core\Config;
use App\Core\Helpers;
/**
 * Vista del Editor Visual de Homepage
 * Permite arrastrar y soltar secciones, editar configuración y previsualizar
 */
$pageTitle = 'Editor de Homepage';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="container-fluid py-4">
    <?php
        $actionTitle = 'Editor Visual de Homepage';
        $actionSubtitle = 'Arrastra las secciones para reordenarlas y haz click para editarlas';
        $actionButtons = [
            ['label' => 'Previsualizar', 'icon' => 'fas fa-eye', 'variant' => 'outline-primary', 'href' => Config::getBaseUrl(), 'attributes' => ['target' => '_blank']],
            ['label' => 'Guardar Cambios', 'icon' => 'fas fa-save', 'variant' => 'success', 'id' => 'saveChangesBtn'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Alertas -->
    <div id="alertContainer"></div>

    <div class="row">
        <!-- Panel de Secciones (Drag & Drop) -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-layer-group me-2"></i>
                        Secciones del Homepage
                    </h5>
                    <small class="text-muted">Arrastra para reordenar • Click para editar</small>
                </div>
                <div class="card-body p-3">
                    <div id="sortableSections" class="sections-container">
                        <?php foreach ($sections as $section): ?>
                            <?php
                            $config = json_decode($section['section_config'], true) ?? [];
                            $typeLabels = [
                                'trust_bar' => 'Barra de Confianza',
                                'hero' => 'Hero Principal',
                                'stats' => 'Estadísticas',
                                'partners' => 'Partners/Logos',
                                'featured' => 'Tours Destacados',
                                'categories' => 'Categorías',
                                'reviews' => 'Reseñas de Clientes',
                                'newsletter' => 'Newsletter',
                                'cta' => 'Call to Action',
                                'custom' => 'Sección Custom'
                            ];
                            $typeIcons = [
                                'trust_bar' => 'fas fa-shield-alt',
                                'hero' => 'fas fa-image',
                                'stats' => 'fas fa-chart-bar',
                                'partners' => 'fas fa-handshake',
                                'featured' => 'fas fa-star',
                                'categories' => 'fas fa-th-large',
                                'reviews' => 'fas fa-comments',
                                'newsletter' => 'fas fa-envelope',
                                'cta' => 'fas fa-bullhorn',
                                'custom' => 'fas fa-code'
                            ];
                            ?>
                            <div class="section-item <?= $section['is_visible'] ? '' : 'section-hidden' ?>"
                                 data-section-id="<?= $section['id'] ?>"
                                 data-section-type="<?= htmlspecialchars($section['section_type']) ?>">
                                <div class="section-drag-handle">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>
                                <div class="section-info flex-grow-1">
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="<?= $typeIcons[$section['section_type']] ?? 'fas fa-cube' ?> me-2 text-primary"></i>
                                        <strong><?= htmlspecialchars($section['section_title'] ?? $typeLabels[$section['section_type']] ?? 'Sección') ?></strong>
                                        <span class="badge bg-secondary ms-2"><?= $section['section_type'] ?></span>
                                    </div>
                                    <small class="text-muted">
                                        <?php if (!empty($config['title'])): ?>
                                            <?= htmlspecialchars(Helpers::truncate($config['title'], 60)) ?>
                                        <?php elseif (!empty($config['subtitle'])): ?>
                                            <?= htmlspecialchars(Helpers::truncate($config['subtitle'], 60)) ?>
                                        <?php else: ?>
                                            Click para editar configuración
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="section-actions">
                                    <button class="btn btn-sm btn-outline-secondary toggle-visibility-btn"
                                            data-section-id="<?= $section['id'] ?>"
                                            title="<?= $section['is_visible'] ? 'Ocultar' : 'Mostrar' ?>">
                                        <i class="fas fa-eye<?= $section['is_visible'] ? '' : '-slash' ?>"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary edit-section-btn"
                                            data-section-id="<?= $section['id'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($section['section_type'] === 'custom'): ?>
                                    <button class="btn btn-sm btn-outline-danger delete-section-btn"
                                            data-section-id="<?= $section['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-3">
                        <button class="btn btn-outline-primary" id="addCustomSectionBtn">
                            <i class="fas fa-plus me-1"></i> Agregar Sección Custom
                        </button>
                    </div>
                </div>
            </div>

            <!-- Info sobre cambios pendientes -->
            <div class="alert alert-info" id="pendingChangesAlert" style="display: none;">
                <i class="fas fa-info-circle me-2"></i>
                Tienes cambios pendientes. No olvides guardar.
            </div>
        </div>

        <!-- Panel Lateral: Editor de Configuración -->
        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2"></i>
                        Editor de Configuración
                    </h5>
                </div>
                <div class="card-body" id="configEditorPanel">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-mouse-pointer fa-3x mb-3"></i>
                        <p>Selecciona una sección para editarla</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para crear sección custom -->
<div class="modal fade" id="createSectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>
                    Nueva Sección Custom
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createSectionForm">
                    <div class="mb-3">
                        <label class="form-label">Título de la Sección</label>
                        <input type="text" class="form-control" name="title" required placeholder="Ej: Testimonios">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipo de Contenido</label>
                        <select class="form-select" name="content_type" required>
                            <option value="html">HTML/Texto libre</option>
                            <option value="cta">Call to Action</option>
                            <option value="testimonials">Testimonios</option>
                            <option value="gallery">Galería de Imágenes</option>
                            <option value="video">Video</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="confirmCreateSectionBtn">
                    <i class="fas fa-plus me-1"></i> Crear Sección
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos del Editor */
.sections-container {
    min-height: 400px;
}

.section-item {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 15px;
    cursor: move;
    transition: all 0.2s ease;
}

.section-item:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.1);
}

.section-item.section-hidden {
    opacity: 0.5;
    background: #f8f9fa;
}

.section-item.sortable-chosen {
    opacity: 0.5;
    transform: scale(0.98);
}

.section-item.sortable-ghost {
    opacity: 0.3;
    border-style: dashed;
}

.section-drag-handle {
    color: #6c757d;
    cursor: grab;
    font-size: 1.2rem;
}

.section-drag-handle:active {
    cursor: grabbing;
}

.section-info {
    flex-grow: 1;
}

.section-actions {
    display: flex;
    gap: 8px;
}

.section-actions .btn {
    padding: 0.25rem 0.5rem;
}

/* Config Editor Panel */
#configEditorPanel .form-label {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

#configEditorPanel .form-control,
#configEditorPanel .form-select {
    font-size: 0.9rem;
}

/* Loading states */
.btn.loading {
    position: relative;
    pointer-events: none;
    opacity: 0.6;
}

.btn.loading::after {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid #fff;
    border-radius: 50%;
    border-top-color: transparent;
    animation: spinner 0.6s linear infinite;
}

@keyframes spinner {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 991.98px) {
    .sticky-top {
        position: relative !important;
        top: 0 !important;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
// Helper to escape HTML (defined first)
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

document.addEventListener('DOMContentLoaded', function() {
    const BASE_URL = '<?= Config::getBaseUrl() ?>';
    let hasChanges = false;
    let currentEditingSection = null;

    // Initialize Sortable.js
    const sortableContainer = document.getElementById('sortableSections');
    const sortable = new Sortable(sortableContainer, {
        handle: '.section-drag-handle',
        animation: 150,
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function(evt) {
            markAsChanged();
        }
    });

    // Toggle visibility
    document.querySelectorAll('.toggle-visibility-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const sectionId = this.dataset.sectionId;
            const sectionItem = this.closest('.section-item');
            const icon = this.querySelector('i');
            const button = this;

            console.log('Toggle visibility for section:', sectionId);
            button.classList.add('loading');

            fetch(BASE_URL + '?route=homepage-editor/api-toggle-visibility', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id: sectionId })
            })
            .then(res => {
                console.log('Response status:', res.status);
                console.log('Response headers:', res.headers.get('content-type'));
                return res.text();
            })
            .then(text => {
                console.log('Response text:', text);
                try {
                    const data = JSON.parse(text);
                    button.classList.remove('loading');

                    console.log('Parsed data:', data);

                    if (data.success) {
                        sectionItem.classList.toggle('section-hidden', !data.is_visible);
                        icon.className = data.is_visible ? 'fas fa-eye' : 'fas fa-eye-slash';
                        button.title = data.is_visible ? 'Ocultar' : 'Mostrar';
                        showAlert('success', data.message);
                    } else {
                        showAlert('danger', data.message || 'Error desconocido');
                        console.error('Server error:', data);
                    }
                } catch (parseError) {
                    button.classList.remove('loading');
                    console.error('JSON parse error:', parseError);
                    console.error('Received text:', text);
                    showAlert('danger', 'Error: El servidor no devolvió JSON válido');
                }
            })
            .catch(err => {
                button.classList.remove('loading');
                console.error('Fetch error:', err);
                showAlert('danger', 'Error al cambiar visibilidad: ' + err.message);
            });
        });
    });

    // Edit section
    document.querySelectorAll('.edit-section-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const sectionId = this.dataset.sectionId;
            const sectionItem = this.closest('.section-item');
            const sectionType = sectionItem.dataset.sectionType;

            loadSectionEditor(sectionId, sectionType);
        });
    });

    // Delete section
    document.querySelectorAll('.delete-section-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const sectionId = this.dataset.sectionId;

            if (!confirm('¿Estás seguro de eliminar esta sección?')) return;

            this.classList.add('loading');

            fetch(BASE_URL + '?route=homepage-editor/api-delete-section', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ id: sectionId })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.closest('.section-item').remove();
                    showAlert('success', data.message);
                    markAsChanged();
                } else {
                    showAlert('danger', data.message);
                    this.classList.remove('loading');
                }
            })
            .catch(err => {
                showAlert('danger', 'Error al eliminar sección');
                this.classList.remove('loading');
            });
        });
    });

    // Save changes (order)
    document.getElementById('saveChangesBtn').addEventListener('click', function() {
        const sections = sortableContainer.querySelectorAll('.section-item');
        const order = Array.from(sections).map(s => s.dataset.sectionId);

        this.classList.add('loading');

        fetch(BASE_URL + '?route=homepage-editor/api-save-order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ order: JSON.stringify(order) })
        })
        .then(res => res.json())
        .then(data => {
            this.classList.remove('loading');
            if (data.success) {
                showAlert('success', data.message);
                hasChanges = false;
                document.getElementById('pendingChangesAlert').style.display = 'none';
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(err => {
            this.classList.remove('loading');
            showAlert('danger', 'Error al guardar cambios');
        });
    });

    // Add custom section
    document.getElementById('addCustomSectionBtn').addEventListener('click', function() {
        const modal = new bootstrap.Modal(document.getElementById('createSectionModal'));
        modal.show();
    });

    document.getElementById('confirmCreateSectionBtn').addEventListener('click', function() {
        const form = document.getElementById('createSectionForm');
        const formData = new FormData(form);

        this.classList.add('loading');

        fetch(BASE_URL + '?route=homepage-editor/api-create-section', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                section_type: 'custom',
                title: formData.get('title'),
                config: JSON.stringify({ content_type: formData.get('content_type') })
            })
        })
        .then(res => res.json())
        .then(data => {
            this.classList.remove('loading');
            if (data.success) {
                showAlert('success', data.message);
                location.reload(); // Recargar para mostrar nueva sección
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(err => {
            this.classList.remove('loading');
            showAlert('danger', 'Error al crear sección');
        });
    });

    // Helper: Load section editor
    function loadSectionEditor(sectionId, sectionType) {
        currentEditingSection = sectionId;
        const panel = document.getElementById('configEditorPanel');

        // Mostrar loading
        panel.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"></div></div>';

        // Fetch section data
        fetch(BASE_URL + '?route=homepage-editor/api-get-sections')
            .then(res => res.json())
            .then(response => {
                if (!response.success) {
                    panel.innerHTML = '<div class="alert alert-danger">Error al cargar sección</div>';
                    return;
                }

                const section = response.sections.find(s => s.id == sectionId);
                if (!section) {
                    panel.innerHTML = '<div class="alert alert-danger">Sección no encontrada</div>';
                    return;
                }

                const config = JSON.parse(section.section_config || '{}');

                // Renderizar editor según el tipo
                let editorHTML = '';

                switch(sectionType) {
                    case 'trust_bar':
                        editorHTML = getTrustBarEditor(section, config);
                        break;
                    case 'hero':
                        editorHTML = getHeroEditor(section, config);
                        break;
                    case 'stats':
                        editorHTML = getStatsEditor(section, config);
                        break;
                    case 'partners':
                        editorHTML = getPartnersEditor(section, config);
                        break;
                    case 'featured':
                        editorHTML = getFeaturedEditor(section, config);
                        break;
                    case 'categories':
                        editorHTML = getCategoriesEditor(section, config);
                        break;
                    case 'reviews':
                        editorHTML = getReviewsEditor(section, config);
                        break;
                    case 'newsletter':
                        editorHTML = getNewsletterEditor(section, config);
                        break;
                    case 'cta':
                        editorHTML = getCTAEditor(section, config);
                        break;
                    case 'custom':
                        editorHTML = getCustomEditor(section, config);
                        break;
                    default:
                        editorHTML = `<div class="alert alert-warning">Tipo de sección no soportado: ${sectionType}</div>`;
                }

                panel.innerHTML = editorHTML;

                // Attach save handler
                const saveBtn = panel.querySelector('#saveSectionBtn');
                if (saveBtn) {
                    saveBtn.addEventListener('click', () => saveSectionConfig(sectionId));
                }
            })
            .catch(err => {
                panel.innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
                console.error(err);
            });
    }

    // Editor for Hero section
    function getHeroEditor(section, config) {
        var html = '<h6 class="mb-3"><i class="fas fa-image me-2"></i>Editar Hero Principal</h6>';
        html += '<form id="sectionConfigForm">';

        // Título y Subtítulo
        html += '<div class="mb-3">';
        html += '<label class="form-label">Título Principal</label>';
        html += '<input type="text" class="form-control" name="title" value="' + escapeHtml(config.title || 'Descubre el Mundo Maya') + '" placeholder="Ej: Descubre el Mundo Maya">';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Subtítulo</label>';
        html += '<textarea class="form-control" name="subtitle" rows="2" placeholder="Texto descriptivo...">' + escapeHtml(config.subtitle || '') + '</textarea>';
        html += '</div>';

        // Botón Principal
        html += '<hr class="my-3">';
        html += '<h6 class="mb-3"><i class="fas fa-mouse-pointer me-2"></i>Botón Principal</h6>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Texto del Botón</label>';
        html += '<input type="text" class="form-control" name="cta_text" value="' + escapeHtml(config.cta_text || 'Explorar Destinos') + '" placeholder="Explorar Destinos">';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Enlace (ruta sin ?route=)</label>';
        html += '<input type="text" class="form-control" name="cta_link" value="' + escapeHtml(config.cta_link || 'tours') + '" placeholder="tours">';
        html += '<small class="text-muted">Ejemplo: tours, contact, booking</small>';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Icono (FontAwesome sin fa-)</label>';
        html += '<input type="text" class="form-control" name="cta_icon" value="' + escapeHtml(config.cta_icon || 'search') + '" placeholder="search">';
        html += '<small class="text-muted">Ejemplo: search, plane, map-marked-alt</small>';
        html += '</div>';

        // Botón Secundario
        html += '<hr class="my-3">';
        html += '<h6 class="mb-3"><i class="fas fa-mouse-pointer me-2"></i>Botón Secundario</h6>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Texto del Botón</label>';
        html += '<input type="text" class="form-control" name="secondary_cta_text" value="' + escapeHtml(config.secondary_cta_text || 'Contactar') + '" placeholder="Contactar">';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Enlace (ruta sin ?route=)</label>';
        html += '<input type="text" class="form-control" name="secondary_cta_link" value="' + escapeHtml(config.secondary_cta_link || 'contact') + '" placeholder="contact">';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Icono (FontAwesome sin fa-)</label>';
        html += '<input type="text" class="form-control" name="secondary_cta_icon" value="' + escapeHtml(config.secondary_cta_icon || 'envelope') + '" placeholder="envelope">';
        html += '</div>';

        // Guardar
        html += '<button type="button" class="btn btn-primary w-100 mt-3" id="saveSectionBtn">';
        html += '<i class="fas fa-save me-2"></i>Guardar Cambios';
        html += '</button>';
        html += '</form>';
        return html;
    }

    // Editor for Stats section
    function getStatsEditor(section, config) {
        // Si no hay stats personalizados, usar valores vacíos para indicar que usa BD
        var hasCustomStats = config.stats && config.stats.length > 0;
        var stats = hasCustomStats ? config.stats : [
            { number: '', label: '', icon: 'map-marked-alt' },
            { number: '', label: '', icon: 'th-large' },
            { number: '', label: '', icon: 'users' }
        ];

        var html = '<h6 class="mb-3"><i class="fas fa-chart-bar me-2"></i>Editar Estadísticas</h6>';
        html += '<form id="sectionConfigForm">';

        // Mensaje informativo
        html += '<div class="alert alert-info alert-sm mb-3">';
        html += '<i class="fas fa-info-circle me-2"></i>';
        if (hasCustomStats) {
            html += '<strong>Modo:</strong> Estadísticas personalizadas. Si quieres usar datos en vivo de la BD, deja los números vacíos.';
        } else {
            html += '<strong>Modo actual:</strong> Datos en vivo de la base de datos. Para usar números personalizados, ingresa valores abajo.';
        }
        html += '</div>';

        html += '<div class="mb-3">';
        html += '<label class="form-label">Título de Sección (opcional)</label>';
        html += '<input type="text" class="form-control" name="section_title" value="' + escapeHtml(config.section_title || '') + '" placeholder="Ej: Nuestros Números">';
        html += '</div>';

        for (var i = 0; i < stats.length; i++) {
            var stat = stats[i];
            html += '<div class="card mb-3"><div class="card-body">';
            html += '<h6>Estadística ' + (i + 1) + '</h6>';
            html += '<div class="mb-2">';
            html += '<label class="form-label small">Número</label>';
            html += '<input type="text" class="form-control form-control-sm" name="stat_' + i + '_number" value="' + escapeHtml(stat.number) + '" placeholder="' + (hasCustomStats ? '500+' : 'Vacío = usar BD') + '">';
            html += '<small class="text-muted">Ejemplo: 500+, 15, 50+. Vacío = datos de BD</small>';
            html += '</div>';
            html += '<div class="mb-2">';
            html += '<label class="form-label small">Etiqueta</label>';
            html += '<input type="text" class="form-control form-control-sm" name="stat_' + i + '_label" value="' + escapeHtml(stat.label) + '" placeholder="' + (hasCustomStats ? 'Clientes Felices' : 'Vacío = usar BD') + '">';
            html += '</div>';
            html += '<div class="mb-2">';
            html += '<label class="form-label small">Icono (FontAwesome)</label>';
            html += '<input type="text" class="form-control form-control-sm" name="stat_' + i + '_icon" value="' + escapeHtml(stat.icon) + '" placeholder="users">';
            html += '<small class="text-muted">Sin \'fa-\', ej: users, calendar, chart-line</small>';
            html += '</div>';
            html += '</div></div>';
        }

        html += '<button type="button" class="btn btn-primary w-100" id="saveSectionBtn">';
        html += '<i class="fas fa-save me-2"></i>Guardar Cambios';
        html += '</button>';
        html += '</form>';
        return html;
    }

    // Editor for Newsletter section
    function getNewsletterEditor(section, config) {
        var html = '<h6 class="mb-3"><i class="fas fa-envelope me-2"></i>Editar Newsletter</h6>';
        html += '<form id="sectionConfigForm">';
        html += '<div class="mb-3">';
        html += '<label class="form-label">T&iacute;tulo</label>';
        html += '<input type="text" class="form-control" name="title" value="' + escapeHtml(config.title || 'Suscribete') + '" placeholder="Suscribete">';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Descripci&oacute;n</label>';
        html += '<textarea class="form-control" name="description" rows="3" placeholder="Recibe ofertas exclusivas...">' + escapeHtml(config.description || '') + '</textarea>';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Placeholder del Email</label>';
        html += '<input type="text" class="form-control" name="email_placeholder" value="' + escapeHtml(config.email_placeholder || 'Tu correo electronico') + '" placeholder="Tu correo electronico">';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Texto del Bot&oacute;n</label>';
        html += '<input type="text" class="form-control" name="button_text" value="' + escapeHtml(config.button_text || 'Suscribirse') + '" placeholder="Suscribirse">';
        html += '</div>';
        html += '<button type="button" class="btn btn-primary w-100" id="saveSectionBtn">';
        html += '<i class="fas fa-save me-2"></i>Guardar Cambios';
        html += '</button>';
        html += '</form>';
        return html;
    }

    // Editor for Trust Bar
    function getTrustBarEditor(section, config) {
        return getGenericEditor(section, config, 'Barra de Confianza', 'shield-alt', 'Esta sección muestra los beneficios de reserva (cancelación gratis, confirmación inmediata, etc.). El contenido está predefinido en el código.');
    }

    // Editor for Reviews
    function getReviewsEditor(section, config) {
        var html = '<h6 class="mb-3"><i class="fas fa-comments me-2"></i>Editar Reseñas</h6>';
        html += '<form id="sectionConfigForm">';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Título</label>';
        html += '<input type="text" class="form-control" name="title" value="' + escapeHtml(config.title || 'Lo que Dicen Nuestros Clientes') + '" placeholder="Lo que Dicen Nuestros Clientes">';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Subtítulo</label>';
        html += '<input type="text" class="form-control" name="subtitle" value="' + escapeHtml(config.subtitle || 'Experiencias reales de viajeros satisfechos') + '" placeholder="Experiencias reales...">';
        html += '</div>';
        html += '<div class="alert alert-info">';
        html += '<i class="fas fa-info-circle me-2"></i>';
        html += '<strong>Nota:</strong> Las reseñas se gestionan desde el módulo de Reseñas del admin. Aquí solo puedes editar el título y subtítulo.';
        html += '</div>';
        html += '<button type="button" class="btn btn-primary w-100" id="saveSectionBtn">';
        html += '<i class="fas fa-save me-2"></i>Guardar Cambios';
        html += '</button>';
        html += '</form>';
        return html;
    }

    // Editor for CTA
    function getCTAEditor(section, config) {
        var html = '<h6 class="mb-3"><i class="fas fa-bullhorn me-2"></i>Editar Call to Action</h6>';
        html += '<form id="sectionConfigForm">';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Título</label>';
        html += '<input type="text" class="form-control" name="title" value="' + escapeHtml(config.title || '¿Listo para tu Próxima Aventura?') + '" placeholder="¿Listo para tu Próxima Aventura?">';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Subtítulo</label>';
        html += '<textarea class="form-control" name="subtitle" rows="2" placeholder="Texto descriptivo...">' + escapeHtml(config.subtitle || 'Contáctanos y te ayudaremos a planificar el viaje perfecto') + '</textarea>';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Texto del Botón Principal</label>';
        html += '<input type="text" class="form-control" name="button_text" value="' + escapeHtml(config.button_text || 'Contáctanos') + '" placeholder="Contáctanos">';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Enlace del Botón Principal</label>';
        html += '<input type="text" class="form-control" name="button_link" value="' + escapeHtml(config.button_link || 'contact') + '" placeholder="contact">';
        html += '<small class="text-muted">Ruta sin "?route=", ej: contact, tours</small>';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Texto del Botón Secundario</label>';
        html += '<input type="text" class="form-control" name="secondary_button_text" value="' + escapeHtml(config.secondary_button_text || 'Ver Destinos') + '" placeholder="Ver Destinos">';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Enlace del Botón Secundario</label>';
        html += '<input type="text" class="form-control" name="secondary_button_link" value="' + escapeHtml(config.secondary_button_link || 'tours') + '" placeholder="tours">';
        html += '</div>';
        html += '<button type="button" class="btn btn-primary w-100" id="saveSectionBtn">';
        html += '<i class="fas fa-save me-2"></i>Guardar Cambios';
        html += '</button>';
        html += '</form>';
        return html;
    }

    // Generic editors for other sections
    function getPartnersEditor(section, config) {
        const partners = config.partners || [];

        let html = '<h6 class="mb-3"><i class="fas fa-handshake me-2"></i>Editar Partners/Logos</h6>';
        html += '<form id="sectionConfigForm">';

        // Título de la sección
        html += '<div class="mb-3">';
        html += '<label class="form-label">Título de la Sección</label>';
        html += '<input type="text" class="form-control" name="title" value="' + escapeHtml(config.title || 'Aliados de confianza') + '">';
        html += '</div>';

        // Lista de partners
        html += '<div class="mb-3">';
        html += '<label class="form-label d-flex justify-content-between align-items-center">';
        html += '<span>Partners</span>';
        html += '<button type="button" class="btn btn-sm btn-success" onclick="addPartner()"><i class="fas fa-plus me-1"></i>Agregar</button>';
        html += '</label>';
        html += '<div id="partnersList" class="mt-2">';

        partners.forEach((partner, index) => {
            html += getPartnerRow(index, partner);
        });

        if (partners.length === 0) {
            html += '<p class="text-muted text-center py-3">No hay partners. Haz clic en "Agregar" para añadir uno.</p>';
        }

        html += '</div>';
        html += '</div>';

        html += '<button type="button" class="btn btn-primary w-100" id="saveSectionBtn">';
        html += '<i class="fas fa-save me-2"></i>Guardar Cambios';
        html += '</button>';
        html += '</form>';

        return html;
    }

    function getPartnerRow(index, partner = {}) {
        let html = '<div class="partner-row mb-3 p-3 border rounded" data-index="' + index + '">';
        html += '<div class="d-flex justify-content-between align-items-center mb-2">';
        html += '<strong>Partner #' + (index + 1) + '</strong>';
        html += '<button type="button" class="btn btn-sm btn-danger" onclick="removePartner(' + index + ')" aria-label="Eliminar partner" title="Eliminar partner"><i class="fas fa-trash"></i></button>';
        html += '</div>';

        html += '<div class="row g-2">';
        html += '<div class="col-md-6">';
        html += '<label class="form-label">Nombre</label>';
        html += '<input type="text" class="form-control" name="partner_' + index + '_name" value="' + escapeHtml(partner.name || '') + '" required>';
        html += '</div>';
        html += '<div class="col-md-6">';
        html += '<label class="form-label">Link (URL)</label>';
        html += '<input type="url" class="form-control" name="partner_' + index + '_link" value="' + escapeHtml(partner.link || '#') + '">';
        html += '</div>';
        html += '<div class="col-12">';
        html += '<label class="form-label">Logo</label>';
        html += '<div class="input-group">';
        html += '<input type="text" class="form-control" name="partner_' + index + '_logo" value="' + escapeHtml(partner.logo || '') + '" id="partner_' + index + '_logo_input" readonly>';
        html += '<button type="button" class="btn btn-outline-secondary" onclick="uploadPartnerLogo(' + index + ')"><i class="fas fa-upload me-1"></i>Subir</button>';
        html += '</div>';

        if (partner.logo) {
            html += '<div class="mt-2"><img src="' + escapeHtml(partner.logo) + '" alt="Preview" class="img-thumbnail" style="max-height: 60px;"></div>';
        }

        html += '<input type="file" id="partner_' + index + '_logo_file" accept="image/*" style="display:none;" onchange="handlePartnerLogoUpload(' + index + ', this)">';
        html += '</div>';
        html += '</div>';
        html += '</div>';

        return html;
    }

    window.addPartner = function() {
        const partnersList = document.getElementById('partnersList');
        const emptyMsg = partnersList.querySelector('.text-muted');
        if (emptyMsg) emptyMsg.remove();

        const currentPartners = partnersList.querySelectorAll('.partner-row').length;
        partnersList.insertAdjacentHTML('beforeend', getPartnerRow(currentPartners, {}));
    };

    window.removePartner = function(index) {
        const row = document.querySelector(`.partner-row[data-index="${index}"]`);
        if (row && confirm('¿Eliminar este partner?')) {
            row.remove();

            // Renumerar los partners restantes
            document.querySelectorAll('.partner-row').forEach((row, newIndex) => {
                row.dataset.index = newIndex;
                row.querySelector('strong').textContent = 'Partner #' + (newIndex + 1);

                // Actualizar nombres de los inputs
                row.querySelectorAll('input, button').forEach(input => {
                    ['name', 'id', 'onclick'].forEach(attr => {
                        if (input.hasAttribute(attr)) {
                            const value = input.getAttribute(attr);
                            input.setAttribute(attr, value.replace(/partner_\d+_/, 'partner_' + newIndex + '_'));
                        }
                    });
                });
            });
        }
    };

    window.uploadPartnerLogo = function(index) {
        document.getElementById('partner_' + index + '_logo_file').click();
    };

    window.handlePartnerLogoUpload = async function(index, input) {
        if (!input.files || !input.files[0]) return;

        const file = input.files[0];
        const formData = new FormData();
        formData.append('logo', file);

        // Buscar el input group que está antes del file input
        const inputGroup = input.previousElementSibling;
        if (!inputGroup) {
            console.error('No se encontró el input-group');
            showAlert('danger', 'Error en la estructura del formulario');
            return;
        }

        // Buscar el botón de subir dentro del input-group
        const btn = inputGroup.querySelector('.btn-outline-secondary');
        if (!btn) {
            console.error('No se encontró el botón de subir');
            showAlert('danger', 'Error en la estructura del formulario');
            return;
        }

        // Buscar el text input dentro del input-group
        const textInput = inputGroup.querySelector('input[type="text"]');
        if (!textInput) {
            console.error('No se encontró el text input');
            showAlert('danger', 'Error en la estructura del formulario');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Subiendo...';

        try {
            const response = await fetch(BASE_URL + '?route=homepage-editor/api-upload-partner-logo', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Actualizar el valor del input de texto
                textInput.value = data.url;

                // Buscar el contenedor de la imagen (puede estar después del file input o del input-group)
                let previewContainer = input.nextElementSibling;

                // Si el preview ya existe, actualizarlo
                if (previewContainer && previewContainer.querySelector('img')) {
                    previewContainer.innerHTML = '<img src="' + escapeHtml(data.url) + '" alt="Preview" class="img-thumbnail" style="max-height: 60px;">';
                } else {
                    // Si no existe, crearlo después del file input
                    input.insertAdjacentHTML('afterend', '<div class="mt-2"><img src="' + escapeHtml(data.url) + '" alt="Preview" class="img-thumbnail" style="max-height: 60px;"></div>');
                }

                showAlert('success', 'Logo subido correctamente');
            } else {
                showAlert('danger', data.message || 'Error al subir el logo');
            }
        } catch (error) {
            showAlert('danger', 'Error de conexión al subir el logo: ' + error.message);
            console.error('Upload error:', error);
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-upload me-1"></i>Subir';
    };

    function getFeaturedEditor(section, config) {
        return getGenericEditor(section, config, 'Tours Destacados', 'star');
    }

    function getCategoriesEditor(section, config) {
        return getGenericEditor(section, config, 'Categorías', 'th-large');
    }

    function getCustomEditor(section, config) {
        return getGenericEditor(section, config, 'Sección Personalizada', 'code');
    }

    function getGenericEditor(section, config, sectionName, icon, customNote) {
        var html = '<h6 class="mb-3"><i class="fas fa-' + icon + ' me-2"></i>Editar ' + sectionName + '</h6>';
        html += '<form id="sectionConfigForm">';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Título de la Sección</label>';
        html += '<input type="text" class="form-control" name="title" value="' + escapeHtml(section.section_title || '') + '" placeholder="' + sectionName + '">';
        html += '</div>';
        html += '<div class="mb-3">';
        html += '<label class="form-label">Subtítulo</label>';
        html += '<textarea class="form-control" name="subtitle" rows="2" placeholder="Texto descriptivo...">' + escapeHtml(config.subtitle || '') + '</textarea>';
        html += '</div>';
        html += '<div class="alert alert-info">';
        html += '<i class="fas fa-info-circle me-2"></i>';
        html += '<strong>Nota:</strong> ' + (customNote || 'Esta sección se genera dinámicamente desde la base de datos. Puedes cambiar el título y subtítulo aquí, pero el contenido se gestiona desde otros módulos del admin.');
        html += '</div>';
        html += '<button type="button" class="btn btn-primary w-100" id="saveSectionBtn">';
        html += '<i class="fas fa-save me-2"></i>Guardar Cambios';
        html += '</button>';
        html += '</form>';
        return html;
    }

    // Save section configuration
    function saveSectionConfig(sectionId) {
        const form = document.getElementById('sectionConfigForm');
        const formData = new FormData(form);
        const config = {};

        // Convert FormData to object
        for (let [key, value] of formData.entries()) {
            // Handle stats array
            if (key.startsWith('stat_')) {
                const parts = key.split('_');
                const index = parts[1];
                const field = parts[2];

                if (!config.stats) config.stats = [];
                if (!config.stats[index]) config.stats[index] = {};
                config.stats[index][field] = value;
            }
            // Handle partners array
            else if (key.startsWith('partner_')) {
                const parts = key.split('_');
                const index = parts[1];
                const field = parts[2];

                if (!config.partners) config.partners = [];
                if (!config.partners[index]) config.partners[index] = {};
                config.partners[index][field] = value;
            } else {
                config[key] = value;
            }
        }

        // Filtrar stats vacíos (si todos los campos están vacíos, no guardar el array)
        if (config.stats && config.stats.length > 0) {
            config.stats = config.stats.filter(stat => {
                return stat.number || stat.label;
            });
            // Si después de filtrar no hay stats, eliminar el array completo
            if (config.stats.length === 0) {
                delete config.stats;
            }
        }

        // Filtrar partners vacíos
        if (config.partners && config.partners.length > 0) {
            config.partners = config.partners.filter(partner => {
                return partner.name && partner.logo;
            });
            // Si después de filtrar no hay partners, eliminar el array completo
            if (config.partners.length === 0) {
                delete config.partners;
            }
        }

        const saveBtn = document.getElementById('saveSectionBtn');
        saveBtn.classList.add('loading');
        saveBtn.disabled = true;

        fetch(BASE_URL + '?route=homepage-editor/api-update-section', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                id: sectionId,
                config: JSON.stringify(config)
            })
        })
        .then(res => res.json())
        .then(data => {
            saveBtn.classList.remove('loading');
            saveBtn.disabled = false;

            if (data.success) {
                showAlert('success', data.message);
                markAsChanged();
                // Reload section list to show updated preview
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(err => {
            saveBtn.classList.remove('loading');
            saveBtn.disabled = false;
            showAlert('danger', 'Error al guardar');
            console.error(err);
        });
    }

    // Helper: Mark as changed
    function markAsChanged() {
        hasChanges = true;
        document.getElementById('pendingChangesAlert').style.display = 'block';
    }

    // Helper: Show alert
    function showAlert(type, message) {
        const container = document.getElementById('alertContainer');
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        container.appendChild(alert);

        setTimeout(() => alert.remove(), 5000);
    }

    // Warn before leaving if changes pending
    window.addEventListener('beforeunload', function(e) {
        if (hasChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
