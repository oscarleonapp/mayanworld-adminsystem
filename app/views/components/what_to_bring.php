<?php
use App\Core\Config;
/**
 * Componente: Qué Traer / Lista de Equipaje
 * Lee los ítems desde $product['que_llevar'] (JSON guardado desde el admin).
 * Si el campo está vacío o no es JSON válido, usa datos hardcoded por defecto.
 *
 * @param array $product - Datos del tour
 */

// Iconos por defecto según categoría
$catIcons = [
    'esencial'    => 'fa-exclamation-circle',
    'recomendado' => 'fa-check-circle',
    'opcional'    => 'fa-star',
];

// Intentar leer items configurados desde el admin
$customItems = null;
if (!empty($product['que_llevar'])) {
    $decoded = json_decode($product['que_llevar'], true);
    if (is_array($decoded) && count($decoded) > 0 && isset($decoded[0]['item'])) {
        $customItems = $decoded;
    }
}

if ($customItems !== null) {
    // --- Datos editados desde el admin ---
    $essentialItems    = [];
    $recommendedItems  = [];
    $optionalItems     = [];

    foreach ($customItems as $entry) {
        $cat  = $entry['categoria'] ?? 'esencial';
        $row  = [
            'item'     => $entry['item'] ?? '',
            'icon'     => $catIcons[$cat] ?? 'fa-circle',
            'category' => $cat,
            'tip'      => $entry['tip'] ?? '',
        ];
        if ($cat === 'recomendado')      $recommendedItems[] = $row;
        elseif ($cat === 'opcional')     $optionalItems[]    = $row;
        else                             $essentialItems[]   = $row;
    }
} else {
    // --- Datos hardcoded por defecto ---
    $essentialItems = [
        ['item' => 'Calzado cómodo para caminar', 'icon' => 'fa-shoe-prints', 'category' => 'esencial',
         'tip'  => 'Preferiblemente botas de trekking o zapatos deportivos cerrados con buen agarre.'],
        ['item' => 'Protector solar SPF 50+',      'icon' => 'fa-sun',         'category' => 'esencial',
         'tip'  => 'Guatemala está cerca del ecuador. El sol es fuerte incluso en días nublados.'],
        ['item' => 'Botella de agua reutilizable', 'icon' => 'fa-bottle-water','category' => 'esencial',
         'tip'  => 'Manténte hidratado. Hay puntos de recarga en la ruta.'],
        ['item' => 'Sombrero o gorra',             'icon' => 'fa-hat-cowboy',  'category' => 'esencial',
         'tip'  => 'Protección adicional contra el sol.'],
        ['item' => 'Repelente de insectos',        'icon' => 'fa-bug',         'category' => 'esencial',
         'tip'  => 'Especialmente importante en áreas de selva.'],
    ];

    $recommendedItems = [];
    if (($product['duracion_dias'] ?? 1) > 1) {
        $recommendedItems[] = ['item' => 'Ropa de cambio', 'icon' => 'fa-tshirt', 'category' => 'recomendado',
            'tip' => 'Ropa ligera y transpirable. ' . ($product['duracion_dias'] ?? 2) . ' cambios completos.'];
        $recommendedItems[] = ['item' => 'Artículos de higiene personal', 'icon' => 'fa-soap', 'category' => 'recomendado',
            'tip' => 'Pasta dental, cepillo, toalla pequeña, etc.'];
    }
    if (in_array($product['dificultad'] ?? '', ['moderado', 'dificil'])) {
        $recommendedItems[] = ['item' => 'Bastón de trekking', 'icon' => 'fa-walking', 'category' => 'recomendado',
            'tip' => 'Ayuda en terrenos irregulares y protege las rodillas.'];
    }
    $recommendedItems = array_merge($recommendedItems, [
        ['item' => 'Cámara o smartphone',      'icon' => 'fa-camera',         'category' => 'recomendado',
         'tip'  => '¡Querrás capturar cada momento! Trae batería externa.'],
        ['item' => 'Dinero en efectivo',        'icon' => 'fa-money-bill-wave','category' => 'recomendado',
         'tip'  => 'Para compras personales, propinas y emergencias. Quetzales y dólares.'],
        ['item' => 'Documentos (ID/Pasaporte)', 'icon' => 'fa-id-card',        'category' => 'recomendado',
         'tip'  => 'Copia física o digital. Necesario en algunos sitios.'],
        ['item' => 'Impermeable o poncho',      'icon' => 'fa-umbrella',       'category' => 'recomendado',
         'tip'  => 'El clima puede cambiar rápidamente. Mejor prevenir.'],
    ]);

    $optionalItems = [
        ['item' => 'Linterna o frontal',     'icon' => 'fa-lightbulb',   'category' => 'opcional',
         'tip'  => 'Útil para tours que incluyen cuevas o caminatas nocturnas.'],
        ['item' => 'Snacks energéticos',     'icon' => 'fa-cookie-bite', 'category' => 'opcional',
         'tip'  => 'Barras energéticas, frutos secos, chocolate.'],
        ['item' => 'Binoculares',            'icon' => 'fa-binoculars',  'category' => 'opcional',
         'tip'  => 'Para observación de aves y vida silvestre.'],
        ['item' => 'Medicamentos personales','icon' => 'fa-pills',       'category' => 'opcional',
         'tip'  => 'Trae tus medicamentos habituales. Antiácidos y analgésicos son útiles.'],
    ];
}

