<?php

namespace App\Core;

class Config
{
    // Configuración cargada dinámicamente
    private static $config = null;

    // Cache de configuración de base de datos
    private static $dbSettings = null;

    // Configuración de base de datos (valores por defecto)
    const DB_HOST = '127.0.0.1';
    const DB_NAME = 'travel_mvp';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHARSET = 'utf8mb4';

    // Configuración de la aplicación
    const APP_NAME = 'Travel Mayan World';
    const APP_DESCRIPTION = 'Descubre la magia del mundo maya con tours personalizados';
    const APP_VERSION = '1.0.0';

    // URL Base - se detecta automáticamente si se deja null
    // En desarrollo local: 'http://localhost/travel-agency-mvp/public/'
    // En producción: null para auto-detección
    const BASE_URL = null;

    // Tema del Admin: 'new' | 'old'
    const ADMIN_THEME = 'new';
    // Versionado de assets para cache busting
    const ASSET_VERSION = '20251211v2';
    // CDN opcional para assets (dejar vacío para desactivar)
    const CDN_BASE_URL = '';
    
    // Información de la empresa
    const COMPANY_PHONE = '+502 7867-5095';
    const COMPANY_EMAIL = 'info@mayanworldtravelagency.com';
    const COMPANY_ADDRESS = 'Flores, Petén, Guatemala';
    const COMPANY_WEBSITE = 'https://www.mayanworldtravelagency.com';
    
    // Redes sociales
    const SOCIAL_FACEBOOK = 'https://facebook.com/MayanWorldTravel';
    const SOCIAL_INSTAGRAM = 'https://instagram.com/mayanworldtravel';
    const SOCIAL_WHATSAPP = '+50278675095';
    
    // Configuración de sesiones
    const SESSION_TIMEOUT = 3600; // 1 hora
    const SESSION_NAME = 'travel_session';
    
    // Configuración de archivos
    const UPLOAD_PATH = '/uploads/';
    const MAX_FILE_SIZE = 5242880; // 5MB
    const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf'];
    
    // Configuración de paginación
    const ITEMS_PER_PAGE = 12;

    // Stripe - NUNCA hardcodear keys en producción
    // Usar config.local.php para configurar las keys reales
    const STRIPE_PUBLISHABLE_KEY = 'pk_test_REPLACE_WITH_YOUR_KEY';
    const STRIPE_SECRET_KEY = 'sk_test_REPLACE_WITH_YOUR_KEY';
    const STRIPE_WEBHOOK_SECRET = 'whsec_REPLACE_WITH_YOUR_SECRET';

    // Pagos
    const DEPOSIT_RATE = 0.30; // 30% de anticipo

    /**
     * Cargar configuración desde archivo local si existe
     */
    public static function loadLocalConfig()
    {
        if (self::$config !== null) {
            return self::$config;
        }

        $localConfigPath = dirname(dirname(__DIR__)) . '/config.local.php';

        if (file_exists($localConfigPath)) {
            self::$config = require $localConfigPath;
        } else {
            self::$config = [];
        }

        return self::$config;
    }

    /**
     * Cargar configuración desde la base de datos
     */
    private static function loadDatabaseSettings()
    {
        if (self::$dbSettings !== null) {
            return self::$dbSettings;
        }

        self::$dbSettings = [];

        try {
            // Obtener instancia de base de datos
            $db = Database::getInstance();

            // Verificar si la tabla payment_settings existe
            $tableExists = $db->fetch(
                "SELECT COUNT(*) as count FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'payment_settings'",
                ['db' => self::DB_NAME]
            );

            if ($tableExists && $tableExists['count'] > 0) {
                // Cargar todas las configuraciones
                $settings = $db->fetchAll("SELECT * FROM payment_settings");

                foreach ($settings as $setting) {
                    $gateway = strtoupper($setting['gateway']);
                    $key = strtoupper($setting['setting_key']);
                    $configKey = $gateway . '_' . $key;

                    self::$dbSettings[$configKey] = $setting['setting_value'];
                }
            }
        } catch (\Exception $e) {
            // Si hay error al cargar de BD, continuar con configuración local
            error_log("Error loading database settings: " . $e->getMessage());
        }

        return self::$dbSettings;
    }

    /**
     * Obtener valor de configuración con fallback
     * Prioridad: 1. Base de datos, 2. config.local.php, 3. valor por defecto
     */
    private static function get($key, $default)
    {
        // 1. Intentar obtener de base de datos primero
        $dbSettings = self::loadDatabaseSettings();
        if (isset($dbSettings[$key]) && $dbSettings[$key] !== '') {
            // Convertir string 'true'/'false' a booleano si es necesario
            if ($dbSettings[$key] === 'true') {
                return true;
            }
            if ($dbSettings[$key] === 'false') {
                return false;
            }
            return $dbSettings[$key];
        }

        // 2. Si no está en BD, intentar con config.local.php
        $config = self::loadLocalConfig();
        return $config[$key] ?? $default;
    }

    /**
     * Limpiar caché de configuración
     * Útil después de actualizar configuraciones en BD
     */
    public static function clearCache()
    {
        self::$dbSettings = null;
        self::$config = null;
    }

    // Obtener configuración de base de datos como array
    public static function getDbConfig()
    {
        return [
            'host' => self::get('DB_HOST', self::DB_HOST),
            'dbname' => self::get('DB_NAME', self::DB_NAME),
            'username' => self::get('DB_USER', self::DB_USER),
            'password' => self::get('DB_PASS', self::DB_PASS),
            'charset' => self::get('DB_CHARSET', self::DB_CHARSET)
        ];
    }
    
