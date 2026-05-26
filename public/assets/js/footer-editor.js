/**
 * Footer Editor - JavaScript
 * Gestión visual del editor de footer con drag & drop
 *
 * @version 1.0.0
 * @date 2025-11-05
 */

// Variables globales
let currentSectionId = null;
let currentSectionType = null;
let sortableInstances = [];

// ================================================
// Inicialización
// ================================================

document.addEventListener('DOMContentLoaded', function() {
    initSortable();
    initEventListeners();
    console.log('Footer Editor initialized');
});

// ================================================
// Sortable (Drag & Drop)
// ================================================

function initSortable() {
    const columns = document.querySelectorAll('.sortable-column');

    columns.forEach(column => {
        const sortable = Sortable.create(column, {
            group: 'footer-sections',
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: handleDragEnd
        });

        sortableInstances.push(sortable);
    });
}

function handleDragEnd(evt) {
    const sectionId = evt.item.dataset.id;
    const newColumn = evt.to.dataset.column;
    const oldColumn = evt.from.dataset.column;

    // Actualizar placeholders
    updateEmptyPlaceholders();

    // Si cambió de columna, mover en BD
    if (newColumn !== oldColumn) {
        moveSectionToColumn(sectionId, newColumn);
    } else {
        // Solo reordenar en la misma columna
        reorderSections(newColumn);
    }
}

function reorderSections(columnPosition) {
    const column = document.querySelector(`[data-column="${columnPosition}"]`);
    const sections = Array.from(column.querySelectorAll('.section-card'));
    const orderedIds = sections.map(s => s.dataset.id);

    showLoading('Reordenando secciones...');

    fetch(`${BASE_URL}?route=admin/footer/reorder`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            column_position: parseInt(columnPosition),
            items: orderedIds,
            csrf_token: CSRF_TOKEN
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showToast('Éxito', 'Secciones reordenadas correctamente', 'success');
        } else {
            showToast('Error', data.message || 'Error al reordenar', 'error');
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        hideLoading();
        window.location.reload();
    });
}

function moveSectionToColumn(sectionId, newColumn) {
    showLoading('Moviendo sección...');

    fetch(`${BASE_URL}?route=admin/footer/move-section`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: parseInt(sectionId),
            new_column: parseInt(newColumn),
            csrf_token: CSRF_TOKEN
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showToast('Éxito', 'Sección movida correctamente', 'success');
        } else {
            showToast('Error', data.message || 'Error al mover sección', 'error');
            window.location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        hideLoading();
        window.location.reload();
    });
}

// ================================================
// Event Listeners
// ================================================

function initEventListeners() {
    // Botones "Agregar Sección"
    document.querySelectorAll('.add-section-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const type = this.dataset.type;
            openAddSectionModal(type);
        });
    });

    // Botón "Editar Sección"
    document.querySelectorAll('.edit-section-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const sectionId = this.dataset.id;
            openEditSectionModal(sectionId);
        });
    });

    // Botón "Eliminar Sección"
    document.querySelectorAll('.delete-section-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const sectionId = this.dataset.id;
            deleteSection(sectionId);
        });
    });

    // Botón "Duplicar Sección"
    document.querySelectorAll('.duplicate-section-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const sectionId = this.dataset.id;
            duplicateSection(sectionId);
        });
    });

    // Botón "Toggle Visibilidad"
    document.querySelectorAll('.toggle-visible-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const sectionId = this.dataset.id;
            toggleVisibility(sectionId);
        });
    });

    // Botón "Guardar Sección"
    document.getElementById('saveSectionBtn').addEventListener('click', saveSection);

    // Botón "Configuración Global"
    document.getElementById('settingsBtn').addEventListener('click', function() {
        new bootstrap.Modal(document.getElementById('settingsModal')).show();
    });

    // Botón "Guardar Configuración"
    document.getElementById('saveSettingsBtn').addEventListener('click', saveSettings);

    // Toggle "Activar Footer Dinámico"
    document.getElementById('enableFooterToggle').addEventListener('change', function() {
        toggleFooterEnabled(this.checked);
    });
}

