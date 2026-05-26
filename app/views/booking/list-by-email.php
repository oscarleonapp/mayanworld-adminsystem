<?php
use App\Core\Config;
use App\Core\Helpers;
$title = 'Mis Reservas | Travel Mayan World';
$metaDescription = 'Lista de todas tus reservas';
include __DIR__ . '/../layouts/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Header -->
            <div class="mb-4">
                <a href="<?= Config::getBaseUrl() ?>?route=booking/find" class="btn btn-outline-secondary mb-3">
                    <i class="fas fa-arrow-left me-2"></i>Nueva Búsqueda
                </a>
                <h1 class="h2 mb-2">Mis Reservas</h1>
                <p class="text-muted">
                    <i class="fas fa-envelope me-2"></i>
                    Mostrando reservas para: <strong><?= htmlspecialchars($email) ?></strong>
                </p>
            </div>

            <!-- Bookings List -->
            <div class="row g-4">
                <?php foreach ($bookings as $booking):
                    $tour = $this->db->fetchOne("SELECT nombre, imagen_principal FROM tours WHERE id = ?", [$booking['tour_id']]);
                    $imagenTour = $tour['imagen_principal'] ?? 'default-tour.jpg';

                    // Determine status badge
                    $statusBadge = [
                        'pendiente' => '<span class="badge bg-warning">Pendiente</span>',
                        'confirmada' => '<span class="badge bg-info">Confirmada</span>',
                        'pagada' => '<span class="badge bg-success">Pagada</span>',
                        'cancelada' => '<span class="badge bg-danger">Cancelada</span>'
                    ];

                    $isRNPL = $booking['payment_method'] === 'rnpl';
                    $detailUrl = $isRNPL
                        ? Config::getBaseUrl() . '?route=rnpl/confirmation/' . $booking['id']
                        : Config::getBaseUrl() . '?route=booking/confirmation?id=' . $booking['id'];
                ?>
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100 hover-card">
                            <div class="row g-0">
                                <div class="col-4">
                                    <img src="<?= Config::getBaseUrl() ?>uploads/tours/<?= htmlspecialchars($imagenTour) ?>"
                                         class="img-fluid rounded-start h-100 object-fit-cover"
                                         alt="<?= htmlspecialchars($tour['nombre'] ?? 'Tour') ?>"
                                         style="min-height: 200px;"
                                         loading="lazy"
                                         decoding="async">
                                </div>
                                <div class="col-8">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title mb-0 small">
                                                <?= htmlspecialchars($tour['nombre'] ?? 'Tour') ?>
                                            </h5>
                                            <?= $statusBadge[$booking['estado']] ?? '' ?>
                                        </div>

                                        <div class="mb-3">
                                            <small class="text-muted d-block">
                                                <i class="fas fa-barcode me-1"></i>
                                                <strong>Código:</strong> <?= htmlspecialchars($booking['codigo_reserva']) ?>
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-calendar me-1"></i>
                                                <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($booking['fecha_salida'])) ?>
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-users me-1"></i>
                                                <strong>Personas:</strong> <?= $booking['numero_personas'] ?>
                                            </small>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-dollar-sign me-1"></i>
                                                <strong>Total:</strong> $<?= number_format($booking['precio_final'], 2) ?>
                                            </small>
                                            <?php if ($isRNPL): ?>
                                                <small class="text-primary d-block mt-2">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    <strong>RNPL:</strong> Paga después
                                                </small>
                                            <?php endif; ?>
                                        </div>

                                        <a href="<?= $detailUrl ?>" class="btn btn-primary btn-sm w-100">
                                            <i class="fas fa-eye me-2"></i>Ver Detalles
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Summary -->
            <div class="card bg-light border-0 mt-4">
                <div class="card-body text-center">
                    <p class="mb-0">
                        <i class="fas fa-check-circle text-success me-2"></i>
                        Total de reservas encontradas: <strong><?= count($bookings) ?></strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.hover-card {
    transition: all 0.3s ease;
    border-radius: 12px;
    overflow: hidden;
}

.hover-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
}

.object-fit-cover {
    object-fit: cover;
}
</style>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
