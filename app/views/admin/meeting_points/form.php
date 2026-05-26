<?php
use App\Core\Config;
use App\Core\Helpers;
$pageTitle = isset($meetingPoint['id']) ? 'Editar Punto de Encuentro' : 'Nuevo Punto de Encuentro';
require_once __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800"><?= $pageTitle ?></h1>
        </div>
        <a href="<?= Config::getBaseUrl() ?>?route=admin/meeting-points" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Volver
        </a>
    </div>

    <?php Helpers::displayFlashMessage(); ?>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="type" class="form-label">Tipo de Punto <span class="text-danger">*</span></label>
                            <select class="form-select" name="type" id="type" required>
                                <option value="standard" <?= ($meetingPoint['type'] ?? '') === 'standard' ? 'selected' : '' ?>>Punto de Encuentro Estándar</option>
                                <option value="hotel_pickup" <?= ($meetingPoint['type'] ?? '') === 'hotel_pickup' ? 'selected' : '' ?>>Recogida en Hotel (Genérico)</option>
                            </select>
                            <small class="text-muted">Si seleccionas "Recogida en Hotel", el cliente podrá ingresar su hotel.</small>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?= htmlspecialchars($meetingPoint['title'] ?? '') ?>" required>
                            <small class="text-muted">Nombre del lugar (ej. "Aeropuerto Internacional La Aurora")</small>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Dirección</label>
                            <textarea class="form-control" id="address" name="address" rows="2"><?= htmlspecialchars($meetingPoint['address'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="map_link" class="form-label">Enlace de Google Maps</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <input type="url" class="form-control" id="map_link" name="map_link" 
                                       value="<?= htmlspecialchars($meetingPoint['map_link'] ?? '') ?>" placeholder="https://maps.google.com/...">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción / Instrucciones</label>
                            <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($meetingPoint['description'] ?? '') ?></textarea>
                            <small class="text-muted">Instrucciones específicas para encontrar el punto de encuentro.</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card bg-light mb-3">
                            <div class="card-header">Configuración</div>
                            <div class="card-body">
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                           <?= (!isset($meetingPoint['is_active']) || $meetingPoint['is_active']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="is_active">Activo</label>
                                </div>
                                <hr>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save me-2"></i>Guardar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../layouts/admin_footer.php'; ?>
