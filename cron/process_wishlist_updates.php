<?php
/**
 * Cron job para procesar actualizaciones de wishlist
 * Debe ejecutarse cada 6 horas para verificar cambios de precio y enviar notificaciones
 * 
 * Uso: php process_wishlist_updates.php
 * Crontab: 0 */6 * * * /usr/bin/php /path/to/travel-agency-mvp/cron/process_wishlist_updates.php >> /path/to/travel-agency-mvp/logs/wishlist.log 2>&1
 */

// Establecer límites de tiempo y memoria
set_time_limit(600); // 10 minutos máximo
ini_set('memory_limit', '256M');

// Establecer timezone
date_default_timezone_set('America/Guatemala');

// Configuración de paths
define('BASE_PATH', dirname(__DIR__));
define('LOG_DIR', BASE_PATH . '/logs');
define('LOG_FILE', LOG_DIR . '/wishlist.log');

// Crear directorio de logs si no existe
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

// Función de logging
function logMessage($message, $level = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
    echo $logEntry;
}

// Verificar que no haya otra instancia ejecutándose
$lockFile = sys_get_temp_dir() . '/wishlist_updates.lock';
if (file_exists($lockFile)) {
    $lockTime = filemtime($lockFile);
    if ((time() - $lockTime) < 3600) { // 1 hora de timeout
        logMessage("Otra instancia ya está ejecutándose. Saliendo.", 'WARNING');
        exit(1);
    }
    unlink($lockFile);
}

// Crear lock file
file_put_contents($lockFile, getmypid());

// Función de limpieza
function cleanup() {
    global $lockFile;
    if (file_exists($lockFile)) {
        unlink($lockFile);
    }
}

// Registrar función de limpieza
register_shutdown_function('cleanup');

