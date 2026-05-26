<?php 

use App\Core\Config;
$pageTitle = 'Configuración Referidos';
include __DIR__ . '/../../layouts/admin_header.php';
?>

<style>
        .config-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 4px solid #667eea;
        }
        
        .section-title {
            color: #667eea;
            font-weight: 600;
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            font-size: 1.25rem;
        }
        
        .section-title i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        
        .form-switch .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }
        
        .preview-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            margin-top: 1rem;
        }
        
        .level-config {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #dee2e6;
        }
        
        .level-config.level-1 { border-left: 4px solid #28a745; }
        .level-config.level-2 { border-left: 4px solid #17a2b8; }
        .level-config.level-3 { border-left: 4px solid #ffc107; }
        .level-config.level-4 { border-left: 4px solid #fd7e14; }
        .level-config.level-5 { border-left: 4px solid #dc3545; }
        
        .badge-config {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .badge-config:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .badge-config.active {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .color-picker {
            width: 50px;
            height: 35px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
        }
        
        .save-floating {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            border-radius: 50px;
            padding: 1rem 2rem;
            font-size: 1.1rem;
            font-weight: 600;
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            box-shadow: 0 10px 30px rgba(40, 167, 69, 0.3);
            transition: all 0.3s ease;
        }
        
        .save-floating:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(40, 167, 69, 0.4);
        }
        
        .email-template {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid #dee2e6;
            margin-bottom: 1rem;
        }
        
        .template-preview {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #e9ecef;
            font-family: Arial, sans-serif;
            font-size: 0.9rem;
            margin-top: 1rem;
        }
</style>

<div class="container-fluid py-4">
    <?php
        $actionTitle = 'Configuración del Programa de Referidos';
        $actionSubtitle = 'Personaliza reglas, recompensas y comportamiento del sistema';
        $actionButtons = [
            ['label' => 'Volver al Dashboard', 'icon' => 'fas fa-arrow-left', 'variant' => 'outline-primary', 'href' => Config::getBaseUrl() . '?route=admin/referral/dashboard'],
            ['label' => 'Vista Previa', 'icon' => 'fas fa-eye', 'variant' => 'info', 'onclick' => 'previewChanges()'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <strong>¡Éxito!</strong> <?= htmlspecialchars($success_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Error:</strong> <?= htmlspecialchars($error_message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= Config::getBaseUrl() ?>?route=admin/referral/settings" id="settingsForm" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        
        <div class="row">
            <!-- Configuración General -->
            <div class="col-lg-6">
                <div class="config-section">
                    <div class="section-title">
                        <i class="fas fa-cog"></i>
                        Configuración General
                    </div>
                    
                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" id="program_enabled" name="program_enabled" 
                               <?= ($settings['program_enabled'] ?? true) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="program_enabled">
                            <strong>Programa Activo</strong>
                            <div class="text-muted small">Habilitar o deshabilitar completamente el programa de referidos</div>
                        </label>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="referral_commission" class="form-label">
                                <i class="fas fa-percentage text-success me-2"></i>
                                Comisión por Referido (%)
                            </label>
                            <input type="number" class="form-control" id="referral_commission" name="referral_commission" 
                                   value="<?= htmlspecialchars($settings['referral_commission'] ?? '10') ?>" 
                                   min="0" max="50" step="0.1" required>
                            <div class="form-text">Porcentaje que recibe el referidor por cada venta exitosa</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="min_payout_amount" class="form-label">
                                <i class="fas fa-dollar-sign text-warning me-2"></i>
                                Monto Mínimo de Pago ($)
                            </label>
                            <input type="number" class="form-control" id="min_payout_amount" name="min_payout_amount" 
                                   value="<?= htmlspecialchars($settings['min_payout_amount'] ?? '50') ?>" 
                                   min="1" step="0.01" required>
                            <div class="form-text">Monto mínimo para procesar pagos</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="referrer_discount" class="form-label">
                                <i class="fas fa-tag text-primary me-2"></i>
                                Descuento Referidor (%)
                            </label>
                            <input type="number" class="form-control" id="referrer_discount" name="referrer_discount" 
                                   value="<?= htmlspecialchars($settings['referrer_discount'] ?? '5') ?>" 
                                   min="0" max="30" step="0.1">
                            <div class="form-text">Descuento adicional para el referidor en sus compras</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="referee_discount" class="form-label">
                                <i class="fas fa-gift text-info me-2"></i>
                                Descuento Referido (%)
                            </label>
                            <input type="number" class="form-control" id="referee_discount" name="referee_discount" 
                                   value="<?= htmlspecialchars($settings['referee_discount'] ?? '10') ?>" 
                                   min="0" max="30" step="0.1">
                            <div class="form-text">Descuento que recibe el nuevo cliente referido</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="cookie_duration" class="form-label">
                                <i class="fas fa-clock text-secondary me-2"></i>
                                Duración Cookie (días)
                            </label>
                            <input type="number" class="form-control" id="cookie_duration" name="cookie_duration" 
                                   value="<?= htmlspecialchars($settings['cookie_duration'] ?? '30') ?>" 
                                   min="1" max="365" required>
                            <div class="form-text">Tiempo de validez del tracking de referidos</div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="share_bonus_points" class="form-label">
                                <i class="fas fa-share-alt text-success me-2"></i>
                                Puntos por Compartir
                            </label>
                            <input type="number" class="form-control" id="share_bonus_points" name="share_bonus_points" 
                                   value="<?= htmlspecialchars($settings['share_bonus_points'] ?? '10') ?>" 
                                   min="0" max="100">
                            <div class="form-text">Puntos otorgados por compartir en redes sociales</div>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="auto_approve_payouts" name="auto_approve_payouts" 
                                       <?= ($settings['auto_approve_payouts'] ?? false) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="auto_approve_payouts">
                                    <strong>Auto-aprobar Pagos</strong>
                                    <div class="text-muted small">Los pagos se procesan automáticamente sin revisión manual</div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                       <?= ($settings['email_notifications'] ?? true) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="email_notifications">
                                    <strong>Notificaciones Email</strong>
                                    <div class="text-muted small">Enviar notificaciones automáticas a usuarios</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración de Niveles -->
                <div class="config-section">
                    <div class="section-title">
                        <i class="fas fa-layer-group"></i>
                        Niveles y Recompensas
                    </div>
                    
                    <div class="level-config level-1">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">
                                <span class="badge bg-success">Nivel 1</span> Principiante
                            </h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="level1_enabled" checked>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label small">Referidos Requeridos</label>
                                <input type="number" class="form-control form-control-sm" value="0" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Comisión (%)</label>
                                <input type="number" class="form-control form-control-sm" name="level1_commission" value="10" min="0" max="50" step="0.1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Límite Mensual ($)</label>
                                <input type="number" class="form-control form-control-sm" name="level1_limit" value="500" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="level-config level-2">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">
                                <span class="badge bg-info">Nivel 2</span> Embajador
                            </h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="level2_enabled" checked>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label small">Referidos Requeridos</label>
                                <input type="number" class="form-control form-control-sm" name="level2_required" value="5" min="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Comisión (%)</label>
                                <input type="number" class="form-control form-control-sm" name="level2_commission" value="12" min="0" max="50" step="0.1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Límite Mensual ($)</label>
                                <input type="number" class="form-control form-control-sm" name="level2_limit" value="1000" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="level-config level-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">
                                <span class="badge bg-warning">Nivel 3</span> Experto
                            </h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="level3_enabled" checked>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label small">Referidos Requeridos</label>
                                <input type="number" class="form-control form-control-sm" name="level3_required" value="15" min="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Comisión (%)</label>
                                <input type="number" class="form-control form-control-sm" name="level3_commission" value="15" min="0" max="50" step="0.1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Límite Mensual ($)</label>
                                <input type="number" class="form-control form-control-sm" name="level3_limit" value="2000" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="level-config level-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">
                                <span class="badge bg-danger">Nivel 4</span> Elite
                            </h6>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="level4_enabled" checked>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-label small">Referidos Requeridos</label>
                                <input type="number" class="form-control form-control-sm" name="level4_required" value="30" min="1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Comisión (%)</label>
                                <input type="number" class="form-control form-control-sm" name="level4_commission" value="18" min="0" max="50" step="0.1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Límite Mensual ($)</label>
                                <input type="number" class="form-control form-control-sm" name="level4_limit" value="5000" min="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuración de Insignias y Plantillas -->
            <div class="col-lg-6">
                <!-- Configuración de Insignias -->
                <div class="config-section">
                    <div class="section-title">
                        <i class="fas fa-medal"></i>
                        Insignias y Logros
                    </div>
                    
                    <div class="badge-config active" data-badge="first_referral">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-baby fa-2x text-success"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Primer Referido</h6>
                                <small class="text-muted">Se otorga al conseguir el primer referido exitoso</small>
                            </div>
                            <div>
                                <input type="number" class="form-control form-control-sm" name="badge_first_points" 
                                       value="100" min="0" max="1000" style="width: 80px;" placeholder="Puntos">
                            </div>
                        </div>
                    </div>
                    
                    <div class="badge-config" data-badge="streak_master">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-fire fa-2x text-warning"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Maestro de Rachas</h6>
                                <small class="text-muted">5 referidos exitosos consecutivos en 30 días</small>
                            </div>
                            <div>
                                <input type="number" class="form-control form-control-sm" name="badge_streak_points" 
                                       value="250" min="0" max="1000" style="width: 80px;" placeholder="Puntos">
                            </div>
                        </div>
                    </div>
                    
                    <div class="badge-config" data-badge="social_influencer">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-share-alt fa-2x text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Influencer Social</h6>
                                <small class="text-muted">Compartir en redes sociales 10 veces</small>
                            </div>
                            <div>
                                <input type="number" class="form-control form-control-sm" name="badge_social_points" 
                                       value="150" min="0" max="1000" style="width: 80px;" placeholder="Puntos">
                            </div>
                        </div>
                    </div>
                    
                    <div class="badge-config" data-badge="top_performer">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-crown fa-2x text-warning"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1">Top Performer</h6>
                                <small class="text-muted">Estar en el top 10 del leaderboard mensual</small>
                            </div>
                            <div>
                                <input type="number" class="form-control form-control-sm" name="badge_top_points" 
                                       value="500" min="0" max="1000" style="width: 80px;" placeholder="Puntos">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Plantillas de Email -->
                <div class="config-section">
                    <div class="section-title">
                        <i class="fas fa-envelope"></i>
                        Plantillas de Email
                    </div>
                    
                    <div class="email-template">
                        <h6 class="mb-3">
                            <i class="fas fa-user-plus text-success me-2"></i>
                            Bienvenida a Nuevo Usuario
                        </h6>
                        <div class="mb-3">
                            <label class="form-label">Asunto del Email</label>
                            <input type="text" class="form-control" name="welcome_subject" 
                                   value="¡Bienvenido al Programa de Referidos de Mayan World Travel!" 
                                   placeholder="Asunto del email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contenido del Email</label>
                            <textarea class="form-control" rows="4" name="welcome_content" 
                                      placeholder="Contenido personalizable...">Hola {{user_name}},

¡Te damos la bienvenida a nuestro programa de referidos! Tu código único es: {{referral_code}}

Comparte con tus amigos y gana increíbles recompensas por cada reserva exitosa.

¡Empezar es fácil y las recompensas son increíbles!</textarea>
                        </div>
                        <div class="template-preview">
                            <strong>Vista Previa:</strong><br>
                            <div id="welcomePreview" class="mt-2">
                                Hola <strong>María Gonzalez</strong>,<br><br>
                                ¡Te damos la bienvenida a nuestro programa de referidos! Tu código único es: <strong>MAYA2024ABC</strong><br><br>
                                Comparte con tus amigos y gana increíbles recompensas por cada reserva exitosa.<br><br>
                                ¡Empezar es fácil y las recompensas son increíbles!
                            </div>
                        </div>
                    </div>
                    
                    <div class="email-template">
                        <h6 class="mb-3">
                            <i class="fas fa-dollar-sign text-warning me-2"></i>
                            Notificación de Pago
                        </h6>
                        <div class="mb-3">
                            <label class="form-label">Asunto del Email</label>
                            <input type="text" class="form-control" name="payout_subject" 
                                   value="💰 ¡Tu pago de referidos está listo!" 
                                   placeholder="Asunto del email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contenido del Email</label>
                            <textarea class="form-control" rows="4" name="payout_content" 
                                      placeholder="Contenido personalizable...">¡Felicidades {{user_name}}!

Hemos procesado tu pago por ${{amount}} USD correspondiente a {{referrals_count}} referidos exitosos.

El pago será transferido a tu cuenta en las próximas 24-48 horas.

¡Sigue compartiendo y ganando!</textarea>
                        </div>
                    </div>
                </div>

                <!-- Configuración Avanzada -->
                <div class="config-section">
                    <div class="section-title">
                        <i class="fas fa-cogs"></i>
                        Configuración Avanzada
                    </div>
                    
                    <div class="mb-3">
                        <label for="fraud_detection" class="form-label">
                            <i class="fas fa-shield-alt text-danger me-2"></i>
                            Detección de Fraude
                        </label>
                        <select class="form-select" id="fraud_detection" name="fraud_detection">
                            <option value="disabled">Deshabilitada</option>
                            <option value="basic" selected>Básica (IP y Device)</option>
                            <option value="advanced">Avanzada (IA y Patrones)</option>
                        </select>
                        <div class="form-text">Nivel de verificación para detectar referidos fraudulentos</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="api_limits" class="form-label">
                            <i class="fas fa-tachometer-alt text-info me-2"></i>
                            Límites de API (por hora)
                        </label>
                        <input type="number" class="form-control" id="api_limits" name="api_limits" 
                               value="1000" min="100" max="10000">
                        <div class="form-text">Número máximo de requests por hora por usuario</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tracking_analytics" class="form-label">
                            <i class="fas fa-chart-line text-success me-2"></i>
                            Analytics y Tracking
                        </label>
                        <select class="form-select" id="tracking_analytics" name="tracking_analytics">
                            <option value="basic" selected>Básico</option>
                            <option value="detailed">Detallado</option>
                            <option value="advanced">Avanzado (ML)</option>
                        </select>
                        <div class="form-text">Nivel de análisis y tracking de comportamiento</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="gdpr_compliance" name="gdpr_compliance" checked>
                                <label class="form-check-label" for="gdpr_compliance">
                                    <strong>Cumplimiento GDPR</strong>
                                    <div class="text-muted small">Anonimizar datos después de 2 años</div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="debug_mode" name="debug_mode">
                                <label class="form-check-label" for="debug_mode">
                                    <strong>Modo Debug</strong>
                                    <div class="text-muted small">Logs detallados para debugging</div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Botón de Guardado Flotante -->
        <button type="submit" class="btn btn-success save-floating" id="saveButton">
            <i class="fas fa-save me-2"></i>
            Guardar Configuración
        </button>
    </form>
</div>

<!-- Modal de Vista Previa -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista Previa de Configuración</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Contenido generado dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="applyPreview()">Aplicar Cambios</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación en tiempo real
    const form = document.getElementById('settingsForm');
    const inputs = form.querySelectorAll('input[type="number"]');
    
    inputs.forEach(input => {
        input.addEventListener('input', validateInput);
    });
    
    // Actualización automática de vista previa
    const templateInputs = form.querySelectorAll('input[name*="welcome"], textarea[name*="welcome"]');
    templateInputs.forEach(input => {
        input.addEventListener('input', updateWelcomePreview);
    });
    
    // Configuración de insignias
    const badgeConfigs = document.querySelectorAll('.badge-config');
    badgeConfigs.forEach(config => {
        config.addEventListener('click', function() {
            badgeConfigs.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
        });
    });
    
    // Inicializar vista previa
    updateWelcomePreview();
});

function validateInput(event) {
    const input = event.target;
    const value = parseFloat(input.value);
    const min = parseFloat(input.min) || 0;
    const max = parseFloat(input.max) || Infinity;
    
    if (value < min || value > max) {
        input.classList.add('is-invalid');
        showValidationError(input, `Valor debe estar entre ${min} y ${max}`);
    } else {
        input.classList.remove('is-invalid');
        removeValidationError(input);
    }
    
    // Validaciones específicas
    if (input.name === 'referral_commission' && value > 30) {
        showToast('Comisión muy alta puede afectar rentabilidad', 'warning');
    }
    
    if (input.name === 'min_payout_amount' && value < 10) {
        showToast('Monto muy bajo puede generar muchos pagos pequeños', 'info');
    }
}

function showValidationError(input, message) {
    let feedback = input.parentNode.querySelector('.invalid-feedback');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'invalid-feedback';
        input.parentNode.appendChild(feedback);
    }
    feedback.textContent = message;
}

function removeValidationError(input) {
    const feedback = input.parentNode.querySelector('.invalid-feedback');
    if (feedback) {
        feedback.remove();
    }
}

function updateWelcomePreview() {
    const subject = document.querySelector('input[name="welcome_subject"]').value;
    const content = document.querySelector('textarea[name="welcome_content"]').value;
    
    const preview = document.getElementById('welcomePreview');
    const processedContent = content
        .replace(/{{user_name}}/g, '<strong>María Gonzalez</strong>')
        .replace(/{{referral_code}}/g, '<strong>MAYA2024ABC</strong>')
        .replace(/\n\n/g, '<br><br>')
        .replace(/\n/g, '<br>');
    
    preview.innerHTML = processedContent;
}

function previewChanges() {
    const formData = new FormData(document.getElementById('settingsForm'));
    const previewData = {};
    
    for (let [key, value] of formData.entries()) {
        previewData[key] = value;
    }
    
    // Generar vista previa
    let previewHTML = `
        <h6><i class="fas fa-cog me-2"></i>Configuración General</h6>
        <div class="row">
            <div class="col-md-6">
                <div class="alert alert-${previewData.program_enabled ? 'success' : 'danger'}">
                    <strong>Estado:</strong> ${previewData.program_enabled ? 'Activo' : 'Inactivo'}
                </div>
            </div>
            <div class="col-md-6">
                <div class="alert alert-info">
                    <strong>Comisión:</strong> ${previewData.referral_commission || 0}%
                </div>
            </div>
        </div>
        
        <h6><i class="fas fa-layer-group me-2"></i>Estructura de Niveles</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Nivel</th>
                        <th>Referidos</th>
                        <th>Comisión</th>
                        <th>Límite Mensual</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Nivel 1</td>
                        <td>0</td>
                        <td>${previewData.level1_commission || 10}%</td>
                        <td>$${previewData.level1_limit || 500}</td>
                    </tr>
                    <tr>
                        <td>Nivel 2</td>
                        <td>${previewData.level2_required || 5}</td>
                        <td>${previewData.level2_commission || 12}%</td>
                        <td>$${previewData.level2_limit || 1000}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <h6><i class="fas fa-envelope me-2"></i>Plantilla de Bienvenida</h6>
        <div class="alert alert-light">
            <strong>Asunto:</strong> ${previewData.welcome_subject || ''}<br>
            <strong>Contenido:</strong> ${(previewData.welcome_content || '').substring(0, 100)}...
        </div>
    `;
    
    document.getElementById('previewContent').innerHTML = previewHTML;
    new bootstrap.Modal(document.getElementById('previewModal')).show();
}

function applyPreview() {
    document.getElementById('settingsForm').submit();
}

function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0 position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999;';
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${type === 'success' ? 'check' : type === 'warning' ? 'exclamation' : 'info'}-circle me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    document.body.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

// Validación del formulario antes del envío
document.getElementById('settingsForm').addEventListener('submit', function(e) {
    const invalidInputs = this.querySelectorAll('.is-invalid');
    
    if (invalidInputs.length > 0) {
        e.preventDefault();
        showToast('Por favor corrige los errores antes de guardar', 'danger');
        invalidInputs[0].focus();
        return false;
    }
    
    // Mostrar indicador de guardado
    const saveButton = document.getElementById('saveButton');
    saveButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';
    saveButton.disabled = true;
});

// Detección de cambios no guardados
let formChanged = false;
document.getElementById('settingsForm').addEventListener('input', function() {
    formChanged = true;
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});
</script>

<?php include __DIR__ . '/../../layouts/admin_footer.php'; ?>
