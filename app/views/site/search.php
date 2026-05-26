<?php 

use App\Core\Config;
use App\Core\Helpers;
$cleanQuery = trim((string)($query ?? ''));
$title = ($cleanQuery ? ("Buscar: '" . htmlspecialchars($cleanQuery) . "'") : 'Buscar') . ' | Travel Mayan World';
$metaDescription = $cleanQuery
    ? ('Resultados de búsqueda para "' . htmlspecialchars($cleanQuery) . '" en destinos y experiencias de viaje.')
    : 'Busca destinos y experiencias por nombre y categoría.';
$metaImage = Helpers::asset('images/hero-travel.jpg');
include __DIR__ . '/../layouts/header.php'; ?>

<div class="container py-5">
    <h1 class="mb-4">Resultados de "<?= htmlspecialchars($query ?? '') ?>"</h1>

    <form class="row g-3 mb-4" method="GET" action="<?= Config::getBaseUrl() ?>">
        <input type="hidden" name="route" value="search">
        <div class="col-md-6">
            <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($query ?? '') ?>" placeholder="Buscar destinos...">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">Todas las categorías</option>
                <?php foreach (($categories ?? []) as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($category == $cat['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100"><i class="fas fa-search me-2"></i>Buscar</button>
        </div>
    </form>

    <?php if (!empty($results)): ?>
        <div class="row g-3">
            <?php foreach ($results as $product): ?>
                <div class="col-md-4">
                    <div class="card h-100">
                        <?php
                        $img = Helpers::tourImage($product['imagen_principal'] ?? null, 'images/placeholder.jpg');
                        $img = $img . '?v=' . time();
                        ?>
                        <img src="<?= htmlspecialchars($img) ?>"
                             class="card-img-top skeleton"
                             alt="<?= htmlspecialchars($product['nombre'] ?? 'Tour') ?>"
                             loading="lazy" decoding="async"
                             width="800" height="450"
                             style="height: 200px; object-fit: cover;"
                             onerror="this.src='<?= Helpers::asset('images/default-destination.jpg') ?>'">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($product['nombre'] ?? '') ?></h5>
                            <p class="card-text text-muted"><?= htmlspecialchars($product['descripcion_corta'] ?? '') ?></p>
                            <a class="btn btn-outline-primary" href="<?= Config::getBaseUrl() ?>?route=tour/<?= $product['id'] ?>">
                                Ver detalle
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No hay resultados para esta búsqueda.</div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