// ================================================
// Modales
// ================================================

function openAddSectionModal(type) {
    currentSectionId = null;
    currentSectionType = type;

    document.getElementById('sectionModalTitle').innerHTML =
        `<i class="fas fa-plus-circle me-2"></i>Nueva Sección - ${getSectionTypeName(type)}`;
    document.getElementById('sectionId').value = '';
    document.getElementById('sectionType').value = type;
    document.getElementById('sectionTitle').value = getSectionDefaultTitle(type);
    document.getElementById('sectionColumn').value = '1';
    document.getElementById('sectionVisible').checked = true;

    renderTypeSpecificFields(type, {});

    new bootstrap.Modal(document.getElementById('sectionModal')).show();
}

async function openEditSectionModal(sectionId) {
    showLoading('Cargando sección...');

    try {
        const response = await fetch(`${BASE_URL}?route=admin/footer/get-section&id=${sectionId}`);
        const data = await response.json();

        hideLoading();

        if (!data.success) {
            showToast('Error', data.message || 'Error al cargar sección', 'error');
            return;
        }

        const section = data.section;
        currentSectionId = section.id;
        currentSectionType = section.type;

        document.getElementById('sectionModalTitle').innerHTML =
            `<i class="fas fa-edit me-2"></i>Editar Sección - ${getSectionTypeName(section.type)}`;
        document.getElementById('sectionId').value = section.id;
        document.getElementById('sectionType').value = section.type;
        document.getElementById('sectionTitle').value = section.title;
        document.getElementById('sectionColumn').value = section.column_position;
        document.getElementById('sectionVisible').checked = section.visible == 1;

        renderTypeSpecificFields(section.type, section.content_decoded || {});

        new bootstrap.Modal(document.getElementById('sectionModal')).show();
    } catch (error) {
        console.error('Error:', error);
        hideLoading();
        showToast('Error', 'Error al cargar la sección', 'error');
    }
}

// ================================================
// Campos Específicos por Tipo
// ================================================

