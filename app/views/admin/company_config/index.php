<?php
use App\Core\Config;
use App\Core\Helpers;
/**
 * Vista: Configuración de Empresa
 * Centraliza toda la información de la empresa
 */

if (!function_exists('renderCompanyConfigInput')) {
    function renderCompanyConfigInput($config)
    {
        $required = !empty($config['requerido']);
        $value = htmlspecialchars($config['config_value'] ?? '');
        $placeholder = htmlspecialchars($config['placeholder'] ?? '');
        $isImageField = strpos($config['config_key'], '_image') !== false || strpos($config['config_label'], 'Imagen') !== false;

        ob_start();
        ?>
        <div class="mb-3">
            <label class="form-label">
                <?= htmlspecialchars($config['config_label']) ?>
                <?php if ($required): ?><span class="text-danger">*</span><?php endif; ?>
            </label>

            <?php if ($isImageField): ?>
                <!-- Campo de imagen con preview y botón de subir -->
                <div class="image-upload-field">
                    <div class="input-group mb-2">
                        <input type="text"
                               class="form-control"
                               name="configs[<?= $config['config_key'] ?>]"
                               id="input_<?= $config['config_key'] ?>"
                               value="<?= $value ?>"
                               <?= $required ? 'required' : '' ?>
                               placeholder="<?= $placeholder ?: 'images/ejemplo.jpg' ?>">
                        <button type="button"
                                class="btn btn-outline-primary"
                                onclick="openImageUploadModal('<?= $config['config_key'] ?>')">
                            <i class="fas fa-upload me-1"></i>
                            Subir
                        </button>
                    </div>

                    <?php if (!empty($value)): ?>
                        <div class="image-preview mb-2" id="preview_<?= $config['config_key'] ?>">
                            <img src="<?= Helpers::asset($value) ?>"
                                 alt="Preview"
                                 class="img-thumbnail"
                                 style="max-height: 150px;"
                                 onerror="this.parentElement.style.display='none'">
                        </div>
                    <?php else: ?>
                        <div class="image-preview mb-2" id="preview_<?= $config['config_key'] ?>" style="display: none;">
                            <img src=""
                                 alt="Preview"
                                 class="img-thumbnail"
                                 style="max-height: 150px;">
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif (($config['config_type'] ?? 'text') === 'textarea'): ?>
                <textarea class="form-control"
                          name="configs[<?= $config['config_key'] ?>]"
                          rows="3"
                          <?= $required ? 'required' : '' ?>
                          placeholder="<?= $placeholder ?>"><?= $value ?></textarea>
            <?php else: ?>
                <input type="<?= htmlspecialchars($config['config_type'] ?? 'text') ?>"
                       class="form-control"
                       name="configs[<?= $config['config_key'] ?>]"
                       value="<?= $value ?>"
                       <?= $required ? 'required' : '' ?>
                       placeholder="<?= $placeholder ?>">
            <?php endif; ?>

            <?php if (!empty($config['config_description'])): ?>
                <small class="text-muted"><?= htmlspecialchars($config['config_description']) ?></small>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

$pageTitle = 'Configuración de Empresa';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="admin-company-config">
    <?php
        $actionTitle = 'Configuración de Empresa';
        $actionSubtitle = 'Gestiona la información de tu empresa que se muestra en el sitio web';
        $actionButtons = [
            [
                'label' => 'Guardar Cambios',
                'icon' => 'fas fa-save',
                'variant' => 'primary',
                'attributes' => [
                    'type' => 'submit',
                    'form' => 'formCompanyConfig'
                ]
            ],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Tabs de navegación -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabGeneral" type="button">
                <i class="fas fa-info-circle me-2"></i>
                General
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabContacto" type="button">
                <i class="fas fa-phone me-2"></i>
                Contacto
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRedes" type="button">
                <i class="fas fa-share-alt me-2"></i>
                Redes Sociales
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabBranding" type="button">
                <i class="fas fa-palette me-2"></i>
                Branding
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabHomepage" type="button">
                <i class="fas fa-home me-2"></i>
                Homepage
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabHorarios" type="button">
                <i class="fas fa-clock me-2"></i>
                Horarios
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSeo" type="button">
                <i class="fas fa-search me-2"></i>
                SEO
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPagos" type="button">
                <i class="fas fa-dollar-sign me-2"></i>
                Pagos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabAboutPage" type="button">
                <i class="fas fa-users me-2"></i>
                Nosotros
            </button>
        </li>
    </ul>

    <form id="formCompanyConfig" method="POST" action="?route=admin/company-config/update" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- General -->
            <div class="tab-pane fade show active" id="tabGeneral" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Información General</h5>

                        <?php foreach ($groupedConfigs['general'] ?? [] as $config): ?>
                            <div class="mb-3">
                                <label class="form-label">
                                    <?= htmlspecialchars($config['config_label']) ?>
                                    <?php if ($config['requerido'] ?? false): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>

                                <?php if ($config['config_type'] === 'textarea'): ?>
                                    <textarea class="form-control"
                                              name="configs[<?= $config['config_key'] ?>]"
                                              rows="3"
                                              <?= ($config['requerido'] ?? false) ? 'required' : '' ?>
                                              placeholder="<?= htmlspecialchars($config['placeholder'] ?? '') ?>"><?= htmlspecialchars($config['config_value'] ?? '') ?></textarea>
                                <?php else: ?>
                                    <input type="<?= $config['config_type'] ?>"
                                           class="form-control"
                                           name="configs[<?= $config['config_key'] ?>]"
                                           value="<?= htmlspecialchars($config['config_value'] ?? '') ?>"
                                           <?= ($config['requerido'] ?? false) ? 'required' : '' ?>
                                           placeholder="<?= htmlspecialchars($config['placeholder'] ?? '') ?>">
                                <?php endif; ?>

                                <?php if (!empty($config['config_description'])): ?>
                                    <small class="text-muted"><?= htmlspecialchars($config['config_description']) ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Contacto -->
            <div class="tab-pane fade" id="tabContacto" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Información de Contacto</h5>

                        <?php foreach ($groupedConfigs['contacto'] ?? [] as $config): ?>
                            <div class="mb-3">
                                <label class="form-label">
                                    <?= htmlspecialchars($config['config_label']) ?>
                                    <?php if ($config['requerido'] ?? false): ?>
                                        <span class="text-danger">*</span>
                                    <?php endif; ?>
                                </label>

                                <?php if ($config['config_type'] === 'email'): ?>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email"
                                               class="form-control"
                                               name="configs[<?= $config['config_key'] ?>]"
                                               value="<?= htmlspecialchars($config['config_value'] ?? '') ?>"
                                               <?= ($config['requerido'] ?? false) ? 'required' : '' ?>>
                                    </div>
                                <?php elseif ($config['config_type'] === 'telefono'): ?>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="text"
                                               class="form-control"
                                               name="configs[<?= $config['config_key'] ?>]"
                                               value="<?= htmlspecialchars($config['config_value'] ?? '') ?>"
                                               <?= ($config['requerido'] ?? false) ? 'required' : '' ?>>
                                    </div>
                                <?php else: ?>
                                    <input type="text"
                                           class="form-control"
                                           name="configs[<?= $config['config_key'] ?>]"
                                           value="<?= htmlspecialchars($config['config_value'] ?? '') ?>"
                                           <?= ($config['requerido'] ?? false) ? 'required' : '' ?>>
                                <?php endif; ?>

                                <?php if (!empty($config['config_description'])): ?>
                                    <small class="text-muted"><?= htmlspecialchars($config['config_description']) ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Redes Sociales -->
            <div class="tab-pane fade" id="tabRedes" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Redes Sociales</h5>

                        <?php foreach ($groupedConfigs['redes_sociales'] ?? [] as $config): ?>
                            <div class="mb-3">
                                <label class="form-label">
                                    <?= htmlspecialchars($config['config_label']) ?>
                                </label>

                                <?php
                                $socialIcons = [
                                    'facebook' => 'fa-facebook',
                                    'instagram' => 'fa-instagram',
                                    'twitter' => 'fa-twitter',
                                    'youtube' => 'fa-youtube',
                                    'tiktok' => 'fa-tiktok',
                                    'whatsapp' => 'fa-whatsapp'
                                ];

                                $key = str_replace('social_', '', $config['config_key']);
                                $icon = $socialIcons[$key] ?? 'fa-link';
                                ?>

                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fab <?= $icon ?>"></i>
                                    </span>
                                    <input type="<?= $config['config_type'] ?>"
                                           class="form-control"
                                           name="configs[<?= $config['config_key'] ?>]"
                                           value="<?= htmlspecialchars($config['config_value'] ?? '') ?>"
                                           placeholder="<?= htmlspecialchars($config['config_description'] ?? '') ?>">

                                    <?php if (!empty($config['config_value'])): ?>
                                        <a href="<?= htmlspecialchars($config['config_value']) ?>"
                                           target="_blank"
                                           class="btn btn-outline-secondary">
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Branding -->
            <div class="tab-pane fade" id="tabBranding" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Branding Visual</h5>

                        <div class="row g-4">
                            <?php foreach ($groupedConfigs['branding'] ?? [] as $config): ?>
                                <div class="col-md-6">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <label class="form-label fw-bold">
                                                <?= htmlspecialchars($config['config_label']) ?>
                                            </label>

                                            <?php if ($config['config_type'] === 'color'): ?>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="color"
                                                           class="form-control form-control-color"
                                                           name="configs[<?= $config['config_key'] ?>]"
                                                           value="<?= htmlspecialchars($config['config_value'] ?? '#3b82f6') ?>"
                                                           style="width: 60px; height: 60px;">
                                                    <input type="text"
                                                           class="form-control color-hex-input"
                                                           value="<?= htmlspecialchars($config['config_value'] ?? '#3b82f6') ?>"
                                                           pattern="^#[0-9A-Fa-f]{6}$"
                                                           data-key="<?= $config['config_key'] ?>">
                                                </div>
                                            <?php elseif ($config['config_type'] === 'image' || $config['config_type'] === 'imagen'): ?>
                                                <!-- Campo de subida de imagen -->
                                                <div class="mb-3">
                                                    <input type="file"
                                                           class="form-control"
                                                           name="upload_<?= $config['config_key'] ?>"
                                                           accept="image/*"
                                                           id="upload_<?= $config['config_key'] ?>"
                                                           onchange="handleImageUploadPreview('<?= $config['config_key'] ?>')">
                                                </div>

                                                <!-- Preview -->
                                                <?php if (!empty($config['config_value'])): ?>
                                                    <div class="mt-2" id="preview_<?= $config['config_key'] ?>">
                                                        <p class="small text-muted mb-1">Vista previa:</p>
                                                        <?php
                                                        // Construir URL correcta para la imagen
                                                        $imageUrl = (strpos($config['config_value'], 'http') === 0)
                                                            ? $config['config_value']
                                                            : Config::getBaseUrl() . ltrim($config['config_value'], '/');
                                                        ?>
                                                        <img src="<?= htmlspecialchars($imageUrl) ?>"
                                                             alt="Preview"
                                                             class="img-thumbnail"
                                                             style="max-height: 100px;"
                                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'100\'%3E%3Crect fill=\'%23eee\' width=\'200\' height=\'100\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3EImagen no disponible%3C/text%3E%3C/svg%3E'">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="mt-2" id="preview_<?= $config['config_key'] ?>" style="display: none;">
                                                        <p class="small text-muted mb-1">Vista previa:</p>
                                                        <img src="" alt="Preview" class="img-thumbnail" style="max-height: 100px;">
                                                    </div>
                                                <?php endif; ?>

                                            <?php elseif ($config['config_type'] === 'url_or_upload'): ?>
                                                <!-- Opción 1: Subir archivo -->
                                                <div class="mb-3">
                                                    <label class="form-label small">Opción 1: Subir Imagen</label>
                                                    <input type="file"
                                                           class="form-control"
                                                           name="upload_<?= $config['config_key'] ?>"
                                                           accept="image/*"
                                                           id="upload_<?= $config['config_key'] ?>"
                                                           onchange="handleImageUploadPreview('<?= $config['config_key'] ?>')">
                                                </div>

                                                <!-- Opción 2: URL externa o ruta local -->
                                                <div class="mb-3">
                                                    <label class="form-label small">Opción 2: URL Externa</label>
                                                    <input type="text"
                                                           class="form-control"
                                                           name="configs[<?= $config['config_key'] ?>]"
                                                           id="text_<?= $config['config_key'] ?>"
                                                           value="<?= htmlspecialchars($config['config_value'] ?? '') ?>"
                                                           placeholder="https://images.unsplash.com/photo... o deja vacío para subir archivo">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle"></i>
                                                        Puedes ingresar una URL externa o subir un archivo arriba
                                                    </small>
                                                </div>

                                                <!-- Preview -->
                                                <?php if (!empty($config['config_value'])): ?>
                                                    <div class="mt-2" id="preview_<?= $config['config_key'] ?>">
                                                        <p class="small text-muted mb-1">Vista previa:</p>
                                                        <img src="<?= htmlspecialchars($config['config_value']) ?>"
                                                             alt="Preview"
                                                             class="img-thumbnail"
                                                             style="max-height: 150px;"
                                                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'100\'%3E%3Crect fill=\'%23eee\' width=\'200\' height=\'100\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%23999\'%3EImagen no disponible%3C/text%3E%3C/svg%3E'">
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>

                                            <?php if (!empty($config['config_description'])): ?>
                                                <small class="text-muted d-block mt-2">
                                                    <?= htmlspecialchars($config['config_description']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Homepage -->
            <div class="tab-pane fade" id="tabHomepage" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Textos de la Página de Inicio</h5>
                        <p class="text-muted mb-4">Personaliza los textos que aparecen en las diferentes secciones de tu homepage</p>

                        <?php foreach ($groupedConfigs['homepage'] ?? [] as $config): ?>
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <?= htmlspecialchars($config['config_label']) ?>
                                </label>

                                <?php if ($config['config_type'] === 'textarea'): ?>
                                    <textarea class="form-control"
                                              name="configs[<?= $config['config_key'] ?>]"
                                              rows="3"><?= htmlspecialchars($config['config_value'] ?? '') ?></textarea>
                                <?php else: ?>
                                    <input type="text"
                                           class="form-control"
                                           name="configs[<?= $config['config_key'] ?>]"
                                           value="<?= htmlspecialchars($config['config_value'] ?? '') ?>">
                                <?php endif; ?>

                                <?php if (!empty($config['config_description'])): ?>
                                    <small class="text-muted d-block mt-1"><?= htmlspecialchars($config['config_description']) ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>

                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Tip:</strong> Los cambios se reflejarán inmediatamente en la página de inicio después de guardar.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Horarios -->
            <div class="tab-pane fade" id="tabHorarios" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Horarios de Atención</h5>

                        <?php foreach ($groupedConfigs['horarios'] ?? [] as $config): ?>
                            <div class="mb-3">
                                <label class="form-label">
                                    <?= htmlspecialchars($config['config_label']) ?>
                                </label>

                                <?php if ($config['config_type'] === 'textarea'): ?>
                                    <textarea class="form-control"
                                              name="configs[<?= $config['config_key'] ?>]"
                                              rows="3"><?= htmlspecialchars($config['config_value'] ?? '') ?></textarea>
                                <?php else: ?>
                                    <input type="text"
                                           class="form-control"
                                           name="configs[<?= $config['config_key'] ?>]"
                                           value="<?= htmlspecialchars($config['config_value'] ?? '') ?>"
                                           placeholder="<?= htmlspecialchars($config['config_description'] ?? '') ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- SEO -->
            <div class="tab-pane fade" id="tabSeo" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Optimización para Motores de Búsqueda</h5>

                        <?php foreach ($groupedConfigs['seo'] ?? [] as $config): ?>
                            <div class="mb-3">
                                <label class="form-label">
                                    <?= htmlspecialchars($config['config_label']) ?>
                                </label>

                                <?php if ($config['config_type'] === 'textarea'): ?>
                                    <textarea class="form-control"
                                              name="configs[<?= $config['config_key'] ?>]"
                                              rows="3"><?= htmlspecialchars($config['config_value'] ?? '') ?></textarea>
                                <?php else: ?>
                                    <input type="text"
                                           class="form-control"
                                           name="configs[<?= $config['config_key'] ?>]"
                                           value="<?= htmlspecialchars($config['config_value'] ?? '') ?>">
                                <?php endif; ?>

                                <?php if (!empty($config['config_description'])): ?>
                                    <small class="text-muted"><?= htmlspecialchars($config['config_description']) ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Pagos -->
            <div class="tab-pane fade" id="tabPagos" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Configuración de Pagos</h5>

                        <?php foreach ($groupedConfigs['pagos'] ?? [] as $config): ?>
                            <div class="mb-3">
                                <label class="form-label">
                                    <?= htmlspecialchars($config['config_label']) ?>
                                </label>

                                <?php if ($config['config_type'] === 'textarea'): ?>
                                    <textarea class="form-control"
                                              name="configs[<?= $config['config_key'] ?>]"
                                              rows="3"><?= htmlspecialchars($config['config_value'] ?? '') ?></textarea>
                                <?php elseif ($config['config_type'] === 'number'): ?>
                                    <input type="number"
                                           class="form-control"
                                           name="configs[<?= $config['config_key'] ?>]"
                                           value="<?= htmlspecialchars($config['config_value'] ?? '') ?>"
                                           min="0"
                                           max="100">
                                <?php else: ?>
                                    <input type="text"
                                           class="form-control"
                                           name="configs[<?= $config['config_key'] ?>]"
                                           value="<?= htmlspecialchars($config['config_value'] ?? '') ?>">
                                <?php endif; ?>

                                <?php if (!empty($config['config_description'])): ?>
                                    <small class="text-muted"><?= htmlspecialchars($config['config_description']) ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tabAboutPage" role="tabpanel">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Página "Nosotros"</h5>
                        <p class="text-muted">Personaliza los textos e imágenes del apartado Acerca de Nosotros. Para listas, agrega un elemento por línea usando el formato <code>Título|Descripción</code>.</p>

                        <?php $aboutConfigs = $groupedConfigs['about_page'] ?? []; ?>
                        <?php if (!empty($aboutConfigs)): ?>
                            <?php
                            $sections = [
                                'Sección Hero' => ['about_hero_title', 'about_hero_subtitle', 'about_hero_image', 'about_hero_cta_text', 'about_hero_cta_link'],
                                'Estadísticas' => ['about_stats'],
                                'Nuestra Misión' => ['about_mission_title', 'about_mission_description', 'about_mission_points'],
                                'Valores' => ['about_values'],
                                'Historia' => ['about_story_title', 'about_story_content', 'about_story_image'],
                                'Equipo Destacado' => ['about_team'],
                                'CTA Final' => ['about_cta_title', 'about_cta_subtitle', 'about_cta_button_text', 'about_cta_button_link']
                            ];
                            ?>
                            <?php foreach ($sections as $sectionTitle => $keys): ?>
                                <div class="border rounded-3 p-3 mb-4 bg-light">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-layer-group me-2"></i>
                                        <?= $sectionTitle ?>
                                    </h6>
                                    <?php foreach ($aboutConfigs as $config): ?>
                                        <?php if (in_array($config['config_key'], $keys, true)): ?>
                                            <?= renderCompanyConfigInput($config) ?>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                No se detectaron configuraciones para esta sección. Ejecuta la migración <code>sql/migrations/010_about_page_config.sql</code> para generarlas automáticamente.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer con botones -->
        <div class="card mt-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted">
                        <i class="fas fa-info-circle me-2"></i>
                        Los cambios se aplicarán inmediatamente en todo el sitio web
                    </div>
                    <div>
                        <button type="reset" class="btn btn-secondary me-2">
                            <i class="fas fa-undo me-2"></i>
                            Restablecer
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>
                            Guardar Configuración
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sincronizar color picker con hex input
    document.querySelectorAll('.color-hex-input').forEach(input => {
        const key = input.dataset.key;
        const colorPicker = document.querySelector(`input[name="configs[${key}]"][type="color"]`);

        if (colorPicker) {
            input.addEventListener('input', function() {
                if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                    colorPicker.value = this.value;
                }
            });

            colorPicker.addEventListener('input', function() {
                input.value = this.value;
            });
        }
    });

    // Preview de imágenes
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewId = input.dataset.preview;
                    const preview = document.getElementById(previewId);

                    if (preview) {
                        preview.innerHTML = `
                            <img src="${e.target.result}"
                                 alt="Preview"
                                 class="img-thumbnail"
                                 style="max-height: 100px;">
                        `;
                    } else {
                        const newPreview = document.createElement('div');
                        newPreview.id = previewId;
                        newPreview.className = 'mt-2';
                        newPreview.innerHTML = `
                            <img src="${e.target.result}"
                                 alt="Preview"
                                 class="img-thumbnail"
                                 style="max-height: 100px;">
                        `;
                        input.parentElement.appendChild(newPreview);
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    });

    // Validación del formulario
    document.getElementById('formCompanyConfig').addEventListener('submit', function(e) {
        const requiredInputs = this.querySelectorAll('[required]');
        let isValid = true;

        requiredInputs.forEach(input => {
            if (!input.value.trim()) {
                isValid = false;
                input.classList.add('is-invalid');
            } else {
                input.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Por favor completa todos los campos requeridos');
        }
    });
});

// Función para manejar preview de imágenes subidas
function handleImageUploadPreview(configKey) {
    const fileInput = document.getElementById(`upload_${configKey}`);
    const textInput = document.getElementById(`text_${configKey}`) || document.querySelector(`input[name="configs[${configKey}]"]`);
    const previewDiv = document.getElementById(`preview_${configKey}`);

    if (fileInput.files && fileInput.files[0]) {
        const file = fileInput.files[0];
        const reader = new FileReader();

        reader.onload = function(e) {
            // Actualizar campo de texto con mensaje temporal
            // (el servidor actualizará con la ruta real al guardar)
            if (textInput) {
                textInput.value = `(Archivo seleccionado: ${file.name})`;
                textInput.style.fontStyle = 'italic';
                textInput.style.color = '#666';
            }

            // Mostrar preview
            const preview = document.getElementById(`preview_${configKey}`);
            if (preview) {
                preview.style.display = 'block';
                preview.innerHTML = `
                    <p class="small text-muted mb-1">Vista previa:</p>
                    <img src="${e.target.result}"
                         alt="Preview"
                         class="img-thumbnail"
                         style="max-height: 150px;">
                    <p class="small text-muted mt-1">
                        <i class="fas fa-info-circle"></i>
                        Archivo: ${file.name} (${(file.size / 1024).toFixed(2)} KB)
                        <br>
                        <i class="fas fa-check-circle text-success"></i>
                        Se subirá al hacer clic en "Guardar Configuración"
                    </p>
                `;
            } else {
                // Crear preview si no existe
                const newPreview = document.createElement('div');
                newPreview.id = `preview_${configKey}`;
                newPreview.className = 'mt-2';
                newPreview.innerHTML = `
                    <p class="small text-muted mb-1">Vista previa:</p>
                    <img src="${e.target.result}"
                         alt="Preview"
                         class="img-thumbnail"
                         style="max-height: 150px;">
                    <p class="small text-muted mt-1">
                        <i class="fas fa-info-circle"></i>
                        Archivo: ${file.name} (${(file.size / 1024).toFixed(2)} KB)
                    </p>
                `;
                fileInput.parentElement.parentElement.appendChild(newPreview);
            }
        };

        reader.readAsDataURL(file);
    }
}
</script>

<style>
.nav-tabs .nav-link {
    color: #6c757d;
}

.nav-tabs .nav-link.active {
    font-weight: 600;
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.card-title {
    border-bottom: 2px solid #f0f0f0;
    padding-bottom: 10px;
}
</style>

<!-- Modal para subir imágenes -->
<div class="modal fade" id="imageUploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Subir Imagen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="imageUploadForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="imageFile" class="form-label">Seleccionar imagen</label>
                        <input type="file" class="form-control" id="imageFile" name="image" accept="image/*" required>
                        <small class="text-muted">Formatos: JPG, PNG, GIF, WebP (Max: 5MB)</small>
                    </div>
                    <div id="uploadProgress" class="progress mb-3" style="display: none;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                    <div id="uploadMessage"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="uploadImage()">
                    <i class="fas fa-upload me-1"></i>Subir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentImageField = '';
let imageUploadModal = null;

document.addEventListener('DOMContentLoaded', function() {
    imageUploadModal = new bootstrap.Modal(document.getElementById('imageUploadModal'));
});

function openImageUploadModal(fieldKey) {
    currentImageField = fieldKey;
    document.getElementById('imageFile').value = '';
    document.getElementById('uploadProgress').style.display = 'none';
    document.getElementById('uploadMessage').innerHTML = '';
    imageUploadModal.show();
}

function uploadImage() {
    const form = document.getElementById('imageUploadForm');
    const fileInput = document.getElementById('imageFile');
    const file = fileInput.files[0];

    if (!file) {
        document.getElementById('uploadMessage').innerHTML = '<div class="alert alert-warning">Por favor selecciona una imagen</div>';
        return;
    }

    // Validar tamaño (5MB)
    if (file.size > 5 * 1024 * 1024) {
        document.getElementById('uploadMessage').innerHTML = '<div class="alert alert-danger">La imagen es muy grande. Máximo 5MB.</div>';
        return;
    }

    const formData = new FormData();
    formData.append('image', file);
    formData.append('field_key', currentImageField);

    const progressBar = document.getElementById('uploadProgress');
    const progressBarInner = progressBar.querySelector('.progress-bar');
    progressBar.style.display = 'block';

    fetch('<?= Config::getBaseUrl() ?>?route=admin/upload-config-image', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar el campo de texto
            const inputField = document.getElementById('input_' + currentImageField);
            inputField.value = data.path;

            // Actualizar preview
            const preview = document.getElementById('preview_' + currentImageField);
            const img = preview.querySelector('img');
            img.src = '<?= Config::getBaseUrl() ?>../public/assets/' + data.path;
            preview.style.display = 'block';

            // Mostrar mensaje
            document.getElementById('uploadMessage').innerHTML = '<div class="alert alert-success">¡Imagen subida exitosamente!</div>';

            // Cerrar modal después de 1 segundo
            setTimeout(() => {
                imageUploadModal.hide();
            }, 1000);
        } else {
            document.getElementById('uploadMessage').innerHTML = '<div class="alert alert-danger">Error: ' + data.message + '</div>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('uploadMessage').innerHTML = '<div class="alert alert-danger">Error al subir la imagen</div>';
    })
    .finally(() => {
        progressBar.style.display = 'none';
    });
}

// Preview de imagen al escribir en el campo de texto
document.addEventListener('input', function(e) {
    if (e.target.id && e.target.id.startsWith('input_') && e.target.id.includes('_image')) {
        const fieldKey = e.target.id.replace('input_', '');
        const value = e.target.value.trim();
        const preview = document.getElementById('preview_' + fieldKey);

        if (preview && value) {
            const img = preview.querySelector('img');
            img.src = '<?= Config::getBaseUrl() ?>../public/assets/' + value;
            preview.style.display = 'block';
        } else if (preview) {
            preview.style.display = 'none';
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
