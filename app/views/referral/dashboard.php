<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Programa de Referidos - Travel Mayan World</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Chart.js para gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .referral-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            border: none;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .level-progress {
            background: #f8f9fa;
            border-radius: 50px;
            height: 12px;
            overflow: hidden;
            position: relative;
        }
        
        .level-progress-bar {
            height: 100%;
            border-radius: 50px;
            background: linear-gradient(90deg, #28a745, #20c997);
            transition: width 1s ease;
        }
        
        .badge-item {
            text-align: center;
            padding: 1rem;
            border-radius: 10px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .badge-item:hover {
            background: #e9ecef;
            transform: scale(1.05);
        }
        
        .badge-earned {
            background: linear-gradient(135deg, #ffd700, #ffed4a);
            color: #333;
        }
        
        .referral-code-card {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .referral-code-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            transform: rotate(45deg);
            animation: shine 3s infinite;
        }
        
        @keyframes shine {
            0%, 100% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            50% { transform: translateX(100%) translateY(100%) rotate(45deg); }
        }
        
        .share-button {
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            margin: 0.25rem;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .share-facebook { background: #3b5998; color: white; }
        .share-whatsapp { background: #25d366; color: white; }
        .share-twitter { background: #1da1f2; color: white; }
        .share-email { background: #6c757d; color: white; }
        
        .activity-item {
            padding: 1rem;
            border-left: 3px solid #28a745;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
        }
        
        .activity-date {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        .challenge-card {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .challenge-card:hover {
            border-color: #28a745;
            background: #f8fff9;
        }
        
        .challenge-active {
            border-color: #ffc107;
            background: #fffef7;
        }
        
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }
        
        .notification-toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        }
    </style>
</head>
<body class="bg-light">

<?php 
use App\Core\Config;
if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<?php if (isset($success_message)): ?>
<div class="alert alert-success alert-dismissible fade show m-3" role="alert">
    <strong>¡Excelente!</strong> <?= htmlspecialchars($success_message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<!-- Hero Section -->
<div class="referral-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4">
                    <i class="fas fa-users me-3"></i>
                    Mi Programa de Referidos
                </h1>
                <p class="lead mb-4">
                    Comparte la magia de los tours mayas y gana increíbles recompensas por cada amigo que se una a nuestras aventuras.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-light text-dark fs-6 py-2 px-3">
                        <i class="fas fa-star me-2"></i>Nivel <?= $dashboard['user_level']['level'] ?? 1 ?>
                    </span>
                    <span class="badge bg-warning text-dark fs-6 py-2 px-3">
                        <i class="fas fa-coins me-2"></i><?= number_format($dashboard['total_points'] ?? 0) ?> puntos
                    </span>
                    <span class="badge bg-success fs-6 py-2 px-3">
                        <i class="fas fa-medal me-2"></i><?= $dashboard['badges_count'] ?? 0 ?> insignias
                    </span>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="referral-code-card">
                    <h5 class="mb-3">Tu Código de Referido</h5>
                    <div class="display-6 fw-bold mb-3" id="referralCode">
                        <?= $dashboard['referral_code'] ?? 'LOADING' ?>
                    </div>
                    <button class="btn btn-light" onclick="copyReferralCode()" id="copyBtn">
                        <i class="fas fa-copy me-2"></i>Copiar Código
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Estadísticas Principales -->
    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6">
            <div class="stats-card text-center">
                <div class="stats-number text-primary">
                    <?= number_format($dashboard['total_referrals'] ?? 0) ?>
                </div>
                <div class="stats-label">Referidos Totales</div>
                <small class="text-muted">
                    <i class="fas fa-arrow-up text-success"></i>
                    +<?= $dashboard['referrals_this_month'] ?? 0 ?> este mes
                </small>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="stats-card text-center">
                <div class="stats-number text-success">
                    $<?= number_format($dashboard['total_earnings'] ?? 0, 2) ?>
                </div>
                <div class="stats-label">Ganancias Totales</div>
                <small class="text-muted">
                    <i class="fas fa-dollar-sign text-success"></i>
                    $<?= number_format($dashboard['pending_earnings'] ?? 0, 2) ?> pendiente
                </small>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="stats-card text-center">
                <div class="stats-number text-warning">
                    <?= $dashboard['current_streak'] ?? 0 ?>
                </div>
                <div class="stats-label">Racha Actual</div>
                <small class="text-muted">
                    <i class="fas fa-fire text-orange"></i>
                    días consecutivos
                </small>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6">
            <div class="stats-card text-center">
                <div class="stats-number text-info">
                    <?= number_format($dashboard['conversion_rate'] ?? 0, 1) ?>%
                </div>
                <div class="stats-label">Tasa de Conversión</div>
                <small class="text-muted">
                    <i class="fas fa-chart-line text-info"></i>
                    de tus referidos
                </small>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Panel Principal -->
        <div class="col-lg-8">
            <!-- Progreso de Nivel -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-trophy text-warning me-2"></i>
                            Progreso de Nivel
                        </h5>
                        <span class="badge bg-primary">
                            Nivel <?= $dashboard['user_level']['level'] ?? 1 ?>
                        </span>
                    </div>
                    
                    <?php if (isset($next_level)): ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span><?= $dashboard['user_level']['name'] ?? 'Principiante' ?></span>
                            <span><?= $next_level['name'] ?? 'Siguiente Nivel' ?></span>
                        </div>
                        <div class="level-progress">
                            <div class="level-progress-bar" 
                                 style="width: <?= ($dashboard['total_points'] / $next_level['required_points']) * 100 ?>%">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <small class="text-muted">
                                <?= number_format($dashboard['total_points'] ?? 0) ?> puntos
                            </small>
                            <small class="text-muted">
                                <?= number_format($next_level['required_points'] - $dashboard['total_points']) ?> puntos para subir
                            </small>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row text-center">
                        <div class="col">
                            <strong><?= $dashboard['user_level']['commission_rate'] ?? 10 ?>%</strong><br>
                            <small class="text-muted">Comisión</small>
                        </div>
                        <div class="col">
                            <strong><?= $dashboard['user_level']['bonus_multiplier'] ?? 1 ?>x</strong><br>
                            <small class="text-muted">Multiplicador</small>
                        </div>
                        <div class="col">
                            <strong><?= $dashboard['user_level']['max_monthly_earnings'] ?? 500 ?></strong><br>
                            <small class="text-muted">Límite mensual</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compartir en Redes Sociales -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-share-alt text-info me-2"></i>
                        Comparte y Gana Puntos
                    </h5>
                    <p class="text-muted mb-3">
                        Comparte tu código en redes sociales y gana 10 puntos por cada compartida.
                    </p>
                    
                    <div class="text-center mb-3">
                        <div class="share-buttons">
                            <button class="btn share-facebook share-button" onclick="shareOnPlatform('facebook')">
                                <i class="fab fa-facebook-f me-2"></i>Facebook
                            </button>
                            <button class="btn share-whatsapp share-button" onclick="shareOnPlatform('whatsapp')">
                                <i class="fab fa-whatsapp me-2"></i>WhatsApp
                            </button>
                            <button class="btn share-twitter share-button" onclick="shareOnPlatform('twitter')">
                                <i class="fab fa-twitter me-2"></i>Twitter
                            </button>
                            <button class="btn share-email share-button" onclick="shareOnPlatform('email')">
                                <i class="fas fa-envelope me-2"></i>Email
                            </button>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Tu enlace de referido:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="referralLink" 
                                       value="<?= Config::getBaseUrl() ?>?route=ref/<?= $dashboard['referral_code'] ?? '' ?>" readonly>
                                <button class="btn btn-outline-secondary" onclick="copyReferralLink()">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Código QR:</label>
                            <div class="text-center">
                                <div id="qrcode" class="d-inline-block"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Progreso -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-chart-area text-success me-2"></i>
                        Tu Progreso Mensual
                    </h5>
                    <canvas id="progressChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Panel Lateral -->
        <div class="col-lg-4">
            <!-- Insignias -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-medal text-warning me-2"></i>
                        Mis Insignias
                    </h5>
                    <div class="row g-2">
                        <?php if (isset($dashboard['badges']) && !empty($dashboard['badges'])): ?>
                            <?php foreach ($dashboard['badges'] as $badge): ?>
                            <div class="col-6">
                                <div class="badge-item <?= $badge['earned'] ? 'badge-earned' : '' ?>">
                                    <div class="mb-2">
                                        <i class="<?= $badge['icon'] ?> fa-2x"></i>
                                    </div>
                                    <div class="fw-bold"><?= htmlspecialchars($badge['name']) ?></div>
                                    <small><?= htmlspecialchars($badge['description']) ?></small>
                                    <?php if ($badge['earned']): ?>
                                        <div class="text-success mt-1">
                                            <i class="fas fa-check-circle"></i> Obtenida
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center text-muted py-3">
                                <i class="fas fa-medal fa-3x mb-3 opacity-25"></i>
                                <p>Aún no tienes insignias. ¡Comienza a referir amigos para desbloquearlas!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Desafíos Activos -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-tasks text-primary me-2"></i>
                        Desafíos Activos
                    </h5>
                    <?php if (isset($challenges) && !empty($challenges)): ?>
                        <?php foreach ($challenges as $challenge): ?>
                        <div class="challenge-card <?= $challenge['active'] ? 'challenge-active' : '' ?> mb-3">
                            <div class="d-flex align-items-center mb-2">
                                <i class="<?= $challenge['icon'] ?> fa-lg text-primary me-3"></i>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($challenge['name']) ?></h6>
                                    <small class="text-muted"><?= htmlspecialchars($challenge['description']) ?></small>
                                </div>
                            </div>
                            <div class="progress mb-2" style="height: 6px;">
                                <div class="progress-bar" style="width: <?= ($challenge['current_value'] / $challenge['target_value']) * 100 ?>%"></div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <small><?= $challenge['current_value'] ?>/<?= $challenge['target_value'] ?></small>
                                <small class="text-success">+<?= $challenge['reward_points'] ?> puntos</small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-tasks fa-3x mb-3 opacity-25"></i>
                            <p>No hay desafíos activos actualmente.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actividad Reciente -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-history text-info me-2"></i>
                        Actividad Reciente
                    </h5>
                    <div id="recentActivity">
                        <?php if (isset($recent_activity) && !empty($recent_activity)): ?>
                            <?php foreach ($recent_activity as $activity): ?>
                            <div class="activity-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($activity['description']) ?></div>
                                        <div class="activity-date">
                                            <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                                        </div>
                                    </div>
                                    <?php if ($activity['points'] > 0): ?>
                                    <span class="badge bg-success">+<?= $activity['points'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div class="text-center mt-3">
                                <button class="btn btn-outline-primary btn-sm" onclick="loadMoreActivity()">
                                    <i class="fas fa-plus me-2"></i>Ver más
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-history fa-3x mb-3 opacity-25"></i>
                                <p>Aún no tienes actividad registrada.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Generar código QR
    const referralLink = document.getElementById('referralLink').value;
    QRCode.toCanvas(document.getElementById('qrcode'), referralLink, {
        width: 100,
        height: 100,
        margin: 1
    });
    
    // Crear gráfico de progreso
    createProgressChart();
    
    // Actualizar estadísticas cada 30 segundos
    setInterval(updateStats, 30000);
});

function copyReferralCode() {
    const code = document.getElementById('referralCode').textContent;
    navigator.clipboard.writeText(code).then(() => {
        showNotification('Código copiado al portapapeles', 'success');
        document.getElementById('copyBtn').innerHTML = '<i class="fas fa-check me-2"></i>¡Copiado!';
        setTimeout(() => {
            document.getElementById('copyBtn').innerHTML = '<i class="fas fa-copy me-2"></i>Copiar Código';
        }, 2000);
    });
}

function copyReferralLink() {
    const link = document.getElementById('referralLink');
    link.select();
    document.execCommand('copy');
    showNotification('Enlace copiado al portapapeles', 'success');
}

function shareOnPlatform(platform) {
    const code = document.getElementById('referralCode').textContent;
    const link = document.getElementById('referralLink').value;
    const message = `¡Descubre los increíbles tours mayas con Travel Mayan World! Usa mi código ${code} y obtén un 10% de descuento. ${link}`;
    
    let shareUrl = '';
    
    switch(platform) {
        case 'facebook':
            shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(link)}`;
            break;
        case 'whatsapp':
            shareUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
            break;
        case 'twitter':
            shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(message)}`;
            break;
        case 'email':
            shareUrl = `mailto:?subject=Descubre los tours mayas&body=${encodeURIComponent(message)}`;
            break;
    }
    
    if (shareUrl) {
        window.open(shareUrl, '_blank', 'width=600,height=400');
        
        // Registrar la compartida
        fetch('?route=referral/share', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `platform=${platform}&csrf_token=${getCSRFToken()}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.points_earned) {
                showNotification(`¡Ganaste ${data.points_earned} puntos por compartir!`, 'success');
                updateStats();
            }
        });
    }
}

function createProgressChart() {
    const ctx = document.getElementById('progressChart').getContext('2d');
    
    // Datos de ejemplo - en implementación real vendrían del backend
    const monthlyData = {
        labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
        datasets: [{
            label: 'Referidos',
            data: [2, 4, 3, 8, 6, 10],
            borderColor: '#28a745',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            fill: true,
            tension: 0.4
        }, {
            label: 'Ganancias ($)',
            data: [50, 120, 90, 240, 180, 350],
            borderColor: '#007bff',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            fill: true,
            tension: 0.4,
            yAxisID: 'y1'
        }]
    };
    
    new Chart(ctx, {
        type: 'line',
        data: monthlyData,
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    beginAtZero: true,
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
}

function updateStats() {
    fetch('?route=referral/get-stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Actualizar estadísticas en la interfaz
                updateStatsDisplay(data.stats);
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}

function updateStatsDisplay(stats) {
    // Actualizar elementos del DOM con las nuevas estadísticas
    const elements = {
        '.stats-number:eq(0)': stats.total_referrals,
        '.stats-number:eq(1)': '$' + parseFloat(stats.total_earnings).toFixed(2),
        '.stats-number:eq(2)': stats.current_streak,
        '.stats-number:eq(3)': parseFloat(stats.conversion_rate).toFixed(1) + '%'
    };
    
    for (const [selector, value] of Object.entries(elements)) {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = value;
        }
    }
}

function loadMoreActivity() {
    fetch('?route=referral/get-activity?limit=20')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateActivityDisplay(data.activity);
            }
        })
        .catch(error => console.error('Error loading activity:', error));
}

function updateActivityDisplay(activities) {
    const container = document.getElementById('recentActivity');
    let html = '';
    
    activities.forEach(activity => {
        html += `
            <div class="activity-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="fw-bold">${activity.description}</div>
                        <div class="activity-date">
                            ${new Date(activity.created_at).toLocaleString('es-ES')}
                        </div>
                    </div>
                    ${activity.points > 0 ? `<span class="badge bg-success">+${activity.points}</span>` : ''}
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `toast notification-toast align-items-center text-bg-${type} border-0`;
    notification.setAttribute('role', 'alert');
    notification.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check' : 'info'}-circle me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
    `;
    
    document.body.appendChild(notification);
    const toast = new bootstrap.Toast(notification);
    toast.show();
    
    // Remover después de que se oculte
    notification.addEventListener('hidden.bs.toast', () => {
        notification.remove();
    });
}

function getCSRFToken() {
    // En implementación real, este token vendría de una meta tag o input hidden
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}
</script>

</body>
</html>
