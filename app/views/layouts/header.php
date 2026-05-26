<?php
use App\Core\Auth;
use App\Core\Config;
use App\Core\Helpers;
use App\Helpers\CompanyConfigHelper;
use App\Helpers\NavigationHelper;

$auth = Auth::getInstance();
$user = $auth->getCurrentUser();
$title = $title ?? 'Travel Mayan World';
$metaDescription = $metaDescription ?? 'Sistema completo para agencia de viajes con gestión de tours, reservas y administración';
$metaImage = $metaImage ?? Helpers::asset('images/hero-travel.jpg');
$extraStyles = $extraStyles ?? [];
$currentRoute = $_GET['route'] ?? '';
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?></title>
    
    <!-- SEO Básico -->
    <meta name="robots" content="index, follow">
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#0d6efd">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="TWM">
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="keywords" content="viajes, turismo, reservas, destinos, agencia">
    
    <!-- Open Graph / Twitter -->
    <?php $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? ''); ?>
    <link rel="canonical" href="<?= htmlspecialchars($currentUrl) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($currentUrl) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($metaImage) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($metaImage) ?>">
    
    <!-- PWA Manifest (dinámico - auto-detecta local/producción) -->
    <link rel="manifest" href="<?= Config::getBaseUrl() ?>manifest.php">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="<?= Helpers::asset('images/icons/icon-180x180.png') ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= Helpers::asset('images/icons/icon-32x32.png') ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= Helpers::asset('images/icons/icon-16x16.png') ?>">
    
    <!-- Performance Hints -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= Helpers::asset('css/main.css') ?>" rel="stylesheet">
    <?php foreach ($extraStyles as $style): ?>
        <?php
        $href = '';
        $attrs = '';
        if (is_array($style)) {
            $href = $style['href'] ?? '';
            $attrs = $style['attrs'] ?? '';
            $isAsset = $style['asset'] ?? !preg_match('#^https?://#', $href);
        } else {
            $href = $style;
            $isAsset = !preg_match('#^https?://#', $href);
        }
        $resolvedHref = $isAsset ? Helpers::asset(ltrim($href, '/')) : $href;
        ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($resolvedHref) ?>" <?= $attrs ?> />
    <?php endforeach; ?>

    <?php if (!empty($preloadImages) && is_array($preloadImages)): ?>
        <?php foreach ($preloadImages as $img): ?>
            <link rel="preload" as="image" href="<?= htmlspecialchars($img) ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Schema.org JSON-LD -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "name": "Travel Mayan World",
      "url": "<?= rtrim(Config::getBaseUrl(), '/') ?>/",
      "potentialAction": {
        "@type": "SearchAction",
        "target": "<?= Config::getBaseUrl() ?>?route=tours&search={search_term_string}",
        "query-input": "required name=search_term_string"
      }
    }
    </script>
</head>
<body>
    <a class="visually-hidden-focusable position-absolute top-0 start-0 m-2 p-2 bg-light rounded" href="#main-content">Saltar al contenido</a>
    <?= $beforeNavbar ?? '' ?>
    <?php
    // Cargar configuración para navbar
    $companyName = CompanyConfigHelper::get('company_name', 'Travel Mayan World');
    $logoUrl = CompanyConfigHelper::get('logo_url');
    $isHomepage = $isHomepage ?? false;
    $navbarClasses = $isHomepage ? 'navbar navbar-expand-lg navbar-dark homepage-navbar-overlay' : 'navbar navbar-expand-lg navbar-dark bg-primary sticky-top';
    ?>
    <!-- Navbar -->
    <nav class="<?= $navbarClasses ?>" role="navigation" aria-label="Navegación principal">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= Config::getBaseUrl() ?>">
                <?php if (!empty($logoUrl) && file_exists('../public/' . $logoUrl)): ?>
                    <img src="<?= Config::getBaseUrl() . $logoUrl ?>" alt="<?= htmlspecialchars($companyName) ?>" height="30" class="d-inline-block align-text-top me-2">
                <?php else: ?>
                    <i class="fas fa-plane me-2"></i>
                <?php endif; ?>
                <?= htmlspecialchars($companyName) ?>
            </a>
            
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mainMobileMenu" aria-controls="mainMobileMenu" aria-label="Abrir menú">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="offcanvas offcanvas-end" tabindex="-1" id="mainMobileMenu" aria-labelledby="mainMobileMenuLabel">
                <div class="offcanvas-header">
                    <h5 class="offcanvas-title" id="mainMobileMenuLabel">
                        <?php if (!empty($logoUrl) && file_exists('../public/' . $logoUrl)): ?>
                            <img src="<?= Config::getBaseUrl() . $logoUrl ?>" alt="<?= htmlspecialchars($companyName) ?>" height="24" class="me-2">
                        <?php else: ?>
                            <i class="fas fa-plane me-2"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($companyName) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
                </div>
                <div class="offcanvas-body">
                    <?php
                    // Renderizar menú principal dinámico desde BD
                    echo NavigationHelper::renderMenu('main', [
                        'class' => 'navbar-nav me-auto',
                        'item_class' => 'nav-item',
                        'link_class' => 'nav-link',
                        'dropdown_class' => 'dropdown-menu',
                        'show_icons' => true,
                        'format' => 'bootstrap5'
                    ]);
                    ?>

                    <div class="offcanvas-divider"></div>

                    <ul class="navbar-nav">
                        <?php if ($auth->isLoggedIn()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($user['nombre']) ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php if ($auth->isAdmin()): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?= Config::getBaseUrl() ?>?route=admin">
                                                <i class="fas fa-cogs me-2"></i>Admin Panel
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item" href="<?= Config::getBaseUrl() ?>?route=profile">
                                                <i class="fas fa-user-cog me-2"></i>Mi Perfil
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li>
                                            <a class="dropdown-item" href="<?= Config::getBaseUrl() ?>?route=client/dashboard">
                                                <i class="fas fa-tachometer-alt me-2"></i>Mi Panel
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= Config::getBaseUrl() ?>?route=client/bookings">
                                                <i class="fas fa-calendar-check me-2"></i>Mis Reservas
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="<?= Config::getBaseUrl() ?>?route=client/profile">
                                                <i class="fas fa-user-cog me-2"></i>Mi Perfil
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item text-danger" href="<?= Config::getBaseUrl() ?>?route=logout">
                                            <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link nav-link-login" href="<?= Config::getBaseUrl() ?>?route=login">
                                    <i class="fas fa-user-circle me-1"></i>Panel de Clientes
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (Helpers::hasFlashMessages()): ?>
        <div class="container mt-3">
            <?php foreach (Helpers::getFlashMessages() as $flash): ?>
                <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
                    <?= htmlspecialchars($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main id="main-content">
