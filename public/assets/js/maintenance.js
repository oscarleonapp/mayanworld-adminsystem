// Basic UX sprinkles for the under-construction page
(function () {
  const $time = document.getElementById('lastUpdated');
  try {
    const now = new Date();
    if ($time) {
      // Format as DD/MM/YYYY HH:MM (local)
      const opts = { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' };
      $time.textContent = now.toLocaleString('es-ES', opts);
      $time.setAttribute('datetime', now.toISOString());
    }
  } catch (e) { /* no-op */ }

  // Subtle pulse on the card when the page becomes visible
  const card = document.querySelector('.uc-card');
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden && card) {
      card.animate([
        { transform: 'scale(1)', boxShadow: '0 10px 30px rgba(0,0,0,.25)' },
        { transform: 'scale(1.02)', boxShadow: '0 14px 36px rgba(0,0,0,.3)' },
        { transform: 'scale(1)', boxShadow: '0 10px 30px rgba(0,0,0,.25)' },
      ], { duration: 600, easing: 'ease-out' });
    }
  });
})();

