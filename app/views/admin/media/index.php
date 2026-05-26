<?php
use App\Core\Config;
use App\Core\Helpers;
require_once __DIR__ . '/../../layouts/admin_header.php'; ?>

<div class="container-fluid px-4 py-3">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= Config::getBaseUrl() ?>?route=admin/dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">Biblioteca de Medios</li>
        </ol>
    </nav>

    <?php
        $actionTitle = 'Biblioteca de Medios';
        $actionSubtitle = $stats['total_files'] . ' archivos · ' . number_format($stats['total_size'] / 1048576, 2) . ' MB · ' . $stats['total_images'] . ' imágenes';
        $actionButtons = [
            ['label' => 'Subir Archivos', 'icon' => 'fas fa-upload', 'variant' => 'primary', 'id' => 'uploadBtn'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Filtros y Búsqueda -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchInput"
                           placeholder="Buscar archivos..." value="<?= htmlspecialchars($currentSearch) ?>">
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="folderFilter">
                        <option value="">Todas las carpetas</option>
                        <?php foreach ($folders as $folder): ?>
                        <option value="<?= htmlspecialchars($folder['folder']) ?>"
                                <?= $currentFolder === $folder['folder'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($folder['folder']) ?> (<?= $folder['count'] ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="typeFilter">
                        <option value="">Todos los tipos</option>
                        <option value="image">Imágenes</option>
                        <option value="application">Documentos</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-secondary w-100" id="clearFiltersBtn">
                        <i class="fas fa-times me-2"></i>Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Acciones -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <button class="btn btn-sm btn-outline-primary" id="selectAllBtn">
                <i class="fas fa-check-square me-1"></i>Seleccionar Todo
            </button>
            <button class="btn btn-sm btn-outline-secondary" id="deselectAllBtn" style="display:none;">
                <i class="fas fa-square me-1"></i>Deseleccionar
            </button>
            <span class="ms-3 text-muted" id="selectedCount">0 seleccionados</span>
        </div>
        <div>
            <button class="btn btn-sm btn-danger" id="bulkDeleteBtn" style="display:none;">
                <i class="fas fa-trash me-1"></i>Eliminar Seleccionados
            </button>
        </div>
    </div>

    <!-- Grid de Medios -->
    <div id="mediaGrid" class="media-grid mb-4">
        <?php if (empty($items)): ?>
        <div class="text-center py-5">
            <i class="fas fa-images fa-3x text-muted mb-3"></i>
            <p class="text-muted">No hay archivos en la biblioteca</p>
            <button class="btn btn-primary" onclick="document.getElementById('uploadBtn').click()">
                <i class="fas fa-upload me-2"></i>Subir tu primer archivo
            </button>
        </div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
            <div class="media-item" data-id="<?= $item['id'] ?>" data-mime="<?= htmlspecialchars($item['mime_type']) ?>">
                <div class="media-item-inner">
                    <div class="media-checkbox">
                        <input type="checkbox" class="form-check-input media-select" value="<?= $item['id'] ?>">
                    </div>
                    <div class="media-preview" onclick="openPreview(<?= $item['id'] ?>)">
                        <?php if (strpos($item['mime_type'], 'image/') === 0): ?>
                            <img src="<?= htmlspecialchars($item['url']) ?>"
                                 alt="<?= htmlspecialchars($item['alt_text']) ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="file-icon">
                                <i class="fas fa-file fa-3x text-secondary"></i>
                                <div class="file-ext"><?= strtoupper(pathinfo($item['filename'], PATHINFO_EXTENSION)) ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="media-info">
                        <div class="media-title" title="<?= htmlspecialchars($item['title']) ?>">
                            <?= htmlspecialchars($item['title'] ?: $item['original_filename']) ?>
                        </div>
                        <div class="media-meta">
                            <?php if ($item['width']): ?>
                                <?= $item['width'] ?>×<?= $item['height'] ?> ·
                            <?php endif; ?>
                            <?= number_format($item['file_size'] / 1024, 0) ?> KB
                            <?php if ($item['used_count'] > 0): ?>
                                <span class="badge bg-success ms-1" title="Usado <?= $item['used_count'] ?> veces">
                                    <i class="fas fa-check"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginación -->
    <?php if ($pagination['total_pages'] > 1): ?>
    <nav>
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
            <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                <a class="page-link" href="?route=admin/media&page=<?= $i ?>&folder=<?= urlencode($currentFolder) ?>&search=<?= urlencode($currentSearch) ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- Modal: Upload -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-upload me-2"></i>Subir Archivos
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="dropZone" class="drop-zone">
                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                    <p class="mb-2">Arrastra archivos aquí o haz clic para seleccionar</p>
                    <small class="text-muted">Máximo 10MB por archivo</small>
                    <input type="file" id="fileInput" multiple accept="image/*,.pdf,.doc,.docx" style="display:none;">
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <label class="form-label">Carpeta</label>
                        <select class="form-select" id="uploadFolder">
                            <option value="general">General</option>
                            <option value="tours">Tours</option>
                            <option value="banners">Banners</option>
                            <option value="profiles">Perfiles</option>
                        </select>
                    </div>
                </div>
                <div id="uploadProgress" style="display:none;" class="mt-3">
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                    <p class="text-center mt-2 mb-0" id="uploadStatus">Subiendo...</p>
                </div>
                <div id="uploadResults" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="startUploadBtn" disabled>
                    <i class="fas fa-upload me-2"></i>Subir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Preview & Edit -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewTitle">Vista Previa</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <div id="previewContent" class="text-center"></div>
                    </div>
                    <div class="col-md-4">
                        <form id="metadataForm">
                            <input type="hidden" id="editMediaId">
                            <div class="mb-3">
                                <label class="form-label">Título</label>
                                <input type="text" class="form-control" id="editTitle">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Texto Alternativo (Alt)</label>
                                <input type="text" class="form-control" id="editAlt">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Descripción</label>
                                <textarea class="form-control" id="editDescription" rows="3"></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Carpeta</label>
                                <select class="form-select" id="editFolder">
                                    <option value="general">General</option>
                                    <option value="tours">Tours</option>
                                    <option value="banners">Banners</option>
                                    <option value="profiles">Perfiles</option>
                                </select>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <strong>URL:</strong>
                                <div class="input-group input-group-sm">
                                    <input type="text" class="form-control" id="mediaUrl" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyUrl()">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="variantsList"></div>
                            <div id="usageInfo"></div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger me-auto" id="deleteMediaBtn">
                    <i class="fas fa-trash me-2"></i>Eliminar
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="saveMetadataBtn">
                    <i class="fas fa-save me-2"></i>Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= Config::getBaseUrl() ?>';
const CSRF_TOKEN = '<?= $_SESSION['csrf_token'] ?? '' ?>';
</script>
<script src="<?= Helpers::asset('js/media-library.js') ?>"></script>

<style>
.media-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.media-item {
    position: relative;
    border: 2px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: hidden;
    transition: all 0.3s;
    background: white;
}

.media-item:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.media-item.selected {
    border-color: #0d6efd;
    background: #e7f3ff;
}

.media-item-inner {
    position: relative;
}

.media-checkbox {
    position: absolute;
    top: 8px;
    left: 8px;
    z-index: 10;
}

.media-checkbox input {
    width: 20px;
    height: 20px;
}

.media-preview {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f8f9fa;
    cursor: pointer;
    overflow: hidden;
}

.media-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.file-icon {
    text-align: center;
}

.file-ext {
    font-size: 0.75rem;
    font-weight: bold;
    color: #6c757d;
    margin-top: 0.5rem;
}

.media-info {
    padding: 0.75rem;
}

.media-title {
    font-size: 0.875rem;
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.media-meta {
    font-size: 0.75rem;
    color: #6c757d;
    margin-top: 0.25rem;
}

.drop-zone {
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
    padding: 3rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.drop-zone:hover, .drop-zone.drag-over {
    border-color: #0d6efd;
    background: #e7f3ff;
}

#previewContent img {
    max-width: 100%;
    max-height: 500px;
    border-radius: 0.375rem;
}
</style>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
