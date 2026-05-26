<?php
/**
 * Vista: Gestión de FAQs (Preguntas Frecuentes)
 * Con categorías, drag & drop ordering, y métricas de utilidad
 */

$pageTitle = 'Preguntas Frecuentes (FAQs)';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<div class="admin-faqs">
    <?php
        $actionTitle = 'Preguntas Frecuentes (FAQs)';
        $actionSubtitle = 'Gestiona las preguntas más comunes de tus clientes';
        $actionButtons = [
            [
                'label' => 'Nueva Pregunta',
                'icon' => 'fas fa-plus',
                'variant' => 'primary',
                'attributes' => [
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#modalFAQ'
                ]
            ]
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Estadísticas -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded p-3">
                                <i class="fas fa-question-circle text-primary fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total FAQs</div>
                            <div class="h4 mb-0"><?= count($faqs ?? []) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded p-3">
                                <i class="fas fa-check-circle text-success fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Activas</div>
                            <div class="h4 mb-0">
                                <?= count(array_filter($faqs ?? [], fn($f) => $f['activo'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded p-3">
                                <i class="fas fa-eye text-info fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Total Visitas</div>
                            <div class="h4 mb-0">
                                <?= array_sum(array_column($faqs ?? [], 'visitas')) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded p-3">
                                <i class="fas fa-thumbs-up text-warning fa-2x"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <div class="text-muted small">Útiles</div>
                            <div class="h4 mb-0">
                                <?php
                                $utilSiArray = array_column($faqs ?? [], 'util_si');
                                $totalUtilSi = 0;
                                foreach ($utilSiArray as $val) {
                                    $totalUtilSi += ($val ?? 0);
                                }
                                echo $totalUtilSi;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Buscar</label>
                    <input type="text" class="form-control" id="searchFAQs" placeholder="Buscar pregunta o respuesta...">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Categoría</label>
                    <select class="form-select" id="filterCategoria">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias ?? [] as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>">
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <select class="form-select" id="filterActivo">
                        <option value="">Todos</option>
                        <option value="1">Activas</option>
                        <option value="0">Inactivas</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de FAQs agrupados por categoría -->
    <div id="faqsContainer">
        <?php if (empty($faqsPorCategoria)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No hay preguntas frecuentes. Crea la primera para comenzar.
            </div>
        <?php else: ?>
            <?php foreach ($faqsPorCategoria as $categoria => $preguntas): ?>
                <div class="card mb-4 category-card" data-categoria="<?= htmlspecialchars($categoria) ?>">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-folder text-primary me-2"></i>
                                Categoría: <strong><?= htmlspecialchars($categoria ?: 'Sin categoría') ?></strong>
                                <span class="badge bg-secondary ms-2"><?= count($preguntas) ?> preguntas</span>
                            </h5>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 40px;"><i class="fas fa-grip-vertical text-muted"></i></th>
                                        <th>Pregunta</th>
                                        <th style="width: 120px;">Visitas</th>
                                        <th style="width: 150px;">Utilidad</th>
                                        <th style="width: 100px;">Estado</th>
                                        <th style="width: 150px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="sortable-faqs" data-categoria="<?= htmlspecialchars($categoria) ?>">
                                    <?php foreach ($preguntas as $faq): ?>
                                        <tr class="faq-row"
                                            data-id="<?= $faq['id'] ?>"
                                            data-activo="<?= $faq['activo'] ?>">
                                            <td class="text-center">
                                                <i class="fas fa-grip-vertical text-muted sortable-handle" style="cursor: move;"></i>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($faq['pregunta']) ?></strong>
                                                </div>
                                                <div class="text-muted small mt-1">
                                                    <?= htmlspecialchars(substr($faq['respuesta'], 0, 100)) ?>
                                                    <?= strlen($faq['respuesta']) > 100 ? '...' : '' ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info">
                                                    <i class="fas fa-eye me-1"></i>
                                                    <?= number_format($faq['vistas'] ?? 0) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $totalVotos = ($faq['util_si'] ?? 0) + ($faq['util_no'] ?? 0);
                                                $porcentajeUtil = $totalVotos > 0
                                                    ? round((($faq['util_si'] ?? 0) / $totalVotos) * 100)
                                                    : 0;
                                                ?>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="flex-grow-1">
                                                        <div class="progress" style="height: 8px;">
                                                            <div class="progress-bar bg-success"
                                                                 style="width: <?= $porcentajeUtil ?>%"></div>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted">
                                                        <?= $porcentajeUtil ?>%
                                                    </small>
                                                </div>
                                                <div class="d-flex gap-2 mt-1">
                                                    <small class="text-success">
                                                        <i class="fas fa-thumbs-up"></i> <?= $faq['util_si'] ?? 0 ?>
                                                    </small>
                                                    <small class="text-danger">
                                                        <i class="fas fa-thumbs-down"></i> <?= $faq['util_no'] ?? 0 ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input toggle-active"
                                                           type="checkbox"
                                                           data-id="<?= $faq['id'] ?>"
                                                           <?= $faq['activo'] ? 'checked' : '' ?>>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary btn-edit"
                                                            data-id="<?= $faq['id'] ?>"
                                                            title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger btn-delete"
                                                            data-id="<?= $faq['id'] ?>"
                                                            data-pregunta="<?= htmlspecialchars($faq['pregunta']) ?>"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Crear/Editar FAQ -->
<div class="modal fade" id="modalFAQ" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-question-circle me-2"></i>
                    <span id="modalTitle">Nueva Pregunta Frecuente</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formFAQ">
                <input type="hidden" name="id" id="faqId">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Categoría</label>
                            <input type="text"
                                   class="form-control"
                                   name="categoria"
                                   id="faqCategoria"
                                   list="categoriasList"
                                   placeholder="Reservas, Pagos, General...">
                            <datalist id="categoriasList">
                                <?php foreach ($categorias ?? [] as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Orden</label>
                            <input type="number"
                                   class="form-control"
                                   name="orden"
                                   id="faqOrden"
                                   value="0">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Pregunta *</label>
                            <input type="text"
                                   class="form-control form-control-lg"
                                   name="pregunta"
                                   id="faqPregunta"
                                   required
                                   placeholder="¿Cómo puedo hacer una reserva?">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Respuesta *</label>
                            <textarea class="form-control"
                                      name="respuesta"
                                      id="faqRespuesta"
                                      rows="6"
                                      required
                                      placeholder="Puedes hacer tu reserva directamente desde nuestro sitio web..."></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input"
                                       type="checkbox"
                                       name="activo"
                                       id="faqActivo"
                                       value="1"
                                       checked>
                                <label class="form-check-label" for="faqActivo">Pregunta activa</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>
                        Guardar Pregunta
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = new bootstrap.Modal(document.getElementById('modalFAQ'));
    const form = document.getElementById('formFAQ');

    // Crear/Editar FAQ
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const id = document.getElementById('faqId').value;

        // Asegurar que el checkbox activo se envíe correctamente
        const activoCheckbox = document.getElementById('faqActivo');
        if (!formData.has('activo') || !activoCheckbox.checked) {
            formData.set('activo', activoCheckbox.checked ? '1' : '0');
        }

        // Incluir el ID en el FormData para el método update
        if (id) {
            formData.set('id', id);
        }

        const url = id ? `?route=admin/faqs/update/${id}` : '?route=admin/faqs/store';

        try {
            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showToast('Pregunta guardada correctamente', 'success');
                // Quitar el foco antes de cerrar el modal para evitar warning de accesibilidad
                document.activeElement?.blur();
                modal.hide();
                setTimeout(() => location.reload(), 300);
            } else {
                showToast(result.message || 'Error al guardar', 'error');
            }
        } catch (error) {
            showToast('Error de conexión', 'error');
        }
    });

    // Editar FAQ
    document.querySelectorAll('.btn-edit').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;

            try {
                const response = await fetch(`?route=admin/faqs/edit/${id}`);
                const faq = await response.json();

                document.getElementById('modalTitle').textContent = 'Editar Pregunta';
                document.getElementById('faqId').value = faq.id;
                document.getElementById('faqCategoria').value = faq.categoria || '';
                document.getElementById('faqPregunta').value = faq.pregunta;
                document.getElementById('faqRespuesta').value = faq.respuesta;
                document.getElementById('faqOrden').value = faq.orden;
                document.getElementById('faqActivo').checked = faq.activo == 1;

                modal.show();
            } catch (error) {
                showToast('Error al cargar pregunta', 'error');
            }
        });
    });

    // Eliminar FAQ
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', async function() {
            const id = this.dataset.id;
            const pregunta = this.dataset.pregunta;

            if (!confirm(`¿Eliminar la pregunta "${pregunta}"?`)) return;

            try {
                const formData = new FormData();
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');

                const response = await fetch(`?route=admin/faqs/delete/${id}`, {
                    method: 'POST',
                    body: formData
                });

                // Debug: verificar respuesta
                const text = await response.text();
                console.log('Response text:', text);

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Error parsing JSON:', e);
                    showToast('Error: respuesta inválida del servidor', 'error');
                    return;
                }

                if (result.success) {
                    showToast('Pregunta eliminada', 'success');
                    this.closest('tr').remove();
                } else {
                    showToast(result.message || 'Error al eliminar', 'error');
                }
            } catch (error) {
                console.error('Error completo:', error);
                showToast('Error de conexión', 'error');
            }
        });
    });

    // Limpiar modal cuando se cierra
    document.getElementById('modalFAQ').addEventListener('hidden.bs.modal', function() {
        form.reset();
        document.getElementById('faqId').value = '';
        document.getElementById('modalTitle').textContent = 'Nueva Pregunta';
    });

    // Toggle activo
    document.querySelectorAll('.toggle-active').forEach(toggle => {
        toggle.addEventListener('change', async function() {
            const id = this.dataset.id;
            const activo = this.checked ? 1 : 0;

            try {
                const response = await fetch('?route=admin/faqs/toggle-active', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: id,
                        activo: activo,
                        csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showToast(activo ? 'Pregunta activada' : 'Pregunta desactivada', 'success');
                } else {
                    this.checked = !this.checked;
                    showToast('Error al cambiar estado', 'error');
                }
            } catch (error) {
                this.checked = !this.checked;
                showToast('Error de conexión', 'error');
            }
        });
    });

    // Sortable (drag & drop por categoría)
    document.querySelectorAll('.sortable-faqs').forEach(tbody => {
        new Sortable(tbody, {
            handle: '.sortable-handle',
            animation: 150,
            onEnd: async function(evt) {
                const orden = Array.from(evt.to.children).map(tr => tr.dataset.id);
                const categoria = evt.to.dataset.categoria;

                try {
                    const response = await fetch('?route=admin/faqs/update-order', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            orden: orden,
                            categoria: categoria,
                            csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
                        })
                    });

                    const result = await response.json();
                    if (result.success) {
                        showToast('Orden actualizado', 'success');
                    }
                } catch (error) {
                    showToast('Error al actualizar orden', 'error');
                }
            }
        });
    });

    // Filtros
    ['searchFAQs', 'filterCategoria', 'filterActivo'].forEach(id => {
        document.getElementById(id).addEventListener('input', filterFAQs);
    });

    function filterFAQs() {
        const search = document.getElementById('searchFAQs').value.toLowerCase();
        const categoria = document.getElementById('filterCategoria').value;
        const activo = document.getElementById('filterActivo').value;

        document.querySelectorAll('.category-card').forEach(card => {
            const cardCategoria = card.dataset.categoria;
            const matchCategoria = categoria === '' || cardCategoria === categoria;

            card.style.display = matchCategoria ? '' : 'none';
        });

        document.querySelectorAll('.faq-row').forEach(row => {
            const pregunta = row.querySelector('strong').textContent.toLowerCase();
            const respuesta = row.querySelector('.text-muted').textContent.toLowerCase();
            const rowActivo = row.dataset.activo;

            const matchSearch = search === '' || pregunta.includes(search) || respuesta.includes(search);
            const matchActivo = activo === '' || rowActivo === activo;

            row.style.display = matchSearch && matchActivo ? '' : 'none';
        });
    }

    // Resetear modal al abrir nuevo
    document.querySelector('[data-bs-target="#modalFAQ"]').addEventListener('click', function() {
        document.getElementById('modalTitle').textContent = 'Nueva Pregunta Frecuente';
        form.reset();
        document.getElementById('faqId').value = '';
    });

    function showToast(message, type) {
        alert(message); // Implementar toast
    }
});
</script>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
