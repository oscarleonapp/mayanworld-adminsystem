<?php
use App\Core\Config;
$title = $title ?? 'Configurar Métodos de Pago';
include __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/dashboard">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/tours">
                            <i class="fas fa-box"></i> Tours
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/tours/edit/<?= $product['id'] ?>">
                            <?= htmlspecialchars($product['nombre']) ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Métodos de Pago
                    </li>
                </ol>
            </nav>

            <?php
                $actionTitle = 'Métodos de Pago';
                $actionSubtitle = 'Tour: ' . htmlspecialchars($product['nombre']);
                $actionButtons = [
                    ['label' => 'Volver al tour', 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary', 'href' => Config::getBaseUrl() . '?route=admin/tours/edit/' . $product['id']],
                ];
                include __DIR__ . '/../../partials/admin_action_bar.php';
            ?>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Selección de Pasarelas
                    </h4>
                </div>

                <div class="card-body">
                    <!-- Información -->
                    <div class="alert alert-info border-left-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Instrucciones:</strong> Selecciona los métodos de pago que estarán disponibles para este tour.
                        Los clientes solo verán las opciones que habilites aquí durante el checkout.
                    </div>

                    <!-- Formulario -->
                    <form method="POST" action="<?= Config::getBaseUrl() ?>?route=admin/tours/payment-gateways/<?= $product['id'] ?>" id="gatewayForm">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

                        <div class="row g-4">
                            <?php foreach ($available_gateways as $gateway): ?>
                                <?php
                                $info = $gateway_info[$gateway];
                                $isEnabled = in_array($gateway, $current_gateways);
                                ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="gateway-card h-100 <?= $isEnabled ? 'gateway-card-selected' : '' ?>" data-gateway="<?= $gateway ?>">
                                        <div class="card h-100 shadow-sm border-2">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input gateway-checkbox"
                                                           type="checkbox"
                                                           name="payment_gateways[]"
                                                           value="<?= $gateway ?>"
                                                           id="gateway_<?= $gateway ?>"
                                                           <?= $isEnabled ? 'checked' : '' ?>>
                                                    <label class="form-check-label w-100 cursor-pointer" for="gateway_<?= $gateway ?>">
                                                        <!-- Icono y Título -->
                                                        <div class="d-flex align-items-center mb-3">
                                                            <div class="gateway-icon me-3" style="color: <?= $info['color'] ?>">
                                                                <i class="fas <?= $info['icon'] ?> fa-3x"></i>
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <h5 class="mb-0 fw-bold"><?= htmlspecialchars($info['display_name']) ?></h5>
                                                            </div>
                                                        </div>

                                                        <!-- Descripción -->
                                                        <p class="text-muted small mb-3">
                                                            <?= htmlspecialchars($info['description']) ?>
                                                        </p>

                                                        <!-- Metadata -->
                                                        <div class="gateway-metadata">
                                                            <!-- Monedas -->
                                                            <div class="mb-2">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-money-bill-wave me-1"></i>
                                                                    <strong>Monedas:</strong>
                                                                </small>
                                                                <div class="mt-1">
                                                                    <?php foreach ($info['currencies'] as $currency): ?>
                                                                        <span class="badge bg-secondary"><?= $currency ?></span>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>

                                                            <!-- Países -->
                                                            <div class="mb-2">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-globe me-1"></i>
                                                                    <strong>Disponible en:</strong>
                                                                </small>
                                                                <div class="mt-1">
                                                                    <?php foreach ($info['countries'] as $country): ?>
                                                                        <span class="badge bg-info"><?= $country ?></span>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>

                                                            <!-- Tipo de pago -->
                                                            <div class="mb-0">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-tag me-1"></i>
                                                                    <strong>Tipo:</strong>
                                                                </small>
                                                                <div class="mt-1">
                                                                    <?php
                                                                    $typeLabels = [
                                                                        'immediate' => 'Pago Inmediato',
                                                                        'deferred' => 'Pago Diferido',
                                                                        'link' => 'Link de Pago',
                                                                        'redirect' => 'Redirección Externa'
                                                                    ];
                                                                    $typeLabel = $typeLabels[$info['payment_type']] ?? $info['payment_type'];
                                                                    ?>
                                                                    <span class="badge bg-primary"><?= $typeLabel ?></span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Check icon cuando está seleccionado -->
                                                        <div class="selected-indicator">
                                                            <i class="fas fa-check-circle"></i>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Contador de selección -->
                        <div class="mt-4">
                            <div class="alert alert-secondary d-flex align-items-center" role="alert">
                                <i class="fas fa-check-square fa-2x me-3"></i>
                                <div>
                                    <strong id="selectionCounter">
                                        <?= count($current_gateways) ?> método(s) de pago seleccionado(s)
                                    </strong>
                                    <br>
                                    <small class="text-muted">
                                        Se requiere al menos 1 método de pago activo
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg" id="saveButton">
                                <i class="fas fa-save me-2"></i>
                                Guardar Configuración
                            </button>
                            <a href="<?= Config::getBaseUrl() ?>?route=admin/tours/edit/<?= $product['id'] ?>"
                               class="btn btn-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.gateway-card {
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.gateway-card:hover {
    transform: translateY(-5px);
}

.gateway-card .card {
    border-color: #dee2e6;
    transition: all 0.3s ease;
}

.gateway-card:hover .card {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.gateway-card-selected .card {
    border-color: #0d6efd !important;
    background-color: #f8f9fa;
}

.gateway-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 70px;
    height: 70px;
    border-radius: 10px;
    background: rgba(0, 0, 0, 0.03);
}

.selected-indicator {
    position: absolute;
    top: 10px;
    right: 10px;
    color: #0d6efd;
    font-size: 1.5rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.gateway-card-selected .selected-indicator {
    opacity: 1;
}

.form-check-input:checked ~ .form-check-label {
    color: inherit;
}

.cursor-pointer {
    cursor: pointer;
}

.border-left-info {
    border-left: 4px solid #0dcaf0;
}

.badge {
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('gatewayForm');
    const checkboxes = document.querySelectorAll('.gateway-checkbox');
    const counter = document.getElementById('selectionCounter');
    const saveButton = document.getElementById('saveButton');

    // Actualizar UI cuando cambian los checkboxes
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateUI);

        // También permitir click en la card
        const card = checkbox.closest('.gateway-card');
        card.addEventListener('click', function(e) {
            // Evitar doble toggle si se hace click directo en el checkbox
            if (e.target !== checkbox && !e.target.closest('.form-check-input')) {
                checkbox.checked = !checkbox.checked;
                updateUI();
            }
        });
    });

    function updateUI() {
        const selectedCount = document.querySelectorAll('.gateway-checkbox:checked').length;

        // Actualizar contador
        counter.textContent = `${selectedCount} método(s) de pago seleccionado(s)`;

        // Actualizar clases de cards
        checkboxes.forEach(checkbox => {
            const card = checkbox.closest('.gateway-card');
            if (checkbox.checked) {
                card.classList.add('gateway-card-selected');
            } else {
                card.classList.remove('gateway-card-selected');
            }
        });

        // Deshabilitar botón si no hay selección
        saveButton.disabled = selectedCount === 0;
    }

    // Validación antes de enviar
    form.addEventListener('submit', function(e) {
        const selectedCount = document.querySelectorAll('.gateway-checkbox:checked').length;

        if (selectedCount === 0) {
            e.preventDefault();
            alert('Debes seleccionar al menos un método de pago');
            return false;
        }

        // Confirmación
        const confirmed = confirm(`¿Confirmas guardar ${selectedCount} método(s) de pago para este tour?`);
        if (!confirmed) {
            e.preventDefault();
            return false;
        }
    });

    // Inicializar UI
    updateUI();
});
</script>

<?php include __DIR__ . '/../../layouts/admin_footer.php'; ?>
