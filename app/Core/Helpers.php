<?php

namespace App\Core;

use DateTime;

class Helpers
{
    // Funciones de validación
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validatePhone($phone)
    {
        return preg_match('/^[\+]?[0-9\s\-\(\)]{8,20}$/', $phone);
    }
    
    public static function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    // Funciones de sanitización
    public static function sanitizeString($string)
    {
        if ($string === null) {
            return '';
        }
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    }

    // Escapar para HTML con UTF-8 (usar en vistas)
    public static function e($string)
    {
        if ($string === null || $string === '') {
            return '';
        }
        return htmlspecialchars($string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
    
    public static function sanitizeInt($int)
    {
        return filter_var($int, FILTER_SANITIZE_NUMBER_INT);
    }
    
    public static function sanitizeFloat($float)
    {
        return filter_var($float, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    // Funciones de formato
    public static function formatPrice($price)
    {
        if ($price === null || $price === '' || !is_numeric($price)) {
            return '$0.00';
        }
        $numericPrice = (float)$price;
        return '$' . self::safeNumberFormat($numericPrice, 2, '.', ',');
    }

    // Helper para number_format que maneja nulls
    public static function safeNumberFormat($number, $decimals = 0, $decimal_separator = '.', $thousands_separator = ',')
    {
        if ($number === null || $number === '' || !is_numeric($number)) {
            return '0' . ($decimals > 0 ? '.' . str_repeat('0', $decimals) : '');
        }
        return number_format((float)$number, $decimals, $decimal_separator, $thousands_separator);
    }
    
    public static function formatDate($date, $format = 'd/m/Y')
    {
        if ($date instanceof DateTime) {
            return $date->format($format);
        }
        
        $dateObj = new DateTime($date);
        return $dateObj->format($format);
    }
    
    public static function formatDateTime($datetime, $format = 'd/m/Y H:i')
    {
        if ($datetime instanceof DateTime) {
            return $datetime->format($format);
        }
        
        $dateObj = new DateTime($datetime);
        return $dateObj->format($format);
    }
    
    // Funciones de texto
    public static function truncate($text, $length = 100, $suffix = '...')
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return rtrim(substr($text, 0, $length)) . $suffix;
    }
    
    public static function slug($text)
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-');
    }
    
    // Funciones de archivos
    public static function uploadFile($file, $uploadPath = 'uploads/', $allowedTypes = ['jpg', 'jpeg', 'png'])
    {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            return ['success' => false, 'error' => 'No se recibió ningún archivo'];
        }

        $fileName = $file['name'];
        $fileTmp = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];

        if ($fileError !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
                UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
                UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
                UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco',
                UPLOAD_ERR_EXTENSION => 'Una extensión de PHP detuvo la subida'
            ];
            $errorMsg = $errorMessages[$fileError] ?? 'Error desconocido en la subida';
            return ['success' => false, 'error' => $errorMsg];
        }

