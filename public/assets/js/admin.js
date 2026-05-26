// Admin UI helpers
window.AdminUI = (function () {
  function getContainer() {
    return document.getElementById('toast-container');
  }

  function toast(message, type = 'primary', opts = {}) {
    const container = getContainer();
    if (!container || typeof bootstrap === 'undefined') return;
    const id = 't' + Date.now();
    const TITLES = { success: 'Éxito', danger: 'Error', warning: 'Aviso', info: 'Información', primary: 'Notificación', secondary: 'Notificación' };
    const title = opts.title || TITLES[type] || 'Notificación';
    const autohide = opts.autohide !== false;
    const delay = opts.delay || 3000;

    const el = document.createElement('div');
    el.className = 'toast text-bg-' + type;
    el.id = id;
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', 'assertive');
    el.setAttribute('aria-atomic', 'true');
    el.innerHTML = `
      <div class="toast-header">
        <strong class="me-auto">${title}</strong>
        <small class="text-muted">Ahora</small>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Cerrar"></button>
      </div>
      <div class="toast-body">${message}</div>
    `;
    container.appendChild(el);

    const t = new bootstrap.Toast(el, { autohide, delay });
    t.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }

  return { toast };
})();

// Density toggle
(function(){
  const KEY = 'admin_density';
  function applyDensity(val){
    const on = val === 'compact';
    document.body.classList.toggle('compact', on);
  }
  function init(){
    try{
      const saved = localStorage.getItem(KEY) || 'regular';
      applyDensity(saved);
      window.AdminUI = window.AdminUI || {};
      window.AdminUI.toggleDensity = function(){
        const next = document.body.classList.contains('compact') ? 'regular' : 'compact';
        localStorage.setItem(KEY, next);
        applyDensity(next);
        window.AdminUI.toast('Densidad: ' + (next === 'compact' ? 'Compacta' : 'Regular'), 'primary');
      }
    }catch(e){ /* noop */ }
  }
  if (document.readyState !== 'loading') init();
  else document.addEventListener('DOMContentLoaded', init);
})();
