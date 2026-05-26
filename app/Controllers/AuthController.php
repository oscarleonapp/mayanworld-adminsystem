<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Helpers;
use App\Core\Config;
use App\Core\Database;
use App\Helpers\SecurityHelper;

class AuthController
{
    private $auth;

    public function __construct()
    {
        $this->auth = Auth::getInstance();
    }
    
    // Mostrar formulario de login
    public function login()
    {
        // Si ya está logueado, redirigir
        if ($this->auth->isLoggedIn()) {
            $redirectTo = $this->auth->isAdmin() ? 'admin' : 'home';
            Helpers::redirect(Config::getBaseUrl() . "?route={$redirectTo}");
        }

        // Si es POST, procesar login
        if (Helpers::isPost()) {
            $this->processLogin();
            return;
        }

        // Obtener imagen de fondo configurable desde company_config
        $db = Database::getInstance();
        $bgConfig = $db->fetch("SELECT config_value FROM company_config WHERE config_key = 'login_background_image'");
        $loginBgImage = $bgConfig['config_value'] ?? '';

        // Mostrar formulario
        Helpers::view('auth/login', [
            'title' => 'Iniciar Sesión',
            'csrf_token' => Helpers::generateCsrfToken(),
            'login_background_image' => $loginBgImage
        ]);
    }
    
    // Procesar login
    private function processLogin()
    {
        // Verificar token CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helpers::validateCsrfToken($csrfToken)) {
            Helpers::setFlashMessage('error', 'Token de seguridad inválido');
            Helpers::redirect(Config::getBaseUrl() . '?route=login');
            return;
        }

        // Rate limiting
        $clientIP = SecurityHelper::getClientIP();
        if (!SecurityHelper::checkRateLimit($clientIP, 'login', 5, 900)) {
            Helpers::setFlashMessage('error', 'Demasiados intentos de login. Por favor intenta más tarde.');
            Helpers::redirect(Config::getBaseUrl() . '?route=login');
            return;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        $result = $this->auth->login($email, $password, $remember);

        if ($result['success']) {
            // Verificar si requiere cambio de contraseña
            if (isset($result['user']['password_change_required']) && $result['user']['password_change_required']) {
                Helpers::setFlashMessage('warning', 'Debes cambiar tu contraseña antes de continuar');
                Helpers::redirect(Config::getBaseUrl() . '?route=change-password');
                return;
            }

            Helpers::setFlashMessage('success', 'Bienvenido ' . $result['user']['nombre']);

            // Redirigir según tipo de usuario
            $redirectTo = $this->auth->isAdmin() ? 'admin' : 'client';
            Helpers::redirect(Config::getBaseUrl() . "?route={$redirectTo}");
        } else {
            Helpers::setFlashMessage('error', $result['message']);
            Helpers::redirect(Config::getBaseUrl() . '?route=login');
        }
    }
    
