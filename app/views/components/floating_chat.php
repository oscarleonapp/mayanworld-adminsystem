<?php
/**
 * Botón Flotante de WhatsApp
 * Configurable desde el panel de administración
 */
use App\Core\Database;
use App\Core\Config;

// Obtener configuración de WhatsApp
$db = Database::getInstance();
$whatsappConfig = $db->fetchOne("SELECT * FROM whatsapp_config WHERE is_active = 1 LIMIT 1");

// Si no hay configuración, usar valores por defecto
if (!$whatsappConfig) {
    $whatsappConfig = [
        'phone_number' => '502XXXXXXXX',
        'welcome_message' => 'Hola! Me interesa conocer más sobre sus tours.',
        'button_text' => 'Chatea con nosotros',
        'button_position' => 'bottom-right',
        'business_hours_only' => false
    ];
}

// Verificar horario de negocio si está activado
$showButton = true;
if ($whatsappConfig['business_hours_only']) {
    $currentDay = strtolower(date('D')); // mon, tue, wed...
    $currentTime = date('H:i:s');
    $businessDays = explode(',', $whatsappConfig['business_days'] ?? 'mon,tue,wed,thu,fri');

    $isBusinessDay = in_array($currentDay, $businessDays);
    $isBusinessHours = $currentTime >= ($whatsappConfig['business_hours_start'] ?? '08:00:00') &&
                       $currentTime <= ($whatsappConfig['business_hours_end'] ?? '18:00:00');

    $showButton = $isBusinessDay && $isBusinessHours;
}

if (!$showButton) {
    return; // No mostrar el botón fuera del horario
}

// Formatear número de WhatsApp (remover espacios, guiones, etc.)
$phoneNumber = preg_replace('/[^0-9]/', '', $whatsappConfig['phone_number']);

// Construir URL de WhatsApp
$whatsappMessage = urlencode($whatsappConfig['welcome_message']);
$whatsappUrl = "https://wa.me/{$phoneNumber}?text={$whatsappMessage}";

// Obtener posición del botón
$position = $whatsappConfig['button_position'];
$positionStyles = match($position) {
    'bottom-left' => 'bottom: 20px; left: 20px;',
    'top-right' => 'top: 20px; right: 20px;',
    'top-left' => 'top: 20px; left: 20px;',
    default => 'bottom: 20px; right: 20px;' // bottom-right
};
?>

<!-- Botón Flotante de WhatsApp -->
<a href="<?= htmlspecialchars($whatsappUrl) ?>"
   target="_blank"
   rel="noopener noreferrer"
   class="whatsapp-float-button"
   id="whatsappFloatBtn"
   aria-label="<?= htmlspecialchars($whatsappConfig['button_text']) ?>"
   style="<?= $positionStyles ?>">
    <div class="whatsapp-icon">
        <svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
            <path fill="currentColor" d="M16 0c-8.837 0-16 7.163-16 16 0 2.825 0.737 5.607 2.137 8.048l-2.137 7.952 7.933-2.127c2.42 1.37 5.173 2.127 8.067 2.127 8.837 0 16-7.163 16-16s-7.163-16-16-16zM16 29.467c-2.482 0-4.908-0.646-7.07-1.87l-0.507-0.292-5.203 1.393 1.4-5.145-0.321-0.53c-1.331-2.197-2.032-4.72-2.032-7.29 0-7.72 6.28-14 14-14s14 6.28 14 14-6.28 14-14 14zM23.683 19.787c-0.327-0.163-1.933-0.952-2.233-1.062s-0.517-0.163-0.733 0.163c-0.217 0.327-0.838 1.062-1.027 1.278s-0.38 0.247-0.707 0.083c-0.327-0.163-1.38-0.508-2.627-1.62-0.97-0.867-1.627-1.938-1.817-2.265s-0.020-0.503 0.143-0.667c0.147-0.147 0.327-0.38 0.49-0.573s0.217-0.327 0.327-0.543 0.055-0.408-0.027-0.573c-0.083-0.163-0.733-1.767-1.005-2.42s-0.533-0.55-0.733-0.56c-0.19-0.010-0.407-0.012-0.623-0.012s-0.57 0.083-0.867 0.408c-0.298 0.327-1.138 1.113-1.138 2.715s1.165 3.148 1.328 3.365 2.292 3.5 5.55 4.91c0.777 0.337 1.383 0.538 1.857 0.688 0.78 0.248 1.488 0.213 2.048 0.13 0.625-0.093 1.933-0.79 2.205-1.553s0.272-1.418 0.19-1.553c-0.080-0.137-0.3-0.22-0.627-0.383z"/>
        </svg>
    </div>
    <span class="whatsapp-text"><?= htmlspecialchars($whatsappConfig['button_text']) ?></span>
    <div class="whatsapp-pulse"></div>
