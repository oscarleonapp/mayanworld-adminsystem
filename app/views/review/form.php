<?php
use App\Core\Helpers;
$invitation = $data['invitation'] ?? null;
$tour = $data['tour'] ?? null;
$token = $data['token'] ?? '';

if (!$invitation || !$tour) {
    header('Location: ?route=tours');
    exit;
}

// Datos del formulario previo si hay errores
$formData = $_SESSION['form_data'] ?? [];
$errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_data'], $_SESSION['form_errors']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparte tu Experiencia - <?= htmlspecialchars($tour['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .review-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 3rem 0;
            color: white;
        }
        .tour-info {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .verified-badge {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
        }
        .rating-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .star-rating {
            display: flex;
            gap: 0.25rem;
            margin-bottom: 0.5rem;
        }
        .star-rating input[type="radio"] {
            display: none;
        }
        .star-rating label {
            font-size: 1.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }
        .star-rating label:hover,
        .star-rating label.active {
            color: #ffc107;
        }
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 4px solid #007bff;
        }
        .char-counter {
            font-size: 0.8rem;
            color: #6c757d;
            text-align: right;
            margin-top: 0.25rem;
        }
        .char-counter.warning {
            color: #fd7e14;
        }
        .char-counter.danger {
            color: #dc3545;
        }
        .traveler-info {
            background: #e3f2fd;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .submit-section {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
        }
        @media (max-width: 768px) {
            .star-rating label {
                font-size: 1.2rem;
            }
            .form-section {
                padding: 1rem;
            }
        }
        .is-invalid {
            border-color: #dc3545;
        }
        .invalid-feedback {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="review-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-3">
                        <i class="fas fa-star me-2"></i>
                        Comparte tu Experiencia
                    </h1>
                    <p class="lead mb-0">Tu opinión es muy valiosa para futuros viajeros</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="verified-badge">
                        <i class="fas fa-certificate me-2"></i>
                        Review Verificado
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <!-- Información del Tour -->
        <div class="tour-info">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <img src="<?= htmlspecialchars(Helpers::tourImage($tour['imagen_principal'] ?? null, 'images/default-destination.jpg')) ?>" 
                         alt="<?= htmlspecialchars($tour['nombre']) ?>"
                         class="img-fluid rounded"
                         loading="lazy"
                         decoding="async">
                </div>
                <div class="col-md-9">
                    <h3 class="mb-2"><?= htmlspecialchars($tour['nombre']) ?></h3>
                    <p class="text-muted mb-2">
                        <i class="fas fa-calendar me-2"></i>
                        Fecha del tour: <?= date('d \d\e F, Y', strtotime($invitation['fecha_salida'])) ?>
                    </p>
                    <p class="text-muted mb-0">
                        <i class="fas fa-user me-2"></i>
                        <?= htmlspecialchars($invitation['usuario_nombre']) ?>
                    </p>
                </div>
            </div>
        </div>

        <form action="?route=review/form&token=<?= urlencode($token) ?>" method="POST" id="reviewForm">
            <input type="hidden" name="csrf_token" value="<?= $data['csrf_token'] ?>">

            <!-- Calificaciones -->
            <div class="form-section">
                <h4 class="mb-4">
                    <i class="fas fa-star text-warning me-2"></i>
                    Califica tu Experiencia
                </h4>

                <!-- Calificación General -->
                <div class="mb-4">
                    <label class="form-label fw-semibold">
                        Calificación General <span class="text-danger">*</span>
                    </label>
                    <div class="star-rating" data-rating="calificacion_general">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <input type="radio" name="calificacion_general" value="<?= $i ?>" 
                                   id="general_<?= $i ?>" 
                                   <?= ($formData['calificacion_general'] ?? '') == $i ? 'checked' : '' ?>>
                            <label for="general_<?= $i ?>" class="<?= ($formData['calificacion_general'] ?? 0) >= $i ? 'active' : '' ?>">
                                <i class="fas fa-star"></i>
                            </label>
                        <?php endfor; ?>
                        <span class="ms-3 rating-text">Selecciona una calificación</span>
                    </div>
                    <?php if (isset($errors['calificacion_general'])): ?>
                        <div class="invalid-feedback"><?= $errors['calificacion_general'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Calificaciones Específicas -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Guía Turístico</label>
                        <div class="star-rating" data-rating="calificacion_guia">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="calificacion_guia" value="<?= $i ?>" 
                                       id="guia_<?= $i ?>"
                                       <?= ($formData['calificacion_guia'] ?? '') == $i ? 'checked' : '' ?>>
                                <label for="guia_<?= $i ?>" class="<?= ($formData['calificacion_guia'] ?? 0) >= $i ? 'active' : '' ?>">
                                    <i class="fas fa-star"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Transporte</label>
                        <div class="star-rating" data-rating="calificacion_transporte">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="calificacion_transporte" value="<?= $i ?>" 
                                       id="transporte_<?= $i ?>"
                                       <?= ($formData['calificacion_transporte'] ?? '') == $i ? 'checked' : '' ?>>
                                <label for="transporte_<?= $i ?>" class="<?= ($formData['calificacion_transporte'] ?? 0) >= $i ? 'active' : '' ?>">
                                    <i class="fas fa-star"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Organización</label>
                        <div class="star-rating" data-rating="calificacion_organizacion">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="calificacion_organizacion" value="<?= $i ?>" 
                                       id="organizacion_<?= $i ?>"
                                       <?= ($formData['calificacion_organizacion'] ?? '') == $i ? 'checked' : '' ?>>
                                <label for="organizacion_<?= $i ?>" class="<?= ($formData['calificacion_organizacion'] ?? 0) >= $i ? 'active' : '' ?>">
                                    <i class="fas fa-star"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Relación Calidad-Precio</label>
                        <div class="star-rating" data-rating="calificacion_valor">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="calificacion_valor" value="<?= $i ?>" 
                                       id="valor_<?= $i ?>"
                                       <?= ($formData['calificacion_valor'] ?? '') == $i ? 'checked' : '' ?>>
                                <label for="valor_<?= $i ?>" class="<?= ($formData['calificacion_valor'] ?? 0) >= $i ? 'active' : '' ?>">
                                    <i class="fas fa-star"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Título y Comentario -->
            <div class="form-section">
                <h4 class="mb-4">
                    <i class="fas fa-edit text-primary me-2"></i>
                    Cuéntanos tu Experiencia
                </h4>

                <!-- Título -->
                <div class="mb-3">
                    <label for="titulo" class="form-label fw-semibold">
                        Título de tu Review <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control <?= isset($errors['titulo']) ? 'is-invalid' : '' ?>" 
                           id="titulo" name="titulo" maxlength="200"
                           placeholder="Ej: Una experiencia increíble en Tikal"
                           value="<?= htmlspecialchars($formData['titulo'] ?? '') ?>">
                    <div class="char-counter">
                        <span id="titulo-count">0</span>/200 caracteres
                    </div>
                    <?php if (isset($errors['titulo'])): ?>
                        <div class="invalid-feedback"><?= $errors['titulo'] ?></div>
                    <?php endif; ?>
                </div>

                <!-- Comentario -->
                <div class="mb-3">
                    <label for="comentario" class="form-label fw-semibold">
                        Comentario Detallado <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control <?= isset($errors['comentario']) ? 'is-invalid' : '' ?>" 
                              id="comentario" name="comentario" rows="6" maxlength="2000"
                              placeholder="Comparte los detalles de tu experiencia: ¿qué te gustó más? ¿qué recomendarías? ¿cómo fue el servicio?"><?= htmlspecialchars($formData['comentario'] ?? '') ?></textarea>
                    <div class="char-counter">
                        <span id="comentario-count">0</span>/2000 caracteres (mínimo 50)
                    </div>
                    <?php if (isset($errors['comentario'])): ?>
                        <div class="invalid-feedback"><?= $errors['comentario'] ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información del Viajero -->
            <div class="traveler-info">
                <h4 class="mb-3">
                    <i class="fas fa-user-friends text-info me-2"></i>
                    Información Adicional
                </h4>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="experiencia_previa" class="form-label">Experiencia en Tours</label>
                        <select class="form-select" id="experiencia_previa" name="experiencia_previa">
                            <option value="primera_vez" <?= ($formData['experiencia_previa'] ?? 'primera_vez') === 'primera_vez' ? 'selected' : '' ?>>Primera vez</option>
                            <option value="ocasional" <?= ($formData['experiencia_previa'] ?? '') === 'ocasional' ? 'selected' : '' ?>>Ocasional</option>
                            <option value="frecuente" <?= ($formData['experiencia_previa'] ?? '') === 'frecuente' ? 'selected' : '' ?>>Frecuente</option>
                            <option value="experto" <?= ($formData['experiencia_previa'] ?? '') === 'experto' ? 'selected' : '' ?>>Experto</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="tipo_viajero" class="form-label">Tipo de Viajero</label>
                        <select class="form-select" id="tipo_viajero" name="tipo_viajero">
                            <option value="solo" <?= ($formData['tipo_viajero'] ?? 'solo') === 'solo' ? 'selected' : '' ?>>Solo</option>
                            <option value="pareja" <?= ($formData['tipo_viajero'] ?? '') === 'pareja' ? 'selected' : '' ?>>En pareja</option>
                            <option value="familia" <?= ($formData['tipo_viajero'] ?? '') === 'familia' ? 'selected' : '' ?>>En familia</option>
                            <option value="amigos" <?= ($formData['tipo_viajero'] ?? '') === 'amigos' ? 'selected' : '' ?>>Con amigos</option>
                            <option value="business" <?= ($formData['tipo_viajero'] ?? '') === 'business' ? 'selected' : '' ?>>Viaje de negocios</option>
                        </select>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="grupo_edad" class="form-label">Grupo de Edad</label>
                        <select class="form-select" id="grupo_edad" name="grupo_edad">
                            <option value="18-25" <?= ($formData['grupo_edad'] ?? '') === '18-25' ? 'selected' : '' ?>>18-25 años</option>
                            <option value="26-35" <?= ($formData['grupo_edad'] ?? '26-35') === '26-35' ? 'selected' : '' ?>>26-35 años</option>
                            <option value="36-45" <?= ($formData['grupo_edad'] ?? '') === '36-45' ? 'selected' : '' ?>>36-45 años</option>
                            <option value="46-55" <?= ($formData['grupo_edad'] ?? '') === '46-55' ? 'selected' : '' ?>>46-55 años</option>
                            <option value="56-65" <?= ($formData['grupo_edad'] ?? '') === '56-65' ? 'selected' : '' ?>>56-65 años</option>
                            <option value="65+" <?= ($formData['grupo_edad'] ?? '') === '65+' ? 'selected' : '' ?>>65+ años</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Envío -->
            <div class="submit-section">
                <h4 class="mb-3">¡Listo para Compartir!</h4>
                <p class="mb-4">Tu review será verificado automáticamente y ayudará a otros viajeros</p>
                
                <button type="submit" class="btn btn-light btn-lg px-5" id="submitBtn">
                    <i class="fas fa-paper-plane me-2"></i>
                    Publicar Review
                </button>
                
                <div class="mt-3">
                    <small class="opacity-75">
                        <i class="fas fa-shield-alt me-1"></i>
                        Review verificado • Protección de datos garantizada
                    </small>
                </div>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Funcionalidad de estrellas
            document.querySelectorAll('.star-rating').forEach(rating => {
                const stars = rating.querySelectorAll('label');
                const inputs = rating.querySelectorAll('input[type="radio"]');
                const ratingName = rating.dataset.rating;
                
                stars.forEach((star, index) => {
                    star.addEventListener('click', function() {
                        // Actualizar estado visual
                        stars.forEach((s, i) => {
                            s.classList.toggle('active', i <= index);
                        });
                        
                        // Actualizar texto para calificación general
                        if (ratingName === 'calificacion_general') {
                            const ratingText = rating.querySelector('.rating-text');
                            const texts = ['', 'Muy malo', 'Malo', 'Regular', 'Bueno', 'Excelente'];
                            ratingText.textContent = texts[index + 1];
                            ratingText.className = 'ms-3 rating-text text-' + 
                                ['', 'danger', 'danger', 'warning', 'success', 'success'][index + 1];
                        }
                    });
                });
                
                // Restaurar estado visual de estrellas
                inputs.forEach((input, index) => {
                    if (input.checked) {
                        for (let i = 0; i <= index; i++) {
                            stars[i].classList.add('active');
                        }
                    }
                });
            });

            // Contadores de caracteres
            const titleInput = document.getElementById('titulo');
            const commentInput = document.getElementById('comentario');
            const titleCounter = document.getElementById('titulo-count');
            const commentCounter = document.getElementById('comentario-count');

            function updateCounter(input, counter, max, min = 0) {
                const count = input.value.length;
                counter.textContent = count;
                
                const counterParent = counter.parentElement;
                counterParent.classList.remove('warning', 'danger');
                
                if (count > max * 0.9) {
                    counterParent.classList.add('warning');
                }
                if (count >= max) {
                    counterParent.classList.add('danger');
                }

                if (min > 0 && count < min) {
                    counterParent.classList.add('danger');
                }
            }

            titleInput.addEventListener('input', () => updateCounter(titleInput, titleCounter, 200));
            commentInput.addEventListener('input', () => updateCounter(commentInput, commentCounter, 2000, 50));

            // Inicializar contadores
            updateCounter(titleInput, titleCounter, 200);
            updateCounter(commentInput, commentCounter, 2000, 50);

            // Validación del formulario
            const form = document.getElementById('reviewForm');
            const submitBtn = document.getElementById('submitBtn');

            form.addEventListener('submit', function(e) {
                let valid = true;

                // Verificar calificación general
                const generalRating = form.querySelector('input[name="calificacion_general"]:checked');
                if (!generalRating) {
                    alert('Por favor selecciona una calificación general');
                    valid = false;
                }

                // Verificar título
                if (titleInput.value.trim().length === 0) {
                    alert('Por favor ingresa un título para tu review');
                    titleInput.focus();
                    valid = false;
                }

                // Verificar comentario
                if (commentInput.value.trim().length < 50) {
                    alert('El comentario debe tener al menos 50 caracteres');
                    commentInput.focus();
                    valid = false;
                }

                if (!valid) {
                    e.preventDefault();
                } else {
                    // Deshabilitar botón para evitar doble envío
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
                }
            });
        });
    </script>
</body>
</html>
