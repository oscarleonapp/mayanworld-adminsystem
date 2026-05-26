<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Models\ReferralProgram;
use Exception;

class ReferralController extends BaseController
{
    private $referralProgram;
    
    public function __construct()
    {
        parent::__construct();
        $this->referralProgram = new ReferralProgram();
    }

    /**
     * Dashboard principal del programa de referidos
     */
    public function dashboard()
    {
        // Verificar autenticación
        if (!Auth::isLoggedIn()) {
            header('Location: ?route=login');
            exit;
        }

        $userId = Auth::getUserId();
        
        try {
            // Obtener datos completos del dashboard
            $dashboardData = $this->referralProgram->getUserDashboard($userId);
            
            // Obtener estadísticas adicionales
            $recentActivity = $this->referralProgram->getRecentActivity($userId, 10);
            $availableChallenges = $this->referralProgram->getAvailableChallenges($userId);
            $nextLevelInfo = $this->referralProgram->getNextLevelInfo($userId);
            
            $this->view('referral/dashboard', [
                'dashboard' => $dashboardData,
                'recent_activity' => $recentActivity,
                'challenges' => $availableChallenges,
                'next_level' => $nextLevelInfo,
                'success_message' => $_SESSION['referral_success'] ?? null
            ]);
            
            // Limpiar mensaje de éxito
            unset($_SESSION['referral_success']);
            
        } catch (Exception $e) {
            $this->view('referral/dashboard', [
                'error' => 'Error al cargar el dashboard: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Proceso de inscripción al programa de referidos
     */
    public function enroll()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Validar token CSRF
            if (!$this->validateCsrfToken()) {
                $this->jsonResponse(['success' => false, 'message' => 'Token inválido']);
                return;
            }

            if (!Auth::isLoggedIn()) {
                $this->jsonResponse(['success' => false, 'message' => 'Debes iniciar sesión']);
                return;
            }

            $userId = Auth::getUserId();
            $userEmail = Auth::getUserEmail();
            
            try {
                $enrollmentData = [
                    'user_id' => $userId,
                    'user_email' => $userEmail,
                    'phone' => $_POST['phone'] ?? null,
                    'social_media' => [
                        'facebook' => $_POST['facebook'] ?? null,
                        'instagram' => $_POST['instagram'] ?? null,
                        'whatsapp' => $_POST['whatsapp'] ?? null
                    ]
                ];

                $result = $this->referralProgram->enrollUser($enrollmentData);
                
                if ($result['success']) {
                    $_SESSION['referral_success'] = 'Te has inscrito exitosamente al programa de referidos';
                    $this->jsonResponse([
                        'success' => true, 
                        'message' => 'Inscripción exitosa',
                        'referral_code' => $result['referral_code'],
                        'redirect' => '?route=referral/dashboard'
                    ]);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => $result['message']]);
                }
                
            } catch (Exception $e) {
                $this->jsonResponse(['success' => false, 'message' => 'Error en inscripción: ' . $e->getMessage()]);
            }
        } else {
            // Mostrar formulario de inscripción
            if (!Auth::isLoggedIn()) {
                header('Location: ?route=login');
                exit;
            }

            // Verificar si ya está inscrito
            $userId = Auth::getUserId();
            $isEnrolled = $this->referralProgram->isUserEnrolled($userId);
            
            if ($isEnrolled) {
                header('Location: ?route=referral/dashboard');
                exit;
            }

            $this->view('referral/enroll');
        }
    }

    /**
     * Procesar clic en enlace de referido
     */
    public function processClick($referralCode)
    {
        try {
            $visitorData = [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'referrer' => $_SERVER['HTTP_REFERER'] ?? null,
                'utm_source' => $_GET['utm_source'] ?? null,
                'utm_medium' => $_GET['utm_medium'] ?? null,
                'utm_campaign' => $_GET['utm_campaign'] ?? null
            ];

            $result = $this->referralProgram->processReferralClick($referralCode, $visitorData);
            
            if ($result['success']) {
                // Establecer cookie para tracking
                setcookie('referral_code', $referralCode, time() + (30 * 24 * 60 * 60), '/'); // 30 días
                
                // Redirigir a la página principal con banner especial
                $_SESSION['referral_banner'] = [
                    'code' => $referralCode,
                    'referrer_name' => $result['referrer_name'] ?? 'Un amigo',
                    'discount' => $result['discount'] ?? 10
                ];
                
                header('Location: ?route=home&ref=' . $referralCode);
            } else {
                // Código inválido, redirigir a home sin banner
                header('Location: ?route=home');
            }
            
        } catch (Exception $e) {
            error_log('Error procesando clic referido: ' . $e->getMessage());
            header('Location: ?route=home');
        }
        exit;
    }

