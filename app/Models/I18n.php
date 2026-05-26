<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;
use DateTime;
use Exception;

class I18n extends Model
{
    protected $table = 'translations';
    protected $fillable = ['language_id', 'translation_key', 'translation_value', 'context', 'is_system'];
    
    private static $currentLanguage = 'es';
    private static $currentCurrency = 'USD';
    private static $translations = [];
    private static $currencies = [];
    private static $languages = [];
    
    /**
     * Inicializar sistema de internacionalización
     */
    public static function initialize($languageCode = 'es', $currencyCode = 'USD')
    {
        self::$currentLanguage = $languageCode;
        self::$currentCurrency = $currencyCode;
        
        // Cargar idiomas disponibles
        self::loadLanguages();
        
        // Cargar monedas disponibles
        self::loadCurrencies();
        
        // Cargar traducciones del idioma actual
        self::loadTranslations($languageCode);
        
        // Detectar localización si es primera visita
        self::detectUserLocalization();
    }
    
    /**
     * Obtener traducción por clave
     */
    public static function translate($key, $params = [], $context = 'frontend')
    {
        $translation = self::$translations[$key] ?? $key;
        
        // Reemplazar parámetros si existen
        if (!empty($params)) {
            foreach ($params as $param => $value) {
                $translation = str_replace('{' . $param . '}', $value, $translation);
            }
        }
        
        return $translation;
    }
    
    /**
     * Alias corto para translate
     */
    public static function t($key, $params = [], $context = 'frontend')
    {
        return self::translate($key, $params, $context);
    }
    
    /**
     * Cargar traducciones del idioma
     */
    private static function loadTranslations($languageCode)
    {
        $db = Database::getInstance();
        
        $sql = "
            SELECT t.translation_key, t.translation_value
            FROM translations t
            JOIN languages l ON t.language_id = l.id
            WHERE l.code = ? AND l.is_active = TRUE
        ";
        
        $translations = $db->fetchAll($sql, [$languageCode]);
        
        foreach ($translations as $translation) {
            self::$translations[$translation['translation_key']] = $translation['translation_value'];
        }
    }
    
    /**
     * Cargar idiomas disponibles
     */
    private static function loadLanguages()
    {
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM languages WHERE is_active = TRUE ORDER BY is_default DESC, name ASC";
        self::$languages = $db->fetchAll($sql);
    }
    
    /**
     * Cargar monedas disponibles
     */
    private static function loadCurrencies()
    {
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM currencies WHERE is_active = TRUE ORDER BY is_default DESC, name ASC";
        self::$currencies = $db->fetchAll($sql);
    }
    
    /**
     * Detectar localización del usuario automáticamente
     */
    private static function detectUserLocalization()
    {
        // Si ya hay preferencias en sesión, usar esas
        if (isset($_SESSION['language_code']) && isset($_SESSION['currency_code'])) {
            self::$currentLanguage = $_SESSION['language_code'];
            self::$currentCurrency = $_SESSION['currency_code'];
            return;
        }
        
        // Detectar por IP usando servicio externo o base de datos geo
        $userIP = self::getUserIP();
        $countryCode = self::getCountryByIP($userIP);
        
        if ($countryCode) {
            $db = Database::getInstance();
            
            $sql = "
                SELECT l.code as language_code, c.code as currency_code
                FROM geo_localization g
                JOIN languages l ON g.suggested_language_id = l.id
                JOIN currencies c ON g.suggested_currency_id = c.id
                WHERE g.country_code = ? AND g.is_active = TRUE
            ";
            
            $result = $db->fetch($sql, [$countryCode]);
            
            if ($result) {
                self::$currentLanguage = $result['language_code'];
                self::$currentCurrency = $result['currency_code'];
                
                // Guardar en sesión
                $_SESSION['language_code'] = self::$currentLanguage;
                $_SESSION['currency_code'] = self::$currentCurrency;
                
                // Guardar preferencias del usuario
                self::saveUserPreferences();
            }
        }
    }
    
    /**
     * Cambiar idioma actual
     */
    public static function setLanguage($languageCode)
    {
        self::$currentLanguage = $languageCode;
        $_SESSION['language_code'] = $languageCode;
        
        // Recargar traducciones
        self::$translations = [];
        self::loadTranslations($languageCode);
        
        // Actualizar preferencias
        self::saveUserPreferences();
    }
    
