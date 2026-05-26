/**
 * Tour Comparator - Componente JavaScript
 * Permite comparar hasta 3 tours lado a lado
 */

class TourComparator {
    constructor() {
        this.selectedTours = this.loadFromStorage();
        this.maxTours = 3;
        this.init();
    }

    init() {
        // Crear barra flotante si hay tours seleccionados
        if (this.selectedTours.length > 0) {
            this.createFloatingBar();
        }

        // Agregar checkboxes a las tarjetas de tour
        this.addCheckboxesToProducts();

        // Event listeners
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('compare-checkbox')) {
                this.handleCheckboxChange(e.target);
            }
        });
    }

    loadFromStorage() {
        const stored = localStorage.getItem('tourComparator');
        return stored ? JSON.parse(stored) : [];
    }

    saveToStorage() {
        localStorage.setItem('tourComparator', JSON.stringify(this.selectedTours));
    }

    addCheckboxesToProducts() {
        // Buscar todas las tarjetas de tour
        const productCards = document.querySelectorAll('.product-card, [data-product-id]');

        productCards.forEach(card => {
            const productId = card.dataset.productId || card.querySelector('[data-product-id]')?.dataset.productId;

            if (!productId) return;

            // Verificar si ya tiene checkbox
            if (card.querySelector('.compare-checkbox')) return;

            // Crear checkbox
            const checkboxHtml = `
                <div class="compare-checkbox-wrapper">
                    <label class="compare-checkbox-label">
                        <input type="checkbox"
                               class="compare-checkbox"
                               data-product-id="${productId}"
                               ${this.selectedTours.includes(productId) ? 'checked' : ''}>
                        <span class="compare-checkbox-text">
                            <i class="fas fa-exchange-alt me-1"></i>
                            Comparar
                        </span>
                    </label>
                </div>
            `;

            // Insertar en la tarjeta (buscar el mejor lugar)
            const cardBody = card.querySelector('.card-body');
            const cardFooter = card.querySelector('.card-footer');

            if (cardFooter) {
                cardFooter.insertAdjacentHTML('afterbegin', checkboxHtml);
            } else if (cardBody) {
                cardBody.insertAdjacentHTML('beforeend', checkboxHtml);
            } else {
                card.insertAdjacentHTML('beforeend', checkboxHtml);
            }
        });
    }

    handleCheckboxChange(checkbox) {
        const productId = checkbox.dataset.productId;
        const isChecked = checkbox.checked;

        if (isChecked) {
            // Agregar
            if (this.selectedTours.length >= this.maxTours) {
                checkbox.checked = false;
                this.showToast(`Solo puedes comparar hasta ${this.maxTours} tours a la vez`, 'warning');
                return;
            }

            this.selectedTours.push(productId);
            this.showToast('Tour agregado para comparar', 'success');
        } else {
            // Remover
            this.selectedTours = this.selectedTours.filter(id => id !== productId);
            this.showToast('Tour removido de la comparación', 'info');
        }

        this.saveToStorage();
        this.updateFloatingBar();
    }

    createFloatingBar() {
        // Verificar si ya existe
        if (document.getElementById('compareFloatingBar')) {
            this.updateFloatingBar();
            return;
        }

        const bar = document.createElement('div');
        bar.id = 'compareFloatingBar';
        bar.className = 'compare-floating-bar';
        bar.innerHTML = `
            <div class="compare-bar-content">
                <div class="compare-bar-left">
                    <i class="fas fa-exchange-alt me-2"></i>
                    <span class="compare-bar-count">${this.selectedTours.length}</span>
                    <span class="compare-bar-text">tour${this.selectedTours.length !== 1 ? 's' : ''} seleccionado${this.selectedTours.length !== 1 ? 's' : ''}</span>
                </div>
                <div class="compare-bar-right">
                    <button class="btn btn-sm btn-outline-light me-2" onclick="tourComparator.clearAll()">
                        <i class="fas fa-times me-1"></i>Limpiar
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="tourComparator.goToComparison()">
                        <i class="fas fa-eye me-1"></i>Comparar ahora
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(bar);

        // Animación de entrada
        setTimeout(() => bar.classList.add('show'), 100);
    }

    updateFloatingBar() {
        const bar = document.getElementById('compareFloatingBar');

        if (this.selectedTours.length === 0) {
            // Ocultar barra
            if (bar) {
                bar.classList.remove('show');
                setTimeout(() => bar.remove(), 300);
            }
            return;
        }

        if (!bar) {
            this.createFloatingBar();
            return;
        }

        // Actualizar contador
        const countEl = bar.querySelector('.compare-bar-count');
        const textEl = bar.querySelector('.compare-bar-text');

        if (countEl) countEl.textContent = this.selectedTours.length;
        if (textEl) textEl.textContent = `tour${this.selectedTours.length !== 1 ? 's' : ''} seleccionado${this.selectedTours.length !== 1 ? 's' : ''}`;
    }

    clearAll() {
        if (!confirm('¿Quitar todos los tours de la comparación?')) return;

        this.selectedTours = [];
        this.saveToStorage();

        // Desmarcar checkboxes
        document.querySelectorAll('.compare-checkbox:checked').forEach(cb => {
            cb.checked = false;
        });

        this.updateFloatingBar();
        this.showToast('Comparación limpiada', 'info');
    }

    goToComparison() {
        if (this.selectedTours.length < 2) {
            this.showToast('Selecciona al menos 2 tours para comparar', 'warning');
            return;
        }

        const baseUrl = window.location.origin + window.location.pathname;
        const url = `${baseUrl}?route=tour/compare&ids=${this.selectedTours.join(',')}`;
        window.location.href = url;
    }

    showToast(message, type = 'info') {
        // Usar sistema de toasts existente o crear uno básico
        if (typeof showNotification === 'function') {
            showNotification(message, type);
            return;
        }

        // Toast básico
        const toast = document.createElement('div');
        toast.className = `toast-notification toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#28a745' : type === 'warning' ? '#ffc107' : '#17a2b8'};
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            animation: slideInRight 0.3s ease;
        `;

        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Inicializar cuando el DOM esté listo
let tourComparator;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        tourComparator = new TourComparator();
    });
} else {
    tourComparator = new TourComparator();
}

// Estilos CSS para la barra flotante
const styles = document.createElement('style');
styles.textContent = `
    .compare-floating-bar {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem 0;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
        z-index: 1000;
        transform: translateY(100%);
        transition: transform 0.3s ease;
    }

    .compare-floating-bar.show {
        transform: translateY(0);
    }

    .compare-bar-content {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .compare-bar-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .compare-bar-count {
        background: rgba(255, 255, 255, 0.2);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-weight: 700;
        font-size: 1.1rem;
    }

    .compare-bar-text {
        font-weight: 500;
    }

    .compare-bar-right {
        display: flex;
        gap: 0.5rem;
    }

    .compare-checkbox-wrapper {
        margin-top: 0.5rem;
    }

    .compare-checkbox-label {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        padding: 0.5rem;
        border-radius: 6px;
        transition: background 0.2s ease;
        user-select: none;
    }

    .compare-checkbox-label:hover {
        background: rgba(13, 110, 253, 0.1);
    }

    .compare-checkbox {
        cursor: pointer;
    }

    .compare-checkbox-text {
        font-size: 0.9rem;
        color: #0d6efd;
        font-weight: 500;
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(20px);
        }
    }

    /* Responsive */
    @media (max-width: 767.98px) {
        .compare-bar-content {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .compare-bar-right {
            width: 100%;
        }

        .compare-bar-right button {
            flex: 1;
        }

        .compare-bar-text {
            font-size: 0.9rem;
        }
    }
`;
document.head.appendChild(styles);
