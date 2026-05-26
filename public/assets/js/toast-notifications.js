/**
 * Toast Notifications System
 * Sistema de notificaciones toast personalizado, ligero y sin dependencias
 *
 * Características:
 * - Sin dependencias (vanilla JS)
 * - 4 tipos: success, error, warning, info
 * - Posiciones configurables
 * - Auto-dismiss con progreso visual
 * - Animaciones suaves
 * - Queue management
 * - Click to dismiss
 * - Responsive
 */

class ToastNotification {
    constructor(options = {}) {
        this.options = {
            position: options.position || 'top-right', // top-right, top-left, bottom-right, bottom-left, top-center, bottom-center
            duration: options.duration || 5000, // ms
            maxToasts: options.maxToasts || 5,
            showProgress: options.showProgress !== false,
            pauseOnHover: options.pauseOnHover !== false,
            closeButton: options.closeButton !== false,
            newestOnTop: options.newestOnTop !== false
        };

        this.toasts = [];
        this.container = null;
        this.init();
    }

    init() {
        // Crear contenedor si no existe
        if (!document.getElementById('toast-container')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.className = `toast-container toast-${this.options.position}`;
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toast-container');
        }

        // Inyectar estilos si no existen
        if (!document.getElementById('toast-styles')) {
            this.injectStyles();
        }
    }

    injectStyles() {
        const styles = `
            <style id="toast-styles">
                .toast-container {
                    position: fixed;
                    z-index: 9999;
                    pointer-events: none;
                }

                .toast-container.toast-top-right {
                    top: 20px;
                    right: 20px;
                }

                .toast-container.toast-top-left {
                    top: 20px;
                    left: 20px;
                }

                .toast-container.toast-bottom-right {
                    bottom: 20px;
                    right: 20px;
                }

                .toast-container.toast-bottom-left {
                    bottom: 20px;
                    left: 20px;
                }

                .toast-container.toast-top-center {
                    top: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                }

                .toast-container.toast-bottom-center {
                    bottom: 20px;
                    left: 50%;
                    transform: translateX(-50%);
                }

                .toast {
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                    padding: 16px 20px;
                    margin-bottom: 10px;
                    min-width: 300px;
                    max-width: 500px;
                    pointer-events: auto;
                    position: relative;
                    overflow: hidden;
                    animation: toastSlideIn 0.3s ease;
                    display: flex;
                    align-items: flex-start;
                    gap: 12px;
                }

                .toast.toast-removing {
                    animation: toastSlideOut 0.3s ease forwards;
                }

                @keyframes toastSlideIn {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }

                @keyframes toastSlideOut {
                    from {
                        transform: translateX(0);
                        opacity: 1;
                    }
                    to {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                }

                .toast-icon {
                    flex-shrink: 0;
                    width: 24px;
                    height: 24px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 18px;
                }

                .toast-content {
                    flex: 1;
                }

                .toast-title {
                    font-weight: 600;
                    font-size: 14px;
                    margin-bottom: 4px;
                    color: #1a1a1a;
                }

                .toast-message {
                    font-size: 13px;
                    color: #666;
                    line-height: 1.4;
                }

                .toast-close {
                    flex-shrink: 0;
                    background: none;
                    border: none;
                    padding: 0;
                    cursor: pointer;
                    color: #999;
                    font-size: 20px;
                    line-height: 1;
                    width: 20px;
                    height: 20px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: color 0.2s;
                }

                .toast-close:hover {
                    color: #333;
                }

                .toast-progress {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    height: 3px;
                    background: currentColor;
                    opacity: 0.4;
                    animation: toastProgress linear;
                }

                @keyframes toastProgress {
                    from {
                        width: 100%;
                    }
                    to {
                        width: 0%;
                    }
                }

                .toast.toast-success {
                    border-left: 4px solid #10b981;
                    color: #10b981;
                }

                .toast.toast-error {
                    border-left: 4px solid #ef4444;
                    color: #ef4444;
                }

                .toast.toast-warning {
                    border-left: 4px solid #f59e0b;
                    color: #f59e0b;
                }

                .toast.toast-info {
                    border-left: 4px solid #3b82f6;
                    color: #3b82f6;
                }

                /* Mobile responsive */
                @media (max-width: 640px) {
                    .toast-container {
                        left: 10px !important;
                        right: 10px !important;
                        top: 10px !important;
                        transform: none !important;
                    }

                    .toast {
                        min-width: auto;
                        width: 100%;
                    }
                }
            </style>
        `;

        document.head.insertAdjacentHTML('beforeend', styles);
    }

