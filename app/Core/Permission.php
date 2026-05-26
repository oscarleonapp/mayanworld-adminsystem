<?php

namespace App\Core;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Helpers;

class Permission
{
    private static $instance = null;
    private $db;
    private $userPermissions = [];
    
    private function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Cargar permisos del usuario
    public function loadUserPermissions($userId, $userRole)
    {
        $this->userPermissions[$userId] = [];
        
        // Obtener permisos por rol
        $sql = "
            SELECT p.nombre, p.modulo, p.accion 
            FROM permisos p 
            INNER JOIN rol_permisos rp ON p.id = rp.permiso_id 
            WHERE rp.rol = :rol
        ";
        
        $permissions = $this->db->fetchAll($sql, ['rol' => $userRole]);
        
        foreach ($permissions as $permission) {
            $this->userPermissions[$userId][] = $permission['nombre'];
        }
        
        return $this->userPermissions[$userId];
    }
    
    // Verificar si el usuario tiene un permiso específico
    public function hasPermission($userId, $userRole, $permission)
    {
        // Super admin tiene todos los permisos
        if ($userRole === 'super_admin') {
            return true;
        }
        
        // Cargar permisos si no están en memoria
        if (!isset($this->userPermissions[$userId])) {
            $this->loadUserPermissions($userId, $userRole);
        }
        
        return in_array($permission, $this->userPermissions[$userId]);
    }
    
    // Verificar múltiples permisos (OR)
    public function hasAnyPermission($userId, $userRole, $permissions)
    {
        if ($userRole === 'super_admin') {
            return true;
        }
        
        foreach ($permissions as $permission) {
            if ($this->hasPermission($userId, $userRole, $permission)) {
                return true;
            }
        }
        
        return false;
    }
    
