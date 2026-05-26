<?php
use App\Core\Config;
use App\Core\Helpers;
include __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
  <!-- Page Header -->
  <?php
    $actionTitle = 'Gestión de Reseñas';
    $actionSubtitle = 'Administra las reseñas y testimonios de clientes';
    $pendingCount = array_sum(array_map(function($r) { return ($r['aprobado'] ?? 1) == 0 ? 1 : 0; }, $reviews ?? []));
    $actionButtons = [
      ['label' => 'Pendientes', 'icon' => 'fas fa-clock', 'variant' => 'outline-warning', 'href' => Config::getBaseUrl() . '?route=admin/reviews&status=0', 'badge' => $pendingCount, 'badgeClass' => 'bg-warning text-dark'],
      ['label' => 'Nueva Reseña', 'icon' => 'fas fa-plus', 'variant' => 'primary', 'onclick' => "new bootstrap.Modal(document.getElementById('newReviewModal')).show()"],
    ];
    include __DIR__ . '/../partials/admin_action_bar.php';
  ?>

  <!-- Filters -->
  <div class="card shadow mb-4">
    <div class="card-body">
      <form class="row g-3" method="get" action="">
        <input type="hidden" name="route" value="admin/reviews">
        <div class="col-md-3">
          <label for="status" class="form-label">Estado</label>
          <select name="status" id="status" class="form-select" onchange="this.form.submit()">
            <option value="">Todas las reseñas</option>
            <option value="0" <?= $status_filter==='0' ? 'selected' : '' ?>>Pendientes de aprobación</option>
            <option value="1" <?= $status_filter==='1' ? 'selected' : '' ?>>Aprobadas</option>
          </select>
        </div>
      </form>
    </div>
  </div>

  <!-- Reviews Table -->
  <div class="card shadow">
    <div class="card-header bg-white">
      <h6 class="m-0 font-weight-bold text-primary">
        Lista de Reseñas (<?= count($reviews) ?>)
      </h6>
    </div>
    <div class="card-body p-0">
      <?php if (!empty($reviews)): ?>
      <div class="table-responsive">
        <table class="table table-hover table-sticky align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th width="60">ID</th>
              <th>Tour</th>
              <th>Cliente</th>
              <th width="120">Rating</th>
              <th>Comentario</th>
              <th width="100">Fecha</th>
              <th width="100">Estado</th>
              <th width="180">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reviews as $r): ?>
            <tr>
              <td><?= (int)$r['id'] ?></td>
              <td>
                <?php if (!empty($r['tour_id'])): ?>
                  <span class="badge bg-info">Tour #<?= (int)$r['tour_id'] ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary">General</span>
                <?php endif; ?>
              </td>
              <td>
                <strong><?= htmlspecialchars($r['nombre']) ?></strong>
              </td>
              <td>
                <div class="text-warning">
                  <?php for ($i=1;$i<=5;$i++): ?>
                    <i class="<?= $i <= (int)$r['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                  <?php endfor; ?>
                  <span class="ms-1 small text-muted">(<?= (int)$r['rating'] ?>)</span>
                </div>
              </td>
              <td class="small">
                <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                  <?= htmlspecialchars($r['comentario']) ?>
                </div>
              </td>
              <td class="small text-muted">
                <?= date('d/m/Y', strtotime($r['created_at'])) ?>
              </td>
              <td>
                <span class="badge bg-<?= $r['aprobado'] ? 'success' : 'warning' ?>">
                  <?= $r['aprobado'] ? 'Aprobada' : 'Pendiente' ?>
                </span>
              </td>
              <td>
                <div class="btn-group btn-group-sm">
                  <button class="btn btn-outline-info" onclick="viewReview(<?= $r['id'] ?>)" title="Ver completa">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="btn btn-outline-primary" onclick="editReview(<?= $r['id'] ?>)" title="Editar">
                    <i class="fas fa-edit"></i>
                  </button>
                  <?php if (!$r['aprobado']): ?>
                  <a class="btn btn-outline-success" href="<?= Config::getBaseUrl() ?>?route=admin/reviews/approve/<?= (int)$r['id'] ?>" title="Aprobar">
                    <i class="fas fa-check"></i>
                  </a>
                  <?php else: ?>
                  <a class="btn btn-outline-danger" href="<?= Config::getBaseUrl() ?>?route=admin/reviews/reject/<?= (int)$r['id'] ?>" title="Rechazar">
                    <i class="fas fa-times"></i>
                  </a>
                  <?php endif; ?>
                  <button class="btn btn-outline-danger" onclick="deleteReview(<?= $r['id'] ?>)" title="Eliminar">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="text-center py-5">
        <i class="fas fa-star fa-4x text-muted mb-4"></i>
        <h4 class="text-muted mb-3">No hay reseñas</h4>
        <p class="text-muted mb-4">
          <?php if (!empty($_GET['status'])): ?>
            No hay reseñas que coincidan con los filtros aplicados.
          <?php else: ?>
            Aún no hay reseñas registradas en el sistema.
          <?php endif; ?>
        </p>
        <button class="btn btn-primary" onclick="new bootstrap.Modal(document.getElementById('newReviewModal')).show()">
          <i class="fas fa-plus me-2"></i>Agregar Primera Reseña
        </button>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Modal para Nueva Reseña -->