$allItems = array_merge($essentialItems, $recommendedItems, $optionalItems);
?>

<!-- Sección: Qué Traer -->
<div class="card shadow-sm mt-4 what-to-bring-card">
    <div class="card-header bg-warning text-dark">
        <div class="d-flex align-items-center">
            <div class="bring-header-icon me-3">
                <i class="fas fa-backpack"></i>
            </div>
            <div>
                <h4 class="mb-0">
                    <i class="fas fa-list-check me-2"></i>Qué Traer / Equipaje Recomendado
                </h4>
                <small class="opacity-75">Prepárate correctamente para disfrutar al máximo</small>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Tabs para organizar por categoría -->
        <ul class="nav nav-tabs mb-4" id="bringTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="essential-tab" data-bs-toggle="tab" data-bs-target="#essential"
                        type="button" role="tab" aria-controls="essential" aria-selected="true">
                    <i class="fas fa-exclamation-circle text-danger me-1"></i>
                    Esenciales
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="recommended-tab" data-bs-toggle="tab" data-bs-target="#recommended"
                        type="button" role="tab" aria-controls="recommended" aria-selected="false">
                    <i class="fas fa-check-circle text-success me-1"></i>
                    Recomendados
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="optional-tab" data-bs-toggle="tab" data-bs-target="#optional"
                        type="button" role="tab" aria-controls="optional" aria-selected="false">
                    <i class="fas fa-star text-info me-1"></i>
                    Opcionales
                </button>
            </li>
        </ul>

        <div class="tab-content" id="bringTabsContent">
            <!-- Esenciales -->
            <div class="tab-pane fade show active" id="essential" role="tabpanel" aria-labelledby="essential-tab">
                <div class="alert alert-danger mb-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>¡No olvides estos!</strong> Son indispensables para tu seguridad y comodidad.
                </div>
                <div class="row">
                    <?php foreach ($essentialItems as $item): ?>
                        <div class="col-md-6 mb-3">
                            <div class="item-card essential-item">
                                <div class="item-icon">
                                    <i class="fas <?= $item['icon'] ?>"></i>
                                </div>
                                <div class="item-content">
                                    <h6 class="item-name mb-1"><?= htmlspecialchars($item['item']) ?></h6>
                                    <p class="item-tip mb-0">
                                        <i class="fas fa-lightbulb text-warning me-1"></i>
                                        <small><?= htmlspecialchars($item['tip']) ?></small>
                                    </p>
                                </div>
                                <div class="item-checkbox">
                                    <input type="checkbox" class="form-check-input" id="check-<?= md5($item['item']) ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Recomendados -->
            <div class="tab-pane fade" id="recommended" role="tabpanel" aria-labelledby="recommended-tab">
                <div class="alert alert-success mb-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Altamente recomendados</strong> para una mejor experiencia.
                </div>
                <div class="row">
                    <?php foreach ($recommendedItems as $item): ?>
                        <div class="col-md-6 mb-3">
                            <div class="item-card recommended-item">
                                <div class="item-icon">
                                    <i class="fas <?= $item['icon'] ?>"></i>
                                </div>
                                <div class="item-content">
                                    <h6 class="item-name mb-1"><?= htmlspecialchars($item['item']) ?></h6>
                                    <p class="item-tip mb-0">
                                        <i class="fas fa-lightbulb text-warning me-1"></i>
                                        <small><?= htmlspecialchars($item['tip']) ?></small>
                                    </p>
                                </div>
                                <div class="item-checkbox">
                                    <input type="checkbox" class="form-check-input" id="check-<?= md5($item['item']) ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Opcionales -->
            <div class="tab-pane fade" id="optional" role="tabpanel" aria-labelledby="optional-tab">
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Opcionales</strong> pero pueden mejorar tu experiencia.
                </div>
                <div class="row">
                    <?php foreach ($optionalItems as $item): ?>
                        <div class="col-md-6 mb-3">
                            <div class="item-card optional-item">
                                <div class="item-icon">
                                    <i class="fas <?= $item['icon'] ?>"></i>
                                </div>
                                <div class="item-content">
                                    <h6 class="item-name mb-1"><?= htmlspecialchars($item['item']) ?></h6>
                                    <p class="item-tip mb-0">
                                        <i class="fas fa-lightbulb text-warning me-1"></i>
                                        <small><?= htmlspecialchars($item['tip']) ?></small>
                                    </p>
                                </div>
                                <div class="item-checkbox">
                                    <input type="checkbox" class="form-check-input" id="check-<?= md5($item['item']) ?>">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Acciones -->
        <div class="mt-4 pt-3 border-top">
            <div class="row">
                <div class="col-md-6 mb-2 mb-md-0">
                    <button class="btn btn-outline-primary w-100" onclick="printChecklist()">
                        <i class="fas fa-print me-2"></i>Imprimir Checklist
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-outline-success w-100" onclick="shareChecklist()">
                        <i class="fas fa-share-alt me-2"></i>Compartir por WhatsApp
                    </button>
                </div>
            </div>
        </div>

        <!-- Consejos adicionales -->
        <div class="packing-tips mt-4 p-3 bg-light rounded">
            <h6><i class="fas fa-lightbulb text-warning me-2"></i>Consejos de Empaque</h6>
            <ul class="mb-0 small">
                <li>Empaca ligero. Puedes lavar ropa en la ruta si es necesario.</li>
                <li>Usa capas de ropa. El clima puede variar entre frío y calor en el mismo día.</li>
                <li>Guarda tus electrónicos en bolsas impermeables.</li>
                <li>Deja objetos de valor en el hotel. Solo trae lo necesario.</li>
                <li>Tu guía te enviará recordatorios por WhatsApp 24h antes del tour.</li>
            </ul>
        </div>
    </div>
