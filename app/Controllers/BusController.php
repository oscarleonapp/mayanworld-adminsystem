<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Helpers;
use App\Models\Bus;
use Exception;

class BusController extends BaseController
{
    private $busModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->busModel = new Bus();
        
        // Verificar que sea empleado para acceder
        if (!$this->auth->isEmployee()) {
            $this->redirect('home');
        }
    }
    
    // Lista de buses
    public function index()
    {
        $this->auth->requirePermission('buses.leer');
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $status = isset($_GET['status']) ? trim($_GET['status']) : '';
        $type = isset($_GET['type']) ? trim($_GET['type']) : '';
        
        $conditions = [];
        
        if ($search) {
            $conditions['search'] = $search;
        }
        
        if ($status) {
            $conditions['estado'] = $status;
        }
        
        if ($type) {
            $conditions['tipo'] = $type;
        }
        
        $pagination = $this->busModel->getBusesWithDetails($page, Config::ITEMS_PER_PAGE, $conditions);
        $stats = $this->busModel->getBusStats();
        
        $this->view('admin/buses/index', [
            'buses' => $pagination['data'],
            'pagination' => $pagination,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'type' => $type
            ]
        ]);
    }
    
    // Crear bus
    public function create()
    {
        $this->auth->requirePermission('buses.crear');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }
        
        $conductors = $this->busModel->getAvailableDrivers();
        
        $this->view('admin/buses/create', [
            'conductors' => $conductors
        ]);
    }
    
    private function handleCreate()
    {
        $this->validateCSRF();
        
        $data = [
            'numero_bus' => $_POST['numero_bus'] ?? '',
            'placa' => $_POST['placa'] ?? '',
            'marca' => $_POST['marca'] ?? '',
            'modelo' => $_POST['modelo'] ?? '',
            'año' => $_POST['año'] ?? null,
            'capacidad' => $_POST['capacidad'] ?? 40,
            'tipo' => $_POST['tipo'] ?? 'economico',
            'conductor_principal_id' => $_POST['conductor_principal_id'] ?? null,
            'conductor_auxiliar_id' => $_POST['conductor_auxiliar_id'] ?? null,
            'kilometraje' => $_POST['kilometraje'] ?? 0,
            'fecha_revision' => $_POST['fecha_revision'] ?? null,
            'proxima_revision' => $_POST['proxima_revision'] ?? null,
            'seguro_vigencia' => $_POST['seguro_vigencia'] ?? null,
            'caracteristicas' => json_encode([
                'wifi' => isset($_POST['wifi']),
                'aire' => isset($_POST['aire']),
                'tv' => isset($_POST['tv']),
                'baño' => isset($_POST['baño']),
                'gps' => isset($_POST['gps']),
                'cinturones' => isset($_POST['cinturones'])
            ])
        ];
        
        // Validar datos
        $validation = $this->validateBusData($data);
        if (!$validation['success']) {
            $this->setFlashMessage('error', $validation['message']);
            $this->redirect('admin/buses/create');
            return;
        }
        
        $result = $this->busModel->createBus($data);
        
        if ($result['success']) {
            $this->logActivity('bus_creado', 'buses', "Bus {$data['numero_bus']} registrado");
            $this->setFlashMessage('success', 'Bus registrado exitosamente');
            $this->redirect('admin/buses');
        } else {
            $this->setFlashMessage('error', $result['message']);
            $this->redirect('admin/buses/create');
        }
    }
    
    // Ver detalles del bus
    public function view($id)
    {
        $this->auth->requirePermission('buses.leer');
        
        $bus = $this->busModel->getBusWithDetails($id);
        
        if (!$bus) {
            $this->setFlashMessage('error', 'Bus no encontrado');
            $this->redirect('admin/buses');
            return;
        }
        
        // Obtener historial de viajes
        $trips = $this->busModel->getBusTrips($id, 10);
        
        // Obtener estadísticas del bus
        $busStats = $this->busModel->getBusStatistics($id);
        
        // Obtener ubicación actual si tiene GPS
        $currentLocation = $this->busModel->getCurrentLocation($id);
        
        $this->view('admin/buses/view', [
            'bus' => $bus,
            'trips' => $trips,
            'stats' => $busStats,
            'location' => $currentLocation
        ]);
    }
    
    // Editar bus
    public function edit($id)
    {
        $this->auth->requirePermission('buses.actualizar');
        
        $bus = $this->busModel->find($id);
        
        if (!$bus) {
            $this->setFlashMessage('error', 'Bus no encontrado');
            $this->redirect('admin/buses');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleEdit($id);
            return;
        }
        
        $conductors = $this->busModel->getAvailableDrivers($id);
        
        $this->view('admin/buses/edit', [
            'bus' => $bus,
            'conductors' => $conductors
        ]);
    }
    
    private function handleEdit($id)
    {
        $this->validateCSRF();
        
        $oldData = $this->busModel->find($id);
        
        $data = [
            'numero_bus' => $_POST['numero_bus'] ?? '',
            'placa' => $_POST['placa'] ?? '',
            'marca' => $_POST['marca'] ?? '',
            'modelo' => $_POST['modelo'] ?? '',
            'año' => $_POST['año'] ?? null,
            'capacidad' => $_POST['capacidad'] ?? 40,
            'tipo' => $_POST['tipo'] ?? 'economico',
            'estado' => $_POST['estado'] ?? $oldData['estado'],
            'conductor_principal_id' => $_POST['conductor_principal_id'] ?? null,
            'conductor_auxiliar_id' => $_POST['conductor_auxiliar_id'] ?? null,
            'kilometraje' => $_POST['kilometraje'] ?? $oldData['kilometraje'],
            'fecha_revision' => $_POST['fecha_revision'] ?? null,
            'proxima_revision' => $_POST['proxima_revision'] ?? null,
            'seguro_vigencia' => $_POST['seguro_vigencia'] ?? null,
            'caracteristicas' => json_encode([
                'wifi' => isset($_POST['wifi']),
                'aire' => isset($_POST['aire']),
                'tv' => isset($_POST['tv']),
                'baño' => isset($_POST['baño']),
                'gps' => isset($_POST['gps']),
                'cinturones' => isset($_POST['cinturones'])
            ])
        ];
        
        $result = $this->busModel->updateBus($id, $data);
        
        if ($result['success']) {
            $this->logActivity('bus_actualizado', 'buses', 
                "Bus {$data['numero_bus']} actualizado", $oldData, $data);
                
            $this->setFlashMessage('success', 'Bus actualizado exitosamente');
            $this->redirect("admin/buses/view/$id");
        } else {
            $this->setFlashMessage('error', $result['message']);
            $this->redirect("admin/buses/edit/$id");
        }
    }
    
    // Tracking GPS en tiempo real
    public function tracking()
    {
        $this->auth->requirePermission('buses.tracking');
        
        $activeBuses = $this->busModel->getActiveBusesWithLocation();
        $routes = $this->busModel->getActiveRoutesForMap();
        
        if (Helpers::isAjax()) {
            $this->jsonResponse([
                'success' => true,
                'buses' => $activeBuses
            ]);
        } else {
            $this->view('admin/buses/tracking', [
                'buses' => $activeBuses,
                'routes' => $routes
            ]);
        }
    }
    
    // Actualizar ubicación GPS (API para dispositivos móviles)
    public function api_update_location()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        $busId = $_POST['bus_id'] ?? '';
        $viajeId = $_POST['viaje_id'] ?? '';
        $lat = $_POST['latitude'] ?? '';
        $lng = $_POST['longitude'] ?? '';
        $speed = $_POST['speed'] ?? 0;
        $heading = $_POST['heading'] ?? 0;
        
        if (!$busId || !$lat || !$lng) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Datos de ubicación requeridos'
            ], 400);
            return;
        }
        
        // Verificar que el bus existe
        $bus = $this->busModel->find($busId);
        if (!$bus) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Bus no encontrado'
            ], 404);
            return;
        }
        
        try {
            // Actualizar ubicación del bus
            $this->busModel->updateLocation($busId, $lat, $lng);
            
            // Si hay un viaje activo, registrar tracking
            if ($viajeId) {
                $this->busModel->recordTripTracking($viajeId, $lat, $lng, $speed, $heading);
            }
            
            $this->jsonResponse([
                'success' => true,
                'message' => 'Ubicación actualizada'
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al actualizar ubicación: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Cambiar estado del bus
    public function change_status($id)
    {
        $this->auth->requirePermission('buses.actualizar');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        $this->validateCSRF();
        
        $status = $_POST['status'] ?? '';
        $validStatuses = ['disponible', 'en_ruta', 'mantenimiento', 'fuera_servicio'];
        
        if (!in_array($status, $validStatuses)) {
            $this->jsonResponse(['success' => false, 'message' => 'Estado inválido'], 400);
            return;
        }
        
        $bus = $this->busModel->find($id);
        if (!$bus) {
            $this->jsonResponse(['success' => false, 'message' => 'Bus no encontrado'], 404);
            return;
        }
        
        $result = $this->busModel->update($id, ['estado' => $status]);
        
        if ($result) {
            $this->logActivity('bus_estado_cambio', 'buses', 
                "Estado del bus {$bus['numero_bus']} cambiado a {$status}");
                
            $this->jsonResponse([
                'success' => true,
                'message' => 'Estado actualizado correctamente'
            ]);
        } else {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al actualizar estado'
            ], 500);
        }
    }
    
    // Gestión de rutas
    public function routes()
    {
        $this->auth->requirePermission('rutas.leer');
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        
        $conditions = [];
        if ($search) {
            $conditions['search'] = $search;
        }
        
        $pagination = $this->busModel->getRoutesWithStats($page, Config::ITEMS_PER_PAGE, $conditions);
        $routeStats = $this->busModel->getRouteStats();
        
        $this->view('admin/buses/routes', [
            'routes' => $pagination['data'],
            'pagination' => $pagination,
            'stats' => $routeStats,
            'search' => $search
        ]);
    }
    
    // Horarios de buses
    public function schedules($routeId = null)
    {
        $this->auth->requirePermission('horarios.leer');
        
        if ($routeId) {
            $route = $this->busModel->getRoute($routeId);
            if (!$route) {
                $this->setFlashMessage('error', 'Ruta no encontrada');
                $this->redirect('admin/buses/routes');
                return;
            }
            
            $schedules = $this->busModel->getRouteSchedules($routeId);
            
            $this->view('admin/buses/route_schedules', [
                'route' => $route,
                'schedules' => $schedules
            ]);
        } else {
            $allSchedules = $this->busModel->getAllSchedules();
            
            $this->view('admin/buses/schedules', [
                'schedules' => $allSchedules
            ]);
        }
    }
    
    // Mantenimiento de buses
    public function maintenance()
    {
        $this->auth->requirePermission('buses.mantenimiento');
        
        $upcomingMaintenance = $this->busModel->getUpcomingMaintenance();
        $overdueMaintenance = $this->busModel->getOverdueMaintenance();
        $maintenanceHistory = $this->busModel->getMaintenanceHistory(20);
        
        $this->view('admin/buses/maintenance', [
            'upcoming' => $upcomingMaintenance,
            'overdue' => $overdueMaintenance,
            'history' => $maintenanceHistory
        ]);
    }
    
    // Reportes de buses
    public function reports()
    {
        $this->auth->requirePermission('reportes.operaciones');
        
        $period = $_GET['period'] ?? 'month';
        $busId = $_GET['bus_id'] ?? null;
        
        $reports = [
            'efficiency' => $this->busModel->getBusEfficiencyReport($period, $busId),
            'fuel' => $this->busModel->getFuelConsumptionReport($period, $busId),
            'utilization' => $this->busModel->getBusUtilizationReport($period, $busId),
            'incidents' => $this->busModel->getIncidentReport($period, $busId)
        ];
        
        $buses = $this->busModel->findAll(['estado' => ['!=', 'fuera_servicio']]);
        
        $this->view('admin/buses/reports', [
            'reports' => $reports,
            'buses' => $buses,
            'period' => $period,
            'selectedBus' => $busId
        ]);
    }
    
    private function validateBusData($data)
    {
        if (empty($data['numero_bus'])) {
            return ['success' => false, 'message' => 'El número de bus es requerido'];
        }
        
        if (empty($data['placa'])) {
            return ['success' => false, 'message' => 'La placa es requerida'];
        }
        
        if ($data['capacidad'] < 1 || $data['capacidad'] > 100) {
            return ['success' => false, 'message' => 'La capacidad debe estar entre 1 y 100'];
        }
        
        return ['success' => true];
    }
    
    private function logActivity($action, $module, $description, $oldData = null, $newData = null)
    {
        $user = $this->auth->getCurrentUser();
        $this->busModel->logActivity([
            'usuario_id' => $user['id'],
            'accion' => $action,
            'modulo' => $module,
            'descripcion' => $description,
            'datos_anteriores' => $oldData ? json_encode($oldData) : null,
            'datos_nuevos' => $newData ? json_encode($newData) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}