<?php
use App\Core\Config;
include __DIR__ . '/../../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <?php
      $actionTitle = 'Crear Tour';
      $actionSubtitle = 'Agrega un nuevo destino o experiencia de viaje';
      $actionButtons = [
        ['label' => 'Volver a Tours', 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary', 'href' => Config::getBaseUrl() . '?route=admin/tours'],
      ];
      include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Error:</strong>
            <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-plus-circle me-2"></i>Información del Tour</h6>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">

                <!-- ① TIPO DE TOUR (primera decisión) -->
                <div class="card border-primary mb-4">
                    <div class="card-header bg-primary text-white fw-semibold">
                        <i class="fas fa-tag me-2"></i>Tipo de Tour
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-4 flex-wrap mb-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="es_privado" id="tipo_normal" value="0"
                                       <?= empty($data['es_privado']) ? 'checked' : '' ?> onchange="onTipoChange()">
                                <label class="form-check-label fw-semibold" for="tipo_normal">
                                    <i class="fas fa-users text-success me-1"></i>Tour Grupal / Normal
                                    <div class="text-muted fw-normal small">Precio fijo por persona, capacidad abierta.</div>
                                </label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="es_privado" id="tipo_privado" value="1"
                                       <?= !empty($data['es_privado']) ? 'checked' : '' ?> onchange="onTipoChange()">
                                <label class="form-check-label fw-semibold" for="tipo_privado">
                                    <i class="fas fa-lock text-primary me-1"></i>Tour Privado
                                    <div class="text-muted fw-normal small">Precio varía según tamaño del grupo.</div>
                                </label>
                            </div>
                        </div>

                        <!-- Precios grupales (solo privado) -->
                        <div id="precios-grupo-section" style="<?= empty($data['es_privado']) ? 'display:none' : '' ?>">
                            <hr class="my-2">
                            <label class="form-label fw-semibold mb-2">Precios por tamaño de grupo</label>
                            <p class="text-muted small mb-2">Define el precio por persona según la cantidad de personas del grupo.</p>
                            <div class="mb-1 d-flex gap-2" style="font-size:.78rem;color:#6c757d;font-weight:600;">
                                <span style="width:90px;flex-shrink:0;">Desde (personas)</span>
                                <span style="width:90px;flex-shrink:0;">Hasta <small class="fw-normal">(vacío=∞)</small></span>
                                <span style="flex:1;">Precio / persona (USD)</span>
                                <span style="width:32px;"></span>
                            </div>
                            <div id="pg-builder"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-1" onclick="pgAdd()">
                                <i class="fas fa-plus me-1"></i>Agregar tramo
                            </button>
                            <input type="hidden" id="precios_grupo" name="precios_grupo" value="<?= htmlspecialchars($data['precios_grupo'] ?? '') ?>">
                            <p class="text-muted small mt-2 mb-0"><i class="fas fa-info-circle me-1"></i>El "Precio base" de abajo es el fallback si no se configuran tramos.</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Información Básica -->
                    <div class="col-md-8">
                        <h5 class="mb-3">Información Básica</h5>

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Tour <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre"
                                   value="<?= htmlspecialchars($data['nombre'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion_corta" class="form-label">Descripción Corta</label>
                            <textarea class="form-control" id="descripcion_corta" name="descripcion_corta"
                                      rows="2" maxlength="200"><?= htmlspecialchars($data['descripcion_corta'] ?? '') ?></textarea>
                            <div class="form-text">Máximo 200 caracteres</div>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción Completa <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="descripcion" name="descripcion"
                                      rows="5" required><?= htmlspecialchars($data['descripcion'] ?? '') ?></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="precio" class="form-label">Precio base (USD) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="precio" name="precio"
                                           value="<?= htmlspecialchars($data['precio'] ?? '') ?>" required>
                                </div>
                                <small class="form-text text-muted">Precio cuando no hay tramos por grupo.</small>
                            </div>
                            <div class="col-md-3">
                                <label for="precio_nino" class="form-label">Precio Niños (USD)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="precio_nino" name="precio_nino"
                                           value="<?= htmlspecialchars($data['precio_nino'] ?? '') ?>" placeholder="Opcional">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="duracion" class="form-label">Duración</label>
                                <input type="text" class="form-control" id="duracion" name="duracion"
                                       value="<?= htmlspecialchars($data['duracion'] ?? '') ?>"
                                       placeholder="ej: 3 días, 5 horas">
                            </div>
                            <div class="col-md-3">
                                <label for="categoria_id" class="form-label">Categoría <span class="text-danger">*</span></label>
                                <select class="form-select" id="categoria_id" name="categoria_id" required>
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= $categoria['id'] ?>"
                                                <?= (isset($data['categoria_id']) && $data['categoria_id'] == $categoria['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($categoria['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Detalles -->
                    <div class="col-md-4">
                        <h5 class="mb-3">Detalles</h5>

                        <div class="mb-3">
                            <label for="ubicacion" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="ubicacion" name="ubicacion"
                                   value="<?= htmlspecialchars($data['ubicacion'] ?? '') ?>" placeholder="Ciudad, País">
                        </div>

                        <div class="mb-3">
                            <label for="dificultad" class="form-label">Dificultad</label>
                            <select class="form-select" id="dificultad" name="dificultad">
                                <option value="facil" <?= ($data['dificultad'] ?? '') == 'facil' ? 'selected' : '' ?>>Fácil</option>
                                <option value="moderado" <?= ($data['dificultad'] ?? '') == 'moderado' ? 'selected' : '' ?>>Moderado</option>
                                <option value="dificil" <?= ($data['dificultad'] ?? '') == 'dificil' ? 'selected' : '' ?>>Difícil</option>
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label for="grupo_min" class="form-label">Grupo Mín.</label>
                                <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control" id="grupo_min" name="grupo_min"
                                       value="<?= htmlspecialchars($data['grupo_min'] ?? '1') ?>">
                            </div>
                            <div class="col-6">
                                <label for="grupo_max" class="form-label">Capacidad Máxima</label>
                                <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control" id="grupo_max" name="grupo_max"
                                       value="<?= htmlspecialchars($data['grupo_max'] ?? '20') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="edad_min" class="form-label">Edad Mínima del Tour</label>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control" id="edad_min" name="edad_min"
                                   value="<?= htmlspecialchars($data['edad_min'] ?? '0') ?>">
                            <div class="form-text">Edad mínima requerida para participar en el tour.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rango de Edad para Niños</label>
                            <div class="d-flex align-items-center gap-2">
                                <div class="flex-fill">
                                    <select class="form-select" id="edad_min_nino" name="edad_min_nino">
                                        <?php for ($a = 1; $a <= 12; $a++): ?>
                                        <option value="<?= $a ?>" <?= (int)($data['edad_min_nino'] ?? 1) === $a ? 'selected' : '' ?>><?= $a ?> año<?= $a > 1 ? 's' : '' ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="form-text text-center">Desde</div>
                                </div>
                                <span class="fw-semibold text-muted">—</span>
                                <div class="flex-fill">
                                    <select class="form-select" id="edad_max_nino" name="edad_max_nino">
                                        <?php for ($a = 1; $a <= 12; $a++): ?>
                                        <option value="<?= $a ?>" <?= (int)($data['edad_max_nino'] ?? 7) === $a ? 'selected' : '' ?>><?= $a ?> años</option>
                                        <?php endfor; ?>
                                    </select>
                                    <div class="form-text text-center">Hasta</div>
                                </div>
                            </div>
                            <div class="form-text">Rango de edades que aplica para el precio de niño.</div>
                        </div>

                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="activo" selected>Activo</option>
                                <option value="inactivo">Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Imágenes -->
                <hr class="my-4">
                <h5 class="mb-3">Imágenes</h5>
                <style>
                .upload-area { border:2px dashed #dee2e6; border-radius:8px; padding:18px; text-align:center; cursor:pointer; transition:border-color .15s; }
                .upload-area:hover { border-color:#0d6efd; background:#f0f5ff; }
                .img-thumb-wrap { position:relative; display:inline-block; }
                .img-thumb-wrap img { width:90px; height:70px; object-fit:cover; border-radius:6px; border:1px solid #dee2e6; }
                .img-thumb-wrap .img-del { position:absolute; top:-6px; right:-6px; width:20px; height:20px; border-radius:50%; background:#dc3545; color:#fff; border:none; font-size:.7rem; cursor:pointer; display:flex; align-items:center; justify-content:center; }
                </style>
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Imagen Principal</label>
                        <div id="main-img-new-preview" class="mb-2"></div>
                        <input type="hidden" name="imagen_principal" value="">
                        <label for="imagen_principal_file" class="upload-area d-block">
                            <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-1"></i><br>
                            <span class="text-muted small">Haz clic para seleccionar imagen</span><br>
                            <small class="text-muted">JPG, PNG, WEBP · máx. 5 MB</small>
                        </label>
                        <input type="file" class="d-none" id="imagen_principal_file" name="imagen_principal_file"
                               accept="image/jpeg,image/png,image/jpg,image/webp">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Imágenes Adicionales</label>
                        <div id="gallery-new-preview" class="d-flex flex-wrap gap-2 mb-2"></div>
                        <label for="imagenes_files" class="upload-area d-block">
                            <i class="fas fa-images fa-2x text-muted mb-1"></i><br>
                            <span class="text-muted small">Haz clic para agregar imágenes</span><br>
                            <small class="text-muted">Puedes seleccionar varias a la vez · JPG, PNG, WEBP · máx. 5 MB c/u</small>
                        </label>
                        <input type="file" class="d-none" id="imagenes_files" name="imagenes_files[]"
                               accept="image/jpeg,image/png,image/jpg,image/webp" multiple>
                    </div>
                </div>

                <!-- Incluye / No incluye -->
                <hr class="my-4">
                <h5 class="mb-3">Qué incluye y no incluye</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="incluye" class="form-label">Qué incluye</label>
                            <textarea class="form-control" id="incluye" name="incluye" rows="5"
                                      placeholder="- Transporte&#10;- Guía turístico&#10;- Almuerzo"><?= htmlspecialchars($data['incluye'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="no_incluye" class="form-label">Qué NO incluye</label>
                            <textarea class="form-control" id="no_incluye" name="no_incluye" rows="5"
                                      placeholder="- Bebidas alcohólicas&#10;- Propinas&#10;- Gastos personales"><?= htmlspecialchars($data['no_incluye'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Información Adicional -->
                <hr class="my-4">
                <h5 class="mb-3">Información Adicional</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="que_llevar" class="form-label">Qué llevar</label>
                            <textarea class="form-control" id="que_llevar" name="que_llevar" rows="4"
                                      placeholder="- Ropa cómoda&#10;- Protector solar&#10;- Cámara fotográfica"><?= htmlspecialchars($data['que_llevar'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="politicas" class="form-label">Políticas de Cancelación</label>
                            <textarea class="form-control" id="politicas" name="politicas" rows="4"><?= htmlspecialchars($data['politicas'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="itinerario" class="form-label">Itinerario Detallado</label>
                    <textarea class="form-control" id="itinerario" name="itinerario" rows="6"><?= htmlspecialchars($data['itinerario'] ?? '') ?></textarea>
                </div>

                <!-- Disponibilidad -->
                <hr class="my-4">
                <h5 class="mb-3">Disponibilidad</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="disponible_desde" class="form-label">Disponible Desde</label>
                            <input type="date" class="form-control" id="disponible_desde" name="disponible_desde"
                                   value="<?= htmlspecialchars($data['disponible_desde'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="disponible_hasta" class="form-label">Disponible Hasta</label>
                            <input type="date" class="form-control" id="disponible_hasta" name="disponible_hasta"
                                   value="<?= htmlspecialchars($data['disponible_hasta'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <hr class="my-4">
                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= Config::getBaseUrl() ?>?route=admin/tours" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Crear Tour
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ---- Tipo de tour ----
function onTipoChange() {
    const privado = document.getElementById('tipo_privado').checked;
    document.getElementById('precios-grupo-section').style.display = privado ? '' : 'none';
}

// ---- Precios por grupo ----
function pgSync() {
    const rows = document.querySelectorAll('#pg-builder .pg-row');
    const data = [];
    rows.forEach(row => {
        const desde  = parseInt(row.querySelector('.pg-desde').value) || 1;
        const hastaV = row.querySelector('.pg-hasta').value.trim();
        const hasta  = hastaV === '' ? null : parseInt(hastaV);
        const precio = parseFloat(row.querySelector('.pg-precio').value) || 0;
        if (precio > 0) data.push({ desde, hasta, precio });
    });
    document.getElementById('precios_grupo').value = data.length ? JSON.stringify(data) : '';
}

function pgAdd(desde = '', hasta = '', precio = '') {
    const builder = document.getElementById('pg-builder');
    const row = document.createElement('div');
    row.className = 'pg-row d-flex gap-2 mb-1 align-items-center';
    row.innerHTML = `
        <input type="number" min="1" class="form-control form-control-sm pg-desde" value="${desde}" placeholder="1" style="width:90px;flex-shrink:0;">
        <input type="number" min="1" class="form-control form-control-sm pg-hasta" value="${hasta}" placeholder="∞" style="width:90px;flex-shrink:0;">
        <div class="input-group input-group-sm" style="flex:1;">
            <span class="input-group-text">$</span>
            <input type="number" step="0.01" min="0" class="form-control pg-precio" value="${precio}" placeholder="0.00">
        </div>
        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('.pg-row').remove();pgSync();">
            <i class="fas fa-trash-alt"></i>
        </button>`;
    row.querySelectorAll('input').forEach(i => i.addEventListener('input', pgSync));
    builder.appendChild(row);
    if (!precio) row.querySelector('.pg-desde').focus();
    pgSync();
}

// ---- Preview imágenes ----
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('imagen_principal_file').addEventListener('change', function() {
        const file = this.files[0];
        const preview = document.getElementById('main-img-new-preview');
        if (!file) { preview.innerHTML = ''; return; }
        const reader = new FileReader();
        reader.onload = e => {
            preview.innerHTML = `<div class="img-thumb-wrap"><img src="${e.target.result}" alt=""></div>
                <div class="mt-1"><small class="text-success"><i class="fas fa-check me-1"></i>Imagen lista para subir</small></div>`;
        };
        reader.readAsDataURL(file);
    });

    document.getElementById('imagenes_files').addEventListener('change', function() {
        const preview = document.getElementById('gallery-new-preview');
        preview.innerHTML = '';
        Array.from(this.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = e => {
                const wrap = document.createElement('div');
                wrap.className = 'img-thumb-wrap';
                wrap.innerHTML = `<img src="${e.target.result}" alt="">`;
                preview.appendChild(wrap);
            };
            reader.readAsDataURL(file);
        });
    });

    // Inicializar tramos si hay data previa
    const rawPG = document.getElementById('precios_grupo')?.value;
    if (rawPG) {
        try {
            JSON.parse(rawPG).forEach(p => pgAdd(p.desde ?? '', p.hasta ?? '', p.precio ?? ''));
        } catch(e) {}
    }
});
</script>

<?php include __DIR__ . '/../../layouts/admin_footer.php'; ?>
