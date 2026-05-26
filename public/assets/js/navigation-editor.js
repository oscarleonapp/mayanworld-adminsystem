/**
 * Navigation Editor
 * Gestión interactiva de menús con drag & drop
 */

(function() {
    'use strict';

    // Configuración
    const BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '';
    const CSRF_TOKEN = document.querySelector('input[name="csrf_token"]')?.value || '';

    // Elementos del DOM
    let sortableInstance = null;
    let currentMenuId = null;
    let itemModal = null;
    let itemForm = null;
    let isEditMode = false;

    /**
     * Inicializar editor
     */
    function init() {
        // Obtener elementos
        const sortableContainer = document.getElementById('sortable-menu');
        if (!sortableContainer) return;

        currentMenuId = sortableContainer.dataset.menuId;

        // Inicializar modal
        itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
        itemForm = document.getElementById('itemForm');

        // Inicializar Sortable.js
        initSortable(sortableContainer);

        // Event listeners
        bindEvents();

        console.log('Navigation Editor initialized');
    }

    /**
     * Inicializar Sortable (drag & drop)
     */
    function initSortable(container) {
        sortableInstance = Sortable.create(container, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            dragClass: 'sortable-drag',
            onEnd: handleReorder
        });
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Botón agregar item
        document.getElementById('addItemBtn')?.addEventListener('click', () => {
            openModal(false);
        });

        // Botones de editar
        document.querySelectorAll('.edit-item-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const itemId = e.currentTarget.dataset.id;
                openModal(true, itemId);
            });
        });

        // Botones de eliminar
        document.querySelectorAll('.delete-item-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const itemId = e.currentTarget.dataset.id;
                deleteItem(itemId);
            });
        });

        // Botones de toggle visible
        document.querySelectorAll('.toggle-visible-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const itemId = e.currentTarget.dataset.id;
                toggleVisible(itemId, e.currentTarget);
            });
        });

        // Form submit
        itemForm?.addEventListener('submit', handleFormSubmit);

        // Radio buttons para URL/Route
        document.querySelectorAll('input[name="link_type"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                if (e.target.value === 'route') {
                    document.getElementById('item_route').disabled = false;
                    document.getElementById('item_url').disabled = true;
                    document.getElementById('item_url').value = '';
                } else {
                    document.getElementById('item_route').disabled = true;
                    document.getElementById('item_route').value = '';
                    document.getElementById('item_url').disabled = false;
                }
            });
        });

        // Preview de ícono
        document.getElementById('item_icon')?.addEventListener('input', (e) => {
            const preview = document.getElementById('icon_preview');
            if (preview) {
                preview.className = e.target.value || 'fas fa-link';
            }
        });
    }

    /**
     * Abrir modal para agregar/editar
     */
    async function openModal(editMode, itemId = null) {
        isEditMode = editMode;

        // Reset form
        itemForm.reset();
        document.getElementById('item_id').value = '';

        // Actualizar título
        document.getElementById('itemModalTitle').textContent = editMode ? 'Editar Item' : 'Agregar Item';

        if (editMode && itemId) {
            // Cargar datos del item
            try {
                showLoading('Cargando...');
                const response = await fetch(`${BASE_URL}?route=admin/navigation/get-item&id=${itemId}`);
                const data = await response.json();

                if (data.success) {
                    populateForm(data.item);
                } else {
                    showToast('Error', data.message || 'No se pudo cargar el item', 'error');
                }
            } catch (error) {
                console.error('Error loading item:', error);
                showToast('Error', 'Error al cargar el item', 'error');
            } finally {
                hideLoading();
            }
        }

        // Mostrar modal
        itemModal.show();
    }

    /**
     * Poblar formulario con datos del item
     */
    function populateForm(item) {
        document.getElementById('item_id').value = item.id;
        document.getElementById('item_label').value = item.label;
        document.getElementById('item_icon').value = item.icon || '';
        document.getElementById('item_target').value = item.target || '_self';
        document.getElementById('item_css_class').value = item.css_class || '';
        document.getElementById('item_visible').checked = item.visible;
        document.getElementById('item_auth_required').checked = item.auth_required;
        document.getElementById('item_role').value = item.role_required || '';
        document.getElementById('item_parent').value = item.parent_id || '';

        // URL o Route
        if (item.route) {
            document.getElementById('link_type_route').checked = true;
            document.getElementById('item_route').value = item.route;
            document.getElementById('item_route').disabled = false;
            document.getElementById('item_url').disabled = true;
        } else if (item.url) {
            document.getElementById('link_type_url').checked = true;
            document.getElementById('item_url').value = item.url;
            document.getElementById('item_url').disabled = false;
            document.getElementById('item_route').disabled = true;
        }

        // Preview ícono
        document.getElementById('icon_preview').className = item.icon || 'fas fa-link';
    }

    /**
     * Handle form submit
     */
    async function handleFormSubmit(e) {
        e.preventDefault();

        const formData = new FormData(itemForm);
        const data = Object.fromEntries(formData);

        // Convertir checkboxes a boolean
        data.visible = document.getElementById('item_visible').checked;
        data.auth_required = document.getElementById('item_auth_required').checked;

        // Validación básica
        if (!data.label) {
            showToast('Error', 'El texto del enlace es requerido', 'error');
            return;
        }

        if (!data.route && !data.url) {
            showToast('Error', 'Debe especificar una ruta o URL', 'error');
            return;
        }

        const endpoint = isEditMode ? 'update-item' : 'add-item';

        try {
            showLoading('Guardando...');

            const response = await fetch(`${BASE_URL}?route=admin/navigation/${endpoint}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (result.success) {
                showToast('Éxito', result.message || 'Item guardado correctamente', 'success');
                itemModal.hide();

                // Recargar página
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast('Error', result.message || 'Error al guardar', 'error');
            }
        } catch (error) {
            console.error('Error saving item:', error);
            showToast('Error', 'Error al guardar el item', 'error');
        } finally {
            hideLoading();
        }
    }

    /**
     * Eliminar item
     */
    async function deleteItem(itemId) {
        if (!confirm('¿Estás seguro de eliminar este item? Esta acción no se puede deshacer.')) {
            return;
        }

        try {
            showLoading('Eliminando...');

            const response = await fetch(`${BASE_URL}?route=admin/navigation/delete-item`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: itemId,
                    csrf_token: CSRF_TOKEN
                })
            });

            const result = await response.json();

            if (result.success) {
                showToast('Éxito', 'Item eliminado correctamente', 'success');

                // Recargar página
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast('Error', result.message || 'Error al eliminar', 'error');
            }
        } catch (error) {
            console.error('Error deleting item:', error);
            showToast('Error', 'Error al eliminar el item', 'error');
        } finally {
            hideLoading();
        }
    }

    /**
     * Toggle visibilidad de item
     */
    async function toggleVisible(itemId, button) {
        try {
            const response = await fetch(`${BASE_URL}?route=admin/navigation/toggle-visible`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: itemId,
                    csrf_token: CSRF_TOKEN
                })
            });

            const result = await response.json();

            if (result.success) {
                // Actualizar botón
                const icon = button.querySelector('i');
                if (result.visible) {
                    button.classList.remove('btn-outline-secondary');
                    button.classList.add('btn-outline-success');
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                    button.title = 'Ocultar';
                } else {
                    button.classList.remove('btn-outline-success');
                    button.classList.add('btn-outline-secondary');
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                    button.title = 'Mostrar';
                }

                // Actualizar card
                const menuItem = button.closest('.menu-item');
                if (result.visible) {
                    menuItem.classList.remove('item-hidden');
                } else {
                    menuItem.classList.add('item-hidden');
                }

                showToast('Éxito', result.message, 'success');
            } else {
                showToast('Error', result.message || 'Error al cambiar visibilidad', 'error');
            }
        } catch (error) {
            console.error('Error toggling visibility:', error);
            showToast('Error', 'Error al cambiar visibilidad', 'error');
        }
    }

    /**
     * Handle reorden (drag & drop)
     */
    async function handleReorder(evt) {
        // Obtener nuevo orden
        const items = Array.from(document.querySelectorAll('.menu-item')).map(el => el.dataset.id);

        try {
            showLoading('Reordenando...');

            const response = await fetch(`${BASE_URL}?route=admin/navigation/reorder`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    menu_id: currentMenuId,
                    items: items,
                    csrf_token: CSRF_TOKEN
                })
            });

            const result = await response.json();

            if (result.success) {
                showToast('Éxito', 'Items reordenados correctamente', 'success');
            } else {
                showToast('Error', result.message || 'Error al reordenar', 'error');
                // Recargar para restaurar orden original
                window.location.reload();
            }
        } catch (error) {
            console.error('Error reordering items:', error);
            showToast('Error', 'Error al reordenar items', 'error');
            window.location.reload();
        } finally {
            hideLoading();
        }
    }

    /**
     * Utilidades de UI
     */
    function showToast(title, message, type = 'info') {
        // Si existe sistema de toast, usarlo
        if (typeof window.showToast === 'function') {
            window.showToast(title, message, type);
            return;
        }

        // Fallback a alert
        alert(`${title}: ${message}`);
    }

    function showLoading(message = 'Cargando...') {
        // Implementar loading overlay si no existe
        let overlay = document.getElementById('loading-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
            `;
            overlay.innerHTML = `
                <div style="background: white; padding: 20px; border-radius: 8px;">
                    <div class="spinner-border text-primary me-2"></div>
                    <span>${message}</span>
                </div>
            `;
            document.body.appendChild(overlay);
        }
        overlay.style.display = 'flex';
    }

    function hideLoading() {
        const overlay = document.getElementById('loading-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
