<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Config;
use App\Core\Auth;
use App\Models\Tour;
use Exception;

class TourController extends BaseController
{
    private $tourModel;

    public function __construct()
    {
        parent::__construct();
        $this->tourModel = new Tour();
    }

    /**
     * Listado de tours con filtros
     */
    public function list()
    {
        try {
            // Obtener parámetros de filtros
            $filters = $this->getFilters();

            // Obtener tours con filtros aplicados
            $tours = $this->getFilteredTours($filters);

            // Obtener categorías para el select
            $categories = $this->db->fetchAll(
                "SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre"
            );

            // Renderizar vista
            $this->view('tours/index', [
                'title' => $this->getPageTitle($filters),
                'tours' => $tours,
                'categories' => $categories,
                'filters' => $filters,
                'total_tours' => count($tours)
            ]);

        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                die("Error: " . $e->getMessage());
            }

            $this->view('tours/index', [
                'title' => 'Destinos',
                'tours' => [],
                'categories' => [],
                'filters' => [],
                'total_tours' => 0,
                'error' => 'Error al cargar tours'
            ]);
        }
    }

    /**
     * Obtener todos los filtros de la URL
     */
    private function getFilters()
    {
        return [
            'search' => trim($this->getInput('search', '')),
            'category' => $this->getInput('category', ''),
            'difficulty' => $this->getInput('difficulty', ''),
            'min_price' => $this->getInput('min_price', ''),
            'max_price' => $this->getInput('max_price', ''),
            'duration' => $this->getInput('duration', ''),
            'featured' => $this->getInput('featured', '') === '1',
            'verified' => $this->getInput('verified', '') === '1',
            'sort' => $this->getInput('sort', 'destacado')
        ];
    }

    /**
     * Obtener tours filtrados de la base de datos
     */
    private function getFilteredTours($filters)
    {
        $where = ["activo = 1"];
        $params = [];

        // Búsqueda por texto
        if (!empty($filters['search'])) {
            $where[] = "(nombre LIKE :search1 OR descripcion LIKE :search2 OR ubicacion LIKE :search3)";
            $params['search1'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
            $params['search3'] = '%' . $filters['search'] . '%';
        }

        // Filtro por categoría
        if (!empty($filters['category'])) {
            $where[] = "categoria_id = :category";
            $params['category'] = $filters['category'];
        }

        // Filtro por dificultad
        if (!empty($filters['difficulty'])) {
            $where[] = "dificultad = :difficulty";
            $params['difficulty'] = $filters['difficulty'];
        }

        // Filtro por precio mínimo
        if (!empty($filters['min_price']) && is_numeric($filters['min_price'])) {
            $where[] = "precio >= :min_price";
            $params['min_price'] = (float)$filters['min_price'];
        }

        // Filtro por precio máximo
        if (!empty($filters['max_price']) && is_numeric($filters['max_price'])) {
            $where[] = "precio <= :max_price";
            $params['max_price'] = (float)$filters['max_price'];
        }

        // Filtro por duración
        if (!empty($filters['duration'])) {
            switch ($filters['duration']) {
                case 'half-day':
                    $where[] = "(duracion_dias < 1 OR duracion_dias IS NULL)";
                    break;
                case 'full-day':
                    $where[] = "duracion_dias = 1";
                    break;
                case 'multi-day':
                    $where[] = "duracion_dias > 1";
                    break;
            }
        }

        // Filtro destacados
        if ($filters['featured']) {
            $where[] = "destacado = 1";
        }

        // Filtro verificados
        if ($filters['verified']) {
            $where[] = "verified = 1";
        }

        // Construir ORDER BY
        $orderBy = $this->getOrderBy($filters['sort']);

        // Construir query
        $whereSql = implode(' AND ', $where);
        $sql = "SELECT * FROM tours WHERE {$whereSql} ORDER BY {$orderBy}";

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Obtener ORDER BY según el tipo de ordenamiento
     */
    private function getOrderBy($sort)
    {
        switch ($sort) {
            case 'nombre':
                return 'nombre ASC';
            case 'precio_asc':
                return 'precio ASC';
            case 'precio_desc':
                return 'precio DESC';
            case 'duracion':
                return 'duracion_dias ASC';
            case 'destacado':
            default:
                return 'destacado DESC, created_at DESC';
        }
    }

    /**
     * Obtener título de página según filtros aplicados
     */
    private function getPageTitle($filters)
    {
        if (!empty($filters['search'])) {
            return 'Resultados para: ' . htmlspecialchars($filters['search']);
        }

        if (!empty($filters['category'])) {
            $cat = $this->db->fetch(
                "SELECT nombre FROM categorias WHERE id = :id",
                ['id' => $filters['category']]
            );
            if ($cat) {
                return 'Destinos de ' . htmlspecialchars($cat['nombre']);
            }
        }

        return 'Nuestros Destinos';
    }

    /**
     * Detalle de un tour
     */
    public function detail($id)
    {
        if (!$id || !is_numeric($id)) {
            $this->notFound('Tour no encontrado');
            return;
        }

        try {
            $tour = $this->db->fetch(
                "SELECT t.*, c.nombre as categoria_nombre
                 FROM tours t
                 LEFT JOIN categorias c ON t.categoria_id = c.id
                 WHERE t.id = :id AND t.activo = 1",
                ['id' => $id]
            );

            if (!$tour) {
                $this->notFound('Tour no encontrado');
                return;
            }

            // Tours relacionados
            $related = [];
            if (!empty($tour['categoria_id'])) {
                $related = $this->db->fetchAll(
                    "SELECT * FROM tours
                     WHERE categoria_id = :cat_id
                     AND id != :id
                     AND activo = 1
                     LIMIT 3",
                    ['cat_id' => $tour['categoria_id'], 'id' => $id]
                );
            }

            // Obtener disponibilidad (fechas futuras con cupos disponibles)
            $availability = $this->db->fetchAll(
                "SELECT * FROM disponibilidad
                 WHERE tour_id = :tour_id
                 AND activo = 1
                 AND fecha_salida >= CURDATE()
                 AND (cupos_disponibles - cupos_reservados) > 0
                 ORDER BY fecha_salida ASC",
                ['tour_id' => $id]
            );

            // Obtener reseñas del tour (si la tabla existe)
            $reviews = [];
            try {
                $reviews = $this->db->fetchAll(
                    "SELECT * FROM reviews
                     WHERE tour_id = :tour_id
                     AND estado = 'aprobada'
                     ORDER BY created_at DESC
                     LIMIT 10",
                    ['tour_id' => $id]
                );
            } catch (Exception $e) {
                // La tabla reviews no existe o hay un error, continuar sin reseñas
                $reviews = [];
            }

            // Obtener categoría
            $category = null;
            if (!empty($tour['categoria_id'])) {
                $category = $this->db->fetch(
                    "SELECT * FROM categorias WHERE id = :id",
                    ['id' => $tour['categoria_id']]
                );
            }

            // Verificar si el usuario puede dejar reseña (debe estar autenticado y tener una reserva completada)
            $canReview = false;
            $userCompletedBooking = null;
            $auth = Auth::getInstance();
            if ($auth->isLoggedIn()) {
                $user = $auth->getUser();
                try {
                    $userCompletedBooking = $this->db->fetch(
                        "SELECT id FROM reservas
                         WHERE tour_id = :tour_id
                         AND cliente_email = :email
                         AND estado IN ('completada', 'finalizada')
                         LIMIT 1",
                        ['tour_id' => $id, 'email' => $user['email']]
                    );
                    $canReview = !empty($userCompletedBooking);
                } catch (Exception $e) {
                    $canReview = false;
                }
            }

            $this->view('tour/detail', [
                'title' => $tour['nombre'],
                'tour' => $tour,
                'related_tours' => $related,
                'availability' => $availability,
                'reviews' => $reviews,
                'category' => $category ?? ['nombre' => 'Sin categoría'],
                'canReview' => $canReview,
                'isAuthenticated' => $auth->isLoggedIn()
            ]);

        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                throw $e;
            }
            $this->notFound('Error al cargar el tour');
        }
    }
}
