<?php
use App\Core\Config;
use App\Core\Helpers;
$baseUrl = Config::getBaseUrl();
$filters = $filters ?? [];
$tours = $tours ?? [];
$categories = $categories ?? [];
$total_tours = $total_tours ?? 0;
?>

<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<main id="main-content" class="py-5">
    <div class="container">

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="display-5 fw-bold">
                    <i class="fas fa-map-marked-alt text-primary me-2"></i>
                    <?php echo htmlspecialchars($title ?? 'Nuestros Destinos'); ?>
                </h1>
                <p class="lead text-muted">
                    <?php echo $total_tours; ?> destinos disponibles
                </p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filterSection">
                    <i class="fas fa-filter me-2"></i>
                    Filtros
                </button>
            </div>
        </div>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Filtros -->
        <div class="collapse show mb-4" id="filterSection">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="get" action="<?php echo $baseUrl; ?>" id="filterForm">
                        <input type="hidden" name="route" value="tours">

                        <div class="row g-3">
                            <!-- Búsqueda -->
                            <div class="col-md-4">
                                <label for="search" class="form-label">Buscar</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="search"
                                    name="search"
                                    placeholder="Buscar destinos..."
                                    value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                            </div>

                            <!-- Categoría -->
                            <div class="col-md-4">
                                <label for="category" class="form-label">Categoría</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        <?php echo ($filters['category'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Dificultad -->
                            <div class="col-md-4">
                                <label for="difficulty" class="form-label">Dificultad</label>
                                <select class="form-select" id="difficulty" name="difficulty">
                                    <option value="">Cualquier dificultad</option>
                                    <option value="facil" <?php echo ($filters['difficulty'] ?? '') == 'facil' ? 'selected' : ''; ?>>Fácil</option>
                                    <option value="moderado" <?php echo ($filters['difficulty'] ?? '') == 'moderado' ? 'selected' : ''; ?>>Moderado</option>
                                    <option value="dificil" <?php echo ($filters['difficulty'] ?? '') == 'dificil' ? 'selected' : ''; ?>>Difícil</option>
                                </select>
                            </div>

                            <!-- Precio Mínimo -->
                            <div class="col-md-3">
                                <label for="min_price" class="form-label">Precio mínimo</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="min_price"
                                    name="min_price"
                                    placeholder="0"
                                    min="0"
                                    step="100"
                                    value="<?php echo htmlspecialchars($filters['min_price'] ?? ''); ?>">
                            </div>

                            <!-- Precio Máximo -->
                            <div class="col-md-3">
                                <label for="max_price" class="form-label">Precio máximo</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="max_price"
                                    name="max_price"
                                    placeholder="50000"
                                    min="0"
                                    step="100"
                                    value="<?php echo htmlspecialchars($filters['max_price'] ?? ''); ?>">
                            </div>

                            <!-- Duración -->
                            <div class="col-md-3">
                                <label for="duration" class="form-label">Duración</label>
                                <select class="form-select" id="duration" name="duration">
                                    <option value="">Cualquier duración</option>
                                    <option value="half-day" <?php echo ($filters['duration'] ?? '') == 'half-day' ? 'selected' : ''; ?>>Medio día</option>
                                    <option value="full-day" <?php echo ($filters['duration'] ?? '') == 'full-day' ? 'selected' : ''; ?>>Día completo</option>
                                    <option value="multi-day" <?php echo ($filters['duration'] ?? '') == 'multi-day' ? 'selected' : ''; ?>>Varios días</option>
                                </select>
                            </div>

                            <!-- Ordenar -->
                            <div class="col-md-3">
                                <label for="sort" class="form-label">Ordenar por</label>
                                <select class="form-select" id="sort" name="sort">
                                    <option value="destacado" <?php echo ($filters['sort'] ?? 'destacado') == 'destacado' ? 'selected' : ''; ?>>Destacados</option>
                                    <option value="nombre" <?php echo ($filters['sort'] ?? '') == 'nombre' ? 'selected' : ''; ?>>Nombre A-Z</option>
                                    <option value="precio_asc" <?php echo ($filters['sort'] ?? '') == 'precio_asc' ? 'selected' : ''; ?>>Precio: Menor a Mayor</option>
                                    <option value="precio_desc" <?php echo ($filters['sort'] ?? '') == 'precio_desc' ? 'selected' : ''; ?>>Precio: Mayor a Menor</option>
                                    <option value="duracion" <?php echo ($filters['sort'] ?? '') == 'duracion' ? 'selected' : ''; ?>>Duración</option>
                                </select>
                            </div>

                            <!-- Checkboxes -->
                            <div class="col-md-12">
                                <div class="form-check form-check-inline">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="featured"
                                        name="featured"
                                        value="1"
                                        <?php echo !empty($filters['featured']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="featured">
                                        <i class="fas fa-star text-warning me-1"></i>
                                        Solo destacados
                                    </label>
                                </div>

                                <div class="form-check form-check-inline">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        id="verified"
                                        name="verified"
                                        value="1"
                                        <?php echo !empty($filters['verified']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="verified">
                                        <i class="fas fa-check-circle text-success me-1"></i>
                                        Operadores verificados
                                    </label>
                                </div>
                            </div>

                            <!-- Botones -->
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search me-2"></i>
                                    Buscar
                                </button>
                                <a href="<?php echo $baseUrl; ?>?route=tours" class="btn btn-secondary">
                                    <i class="fas fa-redo me-2"></i>
                                    Limpiar filtros
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Listado de tours -->
        <?php if (empty($tours)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="fas fa-info-circle fa-3x mb-3"></i>
            <h4>No se encontraron destinos</h4>
            <p>Intenta ajustar los filtros de búsqueda</p>
            <a href="<?php echo $baseUrl; ?>?route=tours" class="btn btn-primary mt-3">
                Ver todos los destinos
            </a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($tours as $tour): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 shadow-sm product-card">
                    <!-- Imagen -->
                    <div class="position-relative">
                        <?php if (!empty($tour['imagen_principal'])): ?>
                        <img
                            src="<?php echo htmlspecialchars(Helpers::tourImage($tour['imagen_principal'] ?? null, 'images/default-destination.jpg')); ?>"
                            class="card-img-top"
                            alt="<?php echo htmlspecialchars($tour['nombre']); ?>"
                            loading="lazy"
                            decoding="async"
                            style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                        <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="fas fa-image fa-3x text-white"></i>
                        </div>
                        <?php endif; ?>

                        <!-- Badges -->
                        <div class="position-absolute top-0 end-0 p-2 d-flex flex-column gap-1 align-items-end">
                            <?php if (!empty($tour['es_privado'])): ?>
                            <span class="badge text-white" style="background:linear-gradient(135deg,#1a237e,#283593);">
                                <i class="fas fa-lock me-1"></i>Privado
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($tour['destacado'])): ?>
                            <span class="badge bg-warning text-dark">
                                <i class="fas fa-star"></i> Destacado
                            </span>
                            <?php endif; ?>
                        </div>

                        <div class="position-absolute top-0 start-0 p-2">
                            <?php if (!empty($tour['verified'])): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle"></i> Verificado
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card-body">
                        <h5 class="card-title">
                            <?php echo htmlspecialchars($tour['nombre']); ?>
                        </h5>

                        <?php if (!empty($tour['descripcion_corta'])): ?>
                        <p class="card-text text-muted small">
                            <?php echo htmlspecialchars(substr($tour['descripcion_corta'], 0, 100)); ?>
                            <?php echo strlen($tour['descripcion_corta']) > 100 ? '...' : ''; ?>
                        </p>
                        <?php endif; ?>

                        <!-- Info -->
                        <div class="mb-3">
                            <?php if (!empty($tour['duracion'])): ?>
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo htmlspecialchars($tour['duracion']); ?>
                            </small>
                            <br>
                            <?php endif; ?>

                            <?php if (!empty($tour['dificultad'])): ?>
                            <small class="text-muted">
                                <i class="fas fa-hiking me-1"></i>
                                <?php
                                $dificultad_map = [
                                    'facil' => 'Fácil',
                                    'moderado' => 'Moderado',
                                    'dificil' => 'Difícil'
                                ];
                                echo $dificultad_map[$tour['dificultad']] ?? ucfirst($tour['dificultad']);
                                ?>
                            </small>
                            <br>
                            <?php endif; ?>

                            <?php if (!empty($tour['ubicacion'])): ?>
                            <small class="text-muted">
                                <i class="fas fa-map-marker-alt me-1"></i>
                                <?php echo htmlspecialchars($tour['ubicacion']); ?>
                            </small>
                            <?php endif; ?>
                        </div>

                        <!-- Precio -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <?php
                                $__pg = null;
                                if (!empty($tour['precios_grupo'])) {
                                    $__pgd = json_decode($tour['precios_grupo'], true);
                                    if (is_array($__pgd) && count($__pgd)) $__pg = $__pgd;
                                }
                                if ($__pg): ?>
                                    <div class="small text-muted">Desde</div>
                                    <span class="h5 mb-0" style="color:#1a237e;">
                                        $<?= number_format(min(array_column($__pg,'precio')), 0) ?>
                                        <small class="fs-6 fw-normal text-muted">/ persona</small>
                                    </span>
                                <?php elseif (!empty($tour['precio_descuento']) && $tour['precio_descuento'] < $tour['precio']): ?>
                                    <span class="text-muted text-decoration-line-through small">$<?= number_format($tour['precio'], 2) ?></span><br>
                                    <span class="h5 text-primary mb-0">$<?= number_format($tour['precio_descuento'], 2) ?></span>
                                <?php else: ?>
                                    <span class="h5 text-primary mb-0">$<?= number_format($tour['precio'], 2) ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="<?php echo $baseUrl; ?>?route=tour/<?php echo $tour['id']; ?>"
                               class="btn <?= !empty($tour['es_privado']) ? 'btn-dark' : 'btn-primary' ?>">
                                <?= !empty($tour['es_privado']) ? '<i class="fas fa-lock me-1"></i>Ver detalles' : 'Ver más' ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</main>

<style>
.product-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}
</style>

<script src="<?php echo $baseUrl; ?>/assets/js/tours.js"></script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
