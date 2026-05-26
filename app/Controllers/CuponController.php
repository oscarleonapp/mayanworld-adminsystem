<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\Helpers;
use App\Core\Config;
use App\Core\Database;
use Exception;

/**
 * CuponController
 *
 * Gestiona cupones de descuento y validaciones
 */
class CuponController extends BaseController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Admin: Lista de cupones
     */
    public function index()
    {
        if (!Auth::hasRole('admin')) {
            Auth::requireRole('admin');
        }

        $cupones = $this->db->fetchAll(
            "SELECT c.*, b.nombre as banner_nombre, u.nombre as creador_nombre
             FROM cupones c
             LEFT JOIN banners b ON c.banner_id = b.id
             LEFT JOIN usuarios u ON c.created_by = u.id
             ORDER BY c.created_at DESC"
        );

        $this->view('admin/cupones/index', [
            'cupones' => $cupones,
            'pageTitle' => 'Gestor de Cupones'
        ]);
    }

    /**
     * Admin: Crear/editar cupón
     */
    public function form($id = null)
    {
        if (!Auth::hasRole('admin')) {
            Auth::requireRole('admin');
        }

        $cupon = null;
        if ($id) {
            $cupon = $this->db->fetchOne("SELECT * FROM cupones WHERE id = ?", [$id]);
            if (!$cupon) {
                Helpers::setFlashMessage('error', 'Cupón no encontrado');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/cupones');
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'codigo' => strtoupper(trim($this->getInput('codigo'))),
                'nombre' => $this->getInput('nombre'),
                'descripcion' => $this->getInput('descripcion'),
                'tipo_descuento' => $this->getInput('tipo_descuento', 'porcentaje'),
                'valor_descuento' => $this->getInput('valor_descuento', 0),
                'monto_minimo' => $this->getInput('monto_minimo'),
                'monto_maximo_descuento' => $this->getInput('monto_maximo_descuento'),
                'fecha_inicio' => $this->getInput('fecha_inicio'),
                'fecha_fin' => $this->getInput('fecha_fin'),
                'usos_maximos' => $this->getInput('usos_maximos'),
                'usos_por_usuario' => $this->getInput('usos_por_usuario', 1),
                'tours_aplicables' => $this->getInput('tours_aplicables'),
                'categorias_aplicables' => $this->getInput('categorias_aplicables'),
                'solo_primera_compra' => $this->getInput('solo_primera_compra', 0),
                'activo' => $this->getInput('activo', 1),
                'banner_id' => $this->getInput('banner_id')
            ];

            if (!$id) {
                $data['created_by'] = Auth::user()['id'] ?? null;
            }

            try {
                if ($id) {
                    $this->db->update('cupones', $data, 'id = :id', ['id' => $id]);
                    Helpers::setFlashMessage('success', 'Cupón actualizado correctamente');
                } else {
                    $newId = $this->db->insert('cupones', $data);
                    Helpers::setFlashMessage('success', 'Cupón creado correctamente');
                    header('Location: ' . Config::getBaseUrl() . '?route=admin/cupones/edit/' . $newId);
                    exit;
                }
            } catch (Exception $e) {
                Helpers::setFlashMessage('error', 'Error al guardar: ' . $e->getMessage());
            }
        }

        // Obtener banners para select
        $banners = $this->db->fetchAll("SELECT id, nombre FROM banners ORDER BY nombre");

        // Obtener tours para multiselect
        $tours = $this->db->fetchAll("SELECT id, nombre FROM tours WHERE activo = 1 ORDER BY nombre");

        // Obtener categorías
        $categorias = $this->db->fetchAll("SELECT id, nombre FROM categorias ORDER BY nombre");

        $this->view('admin/cupones/form', [
            'cupon' => $cupon,
            'banners' => $banners,
            'tours' => $tours,
            'categorias' => $categorias,
            'pageTitle' => $id ? 'Editar Cupón' : 'Crear Cupón'
        ]);
    }

    /**
     * Admin: Eliminar cupón
     */
    public function delete($id)
    {
        if (!Auth::hasRole('admin')) {
            Auth::requireRole('admin');
        }

        try {
            // Verificar si tiene usos
            $usos = $this->db->fetchOne("SELECT COUNT(*) as total FROM cupon_usos WHERE cupon_id = ?", [$id]);

            if ($usos['total'] > 0) {
                Helpers::setFlashMessage('warning', 'Este cupón tiene ' . $usos['total'] . ' usos registrados. Se desactivará en lugar de eliminarse.');
                $this->db->update('cupones', ['activo' => 0], 'id = :id', ['id' => $id]);
            } else {
                $this->db->delete('cupones', 'id = :id', ['id' => $id]);
                Helpers::setFlashMessage('success', 'Cupón eliminado correctamente');
            }
        } catch (Exception $e) {
            Helpers::setFlashMessage('error', 'Error al eliminar: ' . $e->getMessage());
        }

        header('Location: ' . Config::getBaseUrl() . '?route=admin/cupones');
        exit;
    }

    /**
     * API: Validar cupón (público - usado en checkout)
     */
    public function apiValidate()
    {
        header('Content-Type: application/json; charset=utf-8');

        $codigo = strtoupper(trim($this->getInput('codigo')));
        $montoCompra = floatval($this->getInput('monto', 0));
        $productIds = $this->getInput('product_ids', []);
        $userId = Auth::user()['id'] ?? null;

        if (!$codigo) {
            echo json_encode(['valid' => false, 'message' => 'Código de cupón requerido']);
            exit;
        }

        $cupon = $this->db->fetchOne("SELECT * FROM cupones WHERE codigo = ? AND activo = 1", [$codigo]);

        if (!$cupon) {
            echo json_encode(['valid' => false, 'message' => 'Cupón no válido o expirado']);
            exit;
        }

        // Validar fechas
        $now = date('Y-m-d H:i:s');
        if ($cupon['fecha_inicio'] > $now) {
            echo json_encode(['valid' => false, 'message' => 'Este cupón aún no está activo']);
            exit;
        }
        if ($cupon['fecha_fin'] && $cupon['fecha_fin'] < $now) {
            echo json_encode(['valid' => false, 'message' => 'Este cupón ha expirado']);
            exit;
        }

        // Validar usos máximos
        if ($cupon['usos_maximos'] && $cupon['usos_actuales'] >= $cupon['usos_maximos']) {
            echo json_encode(['valid' => false, 'message' => 'Este cupón ya alcanzó su límite de usos']);
            exit;
        }

        // Validar usos por usuario
        if ($userId && $cupon['usos_por_usuario'] > 0) {
            $usosUsuario = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM cupon_usos WHERE cupon_id = ? AND usuario_id = ?",
                [$cupon['id'], $userId]
            );
            if ($usosUsuario['total'] >= $cupon['usos_por_usuario']) {
                echo json_encode(['valid' => false, 'message' => 'Ya has usado este cupón el máximo de veces permitido']);
                exit;
            }
        }

        // Validar solo primera compra
        if ($cupon['solo_primera_compra'] && $userId) {
            $comprasAnteriores = $this->db->fetchOne(
                "SELECT COUNT(*) as total FROM reservas WHERE usuario_id = ? AND estado != 'cancelada'",
                [$userId]
            );
            if ($comprasAnteriores['total'] > 0) {
                echo json_encode(['valid' => false, 'message' => 'Este cupón solo es válido para la primera compra']);
                exit;
            }
        }

        // Validar monto mínimo
        if ($cupon['monto_minimo'] && $montoCompra < $cupon['monto_minimo']) {
            echo json_encode([
                'valid' => false,
                'message' => 'Monto mínimo requerido: $' . number_format($cupon['monto_minimo'], 2)
            ]);
            exit;
        }

        // Validar tours aplicables
        if ($cupon['tours_aplicables']) {
            $toursPermitidos = json_decode($cupon['tours_aplicables'], true);
            if (!empty($toursPermitidos) && !empty($productIds)) {
                $hayCoincidencia = false;
                foreach ($productIds as $pid) {
                    if (in_array($pid, $toursPermitidos)) {
                        $hayCoincidencia = true;
                        break;
                    }
                }
                if (!$hayCoincidencia) {
                    echo json_encode(['valid' => false, 'message' => 'Este cupón no es válido para los tours seleccionados']);
                    exit;
                }
            }
        }

        // Calcular descuento
        $montoDescuento = 0;
        if ($cupon['tipo_descuento'] === 'porcentaje') {
            $montoDescuento = ($montoCompra * $cupon['valor_descuento']) / 100;
            // Aplicar límite máximo si existe
            if ($cupon['monto_maximo_descuento'] && $montoDescuento > $cupon['monto_maximo_descuento']) {
                $montoDescuento = $cupon['monto_maximo_descuento'];
            }
        } else {
            $montoDescuento = $cupon['valor_descuento'];
        }

        // No puede ser mayor al monto de compra
        if ($montoDescuento > $montoCompra) {
            $montoDescuento = $montoCompra;
        }

        echo json_encode([
            'valid' => true,
            'cupon_id' => $cupon['id'],
            'codigo' => $cupon['codigo'],
            'tipo_descuento' => $cupon['tipo_descuento'],
            'valor_descuento' => $cupon['valor_descuento'],
            'monto_descuento' => round($montoDescuento, 2),
            'monto_final' => round($montoCompra - $montoDescuento, 2),
            'message' => 'Cupón aplicado: -$' . number_format($montoDescuento, 2)
        ]);
        exit;
    }

    /**
     * Registrar uso de cupón (llamar desde BookingController después de confirmar reserva)
     */
    public function registrarUso($cuponId, $reservaId, $montoOriginal, $montoDescuento, $userId = null)
    {
        try {
            // Insertar en cupon_usos
            $this->db->insert('cupon_usos', [
                'cupon_id' => $cuponId,
                'usuario_id' => $userId,
                'reserva_id' => $reservaId,
                'monto_original' => $montoOriginal,
                'monto_descuento' => $montoDescuento,
                'monto_final' => $montoOriginal - $montoDescuento,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);

            // Incrementar usos_actuales del cupón
            $this->db->query("UPDATE cupones SET usos_actuales = usos_actuales + 1 WHERE id = ?", [$cuponId]);

            return true;
        } catch (Exception $e) {
            error_log("Error al registrar uso de cupón: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Admin: Estadísticas de cupones
     */
    public function estadisticas()
    {
        if (!Auth::hasRole('admin')) {
            Auth::requireRole('admin');
        }

        // Top cupones más usados
        $topCupones = $this->db->fetchAll(
            "SELECT c.codigo, c.nombre, c.usos_actuales,
                    COUNT(cu.id) as total_usos,
                    SUM(cu.monto_descuento) as total_descuentos
             FROM cupones c
             LEFT JOIN cupon_usos cu ON cu.cupon_id = c.id
             GROUP BY c.id
             ORDER BY total_usos DESC
             LIMIT 10"
        );

        // Usos por mes
        $usosPorMes = $this->db->fetchAll(
            "SELECT DATE_FORMAT(fecha_uso, '%Y-%m') as mes,
                    COUNT(*) as total_usos,
                    SUM(monto_descuento) as total_descuentos
             FROM cupon_usos
             WHERE fecha_uso >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY mes
             ORDER BY mes DESC"
        );

        $this->view('admin/cupones/estadisticas', [
            'topCupones' => $topCupones,
            'usosPorMes' => $usosPorMes,
            'pageTitle' => 'Estadísticas de Cupones'
        ]);
    }
}
