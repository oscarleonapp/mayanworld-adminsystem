<?php
/**
 * Cron Job - Procesar Recordatorios Automáticos
 * 
 * Este script debe ejecutarse cada 15-30 minutos mediante cron
 * Ejemplo de configuración crontab:
 * */15 * * * * /usr/bin/php /path/to/travel-agency-mvp/cron/process_reminders.php >> /var/log/reminders_cron.log 2>&1
 */

// Establecer zona horaria
date_default_timezone_set('America/Guatemala');

// Incluir archivos necesarios
require_once '../app/core/Database.php';
require_once '../app/core/Config.php';
require_once '../app/helpers/RemindersEngine.php';
require_once '../app/models/Review.php';

// Configurar límites para ejecución por cron
ini_set('max_execution_time', 600); // 10 minutos máximo
ini_set('memory_limit', '256M');

// Función para logging con timestamp
function cronLog($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    echo "[$timestamp] [$level] $message\n";
}

try {
    cronLog("=== INICIO PROCESAMIENTO RECORDATORIOS ===");
    cronLog("PID: " . getmypid());
    cronLog("Memoria inicial: " . formatBytes(memory_get_usage()));

    // Verificar que no haya otro proceso ejecutándose
    $lockFile = '/tmp/reminders_engine.lock';
    $lockHandle = fopen($lockFile, 'w');
    
    if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
        cronLog("Otro proceso ya está ejecutándose. Saliendo.", 'WARNING');
        exit(1);
    }

    // Escribir PID al archivo de lock
    fwrite($lockHandle, getmypid());

    // Inicializar engine
    $engine = new RemindersEngine();
    
    // Procesar recordatorios pendientes
    $engine->processPendingReminders();
    
    // Procesar tareas adicionales según el minuto actual
    $currentMinute = (int)date('i');
    
    // Cada hora en punto (minuto 0): generar invitaciones de review para tours completados
    if ($currentMinute === 0) {
        cronLog("Generando invitaciones de review para tours completados");
        processCompletedTours($engine);
    }
    
    // Cada 4 horas: programar recordatorios RNPL para pagos próximos a vencer
    if ($currentMinute === 0 && (int)date('H') % 4 === 0) {
        cronLog("Programando recordatorios RNPL para pagos próximos");
        processPendingRnplPayments($engine);
    }
    
    // Una vez al día a las 2:00 AM: cleanup de registros antiguos
    if (date('H:i') === '02:00') {
        cronLog("Ejecutando limpieza de registros antiguos");
        $cleaned = $engine->cleanup();
        cronLog("Limpieza completada: {$cleaned['reminders']} recordatorios, {$cleaned['logs']} logs");
    }

    cronLog("Memoria final: " . formatBytes(memory_get_usage()));
    cronLog("=== FIN PROCESAMIENTO RECORDATORIOS ===");

} catch (Exception $e) {
    cronLog("ERROR CRÍTICO: " . $e->getMessage(), 'CRITICAL');
    cronLog("Stack trace: " . $e->getTraceAsString(), 'ERROR');
    
    // Notificación de error crítico (en producción enviar email/SMS)
    error_log("RemindersEngine Critical Error: " . $e->getMessage());
    
    exit(1);
    
} finally {
    // Liberar lock
    if (isset($lockHandle)) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
        unlink($lockFile);
    }
}

/**
 * Procesar tours completados para generar invitaciones de review
 */
function processCompletedTours($engine) {
    try {
        $db = Database::getInstance();
        
        // Obtener tours completados en los últimos 7 días sin invitación de review
        $sql = "SELECT r.id, r.tour_id, r.cliente_email, r.cliente_nombre, 
                       r.fecha_salida, p.nombre as tour_nombre
                FROM reservas r
                JOIN tours p ON r.tour_id = p.id
                LEFT JOIN review_invitaciones ri ON r.id = ri.reserva_id
                WHERE r.estado = 'completada'
                AND r.fecha_salida BETWEEN DATE_SUB(NOW(), INTERVAL 7 DAY) AND DATE_SUB(NOW(), INTERVAL 3 DAY)
                AND ri.id IS NULL";
        
        $completedTours = $db->fetchAll($sql);
        
        foreach ($completedTours as $tour) {
            $success = $engine->scheduleReviewInvitation(
                $tour['id'],
                $tour['tour_id'],
                $tour['cliente_email'],
                $tour['cliente_nombre'],
                $tour['fecha_salida'],
                $tour['tour_nombre']
            );
            
            if ($success) {
                cronLog("Invitación de review programada para reserva #{$tour['id']}");
            }
        }
        
        cronLog("Procesados " . count($completedTours) . " tours completados");
        
    } catch (Exception $e) {
        cronLog("Error procesando tours completados: " . $e->getMessage(), 'ERROR');
    }
}

/**
 * Procesar pagos RNPL próximos a vencer
 */
function processPendingRnplPayments($engine) {
    try {
        $db = Database::getInstance();
        
        // Obtener reservas RNPL con pagos pendientes próximos a vencer
        $sql = "SELECT r.id, r.cliente_email, r.cliente_nombre, r.fecha_salida,
                       r.precio_final, p.nombre as tour_nombre,
                       COALESCE(r.rnpl_hold_amount, r.precio_final * 0.1) as monto_pagado,
                       (r.precio_final - COALESCE(r.rnpl_hold_amount, r.precio_final * 0.1)) as monto_pendiente
                FROM reservas r
                JOIN tours p ON r.tour_id = p.id
                LEFT JOIN scheduled_reminders sr ON r.id = sr.referencia_id 
                    AND sr.tipo = 'rnpl_payment' 
                    AND sr.estado IN ('programado', 'enviado')
                WHERE r.payment_method = 'rnpl'
                AND r.payment_status = 'pending'
                AND r.fecha_salida > NOW()
                AND r.fecha_salida <= DATE_ADD(NOW(), INTERVAL 8 DAY)
                AND sr.id IS NULL";  -- Solo reservas sin recordatorios ya programados
        
        $pendingPayments = $db->fetchAll($sql);
        
        foreach ($pendingPayments as $payment) {
            $success = $engine->scheduleRnplReminders(
                $payment['id'],
                $payment['cliente_email'],
                $payment['cliente_nombre'],
                $payment['fecha_salida'],
                $payment['monto_pendiente'],
                $payment['tour_nombre']
            );
            
            if ($success) {
                cronLog("Recordatorios RNPL programados para reserva #{$payment['id']}");
            }
        }
        
        cronLog("Procesados " . count($pendingPayments) . " pagos RNPL pendientes");
        
    } catch (Exception $e) {
        cronLog("Error procesando pagos RNPL: " . $e->getMessage(), 'ERROR');
    }
}

/**
 * Formatear bytes en formato legible
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}