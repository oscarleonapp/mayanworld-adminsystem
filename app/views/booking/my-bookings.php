<?php
use App\Core\Config;
use App\Core\Helpers;
include __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Mis Reservas</h1>

    <?php if (!empty($bookings)): ?>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Tour</th>
                        <th>Personas</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= htmlspecialchars($b['codigo_reserva'] ?? '') ?></td>
                        <td><?= htmlspecialchars($b['tour_nombre'] ?? '') ?></td>
                        <td><?= (int)($b['numero_personas'] ?? 1) ?></td>
                        <td><span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($b['estado'] ?? 'pendiente') ?></span></td>
                        <td><?= htmlspecialchars(Helpers::formatDate($b['created_at'] ?? date('Y-m-d'))) ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="<?= Config::getBaseUrl() ?>?route=booking/confirm&code=<?= urlencode($b['codigo_reserva'] ?? '') ?>">Ver</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-info">Aún no tienes reservas registradas.</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

