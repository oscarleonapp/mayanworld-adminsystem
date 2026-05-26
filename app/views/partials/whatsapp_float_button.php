<!-- WhatsApp Floating Button -->
<?php
use App\Helpers\CompanyConfigHelper;
// Cargar configuración de WhatsApp
$whatsappNumber = CompanyConfigHelper::get('company_whatsapp');
$whatsappMessage = CompanyConfigHelper::get('whatsapp_default_message', '¡Hola! Me gustaría obtener más información sobre sus tours.');
$whatsappEnabled = CompanyConfigHelper::get('whatsapp_enabled', 'yes'); // Default yes para retrocompatibilidad

// Solo mostrar si está habilitado y hay número configurado
if ($whatsappEnabled === 'yes' && !empty($whatsappNumber)):
?>
<a
    href="<?= CompanyConfigHelper::getWhatsAppLink($whatsappMessage) ?>"
    class="whatsapp-float"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="Contáctanos por WhatsApp"
    title="Contáctanos por WhatsApp"
>
    <i class="fab fa-whatsapp"></i>
    <span class="whatsapp-tooltip">¿Necesitas ayuda?<br>Escríbenos</span>
</a>
<?php endif; ?>

<style>
.whatsapp-float {
    position: fixed;
    bottom: 30px;
    right: 30px;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    box-shadow: 0 4px 20px rgba(37, 211, 102, 0.4);
    z-index: 9999;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

.whatsapp-float:hover {
    transform: scale(1.1) rotate(5deg);
    box-shadow: 0 6px 30px rgba(37, 211, 102, 0.6);
    color: white;
    background: linear-gradient(135deg, #128C7E 0%, #25D366 100%);
}

.whatsapp-float:active {
    transform: scale(0.95);
}

.whatsapp-float .whatsapp-tooltip {
    position: absolute;
    right: 75px;
    background: white;
    color: #333;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 14px;
    line-height: 1.4;
    white-space: nowrap;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    pointer-events: none;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
}

.whatsapp-float .whatsapp-tooltip::after {
    content: '';
    position: absolute;
    right: -8px;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid white;
    border-top: 8px solid transparent;
    border-bottom: 8px solid transparent;
}

.whatsapp-float:hover .whatsapp-tooltip {
    opacity: 1;
    visibility: visible;
    right: 80px;
}

/* Animación de entrada */
@keyframes slideInFromRight {
    from {
        transform: translateX(100px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.whatsapp-float {
    animation: slideInFromRight 0.5s ease-out;
}

/* Responsive */
@media (max-width: 768px) {
    .whatsapp-float {
        width: 56px;
        height: 56px;
        font-size: 28px;
        bottom: 20px;
        right: 20px;
    }

    .whatsapp-float .whatsapp-tooltip {
        display: none; /* Ocultar tooltip en móvil para no obstruir */
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .whatsapp-float .whatsapp-tooltip {
        background: #1e1e1e;
        color: #fff;
    }

    .whatsapp-float .whatsapp-tooltip::after {
        border-left-color: #1e1e1e;
    }
}

/* Accesibilidad: reducir movimiento */
@media (prefers-reduced-motion: reduce) {
    .whatsapp-float {
        animation: none;
    }

    .whatsapp-float:hover {
        transform: scale(1.05);
    }
}

/* Print: ocultar el botón */
@media print {
    .whatsapp-float {
        display: none !important;
    }
}
</style>
