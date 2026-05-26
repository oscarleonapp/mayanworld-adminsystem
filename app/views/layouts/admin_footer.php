<?php
use App\Core\Config;
use App\Core\Helpers;
?>
                </main>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Admin JS -->
    <script src="<?= Helpers::asset('js/admin.js') ?>"></script>
    
    <!-- Toast Container -->
    <div class="toast-container-fixed" id="toast-container" aria-live="polite" aria-atomic="true"></div>
    
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
        
        // Confirm delete actions
        $('.btn-danger[onclick*="delete"]').on('click', function(e) {
            if (!confirm('¿Está seguro de que desea eliminar este elemento?')) {
                e.preventDefault();
                return false;
            }
        });
        
        // Form validation enhancement
        $('form[data-validate="true"]').on('submit', function(e) {
            const form = $(this);
            let valid = true;
            
            // Check required fields
            form.find('[required]').each(function() {
                if (!$(this).val().trim()) {
                    $(this).addClass('is-invalid');
                    valid = false;
                } else {
                    $(this).removeClass('is-invalid').addClass('is-valid');
                }
            });
            
            // Check email format
            form.find('input[type="email"]').each(function() {
                const email = $(this).val();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (email && !emailRegex.test(email)) {
                    $(this).addClass('is-invalid');
                    valid = false;
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Revisa los campos requeridos marcados en rojo.');
            }
        });
        
        // Real-time form validation
        $('input[required], select[required], textarea[required]').on('blur', function() {
            if ($(this).val().trim()) {
                $(this).removeClass('is-invalid').addClass('is-valid');
            } else {
                $(this).removeClass('is-valid').addClass('is-invalid');
            }
        });
        
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Evitar salto por anchors vacíos usados como botones
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[href="#"]');
            if (link) {
                e.preventDefault();
            }
        });
        
        // ===================================
        // NOTIFICATION SYSTEM (Real-time)
        // ===================================
        const NotificationSystem = {
            baseUrl: '<?= Config::getBaseUrl() ?>',
            refreshInterval: null,

            init() {
                this.loadNotifications();
                this.startAutoRefresh();
                this.bindEvents();
            },

            async loadNotifications() {
                try {
                    const response = await fetch(`${this.baseUrl}?route=admin/notifications/get-unread`);
                    const data = await response.json();

                    this.updateCount(data.count || 0);
                    this.renderNotifications(data.notifications || []);
                } catch (error) {
                    console.error('Error loading notifications:', error);
                }
            },

            updateCount(count) {
                const badge = document.getElementById('notificationCount');
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.style.display = 'inline-block';

                    // Animate bell on new notification
                    const bell = document.querySelector('#notificationBell i');
                    bell.classList.add('fa-shake');
                    setTimeout(() => bell.classList.remove('fa-shake'), 500);
                } else {
                    badge.style.display = 'none';
                }
            },

            renderNotifications(notifications) {
                const container = document.getElementById('notificationsList');

                if (notifications.length === 0) {
                    container.innerHTML = `
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-bell-slash fa-3x opacity-25 mb-3"></i>
                            <p class="small mb-0">No tienes notificaciones</p>
                        </div>
                    `;
                    return;
                }

                const prioridadColors = {
                    'urgente': 'danger',
                    'alta': 'warning',
                    'media': 'info',
                    'baja': 'secondary'
                };

                let html = '';
                notifications.forEach(notif => {
                    const color = prioridadColors[notif.prioridad] || 'secondary';
                    const timeAgo = this.getTimeAgo(notif.created_at);

                    html += `
                        <div class="dropdown-item notification-item ${!notif.leida ? 'bg-light' : ''}"
                             data-id="${notif.id}"
                             ${notif.url ? `onclick="location.href='${notif.url}'"` : ''}>
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-2">
                                    <div class="notification-icon-sm bg-${color} bg-opacity-10 rounded-circle p-2">
                                        <i class="${notif.icono} text-${color}"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <strong class="small">${this.escapeHtml(notif.titulo)}</strong>
                                        ${!notif.leida ? '<span class="badge bg-primary badge-sm">Nueva</span>' : ''}
                                    </div>
                                    <p class="small text-muted mb-1">${this.escapeHtml(notif.mensaje)}</p>
                                    <small class="text-muted">
                                        <i class="fas fa-clock me-1"></i>${timeAgo}
                                    </small>
                                </div>
                            </div>
                        </div>
                    `;
                });

                container.innerHTML = html;
            },

            async markAllAsRead() {
                try {
                    const response = await fetch(`${this.baseUrl}?route=admin/notifications/mark-all-read`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>' })
                    });

                    if (response.ok) {
                        this.loadNotifications();
                    }
                } catch (error) {
                    console.error('Error marking as read:', error);
                }
            },

            startAutoRefresh() {
                this.refreshInterval = setInterval(() => {
                    this.loadNotifications();
                }, 30000); // 30 seconds
            },

            stopAutoRefresh() {
                if (this.refreshInterval) {
                    clearInterval(this.refreshInterval);
                }
            },

            bindEvents() {
                const markAllBtn = document.getElementById('markAllReadBtn');
                if (markAllBtn) {
                    markAllBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.markAllAsRead();
                    });
                }
            },

            getTimeAgo(dateString) {
                const now = new Date();
                const past = new Date(dateString);
                const diff = Math.floor((now - past) / 1000);

                if (diff < 60) return 'Hace un momento';
                if (diff < 3600) return `Hace ${Math.floor(diff / 60)} min`;
                if (diff < 86400) return `Hace ${Math.floor(diff / 3600)}h`;
                if (diff < 604800) return `Hace ${Math.floor(diff / 86400)}d`;
                return past.toLocaleDateString('es-GT');
            },

            escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        };

        // Initialize notification system when DOM is ready
        $(document).ready(function() {
            // TODO: Fix notification endpoint before enabling
            // NotificationSystem.init();
        });
        
        // Mobile menu toggle
        $('.navbar-toggler').on('click', function() {
            $('.admin-sidebar').toggleClass('show');
        });
        
        // Close mobile menu when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.admin-sidebar, .navbar-toggler').length) {
                $('.admin-sidebar').removeClass('show');
            }
        });
        
        // Add loading state to buttons
        $('form').on('submit', function() {
            if ($(this).data('noLoading') || $(this).attr('data-no-loading') === 'true') {
                return;
            }
            $(this).find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Procesando...');
        });
        
        // Table row hover effect
        $('.table tbody tr').hover(
            function() { $(this).addClass('table-hover-highlight'); },
            function() { $(this).removeClass('table-hover-highlight'); }
        );
        
        // Initialize DataTables if available
        if (typeof $.fn.DataTable !== 'undefined') {
            $('.data-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                responsive: true,
                pageLength: 25,
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });
        }
        
        // Charts initialization placeholder
        function initCharts() {
            // This would initialize Chart.js charts if present
            console.log('Charts initialized');
        }
        
        // Call chart initialization when document is ready
        $(document).ready(function() {
            if (typeof Chart !== 'undefined') {
                initCharts();
            }
        });

        // ===================================
        // FLASH MESSAGES (Auto-display)
        // ===================================
        <?php
        $flashMessages = Helpers::getFlashMessages();
        if (!empty($flashMessages)):
        ?>
        // Auto-mostrar flash messages cuando AdminUI esté listo
        (function() {
            function showFlashMessages() {
                if (typeof AdminUI === 'undefined' || typeof AdminUI.toast === 'undefined') {
                    // Esperar a que AdminUI esté disponible
                    setTimeout(showFlashMessages, 100);
                    return;
                }

                const messages = <?= json_encode($flashMessages) ?>;

                messages.forEach(function(flash, index) {
                    setTimeout(function() {
                        const type = flash.type;
                        const message = flash.message;

                        // Mapear tipos de PHP a tipos de AdminUI toast
                        const typeMap = {
                            'success': 'success',
                            'error': 'danger',
                            'danger': 'danger',
                            'warning': 'warning',
                            'info': 'info',
                            'primary': 'primary',
                            'secondary': 'secondary'
                        };

                        const toastType = typeMap[type] || 'primary';

                        // Mostrar toast usando AdminUI
                        AdminUI.toast(message, toastType, {
                            autohide: true,
                            delay: type === 'error' || type === 'danger' ? 7000 : 5000
                        });
                    }, index * 300); // 300ms delay entre cada toast
                });
            }

            // Ejecutar cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', showFlashMessages);
            } else {
                showFlashMessages();
            }
        })();
        <?php endif; ?>

        // ===================================
        // SIDEBAR SCROLL PERSISTENCE
        // ===================================
        (function() {
            const sidebar = document.getElementById('adminSidebar');
            if (!sidebar) return;

            // Restore scroll position on page load
            const savedScrollPos = sessionStorage.getItem('adminSidebarScrollPos');
            if (savedScrollPos !== null) {
                sidebar.scrollTop = parseInt(savedScrollPos, 10);
            }

            // Save scroll position before navigating away
            const saveScrollPosition = () => {
                sessionStorage.setItem('adminSidebarScrollPos', sidebar.scrollTop);
            };

            // Save on scroll (debounced)
            let scrollTimeout;
            sidebar.addEventListener('scroll', function() {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(saveScrollPosition, 100);
            });

            // Save before clicking any link
            const sidebarLinks = sidebar.querySelectorAll('a[href]');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', saveScrollPosition);
            });

            // Save before page unload
            window.addEventListener('beforeunload', saveScrollPosition);
        })();

        // ===================================
        // ADMIN FILE DOWNLOAD HELPER
        // ===================================
        window.AdminDownload = function(url, options = {}) {
            const {
                filenameFallback = 'export.csv',
                startMessage = null,
                errorMessage = 'Error al exportar',
                credentials = 'same-origin'
            } = options;

            if (startMessage && typeof AdminUI !== 'undefined' && AdminUI.toast) {
                AdminUI.toast(startMessage, 'info');
            }

            return fetch(url, { credentials })
                .then(async response => {
                    if (!response.ok) {
                        const message = await response.text();
                        throw new Error(message || errorMessage);
                    }
                    const blob = await response.blob();
                    let filename = filenameFallback;
                    const disposition = response.headers.get('Content-Disposition') || response.headers.get('content-disposition');
                    if (disposition) {
                        const match = /filename="?([^"]+)"?/.exec(disposition);
                        if (match && match[1]) {
                            filename = match[1];
                        }
                    }

                    const blobUrl = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = blobUrl;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    setTimeout(() => {
                        window.URL.revokeObjectURL(blobUrl);
                        link.remove();
                    }, 0);
                })
                .catch(error => {
                    console.error('Error exportando archivo:', error);
                    if (typeof AdminUI !== 'undefined' && AdminUI.toast) {
                        AdminUI.toast(errorMessage, 'danger');
                    } else {
                        alert(errorMessage);
                    }
                });
        };
    </script>
    
    
</body>
</html>
