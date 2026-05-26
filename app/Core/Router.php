<?php

namespace App\Core;

class Router
{
    private $routes = [];
    private $defaultController = 'Site';
    private $defaultAction = 'home';
    
    public function __construct()
    {
        // Rutas predefinidas del sistema
        $this->addRoute('', 'Site', 'home');
        $this->addRoute('home', 'Site', 'home');
        // Test route for debugging
        $this->addRoute('test', 'Test', 'index');
        // Páginas informativas y búsqueda
        $this->addRoute('contact', 'Site', 'contact');
        $this->addRoute('help', 'Site', 'help');
        $this->addRoute('faq', 'Site', 'faq');
        $this->addRoute('search', 'Site', 'search');

        // Páginas estáticas dinámicas (gestionadas desde admin)
        $this->addRoute('about', 'StaticPage', 'view');
        $this->addRoute('terms', 'StaticPage', 'view');
        $this->addRoute('privacy', 'StaticPage', 'view');
        $this->addRoute('page/{slug}', 'StaticPage', 'viewBySlug'); // Ruta genérica para páginas estáticas

        // Rutas de traslados
        $this->addRoute('transfers', 'Transfer', 'list');
        $this->addRoute('transfer/{id}', 'Transfer', 'detail');
        $this->addRoute('transfer/quote/{id}', 'Transfer', 'quote');
        $this->addRoute('transfer/search', 'Transfer', 'search');

        // Rutas de tours
        $this->addRoute('tours', 'Tour', 'list');
        $this->addRoute('tour/{id}', 'Tour', 'detail');
        // Alias legacy para detalle
        $this->addRoute('tours/{id}', 'Tour', 'detail');
        $this->addRoute('tours/detail/{id}', 'Tour', 'detail');
        // Endpoints auxiliares (AJAX)
        $this->addRoute('tour/search', 'Tour', 'search');
        $this->addRoute('tour/availability/{tourId}', 'Tour', 'getAvailability');
        $this->addRoute('tour/by-category/{categoryId}', 'Tour', 'getByCategory');
        // Reseñas
        $this->addRoute('reviews', 'Site', 'reviews');
        $this->addRoute('review/submit', 'Review', 'submit');
        $this->addRoute('review/form', 'Review', 'form');
        $this->addRoute('review/vote', 'Review', 'vote');
        $this->addRoute('review/get-tour-reviews', 'Review', 'getTourReviews');
        $this->addRoute('review/expired', 'Review', 'expired');
        
        // Rutas de reservas
        $this->addRoute('booking', 'Booking', 'form');
        $this->addRoute('booking/confirm', 'Booking', 'confirm');
        $this->addRoute('booking/checkout', 'Booking', 'checkout');
        $this->addRoute('booking/checkout-step1', 'Booking', 'checkoutStep1');
        $this->addRoute('booking/checkout-step2', 'Booking', 'checkoutStep2');
        $this->addRoute('booking/process', 'Booking', 'process');
        $this->addRoute('booking/process-payment', 'Booking', 'processPayment');
        $this->addRoute('booking/success', 'Booking', 'success');
        $this->addRoute('booking/confirmation', 'Booking', 'confirmation');
        $this->addRoute('booking/my-bookings', 'Booking', 'myBookings');
        $this->addRoute('booking/find', 'Booking', 'findBooking');

        // Pagos (Stripe Checkout)
        $this->addRoute('payment/checkout/{bookingId}', 'Payment', 'checkout');
        $this->addRoute('payment/success', 'Payment', 'success');
        $this->addRoute('payment/cancel', 'Payment', 'cancel');
        $this->addRoute('payment/webhook', 'Payment', 'webhook');
        $this->addRoute('payment/apple-pay-validate', 'Payment', 'applePayValidate');

        // Nuevas pasarelas: Paggo y Recurrente
        $this->addRoute('payment/process', 'Payment', 'process');
        $this->addRoute('payment/pending/{reservaId}', 'Payment', 'pending');
        $this->addRoute('payment/success-recurrente', 'Payment', 'successRecurrente');

        // Webhook de Recurrente
        $this->addRoute('webhook/recurrente', 'Webhook', 'recurrente');

        // RNPL (Reserve Now Pay Later)
        $this->addRoute('rnpl/process', 'Rnpl', 'processBooking');
        $this->addRoute('rnpl/confirmation/{id}', 'Rnpl', 'confirmation');
        $this->addRoute('rnpl/payment/{id}', 'Rnpl', 'payment');
        $this->addRoute('rnpl/process-payment', 'Rnpl', 'processPayment');
        $this->addRoute('rnpl/info/{tourId}', 'Rnpl', 'getTourRnplInfo');
        $this->addRoute('rnpl/webhook', 'Rnpl', 'stripeWebhook');

        // Rutas de autenticación
        $this->addRoute('login', 'Auth', 'login');
        $this->addRoute('logout', 'Auth', 'logout');
        $this->addRoute('register', 'Auth', 'register');
        $this->addRoute('profile', 'Auth', 'profile');
        $this->addRoute('check-session', 'Auth', 'checkSession');
        $this->addRoute('change-password', 'Auth', 'changePassword');
        $this->addRoute('admin/login', 'Auth', 'loginAdmin');

        // Rutas de panel de clientes
        $this->addRoute('client', 'Client', 'dashboard');
        $this->addRoute('client/dashboard', 'Client', 'dashboard');
        $this->addRoute('client/bookings', 'Client', 'bookings');
        $this->addRoute('client/booking/{id}', 'Client', 'bookingDetail');
        $this->addRoute('client/profile', 'Client', 'profile');
        $this->addRoute('client/cancel-booking/{id}', 'Client', 'cancelBooking');

        // Rutas de administración
        $this->addRoute('admin', 'Admin', 'dashboard');
        $this->addRoute('admin/dashboard', 'Admin', 'dashboard');
        $this->addRoute('admin/tours', 'Admin', 'tours');
        $this->addRoute('admin/tours/create', 'Admin', 'createTour');
        $this->addRoute('admin/tours/edit/{id}', 'Admin', 'editTour');
        $this->addRoute('admin/tours/delete/{id}', 'Admin', 'deleteTour');
        $this->addRoute('admin/tours/delete', 'Admin', 'deleteTour');
        $this->addRoute('admin/tours/bulk-action', 'Admin', 'bulkActionTours');
        $this->addRoute('admin/tours/toggle-verified', 'Admin', 'toggleTourVerified');
        $this->addRoute('admin/tours/toggle-status', 'Admin', 'toggleTourStatus');
        $this->addRoute('admin/tours/toggle-featured', 'Admin', 'toggleTourFeatured');
        $this->addRoute('admin/tours/set-coords', 'Admin', 'setTourCoords');
        $this->addRoute('admin/tours/geocode', 'Admin', 'geocode');

        // Configuración de pasarelas de pago por tour
        $this->addRoute('admin/tours/payment-gateways/{id}', 'Admin', 'tourPaymentGateways');

        // Configuración global de pasarelas de pago (credenciales)
        $this->addRoute('admin/settings/payments', 'Admin', 'paymentSettings');
        $this->addRoute('admin/settings/payments/update', 'Admin', 'updatePaymentSettings');

        // Gestión de disponibilidad (fechas específicas)
        $this->addRoute('admin/availability/list', 'Admin', 'listAvailability');
        $this->addRoute('admin/availability/create', 'Admin', 'createAvailability');
        $this->addRoute('admin/availability/delete', 'Admin', 'deleteAvailability');

        // Gestión de puntos de encuentro (Catálogo Global)
        $this->addRoute('admin/meeting-points', 'MeetingPoint', 'index');
        $this->addRoute('admin/meeting-points/create', 'MeetingPoint', 'create');
        $this->addRoute('admin/meeting-points/edit/{id}', 'MeetingPoint', 'edit');
        $this->addRoute('admin/meeting-points/delete/{id}', 'MeetingPoint', 'delete');
        $this->addRoute('api/meeting-points/list', 'MeetingPoint', 'list'); // JSON: Todos los activos
        $this->addRoute('api/meeting-points/assigned', 'MeetingPoint', 'assigned');
        $this->addRoute('api/meeting-points/assign', 'MeetingPoint', 'assign');
        $this->addRoute('api/meeting-points/detach', 'MeetingPoint', 'detach');

        $this->addRoute('admin/bookings', 'Admin', 'bookings');
        $this->addRoute('admin/bookings/create', 'Admin', 'createBooking');
        $this->addRoute('admin/bookings/update', 'Admin', 'updateBooking');
        $this->addRoute('admin/bookings/export', 'Admin', 'exportBookings');
        $this->addRoute('admin/booking/{id}/details', 'Admin', 'bookingDetails');
        $this->addRoute('admin/booking/{id}/history', 'Admin', 'bookingHistory');
        $this->addRoute('admin/dashboard/chart-data', 'Admin', 'dashboardChartData');
        $this->addRoute('admin/update-booking-status', 'Admin', 'updateBookingStatus');
        $this->addRoute('admin/users', 'Admin', 'users');
        $this->addRoute('admin/messages', 'Admin', 'messages');
        $this->addRoute('admin/message/{id}/details', 'Admin', 'messageDetails');
        $this->addRoute('admin/update-message-status', 'Admin', 'updateMessageStatus');
        $this->addRoute('admin/reply-message', 'Admin', 'replyMessage');
        $this->addRoute('admin/delete-message', 'Admin', 'deleteMessage');
        $this->addRoute('admin/messages/mark-all-read', 'Admin', 'markAllMessagesRead');
        $this->addRoute('admin/reminders/run', 'Admin', 'runReminders');
        $this->addRoute('admin/staff', 'Admin', 'staff');
        $this->addRoute('admin/staff/add', 'Admin', 'addStaff');
        $this->addRoute('admin/staff/types', 'EmployeeType', 'list');
        $this->addRoute('admin/staff/types/create', 'EmployeeType', 'create');
        $this->addRoute('admin/staff/types/edit/{id}', 'EmployeeType', 'edit');
        $this->addRoute('admin/staff/types/delete/{id}', 'EmployeeType', 'delete');
        $this->addRoute('admin/staff/types/toggle', 'EmployeeType', 'toggleActive');
        $this->addRoute('admin/staff/languages', 'Language', 'list');
        $this->addRoute('admin/staff/languages/create', 'Language', 'create');
        $this->addRoute('admin/staff/languages/edit/{id}', 'Language', 'edit');
        $this->addRoute('admin/staff/languages/delete/{id}', 'Language', 'delete');
        $this->addRoute('admin/staff/languages/toggle', 'Language', 'toggleActive');
        $this->addRoute('admin/staff/edit/{id}', 'Admin', 'editStaff');
        $this->addRoute('admin/staff/details/{id}', 'Admin', 'staffDetails');
        $this->addRoute('admin/staff/status', 'Admin', 'toggleStaffStatus');
        $this->addRoute('admin/staff/delete/{id}', 'Admin', 'deleteStaff');
        $this->addRoute('admin/routes', 'Admin', 'busRoutes');
        $this->addRoute('admin/routes/add', 'Admin', 'addRoute');
        $this->addRoute('admin/routes/edit/{id}', 'Admin', 'editRoute');
        $this->addRoute('admin/routes/details/{id}', 'Admin', 'routeDetails');
        $this->addRoute('admin/routes/delete/{id}', 'Admin', 'deleteRoute');
        // Gestión de transportes
        $this->addRoute('admin/transport', 'Admin', 'transport');
        $this->addRoute('admin/transport/add', 'Admin', 'addTransport');
        $this->addRoute('admin/transport/edit/{id}', 'Admin', 'editTransport');
        $this->addRoute('admin/transport/update/{id}', 'Admin', 'updateTransport');
        $this->addRoute('admin/transport/delete', 'Admin', 'deleteTransportPost');
        $this->addRoute('admin/transport/toggle-status', 'Admin', 'toggleTransportStatus');
        $this->addRoute('admin/transport/bulk-action', 'Admin', 'bulkActionTransport');
        $this->addRoute('admin/transport/export', 'Admin', 'exportTransport');
        $this->addRoute('admin/settings', 'Admin', 'settings');
        $this->addRoute('admin/settings/whatsapp', 'Admin', 'whatsappSettings');
        $this->addRoute('admin/settings/whatsapp/save', 'Admin', 'saveWhatsappSettings');
        $this->addRoute('admin/uploadHeroImage', 'Admin', 'uploadHeroImage');
        $this->addRoute('admin/uploadHeroVideo', 'Admin', 'uploadHeroVideo');
        $this->addRoute('admin/saveHeroUrl', 'Admin', 'saveHeroUrl');
        $this->addRoute('admin/profile', 'Admin', 'profile');

        // Moderación de reseñas
        $this->addRoute('admin/reviews', 'Admin', 'reviews');
        $this->addRoute('admin/reviews/create', 'Admin', 'createReview');
        $this->addRoute('admin/reviews/edit/{id}', 'Admin', 'editReview');
        $this->addRoute('admin/reviews/update/{id}', 'Admin', 'updateReview');
        $this->addRoute('admin/reviews/approve/{id}', 'Admin', 'approveReview');
        $this->addRoute('admin/reviews/reject/{id}', 'Admin', 'rejectReview');
        $this->addRoute('admin/reviews/delete/{id}', 'Admin', 'deleteReview');
        $this->addRoute('admin/reports', 'Admin', 'reports');


        // ================================================
        // BLOG SYSTEM CON SEO ENTERPRISE
        // ================================================

        // Blog Frontend (Público)
        $this->addRoute('blog', 'Blog', 'list');
        $this->addRoute('blog/page/{page}', 'Blog', 'list');
        $this->addRoute('blog/buscar', 'Blog', 'search');
        $this->addRoute('blog/categoria/{slug}', 'Blog', 'category');
        $this->addRoute('blog/archivo/{year}/{month}', 'Blog', 'archive');
        $this->addRoute('blog/archivo/{year}', 'Blog', 'archive');
        $this->addRoute('blog/rss', 'Blog', 'rss');
        $this->addRoute('blog/{slug}', 'Blog', 'detail'); // IMPORTANTE: Esta debe ir al final para no capturar otras rutas

        // Blog Admin - Gestión de Posts
        $this->addRoute('admin/blog', 'Blog', 'adminList');
        $this->addRoute('admin/blog/crear', 'Blog', 'adminCreate');
        $this->addRoute('admin/blog/editar/{id}', 'Blog', 'adminEdit');
        $this->addRoute('admin/blog/eliminar/{id}', 'Blog', 'adminDelete');
        $this->addRoute('admin/blog/toggle-status', 'Blog', 'adminToggleStatus');
        $this->addRoute('admin/blog/toggle-featured', 'Blog', 'adminToggleFeatured');
        $this->addRoute('admin/blog/analizar-seo', 'Blog', 'adminAnalyzeSeo');
        $this->addRoute('admin/blog/generar-slug', 'Blog', 'adminGenerateSlug');
        $this->addRoute('admin/blog/subir-imagen', 'Blog', 'adminUploadImage');

        // Blog Admin - Gestión de Categorías
        $this->addRoute('admin/blog/categorias', 'BlogCategory', 'list');
        $this->addRoute('admin/blog/categorias/crear', 'BlogCategory', 'create');
        $this->addRoute('admin/blog/categorias/editar/{id}', 'BlogCategory', 'edit');
        $this->addRoute('admin/blog/categorias/eliminar/{id}', 'BlogCategory', 'delete');
        $this->addRoute('admin/blog/categorias/reordenar', 'BlogCategory', 'reorder');
        $this->addRoute('admin/blog/categorias/toggle-activo', 'BlogCategory', 'toggleActive');
        $this->addRoute('admin/blog/categorias/generar-slug', 'BlogCategory', 'generateSlug');
        $this->addRoute('admin/blog/categorias/stats/{id}', 'BlogCategory', 'stats');

        // SEO Enterprise - Sitemaps XML
        $this->addRoute('sitemap.xml', 'Sitemap', 'index');
        $this->addRoute('sitemap-blog.xml', 'Sitemap', 'blog');
        $this->addRoute('sitemap-blog-categories.xml', 'Sitemap', 'blogCategories');
        $this->addRoute('sitemap-tours.xml', 'Sitemap', 'tours');
        $this->addRoute('sitemap-pages.xml', 'Sitemap', 'pages');

        // Gestión de testimonios/reseñas
        $this->addRoute('admin/testimonials', 'Testimonial', 'index');
        $this->addRoute('admin/testimonials/create', 'Testimonial', 'form');
        $this->addRoute('admin/testimonials/edit/{id}', 'Testimonial', 'form');
        $this->addRoute('admin/testimonials/delete/{id}', 'Testimonial', 'delete');
        $this->addRoute('admin/testimonials/toggle-active/{id}', 'Testimonial', 'toggleActive');
        $this->addRoute('admin/testimonials/toggle-featured/{id}', 'Testimonial', 'toggleFeatured');
        $this->addRoute('admin/testimonials/import-google', 'Testimonial', 'importFromGoogle');

        // Editor de Homepage
        $this->addRoute('admin/homepage-editor', 'HomepageEditor', 'index');
        $this->addRoute('homepage-editor/api-get-sections', 'HomepageEditor', 'apiGetSections');
        $this->addRoute('homepage-editor/api-save-order', 'HomepageEditor', 'apiSaveOrder');
        $this->addRoute('homepage-editor/api-toggle-visibility', 'HomepageEditor', 'apiToggleVisibility');
        $this->addRoute('homepage-editor/api-update-section', 'HomepageEditor', 'apiUpdateSection');
        $this->addRoute('homepage-editor/api-create-section', 'HomepageEditor', 'apiCreateSection');
        $this->addRoute('homepage-editor/api-delete-section', 'HomepageEditor', 'apiDeleteSection');
        $this->addRoute('homepage-editor/api-upload-partner-logo', 'HomepageEditor', 'apiUploadPartnerLogo');
        $this->addRoute('homepage-editor/preview', 'HomepageEditor', 'preview');

        // Gestor de Banners
        $this->addRoute('admin/banners', 'Banner', 'index');
        $this->addRoute('admin/banners/create', 'Banner', 'form');
        $this->addRoute('admin/banners/edit/{id}', 'Banner', 'form');
        $this->addRoute('admin/banners/delete/{id}', 'Banner', 'delete');
        $this->addRoute('banner/api-toggle', 'Banner', 'apiToggle');
        $this->addRoute('banner/api-track-click', 'Banner', 'apiTrackClick');

        // Gestor de Cupones
        $this->addRoute('admin/cupones', 'Cupon', 'index');
        $this->addRoute('admin/cupones/create', 'Cupon', 'form');
        $this->addRoute('admin/cupones/edit/{id}', 'Cupon', 'form');
        $this->addRoute('admin/cupones/delete/{id}', 'Cupon', 'delete');
        $this->addRoute('admin/cupones/estadisticas', 'Cupon', 'estadisticas');
        $this->addRoute('cupon/api-validate', 'Cupon', 'apiValidate');

        // Rutas de wishlist
        $this->addRoute('wishlist', 'Wishlist', 'index');
        $this->addRoute('wishlist/add', 'Wishlist', 'add');
        $this->addRoute('wishlist/remove', 'Wishlist', 'remove');
        $this->addRoute('wishlist/check', 'Wishlist', 'check');
        $this->addRoute('wishlist/share', 'Wishlist', 'share');
        $this->addRoute('wishlist/shared', 'Wishlist', 'shared');
        $this->addRoute('wishlist/createAlert', 'Wishlist', 'createAlert');
        $this->addRoute('wishlist/getNotifications', 'Wishlist', 'getNotifications');
        $this->addRoute('wishlist/markNotificationRead', 'Wishlist', 'markNotificationRead');
        $this->addRoute('wishlist/settings', 'Wishlist', 'settings');
        $this->addRoute('wishlist/explore', 'Wishlist', 'explore');
        $this->addRoute('wishlist/getCount', 'Wishlist', 'getCount');
        $this->addRoute('wishlist/widget', 'Wishlist', 'widget');
        
        // Chat routes commented out - feature disabled
        // $this->addRoute('chat', 'WhatsAppChat', 'index');
        // $this->addRoute('chat/start-web-conversation', 'WhatsAppChat', 'startWebConversation');
        // $this->addRoute('chat/send-message', 'WhatsAppChat', 'sendMessage');
        // $this->addRoute('chat/get-messages', 'WhatsAppChat', 'getMessages');
        // $this->addRoute('chat/mark-read', 'WhatsAppChat', 'markMessagesAsRead');
        // $this->addRoute('chat/webhook', 'WhatsAppChat', 'webhook');
        // $this->addRoute('chat/close-conversation', 'WhatsAppChat', 'closeConversation');
        // $this->addRoute('chat/send', 'Chat', 'send');
        // $this->addRoute('chat/messages', 'Chat', 'messages');
        // $this->addRoute('chat-legacy', 'Chat', 'index');
        // $this->addRoute('chat-legacy/send', 'Chat', 'send_message');
        // $this->addRoute('chat-legacy/messages', 'Chat', 'get_messages');
        // $this->addRoute('admin/chat', 'WhatsAppChat', 'admin');
        // $this->addRoute('admin/chat/dashboard', 'WhatsAppChat', 'admin');
        // $this->addRoute('admin/chat/agent-interface', 'WhatsAppChat', 'agentInterface');
        // $this->addRoute('admin/chat/whatsapp-config', 'WhatsAppChat', 'whatsappConfig');
        // $this->addRoute('admin/chat/assign-conversation', 'WhatsAppChat', 'assignConversation');
        // $this->addRoute('admin/chat/conversations', 'Chat', 'adminConversations');
        // $this->addRoute('admin/chat/api-conversations', 'Chat', 'apiConversations');
        // $this->addRoute('admin/chat/send-message', 'Chat', 'sendMessageAdmin');
        // $this->addRoute('admin/chat/close-conversation', 'Chat', 'closeConversation');
        // $this->addRoute('admin/chat/transfer-conversation', 'WhatsAppChat', 'transferConversation');
        // $this->addRoute('admin/chat/conversation/{id}', 'WhatsAppChat', 'getConversation');
        // $this->addRoute('admin/chat/dashboard-updates', 'WhatsAppChat', 'getDashboardUpdates');
        // $this->addRoute('admin/chat/auto-assign-all', 'WhatsAppChat', 'autoAssignAll');
        // $this->addRoute('admin/chat-legacy', 'Chat', 'admin');
        // $this->addRoute('admin/chat-legacy/conversations', 'Chat', 'conversations');
        // $this->addRoute('admin/chat-legacy/operators', 'Chat', 'operators');
        // $this->addRoute('admin/chat-legacy/conversation', 'Chat', 'conversation');
        // $this->addRoute('admin/chat-legacy/messages', 'Chat', 'messages');
        // $this->addRoute('admin/chat-legacy/send', 'Chat', 'send');
        // $this->addRoute('admin/chat-legacy/new_messages', 'Chat', 'new_messages');
        // $this->addRoute('admin/chat-legacy/mark_read', 'Chat', 'mark_read');
        // $this->addRoute('admin/chat-legacy/close', 'Chat', 'close');
        
        // Sistema de Referidos y Programa de Recompensas
        $this->addRoute('referral', 'Referral', 'dashboard');
        $this->addRoute('referral/dashboard', 'Referral', 'dashboard');
        $this->addRoute('referral/enroll', 'Referral', 'enroll');
        $this->addRoute('referral/share', 'Referral', 'share');
        $this->addRoute('referral/redeem-reward', 'Referral', 'redeemReward');
        $this->addRoute('referral/get-stats', 'Referral', 'getStats');
        $this->addRoute('referral/get-activity', 'Referral', 'getRecentActivity');
        $this->addRoute('referral/generate-promo', 'Referral', 'generatePromoCode');
        $this->addRoute('referral/terms', 'Referral', 'terms');
        $this->addRoute('referral/faq', 'Referral', 'faq');
        $this->addRoute('referral/leaderboard', 'Referral', 'leaderboard');
        
        // Proceso de enlace de referido (público)
        $this->addRoute('ref/{code}', 'Referral', 'processClick');
        $this->addRoute('referral/click/{code}', 'Referral', 'processClick');
        
        // Administración de programa de referidos
        $this->addRoute('admin/referral', 'Referral', 'adminDashboard');
        $this->addRoute('admin/referral/dashboard', 'Referral', 'adminDashboard');
        $this->addRoute('admin/referral/users', 'Referral', 'adminUsers');
        $this->addRoute('admin/referral/payouts', 'Referral', 'adminProcessPayouts');
        $this->addRoute('admin/referral/settings', 'Referral', 'adminSettings');
        
        // Sistema de Internacionalización (i18n) y Múltiples Monedas
        $this->addRoute('i18n/set-language', 'I18n', 'setLanguage');
        $this->addRoute('i18n/set-currency', 'I18n', 'setCurrency');
        $this->addRoute('i18n/get-translations', 'I18n', 'getTranslations');
        $this->addRoute('i18n/convert-price', 'I18n', 'convertPrice');
        $this->addRoute('i18n/language-widget', 'I18n', 'languageWidget');
        $this->addRoute('i18n/update-exchange-rates', 'I18n', 'updateExchangeRates');
        
        // Administración de i18n
        $this->addRoute('admin/i18n', 'I18n', 'admin');
        $this->addRoute('admin/i18n/dashboard', 'I18n', 'admin');
        $this->addRoute('admin/i18n/translations', 'I18n', 'translations');
        $this->addRoute('admin/i18n/tour-translations', 'I18n', 'tourTranslations');
        $this->addRoute('admin/i18n/languages', 'I18n', 'manageLanguages');
        $this->addRoute('admin/i18n/currencies', 'I18n', 'manageCurrencies');

        // ================================================
        // CMS PREMIUM V2 - Nuevas Rutas
        // ================================================

        // Content Blocks (Bloques de Contenido)
        $this->addRoute('admin/content-blocks', 'ContentBlock', 'list');
        $this->addRoute('admin/content-blocks/create', 'ContentBlock', 'create');
        $this->addRoute('admin/content-blocks/edit/{id}', 'ContentBlock', 'edit');
        $this->addRoute('admin/content-blocks/update', 'ContentBlock', 'update');
        $this->addRoute('admin/content-blocks/delete/{id}', 'ContentBlock', 'delete');
        $this->addRoute('admin/content-blocks/toggle', 'ContentBlock', 'toggleActive');
        $this->addRoute('api/content-blocks/{section}', 'ContentBlock', 'getBySection');

        // Categories (Categorías con iconos y colores)
        $this->addRoute('admin/categories', 'Category', 'list');
        $this->addRoute('admin/categories/create', 'Category', 'create');
        $this->addRoute('admin/categories/edit/{id}', 'Category', 'edit');
        $this->addRoute('admin/categories/delete/{id}', 'Category', 'delete');
        $this->addRoute('admin/categories/update-order', 'Category', 'updateOrder');
        $this->addRoute('admin/categories/toggle', 'Category', 'toggleActive');
        $this->addRoute('api/categories/generate-slug', 'Category', 'generateSlug');

        // Static Pages (Páginas Estáticas)
        $this->addRoute('admin/pages', 'StaticPage', 'list');
        $this->addRoute('admin/pages/create', 'StaticPage', 'create');
        $this->addRoute('admin/pages/edit/{id}', 'StaticPage', 'edit');
        $this->addRoute('admin/pages/delete/{id}', 'StaticPage', 'delete');
        $this->addRoute('admin/pages/preview/{id}', 'StaticPage', 'preview');
        $this->addRoute('admin/pages/duplicate/{id}', 'StaticPage', 'duplicate');
        $this->addRoute('admin/pages/toggle', 'StaticPage', 'toggleActive');
        $this->addRoute('api/pages/generate-slug', 'StaticPage', 'generateSlug');
        $this->addRoute('page/{slug}', 'StaticPage', 'viewPage'); // Frontend público

        // Company Config (Configuración de Empresa)
        // Configuración de empresa
        $this->addRoute('admin/company-config', 'CompanyConfig', 'index');
        $this->addRoute('admin/company-config/update', 'CompanyConfig', 'update');
        $this->addRoute('admin/company-config/upload-logo', 'CompanyConfig', 'uploadLogo');
        $this->addRoute('admin/company-config/create', 'CompanyConfig', 'createConfig');
        $this->addRoute('admin/company-config/delete/{id}', 'CompanyConfig', 'deleteConfig');

        // Gestión de Menús de Navegación
        $this->addRoute('admin/navigation', 'Navigation', 'index');
        $this->addRoute('admin/navigation/edit/{menuId}', 'Navigation', 'edit');
        $this->addRoute('admin/navigation/add-item', 'Navigation', 'addItem');
        $this->addRoute('admin/navigation/update-item', 'Navigation', 'updateItem');
        $this->addRoute('admin/navigation/delete-item', 'Navigation', 'deleteItem');
        $this->addRoute('admin/navigation/reorder', 'Navigation', 'reorder');
        $this->addRoute('admin/navigation/toggle-visible', 'Navigation', 'toggleVisible');
        $this->addRoute('admin/navigation/get-item', 'Navigation', 'getItem');
        $this->addRoute('admin/navigation/clone-menu', 'Navigation', 'cloneMenu');

        // Editor de Footer
        $this->addRoute('admin/footer', 'Footer', 'index');
        $this->addRoute('admin/footer/add-section', 'Footer', 'addSection');
        $this->addRoute('admin/footer/update-section', 'Footer', 'updateSection');
        $this->addRoute('admin/footer/delete-section', 'Footer', 'deleteSection');
        $this->addRoute('admin/footer/reorder', 'Footer', 'reorder');
        $this->addRoute('admin/footer/move-section', 'Footer', 'moveSection');
        $this->addRoute('admin/footer/toggle-visible', 'Footer', 'toggleVisible');
        $this->addRoute('admin/footer/get-section', 'Footer', 'getSection');
        $this->addRoute('admin/footer/duplicate-section', 'Footer', 'duplicateSection');
        $this->addRoute('admin/footer/update-config', 'Footer', 'updateConfig');

        // Media Library (Biblioteca de Medios)
        $this->addRoute('admin/media', 'Media', 'index');
        $this->addRoute('admin/media/upload', 'Media', 'upload');
        $this->addRoute('admin/media/get-file', 'Media', 'getFile');
        $this->addRoute('admin/media/update-metadata', 'Media', 'updateMetadata');
        $this->addRoute('admin/media/delete', 'Media', 'delete');
        $this->addRoute('admin/media/bulk-delete', 'Media', 'bulkDelete');
        $this->addRoute('admin/media/search', 'Media', 'search');
        $this->addRoute('admin/media/get-folders', 'Media', 'getFolders');
        $this->addRoute('admin/media/get-stats', 'Media', 'getStats');
        $this->addRoute('admin/media/picker', 'Media', 'picker');
        $this->addRoute('admin/media/get-url', 'Media', 'getUrl');
        $this->addRoute('admin/company-config/reset', 'CompanyConfig', 'resetToDefaults');
        $this->addRoute('api/config/{key}', 'CompanyConfig', 'getConfig');
        $this->addRoute('api/config/group/{group}', 'CompanyConfig', 'getConfigsByGroup');
        $this->addRoute('api/config/all', 'CompanyConfig', 'getAllPublicConfigs');

        // FAQs (Preguntas Frecuentes)
        $this->addRoute('admin/faqs', 'FAQ', 'list');
        $this->addRoute('admin/faqs/create', 'FAQ', 'create');
        $this->addRoute('admin/faqs/store', 'FAQ', 'store');
        $this->addRoute('admin/faqs/edit/{id}', 'FAQ', 'edit');
        $this->addRoute('admin/faqs/update/{id}', 'FAQ', 'update');
        $this->addRoute('admin/faqs/delete/{id}', 'FAQ', 'delete');
        $this->addRoute('admin/faqs/update-order', 'FAQ', 'updateOrder');
        $this->addRoute('admin/faqs/toggle', 'FAQ', 'toggleActive');
        $this->addRoute('api/faqs/public', 'FAQ', 'getPublicFAQs'); // Frontend

        // Notificaciones
        $this->addRoute('admin/notifications', 'Notification', 'index');
        $this->addRoute('admin/notifications/mark-read/{id}', 'Notification', 'markAsRead');
        $this->addRoute('admin/notifications/mark-all-read', 'Notification', 'markAllAsRead');
        $this->addRoute('admin/notifications/delete/{id}', 'Notification', 'delete');
        $this->addRoute('api/notifications/unread', 'Notification', 'getUnread');
        $this->addRoute('api/notifications/count', 'Notification', 'countUnread');

        // Audit Log
        $this->addRoute('admin/audit', 'Admin', 'audit'); // Nueva ruta simplificada
        $this->addRoute('admin/audit-log', 'AuditLog', 'index');
        $this->addRoute('admin/audit-log/view/{id}', 'AuditLog', 'viewLog');
        $this->addRoute('admin/audit-log/export-csv', 'AuditLog', 'exportCSV');
        $this->addRoute('admin/audit-log/clean', 'AuditLog', 'clean');
        $this->addRoute('admin/audit-log/search', 'AuditLog', 'search');
        $this->addRoute('admin/audit-log/user/{id}', 'AuditLog', 'userActivity');
        $this->addRoute('api/audit-log/stats', 'AuditLog', 'getStats');
    }
    