    // Mostrar formulario de registro
    public function register()
    {
        // Si ya está logueado, redirigir
        if ($this->auth->isLoggedIn()) {
            $redirectTo = $this->auth->isAdmin() ? 'admin' : 'home';
            Helpers::redirect(Config::getBaseUrl() . "?route={$redirectTo}");
        }
        
        // Si es POST, procesar registro
        if (Helpers::isPost()) {
            $this->processRegister();
            return;
        }
        
        // Mostrar formulario
        Helpers::view('auth/register', [
            'title' => 'Registrarse',
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }
    
    // Procesar registro
    private function processRegister()
    {
        // Verificar token CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helpers::validateCsrfToken($csrfToken)) {
            Helpers::setFlashMessage('error', 'Token de seguridad inválido');
            Helpers::redirect(Config::getBaseUrl() . '?route=register');
            return;
        }

        // Rate limiting
        $clientIP = SecurityHelper::getClientIP();
        if (!SecurityHelper::checkRateLimit($clientIP, 'register', 3, 3600)) {
            Helpers::setFlashMessage('error', 'Demasiados intentos de registro. Por favor intenta más tarde.');
            Helpers::redirect(Config::getBaseUrl() . '?route=register');
            return;
        }

        $data = [
            'nombre' => $_POST['nombre'] ?? '',
            'email' => $_POST['email'] ?? '',
            'telefono' => $_POST['telefono'] ?? '',
            'password' => $_POST['password'] ?? '',
        ];

        // Verificar confirmación de contraseña
        $confirmPassword = $_POST['confirm_password'] ?? '';
        if ($data['password'] !== $confirmPassword) {
            Helpers::setFlashMessage('error', 'Las contraseñas no coinciden');
            Helpers::redirect(Config::getBaseUrl() . '?route=register');
            return;
        }

        // Validar fortaleza de contraseña
        $passwordCheck = SecurityHelper::validatePasswordStrength($data['password'], [
            'min_length' => 8
        ]);

        if (!$passwordCheck['valid']) {
            Helpers::setFlashMessage('error', $passwordCheck['message']);
            Helpers::redirect(Config::getBaseUrl() . '?route=register');
            return;
        }

        $result = $this->auth->register($data);

        if ($result['success']) {
            Helpers::setFlashMessage('success', 'Usuario registrado exitosamente. Puedes iniciar sesión ahora.');
            Helpers::redirect(Config::getBaseUrl() . '?route=login');
        } else {
            Helpers::setFlashMessage('error', $result['message']);
            Helpers::redirect(Config::getBaseUrl() . '?route=register');
        }
    }
    
    // Mostrar formulario de login para administradores
    public function loginAdmin()
    {
        // Si ya está logueado
        if ($this->auth->isLoggedIn()) {
            // Si es admin, ir al panel
            if ($this->auth->isAdmin()) {
                Helpers::redirect(Config::getBaseUrl() . "?route=admin");
                return;
            }
            // Si no es admin, cerrar sesión y mostrar formulario
            $this->auth->logout();
        }

        // Si es POST, procesar login
        if (Helpers::isPost()) {
            $this->processAdminLogin();
            return;
        }

        // Obtener imagen de fondo configurable para admin login desde company_config
        $db = Database::getInstance();
        $bgConfig = $db->fetch("SELECT config_value FROM company_config WHERE config_key = 'admin_login_background_image'");
        $adminLoginBgImage = $bgConfig['config_value'] ?? '';

        // Mostrar formulario de admin
        Helpers::view('auth/login-admin', [
            'title' => 'Acceso Administrativo',
            'csrf_token' => Helpers::generateCsrfToken(),
            'admin_login_background_image' => $adminLoginBgImage
        ]);
    }

    // Procesar login de administrador
    private function processAdminLogin()
    {
        // Verificar token CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helpers::validateCsrfToken($csrfToken)) {
            Helpers::setFlashMessage('error', 'Token de seguridad inválido');
            Helpers::redirect(Config::getBaseUrl() . '?route=admin/login');
            return;
        }

        // Rate limiting más estricto para admin
        $clientIP = SecurityHelper::getClientIP();
        if (!SecurityHelper::checkRateLimit($clientIP, 'admin_login', 3, 900)) {
            Helpers::setFlashMessage('error', 'Demasiados intentos de acceso. Por favor intenta más tarde.');
            Helpers::redirect(Config::getBaseUrl() . '?route=admin/login');
            return;
        }

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        $result = $this->auth->login($email, $password, $remember);

        if ($result['success']) {
            // IMPORTANTE: Verificar que sea admin
            if (!$this->auth->isAdmin()) {
                $this->auth->logout();
                Helpers::setFlashMessage('error', 'Acceso denegado. Esta área es solo para administradores.');
                Helpers::redirect(Config::getBaseUrl() . '?route=admin/login');
                return;
            }

            Helpers::setFlashMessage('success', 'Bienvenido al panel administrativo, ' . $result['user']['nombre']);
            Helpers::redirect(Config::getBaseUrl() . "?route=admin");
        } else {
            Helpers::setFlashMessage('error', $result['message']);
            Helpers::redirect(Config::getBaseUrl() . '?route=admin/login');
        }
    }

    // Cerrar sesión
    public function logout()
    {
        // Verificar si es admin ANTES de cerrar sesión
        $wasAdmin = $this->auth->isAdmin();

        $result = $this->auth->logout();

        if ($result['success']) {
            Helpers::setFlashMessage('success', 'Has cerrado sesión correctamente');
        }

        // Redirigir según el tipo de usuario
        if ($wasAdmin) {
            Helpers::redirect(Config::getBaseUrl() . '?route=admin/login');
        } else {
            Helpers::redirect(Config::getBaseUrl());
        }
    }
    
