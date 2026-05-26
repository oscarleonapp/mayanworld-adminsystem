<!-- Widget Selector de Idioma y Moneda -->
<div class="language-currency-selector">
    <div class="dropdown-group d-flex gap-2">
        <!-- Selector de Idioma -->
        <div class="dropdown">
            <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="languageSelector" data-bs-toggle="dropdown" aria-expanded="false">
                <?php 
                $currentLang = null;
                foreach ($languages as $lang) {
                    if ($lang['code'] === $currentLanguage) {
                        $currentLang = $lang;
                        break;
                    }
                }
                echo $currentLang['flag_emoji'] ?? '🌐';
                echo ' ' . ($currentLang['name'] ?? 'Language');
                ?>
            </button>
            <ul class="dropdown-menu" aria-labelledby="languageSelector">
                <?php foreach ($languages as $language): ?>
                <li>
                    <a class="dropdown-item language-option <?= $language['code'] === $currentLanguage ? 'active' : '' ?>" 
                       href="#" 
                       data-language="<?= $language['code'] ?>">
                        <span class="flag-emoji me-2"><?= $language['flag_emoji'] ?></span>
                        <span class="language-name"><?= htmlspecialchars($language['native_name']) ?></span>
                        <small class="text-muted ms-2">(<?= strtoupper($language['code']) ?>)</small>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <!-- Selector de Moneda -->
        <div class="dropdown">
            <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" id="currencySelector" data-bs-toggle="dropdown" aria-expanded="false">
                <?php 
                $currentCurr = null;
                foreach ($currencies as $curr) {
                    if ($curr['code'] === $currentCurrency) {
                        $currentCurr = $curr;
                        break;
                    }
                }
                echo $currentCurr['symbol'] ?? '$';
                echo ' ' . ($currentCurr['code'] ?? 'USD');
                ?>
            </button>
            <ul class="dropdown-menu" aria-labelledby="currencySelector">
                <?php foreach ($currencies as $currency): ?>
                <li>
                    <a class="dropdown-item currency-option <?= $currency['code'] === $currentCurrency ? 'active' : '' ?>" 
                       href="#" 
                       data-currency="<?= $currency['code'] ?>">
                        <span class="currency-symbol me-2"><?= htmlspecialchars($currency['symbol']) ?></span>
                        <span class="currency-name"><?= htmlspecialchars($currency['name']) ?></span>
                        <small class="text-muted ms-2"><?= $currency['code'] ?></small>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<!-- CSS específico del widget -->
<style>
.language-currency-selector .dropdown-toggle {
    font-size: 0.875rem;
    padding: 0.375rem 0.75rem;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.language-currency-selector .dropdown-toggle:hover {
    background: rgba(255,255,255,0.2);
    border-color: rgba(255,255,255,0.5);
}

.language-currency-selector .dropdown-menu {
    min-width: 200px;
    border: none;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    border-radius: 10px;
    padding: 0.5rem 0;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(20px);
}

.language-currency-selector .dropdown-item {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
}

.language-currency-selector .dropdown-item:hover {
    background: rgba(var(--bs-primary-rgb), 0.1);
    color: var(--bs-primary);
}

.language-currency-selector .dropdown-item.active {
    background: rgba(var(--bs-primary-rgb), 0.2);
    color: var(--bs-primary);
    font-weight: 600;
}

.language-currency-selector .flag-emoji {
    font-size: 1.1rem;
}

.language-currency-selector .currency-symbol {
    font-weight: 600;
    color: var(--bs-success);
}

/* Loading state */
.language-currency-selector.loading .dropdown-toggle {
    opacity: 0.7;
    pointer-events: none;
}

.language-currency-selector.loading .dropdown-toggle::after {
    content: '';
    width: 12px;
    height: 12px;
    border: 2px solid transparent;
    border-top: 2px solid currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-left: 0.5rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .language-currency-selector .dropdown-group {
        flex-direction: column;
    }
    
    .language-currency-selector .dropdown-toggle {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }
    
    .language-currency-selector .dropdown-menu {
        min-width: 180px;
    }
}
</style>

<!-- JavaScript para manejo del widget -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const languageSelector = document.querySelector('.language-currency-selector');
    
    // Cambio de idioma
    document.querySelectorAll('.language-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            
            const languageCode = this.getAttribute('data-language');
            if (languageCode) {
                changeLanguage(languageCode);
            }
        });
    });
    
    // Cambio de moneda
    document.querySelectorAll('.currency-option').forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            
            const currencyCode = this.getAttribute('data-currency');
            if (currencyCode) {
                changeCurrency(currencyCode);
            }
        });
    });
    
    function changeLanguage(languageCode) {
        languageSelector.classList.add('loading');
        
        fetch('?route=i18n/set-language', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `language_code=${languageCode}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recargar la página para aplicar el nuevo idioma
                window.location.reload();
            } else {
                console.error('Error changing language:', data.message);
                showNotification('Error al cambiar idioma', 'error');
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            showNotification('Error de conexión', 'error');
        })
        .finally(() => {
            languageSelector.classList.remove('loading');
        });
    }
    
    function changeCurrency(currencyCode) {
        languageSelector.classList.add('loading');
        
        fetch('?route=i18n/set-currency', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `currency_code=${currencyCode}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar precios en la página sin recargar
                updatePagePrices(currencyCode);
                updateCurrencySelector(currencyCode);
                showNotification(data.message || 'Moneda actualizada', 'success');
            } else {
                console.error('Error changing currency:', data.message);
                showNotification('Error al cambiar moneda', 'error');
            }
        })
        .catch(error => {
            console.error('Network error:', error);
            showNotification('Error de conexión', 'error');
        })
        .finally(() => {
            languageSelector.classList.remove('loading');
        });
    }
    
    function updatePagePrices(currencyCode) {
        // Actualizar todos los precios en la página
        document.querySelectorAll('[data-price]').forEach(element => {
            const originalPrice = parseFloat(element.getAttribute('data-price'));
            const originalCurrency = element.getAttribute('data-currency') || 'USD';
            
            if (originalPrice && originalPrice > 0) {
                convertPrice(originalPrice, originalCurrency, currencyCode)
                    .then(result => {
                        if (result.success) {
                            element.textContent = result.formatted;
                            element.setAttribute('data-currency', currencyCode);
                        }
                    });
            }
        });
    }
    
    function updateCurrencySelector(currencyCode) {
        // Actualizar el texto del botón de moneda
        const currencyButton = document.querySelector('#currencySelector');
        const selectedOption = document.querySelector(`[data-currency="${currencyCode}"]`);
        
        if (currencyButton && selectedOption) {
            const symbol = selectedOption.querySelector('.currency-symbol').textContent;
            currencyButton.innerHTML = `${symbol} ${currencyCode} <span class="dropdown-toggle"></span>`;
        }
        
        // Actualizar clases active
        document.querySelectorAll('.currency-option').forEach(option => {
            option.classList.remove('active');
        });
        selectedOption?.classList.add('active');
    }
    
    function convertPrice(amount, fromCurrency, toCurrency) {
        return fetch(`?route=i18n/convert-price&amount=${amount}&from=${fromCurrency}&to=${toCurrency}`)
            .then(response => response.json());
    }
    
    function showNotification(message, type = 'info') {
        // Crear notificación toast
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
            </div>
        `;
        
        // Agregar al contenedor de toasts
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        toastContainer.appendChild(toast);
        
        // Mostrar toast
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: type === 'error' ? 5000 : 3000
        });
        bsToast.show();
        
        // Remover del DOM después de que se oculte
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
});
</script>
