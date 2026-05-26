<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Core\Config;
use App\Models\Route;
use Exception;

class TransferController extends BaseController
{
    private $routeModel;

    public function __construct()
    {
        parent::__construct();
        $this->routeModel = new Route();
    }

    /**
     * Listado de traslados con filtros
     */
    public function list()
    {
        $origen = $this->getInput('origen');
        $destino = $this->getInput('destino');
        $maxPrecio = $this->getInput('max_precio');
        $sort = $this->getInput('sort', 'precio_asc');

        // Construir query base con información del conductor
        $sql = "SELECT r.*,
                       CONCAT(e.nombre, ' ', e.apellido) as conductor_nombre,
                       e.foto as conductor_foto
                FROM rutas r
                LEFT JOIN empleados e ON r.conductor_id = e.id
                WHERE r.activo = 1 AND r.estado = 'activo'";
        $params = [];

        // Filtros
        if ($origen) {
            $sql .= " AND r.origen LIKE :origen";
            $params['origen'] = "%{$origen}%";
        }

        if ($destino) {
            $sql .= " AND r.destino LIKE :destino";
            $params['destino'] = "%{$destino}%";
        }

        if ($maxPrecio) {
            $sql .= " AND r.precio <= :max_precio";
            $params['max_precio'] = $maxPrecio;
        }

        // Ordenamiento
        switch ($sort) {
            case 'precio_asc':
                $sql .= " ORDER BY r.precio ASC";
                break;
            case 'precio_desc':
                $sql .= " ORDER BY r.precio DESC";
                break;
            case 'distancia_asc':
                $sql .= " ORDER BY r.distancia_km ASC";
                break;
            case 'distancia_desc':
                $sql .= " ORDER BY r.distancia_km DESC";
                break;
            case 'nombre':
                $sql .= " ORDER BY r.nombre ASC";
                break;
            default:
                $sql .= " ORDER BY r.precio ASC";
        }

        $routes = $this->db->fetchAll($sql, $params);

        // Obtener orígenes y destinos únicos para filtros
        $origenes = $this->db->fetchAll("SELECT DISTINCT origen FROM rutas WHERE activo = 1 ORDER BY origen");
        $destinos = $this->db->fetchAll("SELECT DISTINCT destino FROM rutas WHERE activo = 1 ORDER BY destino");

        // Estadísticas
        $stats = [
            'total_routes' => count($routes),
            'min_precio' => $this->db->fetch("SELECT MIN(precio) as min FROM rutas WHERE activo = 1")['min'] ?? 0,
            'max_precio' => $this->db->fetch("SELECT MAX(precio) as max FROM rutas WHERE activo = 1")['max'] ?? 0,
        ];

        $this->view('transfers/list', [
            'title' => 'Traslados y Transportes | Travel Mayan World',
            'metaDescription' => 'Servicios de traslado privado y compartido en Guatemala. Aeropuerto, hoteles, tours y más.',
            'routes' => $routes,
            'origenes' => $origenes,
            'destinos' => $destinos,
            'stats' => $stats,
            'filters' => [
                'origen' => $origen,
                'destino' => $destino,
                'max_precio' => $maxPrecio,
                'sort' => $sort
            ]
        ]);
    }

    /**
     * Detalle de traslado
     */
    public function detail($id)
    {
        // Obtener ruta con información del conductor y foto
        $sql = "SELECT r.*,
                       CONCAT(e.nombre, ' ', e.apellido) as conductor_nombre,
                       e.foto as conductor_foto
                FROM rutas r
                LEFT JOIN empleados e ON r.conductor_id = e.id
                WHERE r.id = :id";

        $route = $this->db->fetch($sql, ['id' => $id]);

        if (!$route || !$route['activo']) {
            Helpers::setFlashMessage('error', 'Traslado no encontrado');
            header('Location: ' . Config::getBaseUrl() . '?route=transfers');
            exit;
        }

        // Decodificar campos JSON
        $route['horarios'] = json_decode($route['horarios'] ?? '[]', true) ?: [];
        $route['paradas_intermedias'] = json_decode($route['paradas_intermedias'] ?? '[]', true) ?: [];
        $route['dias_operacion'] = json_decode($route['dias_operacion'] ?? '[]', true) ?: [];

        // Obtener información del transporte si existe
        $transporte = null;
        if ($route['transporte_id']) {
            $transporte = $this->db->fetch("SELECT * FROM transportes WHERE id = :id", ['id' => $route['transporte_id']]);
        }

        // Rutas relacionadas (mismo origen o destino)
        $relatedRoutes = $this->db->fetchAll(
            "SELECT * FROM rutas
             WHERE activo = 1
             AND id != :id
             AND (origen = :origen OR destino = :destino)
             LIMIT 4",
            [
                'id' => $id,
                'origen' => $route['origen'],
                'destino' => $route['destino']
            ]
        );

        $this->view('transfers/detail', [
            'title' => htmlspecialchars($route['nombre']) . ' | Traslados',
            'metaDescription' => htmlspecialchars(Helpers::truncate($route['descripcion'] ?? '', 155)),
            'route' => $route,
            'transporte' => $transporte,
            'relatedRoutes' => $relatedRoutes
        ]);
    }

    /**
     * API: Buscar traslados (AJAX)
     */
    public function search()
    {
        $query = $this->getInput('q', '');

        if (strlen($query) < 2) {
            echo json_encode(['routes' => []]);
            exit;
        }

        $sql = "SELECT id, nombre, origen, destino, precio, duracion_estimada
                FROM rutas
                WHERE activo = 1
                AND (nombre LIKE :query OR origen LIKE :query OR destino LIKE :query)
                LIMIT 10";

        $routes = $this->db->fetchAll($sql, ['query' => "%{$query}%"]);

        echo json_encode(['routes' => $routes]);
        exit;
    }

    /**
     * Formulario de cotización rápida
     */
    public function quote($id)
    {
        $route = $this->routeModel->find($id);

        if (!$route) {
            echo json_encode(['success' => false, 'message' => 'Traslado no encontrado']);
            exit;
        }

        if (Helpers::isPost()) {
            // Guardar cotización como mensaje
            $data = [
                'nombre' => Helpers::sanitizeString($_POST['nombre']),
                'email' => Helpers::sanitizeString($_POST['email']),
                'telefono' => Helpers::sanitizeString($_POST['telefono'] ?? ''),
                'mensaje' => "Cotización de traslado:\n\nRuta: {$route['nombre']}\nOrigen: {$route['origen']}\nDestino: {$route['destino']}\nFecha: " . ($_POST['fecha'] ?? 'No especificada') . "\nPersonas: " . ($_POST['personas'] ?? '1') . "\n\nComentarios: " . ($_POST['comentarios'] ?? 'Ninguno'),
                'tipo' => 'cotizacion',
                'estado' => 'nuevo'
            ];

            try {
                $this->db->insert('mensajes', $data);
                echo json_encode(['success' => true, 'message' => 'Cotización enviada correctamente. Nos contactaremos pronto.']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error al enviar cotización']);
            }
            exit;
        }
    }
}