    // Verificar múltiples permisos (AND)
    public function hasAllPermissions($userId, $userRole, $permissions)
    {
        if ($userRole === 'super_admin') {
            return true;
        }
        
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($userId, $userRole, $permission)) {
                return false;
            }
        }
        
        return true;
    }
    
    // Middleware para verificar permisos en rutas
    public function requirePermission($permission, $redirectTo = 'unauthorized')
    {
        $auth = Auth::getInstance();
        
        if (!$auth->isLoggedIn()) {
            Helpers::redirect('login');
            return false;
        }
        
        $user = $auth->getCurrentUser();
        
        if (!$this->hasPermission($user['id'], $user['rol'], $permission)) {
            if ($redirectTo === 'unauthorized') {
                http_response_code(403);
                Helpers::view('errors/403', ['message' => 'No tienes permisos para acceder a esta sección']);
                exit();
            } else {
                Helpers::redirect($redirectTo);
                return false;
            }
        }
        
        return true;
    }
    
    // Obtener todos los permisos por módulo
    public function getPermissionsByModule($module)
    {
        $sql = "SELECT * FROM permisos WHERE modulo = :modulo ORDER BY nombre";
        return $this->db->fetchAll($sql, ['modulo' => $module]);
    }
    
    // Obtener todos los módulos
    public function getModules()
    {
        $sql = "SELECT DISTINCT modulo FROM permisos ORDER BY modulo";
        $result = $this->db->fetchAll($sql);
        
        return array_column($result, 'modulo');
    }
    
    // Asignar permiso a rol
    public function assignPermissionToRole($role, $permissionId)
    {
        $sql = "INSERT IGNORE INTO rol_permisos (rol, permiso_id) VALUES (:rol, :permiso_id)";
        return $this->db->query($sql, [
            'rol' => $role,
            'permiso_id' => $permissionId
        ]);
    }
    
    // Revocar permiso de rol
    public function revokePermissionFromRole($role, $permissionId)
    {
        $sql = "DELETE FROM rol_permisos WHERE rol = :rol AND permiso_id = :permiso_id";
        return $this->db->query($sql, [
            'rol' => $role,
            'permiso_id' => $permissionId
        ]);
    }
    
    // Obtener permisos de un rol
    public function getRolePermissions($role)
    {
        $sql = "
            SELECT p.*, rp.id as asignacion_id
            FROM permisos p 
            INNER JOIN rol_permisos rp ON p.id = rp.permiso_id 
            WHERE rp.rol = :rol
            ORDER BY p.modulo, p.nombre
        ";
        
        return $this->db->fetchAll($sql, ['rol' => $role]);
    }
    
    // Obtener todos los roles disponibles
    public function getRoles()
    {
        return [
            'super_admin' => 'Super Administrador',
            'admin' => 'Administrador',
            'gerente' => 'Gerente',
            'operador' => 'Operador',
            'vendedor' => 'Vendedor',
            'conductor' => 'Conductor',
            'soporte' => 'Soporte',
            'cliente' => 'Cliente'
        ];
    }
    
    // Helper para generar menú basado en permisos
    public function generateMenu($userId, $userRole)
    {
        $menu = [];
        
        // Dashboard - siempre visible para empleados
        if ($userRole !== 'cliente') {
            $menu[] = [
                'title' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'url' => '/admin',
                'permission' => null
            ];
        }
        
        // Bookings
        if ($this->hasAnyPermission($userId, $userRole, ['bookings.leer', 'bookings.crear'])) {
            $submenu = [];
            
            if ($this->hasPermission($userId, $userRole, 'bookings.leer')) {
                $submenu[] = ['title' => 'Ver Reservas', 'url' => '/admin/bookings'];
            }
            
            if ($this->hasPermission($userId, $userRole, 'bookings.crear')) {
                $submenu[] = ['title' => 'Nueva Reserva', 'url' => '/admin/bookings/create'];
            }
            
            $menu[] = [
                'title' => 'Reservas',
                'icon' => 'fas fa-calendar-check',
                'submenu' => $submenu
            ];
        }
        
        // Buses
        if ($this->hasAnyPermission($userId, $userRole, ['buses.leer', 'buses.tracking'])) {
            $submenu = [];
            
            if ($this->hasPermission($userId, $userRole, 'buses.leer')) {
                $submenu[] = ['title' => 'Gestión de Buses', 'url' => '/admin/buses'];
            }
            
            if ($this->hasPermission($userId, $userRole, 'buses.tracking')) {
                $submenu[] = ['title' => 'Seguimiento GPS', 'url' => '/admin/buses/tracking'];
            }
            
            $menu[] = [
                'title' => 'Transporte',
                'icon' => 'fas fa-bus',
                'submenu' => $submenu
            ];
        }
        
        // Empleados
        if ($this->hasAnyPermission($userId, $userRole, ['empleados.leer', 'empleados.crear'])) {
            $menu[] = [
                'title' => 'Empleados',
                'icon' => 'fas fa-users',
                'url' => '/admin/employees'
            ];
        }
        
        // Contenido Web
        if ($this->hasAnyPermission($userId, $userRole, ['contenido.leer', 'contenido.actualizar'])) {
            $menu[] = [
                'title' => 'Editor Web',
                'icon' => 'fas fa-edit',
                'url' => '/admin/content'
            ];
        }
        
        // Chat
        if ($this->hasAnyPermission($userId, $userRole, ['chat.leer', 'chat.responder'])) {
            $menu[] = [
                'title' => 'Chat Soporte',
                'icon' => 'fas fa-comments',
                'url' => '/admin/chat'
            ];
        }
        
        // Reportes
        if ($this->hasAnyPermission($userId, $userRole, ['reportes.ventas', 'reportes.operaciones', 'reportes.financieros'])) {
            $submenu = [];
            
            if ($this->hasPermission($userId, $userRole, 'reportes.ventas')) {
                $submenu[] = ['title' => 'Ventas', 'url' => '/admin/reports/sales'];
            }
            
            if ($this->hasPermission($userId, $userRole, 'reportes.operaciones')) {
                $submenu[] = ['title' => 'Operaciones', 'url' => '/admin/reports/operations'];
            }
            
            if ($this->hasPermission($userId, $userRole, 'reportes.financieros')) {
                $submenu[] = ['title' => 'Financieros', 'url' => '/admin/reports/financial'];
            }
            
            $menu[] = [
                'title' => 'Reportes',
                'icon' => 'fas fa-chart-bar',
                'submenu' => $submenu
            ];
        }
        
        // Sistema (solo super_admin y admin)
        if ($this->hasAnyPermission($userId, $userRole, ['sistema.configurar', 'sistema.logs'])) {
            $menu[] = [
                'title' => 'Sistema',
                'icon' => 'fas fa-cogs',
                'url' => '/admin/system'
            ];
        }
        
        return $menu;
    }
}