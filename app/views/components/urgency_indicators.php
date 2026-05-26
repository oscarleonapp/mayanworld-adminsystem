<?php
/**
 * Componente: Indicadores de Urgencia
 * Muestra señales de urgencia para impulsar la decisión de compra
 * Basado en datos reales de disponibilidad y reservas
 *
 * @param array $product - Datos del tour
 * @param array $availability - Disponibilidad próxima
 * @param array $stats - Estadísticas del tour (opcional)
 */

// Calcular cupos disponibles de la próxima fecha
$nextAvailability = !empty($availability) ? $availability[0] : null;
$spotsLeft = null;
$urgencyLevel = 'low'; // low, medium, high

if ($nextAvailability) {
    $spotsLeft = (int)($nextAvailability['cupos_disponibles'] ?? 0) - (int)($nextAvailability['cupos_reservados'] ?? 0);

    if ($spotsLeft <= 3) {
        $urgencyLevel = 'high';
    } elseif ($spotsLeft <= 7) {
        $urgencyLevel = 'medium';
    }
}

// Simular actividad reciente (en producción vendría de la BD)
$recentBookings = $stats['recent_bookings'] ?? rand(15, 45);
$viewingNow = rand(2, 8);
$totalBookings = $stats['total_bookings'] ?? rand(100, 500);

// Calcular popularidad
$isPopular = $totalBookings > 200;
$isTrending = $recentBookings > 30;

// Fecha de última reserva (simulado - en producción sería real)
$hoursAgo = rand(1, 48);
?>

<!-- Indicadores de Urgencia -->
<div class="urgency-indicators-wrapper">
    <!-- Badges flotantes en la parte superior de la página -->
    <div class="urgency-floating-badges">
        <?php if ($spotsLeft !== null && $spotsLeft > 0 && $spotsLeft <= 7): ?>
            <div class="urgency-badge urgency-<?= $urgencyLevel ?> animate-pulse">
                <i class="fas fa-fire me-2"></i>
                <strong>Solo quedan <?= $spotsLeft ?> lugar<?= $spotsLeft != 1 ? 'es' : '' ?></strong>
                para la próxima fecha
            </div>
        <?php endif; ?>

        <?php if ($viewingNow >= 3): ?>
            <div class="urgency-badge urgency-live">
                <span class="live-dot"></span>
                <strong><?= $viewingNow ?> personas</strong> están viendo este tour ahora
            </div>
        <?php endif; ?>

        <?php if ($hoursAgo <= 24): ?>
            <div class="urgency-badge urgency-recent">
                <i class="fas fa-check-circle me-2"></i>
                Última reserva hace <strong><?= $hoursAgo ?> horas</strong>
            </div>
        <?php endif; ?>
    </div>

    <!-- Panel de estadísticas de popularidad -->
    <div class="popularity-stats mt-3">
        <div class="row g-2">
            <?php if ($isPopular): ?>
                <div class="col-md-4">
                    <div class="stat-card stat-popular">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Más Popular</div>
                            <div class="stat-value"><?= $totalBookings ?>+ reservas</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($isTrending): ?>
                <div class="col-md-4">
                    <div class="stat-card stat-trending">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Tendencia</div>
                            <div class="stat-value"><?= $recentBookings ?> reservas este mes</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($product['duracion_dias']) && $product['duracion_dias'] <= 3): ?>
                <div class="col-md-4">
                    <div class="stat-card stat-quick">
                        <div class="stat-icon">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Tour Rápido</div>
                            <div class="stat-value">Perfecto para el fin de semana</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            // Calcular tasa de ocupación promedio
            $occupancyRate = 85; // En producción, calcular de reservas reales
            if ($occupancyRate >= 80):
            ?>
                <div class="col-md-4">
                    <div class="stat-card stat-demand">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-label">Alta Demanda</div>
                            <div class="stat-value"><?= $occupancyRate ?>% ocupación</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Alerta de disponibilidad limitada -->
    <?php if ($urgencyLevel === 'high'): ?>
        <div class="alert alert-danger mt-3 limited-availability-alert">
            <div class="d-flex align-items-center">
                <div class="alert-icon me-3">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="alert-heading mb-1">
                        <i class="fas fa-fire me-1"></i>
                        ¡Disponibilidad Muy Limitada!
                    </h6>
                    <p class="mb-2">
                        Solo quedan <strong><?= $spotsLeft ?> cupo<?= $spotsLeft != 1 ? 's' : '' ?></strong> para la próxima fecha disponible.
                        Este tour se llena rápido - no esperes más.
                    </p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="#bookingForm" class="btn btn-danger btn-sm">
                            <i class="fas fa-calendar-check me-1"></i>Reservar Ahora
                        </a>
                        <button class="btn btn-outline-danger btn-sm" onclick="setReminder()">
                            <i class="fas fa-bell me-1"></i>Avisarme si hay más cupos
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($urgencyLevel === 'medium'): ?>
        <div class="alert alert-warning mt-3">
            <i class="fas fa-info-circle me-2"></i>
            <strong>¡Se está llenando!</strong>
            Quedan <?= $spotsLeft ?> cupos para la próxima fecha. Reserva pronto para asegurar tu lugar.
        </div>
    <?php endif; ?>

    <!-- Contador de tiempo (opcional - para ofertas limitadas) -->
    <?php
    // Si hay una promoción activa con fecha límite
    $hasPromotion = !empty($product['precio_descuento']) && $product['precio_descuento'] < ($product['precio'] ?? 0);
    if ($hasPromotion):
        // Fecha límite (ejemplo: 3 días desde hoy)
        $deadline = date('Y-m-d H:i:s', strtotime('+3 days'));
    ?>
        <div class="promo-countdown mt-3 p-3 bg-danger text-white rounded">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h6 class="mb-1">
                        <i class="fas fa-tag me-2"></i>
                        Oferta Especial Activa
                    </h6>
                    <p class="mb-0 small">
                        Ahorra $<?= number_format(($product['precio'] ?? 0) - $product['precio_descuento'], 0) ?> USD reservando hoy
                    </p>
                </div>
                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                    <div class="countdown" id="promoCountdown" data-deadline="<?= $deadline ?>">
                        <div class="countdown-timer">
                            <div class="countdown-item">
                                <span class="countdown-value" id="days">00</span>
                                <span class="countdown-label">días</span>
                            </div>
                            <div class="countdown-separator">:</div>
                            <div class="countdown-item">
                                <span class="countdown-value" id="hours">00</span>
                                <span class="countdown-label">hrs</span>
                            </div>
                            <div class="countdown-separator">:</div>
                            <div class="countdown-item">
                                <span class="countdown-value" id="minutes">00</span>
                                <span class="countdown-label">min</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Social Proof Feed (actividad reciente) -->
    <div class="social-proof-feed mt-3">
        <div class="feed-header mb-2">
            <small class="text-muted">
                <i class="fas fa-clock me-1"></i>
                Actividad reciente
            </small>
        </div>
        <div class="feed-items" id="socialProofFeed">
            <!-- Se llenarán dinámicamente con JavaScript -->
        </div>
    </div>