function renderTypeSpecificFields(type, content) {
    const container = document.getElementById('typeSpecificFields');
    let html = '';

    switch (type) {
        case 'company_info':
            html = `
                <h6 class="mb-3">Configuración de Información de Empresa</h6>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="show_logo" name="show_logo" value="true"
                           ${content.show_logo ? 'checked' : ''}>
                    <label class="form-check-label" for="show_logo">Mostrar Logo/Ícono</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="show_name" name="show_name" value="true"
                           ${content.show_name !== false ? 'checked' : ''}>
                    <label class="form-check-label" for="show_name">Mostrar Nombre de Empresa</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="show_tagline" name="show_tagline" value="true"
                           ${content.show_tagline !== false ? 'checked' : ''}>
                    <label class="form-check-label" for="show_tagline">Mostrar Eslogan</label>
                </div>
                <div class="alert alert-info small mt-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Los datos se toman de la Configuración de Empresa
                </div>
            `;
            break;

        case 'links':
            html = `
                <h6 class="mb-3">Configuración de Enlaces</h6>
                <div class="mb-3">
                    <label class="form-label">Fuente de Enlaces</label>
                    <select class="form-select" id="links_source" name="links_source">
                        <option value="navigation_menu" ${content.source === 'navigation_menu' ? 'selected' : ''}>
                            Menú de Navegación
                        </option>
                        <option value="custom" ${content.source === 'custom' ? 'selected' : ''}>
                            Enlaces Personalizados
                        </option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Nombre del Menú</label>
                    <input type="text" class="form-control" id="menu_name" name="menu_name"
                           value="${content.menu_name || 'footer'}"
                           placeholder="footer">
                    <small class="form-text text-muted">Ejemplo: footer, main, user</small>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="show_icons" name="show_icons" value="true"
                           ${content.show_icons !== false ? 'checked' : ''}>
                    <label class="form-check-label" for="show_icons">Mostrar Íconos</label>
                </div>
            `;
            break;

        case 'contact':
            const showFields = content.show_fields || ['email', 'phone', 'address', 'whatsapp'];
            html = `
                <h6 class="mb-3">Configuración de Contacto</h6>
                <p class="text-muted small">Selecciona qué campos mostrar:</p>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="show_email" name="show_email" value="true"
                           ${showFields.includes('email') ? 'checked' : ''}>
                    <label class="form-check-label" for="show_email">Email</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="show_phone" name="show_phone" value="true"
                           ${showFields.includes('phone') ? 'checked' : ''}>
                    <label class="form-check-label" for="show_phone">Teléfono</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="show_address" name="show_address" value="true"
                           ${showFields.includes('address') ? 'checked' : ''}>
                    <label class="form-check-label" for="show_address">Dirección</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="show_whatsapp" name="show_whatsapp" value="true"
                           ${showFields.includes('whatsapp') ? 'checked' : ''}>
                    <label class="form-check-label" for="show_whatsapp">WhatsApp</label>
                </div>
                <hr>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="show_icons" name="show_icons" value="true"
                           ${content.show_icons !== false ? 'checked' : ''}>
                    <label class="form-check-label" for="show_icons">Mostrar Íconos</label>
                </div>
                <div class="alert alert-info small mt-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Los datos se toman de la Configuración de Empresa
                </div>
            `;
            break;

        case 'social':
            const platforms = content.platforms || ['facebook', 'instagram', 'twitter', 'youtube', 'whatsapp'];
            html = `
                <h6 class="mb-3">Configuración de Redes Sociales</h6>
                <p class="text-muted small">Selecciona qué redes mostrar:</p>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="platform_facebook" name="platform_facebook" value="true"
                           ${platforms.includes('facebook') ? 'checked' : ''}>
                    <label class="form-check-label" for="platform_facebook">Facebook</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="platform_instagram" name="platform_instagram" value="true"
                           ${platforms.includes('instagram') ? 'checked' : ''}>
                    <label class="form-check-label" for="platform_instagram">Instagram</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="platform_twitter" name="platform_twitter" value="true"
                           ${platforms.includes('twitter') ? 'checked' : ''}>
                    <label class="form-check-label" for="platform_twitter">Twitter</label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="platform_youtube" name="platform_youtube" value="true"
                           ${platforms.includes('youtube') ? 'checked' : ''}>
                    <label class="form-check-label" for="platform_youtube">YouTube</label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="platform_whatsapp" name="platform_whatsapp" value="true"
                           ${platforms.includes('whatsapp') ? 'checked' : ''}>
                    <label class="form-check-label" for="platform_whatsapp">WhatsApp</label>
                </div>
                <hr>
                <div class="mb-3">
                    <label class="form-label">Tamaño de Íconos</label>
                    <select class="form-select" id="icon_size" name="icon_size">
                        <option value="fa-sm" ${content.icon_size === 'fa-sm' ? 'selected' : ''}>Pequeño</option>
                        <option value="fa-lg" ${content.icon_size === 'fa-lg' || !content.icon_size ? 'selected' : ''}>Grande</option>
                        <option value="fa-2x" ${content.icon_size === 'fa-2x' ? 'selected' : ''}>Muy Grande</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Disposición</label>
                    <select class="form-select" id="layout" name="layout">
                        <option value="horizontal" ${content.layout === 'horizontal' || !content.layout ? 'selected' : ''}>Horizontal</option>
                        <option value="vertical" ${content.layout === 'vertical' ? 'selected' : ''}>Vertical</option>
                    </select>
                </div>
                <div class="alert alert-info small mt-3">
                    <i class="fas fa-info-circle me-1"></i>
                    Las URLs se toman de la Configuración de Empresa
                </div>
            `;
            break;

        case 'newsletter':
            html = `
                <h6 class="mb-3">Configuración de Newsletter</h6>
                <div class="mb-3">
                    <label class="form-label">Título</label>
                    <input type="text" class="form-control" id="newsletter_title" name="newsletter_title"
                           value="${content.title || 'Newsletter'}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Descripción</label>
                    <input type="text" class="form-control" id="newsletter_description" name="newsletter_description"
                           value="${content.description || 'Recibe ofertas exclusivas'}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Texto del Botón</label>
                    <input type="text" class="form-control" id="newsletter_button" name="newsletter_button"
                           value="${content.button_text || 'Suscribirse'}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Placeholder del Email</label>
                    <input type="text" class="form-control" id="newsletter_placeholder" name="newsletter_placeholder"
                           value="${content.placeholder || 'Tu email'}">
                </div>
            `;
            break;

        case 'custom':
            html = `
                <h6 class="mb-3">HTML Personalizado</h6>
                <div class="mb-3">
                    <label class="form-label">Código HTML</label>
                    <textarea class="form-control font-monospace" id="custom_html" name="custom_html"
                              rows="8" placeholder="<p>Tu HTML aquí...</p>">${content.html || ''}</textarea>
                    <small class="form-text text-muted">
                        Puedes usar cualquier HTML válido. Evita usar <code>&lt;script&gt;</code> por seguridad.
                    </small>
                </div>
            `;
            break;
    }

    container.innerHTML = html;
}

