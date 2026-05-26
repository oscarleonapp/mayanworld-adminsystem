/**
 * MediaPicker Class
 * Selector reutilizable de medios para formularios
 *
 * @version 1.0.0
 * @date 2025-11-05
 */

class MediaPicker {
    constructor(options = {}) {
        this.options = {
            filter: 'all', // 'images', 'documents', 'all'
            onSelect: null,
            ...options
        };

        this.pickerWindow = null;
        this.callback = null;
    }

    /**
     * Abrir selector de medios
     *
     * @param {Function} callback Función a ejecutar con la media seleccionada
     */
    open(callback) {
        this.callback = callback;

        const width = 800;
        const height = 600;
        const left = (screen.width - width) / 2;
        const top = (screen.height - height) / 2;

        const filter = this.options.filter;
        const url = `${BASE_URL}?route=admin/media/picker&filter=${filter}`;

        this.pickerWindow = window.open(
            url,
            'MediaPicker',
            `width=${width},height=${height},left=${left},top=${top},scrollbars=yes,resizable=yes`
        );

        // Escuchar mensaje de la ventana hija
        window.addEventListener('message', this.handleMessage.bind(this));
    }

    /**
     * Manejar mensaje de la ventana picker
     */
    handleMessage(event) {
        // Validar origen si es necesario
        if (event.data.type === 'media-selected') {
            const media = event.data.media;

            if (this.callback) {
                this.callback(media);
            }

            if (this.options.onSelect) {
                this.options.onSelect(media);
            }

            // Limpiar
            this.callback = null;
        }

        if (event.data.type === 'open-upload') {
            // Abrir modal de upload en ventana principal
            if (typeof uploadBtn !== 'undefined') {
                uploadBtn.click();
            }
        }
    }

    /**
     * Insertar imagen en un input
     *
     * @param {string} inputId ID del input donde insertar la URL
     */
    insertIntoInput(inputId) {
        this.open((media) => {
            const input = document.getElementById(inputId);
            if (input) {
                input.value = media.url;

                // Trigger change event
                input.dispatchEvent(new Event('change'));

                // Si hay preview, actualizarlo
                const preview = document.getElementById(inputId + '_preview');
                if (preview && media.url.match(/\.(jpg|jpeg|png|gif|webp)$/i)) {
                    preview.innerHTML = `<img src="${media.url}" style="max-width: 200px; max-height: 200px;">`;
                }
            }
        });
    }

    /**
     * Insertar imagen en TinyMCE
     *
     * @param {Function} callback Callback de TinyMCE
     */
    insertIntoTinyMCE(callback) {
        this.open((media) => {
            callback(media.url, {
                alt: media.alt_text || '',
                title: media.title || ''
            });
        });
    }
}

// Exponer globalmente
window.MediaPicker = MediaPicker;

/**
 * Helper: Agregar botón de media picker a un input
 *
 * @param {string} inputId ID del input
 * @param {Object} options Opciones del picker
 */
function addMediaPickerButton(inputId, options = {}) {
    const input = document.getElementById(inputId);
    if (!input) return;

    // Crear botón
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-outline-secondary';
    btn.innerHTML = '<i class="fas fa-images"></i> Seleccionar';

    // Crear preview
    const preview = document.createElement('div');
    preview.id = inputId + '_preview';
    preview.className = 'mt-2';

    // Insertar después del input
    input.parentNode.insertBefore(btn, input.nextSibling);
    input.parentNode.insertBefore(preview, btn.nextSibling);

    // Click en botón
    btn.addEventListener('click', () => {
        const picker = new MediaPicker(options);
        picker.insertIntoInput(inputId);
    });

    // Si ya hay valor, mostrar preview
    if (input.value && input.value.match(/\.(jpg|jpeg|png|gif|webp)$/i)) {
        preview.innerHTML = `<img src="${input.value}" style="max-width: 200px; max-height: 200px;">`;
    }
}

/**
 * Configuración para TinyMCE
 *
 * @returns {Object} Configuración de file_picker_callback
 */
function getMediaPickerForTinyMCE() {
    return function(callback, value, meta) {
        if (meta.filetype === 'image') {
            const picker = new MediaPicker({ filter: 'images' });
            picker.insertIntoTinyMCE(callback);
        } else if (meta.filetype === 'file') {
            const picker = new MediaPicker({ filter: 'documents' });
            picker.insertIntoTinyMCE(callback);
        }
    };
}

// Exponer funciones helper
window.addMediaPickerButton = addMediaPickerButton;
window.getMediaPickerForTinyMCE = getMediaPickerForTinyMCE;