    // Perfil de usuario
    public function profile()
    {
        $this->auth->requireAuth();
        
        $user = $this->auth->getCurrentUser();
        
        // Si es POST, actualizar perfil
        if (Helpers::isPost()) {
            $this->updateProfile();
            return;
        }
        
        Helpers::view('auth/profile', [
            'title' => 'Mi Perfil',
            'user' => $user,
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }
    
    // Actualizar perfil
    private function updateProfile()
    {
        // Verificar token CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helpers::validateCsrfToken($csrfToken)) {
            Helpers::setFlashMessage('error', 'Token de seguridad inválido');
            Helpers::redirect(Config::getBaseUrl() . '?route=profile');
            return;
        }
        
        $user = $this->auth->getCurrentUser();
        $db = Database::getInstance();
        
        $updateData = [
            'nombre' => Helpers::sanitizeString($_POST['nombre'] ?? ''),
            'telefono' => Helpers::sanitizeString($_POST['telefono'] ?? '')
        ];
        
        // Validar datos básicos
        if (empty($updateData['nombre'])) {
            Helpers::setFlashMessage('error', 'El nombre es requerido');
            Helpers::redirect(Config::getBaseUrl() . '?route=profile');
            return;
        }
        
        // Actualizar información básica
        $updated = $db->update(
            'usuarios',
            $updateData,
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
                Helpers::redirect(Config::getBaseUrl() . '?route=profile');
                return;
            }

            $passwordResult = $this->auth->changePassword($currentPassword, $newPassword);

            if (!$passwordResult['success']) {
                Helpers::setFlashMessage('error', $passwordResult['message']);
                Helpers::redirect(Config::getBaseUrl() . '?route=profile');
                return;
            }
        }

        // Refrescar datos del usuario en la sesión
        $this->auth->refreshCurrentUser();

        if ($updated) {
            Helpers::setFlashMessage('success', 'Perfil actualizado exitosamente');
        } else {
            Helpers::setFlashMessage('warning', 'No se realizaron cambios');
        }

        Helpers::redirect(Config::getBaseUrl() . '?route=profile');
    }
    
    // Verificar sesión vía AJAX
    public function checkSession()
    {
        header('Content-Type: application/json; charset=utf-8');

        $valid = $this->auth->checkSessionTimeout();
        $user = $this->auth->getCurrentUser();

        echo json_encode([
            'valid' => $valid,
            'logged_in' => $this->auth->isLoggedIn(),
            'user' => $user ? [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email'],
                'tipo' => $user['tipo']
            ] : null
        ]);
    }

    // Mostrar formulario de cambio forzado de contraseña
    public function changePassword()
    {
        $this->auth->requireAuth();

        $user = $this->auth->getCurrentUser();

        // Si es POST, procesar cambio
        if (Helpers::isPost()) {
            $this->processForceChangePassword();
            return;
        }

        // Mostrar formulario
        Helpers::view('auth/change_password', [
            'title' => 'Cambiar Contraseña',
            'user' => $user,
            'forced' => true,
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }

    // Procesar cambio forzado de contraseña
    private function processForceChangePassword()
    {
        // Verificar token CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Helpers::validateCsrfToken($csrfToken)) {
            Helpers::setFlashMessage('error', 'Token de seguridad inválido');
            Helpers::redirect(Config::getBaseUrl() . '?route=change-password');
            return;
        }

        $user = $this->auth->getCurrentUser();
        $db = Database::getInstance();

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validaciones
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            Helpers::setFlashMessage('error', 'Todos los campos son requeridos');
            Helpers::redirect(Config::getBaseUrl() . '?route=change-password');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            Helpers::setFlashMessage('error', 'Las contraseñas nuevas no coinciden');
            Helpers::redirect(Config::getBaseUrl() . '?route=change-password');
            return;
        }

        // Validar fortaleza de contraseña
        $passwordCheck = SecurityHelper::validatePasswordStrength($newPassword, [
            'min_length' => 8
        ]);

        if (!$passwordCheck['valid']) {
            Helpers::setFlashMessage('error', $passwordCheck['message']);
            Helpers::redirect(Config::getBaseUrl() . '?route=change-password');
            return;
        }

        $passwordResult = $this->auth->changePassword($currentPassword, $newPassword);

        if (!$passwordResult['success']) {
            Helpers::setFlashMessage('error', $passwordResult['message']);
            Helpers::redirect(Config::getBaseUrl() . '?route=change-password');
            return;
        }

        // Marcar que ya no requiere cambio de contraseña
        $db->update(
            'usuarios',
            [
                'password_change_required' => 0,
                'last_password_change' => date('Y-m-d H:i:s')
            ],
            'id = :id',
            ['id' => $user['id']]
        );

        Helpers::setFlashMessage('success', 'Contraseña actualizada exitosamente');

        // Redirigir según tipo de usuario
        $redirectTo = $this->auth->isAdmin() ? 'admin' : 'home';
        Helpers::redirect(Config::getBaseUrl() . "?route={$redirectTo}");
    }
}