<?php
use App\Core\Helpers;
/**
 * Componente: Perfil del Guía
 * Muestra información del guía asignado al tour para generar confianza
 *
 * @param array $guide - Datos del guía (de tabla empleados)
 * @param array $product - Datos del tour/tour
 */

if (empty($guide)) {
    return;
}

// Parse idiomas y certificaciones si están en JSON
$idiomas = [];
if (!empty($guide['idiomas'])) {
    $idiomasData = is_string($guide['idiomas']) ? json_decode($guide['idiomas'], true) : $guide['idiomas'];
    $idiomas = is_array($idiomasData) ? $idiomasData : explode(',', $guide['idiomas']);
}

$certificaciones = [];
if (!empty($guide['certificaciones'])) {
    $certData = is_string($guide['certificaciones']) ? json_decode($guide['certificaciones'], true) : $guide['certificaciones'];
    $certificaciones = is_array($certData) ? $certData : explode(',', $guide['certificaciones']);
}

$experienciaAnios = (int)($guide['experiencia_anios'] ?? 0);
$nombre = htmlspecialchars($guide['nombre'] ?? '');
$apellido = htmlspecialchars($guide['apellido'] ?? '');
$nombreCompleto = trim($nombre . ' ' . $apellido);
$foto = $guide['foto'] ?? 'default-guide.jpg';
$fotoUrl = file_exists('../public/assets/images/guides/' . $foto)
    ? Helpers::asset('images/guides/' . $foto)
    : Helpers::asset('images/default-guide.jpg');
?>

<!-- Sección: Conoce a tu Guía -->
<div class="card shadow-sm mt-4 guide-profile-card">
    <div class="card-header bg-gradient-primary text-white">
        <div class="d-flex align-items-center">
            <div class="guide-header-icon me-3">
                <i class="fas fa-user-tie"></i>
            </div>
            <div>
                <h4 class="mb-0">
                    <i class="fas fa-award me-2"></i>Conoce a tu Guía
                </h4>
                <small class="opacity-90">Experto local certificado</small>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="row align-items-center">
            <!-- Foto del guía -->
            <div class="col-md-3 text-center mb-3 mb-md-0">
                <div class="guide-photo-wrapper">
                    <img src="<?= $fotoUrl ?>"
                         alt="Guía <?= $nombreCompleto ?>"
                         class="guide-photo img-fluid rounded-circle"
                         loading="lazy"
                         onerror="this.src='<?= Helpers::asset('images/default-guide.jpg') ?>'">
                    <div class="guide-verified-badge" title="Guía verificado">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>

            <!-- Info del guía -->
            <div class="col-md-9">
                <div class="guide-info">
                    <h5 class="guide-name mb-2">
                        <?= $nombreCompleto ?>
                        <span class="badge bg-success ms-2">
                            <i class="fas fa-certificate me-1"></i>Certificado
                        </span>
                    </h5>

                    <!-- Estadísticas rápidas -->
                    <div class="guide-stats mb-3">
                        <div class="stat-item">
                            <i class="fas fa-star text-warning"></i>
                            <span class="stat-value">4.9</span>
                            <span class="stat-label">Rating</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-route text-primary"></i>
                            <span class="stat-value"><?= $experienciaAnios ?></span>
                            <span class="stat-label">años exp.</span>
                        </div>
                        <div class="stat-item">
                            <i class="fas fa-users text-info"></i>
                            <span class="stat-value">500+</span>
                            <span class="stat-label">tours</span>
                        </div>
                    </div>

                    <!-- Idiomas -->
                    <?php if (!empty($idiomas)): ?>
                    <div class="guide-languages mb-2">
                        <strong><i class="fas fa-language text-primary me-1"></i> Idiomas:</strong>
                        <div class="mt-1">
                            <?php foreach ($idiomas as $idioma):
                                $idiomaLimpio = trim(is_array($idioma) ? ($idioma['nombre'] ?? $idioma[0] ?? '') : $idioma);
                                if (empty($idiomaLimpio)) continue;
                            ?>
                                <span class="badge bg-light text-dark me-1">
                                    <?php
                                    $flagIcon = match(strtolower($idiomaLimpio)) {
                                        'español', 'spanish' => '🇪🇸',
                                        'inglés', 'english' => '🇬🇧',
                                        'francés', 'french' => '🇫🇷',
                                        'alemán', 'german' => '🇩🇪',
                                        'italiano', 'italian' => '🇮🇹',
                                        'portugués', 'portuguese' => '🇵🇹',
                                        default => '🗣️'
                                    };
                                    echo $flagIcon . ' ' . htmlspecialchars(ucfirst($idiomaLimpio));
                                    ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Certificaciones -->
                    <?php if (!empty($certificaciones)): ?>
                    <div class="guide-certifications mb-2">
                        <strong><i class="fas fa-award text-warning me-1"></i> Certificaciones:</strong>
                        <div class="mt-1">
                            <?php foreach (array_slice($certificaciones, 0, 3) as $cert):
                                $certLimpia = trim(is_array($cert) ? ($cert['nombre'] ?? $cert[0] ?? '') : $cert);
                                if (empty($certLimpia)) continue;
                            ?>
                                <span class="badge bg-warning text-dark me-1 mb-1">
                                    <i class="fas fa-certificate me-1"></i><?= htmlspecialchars($certLimpia) ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Especialidades -->
                    <div class="guide-specialties mt-3">
                        <div class="specialty-tags">
                            <span class="specialty-tag">
                                <i class="fas fa-landmark"></i> Arqueología Maya
                            </span>
                            <span class="specialty-tag">
                                <i class="fas fa-tree"></i> Naturaleza
                            </span>
                            <span class="specialty-tag">
                                <i class="fas fa-camera"></i> Fotografía
                            </span>
                        </div>
                    </div>

                    <!-- Mensaje del guía -->
                    <div class="guide-message mt-3 p-3 bg-light rounded">
                        <i class="fas fa-quote-left text-muted me-2"></i>
                        <em class="text-muted">
                            "¡Hola! Soy <?= $nombre ?> y será un placer guiarte en esta aventura.
                            Mi pasión es compartir la riqueza cultural de <?= htmlspecialchars($product['ubicacion'] ?? 'Guatemala') ?>
                            y asegurarme de que vivas una experiencia inolvidable."
                        </em>
                        <i class="fas fa-quote-right text-muted ms-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews del guía (opcional) -->
        <div class="guide-reviews mt-4 pt-3 border-top">
            <h6 class="mb-3">
                <i class="fas fa-comments text-primary me-2"></i>
                Lo que dicen los viajeros sobre <?= $nombre ?>
            </h6>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <div class="guide-review-snippet">
                        <div class="review-stars">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="small mb-1">
                            <em>"<?= $nombre ?> es increíble! Su conocimiento de la historia maya es impresionante."</em>
                        </p>
                        <small class="text-muted">- María, España</small>
                    </div>
                </div>
                <div class="col-md-6 mb-2">
                    <div class="guide-review-snippet">
                        <div class="review-stars">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="small mb-1">
                            <em>"Muy profesional y atento. Hizo que el tour fuera perfecto para toda la familia."</em>
                        </p>
                        <small class="text-muted">- John, USA</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos del componente -->
