<?php
use App\Core\Config;
use App\Core\Helpers;
use App\Helpers\CompanyConfigHelper;
use App\Helpers\NavigationHelper;
use App\Core\Auth;
?>
    </main>

    <!-- Footer -->
    <?php
    // Cargar configuración de la empresa
    $companyName = CompanyConfigHelper::get('company_name', 'Travel Mayan World');
    $companyTagline = CompanyConfigHelper::get('company_tagline', 'Tu agencia de viajes de confianza');
    $contactInfo = CompanyConfigHelper::getContactInfo();
    $socialMedia = CompanyConfigHelper::getSocialMedia();
    ?>
    <footer class="site-footer py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <h5><i class="fas fa-plane me-2"></i><?= htmlspecialchars($companyName) ?></h5>
                    <p class="text-muted"><?= htmlspecialchars($companyTagline) ?></p>
                </div>
                <div class="col-md-3">
                    <h6>Enlaces Útiles</h6>
                    <?php
                    // Renderizar menú footer dinámico desde BD
                    echo NavigationHelper::renderMenu('footer', [
                        'class' => 'list-unstyled',
                        'item_class' => 'mb-2',
                        'link_class' => 'text-muted text-decoration-none',
                        'show_icons' => true,
                        'format' => 'list'
                    ]);
                    ?>
                </div>
                <div class="col-md-3">
                    <h6>Contacto</h6>
                    <?php if (!empty($contactInfo['email'])): ?>
                    <p class="text-muted mb-1">
                        <i class="fas fa-envelope me-2"></i>
                        <a href="<?= CompanyConfigHelper::getEmailLink() ?>" class="text-muted text-decoration-none">
                            <?= htmlspecialchars($contactInfo['email']) ?>
                        </a>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($contactInfo['phone'])): ?>
                    <p class="text-muted mb-1">
                        <i class="fas fa-phone me-2"></i>
                        <a href="<?= CompanyConfigHelper::getPhoneLink() ?>" class="text-muted text-decoration-none">
                            <?= htmlspecialchars($contactInfo['phone']) ?>
                        </a>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($contactInfo['address'])): ?>
                    <p class="text-muted mb-1">
                        <i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($contactInfo['address']) ?>
                    </p>
                    <?php endif; ?>
                    <?php if (!empty($contactInfo['whatsapp'])): ?>
                    <p class="text-muted">
                        <i class="fab fa-whatsapp me-2"></i>
                        <a href="<?= CompanyConfigHelper::getWhatsAppLink('Hola, quiero más información') ?>"
                           target="_blank"
                           class="text-muted text-decoration-none">
                            <?= htmlspecialchars($contactInfo['whatsapp']) ?>
                        </a>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <h6>Síguenos</h6>
                    <div class="d-flex gap-3 footer-social">
                        <?php if (!empty($socialMedia['facebook'])): ?>
                        <a href="<?= htmlspecialchars($socialMedia['facebook']) ?>" target="_blank" aria-label="Facebook">
                            <i class="fab fa-facebook fa-lg"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($socialMedia['instagram'])): ?>
                        <a href="<?= htmlspecialchars($socialMedia['instagram']) ?>" target="_blank" aria-label="Instagram">
                            <i class="fab fa-instagram fa-lg"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($socialMedia['twitter'])): ?>
                        <a href="<?= htmlspecialchars($socialMedia['twitter']) ?>" target="_blank" aria-label="Twitter">
                            <i class="fab fa-twitter fa-lg"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($socialMedia['youtube'])): ?>
                        <a href="<?= htmlspecialchars($socialMedia['youtube']) ?>" target="_blank" aria-label="YouTube">
                            <i class="fab fa-youtube fa-lg"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($contactInfo['whatsapp'])): ?>
                        <a href="<?= CompanyConfigHelper::getWhatsAppLink('Hola, quiero más información') ?>"
                           target="_blank"
                           aria-label="WhatsApp">
                            <i class="fab fa-whatsapp fa-lg"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-muted mb-0">&copy; <?= date('Y') ?> <?= htmlspecialchars($companyName) ?>. Todos los derechos reservados.</p>
                </div>
                <div class="col-md-6 text-md-end footer-links">
                    <a href="<?= Config::getBaseUrl() ?>?route=tours" class="me-3">Destinos</a>
                    <a href="<?= Config::getBaseUrl() ?>?route=privacy" class="me-3">Política de Privacidad</a>
                    <a href="<?= Config::getBaseUrl() ?>?route=terms" class="me-3">Términos y Condiciones</a>
                    <span class="text-muted">|</span>
                    <a href="<?= Config::getBaseUrl() ?>?route=admin/login" class="ms-3 text-muted" style="font-size: 0.85rem;">
                        <i class="fas fa-shield-alt me-1"></i>Admin
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Toast Notifications System -->
    <script src="<?= Helpers::asset('js/toast-notifications.js') ?>"></script>

    <!-- Retry Helper (opcional - solo si se necesita) -->
    <script src="<?= Helpers::asset('js/retry-helper.js') ?>"></script>

    <!-- Custom JS -->
    <script src="<?= Helpers::asset('js/main.js') ?>"></script>

    <!-- Toast Flash Messages (Auto-display PHP flash messages) -->
    <?php require_once __DIR__ . '/../partials/toast_flash_messages.php'; ?>

    <!-- WhatsApp Floating Button -->
    <?php require_once __DIR__ . '/../partials/whatsapp_float_button.php'; ?>

    <!-- PWA Service Worker - Handled in main.js -->
        

    </script>
    
    <!-- Session expired modal -->
    <?php if ($auth->isLoggedIn()): ?>
    <div class="modal fade" id="sessionExpiredModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <div class="modal-body text-center px-4 py-5">
                    <div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#fff3cd,#ffeaa7);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                        <i class="fas fa-clock fa-2x" style="color:#e17055;"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Sesión expirada</h5>
                    <p class="text-muted mb-4" style="font-size:.92rem;">Tu sesión ha expirado por inactividad. Inicia sesión nuevamente para continuar.</p>
                    <a href="<?= Config::getBaseUrl() ?>?route=login" class="btn btn-primary w-100 py-2" style="border-radius:10px;">
                        <i class="fas fa-sign-in-alt me-2"></i>Ir al login
                    </a>
                    <div class="mt-3 text-muted" style="font-size:.8rem;">
                        Redirigiendo en <span id="sessionCountdown">5</span>s...
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Verificar sesión cada 5 minutos
        setInterval(function() {
            fetch('<?= Config::getBaseUrl() ?>?route=check-session')
                .then(response => response.json())
                .then(data => {
                    if (!data.valid || !data.logged_in) {
                        const modal = new bootstrap.Modal(document.getElementById('sessionExpiredModal'));
                        modal.show();
                        let seconds = 5;
                        const countdown = document.getElementById('sessionCountdown');
                        const timer = setInterval(function() {
                            seconds--;
                            if (countdown) countdown.textContent = seconds;
                            if (seconds <= 0) {
                                clearInterval(timer);
                                window.location.href = '<?= Config::getBaseUrl() ?>?route=login';
                            }
                        }, 1000);
                    }
                })
                .catch(error => console.error('Error checking session:', error));
        }, 300000); // 5 minutos
    </script>
    <?php endif; ?>
</body>
</html>