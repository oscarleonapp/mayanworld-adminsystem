<?php
namespace App\Helpers;

use App\Core\Database;
use Exception;

/**
 * CompanyConfigHelper
 *
 * Helper para obtener configuraciones de la empresa de forma síncrona
 * Para usar en vistas y controladores sin hacer llamadas AJAX
 */

class CompanyConfigHelper
{
    private static $configs = null;
    private static $db = null;

    /**
     * Obtener todas las configuraciones (cached)
     */
    public static function getAll()
    {
        if (self::$configs === null) {
            self::loadConfigs();
        }
        return self::$configs;
    }

    /**
     * Obtener una configuración específica por key
     */
    public static function get($key, $default = '')
    {
        if (self::$configs === null) {
            self::loadConfigs();
        }
        return self::$configs[$key] ?? $default;
    }

    /**
     * Obtener configuraciones por grupo
     */
    public static function getByGroup($group)
    {
        if (self::$configs === null) {
            self::loadConfigs();
        }

        $grouped = [];
        foreach (self::$configs as $key => $value) {
            if (isset(self::$configsRaw[$key]) && self::$configsRaw[$key]['config_group'] === $group) {
                $grouped[$key] = $value;
            }
        }
        return $grouped;
    }

    /**
     * Cargar configuraciones desde la base de datos
     */
    private static function loadConfigs()
    {
        try {
            if (self::$db === null) {
                self::$db = Database::getInstance();
            }

            $results = self::$db->fetchAll("SELECT * FROM company_config ORDER BY config_group ASC, config_order ASC");

            self::$configs = [];
            foreach ($results as $config) {
                self::$configs[$config['config_key']] = $config['config_value'];
            }

        } catch (Exception $e) {
            // Si hay error, usar valores por defecto
            self::$configs = self::getDefaults();
            error_log("Error loading company config: " . $e->getMessage());
        }
    }

    /**
     * Valores por defecto si no se puede cargar desde BD
     */
    private static function getDefaults()
    {
        return [
            'company_name' => 'Travel Mayan World',
            'company_tagline' => 'Descubre la magia del mundo maya',
            'company_description' => 'Agencia de viajes especializada en tours al mundo maya',
            'company_phone' => '+502 7867-5095',
            'company_whatsapp' => '+50278675095',
            'company_email' => 'info@mayanworldtravelagency.com',
            'company_address' => 'Flores, Petén, Guatemala',
            'social_facebook' => 'https://facebook.com/MayanWorldTravel',
            'social_instagram' => 'https://instagram.com/mayanworldtravel',
            'social_twitter' => '',
            'social_youtube' => '',
            'logo_url' => '',
            'logo_dark_url' => '',
            'favicon_url' => '',
            'primary_color' => '#007bff',
            'secondary_color' => '#6c757d'
        ];
    }

    /**
     * Refrescar cache de configuraciones
     */
    public static function refresh()
    {
        self::$configs = null;
        self::loadConfigs();
    }

    /**
     * Verificar si existe una configuración
     */
    public static function has($key)
    {
        if (self::$configs === null) {
            self::loadConfigs();
        }
        return isset(self::$configs[$key]);
    }

    /**
     * Obtener configuraciones de contacto para mostrar en footer/contacto
     */
    public static function getContactInfo()
    {
        return [
            'phone' => self::get('company_phone'),
            'whatsapp' => self::get('company_whatsapp'),
            'email' => self::get('company_email'),
            'address' => self::get('company_address')
        ];
    }

    /**
     * Obtener configuraciones de redes sociales
     */
    public static function getSocialMedia()
    {
        return [
            'facebook' => self::get('social_facebook'),
            'instagram' => self::get('social_instagram'),
            'twitter' => self::get('social_twitter'),
            'youtube' => self::get('social_youtube')
        ];
    }

    /**
     * Obtener configuraciones de branding
     */
    public static function getBranding()
    {
        return [
            'logo' => self::get('logo_url'),
            'logo_dark' => self::get('logo_dark_url'),
            'favicon' => self::get('favicon_url'),
            'primary_color' => self::get('primary_color'),
            'secondary_color' => self::get('secondary_color')
        ];
    }

    /**
     * Formatear número de WhatsApp para enlace directo
     */
    public static function getWhatsAppLink($message = '')
    {
        $number = self::get('company_whatsapp');
        // Limpiar número (quitar espacios, guiones, etc.)
        $cleanNumber = preg_replace('/[^0-9+]/', '', $number);

        $link = "https://wa.me/" . ltrim($cleanNumber, '+');

        if (!empty($message)) {
            $link .= "?text=" . urlencode($message);
        }

        return $link;
    }

    /**
     * Formatear número de teléfono para tel: link
     */
    public static function getPhoneLink()
    {
        $number = self::get('company_phone');
        return "tel:" . preg_replace('/[^0-9+]/', '', $number);
    }

    /**
     * Obtener mailto link
     */
    public static function getEmailLink($subject = '', $body = '')
    {
        $email = self::get('company_email');
        $link = "mailto:" . $email;

        $params = [];
        if (!empty($subject)) {
            $params[] = "subject=" . urlencode($subject);
        }
        if (!empty($body)) {
            $params[] = "body=" . urlencode($body);
        }

        if (!empty($params)) {
            $link .= "?" . implode("&", $params);
        }

        return $link;
    }
}
