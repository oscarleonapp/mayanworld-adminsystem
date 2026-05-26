/**
 * Retry Helper - Sistema de Reintentos con Exponential Backoff
 *
 * Proporciona funciones para reintentar operaciones que pueden fallar temporalmente
 * (llamadas a API, fetch, AJAX, etc.) con estrategia de backoff exponencial y jitter.
 */

/**
 * Ejecutar función con reintentos automáticos
 *
 * @param {Function} fn - Función async a ejecutar
 * @param {Object} options - Opciones de configuración
 * @param {number} options.retries - Número máximo de reintentos (default: 4)
 * @param {number} options.base - Delay base en ms para el backoff (default: 300)
 * @param {number} options.maxDelay - Delay máximo en ms (default: 5000)
 * @param {Function} options.shouldRetry - Función para determinar si reintentar (default: status >= 500)
 * @param {Function} options.onRetry - Callback ejecutado en cada reintento
 * @returns {Promise} - Resultado de la función o error final
 *
 * @example
 * // Fetch con retry
 * const data = await withRetry(() =>
 *   fetch('/api/endpoint').then(r => r.ok ? r.json() : Promise.reject(r))
 * );
 *
 * @example
 * // AJAX con retry y callback
 * const result = await withRetry(
 *   () => $.ajax({url: '/api/data', method: 'POST'}),
 *   {
 *     retries: 3,
 *     onRetry: (attempt, error) => console.log(`Reintento ${attempt}:`, error)
 *   }
 * );
 */
async function withRetry(fn, options = {}) {
    const {
        retries = 4,
        base = 300,
        maxDelay = 5000,
        shouldRetry = defaultShouldRetry,
        onRetry = null
    } = options;

    let attempt = 0;

    while (true) {
        try {
            return await fn();
        } catch (err) {
            attempt++;

            // Determinar si el error es retriable
            const retriable = shouldRetry(err, attempt);

            // Si no es retriable o se acabaron los intentos, lanzar error
            if (!retriable || attempt > retries) {
                console.error(`[Retry] Failed after ${attempt} attempts:`, err);
                throw err;
            }

            // Calcular delay con exponential backoff + jitter
            const jitter = Math.random() * 100;
            const exponentialDelay = base * Math.pow(2, attempt - 1);
            const delay = Math.min(maxDelay, exponentialDelay) + jitter;

            console.warn(`[Retry] Attempt ${attempt}/${retries} failed. Retrying in ${Math.round(delay)}ms...`, err.message || err);

            // Callback opcional
            if (onRetry && typeof onRetry === 'function') {
                onRetry(attempt, err, delay);
            }

            // Esperar antes de reintentar
            await sleep(delay);
        }
    }
}

/**
 * Función por defecto para determinar si un error es retriable
 * Considera retriables: errores de red, 5xx, timeouts
 */
function defaultShouldRetry(error, attempt) {
    // Error de red (sin respuesta HTTP)
    if (!error.response && !error.status) {
        return true;
    }

    // Status HTTP
    const status = error.response?.status || error.status;

    // Server errors (5xx) son retriables
    if (status >= 500 && status < 600) {
        return true;
    }

    // Rate limit (429) es retriable
    if (status === 429) {
        return true;
    }

    // Timeout errors
    if (error.name === 'AbortError' || error.code === 'ETIMEDOUT') {
        return true;
    }

    // Client errors (4xx) generalmente NO son retriables
    return false;
}

/**
 * Helper para dormir/esperar
 */
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Fetch wrapper con retry automático
 *
 * @example
 * const data = await fetchWithRetry('/api/tours', {method: 'GET'});
 */
async function fetchWithRetry(url, fetchOptions = {}, retryOptions = {}) {
    return withRetry(async () => {
        const response = await fetch(url, fetchOptions);

        if (!response.ok) {
            const error = new Error(`HTTP ${response.status}: ${response.statusText}`);
            error.response = response;
            error.status = response.status;
            throw error;
        }

        return response.json();
    }, retryOptions);
}

/**
 * jQuery AJAX wrapper con retry automático
 *
 * @example
 * const data = await ajaxWithRetry({
 *   url: '/api/booking',
 *   method: 'POST',
 *   data: {id: 123}
 * });
 */
async function ajaxWithRetry(ajaxOptions, retryOptions = {}) {
    return withRetry(async () => {
        return new Promise((resolve, reject) => {
            $.ajax({
                ...ajaxOptions,
                success: resolve,
                error: (xhr, status, error) => {
                    const err = new Error(error || 'AJAX request failed');
                    err.status = xhr.status;
                    err.response = xhr;
                    err.xhr = xhr;
                    reject(err);
                }
            });
        });
    }, retryOptions);
}

/**
 * Retry con timeout
 * Si la operación excede el timeout, se cancela y reintenta
 *
 * @example
 * const data = await withRetryAndTimeout(
 *   () => fetch('/slow-endpoint'),
 *   {timeout: 5000, retries: 3}
 * );
 */
async function withRetryAndTimeout(fn, options = {}) {
    const {timeout = 10000, ...retryOptions} = options;

    return withRetry(async () => {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);

        try {
            const result = await fn(controller.signal);
            clearTimeout(timeoutId);
            return result;
        } catch (err) {
            clearTimeout(timeoutId);
            if (err.name === 'AbortError') {
                const timeoutError = new Error(`Operation timed out after ${timeout}ms`);
                timeoutError.name = 'TimeoutError';
                throw timeoutError;
            }
            throw err;
        }
    }, retryOptions);
}

/**
 * Retry con circuit breaker simple
 * Si falla N veces seguidas, abre el circuito por X tiempo
 *
 * Útil para prevenir llamadas repetidas a servicios caídos
 */
class CircuitBreaker {
    constructor(options = {}) {
        this.failureThreshold = options.failureThreshold || 5;
        this.resetTimeout = options.resetTimeout || 60000; // 1 minuto
        this.failures = 0;
        this.state = 'closed'; // 'closed', 'open', 'half-open'
        this.nextAttempt = Date.now();
    }

    async execute(fn, retryOptions = {}) {
        // Si el circuito está abierto
        if (this.state === 'open') {
            if (Date.now() < this.nextAttempt) {
                throw new Error('Circuit breaker is OPEN. Service temporarily unavailable.');
            }
            // Intentar medio-abrir
            this.state = 'half-open';
        }

        try {
            const result = await withRetry(fn, retryOptions);
            this.onSuccess();
            return result;
        } catch (err) {
            this.onFailure();
            throw err;
        }
    }

    onSuccess() {
        this.failures = 0;
        this.state = 'closed';
    }

    onFailure() {
        this.failures++;
        if (this.failures >= this.failureThreshold) {
            this.state = 'open';
            this.nextAttempt = Date.now() + this.resetTimeout;
            console.error(`[Circuit Breaker] OPENED after ${this.failures} failures. Will retry after ${this.resetTimeout}ms`);
        }
    }

    reset() {
        this.failures = 0;
        this.state = 'closed';
        this.nextAttempt = Date.now();
    }
}

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.RetryHelper = {
        withRetry,
        fetchWithRetry,
        ajaxWithRetry,
        withRetryAndTimeout,
        CircuitBreaker,
        sleep
    };
}

// Exportar para módulos ES6
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        withRetry,
        fetchWithRetry,
        ajaxWithRetry,
        withRetryAndTimeout,
        CircuitBreaker,
        sleep
    };
}