try {
    logMessage("=== Iniciando procesamiento de wishlist updates ===");
    
    // Incluir archivos necesarios
    require_once BASE_PATH . '/app/core/Database.php';
    require_once BASE_PATH . '/app/core/Config.php';
    require_once BASE_PATH . '/app/models/Wishlist.php';
    
    // Inicializar database
    $db = Database::getInstance();
    if (!$db) {
        throw new Exception("No se pudo conectar a la base de datos");
    }
    
    logMessage("Conexión a base de datos establecida");
    
    // Inicializar modelo
    $wishlistModel = new Wishlist();
    
    // Estadísticas de procesamiento
    $stats = [
        'price_changes_detected' => 0,
        'price_alerts_triggered' => 0,
        'notifications_created' => 0,
        'emails_sent' => 0,
        'errors' => 0
    ];
    
    // 1. Procesar cambios de precio existentes
    logMessage("Procesando cambios de precio...");
    try {
        $result = $wishlistModel->processExistingPriceChanges();
        if ($result) {
            logMessage("Cambios de precio procesados exitosamente");
            
            // Obtener estadísticas de cambios detectados
            $priceChanges = $db->fetchAll("
                SELECT COUNT(*) as total_changes 
                FROM wishlist_notifications 
                WHERE tipo IN ('precio_bajo', 'precio_subio') 
                AND fecha_creada > DATE_SUB(NOW(), INTERVAL 6 HOUR)
            ");
            
            if ($priceChanges && !empty($priceChanges[0]['total_changes'])) {
                $stats['price_changes_detected'] = $priceChanges[0]['total_changes'];
                logMessage("Cambios de precio detectados: " . $stats['price_changes_detected']);
            }
        } else {
            logMessage("Error al procesar cambios de precio", 'ERROR');
            $stats['errors']++;
        }
    } catch (Exception $e) {
        logMessage("Error en procesamiento de precios: " . $e->getMessage(), 'ERROR');
        $stats['errors']++;
    }
    
    // 2. Verificar alertas de precio personalizadas
    logMessage("Verificando alertas de precio...");
    try {
        $result = $wishlistModel->checkPriceAlerts();
        if ($result) {
            logMessage("Alertas de precio verificadas exitosamente");
            
            // Obtener estadísticas de alertas disparadas
            $priceAlerts = $db->fetchAll("
                SELECT COUNT(*) as alerts_triggered 
                FROM price_alerts 
                WHERE fecha_ultima_activacion > DATE_SUB(NOW(), INTERVAL 6 HOUR)
            ");
            
            if ($priceAlerts && !empty($priceAlerts[0]['alerts_triggered'])) {
                $stats['price_alerts_triggered'] = $priceAlerts[0]['alerts_triggered'];
                logMessage("Alertas de precio disparadas: " . $stats['price_alerts_triggered']);
            }
        } else {
            logMessage("Error al verificar alertas de precio", 'ERROR');
            $stats['errors']++;
        }
    } catch (Exception $e) {
        logMessage("Error en verificación de alertas: " . $e->getMessage(), 'ERROR');
        $stats['errors']++;
    }
    
    // 3. Procesar disponibilidad de tours
    logMessage("Verificando cambios de disponibilidad...");
    try {
        // Actualizar disponibilidad de tours en wishlist
        $availabilityUpdates = $db->execute("
            UPDATE wishlist_items wi
            JOIN tours p ON wi.tour_id = p.id
            LEFT JOIN disponibilidad d ON p.id = d.tour_id AND d.fecha_salida > NOW()
            SET wi.estaba_disponible = CASE 
                WHEN d.cupos_disponibles > 0 THEN TRUE 
                ELSE FALSE 
            END,
            wi.fecha_ultimo_check_disponibilidad = NOW()
            WHERE wi.fecha_ultimo_check_disponibilidad < DATE_SUB(NOW(), INTERVAL 6 HOUR)
        ");
        
        logMessage("Disponibilidad actualizada para tours en wishlist");
        
        // Crear notificaciones para tours que volvieron a estar disponibles
        $availabilityNotifications = $db->execute("
            INSERT INTO wishlist_notifications (wishlist_item_id, tipo, titulo, mensaje)
            SELECT wi.id, 'disponible', 
                   '🎉 ¡Ya hay cupos disponibles!',
                   CONCAT('El ', p.nombre, ' ya tiene fechas disponibles. ¡Reserva ahora!')
            FROM wishlist_items wi
            JOIN tours p ON wi.tour_id = p.id
            JOIN disponibilidad d ON p.id = d.tour_id
            WHERE wi.estaba_disponible = FALSE
            AND d.fecha_salida > NOW()
            AND d.cupos_disponibles > 0
            AND wi.fecha_ultimo_check_disponibilidad > DATE_SUB(NOW(), INTERVAL 6 HOUR)
        ");
        
        if ($availabilityNotifications > 0) {
            logMessage("Creadas {$availabilityNotifications} notificaciones de disponibilidad");
            $stats['notifications_created'] += $availabilityNotifications;
        }
        
    } catch (Exception $e) {
        logMessage("Error en verificación de disponibilidad: " . $e->getMessage(), 'ERROR');
        $stats['errors']++;
    }
    
    // 4. Enviar notificaciones por email (integración con RemindersEngine)
    logMessage("Enviando notificaciones por email...");
    try {
        $emailResult = $wishlistModel->sendWishlistNotifications(50);
        if ($emailResult['success']) {
            $stats['emails_sent'] = $emailResult['sent'];
            logMessage("Emails enviados: " . $stats['emails_sent']);
        } else {
            logMessage("Error al enviar emails: " . $emailResult['error'], 'ERROR');
            $stats['errors']++;
        }
    } catch (Exception $e) {
        logMessage("Error en envío de emails: " . $e->getMessage(), 'ERROR');
        $stats['errors']++;
    }
    
    // 5. Limpiar notificaciones antiguas (más de 30 días)
    logMessage("Limpiando notificaciones antiguas...");
    try {
        $cleanupResult = $db->execute("
            UPDATE wishlist_notifications 
            SET estado = 'descartada' 
            WHERE estado = 'pendiente' 
            AND fecha_creada < DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        if ($cleanupResult > 0) {
            logMessage("Limpiadas {$cleanupResult} notificaciones antiguas");
        }
    } catch (Exception $e) {
        logMessage("Error en limpieza: " . $e->getMessage(), 'ERROR');
        $stats['errors']++;
    }
    
    // 6. Actualizar estadísticas diarias
    logMessage("Actualizando estadísticas...");
    try {
        // Obtener métricas actuales
        $todayStats = $db->fetch("
            SELECT 
                COUNT(DISTINCT w.id) as total_wishlists,
                COUNT(DISTINCT CASE WHEN wi.id IS NOT NULL THEN w.id END) as wishlists_activas,
                COUNT(wi.id) as total_items,
                COUNT(CASE WHEN wi.fecha_agregado >= CURDATE() THEN wi.id END) as items_agregados_hoy,
                COUNT(CASE WHEN wn.tipo IN ('precio_bajo', 'precio_subio') AND wn.fecha_creada >= CURDATE() THEN wn.id END) as notificaciones_precio,
                COUNT(CASE WHEN w.veces_compartida > 0 THEN w.id END) as listas_compartidas
            FROM wishlists w
            LEFT JOIN wishlist_items wi ON w.id = wi.wishlist_id
            LEFT JOIN wishlist_notifications wn ON wi.id = wn.wishlist_item_id
        ");
        
        if ($todayStats) {
            $db->execute("
                INSERT INTO wishlist_stats (
                    fecha, total_wishlists, wishlists_activas, total_items, 
                    items_agregados_hoy, notificaciones_enviadas, listas_compartidas
                ) VALUES (
                    CURDATE(), ?, ?, ?, ?, ?, ?
                ) ON DUPLICATE KEY UPDATE
                    total_wishlists = VALUES(total_wishlists),
                    wishlists_activas = VALUES(wishlists_activas),
                    total_items = VALUES(total_items),
                    items_agregados_hoy = VALUES(items_agregados_hoy),
                    notificaciones_enviadas = VALUES(notificaciones_enviadas),
                    listas_compartidas = VALUES(listas_compartidas),
                    updated_at = CURRENT_TIMESTAMP
            ", [
                $todayStats['total_wishlists'],
                $todayStats['wishlists_activas'],
                $todayStats['total_items'],
                $todayStats['items_agregados_hoy'],
                $todayStats['notificaciones_precio'],
                $todayStats['listas_compartidas']
            ]);
            
            logMessage("Estadísticas actualizadas");
        }
    } catch (Exception $e) {
        logMessage("Error actualizando estadísticas: " . $e->getMessage(), 'ERROR');
        $stats['errors']++;
    }
    
    // Resumen final
    $processingTime = time() - filemtime($lockFile);
    logMessage("=== Procesamiento completado en {$processingTime} segundos ===");
    logMessage("Estadísticas finales:");
    logMessage("- Cambios de precio detectados: " . $stats['price_changes_detected']);
    logMessage("- Alertas de precio disparadas: " . $stats['price_alerts_triggered']);
    logMessage("- Notificaciones creadas: " . $stats['notifications_created']);
    logMessage("- Emails enviados: " . $stats['emails_sent']);
    logMessage("- Errores: " . $stats['errors']);
    
    // Código de salida
    $exitCode = $stats['errors'] > 0 ? 1 : 0;
    
    if ($exitCode === 0) {
        logMessage("Procesamiento exitoso", 'SUCCESS');
    } else {
        logMessage("Procesamiento completado con errores", 'WARNING');
    }
    
    exit($exitCode);

} catch (Exception $e) {
    logMessage("Error crítico: " . $e->getMessage(), 'CRITICAL');
    logMessage("Stack trace: " . $e->getTraceAsString(), 'DEBUG');
    exit(2);
} catch (Throwable $e) {
    logMessage("Error fatal: " . $e->getMessage(), 'FATAL');
    exit(3);
}
?>