        // Validar extensión
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($fileExt, $allowedTypes)) {
            return ['success' => false, 'error' => 'Tipo de archivo no permitido. Permitidos: ' . implode(', ', $allowedTypes)];
        }

        // Validar MIME type real (seguridad adicional)
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'webp' => 'image/webp'
        ];

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fileTmp);
            finfo_close($finfo);

            $expectedMime = $allowedMimes[$fileExt] ?? null;

            if ($expectedMime && $mimeType !== $expectedMime) {
                return ['success' => false, 'error' => 'El tipo de archivo no coincide con su extensión (posible archivo malicioso)'];
            }
        }

        // Validar tamaño
        if ($fileSize > Config::MAX_FILE_SIZE) {
            $maxSizeMB = round(Config::MAX_FILE_SIZE / 1048576, 2);
            return ['success' => false, 'error' => "El archivo es demasiado grande. Máximo: {$maxSizeMB}MB"];
        }

        // Validar que es un archivo subido (seguridad contra ataques)
        if (!is_uploaded_file($fileTmp)) {
            return ['success' => false, 'error' => 'Archivo inválido o no subido correctamente'];
        }

        // Generar nombre único y seguro
        $newFileName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $fileExt;
        $destination = $uploadPath . $newFileName;

        // Crear directorio si no existe con permisos seguros
        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0755, true)) {
                return ['success' => false, 'error' => 'No se pudo crear el directorio de destino'];
            }
        }

        // Mover archivo
        if (move_uploaded_file($fileTmp, $destination)) {
            // Establecer permisos seguros
            chmod($destination, 0644);

            return [
                'success' => true,
                'file' => $newFileName,
                'path' => $destination,
                'size' => $fileSize,
                'mime' => $mimeType ?? 'unknown'
            ];
        } else {
            return ['success' => false, 'error' => 'Error al guardar el archivo en el servidor'];
        }
    }
    
    // Funciones de sesión
    public static function startSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(Config::SESSION_NAME);
            session_start();
        }
    }
    
    public static function setFlashMessage($type, $message)
    {
        self::startSession();
        $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
    }
    
    public static function getFlashMessages()
    {
        self::startSession();
        $messages = $_SESSION['flash_messages'] ?? [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }
    
    public static function hasFlashMessages()
    {
        self::startSession();
        return !empty($_SESSION['flash_messages']);
    }

    // Obtener un mensaje flash específico por tipo
    public static function getFlashMessage($type)
    {
        self::startSession();
        if (isset($_SESSION['flash_messages'])) {
            foreach ($_SESSION['flash_messages'] as $key => $flash) {
                if ($flash['type'] === $type) {
                    $message = $flash['message'];
                    unset($_SESSION['flash_messages'][$key]);
                    return $message;
                }
            }
        }
        return null;
    }

    // Display flash messages
    public static function displayFlashMessage()
    {
        $messages = self::getFlashMessages();
        foreach ($messages as $msg) {
            $type = ($msg['type'] === 'error') ? 'danger' : $msg['type'];
            echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
            echo $msg['message'];
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            echo '</div>';
        }
    }
    
    // Funciones de URL y redirección
    public static function redirect($url)
    {
        header("Location: $url");
        exit();
    }

    public static function getCurrentUrl()
    {
        return (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    // Respuesta JSON para APIs
    public static function jsonResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    public static function asset($path)
    {
        $base = rtrim(Config::CDN_BASE_URL ?: Config::getBaseUrl(), '/') . '/';
        $url = $base . 'assets/' . ltrim($path, '/');
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['css', 'js'])) {
            $sep = (strpos($url, '?') === false) ? '?' : '&';
            $url .= $sep . 'v=' . urlencode(Config::getAssetVersion());
        }
        return $url;
    }

    // Construye una URL absoluta válida para imágenes de tours, tolerando distintos formatos en DB
    public static function tourImage($value = null, $default = 'images/default-destination.jpg')
    {
        if (empty($value)) {
            return self::asset($default);
        }
        $val = trim((string)$value);
        // Compatibilidad: rutas antiguas de tours
        $val = str_replace('/uploads/products/', '/uploads/tours/', $val);
        $val = str_replace('uploads/products/', 'uploads/tours/', $val);
        // Absolutas (externas o full URLs)
        if (preg_match('#^https?://#i', $val)) {
            return $val;
        }
        // Ya apunta a /assets
        if (str_starts_with($val, '/assets/')) {
            return $val;
        }
        // assets relativo (sin slash)
        if (str_starts_with($val, 'assets/')) {
            return rtrim(Config::getBaseUrl(), '/') . '/' . $val;
        }
        // uploads relativo (sin slash) - common for tour images
        if (str_starts_with($val, 'uploads/')) {
            return rtrim(Config::getBaseUrl(), '/') . '/' . $val;
        }
        // Absolute path starting with /uploads/
        if (str_starts_with($val, '/uploads/')) {
            return rtrim(Config::getBaseUrl(), '/') . $val;
        }
        // Rutas relativas comunes
        if (str_starts_with($val, 'images/')) {
            return self::asset($val);
        }
        if (str_starts_with($val, 'tours/')) {
            return self::asset('images/' . $val);
        }
        // Solo nombre de archivo: asumir carpeta uploads/tours
        $val = ltrim($val, '/');
        if (strpos($val, '/') === false) {
            return rtrim(Config::getBaseUrl(), '/') . '/uploads/tours/' . $val;
        }
        // Fallback genérico: adjuntar bajo assets/images
        return self::asset('images/' . $val);
    }
    
    // Funciones de arrays
    public static function arrayGet($array, $key, $default = null)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }
    
    public static function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    public static function isGet()
    {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    public static function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }

        if (!empty($_SERVER['HTTP_ACCEPT']) &&
            stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            return true;
        }

        if ((isset($_POST['ajax']) && $_POST['ajax'] === '1') ||
            (isset($_GET['ajax']) && $_GET['ajax'] === '1')) {
            return true;
        }

        return false;
    }
    
    // Funciones de debug (solo en desarrollo)
    public static function dump($var, $die = true)
    {
        if (Config::isDevelopment()) {
            echo '<pre>';
            var_dump($var);
            echo '</pre>';
            
            if ($die) {
                die();
            }
        }
    }
    
    public static function dd($var)
    {
        self::dump($var, true);
    }
    
    // Generar token CSRF
    public static function generateCsrfToken()
    {
        self::startSession();
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateCsrfToken($token)
    {
        self::startSession();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Función para renderizar vistas
    public static function view($viewPath, $data = [])
    {
        extract($data);

        // Detectar la ruta base del proyecto
        // Si index.php está en la raíz: __DIR__ = /app/core, necesitamos ../../app/views
        // La ruta correcta desde app/core/ es ../../app/views/ o simplemente ../views/
        $viewFile = __DIR__ . '/../views/' . $viewPath . '.php';

        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            if (Config::isDevelopment()) {
                die("Vista no encontrada: {$viewPath}<br>Buscando en: {$viewFile}");
            } else {
                die("Error interno del servidor");
            }
        }
    }
}