    public function addRoute($route, $controller, $action)
    {
        $this->routes[$route] = [
            'controller' => $controller,
            'action' => $action
        ];
    }
    
    public function dispatch()
    {
        $route = $this->getRoute();
        $params = [];
        
        // Buscar ruta exacta primero
        if (isset($this->routes[$route])) {
            $controller = $this->routes[$route]['controller'];
            $action = $this->routes[$route]['action'];
        } else {
            // Buscar rutas con parámetros
            $match = $this->matchRoute($route);
            if ($match) {
                $controller = $match['controller'];
                $action = $match['action'];
                $params = $match['params'];
            } else {
                // Usar controlador y acción por defecto
                $controller = $this->defaultController;
                $action = $this->defaultAction;
            }
        }
        
        $this->loadController($controller, $action, $params);
    }
    
    private function getRoute()
    {
        $route = $_GET['route'] ?? '';
        $route = trim($route, '/');
        return $route;
    }
    
    private function matchRoute($currentRoute)
    {
        foreach ($this->routes as $route => $config) {
            // Convertir rutas con parámetros a regex
            // Usar [\w-]+ para capturar letras, números, guiones bajos Y guiones normales
            $pattern = preg_replace('/\{(\w+)\}/', '([\w-]+)', $route);
            $pattern = str_replace('/', '\/', $pattern);
            $pattern = '/^' . $pattern . '$/';

            if (preg_match($pattern, $currentRoute, $matches)) {
                // Extraer nombres de parámetros
                preg_match_all('/\{(\w+)\}/', $route, $paramNames);

                $params = [];
                for ($i = 1; $i < count($matches); $i++) {
                    if (isset($paramNames[1][$i-1])) {
                        $params[$paramNames[1][$i-1]] = $matches[$i];
                    }
                }

                return [
                    'controller' => $config['controller'],
                    'action' => $config['action'],
                    'params' => $params
                ];
            }
        }

        return null;
    }
    
