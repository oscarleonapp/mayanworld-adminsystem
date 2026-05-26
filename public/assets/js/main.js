/**
 * Travel Agency MVP - Main JavaScript
 * Enhanced functionality and user experience
 */

// Global application object
const TravelApp = {
    config: {
        baseUrl: window.location.origin,
        apiEndpoint: '/api',
        version: '1.0.0'
    },
    
    // Initialize the application
    init() {
        console.log('🚀 Travel Agency MVP initialized');
        
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                this.setupEventListeners();
                this.initializeComponents();
            });
        } else {
            this.setupEventListeners();
            this.initializeComponents();
        }
    },
    
    // Setup global event listeners
    setupEventListeners() {
        // Global click handler for smooth animations
        document.addEventListener('click', this.handleGlobalClick.bind(this));
        
        // Form validation enhancement
        document.addEventListener('submit', this.handleFormSubmission.bind(this));
        
        // Lazy loading for images
        this.initLazyLoading();
        
        // Keyboard navigation improvements
        this.enhanceKeyboardNavigation();
        
        // Search functionality
        this.initializeSearch();
    },
    
    // Initialize various components
    initializeComponents() {
        this.initTooltips();
        this.initCarousels();
        this.initModals();
        this.initDatePickers();
        this.initPriceCalculators();
        this.initAOS(); // Animate On Scroll
        this.initNotifications();
    },
    
    // Global click handler for animations and effects
    handleGlobalClick(e) {
        // Add ripple effect to buttons
        if (e.target.classList.contains('btn')) {
            this.addRippleEffect(e.target, e);
        }

        // Smooth scroll for anchor links
        if (e.target.matches('a[href^="#"]')) {
            const href = e.target.getAttribute('href');
            // Only process if href has content after # (not just "#")
            if (href && href.length > 1) {
                e.preventDefault();
                this.smoothScrollTo(href);
            }
        }
    },
    
    // Add ripple effect to buttons
    addRippleEffect(element, event) {
        const ripple = document.createElement('span');
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = event.clientX - rect.left - size / 2;
        const y = event.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            left: ${x}px;
            top: ${y}px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            pointer-events: none;
            animation: ripple 0.6s ease-out;
        `;
        
        // Add ripple styles if not exists
        if (!document.getElementById('ripple-styles')) {
            const style = document.createElement('style');
            style.id = 'ripple-styles';
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(2);
                        opacity: 0;
                    }
                }
                .btn {
                    position: relative;
                    overflow: hidden;
                }
            `;
            document.head.appendChild(style);
        }
        
        element.style.position = 'relative';
        element.style.overflow = 'hidden';
        element.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
    },
    
    // Smooth scroll to target
    smoothScrollTo(target) {
        // Validate target selector
        if (!target || target === '#' || target.length <= 1) {
            return;
        }

        try {
            const element = document.querySelector(target);
            if (element) {
                const headerOffset = 80; // Account for fixed header
                const elementPosition = element.offsetTop;
                const offsetPosition = elementPosition - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        } catch (error) {
            console.warn('Invalid selector for smooth scroll:', target);
        }
    },
    
    // Enhanced form submission
    handleFormSubmission(e) {
        const form = e.target;
        
        // Skip if form has novalidate
        if (form.hasAttribute('novalidate')) return;
        
        // Add loading state to submit buttons
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            const originalContent = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Procesando...';
            submitBtn.disabled = true;
            
            // Reset button after 5 seconds (fallback)
            setTimeout(() => {
                if (submitBtn.disabled) {
                    submitBtn.innerHTML = originalContent;
                    submitBtn.disabled = false;
                }
            }, 5000);
        }
        
        // Real-time validation feedback
        this.addValidationFeedback(form);
    },
    
    // Add validation feedback to forms
    addValidationFeedback(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => {
                this.validateField(input);
            });
            
            input.addEventListener('input', () => {
                if (input.classList.contains('is-invalid')) {
                    this.validateField(input);
                }
            });
        });
    },
    
    // Validate individual field
    validateField(field) {
        const isValid = field.checkValidity();
        
        // Remove existing feedback
        field.classList.remove('is-valid', 'is-invalid');
        const feedback = field.parentNode.querySelector('.invalid-feedback');
        if (feedback) feedback.remove();
        
        // Add appropriate class and feedback
        if (isValid) {
            field.classList.add('is-valid');
        } else {
            field.classList.add('is-invalid');
            
            // Add custom feedback message
            const feedbackElement = document.createElement('div');
            feedbackElement.className = 'invalid-feedback';
            feedbackElement.textContent = field.validationMessage;
            field.parentNode.appendChild(feedbackElement);
        }
    },
    
    // Initialize lazy loading for images
    initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        img.classList.add('lazy-loaded');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    },
    
    // Enhance keyboard navigation
    enhanceKeyboardNavigation() {
        // Escape key to close modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const openModals = document.querySelectorAll('.modal.show');
                openModals.forEach(modal => {
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) modalInstance.hide();
                });
            }
        });
        
        // Tab navigation improvements
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
        });
        
        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
    },
    
    // Initialize search functionality
    initializeSearch() {
        const searchInputs = document.querySelectorAll('[data-search]');
        
        searchInputs.forEach(input => {
            const searchTarget = input.dataset.search;
            const searchItems = document.querySelectorAll(`[data-search-item="${searchTarget}"]`);
            
            let searchTimeout;
            input.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.performSearch(e.target.value, searchItems);
                }, 300);
            });
        });
    },
    
    // Perform search filtering
    performSearch(query, items) {
        const searchQuery = query.toLowerCase().trim();
        
        items.forEach(item => {
            const searchText = item.textContent.toLowerCase();
            const isMatch = searchText.includes(searchQuery);
            
            if (isMatch || searchQuery === '') {
                item.style.display = '';
                item.classList.remove('search-hidden');
            } else {
                item.style.display = 'none';
                item.classList.add('search-hidden');
            }
        });
        
        // Show no results message if needed
        this.toggleNoResultsMessage(items, query);
    },
    
    // Toggle no results message
    toggleNoResultsMessage(items, query) {
        const visibleItems = Array.from(items).filter(item => !item.classList.contains('search-hidden'));
        const container = items[0]?.parentElement;
        
        if (!container) return;
        
        let noResultsMsg = container.querySelector('.no-search-results');
        
        if (visibleItems.length === 0 && query.trim() !== '') {
            if (!noResultsMsg) {
                noResultsMsg = document.createElement('div');
                noResultsMsg.className = 'no-search-results text-center py-4 text-muted';
                noResultsMsg.innerHTML = `
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <h5>No se encontraron resultados</h5>
                    <p>Intenta con diferentes términos de búsqueda</p>
                `;
                container.appendChild(noResultsMsg);
            }
        } else if (noResultsMsg) {
            noResultsMsg.remove();
        }
    },
    
    // Initialize tooltips
    initTooltips() {
        const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipElements.forEach(element => {
            new bootstrap.Tooltip(element, {
                delay: { show: 500, hide: 100 }
            });
        });
    },
    
    // Initialize carousels with custom settings
    initCarousels() {
        const carousels = document.querySelectorAll('.carousel');
        carousels.forEach(carousel => {
            new bootstrap.Carousel(carousel, {
                interval: 5000,
                pause: 'hover',
                wrap: true
            });
        });
    },
    
    // Initialize modal enhancements
    initModals() {
        document.addEventListener('show.bs.modal', (e) => {
            const modalEl = e.target;
            if (modalEl && modalEl.parentElement !== document.body) {
                document.body.appendChild(modalEl);
            }
            document.body.classList.add('modal-open-blur');
        });
        
        document.addEventListener('hidden.bs.modal', (e) => {
            document.body.classList.remove('modal-open-blur');
        });
    },
    
    // Initialize date pickers
    initDatePickers() {
        const dateInputs = document.querySelectorAll('input[type="date"]');
        
        dateInputs.forEach(input => {
            // Set minimum date to today
            if (input.hasAttribute('data-min-today')) {
                input.min = new Date().toISOString().split('T')[0];
            }
            
            // Auto-calculate return dates
            if (input.name === 'fecha_salida') {
                input.addEventListener('change', (e) => {
                    const returnInput = document.querySelector('input[name="fecha_regreso"]');
                    if (returnInput && !returnInput.value) {
                        const departureDate = new Date(e.target.value);
                        const returnDate = new Date(departureDate);
                        returnDate.setDate(returnDate.getDate() + 7); // Default 7 days
                        returnInput.value = returnDate.toISOString().split('T')[0];
                        returnInput.min = e.target.value;
                    }
                });
            }
        });
    },
    
    // Initialize price calculators
    initPriceCalculators() {
        const priceCalculators = document.querySelectorAll('[data-price-calculator]');
        
        priceCalculators.forEach(calculator => {
            const basePrice = parseFloat(calculator.dataset.basePrice || 0);
            const discountPrice = parseFloat(calculator.dataset.discountPrice || basePrice);
            const peopleInput = calculator.querySelector('input[name="numero_personas"], select[name="numero_personas"]');
            const totalDisplay = calculator.querySelector('[data-total-display]');
            const peopleDisplay = calculator.querySelector('[data-people-display]');
            
            if (peopleInput && totalDisplay) {
                peopleInput.addEventListener('change', () => {
                    const people = parseInt(peopleInput.value) || 1;
                    const total = (discountPrice || basePrice) * people;
                    
                    totalDisplay.textContent = this.formatPrice(total);
                    
                    if (peopleDisplay) {
                        peopleDisplay.textContent = people;
                    }
                });
                
                // Trigger initial calculation
                peopleInput.dispatchEvent(new Event('change'));
            }
        });
    },
    
    // Initialize Animate On Scroll
    initAOS() {
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)');
        const revealTargets = document.querySelectorAll('.reveal-on-scroll');
        const animatedElements = document.querySelectorAll('[data-animate]');

        if (prefersReduced.matches || !('IntersectionObserver' in window)) {
            revealTargets.forEach(el => el.classList.add('is-visible'));
            animatedElements.forEach(el => el.classList.add('animate-in'));
            return;
        }

        const animationObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;
                if (entry.target.dataset.animate !== undefined) {
                    entry.target.classList.add('animate-in');
                }
                entry.target.classList.add('is-visible');
                animationObserver.unobserve(entry.target);
            });
        }, {
            threshold: 0.2,
            rootMargin: '0px 0px -10% 0px'
        });

        animatedElements.forEach(element => {
            animationObserver.observe(element);
        });
        revealTargets.forEach(element => {
            animationObserver.observe(element);
        });
    },
    
    // Initialize notification system
    initNotifications() {
        // Create notification container if it doesn't exist
        if (!document.getElementById('notification-container')) {
            const container = document.createElement('div');
            container.id = 'notification-container';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
    },
    
    // Utility function to format prices
    formatPrice(amount) {
        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    },
    
    // Show notification
    showNotification(message, type = 'info', duration = 5000) {
        const container = document.getElementById('notification-container');
        const notification = document.createElement('div');
        
        const types = {
            success: 'alert-success',
            error: 'alert-danger',
            warning: 'alert-warning',
            info: 'alert-info'
        };
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        notification.className = `alert ${types[type]} alert-dismissible fade show`;
        notification.style.minWidth = '300px';
        notification.innerHTML = `
            <i class="fas ${icons[type]} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        container.appendChild(notification);
        
        // Auto-remove after duration
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, duration);
        
        return notification;
    },
    
    // Utility function for AJAX requests
    async request(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            }
            
            return await response.text();
        } catch (error) {
            console.error('Request failed:', error);
            this.showNotification('Error de conexión. Por favor intenta nuevamente.', 'error');
            throw error;
        }
    },
    
    // Debounce utility function
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },
    
    // Throttle utility function
    throttle(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            if (!timeout) {
                func.apply(this, args);
                timeout = setTimeout(() => {
                    timeout = null;
                }, wait);
            }
        };
    },
    
    // Local storage utilities
    storage: {
        set(key, value) {
            try {
                localStorage.setItem(`travel_app_${key}`, JSON.stringify(value));
            } catch (e) {
                console.error('LocalStorage error:', e);
            }
        },
        
        get(key, defaultValue = null) {
            try {
                const item = localStorage.getItem(`travel_app_${key}`);
                return item ? JSON.parse(item) : defaultValue;
            } catch (e) {
                console.error('LocalStorage error:', e);
                return defaultValue;
            }
        },
        
        remove(key) {
            try {
                localStorage.removeItem(`travel_app_${key}`);
            } catch (e) {
                console.error('LocalStorage error:', e);
            }
        }
    }
};

// Initialize the application
TravelApp.init();

// Expose TravelApp globally for debugging and external scripts
window.TravelApp = TravelApp;

// Additional utility functions for common tasks
window.TravelUtils = {
    // Format date for display
    formatDate(date, locale = 'es-ES') {
        return new Date(date).toLocaleDateString(locale, {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    },
    
    // Calculate days between dates
    daysBetween(date1, date2) {
        const oneDay = 24 * 60 * 60 * 1000;
        const firstDate = new Date(date1);
        const secondDate = new Date(date2);
        return Math.round(Math.abs((firstDate - secondDate) / oneDay));
    },
    
    // Validate email format
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },
    
    // Validate Mexican phone number
    isValidMexicanPhone(phone) {
        const phoneRegex = /^(\+52|52)?[1-9]\d{9}$/;
        return phoneRegex.test(phone.replace(/\s|-|\(|\)/g, ''));
    },
    
    // Generate random ID
    generateId(prefix = 'id') {
        return `${prefix}_${Math.random().toString(36).substr(2, 9)}`;
    },
    
    // Copy text to clipboard
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            TravelApp.showNotification('Copiado al portapapeles', 'success', 2000);
            return true;
        } catch (error) {
            console.error('Failed to copy:', error);
            TravelApp.showNotification('Error al copiar', 'error');
            return false;
        }
    },
    
    // Share content using Web Share API
    async share(data) {
        if (navigator.share) {
            try {
                await navigator.share(data);
                return true;
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error('Share failed:', error);
                }
                return false;
            }
        } else {
            // Fallback: copy URL to clipboard
            if (data.url) {
                return this.copyToClipboard(data.url);
            }
            return false;
        }
    }
};

// Performance monitoring
window.addEventListener('load', () => {
    if ('performance' in window) {
        const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
        console.log(`🚀 Page loaded in ${loadTime}ms`);
        
        // Send to analytics if configured
        if (window.gtag) {
            gtag('event', 'page_load_time', {
                event_category: 'Performance',
                event_label: 'Load Time',
                value: loadTime
            });
        }
    }
});

// Core Web Vitals (LCP, FID, CLS) lightweight capture
(function monitorWebVitals() {
    if (!('PerformanceObserver' in window)) return;

    // Largest Contentful Paint
    try {
        let lcpValue = 0;
        const poLCP = new PerformanceObserver((list) => {
            const entries = list.getEntries();
            const lastEntry = entries[entries.length - 1];
            if (lastEntry) lcpValue = lastEntry.renderTime || lastEntry.loadTime || 0;
        });
        poLCP.observe({ type: 'largest-contentful-paint', buffered: true });
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                if (lcpValue) {
                    const lcpMs = Math.round(lcpValue);
                    console.log(`📏 LCP: ${lcpMs} ms`);
                    if (window.gtag) { gtag('event', 'web_vital', { name: 'LCP', value: lcpMs }); }
                }
            }
        });
    } catch (e) { /* noop */ }

    // Cumulative Layout Shift
    try {
        let clsValue = 0;
        const poCLS = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                // Ignore shifts after user input
                if (!entry.hadRecentInput) {
                    clsValue += entry.value;
                }
            }
        });
        poCLS.observe({ type: 'layout-shift', buffered: true });
        window.addEventListener('beforeunload', () => {
            const cls = Number(clsValue.toFixed(3));
            console.log(`📏 CLS: ${cls}`);
            if (window.gtag) { gtag('event', 'web_vital', { name: 'CLS', value: cls }); }
        });
    } catch (e) { /* noop */ }

    // First Input Delay
    try {
        const poFID = new PerformanceObserver((list) => {
            for (const entry of list.getEntries()) {
                const fid = Math.round(entry.processingStart - entry.startTime);
                console.log(`📏 FID: ${fid} ms`);
                if (window.gtag) { gtag('event', 'web_vital', { name: 'FID', value: fid }); }
            }
        });
        poFID.observe({ type: 'first-input', buffered: true });
    } catch (e) { /* noop */ }
})();
