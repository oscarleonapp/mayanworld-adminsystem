<?php

namespace App\Core;

abstract class BaseController
{
    protected $auth;
    protected $db;
    protected $currentUser;
    
    public function __construct()
    {
        $this->auth = Auth::getInstance();
        $this->db = Database::getInstance();
        $this->currentUser = $this->auth->getCurrentUser();

        // Evitar que warnings/notices rompan respuestas JSON en peticiones AJAX
        if (Helpers::isAjax()) {
            @ini_set('display_errors', '0');
            @ini_set('html_errors', '0');
        }
    }
    
    // Requerir autenticación
    protected function requireAuth($redirectTo = 'login')
    {
        $this->auth->requireAuth($redirectTo);
    }
    
    // Requerir admin
    protected function requireAdmin($redirectTo = 'home')
    {
        $this->auth->requireAdmin($redirectTo);
    }
    
    // Renderizar vista
    protected function view($viewPath, $data = [])
    {
        // Pasar datos globales a todas las vistas
        $data['auth'] = $this->auth;
        $data['currentUser'] = $this->currentUser;
        
        Helpers::view($viewPath, $data);
    }
    
    // Respuesta JSON
    protected function json($data, $statusCode = 200)
    {
        // Limpiar TODOS los niveles de output buffering
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Iniciar nuevo buffer limpio
        ob_start();

        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);

        ob_end_flush();
        exit;
    }
    
    // Redireccionar con mensaje
    protected function redirect($route, $message = null, $type = 'info')
    {
        if ($message) {
            Helpers::setFlashMessage($type, $message);
        }
        
        $url = Config::getBaseUrl();
        if ($route) {
            $url .= '?route=' . $route;
        }
        
        Helpers::redirect($url);
    }
    
    // Validar token CSRF
    protected function validateCsrf()
    {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
        
        if (!Helpers::validateCsrfToken($token)) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Token de seguridad inválido'], 400);
            } else {
                $this->redirect('home', 'Token de seguridad inválido', 'error');
            }
        }
    }
    
    // Manejar errores de validación
    protected function handleValidationErrors($errors, $redirectRoute = null)
    {
        if (empty($errors)) {
            return;
        }
        
        if (Helpers::isAjax()) {
            $this->json(['success' => false, 'errors' => $errors], 400);
        } else {
            $errorMessage = is_array($errors) ? implode(', ', $errors) : $errors;
            if ($redirectRoute) {
                $this->redirect($redirectRoute, $errorMessage, 'error');
            } else {
                Helpers::setFlashMessage('error', $errorMessage);
            }
        }
    }
    
    // Obtener datos de entrada (POST/GET)
    protected function getInput($key = null, $default = null)
    {
        $input = array_merge($_GET, $_POST);
        
        if ($key === null) {
            return $input;
        }
        
        return $input[$key] ?? $default;
    }
    
    // Sanitizar datos de entrada
    protected function sanitizeInput($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitizeInput'], $data);
        }
        
        return Helpers::sanitizeString($data);
    }
    
    // Paginación
    protected function paginate($model, $page = 1, $perPage = null, $conditions = [], $orderBy = null)
    {
        $perPage = $perPage ?: Config::ITEMS_PER_PAGE;
        $page = max(1, (int)$page);
        
        return $model->paginate($page, $perPage, $conditions, $orderBy);
    }
    
    // Mostrar página 404
    protected function notFound($message = 'Página no encontrada')
    {
        http_response_code(404);
        $this->view('errors/404', [
            'title' => 'Error 404',
            'message' => $message
        ]);
    }
    
    // Mostrar página 403 (acceso denegado)
    protected function forbidden($message = 'Acceso denegado')
    {
        http_response_code(403);
        $this->view('errors/403', [
            'title' => 'Acceso Denegado',
            'message' => $message
        ]);
    }
    
    // Manejar subida de archivos
    protected function handleFileUpload($fileKey, $uploadPath = 'uploads/', $allowedTypes = null)
    {
        if (!isset($_FILES[$fileKey])) {
            return ['success' => false, 'error' => 'No se encontró el archivo'];
        }
        
        $allowedTypes = $allowedTypes ?: Config::ALLOWED_EXTENSIONS;
        
        return Helpers::uploadFile($_FILES[$fileKey], $uploadPath, $allowedTypes);
    }
    
    // Log de actividades (para auditoria)
    protected function logActivity($action, $details = null, $userId = null)
    {
        $userId = $userId ?: ($this->currentUser['id'] ?? null);
        
        try {
            $this->db->insert('activity_log', [
                'user_id' => $userId,
                'action' => $action,
                'details' => $details ? json_encode($details) : null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            // Ignorar errores de log para no afectar funcionalidad principal
        }
    }
    
    // Verificar permisos
    protected function can($permission, $resource = null)
    {
        // Sistema básico de permisos basado en roles
        if (!$this->auth->isLoggedIn()) {
            return false;
        }
        
        $userType = $this->currentUser['tipo'];
        
        // Admins pueden hacer todo
        if ($userType === 'admin') {
            return true;
        }
        
        // Clientes tienen permisos limitados
        if ($userType === 'cliente') {
            $allowedPermissions = ['view_products', 'create_booking', 'view_own_bookings', 'send_messages'];
            return in_array($permission, $allowedPermissions);
        }
        
        return false;
    }
    
    // Obtener parámetros de URL
    protected function getUrlParams()
    {
        $route = $_GET['route'] ?? '';
        $parts = explode('/', trim($route, '/'));
        
        return [
            'controller' => $parts[0] ?? null,
            'action' => $parts[1] ?? null,
            'params' => array_slice($parts, 2)
        ];
    }
}