    /**
     * Cambiar moneda actual
     */
    public static function setCurrency($currencyCode)
    {
        self::$currentCurrency = $currencyCode;
        $_SESSION['currency_code'] = $currencyCode;
        
        // Actualizar preferencias
        self::saveUserPreferences();
    }
    
    /**
     * Obtener idioma actual
     */
    public static function getCurrentLanguage()
    {
        return self::$currentLanguage;
    }
    
    /**
     * Obtener moneda actual
     */
    public static function getCurrentCurrency()
    {
        return self::$currentCurrency;
    }
    
    /**
     * Obtener todos los idiomas disponibles
     */
    public static function getLanguages()
    {
        return self::$languages;
    }
    
    /**
     * Obtener todas las monedas disponibles
     */
    public static function getCurrencies()
    {
        return self::$currencies;
    }
    
    /**
     * Formatear precio según moneda actual
     */
    public static function formatPrice($amount, $currencyCode = null)
    {
        $currencyCode = $currencyCode ?: self::$currentCurrency;
        
        // Encontrar información de la moneda
        $currency = null;
        foreach (self::$currencies as $curr) {
            if ($curr['code'] === $currencyCode) {
                $currency = $curr;
                break;
            }
        }
        
        if (!$currency) {
            return $amount; // Fallback si no se encuentra la moneda
        }
        
        // Formatear según la moneda
        $formatted = number_format($amount, $currency['decimal_places']);
        
        // Agregar símbolo de moneda
        return $currency['symbol'] . $formatted;
    }
    
    /**
     * Convertir precio a moneda actual
     */
    public static function convertPrice($baseAmount, $baseCurrency = 'USD', $targetCurrency = null)
    {
        $targetCurrency = $targetCurrency ?: self::$currentCurrency;
        
        if ($baseCurrency === $targetCurrency) {
            return $baseAmount;
        }
        
        $db = Database::getInstance();
        
        // Obtener tasa de cambio
        $sql = "SELECT exchange_rate FROM currencies WHERE code = ? AND is_active = TRUE";
        $rate = $db->fetch($sql, [$targetCurrency]);
        
        if (!$rate) {
            return $baseAmount; // Fallback si no hay tasa
        }
        
        return $baseAmount * $rate['exchange_rate'];
    }
    
    /**
     * Formatear fecha según localización
     */
    public static function formatDate($date, $format = null)
    {
        if (!$format) {
            // Formato por defecto según idioma
            $formats = [
                'es' => 'd/m/Y',
                'en' => 'm/d/Y',
                'fr' => 'd/m/Y'
            ];
            $format = $formats[self::$currentLanguage] ?? 'Y-m-d';
        }
        
        if (is_string($date)) {
            $date = new DateTime($date);
        }
        
        return $date->format($format);
    }
    
    /**
     * Obtener traducción de tour
     */
    public static function getTourTranslation($productId, $languageCode = null)
    {
        $languageCode = $languageCode ?: self::$currentLanguage;
        $db = Database::getInstance();
        
        $sql = "
            SELECT pt.*, l.code as language_code
            FROM tour_translations pt
            JOIN languages l ON pt.language_id = l.id
            WHERE pt.tour_id = ? AND l.code = ?
        ";
        
        return $db->fetch($sql, [$productId, $languageCode]);
    }
    
    /**
     * Obtener precio de tour en moneda actual
     */
    public static function getTourPrice($productId, $currencyCode = null)
    {
        $currencyCode = $currencyCode ?: self::$currentCurrency;
        $db = Database::getInstance();
        
        // Primero buscar precio personalizado
        $sql = "
            SELECT pp.price, pp.discount_price
            FROM tour_prices pp
            JOIN currencies c ON pp.currency_id = c.id
            WHERE pp.tour_id = ? AND c.code = ?
        ";
        
        $customPrice = $db->fetch($sql, [$productId, $currencyCode]);
        
        if ($customPrice) {
            return $customPrice;
        }
        
        // Si no hay precio personalizado, convertir desde USD
        $sql = "
            SELECT p.precio as price, p.precio_descuento as discount_price
            FROM tours p
            WHERE p.id = ?
        ";
        
        $basePrice = $db->fetch($sql, [$productId]);
        
        if ($basePrice) {
            return [
                'price' => self::convertPrice($basePrice['price'], 'USD', $currencyCode),
                'discount_price' => $basePrice['discount_price'] 
                    ? self::convertPrice($basePrice['discount_price'], 'USD', $currencyCode)
                    : null
            ];
        }
        
        return null;
    }
    
