<?php
use App\Core\Config;
use App\Core\Helpers;
require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            
            <!-- Progreso completado -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2">
                        <small class="text-success"><i class="fas fa-check-circle"></i> Selección</small>
                        <small class="text-success"><i class="fas fa-check-circle"></i> Datos y Pago</small>
                        <small class="text-success"><strong><i class="fas fa-check-circle"></i> ¡Confirmada!</strong></small>
                    </div>
                </div>
            </div>

            <!-- Mensaje de confirmación -->
            <div class="text-center mb-4">
                <div class="success-icon mb-3">
                    <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                </div>
                <h1 class="text-success mb-2">¡Reserva Confirmada!</h1>
                <p class="lead text-muted">Tu reserva ha sido procesada exitosamente</p>
            </div>

            <!-- Detalles de la reserva -->
            <div class="card shadow-lg mb-4">
                <div class="card-header bg-success text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-ticket-alt"></i> Detalles de la Reserva</h4>
                        <span class="badge bg-light text-dark fs-6"><?= htmlspecialchars($booking['codigo_reserva']) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Información del tour -->
                        <div class="col-md-6">
                            <h6 class="text-primary"><i class="fas fa-map-marked-alt"></i> Tour Reservado</h6>
                            <div class="mb-3">
                                <h5 class="mb-1"><?= htmlspecialchars($booking['tour_nombre']) ?></h5>
                                <span class="badge bg-info"><?= htmlspecialchars($booking['categoria_nombre'] ?? '') ?></span>
                            </div>
                            
                            <div class="booking-info">
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="fas fa-calendar text-muted"></i> Fecha de salida:</span>
                                    <strong><?= date('d/m/Y', strtotime($booking['fecha_salida'])) ?></strong>
                                </div>
                                <?php if ($booking['fecha_salida'] !== $booking['fecha_regreso']): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="fas fa-calendar text-muted"></i> Fecha de regreso:</span>
                                    <strong><?= date('d/m/Y', strtotime($booking['fecha_regreso'])) ?></strong>
                                </div>
                                <?php endif; ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="fas fa-users text-muted"></i> Personas:</span>
                                    <strong><?= $booking['numero_personas'] ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><i class="fas fa-clock text-muted"></i> Duración:</span>
                                    <strong><?= htmlspecialchars($booking['duracion'] ?? 'N/A') ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><i class="fas fa-dollar-sign text-muted"></i> Total pagado:</span>
                                    <strong class="text-success"><?= isset($paid_amount) ? Helpers::formatPrice($paid_amount) : Helpers::formatPrice($booking['precio_total']) ?></strong>
                                </div>
                                <?php if (isset($pending_amount) && $pending_amount > 0): ?>
                                <div class="d-flex justify-content-between mt-1">
                                    <span><i class="fas fa-exclamation-circle text-warning"></i> Pendiente:</span>
                                    <strong class="text-danger"><?= Helpers::formatPrice($pending_amount) ?></strong>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Información del cliente -->
                        <div class="col-md-6">
                            <h6 class="text-primary"><i class="fas fa-user"></i> Información del Cliente</h6>
                            <div class="client-info">
                                <p class="mb-1"><strong>Nombre:</strong> <?= htmlspecialchars($booking['cliente_nombre']) ?></p>
                                <p class="mb-1"><strong>Email:</strong> <?= htmlspecialchars($booking['cliente_email']) ?></p>
                                <p class="mb-1"><strong>Teléfono:</strong> <?= htmlspecialchars($booking['cliente_telefono']) ?></p>
                                <?php if (!empty($booking['cliente_direccion'])): ?>
                                    <p class="mb-1"><strong>Dirección:</strong> <?= htmlspecialchars($booking['cliente_direccion']) ?></p>
                                <?php endif; ?>
                                
                                <hr>
                                <p class="mb-1">
                                    <strong>Estado:</strong> 
                                    <span class="badge bg-<?= $booking['estado'] === 'pagada' ? 'success' : ($booking['estado'] === 'confirmada' ? 'info' : 'warning') ?>"><?= ucfirst($booking['estado']) ?></span>
                                    <?php if (($booking['estado'] === 'confirmada') && isset($pending_amount) && $pending_amount > 0): ?>
                                        <span class="badge bg-info ms-1">Confirmada por anticipo</span>
                                    <?php endif; ?>
                                </p>
                                <p class="mb-0">
                                    <strong>Método de pago:</strong> 
                                    <?php
                                    $metodoPago = [
                                        'transferencia' => 'Transferencia Bancaria',
                                        'efectivo' => 'Efectivo',
                                        'tarjeta' => 'Tarjeta de Crédito'
                                    ];
                                    echo $metodoPago[$booking['metodo_pago']] ?? ucfirst($booking['metodo_pago']);
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($booking['notas_cliente'])): ?>
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-primary"><i class="fas fa-sticky-note"></i> Notas del Cliente</h6>
                            <p class="text-muted mb-0"><?= nl2br(htmlspecialchars($booking['notas_cliente'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Instrucciones de pago / Completar pago -->
            <?php if ($booking['estado'] === 'pendiente'): ?>
            <div class="alert alert-secondary d-flex justify-content-between align-items-center mb-3">
                <div>
                    <i class="fas fa-info-circle me-2"></i>
                    Tu reserva está pendiente de pago. Puedes pagar ahora con tarjeta para confirmarla al instante.
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-primary" href="<?= Config::getBaseUrl() ?>?route=payment/checkout/<?= $booking['id'] ?>&type=deposit">
                        <i class="fas fa-credit-card me-1"></i> Anticipo <?= (int)(Config::DEPOSIT_RATE*100) ?>%
                    </a>
                    <a class="btn btn-primary" href="<?= Config::getBaseUrl() ?>?route=payment/checkout/<?= $booking['id'] ?>">
                        <i class="fas fa-credit-card me-1"></i> Pago total
                    </a>
                </div>
            </div>
            <?php elseif ($booking['estado'] === 'confirmada' && isset($pending_amount) && $pending_amount > 0): ?>
            <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
                <div>
                    <i class="fas fa-info-circle me-2"></i>
                    Tu reserva está confirmada por anticipo. Puedes pagar el saldo pendiente ahora.
                </div>
                <a class="btn btn-primary" href="<?= Config::getBaseUrl() ?>?route=payment/checkout/<?= $booking['id'] ?>&type=balance">
                    <i class="fas fa-credit-card me-1"></i> Pagar saldo
                </a>
            </div>
            <?php endif; ?>

            <?php if ($booking['metodo_pago'] === 'transferencia'): ?>
            <div class="card border-info mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-info"><i class="fas fa-university"></i> Instrucciones de Pago</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Datos para Transferencia Bancaria</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Banco:</strong> Banco Industrial</p>
                                <p class="mb-1"><strong>Tipo de cuenta:</strong> Monetaria</p>
                                <p class="mb-1"><strong>No. Cuenta:</strong> 123-456789-0</p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Nombre:</strong> Travel Mayan World</p>
                                <p class="mb-1"><strong>Monto:</strong> $<?= number_format($booking['precio_final'], 2) ?> USD</p>
                                <p class="mb-1"><strong>Referencia:</strong> <?= $booking['codigo_reserva'] ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a href="https://wa.me/<?= str_replace(['+', ' ', '-'], '', $company_info['whatsapp']) ?>?text=Hola,%20realicé%20transferencia%20para%20reserva%20<?= $booking['codigo_reserva'] ?>" 
                           target="_blank" 
                           class="btn btn-success">
                            <i class="fab fa-whatsapp"></i> Enviar Comprobante por WhatsApp
                        </a>
                        <a href="mailto:<?= $company_info['email'] ?>?subject=Comprobante%20Reserva%20<?= $booking['codigo_reserva'] ?>" 
                           class="btn btn-primary">
                            <i class="fas fa-envelope"></i> Enviar por Email
                        </a>
                    </div>
                    
                    <small class="text-muted">
                        <i class="fas fa-clock"></i> El comprobante debe enviarse dentro de 24 horas para confirmar la reserva.
                    </small>
                </div>
            </div>
            <?php elseif ($booking['metodo_pago'] === 'efectivo'): ?>
            <div class="card border-success mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-success"><i class="fas fa-money-bill-wave"></i> Pago en Efectivo</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <h6><i class="fas fa-map-marker-alt"></i> Ubicación de Pago</h6>
                        <p class="mb-1"><strong>Dirección:</strong> Oficina principal, Flores, Petén</p>
                        <p class="mb-1"><strong>Horario:</strong> Lunes a Domingo, 8:00 AM - 6:00 PM</p>
                        <p class="mb-1"><strong>Monto a pagar:</strong> $<?= number_format($booking['precio_final'], 2) ?> USD</p>
                        <p class="mb-0"><strong>Código de reserva:</strong> <?= $booking['codigo_reserva'] ?></p>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <a href="tel:<?= $company_info['phone'] ?>" class="btn btn-primary">
                            <i class="fas fa-phone"></i> Llamar para Coordinar
                        </a>
                        <a href="https://wa.me/<?= str_replace(['+', ' ', '-'], '', $company_info['whatsapp']) ?>?text=Hola,%20quiero%20coordinar%20pago%20en%20efectivo%20para%20reserva%20<?= $booking['codigo_reserva'] ?>" 
                           target="_blank" 
                           class="btn btn-success">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Próximos pasos -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list-check"></i> Próximos Pasos</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item d-flex mb-3">
                            <div class="timeline-marker bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px;">
                                <small>1</small>
                            </div>
                            <div>
                                <h6>Confirmación de Pago</h6>
                                <p class="text-muted mb-0">Procesa tu pago y envía el comprobante dentro de 24 horas.</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item d-flex mb-3">
                            <div class="timeline-marker bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px;">
                                <small>2</small>
                            </div>
                            <div>
                                <h6>Confirmación de Reserva</h6>
                                <p class="text-muted mb-0">Te contactaremos para confirmar detalles y punto de encuentro.</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item d-flex mb-3">
                            <div class="timeline-marker bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px;">
                                <small>3</small>
                            </div>
                            <div>
                                <h6>Información Pre-Tour</h6>
                                <p class="text-muted mb-0">Recibirás detalles sobre qué llevar y recomendaciones 1-2 días antes.</p>
                            </div>
                        </div>
                        
                        <div class="timeline-item d-flex">
                            <div class="timeline-marker bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px;">
                                <small>4</small>
                            </div>
                            <div>
                                <h6>¡Disfruta tu Aventura!</h6>
                                <p class="text-muted mb-0">Llega puntual al punto de encuentro y disfruta de tu experiencia maya.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="text-center mb-4">
                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="/" class="btn btn-outline-primary">
                        <i class="fas fa-home"></i> Volver al Inicio
                    </a>
                    <a href="/?route=tours" class="btn btn-outline-secondary">
                        <i class="fas fa-search"></i> Explorar Más Tours
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-info">
                        <i class="fas fa-print"></i> Imprimir Reserva
                    </button>
                </div>
            </div>

            <!-- Información de contacto -->
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h6><i class="fas fa-headset"></i> ¿Tienes Preguntas?</h6>
                    <p class="mb-2">Nuestro equipo está listo para ayudarte</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="tel:<?= $company_info['phone'] ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-phone"></i> <?= $company_info['phone'] ?>
                        </a>
                        <a href="mailto:<?= $company_info['email'] ?>" class="btn btn-sm btn-info">
                            <i class="fas fa-envelope"></i> Email
                        </a>
                        <a href="https://wa.me/<?= str_replace(['+', ' ', '-'], '', $company_info['whatsapp']) ?>?text=Hola,%20tengo%20preguntas%20sobre%20mi%20reserva%20<?= $booking['codigo_reserva'] ?>" 
                           target="_blank" 
                           class="btn btn-sm btn-success">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline-item {
    border-left: 2px solid #e9ecef;
    margin-left: 15px;
    padding-left: 20px;
    padding-bottom: 20px;
}

.timeline-item:last-child {
    border-left: none;
    padding-bottom: 0;
}

.timeline-marker {
    margin-left: -16px;
}

@media print {
    .btn, .card-header, nav, footer {
        display: none !important;
    }
    .card {
        border: 1px solid #ddd !important;
        box-shadow: none !important;
    }
}
</style>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
