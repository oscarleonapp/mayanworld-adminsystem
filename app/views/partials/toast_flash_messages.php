<?php
use App\Core\Helpers;
/**
 * Toast Flash Messages
 * Convierte automáticamente los flash messages de PHP en toast notifications
 * Incluir en layouts/header.php o layouts/footer.php
 */

$flashMessages = Helpers::getFlashMessages();
if (!empty($flashMessages)):
?>
<script>
// Auto-mostrar toast notifications desde flash messages de PHP
(function() {
    // Esperar a que Toast esté disponible
    function showFlashToasts() {
        if (typeof Toast === 'undefined') {
            setTimeout(showFlashToasts, 100);
            return;
        }

        const messages = <?= json_encode($flashMessages) ?>;

        messages.forEach(function(flash, index) {
            // Pequeño delay entre toasts para que no se superpongan
            setTimeout(function() {
                const type = flash.type;
                const message = flash.message;

                // Mapear tipos de PHP a tipos de toast
                const typeMap = {
                    'success': 'success',
                    'error': 'error',
                    'danger': 'error',
                    'warning': 'warning',
                    'info': 'info',
                    'primary': 'info',
                    'secondary': 'info'
                };

                const toastType = typeMap[type] || 'info';

                // Mostrar toast
                Toast[toastType](message, {
                    duration: type === 'error' || type === 'danger' ? 7000 : 5000
                });
            }, index * 300); // 300ms delay entre cada toast
        });
    }

    // Ejecutar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showFlashToasts);
    } else {
        showFlashToasts();
    }
})();
</script>
<?php endif; ?>
