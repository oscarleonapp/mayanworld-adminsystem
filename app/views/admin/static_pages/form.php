<?php

use App\Core\Config;
/**
 * Vista: Crear/Editar Página Estática
 * Con editor WYSIWYG Quill.js y campos SEO
 */

$isEdit = isset($page) && !empty($page['id']);
$pageTitle = $isEdit ? 'Editar Página' : 'Nueva Página';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<!-- Quill Editor (Free alternative to TinyMCE) -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

<div class="admin-page-form">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="?route=admin">Dashboard</a>
            </li>
            <li class="breadcrumb-item">
                <a href="?route=admin/pages">Páginas Estáticas</a>
            </li>
            <li class="breadcrumb-item active">
                <?= $isEdit ? 'Editar' : 'Nueva Página' ?>
            </li>
        </ol>
    </nav>

    <?php
        $actionTitle = $isEdit ? 'Editar Página: ' . htmlspecialchars($page['title']) : 'Nueva Página Estática';
        $actionSubtitle = $isEdit ? 'Actualiza contenido y configuración SEO' : 'Crea una nueva página para el sitio';
        $actionButtons = [];
        if ($isEdit) {
            $actionButtons[] = [
                'label' => 'Ver Página',
                'icon' => 'fas fa-external-link-alt',
                'variant' => 'outline-secondary',
                'href' => '?route=page/' . htmlspecialchars($page['slug'])
            ];
        }
        $actionButtons[] = [
            'label' => 'Volver',
            'icon' => 'fas fa-arrow-left',
            'variant' => 'secondary',
            'href' => '?route=admin/pages'
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <form id="formPage" method="POST" action="?route=admin/pages/<?= $isEdit ? 'edit/' . $page['id'] : 'create' ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="row g-4">
            <!-- Columna principal -->
            <div class="col-lg-8">
                <!-- Información básica -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Información Básica
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Título de la Página *</label>
                            <input type="text"
                                   class="form-control form-control-lg"
                                   name="title"
                                   id="pageTitulo"
                                   value="<?= htmlspecialchars($page['title'] ?? '') ?>"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Slug (URL) *</label>
                            <div class="input-group">
                                <span class="input-group-text"><?= Config::getBaseUrl() ?>page/</span>
                                <input type="text"
                                       class="form-control"
                                       name="slug"
                                       id="pageSlug"
                                       value="<?= htmlspecialchars($page['slug'] ?? '') ?>"
                                       pattern="[a-z0-9\-]+"
                                       title="Solo letras minúsculas, números y guiones"
                                       required>
                            </div>
                            <small class="text-muted">Solo letras minúsculas, números y guiones. Se genera automáticamente.</small>
                        </div>
                    </div>
                </div>

                <!-- Contenido -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            Contenido de la Página
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Contenedor del editor Quill -->
                        <div id="editor-container" style="min-height: 400px; background: white;"></div>
                        <!-- Textarea oculto para enviar el contenido -->
                        <textarea name="content" id="pageContenido" class="form-control" style="display: none;"><?= $page['content'] ?? '' ?></textarea>
                    </div>
                </div>

                <!-- SEO -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-search me-2"></i>
                            Optimización SEO
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Meta Título</label>
                            <input type="text"
                                   class="form-control"
                                   name="meta_title"
                                   id="metaTitle"
                                   value="<?= htmlspecialchars($page['meta_title'] ?? '') ?>"
                                   maxlength="60">
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Recomendado: 50-60 caracteres</small>
                                <small class="text-muted" id="metaTitleCount">0 / 60</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Meta Descripción</label>
                            <textarea class="form-control"
                                      name="meta_description"
                                      id="metaDescription"
                                      rows="3"
                                      maxlength="160"><?= htmlspecialchars($page['meta_description'] ?? '') ?></textarea>
                            <div class="d-flex justify-content-between mt-1">
                                <small class="text-muted">Recomendado: 150-160 caracteres</small>
                                <small class="text-muted" id="metaDescCount">0 / 160</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Meta Keywords</label>
                            <input type="text"
                                   class="form-control"
                                   name="meta_keywords"
                                   value="<?= htmlspecialchars($page['meta_keywords'] ?? '') ?>"
                                   placeholder="tours guatemala, viajes, tikal, semuc champey">
                            <small class="text-muted">Palabras clave separadas por comas</small>
                        </div>

                        <!-- Vista previa SEO -->
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="text-muted small mb-2">VISTA PREVIA EN GOOGLE:</h6>
                                <div id="seoPreview">
                                    <div class="text-primary" id="seoPreviewTitle" style="font-size: 18px;">
                                        <?= htmlspecialchars($page['meta_title'] ?? $page['title'] ?? 'Título de la Página') ?>
                                    </div>
                                    <div class="text-success small" id="seoPreviewUrl">
                                        <?= Config::getBaseUrl() ?>page/<?= htmlspecialchars($page['slug'] ?? 'slug') ?>
                                    </div>
                                    <div class="text-muted small" id="seoPreviewDesc">
                                        <?= htmlspecialchars($page['meta_description'] ?? 'Descripción de la página...') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
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
                            <input type="hidden" name="status" value="draft">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="status"
                                       id="pageActivo"
                                       value="published"
                                       <?= (!isset($page['status']) || $page['status'] === 'published') ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pageActivo">
                                    Página publicada
                                </label>
                            </div>
                            <small class="text-muted">La página será visible para el público</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="show_in_menu"
                                       id="pageMenu"
                                       value="1"
                                       <?= ($page['show_in_menu'] ?? false) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="pageMenu">
                                    Mostrar en menú
                                </label>
                            </div>
                            <small class="text-muted">Aparecerá en el menú principal</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Orden en menú</label>
                            <input type="number"
                                   class="form-control"
                                   name="menu_order"
                                   value="<?= $page['menu_order'] ?? 0 ?>"
                                   min="0">
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?= $isEdit ? 'Actualizar Página' : 'Crear Página' ?>
                            </button>

                            <?php if ($isEdit): ?>
                                <button type="button" class="btn btn-outline-secondary" id="btnSaveDraft">
                                    <i class="fas fa-file me-2"></i>
                                    Guardar borrador
                                </button>
                            <?php endif; ?>
                        </div>

                        <?php if ($isEdit): ?>
                            <hr>
                            <div class="small text-muted">
                                <div>Creada: <?= date('d/m/Y H:i', strtotime($page['created_at'])) ?></div>
                                <div>Actualizada: <?= date('d/m/Y H:i', strtotime($page['updated_at'])) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Ayuda -->
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-lightbulb me-2"></i>
                            Consejos
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="small mb-0">
                            <li>Usa títulos descriptivos y únicos</li>
                            <li>El slug debe ser corto y amigable</li>
                            <li>Optimiza el meta título (50-60 caracteres)</li>
                            <li>La meta descripción debe ser atractiva (150-160 caracteres)</li>
                            <li>Usa el editor para dar formato al texto</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-generar slug
    const tituloInput = document.getElementById('pageTitulo');
    const slugInput = document.getElementById('pageSlug');
    const isEdit = <?= $isEdit ? 'true' : 'false' ?>;

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
        placeholder: 'Escribe el contenido de la página aquí...'
    });

    // Cargar contenido existente
    const existingContent = document.getElementById('pageContenido').value;
    if (existingContent) {
        quillEditor.root.innerHTML = existingContent;
    }

    // Sincronizar contenido con textarea oculto al escribir
    quillEditor.on('text-change', function() {
        document.getElementById('pageContenido').value = quillEditor.root.innerHTML;
    });

    // Contadores de caracteres
    const metaTitleInput = document.getElementById('metaTitle');
    const metaDescInput = document.getElementById('metaDescription');

    metaTitleInput.addEventListener('input', function() {
        document.getElementById('metaTitleCount').textContent = `${this.value.length} / 60`;
        updateSeoPreview();
    });

    metaDescInput.addEventListener('input', function() {
        document.getElementById('metaDescCount').textContent = `${this.value.length} / 160`;
        updateSeoPreview();
    });

    slugInput.addEventListener('input', updateSeoPreview);

    // Actualizar vista previa SEO
    function updateSeoPreview() {
        const titulo = metaTitleInput.value || tituloInput.value || 'Título de la Página';
        const descripcion = metaDescInput.value || 'Descripción de la página...';
        const slug = slugInput.value || 'slug';

        document.getElementById('seoPreviewTitle').textContent = titulo;
        document.getElementById('seoPreviewUrl').textContent =
            '<?= Config::getBaseUrl() ?>page/' + slug;
        document.getElementById('seoPreviewDesc').textContent = descripcion;
    }

    // Inicializar contadores
    metaTitleInput.dispatchEvent(new Event('input'));
    metaDescInput.dispatchEvent(new Event('input'));

    // Guardar borrador
    <?php if ($isEdit): ?>
    document.getElementById('btnSaveDraft').addEventListener('click', function() {
        const activoCheckbox = document.getElementById('pageActivo');
        const wasChecked = activoCheckbox.checked;

        activoCheckbox.checked = false;
        document.getElementById('formPage').submit();

        if (wasChecked) {
            activoCheckbox.checked = true;
        }
    });
    <?php endif; ?>

    // Validación del formulario
    document.getElementById('formPage').addEventListener('submit', function(e) {
        const titulo = tituloInput.value.trim();
        const slug = slugInput.value.trim();

        if (!titulo || !slug) {
            e.preventDefault();
            alert('Por favor completa el título y el slug');
            return false;
        }

        // Sincronizar contenido de Quill al textarea
        document.getElementById('pageContenido').value = quillEditor.root.innerHTML;
    });
});
</script>

<style>
#seoPreview {
    font-family: Arial, sans-serif;
}

#seoPreviewTitle {
    text-decoration: none;
    cursor: pointer;
}

#seoPreviewTitle:hover {
    text-decoration: underline;
}

.ql-container {
    font-size: 16px;
}

.ql-editor {
    min-height: 400px;
}
</style>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