</div>

<!-- Estilos del componente -->
<style>
.what-to-bring-card {
    border: none;
}

.bring-header-icon {
    background: rgba(0, 0, 0, 0.1);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.nav-tabs .nav-link {
    color: #6c757d;
    border: none;
    border-bottom: 3px solid transparent;
    padding: 0.75rem 1.25rem;
    font-weight: 500;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
    border-color: #dee2e6;
}

.nav-tabs .nav-link.active {
    color: #0d6efd;
    background-color: transparent;
    border-color: #0d6efd;
}

.item-card {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: #fff;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    transition: all 0.3s ease;
}

.item-card:hover {
    border-color: #0d6efd;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.1);
    transform: translateY(-2px);
}

.essential-item {
    border-left: 4px solid #dc3545;
}

.recommended-item {
    border-left: 4px solid #28a745;
}

.optional-item {
    border-left: 4px solid #17a2b8;
}

.item-icon {
    background: linear-gradient(135deg, rgba(13, 110, 253, 0.1), rgba(102, 126, 234, 0.1));
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #0d6efd;
    flex-shrink: 0;
}

.item-content {
    flex: 1;
}

.item-name {
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.item-tip {
    color: #6c757d;
    line-height: 1.5;
}

.item-checkbox {
    flex-shrink: 0;
}

.item-checkbox .form-check-input {
    width: 24px;
    height: 24px;
    cursor: pointer;
}

.item-checkbox .form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.packing-tips {
    background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 152, 0, 0.1));
    border-left: 4px solid #ffc107;
}

