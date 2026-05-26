<?php
use App\Core\Config;
/**
 * Vista: Formulario de Cupón
 * Crear/Editar cupón de descuento
 */
$pageTitle = $cupon ? 'Editar Cupón' : 'Crear Cupón';
include __DIR__ . '/../../partials/admin_header.php';

// Valores por defecto
$id = $cupon['id'] ?? null;
$codigo = $cupon['codigo'] ?? '';
$nombre = $cupon['nombre'] ?? '';
$descripcion = $cupon['descripcion'] ?? '';
$tipo_descuento = $cupon['tipo_descuento'] ?? 'porcentaje';
$valor_descuento = $cupon['valor_descuento'] ?? '';
$monto_minimo = $cupon['monto_minimo'] ?? '';
$monto_maximo_descuento = $cupon['monto_maximo_descuento'] ?? '';
$fecha_inicio = $cupon['fecha_inicio'] ?? date('Y-m-d\TH:i');
$fecha_fin = $cupon['fecha_fin'] ?? '';
$usos_maximos = $cupon['usos_maximos'] ?? '';
$usos_por_usuario = $cupon['usos_por_usuario'] ?? 1;
$tours_aplicables = $cupon['tours_aplicables'] ?? '';
$categorias_aplicables = $cupon['categorias_aplicables'] ?? '';
$solo_primera_compra = $cupon['solo_primera_compra'] ?? 0;
$activo = isset($cupon['activo']) ? $cupon['activo'] : 1;
$banner_id = $cupon['banner_id'] ?? '';
?>