    private function loadController($controllerName, $action, $params = [])
    {
        $controllerClass = 'App\\Controllers\\' . $controllerName . 'Controller';
        
        if (!class_exists($controllerClass)) {
            $this->show404("Controlador no encontrado: {$controllerName}");
            return;
        }
        
        $controller = new $controllerClass();
        
        if (!method_exists($controller, $action)) {
            $this->show404("Acción no encontrada: {$action}");
            return;
        }
        
        // Llamar al método con parámetros
        if (!empty($params)) {
            call_user_func_array([$controller, $action], array_values($params));
        } else {
            $controller->$action();
        }
    }
    
    private function show404($message = "Página no encontrada")
    {
        http_response_code(404);
        echo "<h1>Error 404</h1>";
        echo "<p>{$message}</p>";
        
        if (Config::isDevelopment()) {
            echo "<hr>";
            echo "<h3>Información de depuración:</h3>";
            echo "<p><strong>Ruta solicitada:</strong> " . $this->getRoute() . "</p>";
            echo "<p><strong>Rutas disponibles:</strong></p>";
            echo "<ul>";
            foreach ($this->routes as $route => $config) {
                echo "<li>{$route} → {$config['controller']}Controller::{$config['action']}</li>";
            }
            echo "</ul>";
        }
    }
    
    public function url($route, $params = [])
    {
        $url = Config::getBaseUrl();
        
        if (!empty($route)) {
            // Reemplazar parámetros en la ruta
            foreach ($params as $key => $value) {
                $route = str_replace('{' . $key . '}', $value, $route);
            }
            $url .= '?route=' . $route;
        }
        
        return $url;
    }
    
    public function redirect($route, $params = [])
    {
        $url = $this->url($route, $params);
        header("Location: {$url}");
        exit();
    }
}
