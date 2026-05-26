<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Core\Config;
use App\Models\Wishlist;
use App\Models\Tour;
use Exception;

class WishlistController extends BaseController
{
    private $wishlistModel;
    private $tourModel;

    public function __construct()
    {
        parent::__construct();
        $this->wishlistModel = new Wishlist();
        $this->tourModel = new Tour();
    }

    /**
     * Mostrar wishlist del usuario
     */
    public function index()
    {
        $userEmail = $_SESSION['user_email'] ?? null;
        $userName = $_SESSION['user_name'] ?? null;
        $sessionId = session_id();

        // Obtener o crear wishlist
        $wishlist = $this->wishlistModel->getOrCreateUserWishlist($userEmail, $userName, $sessionId);
        
        if (!$wishlist) {
            $this->redirect('tours', 'Error accediendo a tu lista de deseos', 'error');
            return;
        }

        // Obtener tours de la wishlist
        $tours = $this->wishlistModel->getWishlistTours($wishlist['id']);

        // Obtener notificaciones pendientes
        $notifications = [];
        if ($userEmail) {
            $notifications = $this->wishlistModel->getPendingNotifications($userEmail, 5);
        }

        // Tours similares recomendados
        $recommendations = [];
        if (!empty($tours)) {
            $tourIds = array_column($tours, 'tour_id');
            $recommendations = $this->getSimilarTours($tourIds);
        }

        $this->view('wishlist/index', [
            'title' => 'Mi Lista de Deseos',
            'wishlist' => $wishlist,
            'tours' => $tours,
            'notifications' => $notifications,
            'recommendations' => $recommendations,
            'total_items' => count($tours),
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }

    /**
     * Agregar tour a wishlist (AJAX)
     */
    public function add()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $tourId = (int)$this->getInput('tour_id');
        $notes = $this->getInput('notes');
        $priority = $this->getInput('priority', 'media');

        if (!$tourId) {
            $this->json(['success' => false, 'error' => 'ID de tour requerido']);
            return;
        }

        // Obtener información del usuario
        $userEmail = $_SESSION['user_email'] ?? null;
        $userName = $_SESSION['user_name'] ?? null;
        $sessionId = session_id();

        // Obtener o crear wishlist
        $wishlist = $this->wishlistModel->getOrCreateUserWishlist($userEmail, $userName, $sessionId);
        
        if (!$wishlist) {
            $this->json(['success' => false, 'error' => 'Error creando lista de deseos']);
            return;
        }

        // Agregar tour
        $result = $this->wishlistModel->addTour($wishlist['id'], $tourId, $notes, $priority);

        // Agregar información adicional para la respuesta
        if ($result['success']) {
            $tour = $this->tourModel->find($tourId);
            $result['tour'] = [
                'id' => $tour['id'],
                'nombre' => $tour['nombre'],
                'imagen' => $tour['imagen_principal']
            ];
            $result['wishlist_count'] = count($this->wishlistModel->getWishlistTours($wishlist['id']));
        }

        $this->json($result);
    }

    /**
     * Remover tour de wishlist (AJAX)
     */
    public function remove()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $tourId = (int)$this->getInput('tour_id');

        if (!$tourId) {
            $this->json(['success' => false, 'error' => 'ID de tour requerido']);
            return;
        }

        // Obtener wishlist del usuario
        $userEmail = $_SESSION['user_email'] ?? null;
        $sessionId = session_id();
        $wishlist = $this->wishlistModel->getOrCreateUserWishlist($userEmail, null, $sessionId);

        if (!$wishlist) {
            $this->json(['success' => false, 'error' => 'Lista de deseos no encontrada']);
            return;
        }

        $result = $this->wishlistModel->removeTour($wishlist['id'], $tourId);
        
        if ($result['success']) {
            $result['wishlist_count'] = count($this->wishlistModel->getWishlistTours($wishlist['id']));
        }

        $this->json($result);
    }

    /**
     * Verificar si un tour está en la wishlist (AJAX)
     */
    public function check()
    {
        $tourId = (int)$this->getInput('tour_id');
        
        if (!$tourId) {
            $this->json(['in_wishlist' => false]);
            return;
        }

        $userEmail = $_SESSION['user_email'] ?? null;
        $sessionId = session_id();

        $isInWishlist = $this->wishlistModel->isTourInWishlist($tourId, $userEmail, $sessionId);

        $this->json(['in_wishlist' => $isInWishlist]);
    }

    /**
     * Compartir wishlist
     */
    public function share()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $userEmail = $_SESSION['user_email'] ?? null;
        $sessionId = session_id();
        $wishlist = $this->wishlistModel->getOrCreateUserWishlist($userEmail, null, $sessionId);

        if (!$wishlist) {
            $this->json(['success' => false, 'error' => 'Lista de deseos no encontrada']);
            return;
        }

        $expiresInDays = (int)$this->getInput('expires_in_days', 30);
        $result = $this->wishlistModel->generateShareToken($wishlist['id'], $expiresInDays);

