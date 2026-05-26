<?php
// Iniciar output buffering para prevenir problemas con headers
ob_start();

// Asegurar encabezado UTF-8 para evitar caracteres mal codificados
header('Content-Type: text/html; charset=UTF-8');

// Inicializar el sistema
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar el autoloader de Composer
require_once '../vendor/autoload.php';

// Importar clases necesarias
use App\Core\Config;
use App\Core\Database;
use App\Core\Router;
use App\Core\Helpers;
use App\Core\Auth;
use App\Helpers\CompanyConfigHelper;
use App\Helpers\NavigationHelper;
use App\Helpers\FooterHelper;

// Inicializar sesiones (ANTES de enviar cualquier header)
Helpers::startSession();

// Configurar encoding UTF-8 (sin enviar header todavía)
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Configurar zona horaria
date_default_timezone_set('America/Mexico_City');

try {
    // Verificar conexión a la base de datos
    $db = Database::getInstance();
    
    // Inicializar sistema de autenticación
    $auth = Auth::getInstance();
    
    // Verificar timeout de sesión
    $auth->checkSessionTimeout();
    
    // Crear e inicializar el router
    $router = new Router();
    
    // Ejecutar la ruta correspondiente
    $router->dispatch();
    
} catch (Exception $e) {
    // Manejo de errores
    if (Config::isDevelopment()) {
        echo "<h1>Error del Sistema</h1>";
        echo "<p><strong>Mensaje:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Archivo:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    } else {
        // En producción, mostrar error genérico y registrar el error
        error_log($e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        echo "<h1>Lo sentimos</h1>";
        echo "<p>Ha ocurrido un error interno. Por favor, inténtelo más tarde.</p>";
    }
}