.packing-tips ul {
    padding-left: 1.25rem;
}

.packing-tips li {
    margin-bottom: 0.5rem;
}

@media (max-width: 767.98px) {
    .nav-tabs .nav-link {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
    }

    .item-card {
        flex-direction: column;
        gap: 0.75rem;
    }

    .item-checkbox {
        align-self: flex-end;
    }
}
</style>

<!-- JavaScript del componente -->
<script>
// Imprimir checklist
function printChecklist() {
    const printContent = document.querySelector('.what-to-bring-card').cloneNode(true);
    const printWindow = window.open('', '', 'height=600,width=800');

    printWindow.document.write('<html><head><title>Lista de Equipaje - <?= htmlspecialchars($product['nombre'] ?? 'Tour') ?></title>');
    printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
    printWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">');
    printWindow.document.write('</head><body>');
    printWindow.document.write('<div class="container mt-4">');
    printWindow.document.write('<h2>Lista de Equipaje</h2>');
    printWindow.document.write('<h5><?= htmlspecialchars($product['nombre'] ?? 'Tour') ?></h5>');
    printWindow.document.write('<hr>');
    printWindow.document.write(printContent.innerHTML);
    printWindow.document.write('</div>');
    printWindow.document.write('</body></html>');

    printWindow.document.close();
    printWindow.print();
}

// Compartir checklist por WhatsApp
function shareChecklist() {
    const productName = '<?= htmlspecialchars($product['nombre'] ?? 'el tour') ?>';
    let message = `📋 *Lista de Equipaje para ${productName}*\n\n`;

    message += '*ESENCIALES:*\n';
    <?php foreach ($essentialItems as $item): ?>
    message += '✓ <?= addslashes($item['item']) ?>\n';
    <?php endforeach; ?>

    message += '\n*RECOMENDADOS:*\n';
    <?php foreach (array_slice($recommendedItems, 0, 5) as $item): ?>
    message += '• <?= addslashes($item['item']) ?>\n';
    <?php endforeach; ?>

    message += '\n📱 Reserva tu tour: <?= Config::getBaseUrl() ?>?route=tour/<?= $product['id'] ?? '' ?>';

    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}

// Guardar estado de checkboxes en localStorage
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.item-checkbox .form-check-input');
    const storageKey = 'checklist_<?= $product['id'] ?? 'tour' ?>';

    // Cargar estado guardado
    const savedState = localStorage.getItem(storageKey);
    if (savedState) {
        const checked = JSON.parse(savedState);
        checkboxes.forEach((checkbox, index) => {
            checkbox.checked = checked[index] || false;
        });
    }

    // Guardar estado al cambiar
    checkboxes.forEach((checkbox, index) => {
        checkbox.addEventListener('change', function() {
            const state = Array.from(checkboxes).map(cb => cb.checked);
            localStorage.setItem(storageKey, JSON.stringify(state));
        });
    });
});
</script>
