<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\Helpers;
use App\Core\Config;
use App\Models\Booking;
use App\Models\Tour;
use Exception;

class ClientController extends BaseController
{
    private $bookingModel;
    private $tourModel;

    public function __construct()
    {
        parent::__construct();

        // Requiere autenticación de cliente
        if (!$this->auth->isLoggedIn()) {
            Helpers::setFlashMessage('error', 'Debes iniciar sesión para acceder');
            Helpers::redirect(Config::getBaseUrl() . '?route=login');
        }

        $this->bookingModel = new Booking();
        $this->tourModel = new Tour();
    }

    /**
     * Dashboard principal del cliente
     */
    public function dashboard()
    {
        $user = $this->auth->getCurrentUser();

        // Obtener reservas del cliente
        $bookings = $this->bookingModel->getByClientEmail($user['email']);

        // Estadísticas del cliente
        $stats = [
            'total_bookings' => count($bookings),
            'pending_bookings' => 0,
            'confirmed_bookings' => 0,
            'completed_bookings' => 0,
            'cancelled_bookings' => 0,
        ];

        foreach ($bookings as $booking) {
            switch ($booking['estado']) {
                case 'pendiente':
                    $stats['pending_bookings']++;
                    break;
                case 'confirmada':
                case 'pagada':
                    $stats['confirmed_bookings']++;
                    break;
                case 'completada':
                    $stats['completed_bookings']++;
                    break;
                case 'cancelada':
                    $stats['cancelled_bookings']++;
                    break;
            }
        }

        // Próximas reservas (confirmadas y futuras)
        $upcomingBookings = array_filter($bookings, function($booking) {
            return in_array($booking['estado'], ['confirmada', 'pagada', 'pendiente'])
                && strtotime($booking['fecha_salida']) >= time();
        });

        // Ordenar por fecha más cercana
        usort($upcomingBookings, function($a, $b) {
            return strtotime($a['fecha_salida']) - strtotime($b['fecha_salida']);
        });

        // Tours destacados
        $featuredProducts = $this->tourModel->findAll([
            'destacado' => 1,
            'activo' => 1
        ], null, 6);

        $this->view('client/dashboard', [
            'title' => 'Mi Panel - ' . $user['nombre'],
            'user' => $user,
            'bookings' => $bookings,
            'upcomingBookings' => array_slice($upcomingBookings, 0, 3),
            'stats' => $stats,
            'featuredProducts' => $featuredProducts
        ]);
    }

