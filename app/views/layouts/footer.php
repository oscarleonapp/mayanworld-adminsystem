<?php
use App\Core\Config;
use App\Core\Helpers;
use App\Helpers\FooterHelper;
use App\Core\Auth;
?>
    </main>

    <!-- Footer Dinámico -->
    <?php
    // Renderizar footer dinámico usando FooterHelper
    echo FooterHelper::renderFooter();
    ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Toast Notifications System -->
    <script src="<?= Helpers::asset('js/toast-notifications.js') ?>"></script>

    <!-- Retry Helper (opcional - solo si se necesita) -->
    <script src="<?= Helpers::asset('js/retry-helper.js') ?>"></script>

    <!-- Custom JS -->
    <script src="<?= Helpers::asset('js/main.js') ?>"></script>

    <!-- Toast Flash Messages disabled for public pages to avoid admin notifications showing up -->
    <?php
    // Flash messages are only shown in admin panel via admin_footer.php
    // This prevents admin notifications from appearing on the public landing page
    ?>

    <!-- Chat features disabled -->
    <?php // require_once __DIR__ . '/../partials/whatsapp_float_button.php'; ?>
    <?php // require_once __DIR__ . '/../components/floating_chat.php'; ?>

    <!-- PWA Service Worker - Handled in main.js -->

    <script>
        // Evita saltos al top por anchors vacíos usados como botones
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href="#"]');
            if (link) {
                e.preventDefault();
            }
        });
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
