<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking de Referidores - Travel Mayan World</title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        .leaderboard-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .leaderboard-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="trophy" x="0" y="0" width="50" height="50" patternUnits="userSpaceOnUse"><text x="25" y="25" text-anchor="middle" fill="rgba(255,255,255,0.1)" font-size="20">🏆</text></pattern></defs><rect width="100" height="100" fill="url(%23trophy)"/></svg>');
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% { transform: translateX(-100px) translateY(-100px); }
            100% { transform: translateX(100px) translateY(100px); }
        }
        
        .period-selector {
            background: rgba(255,255,255,0.1);
            border-radius: 50px;
            padding: 0.5rem;
            display: inline-flex;
            margin-bottom: 2rem;
        }
        
        .period-btn {
            background: transparent;
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            margin: 0 0.25rem;
        }
        
        .period-btn.active {
            background: white;
            color: #667eea;
            font-weight: 600;
        }
        
        .podium {
            margin: 3rem 0;
            position: relative;
        }
        
        .podium-position {
            text-align: center;
            position: relative;
        }
        
        .podium-base {
            background: linear-gradient(135deg, #ffd700, #ffed4a);
            border-radius: 10px 10px 0 0;
            padding: 2rem 1rem;
            margin-top: 2rem;
            position: relative;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        
        .podium-second {
            background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
            height: 100px;
        }
        
        .podium-third {
            background: linear-gradient(135deg, #cd7f32, #daa520);
            height: 80px;
        }
        
        .podium-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid white;
            margin: 0 auto 1rem;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            position: absolute;
            top: -40px;
            left: 50%;
            transform: translateX(-50%);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .podium-crown {
            position: absolute;
            top: -60px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2rem;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateX(-50%) translateY(0); }
            40% { transform: translateX(-50%) translateY(-10px); }
            60% { transform: translateX(-50%) translateY(-5px); }
        }
        
        .leaderboard-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .rank-cell {
            width: 60px;
            text-align: center;
            font-weight: bold;
        }
        
        .rank-1 { color: #ffd700; }
        .rank-2 { color: #c0c0c0; }
        .rank-3 { color: #cd7f32; }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 1rem;
        }
        
        .progress-bar-custom {
            height: 6px;
            border-radius: 3px;
            background: #e9ecef;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #28a745, #20c997);
            border-radius: 3px;
            transition: width 1s ease;
        }
        
        .user-rank-card {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
        }
        
        .achievement-badge {
            background: linear-gradient(135deg, #ffc107, #ffed4a);
            color: #333;
            border-radius: 25px;
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        
        .loading-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
            border-radius: 4px;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        .stats-mini {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .level-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 12px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-light">

<?php if (isset($error)): ?>
<div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
    <strong>Error:</strong> <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
</div>
<?php endif; ?>

<!-- Hero Section -->
<div class="leaderboard-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8 text-center mx-auto">
                <h1 class="display-4 fw-bold mb-4">
                    <i class="fas fa-trophy me-3"></i>
                    Ranking de Campeones
                </h1>
                <p class="lead mb-4">
                    Los mejores referidores de la comunidad Maya. ¡Compite y escala posiciones para ganar premios exclusivos!
                </p>
                
                <!-- Selector de Período -->
                <div class="period-selector">
                    <button class="period-btn <?= ($period === 'daily') ? 'active' : '' ?>" 
                            onclick="changePeriod('daily')">Hoy</button>
                    <button class="period-btn <?= ($period === 'weekly') ? 'active' : '' ?>" 
                            onclick="changePeriod('weekly')">Semana</button>
                    <button class="period-btn <?= ($period === 'monthly') ? 'active' : '' ?>" 
                            onclick="changePeriod('monthly')">Mes</button>
                    <button class="period-btn <?= ($period === 'all_time') ? 'active' : '' ?>" 
                            onclick="changePeriod('all_time')">Histórico</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <!-- Tu Posición Actual -->
    <?php if (isset($user_ranking) && $user_ranking): ?>
    <div class="user-rank-card">
        <div class="row align-items-center">
            <div class="col-md-3 text-center">
                <div class="display-4 fw-bold mb-2">
                    #<?= $user_ranking['position'] ?>
                </div>
                <div>Tu Posición Actual</div>
            </div>
            <div class="col-md-6">
                <h5 class="mb-2">
                    <?= htmlspecialchars($user_ranking['name']) ?>
                    <span class="level-badge">Nivel <?= $user_ranking['level'] ?></span>
                </h5>
                <div class="row text-center">
                    <div class="col-4">
                        <div class="fw-bold"><?= $user_ranking['referrals'] ?></div>
                        <small>Referidos</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold">$<?= number_format($user_ranking['earnings'], 2) ?></div>
                        <small>Ganancias</small>
                    </div>
                    <div class="col-4">
                        <div class="fw-bold"><?= $user_ranking['points'] ?></div>
                        <small>Puntos</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <a href="?route=referral/dashboard" class="btn btn-light btn-lg">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Mi Dashboard
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Podium Top 3 -->
    <?php if (isset($leaderboard) && count($leaderboard) >= 3): ?>
    <div class="podium">
        <div class="row">
            <!-- Segundo Lugar -->
            <div class="col-md-4">
                <div class="podium-position">
                    <div class="podium-avatar">
                        <?= strtoupper(substr($leaderboard[1]['name'], 0, 2)) ?>
                    </div>
                    <div class="podium-base podium-second">
                        <div class="fw-bold h5"><?= htmlspecialchars($leaderboard[1]['name']) ?></div>
                        <div class="stats-mini">
                            <i class="fas fa-users me-1"></i><?= $leaderboard[1]['referrals'] ?> referidos<br>
                            <i class="fas fa-dollar-sign me-1"></i>$<?= number_format($leaderboard[1]['earnings'], 2) ?>
                        </div>
                        <span class="level-badge mt-2">Nivel <?= $leaderboard[1]['level'] ?></span>
                    </div>
                    <div class="text-center mt-2">
                        <i class="fas fa-medal fa-2x" style="color: #c0c0c0;"></i>
                    </div>
                </div>
            </div>

            <!-- Primer Lugar -->
            <div class="col-md-4">
                <div class="podium-position">
                    <div class="podium-crown">👑</div>
                    <div class="podium-avatar">
                        <?= strtoupper(substr($leaderboard[0]['name'], 0, 2)) ?>
                    </div>
                    <div class="podium-base">
                        <div class="fw-bold h4"><?= htmlspecialchars($leaderboard[0]['name']) ?></div>
                        <div class="stats-mini text-dark">
                            <i class="fas fa-users me-1"></i><?= $leaderboard[0]['referrals'] ?> referidos<br>
                            <i class="fas fa-dollar-sign me-1"></i>$<?= number_format($leaderboard[0]['earnings'], 2) ?>
                        </div>
                        <span class="level-badge mt-2" style="background: #333;">Nivel <?= $leaderboard[0]['level'] ?></span>
                        <?php if (isset($leaderboard[0]['achievements'])): ?>
                            <?php foreach ($leaderboard[0]['achievements'] as $achievement): ?>
                            <span class="achievement-badge"><?= $achievement ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="text-center mt-2">
                        <i class="fas fa-crown fa-2x" style="color: #ffd700;"></i>
                    </div>
                </div>
            </div>

            <!-- Tercer Lugar -->
            <div class="col-md-4">
                <div class="podium-position">
                    <div class="podium-avatar">
                        <?= strtoupper(substr($leaderboard[2]['name'], 0, 2)) ?>
                    </div>
                    <div class="podium-base podium-third">
                        <div class="fw-bold h5"><?= htmlspecialchars($leaderboard[2]['name']) ?></div>
                        <div class="stats-mini">
                            <i class="fas fa-users me-1"></i><?= $leaderboard[2]['referrals'] ?> referidos<br>
                            <i class="fas fa-dollar-sign me-1"></i>$<?= number_format($leaderboard[2]['earnings'], 2) ?>
                        </div>
                        <span class="level-badge mt-2">Nivel <?= $leaderboard[2]['level'] ?></span>
                    </div>
                    <div class="text-center mt-2">
                        <i class="fas fa-medal fa-2x" style="color: #cd7f32;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tabla de Ranking Completa -->
    <div class="leaderboard-table">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="leaderboardTable">
                <thead class="table-dark">
                    <tr>
                        <th class="rank-cell">Pos.</th>
                        <th>Usuario</th>
                        <th class="text-center">Nivel</th>
                        <th class="text-center">Referidos</th>
                        <th class="text-center">Ganancias</th>
                        <th class="text-center">Puntos</th>
                        <th class="text-center">Progreso</th>
                        <th class="text-center">Insignias</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($leaderboard) && !empty($leaderboard)): ?>
                        <?php foreach ($leaderboard as $index => $user): ?>
                        <tr class="<?= ($user['is_current_user'] ?? false) ? 'table-success' : '' ?>">
                            <td class="rank-cell">
                                <span class="rank-<?= min($index + 1, 3) ?>">
                                    <?php if ($index === 0): ?>
                                        <i class="fas fa-crown text-warning"></i>
                                    <?php elseif ($index === 1): ?>
                                        <i class="fas fa-medal" style="color: #c0c0c0;"></i>
                                    <?php elseif ($index === 2): ?>
                                        <i class="fas fa-medal" style="color: #cd7f32;"></i>
                                    <?php else: ?>
                                        #<?= $index + 1 ?>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar">
                                        <?= strtoupper(substr($user['name'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold">
                                            <?= htmlspecialchars($user['name']) ?>
                                            <?php if ($user['is_current_user'] ?? false): ?>
                                                <span class="badge bg-success ms-2">TÚ</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">
                                            Miembro desde <?= date('M Y', strtotime($user['joined_date'] ?? 'now')) ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="level-badge">
                                    Nivel <?= $user['level'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="fw-bold"><?= number_format($user['referrals']) ?></div>
                                <small class="text-muted">
                                    <?= $user['successful_rate'] ?? 0 ?>% éxito
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="fw-bold text-success">
                                    $<?= number_format($user['earnings'], 2) ?>
                                </div>
                                <small class="text-muted">
                                    +$<?= number_format($user['this_month_earnings'] ?? 0, 2) ?> este mes
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="fw-bold text-primary">
                                    <?= number_format($user['points']) ?>
                                </div>
                                <small class="text-muted">pts</small>
                            </td>
                            <td class="text-center" style="width: 150px;">
                                <div class="progress-bar-custom mb-1">
                                    <div class="progress-fill" 
                                         style="width: <?= min(($user['points'] / 1000) * 100, 100) ?>%">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?= $user['progress_to_next_level'] ?? 0 ?>% al siguiente
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="fw-bold"><?= $user['badges_count'] ?? 0 ?></div>
                                <small class="text-muted">
                                    <i class="fas fa-medal text-warning"></i>
                                </small>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-users fa-3x mb-3 opacity-25"></i>
                                    <h5>No hay datos disponibles</h5>
                                    <p>Sé el primero en aparecer en el ranking</p>
                                    <a href="?route=referral/enroll" class="btn btn-primary">
                                        Unirme al Programa
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Información de Premios -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title text-center mb-4">
                        <i class="fas fa-gift text-warning me-2"></i>
                        Premios por Ranking
                    </h5>
                    
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="p-3 border rounded">
                                <i class="fas fa-crown fa-2x text-warning mb-3"></i>
                                <h6>1er Lugar</h6>
                                <div class="fw-bold text-success">$500 USD</div>
                                <small class="text-muted">+ Tour VIP gratuito</small>
                            </div>
                        </div>
                        
                        <div class="col-md-3 text-center">
                            <div class="p-3 border rounded">
                                <i class="fas fa-medal fa-2x" style="color: #c0c0c0;" mb-3></i>
                                <h6>2do Lugar</h6>
                                <div class="fw-bold text-success">$250 USD</div>
                                <small class="text-muted">+ Descuento 50%</small>
                            </div>
                        </div>
                        
                        <div class="col-md-3 text-center">
                            <div class="p-3 border rounded">
                                <i class="fas fa-medal fa-2x" style="color: #cd7f32;" mb-3></i>
                                <h6>3er Lugar</h6>
                                <div class="fw-bold text-success">$100 USD</div>
                                <small class="text-muted">+ Descuento 30%</small>
                            </div>
                        </div>
                        
                        <div class="col-md-3 text-center">
                            <div class="p-3 border rounded">
                                <i class="fas fa-star fa-2x text-info mb-3"></i>
                                <h6>Top 10</h6>
                                <div class="fw-bold text-success">$50 USD</div>
                                <small class="text-muted">+ Insignia exclusiva</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            Los premios se entregan mensualmente a los ganadores del ranking histórico.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="text-center mt-5">
        <div class="btn-group" role="group">
            <a href="?route=referral/dashboard" class="btn btn-primary">
                <i class="fas fa-tachometer-alt me-2"></i>Mi Dashboard
            </a>
            <a href="?route=referral/enroll" class="btn btn-success">
                <i class="fas fa-user-plus me-2"></i>Unirme
            </a>
            <a href="?route=referral/faq" class="btn btn-outline-info">
                <i class="fas fa-question-circle me-2"></i>FAQ
            </a>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function changePeriod(period) {
    // Mostrar loading en la tabla
    showTableLoading();
    
    // Actualizar URL y recargar
    const url = new URL(window.location);
    url.searchParams.set('period', period);
    window.location.href = url.toString();
}

function showTableLoading() {
    const tbody = document.querySelector('#leaderboardTable tbody');
    const skeletonRows = [];
    
    for (let i = 0; i < 10; i++) {
        skeletonRows.push(`
            <tr>
                <td class="text-center"><div class="loading-skeleton" style="height: 20px; width: 30px; margin: 0 auto;"></div></td>
                <td><div class="loading-skeleton" style="height: 20px; width: 200px;"></div></td>
                <td class="text-center"><div class="loading-skeleton" style="height: 20px; width: 60px; margin: 0 auto;"></div></td>
                <td class="text-center"><div class="loading-skeleton" style="height: 20px; width: 40px; margin: 0 auto;"></div></td>
                <td class="text-center"><div class="loading-skeleton" style="height: 20px; width: 80px; margin: 0 auto;"></div></td>
                <td class="text-center"><div class="loading-skeleton" style="height: 20px; width: 60px; margin: 0 auto;"></div></td>
                <td class="text-center"><div class="loading-skeleton" style="height: 10px; width: 100px; margin: 0 auto;"></div></td>
                <td class="text-center"><div class="loading-skeleton" style="height: 20px; width: 40px; margin: 0 auto;"></div></td>
            </tr>
        `);
    }
    
    tbody.innerHTML = skeletonRows.join('');
}

// Animaciones de entrada
document.addEventListener('DOMContentLoaded', function() {
    // Animar barras de progreso
    const progressBars = document.querySelectorAll('.progress-fill');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
            bar.style.width = width;
        }, 500);
    });
    
    // Efecto hover en filas de tabla
    const tableRows = document.querySelectorAll('#leaderboardTable tbody tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02)';
            this.style.transition = 'all 0.2s ease';
        });
        
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Auto-refresh cada 2 minutos
    setInterval(() => {
        if (document.visibilityState === 'visible') {
            window.location.reload();
        }
    }, 120000);
});

// Funcionalidad de tooltip para logros
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

</body>
</html>
