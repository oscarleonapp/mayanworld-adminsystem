<?php 

use App\Core\Config;
$pageTitle = 'Programa de Referidos';
include __DIR__ . '/../../layouts/admin_header.php';
?>

<!-- Dependencias específicas -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
        .admin-sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border: none;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            line-height: 1;
        }
        
        .trend-up {
            color: #28a745;
        }
        
        .trend-down {
            color: #dc3545;
        }
        
        .quick-action {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: block;
            margin-bottom: 1rem;
        }
        
        .quick-action:hover {
            transform: translateX(10px);
            color: white;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .recent-activity {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 1rem;
            border-left: 3px solid #28a745;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
        }
        
        .payout-pending {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        
        .payout-approved {
            background: #d1edff;
            border-left-color: #0dcaf0;
        }
        
        .payout-processed {
            background: #d1e7dd;
            border-left-color: #198754;
        }
        
        .alert-metric {
            border-left: 4px solid;
            padding-left: 1rem;
        }
        
        .alert-success { border-left-color: #28a745; }
        .alert-warning { border-left-color: #ffc107; }
        .alert-danger { border-left-color: #dc3545; }
</style>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 p-0">
            <div class="admin-sidebar p-4">
                <h5 class="mb-4">
                    <i class="fas fa-users me-2"></i>
                    Panel Referidos
                </h5>
                
                <div class="nav flex-column">
                    <a href="<?= Config::getBaseUrl() ?>?route=admin/referral/dashboard" class="quick-action">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Dashboard
                    </a>
                    <a href="<?= Config::getBaseUrl() ?>?route=admin/referral/users" class="quick-action">
                        <i class="fas fa-users me-2"></i>
                        Usuarios (<?= $stats['total_users'] ?? 0 ?>)
                    </a>
                    <a href="<?= Config::getBaseUrl() ?>?route=admin/referral/payouts" class="quick-action">
                        <i class="fas fa-dollar-sign me-2"></i>
                        Pagos Pendientes (<?= $stats['pending_payouts'] ?? 0 ?>)
                    </a>
                    <a href="<?= Config::getBaseUrl() ?>?route=admin/referral/settings" class="quick-action">
                        <i class="fas fa-cog me-2"></i>
                        Configuración
                    </a>
                </div>
                
                <hr class="my-4" style="border-color: rgba(255,255,255,0.3);">
                
                <div class="alert-metric alert-success">
                    <small>Programa Activo</small><br>
                    <strong><?= $stats['program_status'] ?? 'Habilitado' ?></strong>
                </div>
                
                <div class="alert-metric alert-warning mt-3">
                    <small>Comisión Actual</small><br>
                    <strong><?= $stats['commission_rate'] ?? 10 ?>%</strong>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <?php
                $actionTitle = 'Panel de Administración - Referidos';
                $actionSubtitle = 'Gestiona el programa de referidos y recompensas';
                $actionButtons = [
                    ['label' => 'Exportar Datos', 'icon' => 'fas fa-download', 'variant' => 'primary', 'onclick' => 'exportData()'],
                    ['label' => 'Notificar Usuarios', 'icon' => 'fas fa-bell', 'variant' => 'success', 'onclick' => 'sendBulkNotifications()'],
                ];
                include __DIR__ . '/../../partials/admin_action_bar.php';
            ?>

            <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Estadísticas Principales -->
            <div class="row g-4 mb-5">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary text-white">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <div class="stat-number text-primary">
                                    <?= number_format($stats['total_users'] ?? 0) ?>
                                </div>
                                <div class="text-muted">Usuarios Inscritos</div>
                                <small class="trend-up">
                                    <i class="fas fa-arrow-up me-1"></i>
                                    +<?= $stats['new_users_this_month'] ?? 0 ?> este mes
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success text-white">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <div class="stat-number text-success">
                                    <?= number_format($stats['total_referrals'] ?? 0) ?>
                                </div>
                                <div class="text-muted">Referidos Totales</div>
                                <small class="trend-up">
                                    <i class="fas fa-arrow-up me-1"></i>
                                    +<?= $stats['referrals_this_week'] ?? 0 ?> esta semana
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning text-white">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <div class="stat-number text-warning">
                                    $<?= number_format($stats['total_commissions'] ?? 0, 2) ?>
                                </div>
                                <div class="text-muted">Comisiones Pagadas</div>
                                <small class="text-muted">
                                    $<?= number_format($stats['pending_amount'] ?? 0, 2) ?> pendiente
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info text-white">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="ms-3 flex-grow-1">
                                <div class="stat-number text-info">
                                    <?= number_format($stats['conversion_rate'] ?? 0, 1) ?>%
                                </div>
                                <div class="text-muted">Tasa Conversión</div>
                                <small class="<?= ($stats['conversion_trend'] ?? 0) > 0 ? 'trend-up' : 'trend-down' ?>">
                                    <i class="fas fa-arrow-<?= ($stats['conversion_trend'] ?? 0) > 0 ? 'up' : 'down' ?> me-1"></i>
                                    <?= abs($stats['conversion_trend'] ?? 0) ?>% vs mes anterior
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos y Análisis -->
            <div class="row mb-5">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2"></i>
                                Tendencias del Programa
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="trendsChart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-pie me-2"></i>
                                Distribución por Nivel
                            </h5>
                        </div>
                        <div class="card-body">
                            <canvas id="levelsChart" width="300" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Performers y Pagos Pendientes -->
            <div class="row mb-5">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-trophy me-2 text-warning"></i>
                                Top Referidores
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (isset($top_referrers) && !empty($top_referrers)): ?>
                                <?php foreach ($top_referrers as $index => $user): ?>
                                <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                                    <div class="me-3">
                                        <span class="badge bg-<?= $index === 0 ? 'warning' : ($index === 1 ? 'secondary' : 'info') ?> rounded-circle" style="width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">
                                            <?= $index + 1 ?>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?= htmlspecialchars($user['name']) ?></div>
                                        <small class="text-muted">
                                            <?= $user['referrals'] ?> referidos • $<?= number_format($user['earnings'], 2) ?>
                                        </small>
                                    </div>
                                    <div>
                                        <span class="badge bg-primary">Nivel <?= $user['level'] ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-trophy fa-3x opacity-25 mb-3"></i>
                                    <p>No hay datos de top referidores disponibles</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clock me-2 text-warning"></i>
                                Pagos Pendientes
                            </h5>
                            <button class="btn btn-success btn-sm" onclick="processBulkPayouts()">
                                <i class="fas fa-check me-1"></i>Procesar Todos
                            </button>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <?php if (isset($pending_payouts) && !empty($pending_payouts)): ?>
                                <?php foreach ($pending_payouts as $payout): ?>
                                <div class="activity-item payout-pending">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="fw-bold"><?= htmlspecialchars($payout['user_name']) ?></div>
                                            <div class="text-success fw-bold">$<?= number_format($payout['amount'], 2) ?></div>
                                            <small class="text-muted">
                                                Por <?= $payout['referrals_count'] ?> referidos
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-success" onclick="approvePayout(<?= $payout['id'] ?>)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-outline-danger" onclick="rejectPayout(<?= $payout['id'] ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-check-circle fa-3x opacity-25 mb-3"></i>
                                    <p>No hay pagos pendientes</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actividad Reciente -->
            <div class="card mb-5">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2"></i>
                        Actividad Reciente
                    </h5>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary active" onclick="filterActivity('all')">Todos</button>
                        <button class="btn btn-outline-success" onclick="filterActivity('referrals')">Referidos</button>
                        <button class="btn btn-outline-warning" onclick="filterActivity('payouts')">Pagos</button>
                        <button class="btn btn-outline-info" onclick="filterActivity('levels')">Niveles</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="recent-activity" id="recentActivity">
                        <?php if (isset($recent_referrals) && !empty($recent_referrals)): ?>
                            <?php foreach ($recent_referrals as $activity): ?>
                            <div class="activity-item" data-type="<?= $activity['type'] ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold"><?= htmlspecialchars($activity['description']) ?></div>
                                        <div class="d-flex align-items-center mt-1">
                                            <small class="text-muted me-3">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= date('d/m/Y H:i', strtotime($activity['created_at'])) ?>
                                            </small>
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1"></i>
                                                <?= htmlspecialchars($activity['user_name']) ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <?php if (isset($activity['amount']) && $activity['amount'] > 0): ?>
                                        <span class="badge bg-success">$<?= number_format($activity['amount'], 2) ?></span>
                                        <?php endif; ?>
                                        <?php if (isset($activity['points']) && $activity['points'] > 0): ?>
                                        <span class="badge bg-primary">+<?= $activity['points'] ?> pts</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-history fa-3x opacity-25 mb-3"></i>
                                <p>No hay actividad reciente</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Alertas del Sistema -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-exclamation-triangle me-2 text-warning"></i>
                                Alertas del Sistema
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row" id="systemAlerts">
                                <div class="col-md-4">
                                    <div class="alert alert-warning mb-2">
                                        <strong>Pagos Pendientes:</strong> <?= $stats['pending_payouts'] ?? 0 ?> pagos esperando aprobación
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="alert alert-info mb-2">
                                        <strong>Nuevos Usuarios:</strong> <?= $stats['new_users_today'] ?? 0 ?> inscripciones hoy
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="alert alert-<?= ($stats['conversion_rate'] ?? 0) < 5 ? 'danger' : 'success' ?> mb-2">
                                        <strong>Conversión:</strong> <?= number_format($stats['conversion_rate'] ?? 0, 1) ?>% 
                                        (<?= ($stats['conversion_rate'] ?? 0) < 5 ? 'Baja' : 'Buena' ?>)
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const baseUrl = '<?= Config::getBaseUrl() ?>';
document.addEventListener('DOMContentLoaded', function() {
    createTrendsChart();
    createLevelsChart();
    
    // Auto-refresh cada 5 minutos
    setInterval(refreshDashboard, 300000);
});

function createTrendsChart() {
    const ctx = document.getElementById('trendsChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'],
            datasets: [{
                label: 'Nuevos Referidos',
                data: [12, 19, 15, 25, 22, 30],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.4,
                fill: true
            }, {
                label: 'Comisiones ($)',
                data: [300, 450, 380, 620, 550, 750],
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left'
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

function createLevelsChart() {
    const ctx = document.getElementById('levelsChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Nivel 1', 'Nivel 2', 'Nivel 3', 'Nivel 4', 'Nivel 5+'],
            datasets: [{
                data: [45, 25, 15, 10, 5],
                backgroundColor: [
                    '#667eea',
                    '#764ba2',
                    '#28a745',
                    '#ffc107',
                    '#dc3545'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

function approvePayout(payoutId) {
    if (confirm('¿Aprobar este pago?')) {
        fetch(`${baseUrl}?route=admin/referral/payouts`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=approve&payout_id=${payoutId}&csrf_token=${getCSRFToken()}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Pago aprobado exitosamente', 'success');
                refreshDashboard();
            } else {
                showNotification('Error al aprobar pago: ' + data.message, 'danger');
            }
        });
    }
}

function rejectPayout(payoutId) {
    if (confirm('¿Rechazar este pago?')) {
        const reason = prompt('Motivo del rechazo (opcional):');
        
        fetch(`${baseUrl}?route=admin/referral/payouts`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=reject&payout_id=${payoutId}&reason=${encodeURIComponent(reason || '')}&csrf_token=${getCSRFToken()}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Pago rechazado', 'warning');
                refreshDashboard();
            } else {
                showNotification('Error al rechazar pago: ' + data.message, 'danger');
            }
        });
    }
}

function processBulkPayouts() {
    if (confirm('¿Procesar todos los pagos pendientes?')) {
        fetch(`${baseUrl}?route=admin/referral/notify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=bulk_process&csrf_token=${getCSRFToken()}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`Procesados ${data.processed} pagos exitosamente`, 'success');
                refreshDashboard();
            } else {
                showNotification('Error al procesar pagos: ' + data.message, 'danger');
            }
        });
    }
}

function filterActivity(type) {
    const activities = document.querySelectorAll('#recentActivity .activity-item');
    const buttons = document.querySelectorAll('.btn-group .btn');
    
    // Reset button states
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    activities.forEach(activity => {
        if (type === 'all' || activity.dataset.type === type) {
            activity.style.display = 'block';
        } else {
            activity.style.display = 'none';
        }
    });
}

function exportData() {
    const format = prompt('Formato de exportación (csv/excel):', 'csv');
    if (format) {
        const url = `${baseUrl}?route=admin/referral/export&format=${encodeURIComponent(format)}`;
        if (typeof AdminDownload === 'function') {
            AdminDownload(url, {
                filenameFallback: `referidos.${format === 'excel' ? 'xlsx' : 'csv'}`,
                startMessage: 'Generando exportación...',
                errorMessage: 'Error al exportar referidos'
            });
        }
    }
}

function sendBulkNotifications() {
    const message = prompt('Mensaje para enviar a todos los usuarios:');
    if (message) {
        fetch(`${baseUrl}?route=admin/referral/notify`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `message=${encodeURIComponent(message)}&csrf_token=${getCSRFToken()}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(`Notificaciones enviadas a ${data.sent} usuarios`, 'success');
            } else {
                showNotification('Error al enviar notificaciones: ' + data.message, 'danger');
            }
        });
    }
}

function refreshDashboard() {
    window.location.reload();
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

// Auto-actualización de estadísticas cada 30 segundos
setInterval(() => {
    fetch(`${baseUrl}?route=admin/referral/stats`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatsDisplay(data.stats);
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}, 30000);

function updateStatsDisplay(stats) {
    // Actualizar números principales
    document.querySelectorAll('.stat-number')[0].textContent = numberFormat(stats.total_users);
    document.querySelectorAll('.stat-number')[1].textContent = numberFormat(stats.total_referrals);
    document.querySelectorAll('.stat-number')[2].textContent = '$' + numberFormat(stats.total_commissions, 2);
    document.querySelectorAll('.stat-number')[3].textContent = numberFormat(stats.conversion_rate, 1) + '%';
}

function numberFormat(number, decimals = 0) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
}
</script>

<?php include __DIR__ . '/../../layouts/admin_footer.php'; ?>