    // Obtener URL base
    public static function getBaseUrl()
    {
        // Verificar primero si hay configuración local
        $configuredUrl = self::get('BASE_URL', self::BASE_URL);

        // Si BASE_URL está configurada manualmente, usarla
        if ($configuredUrl !== null) {
            return rtrim($configuredUrl, '/') . '/';
        }

        // Auto-detectar la URL base según el entorno
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Obtener el directorio base del script
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $scriptDir = dirname($scriptName);

        // Normalizar el path
        $basePath = str_replace('\\', '/', $scriptDir);
        $basePath = rtrim($basePath, '/');

        // Si el basePath es solo '/', es la raíz
        if ($basePath === '') {
            $basePath = '/';
        } else {
            $basePath = $basePath . '/';
        }

        return $protocol . '://' . $host . $basePath;
    }
    
    public static function getAssetVersion()
    {
        return self::ASSET_VERSION ?: self::APP_VERSION;
    }
    
    // Verificar si estamos en modo desarrollo
    public static function isDevelopment()
    {
        return $_SERVER['HTTP_HOST'] === 'localhost' ||
               strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false;
    }

    // Verificar si estamos en producción
    public static function isProduction()
    {
        return !self::isDevelopment();
    }

    // Obtener Stripe Publishable Key (desde config.local.php o constante)
    public static function getStripePublishableKey()
    {
        return self::get('STRIPE_PUBLISHABLE_KEY', self::STRIPE_PUBLISHABLE_KEY);
    }

    // Obtener Stripe Secret Key (desde config.local.php o constante)
    public static function getStripeSecretKey()
    {
        return self::get('STRIPE_SECRET_KEY', self::STRIPE_SECRET_KEY);
    }

    // Obtener Stripe Webhook Secret (desde config.local.php o constante)
    public static function getStripeWebhookSecret()
    {
        return self::get('STRIPE_WEBHOOK_SECRET', self::STRIPE_WEBHOOK_SECRET);
    }

    // ========================================
    // PAGGO PAYMENT GATEWAY CONFIG
    // ========================================

    // Obtener Paggo API Key
    public static function getPaggoApiKey()
    {
        return self::get('PAGGO_API_KEY', '');
    }

    // Obtener Paggo Base URL
    public static function getPaggoBaseUrl()
    {
        return self::get('PAGGO_BASE_URL', 'https://api.paggoapp.com');
    }

    // Verificar si Paggo está habilitado y configurado
    public static function isPaggoEnabled()
    {
        return (bool)self::get('PAGGO_ENABLED', false) && !empty(self::getPaggoApiKey());
    }

    // Obtener expiración de link de pago de Paggo (en horas)
    public static function getPaggoLinkExpirationHours()
    {
        return (int)self::get('PAGGO_LINK_EXPIRATION_HOURS', 48);
    }

    // ========================================
    // RECURRENTE PAYMENT GATEWAY CONFIG
    // ========================================

    // Obtener Recurrente Public Key
    public static function getRecurrentePublicKey()
    {
        return self::get('RECURRENTE_PUBLIC_KEY', '');
    }

    // Obtener Recurrente Secret Key
    public static function getRecurrenteSecretKey()
    {
        return self::get('RECURRENTE_SECRET_KEY', '');
    }

    // Obtener Recurrente Base URL
    public static function getRecurrenteBaseUrl()
    {
        return self::get('RECURRENTE_BASE_URL', 'https://app.recurrente.com/api');
    }

    // Obtener Recurrente Webhook Secret
    public static function getRecurrenteWebhookSecret()
    {
        return self::get('RECURRENTE_WEBHOOK_SECRET', '');
    }

    // Verificar si Recurrente está habilitado y configurado
    public static function isRecurrenteEnabled()
    {
        return (bool)self::get('RECURRENTE_ENABLED', false)
            && !empty(self::getRecurrentePublicKey())
            && !empty(self::getRecurrenteSecretKey());
    }

    // Obtener moneda por defecto de Recurrente
    public static function getRecurrenteDefaultCurrency()
    {
        return self::get('RECURRENTE_DEFAULT_CURRENCY', 'USD');
    }

    // ========================================
    // END PAYMENT GATEWAYS CONFIG
    // ========================================

    // Obtener Google reCAPTCHA Site Key
    public static function getRecaptchaSiteKey()
    {
        return self::get('RECAPTCHA_SITE_KEY', '');
    }

    // Obtener Google reCAPTCHA Secret Key
    public static function getRecaptchaSecretKey()
    {
        return self::get('RECAPTCHA_SECRET_KEY', '');
    }

    // Obtener configuración de seguridad
    public static function getSecurityConfig($key, $default = null)
    {
        $config = [
            'force_https' => self::get('FORCE_HTTPS', false),
            'debug_mode' => self::get('DEBUG_MODE', self::isDevelopment()),
            'log_level' => self::get('LOG_LEVEL', self::isDevelopment() ? 'DEBUG' : 'WARNING'),
            'rate_limit_login_attempts' => self::get('RATE_LIMIT_LOGIN_ATTEMPTS', 5),
            'rate_limit_login_window' => self::get('RATE_LIMIT_LOGIN_WINDOW', 900),
            'rate_limit_register_attempts' => self::get('RATE_LIMIT_REGISTER_ATTEMPTS', 3),
            'rate_limit_register_window' => self::get('RATE_LIMIT_REGISTER_WINDOW', 3600),
        ];

        return $key ? ($config[$key] ?? $default) : $config;
    }
}
