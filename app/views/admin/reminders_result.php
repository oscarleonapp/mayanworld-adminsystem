<?php
use App\Core\Helpers;
include __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container py-5">
  <?php
    $actionTitle = 'Recordatorios de saldo pendiente';
    $actionSubtitle = 'Fecha objetivo: ' . htmlspecialchars($date) . ' (en ' . (int)$days . ' días)';
    $actionButtons = [];
    include __DIR__ . '/../partials/admin_action_bar.php';
  ?>

  <?php if (empty($results)): ?>
    <div class="alert alert-info">No se encontraron reservas con saldo pendiente para la fecha indicada.</div>
  <?php else: ?>
    <div class="card">
      <div class="card-header bg-light">
        <strong><?= count($results) ?></strong> recordatorio(s) procesados
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>Código</th>
                <th>Email</th>
                <th>Pendiente</th>
                <th>WhatsApp</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($results as $row): ?>
                <tr>
                  <td><?= htmlspecialchars($row['code']) ?></td>
                  <td><?= htmlspecialchars($row['email']) ?></td>
                  <td><span class="badge bg-warning text-dark"><?= Helpers::formatPrice($row['pending']) ?></span></td>
                  <td>
                    <a class="btn btn-sm btn-success" target="_blank" rel="noopener" href="<?= $row['whatsapp_link'] ?>">
                      <i class="fab fa-whatsapp"></i> Abrir chat
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/admin_footer.php'; ?>