<div class="container-fluid py-4">
    <?php
        $actionTitle = $id ? 'Editar Cupón' : 'Crear Nuevo Cupón';
        $actionSubtitle = 'Configura reglas y condiciones del cupón de descuento';
        $actionButtons = [
            ['label' => 'Volver', 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary', 'href' => Config::getBaseUrl() . '?route=admin/cupones'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= Config::getBaseUrl() ?>?route=admin">Admin</a></li>
            <li class="breadcrumb-item"><a href="<?= Config::getBaseUrl() ?>?route=admin/cupones">Cupones</a></li>
            <li class="breadcrumb-item active"><?= $id ? 'Editar' : 'Crear' ?></li>
        </ol>
    </nav>

    <!-- Alertas -->
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show">
            <?= htmlspecialchars($_SESSION['flash_message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); ?>
    <?php endif; ?>

    <form method="POST" action="" id="cuponForm">
        <div class="row">
            <!-- Columna principal -->
            <div class="col-lg-8">
                <!-- Información básica -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Información Básica</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="codigo">Código del Cupón <span class="text-danger">*</span></label>
                                <input type="text" class="form-control text-uppercase font-monospace" id="codigo" name="codigo"
                                       value="<?= htmlspecialchars($codigo) ?>" required
                                       pattern="[A-Z0-9]+"
                                       placeholder="Ej: BLACKFRIDAY30"
                                       <?= $id ? 'readonly' : '' ?>>
                                <small class="text-muted">Solo letras mayúsculas y números (sin espacios ni caracteres especiales)</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="nombre">Nombre del Cupón <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre"
                                       value="<?= htmlspecialchars($nombre) ?>" required
                                       placeholder="Ej: Black Friday 30% OFF">
                                <small class="text-muted">Nombre descriptivo interno</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2"
                                      placeholder="Ej: Descuento especial de Black Friday en todos los tours"><?= htmlspecialchars($descripcion) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Configuración del descuento -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Configuración del Descuento</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="tipo_descuento">Tipo de Descuento <span class="text-danger">*</span></label>
                                <select class="form-select" id="tipo_descuento" name="tipo_descuento" required>
                                    <option value="porcentaje" <?= $tipo_descuento === 'porcentaje' ? 'selected' : '' ?>>Porcentaje (%)</option>
                                    <option value="fijo" <?= $tipo_descuento === 'fijo' ? 'selected' : '' ?>>Monto Fijo ($)</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="valor_descuento">
                                    Valor del Descuento <span class="text-danger">*</span>
                                    <span id="valorLabel">(<?= $tipo_descuento === 'porcentaje' ? '%' : '$' ?>)</span>
                                </label>
                                <input type="number" class="form-control" id="valor_descuento" name="valor_descuento"
                                       value="<?= $valor_descuento ?>" required
                                       step="0.01" min="0" max="<?= $tipo_descuento === 'porcentaje' ? '100' : '9999' ?>"
                                       placeholder="Ej: <?= $tipo_descuento === 'porcentaje' ? '15' : '50' ?>">
                                <small class="text-muted" id="valorHint">
                                    <?= $tipo_descuento === 'porcentaje' ? 'Porcentaje de descuento (0-100)' : 'Monto fijo en USD' ?>
                                </small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="monto_minimo">Monto Mínimo de Compra</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="monto_minimo" name="monto_minimo"
                                           value="<?= $monto_minimo ?>"
                                           step="0.01" min="0"
                                           placeholder="50.00">
                                </div>
                                <small class="text-muted">Compra mínima requerida (dejar vacío = sin mínimo)</small>
                            </div>

                            <div class="col-md-6 mb-3" id="montoMaximoContainer" style="<?= $tipo_descuento === 'fijo' ? 'display:none;' : '' ?>">
                                <label class="form-label" for="monto_maximo_descuento">Descuento Máximo</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="monto_maximo_descuento" name="monto_maximo_descuento"
                                           value="<?= $monto_maximo_descuento ?>"
                                           step="0.01" min="0"
                                           placeholder="100.00">
                                </div>
                                <small class="text-muted">Máximo descuento en $ (solo para %)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Restricciones -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Restricciones y Límites</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="usos_maximos">Usos Máximos Totales</label>
                                <input type="number" class="form-control" id="usos_maximos" name="usos_maximos"
                                       value="<?= $usos_maximos ?>"
                                       min="0" step="1"
                                       placeholder="Ej: 100">
                                <small class="text-muted">Límite total de usos (dejar vacío = ilimitado)</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="usos_por_usuario">Usos por Usuario</label>
                                <input type="number" class="form-control" id="usos_por_usuario" name="usos_por_usuario"
                                       value="<?= $usos_por_usuario ?>"
                                       min="0" step="1"
                                       placeholder="1">
                                <small class="text-muted">Veces que un usuario puede usarlo</small>
                            </div>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="solo_primera_compra" name="solo_primera_compra" value="1"
                                   <?= $solo_primera_compra ? 'checked' : '' ?>>
                            <label class="form-check-label" for="solo_primera_compra">
                                Solo válido para primera compra
                            </label>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="tours_aplicables">Tours Aplicables (IDs)</label>
                            <input type="text" class="form-control font-monospace" id="tours_aplicables" name="tours_aplicables"
                                   value="<?= htmlspecialchars($tours_aplicables) ?>"
                                   placeholder='[1, 2, 5]'>
                            <small class="text-muted">Array JSON de IDs de tours (vacío = todos los tours)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="categorias_aplicables">Categorías Aplicables (IDs)</label>
                            <input type="text" class="form-control font-monospace" id="categorias_aplicables" name="categorias_aplicables"
                                   value="<?= htmlspecialchars($categorias_aplicables) ?>"
                                   placeholder='[1, 3]'>
                            <small class="text-muted">Array JSON de IDs de categorías (vacío = todas las categorías)</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna lateral -->
            <div class="col-lg-4">
                <!-- Configuración general -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Configuración</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="fecha_inicio">Fecha Inicio <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                   value="<?= date('Y-m-d\TH:i', strtotime($fecha_inicio)) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="fecha_fin">Fecha Fin</label>
                            <input type="datetime-local" class="form-control" id="fecha_fin" name="fecha_fin"
                                   value="<?= $fecha_fin ? date('Y-m-d\TH:i', strtotime($fecha_fin)) : '' ?>">
                            <small class="text-muted">Dejar vacío = sin límite</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="banner_id">Banner Asociado</label>
                            <select class="form-select" id="banner_id" name="banner_id">
                                <option value="">Ninguno</option>
                                <?php foreach ($banners as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= $banner_id == $b['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($b['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Banner que promociona este cupón</small>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?= $activo ? 'checked' : '' ?>>
                            <label class="form-check-label" for="activo">
                                Cupón Activo
                            </label>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                <?= $id ? 'Actualizar Cupón' : 'Crear Cupón' ?>
                            </button>
                            <a href="<?= Config::getBaseUrl() ?>?route=admin/cupones" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas (solo si es edición) -->
                <?php if ($id): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Estadísticas de Uso</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">Usos Actuales:</small>
                            <strong class="float-end"><?= number_format($cupon['usos_actuales'] ?? 0) ?></strong>
                        </div>
                        <?php if ($cupon['usos_maximos']): ?>
                        <div class="mb-2">
                            <small class="text-muted">Usos Máximos:</small>
                            <strong class="float-end"><?= number_format($cupon['usos_maximos']) ?></strong>
                        </div>
                        <div class="progress mb-2" style="height: 8px;">
                            <?php
                            $porcentaje = ($cupon['usos_actuales'] / $cupon['usos_maximos']) * 100;
                            $colorClass = $porcentaje >= 90 ? 'bg-danger' : ($porcentaje >= 70 ? 'bg-warning' : 'bg-success');
                            ?>
                            <div class="progress-bar <?= $colorClass ?>" style="width: <?= min($porcentaje, 100) ?>%"></div>
                        </div>
                        <?php endif; ?>
                        <div>
                            <small class="text-muted">Creado por:</small>
                            <strong class="float-end"><?= htmlspecialchars($cupon['creador_nombre'] ?? 'Sistema') ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Vista previa del código -->
                <div class="card shadow-sm border-primary">
                    <div class="card-body text-center">
                        <small class="text-muted d-block mb-2">Vista Previa:</small>
                        <div class="p-3 bg-light rounded">
                            <h3 class="font-monospace mb-0"><?= htmlspecialchars($codigo) ?></h3>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoDescuento = document.getElementById('tipo_descuento');
    const valorDescuento = document.getElementById('valor_descuento');
    const valorLabel = document.getElementById('valorLabel');
    const valorHint = document.getElementById('valorHint');
    const montoMaximoContainer = document.getElementById('montoMaximoContainer');

    // Cambiar labels según tipo de descuento
    tipoDescuento.addEventListener('change', function() {
        const esPorcentaje = this.value === 'porcentaje';

        valorLabel.textContent = esPorcentaje ? '(%)' : '($)';
        valorHint.textContent = esPorcentaje
            ? 'Porcentaje de descuento (0-100)'
            : 'Monto fijo en USD';
        valorDescuento.max = esPorcentaje ? '100' : '9999';
        valorDescuento.placeholder = esPorcentaje ? '15' : '50';

        // Mostrar/ocultar monto máximo
        montoMaximoContainer.style.display = esPorcentaje ? '' : 'none';
    });

    // Convertir código a mayúsculas
    const codigoInput = document.getElementById('codigo');
    codigoInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });

    // Validación de JSON
    const jsonFields = ['tours_aplicables', 'categorias_aplicables'];
    jsonFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        field.addEventListener('blur', function() {
            const value = this.value.trim();
            if (value) {
                try {
                    JSON.parse(value);
                    this.classList.remove('is-invalid');
                    this.classList.add('is-valid');
                } catch (e) {
                    this.classList.remove('is-valid');
                    this.classList.add('is-invalid');
                    alert('JSON inválido en ' + fieldId);
                }
            } else {
                this.classList.remove('is-invalid', 'is-valid');
            }
        });
    });

    // Validar que fecha_fin > fecha_inicio
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');

    fechaFin.addEventListener('change', function() {
        if (this.value && fechaInicio.value) {
            if (new Date(this.value) <= new Date(fechaInicio.value)) {
                alert('La fecha de fin debe ser posterior a la fecha de inicio');
                this.value = '';
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../../partials/admin_footer.php'; ?>
