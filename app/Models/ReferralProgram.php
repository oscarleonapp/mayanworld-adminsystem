<?php

namespace App\Models;

use App\Core\Model;
use App\Core\Config;
use Exception;

class ReferralProgram extends Model
{
    protected $table = 'referral_users';
    protected $fillable = [
        'user_id', 'user_email', 'user_name', 'user_phone', 'referral_code',
        'status', 'preferred_payout_method', 'payout_details', 'marketing_consent'
    ];

    /**
     * Generar código de referido único
     */
    private function generateReferralCode($userName, $userEmail)
    {
        // Crear código base a partir del nombre y email
        $baseCode = strtoupper(substr(str_replace(' ', '', $userName), 0, 4));
        $emailHash = substr(md5($userEmail), 0, 3);
        
        // Intentar códigos hasta encontrar uno único
        $attempts = 0;
        do {
            if ($attempts === 0) {
                $code = $baseCode . $emailHash;
            } else {
                $code = $baseCode . $emailHash . str_pad($attempts, 2, '0', STR_PAD_LEFT);
            }
            $attempts++;
            
            $exists = $this->db->fetch(
                "SELECT id FROM referral_users WHERE referral_code = ?",
                [$code]
            );
        } while ($exists && $attempts < 100);
        
        if ($attempts >= 100) {
            // Fallback a código completamente aleatorio
            $code = 'REF' . strtoupper(bin2hex(random_bytes(4)));
        }
        
        return $code;
    }

    /**
     * Generar código QR para referido
     */
    private function generateQRCode($referralLink, $referralCode)
    {
        // Directorio para códigos QR
        $qrDir = '../public/assets/qr-codes/';
        if (!is_dir($qrDir)) {
            mkdir($qrDir, 0755, true);
        }
        
        $qrFilename = 'referral-' . $referralCode . '.png';
        $qrPath = $qrDir . $qrFilename;
        
        // En un entorno real, se usaría una biblioteca como PHP QR Code
        // Por ahora, retornamos la ruta donde se guardaría
        return 'assets/qr-codes/' . $qrFilename;
    }

