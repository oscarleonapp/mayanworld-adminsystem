<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\Helpers;
use App\Core\Config;
use App\Core\Database;
use Exception;

/**
 * BannerController
 *
 * Gestiona banners promocionales y cupones de descuento
 */
class BannerController extends BaseController
{
    private $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getInstance();
    }

    /**
     * Admin: Lista de banners
     */
    public function index()
    {
        // Solo admins
        if (!Auth::hasRole('admin')) {
            Auth::requireRole('admin');
        }

        $banners = $this->db->fetchAll(
            "SELECT b.*, COUNT(c.id) as cupones_count
             FROM banners b
             LEFT JOIN cupones c ON c.banner_id = b.id
             GROUP BY b.id
             ORDER BY b.orden ASC, b.created_at DESC"
        );

        $this->view('admin/banners/index', [
            'banners' => $banners,
            'pageTitle' => 'Gestor de Banners'
        ]);
    }

    /**
     * Admin: Crear/editar banner
     */
    public function form($id = null)
    {
        if (!Auth::hasRole('admin')) {
            Auth::requireRole('admin');
        }

        $banner = null;
        if ($id) {
            $banner = $this->db->fetchOne("SELECT * FROM banners WHERE id = ?", [$id]);
            if (!$banner) {
                Helpers::setFlashMessage('error', 'Banner no encontrado');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/banners');
                exit;
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'nombre' => $this->getInput('nombre'),
                'tipo' => $this->getInput('tipo', 'strip'),
                'imagen' => $this->getInput('imagen'),
                'titulo' => $this->getInput('titulo'),
                'subtitulo' => $this->getInput('subtitulo'),
                'cta_texto' => $this->getInput('cta_texto'),
                'cta_link' => $this->getInput('cta_link'),
                'posicion' => $this->getInput('posicion', 'all'),
                'fecha_inicio' => $this->getInput('fecha_inicio'),
                'fecha_fin' => $this->getInput('fecha_fin'),
                'activo' => $this->getInput('activo', 0),
                'orden' => $this->getInput('orden', 0),
                'target_audiencia' => $this->getInput('target_audiencia'),
                'estilos_custom' => $this->getInput('estilos_custom')
            ];

            try {
                if ($id) {
                    $this->db->update('banners', $data, 'id = :id', ['id' => $id]);
                    Helpers::setFlashMessage('success', 'Banner actualizado correctamente');
                } else {
                    $newId = $this->db->insert('banners', $data);
                    Helpers::setFlashMessage('success', 'Banner creado correctamente');
                    header('Location: ' . Config::getBaseUrl() . '?route=admin/banners/edit/' . $newId);
                    exit;
                }
            } catch (Exception $e) {
                Helpers::setFlashMessage('error', 'Error al guardar: ' . $e->getMessage());
            }
        }

        $this->view('admin/banners/form', [
            'banner' => $banner,
            'pageTitle' => $id ? 'Editar Banner' : 'Crear Banner'
        ]);
    }

    /**
     * Admin: Eliminar banner
     */
    public function delete($id)
    {
        if (!Auth::hasRole('admin')) {
            Auth::requireRole('admin');
        }

        try {
            // Desvincular cupones asociados
            $this->db->query("UPDATE cupones SET banner_id = NULL WHERE banner_id = ?", [$id]);

            // Eliminar banner
            $this->db->delete('banners', 'id = :id', ['id' => $id]);

            Helpers::setFlashMessage('success', 'Banner eliminado correctamente');
        } catch (Exception $e) {
            Helpers::setFlashMessage('error', 'Error al eliminar: ' . $e->getMessage());
        }

        header('Location: ' . Config::getBaseUrl() . '?route=admin/banners');
        exit;
    }

    /**
     * API: Toggle activo
     */
    public function apiToggle()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::hasRole('admin')) {
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }

        $id = $this->getInput('id');
        $banner = $this->db->fetchOne("SELECT activo FROM banners WHERE id = ?", [$id]);

        if (!$banner) {
            echo json_encode(['success' => false, 'message' => 'Banner no encontrado']);
            exit;
        }

        $newStatus = $banner['activo'] ? 0 : 1;
        $this->db->update('banners', ['activo' => $newStatus], 'id = :id', ['id' => $id]);

        echo json_encode([
            'success' => true,
            'activo' => $newStatus,
            'message' => $newStatus ? 'Banner activado' : 'Banner desactivado'
        ]);
        exit;
    }

    /**
     * API: Registrar click en banner
     */
    public function apiTrackClick()
    {
        header('Content-Type: application/json; charset=utf-8');

        $bannerId = $this->getInput('banner_id');

        if ($bannerId) {
            $this->db->query("UPDATE banners SET clicks = clicks + 1 WHERE id = ?", [$bannerId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }

    /**
     * Obtener banners activos para mostrar (público)
     */
    public function getActiveBanners($posicion = 'all')
    {
        $now = date('Y-m-d H:i:s');

        $query = "SELECT * FROM banners
                  WHERE activo = 1
                  AND fecha_inicio <= ?
                  AND (fecha_fin IS NULL OR fecha_fin >= ?)
                  AND (posicion = ? OR posicion = 'all')
                  ORDER BY orden ASC, created_at DESC";

        $banners = $this->db->fetchAll($query, [$now, $now, $posicion]);

        // Incrementar vistas
        foreach ($banners as $banner) {
            $this->db->query("UPDATE banners SET vistas = vistas + 1 WHERE id = ?", [$banner['id']]);
        }

        return $banners;
    }
}
