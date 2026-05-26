<?php
use App\Core\Config;
/**
 * Componente: Calendario Visual de Disponibilidad
 * Calendario interactivo que muestra disponibilidad mes a mes
 * Aumenta urgencia y facilita la selección de fecha
 *
 * @param array $product - Datos del tour
 * @param array $availability - Array de disponibilidad futura
 * @param string $formId - ID del formulario de reserva (default: 'bookingForm')
 */

if (empty($availability)) {
    return; // No mostrar calendario si no hay disponibilidad
}

$formId = $formId ?? 'bookingForm';

// Preparar datos de disponibilidad para JavaScript
$availabilityData = [];
foreach ($availability as $avail) {
    $date = $avail['fecha_salida'];
    $available = (int)$avail['cupos_disponibles'] - (int)$avail['cupos_reservados'];
    $price = !empty($avail['precio_especial']) ? $avail['precio_especial'] : ($product['precio_descuento'] ?? $product['precio'] ?? 0);

    $availabilityData[] = [
        'id' => $avail['id'],
        'date' => $date,
        'available' => $available,
        'capacity' => (int)$avail['cupos_disponibles'],
        'price' => (float)$price,
        'formatted_date' => date('d/m/Y', strtotime($date)),
        'urgency' => $available <= 3 ? 'high' : ($available <= 7 ? 'medium' : 'low')
    ];
}

// Obtener rango de meses disponibles
$months = [];
if (!empty($availabilityData)) {
    foreach ($availabilityData as $item) {
        $month = substr($item['date'], 0, 7); // YYYY-MM
        if (!in_array($month, $months)) {
            $months[] = $month;
        }
    }
    sort($months);
}
?>

<!-- Calendario Visual de Disponibilidad -->
<div class="availability-calendar-wrapper mt-4">
    <div class="card shadow-sm">
        <div class="card-header-custom bg-success text-white">
            <div class="d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="calendar-header-icon me-3">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div>
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-check me-2"></i>Disponibilidad del Tour
                        </h4>
                        <small class="opacity-90">Selecciona una fecha y reserva al instante</small>
                    </div>
                </div>
                <div class="d-none d-md-block">
                    <button class="btn btn-light btn-sm" onclick="scrollToBookingForm()">
                        <i class="fas fa-arrow-down me-1"></i>Ir a reserva
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-md-4">
            <!-- Controles del calendario -->
            <div class="calendar-controls mb-4">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <button class="btn btn-outline-primary" id="calPrevMonth" disabled>
                            <i class="fas fa-chevron-left"></i>
                            <span class="d-none d-md-inline ms-1">Anterior</span>
                        </button>
                    </div>
                    <div class="col text-center">
                        <h5 class="mb-0 fw-bold" id="calCurrentMonth">Cargando...</h5>
                        <small class="text-muted" id="calAvailabilityCount">0 fechas disponibles</small>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-primary" id="calNextMonth">
                            <span class="d-none d-md-inline me-1">Siguiente</span>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Leyenda -->
            <div class="calendar-legend mb-3">
                <div class="row g-2 justify-content-center">
                    <div class="col-auto">
                        <span class="legend-item">
                            <span class="legend-indicator legend-available"></span>
                            Disponible
                        </span>
                    </div>
                    <div class="col-auto">
                        <span class="legend-item">
                            <span class="legend-indicator legend-limited"></span>
                            Pocos cupos
                        </span>
                    </div>
                    <div class="col-auto">
                        <span class="legend-item">
                            <span class="legend-indicator legend-full"></span>
                            Agotado
                        </span>
                    </div>
                    <div class="col-auto">
                        <span class="legend-item">
                            <span class="legend-indicator legend-unavailable"></span>
                            No disponible
                        </span>
                    </div>
                </div>
            </div>

            <!-- Grid del calendario -->
            <div id="calendarGrid" class="calendar-grid-container">
                <!-- Se llenará con JavaScript -->
            </div>

            <!-- Info de fecha seleccionada -->
            <div id="selectedDateInfo" class="selected-date-info mt-4" style="display: none;">
                <div class="alert alert-success mb-0">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="alert-heading mb-1">
                                <i class="fas fa-check-circle me-2"></i>
                                Fecha seleccionada
                            </h6>
                            <div class="selected-date-details">
                                <strong id="selectedDateText"></strong>
                                <span class="mx-2">•</span>
                                <span id="selectedSpotsText"></span>
                                <span class="mx-2">•</span>
                                <span id="selectedPriceText"></span>
                            </div>
                        </div>
                        <div class="col-md-4 text-md-end mt-2 mt-md-0">
                            <button type="button" class="btn btn-success" onclick="submitBookingForm()">
                                <i class="fas fa-check-circle me-1"></i>Completar Reserva
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Opción para alertas de disponibilidad -->
            <div class="availability-alerts mt-3 p-3 bg-light rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="mb-1">
                            <i class="fas fa-bell text-primary me-2"></i>
                            ¿No encuentras la fecha que buscas?
                        </h6>
                        <p class="mb-0 small text-muted">
                            Te avisaremos cuando se agreguen nuevas fechas para este tour
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-2 mt-md-0">
                        <button class="btn btn-outline-primary btn-sm" onclick="setupAvailabilityAlert()">
                            <i class="fas fa-bell me-1"></i>Configurar alerta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos del calendario -->