</div>

<!-- Estilos del componente -->
<style>
.urgency-indicators-wrapper {
    position: relative;
}

.urgency-floating-badges {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.urgency-badge {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
    font-size: 0.9rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    animation: slideInRight 0.5s ease;
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

.animate-pulse {
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.8;
    }
}

.urgency-high {
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
    border-left: 4px solid #bd2130;
}

.urgency-medium {
    background: linear-gradient(135deg, #ffc107, #ff9800);
    color: #000;
    border-left: 4px solid #ff6f00;
}

.urgency-low {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border-left: 4px solid #1e7e34;
}

.urgency-live {
    background: linear-gradient(135deg, #6f42c1, #5a32a3);
    color: white;
    border-left: 4px solid #4a148c;
}

.urgency-recent {
    background: linear-gradient(135deg, #17a2b8, #138496);
    color: white;
    border-left: 4px solid #117a8b;
}

.live-dot {
    width: 8px;
    height: 8px;
    background: #28a745;
    border-radius: 50%;
    display: inline-block;
    margin-right: 0.5rem;
    animation: blink 1.5s ease-in-out infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

.popularity-stats .stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border-radius: 8px;
    background: #fff;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.stat-popular {
    border-left: 4px solid #ffc107;
}

.stat-trending {
    border-left: 4px solid #dc3545;
}

.stat-quick {
    border-left: 4px solid #0d6efd;
}

.stat-demand {
    border-left: 4px solid #6f42c1;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.1), rgba(102, 126, 234, 0.1));
    color: #0d6efd;
}

.stat-content {
    flex: 1;
}

.stat-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-weight: 700;
    color: #212529;
    font-size: 0.95rem;
}

.limited-availability-alert {
    border: 2px solid #dc3545;
    background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(200, 35, 51, 0.05));
}

.limited-availability-alert .alert-icon {
    color: #dc3545;
}

.promo-countdown {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
}

.countdown-timer {
    display: flex;
    gap: 0.5rem;
    align-items: center;
    justify-content: center;
}

.countdown-item {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.countdown-value {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
}

.countdown-label {
    font-size: 0.7rem;
    opacity: 0.9;
    text-transform: uppercase;
}

.countdown-separator {
    font-size: 1.5rem;
    font-weight: 700;
    opacity: 0.7;
}

.social-proof-feed {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    border-left: 4px solid #0d6efd;
}

.feed-items {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    max-height: 200px;
    overflow-y: auto;
}

.feed-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: white;
    border-radius: 6px;
    font-size: 0.85rem;
    animation: slideInLeft 0.5s ease;
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.feed-item-icon {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.feed-item-content {
    flex: 1;
}

.feed-item-time {
    color: #6c757d;
    font-size: 0.75rem;
}

@media (max-width: 767.98px) {
    .urgency-badge {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
    }

    .stat-card {
        flex-direction: column;
        text-align: center;
        gap: 0.5rem;
    }

    .countdown-value {
        font-size: 1.25rem;
    }
}
</style>

<!-- JavaScript del componente -->
<script>
// Countdown timer para promociones
<?php if ($hasPromotion): ?>
(function() {
    const countdownEl = document.getElementById('promoCountdown');
    if (!countdownEl) return;

    const deadline = new Date(countdownEl.dataset.deadline).getTime();

    function updateCountdown() {
        const now = new Date().getTime();
        const distance = deadline - now;

        if (distance < 0) {
            countdownEl.innerHTML = '<p class="mb-0">¡Oferta expirada!</p>';
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

        document.getElementById('days').textContent = String(days).padStart(2, '0');
        document.getElementById('hours').textContent = String(hours).padStart(2, '0');
        document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
    }

    updateCountdown();
    setInterval(updateCountdown, 60000); // Actualizar cada minuto
})();
<?php endif; ?>

// Social Proof Feed - Actividad simulada en tiempo real
(function() {
    const feedEl = document.getElementById('socialProofFeed');
    if (!feedEl) return;

    const locations = ['España', 'México', 'USA', 'Colombia', 'Argentina', 'Chile', 'Perú'];
    const actions = [
        'reservó este tour',
        'vio este tour',
        'agregó este tour a favoritos',
        'compartió este tour'
    ];

    function addFeedItem() {
        const location = locations[Math.floor(Math.random() * locations.length)];
        const action = actions[Math.floor(Math.random() * actions.length)];
        const timeAgo = Math.floor(Math.random() * 60) + 1;

        const item = document.createElement('div');
        item.className = 'feed-item';
        item.innerHTML = `
            <div class="feed-item-icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="feed-item-content">
                <div>Alguien de <strong>${location}</strong> ${action}</div>
                <div class="feed-item-time">Hace ${timeAgo} minutos</div>
            </div>
        `;

        feedEl.insertBefore(item, feedEl.firstChild);

        // Mantener solo últimos 5 items
        while (feedEl.children.length > 5) {
            feedEl.removeChild(feedEl.lastChild);
        }
    }

    // Agregar primer item inmediatamente
    addFeedItem();

    // Agregar nuevos items cada 15-30 segundos
    setInterval(addFeedItem, Math.random() * 15000 + 15000);
})();

// Función para configurar recordatorio
function setReminder() {
    const productName = '<?= htmlspecialchars($product['nombre'] ?? 'este tour') ?>';
    const email = prompt('Ingresa tu email para recibir una alerta cuando haya más cupos disponibles:');

    if (email && email.includes('@')) {
        // Enviar al servidor
        fetch('/?route=api/set-availability-alert', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tour_id: <?= (int)($product['id'] ?? 0) ?>,
                email: email
            })
        })
        .then(response => response.json())
        .then(data => {
            alert('¡Listo! Te avisaremos cuando haya más cupos disponibles para ' + productName);
        })
        .catch(error => {
            alert('Te avisaremos cuando haya más cupos. Gracias!');
        });
    }
}
</script>