    /**
     * Guardar preferencias del usuario
     */
    private static function saveUserPreferences()
    {
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'] ?? null;
        $sessionId = session_id();
        $userIP = self::getUserIP();
        
        // Obtener IDs de idioma y moneda
        $languageId = null;
        $currencyId = null;
        
        foreach (self::$languages as $lang) {
            if ($lang['code'] === self::$currentLanguage) {
                $languageId = $lang['id'];
                break;
            }
        }
        
        foreach (self::$currencies as $curr) {
            if ($curr['code'] === self::$currentCurrency) {
                $currencyId = $curr['id'];
                break;
            }
        }
        
        if ($languageId && $currencyId) {
            // Insertar o actualizar preferencias
            $sql = "
                INSERT INTO user_preferences 
                (user_id, session_id, language_id, currency_id, ip_address)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                language_id = VALUES(language_id),
                currency_id = VALUES(currency_id),
                updated_at = CURRENT_TIMESTAMP
            ";
            
            $db->query($sql, [$userId, $sessionId, $languageId, $currencyId, $userIP]);
        }
    }
    
    /**
     * Obtener IP del usuario
     */
    private static function getUserIP()
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Obtener país por IP (implementación básica)
     */
    private static function getCountryByIP($ip)
    {
        // Implementación básica - en producción usar servicio como ipapi.co
        if ($ip === '127.0.0.1' || strpos($ip, '192.168.') === 0) {
            return 'GT'; // Default para desarrollo
        }
        
        // Servicio gratuito para detectar país
        $url = "https://ip-api.com/json/{$ip}?fields=countryCode";
        
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'method' => 'GET'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response) {
                $data = json_decode($response, true);
                return $data['countryCode'] ?? 'GT';
            }
        } catch (Exception $e) {
            // Silently fail and use default
        }
        
        return 'GT'; // Default
    }
    
    /**
     * Actualizar tasas de cambio desde API externa
     */
    public static function updateExchangeRates()
    {
        // Implementar actualización desde API como exchangerate-api.com
        $db = Database::getInstance();
        
        try {
            // API gratuita para tasas de cambio
            $url = "https://api.exchangerate-api.com/v4/latest/USD";
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'GET'
                ]
            ]);
            
            $response = @file_get_contents($url, false, $context);
            
            if ($response) {
                $data = json_decode($response, true);
                $rates = $data['rates'] ?? [];
                
                foreach ($rates as $currencyCode => $rate) {
                    // Actualizar tasas existentes
                    $sql = "
                        UPDATE currencies 
                        SET exchange_rate = ?, last_updated = CURRENT_TIMESTAMP
                        WHERE code = ? AND is_active = TRUE
                    ";
                    
                    $db->query($sql, [$rate, $currencyCode]);
                }
                
                return true;
            }
        } catch (Exception $e) {
            error_log("Error updating exchange rates: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Agregar nueva traducción
     */
    public static function addTranslation($key, $value, $languageCode, $context = 'frontend', $isSystem = false)
    {
        $db = Database::getInstance();
        
        // Obtener ID del idioma
        $languageId = null;
        foreach (self::$languages as $lang) {
            if ($lang['code'] === $languageCode) {
                $languageId = $lang['id'];
                break;
            }
        }
        
        if (!$languageId) {
            return false;
        }
        
        $sql = "
            INSERT INTO translations (language_id, translation_key, translation_value, context, is_system)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            translation_value = VALUES(translation_value),
            updated_at = CURRENT_TIMESTAMP
        ";
        
        try {
            $db->query($sql, [$languageId, $key, $value, $context, $isSystem]);
            
            // Actualizar cache si es el idioma actual
            if ($languageCode === self::$currentLanguage) {
                self::$translations[$key] = $value;
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error adding translation: " . $e->getMessage());
            return false;
        }
    }
}