<style>
.availability-calendar-wrapper {
    position: relative;
}

.calendar-header-icon {
    background: rgba(255, 255, 255, 0.2);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.calendar-controls button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.calendar-legend {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.legend-item {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
}

.legend-indicator {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: 2px solid #dee2e6;
}

.legend-available {
    background-color: #d4edda;
    border-color: #28a745;
}

.legend-limited {
    background-color: #fff3cd;
    border-color: #ffc107;
}

.legend-full {
    background-color: #f8d7da;
    border-color: #dc3545;
}

.legend-unavailable {
    background-color: #f8f9fa;
    border-color: #dee2e6;
}

.calendar-grid-container {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 8px;
    margin: 0 auto;
    max-width: 800px;
}

.cal-day-header {
    text-align: center;
    font-weight: 600;
    font-size: 0.85rem;
    color: #6c757d;
    padding: 0.5rem 0;
    text-transform: uppercase;
}

.cal-day {
    aspect-ratio: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    background: #fff;
    position: relative;
    transition: all 0.2s ease;
    cursor: default;
    padding: 0.5rem;
    min-height: 70px;
}

.cal-day.empty {
    background: transparent;
    border: none;
}

.cal-day.available {
    cursor: pointer;
    background: #d4edda;
    border-color: #28a745;
}

.cal-day.available:hover {
    background: #c3e6cb;
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    z-index: 1;
}

.cal-day.limited {
    cursor: pointer;
    background: #fff3cd;
    border-color: #ffc107;
}

.cal-day.limited:hover {
    background: #ffe8a1;
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
    z-index: 1;
}

.cal-day.full {
    background: #f8d7da;
    border-color: #dc3545;
    cursor: not-allowed;
    opacity: 0.6;
}

.cal-day.unavailable {
    background: #f8f9fa;
    border-color: #dee2e6;
    color: #adb5bd;
}

.cal-day.selected {
    background: #007bff !important;
    border-color: #0056b3 !important;
    color: white !important;
    transform: scale(1.08);
    box-shadow: 0 6px 16px rgba(0, 123, 255, 0.4);
}

.cal-day-number {
    font-size: 1rem;
    font-weight: 600;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.cal-day-price {
    font-size: 0.7rem;
    font-weight: 600;
    color: #28a745;
    line-height: 1;
}

.cal-day.limited .cal-day-price {
    color: #856404;
}

.cal-day.selected .cal-day-price {
    color: white;
}

.cal-day-spots {
    font-size: 0.65rem;
    color: #6c757d;
    line-height: 1;
    margin-top: 0.25rem;
}

.cal-day.selected .cal-day-spots {
    color: rgba(255, 255, 255, 0.9);
}

.cal-day-badge {
    position: absolute;
    top: 2px;
    right: 2px;
    font-size: 0.6rem;
    padding: 2px 4px;
    border-radius: 3px;
    background: #dc3545;
    color: white;
    line-height: 1;
}

.selected-date-info {
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.selected-date-details {
    font-size: 0.95rem;
}

/* Responsive */
@media (max-width: 767.98px) {
    .calendar-grid-container {
        gap: 4px;
    }

    .cal-day {
        min-height: 60px;
        padding: 0.25rem;
    }

    .cal-day-number {
        font-size: 0.9rem;
    }

    .cal-day-price {
        font-size: 0.65rem;
    }

    .cal-day-spots {
        display: none;
    }

    .cal-day-header {
        font-size: 0.75rem;
        padding: 0.25rem 0;
    }
}
</style>

<!-- JavaScript del calendario -->
<script>
(function() {
    // Datos de disponibilidad
    const availabilityData = <?= json_encode($availabilityData) ?>;
    const productName = <?= json_encode($product['nombre'] ?? 'este tour') ?>;
    const formId = '<?= $formId ?>';

    // Agrupar por mes
    const monthsMap = {};
    availabilityData.forEach(item => {
        const month = item.date.substring(0, 7); // YYYY-MM
        if (!monthsMap[month]) {
            monthsMap[month] = [];
        }
        monthsMap[month].push(item);
    });

    const months = Object.keys(monthsMap).sort();
    let currentMonthIndex = 0;
    let selectedDate = null;

    // Referencias DOM
    const gridEl = document.getElementById('calendarGrid');
    const monthEl = document.getElementById('calCurrentMonth');
    const countEl = document.getElementById('calAvailabilityCount');
    const prevBtn = document.getElementById('calPrevMonth');
    const nextBtn = document.getElementById('calNextMonth');
    const selectedInfo = document.getElementById('selectedDateInfo');

    function renderCalendar() {
        const month = months[currentMonthIndex];
        const [year, monthNum] = month.split('-').map(Number);

        // Actualizar título
        const date = new Date(year, monthNum - 1, 1);
        monthEl.textContent = date.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });

        // Contar disponibles este mes
        const availableCount = monthsMap[month].filter(d => d.available > 0).length;
        countEl.textContent = `${availableCount} fecha${availableCount !== 1 ? 's' : ''} disponible${availableCount !== 1 ? 's' : ''}`;

        // Botones nav
        prevBtn.disabled = currentMonthIndex === 0;
        nextBtn.disabled = currentMonthIndex === months.length - 1;

        // Limpiar grid
        gridEl.innerHTML = '';

        // Headers de días
        const dayHeaders = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        dayHeaders.forEach(day => {
            const header = document.createElement('div');
            header.className = 'cal-day-header';
            header.textContent = day;
            gridEl.appendChild(header);
        });

        // Calcular días del mes
        const firstDay = new Date(year, monthNum - 1, 1);
        const lastDay = new Date(year, monthNum, 0);
        const daysInMonth = lastDay.getDate();
        const startDayOfWeek = (firstDay.getDay() + 6) % 7; // Lunes = 0

        // Días vacíos antes del 1
        for (let i = 0; i < startDayOfWeek; i++) {
            const empty = document.createElement('div');
            empty.className = 'cal-day empty';
            gridEl.appendChild(empty);
        }

        // Días del mes
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(monthNum).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dayData = monthsMap[month].find(d => d.date === dateStr);
            const dayDate = new Date(year, monthNum - 1, day);

            const dayEl = document.createElement('div');
            dayEl.className = 'cal-day';

            const dayNum = document.createElement('div');
            dayNum.className = 'cal-day-number';
            dayNum.textContent = day;
            dayEl.appendChild(dayNum);

            if (dayData && dayData.available > 0 && dayDate >= today) {
                // Día disponible
                const urgency = dayData.urgency;
                dayEl.classList.add(urgency === 'high' || urgency === 'medium' ? 'limited' : 'available');

                // Precio
                const priceEl = document.createElement('div');
                priceEl.className = 'cal-day-price';
                priceEl.textContent = '$' + Math.round(dayData.price);
                dayEl.appendChild(priceEl);

                // Cupos
                const spotsEl = document.createElement('div');
                spotsEl.className = 'cal-day-spots';
                spotsEl.textContent = `${dayData.available} cupos`;
                dayEl.appendChild(spotsEl);

                // Badge de urgencia
                if (urgency === 'high') {
                    const badge = document.createElement('div');
                    badge.className = 'cal-day-badge';
                    badge.textContent = '¡Últimos!';
                    dayEl.appendChild(badge);
                }

                // Click handler
                dayEl.addEventListener('click', () => selectDate(dayData));

                // Marcar si está seleccionado
                if (selectedDate && selectedDate.id === dayData.id) {
                    dayEl.classList.add('selected');
                }
            } else if (dayData && dayData.available === 0) {
                // Agotado
                dayEl.classList.add('full');
                dayNum.style.textDecoration = 'line-through';
                const fullEl = document.createElement('div');
                fullEl.className = 'cal-day-spots';
                fullEl.textContent = 'Agotado';
                dayEl.appendChild(fullEl);
            } else {
                // No disponible
                dayEl.classList.add('unavailable');
            }

            gridEl.appendChild(dayEl);
        }
    }

    function selectDate(dayData) {
        selectedDate = dayData;

        // Actualizar select del formulario
        const selectEl = document.querySelector(`#${formId} select[name="disponibilidad_id"]`);
        if (selectEl) {
            selectEl.value = dayData.id;
            // Trigger change event para actualizar precio
            selectEl.dispatchEvent(new Event('change'));
        }

        // Mostrar info
        document.getElementById('selectedDateText').textContent = dayData.formatted_date;
        document.getElementById('selectedSpotsText').textContent = `${dayData.available} cupos disponibles`;
        document.getElementById('selectedPriceText').textContent = `$${Math.round(dayData.price)} USD por persona`;
        selectedInfo.style.display = 'block';

        // Re-render para marcar seleccionado
        renderCalendar();

        // Scroll suave al info
        selectedInfo.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Event listeners
    prevBtn.addEventListener('click', () => {
        if (currentMonthIndex > 0) {
            currentMonthIndex--;
            renderCalendar();
        }
    });

    nextBtn.addEventListener('click', () => {
        if (currentMonthIndex < months.length - 1) {
            currentMonthIndex++;
            renderCalendar();
        }
    });

    // Render inicial
    renderCalendar();
})();

// Función global para scroll al formulario
function scrollToBookingForm() {
    const form = document.getElementById('<?= $formId ?>');
    if (form) {
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        // Focus en fecha después de scroll
        setTimeout(() => {
            const dateSelect = form.querySelector('select[name="disponibilidad_id"]');
            if (dateSelect) dateSelect.focus();
        }, 500);
    }
}

// Función para configurar alerta de disponibilidad
function setupAvailabilityAlert() {
    const email = prompt('Ingresa tu email para recibir alertas cuando haya nuevas fechas disponibles:');

    if (email && email.includes('@')) {
        // Enviar al servidor
        fetch('/?route=api/availability-alert', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                tour_id: <?= (int)$product['id'] ?>,
                email: email
            })
        })
        .then(response => response.json())
        .then(data => {
            alert('¡Perfecto! Te avisaremos cuando se agreguen nuevas fechas para <?= htmlspecialchars(addslashes($product['nombre'] ?? 'este tour')) ?>.');
        })
        .catch(error => {
            alert('Hemos registrado tu email. Te avisaremos cuando haya nuevas fechas. ¡Gracias!');
        });
    }
}
</script>
