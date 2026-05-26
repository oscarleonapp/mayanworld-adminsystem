<?php
use App\Core\Config;
use App\Core\Helpers;
require_once __DIR__ . '/../layouts/admin_header.php'; ?>

<div class="container-fluid p-4">
    <?php
        $actionTitle = 'Configuración del Sistema';
        $actionSubtitle = 'Administra las configuraciones generales de la aplicación';
        $actionButtons = [];
        include __DIR__ . '/../partials/admin_action_bar.php';
    ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error ?? '', ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <?php if ($successMessage = Helpers::getFlashMessage('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage = Helpers::getFlashMessage('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Configuraciones Generales -->
        <div class="col-lg-8">
            <!-- Hero (Imagen o Video) -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-photo-video me-2"></i>Hero de Portada (Imagen o Video)</h5>
                </div>
                <div class="card-body">
                    <!-- Tabs para seleccionar tipo -->
                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="image-tab" data-bs-toggle="tab" data-bs-target="#image-panel" type="button" role="tab">
                                <i class="fas fa-image me-2"></i>Imagen
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="video-tab" data-bs-toggle="tab" data-bs-target="#video-panel" type="button" role="tab">
                                <i class="fas fa-video me-2"></i>Video
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="url-tab" data-bs-toggle="tab" data-bs-target="#url-panel" type="button" role="tab">
                                <i class="fas fa-link me-2"></i>URL Externa
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <!-- Subir Imagen -->
                        <div class="tab-pane fade show active" id="image-panel" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="text-muted mb-3">Sube una imagen para el hero de la página de inicio. Recomendado: 1920x1080 píxeles.</p>

                                    <form action="<?= Config::getBaseUrl() ?>?route=admin/uploadHeroImage" method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="hero_image" class="form-label">Seleccionar imagen</label>
                                            <input type="file" class="form-control" id="hero_image" name="hero_image" accept="image/jpeg,image/jpg,image/png,image/webp" required>
                                            <div class="form-text">Formatos: JPG, PNG, WebP. Máximo 10MB.</div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload me-2"></i>Subir Imagen
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Vista previa actual:</label>
                                    <div class="border rounded p-2 bg-light">
                                        <?php
                                        $currentHeroType = $heroType ?? 'image';
                                        $currentMedia = $heroImage ?? 'images/hero-travel.jpg';
                                        ?>
                                        <?php if ($currentHeroType === 'video' && (strpos($currentMedia, '.mp4') !== false || strpos($currentMedia, '.webm') !== false)): ?>
                                            <video class="w-100 rounded" style="max-height: 200px; object-fit: cover;" controls>
                                                <source src="<?= Helpers::asset($currentMedia) ?>" type="video/<?= pathinfo($currentMedia, PATHINFO_EXTENSION) ?>">
                                            </video>
                                        <?php elseif ($currentHeroType === 'youtube' || strpos($currentMedia, 'youtube') !== false || strpos($currentMedia, 'youtu.be') !== false): ?>
                                            <div class="ratio ratio-16x9">
                                                <iframe src="<?= $currentMedia ?>" frameborder="0" allowfullscreen></iframe>
                                            </div>
                                        <?php else: ?>
                                            <img src="<?= Helpers::asset($currentMedia) ?>"
                                                 alt="Hero actual"
                                                 class="img-fluid rounded"
                                                 style="max-height: 200px; width: 100%; object-fit: cover;">
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted d-block mt-2">
                                        Tipo: <span class="badge bg-info"><?= ucfirst($currentHeroType) ?></span><br>
                                        Ruta: <code><?= htmlspecialchars($currentMedia, ENT_QUOTES, 'UTF-8') ?></code>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Subir Video -->
                        <div class="tab-pane fade" id="video-panel" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="text-muted mb-3">Sube un video para el hero. Recomendado: MP4 o WebM, máximo 30 segundos, sin audio o con audio bajo.</p>

                                    <form action="<?= Config::getBaseUrl() ?>?route=admin/uploadHeroVideo" method="POST" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="hero_video" class="form-label">Seleccionar video</label>
                                            <input type="file" class="form-control" id="hero_video" name="hero_video" accept="video/mp4,video/webm" required>
                                            <div class="form-text">Formatos: MP4, WebM. Máximo 50MB.</div>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="video_autoplay" name="video_autoplay" value="1" checked>
                                            <label class="form-check-label" for="video_autoplay">
                                                Reproducir automáticamente (muted)
                                            </label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="video_loop" name="video_loop" value="1" checked>
                                            <label class="form-check-label" for="video_loop">
                                                Repetir en bucle
                                            </label>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-upload me-2"></i>Subir Video
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-info">
                                        <strong><i class="fas fa-lightbulb me-2"></i>Consejos para videos de hero:</strong>
                                        <ul class="mb-0 mt-2 small">
                                            <li>Usa videos cortos (15-30 segundos)</li>
                                            <li>Comprime el video para web</li>
                                            <li>Considera usar un poster/imagen de respaldo</li>
                                            <li>El video se reproducirá en loop automáticamente</li>
                                            <li>En móviles puede mostrarse una imagen estática</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- URL Externa (YouTube, etc) -->
                        <div class="tab-pane fade" id="url-panel" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="text-muted mb-3">Usa un video de YouTube o una URL externa para el hero.</p>

                                    <form action="<?= Config::getBaseUrl() ?>?route=admin/saveHeroUrl" method="POST">
                                        <div class="mb-3">
                                            <label for="hero_url" class="form-label">URL del video</label>
                                            <input type="url" class="form-control" id="hero_url" name="hero_url"
                                                   placeholder="https://www.youtube.com/embed/VIDEO_ID" required>
                                            <div class="form-text">
                                                Para YouTube: Usa el formato embed: https://www.youtube.com/embed/VIDEO_ID
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="hero_poster" class="form-label">Imagen de respaldo (opcional)</label>
                                            <input type="text" class="form-control" id="hero_poster" name="hero_poster"
                                                   placeholder="images/hero-poster.jpg">
                                            <div class="form-text">Imagen que se mostrará en dispositivos móviles</div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Guardar URL
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <div class="alert alert-warning">
                                        <strong><i class="fas fa-info-circle me-2"></i>Cómo obtener URL de YouTube:</strong>
                                        <ol class="mb-0 mt-2 small">
                                            <li>Abre el video en YouTube</li>
                                            <li>Haz clic en "Compartir"</li>
                                            <li>Selecciona "Insertar"</li>
                                            <li>Copia solo la URL del iframe (src="...")</li>
                                        </ol>
                                    </div>
                                    <div class="alert alert-secondary">
                                        <strong>Ejemplo:</strong><br>
                                        <code class="small">https://www.youtube.com/embed/dQw4w9WgXcQ</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Configuraciones del Sistema</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($configs)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Clave</th>
                                        <th>Valor</th>
                                        <th>Descripción</th>
                                        <th width="100">Tipo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($configs as $config): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($config['clave'], ENT_QUOTES, 'UTF-8') ?></code></td>
                                            <td><strong><?= htmlspecialchars($config['valor'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                            <td class="text-muted"><?= htmlspecialchars($config['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($config['tipo'] ?? 'texto', ENT_QUOTES, 'UTF-8') ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-cog fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay configuraciones disponibles</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Información del Sistema -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información del Sistema</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Aplicación:</dt>
                        <dd class="col-sm-6"><?= Config::APP_NAME ?></dd>

                        <dt class="col-sm-6">Versión:</dt>
                        <dd class="col-sm-6"><code><?= Config::APP_VERSION ?></code></dd>

                        <dt class="col-sm-6">PHP:</dt>
                        <dd class="col-sm-6"><code><?= phpversion() ?></code></dd>

                        <dt class="col-sm-6">Base de Datos:</dt>
                        <dd class="col-sm-6"><code><?= Config::DB_NAME ?></code></dd>

                        <dt class="col-sm-6">Charset:</dt>
                        <dd class="col-sm-6"><code><?= Config::DB_CHARSET ?></code></dd>

                        <dt class="col-sm-6">Zona Horaria:</dt>
                        <dd class="col-sm-6"><code><?= date_default_timezone_get() ?></code></dd>

                        <dt class="col-sm-6">Fecha Actual:</dt>
                        <dd class="col-sm-6"><?= date('Y-m-d H:i:s') ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-palette me-2"></i>Apariencia</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Tema Admin:</label>
                        <div class="d-flex gap-2">
                            <span class="badge <?= Config::ADMIN_THEME === 'new' ? 'bg-primary' : 'bg-secondary' ?>">
                                <?= Config::ADMIN_THEME === 'new' ? 'Nuevo' : 'Clásico' ?>
                            </span>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Versión de Assets:</label>
                        <div><code><?= Config::ASSET_VERSION ?></code></div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Herramientas</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= Config::getBaseUrl() ?>test_utf8.php" class="btn btn-outline-primary btn-sm" target="_blank">
                            <i class="fas fa-font me-2"></i>Verificar UTF-8
                        </a>
                        <a href="<?= Config::getBaseUrl() ?>diagnose_utf8.php" class="btn btn-outline-info btn-sm" target="_blank">
                            <i class="fas fa-stethoscope me-2"></i>Diagnóstico UTF-8
                        </a>
                        <a href="<?= Config::getBaseUrl() ?>check_db.php" class="btn btn-outline-secondary btn-sm" target="_blank">
                            <i class="fas fa-database me-2"></i>Verificar BD
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/admin_footer.php'; ?>
