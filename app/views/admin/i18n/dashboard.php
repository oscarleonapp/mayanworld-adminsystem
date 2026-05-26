<?php
use App\Core\Config;
use App\Models\I18n;

$currentLanguage = I18n::getCurrentLanguage();
$currentCurrency = I18n::getCurrentCurrency();
$pageTitle = 'Gestión i18n';
include __DIR__ . '/../../layouts/admin_header.php';
?>

<div class="container-fluid py-4">
    <?php
        $actionTitle = I18n::t('admin.i18n_management');
        $actionSubtitle = I18n::t('admin.i18n_description');
        $actionButtons = [
            ['label' => I18n::t('admin.update_exchange_rates'), 'icon' => 'fas fa-sync-alt', 'variant' => 'outline-primary', 'id' => 'updateRatesBtn'],
            ['label' => I18n::t('admin.manage_translations'), 'icon' => 'fas fa-language', 'variant' => 'primary', 'href' => Config::getBaseUrl() . '?route=admin/i18n/translations'],
        ];
        include __DIR__ . '/../../partials/admin_action_bar.php';
    ?>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="fas fa-language text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?= I18n::t('admin.total_languages') ?></h6>
                            <h4 class="mb-0 text-primary"><?= $stats['total_languages'] ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="fas fa-coins text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?= I18n::t('admin.total_currencies') ?></h6>
                            <h4 class="mb-0 text-success"><?= $stats['total_currencies'] ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="fas fa-text-width text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?= I18n::t('admin.total_translations') ?></h6>
                            <h4 class="mb-0 text-info"><?= number_format($stats['total_translations']) ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="fas fa-shopping-bag text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?= I18n::t('admin.product_translations') ?></h6>
                            <h4 class="mb-0 text-warning"><?= $stats['tour_translations'] ?></h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Idiomas Activos -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= I18n::t('admin.active_languages') ?></h5>
                        <button type="button" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>
                            <?= I18n::t('admin.add_language') ?>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><?= I18n::t('admin.language') ?></th>
                                    <th><?= I18n::t('admin.code') ?></th>
                                    <th><?= I18n::t('admin.default') ?></th>
                                    <th><?= I18n::t('admin.status') ?></th>
                                    <th width="80"><?= I18n::t('admin.actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($languages as $language): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="me-2"><?= $language['flag_emoji'] ?></span>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($language['native_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($language['name']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= strtoupper($language['code']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($language['is_default']): ?>
                                            <span class="badge bg-primary"><?= I18n::t('admin.default') ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($language['is_active']): ?>
                                            <span class="badge bg-success"><?= I18n::t('admin.active') ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?= I18n::t('admin.inactive') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item disabled" href="#" tabindex="-1" aria-disabled="true"><i class="fas fa-edit me-2"></i><?= I18n::t('admin.edit') ?></a></li>
                                            <li><a class="dropdown-item" href="<?= Config::getBaseUrl() ?>?route=admin/i18n/translations&language_id=<?= $language['id'] ?>"><i class="fas fa-language me-2"></i><?= I18n::t('admin.translations') ?></a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger disabled" href="#" tabindex="-1" aria-disabled="true"><i class="fas fa-trash me-2"></i><?= I18n::t('admin.delete') ?></a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monedas Activas -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-bottom-0 pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?= I18n::t('admin.active_currencies') ?></h5>
                        <button type="button" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-plus me-1"></i>
                            <?= I18n::t('admin.add_currency') ?>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th><?= I18n::t('admin.currency') ?></th>
                                    <th><?= I18n::t('admin.rate') ?></th>
                                    <th><?= I18n::t('admin.default') ?></th>
                                    <th><?= I18n::t('admin.status') ?></th>
                                    <th width="80"><?= I18n::t('admin.actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($currencies as $currency): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="fw-bold me-2 text-success"><?= htmlspecialchars($currency['symbol']) ?></span>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($currency['name']) ?></div>
                                                <small class="text-muted"><?= $currency['code'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="font-monospace"><?= number_format($currency['exchange_rate'], 4) ?></span>
                                        <br>
                                        <small class="text-muted"><?= I18n::formatDate($currency['last_updated']) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($currency['is_default']): ?>
                                            <span class="badge bg-primary"><?= I18n::t('admin.default') ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($currency['is_active']): ?>
                                            <span class="badge bg-success"><?= I18n::t('admin.active') ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><?= I18n::t('admin.inactive') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item disabled" href="#" tabindex="-1" aria-disabled="true"><i class="fas fa-edit me-2"></i><?= I18n::t('admin.edit') ?></a></li>
                                                <li><a class="dropdown-item disabled" href="#" tabindex="-1" aria-disabled="true"><i class="fas fa-chart-line me-2"></i><?= I18n::t('admin.rate_history') ?></a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger disabled" href="#" tabindex="-1" aria-disabled="true"><i class="fas fa-trash me-2"></i><?= I18n::t('admin.delete') ?></a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent">
                    <h5 class="mb-0"><?= I18n::t('admin.quick_actions') ?></h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="d-grid">
                                <a href="<?= Config::getBaseUrl() ?>?route=admin/i18n/translations" class="btn btn-outline-primary">
                                    <i class="fas fa-language me-2"></i>
                                    <?= I18n::t('admin.manage_translations') ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-grid">
                                <a href="<?= Config::getBaseUrl() ?>?route=admin/i18n/tour-translations" class="btn btn-outline-info">
                                    <i class="fas fa-shopping-bag me-2"></i>
                                    <?= I18n::t('admin.product_translations') ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-grid">
                                <button type="button" class="btn btn-outline-success" id="exportTranslationsBtn">
                                    <i class="fas fa-download me-2"></i>
                                    <?= I18n::t('admin.export_translations') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CSS específico -->
<style>
.card {
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.table tbody tr:hover {
    background-color: rgba(var(--bs-primary-rgb), 0.05);
}

.font-monospace {
    font-family: 'JetBrains Mono', 'Fira Code', Consolas, 'Courier New', monospace !important;
}

.badge {
    font-size: 0.75rem;
}

.bg-opacity-10 {
    --bs-bg-opacity: 0.1;
}
</style>

<!-- JavaScript -->
<script>
const baseUrl = '<?= Config::getBaseUrl() ?>';

document.addEventListener('DOMContentLoaded', function() {
    // Actualizar tasas de cambio
    document.getElementById('updateRatesBtn').addEventListener('click', function() {
        const btn = this;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i><?= I18n::t("admin.updating") ?>';
        
        fetch(`${baseUrl}?route=i18n/update-exchange-rates`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification(data.message, 'success');
                // Recargar la página después de 2 segundos
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            showNotification('<?= I18n::t("admin.connection_error") ?>', 'error');
            console.error('Error:', error);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });
    
    // Exportar traducciones
    document.getElementById('exportTranslationsBtn').addEventListener('click', function() {
        // Implementar exportación
        showNotification('<?= I18n::t("admin.export_feature_coming_soon") ?>', 'info');
    });
    
    function showNotification(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
            toastContainer.style.zIndex = '9999';
            document.body.appendChild(toastContainer);
        }
        
        toastContainer.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: type === 'error' ? 5000 : 3000
        });
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
});
</script>

<?php include __DIR__ . '/../../layouts/admin_footer.php'; ?>
