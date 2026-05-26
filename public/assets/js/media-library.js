/**
 * Media Library - JavaScript
 * Gestión de la biblioteca de medios con drag & drop
 *
 * @version 1.0.0
 * @date 2025-11-05
 */

let selectedFiles = [];
let selectedMedia = [];

// ================================================
// Inicialización
// ================================================

document.addEventListener('DOMContentLoaded', function() {
    initUploadModal();
    initFilters();
    initSelection();
    initPreview();
});

// ================================================
// Upload Modal con Drag & Drop
// ================================================

function initUploadModal() {
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const startUploadBtn = document.getElementById('startUploadBtn');

    // Abrir modal
    uploadBtn.addEventListener('click', () => {
        uploadModal.show();
        selectedFiles = [];
        document.getElementById('uploadResults').innerHTML = '';
        document.getElementById('uploadProgress').style.display = 'none';
        startUploadBtn.disabled = true;
    });

    // Click en drop zone
    dropZone.addEventListener('click', () => fileInput.click());

    // Drag & drop
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('drag-over');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('drag-over');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        handleFiles(e.dataTransfer.files);
    });

    // Selección de archivos
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });

    // Iniciar upload
    startUploadBtn.addEventListener('click', uploadFiles);
}

function handleFiles(files) {
    selectedFiles = Array.from(files);

    if (selectedFiles.length === 0) {
        document.getElementById('startUploadBtn').disabled = true;
        return;
    }

    // Mostrar archivos seleccionados
    const resultsDiv = document.getElementById('uploadResults');
    resultsDiv.innerHTML = `
        <div class="alert alert-info">
            <strong>${selectedFiles.length} archivo(s) seleccionado(s)</strong>
            <ul class="mb-0 mt-2">
                ${selectedFiles.map(f => `<li>${f.name} (${formatFileSize(f.size)})</li>`).join('')}
            </ul>
        </div>
    `;

    document.getElementById('startUploadBtn').disabled = false;
}

