<?php

namespace App\Core;

class Auth
{
    private static $instance = null;
    private $db;
    private $currentUser = null;
    private $permissions = null;
    
    private function __construct()
    {
        $this->db = Database::getInstance();
        $this->permissions = Permission::getInstance();
        Helpers::startSession();
        $this->loadCurrentUser();
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Cargar usuario actual desde sesión
    private function loadCurrentUser()
    {
        if (isset($_SESSION['user_id'])) {
            $this->currentUser = $this->getUserById($_SESSION['user_id']);
        }
    }
    
    // Obtener usuario por ID
    private function getUserById($id)
    {
        // Usar 'tipo' que es el campo real en la base de datos
        $sql = "SELECT id, nombre, email, telefono, tipo as rol, activo, created_at FROM usuarios WHERE id = :id AND activo = 1";
        $user = $this->db->fetch($sql, ['id' => $id]);
        return $user;
    }
    
    // Obtener usuario por email
    private function getUserByEmail($email)
    {
        // Usar 'tipo' que es el campo real en la base de datos
        $sql = "SELECT id, nombre, email, password, tipo as rol, activo, password_change_required FROM usuarios WHERE email = :email";
        $user = $this->db->fetch($sql, ['email' => $email]);
        return $user;
    }
    
    // Intentar login
    public function login($email, $password, $remember = false)
    {
        // Validar datos de entrada
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email y contraseña son requeridos'];
        }
        
        if (!Helpers::validateEmail($email)) {
            return ['success' => false, 'message' => 'Email inválido'];
        }
        
        // Buscar usuario
        $user = $this->getUserByEmail($email);
        
        if (!$user) {
            return ['success' => false, 'message' => 'Credenciales inválidas'];
        }
        
        if (!$user['activo']) {
            return ['success' => false, 'message' => 'Cuenta desactivada'];
        }
        
        // Verificar contraseña
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Credenciales inválidas'];
        }
        
        // Login exitoso
        $this->setUserSession($user);
        
        // Registrar último acceso
        $this->updateLastAccess($user['id']);
        
