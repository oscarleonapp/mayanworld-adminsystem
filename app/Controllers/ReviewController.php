<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Models\Review;
use App\Models\Tour;
use Exception;

class ReviewController extends BaseController
{
    private $reviewModel;
    private $tourModel;

    public function __construct()
    {
        parent::__construct();
        $this->reviewModel = new Review();
        $this->tourModel = new Tour();
    }

    /**
     * Formulario para crear review verificado (con token)
     */
    public function form()
    {
        $token = $this->getInput('token');
        
        if (!$token) {
            $this->redirect('tours', 'Token de invitación requerido', 'error');
            return;
        }

        // Validar token
        $validation = $this->reviewModel->validateInvitationToken($token);
        
        if (!$validation['valid']) {
            $this->view('review/token-expired', [
                'title' => 'Invitación Expirada',
                'error' => $validation['error']
            ]);
            return;
        }

        $invitation = $validation['invitation'];

        // Si es POST, procesar el review
        if (Helpers::isPost()) {
            $this->processVerifiedReview($token, $invitation);
            return;
        }

        // Obtener datos del tour para mostrar en el formulario
        $tour = $this->tourModel->find($invitation['tour_id']);

        $this->view('review/form', [
            'title' => 'Comparte tu Experiencia',
            'invitation' => $invitation,
            'tour' => $tour,
            'token' => $token,
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }

    /**
     * Procesar envío de review verificado
     */
    private function processVerifiedReview($token, $invitation)
    {
        $this->validateCsrf();

        $data = [
            'calificacion_general' => $this->getInput('calificacion_general'),
            'calificacion_guia' => $this->getInput('calificacion_guia'),
            'calificacion_transporte' => $this->getInput('calificacion_transporte'),
            'calificacion_organizacion' => $this->getInput('calificacion_organizacion'),
            'calificacion_valor' => $this->getInput('calificacion_valor'),
            'titulo' => $this->getInput('titulo'),
            'comentario' => $this->getInput('comentario'),
            'experiencia_previa' => $this->getInput('experiencia_previa', 'primera_vez'),
            'tipo_viajero' => $this->getInput('tipo_viajero', 'solo'),
            'grupo_edad' => $this->getInput('grupo_edad', '26-35')
        ];

        $result = $this->reviewModel->createVerifiedReview($data, $token);

        if ($result['success']) {
            $this->view('review/thank-you', [
                'title' => 'Gracias por tu Review',
                'review_id' => $result['review_id'],
                'auto_approved' => $result['auto_approved'],
                'message' => $result['message'],
                'tour_name' => $invitation['tour_nombre'],
                'tour_id' => $invitation['tour_id']
            ]);
        } else {
            Helpers::setFlashMessage('error', $result['error']);
            $_SESSION['form_errors'] = ['general' => $result['error']];
            $_SESSION['form_data'] = $data;
            $this->redirect('review/form?token=' . urlencode($token));
        }
    }

    /**
     * Crear review estándar (sin verificación) - método original extendido
     */
    public function submit()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        $this->validateCsrf();

        $data = [
            'tour_id' => $this->getInput('tour_id'),
            'nombre' => $this->getInput('nombre'),
            'email' => $this->getInput('email'),
            'rating' => $this->getInput('rating'),
            'titulo' => $this->getInput('titulo'),
            'comentario' => $this->getInput('comentario'),
            'fecha_viaje' => $this->getInput('fecha_viaje')
        ];

        $result = $this->reviewModel->createReview($data);
        
        if (Helpers::isAjax()) {
            $this->json($result);
        } else {
            if ($result['success']) {
                Helpers::setFlashMessage('success', 'Review enviado exitosamente. Será revisado antes de ser publicado.');
            } else {
                Helpers::setFlashMessage('error', 'Error al enviar el review');
                $_SESSION['form_errors'] = $result['errors'] ?? [];
            }
            $this->redirect('tour/' . (int)$data['tour_id']);
        }
    }

    /**
     * Votar por utilidad de un review
     */
    public function vote()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'error' => 'Método no permitido']);
            return;
        }

        $reviewId = (int)$this->getInput('review_id');
        $isUseful = $this->getInput('is_useful') === 'true';

        if (!$reviewId) {
            $this->json(['success' => false, 'error' => 'Review ID requerido']);
            return;
        }

        $success = $this->reviewModel->voteReviewUtility($reviewId, $isUseful);

        $this->json([
            'success' => $success,
            'message' => $success ? 'Voto registrado' : 'Error al registrar voto'
        ]);
    }

    /**
     * API: Obtener reviews de un tour
     */
    public function getTourReviews()
    {
        $tourId = (int)$this->getInput('tour_id');
        $page = (int)$this->getInput('page', 1);
        $limit = (int)$this->getInput('limit', 10);
        
        if (!$tourId) {
            $this->json(['success' => false, 'error' => 'Tour ID requerido']);
            return;
        }

        $offset = ($page - 1) * $limit;
        
        // Usar el método de reviews verificadas si está disponible
        $reviews = method_exists($this->reviewModel, 'getVerifiedTourReviews') ? 
                  $this->reviewModel->getVerifiedTourReviews($tourId, $limit, $offset) :
                  $this->reviewModel->getApprovedByTour($tourId, $limit);

        // Obtener estadísticas
        $stats = method_exists($this->reviewModel, 'getVerifiedTourReviewStats') ?
                $this->reviewModel->getVerifiedTourReviewStats($tourId) :
                $this->reviewModel->getSummary($tourId);

        $this->json([
            'success' => true,
            'reviews' => $reviews,
            'stats' => $stats,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'has_more' => count($reviews) === $limit
            ]
        ]);
    }

    /**
     * Página de error para token expirado/inválido
     */
    public function expired()
    {
        $this->view('review/expired', [
            'title' => 'Invitación Expirada'
        ]);
    }
}
