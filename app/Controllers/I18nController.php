<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Database;
use App\Models\I18n;
use DateTime;
use Exception;

class I18nController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * Cambiar idioma
     */
    public function setLanguage()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $languageCode = $_POST['language_code'] ?? '';
            
            if ($languageCode) {
                I18n::setLanguage($languageCode);
                
                // Respuesta AJAX
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => true,
                        'message' => I18n::t('system.language_changed'),
                        'language' => $languageCode
                    ]);
                    return;
                }
                
                // Redireccionar a la página anterior
                $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                header("Location: $referer");
                exit;
            }
        }
        
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
        } else {
            header("Location: /");
        }
        exit;
    }
    
    /**
     * Cambiar moneda
     */
    public function setCurrency()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $currencyCode = $_POST['currency_code'] ?? '';
            
            if ($currencyCode) {
                I18n::setCurrency($currencyCode);
                
                // Respuesta AJAX
                if ($this->isAjaxRequest()) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode([
                        'success' => true,
                        'message' => I18n::t('system.currency_changed'),
                        'currency' => $currencyCode
                    ]);
                    return;
                }
                
                // Redireccionar a la página anterior
                $referer = $_SERVER['HTTP_REFERER'] ?? '/';
                header("Location: $referer");
                exit;
            }
        }
        
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
        } else {
            header("Location: /");
        }
        exit;
    }
    
    /**
     * Obtener traducciones para JavaScript
     */
    public function getTranslations()
    {
        $context = $_GET['context'] ?? 'frontend';
        $keys = $_GET['keys'] ?? '';
        
        $translations = [];
        
        if ($keys) {
            $keyList = explode(',', $keys);
            foreach ($keyList as $key) {
                $translations[trim($key)] = I18n::t(trim($key), [], $context);
            }
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'translations' => $translations,
            'language' => I18n::getCurrentLanguage(),
            'currency' => I18n::getCurrentCurrency()
        ]);
    }
    
    /**
     * Widget de selección de idioma y moneda
     */
    public function languageWidget()
    {
        $languages = I18n::getLanguages();
        $currencies = I18n::getCurrencies();
        $currentLanguage = I18n::getCurrentLanguage();
        $currentCurrency = I18n::getCurrentCurrency();
        
        $this->view('i18n/language_widget', [
            'languages' => $languages,
            'currencies' => $currencies,
            'currentLanguage' => $currentLanguage,
            'currentCurrency' => $currentCurrency
        ]);
    }
    
    /**
     * Convertir precio via AJAX
     */
    public function convertPrice()
    {
        $amount = floatval($_GET['amount'] ?? 0);
        $fromCurrency = $_GET['from'] ?? 'USD';
        $toCurrency = $_GET['to'] ?? I18n::getCurrentCurrency();
        
        if ($amount > 0) {
            $converted = I18n::convertPrice($amount, $fromCurrency, $toCurrency);
            $formatted = I18n::formatPrice($converted, $toCurrency);
            
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'original' => $amount,
                'converted' => $converted,
                'formatted' => $formatted,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency
            ]);
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Invalid amount']);
        }
    }
    
    /**
     * Actualizar tasas de cambio (admin only)
     */
    public function updateExchangeRates()
    {
        // Verificar permisos de administrador
        if (!$this->isAdmin()) {
            if ($this->isAjaxRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $this->redirect('login');
            return;
        }
        
        $success = I18n::updateExchangeRates();
        
        if ($this->isAjaxRequest()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => $success,
                'message' => $success 
                    ? I18n::t('admin.exchange_rates_updated')
                    : I18n::t('admin.exchange_rates_error')
            ]);
        } else {
            $_SESSION['flash'] = [
                'type' => $success ? 'success' : 'error',
                'message' => $success 
                    ? I18n::t('admin.exchange_rates_updated')
                    : I18n::t('admin.exchange_rates_error')
            ];
            
            $this->redirect('admin/i18n');
        }
    }
    
    /**
     * Panel de administración de i18n
     */
    public function admin()
    {
        if (!$this->isAdmin()) {
            $this->redirect('login');
            return;
        }
        
        $languages = I18n::getLanguages();
        $currencies = I18n::getCurrencies();
        
        // Estadísticas
        $db = Database::getInstance();
        
        $stats = [
            'total_translations' => $db->count('translations'),
            'total_languages' => $db->count('languages', 'is_active = 1'),
            'total_currencies' => $db->count('currencies', 'is_active = 1'),
            'tour_translations' => $db->count('tour_translations'),
        ];
        
        $this->view('admin/i18n/dashboard', [
            'languages' => $languages,
            'currencies' => $currencies,
            'stats' => $stats,
            'pageTitle' => I18n::t('admin.i18n_management')
        ]);
    }
    
    /**
     * Gestión de traducciones
     */
    public function translations()
    {
        if (!$this->isAdmin()) {
            $this->redirect('login');
            return;
        }
        
        $db = Database::getInstance();
        $page = intval($_GET['page'] ?? 1);
        $perPage = 50;
        $search = $_GET['search'] ?? '';
        $languageId = intval($_GET['language_id'] ?? 0);
        
        $conditions = [];
        $params = [];
        
        if ($search) {
            $conditions[] = "(t.translation_key LIKE ? OR t.translation_value LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        if ($languageId > 0) {
            $conditions[] = "t.language_id = ?";
            $params[] = $languageId;
        }
        
        $whereClause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        
        $sql = "
            SELECT t.*, l.name as language_name, l.code as language_code
            FROM translations t
            JOIN languages l ON t.language_id = l.id
            $whereClause
            ORDER BY t.translation_key ASC, l.code ASC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $perPage;
        $params[] = ($page - 1) * $perPage;
        
        $translations = $db->fetchAll($sql, $params);
        
        // Contar total para paginación
        $countSql = "
            SELECT COUNT(*) as total
            FROM translations t
            JOIN languages l ON t.language_id = l.id
            $whereClause
        ";
        $countParams = array_slice($params, 0, -2); // Remover LIMIT y OFFSET
        $total = $db->fetch($countSql, $countParams)['total'];
        
        $languages = I18n::getLanguages();
        
        $this->view('admin/i18n/translations', [
            'translations' => $translations,
            'languages' => $languages,
            'currentPage' => $page,
            'totalPages' => ceil($total / $perPage),
            'search' => $search,
            'languageId' => $languageId,
            'pageTitle' => I18n::t('admin.translations_management')
        ]);
    }
    
    /**
     * Gestión de tours multiidioma
     */
    public function tourTranslations()
    {
        if (!$this->isAdmin()) {
            $this->redirect('login');
            return;
        }
        
        $productId = intval($_GET['tour_id'] ?? 0);
        
        if ($productId > 0) {
            // Ver/editar traducciones de un tour específico
            $this->editTourTranslations($productId);
            return;
        }
        
        // Lista de tours con estado de traducciones
        $db = Database::getInstance();
        
        $sql = "
            SELECT 
                p.id,
                p.nombre,
                p.activo,
                COUNT(pt.id) as translations_count,
                GROUP_CONCAT(l.code ORDER BY l.code) as translated_languages
            FROM tours p
            LEFT JOIN tour_translations pt ON p.id = pt.tour_id
            LEFT JOIN languages l ON pt.language_id = l.id
            GROUP BY p.id
            ORDER BY p.nombre ASC
        ";
        
        $products = $db->fetchAll($sql);
        $languages = I18n::getLanguages();
        
        $this->view('admin/i18n/tour_translations', [
            'products' => $products,
            'languages' => $languages,
            'pageTitle' => I18n::t('admin.tour_translations')
        ]);
    }
    
    /**
     * Editar traducciones de un tour
     */
    private function editTourTranslations($productId)
    {
        $db = Database::getInstance();
        
        // Obtener tour
        $product = $db->fetch("SELECT * FROM tours WHERE id = ?", [$productId]);
        
        if (!$product) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => I18n::t('admin.product_not_found')];
            $this->redirect('admin/i18n/tour-translations');
            return;
        }
        
        $languages = I18n::getLanguages();
        $translations = [];
        
        // Obtener traducciones existentes
        foreach ($languages as $language) {
            $translation = I18n::getTourTranslation($productId, $language['code']);
            $translations[$language['code']] = $translation ?: [];
        }
        
        // Procesar formulario si es POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveTourTranslations($productId, $_POST);
            return;
        }
        
        $this->view('admin/i18n/edit_tour_translations', [
            'product' => $product,
            'languages' => $languages,
            'translations' => $translations,
            'pageTitle' => I18n::t('admin.edit_tour_translations', ['product' => $product['nombre']])
        ]);
    }
    
    /**
     * Guardar traducciones de tour
     */
    private function saveTourTranslations($productId, $data)
    {
        $db = Database::getInstance();
        $languages = I18n::getLanguages();
        $errors = 0;
        
        foreach ($languages as $language) {
            $langCode = $language['code'];
            $langId = $language['id'];
            
            if (isset($data[$langCode]) && !empty(array_filter($data[$langCode]))) {
                $translationData = $data[$langCode];
                
                // Preparar datos para insertar/actualizar
                $fields = [
                    'tour_id' => $productId,
                    'language_id' => $langId,
                    'nombre' => $translationData['nombre'] ?? '',
                    'descripcion' => $translationData['descripcion'] ?? '',
                    'descripcion_corta' => $translationData['descripcion_corta'] ?? '',
                    'incluye' => $translationData['incluye'] ?? '',
                    'no_incluye' => $translationData['no_incluye'] ?? '',
                    'itinerario' => $translationData['itinerario'] ?? '',
                    'seo_title' => $translationData['seo_title'] ?? '',
                    'seo_description' => $translationData['seo_description'] ?? '',
                    'seo_keywords' => $translationData['seo_keywords'] ?? ''
                ];
                
                try {
                    // Verificar si ya existe
                    $existing = $db->fetch(
                        "SELECT id FROM tour_translations WHERE tour_id = ? AND language_id = ?",
                        [$productId, $langId]
                    );
                    
                    if ($existing) {
                        // Actualizar
                        unset($fields['tour_id'], $fields['language_id']);
                        $db->update('tour_translations', $fields, 'id = ?', [$existing['id']]);
                    } else {
                        // Insertar
                        $db->insert('tour_translations', $fields);
                    }
                } catch (Exception $e) {
                    $errors++;
                    error_log("Error saving product translation: " . $e->getMessage());
                }
            }
        }
        
        $message = $errors > 0 
            ? I18n::t('admin.translations_saved_with_errors', ['errors' => $errors])
            : I18n::t('admin.translations_saved_successfully');
            
        $_SESSION['flash'] = [
            'type' => $errors > 0 ? 'warning' : 'success',
            'message' => $message
        ];
        
        $this->redirect("admin/i18n/tour-translations?tour_id=$productId");
    }
}