    /**
     * Mis reservas
     */
    public function bookings()
    {
        $user = $this->auth->getCurrentUser();

        // Filtros
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';

        // Obtener todas las reservas
        $bookings = $this->bookingModel->getByClientEmail($user['email']);

        // Aplicar filtros
        if ($status) {
            $bookings = array_filter($bookings, function($booking) use ($status) {
                return $booking['estado'] === $status;
            });
        }

        if ($search) {
            $bookings = array_filter($bookings, function($booking) use ($search) {
                return stripos($booking['codigo_reserva'], $search) !== false ||
                       stripos($booking['tour_nombre'] ?? '', $search) !== false;
            });
        }

        // Ordenar por fecha más reciente
        usort($bookings, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        $this->view('client/bookings', [
            'title' => 'Mis Reservas',
            'user' => $user,
            'bookings' => $bookings,
            'filters' => [
                'status' => $status,
                'search' => $search
            ]
        ]);
    }

    /**
     * Detalle de una reserva
     */
    public function bookingDetail($id)
    {
        $user = $this->auth->getCurrentUser();

        $booking = $this->bookingModel->find($id);

        if (!$booking) {
            Helpers::setFlashMessage('error', 'Reserva no encontrada');
            Helpers::redirect(Config::getBaseUrl() . '?route=client/bookings');
            return;
        }

        // Verificar que la reserva pertenezca al cliente (por email O por usuario_id)
        $belongsToUser = false;

        // Verificar por email
        if ($booking['cliente_email'] === $user['email']) {
            $belongsToUser = true;
        }

        // Verificar por usuario_id si existe
        if (isset($booking['usuario_id']) && $booking['usuario_id'] == $user['id']) {
            $belongsToUser = true;
        }

        if (!$belongsToUser) {
            Helpers::setFlashMessage('error', 'No tienes permiso para ver esta reserva');
            Helpers::redirect(Config::getBaseUrl() . '?route=client/bookings');
            return;
        }

        // Obtener información del tour
        $tour = null;
        if ($booking['tour_id']) {
            $tour = $this->tourModel->find($booking['tour_id']);
        }

        $this->view('client/booking-detail', [
            'title' => 'Detalle de Reserva #' . $booking['codigo_reserva'],
            'user' => $user,
            'booking' => $booking,
            'tour' => $tour
        ]);
    }

    /**
     * Perfil del cliente
     */
    public function profile()
    {
        $user = $this->auth->getCurrentUser();

        if (Helpers::isPost()) {
            $this->updateProfile();
            return;
        }

        $this->view('client/profile', [
            'title' => 'Mi Perfil',
            'user' => $user,
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }

    /**
     * Actualizar perfil
     */
    private function updateProfile()
    {
        // Verificar CSRF
        if (!Helpers::validateCsrfToken($_POST['csrf_token'] ?? '')) {
            Helpers::setFlashMessage('error', 'Token de seguridad inválido');
            Helpers::redirect(Config::getBaseUrl() . '?route=client/profile');
            return;
        }

        $user = $this->auth->getCurrentUser();

        $data = [
            'nombre' => Helpers::sanitizeString($_POST['nombre'] ?? ''),
            'telefono' => Helpers::sanitizeString($_POST['telefono'] ?? '')
        ];

        // Validar
        if (empty($data['nombre'])) {
            Helpers::setFlashMessage('error', 'El nombre es requerido');
            Helpers::redirect(Config::getBaseUrl() . '?route=client/profile');
            return;
        }

        // Actualizar
        $updated = $this->db->update(
            'usuarios',
            $data,
            'id = :id',
            ['id' => $user['id']]
        );

        // Cambio de contraseña (opcional)
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!empty($currentPassword) && !empty($newPassword)) {
            if ($newPassword !== $confirmPassword) {
                Helpers::setFlashMessage('error', 'Las contraseñas nuevas no coinciden');
                Helpers::redirect(Config::getBaseUrl() . '?route=client/profile');
                return;
            }

            $passwordResult = $this->auth->changePassword($currentPassword, $newPassword);

            if (!$passwordResult['success']) {
                Helpers::setFlashMessage('error', $passwordResult['message']);
                Helpers::redirect(Config::getBaseUrl() . '?route=client/profile');
                return;
            }
        }

        // Refrescar datos del usuario en la sesión
        $this->auth->refreshCurrentUser();

        Helpers::setFlashMessage('success', 'Perfil actualizado correctamente');
        Helpers::redirect(Config::getBaseUrl() . '?route=client/profile');
    }

    /**
     * Cancelar una reserva
     */
    public function cancelBooking($id)
    {
        if (!Helpers::isPost()) {
            Helpers::jsonResponse(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $this->validateCsrf();

        $user = $this->auth->getCurrentUser();
        $booking = $this->bookingModel->find($id);

        if (!$booking) {
            Helpers::jsonResponse(['success' => false, 'message' => 'Reserva no encontrada']);
            return;
        }

        // Verificar propiedad
        if ($booking['cliente_email'] !== $user['email']) {
            Helpers::jsonResponse(['success' => false, 'message' => 'No tienes permiso']);
            return;
        }

        // No se puede cancelar si ya está completada o cancelada
        if (in_array($booking['estado'], ['completada', 'cancelada'])) {
            Helpers::jsonResponse(['success' => false, 'message' => 'No se puede cancelar esta reserva']);
            return;
        }

        // Actualizar estado
        $updated = $this->db->update(
            'reservas',
            ['estado' => 'cancelada'],
            'id = :id',
            ['id' => $id]
        );

        if ($updated) {
            Helpers::jsonResponse([
                'success' => true,
                'message' => 'Reserva cancelada correctamente'
            ]);
        } else {
            Helpers::jsonResponse([
                'success' => false,
                'message' => 'Error al cancelar la reserva'
            ]);
        }
    }
}
