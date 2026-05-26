/**
 * JavaScript para la página de tours
 */

document.addEventListener('DOMContentLoaded', function() {

    // Auto-submit del formulario cuando cambian los filtros principales
    const filterForm = document.getElementById('filterForm');
    const autoSubmitFields = ['category', 'difficulty', 'duration', 'sort'];

    if (filterForm) {
        autoSubmitFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                field.addEventListener('change', function() {
                    // Pequeño delay para mejorar UX
                    setTimeout(() => {
                        filterForm.submit();
                    }, 300);
                });
            }
        });

        // Para checkboxes también
        const checkboxes = filterForm.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                setTimeout(() => {
                    filterForm.submit();
                }, 300);
            });
        });
    }

    // Validación de precios
    const minPriceInput = document.getElementById('min_price');
    const maxPriceInput = document.getElementById('max_price');

    if (minPriceInput && maxPriceInput) {
        minPriceInput.addEventListener('change', function() {
            const min = parseFloat(this.value) || 0;
            const max = parseFloat(maxPriceInput.value) || 0;

            if (max > 0 && min > max) {
                alert('El precio mínimo no puede ser mayor al precio máximo');
                this.value = '';
            }
        });

        maxPriceInput.addEventListener('change', function() {
            const min = parseFloat(minPriceInput.value) || 0;
            const max = parseFloat(this.value) || 0;

            if (min > 0 && max > 0 && min > max) {
                alert('El precio máximo no puede ser menor al precio mínimo');
                this.value = '';
            }
        });
    }

    // Animación suave para las tarjetas
    const productCards = document.querySelectorAll('.product-card');

    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '0';
                entry.target.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    entry.target.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, 100);

                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    productCards.forEach(card => {
        observer.observe(card);
    });

    // Contador de filtros activos
    function updateFilterCount() {
        const form = document.getElementById('filterForm');
        if (!form) return;

        let count = 0;

        // Contar inputs de texto no vacíos (excepto route)
        const textInputs = form.querySelectorAll('input[type="text"], input[type="number"]');
        textInputs.forEach(input => {
            if (input.value.trim() !== '') count++;
        });

        // Contar selects con valores
        const selects = form.querySelectorAll('select');
        selects.forEach(select => {
            if (select.value !== '') count++;
        });

        // Contar checkboxes marcados
        const checkboxes = form.querySelectorAll('input[type="checkbox"]:checked');
        count += checkboxes.length;

        // Actualizar badge si existe
        const badge = document.querySelector('.btn-primary .badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
    }

    // Actualizar contador al cargar
    updateFilterCount();

    // Scroll suave al hacer clic en "Ver más"
    document.querySelectorAll('a[href*="tour/"]').forEach(link => {
        link.addEventListener('click', function(e) {
            // Agregar clase de loading al botón
            this.classList.add('disabled');
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Cargando...';
        });
    });

    // Tooltip de Bootstrap para badges si están disponibles
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

// Función para limpiar todos los filtros
function clearAllFilters() {
    const baseUrl = document.getElementById('filterForm')?.action || '';
    window.location.href = baseUrl + '?route=tours';
}

// Función para aplicar filtro rápido
function applyQuickFilter(filterName, filterValue) {
    const form = document.getElementById('filterForm');
    if (!form) return;

    const field = form.querySelector(`[name="${filterName}"]`);
    if (field) {
        if (field.type === 'checkbox') {
            field.checked = filterValue === '1' || filterValue === true;
        } else {
            field.value = filterValue;
        }
        form.submit();
    }
}
