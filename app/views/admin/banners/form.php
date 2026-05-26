<?php
use App\Core\Config;
/**
 * Vista: Formulario de Banner
 * Crear/Editar banner promocional
 */
$pageTitle = $banner ? 'Editar Banner' : 'Crear Banner';
include __DIR__ . '/../../partials/admin_header.php';

// Valores por defecto
$id = $banner['id'] ?? null;
$nombre = $banner['nombre'] ?? '';
$tipo = $banner['tipo'] ?? 'strip';
$imagen = $banner['imagen'] ?? '';
$titulo = $banner['titulo'] ?? '';
$subtitulo = $banner['subtitulo'] ?? '';
$cta_texto = $banner['cta_texto'] ?? '';
$cta_link = $banner['cta_link'] ?? '';
$posicion = $banner['posicion'] ?? 'all';
$fecha_inicio = $banner['fecha_inicio'] ?? date('Y-m-d\TH:i');
$fecha_fin = $banner['fecha_fin'] ?? '';
$activo = isset($banner['activo']) ? $banner['activo'] : 1;
$orden = $banner['orden'] ?? 0;
$target_audiencia = $banner['target_audiencia'] ?? '';
$estilos_custom = $banner['estilos_custom'] ?? '';
?>

<div class="container-fluid py-4">
    <?php
        $actionTitle = $id ? 'Editar Banner' : 'Crear Nuevo Banner';
        $actionSubtitle = 'Configura banners promocionales del sitio';
        $actionButtons = [
            ['label' => 'Volver', 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-secondary', 'href' => Config::getBaseUrl() . '?route=admin/banners'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= Config::getBaseUrl() ?>?route=admin">Admin</a></li>
            <li class="breadcrumb-item"><a href="<?= Config::getBaseUrl() ?>?route=admin/banners">Banners</a></li>
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

    <form method="POST" action="" id="bannerForm">
        <div class="row">
            <!-- Columna principal -->
            <div class="col-lg-8">
                <!-- Información básica -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Información Básica</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="nombre">Nombre del Banner <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre"
                                   value="<?= htmlspecialchars($nombre) ?>" required
                                   placeholder="Ej: Black Friday 2025">
                            <small class="text-muted">Identificador interno (no se muestra al público)</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="tipo">Tipo de Banner <span class="text-danger">*</span></label>
                                <select class="form-select" id="tipo" name="tipo" required>
                                    <option value="strip" <?= $tipo === 'strip' ? 'selected' : '' ?>>Strip (Barra horizontal)</option>
                                    <option value="hero" <?= $tipo === 'hero' ? 'selected' : '' ?>>Hero (Banner grande)</option>
                                    <option value="popup" <?= $tipo === 'popup' ? 'selected' : '' ?>>Popup (Modal)</option>
                                    <option value="sidebar" <?= $tipo === 'sidebar' ? 'selected' : '' ?>>Sidebar (Lateral)</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="posicion">Posición <span class="text-danger">*</span></label>
                                <select class="form-select" id="posicion" name="posicion" required>
                                    <option value="all" <?= $posicion === 'all' ? 'selected' : '' ?>>Todas las páginas</option>
                                    <option value="home" <?= $posicion === 'home' ? 'selected' : '' ?>>Solo Homepage</option>
                                    <option value="tours" <?= $posicion === 'tours' ? 'selected' : '' ?>>Solo Tours</option>
                                    <option value="checkout" <?= $posicion === 'checkout' ? 'selected' : '' ?>>Solo Checkout</option>
                                    <option value="top" <?= $posicion === 'top' ? 'selected' : '' ?>>Parte Superior</option>
                                    <option value="middle" <?= $posicion === 'middle' ? 'selected' : '' ?>>Parte Media</option>
                                    <option value="bottom" <?= $posicion === 'bottom' ? 'selected' : '' ?>>Parte Inferior</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="imagen">URL de Imagen</label>
                            <input type="url" class="form-control" id="imagen" name="imagen"
                                   value="<?= htmlspecialchars($imagen) ?>"
                                   placeholder="https://ejemplo.com/banner.jpg">
                            <small class="text-muted">URL completa de la imagen del banner (opcional)</small>
                            <?php if ($imagen): ?>
                                <div class="mt-2">
                                    <img src="<?= htmlspecialchars($imagen) ?>" alt="Preview" class="img-thumbnail" style="max-width: 300px;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Contenido del banner -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Contenido</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="titulo">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo"
                                   value="<?= htmlspecialchars($titulo) ?>"
                                   placeholder="Ej: 🔥 BLACK FRIDAY: 30% OFF en TODOS los tours">
                            <small class="text-muted">Título principal del banner</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="subtitulo">Subtítulo / Descripción</label>
                            <textarea class="form-control" id="subtitulo" name="subtitulo" rows="3"
                                      placeholder="Ej: Usa el código: BLACKFRIDAY30"><?= htmlspecialchars($subtitulo) ?></textarea>
                            <small class="text-muted">Texto secundario o descripción</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="cta_texto">Texto del Botón (CTA)</label>
                                <input type="text" class="form-control" id="cta_texto" name="cta_texto"
                                       value="<?= htmlspecialchars($cta_texto) ?>"
                                       placeholder="Ej: Ver Ofertas">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="cta_link">Link del Botón</label>
                                <input type="text" class="form-control" id="cta_link" name="cta_link"
                                       value="<?= htmlspecialchars($cta_link) ?>"
                                       placeholder="Ej: ?route=tours">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Opciones avanzadas -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-cogs me-2"></i>
                            Opciones Avanzadas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="target_audiencia">Targeting (JSON)</label>
                            <textarea class="form-control font-monospace" id="target_audiencia" name="target_audiencia" rows="3"
                                      placeholder='{"device":"mobile", "country":"GT"}'><?= htmlspecialchars($target_audiencia) ?></textarea>
                            <small class="text-muted">Reglas de segmentación en formato JSON (opcional)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="estilos_custom">CSS Personalizado</label>
                            <textarea class="form-control font-monospace" id="estilos_custom" name="estilos_custom" rows="4"
                                      placeholder=".banner-custom { background: linear-gradient(...); }"><?= htmlspecialchars($estilos_custom) ?></textarea>
                            <small class="text-muted">Estilos CSS adicionales (opcional)</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna lateral -->
            <div class="col-lg-4">
                <!-- Configuración -->
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
                            <label class="form-label" for="orden">Orden de Prioridad</label>
                            <input type="number" class="form-control" id="orden" name="orden"
                                   value="<?= $orden ?>" min="0" step="1">
                            <small class="text-muted">Menor número = mayor prioridad</small>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="activo" name="activo" value="1" <?= $activo ? 'checked' : '' ?>>
                            <label class="form-check-label" for="activo">
                                Banner Activo
                            </label>
                        </div>

                        <hr>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                <?= $id ? 'Actualizar Banner' : 'Crear Banner' ?>
                            </button>
                            <a href="<?= Config::getBaseUrl() ?>?route=admin/banners" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Preview del banner -->
                <?php if ($id): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Estadísticas</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">Vistas:</small>
                            <strong class="float-end"><?= number_format($banner['vistas'] ?? 0) ?></strong>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Clicks:</small>
                            <strong class="float-end"><?= number_format($banner['clicks'] ?? 0) ?></strong>
                        </div>
                        <div>
                            <small class="text-muted">CTR:</small>
                            <strong class="float-end">
                                <?php
                                $vistas = $banner['vistas'] ?? 0;
                                $clicks = $banner['clicks'] ?? 0;
                                $ctr = $vistas > 0 ? ($clicks / $vistas) * 100 : 0;
                                echo number_format($ctr, 2) . '%';
                                ?>
                            </strong>
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
    // Preview de imagen al cambiar URL
    const imagenInput = document.getElementById('imagen');
    imagenInput.addEventListener('blur', function() {
        const url = this.value.trim();
        if (url) {
            // Remover preview anterior
            const oldPreview = this.parentElement.querySelector('.img-thumbnail');
            if (oldPreview) oldPreview.remove();

            // Crear nuevo preview
            const img = document.createElement('img');
            img.src = url;
            img.className = 'img-thumbnail mt-2';
            img.style.maxWidth = '300px';
            img.onerror = function() { this.remove(); };
            this.parentElement.appendChild(img);
        }
    });

    // Validación de JSON
    const jsonFields = ['target_audiencia'];
    jsonFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
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
                    }
                } else {
                    this.classList.remove('is-invalid', 'is-valid');
                }
            });
        }
    });
});
</script>

<?php include __DIR__ . '/../../partials/admin_footer.php'; ?>