    /**
     * Registrar usuario en el programa de referidos
     */
    public function enrollUser($userData)
    {
        try {
            $this->db->beginTransaction();
            
            // Verificar si ya está registrado
            $existing = $this->db->fetch(
                "SELECT id FROM referral_users WHERE user_email = ?",
                [$userData['user_email']]
            );
            
            if ($existing) {
                throw new Exception("Usuario ya está registrado en el programa");
            }
            
            // Generar código de referido único
            $referralCode = $this->generateReferralCode(
                $userData['user_name'], 
                $userData['user_email']
            );
            
            // Generar enlace de referido
            $referralLink = Config::getBaseUrl() . '?ref=' . $referralCode;
            
            // Generar código QR
            $qrCodePath = $this->generateQRCode($referralLink, $referralCode);
            
            // Crear registro de usuario referidor
            $enrollmentData = array_merge($userData, [
                'referral_code' => $referralCode,
                'referral_link' => $referralLink,
                'qr_code_path' => $qrCodePath,
                'status' => 'active',
                'enrollment_date' => date('Y-m-d H:i:s')
            ]);
            
            $userId = $this->create($enrollmentData);
            
            // Registrar actividad
            $this->logActivity($userId, 'enrollment', 'Se inscribió al Programa Maya Explorers');
            
            // Otorgar badge de bienvenida si existe
            $this->checkBadgeAchievements($userId);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'user_id' => $userId,
                'referral_code' => $referralCode,
                'referral_link' => $referralLink,
                'qr_code_path' => $qrCodePath
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error enrolling user in referral program: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Procesar clic en enlace de referido
     */
    public function processReferralClick($referralCode, $visitorData = [])
    {
        try {
            // Obtener información del referidor
            $referrer = $this->db->fetch(
                "SELECT * FROM referral_users WHERE referral_code = ? AND status = 'active'",
                [$referralCode]
            );
            
            if (!$referrer) {
                return ['success' => false, 'error' => 'Código de referido no válido'];
            }
            
            // Guardar datos en sesión para tracking
            $_SESSION['referral_code'] = $referralCode;
            $_SESSION['referrer_id'] = $referrer['id'];
            $_SESSION['referral_timestamp'] = time();
            
            // Registrar clic (estadísticas)
            $this->updateDailyStats('link_clicks', 1);
            
            // Registrar actividad del referidor
            $this->logActivity(
                $referrer['id'], 
                'referral_click', 
                'Alguien hizo clic en tu enlace de referido',
                null,
                $visitorData
            );
            
            return [
                'success' => true,
                'referrer' => [
                    'name' => $referrer['user_name'],
                    'welcome_message' => "¡{$referrer['user_name']} te invitó a descubrir el mundo Maya!"
                ]
            ];

        } catch (Exception $e) {
            error_log("Error processing referral click: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error procesando referido'];
        }
    }

    /**
     * Registrar nuevo referido
     */
    public function registerReferral($referredData)
    {
        try {
            // Verificar si hay código de referido en sesión
            if (!isset($_SESSION['referrer_id']) || !isset($_SESSION['referral_code'])) {
                return ['success' => false, 'error' => 'No hay referido válido'];
            }
            
            $referrerId = $_SESSION['referrer_id'];
            $referralCode = $_SESSION['referral_code'];
            
            // Verificar que el referido no se esté autoreferenciando
            $referrer = $this->find($referrerId);
            if ($referrer['user_email'] === $referredData['referred_email']) {
                return ['success' => false, 'error' => 'No puedes referirte a ti mismo'];
            }
            
            // Verificar que el email no haya sido referido antes
            $existing = $this->db->fetch(
                "SELECT id FROM referrals WHERE referred_email = ?",
                [$referredData['referred_email']]
            );
            
            if ($existing) {
                return ['success' => false, 'error' => 'Este email ya fue referido anteriormente'];
            }
            
            $this->db->beginTransaction();
            
            // Crear registro de referido
            $referralData = array_merge($referredData, [
                'referrer_id' => $referrerId,
                'referral_source' => $_SESSION['referral_source'] ?? 'direct_link',
                'referral_medium' => $_SESSION['referral_medium'] ?? null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'utm_source' => $_GET['utm_source'] ?? null,
                'utm_medium' => $_GET['utm_medium'] ?? null,
                'utm_campaign' => $_GET['utm_campaign'] ?? null,
                'status' => 'registered'
            ]);
            
            $referralId = $this->db->insert('referrals', $referralData);
            
            // Actualizar contador del referidor
            $this->db->update('referral_users', [
                'total_referrals' => $referrer['total_referrals'] + 1,
                'total_points' => $referrer['total_points'] + 50 // 50 puntos por registro
            ], ['id' => $referrerId]);
            
            // Crear recompensa para el referido (si aplica)
            $config = $this->getProgramConfig();
            if ($config['referred_reward_value'] > 0) {
                $this->createReward($referrerId, [
                    'reward_type' => 'referral_signup_bonus',
                    'reward_category' => 'credit',
                    'reward_amount' => $config['referred_reward_value'],
                    'related_referral_id' => $referralId,
                    'description' => 'Bono por registro de nuevo referido'
                ]);
            }
            
            // Registrar actividades
            $this->logActivity(
                $referrerId, 
                'referral_registered', 
                "¡{$referredData['referred_name']} se registró usando tu código!",
                $referralId
            );
            
            // Verificar logros y subidas de nivel
            $this->checkBadgeAchievements($referrerId);
            $this->checkLevelUpgrade($referrerId);
            
            // Actualizar estadísticas
            $this->updateDailyStats('new_referrals', 1);
            
            $this->db->commit();
            
            // Limpiar sesión
            unset($_SESSION['referrer_id'], $_SESSION['referral_code'], $_SESSION['referral_timestamp']);
            
            return [
                'success' => true,
                'referral_id' => $referralId,
                'referrer_name' => $referrer['user_name'],
                'reward_earned' => $config['referred_reward_value']
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error registering referral: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error registrando referido'];
        }
    }

    /**
     * Procesar conversión de referido (primera compra)
     */
    public function processReferralConversion($referredEmail, $bookingAmount, $bookingId)
    {
        try {
            // Buscar el referido
            $referral = $this->db->fetch(
                "SELECT * FROM referrals WHERE referred_email = ? AND status IN ('registered', 'pending')",
                [$referredEmail]
            );
            
            if (!$referral) {
                return ['success' => false, 'error' => 'Referido no encontrado'];
            }
            
            $config = $this->getProgramConfig();
            
            // Verificar monto mínimo
            if ($bookingAmount < $config['min_booking_amount']) {
                return ['success' => false, 'error' => 'Monto no alcanza el mínimo para comisión'];
            }
            
            // Usar procedimiento almacenado para procesar la conversión
            $this->db->execute("CALL ProcessReferralConversion(?, ?, ?)", [
                $referral['id'], $bookingAmount, $bookingId
            ]);
            
            // Obtener información actualizada del referidor
            $referrer = $this->find($referral['referrer_id']);
            
            return [
                'success' => true,
                'referrer_id' => $referral['referrer_id'],
                'commission_earned' => $this->calculateCommission($bookingAmount, $referrer['current_level']),
                'referrer_name' => $referrer['user_name']
            ];

        } catch (Exception $e) {
            error_log("Error processing referral conversion: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error procesando conversión'];
        }
    }

    /**
     * Obtener dashboard completo del usuario
     */
    public function getUserDashboard($userId)
    {
        try {
            // Información principal del usuario
            $userInfo = $this->db->fetch(
                "SELECT * FROM referral_dashboard WHERE id = ?",
                [$userId]
            );
            
            if (!$userInfo) {
                return ['success' => false, 'error' => 'Usuario no encontrado'];
            }
            
            // Referidos recientes
            $recentReferrals = $this->db->fetchAll(
                "SELECT * FROM referrals 
                 WHERE referrer_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT 10",
                [$userId]
            );
            
            // Recompensas recientes
            $recentRewards = $this->db->fetchAll(
                "SELECT * FROM referral_rewards 
                 WHERE user_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT 10",
                [$userId]
            );
            
            // Badges obtenidos
            $userBadges = $this->db->fetchAll(
                "SELECT ub.*, rb.badge_name, rb.badge_description, rb.badge_icon, rb.badge_color
                 FROM user_badges ub
                 JOIN referral_badges rb ON ub.badge_id = rb.id
                 WHERE ub.user_id = ?
                 ORDER BY ub.earned_date DESC",
                [$userId]
            );
            
            // Challenges activos
            $activeChallenges = $this->getActiveChallenges($userId);
            
            // Estadísticas del mes
            $monthlyStats = $this->getMonthlyStats($userId);
            
            // Próximos objetivos
            $nextGoals = $this->getNextGoals($userId, $userInfo);
            
            return [
                'success' => true,
                'user_info' => $userInfo,
                'recent_referrals' => $recentReferrals,
                'recent_rewards' => $recentRewards,
                'user_badges' => $userBadges,
                'active_challenges' => $activeChallenges,
                'monthly_stats' => $monthlyStats,
                'next_goals' => $nextGoals
            ];

        } catch (Exception $e) {
            error_log("Error getting user dashboard: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error obteniendo dashboard'];
        }
    }

    /**
     * Compartir en redes sociales
     */
    public function shareOnSocialMedia($userId, $platform, $shareData = [])
    {
        try {
            $user = $this->find($userId);
            if (!$user) {
                return ['success' => false, 'error' => 'Usuario no encontrado'];
            }
            
            // Actualizar contador de shares
            $platformField = $platform . '_shares';
            $this->db->execute(
                "UPDATE referral_users SET {$platformField} = {$platformField} + 1 WHERE id = ?",
                [$userId]
            );
            
            // Otorgar puntos por compartir
            $this->db->update('referral_users', [
                'total_points' => $user['total_points'] + 10
            ], ['id' => $userId]);
            
            // Registrar actividad
            $this->logActivity(
                $userId, 
                'social_share', 
                "Compartiste tu enlace en " . ucfirst($platform),
                null,
                array_merge($shareData, ['platform' => $platform])
            );
            
            // Verificar badges relacionados con shares
            $this->checkBadgeAchievements($userId);
            
            // Actualizar estadísticas globales
            $this->updateDailyStats('social_shares', 1);
            
            return [
                'success' => true,
                'points_earned' => 10,
                'total_shares' => $user[$platformField] + 1
            ];

        } catch (Exception $e) {
            error_log("Error sharing on social media: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error compartiendo'];
        }
    }

    /**
     * Solicitar pago de recompensas
     */
    public function requestPayout($userId, $amount, $payoutMethod, $payoutDetails)
    {
        try {
            $user = $this->find($userId);
            if (!$user) {
                return ['success' => false, 'error' => 'Usuario no encontrado'];
            }
            
            $config = $this->getProgramConfig();
            
            // Verificar monto mínimo
            if ($amount < $config['minimum_payout']) {
                return ['success' => false, 'error' => "Monto mínimo para pago: $" . $config['minimum_payout']];
            }
            
            // Verificar balance disponible
            if ($amount > $user['available_balance']) {
                return ['success' => false, 'error' => 'Balance insuficiente'];
            }
            
            $this->db->beginTransaction();
            
            // Crear solicitud de pago
            $payoutId = $this->db->insert('referral_rewards', [
                'user_id' => $userId,
                'reward_type' => 'payout_request',
                'reward_category' => 'monetary',
                'reward_amount' => -$amount, // Negativo porque es un retiro
                'description' => 'Solicitud de pago - ' . ucfirst($payoutMethod),
                'status' => 'pending',
                'payment_method' => $payoutMethod,
                'payment_notes' => json_encode($payoutDetails)
            ]);
            
            // Actualizar balance del usuario
            $this->db->update('referral_users', [
                'available_balance' => $user['available_balance'] - $amount
            ], ['id' => $userId]);
            
            // Registrar actividad
            $this->logActivity(
                $userId, 
                'payout_requested', 
                "Solicitaste un pago de $" . number_format($amount, 2),
                null,
                ['amount' => $amount, 'method' => $payoutMethod]
            );
            
            $this->db->commit();
            
            return [
                'success' => true,
                'payout_id' => $payoutId,
                'amount' => $amount,
                'new_balance' => $user['available_balance'] - $amount
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Error requesting payout: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error solicitando pago'];
        }
    }

    // Métodos auxiliares privados...
    
    private function getProgramConfig()
    {
        $config = $this->db->fetch("SELECT * FROM referral_config WHERE is_active = 1 LIMIT 1");
        return $config ?: [
            'referrer_reward_value' => 25.00,
            'referred_reward_value' => 15.00,
            'min_booking_amount' => 50.00,
            'minimum_payout' => 25.00
        ];
    }

    private function calculateCommission($bookingAmount, $userLevel)
    {
        $config = $this->getProgramConfig();
        $baseCommission = $config['referrer_reward_value'];
        
        // Obtener multiplicador del nivel
        $levelInfo = $this->db->fetch(
            "SELECT referrer_bonus_multiplier FROM referral_levels WHERE level_number = ?",
            [$userLevel]
        );
        
        $multiplier = $levelInfo['referrer_bonus_multiplier'] ?? 1.00;
        
        return $baseCommission * $multiplier;
    }

    private function checkBadgeAchievements($userId)
    {
        $this->db->execute("CALL CheckBadgeAchievements(?)", [$userId]);
    }

    private function checkLevelUpgrade($userId)
    {
        $this->db->execute("CALL CheckLevelUpgrade(?)", [$userId]);
    }

    private function createReward($userId, $rewardData)
    {
        $rewardData['user_id'] = $userId;
        $rewardData['status'] = $rewardData['status'] ?? 'approved';
        
        return $this->db->insert('referral_rewards', $rewardData);
    }

    private function logActivity($userId, $activityType, $description, $referralId = null, $activityData = [])
    {
        $this->db->insert('referral_activities', [
            'user_id' => $userId,
            'activity_type' => $activityType,
            'activity_description' => $description,
            'related_referral_id' => $referralId,
            'activity_data' => !empty($activityData) ? json_encode($activityData) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    private function updateDailyStats($metric, $increment = 1)
    {
        $this->db->execute(
            "INSERT INTO referral_stats (date, {$metric}) VALUES (CURDATE(), ?) 
             ON DUPLICATE KEY UPDATE {$metric} = {$metric} + VALUES({$metric})",
            [$increment]
        );
    }

    private function getActiveChallenges($userId)
    {
        return $this->db->fetchAll(
            "SELECT rc.*, 
                    COALESCE(uc.current_progress, 0) as user_progress,
                    uc.is_completed,
                    DATEDIFF(rc.end_date, NOW()) as days_remaining
             FROM referral_challenges rc
             LEFT JOIN user_challenges uc ON rc.id = uc.challenge_id AND uc.user_id = ?
             WHERE rc.is_active = 1 
             AND rc.start_date <= NOW() 
             AND rc.end_date > NOW()
             ORDER BY rc.end_date ASC",
            [$userId]
        );
    }

    private function getMonthlyStats($userId)
    {
        return $this->db->fetch(
            "SELECT 
                COUNT(r.id) as referrals_this_month,
                COUNT(CASE WHEN r.status = 'first_purchase' THEN 1 END) as conversions_this_month,
                COALESCE(SUM(CASE WHEN rr.status = 'approved' THEN rr.reward_amount END), 0) as earnings_this_month,
                COALESCE(SUM(r.total_spent), 0) as referred_revenue_this_month
             FROM referrals r
             LEFT JOIN referral_rewards rr ON r.referrer_id = rr.user_id 
                AND MONTH(rr.created_at) = MONTH(NOW()) 
                AND YEAR(rr.created_at) = YEAR(NOW())
             WHERE r.referrer_id = ?
             AND MONTH(r.created_at) = MONTH(NOW()) 
             AND YEAR(r.created_at) = YEAR(NOW())",
            [$userId]
        );
    }

    private function getNextGoals($userId, $userInfo)
    {
        $goals = [];
        
        // Próximo nivel
        if ($userInfo['next_level']) {
            $goals[] = [
                'type' => 'level_upgrade',
                'title' => "Alcanzar nivel {$userInfo['next_level_name']}",
                'current' => $userInfo['successful_referrals'],
                'target' => $userInfo['next_level_required_referrals'],
                'progress' => ($userInfo['successful_referrals'] / $userInfo['next_level_required_referrals']) * 100
            ];
        }
        
        // Próximo badge
        $nextBadge = $this->db->fetch(
            "SELECT rb.* FROM referral_badges rb
             WHERE rb.id NOT IN (SELECT badge_id FROM user_badges WHERE user_id = ?)
             AND rb.is_active = 1
             ORDER BY rb.criteria_value ASC
             LIMIT 1",
            [$userId]
        );
        
        if ($nextBadge) {
            $currentValue = 0;
            switch ($nextBadge['criteria_type']) {
                case 'referrals_count':
                    $currentValue = $userInfo['total_referrals'];
                    break;
                case 'earnings_amount':
                    $currentValue = floor($userInfo['total_earnings']);
                    break;
            }
            
            $goals[] = [
                'type' => 'badge_achievement',
                'title' => "Obtener badge: {$nextBadge['badge_name']}",
                'current' => $currentValue,
                'target' => $nextBadge['criteria_value'],
                'progress' => ($currentValue / $nextBadge['criteria_value']) * 100
            ];
        }
        
        return $goals;
    }

    /**
     * Obtener leaderboard de referidores
     */
    public function getLeaderboard($period = 'all_time', $limit = 20)
    {
        $whereClause = "";
        $params = [];
        
        switch ($period) {
            case 'this_month':
                $whereClause = "WHERE MONTH(ru.created_at) = MONTH(NOW()) AND YEAR(ru.created_at) = YEAR(NOW())";
                break;
            case 'this_year':
                $whereClause = "WHERE YEAR(ru.created_at) = YEAR(NOW())";
                break;
        }
        
        return $this->db->fetchAll(
            "SELECT ru.user_name, ru.successful_referrals, ru.total_earnings, 
                    ru.current_level, rl.level_name, rl.level_color,
                    ru.badges_count, ru.total_points
             FROM referral_users ru
             LEFT JOIN referral_levels rl ON ru.current_level = rl.level_number
             {$whereClause}
             AND ru.status = 'active'
             ORDER BY ru.successful_referrals DESC, ru.total_earnings DESC
             LIMIT ?",
            array_merge($params, [$limit])
        );
    }

    /**
     * Obtener estadísticas globales del programa
     */
    public function getProgramStats()
    {
        return $this->db->fetch(
            "SELECT 
                COUNT(DISTINCT ru.id) as total_participants,
                COUNT(DISTINCT r.id) as total_referrals,
                COUNT(CASE WHEN r.status = 'first_purchase' THEN 1 END) as total_conversions,
                COALESCE(SUM(rr.reward_amount), 0) as total_rewards_paid,
                COALESCE(SUM(r.total_spent), 0) as total_referred_revenue,
                AVG(ru.successful_referrals) as avg_referrals_per_user,
                (COUNT(CASE WHEN r.status = 'first_purchase' THEN 1 END) / COUNT(DISTINCT r.id)) * 100 as conversion_rate
             FROM referral_users ru
             LEFT JOIN referrals r ON ru.id = r.referrer_id
             LEFT JOIN referral_rewards rr ON ru.id = rr.user_id AND rr.status = 'paid'
             WHERE ru.status = 'active'"
        );
    }
}
?>