<?php
use App\Core\Config;
use App\Core\Helpers;
include __DIR__ . '/../../layouts/admin_header.php'; ?>

<div class="container-fluid py-4">
    <?php
        $actionTitle = 'Editor de Contenido Web';
        $actionSubtitle = 'Gestiona el contenido de tu sitio web';
        $actionButtons = [
            ['label' => 'Nuevo Contenido', 'icon' => 'fas fa-plus', 'variant' => 'primary', 'href' => Config::getBaseUrl() . '?route=admin/content/editor'],
            ['label' => 'Galería', 'icon' => 'fas fa-images', 'variant' => 'outline-primary', 'href' => Config::getBaseUrl() . '?route=admin/content/media'],
            ['label' => 'Configuración', 'icon' => 'fas fa-cog', 'variant' => 'outline-secondary', 'href' => Config::getBaseUrl() . '?route=admin/content/settings'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Contenido
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['total_contenido'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Publicado
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['contenido_publicado'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Borradores
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['borradores'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-edit fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Secciones
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= $stats['total_secciones'] ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-layer-group fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Secciones del Sitio -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-sitemap me-2"></i>Secciones del Sitio Web
                    </h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="toggleView()">
                            <i class="fas fa-th-list" id="viewToggle"></i>
                        </button>
                        <button class="btn btn-outline-success" onclick="publishAll()" 
                                title="Publicar todo el contenido pendiente">
                            <i class="fas fa-rocket"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" id="sectionsTable">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Sección</th>
                                    <th class="text-center">Elementos</th>
                                    <th class="text-center">Publicados</th>
                                    <th class="text-center">Estado</th>
                                    <th>Última Modificación</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($sections)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">No hay contenido creado aún</p>
                                            <a href="<?= Config::getBaseUrl() ?>?route=admin/content/editor" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Crear Primer Contenido
                                            </a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($sections as $section): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="section-icon me-3">
                                                        <i class="<?= getSectionIcon($section['seccion']) ?> text-primary"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?= ucwords(str_replace('_', ' ', $section['seccion'])) ?></h6>
                                                        <small class="text-muted"><?= $section['seccion'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?= $section['total_elementos'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-success"><?= $section['elementos_publicados'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php 
                                                $pendingChanges = $section['total_elementos'] - $section['elementos_publicados'];
                                                if ($pendingChanges > 0): ?>
                                                    <span class="badge bg-warning">
                                                        <?= $pendingChanges ?> pendiente<?= $pendingChanges > 1 ? 's' : '' ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check"></i> Actualizado
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($section['ultima_modificacion']): ?>
                                                    <small><?= Helpers::timeAgo($section['ultima_modificacion']) ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">Nunca</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= Config::getBaseUrl() ?>?route=admin/content/edit-section/<?= $section['seccion'] ?>" 
                                                       class="btn btn-outline-primary" title="Editar sección">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?= Config::getBaseUrl() ?>?route=admin/content/preview/<?= $section['seccion'] ?>" 
                                                       class="btn btn-outline-info" title="Vista previa" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if ($pendingChanges > 0): ?>
                                                        <button class="btn btn-outline-success" 
                                                                onclick="publishSection('<?= $section['seccion'] ?>')" 
                                                                title="Publicar cambios">
                                                            <i class="fas fa-rocket"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Card View (Hidden by default) -->
                    <div id="sectionsCards" class="d-none p-3">
                        <div class="row">
                            <?php foreach ($sections as $section): ?>
                                <div class="col-xl-4 col-lg-6 mb-4">
                                    <div class="card h-100 section-card">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start justify-content-between mb-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="section-icon-large me-3">
                                                        <i class="<?= getSectionIcon($section['seccion']) ?> text-primary fa-2x"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="card-title mb-0">
                                                            <?= ucwords(str_replace('_', ' ', $section['seccion'])) ?>
                                                        </h6>
                                                        <small class="text-muted"><?= $section['seccion'] ?></small>
                                                    </div>
                                                </div>
                                                <?php 
                                                $pendingChanges = $section['total_elementos'] - $section['elementos_publicados'];
                                                if ($pendingChanges > 0): ?>
                                                    <span class="badge bg-warning">
                                                        <?= $pendingChanges ?> pendiente<?= $pendingChanges > 1 ? 's' : '' ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="row text-center mb-3">
                                                <div class="col-4">
                                                    <div class="border-end">
                                                        <div class="h5 mb-0 text-primary"><?= $section['total_elementos'] ?></div>
                                                        <small class="text-muted">Total</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="border-end">
                                                        <div class="h5 mb-0 text-success"><?= $section['elementos_publicados'] ?></div>
                                                        <small class="text-muted">Publicados</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="h5 mb-0 text-info"><?= $section['elementos_activos'] ?></div>
                                                    <small class="text-muted">Activos</small>
                                                </div>
                                            </div>
                                            
                                            <?php if ($section['ultima_modificacion']): ?>
                                                <p class="card-text">
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock me-1"></i>
                                                        Modificado <?= Helpers::timeAgo($section['ultima_modificacion']) ?>
                                                    </small>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-footer bg-transparent">
                                            <div class="d-flex justify-content-between">
                                                <a href="<?= Config::getBaseUrl() ?>?route=admin/content/edit-section/<?= $section['seccion'] ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-edit me-1"></i>Editar
                                                </a>
                                                <a href="<?= Config::getBaseUrl() ?>?route=admin/content/preview/<?= $section['seccion'] ?>" 
                                                   class="btn btn-outline-info btn-sm" target="_blank">
                                                    <i class="fas fa-eye me-1"></i>Vista Previa
                                                </a>
                                                <?php if ($pendingChanges > 0): ?>
                                                    <button class="btn btn-success btn-sm" 
                                                            onclick="publishSection('<?= $section['seccion'] ?>')">
                                                        <i class="fas fa-rocket me-1"></i>Publicar
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actividad Reciente -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Actividad Reciente
                    </h5>
                    <a href="<?= Config::getBaseUrl() ?>?route=admin/logs" class="btn btn-sm btn-outline-primary">
                        Ver Todo
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($recentChanges)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No hay actividad reciente</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($recentChanges as $change): ?>
                                <div class="timeline-item">
                                    <div class="timeline-marker">
                                        <i class="<?= getChangeIcon($change['tipo']) ?> text-primary"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="timeline-title"><?= htmlspecialchars($change['titulo']) ?></h6>
                                        <p class="timeline-text">
                                            <small class="text-muted">
                                                <strong><?= $change['seccion'] ?></strong> • 
                                                por <?= htmlspecialchars($change['modificado_por_nombre'] ?? 'Sistema') ?>
                                            </small>
                                        </p>
                                        <small class="timeline-time">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= Helpers::timeAgo($change['updated_at']) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card shadow mt-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Acciones Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/content/editor" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Crear Nueva Página
                        </a>
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/content/media" class="btn btn-outline-info">
                            <i class="fas fa-upload me-2"></i>Subir Imágenes
                        </a>
                        <a href="<?= Config::getBaseUrl() ?>?route=admin/content/settings" class="btn btn-outline-secondary">
                            <i class="fas fa-cog me-2"></i>Configurar Sitio
                        </a>
                        <button class="btn btn-outline-success" onclick="backupContent()">
                            <i class="fas fa-download me-2"></i>Hacer Backup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
function getSectionIcon($section) {
    $icons = [
        'home_hero' => 'fas fa-home',
        'about_us' => 'fas fa-info-circle',
        'services' => 'fas fa-concierge-bell',
        'contact' => 'fas fa-envelope',
        'destinations' => 'fas fa-map-marked-alt',
        'blog' => 'fas fa-blog',
        'footer' => 'fas fa-grip-horizontal',
        'legal' => 'fas fa-gavel'
    ];
    return $icons[$section] ?? 'fas fa-file-alt';
}

function getChangeIcon($type) {
    $icons = [
        'texto' => 'fas fa-edit',
        'imagen' => 'fas fa-image',
        'configuracion' => 'fas fa-cog'
    ];
    return $icons[$type] ?? 'fas fa-circle';
}
?>

<script>
function toggleView() {
    const table = document.getElementById('sectionsTable');
    const cards = document.getElementById('sectionsCards');
    const toggle = document.getElementById('viewToggle');
    
    if (table.classList.contains('d-none')) {
        table.classList.remove('d-none');
        cards.classList.add('d-none');
        toggle.className = 'fas fa-th-list';
    } else {
        table.classList.add('d-none');
        cards.classList.remove('d-none');
        toggle.className = 'fas fa-table';
    }
}

function publishSection(section) {
    if (!confirm('¿Estás seguro de publicar todos los cambios de esta sección?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('section', section);
    formData.append('csrf_token', '<?= Helpers::getCSRFToken() ?>');
    
    fetch('?route=admin/content/publish', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            location.reload();
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        showNotification('error', 'Error al publicar contenido');
        console.error('Error:', error);
    });
}

function publishAll() {
    if (!confirm('¿Estás seguro de publicar TODO el contenido pendiente?')) {
        return;
    }
    
    showNotification('info', 'Publicando contenido...');
    
    // Obtener todas las secciones con cambios pendientes
    const sections = <?= json_encode(array_column($sections, 'seccion')) ?>;
    
    Promise.all(sections.map(section => {
        const formData = new FormData();
        formData.append('section', section);
        formData.append('csrf_token', '<?= Helpers::getCSRFToken() ?>');
        
        return fetch('?route=admin/content/publish', {
            method: 'POST',
            body: formData
        }).then(response => response.json());
    }))
    .then(results => {
        const successful = results.filter(r => r.success).length;
        const failed = results.length - successful;
        
        if (failed === 0) {
            showNotification('success', `Se publicaron ${successful} secciones exitosamente`);
        } else {
            showNotification('warning', `Se publicaron ${successful} secciones. ${failed} fallaron.`);
        }
        
        location.reload();
    })
    .catch(error => {
        showNotification('error', 'Error al publicar contenido');
        console.error('Error:', error);
    });
}

function backupContent() {
    showNotification('info', 'Generando backup...');
    
    fetch('?route=admin/content/backup', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            csrf_token: '<?= Helpers::getCSRFToken() ?>'
        })
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `content-backup-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        
        showNotification('success', 'Backup descargado exitosamente');
    })
    .catch(error => {
        showNotification('error', 'Error al generar backup');
        console.error('Error:', error);
    });
}
</script>

<!-- Custom CSS moved to public/assets/css/admin.css -->

<?php include __DIR__ . '/../../layouts/admin_footer.php'; ?>
