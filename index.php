<?php
/**
 * Travel Agency MVP - Entry Point
 *
 * ESTRUCTURA PARA cPanel/Namecheap:
 * public_html/
 * ├── index.php (este archivo)
 * ├── assets/
 * ├── uploads/
 * ├── app/
 * ├── vendor/
 * └── config.local.php
 */

// Iniciar output buffering
ob_start();

// Asegurar encabezado UTF-8
header('Content-Type: text/html; charset=UTF-8');

// Configuración de errores (CAMBIAR A 0 en producción)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Cargar el autoloader de Composer
$autoloadPath = __DIR__ . '/vendor/autoload.php';

if (!file_exists($autoloadPath)) {
    die("
    <h1>Error: Dependencias no instaladas</h1>
    <p><strong>vendor/autoload.php</strong> no encontrado en: <code>$autoloadPath</code></p>
    <h2>Solución:</h2>
    <ol>
        <li>En tu computadora local, ejecuta: <code>composer install</code></li>
        <li>Sube la carpeta <code>vendor/</code> completa al servidor</li>
        <li>O sube <code>vendor.zip</code> y descomprímelo en cPanel File Manager</li>
    </ol>
    <p>Ubicación esperada: <code>public_html/vendor/autoload.php</code></p>
    ");
}

require_once $autoloadPath;

// Importar clases necesarias
use App\Core\Config;
use App\Core\Database;
use App\Core\Router;
use App\Core\Helpers;
use App\Core\Auth;
use App\Helpers\CompanyConfigHelper;
use App\Helpers\NavigationHelper;
use App\Helpers\FooterHelper;

// Inicializar sesiones
Helpers::startSession();

// Configurar encoding UTF-8
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
    if (Config::isDevelopment() || (ini_get('display_errors') == 1)) {
        echo "<h1>Error del Sistema</h1>";
        echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Archivo:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
        echo "<details><summary>Stack Trace</summary><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></details>";
    } else {
        // En producción
        error_log("Travel MVP Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        echo "<h1>Error del Sistema</h1>";
        echo "<p>Ha ocurrido un error. Por favor, contacta al administrador.</p>";
    }
}