</a>

<!-- Estilos del Botón de WhatsApp -->
<style>
.whatsapp-float-button {
    position: fixed;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    padding: 0;
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: white;
    text-decoration: none;
    border-radius: 50%;
    box-shadow: 0 4px 20px rgba(37, 211, 102, 0.4);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    font-weight: 600;
    font-size: 15px;
    overflow: visible;
}

.whatsapp-float-button:hover {
    transform: translateY(-3px) scale(1.1);
    box-shadow: 0 8px 30px rgba(37, 211, 102, 0.5);
    color: white;
    text-decoration: none;
}

.whatsapp-float-button:hover .whatsapp-text {
    opacity: 1;
    transform: translateX(0);
}

.whatsapp-float-button:active {
    transform: translateY(-1px) scale(0.98);
}

.whatsapp-icon {
    width: 36px;
    height: 36px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 2;
}

.whatsapp-icon svg {
    width: 100%;
    height: 100%;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

.whatsapp-text {
    white-space: nowrap;
    opacity: 0;
    overflow: hidden;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: absolute;
    right: 75px;
    background: white;
    color: #333;
    padding: 10px 15px;
    border-radius: 8px;
    font-size: 14px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    pointer-events: none;
    transform: translateX(10px);
}

.whatsapp-pulse {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(37, 211, 102, 0.3);
    animation: whatsappPulse 2s infinite;
    pointer-events: none;
}

@keyframes whatsappPulse {
    0% {
        transform: translate(-50%, -50%) scale(0.8);
        opacity: 1;
    }
    50% {
        transform: translate(-50%, -50%) scale(1.2);
        opacity: 0.5;
    }
    100% {
        transform: translate(-50%, -50%) scale(0.8);
        opacity: 0;
    }
}

/* Animación de entrada */
@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.8);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.whatsapp-float-button {
    animation: slideIn 0.5s ease-out 0.5s both;
}

/* Responsive */
@media (max-width: 768px) {
    .whatsapp-float-button {
        width: 56px;
        height: 56px;
    }

    .whatsapp-icon {
        width: 32px;
        height: 32px;
    }

    .whatsapp-text {
        display: none;
    }
}

/* Modo oscuro automático */
@media (prefers-color-scheme: dark) {
    .whatsapp-float-button {
        box-shadow: 0 4px 20px rgba(37, 211, 102, 0.3);
    }

    .whatsapp-float-button:hover {
        box-shadow: 0 8px 30px rgba(37, 211, 102, 0.4);
    }
}

/* Accesibilidad */
.whatsapp-float-button:focus {
    outline: 3px solid rgba(37, 211, 102, 0.5);
    outline-offset: 2px;
}

.whatsapp-float-button:focus:not(:focus-visible) {
    outline: none;
}

/* Animación hover adicional */
@media (hover: hover) {
    .whatsapp-float-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
        opacity: 0;
        transition: opacity 0.3s;
        border-radius: 50%;
    }

    .whatsapp-float-button:hover::before {
        opacity: 1;
    }
}
</style>

<!-- JavaScript para mejoras de UX -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const whatsappBtn = document.getElementById('whatsappFloatBtn');

    if (whatsappBtn) {
        // Agregar evento de click para analytics (opcional)
        whatsappBtn.addEventListener('click', function() {
            console.log('WhatsApp button clicked');

            // Si tienes Google Analytics o similar:
            if (typeof gtag !== 'undefined') {
                gtag('event', 'whatsapp_click', {
                    'event_category': 'engagement',
                    'event_label': 'WhatsApp Float Button'
                });
            }
        });

        // Auto-expandir al hacer hover (táctil)
        let hoverTimeout;
        whatsappBtn.addEventListener('mouseenter', function() {
            clearTimeout(hoverTimeout);
        });

        whatsappBtn.addEventListener('mouseleave', function() {
            hoverTimeout = setTimeout(() => {
                // Opcional: código para contraer
            }, 300);
        });
    }
});
</script>
