<?php

use App\Core\Config;
use App\Core\Helpers;
$title = $title ?? 'Detalle de Traslado';
$metaDescription = $metaDescription ?? 'Información del traslado';
$extraStyles = ['css/components/transfers.css', 'css/components/cards.css'];
include __DIR__ . '/../layouts/header.php';
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= Config::getBaseUrl() ?>">Inicio</a></li>
            <li class="breadcrumb-item"><a href="<?= Config::getBaseUrl() ?>?route=transfers">Traslados</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($route['nombre']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <?php if (!empty($route['imagen'])): ?>
                    <div class="transfer-detail-image">
                        <img src="<?= Helpers::asset($route['imagen']) ?>"
                             alt="<?= htmlspecialchars($route['nombre']) ?>"
                             class="img-fluid w-100"
                             style="max-height: 400px; object-fit: cover; border-radius: 16px 16px 0 0;"
                             loading="lazy"
                             decoding="async"
                             onerror="this.parentElement.style.display='none'">
                    </div>
                <?php else: ?>
                    <div class="transfer-detail-image bg-gradient" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%); height: 400px; display: flex; align-items: center; justify-content: center; border-radius: 16px 16px 0 0;">
                        <i class="fas fa-bus fa-5x text-white opacity-50"></i>
                    </div>
                <?php endif; ?>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h1 class="h3 fw-bold mb-2"><?= htmlspecialchars($route['nombre']) ?></h1>
                            <div class="transfer-route-inline">
                                <span class="text-success"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($route['origen']) ?></span>
                                <i class="fas fa-arrow-right text-muted mx-2"></i>
                                <span class="text-danger"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($route['destino']) ?></span>
                            </div>
                        </div>
                        <?php if ($route['precio']): ?>
                            <div class="text-end">
                                <div class="fs-2 fw-bold text-primary">$<?= number_format($route['precio'], 2) ?></div>
                                <small class="text-muted">USD por vehículo</small>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($route['descripcion']): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3">Descripción</h5>
                            <p class="text-muted"><?= nl2br(htmlspecialchars($route['descripcion'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Route Info -->
                    <div class="row g-3 mb-4">
                        <?php if ($route['distancia_km']): ?>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <i class="fas fa-road text-primary"></i>
                                    <div>
                                        <small class="text-muted d-block">Distancia</small>
                                        <strong><?= number_format($route['distancia_km'], 1) ?> km</strong>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($route['duracion_estimada']): ?>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <i class="fas fa-clock text-primary"></i>
                                    <div>
                                        <small class="text-muted d-block">Duración</small>
                                        <strong><?= htmlspecialchars($route['duracion_estimada']) ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($route['dias_operacion'])): ?>
                            <div class="col-md-4">
                                <div class="info-box">
                                    <i class="fas fa-calendar text-primary"></i>
                                    <div>
                                        <small class="text-muted d-block">Disponibilidad</small>
                                        <strong>
                                            <?php
                                            if (is_array($route['dias_operacion'])) {
                                                $diasMap = [
                                                    'lunes' => 'Lun',
                                                    'martes' => 'Mar',
                                                    'miercoles' => 'Mié',
                                                    'jueves' => 'Jue',
                                                    'viernes' => 'Vie',
                                                    'sabado' => 'Sáb',
                                                    'domingo' => 'Dom'
                                                ];
                                                $diasAbreviados = array_map(function($dia) use ($diasMap) {
                                                    return $diasMap[strtolower($dia)] ?? $dia;
                                                }, $route['dias_operacion']);
                                                echo implode(', ', $diasAbreviados);
                                            } else {
                                                echo htmlspecialchars($route['dias_operacion']);
                                            }
                                            ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($route['horarios']) && is_array($route['horarios'])): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-clock me-2"></i>Horarios de Salida</h5>
                            <div class="row g-3">
                                <?php foreach ($route['horarios'] as $horario): ?>
                                    <div class="col-md-6">
                                        <div class="card bg-light border-0">
                                            <div class="card-body p-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                            <i class="fas fa-clock fs-5"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars($horario['hora'] ?? '') ?></h6>
                                                        <small class="text-muted"><?= htmlspecialchars($horario['lugar'] ?? 'Punto de encuentro') ?></small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($route['paradas_intermedias']) && is_array($route['paradas_intermedias'])): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-map-signs me-2"></i>Paradas Intermedias</h5>
                            <ul class="list-group">
                                <?php foreach ($route['paradas_intermedias'] as $index => $parada): ?>
                                    <li class="list-group-item d-flex align-items-center">
                                        <span class="badge bg-primary rounded-circle me-3" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                                            <?= $index + 1 ?>
                                        </span>
                                        <span><?= htmlspecialchars($parada) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if ($route['requisitos']): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-clipboard-list me-2"></i>Requisitos</h5>
                            <div class="alert alert-warning">
                                <?= nl2br(htmlspecialchars($route['requisitos'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($route['notas_importantes']): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-exclamation-triangle me-2"></i>Notas Importantes</h5>
                            <div class="alert alert-danger">
                                <?= nl2br(htmlspecialchars($route['notas_importantes'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($transporte): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-bus me-2"></i>Información del Vehículo</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-2"><strong>Tipo:</strong> <?= htmlspecialchars($transporte['tipo'] ?? 'No especificado') ?></p>
                                            <p class="mb-2"><strong>Marca/Modelo:</strong> <?= htmlspecialchars($transporte['marca'] ?? '') ?> <?= htmlspecialchars($transporte['modelo'] ?? '') ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <?php if (isset($transporte['capacidad_pasajeros'])): ?>
                                                <p class="mb-2"><strong>Capacidad:</strong> <?= $transporte['capacidad_pasajeros'] ?> pasajeros</p>
                                            <?php endif; ?>
                                            <?php if (isset($transporte['placa'])): ?>
                                                <p class="mb-2"><strong>Placa:</strong> <?= htmlspecialchars($transporte['placa']) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($route['conductor_nombre'])): ?>
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3"><i class="fas fa-user-tie me-2"></i>Tu Conductor</h5>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($route['conductor_foto'])): ?>
                                            <img src="<?= Config::getBaseUrl() ?>uploads/staff/<?= htmlspecialchars($route['conductor_foto']) ?>"
                                                 alt="<?= htmlspecialchars($route['conductor_nombre'] ?? 'Conductor') ?>"
                                                 class="rounded-circle me-3"
                                                 style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                                 loading="lazy"
                                                 decoding="async"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="rounded-circle me-3 bg-primary d-none align-items-center justify-content-center"
                                                 style="width: 80px; height: 80px; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                <i class="fas fa-user fa-2x text-white"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="rounded-circle me-3 bg-primary d-flex align-items-center justify-content-center"
                                                 style="width: 80px; height: 80px; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                                <i class="fas fa-user fa-2x text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="fw-bold mb-1"><?= htmlspecialchars($route['conductor_nombre']) ?></h6>
                                            <p class="text-muted mb-0">
                                                <i class="fas fa-star text-warning"></i>
                                                <i class="fas fa-star text-warning"></i>
                                                <i class="fas fa-star text-warning"></i>
                                                <i class="fas fa-star text-warning"></i>
                                                <i class="fas fa-star text-warning"></i>
                                                <small class="ms-1">Conductor profesional</small>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Related Routes -->
            <?php if (!empty($relatedRoutes)): ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="fw-bold mb-3">Traslados Relacionados</h5>
                        <div class="row g-3">
                            <?php foreach ($relatedRoutes as $related): ?>
                                <div class="col-md-6">
                                    <div class="related-route-card">
                                        <h6 class="fw-bold"><?= htmlspecialchars($related['nombre']) ?></h6>
                                        <p class="small text-muted mb-2">
                                            <?= htmlspecialchars($related['origen']) ?> → <?= htmlspecialchars($related['destino']) ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-primary">$<?= number_format($related['precio'], 2) ?></span>
                                            <a href="<?= Config::getBaseUrl() ?>?route=transfer/<?= $related['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 100px;">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Reservar Traslado</h5>

                    <form id="bookingForm">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre completo *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="telefono" class="form-label">Teléfono *</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" required>
                        </div>

                        <div class="mb-3">
                            <label for="fecha" class="form-label">Fecha de viaje *</label>
                            <input type="date" class="form-control" id="fecha" name="fecha" required min="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="personas" class="form-label">Número de personas *</label>
                            <input type="number" class="form-control" id="personas" name="personas" min="1" value="1" required>
                        </div>

                        <div class="mb-3">
                            <label for="comentarios" class="form-label">Comentarios adicionales</label>
                            <textarea class="form-control" id="comentarios" name="comentarios" rows="3"></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Solicitar Reserva
                            </button>
                        </div>

                        <small class="text-muted d-block mt-3">
                            <i class="fas fa-info-circle me-1"></i>
                            Nos contactaremos contigo para confirmar disponibilidad y detalles.
                        </small>
                    </form>

                    <div id="bookingResult" class="mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('<?= Config::getBaseUrl() ?>?route=transfer/quote/<?= $route['id'] ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        const resultDiv = document.getElementById('bookingResult');
        resultDiv.style.display = 'block';
        if (data.success) {
            resultDiv.className = 'alert alert-success';
            resultDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + data.message;
            this.reset();
        } else {
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + data.message;
        }
        window.scrollTo({top: 0, behavior: 'smooth'});
    });
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