<div class="modal fade" id="newReviewModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-plus me-2"></i>Nueva Reseña
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form id="newReviewForm" action="<?= Config::getBaseUrl() ?>?route=admin/reviews/create" method="POST">
        <div class="modal-body">
          <div class="mb-3">
            <label for="tour_id" class="form-label">Tour (opcional)</label>
            <select class="form-select" id="tour_id" name="tour_id">
              <option value="">General (sin tour específico)</option>
              <?php
              $db = \App\Core\Database::getInstance();
              $tours = $db->fetchAll("SELECT id, nombre FROM tours WHERE activo = 1 ORDER BY nombre");
              foreach ($tours as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">Si no seleccionas un tour, será una reseña general de la agencia</small>
          </div>

          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre del Cliente *</label>
            <input type="text" class="form-control" id="nombre" name="nombre" required>
          </div>

          <div class="mb-3">
            <label for="rating" class="form-label">Rating *</label>
            <div class="rating-input">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
                <label for="star<?= $i ?>">
                  <i class="fas fa-star"></i>
                </label>
              <?php endfor; ?>
            </div>
          </div>

          <div class="mb-3">
            <label for="comentario" class="form-label">Comentario *</label>
            <textarea class="form-control" id="comentario" name="comentario" rows="4" required></textarea>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="aprobado" name="aprobado" value="1" checked>
            <label class="form-check-label" for="aprobado">
              Aprobar automáticamente
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Guardar Reseña
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal para Ver Reseña -->
<div class="modal fade" id="viewReviewModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalles de la Reseña</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="reviewDetails">
        <div class="text-center py-4">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal para Editar Reseña -->
<div class="modal fade" id="editReviewModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-edit me-2"></i>Editar Reseña
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form id="editReviewForm" action="" method="POST">
        <div class="modal-body">
          <input type="hidden" id="edit_review_id" name="review_id">

          <div class="mb-3">
            <label for="edit_tour_id" class="form-label">Tour (opcional)</label>
            <select class="form-select" id="edit_tour_id" name="tour_id">
              <option value="">General (sin tour específico)</option>
              <?php
              $db = \App\Core\Database::getInstance();
              $tours = $db->fetchAll("SELECT id, nombre FROM tours WHERE activo = 1 ORDER BY nombre");
              foreach ($tours as $p): ?>
                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">Si no seleccionas un tour, será una reseña general de la agencia</small>
          </div>

          <div class="mb-3">
            <label for="edit_nombre" class="form-label">Nombre del Cliente *</label>
            <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
          </div>

          <div class="mb-3">
            <label for="edit_rating" class="form-label">Rating *</label>
            <div class="rating-input" id="edit_rating_stars">
              <?php for ($i = 5; $i >= 1; $i--): ?>
                <input type="radio" name="rating" id="edit_star<?= $i ?>" value="<?= $i ?>">
                <label for="edit_star<?= $i ?>">
                  <i class="fas fa-star"></i>
                </label>
              <?php endfor; ?>
            </div>
          </div>

          <div class="mb-3">
            <label for="edit_comentario" class="form-label">Comentario *</label>
            <textarea class="form-control" id="edit_comentario" name="comentario" rows="4" required></textarea>
          </div>

          <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="edit_aprobado" name="aprobado" value="1">
            <label class="form-check-label" for="edit_aprobado">
              Aprobada
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i>Guardar Cambios
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
.rating-input {
  display: flex;
  flex-direction: row-reverse;
  justify-content: flex-end;
  gap: 0.25rem;
}

.rating-input input[type="radio"] {
  display: none;
}

.rating-input label {
  cursor: pointer;
  font-size: 2rem;
  color: #ddd;
  transition: color 0.2s;
}

.rating-input label:hover,
.rating-input label:hover ~ label,
.rating-input input[type="radio"]:checked ~ label {
  color: #ffc107;
}
</style>

<script>
function viewReview(id) {
  const modal = new bootstrap.Modal(document.getElementById('viewReviewModal'));
  const detailsDiv = document.getElementById('reviewDetails');

  detailsDiv.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div></div>';
  modal.show();

  // Buscar la reseña en el array de PHP
  const reviews = <?= json_encode($reviews) ?>;
  const review = reviews.find(r => r.id == id);

  if (review) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
      stars += `<i class="fas fa-star ${i <= review.rating ? 'text-warning' : 'text-muted'}"></i> `;
    }

    detailsDiv.innerHTML = `
      <div class="mb-3">
        <strong>Cliente:</strong><br>
        ${review.nombre}
      </div>
      <div class="mb-3">
        <strong>Rating:</strong><br>
        ${stars}
      </div>
      <div class="mb-3">
        <strong>Tour:</strong><br>
        ${review.tour_id ? `Tour #${review.tour_id}` : 'Reseña General'}
      </div>
      <div class="mb-3">
        <strong>Comentario:</strong><br>
        <div class="bg-light p-3 rounded">${review.comentario}</div>
      </div>
      <div class="mb-3">
        <strong>Fecha:</strong><br>
        ${new Date(review.created_at).toLocaleDateString('es-ES')}
      </div>
      <div>
        <strong>Estado:</strong><br>
        <span class="badge bg-${review.aprobado ? 'success' : 'warning'}">${review.aprobado ? 'Aprobada' : 'Pendiente'}</span>
      </div>
    `;
  }
}

function editReview(id) {
  const modal = new bootstrap.Modal(document.getElementById('editReviewModal'));

  // Obtener datos de la reseña
  fetch('<?= Config::getBaseUrl() ?>?route=admin/reviews/edit/' + id)
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      const review = data.review;

      // Llenar el formulario
      document.getElementById('edit_review_id').value = review.id;
      document.getElementById('edit_nombre').value = review.nombre || '';
      document.getElementById('edit_comentario').value = review.comentario || '';

      // Seleccionar tour
      const tourSelect = document.getElementById('edit_tour_id');
      tourSelect.value = review.tour_id || '';

      // Seleccionar rating
      const ratingInput = document.querySelector(`#edit_rating_stars input[value="${review.rating || 5}"]`);
      if (ratingInput) {
        ratingInput.checked = true;
      }

      // Checkbox aprobado
      document.getElementById('edit_aprobado').checked = review.aprobado == 1;

      // Actualizar action del formulario
      document.getElementById('editReviewForm').action = '<?= Config::getBaseUrl() ?>?route=admin/reviews/update/' + review.id;

      modal.show();
    } else {
      alert('Error al cargar la reseña');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error al cargar la reseña');
  });
}

function deleteReview(id) {
  if (!confirm('¿Estás seguro de eliminar esta reseña? Esta acción no se puede deshacer.')) {
    return;
  }

  fetch('<?= Config::getBaseUrl() ?>?route=admin/reviews/delete/' + id, {
    method: 'POST'
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      location.reload();
    } else {
      alert('Error al eliminar la reseña');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error al eliminar la reseña');
  });
}
</script>

<?php include __DIR__ . '/../layouts/admin_footer.php'; ?>
