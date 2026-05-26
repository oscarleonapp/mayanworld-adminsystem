<?php
use App\Core\Config;
include __DIR__ . '/../../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <?php
      $actionTitle = 'Editar Tour';
      $actionSubtitle = 'Modifica la información del destino o experiencia de viaje';
      $actionButtons = [
        ['label' => 'Volver a Tours', 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary', 'href' => Config::getBaseUrl() . '?route=admin/tours'],
        ['label' => 'Ver Tour', 'icon' => 'fas fa-eye', 'variant' => 'outline-info', 'href' => Config::getBaseUrl() . '?route=tour/' . ($product['id'] ?? ''), 'target' => '_blank'],
        ['label' => 'Configurar Métodos de Pago', 'icon' => 'fas fa-credit-card', 'variant' => 'outline-primary', 'href' => Config::getBaseUrl() . '?route=admin/tours/payment-gateways/' . ($product['id'] ?? '')],
      ];
      include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Error:</strong>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tour Form -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-edit me-2"></i>
                Información del Tour
                <?php if (!empty($product['id'])): ?>
                    <small class="text-muted">#<?= $product['id'] ?></small>
                <?php endif; ?>
            </h6>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <!-- Información Básica -->
                    <div class="col-md-8">
                        <h5 class="mb-3">Información Básica</h5>
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Tour <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?= htmlspecialchars($product['nombre'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion_corta" class="form-label">Descripción Corta</label>
                            <textarea class="form-control" id="descripcion_corta" name="descripcion_corta" 
                                      rows="2" maxlength="200"><?= htmlspecialchars($product['descripcion_corta'] ?? '') ?></textarea>
                            <div class="form-text">Máximo 200 caracteres</div>
                        </div>

                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción Completa <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="descripcion" name="descripcion" 
                                      rows="5" required><?= htmlspecialchars($product['descripcion'] ?? '') ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <label for="precio" class="form-label">Precio base (USD) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="precio" name="precio"
                                           value="<?= htmlspecialchars($product['precio'] ?? '') ?>" required>
                                </div>
                                <small class="form-text text-muted">Se usa cuando no hay tramos por grupo.</small>
                            </div>
                            <div class="col-md-3">
                                <label for="precio_nino" class="form-label">Precio Niños (USD)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" step="0.01" class="form-control" id="precio_nino" name="precio_nino"
                                           value="<?= htmlspecialchars($product['precio_nino'] ?? '') ?>" placeholder="Opcional">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Duración</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="input-group">
                                            <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control" id="duracion_dias" name="duracion_dias"
                                                   value="<?= (int)($product['duracion_dias'] ?? 0) ?>">
                                            <span class="input-group-text">días</span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group">
                                            <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control" id="duracion_horas" name="duracion_horas"
                                                   value="<?= (int)($product['duracion_horas'] ?? 0) ?>">
                                            <span class="input-group-text">hrs</span>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="duracion" id="duracion" value="<?= htmlspecialchars($product['duracion'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="categoria_id" class="form-label">Categoría <span class="text-danger">*</span></label>
                                <select class="form-select" id="categoria_id" name="categoria_id" required>
                                    <option value="">Seleccionar categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= $categoria['id'] ?>" 
                                                <?= (isset($product['categoria_id']) && $product['categoria_id'] == $categoria['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($categoria['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Precios por grupo -->
                        <hr class="my-3">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="es_privado" name="es_privado" value="1"
                                       <?= !empty($product['es_privado']) ? 'checked' : '' ?> onchange="togglePreciosGrupo()">
                                <label class="form-check-label fw-semibold" for="es_privado">Tour privado con precios por grupo</label>
                            </div>
                        </div>

                        <div id="precios-grupo-section" style="<?= empty($product['es_privado']) ? 'display:none' : '' ?>">
                            <p class="text-muted small mb-2">Define el precio por persona según la cantidad de personas del grupo.</p>
                            <div class="mb-1 d-flex gap-2" style="font-size:.78rem;color:#6c757d;font-weight:600;">
                                <span style="width:80px;flex-shrink:0;">Desde</span>
                                <span style="width:80px;flex-shrink:0;">Hasta <small class="fw-normal">(vacío=∞)</small></span>
                                <span style="flex:1;">Precio / persona (USD)</span>
                                <span style="width:30px;"></span>
                            </div>
                            <div id="pg-builder"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-1" onclick="pgAdd()">
                                <i class="fas fa-plus me-1"></i>Agregar tramo
                            </button>
                            <input type="hidden" id="precios_grupo" name="precios_grupo" value="<?= htmlspecialchars($product['precios_grupo'] ?? '') ?>">
                        </div>
                    </div>

                    <!-- Detalles del Tour -->
                    <div class="col-md-4">
                        <h5 class="mb-3">Detalles</h5>
                        
                        <div class="mb-3">
                            <label for="ubicacion" class="form-label">Ubicación</label>
                            <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                                   value="<?= htmlspecialchars($product['ubicacion'] ?? '') ?>" 
                                   placeholder="Ciudad, País">
                        </div>

                        <div class="mb-3">
                            <label for="dificultad" class="form-label">Dificultad</label>
                            <select class="form-select" id="dificultad" name="dificultad">
                                <option value="facil" <?= (isset($product['dificultad']) && $product['dificultad'] == 'facil') ? 'selected' : '' ?>>Fácil</option>
                                <option value="moderado" <?= (isset($product['dificultad']) && $product['dificultad'] == 'moderado') ? 'selected' : '' ?>>Moderado</option>
                                <option value="dificil" <?= (isset($product['dificultad']) && $product['dificultad'] == 'dificil') ? 'selected' : '' ?>>Difícil</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-6">
                                <label for="grupo_min" class="form-label">Grupo Mín.</label>
                                <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control" id="grupo_min" name="grupo_min"
                                       value="<?= htmlspecialchars($product['grupo_min'] ?? '1') ?>">
                            </div>
                            <div class="col-6">
                                <label for="grupo_max" class="form-label">Capacidad Máxima</label>
                                <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control" id="grupo_max" name="grupo_max"
                                       value="<?= htmlspecialchars($product['capacidad_maxima'] ?? '20') ?>">
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label for="edad_min" class="form-label">Edad Mínima</label>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control" id="edad_min" name="edad_min"
                                   value="<?= htmlspecialchars($product['edad_min'] ?? '0') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="estado" class="form-label">Estado</label>
                            <select class="form-select" id="estado" name="estado">
                                <option value="activo" <?= (isset($product['activo']) && $product['activo'] == 1) ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= (isset($product['activo']) && $product['activo'] == 0) ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Puntos de Encuentro -->
                <hr class="my-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Puntos de Encuentro</h5>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAssignPoint">
                        <i class="fas fa-plus me-1"></i>Asignar Punto
                    </button>
                </div>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Selecciona los puntos de encuentro habilitados para este tour desde el catálogo global.
                </div>

                <div id="meeting-points-container" class="mb-4">
                     <!-- Ajax content -->
                     <div class="text-center py-4 text-muted">
                        <i class="fas fa-spinner fa-spin me-2"></i>Cargando puntos asignados...
                     </div>
                </div>

                <!-- Imágenes -->
                <hr class="my-4">
                <h5 class="mb-3">Imágenes</h5>
                <style>
                .img-thumb-wrap { position:relative; display:inline-block; }
                .img-thumb-wrap img { width:90px; height:70px; object-fit:cover; border-radius:6px; border:1px solid #dee2e6; }
                .img-thumb-wrap .img-del { position:absolute; top:-6px; right:-6px; width:20px; height:20px; border-radius:50%; background:#dc3545; color:#fff; border:none; font-size:.7rem; line-height:1; cursor:pointer; display:flex; align-items:center; justify-content:center; }
                .upload-area { border:2px dashed #dee2e6; border-radius:8px; padding:18px; text-align:center; cursor:pointer; transition:border-color .15s; }
                .upload-area:hover { border-color:#0d6efd; background:#f0f5ff; }
                </style>

                <div class="row">
                    <!-- Imagen Principal -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Imagen Principal</label>
                            <!-- Preview actual -->
                            <div id="main-img-preview" class="mb-2">
                                <?php if (!empty($product['imagen_principal'])): ?>
                                    <div class="img-thumb-wrap">
                                        <img src="<?= htmlspecialchars($product['imagen_principal']) ?>" alt="Principal">
                                    </div>
                                    <div class="mt-1"><small class="text-muted">Imagen actual — sube una nueva para reemplazarla</small></div>
                                <?php endif; ?>
                            </div>
                            <!-- Preview nueva imagen seleccionada -->
                            <div id="main-img-new-preview" class="mb-2"></div>
                            <!-- Input oculto para URL (backend lo usa como fallback) -->
                            <input type="hidden" id="imagen_principal" name="imagen_principal"
                                   value="<?= htmlspecialchars($product['imagen_principal'] ?? '') ?>">
                            <!-- Upload area -->
                            <label for="imagen_principal_file" class="upload-area d-block">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-1"></i><br>
                                <span class="text-muted small">Haz clic para seleccionar imagen</span><br>
                                <small class="text-muted">JPG, PNG, WEBP · máx. 5 MB</small>
                            </label>
                            <input type="file" class="d-none" id="imagen_principal_file" name="imagen_principal_file"
                                   accept="image/jpeg,image/png,image/jpg,image/webp">
                        </div>
                    </div>

                    <!-- Imágenes Adicionales (Galería) -->
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Imágenes Adicionales</label>
                            <!-- Miniaturas imágenes existentes -->
                            <div id="gallery-existing" class="d-flex flex-wrap gap-2 mb-2">
                                <?php
                                $existingImages = !empty($product['galeria'])
                                    ? array_filter(array_map('trim', explode(',', $product['galeria'])))
                                    : [];
                                foreach ($existingImages as $imgUrl): ?>
                                    <div class="img-thumb-wrap gallery-existing-item">
                                        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="">
                                        <input type="hidden" name="imagenes[]" value="<?= htmlspecialchars($imgUrl) ?>">
                                        <button type="button" class="img-del" title="Eliminar" onclick="removeExistingGalleryImg(this)">×</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <!-- Preview nuevas imágenes seleccionadas -->
                            <div id="gallery-new-preview" class="d-flex flex-wrap gap-2 mb-2"></div>
                            <!-- Upload area múltiple -->
                            <label for="imagenes_files" class="upload-area d-block">
                                <i class="fas fa-images fa-2x text-muted mb-1"></i><br>
                                <span class="text-muted small">Haz clic para agregar imágenes</span><br>
                                <small class="text-muted">Puedes seleccionar varias a la vez · JPG, PNG, WEBP · máx. 5 MB c/u</small>
                            </label>
                            <input type="file" class="d-none" id="imagenes_files" name="imagenes_files[]"
                                   accept="image/jpeg,image/png,image/jpg,image/webp" multiple>
                        </div>
                    </div>
                </div>

                <!-- Incluye / No incluye -->
                <hr class="my-4">
                <h5 class="mb-3">Qué incluye y no incluye</h5>
                <p class="text-muted small mb-3"><i class="fas fa-info-circle me-1"></i>Cada ítem es un punto separado. Presiona <kbd>Enter</kbd> para agregar otro rápidamente.</p>
                <style>
                    .lb-row:hover .lb-del { opacity: 1; }
                    .lb-del { opacity: 0.3; transition: opacity .15s; }
                    .lb-preview { background: #f8f9fa; border-radius: 8px; padding: 10px 14px; font-size: .85rem; min-height: 36px; }
                    .lb-preview ul { margin: 0; padding-left: 1.2rem; }
                    .lb-preview li { margin-bottom: 2px; }
                </style>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-check-circle text-success me-1"></i>Qué incluye
                            </label>
                            <div id="builder-incluye" data-field="incluye" data-icon="check" data-color="success" class="mb-2"></div>
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="listBuilderAdd('incluye')">
                                <i class="fas fa-plus me-1"></i>Agregar ítem
                            </button>
                            <div class="mt-2">
                                <small class="text-muted">Vista previa:</small>
                                <div class="lb-preview" id="preview-incluye"><em class="text-muted">Sin ítems</em></div>
                            </div>
                            <input type="hidden" id="incluye" name="incluye" value="<?= htmlspecialchars($product['incluye'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="fas fa-times-circle text-danger me-1"></i>Qué NO incluye
                            </label>
                            <div id="builder-no_incluye" data-field="no_incluye" data-icon="times" data-color="danger" class="mb-2"></div>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="listBuilderAdd('no_incluye')">
                                <i class="fas fa-plus me-1"></i>Agregar ítem
                            </button>
                            <div class="mt-2">
                                <small class="text-muted">Vista previa:</small>
                                <div class="lb-preview" id="preview-no_incluye"><em class="text-muted">Sin ítems</em></div>
                            </div>
                            <input type="hidden" id="no_incluye" name="no_incluye" value="<?= htmlspecialchars($product['no_incluye'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Qué llevar / Equipaje -->
                <hr class="my-4">
                <h5 class="mb-1"><i class="fas fa-backpack text-warning me-2"></i>Qué Traer / Equipaje Recomendado</h5>
                <p class="text-muted small mb-3">Estos ítems aparecen en la sección "Qué Traer" de la página pública del tour, organizados en pestañas Esenciales / Recomendados / Opcionales.</p>

                <div class="card border-warning mb-0">
                    <div class="card-body">
                        <div class="mb-2 d-flex gap-2" style="font-size:.78rem; color:#6c757d; font-weight:600;">
                            <span style="width:115px;flex-shrink:0;">Categoría</span>
                            <span style="flex:1;">Nombre del ítem</span>
                            <span style="flex:1;">Descripción / Tip (opcional)</span>
                            <span style="width:32px;"></span>
                        </div>
                        <div id="ql-builder"></div>
                        <button type="button" class="btn btn-outline-warning btn-sm mt-2" onclick="qlAdd()">
                            <i class="fas fa-plus me-1"></i>Agregar ítem
                        </button>
                        <input type="hidden" id="que_llevar" name="que_llevar" value="<?= htmlspecialchars($product['que_llevar'] ?? '') ?>">
                    </div>
                </div>

                <!-- Políticas de Cancelación -->
                <hr class="my-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-shield-alt text-warning me-1"></i>Políticas de Cancelación
                    </label>
                    <div id="builder-politicas" data-field="politicas" data-icon="shield-alt" data-color="warning" data-separator="&#10;" class="mb-2"></div>
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="listBuilderAdd('politicas')">
                        <i class="fas fa-plus me-1"></i>Agregar ítem
                    </button>
                    <div class="mt-2">
                        <small class="text-muted">Vista previa:</small>
                        <div class="lb-preview" id="preview-politicas"><em class="text-muted">Sin ítems</em></div>
                    </div>
                    <input type="hidden" id="politicas" name="politicas" value="<?= htmlspecialchars($product['politicas'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">
                        <i class="fas fa-route text-success me-1"></i>Itinerario Detallado
                    </label>
                    <div id="builder-itinerario" data-field="itinerario" data-icon="map-marker-alt" data-color="success" data-separator="&#10;" class="mb-2"></div>
                    <button type="button" class="btn btn-outline-success btn-sm" onclick="listBuilderAdd('itinerario')">
                        <i class="fas fa-plus me-1"></i>Agregar ítem
                    </button>
                    <div class="mt-2">
                        <small class="text-muted">Vista previa:</small>
                        <div class="lb-preview" id="preview-itinerario"><em class="text-muted">Sin ítems</em></div>
                    </div>
                    <input type="hidden" id="itinerario" name="itinerario" value="<?= htmlspecialchars($product['itinerario'] ?? '') ?>">
                </div>

                <!-- Horarios Disponibles -->
                <hr class="my-4">
                <h5 class="mb-3"><i class="fas fa-clock me-2"></i>Horarios Disponibles</h5>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Configura los horarios de salida para este tour. El cliente podrá elegir su horario preferido al reservar. Si no se configuran horarios, no se mostrará el selector.
                </div>
                <input type="hidden" name="horarios" id="horarios_input" value="<?= htmlspecialchars($product['horarios'] ?? '') ?>">
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-md-4">
                        <label for="new_horario" class="form-label">Agregar horario</label>
                        <input type="time" class="form-control" id="new_horario">
                    </div>
                    <div class="col-md-4">
                        <label for="new_horario_label" class="form-label">Etiqueta <small class="text-muted">(opcional)</small></label>
                        <input type="text" class="form-control" id="new_horario_label" placeholder="Ej: Salida mañana">
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-success" onclick="addHorario()">
                            <i class="fas fa-plus me-1"></i>Agregar
                        </button>
                    </div>
                </div>
                <div id="horarios-list" class="d-flex flex-wrap gap-2 mb-3">
                    <!-- Se llena con JS -->
                </div>
                <script>
                (function(){
                    const input = document.getElementById('horarios_input');
                    let horarios = [];
                    try { horarios = JSON.parse(input.value || '[]'); } catch(e) { horarios = []; }
                    if (!Array.isArray(horarios)) horarios = [];

                    function render() {
                        const list = document.getElementById('horarios-list');
                        list.innerHTML = '';
                        if (horarios.length === 0) {
                            list.innerHTML = '<span class="text-muted small">No hay horarios configurados</span>';
                        }
                        horarios.forEach((h, i) => {
                            const badge = document.createElement('span');
                            badge.className = 'badge bg-primary d-inline-flex align-items-center gap-2 fs-6 py-2 px-3';
                            const timeStr = formatTime(h.hora);
                            badge.innerHTML = '<i class="fas fa-clock"></i> ' +
                                timeStr + (h.label ? ' — ' + escapeHtml(h.label) : '') +
                                ' <button type="button" class="btn-close btn-close-white ms-1" style="font-size:.6rem" onclick="removeHorario(' + i + ')"></button>';
                            list.appendChild(badge);
                        });
                        input.value = JSON.stringify(horarios);
                    }

                    function formatTime(t) {
                        const [hh, mm] = t.split(':');
                        const h = parseInt(hh);
                        const ampm = h >= 12 ? 'PM' : 'AM';
                        const h12 = h % 12 || 12;
                        return h12 + ':' + mm + ' ' + ampm;
                    }

                    function escapeHtml(str) {
                        const d = document.createElement('div');
                        d.textContent = str;
                        return d.innerHTML;
                    }

                    window.addHorario = function() {
                        const hora = document.getElementById('new_horario').value;
                        if (!hora) { alert('Selecciona una hora'); return; }
                        if (horarios.some(h => h.hora === hora)) { alert('Este horario ya existe'); return; }
                        const label = document.getElementById('new_horario_label').value.trim();
                        horarios.push({ hora: hora, label: label || '' });
                        horarios.sort((a, b) => a.hora.localeCompare(b.hora));
                        document.getElementById('new_horario').value = '';
                        document.getElementById('new_horario_label').value = '';
                        render();
                    };

                    window.removeHorario = function(index) {
                        horarios.splice(index, 1);
                        render();
                    };

                    render();
                })();
                </script>

                <!-- Disponibilidad General -->
                <hr class="my-4">
                <h5 class="mb-3">Disponibilidad General</h5>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="disponible_desde" class="form-label">Disponible Desde</label>
                            <input type="date" class="form-control" id="disponible_desde" name="disponible_desde"
                                   value="<?= htmlspecialchars($product['disponible_desde'] ?? '') ?>">
                            <div class="form-text">Rango general de disponibilidad (opcional)</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="disponible_hasta" class="form-label">Disponible Hasta</label>
                            <input type="date" class="form-control" id="disponible_hasta" name="disponible_hasta"
                                   value="<?= htmlspecialchars($product['disponible_hasta'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Fechas Específicas de Disponibilidad -->
                <hr class="my-4">
                <h5 class="mb-3">Fechas Específicas de Salida</h5>

                <style>
                .avail-calendar { user-select: none; }
                .avail-calendar .cal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; }
                .avail-calendar .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
                .avail-calendar .cal-day-name { text-align: center; font-size: 0.72rem; font-weight: 600; color: #6c757d; padding: 4px 0; }
                .avail-calendar .cal-day { min-height: 58px; border-radius: 8px; border: 1px solid #e9ecef; padding: 4px 5px; cursor: pointer; transition: background .15s, border-color .15s; position: relative; font-size: 0.82rem; }
                .avail-calendar .cal-day:hover:not(.cal-empty):not(.cal-past) { border-color: #0d6efd; background: #f0f5ff; }
                .avail-calendar .cal-day.cal-empty { border-color: transparent; background: transparent; cursor: default; }
                .avail-calendar .cal-day.cal-past { background: #f8f9fa; color: #adb5bd; cursor: default; }
                .avail-calendar .cal-day.cal-today { border-color: #0d6efd; }
                .avail-calendar .cal-day .cal-num { font-weight: 600; line-height: 1; }
                .avail-calendar .cal-day .cal-badge { display: block; margin-top: 4px; font-size: 0.68rem; font-weight: 600; border-radius: 4px; padding: 1px 4px; text-align: center; }
                .avail-calendar .cal-day .cal-badge.ok { background: #d1f0e0; color: #0a6640; }
                .avail-calendar .cal-day .cal-badge.low { background: #fff3cd; color: #664d03; }
                .avail-calendar .cal-day .cal-badge.out { background: #f8d7da; color: #842029; }
                /* popup */
                #cal-popup { display:none; position:absolute; z-index:1050; width:260px; background:#fff; border:1px solid #dee2e6; border-radius:10px; box-shadow:0 4px 20px rgba(0,0,0,.14); padding:14px 16px; }
                #cal-popup .popup-close { position:absolute; top:6px; right:10px; cursor:pointer; font-size:1.1rem; color:#6c757d; border:none; background:none; line-height:1; }
                </style>

                <!-- Calendario -->
                <div class="avail-calendar" id="avail-calendar">
                    <div class="cal-header">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="cal-prev"><i class="fas fa-chevron-left"></i></button>
                        <strong id="cal-month-label" style="font-size:1.05rem;"></strong>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="cal-next"><i class="fas fa-chevron-right"></i></button>
                    </div>
                    <div class="cal-grid" id="cal-grid">
                        <div class="cal-day-name">Dom</div>
                        <div class="cal-day-name">Lun</div>
                        <div class="cal-day-name">Mar</div>
                        <div class="cal-day-name">Mié</div>
                        <div class="cal-day-name">Jue</div>
                        <div class="cal-day-name">Vie</div>
                        <div class="cal-day-name">Sáb</div>
                    </div>
                    <div class="mt-2 d-flex gap-3 flex-wrap" style="font-size:.78rem;">
                        <span><span class="badge" style="background:#d1f0e0;color:#0a6640;">●</span> Disponible</span>
                        <span><span class="badge" style="background:#fff3cd;color:#664d03;">●</span> Pocos cupos</span>
                        <span><span class="badge" style="background:#f8d7da;color:#842029;">●</span> Agotado</span>
                        <span class="text-muted">· Clic en un día para agregar / ver</span>
                    </div>
                </div>

                <!-- Popup flotante del día -->
                <div id="cal-popup">
                    <button class="popup-close" onclick="closeCalPopup()">×</button>
                    <div id="cal-popup-content"></div>
                </div>

                <!-- Fechas Recurrentes -->
                <div class="card mt-4">
                    <div class="card-header d-flex align-items-center gap-2" style="background:#f8f9fa;">
                        <i class="fas fa-redo text-primary"></i>
                        <strong>Agregar Fechas Recurrentes</strong>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Selecciona los días de la semana y un rango de fechas. Se crearán automáticamente todas las fechas que caigan en esos días.</p>

                        <label class="form-label fw-semibold">Días de la semana <small class="text-muted fw-normal">(selecciona uno o varios)</small></label>
                        <div class="d-flex gap-2 flex-wrap mb-3">
                            <input type="checkbox" class="btn-check dow-check" id="dow-0" data-dow="0" autocomplete="off">
                            <label class="btn btn-outline-primary" for="dow-0">Dom</label>

                            <input type="checkbox" class="btn-check dow-check" id="dow-1" data-dow="1" autocomplete="off">
                            <label class="btn btn-outline-primary" for="dow-1">Lun</label>

                            <input type="checkbox" class="btn-check dow-check" id="dow-2" data-dow="2" autocomplete="off">
                            <label class="btn btn-outline-primary" for="dow-2">Mar</label>

                            <input type="checkbox" class="btn-check dow-check" id="dow-3" data-dow="3" autocomplete="off">
                            <label class="btn btn-outline-primary" for="dow-3">Mié</label>

                            <input type="checkbox" class="btn-check dow-check" id="dow-4" data-dow="4" autocomplete="off">
                            <label class="btn btn-outline-primary" for="dow-4">Jue</label>

                            <input type="checkbox" class="btn-check dow-check" id="dow-5" data-dow="5" autocomplete="off">
                            <label class="btn btn-outline-primary" for="dow-5">Vie</label>

                            <input type="checkbox" class="btn-check dow-check" id="dow-6" data-dow="6" autocomplete="off">
                            <label class="btn btn-outline-primary" for="dow-6">Sáb</label>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-sm-4">
                                <label class="form-label">Desde</label>
                                <input type="date" class="form-control" id="rec_desde">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">Hasta</label>
                                <input type="date" class="form-control" id="rec_hasta">
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label">Cupos por fecha</label>
                                <input type="number" min="1" class="form-control" id="rec_cupos" value="10">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Precio Especial (USD) <small class="text-muted">opcional</small></label>
                            <input type="number" step="0.01" class="form-control" id="rec_precio" placeholder="Dejar vacío para usar precio base" style="max-width:220px;">
                        </div>

                        <div id="rec-preview" class="mb-3 text-muted small"></div>

                        <button type="button" class="btn btn-primary" onclick="generateRecurringDates()">
                            <i class="fas fa-magic me-2"></i>Generar Fechas
                        </button>
                    </div>
                </div>

                <!-- Formulario fecha única (manual) -->
                <div class="card mt-3">
                    <div class="card-header d-flex align-items-center gap-2" style="background:#f8f9fa;">
                        <i class="fas fa-calendar-plus text-success"></i>
                        <strong>Agregar Fecha Individual</strong>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="new_fecha_salida" class="form-label">Fecha de Salida</label>
                                <input type="date" class="form-control" id="new_fecha_salida">
                            </div>
                            <div class="col-md-4">
                                <label for="new_cupos" class="form-label">Cupos Disponibles</label>
                                <input type="number" min="1" class="form-control" id="new_cupos" value="10">
                            </div>
                            <div class="col-md-4">
                                <label for="new_precio_especial" class="form-label">Precio Especial (USD) <small class="text-muted">opcional</small></label>
                                <input type="number" step="0.01" class="form-control" id="new_precio_especial" placeholder="Precio base del tour">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-success" onclick="addAvailabilityDate()">
                                <i class="fas fa-plus me-2"></i>Agregar Fecha
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Metadatos -->
                <?php if (!empty($product['created_at']) || !empty($product['updated_at'])): ?>
                    <hr class="my-4">
                    <h5 class="mb-3">Información del Sistema</h5>
                    <div class="row">
                        <?php if (!empty($product['created_at'])): ?>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Fecha de Creación</label>
                                    <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($product['created_at'])) ?>" readonly>
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($product['updated_at'])): ?>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Última Actualización</label>
                                    <input type="text" class="form-control" value="<?= date('d/m/Y H:i', strtotime($product['updated_at'])) ?>" readonly>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Botones de acción -->
                <hr class="my-4">
                <div class="d-flex justify-content-between">
                    <div>
                        <?php if (!empty($product['id'])): ?>
                            <button type="button" class="btn btn-outline-danger" onclick="deleteProduct(<?= $product['id'] ?>)">
                                <i class="fas fa-trash me-2"></i>Eliminar Tour
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/tours" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const productId = <?= $product['id'] ?? 0 ?>;

// ---- Calendario de disponibilidad ----
let calDatesMap = {};   // { 'YYYY-MM-DD': {id, cupos_disponibles, cupos_reservados, precio_especial} }
let calYear, calMonth;

document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    calYear  = now.getFullYear();
    calMonth = now.getMonth(); // 0-based
    if (productId > 0) loadAvailabilityDates();
    document.addEventListener('click', function(e) {
        const popup = document.getElementById('cal-popup');
        if (popup.style.display === 'block' && !popup.contains(e.target) && !e.target.closest('.cal-day')) {
            closeCalPopup();
        }
    });
    document.getElementById('cal-prev').addEventListener('click', function() {
        calMonth--;
        if (calMonth < 0) { calMonth = 11; calYear--; }
        renderCalendar();
    });
    document.getElementById('cal-next').addEventListener('click', function() {
        calMonth++;
        if (calMonth > 11) { calMonth = 0; calYear++; }
        renderCalendar();
    });
});

function loadAvailabilityDates() {
    fetch('<?= Config::getBaseUrl() ?>?route=admin/availability/list&tour_id=' + productId)
        .then(r => r.json())
        .then(data => {
            calDatesMap = {};
            (data.dates || []).forEach(d => {
                calDatesMap[d.fecha_salida] = d;
            });
            renderCalendar();
        })
        .catch(() => renderCalendar());
}

function renderCalendar() {
    const MONTHS_ES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    document.getElementById('cal-month-label').textContent = MONTHS_ES[calMonth] + ' ' + calYear;

    const grid = document.getElementById('cal-grid');
    // Keep day-name headers (first 7 children), remove day cells
    const headers = Array.from(grid.children).slice(0, 7);
    grid.innerHTML = '';
    headers.forEach(h => grid.appendChild(h));

    const today = new Date(); today.setHours(0,0,0,0);
    const firstDay = new Date(calYear, calMonth, 1).getDay(); // 0=Sun
    const daysInMonth = new Date(calYear, calMonth + 1, 0).getDate();

    // Empty cells before first day
    for (let i = 0; i < firstDay; i++) {
        const el = document.createElement('div');
        el.className = 'cal-day cal-empty';
        grid.appendChild(el);
    }

    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = calYear + '-' + String(calMonth + 1).padStart(2,'0') + '-' + String(d).padStart(2,'0');
        const cellDate = new Date(calYear, calMonth, d);
        const isPast = cellDate < today;
        const info = calDatesMap[dateStr];

        const el = document.createElement('div');
        el.className = 'cal-day' + (isPast ? ' cal-past' : '');
        if (cellDate.toDateString() === today.toDateString()) el.classList.add('cal-today');

        let inner = `<div class="cal-num">${d}</div>`;
        if (info) {
            const libre = info.cupos_disponibles - info.cupos_reservados;
            const cls = libre > 5 ? 'ok' : (libre > 0 ? 'low' : 'out');
            inner += `<span class="cal-badge ${cls}">${libre > 0 ? libre + ' cupos' : 'Agotado'}</span>`;
        }
        el.innerHTML = inner;

        if (!isPast) {
            el.addEventListener('click', function(e) { openCalPopup(e, dateStr, info); });
        }
        grid.appendChild(el);
    }
}

function openCalPopup(e, dateStr, info) {
    closeCalPopup();
    const popup = document.getElementById('cal-popup');
    const dateLabel = formatDate(dateStr);

    let html = `<div class="fw-bold mb-2" style="font-size:.93rem;">${dateLabel}</div>`;

    if (info) {
        const libre = info.cupos_disponibles - info.cupos_reservados;
        const cls = libre > 5 ? 'success' : (libre > 0 ? 'warning' : 'danger');
        html += `<div class="mb-1"><small class="text-muted">Cupos libres:</small> <strong>${libre} / ${info.cupos_disponibles}</strong></div>`;
        html += `<div class="mb-1"><small class="text-muted">Reservados:</small> <strong>${info.cupos_reservados}</strong> <span class="badge bg-${cls} ms-1">${libre > 0 ? 'Disponible' : 'Agotado'}</span></div>`;
        if (info.precio_especial) html += `<div class="mb-2"><small class="text-muted">Precio especial:</small> <strong>$${parseFloat(info.precio_especial).toFixed(2)}</strong></div>`;
        html += `<button class="btn btn-sm btn-outline-danger w-100 mt-2" onclick="deleteAvailabilityDate(${info.id})"><i class="fas fa-trash me-1"></i>Eliminar esta fecha</button>`;
    } else {
        html += `<div class="text-muted mb-2" style="font-size:.82rem;">Sin fecha configurada — agrega cupos:</div>`;
        html += `<div class="mb-2">
            <label class="form-label small mb-1">Cupos disponibles</label>
            <input type="number" min="1" class="form-control form-control-sm" id="popup-cupos" value="10">
        </div>
        <div class="mb-3">
            <label class="form-label small mb-1">Precio especial (USD) <span class="text-muted">opcional</span></label>
            <input type="number" step="0.01" class="form-control form-control-sm" id="popup-precio" placeholder="Precio base">
        </div>
        <button class="btn btn-sm btn-success w-100" onclick="addFromPopup('${dateStr}')">
            <i class="fas fa-plus me-1"></i>Guardar fecha
        </button>`;
    }

    document.getElementById('cal-popup-content').innerHTML = html;

    // Position near click
    const rect = e.target.closest('.cal-day').getBoundingClientRect();
    const calRect = document.getElementById('avail-calendar').getBoundingClientRect();
    popup.style.display = 'block';
    let left = rect.left - calRect.left;
    let top  = rect.bottom - calRect.top + 6;
    const popupW = 265;
    const calW = calRect.width;
    if (left + popupW > calW) left = calW - popupW;
    if (left < 0) left = 0;
    popup.style.left = left + 'px';
    popup.style.top  = top  + 'px';
    document.getElementById('avail-calendar').style.position = 'relative';

    // Focus cupos input if new date
    if (!info) setTimeout(() => document.getElementById('popup-cupos')?.focus(), 80);
}

function closeCalPopup() {
    document.getElementById('cal-popup').style.display = 'none';
}

function addFromPopup(dateStr) {
    const cupos = document.getElementById('popup-cupos').value;
    const precio = document.getElementById('popup-precio').value;
    if (!cupos || cupos < 1) { alert('Ingresa los cupos disponibles'); return; }
    const btn = document.querySelector('#cal-popup .btn-success');
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Guardando…';
    const data = { tour_id: productId, fecha_salida: dateStr, fecha_regreso: calculateReturnDate(dateStr), cupos_disponibles: cupos, precio_especial: precio || null };
    fetch('<?= Config::getBaseUrl() ?>?route=admin/availability/create', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) })
        .then(r => r.json())
        .then(res => {
            if (res.success) { closeCalPopup(); loadAvailabilityDates(); AdminUI.toast('Fecha agregada', 'success'); }
            else { btn.disabled=false; btn.innerHTML='<i class="fas fa-plus me-1"></i>Guardar fecha'; AdminUI.toast(res.message || 'Error', 'danger'); }
        })
        .catch(() => { btn.disabled=false; btn.innerHTML='<i class="fas fa-plus me-1"></i>Guardar fecha'; AdminUI.toast('Error al guardar', 'danger'); });
}

function prefillAddForm(dateStr) {
    closeCalPopup();
    document.getElementById('new_fecha_salida').value = dateStr;
    document.getElementById('new_cupos').focus();
}

// ---- Fechas Recurrentes ----
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.dow-check').forEach(cb => {
        cb.addEventListener('change', updateRecPreview);
    });
    ['rec_desde','rec_hasta','rec_cupos'].forEach(id => {
        document.getElementById(id)?.addEventListener('change', updateRecPreview);
    });
});

function getSelectedDows() {
    return Array.from(document.querySelectorAll('.dow-check:checked')).map(b => parseInt(b.dataset.dow));
}

function updateRecPreview() {
    const desde = document.getElementById('rec_desde').value;
    const hasta = document.getElementById('rec_hasta').value;
    const dows  = getSelectedDows();
    const el    = document.getElementById('rec-preview');
    if (!desde || !hasta || dows.length === 0) { el.textContent = ''; return; }
    const count = countRecurringDates(desde, hasta, dows);
    el.innerHTML = `<i class="fas fa-info-circle me-1"></i>Se crearán <strong>${count}</strong> fechas.`;
}

function countRecurringDates(desde, hasta, dows) {
    let count = 0;
    const cur = new Date(desde + 'T00:00:00');
    const end = new Date(hasta + 'T00:00:00');
    while (cur <= end) {
        if (dows.includes(cur.getDay())) count++;
        cur.setDate(cur.getDate() + 1);
    }
    return count;
}

async function generateRecurringDates() {
    const desde  = document.getElementById('rec_desde').value;
    const hasta  = document.getElementById('rec_hasta').value;
    const cupos  = document.getElementById('rec_cupos').value;
    const precio = document.getElementById('rec_precio').value;
    const dows   = getSelectedDows();

    if (!desde || !hasta)    { AdminUI.toast('Selecciona el rango de fechas', 'warning'); return; }
    if (dows.length === 0)   { AdminUI.toast('Selecciona al menos un día de la semana', 'warning'); return; }
    if (!cupos || cupos < 1) { AdminUI.toast('Ingresa los cupos disponibles', 'warning'); return; }
    if (new Date(hasta) < new Date(desde)) { AdminUI.toast('"Hasta" debe ser posterior a "Desde"', 'warning'); return; }

    const dates = [];
    const cur = new Date(desde + 'T00:00:00');
    const end = new Date(hasta + 'T00:00:00');
    while (cur <= end) {
        if (dows.includes(cur.getDay())) {
            const ds = cur.toISOString().split('T')[0];
            if (!calDatesMap[ds]) dates.push(ds);   // skip existing
        }
        cur.setDate(cur.getDate() + 1);
    }

    if (dates.length === 0) { AdminUI.toast('Todas las fechas del rango ya existen', 'info'); return; }

    const btn = document.querySelector('[onclick="generateRecurringDates()"]');
    btn.disabled = true;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span>Creando ${dates.length} fechas…`;

    let ok = 0, fail = 0;
    for (const ds of dates) {
        try {
            const res = await fetch('<?= Config::getBaseUrl() ?>?route=admin/availability/create', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ tour_id: productId, fecha_salida: ds, fecha_regreso: calculateReturnDate(ds), cupos_disponibles: cupos, precio_especial: precio || null })
            }).then(r => r.json());
            if (res.success) ok++; else fail++;
        } catch(e) { fail++; }
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-magic me-2"></i>Generar Fechas';
    loadAvailabilityDates();
    AdminUI.toast(`${ok} fechas creadas${fail ? ', ' + fail + ' errores' : ''}`, ok > 0 ? 'success' : 'danger');
}

// Formatear fecha
function formatDate(dateStr) {
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('es-GT', { year: 'numeric', month: 'long', day: 'numeric' });
}

function syncDuracionText() {
    const dias = parseInt(document.getElementById('duracion_dias')?.value) || 0;
    const horas = parseInt(document.getElementById('duracion_horas')?.value) || 0;
    const parts = [];
    if (dias > 0) parts.push(dias + (dias === 1 ? ' día' : ' días'));
    if (horas > 0) parts.push(horas + (horas === 1 ? ' hora' : ' horas'));
    document.getElementById('duracion').value = parts.join(', ') || '';
}
document.getElementById('duracion_dias')?.addEventListener('input', syncDuracionText);
document.getElementById('duracion_horas')?.addEventListener('input', syncDuracionText);

// ---- List Builder (incluye / no_incluye / que_llevar) ----
function listBuilderParseValue(val) {
    if (!val || !val.trim()) return [];
    const byNewline = val.split('\n').map(s => s.replace(/^[-\u2022\s]+/, '').trim()).filter(Boolean);
    const byComma   = val.split(',').map(s => s.trim()).filter(Boolean);
    return byNewline.length > byComma.length ? byNewline : byComma;
}
function listBuilderUpdatePreview(fieldName) {
    const builder = document.getElementById('builder-' + fieldName);
    const preview = document.getElementById('preview-' + fieldName);
    const items   = Array.from(builder.querySelectorAll('.lb-input')).map(i => i.value.trim()).filter(Boolean);
    if (items.length === 0) {
        preview.innerHTML = '<em class="text-muted">Sin \u00edtems</em>';
    } else {
        const icon = builder.dataset.icon, color = builder.dataset.color;
        preview.innerHTML = '<ul>' + items.map(i =>
            '<li><i class="fas fa-' + icon + ' text-' + color + ' me-1" style="font-size:.8rem;"></i>' + i.replace(/</g,'&lt;') + '</li>'
        ).join('') + '</ul>';
    }
}
function listBuilderSync(fieldName) {
    const builder = document.getElementById('builder-' + fieldName);
    const sep     = builder.dataset.separator || ', ';
    const vals    = Array.from(builder.querySelectorAll('.lb-input')).map(i => i.value.trim()).filter(Boolean);
    document.getElementById(fieldName).value = vals.join(sep);
    listBuilderUpdatePreview(fieldName);
}
function listBuilderAdd(fieldName, value) {
    const builder = document.getElementById('builder-' + fieldName);
    const row     = document.createElement('div');
    row.className = 'lb-row d-flex align-items-center gap-2 mb-2';
    const safeVal = (value || '').replace(/"/g, '&quot;');
    row.innerHTML =
        '<i class="fas fa-' + builder.dataset.icon + ' text-' + builder.dataset.color + '" style="width:16px;flex-shrink:0;"></i>' +
        '<input type="text" class="form-control form-control-sm lb-input" value="' + safeVal + '" placeholder="Escribe un \u00edtem...">' +
        '<button type="button" class="btn btn-link text-danger p-0 lb-del" title="Eliminar" tabindex="-1"><i class="fas fa-trash-alt fa-sm"></i></button>';
    row.querySelector('.lb-del').addEventListener('click', function() { row.remove(); listBuilderSync(fieldName); });
    row.querySelector('.lb-input').addEventListener('input', function() { listBuilderSync(fieldName); });
    row.querySelector('.lb-input').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            listBuilderAdd(fieldName, '');
            var all = builder.querySelectorAll('.lb-input');
            all[all.length - 1].focus();
        }
    });
    builder.appendChild(row);
    listBuilderSync(fieldName);
    if (!value) row.querySelector('.lb-input').focus();
}
function listBuilderInit(fieldName) {
    var builder = document.getElementById('builder-' + fieldName);
    var sep     = builder ? builder.dataset.separator : null;
    var raw     = document.getElementById(fieldName).value;
    var items;
    if (sep === '\n') {
        items = raw.split('\n').map(function(s) { return s.replace(/^[-\u2022\s]+/, '').trim(); }).filter(Boolean);
    } else {
        items = listBuilderParseValue(raw);
    }
    if (items.length > 0) { items.forEach(function(item) { listBuilderAdd(fieldName, item); }); }
    else { listBuilderAdd(fieldName, ''); }
}
document.addEventListener('DOMContentLoaded', function() {
    listBuilderInit('incluye');
    listBuilderInit('no_incluye');
    listBuilderInit('politicas');
    listBuilderInit('itinerario');
    qlInit();
});

// ---- Qué Llevar Builder (JSON: [{categoria, item, tip}]) ----
const QL_CATS = [
    { value: 'esencial',    label: 'Esencial',    color: '#dc3545' },
    { value: 'recomendado', label: 'Recomendado', color: '#28a745' },
    { value: 'opcional',    label: 'Opcional',    color: '#17a2b8' },
];

// ---- Precios por Grupo ----
function togglePreciosGrupo() {
    const on = document.getElementById('es_privado').checked;
    document.getElementById('precios-grupo-section').style.display = on ? '' : 'none';
}

function pgSync() {
    const rows = document.querySelectorAll('#pg-builder .pg-row');
    const data = [];
    rows.forEach(row => {
        const desde = parseInt(row.querySelector('.pg-desde').value) || 1;
        const hastaVal = row.querySelector('.pg-hasta').value.trim();
        const hasta = hastaVal === '' ? null : parseInt(hastaVal);
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
        <input type="number" min="1" class="form-control form-control-sm pg-desde" value="${desde}" placeholder="1" style="width:80px;flex-shrink:0;">
        <input type="number" min="1" class="form-control form-control-sm pg-hasta" value="${hasta}" placeholder="∞" style="width:80px;flex-shrink:0;">
        <div class="input-group input-group-sm" style="flex:1;">
            <span class="input-group-text">$</span>
            <input type="number" step="0.01" min="0" class="form-control pg-precio" value="${precio}" placeholder="0.00">
        </div>
        <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('.pg-row').remove();pgSync();">
            <i class="fas fa-trash-alt"></i>
        </button>`;
    row.querySelectorAll('input').forEach(i => i.addEventListener('input', pgSync));
    builder.appendChild(row);
    pgSync();
}

function pgInit() {
    const raw = document.getElementById('precios_grupo')?.value;
    if (!raw) return;
    let parsed = null;
    try { parsed = JSON.parse(raw); } catch(e) {}
    if (Array.isArray(parsed)) {
        parsed.forEach(p => pgAdd(p.desde ?? '', p.hasta ?? '', p.precio ?? ''));
    }
}

document.addEventListener('DOMContentLoaded', pgInit);

function qlSync() {
    const rows = document.querySelectorAll('#ql-builder .ql-row');
    const data = [];
    rows.forEach(row => {
        const cat  = row.querySelector('.ql-cat').value;
        const item = row.querySelector('.ql-item').value.trim();
        const tip  = row.querySelector('.ql-tip').value.trim();
        if (item) data.push({ categoria: cat, item, tip });
    });
    document.getElementById('que_llevar').value = data.length ? JSON.stringify(data) : '';
}

function qlAdd(cat = 'esencial', item = '', tip = '') {
    const builder = document.getElementById('ql-builder');
    const row = document.createElement('div');
    row.className = 'ql-row d-flex gap-1 mb-1 align-items-start';

    const catOptions = QL_CATS.map(c =>
        `<option value="${c.value}" ${cat === c.value ? 'selected' : ''}>${c.label}</option>`
    ).join('');

    row.innerHTML = `
        <select class="form-select form-select-sm ql-cat" style="width:110px;flex-shrink:0;">
            ${catOptions}
        </select>
        <input type="text" class="form-control form-control-sm ql-item" value="${item.replace(/"/g,'&quot;')}" placeholder="Nombre del ítem" style="min-width:0;">
        <input type="text" class="form-control form-control-sm ql-tip" value="${tip.replace(/"/g,'&quot;')}" placeholder="Tip/descripción (opcional)" style="min-width:0;">
        <button type="button" class="btn btn-sm btn-link text-danger p-0 px-1" onclick="this.closest('.ql-row').remove();qlSync();" title="Eliminar">
            <i class="fas fa-trash-alt"></i>
        </button>`;

    row.querySelector('.ql-cat').addEventListener('change', qlSync);
    row.querySelector('.ql-item').addEventListener('input', qlSync);
    row.querySelector('.ql-tip').addEventListener('input', qlSync);

    builder.appendChild(row);
    if (!item) row.querySelector('.ql-item').focus();
    qlSync();
}

const QL_DEFAULTS = [
    {categoria:'esencial',    item:'Calzado cómodo para caminar',   tip:'Preferiblemente botas de trekking o zapatos deportivos cerrados con buen agarre.'},
    {categoria:'esencial',    item:'Protector solar SPF 50+',        tip:'Guatemala está cerca del ecuador. El sol es fuerte incluso en días nublados.'},
    {categoria:'esencial',    item:'Botella de agua reutilizable',   tip:'Manténte hidratado. Hay puntos de recarga en la ruta.'},
    {categoria:'esencial',    item:'Sombrero o gorra',               tip:'Protección adicional contra el sol.'},
    {categoria:'esencial',    item:'Repelente de insectos',          tip:'Especialmente importante en áreas de selva.'},
    {categoria:'recomendado', item:'Cámara o smartphone',            tip:'¡Querrás capturar cada momento! Trae batería externa.'},
    {categoria:'recomendado', item:'Dinero en efectivo',             tip:'Para compras personales, propinas y emergencias. Quetzales y dólares.'},
    {categoria:'recomendado', item:'Documentos (ID/Pasaporte)',      tip:'Copia física o digital. Necesario en algunos sitios.'},
    {categoria:'recomendado', item:'Impermeable o poncho',           tip:'El clima puede cambiar rápidamente. Mejor prevenir.'},
    {categoria:'opcional',    item:'Linterna o frontal',             tip:'Útil para tours que incluyen cuevas o caminatas nocturnas.'},
    {categoria:'opcional',    item:'Snacks energéticos',             tip:'Barras energéticas, frutos secos, chocolate.'},
    {categoria:'opcional',    item:'Binoculares',                    tip:'Para observación de aves y vida silvestre.'},
    {categoria:'opcional',    item:'Medicamentos personales',        tip:'Trae tus medicamentos habituales. Antiácidos y analgésicos son útiles.'},
];

function qlInit() {
    const raw = document.getElementById('que_llevar').value;
    if (!raw || !raw.trim()) {
        // Sin datos: cargar ítems por defecto para que el cliente los edite
        QL_DEFAULTS.forEach(p => qlAdd(p.categoria, p.item, p.tip));
        return;
    }
    let parsed = null;
    try { parsed = JSON.parse(raw); } catch(e) {}
    if (Array.isArray(parsed) && parsed.length) {
        parsed.forEach(p => qlAdd(p.categoria || 'esencial', p.item || '', p.tip || ''));
    } else {
        // Datos legacy (texto plano) → convertir a esenciales
        const lines = raw.split('\n').map(s => s.replace(/^[-•\s]+/, '').trim()).filter(Boolean);
        if (lines.length) lines.forEach(l => qlAdd('esencial', l, ''));
        else QL_DEFAULTS.forEach(p => qlAdd(p.categoria, p.item, p.tip));
    }
}

function getDurationDays() {
    const dias = parseInt(document.getElementById('duracion_dias')?.value) || 0;
    let days = dias > 0 ? dias : 1;

    if (!Number.isFinite(days) || days < 1) {
        days = 1;
    }

    return days;
}

function calculateReturnDate(startDateStr) {
    if (!startDateStr) return startDateStr;
    const startDate = new Date(startDateStr + 'T00:00:00');
    if (Number.isNaN(startDate.getTime())) return startDateStr;

    const endDate = new Date(startDate);
    endDate.setDate(startDate.getDate() + getDurationDays());
    return endDate.toISOString().split('T')[0];
}

// Agregar fecha de disponibilidad
function addAvailabilityDate() {
    const fechaSalida = document.getElementById('new_fecha_salida').value;
    const cupos = document.getElementById('new_cupos').value;
    const precioEspecial = document.getElementById('new_precio_especial').value;

    if (!fechaSalida) {
        AdminUI.toast('Por favor ingresa una fecha de salida', 'warning');
        return;
    }

    if (!cupos || cupos < 1) {
        AdminUI.toast('Por favor ingresa un número válido de cupos', 'warning');
        return;
    }

    const fechaRegreso = calculateReturnDate(fechaSalida);

    const data = {
        tour_id: productId,
        fecha_salida: fechaSalida,
        fecha_regreso: fechaRegreso,
        cupos_disponibles: cupos,
        precio_especial: precioEspecial || null
    };

    fetch('<?= Config::getBaseUrl() ?>?route=admin/availability/create', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Limpiar campos
            document.getElementById('new_fecha_salida').value = '';
            document.getElementById('new_cupos').value = '10';
            document.getElementById('new_precio_especial').value = '';

            // Recargar calendario
            loadAvailabilityDates();
            closeCalPopup();

            AdminUI.toast('Fecha agregada exitosamente', 'success');
        } else {
            AdminUI.toast(data.message || 'No se pudo agregar la fecha', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        AdminUI.toast('Error al agregar la fecha', 'danger');
    });
}

// Eliminar fecha de disponibilidad
function deleteAvailabilityDate(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar esta fecha?')) {
        return;
    }

    fetch('<?= Config::getBaseUrl() ?>?route=admin/availability/delete&id=' + id, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadAvailabilityDates();
            closeCalPopup();
            AdminUI.toast('Fecha eliminada exitosamente', 'success');
        } else {
            AdminUI.toast(data.message || 'No se pudo eliminar la fecha', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        AdminUI.toast('Error al eliminar la fecha', 'danger');
    });
}

function deleteProduct(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este tour? Esta acción no se puede deshacer.')) {
        fetch('<?= Config::getBaseUrl() ?>?route=admin/tours/delete/' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Tour eliminado exitosamente');
                window.location.href = '<?= Config::getBaseUrl() ?>?route=admin/tours';
            } else {
                alert('Error al eliminar el tour: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el tour');
        });
    }
}

// ---- Imágenes ----
document.addEventListener('DOMContentLoaded', function() {
    // Preview imagen principal
    document.getElementById('imagen_principal_file').addEventListener('change', function() {
        const file = this.files[0];
        const preview = document.getElementById('main-img-new-preview');
        if (!file) { preview.innerHTML = ''; return; }
        const reader = new FileReader();
        reader.onload = e => {
            preview.innerHTML = `<div class="img-thumb-wrap"><img src="${e.target.result}" alt="Nueva"></div>
                <div class="mt-1"><small class="text-success"><i class="fas fa-check me-1"></i>Nueva imagen lista para subir</small></div>`;
        };
        reader.readAsDataURL(file);
    });

    // Preview imágenes galería (múltiple)
    document.getElementById('imagenes_files').addEventListener('change', function() {
        const preview = document.getElementById('gallery-new-preview');
        preview.innerHTML = '';
        Array.from(this.files).forEach((file, i) => {
            const reader = new FileReader();
            reader.onload = e => {
                const wrap = document.createElement('div');
                wrap.className = 'img-thumb-wrap';
                wrap.dataset.index = i;
                wrap.innerHTML = `<img src="${e.target.result}" alt=""><button type="button" class="img-del" onclick="removeNewGalleryImg(this, ${i})">×</button>`;
                preview.appendChild(wrap);
            };
            reader.readAsDataURL(file);
        });
    });
});

function removeExistingGalleryImg(btn) {
    btn.closest('.gallery-existing-item').remove();
}

function removeNewGalleryImg(btn, index) {
    // Solo oculta el preview; el backend ignorará si el archivo está vacío
    btn.closest('.img-thumb-wrap').remove();
}


// ==========================================
// PUNTOS DE ENCUENTRO
// ==========================================

// Cargar al inicio si hay ID
document.addEventListener('DOMContentLoaded', function() {
    if (typeof productId !== 'undefined' && productId > 0) {
        loadMeetingPoints();
    }
});

function loadMeetingPoints() {
    fetch('<?= Config::getBaseUrl() ?>?route=api/meeting-points/list&tour_id=' + productId) // Use API assigned
        .then(r => r.json()) // Check if I should use /assigned/ID or logic in list
        // Wait, I added `assigned($tourId)` method.
        // I should stick to that.
    // Re-writing URL
    fetch('<?= Config::getBaseUrl() ?>?route=api/meeting-points/assigned&tour_id=' + productId)
        .then(response => response.json())
        .then(data => {
            renderMeetingPoints(data);
        })
        .catch(console.error);
}

function renderMeetingPoints(points) {
    const container = document.getElementById('meeting-points-container');
    if (!points || points.length === 0) {
        container.innerHTML = '<p class="text-muted small fst-italic ms-1 mb-0 border p-3 rounded bg-light text-center">No hay puntos de encuentro asignados. Asigna uuno desde el botón "Asignar Punto".</p>';
        return;
    }

    let html = '<div class="list-group">';
    points.forEach(p => {
        const icon = p.type === 'hotel_pickup' ? 'fa-hotel' : 'fa-map-marker-alt';
        const typeLabel = p.type === 'hotel_pickup' ? '<span class="badge bg-info text-dark ms-2">Hotel Pickup</span>' : '';
        
        html += `
            <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-bold">
                        <i class="fas ${icon} text-primary me-2"></i>${escapeHtml(p.title)}
                        ${typeLabel}
                    </div>
                    <div class="small text-muted">${escapeHtml(p.address || '')}</div>
                    ${p.map_link ? `<a href="${escapeHtml(p.map_link)}" target="_blank" class="small text-decoration-none"><i class="fas fa-external-link-alt me-1"></i>Ver Mapa</a>` : ''}
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="detachPoint(${p.id})" title="Quitar del tour">
                    <i class="fas fa-minus-circle"></i>
                </button>
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

function loadGlobalPoints() {
    fetch('<?= Config::getBaseUrl() ?>?route=api/meeting-points/list')
        .then(r => r.json())
        .then(data => {
            const select = document.getElementById('global_mp_select');
            select.innerHTML = '<option value="">-- Seleccionar punto --</option>';
            data.forEach(p => {
                select.innerHTML += `<option value="${p.id}">${escapeHtml(p.title)} (${p.type === 'hotel_pickup' ? 'Hotel' : 'Std'})</option>`;
            });
        })
        .catch(console.error);
}

function assignPoint() {
    const select = document.getElementById('global_mp_select');
    const mpId = select.value;
    
    if (!mpId) {
        alert('Selecciona un punto de encuentro');
        return;
    }

    const data = new URLSearchParams();
    data.append('tour_id', productId);
    data.append('meeting_point_id', mpId);

    fetch('<?= Config::getBaseUrl() ?>?route=api/meeting-points/assign', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: data
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            loadMeetingPoints(); // Reload list
            
            // Close modal (bootstrap 5)
            const modalEl = document.getElementById('modalAssignPoint');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            if (typeof AdminUI !== 'undefined') AdminUI.toast('Punto asignado', 'success');
        } else {
            alert(res.error || 'Error al asignar');
        }
    })
    .catch(console.error);
}

function detachPoint(mpId) {
    if (!confirm('¿Quitar este punto de encuentro del tour?')) return;
    
    const data = new URLSearchParams();
    data.append('tour_id', productId);
    data.append('meeting_point_id', mpId);

    fetch('<?= Config::getBaseUrl() ?>?route=api/meeting-points/detach', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: data
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            loadMeetingPoints();
            if (typeof AdminUI !== 'undefined') AdminUI.toast('Punto removido', 'success');
        } else {
            alert(res.error || 'Error al quitar');
        }
    });
}

// Load global points when modal opens
document.addEventListener('DOMContentLoaded', function() {
    const modalAssign = document.getElementById('modalAssignPoint');
    if (modalAssign) {
        modalAssign.addEventListener('show.bs.modal', function () {
            loadGlobalPoints();
        });
    }
});

function escapeHtml(text) {
  if (!text) return text;
  return text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
}
</script>

<!-- Modal Asignar Punto de Encuentro -->
<div class="modal fade" id="modalAssignPoint" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Asignar Punto de Encuentro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Seleccionar Punto del Catálogo Global</label>
                    <select class="form-select" id="global_mp_select">
                        <option value="">Cargando...</option>
                    </select>
                </div>
                <div class="text-end">
                    <a href="<?= Config::getBaseUrl() ?>?route=admin/meeting-points/create" target="_blank" class="small text-decoration-none">
                        <i class="fas fa-plus-circle me-1"></i>Crear nuevo punto global
                    </a>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="assignPoint()">Asignar</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../layouts/admin_footer.php'; ?>
