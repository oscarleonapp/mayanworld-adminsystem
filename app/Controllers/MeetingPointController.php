<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Models\MeetingPoint;

class MeetingPointController extends BaseController
{
    private $mpModel;

    public function __construct()
    {
        parent::__construct();
        $this->mpModel = new MeetingPoint();
    }

    public function index()
    {
        $this->requireAuth();
        
        $meetingPoints = $this->mpModel->findAll([], "title ASC");

        $this->view('admin/meeting_points/index', [
            'title' => 'Puntos de Encuentro',
            'meetingPoints' => $meetingPoints
        ]);
    }

    public function create()
    {
        $this->requireAuth();

        if (Helpers::isPost()) {
            $data = [
                'type' => $this->getInput('type') ?: 'standard',
                'title' => trim($this->getInput('title')),
                'address' => trim($this->getInput('address')),
                'map_link' => trim($this->getInput('map_link')),
                'description' => trim($this->getInput('description')),
                'is_active' => $this->getInput('is_active') ? 1 : 0
            ];

            if (empty($data['title'])) {
                Helpers::setFlashMessage('error', 'El título es requerido');
                $this->redirect('admin/meeting-points/create');
                return;
            }

            if ($this->mpModel->create($data)) {
                Helpers::setFlashMessage('success', 'Punto de encuentro creado correctamente');
                $this->redirect('admin/meeting-points');
            } else {
                Helpers::setFlashMessage('error', 'Error al crear el punto de encuentro');
            }
            return;
        }

        $this->view('admin/meeting_points/form', [
            'title' => 'Nuevo Punto de Encuentro',
            'meetingPoint' => []
        ]);
    }

    public function edit($id)
    {
        $this->requireAuth();
        
        $meetingPoint = $this->mpModel->find($id);
        if (!$meetingPoint) {
            Helpers::setFlashMessage('error', 'Punto de encuentro no encontrado');
            $this->redirect('admin/meeting-points');
            return;
        }

        if (Helpers::isPost()) {
            $data = [
                'type' => $this->getInput('type') ?: 'standard',
                'title' => trim($this->getInput('title')),
                'address' => trim($this->getInput('address')),
                'map_link' => trim($this->getInput('map_link')),
                'description' => trim($this->getInput('description')),
                'is_active' => $this->getInput('is_active') ? 1 : 0
            ];

            if (empty($data['title'])) {
                Helpers::setFlashMessage('error', 'El título es requerido');
                $this->redirect('admin/meeting-points/edit/' . $id);
                return;
            }

            if ($this->mpModel->update($id, $data)) {
                Helpers::setFlashMessage('success', 'Punto de encuentro actualizado correctamente');
                $this->redirect('admin/meeting-points');
            } else {
                Helpers::setFlashMessage('error', 'Error al actualizar');
            }
            return;
        }

        $this->view('admin/meeting_points/form', [
            'title' => 'Editar Punto de Encuentro',
            'meetingPoint' => $meetingPoint
        ]);
    }

    public function delete($id)
    {
        $this->requireAuth();
        
        if ($this->mpModel->delete($id)) {
            Helpers::setFlashMessage('success', 'Punto de encuentro eliminado');
        } else {
            Helpers::setFlashMessage('error', 'Error al eliminar');
        }
        
        $this->redirect('admin/meeting-points');
    }

    // API Endpoint for fetching all active meeting points (for Tour Edit page)
    public function list()
    {
        $this->requireAuth();
        $points = $this->mpModel->getActive();
        Helpers::jsonResponse($points);
    }

    // API: Get assigned points for a tour
    public function assigned()
    {
        $this->requireAuth();
        $tourId = $this->getInput('tour_id');
        
        if (!$tourId) {
            Helpers::jsonResponse([]);
        }

        $points = $this->mpModel->getByTourId($tourId);
        Helpers::jsonResponse($points);
    }

    // API: Assign point to tour
    public function assign()
    {
        $this->requireAuth();
        if (!Helpers::isPost()) {
            Helpers::jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $tourId = $this->getInput('tour_id');
        $meetingPointId = $this->getInput('meeting_point_id');

        if (!$tourId || !$meetingPointId) {
            Helpers::jsonResponse(['success' => false, 'error' => 'Missing parameters'], 400);
        }

        if ($this->mpModel->assignToTour($tourId, $meetingPointId)) {
            Helpers::jsonResponse(['success' => true]);
        } else {
            Helpers::jsonResponse(['success' => false, 'error' => 'Failed to assign'], 500);
        }
    }

    // API: Remove point from tour
    public function detach()
    {
        $this->requireAuth();
        if (!Helpers::isPost()) {
            Helpers::jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
        }

        $tourId = $this->getInput('tour_id');
        $meetingPointId = $this->getInput('meeting_point_id');

        if (!$tourId || !$meetingPointId) {
            Helpers::jsonResponse(['success' => false, 'error' => 'Missing parameters'], 400);
        }

        if ($this->mpModel->removeFromTour($tourId, $meetingPointId)) {
            Helpers::jsonResponse(['success' => true]);
        } else {
            Helpers::jsonResponse(['success' => false, 'error' => 'Failed to detach'], 500);
        }
    }
}
