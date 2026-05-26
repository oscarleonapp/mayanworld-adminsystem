<?php 
use App\Core\Config;
use App\Core\Helpers;
require_once __DIR__ . '/../../layouts/admin_header.php'; ?>

<div class="container-fluid p-4">
    <?php
        $actionTitle = isset($testimonial) ? 'Editar Reseña' : 'Nueva Reseña';
        $actionSubtitle = 'Completa los datos de la reseña del cliente';
        $actionButtons = [
            ['label' => 'Volver', 'icon' => 'fas fa-arrow-left', 'variant' => 'secondary', 'href' => Config::getBaseUrl() . '?route=admin/testimonials'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <?php if ($errorMessage = Helpers::getFlashMessage('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= $errorMessage ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre del Cliente <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre"
                                           value="<?= htmlspecialchars($testimonial['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="calificacion" class="form-label">Calificación <span class="text-danger">*</span></label>
                                    <select class="form-select" id="calificacion" name="calificacion" required>
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <option value="<?= $i ?>" <?= isset($testimonial) && $testimonial['calificacion'] == $i ? 'selected' : '' ?>>
                                                <?= $i ?> Estrella<?= $i > 1 ? 's' : '' ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="comentario" class="form-label">Comentario/Reseña <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="comentario" name="comentario" rows="5" required><?= htmlspecialchars($testimonial['comentario'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            <div class="form-text">Comentario completo del cliente</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fuente" class="form-label">Fuente <span class="text-danger">*</span></label>
                                    <select class="form-select" id="fuente" name="fuente" required>
                                        <option value="google" <?= isset($testimonial) && $testimonial['fuente'] == 'google' ? 'selected' : '' ?>>Google</option>
                                        <option value="tripadvisor" <?= isset($testimonial) && $testimonial['fuente'] == 'tripadvisor' ? 'selected' : '' ?>>TripAdvisor</option>
                                        <option value="facebook" <?= isset($testimonial) && $testimonial['fuente'] == 'facebook' ? 'selected' : '' ?>>Facebook</option>
                                        <option value="manual" <?= isset($testimonial) && $testimonial['fuente'] == 'manual' ? 'selected' : '' ?>>Manual</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_resena" class="form-label">Fecha de Reseña <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="fecha_resena" name="fecha_resena"
                                           value="<?= $testimonial['fecha_resena'] ?? date('Y-m-d') ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="url_fuente" class="form-label">URL de la Reseña (opcional)</label>
                            <input type="url" class="form-control" id="url_fuente" name="url_fuente"
                                   value="<?= htmlspecialchars($testimonial['url_fuente'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="https://...">
                            <div class="form-text">Link directo a la reseña original</div>
                        </div>

                        <div class="mb-3">
                            <label for="avatar" class="form-label">Avatar del Cliente (opcional)</label>
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
                            <div class="form-text">Formatos: JPG, PNG, WebP. Máximo 2MB.</div>
                            <?php if (isset($testimonial) && $testimonial['avatar']): ?>
                                <div class="mt-2">
                                    <img src="<?= Helpers::asset($testimonial['avatar']) ?>" alt="Avatar actual"
                                         class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                                    <small class="text-muted ms-2">Avatar actual</small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="orden" class="form-label">Orden</label>
                                    <input type="number" class="form-control" id="orden" name="orden"
                                           value="<?= $testimonial['orden'] ?? 0 ?>" min="0">
                                    <div class="form-text">Menor número = aparece primero</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" id="activo" name="activo"
                                               <?= !isset($testimonial) || $testimonial['activo'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="activo">
                                            Activo (visible)
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" id="destacado" name="destacado"
                                               <?= isset($testimonial) && $testimonial['destacado'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="destacado">
                                            ⭐ Destacado en home
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i><?= isset($testimonial) ? 'Actualizar' : 'Crear' ?> Reseña
                            </button>
                            <a href="<?= Config::getBaseUrl() ?>?route=admin/testimonials" class="btn btn-secondary">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Ayuda</h6>
                </div>
                <div class="card-body">
                    <p class="small"><strong>Destacado:</strong> Las reseñas marcadas como destacadas aparecerán en la página de inicio.</p>
                    <p class="small"><strong>Orden:</strong> Controla el orden de aparición. Menor número = aparece primero.</p>
                    <p class="small"><strong>Fuente:</strong> Indica de dónde proviene la reseña para mostrar el logo correspondiente.</p>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Consejos</h6>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li>Usa reseñas reales de clientes para mayor credibilidad</li>
                        <li>Mantén activas solo las reseñas más recientes (últimos 6 meses)</li>
                        <li>Incluye el avatar cuando sea posible para mayor personalización</li>
                        <li>Varía las fuentes (Google, TripAdvisor, Facebook) para demostrar presencia</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