        $this->json($result);
    }

    /**
     * Ver wishlist compartida
     */
    public function shared()
    {
        $token = $this->getInput('token');
        
        if (!$token) {
            $this->redirect('tours', 'Token de compartir requerido', 'error');
            return;
        }

        $wishlist = $this->wishlistModel->getByShareToken($token);
        
        if (!$wishlist) {
            $this->view('wishlist/expired', [
                'title' => 'Lista No Disponible',
                'message' => 'Esta lista de deseos no está disponible o ha expirado'
            ]);
            return;
        }

        // Obtener tours de la wishlist
        $tours = $this->wishlistModel->getWishlistTours($wishlist['id']);

        $this->view('wishlist/shared', [
            'title' => htmlspecialchars($wishlist['nombre']),
            'wishlist' => $wishlist,
            'tours' => $tours,
            'total_items' => count($tours),
            'is_shared_view' => true
        ]);
    }

    /**
     * Crear alerta de precio
     */
    public function createAlert()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $this->validateCsrf();

        $itemId = (int)$this->getInput('item_id');
        $targetPrice = (float)$this->getInput('target_price');
        $alertType = $this->getInput('alert_type', 'precio_menor');
        $discountPercentage = $this->getInput('discount_percentage');

        if (!$itemId || !$targetPrice) {
            $this->json(['success' => false, 'error' => 'Datos incompletos']);
            return;
        }

        $result = $this->wishlistModel->createPriceAlert(
            $itemId, 
            $targetPrice, 
            $alertType, 
            $discountPercentage ? (float)$discountPercentage : null
        );

        $this->json($result);
    }

    /**
     * Obtener notificaciones (AJAX)
     */
    public function getNotifications()
    {
        $userEmail = $_SESSION['user_email'] ?? null;
        
        if (!$userEmail) {
            $this->json(['notifications' => []]);
            return;
        }

        $notifications = $this->wishlistModel->getPendingNotifications($userEmail, 20);
        $this->json(['notifications' => $notifications]);
    }

    /**
     * Marcar notificación como leída (AJAX)
     */
    public function markNotificationRead()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $notificationId = (int)$this->getInput('notification_id');
        
        if (!$notificationId) {
            $this->json(['success' => false, 'error' => 'ID de notificación requerido']);
            return;
        }

        $success = $this->wishlistModel->markNotificationAsRead($notificationId);
        $this->json(['success' => $success]);
    }

    /**
     * Configurar notificaciones
     */
    public function settings()
    {
        $userEmail = $_SESSION['user_email'] ?? null;
        $sessionId = session_id();
        $wishlist = $this->wishlistModel->getOrCreateUserWishlist($userEmail, null, $sessionId);

        if (!$wishlist) {
            $this->redirect('wishlist', 'Error accediendo a configuración', 'error');
            return;
        }

        // Si es POST, actualizar configuración
        if (Helpers::isPost()) {
            $this->validateCsrf();

            $settings = [
                'notificar_cambios_precio' => $this->getInput('notify_price_changes') === '1',
                'notificar_disponibilidad' => $this->getInput('notify_availability') === '1',
                'notificar_ofertas_especiales' => $this->getInput('notify_special_offers') === '1'
            ];

            $result = $this->wishlistModel->updateNotificationSettings($wishlist['id'], $settings);

            if ($result['success']) {
                Helpers::setFlashMessage('success', $result['message']);
            } else {
                Helpers::setFlashMessage('error', $result['error']);
            }

            $this->redirect('wishlist/settings');
            return;
        }

        $this->view('wishlist/settings', [
            'title' => 'Configuración de Lista de Deseos',
            'wishlist' => $wishlist,
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }

    /**
     * Ver wishlists públicas populares
     */
    public function explore()
    {
        $popularWishlists = $this->wishlistModel->getPopularPublicWishlists(20);

        $this->view('wishlist/explore', [
            'title' => 'Explorar Listas de Deseos',
            'wishlists' => $popularWishlists
        ]);
    }

    /**
     * API para obtener conteo de wishlist (AJAX)
     */
    public function getCount()
    {
        $userEmail = $_SESSION['user_email'] ?? null;
        $sessionId = session_id();
        
        $wishlist = $this->wishlistModel->getOrCreateUserWishlist($userEmail, null, $sessionId);
        $count = 0;
        
        if ($wishlist) {
            $tours = $this->wishlistModel->getWishlistTours($wishlist['id']);
            $count = count($tours);
        }

        $this->json(['count' => $count]);
    }

    /**
     * Obtener tours similares para recomendaciones
     */
    private function getSimilarTours($tourIds, $limit = 4)
    {
        if (empty($tourIds)) return [];

        $recommendations = [];
        
        // Obtener tours similares de cada tour en la wishlist
        foreach (array_slice($tourIds, 0, 3) as $tourId) {
            $similar = $this->wishlistModel->getSimilarWishlistTours($tourId, 2);
            $recommendations = array_merge($recommendations, $similar);
        }

        // Remover duplicados y tours ya en wishlist
        $uniqueRecommendations = [];
        $seenIds = array_flip($tourIds);
        
        foreach ($recommendations as $tour) {
            if (!isset($seenIds[$tour['id']]) && count($uniqueRecommendations) < $limit) {
                $uniqueRecommendations[] = $tour;
                $seenIds[$tour['id']] = true;
            }
        }

        return $uniqueRecommendations;
    }

    /**
     * Widget de wishlist para incluir en otras páginas
     */
    public function widget()
    {
        $userEmail = $_SESSION['user_email'] ?? null;
        $sessionId = session_id();
        
        $wishlist = $this->wishlistModel->getOrCreateUserWishlist($userEmail, null, $sessionId);
        $tours = [];
        $count = 0;
        
        if ($wishlist) {
            $tours = $this->wishlistModel->getWishlistTours($wishlist['id'], 3); // Solo 3 para widget
            $count = count($this->wishlistModel->getWishlistTours($wishlist['id']));
        }

        // Respuesta para incluir como widget
        if (Helpers::isAjax()) {
            $this->json([
                'count' => $count,
                'tours' => $tours,
                'wishlist_id' => $wishlist['id'] ?? null
            ]);
        } else {
            $this->view('wishlist/widget', [
                'tours' => $tours,
                'count' => $count,
                'wishlist' => $wishlist
            ]);
        }
    }
}
