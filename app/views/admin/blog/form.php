<?php
/**
 * Vista: Admin - Formulario de Post del Blog
 * Con editor Quill.js y análisis SEO en tiempo real
 */

use App\Core\Config;
use App\Core\Helpers;

$isEdit = isset($post) && !empty($post['id']);
$pageTitle = $isEdit ? 'Editar Post' : 'Nuevo Post';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<!-- Quill Editor CSS -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<div class="admin-blog-form">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="?route=admin">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="?route=admin/blog">Blog</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'Editar Post' : 'Nuevo Post' ?></li>
        </ol>
    </nav>

    <?php
        $actionTitle = $isEdit ? 'Editar: ' . htmlspecialchars($post['titulo']) : 'Crear Nuevo Post';
        $actionSubtitle = $isEdit ? 'Actualiza el contenido y el SEO del post' : 'Publica una nueva entrada en el blog';
        $actionButtons = [
            ['label' => 'Volver', 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary', 'href' => '?route=admin/blog'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <form id="formPost" method="POST" action="?route=admin/blog/<?= $isEdit ? 'editar/' . $post['id'] : 'crear' ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="row g-4">
            <!-- Columna Principal -->
            <div class="col-lg-8">
                <!-- Información Básica -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Información Básica
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Título del Post *</label>
                            <input type="text"
                                   class="form-control form-control-lg"
                                   name="titulo"
                                   id="postTitulo"
                                   value="<?= htmlspecialchars($post['titulo'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug (URL) *</label>
                            <div class="input-group">
                                <span class="input-group-text"><?= Config::getBaseUrl() ?>blog/</span>
                                <input type="text"
                                       class="form-control"
                                       name="slug"
                                       id="postSlug"
                                       value="<?= htmlspecialchars($post['slug'] ?? '') ?>"
                                       pattern="[a-z0-9\-]+"
                                       title="Solo letras minúsculas, números y guiones"
                                       required>
                            </div>
                            <small class="text-muted">Solo letras minúsculas, números y guiones. Se genera automáticamente.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción Corta</label>
                            <textarea class="form-control"
                                      name="descripcion_corta"
                                      id="descripcionCorta"
                                      rows="3"
                                      maxlength="200"><?= htmlspecialchars($post['descripcion_corta'] ?? '') ?></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Resumen breve para listados</small>
                                <small class="text-muted" id="descripcionCount">0 / 200</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select name="categoria_id" class="form-select">
                                <option value="">Sin categoría</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($post['categoria_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Contenido -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-file-alt me-2"></i>
                            Contenido del Post
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Editor Quill -->
                        <div id="editor-container" style="min-height: 400px; background: white;"></div>
                        <!-- Textarea oculto para enviar -->
                        <textarea name="contenido" id="postContenido" style="display: none;"><?= $post['contenido'] ?? '' ?></textarea>

                        <div class="mt-3 d-flex justify-content-between text-muted small">
                            <span><i class="fas fa-spell-check me-1"></i><span id="wordCount">0</span> palabras</span>
                            <span><i class="fas fa-clock me-1"></i><span id="readingTime">0</span> min de lectura</span>
                        </div>
                    </div>
                </div>

                <!-- SEO Enterprise -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-search me-2"></i>
                            Optimización SEO Enterprise
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Palabra Clave Principal (Focus Keyword)</label>
                            <input type="text"
                                   class="form-control"
                                   name="focus_keyword"
                                   id="focusKeyword"
                                   value="<?= htmlspecialchars($post['focus_keyword'] ?? '') ?>"
                                   placeholder="ej: viajar a guatemala">
                            <small class="text-muted">Palabra o frase clave principal para este artículo</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Meta Título</label>
                            <input type="text"
                                   class="form-control"
                                   name="meta_title"
                                   id="metaTitle"
                                   value="<?= htmlspecialchars($post['meta_title'] ?? '') ?>"
                                   maxlength="60">
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Título para motores de búsqueda (50-60 caracteres)</small>
                                <small class="text-muted" id="metaTitleCount">0 / 60</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Meta Descripción</label>
                            <textarea class="form-control"
                                      name="meta_description"
                                      id="metaDescription"
                                      rows="3"
                                      maxlength="160"><?= htmlspecialchars($post['meta_description'] ?? '') ?></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Descripción para resultados de búsqueda (150-160 caracteres)</small>
                                <small class="text-muted" id="metaDescCount">0 / 160</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Meta Keywords</label>
                            <input type="text"
                                   class="form-control"
                                   name="meta_keywords"
                                   value="<?= htmlspecialchars($post['meta_keywords'] ?? '') ?>"
                                   placeholder="tikal, guatemala, tours, arqueología">
                            <small class="text-muted">Palabras clave separadas por comas</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">URL Canónica</label>
                            <input type="url"
                                   class="form-control"
                                   name="canonical_url"
                                   id="canonicalUrl"
                                   value="<?= htmlspecialchars($post['canonical_url'] ?? '') ?>"
                                   placeholder="<?= Config::getBaseUrl() ?>blog/mi-post">
                            <small class="text-muted">Se genera automáticamente si se deja vacío</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">URL Imagen Open Graph</label>
                            <input type="url"
                                   class="form-control"
                                   name="og_image"
                                   value="<?= htmlspecialchars($post['og_image'] ?? '') ?>"
                                   placeholder="Usa imagen destacada si está vacío">
                            <small class="text-muted">Imagen para compartir en redes sociales (opcional)</small>
                        </div>

                        <!-- Vista previa SEO -->
                        <div class="card bg-light mt-4">
                            <div class="card-body">
                                <h6 class="text-muted small mb-3">VISTA PREVIA EN GOOGLE:</h6>
                                <div id="seoPreview">
                                    <div class="text-primary seo-preview-title" id="seoPreviewTitle">
                                        <?= htmlspecialchars($post['meta_title'] ?? $post['titulo'] ?? 'Título del Post') ?>
                                    </div>
                                    <div class="text-success small seo-preview-url" id="seoPreviewUrl">
                                        <?= Config::getBaseUrl() ?>blog/<?= htmlspecialchars($post['slug'] ?? 'slug') ?>
                                    </div>
                                    <div class="text-muted small" id="seoPreviewDesc">
                                        <?= htmlspecialchars($post['meta_description'] ?? 'Descripción del post...') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Análisis SEO en Tiempo Real -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Análisis SEO en Tiempo Real
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>SEO Score</strong>
                                    <span id="seoScoreValue" class="badge bg-secondary">0/100</span>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div id="seoScoreBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong>Legibilidad</strong>
                                    <span id="readabilityValue" class="badge bg-secondary">0/100</span>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div id="readabilityBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        <div id="seoChecklist" class="mt-3">
                            <h6>Checklist SEO:</h6>
                            <div id="seoChecklistItems" class="small">
                                <p class="text-muted">Completa los campos para ver el análisis SEO...</p>
                            </div>
                        </div>

                        <div id="seoSuggestions" class="mt-3" style="display: none;">
                            <h6>Sugerencias de Mejora:</h6>
                            <div id="seoSuggestionsItems" class="small"></div>
                        </div>

                        <button type="button" id="btnAnalyzeSeo" class="btn btn-outline-primary btn-sm mt-3">
                            <i class="fas fa-sync me-2"></i>
                            Analizar Ahora
                        </button>
                    </div>
                </div>
            </div>

            <!-- Columna Lateral -->
            <div class="col-lg-4">
                <!-- Publicación -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-cog me-2"></i>
                            Publicación
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Estado</label>
                            <select name="estado" class="form-select">
                                <option value="draft" <?= ($post['estado'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Borrador</option>
                                <option value="published" <?= ($post['estado'] ?? '') === 'published' ? 'selected' : '' ?>>Publicado</option>
                                <option value="scheduled" <?= ($post['estado'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Programado</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Fecha de Publicación</label>
                            <input type="datetime-local"
                                   class="form-control"
                                   name="fecha_publicacion"
                                   value="<?= $post['fecha_publicacion'] ? date('Y-m-d\TH:i', strtotime($post['fecha_publicacion'])) : '' ?>">
                            <small class="text-muted">Dejar vacío para publicar ahora</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="destacado"
                                       id="postDestacado"
                                       value="1"
                                       <?= ($post['destacado'] ?? false) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="postDestacado">
                                    Destacar este post
                                </label>
                            </div>
                            <small class="text-muted">Aparecerá en la sección de destacados</small>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?= $isEdit ? 'Actualizar Post' : 'Crear Post' ?>
                            </button>

                            <?php if ($isEdit): ?>
                                <a href="?route=blog/<?= htmlspecialchars($post['slug']) ?>"
                                   target="_blank"
                                   class="btn btn-outline-secondary">
                                    <i class="fas fa-external-link-alt me-2"></i>
                                    Ver Post
                                </a>
                            <?php endif; ?>
                        </div>

                        <?php if ($isEdit): ?>
                            <hr>
                            <div class="small text-muted">
                                <div><strong>Creado:</strong> <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></div>
                                <div><strong>Actualizado:</strong> <?= date('d/m/Y H:i', strtotime($post['updated_at'])) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Imagen Destacada -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-image me-2"></i>
                            Imagen Destacada
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="imagePreview" class="mb-3">
                            <?php if (!empty($post['imagen_destacada'])): ?>
                                <img src="<?= Config::getBaseUrl() ?>public<?= htmlspecialchars($post['imagen_destacada']) ?>"
                                     class="img-fluid rounded"
                                     alt="Preview">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <input type="file"
                               id="imageUpload"
                               name="image_upload"
                               accept="image/*"
                               class="form-control mb-2">

                        <input type="hidden"
                               name="imagen_destacada"
                               id="imagenDestacadaInput"
                               value="<?= htmlspecialchars($post['imagen_destacada'] ?? '') ?>">

                        <div class="mb-3">
                            <label class="form-label small">Alt Text (SEO)</label>
                            <input type="text"
                                   class="form-control form-control-sm"
                                   name="imagen_alt"
                                   id="imagenAlt"
                                   value="<?= htmlspecialchars($post['imagen_alt'] ?? '') ?>"
                                   placeholder="Descripción de la imagen">
                        </div>

                        <small class="text-muted">Tamaño máximo: 2MB. Formatos: JPG, PNG, WebP</small>
                    </div>
                </div>

                <!-- Configuración Avanzada -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-cogs me-2"></i>
                            Configuración Avanzada
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Tiempo de Lectura (minutos)</label>
                            <input type="number"
                                   class="form-control"
                                   name="tiempo_lectura"
                                   id="tiempoLectura"
                                   value="<?= $post['tiempo_lectura'] ?? 0 ?>"
                                   min="0">
                            <small class="text-muted">Se calcula automáticamente (200 palabras/min)</small>
                        </div>
                    </div>
                </div>

                <?php if ($isEdit): ?>
                    <!-- Estadísticas -->
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-bar me-2"></i>
                                Estadísticas
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Vistas:</span>
                                <strong><?= number_format($post['vistas']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>SEO Score:</span>
                                <strong class="text-<?= $post['seo_score'] >= 80 ? 'success' : ($post['seo_score'] >= 50 ? 'warning' : 'danger') ?>">
                                    <?= $post['seo_score'] ?>/100
                                </strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Legibilidad:</span>
                                <strong><?= $post['readability_score'] ?>/100</strong>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<style>
.seo-preview-title {
    font-size: 18px;
    cursor: pointer;
}
.seo-preview-title:hover {
    text-decoration: underline;
}
.seo-preview-url {
    font-size: 14px;
}
.ql-container {
    font-size: 16px;
}
.ql-editor {
    min-height: 400px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isEdit = <?= $isEdit ? 'true' : 'false' ?>;
    const csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';

    // Auto-generar slug desde título
    const tituloInput = document.getElementById('postTitulo');
    const slugInput = document.getElementById('postSlug');

    if (!isEdit) {
        tituloInput.addEventListener('input', function() {
            const slug = this.value
                .toLowerCase()
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-|-$/g, '');
            slugInput.value = slug;
            updateSeoPreview();
        });
    }

    // Inicializar Quill Editor
    const quillEditor = new Quill('#editor-container', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                ['blockquote', 'code-block'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'indent': '-1'}, { 'indent': '+1' }],
                ['link', 'image'],
                [{ 'align': [] }],
                ['clean']
            ]
        },
        placeholder: 'Escribe el contenido del post aquí...'
    });

    // Cargar contenido existente
    const existingContent = document.getElementById('postContenido').value;
    if (existingContent) {
        quillEditor.root.innerHTML = existingContent;
    }

    // Sincronizar con textarea
    quillEditor.on('text-change', function() {
        document.getElementById('postContenido').value = quillEditor.root.innerHTML;
        updateWordCount();
    });

    // Contador de palabras y tiempo de lectura
    function updateWordCount() {
        const text = quillEditor.getText();
        const wordCount = text.trim().split(/\s+/).filter(w => w.length > 0).length;
        const readingTime = Math.ceil(wordCount / 200);

        document.getElementById('wordCount').textContent = wordCount;
        document.getElementById('readingTime').textContent = readingTime;
        document.getElementById('tiempoLectura').value = readingTime;
    }

    // Contadores de caracteres
    const metaTitleInput = document.getElementById('metaTitle');
    const metaDescInput = document.getElementById('metaDescription');
    const descripcionInput = document.getElementById('descripcionCorta');

    metaTitleInput.addEventListener('input', function() {
        document.getElementById('metaTitleCount').textContent = `${this.value.length} / 60`;
        updateSeoPreview();
    });

    metaDescInput.addEventListener('input', function() {
        document.getElementById('metaDescCount').textContent = `${this.value.length} / 160`;
        updateSeoPreview();
    });

    descripcionInput.addEventListener('input', function() {
        document.getElementById('descripcionCount').textContent = `${this.value.length} / 200`;
    });

    slugInput.addEventListener('input', updateSeoPreview);

    // Vista previa SEO
    function updateSeoPreview() {
        const titulo = metaTitleInput.value || tituloInput.value || 'Título del Post';
        const descripcion = metaDescInput.value || 'Descripción del post...';
        const slug = slugInput.value || 'slug';

        document.getElementById('seoPreviewTitle').textContent = titulo;
        document.getElementById('seoPreviewUrl').textContent = '<?= Config::getBaseUrl() ?>blog/' + slug;
        document.getElementById('seoPreviewDesc').textContent = descripcion;
    }

    // Análisis SEO en tiempo real
    let seoAnalysisTimeout;
    function triggerSeoAnalysis() {
        clearTimeout(seoAnalysisTimeout);
        seoAnalysisTimeout = setTimeout(analyzeSeo, 2000);
    }

    // Disparar análisis al cambiar contenido
    quillEditor.on('text-change', triggerSeoAnalysis);
    tituloInput.addEventListener('input', triggerSeoAnalysis);
    metaTitleInput.addEventListener('input', triggerSeoAnalysis);
    metaDescInput.addEventListener('input', triggerSeoAnalysis);
    document.getElementById('focusKeyword').addEventListener('input', triggerSeoAnalysis);

    // Botón manual de análisis
    document.getElementById('btnAnalyzeSeo').addEventListener('click', analyzeSeo);

    async function analyzeSeo() {
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('titulo', tituloInput.value);
        formData.append('contenido', quillEditor.root.innerHTML);
        formData.append('meta_title', metaTitleInput.value);
        formData.append('meta_description', metaDescInput.value);
        formData.append('slug', slugInput.value);
        formData.append('focus_keyword', document.getElementById('focusKeyword').value);
        formData.append('imagen_destacada', document.getElementById('imagenDestacadaInput').value);
        formData.append('imagen_alt', document.getElementById('imagenAlt').value);

        try {
            const response = await fetch('?route=admin/blog/analizar-seo', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                updateSeoScores(data);
            }
        } catch (error) {
            console.error('Error al analizar SEO:', error);
        }
    }

    function updateSeoScores(data) {
        // SEO Score
        const seoScore = data.seo.score;
        const seoClass = seoScore >= 80 ? 'success' : (seoScore >= 50 ? 'warning' : 'danger');

        document.getElementById('seoScoreValue').textContent = `${seoScore}/100`;
        document.getElementById('seoScoreValue').className = `badge bg-${seoClass}`;

        const seoBar = document.getElementById('seoScoreBar');
        seoBar.style.width = `${seoScore}%`;
        seoBar.className = `progress-bar bg-${seoClass}`;

        // Readability Score
        const readScore = data.readability.score;
        const readClass = readScore >= 60 ? 'success' : (readScore >= 40 ? 'warning' : 'danger');

        document.getElementById('readabilityValue').textContent = `${readScore}/100`;
        document.getElementById('readabilityValue').className = `badge bg-${readClass}`;

        const readBar = document.getElementById('readabilityBar');
        readBar.style.width = `${readScore}%`;
        readBar.className = `progress-bar bg-${readClass}`;

        // Checklist
        const checklistHtml = data.seo.checks.map(check => `
            <div class="mb-1">${check}</div>
        `).join('');

        document.getElementById('seoChecklistItems').innerHTML = checklistHtml || '<p class="text-muted">Sin validaciones</p>';

        // Sugerencias
        if (data.suggestions && data.suggestions.length > 0) {
            const suggestionsHtml = data.suggestions.map(sug => `
                <div class="alert alert-warning alert-sm py-2 mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>${sug}
                </div>
            `).join('');

            document.getElementById('seoSuggestionsItems').innerHTML = suggestionsHtml;
            document.getElementById('seoSuggestions').style.display = 'block';
        } else {
            document.getElementById('seoSuggestions').style.display = 'none';
        }
    }

    // Upload de imagen
    document.getElementById('imageUpload').addEventListener('change', async function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validar tamaño (2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('La imagen no debe exceder 2MB');
            this.value = '';
            return;
        }

        // Preview inmediato
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').innerHTML = `
                <img src="${e.target.result}" class="img-fluid rounded" alt="Preview">
            `;
        };
        reader.readAsDataURL(file);

        // Subir al servidor
        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('image', file);

        try {
            const response = await fetch('?route=admin/blog/subir-imagen', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                document.getElementById('imagenDestacadaInput').value = data.path;
                alert('Imagen subida correctamente');
            } else {
                alert(data.message || 'Error al subir imagen');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error al subir imagen');
        }
    });

    // Validación del formulario
    document.getElementById('formPost').addEventListener('submit', function(e) {
        // Sincronizar contenido de Quill
        document.getElementById('postContenido').value = quillEditor.root.innerHTML;

        const titulo = tituloInput.value.trim();
        const slug = slugInput.value.trim();
        const contenido = quillEditor.getText().trim();

        if (!titulo || !slug || !contenido) {
            e.preventDefault();
            alert('Por favor completa el título, slug y contenido');
            return false;
        }
    });

    // Inicializar contadores
    metaTitleInput.dispatchEvent(new Event('input'));
    metaDescInput.dispatchEvent(new Event('input'));
    descripcionInput.dispatchEvent(new Event('input'));
    updateWordCount();
    updateSeoPreview();

    // Análisis inicial si estamos editando
    if (isEdit) {
        setTimeout(analyzeSeo, 1000);
    }
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