    show(message, type = 'info', options = {}) {
        const toast = this.createToast(message, type, options);

        // Limitar número de toasts
        if (this.toasts.length >= this.options.maxToasts) {
            this.remove(this.toasts[0]);
        }

        // Agregar al DOM
        if (this.options.newestOnTop) {
            this.container.prepend(toast.element);
        } else {
            this.container.appendChild(toast.element);
        }

        this.toasts.push(toast);

        // Auto dismiss
        const duration = options.duration || this.options.duration;
        if (duration > 0) {
            toast.timeout = setTimeout(() => {
                this.remove(toast);
            }, duration);
        }

        return toast;
    }

    createToast(message, type, options) {
        const toast = {
            id: Date.now() + Math.random(),
            type: type,
            element: null,
            timeout: null,
            pauseTime: null,
            remainingTime: options.duration || this.options.duration
        };

        // Crear elemento
        const element = document.createElement('div');
        element.className = `toast toast-${type}`;
        element.dataset.toastId = toast.id;

        // Icono
        const icon = this.getIcon(type);
        const iconHtml = `<div class="toast-icon">${icon}</div>`;

        // Contenido
        let contentHtml = '<div class="toast-content">';

        if (options.title) {
            contentHtml += `<div class="toast-title">${this.escapeHtml(options.title)}</div>`;
        }

        contentHtml += `<div class="toast-message">${this.escapeHtml(message)}</div>`;
        contentHtml += '</div>';

        // Botón cerrar
        let closeHtml = '';
        if (this.options.closeButton) {
            closeHtml = '<button class="toast-close" type="button" aria-label="Close">&times;</button>';
        }

        // Progress bar
        let progressHtml = '';
        if (this.options.showProgress && toast.remainingTime > 0) {
            progressHtml = `<div class="toast-progress" style="animation-duration: ${toast.remainingTime}ms"></div>`;
        }

        element.innerHTML = iconHtml + contentHtml + closeHtml + progressHtml;

        // Event listeners
        if (this.options.closeButton) {
            element.querySelector('.toast-close').addEventListener('click', () => {
                this.remove(toast);
            });
        }

        // Click to dismiss
        element.addEventListener('click', (e) => {
            if (!e.target.closest('.toast-close')) {
                this.remove(toast);
            }
        });

        // Pause on hover
        if (this.options.pauseOnHover && toast.remainingTime > 0) {
            element.addEventListener('mouseenter', () => {
                if (toast.timeout) {
                    clearTimeout(toast.timeout);
                    toast.pauseTime = Date.now();

                    // Pausar animación de progress
                    const progress = element.querySelector('.toast-progress');
                    if (progress) {
                        progress.style.animationPlayState = 'paused';
                    }
                }
            });

            element.addEventListener('mouseleave', () => {
                if (toast.pauseTime) {
                    const elapsed = Date.now() - toast.pauseTime;
                    toast.remainingTime -= elapsed;
                    toast.pauseTime = null;

                    // Reanudar animación
                    const progress = element.querySelector('.toast-progress');
                    if (progress) {
                        progress.style.animationPlayState = 'running';
                    }

                    // Reiniciar timeout
                    toast.timeout = setTimeout(() => {
                        this.remove(toast);
                    }, toast.remainingTime);
                }
            });
        }

        toast.element = element;
        return toast;
    }

    remove(toast) {
        if (!toast || !toast.element) return;

        // Cancelar timeout
        if (toast.timeout) {
            clearTimeout(toast.timeout);
        }

        // Animación de salida
        toast.element.classList.add('toast-removing');

        setTimeout(() => {
            if (toast.element && toast.element.parentNode) {
                toast.element.parentNode.removeChild(toast.element);
            }

            // Remover del array
            const index = this.toasts.indexOf(toast);
            if (index > -1) {
                this.toasts.splice(index, 1);
            }
        }, 300);
    }

    clear() {
        this.toasts.forEach(toast => this.remove(toast));
    }

    getIcon(type) {
        const icons = {
            success: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>',
            error: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
            warning: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
            info: '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
        };

        return icons[type] || icons.info;
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Métodos de conveniencia
    success(message, options = {}) {
        return this.show(message, 'success', options);
    }

    error(message, options = {}) {
        return this.show(message, 'error', options);
    }

    warning(message, options = {}) {
        return this.show(message, 'warning', options);
    }

    info(message, options = {}) {
        return this.show(message, 'info', options);
    }
}

// Instancia global
if (typeof window !== 'undefined') {
    window.Toast = new ToastNotification();
}

// Exportar para módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ToastNotification;
}
