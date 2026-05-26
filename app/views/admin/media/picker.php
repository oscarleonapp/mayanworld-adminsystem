<?php
use App\Core\Helpers;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Media</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?= Helpers::asset('css/admin-premium.css') ?>" rel="stylesheet">
    <style>
        body { padding: 1rem; }
        .media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 0.5rem; }
        .media-item { position: relative; border: 2px solid #dee2e6; border-radius: 0.375rem; cursor: pointer; transition: all 0.2s; background: white; }
        .media-item:hover, .media-item.selected { border-color: #0d6efd; }
        .media-item.selected { background: #e7f3ff; }
        .media-preview { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; background: #f8f9fa; overflow: hidden; }
        .media-preview img { width: 100%; height: 100%; object-fit: cover; }
        .file-icon { text-align: center; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <?php
            $actionTitle = 'Seleccionar Media';
            $actionSubtitle = 'Elige un archivo para adjuntar';
            $actionButtons = [
                ['label' => 'Subir Nuevo', 'icon' => 'fas fa-upload', 'variant' => 'primary', 'id' => 'uploadNewBtn'],
            ];
            include __DIR__ . '/../../partials/admin_action_bar.php';
        ?>

        <!-- Filtros Rápidos -->
        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <input type="text" class="form-control form-control-sm" id="quickSearch" placeholder="Buscar...">
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" id="quickFolder">
                    <option value="">Todas las carpetas</option>
                    <?php foreach ($folders as $folder): ?>
                    <option value="<?= htmlspecialchars($folder['folder']) ?>">
                        <?= htmlspecialchars($folder['folder']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select form-select-sm" id="quickFilter">
                    <option value="all" <?= $currentFilter === 'all' ? 'selected' : '' ?>>Todos</option>
                    <option value="images" <?= $currentFilter === 'images' ? 'selected' : '' ?>>Solo Imágenes</option>
                    <option value="documents" <?= $currentFilter === 'documents' ? 'selected' : '' ?>>Solo Documentos</option>
                </select>
            </div>
        </div>

        <!-- Grid -->
        <div id="pickerGrid" class="media-grid mb-3" style="max-height: 400px; overflow-y: auto;">
            <?php if (empty($items)): ?>
            <div class="col-12 text-center py-4">
                <i class="fas fa-images fa-2x text-muted mb-2"></i>
                <p class="text-muted mb-0">No hay archivos</p>
            </div>
            <?php else: ?>
                <?php foreach ($items as $item): ?>
                <div class="media-item" data-id="<?= $item['id'] ?>" data-url="<?= htmlspecialchars($item['url']) ?>">
                    <div class="media-preview">
                        <?php if (strpos($item['mime_type'], 'image/') === 0): ?>
                            <img src="<?= htmlspecialchars($item['url']) ?>" alt="<?= htmlspecialchars($item['alt_text']) ?>" loading="lazy">
                        <?php else: ?>
                            <div class="file-icon">
                                <i class="fas fa-file fa-2x text-secondary"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Paginación Compacta -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <nav>
            <ul class="pagination pagination-sm justify-content-center mb-3">
                <?php for ($i = 1; $i <= min($pagination['total_pages'], 10); $i++): ?>
                <li class="page-item <?= $i === $pagination['page'] ? 'active' : '' ?>">
                    <a class="page-link" href="?route=admin/media/picker&page=<?= $i ?>&folder=<?= urlencode($currentFolder) ?>&search=<?= urlencode($currentSearch) ?>&filter=<?= urlencode($currentFilter) ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <!-- Footer con Botones -->
        <div class="d-flex justify-content-between">
            <div id="selectedInfo" class="text-muted"></div>
            <div>
                <button class="btn btn-secondary btn-sm" id="cancelBtn">Cancelar</button>
                <button class="btn btn-primary btn-sm" id="selectBtn" disabled>Seleccionar</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let selectedItem = null;

    // Click en item
    document.querySelectorAll('.media-item').forEach(item => {
        item.addEventListener('click', function() {
            // Deseleccionar anterior
            document.querySelectorAll('.media-item').forEach(i => i.classList.remove('selected'));

            // Seleccionar actual
            this.classList.add('selected');
            selectedItem = {
                id: this.dataset.id,
                url: this.dataset.url
            };

            document.getElementById('selectBtn').disabled = false;
            document.getElementById('selectedInfo').textContent = 'Archivo seleccionado';
        });
    });

    // Botón Seleccionar
    document.getElementById('selectBtn').addEventListener('click', function() {
        if (selectedItem && window.opener) {
            // Enviar al padre
            window.opener.postMessage({
                type: 'media-selected',
                media: selectedItem
            }, '*');
            window.close();
        }
    });

    // Botón Cancelar
    document.getElementById('cancelBtn').addEventListener('click', function() {
        window.close();
    });

    // Botón Subir Nuevo (abre modal en ventana padre)
    document.getElementById('uploadNewBtn').addEventListener('click', function() {
        if (window.opener) {
            window.opener.postMessage({ type: 'open-upload' }, '*');
        }
    });

    // Filtros rápidos (recarga página)
    document.getElementById('quickSearch').addEventListener('keyup', debounce(function(e) {
        updateFilters();
    }, 500));

    document.getElementById('quickFolder').addEventListener('change', updateFilters);
    document.getElementById('quickFilter').addEventListener('change', updateFilters);

    function updateFilters() {
        const search = document.getElementById('quickSearch').value;
        const folder = document.getElementById('quickFolder').value;
        const filter = document.getElementById('quickFilter').value;
        window.location.href = `?route=admin/media/picker&search=${encodeURIComponent(search)}&folder=${encodeURIComponent(folder)}&filter=${filter}`;
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    </script>
</body>
</html>
