<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Permission;
use App\Models\Employee;
use Exception;

class EmployeeController extends BaseController
{
    private $employeeModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->employeeModel = new Employee();
        
        // Verificar que sea empleado para acceder
        if (!$this->auth->isEmployee()) {
            $this->redirect('home');
        }
    }
    
    // Lista de empleados
    public function index()
    {
        $this->auth->requirePermission('empleados.leer');
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $department = isset($_GET['department']) ? trim($_GET['department']) : '';
        $status = isset($_GET['status']) ? trim($_GET['status']) : '';
        
        // Obtener empleados con filtros
        $conditions = ['rol' => ['!=', 'cliente']]; // Solo empleados
        
        if ($search) {
            $conditions['search'] = $search;
        }
        
        if ($department) {
            $conditions['departamento'] = $department;
        }
        
        if ($status) {
            $conditions['estado_empleado'] = $status;
        }
        
        $pagination = $this->employeeModel->paginate($page, Config::ITEMS_PER_PAGE, $conditions);
        
        // Obtener estadísticas
        $stats = $this->employeeModel->getEmployeeStats();
        
        // Obtener departamentos para filtro
        $departments = $this->employeeModel->getDepartments();
        
        $this->view('admin/employees/index', [
            'employees' => $pagination['data'],
            'pagination' => $pagination,
            'stats' => $stats,
            'departments' => $departments,
            'filters' => [
                'search' => $search,
                'department' => $department,
                'status' => $status
            ]
        ]);
    }
    
    // Crear empleado
    public function create()
    {
        $this->auth->requirePermission('empleados.crear');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }
        
        $roles = Permission::getInstance()->getRoles();
        $departments = $this->employeeModel->getDepartments();
        $supervisors = $this->employeeModel->getPotentialSupervisors();
        
        $this->view('admin/employees/create', [
            'roles' => $roles,
            'departments' => $departments,
            'supervisors' => $supervisors
        ]);
    }
    
    private function handleCreate()
    {
        $this->validateCSRF();
        
        $data = [
            'nombre' => $_POST['nombre'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'rol' => $_POST['rol'] ?? 'operador',
            'departamento' => $_POST['departamento'] ?? '',
            'puesto' => $_POST['puesto'] ?? '',
            'fecha_ingreso' => $_POST['fecha_ingreso'] ?? date('Y-m-d'),
            'salario' => $_POST['salario'] ?? null,
            'supervisor_id' => $_POST['supervisor_id'] ?? null,
            'direccion' => $_POST['direccion'] ?? '',
            'emergency_contacto' => $_POST['emergency_contacto'] ?? '',
            'emergency_telefono' => $_POST['emergency_telefono'] ?? ''
        ];
        
        // Validar datos
        $validation = $this->validateEmployeeData($data);
        if (!$validation['success']) {
            $this->setFlashMessage('error', $validation['message']);
            $this->redirect('admin/employees/create');
            return;
        }
        
        // Crear empleado
        $result = $this->employeeModel->createEmployee($data);
        
        if ($result['success']) {
            // Log de actividad
            $this->logActivity('empleado_creado', 'empleados', "Empleado {$data['nombre']} creado");
            
            $this->setFlashMessage('success', 'Empleado creado exitosamente');
            $this->redirect('admin/employees');
        } else {
            $this->setFlashMessage('error', $result['message']);
            $this->redirect('admin/employees/create');
        }
    }
    
    // Ver detalles del empleado
    public function view($id)
    {
        $this->auth->requirePermission('empleados.leer');
        
        $employee = $this->employeeModel->getEmployeeDetails($id);
        
        if (!$employee) {
            $this->setFlashMessage('error', 'Empleado no encontrado');
            $this->redirect('admin/employees');
            return;
        }
        
        // Obtener estadísticas del empleado
        $employeeStats = $this->employeeModel->getEmployeePerformance($id);
        
        $this->view('admin/employees/view', [
            'employee' => $employee,
            'stats' => $employeeStats
        ]);
    }
    
    // Editar empleado
    public function edit($id)
    {
        $this->auth->requirePermission('empleados.actualizar');
        
        $employee = $this->employeeModel->find($id);
        
        if (!$employee || $employee['rol'] === 'cliente') {
            $this->setFlashMessage('error', 'Empleado no encontrado');
            $this->redirect('admin/employees');
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleEdit($id);
            return;
        }
        
        $roles = Permission::getInstance()->getRoles();
        $departments = $this->employeeModel->getDepartments();
        $supervisors = $this->employeeModel->getPotentialSupervisors($id);
        
        $this->view('admin/employees/edit', [
            'employee' => $employee,
            'roles' => $roles,
            'departments' => $departments,
            'supervisors' => $supervisors
        ]);
    }
    
    private function handleEdit($id)
    {
        $this->validateCSRF();
        
        $oldData = $this->employeeModel->find($id);
        
        $data = [
            'nombre' => $_POST['nombre'] ?? '',
            'email' => $_POST['email'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
            'rol' => $_POST['rol'] ?? $oldData['rol'],
            'departamento' => $_POST['departamento'] ?? '',
            'puesto' => $_POST['puesto'] ?? '',
            'salario' => $_POST['salario'] ?? null,
            'supervisor_id' => $_POST['supervisor_id'] ?? null,
            'estado_empleado' => $_POST['estado_empleado'] ?? $oldData['estado_empleado'],
            'direccion' => $_POST['direccion'] ?? '',
            'emergency_contacto' => $_POST['emergency_contacto'] ?? '',
            'emergency_telefono' => $_POST['emergency_telefono'] ?? ''
        ];
        
        // Si se proporciona nueva contraseña
        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }
        
        $result = $this->employeeModel->updateEmployee($id, $data);
        
        if ($result['success']) {
            // Log de cambios
            $this->logActivity('empleado_actualizado', 'empleados', 
                "Empleado {$data['nombre']} actualizado", $oldData, $data);
                
            $this->setFlashMessage('success', 'Empleado actualizado exitosamente');
            $this->redirect("admin/employees/view/$id");
        } else {
            $this->setFlashMessage('error', $result['message']);
            $this->redirect("admin/employees/edit/$id");
        }
    }
    
    // API para obtener empleados (AJAX)
    public function api_list()
    {
        $this->auth->requirePermission('empleados.leer');
        
        $search = $_GET['search'] ?? '';
        $department = $_GET['department'] ?? '';
        $role = $_GET['role'] ?? '';
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        
        $employees = $this->employeeModel->searchEmployees($search, [
            'departamento' => $department,
            'rol' => $role,
            'limit' => $limit
        ]);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $employees
        ]);
    }
    
    // Cambiar estado del empleado
    public function changeStatus($id)
    {
        $this->auth->requirePermission('empleados.actualizar');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        $this->validateCSRF();
        
        $status = $_POST['status'] ?? '';
        $validStatuses = ['activo', 'inactivo', 'vacaciones', 'suspension'];
        
        if (!in_array($status, $validStatuses)) {
            $this->jsonResponse(['success' => false, 'message' => 'Estado inválido'], 400);
            return;
        }
        
        $employee = $this->employeeModel->find($id);
        if (!$employee || $employee['rol'] === 'cliente') {
            $this->jsonResponse(['success' => false, 'message' => 'Empleado no encontrado'], 404);
            return;
        }
        
        $result = $this->employeeModel->update($id, ['estado_empleado' => $status]);
        
        if ($result) {
            $this->logActivity('estado_empleado_cambio', 'empleados', 
                "Estado de {$employee['nombre']} cambiado a {$status}");
                
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
    
    // Obtener organigrama
    public function organigrama()
    {
        $this->auth->requirePermission('empleados.leer');
        
        $orgChart = $this->employeeModel->getOrganizationChart();
        
        if (Helpers::isAjax()) {
            $this->jsonResponse([
                'success' => true,
                'data' => $orgChart
            ]);
        } else {
            $this->view('admin/employees/organigrama', [
                'orgChart' => $orgChart
            ]);
        }
    }
    
    private function validateEmployeeData($data)
    {
        if (empty($data['nombre'])) {
            return ['success' => false, 'message' => 'El nombre es requerido'];
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email válido es requerido'];
        }
        
        if (empty($data['password']) && !isset($data['id'])) {
            return ['success' => false, 'message' => 'La contraseña es requerida'];
        }
        
        if (!empty($data['password']) && strlen($data['password']) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
        }
        
        if (empty($data['departamento'])) {
            return ['success' => false, 'message' => 'El departamento es requerido'];
        }
        
        if (empty($data['puesto'])) {
            return ['success' => false, 'message' => 'El puesto es requerido'];
        }
        
        return ['success' => true];
    }
    
    private function logActivity($action, $module, $description, $oldData = null, $newData = null)
    {
        $user = $this->auth->getCurrentUser();
        $this->employeeModel->logActivity([
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