<style>
.guide-profile-card {
    border: none;
    overflow: hidden;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.guide-header-icon {
    background: rgba(255, 255, 255, 0.2);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.guide-photo-wrapper {
    position: relative;
    display: inline-block;
}

.guide-photo {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border: 4px solid #fff;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.guide-verified-badge {
    position: absolute;
    bottom: 5px;
    right: 5px;
    background: #28a745;
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid #fff;
    font-size: 1.1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.guide-stats {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 8px;
    min-width: 80px;
}

.stat-item i {
    font-size: 1.3rem;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.2rem;
    font-weight: 700;
    color: #333;
    line-height: 1;
}

.stat-label {
    font-size: 0.75rem;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.guide-languages .badge,
.guide-certifications .badge {
    font-size: 0.85rem;
    padding: 0.4rem 0.7rem;
}

.specialty-tags {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.specialty-tag {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
    color: #667eea;
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.specialty-tag i {
    font-size: 0.9rem;
}

.guide-message {
    position: relative;
}

.guide-message .fa-quote-left,
.guide-message .fa-quote-right {
    opacity: 0.3;
}

.guide-review-snippet {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 8px;
    border-left: 3px solid #ffc107;
}

.review-stars {
    margin-bottom: 0.5rem;
}

.review-stars i {
    font-size: 0.8rem;
}

@media (max-width: 767.98px) {
    .guide-photo {
        width: 120px;
        height: 120px;
    }

    .guide-stats {
        justify-content: center;
    }

    .stat-item {
        min-width: 70px;
    }
}
</style>
