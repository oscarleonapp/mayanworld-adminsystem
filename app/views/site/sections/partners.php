<?php
/**
 * Partners Section
 * Muestra logos de partners, certificaciones o redes sociales
 */

use App\Core\Config;
use App\Core\Helpers;

$config = json_decode($section['section_config'] ?? '{}', true);
$sectionTitle = $config['title'] ?? 'Nuestros Partners';
$partners = $config['partners'] ?? [
    ['name' => 'TripAdvisor', 'logo' => Helpers::asset('images/partners/tripadvisor.svg'), 'link' => '#'],
    ['name' => 'Booking.com', 'logo' => Helpers::asset('images/partners/booking.svg'), 'link' => '#'],
    ['name' => 'Google Reviews', 'logo' => Helpers::asset('images/partners/google.svg'), 'link' => '#'],
    ['name' => 'Expedia', 'logo' => Helpers::asset('images/partners/expedia.svg'), 'link' => '#'],
];

function normalizePartnerLogoUrl(string $logo = ''): string
{
    $logo = trim($logo);
    if ($logo === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $logo) || str_starts_with($logo, 'data:')) {
        return $logo;
    }

    $baseUrl = rtrim(Config::getBaseUrl(), '/');
    $parts = parse_url($baseUrl);
    $baseHost = '';
    $basePath = '';
    if (!empty($parts['scheme']) && !empty($parts['host'])) {
        $baseHost = $parts['scheme'] . '://' . $parts['host'];
        if (!empty($parts['port'])) {
            $baseHost .= ':' . $parts['port'];
        }
        $basePath = $parts['path'] ?? '';
    }

    if ($basePath && str_starts_with($logo, $basePath . '/')) {
        return $baseHost . $logo;
    }

    $clean = ltrim($logo, '/');

    if (str_starts_with($clean, 'assets/')) {
        return $baseUrl . '/' . $clean;
    }

    if (str_starts_with($clean, 'public/assets/')) {
        if ($basePath && str_ends_with($basePath, '/public')) {
            return $baseUrl . '/' . substr($clean, strlen('public/'));
        }
        return $baseHost . '/' . $clean;
    }

    if (str_starts_with($clean, 'images/')) {
        return Helpers::asset($clean);
    }

    return $baseUrl . '/' . $clean;
}
?>

<!-- Partners Section -->
<section class="partners-section py-5 bg-light">
    <div class="container">
        <?php if (!empty($sectionTitle)): ?>
            <div class="text-center mb-5">
                <h2 class="h3 fw-bold"><?= htmlspecialchars($sectionTitle) ?></h2>
            </div>
        <?php endif; ?>

        <div class="row align-items-center g-4">
            <?php foreach ($partners as $partner): ?>
                <div class="col-6 col-md-3">
                    <div class="partner-card text-center p-4">
                        <?php $logoUrl = !empty($partner['logo']) ? normalizePartnerLogoUrl($partner['logo']) : ''; ?>
                        <?php if (!empty($partner['link']) && $partner['link'] !== '#'): ?>
                            <a href="<?= htmlspecialchars($partner['link']) ?>"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="d-block">
                                <?php if (!empty($logoUrl)): ?>
                                    <img src="<?= htmlspecialchars($logoUrl) ?>"
                                         alt="<?= htmlspecialchars($partner['name']) ?>"
                                         class="partner-logo img-fluid"
                                         loading="lazy"
                                         decoding="async">
                                <?php else: ?>
                                    <div class="partner-placeholder">
                                        <i class="fas fa-handshake fa-3x text-muted"></i>
                                        <p class="mt-2 mb-0 small text-muted"><?= htmlspecialchars($partner['name']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <?php if (!empty($logoUrl)): ?>
                                <img src="<?= htmlspecialchars($logoUrl) ?>"
                                     alt="<?= htmlspecialchars($partner['name']) ?>"
                                     class="partner-logo img-fluid"
                                     loading="lazy"
                                     decoding="async">
                            <?php else: ?>
                                <div class="partner-placeholder">
                                    <i class="fas fa-handshake fa-3x text-muted"></i>
                                    <p class="mt-2 mb-0 small text-muted"><?= htmlspecialchars($partner['name']) ?></p>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<style>
.partners-section {
    overflow: hidden;
}

.partner-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border-radius: 10px;
    background: white;
    min-height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.partner-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.partner-logo {
    max-height: 80px;
    max-width: 100%;
    object-fit: contain;
    filter: grayscale(100%);
    opacity: 0.7;
    transition: all 0.3s ease;
}

.partner-card:hover .partner-logo {
    filter: grayscale(0%);
    opacity: 1;
}

.partner-placeholder {
    padding: 20px;
}

@media (max-width: 768px) {
    .partner-logo {
        max-height: 60px;
    }
}
</style>
