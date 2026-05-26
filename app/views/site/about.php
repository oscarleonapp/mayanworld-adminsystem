<?php
use App\Core\Config;
use App\Core\Helpers;

$hero = $about['hero'] ?? [];
$stats = $about['stats'] ?? [];
$mission = $about['mission'] ?? [];
$values = $about['values'] ?? [];
$story = $about['story'] ?? [];
$team = $about['team'] ?? [];
$cta = $about['cta'] ?? [];

$heroImage = !empty($hero['image']) ? Helpers::asset($hero['image']) : Helpers::asset('images/hero-travel.jpg');
include __DIR__ . '/../layouts/header.php';
?>

<section class="about-hero" style="background-image: url('<?= $heroImage ?>');">
    <div class="overlay"></div>
    <div class="container position-relative py-5">
        <div class="row align-items-center">
            <div class="col-lg-7 text-white">
                <span class="badge bg-light text-dark mb-3">Acerca de nosotros</span>
                <h1 class="display-4 fw-bold mb-3"><?= htmlspecialchars($hero['title'] ?? 'Conecta con el Mundo Maya') ?></h1>
                <p class="lead mb-4"><?= htmlspecialchars($hero['subtitle'] ?? 'Creamos experiencias auténticas en Guatemala, Belice y México.') ?></p>
                <a href="<?= Config::getBaseUrl() . ($hero['cta_link'] ?? '?route=tours') ?>" class="btn btn-light btn-lg">
                    <?= htmlspecialchars($hero['cta_text'] ?? 'Conoce nuestros tours') ?>
                    <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
            <div class="col-lg-5 text-white mt-4 mt-lg-0">
                <div class="about-highlights p-4">
                    <h6 class="text-uppercase text-white-50 mb-3">En números</h6>
                    <div class="row g-3">
                        <?php foreach ($stats as $stat): ?>
                            <div class="col-6">
                                <div class="stat-card text-center p-3">
                                    <div class="stat-value"><?= htmlspecialchars($stat['value'] ?? '') ?></div>
                                    <p class="stat-label mb-0"><?= htmlspecialchars($stat['label'] ?? '') ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="mission-card">
                    <h6 class="text-uppercase text-primary">Nuestra esencia</h6>
                    <h2 class="fw-bold mb-3"><?= htmlspecialchars($mission['title'] ?? 'Nuestra misión') ?></h2>
                    <p class="lead text-muted mb-4"><?= htmlspecialchars($mission['description'] ?? 'Acompañamos a cada viajero para que viva el mundo maya de forma auténtica.') ?></p>
                    <?php if (!empty($mission['points'])): ?>
                        <ul class="list-unstyled">
                            <?php foreach ($mission['points'] as $point): ?>
                                <li class="d-flex mb-3">
                                    <span class="icon-circle me-3"><i class="fas fa-check"></i></span>
                                    <div>
                                        <strong><?= htmlspecialchars($point['title'] ?? '') ?></strong>
                                        <p class="mb-0 text-muted"><?= htmlspecialchars($point['description'] ?? '') ?></p>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="story-card p-4 h-100">
                    <div class="ratio ratio-16x9 rounded overflow-hidden shadow-sm mb-4">
                        <img src="<?= Helpers::asset($story['image'] ?? 'images/about-story.jpg') ?>" class="w-100 h-100 object-fit-cover" alt="Historia de la agencia">
                    </div>
                    <h4 class="fw-bold mb-3"><?= htmlspecialchars($story['title'] ?? 'Nuestra historia') ?></h4>
                    <p class="text-muted mb-0"><?= htmlspecialchars($story['content'] ?? 'Nacimos en Petén con el sueño de mostrar el patrimonio del mundo maya al mundo.') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h6 class="text-uppercase text-primary">Nuestros valores</h6>
            <h2 class="fw-bold">Lo que nos mueve</h2>
            <p class="text-muted">Creemos en un turismo respetuoso, colaborativo y memorable.</p>
        </div>
        <div class="row g-4">
            <?php foreach ($values as $value): ?>
                <div class="col-md-4">
                    <div class="value-card h-100 p-4">
                        <div class="icon-circle mb-3"><i class="fas fa-star"></i></div>
                        <h5 class="fw-bold mb-2"><?= htmlspecialchars($value['title'] ?? '') ?></h5>
                        <p class="text-muted mb-0"><?= htmlspecialchars($value['description'] ?? '') ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <h6 class="text-uppercase text-primary">Nuestro equipo</h6>
                <h2 class="fw-bold mb-3">Personas que cuidan cada detalle</h2>
                <p class="text-muted">Colaboramos con guías locales, artesanos y comunidades para que cada viaje genere un impacto positivo.</p>
            </div>
            <div class="col-lg-7">
                <div class="row g-4">
                    <?php foreach ($team as $member): ?>
                        <div class="col-md-6">
                            <div class="team-card p-4 h-100">
                                <div class="avatar mb-3"><i class="fas fa-user"></i></div>
                                <h5 class="mb-1"><?= htmlspecialchars($member['name'] ?? '') ?></h5>
                                <small class="text-primary d-block mb-2"><?= htmlspecialchars($member['role'] ?? '') ?></small>
                                <p class="text-muted mb-0"><?= htmlspecialchars($member['bio'] ?? '') ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h2 class="fw-bold mb-3"><?= htmlspecialchars($cta['title'] ?? '¿Listo para planear tu próxima aventura?') ?></h2>
                <p class="lead mb-0"><?= htmlspecialchars($cta['subtitle'] ?? 'Cuéntanos qué tipo de experiencia buscas y diseñaremos un itinerario a tu medida.') ?></p>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <a href="<?= Config::getBaseUrl() . ($cta['button_link'] ?? '?route=contact') ?>" class="btn btn-light btn-lg">
                    <?= htmlspecialchars($cta['button_text'] ?? 'Agendar una llamada') ?>
                    <i class="fas fa-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../layouts/footer.php'; ?>

<style>
.about-hero {
    position: relative;
    min-height: 60vh;
    background-size: cover;
    background-position: center;
    color: #fff;
}
.about-hero .overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, rgba(4,33,73,0.9), rgba(15,110,188,0.75));
}
.about-highlights {
    background: rgba(255,255,255,0.15);
    border-radius: 20px;
    backdrop-filter: blur(6px);
}
.stat-card {
    border-radius: 16px;
    background: rgba(255,255,255,0.15);
}
.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
}
.mission-card {
    background: #fff;
    border-radius: 24px;
    padding: 2rem;
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}
.story-card img {
    object-fit: cover;
}
.icon-circle {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(13,110,253,0.15);
    color: #0d6efd;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}
.value-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.05);
}
.team-card {
    background: #fff;
    border-radius: 20px;
    border: 1px solid #eef1f6;
    box-shadow: 0 10px 30px rgba(0,0,0,0.04);
}
.team-card .avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: #0d6efd;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}
@media (max-width: 767px) {
    .about-hero {
        text-align: center;
    }
}
</style>