    /**
     * Compartir en redes sociales
     */
    public function share()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validateCsrfToken()) {
                $this->jsonResponse(['success' => false, 'message' => 'Token inválido']);
                return;
            }

            if (!Auth::isLoggedIn()) {
                $this->jsonResponse(['success' => false, 'message' => 'Debes iniciar sesión']);
                return;
            }

            $userId = Auth::getUserId();
            $platform = $_POST['platform'] ?? '';
            $shareData = $_POST['share_data'] ?? [];

            try {
                $result = $this->referralProgram->shareOnSocialMedia($userId, $platform, $shareData);
                
                if ($result['success']) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Compartido exitosamente',
                        'points_earned' => $result['points_earned'] ?? 0,
                        'share_url' => $result['share_url']
                    ]);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => $result['message']]);
                }
                
            } catch (Exception $e) {
                $this->jsonResponse(['success' => false, 'message' => 'Error al compartir: ' . $e->getMessage()]);
            }
        }
    }

    /**
     * Canjear recompensas
     */
    public function redeemReward()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validateCsrfToken()) {
                $this->jsonResponse(['success' => false, 'message' => 'Token inválido']);
                return;
            }

            if (!Auth::isLoggedIn()) {
                $this->jsonResponse(['success' => false, 'message' => 'Debes iniciar sesión']);
                return;
            }

            $userId = Auth::getUserId();
            $rewardId = $_POST['reward_id'] ?? null;

            if (!$rewardId) {
                $this->jsonResponse(['success' => false, 'message' => 'ID de recompensa requerido']);
                return;
            }

            try {
                $result = $this->referralProgram->redeemReward($userId, $rewardId);
                
                if ($result['success']) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Recompensa canjeada exitosamente',
                        'reward_details' => $result['reward_details'],
                        'remaining_points' => $result['remaining_points']
                    ]);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => $result['message']]);
                }
                
            } catch (Exception $e) {
                $this->jsonResponse(['success' => false, 'message' => 'Error al canjear: ' . $e->getMessage()]);
            }
        }
    }

    /**
     * Obtener estadísticas en tiempo real
     */
    public function getStats()
    {
        if (!Auth::isLoggedIn()) {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        $userId = Auth::getUserId();
        
        try {
            $stats = $this->referralProgram->getUserStats($userId);
            $this->jsonResponse(['success' => true, 'stats' => $stats]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error al obtener estadísticas']);
        }
    }

    /**
     * Obtener actividad reciente
     */
    public function getRecentActivity()
    {
        if (!Auth::isLoggedIn()) {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        $userId = Auth::getUserId();
        $limit = $_GET['limit'] ?? 10;
        
        try {
            $activity = $this->referralProgram->getRecentActivity($userId, $limit);
            $this->jsonResponse(['success' => true, 'activity' => $activity]);
            
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error al obtener actividad']);
        }
    }

    /**
     * Generar código promocional personalizado
     */
    public function generatePromoCode()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validateCsrfToken()) {
                $this->jsonResponse(['success' => false, 'message' => 'Token inválido']);
                return;
            }

            if (!Auth::isLoggedIn()) {
                $this->jsonResponse(['success' => false, 'message' => 'Debes iniciar sesión']);
                return;
            }

            $userId = Auth::getUserId();
            $codeData = [
                'custom_code' => $_POST['custom_code'] ?? null,
                'discount_type' => $_POST['discount_type'] ?? 'percentage',
                'discount_value' => $_POST['discount_value'] ?? 10,
                'max_uses' => $_POST['max_uses'] ?? 50,
                'expires_at' => $_POST['expires_at'] ?? null
            ];

            try {
                $result = $this->referralProgram->generateCustomPromoCode($userId, $codeData);
                
                if ($result['success']) {
                    $this->jsonResponse([
                        'success' => true,
                        'message' => 'Código promocional generado',
                        'promo_code' => $result['promo_code'],
                        'points_cost' => $result['points_cost']
                    ]);
                } else {
                    $this->jsonResponse(['success' => false, 'message' => $result['message']]);
                }
                
            } catch (Exception $e) {
                $this->jsonResponse(['success' => false, 'message' => 'Error al generar código: ' . $e->getMessage()]);
            }
        }
    }

    /**
     * Página de términos y condiciones del programa
     */
    public function terms()
    {
        $this->view('referral/terms');
    }

    /**
     * Página de preguntas frecuentes
     */
    public function faq()
    {
        $faqs = $this->referralProgram->getFAQs();
        $this->view('referral/faq', ['faqs' => $faqs]);
    }

    /**
     * Leaderboard de referidos
     */
    public function leaderboard()
    {
        $period = $_GET['period'] ?? 'monthly'; // daily, weekly, monthly, all_time
        $limit = $_GET['limit'] ?? 50;
        
        try {
            $leaderboard = $this->referralProgram->getLeaderboard($period, $limit);
            $userRanking = null;
            
            if (Auth::isLoggedIn()) {
                $userId = Auth::getUserId();
                $userRanking = $this->referralProgram->getUserRanking($userId, $period);
            }
            
            $this->view('referral/leaderboard', [
                'leaderboard' => $leaderboard,
                'user_ranking' => $userRanking,
                'period' => $period
            ]);
            
        } catch (Exception $e) {
            $this->view('referral/leaderboard', [
                'error' => 'Error al cargar el ranking: ' . $e->getMessage()
            ]);
        }
    }

    // ==================== MÉTODOS ADMIN ====================

    /**
     * Panel de administración de referidos
     */
    public function adminDashboard()
    {
        if (!Auth::isLoggedIn() || !Auth::isAdmin()) {
            header('Location: ?route=login');
            exit;
        }

        try {
            $adminStats = $this->referralProgram->getAdminDashboardStats();
            $recentReferrals = $this->referralProgram->getRecentReferrals(20);
            $topReferrers = $this->referralProgram->getTopReferrers('monthly', 10);
            $pendingPayouts = $this->referralProgram->getPendingPayouts();
            
            $this->view('admin/referral/dashboard', [
                'stats' => $adminStats,
                'recent_referrals' => $recentReferrals,
                'top_referrers' => $topReferrers,
                'pending_payouts' => $pendingPayouts
            ]);
            
        } catch (Exception $e) {
            $this->view('admin/referral/dashboard', [
                'error' => 'Error al cargar panel: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Gestión de usuarios del programa
     */
    public function adminUsers()
    {
        if (!Auth::isLoggedIn() || !Auth::isAdmin()) {
            header('Location: ?route=login');
            exit;
        }

        $page = $_GET['page'] ?? 1;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? 'all';
        
        try {
            $users = $this->referralProgram->getEnrolledUsers($page, 25, $search, $status);
            
            $this->view('admin/referral/users', [
                'users' => $users,
                'current_page' => $page,
                'search' => $search,
                'status' => $status
            ]);
            
        } catch (Exception $e) {
            $this->view('admin/referral/users', [
                'error' => 'Error al cargar usuarios: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Procesar pagos masivos
     */
    public function adminProcessPayouts()
    {
        if (!Auth::isLoggedIn() || !Auth::isAdmin()) {
            $this->jsonResponse(['success' => false, 'message' => 'No autorizado']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validateCsrfToken()) {
                $this->jsonResponse(['success' => false, 'message' => 'Token inválido']);
                return;
            }

            $payoutIds = $_POST['payout_ids'] ?? [];
            
            if (empty($payoutIds)) {
                $this->jsonResponse(['success' => false, 'message' => 'Selecciona al menos un pago']);
                return;
            }

            try {
                $result = $this->referralProgram->processBulkPayouts($payoutIds);
                
                $this->jsonResponse([
                    'success' => true,
                    'message' => "Procesados {$result['processed']} de {$result['total']} pagos",
                    'processed' => $result['processed'],
                    'failed' => $result['failed']
                ]);
                
            } catch (Exception $e) {
                $this->jsonResponse(['success' => false, 'message' => 'Error al procesar pagos: ' . $e->getMessage()]);
            }
        }
    }

    /**
     * Configuración del programa de referidos
     */
    public function adminSettings()
    {
        if (!Auth::isLoggedIn() || !Auth::isAdmin()) {
            header('Location: ?route=login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->validateCsrfToken()) {
                $_SESSION['error_message'] = 'Token CSRF inválido';
                header('Location: ?route=admin/referral/settings');
                exit;
            }

            try {
                $settings = [
                    'program_enabled' => isset($_POST['program_enabled']),
                    'referral_commission' => $_POST['referral_commission'] ?? 10,
                    'referrer_discount' => $_POST['referrer_discount'] ?? 5,
                    'referee_discount' => $_POST['referee_discount'] ?? 10,
                    'min_payout_amount' => $_POST['min_payout_amount'] ?? 50,
                    'cookie_duration' => $_POST['cookie_duration'] ?? 30,
                    'share_bonus_points' => $_POST['share_bonus_points'] ?? 10,
                    'auto_approve_payouts' => isset($_POST['auto_approve_payouts']),
                    'email_notifications' => isset($_POST['email_notifications'])
                ];

                $result = $this->referralProgram->updateProgramSettings($settings);
                
                if ($result['success']) {
                    $_SESSION['success_message'] = 'Configuración actualizada exitosamente';
                } else {
                    $_SESSION['error_message'] = 'Error al actualizar configuración: ' . $result['message'];
                }
                
            } catch (Exception $e) {
                $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
            }
            
            header('Location: ?route=admin/referral/settings');
            exit;
        }

        try {
            $settings = $this->referralProgram->getProgramSettings();
            
            $this->view('admin/referral/settings', [
                'settings' => $settings,
                'success_message' => $_SESSION['success_message'] ?? null,
                'error_message' => $_SESSION['error_message'] ?? null
            ]);
            
            unset($_SESSION['success_message'], $_SESSION['error_message']);
            
        } catch (Exception $e) {
            $this->view('admin/referral/settings', [
                'error' => 'Error al cargar configuración: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Validar token CSRF
     */
    private function validateCsrfToken()
    {
        $token = $_POST['csrf_token'] ?? '';
        return hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    /**
     * Respuesta JSON estándar
     */
    private function jsonResponse($data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit;
    }
}