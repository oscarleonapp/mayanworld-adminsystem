<?php
/**
 * Vista: Configuración de Pasarelas de Pago
 *
 * Permite configurar las credenciales de todas las pasarelas de pago
 * desde el panel de administración.
 */

include __DIR__ . '/../../layouts/admin_header.php';
?>

<style>
    .payment-settings-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .gateway-card {
        background: white;
        border-radius: 10px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-left: 5px solid #667eea;
    }

    .gateway-card.stripe { border-left-color: #635bff; }
    .gateway-card.paggo { border-left-color: #00d4aa; }
    .gateway-card.recurrente { border-left-color: #ff6b6b; }
    .gateway-card.rnpl { border-left-color: #4ecdc4; }

    .gateway-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f0f0;
    }

    .gateway-title {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .gateway-icon {
        font-size: 32px;
    }

    .gateway-title h3 {
        margin: 0;
        color: #333;
        font-size: 24px;
    }

    .gateway-subtitle {
        color: #666;
        font-size: 14px;
        margin: 5px 0 0 0;
    }

    .gateway-toggle {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .toggle-switch {
        position: relative;
        width: 60px;
        height: 30px;
    }

    .toggle-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .toggle-slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: 0.4s;
        border-radius: 30px;
    }

    .toggle-slider:before {
        position: absolute;
        content: "";
        height: 22px;
        width: 22px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: 0.4s;
        border-radius: 50%;
    }

    .toggle-switch input:checked + .toggle-slider {
        background-color: #28a745;
    }

    .toggle-switch input:checked + .toggle-slider:before {
        transform: translateX(30px);
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #555;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .form-group label .required {
        color: #dc3545;
        margin-left: 3px;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 5px;
        font-size: 14px;
        transition: all 0.3s;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-group .help-text {
        font-size: 12px;
        color: #888;
        margin-top: 5px;
    }

    .credential-field {
        position: relative;
    }

    .credential-field input {
        padding-right: 45px;
    }

    .toggle-visibility {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #888;
        cursor: pointer;
        font-size: 18px;
        padding: 5px;
    }

    .toggle-visibility:hover {
        color: #667eea;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 5px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-info {
        background: #d1ecf1;
        border-left: 4px solid #0c5460;
        color: #0c5460;
    }

    .alert-warning {
        background: #fff3cd;
        border-left: 4px solid #856404;
        color: #856404;
    }

    .alert-success {
        background: #d4edda;
        border-left: 4px solid #155724;
        color: #155724;
    }

    .save-button {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 15px 40px;
        border-radius: 5px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .save-button:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .save-button:active {
        transform: translateY(0);
    }

    .documentation-links {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 5px;
        margin-top: 10px;
    }

    .documentation-links h4 {
        margin: 0 0 10px 0;
        color: #555;
        font-size: 14px;
    }

    .documentation-links a {
        color: #667eea;
        text-decoration: none;
        margin-right: 15px;
        font-size: 13px;
    }

    .documentation-links a:hover {
        text-decoration: underline;
    }

    .actions-bar {
        position: sticky;
        bottom: 0;
        background: white;
        padding: 20px;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        border-radius: 10px 10px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 40px;
    }
</style>

<div class="payment-settings-container">
    <?php
        $actionTitle = 'Configuración de Pasarelas de Pago';
        $actionSubtitle = 'Administra credenciales y estados de las pasarelas de pago.';
        $actionButtons = [];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <?php if (!empty($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type'] ?? 'info'; ?>">
            <span>ℹ️</span>
            <span><?php echo htmlspecialchars($_SESSION['flash_message']); ?></span>
        </div>
        <?php
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        ?>
    <?php endif; ?>

    <div class="alert alert-info">
        <span>💡</span>
        <div>
            <strong>Importante:</strong> Las credenciales se guardan en la base de datos y reemplazarán cualquier configuración en <code>config.local.php</code>.
            Asegúrate de usar las credenciales correctas para producción.
        </div>
    </div>

    <form method="POST" action="?route=admin/settings/payments/update">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <!-- STRIPE -->
        <div class="gateway-card stripe">
            <div class="gateway-header">
                <div class="gateway-title">
                    <div class="gateway-icon">💳</div>
                    <div>
                        <h3>Stripe</h3>
                        <p class="gateway-subtitle">Tarjetas de crédito/débito internacionales</p>
                    </div>
                </div>
                <div class="gateway-toggle">
                    <span>Estado:</span>
                    <label class="toggle-switch">
                        <input type="checkbox" name="STRIPE_ENABLED" value="true"
                               <?php echo ($gateway_settings['stripe']['enabled'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Publishable Key <span class="required">*</span></label>
                    <div class="credential-field">
                        <input type="password" name="STRIPE_PUBLISHABLE_KEY"
                               value="<?php echo htmlspecialchars($gateway_settings['stripe']['publishable_key'] ?? ''); ?>"
                               placeholder="pk_live_..." class="credential-input">
                        <button type="button" class="toggle-visibility" onclick="togglePasswordVisibility(this)">
                            👁️
                        </button>
                    </div>
                    <small class="help-text">Clave pública para el frontend</small>
                </div>

                <div class="form-group">
                    <label>Secret Key <span class="required">*</span></label>
                    <div class="credential-field">
                        <input type="password" name="STRIPE_SECRET_KEY"
                               value="<?php echo htmlspecialchars($gateway_settings['stripe']['secret_key'] ?? ''); ?>"
                               placeholder="sk_live_..." class="credential-input">
                        <button type="button" class="toggle-visibility" onclick="togglePasswordVisibility(this)">
                            👁️
                        </button>
                    </div>
                    <small class="help-text">Clave secreta para el backend</small>
                </div>

                <div class="form-group">
                    <label>Webhook Secret</label>
                    <div class="credential-field">
                        <input type="password" name="STRIPE_WEBHOOK_SECRET"
                               value="<?php echo htmlspecialchars($gateway_settings['stripe']['webhook_secret'] ?? ''); ?>"
                               placeholder="whsec_..." class="credential-input">
                        <button type="button" class="toggle-visibility" onclick="togglePasswordVisibility(this)">
                            👁️
                        </button>
                    </div>
                    <small class="help-text">Para verificar eventos webhook</small>
                </div>
            </div>

            <div class="documentation-links">
                <h4>📚 Documentación:</h4>
                <a href="https://dashboard.stripe.com/apikeys" target="_blank">🔑 Obtener API Keys</a>
                <a href="https://dashboard.stripe.com/webhooks" target="_blank">🔗 Configurar Webhooks</a>
                <a href="https://stripe.com/docs" target="_blank">📖 Documentación</a>
            </div>
        </div>

        <!-- PAGGO -->
        <div class="gateway-card paggo">
            <div class="gateway-header">
                <div class="gateway-title">
                    <div class="gateway-icon">🇬🇹</div>
                    <div>
                        <h3>Paggo</h3>
                        <p class="gateway-subtitle">Pagos nacionales - Guatemala (Bancos locales)</p>
                    </div>
                </div>
                <div class="gateway-toggle">
                    <span>Estado:</span>
                    <label class="toggle-switch">
                        <input type="checkbox" name="PAGGO_ENABLED" value="true"
                               <?php echo ($gateway_settings['paggo']['enabled'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>API Key <span class="required">*</span></label>
                    <div class="credential-field">
                        <input type="password" name="PAGGO_API_KEY"
                               value="<?php echo htmlspecialchars($gateway_settings['paggo']['api_key'] ?? ''); ?>"
                               placeholder="pagg_..." class="credential-input">
                        <button type="button" class="toggle-visibility" onclick="togglePasswordVisibility(this)">
                            👁️
                        </button>
                    </div>
                    <small class="help-text">Tu API Key de Paggo</small>
                </div>

                <div class="form-group">
                    <label>Base URL</label>
                    <select name="PAGGO_BASE_URL">
                        <option value="https://api-staging.paggoapp.com"
                                <?php echo ($gateway_settings['paggo']['base_url'] ?? '') === 'https://api-staging.paggoapp.com' ? 'selected' : ''; ?>>
                            Staging (Pruebas)
                        </option>
                        <option value="https://api.paggoapp.com"
                                <?php echo ($gateway_settings['paggo']['base_url'] ?? '') === 'https://api.paggoapp.com' ? 'selected' : ''; ?>>
                            Producción
                        </option>
                    </select>
                    <small class="help-text">Usa Staging para pruebas, Producción para pagos reales</small>
                </div>

                <div class="form-group">
                    <label>Expiración del Link (horas)</label>
                    <input type="number" name="PAGGO_LINK_EXPIRATION_HOURS"
                           value="<?php echo htmlspecialchars($gateway_settings['paggo']['link_expiration_hours'] ?? '48'); ?>"
                           min="1" max="168">
                    <small class="help-text">Validez del link de pago (máximo 7 días)</small>
                </div>
            </div>

            <div class="documentation-links">
                <h4>📚 Documentación:</h4>
                <a href="https://www.paggoapp.com" target="_blank">🏠 Sitio Web</a>
                <a href="https://www.paggoapp.com/login" target="_blank">🔑 Obtener API Key</a>
            </div>
        </div>

        <!-- RECURRENTE -->
        <div class="gateway-card recurrente">
            <div class="gateway-header">
                <div class="gateway-title">
                    <div class="gateway-icon">🌎</div>
                    <div>
                        <h3>Recurrente</h3>
                        <p class="gateway-subtitle">Pagos internacionales (Latinoamérica)</p>
                    </div>
                </div>
                <div class="gateway-toggle">
                    <span>Estado:</span>
                    <label class="toggle-switch">
                        <input type="checkbox" name="RECURRENTE_ENABLED" value="true"
                               <?php echo ($gateway_settings['recurrente']['enabled'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>Public Key <span class="required">*</span></label>
                    <div class="credential-field">
                        <input type="password" name="RECURRENTE_PUBLIC_KEY"
                               value="<?php echo htmlspecialchars($gateway_settings['recurrente']['public_key'] ?? ''); ?>"
                               placeholder="pk_..." class="credential-input">
                        <button type="button" class="toggle-visibility" onclick="togglePasswordVisibility(this)">
                            👁️
                        </button>
                    </div>
                    <small class="help-text">Clave pública</small>
                </div>

                <div class="form-group">
                    <label>Secret Key <span class="required">*</span></label>
                    <div class="credential-field">
                        <input type="password" name="RECURRENTE_SECRET_KEY"
                               value="<?php echo htmlspecialchars($gateway_settings['recurrente']['secret_key'] ?? ''); ?>"
                               placeholder="sk_..." class="credential-input">
                        <button type="button" class="toggle-visibility" onclick="togglePasswordVisibility(this)">
                            👁️
                        </button>
                    </div>
                    <small class="help-text">Clave secreta</small>
                </div>

                <div class="form-group">
                    <label>Webhook Secret</label>
                    <div class="credential-field">
                        <input type="password" name="RECURRENTE_WEBHOOK_SECRET"
                               value="<?php echo htmlspecialchars($gateway_settings['recurrente']['webhook_secret'] ?? ''); ?>"
                               placeholder="whsec_..." class="credential-input">
                        <button type="button" class="toggle-visibility" onclick="togglePasswordVisibility(this)">
                            👁️
                        </button>
                    </div>
                    <small class="help-text">Para verificar webhooks</small>
                </div>

                <div class="form-group">
                    <label>Moneda por Defecto</label>
                    <select name="RECURRENTE_DEFAULT_CURRENCY">
                        <option value="USD" <?php echo ($gateway_settings['recurrente']['default_currency'] ?? 'USD') === 'USD' ? 'selected' : ''; ?>>USD - Dólar Americano</option>
                        <option value="GTQ" <?php echo ($gateway_settings['recurrente']['default_currency'] ?? 'USD') === 'GTQ' ? 'selected' : ''; ?>>GTQ - Quetzal</option>
                        <option value="MXN" <?php echo ($gateway_settings['recurrente']['default_currency'] ?? 'USD') === 'MXN' ? 'selected' : ''; ?>>MXN - Peso Mexicano</option>
                    </select>
                    <small class="help-text">Moneda para transacciones</small>
                </div>

                <div class="form-group">
                    <label>Base URL</label>
                    <input type="text" name="RECURRENTE_BASE_URL"
                           value="<?php echo htmlspecialchars($gateway_settings['recurrente']['base_url'] ?? 'https://app.recurrente.com/api'); ?>"
                           readonly>
                    <small class="help-text">URL de la API (no modificar)</small>
                </div>
            </div>

            <div class="documentation-links">
                <h4>📚 Documentación:</h4>
                <a href="https://app.recurrente.com" target="_blank">🏠 Dashboard</a>
                <a href="https://app.recurrente.com/settings/api" target="_blank">🔑 Obtener API Keys</a>
                <a href="https://docs.recurrente.com" target="_blank">📖 Documentación</a>
            </div>
        </div>

        <!-- RNPL -->
        <div class="gateway-card rnpl">
            <div class="gateway-header">
                <div class="gateway-title">
                    <div class="gateway-icon">🏦</div>
                    <div>
                        <h3>RNPL</h3>
                        <p class="gateway-subtitle">Red Nacional de Pagos de Latinoamérica</p>
                    </div>
                </div>
                <div class="gateway-toggle">
                    <span>Estado:</span>
                    <label class="toggle-switch">
                        <input type="checkbox" name="RNPL_ENABLED" value="true"
                               <?php echo ($gateway_settings['rnpl']['enabled'] ?? 'false') === 'true' ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label>API Key <span class="required">*</span></label>
                    <div class="credential-field">
                        <input type="password" name="RNPL_API_KEY"
                               value="<?php echo htmlspecialchars($gateway_settings['rnpl']['api_key'] ?? ''); ?>"
                               placeholder="rnpl_..." class="credential-input">
                        <button type="button" class="toggle-visibility" onclick="togglePasswordVisibility(this)">
                            👁️
                        </button>
                    </div>
                    <small class="help-text">Tu API Key de RNPL</small>
                </div>
            </div>
        </div>

        <div class="actions-bar">
            <div>
                <a href="?route=admin" style="color: #667eea; text-decoration: none;">← Volver al Dashboard</a>
            </div>
            <button type="submit" class="save-button">
                💾 Guardar Configuración
            </button>
        </div>
    </form>
</div>

<script>
function togglePasswordVisibility(button) {
    const input = button.previousElementSibling;
    if (input.type === 'password') {
        input.type = 'text';
        button.textContent = '🙈';
    } else {
        input.type = 'password';
        button.textContent = '👁️';
    }
}

// Prevenir envío accidental del formulario
document.querySelector('form').addEventListener('submit', function(e) {
    if (!confirm('¿Estás seguro de actualizar la configuración de pagos? Esto afectará todas las transacciones.')) {
        e.preventDefault();
    }
});
</script>

<?php include __DIR__ . '/../../layouts/admin_footer.php'; ?>
