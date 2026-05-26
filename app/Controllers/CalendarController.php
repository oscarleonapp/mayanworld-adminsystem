<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\Config;
use App\Models\Calendar;
use Exception;

class CalendarController extends BaseController
{
    private $calendarModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->calendarModel = new Calendar();
        
        // Verificar que sea empleado para acceder
        if (!$this->auth->isEmployee()) {
            $this->redirect('home');
        }
    }
    
    // Vista principal del calendario
    public function index()
    {
        $this->auth->requirePermission('calendario.leer');
        
        $calendars = $this->calendarModel->getUserCalendars($this->auth->getCurrentUser()['id']);
        $publicCalendars = $this->calendarModel->getPublicCalendars();
        
        $this->view('admin/calendar/index', [
            'calendars' => $calendars,
            'publicCalendars' => $publicCalendars,
            'userRole' => $this->auth->getCurrentUser()['rol']
        ]);
    }
    
    // API para obtener eventos (FullCalendar)
    public function api_events()
    {
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');
        $calendarIds = isset($_GET['calendars']) ? explode(',', $_GET['calendars']) : [];
        
        $events = $this->calendarModel->getEvents($start, $end, $calendarIds);
        
        // Formatear para FullCalendar
        $formattedEvents = array_map(function($event) {
            return [
                'id' => $event['id'],
                'title' => $event['titulo'],
                'start' => $event['fecha_inicio'],
                'end' => $event['fecha_fin'],
                'allDay' => (bool)$event['todo_el_dia'],
                'backgroundColor' => $event['color'] ?: '#007bff',
                'borderColor' => $event['color'] ?: '#007bff',
                'extendedProps' => [
                    'description' => $event['descripcion'],
                    'location' => $event['ubicacion'],
                    'type' => $event['tipo'],
                    'calendar_id' => $event['calendario_id'],
                    'calendar_name' => $event['calendar_nombre'],
                    'resource_type' => $event['recurso_tipo'],
                    'resource_id' => $event['recurso_id']
                ]
            ];
        }, $events);
        
        $this->jsonResponse($formattedEvents);
    }
    
    // Crear evento
    public function create_event()
    {
        $this->auth->requirePermission('calendario.crear');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        $this->validateCSRF();
        
        $data = [
            'calendario_id' => $_POST['calendario_id'] ?? null,
            'titulo' => $_POST['titulo'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
            'fecha_fin' => $_POST['fecha_fin'] ?? null,
            'todo_el_dia' => isset($_POST['todo_el_dia']) ? 1 : 0,
            'ubicacion' => $_POST['ubicacion'] ?? '',
            'tipo' => $_POST['tipo'] ?? 'reunion',
            'recurso_id' => $_POST['recurso_id'] ?? null,
            'recurso_tipo' => $_POST['recurso_tipo'] ?? null,
            'color' => $_POST['color'] ?? null,
            'creado_por' => $this->auth->getCurrentUser()['id']
        ];
        
        // Validar datos obligatorios
        if (empty($data['titulo']) || empty($data['fecha_inicio'])) {
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Título y fecha de inicio son requeridos'
            ], 400);
            return;
        }
        
        // Verificar disponibilidad si es un recurso específico
        if ($data['recurso_id'] && $data['recurso_tipo']) {
            $available = $this->calendarModel->checkResourceAvailability(
                $data['recurso_id'],
                $data['recurso_tipo'],
                $data['fecha_inicio'],
                $data['fecha_fin']
            );
            
            if (!$available) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'El recurso no está disponible en esa fecha/hora'
                ], 400);
                return;
            }
        }
        
        try {
            $eventId = $this->calendarModel->createEvent($data);
            
            // Crear notificaciones si es necesario
            $this->createEventNotifications($eventId, $data);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Evento creado exitosamente',
                'event_id' => $eventId
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al crear evento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Actualizar evento (drag & drop, resize)
    public function update_event($id)
    {
        $this->auth->requirePermission('calendario.actualizar');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        $event = $this->calendarModel->getEvent($id);
        if (!$event) {
            $this->jsonResponse(['success' => false, 'message' => 'Evento no encontrado'], 404);
            return;
        }
        
        // Verificar permisos (solo creador o admin)
        $user = $this->auth->getCurrentUser();
        if ($event['creado_por'] !== $user['id'] && !$this->auth->isSuperAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'Sin permisos'], 403);
            return;
        }
        
        $data = [];
        if (isset($_POST['fecha_inicio'])) $data['fecha_inicio'] = $_POST['fecha_inicio'];
        if (isset($_POST['fecha_fin'])) $data['fecha_fin'] = $_POST['fecha_fin'];
        if (isset($_POST['titulo'])) $data['titulo'] = $_POST['titulo'];
        if (isset($_POST['todo_el_dia'])) $data['todo_el_dia'] = (int)$_POST['todo_el_dia'];
        
        try {
            $this->calendarModel->updateEvent($id, $data);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Evento actualizado exitosamente'
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al actualizar evento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Eliminar evento
    public function delete_event($id)
    {
        $this->auth->requirePermission('calendario.eliminar');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        $event = $this->calendarModel->getEvent($id);
        if (!$event) {
            $this->jsonResponse(['success' => false, 'message' => 'Evento no encontrado'], 404);
            return;
        }
        
        // Verificar permisos
        $user = $this->auth->getCurrentUser();
        if ($event['creado_por'] !== $user['id'] && !$this->auth->isSuperAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'Sin permisos'], 403);
            return;
        }
        
        try {
            $this->calendarModel->deleteEvent($id);
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Evento eliminado exitosamente'
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al eliminar evento: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Vista de recursos (buses, empleados)
    public function resources()
    {
        $this->auth->requirePermission('calendario.leer');
        
        $buses = $this->calendarModel->getAvailableBuses();
        $employees = $this->calendarModel->getAvailableEmployees();
        $routes = $this->calendarModel->getActiveRoutes();
        
        $this->view('admin/calendar/resources', [
            'buses' => $buses,
            'employees' => $employees,
            'routes' => $routes
        ]);
    }
    
    // API para disponibilidad de recursos
    public function api_resource_availability()
    {
        $resourceType = $_GET['type'] ?? '';
        $resourceId = $_GET['id'] ?? '';
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');
        
        if (!$resourceType || !$resourceId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Tipo y ID de recurso requeridos'
            ], 400);
            return;
        }
        
        $availability = $this->calendarModel->getResourceAvailability(
            $resourceId, $resourceType, $start, $end
        );
        
        $this->jsonResponse([
            'success' => true,
            'data' => $availability
        ]);
    }
    
    // Gestión de calendarios
    public function calendars()
    {
        $this->auth->requirePermission('calendario.administrar');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCalendarAction();
            return;
        }
        
        $allCalendars = $this->calendarModel->getAllCalendars();
        
        $this->view('admin/calendar/manage', [
            'calendars' => $allCalendars
        ]);
    }
    
    private function handleCalendarAction()
    {
        $this->validateCSRF();
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $data = [
                    'nombre' => $_POST['nombre'] ?? '',
                    'descripcion' => $_POST['descripcion'] ?? '',
                    'color' => $_POST['color'] ?? '#007bff',
                    'tipo' => $_POST['tipo'] ?? 'publico',
                    'propietario_id' => $this->auth->getCurrentUser()['id']
                ];
                
                if (empty($data['nombre'])) {
                    $this->setFlashMessage('error', 'El nombre del calendario es requerido');
                    break;
                }
                
                try {
                    $this->calendarModel->createCalendar($data);
                    $this->setFlashMessage('success', 'Calendario creado exitosamente');
                } catch (Exception $e) {
                    $this->setFlashMessage('error', 'Error al crear calendario: ' . $e->getMessage());
                }
                break;
                
            case 'update':
                $id = $_POST['calendar_id'] ?? '';
                $data = [
                    'nombre' => $_POST['nombre'] ?? '',
                    'descripcion' => $_POST['descripcion'] ?? '',
                    'color' => $_POST['color'] ?? '#007bff',
                    'tipo' => $_POST['tipo'] ?? 'publico'
                ];
                
                try {
                    $this->calendarModel->updateCalendar($id, $data);
                    $this->setFlashMessage('success', 'Calendario actualizado exitosamente');
                } catch (Exception $e) {
                    $this->setFlashMessage('error', 'Error al actualizar calendario: ' . $e->getMessage());
                }
                break;
                
            case 'delete':
                $id = $_POST['calendar_id'] ?? '';
                
                try {
                    $this->calendarModel->deleteCalendar($id);
                    $this->setFlashMessage('success', 'Calendario eliminado exitosamente');
                } catch (Exception $e) {
                    $this->setFlashMessage('error', 'Error al eliminar calendario: ' . $e->getMessage());
                }
                break;
        }
        
        $this->redirect('admin/calendar/calendars');
    }
    
    // Vista de agenda diaria/semanal
    public function agenda()
    {
        $this->auth->requirePermission('calendario.leer');
        
        $date = $_GET['date'] ?? date('Y-m-d');
        $view = $_GET['view'] ?? 'day'; // day, week, month
        
        $events = $this->calendarModel->getAgendaEvents($date, $view);
        $conflicts = $this->calendarModel->getScheduleConflicts($date);
        
        $this->view('admin/calendar/agenda', [
            'events' => $events,
            'conflicts' => $conflicts,
            'currentDate' => $date,
            'currentView' => $view
        ]);
    }
    
    private function createEventNotifications($eventId, $eventData)
    {
        // Crear notificaciones para participantes
        if ($eventData['tipo'] === 'viaje' && $eventData['recurso_id']) {
            // Notificar a conductores
            $conductors = $this->calendarModel->getEventParticipants($eventData);
            
            foreach ($conductors as $conductor) {
                $this->calendarModel->createNotification([
                    'usuario_id' => $conductor['id'],
                    'tipo' => 'nuevo_viaje_programado',
                    'titulo' => 'Nuevo viaje programado',
                    'mensaje' => "Se te ha asignado el viaje: {$eventData['titulo']}",
                    'datos' => json_encode([
                        'event_id' => $eventId,
                        'fecha' => $eventData['fecha_inicio']
                    ]),
                    'accion_url' => "/admin/calendar/view/$eventId"
                ]);
            }
        }
    }
}