function uploadFiles() {
    const formData = new FormData();
    const folder = document.getElementById('uploadFolder').value;

    selectedFiles.forEach(file => {
        formData.append('files[]', file);
    });

    formData.append('folder', folder);
    formData.append('csrf_token', CSRF_TOKEN);

    // Mostrar progress
    document.getElementById('uploadProgress').style.display = 'block';
    document.getElementById('startUploadBtn').disabled = true;

    fetch(BASE_URL + '?route=admin/media/upload', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('uploadProgress').style.display = 'none';

        if (data.success) {
            const resultsDiv = document.getElementById('uploadResults');
            resultsDiv.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    ${data.message}
                </div>
            `;

            // Recargar página después de 1 segundo
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showError(data.message || 'Error al subir archivos');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('uploadProgress').style.display = 'none';
        showError('Error al procesar la solicitud');
    });
}

// ================================================
// Filtros y Búsqueda
// ================================================

function initFilters() {
    const searchInput = document.getElementById('searchInput');
    const folderFilter = document.getElementById('folderFilter');
    const typeFilter = document.getElementById('typeFilter');
    const clearBtn = document.getElementById('clearFiltersBtn');

    // Búsqueda con debounce
    searchInput.addEventListener('keyup', debounce(() => applyFilters(), 500));

    // Filtros inmediatos
    folderFilter.addEventListener('change', applyFilters);
    typeFilter.addEventListener('change', applyFilters);

    // Limpiar filtros
    clearBtn.addEventListener('click', () => {
        searchInput.value = '';
        folderFilter.value = '';
        typeFilter.value = '';
        applyFilters();
    });
}

function applyFilters() {
    const search = document.getElementById('searchInput').value;
    const folder = document.getElementById('folderFilter').value;
    const type = document.getElementById('typeFilter').value;

    const params = new URLSearchParams();
    params.append('route', 'admin/media');
    if (search) params.append('search', search);
    if (folder) params.append('folder', folder);
    if (type) params.append('type', type);

    window.location.href = `?${params.toString()}`;
}

// ================================================
// Selección de Items
// ================================================

function initSelection() {
    const selectAllBtn = document.getElementById('selectAllBtn');
    const deselectAllBtn = document.getElementById('deselectAllBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

    // Checkboxes individuales
    document.querySelectorAll('.media-select').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelection);
    });

    // Seleccionar todo
    selectAllBtn.addEventListener('click', () => {
        document.querySelectorAll('.media-select').forEach(cb => cb.checked = true);
        updateSelection();
    });

    // Deseleccionar todo
    deselectAllBtn.addEventListener('click', () => {
        document.querySelectorAll('.media-select').forEach(cb => cb.checked = false);
        updateSelection();
    });

    // Eliminar seleccionados
    bulkDeleteBtn.addEventListener('click', bulkDelete);
}

function updateSelection() {
    const checkboxes = document.querySelectorAll('.media-select:checked');
    const count = checkboxes.length;

    selectedMedia = Array.from(checkboxes).map(cb => parseInt(cb.value));

    document.getElementById('selectedCount').textContent = `${count} seleccionado${count !== 1 ? 's' : ''}`;
    document.getElementById('deselectAllBtn').style.display = count > 0 ? 'inline-block' : 'none';
    document.getElementById('bulkDeleteBtn').style.display = count > 0 ? 'inline-block' : 'none';

    // Actualizar clases selected
    document.querySelectorAll('.media-item').forEach(item => {
        const checkbox = item.querySelector('.media-select');
        if (checkbox && checkbox.checked) {
            item.classList.add('selected');
        } else {
            item.classList.remove('selected');
        }
    });
}

function bulkDelete() {
    if (selectedMedia.length === 0) return;

    if (!confirm(`¿Eliminar ${selectedMedia.length} archivo(s)?`)) return;

    fetch(BASE_URL + '?route=admin/media/bulk-delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            ids: selectedMedia,
            csrf_token: CSRF_TOKEN
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message);
            setTimeout(() => window.location.reload(), 500);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error al eliminar archivos');
    });
}

// ================================================
// Preview & Edit Modal
// ================================================

function initPreview() {
    const saveBtn = document.getElementById('saveMetadataBtn');
    const deleteBtn = document.getElementById('deleteMediaBtn');

    saveBtn.addEventListener('click', saveMetadata);
    deleteBtn.addEventListener('click', deleteMedia);
}

function openPreview(id) {
    fetch(BASE_URL + `?route=admin/media/get-file&id=${id}`)
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            showError('Error al cargar archivo');
            return;
        }

        const media = data.media;
        const variants = data.variants || [];
        const usage = data.usage || [];

        // Mostrar preview
        const previewContent = document.getElementById('previewContent');
        if (media.mime_type.startsWith('image/')) {
            previewContent.innerHTML = `<img src="${media.url}" alt="${media.alt_text}" style="max-width:100%; max-height:500px;">`;
        } else {
            previewContent.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-file fa-4x text-secondary mb-3"></i>
                    <p>${media.original_filename}</p>
                </div>
            `;
        }

        // Llenar formulario
        document.getElementById('editMediaId').value = media.id;
        document.getElementById('editTitle').value = media.title || '';
        document.getElementById('editAlt').value = media.alt_text || '';
        document.getElementById('editDescription').value = media.description || '';
        document.getElementById('editFolder').value = media.folder;
        document.getElementById('mediaUrl').value = media.url;

        // Mostrar variantes
        if (variants.length > 0) {
            const variantsList = document.getElementById('variantsList');
            variantsList.innerHTML = `
                <div class="mb-3">
                    <strong>Variantes:</strong>
                    <div class="list-group list-group-flush">
                        ${variants.map(v => `
                            <div class="list-group-item p-1">
                                <small>
                                    ${v.variant_type}: ${v.width}×${v.height}
                                    <a href="${v.url}" target="_blank" class="float-end">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </small>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        // Mostrar uso
        if (usage.length > 0) {
            const usageInfo = document.getElementById('usageInfo');
            usageInfo.innerHTML = `
                <div class="alert alert-info p-2">
                    <small><strong>Usado en:</strong></small>
                    <ul class="mb-0 mt-1">
                        ${usage.map(u => `<li><small>${u.entity_type} #${u.entity_id}</small></li>`).join('')}
                    </ul>
                </div>
            `;
        } else {
            document.getElementById('usageInfo').innerHTML = '';
        }

        // Abrir modal
        new bootstrap.Modal(document.getElementById('previewModal')).show();
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error al cargar archivo');
    });
}

function saveMetadata() {
    const id = document.getElementById('editMediaId').value;
    const formData = new FormData();

    formData.append('id', id);
    formData.append('title', document.getElementById('editTitle').value);
    formData.append('alt_text', document.getElementById('editAlt').value);
    formData.append('description', document.getElementById('editDescription').value);
    formData.append('folder', document.getElementById('editFolder').value);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(BASE_URL + '?route=admin/media/update-metadata', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Metadata actualizada');
            setTimeout(() => window.location.reload(), 500);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error al actualizar metadata');
    });
}

function deleteMedia() {
    const id = document.getElementById('editMediaId').value;

    if (!confirm('¿Eliminar este archivo?')) return;

    const formData = new FormData();
    formData.append('id', id);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(BASE_URL + '?route=admin/media/delete', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Archivo eliminado');
            setTimeout(() => window.location.reload(), 500);
        } else {
            showError(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Error al eliminar archivo');
    });
}

// ================================================
// Utilidades
// ================================================

function copyUrl() {
    const urlInput = document.getElementById('mediaUrl');
    urlInput.select();
    document.execCommand('copy');
    showSuccess('URL copiada al portapapeles');
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
}

function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

function showSuccess(message) {
    // Usar toast si existe, sino alert
    if (typeof window.ToastManager !== 'undefined') {
        window.ToastManager.show('Éxito', message, 'success');
    } else {
        alert(message);
    }
}

function showError(message) {
    if (typeof window.ToastManager !== 'undefined') {
        window.ToastManager.show('Error', message, 'error');
    } else {
        alert('Error: ' + message);
    }
}