        return ['success' => true, 'message' => 'Login exitoso', 'user' => $user];
    }
    
    // Establecer sesión de usuario
    private function setUserSession($user)
    {
        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nombre'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_type'] = $user['rol'];
        $_SESSION['login_time'] = time();
        
        $this->currentUser = $user;
    }
    
    // Actualizar último acceso
    private function updateLastAccess($userId)
    {
        $sql = "UPDATE usuarios SET updated_at = NOW() WHERE id = :id";
        $this->db->query($sql, ['id' => $userId]);
    }
    
    // Logout
    public function logout()
    {
        $this->currentUser = null;
        
        // Limpiar variables de sesión
        $_SESSION = [];
        
        // Destruir cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), 
                '', 
                time() - 42000,
                $params["path"], 
                $params["domain"],
                $params["secure"], 
                $params["httponly"]
            );
        }
        
        // Destruir sesión
        session_destroy();
        
        return ['success' => true, 'message' => 'Logout exitoso'];
    }
    
    // Verificar si está logueado
    public static function isLoggedIn()
    {
        $instance = self::getInstance();
        return $instance->currentUser !== null;
    }

    // Obtener usuario actual
    public static function getCurrentUser()
    {
        $instance = self::getInstance();
        return $instance->currentUser;
    }

    // Alias para getCurrentUser (compatibilidad)
    public static function getUser()
    {
        return self::getCurrentUser();
    }

    // Alias adicional para compatibilidad
    public static function user()
    {
        return self::getCurrentUser();
    }

    // Obtener ID del usuario actual
    public static function getUserId()
    {
        $user = self::getCurrentUser();
        return $user['id'] ?? null;
    }

    // Obtener email del usuario actual
    public static function getUserEmail()
    {
        $user = self::getCurrentUser();
        return $user['email'] ?? null;
    }

    // Verificar tipo de usuario
    public static function isAdmin()
    {
        $instance = self::getInstance();
        return self::isLoggedIn() && $instance->currentUser['rol'] === 'admin';
    }

    public static function isClient()
    {
        $instance = self::getInstance();
        return self::isLoggedIn() && $instance->currentUser['rol'] === 'cliente';
    }

    // Nuevos métodos para roles extendidos
    public static function isSuperAdmin()
    {
        $instance = self::getInstance();
        return self::isLoggedIn() && $instance->currentUser['rol'] === 'super_admin';
    }

    public static function isManager()
    {
        $instance = self::getInstance();
        return self::isLoggedIn() && in_array($instance->currentUser['rol'], ['super_admin', 'admin', 'gerente']);
    }

    public static function isEmployee()
    {
        $instance = self::getInstance();
        return self::isLoggedIn() && $instance->currentUser['rol'] !== 'cliente';
    }
    
    // Verificar permiso específico
    public function hasPermission($permission)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $this->permissions->hasPermission(
            $this->currentUser['id'], 
            $this->currentUser['rol'], 
            $permission
        );
    }
    
    // Verificar cualquiera de varios permisos (OR)
    public function hasAnyPermission($permissions)
    {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $this->permissions->hasAnyPermission(
            $this->currentUser['id'], 
            $this->currentUser['rol'], 
            $permissions
        );
    }
    
    // Requerir permiso específico
    public function requirePermission($permission, $redirectTo = 'unauthorized')
    {
        return $this->permissions->requirePermission($permission, $redirectTo);
    }
    
    // Obtener menú del usuario
    public function getUserMenu()
    {
        if (!$this->isLoggedIn()) {
            return [];
        }
        
        return $this->permissions->generateMenu(
            $this->currentUser['id'], 
            $this->currentUser['rol']
        );
    }
    
    // Requerir autenticación
    public static function requireAuth($redirectTo = 'login')
    {
        $instance = self::getInstance();
        if (!$instance->isLoggedIn()) {
            Helpers::setFlashMessage('warning', 'Debes iniciar sesión para acceder a esta página');

            $router = new Router();
            $router->redirect($redirectTo);
        }
    }

    // Requerir admin
    public static function requireAdmin($redirectTo = 'home')
    {
        self::requireAuth();

        $instance = self::getInstance();
        if (!$instance->isAdmin()) {
            Helpers::setFlashMessage('error', 'No tienes permisos para acceder a esta página');

            $router = new Router();
            $router->redirect($redirectTo);
        }
    }

    // Requerir rol específico
    public static function requireRole($role, $redirectTo = 'home')
    {
        self::requireAuth();

        $instance = self::getInstance();
        if (!$instance->hasRole($role)) {
            Helpers::setFlashMessage('error', 'No tienes permisos para acceder a esta página');

            $router = new Router();
            $router->redirect($redirectTo);
        }
    }

    // Verificar si tiene un rol específico
    public static function hasRole($role)
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        $instance = self::getInstance();

        // Soportar múltiples roles (array o string)
        if (is_array($role)) {
            return in_array($instance->currentUser['rol'], $role);
        }

        return $instance->currentUser['rol'] === $role;
    }
    
    // Registrar nuevo usuario
    public function register($data)
    {
        // Validar datos requeridos
        $required = ['nombre', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "El campo {$field} es requerido"];
            }
        }
        
        // Validaciones específicas
        if (!Helpers::validateEmail($data['email'])) {
            return ['success' => false, 'message' => 'Email inválido'];
        }
        
        if (strlen($data['password']) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
        }
        
        // Verificar si el email ya existe
        $existingUser = $this->getUserByEmail($data['email']);
        if ($existingUser) {
            return ['success' => false, 'message' => 'El email ya está registrado'];
        }
        
        // Preparar datos para insertar (intentar con 'rol' o 'tipo')
        $userData = [
            'nombre' => Helpers::sanitizeString($data['nombre']),
            'email' => strtolower(trim($data['email'])),
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'telefono' => isset($data['telefono']) ? Helpers::sanitizeString($data['telefono']) : null
        ];
        
        // Usar 'tipo' que es el campo real en la base de datos
        $userData['tipo'] = 'cliente';
        
        try {
            $userId = $this->db->insert('usuarios', $userData);
            
            return [
                'success' => true, 
                'message' => 'Usuario registrado exitosamente',
                'user_id' => $userId
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al registrar usuario'];
        }
    }
    
    // Cambiar contraseña
    public function changePassword($currentPassword, $newPassword)
    {
        if (!$this->isLoggedIn()) {
            return ['success' => false, 'message' => 'Debes estar logueado'];
        }
        
        // Obtener datos completos del usuario
        $user = $this->db->fetch(
            "SELECT password FROM usuarios WHERE id = :id", 
            ['id' => $this->currentUser['id']]
        );
        
        // Verificar contraseña actual
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Contraseña actual incorrecta'];
        }
        
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'La nueva contraseña debe tener al menos 6 caracteres'];
        }
        
        // Actualizar contraseña
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $updated = $this->db->update(
            'usuarios', 
            ['password' => $hashedPassword],
            'id = :id',
            ['id' => $this->currentUser['id']]
        );
        
        if ($updated) {
            return ['success' => true, 'message' => 'Contraseña actualizada exitosamente'];
        } else {
            return ['success' => false, 'message' => 'Error al actualizar contraseña'];
        }
    }
    
    // Verificar timeout de sesión
    public function checkSessionTimeout()
    {
        if ($this->isLoggedIn()) {
            $loginTime = $_SESSION['login_time'] ?? 0;
            $currentTime = time();

            // Si han pasado más horas que el timeout configurado
            if (($currentTime - $loginTime) > Config::SESSION_TIMEOUT) {
                $this->logout();
                return false;
            }

            // Actualizar tiempo de actividad
            $_SESSION['last_activity'] = $currentTime;
        }

        return true;
    }

    // Refrescar datos del usuario actual desde la base de datos
    public function refreshCurrentUser()
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        // Recargar usuario desde la base de datos
        $this->currentUser = $this->getUserById($this->currentUser['id']);

        // Actualizar también las variables de sesión
        if ($this->currentUser) {
            $_SESSION['user_name'] = $this->currentUser['nombre'];
            $_SESSION['user_email'] = $this->currentUser['email'];
            $_SESSION['user_type'] = $this->currentUser['rol'];
            return true;
        }

        return false;
    }
}