<?php
use App\Core\Config;
use App\Core\Helpers;
use App\Core\Database;
$currentRoute = $_GET['route'] ?? 'admin/dashboard';
if ($currentRoute === 'admin') {
    $currentRoute = 'admin/dashboard';
}
$navIsActive = function (array $targets) use ($currentRoute) {
    foreach ($targets as $target) {
        if ($currentRoute === $target || str_starts_with($currentRoute, $target . '/')) {
            return 'active';
        }
    }
    return '';
};
$baseUrl = Config::getBaseUrl();

// Get new messages count for badge
$newMessagesCount = 0;
try {
    $db = Database::getInstance();
    $result = $db->fetchOne("SELECT COUNT(*) as count FROM mensajes WHERE estado = 'nuevo'");
    $newMessagesCount = $result['count'] ?? 0;
} catch (Exception $e) {
    // Silently fail if table doesn't exist
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' · ' : '' ?>Panel Administrativo - Travel Mayan World</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Inter Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS (theme switch) -->
    <?php if (Config::ADMIN_THEME === 'new'): ?>
        <link href="<?= Helpers::asset('css/admin.css') ?>" rel="stylesheet">
    <?php else: ?>
        <link href="<?= Helpers::asset('css/main.css') ?>" rel="stylesheet">
    <?php endif; ?>
    <!-- Admin Premium CSS -->
    <link href="<?= Helpers::asset('css/admin-premium.css') ?>" rel="stylesheet">
</head>
<body class="admin-body">
    <div class="container-fluid p-0">
        <div class="row g-0">
            <!-- Sidebar -->
            <div class="col-md-2 admin-sidebar" id="adminSidebar">
                <div class="p-3">
                    <div class="text-center mb-4">
                        <h5 class="text-white mt-2 mb-0">
                            <i class="fas fa-plane-departure me-2"></i>Travel Mayan World
                        </h5>
                        <small class="text-white-50">Panel Admin</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <?php
                        // Menú del sidebar - Hardcodeado para control total
                        // El sistema dinámico NavigationHelper está comentado para evitar duplicados
                        ?>
                        <a href="<?= $baseUrl ?>?route=admin/dashboard" class="nav-link <?= $navIsActive(['admin/dashboard']) ?>">
                            <i class="fas fa-tachometer-alt"></i>Dashboard
                        </a>

                        <hr class="my-3 hr-translucent">

                        <!-- Gestión del Negocio -->
                        <div class="nav-section-header">
                            <small class="text-white-50 px-3">GESTIÓN DEL NEGOCIO</small>
                        </div>
                        <a href="<?= $baseUrl ?>?route=admin/bookings" class="nav-link <?= $navIsActive(['admin/bookings']) ?>">
                            <i class="fas fa-calendar-check"></i>Reservas
                        </a>
                        <a href="<?= $baseUrl ?>?route=admin/messages" class="nav-link <?= $navIsActive(['admin/messages']) ?>">
                            <i class="fas fa-envelope"></i>Mensajes
                            <?php if ($newMessagesCount > 0): ?>
                                <span class="badge bg-warning text-dark ms-auto"><?= $newMessagesCount ?></span>
                            <?php endif; ?>
                        </a>
                        <?php $toursMenuOpen = str_starts_with($currentRoute, 'admin/tours') || str_starts_with($currentRoute, 'admin/meeting-points') ? 'show' : ''; ?>
                        <a class="nav-link d-flex align-items-center <?= $navIsActive(['admin/tours', 'admin/meeting-points']) ?>"
                           data-bs-toggle="collapse" href="#submenuTours" role="button"
                           aria-expanded="<?= $toursMenuOpen ? 'true' : 'false' ?>">
                            <i class="fas fa-map-marked-alt"></i>Tours
                            <i class="fas fa-chevron-down ms-auto small"></i>
                        </a>
                        <div class="collapse <?= $toursMenuOpen ?>" id="submenuTours">
                            <nav class="nav flex-column ms-3 border-start ps-2">
                                <a href="<?= $baseUrl ?>?route=admin/tours" class="nav-link py-1 <?= $currentRoute === 'admin/tours' || str_starts_with($currentRoute, 'admin/tours/') ? 'active' : '' ?>">
                                    <i class="fas fa-list fa-sm"></i>Lista de Tours
                                </a>
                                <a href="<?= $baseUrl ?>?route=admin/meeting-points" class="nav-link py-1 <?= str_starts_with($currentRoute, 'admin/meeting-points') ? 'active' : '' ?>">
                                    <i class="fas fa-map-marker-alt fa-sm"></i>Puntos de Encuentro
                                </a>
                            </nav>
                        </div>
                        <a href="<?= $baseUrl ?>?route=admin/categories" class="nav-link <?= $navIsActive(['admin/categories']) ?>">
                            <i class="fas fa-tags"></i>Categorías
                        </a>
                        <?php $staffMenuOpen = str_starts_with($currentRoute, 'admin/staff') ? 'show' : ''; ?>
                        <a class="nav-link d-flex align-items-center <?= $navIsActive(['admin/staff']) ?>"
                           data-bs-toggle="collapse" href="#submenuPersonal" role="button"
                           aria-expanded="<?= $staffMenuOpen ? 'true' : 'false' ?>">
                            <i class="fas fa-users"></i>Personal
                            <i class="fas fa-chevron-down ms-auto small"></i>
                        </a>
                        <div class="collapse <?= $staffMenuOpen ?>" id="submenuPersonal">
                            <nav class="nav flex-column ms-3 border-start ps-2">
                                <a href="<?= $baseUrl ?>?route=admin/staff" class="nav-link py-1 <?= $currentRoute === 'admin/staff' || str_starts_with($currentRoute, 'admin/staff/edit') || str_starts_with($currentRoute, 'admin/staff/add') ? 'active' : '' ?>">
                                    <i class="fas fa-list fa-sm"></i>Lista de Personal
                                </a>
                                <a href="<?= $baseUrl ?>?route=admin/staff/types" class="nav-link py-1 <?= str_starts_with($currentRoute, 'admin/staff/types') ? 'active' : '' ?>">
                                    <i class="fas fa-id-badge fa-sm"></i>Tipos de empleados
                                </a>
                                <a href="<?= $baseUrl ?>?route=admin/staff/languages" class="nav-link py-1 <?= str_starts_with($currentRoute, 'admin/staff/languages') ? 'active' : '' ?>">
                                    <i class="fas fa-language fa-sm"></i>Idiomas
                                </a>
                            </nav>
                        </div>
                        <a href="<?= $baseUrl ?>?route=admin/routes" class="nav-link <?= $navIsActive(['admin/routes']) ?>">
                            <i class="fas fa-route"></i>Rutas y Traslados
                        </a>
                        <a href="<?= $baseUrl ?>?route=admin/transport" class="nav-link <?= $navIsActive(['admin/transport']) ?>">
                            <i class="fas fa-bus"></i>Transportes
                        </a>
                        <a href="<?= $baseUrl ?>?route=admin/reviews" class="nav-link <?= $navIsActive(['admin/reviews']) ?>">
                            <i class="fas fa-star"></i>Reseñas
                        </a>
                        <a href="<?= $baseUrl ?>?route=admin/faqs" class="nav-link <?= $navIsActive(['admin/faqs']) ?>">
                            <i class="fas fa-question-circle"></i>FAQs
                        </a>

                        <hr class="my-3 hr-translucent">

                        <!-- Editar Web -->
                        <div class="nav-section-header">
                            <small class="text-white-50 px-3">EDITAR WEB</small>
                        </div>
                        <a href="<?= $baseUrl ?>?route=admin/homepage-editor" class="nav-link <?= $navIsActive(['admin/homepage-editor']) ?>">
                            <i class="fas fa-home"></i>Editor de Portada
                        </a>
                        <a href="<?= $baseUrl ?>?route=admin/content-blocks" class="nav-link <?= $navIsActive(['admin/content-blocks']) ?>">
                            <i class="fas fa-cube"></i>Bloques de Contenido
                        </a>
                        <a href="<?= $baseUrl ?>?route=admin/pages" class="nav-link <?= $navIsActive(['admin/pages']) ?>">
                            <i class="fas fa-file-alt"></i>Páginas Estáticas
                        </a>
                        <a href="<?= $baseUrl ?>?route=admin/blog" class="nav-link <?= $navIsActive(['admin/blog']) ?>">
                            <i class="fas fa-blog"></i>Blog
                        </a>
                        <a href="<?= $baseUrl ?>?route=admin/blog/categorias" class="nav-link <?= $navIsActive(['admin/blog/categorias']) ?>">
                            <i class="fas fa-folder"></i>Categorías Blog
                        </a>
                        <a href="<?= $baseUrl ?>?route=admin/company-config" class="nav-link <?= $navIsActive(['admin/company-config']) ?>">
                            <i class="fas fa-building"></i>Configuración Empresa
                        </a>

                        <hr class="my-3 hr-translucent">

                        <!-- Administración -->
                        <div class="nav-section-header">
                            <small class="text-white-50 px-3">ADMINISTRACIÓN</small>
                        </div>
                        <a href="<?= $baseUrl ?>?route=admin/audit" class="nav-link <?= $navIsActive(['admin/audit']) ?>">
                            <i class="fas fa-history"></i>Registro de Actividad
                        </a>
                        <a href="<?= $baseUrl ?>?route=admin/settings" class="nav-link <?= $navIsActive(['admin/settings']) ?>">
                            <i class="fas fa-cog"></i>Configuración
                        </a>
                        <a href="<?= $baseUrl ?>?route=admin/settings/payments" class="nav-link <?= $navIsActive(['admin/settings/payments']) ?>">
                            <i class="fas fa-credit-card"></i>Pasarelas de Pago
                        </a>

                        <hr class="my-3 hr-translucent">
                        <a href="<?= $baseUrl ?>?route=logout" class="nav-link text-danger">
                            <i class="fas fa-sign-out-alt"></i>Cerrar Sesión
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 admin-content">
                <!-- Top Navbar -->
                <nav class="navbar navbar-expand-lg navbar-light navbar-admin">
                    <div class="container-fluid">
                        <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#mobileMenu">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        
                        <div class="d-flex align-items-center ms-auto">
                            <!-- Density Toggle -->
                            <button class="btn btn-outline-secondary me-2" type="button" id="densityToggle" title="Densidad compacta" onclick="window.AdminUI && AdminUI.toggleDensity && AdminUI.toggleDensity();">
                                <i class="fas fa-compress-arrows-alt"></i>
                            </button>

                            <!-- Notification Bell -->
                            <div class="dropdown me-2">
                                <button class="btn btn-outline-secondary position-relative"
                                        type="button"
                                        id="notificationBell"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                    <i class="fas fa-bell"></i>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                          id="notificationCount"
                                          style="display: none;">
                                        0
                                    </span>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end notification-dropdown"
                                     style="width: 380px; max-height: 500px; overflow-y: auto;">
                                    <!-- Header -->
                                    <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                        <h6 class="mb-0">
                                            <i class="fas fa-bell me-2"></i>
                                            Notificaciones
                                        </h6>
                                        <div>
                                            <button class="btn btn-sm btn-link text-muted p-0 me-2"
                                                    id="markAllReadBtn"
                                                    title="Marcar todo como leído">
                                                <i class="fas fa-check-double"></i>
                                            </button>
                                            <a href="<?= $baseUrl ?>?route=admin/notifications"
                                               class="btn btn-sm btn-link text-primary p-0">
                                                Ver todo
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Lista de notificaciones -->
                                    <div id="notificationsList">
                                        <div class="text-center text-muted py-5">
                                            <div class="spinner-border spinner-border-sm" role="status">
                                                <span class="visually-hidden">Cargando...</span>
                                            </div>
                                            <p class="small mt-2 mb-0">Cargando notificaciones...</p>
                                        </div>
                                    </div>

                                    <!-- Footer -->
                                    <div class="border-top px-3 py-2 text-center">
                                        <small class="text-muted">
                                            <i class="fas fa-sync-alt me-1"></i>
                                            Auto-actualización cada 30s
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="dropdown ms-3">
                                <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle me-2"></i>Admin
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= $baseUrl ?>?route=admin/profile">
                                        <i class="fas fa-user me-2"></i>Mi Perfil
                                    </a></li>
                                    <li><a class="dropdown-item" href="<?= $baseUrl ?>?route=admin/settings">
                                        <i class="fas fa-cog me-2"></i>Configuración
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="<?= $baseUrl ?>?route=logout">
                                        <i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
                <main class="admin-main">