// ================================================
// CRUD Operations
// ================================================

function saveSection() {
    const formData = new FormData(document.getElementById('sectionForm'));
    formData.append('csrf_token', CSRF_TOKEN);

    const isEdit = currentSectionId !== null;
    const route = isEdit ? 'admin/footer/update-section' : 'admin/footer/add-section';

    showLoading(isEdit ? 'Actualizando sección...' : 'Agregando sección...');

    fetch(`${BASE_URL}?route=${route}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showToast('Éxito', data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('sectionModal')).hide();
            setTimeout(() => window.location.reload(), 500);
        } else {
            showToast('Error', data.message || 'Error al guardar', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        hideLoading();
        showToast('Error', 'Error al procesar la solicitud', 'error');
    });
}

function deleteSection(sectionId) {
    if (!confirm('¿Estás seguro de eliminar esta sección?')) {
        return;
    }

    showLoading('Eliminando sección...');

    const formData = new FormData();
    formData.append('id', sectionId);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(`${BASE_URL}?route=admin/footer/delete-section`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showToast('Éxito', 'Sección eliminada correctamente', 'success');
            document.querySelector(`[data-id="${sectionId}"]`).remove();
            updateEmptyPlaceholders();
        } else {
            showToast('Error', data.message || 'Error al eliminar', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        hideLoading();
        showToast('Error', 'Error al procesar la solicitud', 'error');
    });
}

function duplicateSection(sectionId) {
    showLoading('Duplicando sección...');

    const formData = new FormData();
    formData.append('id', sectionId);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(`${BASE_URL}?route=admin/footer/duplicate-section`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        if (data.success) {
            showToast('Éxito', 'Sección duplicada correctamente', 'success');
            setTimeout(() => window.location.reload(), 500);
        } else {
            showToast('Error', data.message || 'Error al duplicar', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        hideLoading();
        showToast('Error', 'Error al procesar la solicitud', 'error');
    });
}

function toggleVisibility(sectionId) {
    const formData = new FormData();
    formData.append('id', sectionId);
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(`${BASE_URL}?route=admin/footer/toggle-visible`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const sectionCard = document.querySelector(`[data-id="${sectionId}"]`);
            const btn = sectionCard.querySelector('.toggle-visible-btn');
            const icon = btn.querySelector('i');

            sectionCard.classList.toggle('section-hidden');

            if (sectionCard.classList.contains('section-hidden')) {
                btn.classList.remove('btn-outline-success');
                btn.classList.add('btn-outline-secondary');
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                btn.title = 'Mostrar';
            } else {
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-outline-success');
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                btn.title = 'Ocultar';
            }

            showToast('Éxito', 'Visibilidad actualizada', 'success');
        } else {
            showToast('Error', data.message || 'Error al actualizar', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error', 'Error al procesar la solicitud', 'error');
    });
}

function saveSettings() {
    const numColumns = document.getElementById('numColumns').value;
    const currentNumColumns = CONFIG.num_columns || '4';

    // Si cambia el número de columnas, advertir que se recargará
    if (numColumns !== currentNumColumns) {
        if (!confirm('Cambiar el número de columnas recargará la página. ¿Continuar?')) {
            return;
        }
    }

    // Recopilar todas las configuraciones
    const configs = [
        { key: 'num_columns', value: numColumns },
        { key: 'copyright_text', value: document.getElementById('copyrightText').value },
        { key: 'bg_color', value: document.getElementById('bgColor').value },
        { key: 'text_color', value: document.getElementById('textColor').value },
        { key: 'show_bottom_links', value: document.getElementById('showBottomLinks').checked ? 'yes' : 'no' },
        { key: 'show_admin_link', value: document.getElementById('showAdminLink').checked ? 'yes' : 'no' }
    ];

    showLoading('Guardando configuración...');

    // Guardar cada configuración
    const promises = configs.map(config => {
        const formData = new FormData();
        formData.append('config_key', config.key);
        formData.append('config_value', config.value);
        formData.append('csrf_token', CSRF_TOKEN);

        return fetch(`${BASE_URL}?route=admin/footer/update-config`, {
            method: 'POST',
            body: formData
        });
    });

    Promise.all(promises)
        .then(() => {
            hideLoading();
            showToast('Éxito', 'Configuración guardada correctamente', 'success');
            bootstrap.Modal.getInstance(document.getElementById('settingsModal')).hide();
            setTimeout(() => window.location.reload(), 500);
        })
        .catch(error => {
            console.error('Error:', error);
            hideLoading();
            showToast('Error', 'Error al guardar configuración', 'error');
        });
}

function toggleFooterEnabled(enabled) {
    const formData = new FormData();
    formData.append('config_key', 'enabled');
    formData.append('config_value', enabled ? 'yes' : 'no');
    formData.append('csrf_token', CSRF_TOKEN);

    fetch(`${BASE_URL}?route=admin/footer/update-config`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('footerStatus').textContent = enabled ? 'Activado' : 'Desactivado';
            showToast('Éxito', `Footer dinámico ${enabled ? 'activado' : 'desactivado'}`, 'success');
        } else {
            showToast('Error', data.message || 'Error al actualizar', 'error');
            document.getElementById('enableFooterToggle').checked = !enabled;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error', 'Error al procesar la solicitud', 'error');
        document.getElementById('enableFooterToggle').checked = !enabled;
    });
}

// ================================================
// Utilidades
// ================================================

function updateEmptyPlaceholders() {
    document.querySelectorAll('.sortable-column').forEach(column => {
        const sections = column.querySelectorAll('.section-card');
        const placeholder = column.querySelector('.empty-placeholder');

        if (sections.length === 0 && !placeholder) {
            column.innerHTML = `
                <div class="text-center text-muted py-5 empty-placeholder">
                    <i class="fas fa-inbox fa-2x mb-2"></i>
                    <p class="small mb-0">Arrastra secciones aquí</p>
                </div>
            `;
        } else if (sections.length > 0 && placeholder) {
            placeholder.remove();
        }
    });
}

function getSectionTypeName(type) {
    const names = {
        'company_info': 'Información de Empresa',
        'links': 'Enlaces',
        'contact': 'Contacto',
        'social': 'Redes Sociales',
        'newsletter': 'Newsletter',
        'custom': 'HTML Personalizado'
    };
    return names[type] || type;
}

function getSectionDefaultTitle(type) {
    const titles = {
        'company_info': 'Sobre Nosotros',
        'links': 'Enlaces Útiles',
        'contact': 'Contacto',
        'social': 'Síguenos',
        'newsletter': 'Newsletter',
        'custom': 'Sección Personalizada'
    };
    return titles[type] || 'Nueva Sección';
}

function showLoading(message = 'Cargando...') {
    document.getElementById('loadingOverlay').style.display = 'block';
}

function hideLoading() {
    document.getElementById('loadingOverlay').style.display = 'none';
}

function showToast(title, message, type = 'info') {
    // Usar sistema de toasts si existe
    if (typeof window.ToastManager !== 'undefined') {
        window.ToastManager.show(title, message, type);
    } else {
        // Fallback a alert
        alert(`${title}: ${message}`);
    }
}
