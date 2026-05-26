<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Core\Config;
use App\Core\Database;
use App\Models\Tour;
use App\Models\TourMeetingPoint;
use App\Models\Booking;
use App\Models\Message;
use App\Models\Staff;
use App\Models\EmployeeType;
use App\Models\Language;
use App\Models\Route;
use App\Models\Review;
use App\Models\Bus;
use App\Helpers\AuditLogger;
use Exception;

class AdminController extends BaseController
{
    private $tourModel;
    private $tourMeetingPointModel;
    private $bookingModel;
    private $messageModel;
    private $staffModel;
    private $employeeTypeModel;
    private $languageModel;
    private $routeModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        
        $this->tourModel = new Tour();
        $this->tourMeetingPointModel = new TourMeetingPoint();
        $this->bookingModel = new Booking();
        $this->messageModel = new Message();
        $this->staffModel = new Staff();
        $this->employeeTypeModel = new EmployeeType();
        $this->languageModel = new Language();
        $this->routeModel = new Route();
    }
    
    // Dashboard principal
    public function dashboard()
    {
        // Obtener rango de fechas para reportes (opcional)
        $startDate = $this->getInput('start_date', date('Y-m-01')); // Primer día del mes
        $endDate = $this->getInput('end_date', date('Y-m-d')); // Hoy
        $period = $this->getInput('period', 'month');

        $period = $this->sanitizeDashboardPeriod($period);
        $startDate = $this->sanitizeDashboardDate($startDate, date('Y-m-01'));
        $endDate = $this->sanitizeDashboardDate($endDate, date('Y-m-d'));

        if (strtotime($startDate) > strtotime($endDate)) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';

        try {
            // Estadísticas generales (sin filtro de fecha)
            $totalRevenue = $this->db->fetch("
                SELECT SUM(precio_total) as total
                FROM reservas
                WHERE estado IN ('confirmada', 'pagada', 'completada')
            ")['total'] ?? 0;

            $totalBookings = $this->bookingModel->count();
            $totalCategories = $this->db->count('categorias', 'activo = ?', [1]);
            $totalUsers = $this->db->count('usuarios', 'tipo = ?', ['cliente']);

            $stats = [
                'total_products' => $this->tourModel->count(['activo' => 1]),
                'total_bookings' => $totalBookings,
                'pending_bookings' => $this->bookingModel->count(['estado' => 'pendiente']),
                'new_messages' => $this->messageModel->count(['estado' => 'nuevo']),
                'total_revenue' => $totalRevenue,
                'total_categories' => $totalCategories,
                'total_users' => $totalUsers,
                'avg_booking_value' => $totalBookings > 0 ? $totalRevenue / $totalBookings : 0,
                'conversion_rate' => 15.5, // TODO: Calcular real
                'revenue_change' => '+12.5',
                'bookings_change' => '+8.2'
            ];

            // Datos para gráficos Chart.js
            $chartData = $this->getDashboardChartData($startDateTime, $endDateTime, $period);

            // Reservas recientes
            $recentBookings = $this->bookingModel->getWithTour(null, null);
            $recentBookings = array_slice($recentBookings, 0, 10);

            // === REPORTES DETALLADOS (con filtro de fecha) ===
            // Estadísticas de reservas filtradas por fecha
            $bookingStats = $this->db->fetch(
                "SELECT
                    COUNT(*) as total_reservas,
                    COUNT(CASE WHEN estado = 'confirmada' THEN 1 END) as confirmadas,
                    COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes,
                    COUNT(CASE WHEN estado = 'cancelada' THEN 1 END) as canceladas,
                    SUM(CASE WHEN estado = 'confirmada' THEN precio_total ELSE 0 END) as ingresos_confirmados,
                    SUM(precio_total) as ingresos_totales,
                    AVG(CASE WHEN estado = 'confirmada' THEN precio_total ELSE NULL END) as ticket_promedio
                FROM reservas
                WHERE created_at BETWEEN :start_date AND :end_date",
                ['start_date' => $startDateTime, 'end_date' => $endDateTime]
            );

            // Tours más vendidos (filtrado por fecha)
            $topProducts = $this->db->fetchAll(
                "SELECT
                    p.id,
                    p.nombre,
                    COUNT(r.id) as total_reservas,
                    SUM(r.precio_total) as ingresos
                FROM tours p
                LEFT JOIN reservas r ON p.id = r.tour_id
                    AND r.created_at BETWEEN :start_date AND :end_date
                    AND r.estado = 'confirmada'
                GROUP BY p.id
                HAVING total_reservas > 0
                ORDER BY total_reservas DESC
                LIMIT 10",
                ['start_date' => $startDateTime, 'end_date' => $endDateTime]
            );

            $this->view('admin/dashboard_premium', [
                'title' => 'Dashboard Analytics',
                'stats' => $stats,
                'chart_data' => $chartData,
                'recent_bookings' => $recentBookings,
                // Datos de reportes
                'start_date' => $startDate,
                'end_date' => $endDate,
                'period' => $period,
                'booking_stats' => $bookingStats,
                'top_products' => $topProducts
            ]);

        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                throw $e;
            }

            $this->view('admin/dashboard_premium', [
                'title' => 'Dashboard Analytics',
                'stats' => [
                    'total_products' => 0,
                    'total_bookings' => 0,
                    'pending_bookings' => 0,
                    'new_messages' => 0,
                    'total_revenue' => 0,
                    'total_categories' => 0,
                    'total_users' => 0,
                    'avg_booking_value' => 0,
                    'conversion_rate' => 0,
                    'revenue_change' => '0',
                    'bookings_change' => '0'
                ],
                'chart_data' => [],
                'recent_bookings' => [],
                'start_date' => date('Y-m-01'),
                'end_date' => date('Y-m-d'),
                'period' => 'month',
                'booking_stats' => [],
                'top_products' => []
            ]);
        }
    }

    /**
     * Obtener datos para los gráficos del dashboard
     */
    private function getDashboardChartData($startDateTime, $endDateTime, $period = 'month')
    {
        $period = $this->sanitizeDashboardPeriod($period);

        $periodExpr = "DATE_FORMAT(created_at, '%Y-%m')";
        $orderExpr = "DATE_FORMAT(created_at, '%Y-%m')";
        if ($period === 'day') {
            $periodExpr = "DATE(created_at)";
            $orderExpr = "DATE(created_at)";
        } elseif ($period === 'week') {
            $periodExpr = "YEARWEEK(created_at, 3)";
            $orderExpr = "YEARWEEK(created_at, 3)";
        }

        $bookingsByPeriod = $this->db->fetchAll("
            SELECT
                {$periodExpr} as period_key,
                MIN(created_at) as period_start,
                COUNT(*) as total
            FROM reservas
            WHERE created_at BETWEEN :start_date AND :end_date
            GROUP BY period_key
            ORDER BY {$orderExpr}
        ", ['start_date' => $startDateTime, 'end_date' => $endDateTime]);

        $bookingsMap = [];
        $labelMap = [];
        foreach ($bookingsByPeriod as $row) {
            $key = (string)($row['period_key'] ?? '');
            if ($key === '') {
                continue;
            }
            $bookingsMap[$key] = (int)$row['total'];
            if (!empty($row['period_start']) && !isset($labelMap[$key])) {
                $labelMap[$key] = $this->formatDashboardPeriodLabel($row['period_start'], $period);
            }
        }

        // Ingresos por periodo
        $revenueByPeriod = $this->db->fetchAll("
            SELECT
                {$periodExpr} as period_key,
                MIN(created_at) as period_start,
                SUM(precio_total) as total
            FROM reservas
            WHERE created_at BETWEEN :start_date AND :end_date
                AND estado IN ('confirmada', 'pagada', 'completada')
            GROUP BY period_key
            ORDER BY {$orderExpr}
        ", ['start_date' => $startDateTime, 'end_date' => $endDateTime]);

        $revenueMap = [];
        foreach ($revenueByPeriod as $row) {
            $key = (string)($row['period_key'] ?? '');
            if ($key === '') {
                continue;
            }
            $revenueMap[$key] = (float)$row['total'];
            if (!empty($row['period_start']) && !isset($labelMap[$key])) {
                $labelMap[$key] = $this->formatDashboardPeriodLabel($row['period_start'], $period);
            }
        }

        $allKeys = array_unique(array_merge(array_keys($bookingsMap), array_keys($revenueMap)));
        sort($allKeys);

        $labels = [];
        $bookingsData = [];
        $revenueData = [];
        foreach ($allKeys as $key) {
            $labels[] = $labelMap[$key] ?? $key;
            $bookingsData[] = $bookingsMap[$key] ?? 0;
            $revenueData[] = $revenueMap[$key] ?? 0;
        }

        // Top 5 tours (filtrado por fecha)
        $topProducts = $this->db->fetchAll("
            SELECT p.nombre, COUNT(r.id) as total
            FROM tours p
            LEFT JOIN reservas r ON p.id = r.tour_id
                AND r.created_at BETWEEN :start_date AND :end_date
            WHERE p.activo = 1
            GROUP BY p.id
            ORDER BY total DESC
            LIMIT 5
        ", ['start_date' => $startDateTime, 'end_date' => $endDateTime]);

        $topProductsLabels = [];
        $topProductsData = [];
        foreach ($topProducts as $product) {
            $topProductsLabels[] = $product['nombre'];
            $topProductsData[] = (int)$product['total'];
        }

        // Reservas por estado
        $bookingsByStatus = $this->db->fetchAll("
            SELECT estado, COUNT(*) as total
            FROM reservas
            WHERE created_at BETWEEN :start_date AND :end_date
            GROUP BY estado
            ORDER BY total DESC
        ", ['start_date' => $startDateTime, 'end_date' => $endDateTime]);

        $statusData = [0, 0, 0, 0]; // confirmadas, pendientes, completadas, canceladas
        foreach ($bookingsByStatus as $status) {
            switch ($status['estado']) {
                case 'confirmada':
                    $statusData[0] = (int)$status['total'];
                    break;
                case 'pendiente':
                    $statusData[1] = (int)$status['total'];
                    break;
                case 'completada':
                    $statusData[2] = (int)$status['total'];
                    break;
                case 'cancelada':
                    $statusData[3] = (int)$status['total'];
                    break;
            }
        }

        // Reservas por categoría
        $bookingsByCategory = $this->db->fetchAll("
            SELECT c.nombre, COUNT(r.id) as total
            FROM categorias c
            LEFT JOIN tours p ON c.id = p.categoria_id
            LEFT JOIN reservas r ON p.id = r.tour_id
                AND r.created_at BETWEEN :start_date AND :end_date
            WHERE c.activo = 1
            GROUP BY c.id
            ORDER BY total DESC
            LIMIT 5
        ", ['start_date' => $startDateTime, 'end_date' => $endDateTime]);

        $categoriesLabels = [];
        $categoriesData = [];
        foreach ($bookingsByCategory as $cat) {
            $categoriesLabels[] = $cat['nombre'];
            $categoriesData[] = (int)$cat['total'];
        }

        return [
            'labels' => $labels,
            'bookings_series' => $bookingsData,
            'revenue_series' => $revenueData,
            'months' => $labels,
            'bookings_by_month' => $bookingsData,
            'revenue_by_month' => $revenueData,
            'top_products_labels' => $topProductsLabels,
            'top_products_data' => $topProductsData,
            'bookings_by_status' => $statusData,
            'categories_labels' => $categoriesLabels,
            'bookings_by_category' => $categoriesData
        ];
    }

    public function dashboardChartData()
    {
        $startDate = $this->sanitizeDashboardDate($this->getInput('start_date', date('Y-m-01')), date('Y-m-01'));
        $endDate = $this->sanitizeDashboardDate($this->getInput('end_date', date('Y-m-d')), date('Y-m-d'));
        $period = $this->sanitizeDashboardPeriod($this->getInput('period', 'month'));

        if (strtotime($startDate) > strtotime($endDate)) {
            $temp = $startDate;
            $startDate = $endDate;
            $endDate = $temp;
        }

        $startDateTime = $startDate . ' 00:00:00';
        $endDateTime = $endDate . ' 23:59:59';

        $data = $this->getDashboardChartData($startDateTime, $endDateTime, $period);
        $this->json(['success' => true, 'data' => $data]);
    }

    private function sanitizeDashboardPeriod($period)
    {
        $allowed = ['month', 'week', 'day'];
        return in_array($period, $allowed, true) ? $period : 'month';
    }

    private function sanitizeDashboardDate($date, $fallback)
    {
        $parsed = strtotime($date);
        if (!$parsed) {
            return $fallback;
        }
        return date('Y-m-d', $parsed);
    }

    private function formatDashboardPeriodLabel($dateStr, $period)
    {
        try {
            $date = new \DateTime($dateStr);
        } catch (\Exception $e) {
            return $dateStr;
        }

        if ($period === 'day') {
            return $date->format('d/m');
        }

        if ($period === 'week') {
            return 'Sem ' . $date->format('W Y');
        }

        return $date->format('M Y');
    }
    
    // Gestión de tours
    public function tours()
    {
        $page = (int)$this->getInput('page', 1);
        $search = $this->getInput('search');
        $verified = $this->getInput('verified'); // 'yes' | 'no' | null
        $withImage = $this->getInput('with_image'); // 'yes' | 'no' | null

        // Para mantener lógica simple y consistente con búsqueda, haremos filtrado en memoria
        if ($search) {
            $all = $this->tourModel->searchTours($search, null, null);
        } else {
            $all = $this->tourModel->findAll([], 'created_at DESC');
        }

        // Filtro de verificación
        if ($verified === 'yes') {
            $all = array_values(array_filter($all, function($p){ return !empty($p['verified']); }));
        } elseif ($verified === 'no') {
            $all = array_values(array_filter($all, function($p){ return empty($p['verified']); }));
        }

        // Filtro por imagen principal
        if ($withImage === 'yes') {
            $all = array_values(array_filter($all, function($p){ return !empty($p['imagen_principal']); }));
        } elseif ($withImage === 'no') {
            $all = array_values(array_filter($all, function($p){ return empty($p['imagen_principal']); }));
        }

        // Paginación manual
        $total = count($all);
        $offset = ($page - 1) * Config::ITEMS_PER_PAGE;
        $data = array_slice($all, $offset, Config::ITEMS_PER_PAGE);
        $pagination = [
            'data' => $data,
            'current_page' => $page,
            'total' => $total,
            'total_pages' => max(1, (int)ceil($total / Config::ITEMS_PER_PAGE))
        ];

        $this->view('admin/tours', [
            'title' => 'Gestión de Tours',
            'products' => $pagination['data'],
            'pagination' => $pagination,
            'search' => $search,
            'verified_filter' => $verified,
            'with_image_filter' => $withImage,
            'unverified_count' => count(array_filter($all, function($p){ return empty($p['verified']); })),
            'no_image_count' => count(array_filter($all, function($p){ return empty($p['imagen_principal']); }))
        ]);
    }

    /**
     * Exportar tours a CSV
     */
    public function exportTours()
    {
        try {
            // Obtener todos los tours
            $products = $this->tourModel->findAll([], 'nombre ASC');

            // Nombre del archivo con fecha
            $filename = 'tours_' . date('Y-m-d_His') . '.csv';

            // Headers para descarga
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            // Abrir output stream
            $output = fopen('php://output', 'w');

            // BOM para UTF-8 (para que Excel lo detecte correctamente)
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados de columnas
            $headers = [
                'ID',
                'Nombre',
                'Categoría ID',
                'Precio',
                'Duración (días)',
                'Dificultad',
                'Capacidad Máxima',
                'Edad Mínima',
                'Latitud',
                'Longitud',
                'Activo',
                'Destacado',
                'Verificado',
                'Imagen Principal',
                'Fecha Creación',
                'Fecha Actualización'
            ];

            fputcsv($output, $headers);

            // Datos de tours
            foreach ($products as $product) {
                $row = [
                    $product['id'],
                    $product['nombre'],
                    $product['categoria_id'] ?? '',
                    $product['precio'] ?? '',
                    $product['duracion_dias'] ?? '',
                    $product['dificultad'] ?? '',
                    $product['capacidad_maxima'] ?? '',
                    $product['edad_minima'] ?? '',
                    $product['latitud'] ?? '',
                    $product['longitud'] ?? '',
                    !empty($product['activo']) ? 'Sí' : 'No',
                    !empty($product['destacado']) ? 'Sí' : 'No',
                    !empty($product['verified']) ? 'Sí' : 'No',
                    $product['imagen_principal'] ?? '',
                    $product['created_at'] ?? '',
                    $product['updated_at'] ?? ''
                ];

                fputcsv($output, $row);
            }

            fclose($output);
            exit;

        } catch (Exception $e) {
            error_log('Error al exportar tours: ' . $e->getMessage());
            Helpers::setFlashMessage('error', 'Error al exportar tours: ' . $e->getMessage());
            $this->redirect('admin/tours');
        }
    }

    // Crear nuevo tour
    public function createTour()
    {
        if (Helpers::isPost()) {
            // Manejar subida de imagen
            $imagenPrincipal = $this->getInput('imagen_principal') ?: null;
            if (isset($_FILES['imagen_principal_file']) && $_FILES['imagen_principal_file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->uploadTourImage($_FILES['imagen_principal_file']);
                if ($uploadResult['success']) {
                    $imagenPrincipal = $uploadResult['url'];
                }
            }

            // Procesar imágenes adicionales
            $imagenesArray = [];
            if (isset($_POST['imagenes']) && is_array($_POST['imagenes'])) {
                $imagenesArray = array_filter(array_map('trim', $_POST['imagenes']), function($url) {
                    return !empty($url);
                });
            }
            if (!empty($_FILES['imagenes_files']['name'][0])) {
                $files = $_FILES['imagenes_files'];
                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $singleFile = [
                            'name'     => $files['name'][$i],
                            'type'     => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error'    => $files['error'][$i],
                            'size'     => $files['size'][$i],
                        ];
                        $uploadResult = $this->uploadTourImage($singleFile);
                        if ($uploadResult['success']) {
                            $imagenesArray[] = $uploadResult['url'];
                        }
                    }
                }
            }
            $imagenesAdicionales = !empty($imagenesArray) ? implode(',', array_values($imagenesArray)) : null;

            // Obtener datos del formulario y mapear a columnas de la DB
            $data = [
                'categoria_id' => $this->getInput('categoria_id'),
                'nombre' => $this->getInput('nombre'),
                'descripcion' => $this->getInput('descripcion'),
                'descripcion_corta' => $this->getInput('descripcion_corta') ?: null,
                'precio' => $this->getInput('precio'),
                'precio_nino' => $this->getInput('precio_nino') ?: null,
                'duracion_dias' => (int)$this->getInput('duracion_dias') ?: null,
                'duracion_horas' => (int)$this->getInput('duracion_horas') ?: null,
                'duracion' => $this->getInput('duracion') ?: null,
                'incluye' => $this->getInput('incluye') ?: null,
                'no_incluye' => $this->getInput('no_incluye') ?: null,
                'itinerario' => $this->processItinerario($this->getInput('itinerario')),
                'que_llevar' => $this->getInput('que_llevar') ?: null,
                'politicas' => $this->getInput('politicas') ?: null,
                'capacidad_maxima' => $this->getInput('grupo_max', 20),
                'grupo_min' => (int)$this->getInput('grupo_min') ?: 1,
                'es_privado' => $this->getInput('es_privado') ? 1 : 0,
                'precios_grupo' => $this->getInput('precios_grupo') ?: null,
                'edad_min' => (int)$this->getInput('edad_min') ?: null,
                'dificultad' => $this->getInput('dificultad', 'facil'),
                'imagen_principal' => $imagenPrincipal,
                'galeria' => $imagenesAdicionales, // Usar imágenes procesadas
                'activo' => $this->getInput('estado') === 'activo' ? 1 : 0, // Mapear estado -> activo (boolean)
                'destacado' => 0, // Default value
                'horarios' => $this->getInput('horarios') ?: null
            ];

            // Validación básica
            $errors = [];
            if (empty($data['nombre'])) {
                $errors[] = 'El nombre es requerido';
            }
            if (empty($data['descripcion'])) {
                $errors[] = 'La descripción es requerida';
            }
            if (!is_numeric($data['precio']) || $data['precio'] <= 0) {
                $errors[] = 'El precio debe ser un número mayor a 0';
            }
            if (!is_numeric($data['categoria_id'])) {
                $errors[] = 'Debe seleccionar una categoría válida';
            }

            if (empty($errors)) {
                try {
                    $id = $this->tourModel->create($data);
                    if ($id) {
                        $this->redirect('admin/tours', 'Tour creado exitosamente', 'success');
                    } else {
                        $errors[] = 'Error al crear el tour';
                    }
                } catch (Exception $e) {
                    $errors[] = 'Error: ' . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                $this->view('admin/tours/create', [
                    'title' => 'Crear Tour',
                    'errors' => $errors,
                    'data' => $data,
                    'categorias' => $this->getCategorias()
                ]);
                return;
            }
        }

        $this->view('admin/tours/create', [
            'title' => 'Crear Tour',
            'categorias' => $this->getCategorias()
        ]);
    }

    // Editar tour existente
    public function editTour($id)
    {
        $product = $this->tourModel->find($id);
        if (!$product) {
            $this->redirect('admin/tours', 'Tour no encontrado', 'error');
            return;
        }

        if (Helpers::isPost()) {
            // Manejar subida de imagen
            $imagenPrincipal = $this->getInput('imagen_principal') ?: $product['imagen_principal'];
            if (isset($_FILES['imagen_principal_file']) && $_FILES['imagen_principal_file']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->uploadTourImage($_FILES['imagen_principal_file']);
                if ($uploadResult['success']) {
                    $imagenPrincipal = $uploadResult['url'];
                }
            }

            // Procesar imágenes adicionales
            // Empezar con las imágenes existentes que el usuario no eliminó (vienen como imagenes[])
            $imagenesArray = [];
            if (isset($_POST['imagenes']) && is_array($_POST['imagenes'])) {
                $imagenesArray = array_filter(array_map('trim', $_POST['imagenes']), function($url) {
                    return !empty($url);
                });
            }
            // Subir nuevos archivos de galería (imagenes_files[])
            if (!empty($_FILES['imagenes_files']['name'][0])) {
                $files = $_FILES['imagenes_files'];
                $count = count($files['name']);
                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $singleFile = [
                            'name'     => $files['name'][$i],
                            'type'     => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error'    => $files['error'][$i],
                            'size'     => $files['size'][$i],
                        ];
                        $uploadResult = $this->uploadTourImage($singleFile);
                        if ($uploadResult['success']) {
                            $imagenesArray[] = $uploadResult['url'];
                        }
                    }
                }
            }
            $imagenesAdicionales = !empty($imagenesArray) ? implode(',', array_values($imagenesArray)) : null;

            // Obtener datos del formulario y mapear a columnas de la DB
            $data = [
                'categoria_id' => $this->getInput('categoria_id'),
                'nombre' => $this->getInput('nombre'),
                'descripcion' => $this->getInput('descripcion'),
                'descripcion_corta' => $this->getInput('descripcion_corta'),
                'precio' => $this->getInput('precio'),
                'precio_nino' => $this->getInput('precio_nino') ?: null,
                'duracion_dias' => (int)$this->getInput('duracion_dias') ?: null,
                'duracion_horas' => (int)$this->getInput('duracion_horas') ?: null,
                'duracion' => $this->getInput('duracion') ?: null,
                'incluye' => $this->getInput('incluye') ?: null,
                'no_incluye' => $this->getInput('no_incluye') ?: null,
                'itinerario' => $this->processItinerario($this->getInput('itinerario')),
                'que_llevar' => $this->getInput('que_llevar') ?: null,
                'politicas' => $this->getInput('politicas') ?: null,
                'capacidad_maxima' => $this->getInput('grupo_max'),
                'grupo_min' => (int)$this->getInput('grupo_min') ?: 1,
                'es_privado' => $this->getInput('es_privado') ? 1 : 0,
                'precios_grupo' => $this->getInput('precios_grupo') ?: null,
                'edad_min' => (int)$this->getInput('edad_min') ?: null,
                'dificultad' => $this->getInput('dificultad'),
                'imagen_principal' => $imagenPrincipal,
                'galeria' => $imagenesAdicionales, // Usar imágenes procesadas
                'activo' => $this->getInput('estado') === 'activo' ? 1 : 0, // Mapear estado -> activo (boolean)
                'disponible_desde' => $this->getInput('disponible_desde') ?: null,
                'disponible_hasta' => $this->getInput('disponible_hasta') ?: null,
                'ubicacion' => $this->getInput('ubicacion') ?: null,
                'horarios' => $this->getInput('horarios') ?: null
            ];

            // Validación básica
            $errors = [];
            if (empty($data['nombre'])) {
                $errors[] = 'El nombre es requerido';
            }
            if (empty($data['descripcion'])) {
                $errors[] = 'La descripción es requerida';
            }
            if (!is_numeric($data['precio']) || $data['precio'] <= 0) {
                $errors[] = 'El precio debe ser un número mayor a 0';
            }
            if (!is_numeric($data['categoria_id'])) {
                $errors[] = 'Debe seleccionar una categoría válida';
            }

            if (empty($errors)) {
                try {
                    $result = $this->tourModel->update($id, $data);
                    if ($result) {
                        $this->redirect('admin/tours', 'Tour actualizado exitosamente', 'success');
                    } else {
                        $errors[] = 'Error al actualizar el tour';
                    }
                } catch (Exception $e) {
                    $errors[] = 'Error: ' . $e->getMessage();
                }
            }

            if (!empty($errors)) {
                $this->view('admin/tours/edit', [
                    'title' => 'Editar Tour',
                    'errors' => $errors,
                    'product' => array_merge($product, $data),
                    'categorias' => $this->getCategorias()
                ]);
                return;
            }
        }

        $this->view('admin/tours/edit', [
            'title' => 'Editar Tour',
            'product' => $product,
            'categorias' => $this->getCategorias()
        ]);
    }

    // Eliminar tour
    public function deleteTour($id = null)
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        // Obtener ID desde la URL o desde POST data
        if ($id === null) {
            $id = $_POST['tour_id'] ?? null;
        }

        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID de tour no proporcionado'], 400);
            return;
        }

        // Verificar si el tour existe
        $product = $this->tourModel->find($id);
        if (!$product) {
            $this->json(['success' => false, 'message' => 'Tour no encontrado'], 404);
            return;
        }

        // Verificar si el tour tiene reservas asociadas
        $bookingsCount = $this->db->count('reservas', 'tour_id = :id', ['id' => $id]);
        if ($bookingsCount > 0) {
            $this->json([
                'success' => false,
                'message' => "No se puede eliminar el tour porque tiene {$bookingsCount} reserva(s) asociada(s). Por favor, gestiona las reservas primero o desactiva el tour en su lugar."
            ], 400);
            return;
        }

        try {
            $result = $this->tourModel->delete($id);
            if ($result) {
                $this->json(['success' => true, 'message' => 'Tour eliminado exitosamente']);
            } else {
                $this->json(['success' => false, 'message' => 'Error al eliminar el tour'], 500);
            }
        } catch (\PDOException $e) {
            // Capturar errores de constraint de base de datos
            if ($e->getCode() == '23000') {
                $this->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el tour porque tiene información relacionada (reservas, disponibilidad, etc.). Por favor, desactiva el tour en su lugar.'
                ], 400);
            } else {
                $this->json(['success' => false, 'message' => 'Error de base de datos al eliminar el tour'], 500);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // Helper method para obtener categorías
    private function getCategorias()
    {
        return $this->db->fetchAll("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre ASC");
    }

    // Helper method para procesar itinerario
    private function processItinerario($itinerario)
    {
        if (empty($itinerario)) {
            return null;
        }
        
        // Si ya es JSON válido, devolverlo tal como está
        $decoded = json_decode($itinerario, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $itinerario;
        }
        
        // Si no es JSON válido, devolverlo como texto plano
        return trim($itinerario);
    }

    public function toggleTourVerified()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }
        $id = (int)$this->getInput('tour_id');
        $verified = (int)$this->getInput('verified', 0) === 1 ? 1 : 0;
        if (!$id) {
            $this->json(['success' => false, 'message' => 'Tour inválido'], 400);
        }
        try {
            $this->db->update('tours', ['verified' => $verified], 'id = :id', ['id' => $id]);
            $this->json(['success' => true]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al actualizar'], 500);
        }
    }

    // Cambiar estado activo/inactivo de un tour (AJAX)
    public function toggleTourStatus()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $productId = (int)$this->getInput('tour_id');
        $active = (int)$this->getInput('active', 0) === 1 ? 1 : 0;

        if (!$productId) {
            $this->json(['success' => false, 'message' => 'Tour inválido'], 400);
            return;
        }

        try {
            $updated = $this->db->update(
                'tours',
                ['activo' => $active],
                'id = :id',
                ['id' => $productId]
            );

            if ($updated) {
                $this->json([
                    'success' => true,
                    'message' => 'Estado actualizado correctamente',
                    'active' => $active
                ]);
            } else {
                $this->json(['success' => false, 'message' => 'No se pudo actualizar'], 400);
            }
        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            } else {
                $this->json(['success' => false, 'message' => 'Error al actualizar el estado'], 500);
            }
        }
    }

    // Cambiar estado destacado de un tour (AJAX)
    public function toggleTourFeatured()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $productId = (int)$this->getInput('tour_id');
        $featured = (int)$this->getInput('featured', 0) === 1 ? 1 : 0;

        if (!$productId) {
            $this->json(['success' => false, 'message' => 'Tour inválido'], 400);
            return;
        }

        try {
            // Verificar que el tour existe
            $product = $this->tourModel->find($productId);
            if (!$product) {
                $this->json(['success' => false, 'message' => 'Tour no encontrado'], 404);
                return;
            }

            // Actualizar el estado destacado
            $updated = $this->db->update(
                'tours',
                ['destacado' => $featured],
                'id = :id',
                ['id' => $productId]
            );

            if ($updated) {
                $this->json([
                    'success' => true,
                    'message' => 'Tour ' . ($featured ? 'marcado como destacado' : 'removido de destacados'),
                    'featured' => $featured
                ]);
            } else {
                $this->json(['success' => false, 'message' => 'No se pudo actualizar'], 400);
            }
        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            } else {
                $this->json(['success' => false, 'message' => 'Error al actualizar el tour'], 500);
            }
        }
    }

    // Acciones en lote para tours (AJAX)
    public function bulkActionTours()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $action = $this->getInput('action');
        $productIdsJson = $this->getInput('product_ids');
        $productIds = json_decode($productIdsJson, true);

        if (!is_array($productIds) || empty($productIds)) {
            $this->json(['success' => false, 'message' => 'No se seleccionaron tours'], 400);
            return;
        }

        if (!in_array($action, ['activate', 'deactivate', 'feature', 'delete'])) {
            $this->json(['success' => false, 'message' => 'Acción no válida'], 400);
            return;
        }

        $successCount = 0;
        $failCount = 0;
        $errors = [];

        foreach ($productIds as $id) {
            $id = (int)$id;
            if ($id < 1) {
                $failCount++;
                continue;
            }

            try {
                switch ($action) {
                    case 'activate':
                        $result = $this->db->update('tours', ['activo' => 1], 'id = :id', ['id' => $id]);
                        break;
                    case 'deactivate':
                        $result = $this->db->update('tours', ['activo' => 0], 'id = :id', ['id' => $id]);
                        break;
                    case 'feature':
                        $result = $this->db->update('tours', ['destacado' => 1], 'id = :id', ['id' => $id]);
                        break;
                    case 'delete':
                        // Verificar si tiene reservas
                        $bookingsCount = $this->db->count('reservas', 'tour_id = :id', ['id' => $id]);
                        if ($bookingsCount > 0) {
                            $product = $this->tourModel->find($id);
                            $errors[] = ($product['nombre'] ?? "Tour #$id") . " tiene $bookingsCount reserva(s)";
                            $failCount++;
                            continue 2;
                        }
                        $result = $this->tourModel->delete($id);
                        break;
                }

                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            } catch (\PDOException $e) {
                $failCount++;
                if ($e->getCode() == '23000') {
                    $product = $this->tourModel->find($id);
                    $errors[] = ($product['nombre'] ?? "Tour #$id") . " tiene información relacionada";
                }
            } catch (Exception $e) {
                $failCount++;
            }
        }

        $message = "Acción completada: $successCount exitoso(s)";
        if ($failCount > 0) {
            $message .= ", $failCount fallido(s)";
            if (!empty($errors)) {
                $message .= ". " . implode(", ", $errors);
            }
        }

        $this->json([
            'success' => $successCount > 0,
            'message' => $message,
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'errors' => $errors
        ]);
    }

    // Actualizar coordenadas de un tour (AJAX)
    public function setTourCoords()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }
        $productId = (int)$this->getInput('id');
        $lat = $this->getInput('lat');
        $lng = $this->getInput('lng');
        $address = $this->getInput('address'); // Nueva: recibir dirección

        if (!$productId || $lat === null || $lng === null) {
            $this->json(['success' => false, 'message' => 'Datos incompletos'], 400);
        }
        try {
            $updateData = [
                'latitud' => $lat,
                'longitud' => $lng
            ];

            // Guardar dirección si se proporciona
            if ($address) {
                $updateData['ubicacion'] = $address;
            }

            $this->db->update('tours', $updateData, 'id = :id', ['id' => $productId]);
            $this->json(['success' => true, 'message' => 'Coordenadas guardadas correctamente']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
    }

    // Proxy para geocodificación (evita CORS y cumple con políticas de Nominatim)
    public function geocode()
    {
        $query = $this->getInput('q');

        if (empty($query)) {
            $this->json(['success' => false, 'message' => 'Búsqueda requerida'], 400);
            return;
        }

        // Nominatim requiere un User-Agent identificativo
        $userAgent = 'TravelAgencyMVP/1.0 (Travel Booking Platform; contact@travel-agency.com)';

        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'format' => 'json',
            'limit' => 5,
            'accept-language' => 'es',
            'q' => $query
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            $this->json(['success' => false, 'message' => 'Error en geocodificación'], 500);
            return;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->json(['success' => false, 'message' => 'Respuesta inválida'], 500);
            return;
        }

        $this->json(['success' => true, 'results' => $data]);
    }

    // Gestión de reservas
    public function bookings()
    {
        $page = (int)$this->getInput('page', 1);
        $perPage = Config::ITEMS_PER_PAGE;
        $filters = $this->getBookingFilters();

        $countRow = $this->db->fetch(
            "SELECT COUNT(*) as total
             FROM reservas r
             INNER JOIN tours p ON r.tour_id = p.id
             LEFT JOIN categorias c ON p.categoria_id = c.id
             {$filters['whereSql']}",
            $filters['params']
        );
        $total = (int)($countRow['total'] ?? 0);
        $offset = ($page - 1) * $perPage;

        $bookingsWithProduct = $this->db->fetchAll(
            "SELECT r.*,
                    p.nombre as tour_nombre,
                    p.imagen_principal,
                    p.duracion_dias,
                    p.dificultad,
                    c.nombre as categoria_nombre
             FROM reservas r
             INNER JOIN tours p ON r.tour_id = p.id
             LEFT JOIN categorias c ON p.categoria_id = c.id
             {$filters['whereSql']}
             ORDER BY {$filters['orderBy']}
             LIMIT {$perPage} OFFSET {$offset}",
            $filters['params']
        );

        $pagination = [
            'data' => $bookingsWithProduct,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => max(1, (int)ceil($total / $perPage)),
            'has_next' => $page < (int)ceil($total / $perPage),
            'has_prev' => $page > 1
        ];

        // Obtener tours activos para el formulario de nueva reserva
        $products = $this->tourModel->findAll(['activo' => 1], 'nombre ASC');

        $this->view('admin/bookings', [
            'title' => 'Gestión de Reservas',
            'bookings' => $bookingsWithProduct,
            'pagination' => $pagination,
            'status_filter' => $filters['status'] ?? null,
            'products' => $products
        ]);
    }

    public function exportBookings()
    {
        try {
            $filters = $this->getBookingFilters();

            $bookings = $this->db->fetchAll(
                "SELECT r.*,
                        p.nombre as tour_nombre
                 FROM reservas r
                 INNER JOIN tours p ON r.tour_id = p.id
                 {$filters['whereSql']}
                 ORDER BY {$filters['orderBy']}",
                $filters['params']
            );

            $filename = 'reservas_' . date('Y-m-d_His') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

            $headers = [
                'ID',
                'Código',
                'Cliente',
                'Email',
                'Teléfono',
                'Tour',
                'Fecha salida',
                'Fecha regreso',
                'Personas',
                'Precio unitario',
                'Subtotal',
                'Descuento',
                'Total',
                'Estado',
                'Método de pago',
                'Fecha creación'
            ];

            fputcsv($output, $headers);

            foreach ($bookings as $booking) {
                $row = [
                    $booking['id'] ?? '',
                    $booking['codigo_reserva'] ?? '',
                    $booking['cliente_nombre'] ?? '',
                    $booking['cliente_email'] ?? '',
                    $booking['cliente_telefono'] ?? '',
                    $booking['tour_nombre'] ?? '',
                    $booking['fecha_salida'] ?? '',
                    $booking['fecha_regreso'] ?? '',
                    $booking['numero_personas'] ?? '',
                    $booking['precio_unitario'] ?? '',
                    $booking['precio_total'] ?? '',
                    $booking['descuento'] ?? '',
                    $booking['precio_final'] ?? '',
                    $booking['estado'] ?? '',
                    $booking['metodo_pago'] ?? '',
                    $booking['created_at'] ?? ''
                ];
                fputcsv($output, $row);
            }

            fclose($output);
            exit;
        } catch (Exception $e) {
            error_log('Error al exportar reservas: ' . $e->getMessage());
            Helpers::setFlashMessage('error', 'Error al exportar reservas: ' . $e->getMessage());
            $this->redirect('admin/bookings');
        }
    }

    private function getBookingFilters()
    {
        $search = trim($this->getInput('search', ''));
        $status = $this->getInput('status');
        $dateFrom = $this->getInput('date_from');
        $dateTo = $this->getInput('date_to');
        $sort = $this->getInput('sort', 'created_at_desc');

        $where = [];
        $params = [];

        if ($status) {
            $where[] = 'r.estado = :status';
            $params['status'] = $status;
        }

        if ($search !== '') {
            $where[] = "(r.codigo_reserva LIKE :search
                OR r.cliente_nombre LIKE :search
                OR r.cliente_email LIKE :search
                OR r.cliente_telefono LIKE :search
                OR p.nombre LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }

        if ($dateFrom) {
            $where[] = 'r.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        if ($dateTo) {
            $where[] = 'r.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $orderMap = [
            'created_at_desc' => 'r.created_at DESC',
            'created_at_asc' => 'r.created_at ASC',
            'fecha_salida_asc' => 'r.fecha_salida ASC',
            'fecha_salida_desc' => 'r.fecha_salida DESC',
            'precio_desc' => 'r.precio_total DESC',
            'precio_asc' => 'r.precio_total ASC'
        ];
        $orderBy = $orderMap[$sort] ?? $orderMap['created_at_desc'];

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return [
            'whereSql' => $whereSql,
            'params' => $params,
            'orderBy' => $orderBy,
            'search' => $search,
            'status' => $status,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'sort' => $sort
        ];
    }

    // Crear reserva manualmente desde admin (AJAX)
    public function createBooking()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }

        // Obtener datos del formulario
        $productId = $this->getInput('tour_id');
        $clientName = $this->getInput('cliente_nombre');
        $clientEmail = $this->getInput('cliente_email');
        $clientPhone = $this->getInput('cliente_telefono');
        $numPeople = (int)$this->getInput('numero_personas');
        $departureDate = $this->getInput('fecha_salida');
        $returnDate = $this->getInput('fecha_regreso');
        $paymentMethod = $this->getInput('metodo_pago');
        $status = $this->getInput('estado');
        $adminNotes = $this->getInput('notas_admin');
        $discountInput = $this->getInput('descuento', 0);
        $availabilityInput = $this->getInput('disponibilidad_id', null);
        $availabilityId = ($availabilityInput === null || $availabilityInput === '') ? null : (int)$availabilityInput;

        // Validación básica
        if (!$productId || !$clientName || !$clientEmail || !$numPeople) {
            $this->json(['success' => false, 'message' => 'Datos incompletos'], 400);
        }

        if ($numPeople < 1) {
            $this->json(['success' => false, 'message' => 'El número de personas debe ser al menos 1'], 400);
        }

        // Obtener tour para calcular precio
        $product = $this->tourModel->find($productId);
        if (!$product) {
            $this->json(['success' => false, 'message' => 'Tour no encontrado'], 404);
        }

        $availability = null;
        if ($availabilityId) {
            $availability = $this->db->fetch(
                "SELECT * FROM disponibilidad WHERE id = :id",
                ['id' => $availabilityId]
            );
            if (!$availability) {
                $this->json(['success' => false, 'message' => 'La disponibilidad seleccionada no existe'], 404);
            }
        }

        if ($availability) {
            $departureDate = $availability['fecha_salida'] ?? $availability['fecha_inicio'] ?? $availability['fecha'] ?? $departureDate;
            $returnDate = $availability['fecha_regreso'] ?? $availability['fecha_salida'] ?? $availability['fecha_inicio'] ?? $availability['fecha'] ?? $returnDate;

            $cuposDisponibles = (int)($availability['cupos_disponibles'] ?? 0);
            $cuposReservados = (int)($availability['cupos_reservados'] ?? 0);
            $available = $cuposDisponibles - $cuposReservados;
            if ($numPeople > $available) {
                $this->json(['success' => false, 'message' => 'No hay cupos suficientes para la disponibilidad seleccionada'], 400);
            }
        }

        if (!$departureDate || !$returnDate) {
            $this->json(['success' => false, 'message' => 'Debes seleccionar fechas de viaje'], 400);
        }

        $startDate = strtotime($departureDate);
        $endDate = strtotime($returnDate);
        if (!$startDate || !$endDate || $startDate > $endDate) {
            $this->json(['success' => false, 'message' => 'La fecha de regreso no puede ser anterior a la fecha de salida'], 400);
        }

        // Calcular precio
        $basePrice = $product['precio_descuento'] ?? null;
        $pricePerPerson = $basePrice ? (float)$basePrice : (float)$product['precio'];
        if ($availability && $availability['precio_especial'] !== null && $availability['precio_especial'] !== '') {
            $pricePerPerson = (float)$availability['precio_especial'];
        }
        $totalPrice = $pricePerPerson * $numPeople;
        $discount = (float)$discountInput;
        $finalPrice = max($totalPrice - $discount, 0);

        // Generar código de reserva
        $bookingCode = 'RES' . strtoupper(substr(uniqid(), -6));

        // Crear reserva
        $bookingData = [
            'codigo_reserva' => $bookingCode,
            'tour_id' => $productId,
            'disponibilidad_id' => $availabilityId,
            'cliente_nombre' => $clientName,
            'cliente_email' => $clientEmail,
            'cliente_telefono' => $clientPhone,
            'numero_personas' => $numPeople,
            'fecha_salida' => $departureDate,
            'fecha_regreso' => $returnDate,
            'precio_unitario' => $pricePerPerson,
            'precio_total' => $totalPrice,
            'descuento' => $discount,
            'precio_final' => $finalPrice,
            'metodo_pago' => $paymentMethod,
            'estado' => $status,
            'notas_admin' => $adminNotes
        ];

        try {
            $this->db->beginTransaction();

            $bookingId = $this->bookingModel->create($bookingData);

            if ($bookingId && $availabilityId && $status !== 'cancelada') {
                $this->db->query(
                    "UPDATE disponibilidad 
                     SET cupos_reservados = cupos_reservados + :personas 
                     WHERE id = :id",
                    [
                        'personas' => $numPeople,
                        'id' => $availabilityId
                    ]
                );
            }

            if ($bookingId) {
                AuditLogger::log('crear', 'reservas', $bookingId, $bookingCode, null, $bookingData);
                $this->db->commit();
                $this->json([
                    'success' => true,
                    'message' => 'Reserva creada exitosamente',
                    'booking_id' => $bookingId,
                    'booking_code' => $bookingCode
                ]);
            } else {
                $this->db->rollback();
                $this->json(['success' => false, 'message' => 'Error al crear la reserva'], 500);
            }
        } catch (Exception $e) {
            $this->db->rollback();
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // Actualizar reserva desde admin (AJAX)
    public function updateBooking()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $bookingId = (int)$this->getInput('booking_id');
        if (!$bookingId) {
            $this->json(['success' => false, 'message' => 'ID no proporcionado'], 400);
            return;
        }

        $booking = $this->bookingModel->find($bookingId);
        if (!$booking) {
            $this->json(['success' => false, 'message' => 'Reserva no encontrada'], 404);
            return;
        }

        $productId = $this->getInput('tour_id') ?: $booking['tour_id'];
        $clientName = trim((string)$this->getInput('cliente_nombre', $booking['cliente_nombre']));
        $clientEmail = trim((string)$this->getInput('cliente_email', $booking['cliente_email']));
        $clientPhone = trim((string)$this->getInput('cliente_telefono', $booking['cliente_telefono']));
        $numPeople = (int)($this->getInput('numero_personas', $booking['numero_personas']));
        $departureDate = $this->getInput('fecha_salida', $booking['fecha_salida']);
        $returnDate = $this->getInput('fecha_regreso', $booking['fecha_regreso']);
        $paymentMethod = $this->getInput('metodo_pago', $booking['metodo_pago']);
        $status = $this->getInput('estado', $booking['estado']);
        $adminNotes = $this->getInput('notas_admin', $booking['notas_admin']);
        $clientNotes = $this->getInput('notas_cliente', $booking['notas_cliente']);
        $discountInput = $this->getInput('descuento', null);
        $availabilityInput = $this->getInput('disponibilidad_id', null);
        $newAvailabilityId = ($availabilityInput === null || $availabilityInput === '') ? null : (int)$availabilityInput;

        // Validación básica
        if (!$productId || !$clientName || !$clientEmail || !$numPeople || !$departureDate || !$returnDate) {
            $this->json(['success' => false, 'message' => 'Datos incompletos'], 400);
            return;
        }

        if ($numPeople < 1) {
            $this->json(['success' => false, 'message' => 'El número de personas debe ser al menos 1'], 400);
            return;
        }

        $startDate = strtotime($departureDate);
        $endDate = strtotime($returnDate);
        if (!$startDate || !$endDate || $startDate > $endDate) {
            $this->json(['success' => false, 'message' => 'La fecha de regreso no puede ser anterior a la fecha de salida'], 400);
            return;
        }

        $validStatuses = ['pendiente', 'confirmada', 'pagada', 'cancelada', 'completada'];
        if (!in_array($status, $validStatuses, true)) {
            $this->json(['success' => false, 'message' => 'Estado inválido'], 400);
            return;
        }

        $product = $this->tourModel->find($productId);
        if (!$product) {
            $this->json(['success' => false, 'message' => 'Tour no encontrado'], 404);
            return;
        }

        $oldAvailabilityId = $booking['disponibilidad_id'] ? (int)$booking['disponibilidad_id'] : null;
        $oldPersons = (int)$booking['numero_personas'];
        $oldStatus = $booking['estado'];
        $newActive = $status !== 'cancelada';
        $oldActive = $oldStatus !== 'cancelada';

        $availability = null;
        if ($newAvailabilityId) {
            $availability = $this->db->fetch(
                "SELECT * FROM disponibilidad WHERE id = :id",
                ['id' => $newAvailabilityId]
            );
            if (!$availability) {
                $this->json(['success' => false, 'message' => 'La disponibilidad seleccionada no existe'], 404);
                return;
            }
        }

        if ($availability) {
            $departureDate = $availability['fecha_salida'] ?? $availability['fecha_inicio'] ?? $availability['fecha'] ?? $departureDate;
            $returnDate = $availability['fecha_regreso'] ?? $availability['fecha_salida'] ?? $availability['fecha_inicio'] ?? $availability['fecha'] ?? $returnDate;
        }

        $basePrice = $product['precio_descuento'] ?? null;
        $pricePerPerson = $basePrice ? (float)$basePrice : (float)$product['precio'];
        if ($availability && $availability['precio_especial'] !== null && $availability['precio_especial'] !== '') {
            $pricePerPerson = (float)$availability['precio_especial'];
        }

        $totalPrice = $pricePerPerson * $numPeople;
        $discount = $discountInput === null || $discountInput === '' ? (float)($booking['descuento'] ?? 0) : (float)$discountInput;
        $finalPrice = max($totalPrice - $discount, 0);

        if ($newAvailabilityId && $newActive) {
            $cuposDisponibles = (int)($availability['cupos_disponibles'] ?? 0);
            $cuposReservados = (int)($availability['cupos_reservados'] ?? 0);
            $available = $cuposDisponibles - $cuposReservados;
            if ($oldAvailabilityId === $newAvailabilityId && $oldActive) {
                $available += $oldPersons;
            }
            if ($numPeople > $available) {
                $this->json(['success' => false, 'message' => 'No hay cupos suficientes para la disponibilidad seleccionada'], 400);
                return;
            }
        }

        $updateData = [
            'tour_id' => $productId,
            'disponibilidad_id' => $newAvailabilityId,
            'cliente_nombre' => Helpers::sanitizeString($clientName),
            'cliente_email' => strtolower($clientEmail),
            'cliente_telefono' => Helpers::sanitizeString($clientPhone),
            'numero_personas' => $numPeople,
            'fecha_salida' => $departureDate,
            'fecha_regreso' => $returnDate,
            'precio_unitario' => $pricePerPerson,
            'precio_total' => $totalPrice,
            'descuento' => $discount,
            'precio_final' => $finalPrice,
            'metodo_pago' => $paymentMethod,
            'estado' => $status,
            'notas_admin' => $adminNotes,
            'notas_cliente' => $clientNotes
        ];

        try {
            $this->db->beginTransaction();

            $releaseAvailabilityId = null;
            $releaseAmount = 0;
            $addAvailabilityId = null;
            $addAmount = 0;

            if ($oldActive) {
                if (!$newActive) {
                    if ($oldAvailabilityId) {
                        $releaseAvailabilityId = $oldAvailabilityId;
                        $releaseAmount = $oldPersons;
                    }
                } else {
                    if ($oldAvailabilityId && $oldAvailabilityId !== $newAvailabilityId) {
                        $releaseAvailabilityId = $oldAvailabilityId;
                        $releaseAmount = $oldPersons;
                    } elseif ($oldAvailabilityId && $oldAvailabilityId === $newAvailabilityId && $numPeople < $oldPersons) {
                        $releaseAvailabilityId = $oldAvailabilityId;
                        $releaseAmount = $oldPersons - $numPeople;
                    } elseif ($oldAvailabilityId && !$newAvailabilityId) {
                        $releaseAvailabilityId = $oldAvailabilityId;
                        $releaseAmount = $oldPersons;
                    }
                }
            }

            if ($newActive) {
                if (!$oldActive) {
                    if ($newAvailabilityId) {
                        $addAvailabilityId = $newAvailabilityId;
                        $addAmount = $numPeople;
                    }
                } else {
                    if ($newAvailabilityId && $newAvailabilityId !== $oldAvailabilityId) {
                        $addAvailabilityId = $newAvailabilityId;
                        $addAmount = $numPeople;
                    } elseif ($newAvailabilityId && $newAvailabilityId === $oldAvailabilityId && $numPeople > $oldPersons) {
                        $addAvailabilityId = $newAvailabilityId;
                        $addAmount = $numPeople - $oldPersons;
                    }
                }
            }

            if ($releaseAvailabilityId && $releaseAmount > 0) {
                $this->db->query(
                    "UPDATE disponibilidad
                     SET cupos_reservados = CASE 
                        WHEN cupos_reservados >= :personas THEN cupos_reservados - :personas
                        ELSE 0 END
                     WHERE id = :id",
                    [
                        'personas' => $releaseAmount,
                        'id' => $releaseAvailabilityId
                    ]
                );
            }

            if ($addAvailabilityId && $addAmount > 0) {
                $this->db->query(
                    "UPDATE disponibilidad 
                     SET cupos_reservados = cupos_reservados + :personas 
                     WHERE id = :id",
                    [
                        'personas' => $addAmount,
                        'id' => $addAvailabilityId
                    ]
                );
            }

            if ($newAvailabilityId === null) {
                $this->db->update('reservas', ['disponibilidad_id' => null], 'id = :id', ['id' => $bookingId]);
                unset($updateData['disponibilidad_id']);
            }

            $updated = $this->bookingModel->update($bookingId, $updateData);
            if ($updated) {
                $after = array_merge($booking, $updateData);
                $before = [
                    'tour_id' => $booking['tour_id'] ?? null,
                    'disponibilidad_id' => $booking['disponibilidad_id'] ?? null,
                    'cliente_nombre' => $booking['cliente_nombre'] ?? null,
                    'cliente_email' => $booking['cliente_email'] ?? null,
                    'cliente_telefono' => $booking['cliente_telefono'] ?? null,
                    'numero_personas' => $booking['numero_personas'] ?? null,
                    'fecha_salida' => $booking['fecha_salida'] ?? null,
                    'fecha_regreso' => $booking['fecha_regreso'] ?? null,
                    'precio_unitario' => $booking['precio_unitario'] ?? null,
                    'precio_total' => $booking['precio_total'] ?? null,
                    'descuento' => $booking['descuento'] ?? null,
                    'precio_final' => $booking['precio_final'] ?? null,
                    'metodo_pago' => $booking['metodo_pago'] ?? null,
                    'estado' => $booking['estado'] ?? null,
                    'notas_admin' => $booking['notas_admin'] ?? null,
                    'notas_cliente' => $booking['notas_cliente'] ?? null
                ];
                $afterAudit = [
                    'tour_id' => $after['tour_id'] ?? null,
                    'disponibilidad_id' => $after['disponibilidad_id'] ?? $newAvailabilityId,
                    'cliente_nombre' => $after['cliente_nombre'] ?? null,
                    'cliente_email' => $after['cliente_email'] ?? null,
                    'cliente_telefono' => $after['cliente_telefono'] ?? null,
                    'numero_personas' => $after['numero_personas'] ?? null,
                    'fecha_salida' => $after['fecha_salida'] ?? null,
                    'fecha_regreso' => $after['fecha_regreso'] ?? null,
                    'precio_unitario' => $after['precio_unitario'] ?? null,
                    'precio_total' => $after['precio_total'] ?? null,
                    'descuento' => $after['descuento'] ?? null,
                    'precio_final' => $after['precio_final'] ?? null,
                    'metodo_pago' => $after['metodo_pago'] ?? null,
                    'estado' => $after['estado'] ?? null,
                    'notas_admin' => $after['notas_admin'] ?? null,
                    'notas_cliente' => $after['notas_cliente'] ?? null
                ];

                AuditLogger::log(
                    'editar',
                    'reservas',
                    $bookingId,
                    $booking['codigo_reserva'] ?? null,
                    $before,
                    $afterAudit
                );

                $this->db->commit();
                $this->json(['success' => true, 'message' => 'Reserva actualizada correctamente']);
            } else {
                $this->db->rollback();
                $this->json(['success' => false, 'message' => 'No se pudo actualizar la reserva'], 400);
            }
        } catch (Exception $e) {
            $this->db->rollback();
            $this->json(['success' => false, 'message' => 'Error al actualizar la reserva: ' . $e->getMessage()], 500);
        }
    }
    
    // Gestión de mensajes
    public function messages()
    {
        $page = (int)$this->getInput('page', 1);
        $status = $this->getInput('status');
        
        $conditions = [];
        if ($status) {
            $conditions['estado'] = $status;
        }
        
        $pagination = $this->messageModel->paginate($page, Config::ITEMS_PER_PAGE, $conditions, 'created_at DESC');
        
        $this->view('admin/messages', [
            'title' => 'Gestión de Mensajes',
            'messages' => $pagination['data'],
            'pagination' => $pagination,
            'status_filter' => $status
        ]);
    }
    
    // Cambiar estado de reserva (AJAX)
    public function updateBookingStatus()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $bookingId = (int)$this->getInput('booking_id');
        $newStatus = $this->getInput('status');
        $adminNotes = $this->getInput('admin_notes', '');

        if (!$bookingId || !$newStatus) {
            $this->json(['success' => false, 'message' => 'Datos incompletos'], 400);
            return;
        }

        // Validar que el estado sea válido
        $validStatuses = ['pendiente', 'confirmada', 'pagada', 'cancelada', 'completada'];
        if (!in_array($newStatus, $validStatuses)) {
            $this->json(['success' => false, 'message' => 'Estado inválido'], 400);
            return;
        }

        try {
            // Verificar que la reserva existe
            $booking = $this->bookingModel->find($bookingId);
            if (!$booking) {
                $this->json(['success' => false, 'message' => 'Reserva no encontrada'], 404);
                return;
            }

            // Actualizar el estado
            $data = [
                'estado' => $newStatus
            ];

            if (!empty($adminNotes)) {
                $data['notas_admin'] = $adminNotes;
            }

            $updated = $this->db->update('reservas', $data, 'id = :id', ['id' => $bookingId]);

            if ($updated) {
                $this->json([
                    'success' => true,
                    'message' => 'Estado actualizado correctamente',
                    'status' => $newStatus
                ]);
            } else {
                $this->json(['success' => false, 'message' => 'No se pudo actualizar'], 400);
            }
        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            } else {
                $this->json(['success' => false, 'message' => 'Error al actualizar el estado'], 500);
            }
        }
    }
    
    // Responder mensaje (AJAX)
    public function replyMessage()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }
        
        $messageId = $this->getInput('message_id');
        $response = $this->getInput('response');
        
        if (!$messageId || !$response) {
            $this->json(['success' => false, 'message' => 'Datos incompletos'], 400);
        }
        
        $result = $this->messageModel->replyMessage($messageId, $response, $this->currentUser['id']);
        $this->json($result);
    }

    // Obtener detalles de un mensaje (AJAX)
    public function messageDetails($id)
    {
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID no proporcionado'], 400);
        }

        $message = $this->messageModel->find($id);

        if (!$message) {
            $this->json(['success' => false, 'message' => 'Mensaje no encontrado'], 404);
        }

        $this->json([
            'success' => true,
            'message' => $message
        ]);
    }

    // Actualizar estado de mensaje (AJAX)
    public function updateMessageStatus()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }

        $messageId = $this->getInput('message_id');
        $newStatus = $this->getInput('status');

        if (!$messageId || !$newStatus) {
            $this->json(['success' => false, 'message' => 'Datos incompletos'], 400);
        }

        // Validar estado
        $validStatuses = ['nuevo', 'leido', 'en_proceso', 'respondido', 'resuelto', 'cerrado'];
        if (!in_array($newStatus, $validStatuses)) {
            $this->json(['success' => false, 'message' => 'Estado inválido'], 400);
        }

        $result = $this->db->update('mensajes',
            ['estado' => $newStatus],
            'id = :id',
            ['id' => $messageId]
        );

        if ($result) {
            $this->json(['success' => true, 'message' => 'Estado actualizado correctamente']);
        } else {
            $this->json(['success' => false, 'message' => 'Error al actualizar el estado'], 500);
        }
    }

    // Eliminar mensaje
    public function deleteMessage()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $messageId = $this->getInput('message_id');

        if (!$messageId) {
            $this->json(['success' => false, 'message' => 'ID de mensaje no proporcionado'], 400);
            return;
        }

        try {
            $deleted = $this->db->delete('mensajes', 'id = :id', ['id' => $messageId]);

            if ($deleted) {
                $this->json(['success' => true, 'message' => 'Mensaje eliminado correctamente']);
            } else {
                $this->json(['success' => false, 'message' => 'No se pudo eliminar el mensaje'], 500);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al eliminar el mensaje: ' . $e->getMessage()], 500);
        }
    }

    // Marcar todos los mensajes como leídos
    public function markAllMessagesRead()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        try {
            $result = $this->db->update('mensajes',
                ['estado' => 'leido'],
                'estado = :estado',
                ['estado' => 'nuevo']
            );

            $this->json(['success' => true, 'message' => 'Todos los mensajes marcados como leídos']);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al actualizar los mensajes: ' . $e->getMessage()], 500);
        }
    }

    // Obtener detalles de una reserva (AJAX)
    public function bookingDetails($id)
    {
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID no proporcionado'], 400);
            return;
        }

        try {
            $booking = $this->bookingModel->getWithTour($id, null);

            if (!$booking) {
                $this->json(['success' => false, 'message' => 'Reserva no encontrada'], 404);
                return;
            }

            $this->json([
                'success' => true,
                'booking' => $booking
            ]);
        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            } else {
                $this->json(['success' => false, 'message' => 'Error al cargar los detalles'], 500);
            }
        }
    }

    // Historial de cambios de una reserva (AJAX)
    public function bookingHistory($id)
    {
        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID no proporcionado'], 400);
            return;
        }

        try {
            $isEnglish = $this->db->columnExists('audit_log', 'user_id');
            $actionCol = $isEnglish ? 'action' : 'accion';
            $tableCol = $isEnglish ? 'table_name' : 'modulo';
            $recordCol = $isEnglish ? 'record_id' : 'registro_id';
            $userIdCol = $isEnglish ? 'user_id' : 'usuario_id';
            $oldCol = $isEnglish ? 'old_values' : 'datos_anteriores';
            $newCol = $isEnglish ? 'new_values' : 'datos_nuevos';

            $hasUsuarioNombre = $this->db->columnExists('audit_log', 'usuario_nombre');
            $hasUserEmail = $this->db->columnExists('audit_log', 'user_email');

            if ($hasUsuarioNombre) {
                $nombreExpr = "COALESCE(al.usuario_nombre, u.nombre, u.email)";
            } elseif ($hasUserEmail) {
                $nombreExpr = "COALESCE(u.nombre, al.user_email, u.email)";
            } else {
                $nombreExpr = "COALESCE(u.nombre, u.email)";
            }

            $accionExpr = $isEnglish
                ? "CASE al.{$actionCol}
                    WHEN 'create' THEN 'crear'
                    WHEN 'update' THEN 'editar'
                    WHEN 'delete' THEN 'eliminar'
                    WHEN 'view' THEN 'ver'
                    ELSE al.{$actionCol}
                  END"
                : "al.{$actionCol}";

            $logs = $this->db->fetchAll(
                "SELECT
                    al.id,
                    {$accionExpr} as accion,
                    {$nombreExpr} as usuario_nombre,
                    al.created_at,
                    al.{$oldCol} as datos_anteriores,
                    al.{$newCol} as datos_nuevos
                 FROM audit_log al
                 LEFT JOIN usuarios u ON al.{$userIdCol} = u.id
                 WHERE al.{$tableCol} = 'reservas' AND al.{$recordCol} = :id
                 ORDER BY al.created_at DESC
                 LIMIT 20",
                ['id' => $id]
            );

            $parsedLogs = array_map(function ($log) {
                $log['datos_anteriores'] = $log['datos_anteriores']
                    ? json_decode($log['datos_anteriores'], true)
                    : null;
                $log['datos_nuevos'] = $log['datos_nuevos']
                    ? json_decode($log['datos_nuevos'], true)
                    : null;
                return $log;
            }, $logs ?: []);

            $this->json(['success' => true, 'logs' => $parsedLogs]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al cargar historial: ' . $e->getMessage()], 500);
        }
    }

    // Moderación de reseñas
    public function reviews()
    {
        require_once __DIR__ . '/../models/Review.php';
        $reviewModel = new Review();
        $page = (int)$this->getInput('page', 1);
        $status = $this->getInput('status');
        $conditions = [];
        if ($status !== null && $status !== '') {
            $conditions['aprobado'] = (int)$status;
        }
        $pagination = $reviewModel->paginate($page, Config::ITEMS_PER_PAGE, $conditions, 'created_at DESC');
        $this->view('admin/reviews', [
            'title' => 'Moderación de Reseñas',
            'reviews' => $pagination['data'],
            'pagination' => $pagination,
            'status_filter' => $status
        ]);
    }

    public function approveReview($id)
    {
        require_once __DIR__ . '/../models/Review.php';
        $reviewModel = new Review();
        $reviewModel->moderate((int)$id, true);
        $this->redirect('admin/reviews', 'Reseña aprobada', 'success');
    }

    public function rejectReview($id)
    {
        require_once __DIR__ . '/../models/Review.php';
        $reviewModel = new Review();
        $reviewModel->moderate((int)$id, false);
        $this->redirect('admin/reviews', 'Reseña rechazada', 'success');
    }

    public function createReview()
    {
        if (!Helpers::isPost()) {
            $this->redirect('admin/reviews', 'Método no permitido', 'error');
            return;
        }

        $tour_id = $this->getInput('tour_id') ?: null;
        $nombre = $this->getInput('nombre');
        $rating = (int)$this->getInput('rating', 5);
        $comentario = $this->getInput('comentario');
        $aprobado = $this->getInput('aprobado') ? 1 : 0;

        if (empty($nombre) || empty($comentario)) {
            $this->redirect('admin/reviews', 'Nombre y comentario son requeridos', 'error');
            return;
        }

        $data = [
            'tour_id' => $tour_id,
            'nombre' => $nombre,
            'rating' => $rating,
            'comentario' => $comentario,
            'aprobado' => $aprobado
        ];

        $id = $this->db->insert('reviews', $data);

        if ($id) {
            $this->redirect('admin/reviews', 'Reseña creada exitosamente', 'success');
        } else {
            $this->redirect('admin/reviews', 'Error al crear la reseña', 'error');
        }
    }

    public function editReview($id)
    {
        require_once __DIR__ . '/../models/Review.php';
        $reviewModel = new Review();
        $review = $reviewModel->find((int)$id);

        if (!$review) {
            $this->redirect('admin/reviews', 'Reseña no encontrada', 'error');
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'review' => $review
        ]);
    }

    public function updateReview($id)
    {
        if (!Helpers::isPost()) {
            $this->redirect('admin/reviews', 'Método no permitido', 'error');
            return;
        }

        $tour_id = $this->getInput('tour_id') ?: null;
        $nombre = $this->getInput('nombre');
        $rating = (int)$this->getInput('rating', 5);
        $comentario = $this->getInput('comentario');
        $aprobado = $this->getInput('aprobado') ? 1 : 0;

        if (empty($nombre) || empty($comentario)) {
            $this->redirect('admin/reviews', 'Nombre y comentario son requeridos', 'error');
            return;
        }

        $data = [
            'tour_id' => $tour_id,
            'nombre' => $nombre,
            'rating' => $rating,
            'comentario' => $comentario,
            'aprobado' => $aprobado
        ];

        $updated = $this->db->update('reviews', $data, 'id = :id', ['id' => (int)$id]);

        if ($updated) {
            $this->redirect('admin/reviews', 'Reseña actualizada exitosamente', 'success');
        } else {
            $this->redirect('admin/reviews', 'Error al actualizar la reseña', 'error');
        }
    }

    public function deleteReview($id)
    {
        if (!Helpers::isPost()) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        $deleted = $this->db->query("DELETE FROM reviews WHERE id = ?", [(int)$id]);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $deleted !== false,
            'message' => $deleted !== false ? 'Reseña eliminada' : 'Error al eliminar'
        ]);
    }

    // Ejecutar recordatorios de saldo pendiente (manual/cron)
    public function runReminders()
    {
        // Buscar reservas confirmadas con saldo pendiente y salida en 7 días
        $days = (int)($this->getInput('days', 7));
        $targetDate = date('Y-m-d', strtotime("+{$days} days"));

        $bookings = $this->db->fetchAll(
            "SELECT r.* FROM reservas r WHERE r.estado IN ('confirmada','pendiente') AND r.fecha_salida = :d",
            ['d' => $targetDate]
        );

        $sent = [];
        foreach ($bookings as $b) {
            // Calcular pagado
            $paidRow = $this->db->fetch("SELECT COALESCE(SUM(monto),0) AS paid FROM pagos WHERE reserva_id = :rid AND estado = 'completado'", ['rid' => $b['id']]);
            $paid = (float)($paidRow['paid'] ?? 0);
            $pending = max(0.0, (float)$b['precio_total'] - $paid);
            if ($pending <= 0) continue;

            // Construir mensaje
            $subject = 'Recordatorio de saldo pendiente - Reserva ' . $b['codigo_reserva'];
            $body = "Hola {$b['cliente_nombre']},\n\n" .
                    "Este es un recordatorio de que tu reserva ({$b['codigo_reserva']}) tiene un saldo pendiente de " . Helpers::formatPrice($pending) . ".\n" .
                    "Fecha de salida: " . Helpers::formatDate($b['fecha_salida']) . "\n\n" .
                    "Puedes completar el pago con tarjeta aquí: " . Config::getBaseUrl() . "?route=payment/checkout/{$b['id']}&type=balance\n\n" .
                    "Gracias por viajar con " . Config::APP_NAME . ".";

            // Enviar email (si mail() disponible)
            @mail($b['cliente_email'], $subject, $body, 'From: ' . Config::COMPANY_EMAIL);

            // Guardar log en archivo local
            $logLine = date('c') . " | {$b['cliente_email']} | {$subject} | pending=" . $pending . "\n";
            file_put_contents(__DIR__ . '/../../storage_reminders.log', $logLine, FILE_APPEND);

            // WhatsApp (deep link)
            $wa = 'https://wa.me/' . preg_replace('/\D+/', '', Config::SOCIAL_WHATSAPP) . '?text=' . urlencode("Hola {$b['cliente_nombre']}, tu saldo pendiente es " . Helpers::formatPrice($pending) . ". Puedes pagarlo en: " . Config::getBaseUrl() . "?route=payment/checkout/{$b['id']}&type=balance");
            $sent[] = [
                'code' => $b['codigo_reserva'],
                'email' => $b['cliente_email'],
                'pending' => $pending,
                'whatsapp_link' => $wa
            ];
        }

        $this->view('admin/reminders_result', [
            'title' => 'Recordatorios enviados',
            'days' => $days,
            'date' => $targetDate,
            'results' => $sent
        ]);
    }

    // GESTIÓN DE PERSONAL

    // Listar personal
    public function staff()
    {
        $page = (int)$this->getInput('page', 1);
        $type = $this->getInput('type');
        $search = $this->getInput('search');

        try {
            if ($search) {
                $staff = $this->staffModel->searchStaff($search, $type);
                $pagination = [
                    'data' => array_slice($staff, ($page - 1) * Config::ITEMS_PER_PAGE, Config::ITEMS_PER_PAGE),
                    'current_page' => $page,
                    'total' => count($staff),
                    'total_pages' => ceil(count($staff) / Config::ITEMS_PER_PAGE),
                    'has_next' => $page < ceil(count($staff) / Config::ITEMS_PER_PAGE),
                    'has_prev' => $page > 1
                ];
            } else {
                $conditions = [];
                if ($type) {
                    $conditions['tipo_empleado'] = $type;
                }
                $pagination = $this->staffModel->paginate($page, Config::ITEMS_PER_PAGE, $conditions, 'nombre, apellido');
            }

            // Obtener estadísticas
            $stats = $this->staffModel->getStats();

            $employeeTypes = $this->employeeTypeModel->getActive();
            $languages = $this->languageModel->getActive();

            $this->view('admin/staff', [
                'title' => 'Gestión de Personal',
                'staff' => $pagination['data'],
                'pagination' => $pagination,
                'stats' => $stats,
                'type_filter' => $type,
                'search' => $search,
                'employeeTypes' => $employeeTypes,
                'languages' => $languages
            ]);

        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                throw $e;
            }
            
            $this->view('admin/staff', [
                'title' => 'Gestión de Personal',
                'staff' => [],
                'pagination' => ['data' => [], 'total' => 0],
                'stats' => ['total_empleados' => 0, 'activos' => 0],
                'error' => 'Error al cargar el personal'
            ]);
        }
    }

    // Agregar personal
    public function addStaff()
    {
        if (Helpers::isPost()) {
            $data = [
                'nombre' => $this->getInput('nombre'),
                'apellido' => $this->getInput('apellido'),
                'email' => $this->getInput('email'),
                'telefono' => $this->getInput('telefono'),
                'direccion' => $this->getInput('direccion'),
                'dpi' => $this->getInput('dpi'),
                'fecha_nacimiento' => $this->getInput('fecha_nacimiento'),
                'fecha_contratacion' => $this->getInput('fecha_contratacion', date('Y-m-d')),
                'puesto' => $this->getInput('puesto'),
                'salario' => $this->getInput('salario'),
                'tipo_empleado' => $this->getInput('tipo_empleado'),
                'experiencia_anios' => $this->getInput('experiencia_anios', 0),
                'estado' => $this->getInput('estado', Staff::STATUS_ACTIVE),
                'notas' => $this->getInput('notas')
            ];

            // Procesar idiomas (vienen como array de checkboxes)
            $idiomas = $_POST['idiomas'] ?? [];
            $data['idiomas'] = is_array($idiomas) && !empty($idiomas) ? implode(', ', $idiomas) : '';

            $certificaciones = $this->getInput('certificaciones');
            if ($certificaciones) {
                $data['certificaciones'] = $certificaciones;
            }

            // Procesar foto si se subió
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->uploadStaffPhoto($_FILES['foto']);
                if ($uploadResult['success']) {
                    $data['foto'] = $uploadResult['filename'];
                }
            }

            $result = $this->staffModel->createStaff($data);

            if ($result['success']) {
                $this->redirect('admin/staff', 'Personal agregado exitosamente', 'success');
            } else {
                $this->handleValidationErrors($result['errors'], 'admin/staff/add');
            }
        }

        $this->view('admin/staff/add', [
            'title' => 'Agregar Personal',
            'csrf_token' => Helpers::generateCsrfToken(),
            'employeeTypes' => $this->employeeTypeModel->getActive(),
            'languages' => $this->languageModel->getActive()
        ]);
    }

    // Editar personal
    public function editStaff($id)
    {
        if (!$id || !is_numeric($id)) {
            $this->redirect('admin/staff', 'Personal no encontrado', 'error');
            return;
        }

        $staff = $this->staffModel->find($id);
        if (!$staff) {
            $this->redirect('admin/staff', 'Personal no encontrado', 'error');
            return;
        }

        if (Helpers::isPost()) {
            $data = [
                'nombre' => $this->getInput('nombre'),
                'apellido' => $this->getInput('apellido'),
                'email' => $this->getInput('email'),
                'telefono' => $this->getInput('telefono'),
                'direccion' => $this->getInput('direccion'),
                'dpi' => $this->getInput('dpi'),
                'fecha_nacimiento' => $this->getInput('fecha_nacimiento'),
                'fecha_contratacion' => $this->getInput('fecha_contratacion'),
                'puesto' => $this->getInput('puesto'),
                'salario' => $this->getInput('salario'),
                'tipo_empleado' => $this->getInput('tipo_empleado'),
                'experiencia_anios' => $this->getInput('experiencia_anios'),
                'estado' => $this->getInput('estado'),
                'notas' => $this->getInput('notas')
            ];

            // Procesar idiomas (vienen como array de checkboxes)
            $idiomas = $_POST['idiomas'] ?? [];
            $data['idiomas'] = is_array($idiomas) && !empty($idiomas) ? implode(', ', $idiomas) : '';

            $certificaciones = $this->getInput('certificaciones');
            if ($certificaciones) {
                $data['certificaciones'] = $certificaciones;
            }

            // Procesar foto si se subió
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->uploadStaffPhoto($_FILES['foto']);
                if ($uploadResult['success']) {
                    $data['foto'] = $uploadResult['filename'];
                }
            }

            $result = $this->staffModel->updateStaff($id, $data);

            if ($result['success']) {
                $this->redirect('admin/staff', 'Personal actualizado exitosamente', 'success');
            } else {
                $this->handleValidationErrors($result['errors'], 'admin/staff/edit/' . $id);
            }
        }

        // Convertir idiomas y certificaciones a string si son JSON
        if (!empty($staff['idiomas'])) {
            $idiomas = json_decode($staff['idiomas'], true);
            if (is_array($idiomas)) {
                $staff['idiomas'] = implode(', ', $idiomas);
            }
        }

        if (!empty($staff['certificaciones'])) {
            $certificaciones = json_decode($staff['certificaciones'], true);
            if (is_array($certificaciones)) {
                $staff['certificaciones'] = implode(', ', $certificaciones);
            }
        }

        $this->view('admin/staff/edit', [
            'title' => 'Editar Personal',
            'employee' => $staff,
            'csrf_token' => Helpers::generateCsrfToken(),
            'employeeTypes' => $this->employeeTypeModel->getActive(),
            'languages' => $this->languageModel->getActive()
        ]);
    }

    // Ver detalles de empleado (AJAX - retorna JSON)
    public function staffDetails($id)
    {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 400);
            return;
        }

        $staff = $this->staffModel->find($id);
        if (!$staff) {
            $this->json(['success' => false, 'message' => 'Empleado no encontrado'], 404);
            return;
        }

        // Convertir idiomas y certificaciones a array si son JSON
        if (!empty($staff['idiomas'])) {
            $idiomas = json_decode($staff['idiomas'], true);
            if (is_array($idiomas)) {
                $staff['idiomas'] = $idiomas;
            } else {
                $staff['idiomas'] = array_map('trim', explode(',', $staff['idiomas']));
            }
        } else {
            $staff['idiomas'] = [];
        }

        if (!empty($staff['certificaciones'])) {
            $certificaciones = json_decode($staff['certificaciones'], true);
            if (is_array($certificaciones)) {
                $staff['certificaciones'] = $certificaciones;
            } else {
                $staff['certificaciones'] = array_map('trim', explode(',', $staff['certificaciones']));
            }
        } else {
            $staff['certificaciones'] = [];
        }

        $this->json(['success' => true, 'employee' => $staff]);
    }

    // Cambiar estado de empleado
    public function toggleStaffStatus()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $id = $this->getInput('id');
        $estado = $this->getInput('estado');

        if (!$id || !$estado) {
            $this->json(['success' => false, 'message' => 'Datos incompletos'], 400);
            return;
        }

        $result = $this->staffModel->update($id, ['estado' => $estado]);

        if ($result) {
            $this->json(['success' => true, 'message' => 'Estado actualizado correctamente']);
        } else {
            $this->json(['success' => false, 'message' => 'Error al actualizar estado'], 500);
        }
    }

    // Eliminar empleado
    public function deleteStaff($id)
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 400);
            return;
        }

        $staff = $this->staffModel->find($id);
        if (!$staff) {
            $this->json(['success' => false, 'message' => 'Empleado no encontrado'], 404);
            return;
        }

        // Eliminar foto si existe
        if (!empty($staff['foto'])) {
            $photoPath = __DIR__ . '/../../public/uploads/staff/' . $staff['foto'];
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
        }

        $result = $this->staffModel->delete($id);

        if ($result) {
            $this->json(['success' => true, 'message' => 'Empleado eliminado correctamente']);
        } else {
            $this->json(['success' => false, 'message' => 'Error al eliminar empleado'], 500);
        }
    }

    // GESTIÓN DE RUTAS DE BUSES

    // Listar rutas
    public function busRoutes()
    {
        $page = (int)$this->getInput('page', 1);
        $search = $this->getInput('search');

        try {
            if ($search) {
                $routes = $this->routeModel->searchRoutes($search, null, null);
                $pagination = [
                    'data' => array_slice($routes, ($page - 1) * Config::ITEMS_PER_PAGE, Config::ITEMS_PER_PAGE),
                    'current_page' => $page,
                    'total' => count($routes),
                    'total_pages' => ceil(count($routes) / Config::ITEMS_PER_PAGE),
                    'has_next' => $page < ceil(count($routes) / Config::ITEMS_PER_PAGE),
                    'has_prev' => $page > 1
                ];
            } else {
                $allRoutes = $this->routeModel->getAllWithDetails();
                $pagination = [
                    'data' => array_slice($allRoutes, ($page - 1) * Config::ITEMS_PER_PAGE, Config::ITEMS_PER_PAGE),
                    'current_page' => $page,
                    'total' => count($allRoutes),
                    'total_pages' => ceil(count($allRoutes) / Config::ITEMS_PER_PAGE),
                    'has_next' => $page < ceil(count($allRoutes) / Config::ITEMS_PER_PAGE),
                    'has_prev' => $page > 1
                ];
            }

            // Obtener estadísticas
            $stats = $this->routeModel->getRouteStats();

            // Obtener transportes y conductores para el formulario
            $transports = $this->routeModel->getAvailableTransports();
            $drivers = $this->routeModel->getAvailableDrivers();

            $this->view('admin/routes', [
                'title' => 'Gestión de Rutas',
                'routes' => $pagination['data'],
                'pagination' => $pagination,
                'stats' => $stats,
                'search' => $search,
                'transports' => $transports,
                'drivers' => $drivers
            ]);

        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                throw $e;
            }

            // En caso de error, obtener transportes y conductores para el formulario
            $transports = [];
            $drivers = [];
            try {
                $transports = $this->routeModel->getAvailableTransports();
                $drivers = $this->routeModel->getAvailableDrivers();
            } catch (Exception $e2) {
                // Ignorar si falla
            }

            $this->view('admin/routes', [
                'title' => 'Gestión de Rutas',
                'routes' => [],
                'transports' => $transports,
                'drivers' => $drivers,
                'pagination' => ['data' => [], 'total' => 0],
                'stats' => ['total_rutas' => 0, 'rutas_activas' => 0],
                'error' => 'Error al cargar las rutas'
            ]);
        }
    }

    // Agregar ruta
    public function addRoute()
    {
        if (Helpers::isPost()) {
            $data = [
                'nombre' => $this->getInput('nombre'),
                'origen' => $this->getInput('origen'),
                'destino' => $this->getInput('destino'),
                'descripcion' => $this->getInput('descripcion'),
                'distancia_km' => $this->getInput('distancia_km'),
                'duracion_estimada' => $this->getInput('duracion_estimada'),
                'precio' => $this->getInput('precio'),
                'transporte_id' => $this->getInput('transporte_id') ?: null,
                'conductor_id' => $this->getInput('conductor_id') ?: null,
                'requisitos' => $this->getInput('requisitos'),
                'notas_importantes' => $this->getInput('notas_importantes'),
                'estado' => $this->getInput('estado', 'activo')
            ];

            // Manejar subida de imagen
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleRouteImageUpload($_FILES['imagen']);
                if ($uploadResult['success']) {
                    $data['imagen'] = $uploadResult['path'];
                } else {
                    $this->redirect('admin/routes', 'Error al subir imagen: ' . $uploadResult['message'], 'error');
                    return;
                }
            }

            // Procesar días de operación
            $diasOperacion = $this->getInput('dias_operacion', []);
            if (is_array($diasOperacion)) {
                $data['dias_operacion'] = $diasOperacion;
            }

            // Procesar horarios (viene como JSON desde el formulario)
            $horariosJSON = $this->getInput('horarios');
            if ($horariosJSON) {
                $data['horarios'] = json_decode($horariosJSON, true) ?: [];
            } else {
                $data['horarios'] = [];
            }

            // Procesar paradas (viene como JSON desde el formulario)
            $paradasJSON = $this->getInput('paradas_intermedias');
            if ($paradasJSON) {
                $data['paradas_intermedias'] = json_decode($paradasJSON, true) ?: [];
            } else {
                $data['paradas_intermedias'] = [];
            }

            $result = $this->routeModel->createRoute($data);

            if ($result['success']) {
                $this->redirect('admin/routes', 'Ruta agregada exitosamente', 'success');
            } else {
                $this->redirect('admin/routes', 'Error al agregar ruta: ' . implode(', ', $result['errors']), 'error');
            }
        } else {
            // Si se accede directamente sin POST, redirigir a la lista de rutas
            $this->redirect('admin/routes');
        }
    }

    // Editar ruta
    public function editRoute($id)
    {
        if (!$id || !is_numeric($id)) {
            $this->redirect('admin/routes', 'Ruta no encontrada', 'error');
            return;
        }

        $route = $this->routeModel->find($id);
        if (!$route) {
            $this->redirect('admin/routes', 'Ruta no encontrada', 'error');
            return;
        }

        if (Helpers::isPost()) {
            $data = [
                'nombre' => $this->getInput('nombre'),
                'origen' => $this->getInput('origen'),
                'destino' => $this->getInput('destino'),
                'descripcion' => $this->getInput('descripcion'),
                'distancia_km' => $this->getInput('distancia_km'),
                'duracion_estimada' => $this->getInput('duracion_estimada'),
                'precio' => $this->getInput('precio'),
                'transporte_id' => $this->getInput('transporte_id') ?: null,
                'conductor_id' => $this->getInput('conductor_id') ?: null,
                'requisitos' => $this->getInput('requisitos'),
                'notas_importantes' => $this->getInput('notas_importantes'),
                'estado' => $this->getInput('estado')
            ];

            // Manejar subida de imagen
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = $this->handleRouteImageUpload($_FILES['imagen']);
                if ($uploadResult['success']) {
                    $data['imagen'] = $uploadResult['path'];
                } else {
                    $this->handleValidationErrors([$uploadResult['message']], 'admin/routes/edit/' . $id);
                    return;
                }
            }

            // Procesar días de operación
            $diasOperacion = $this->getInput('dias_operacion', []);
            if (is_array($diasOperacion)) {
                $data['dias_operacion'] = $diasOperacion;
            }

            // Procesar horarios (viene como JSON desde el formulario)
            $horariosJSON = $this->getInput('horarios');
            if ($horariosJSON) {
                $data['horarios'] = json_decode($horariosJSON, true) ?: [];
            } else {
                $data['horarios'] = [];
            }

            // Procesar paradas (viene como JSON desde el formulario)
            $paradasJSON = $this->getInput('paradas_intermedias');
            if ($paradasJSON) {
                $data['paradas_intermedias'] = json_decode($paradasJSON, true) ?: [];
            } else {
                $data['paradas_intermedias'] = [];
            }

            $result = $this->routeModel->updateRoute($id, $data);

            if ($result['success']) {
                $this->redirect('admin/routes', 'Ruta actualizada exitosamente', 'success');
            } else {
                $this->handleValidationErrors($result['errors'], 'admin/routes/edit/' . $id);
            }
        }

        // Parsear JSON fields para mostrar en el formulario
        $route['dias_operacion'] = json_decode($route['dias_operacion'] ?? '[]', true) ?: [];
        $route['horarios'] = json_decode($route['horarios'] ?? '[]', true) ?: [];
        $route['paradas_intermedias'] = json_decode($route['paradas_intermedias'] ?? '[]', true) ?: [];

        // Obtener datos necesarios para el formulario
        $transportes = $this->routeModel->getAvailableTransports();
        $conductores = $this->routeModel->getAvailableDrivers();

        $this->view('admin/routes/edit', [
            'title' => 'Editar Ruta',
            'route' => $route,
            'transportes' => $transportes,
            'conductores' => $conductores,
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }

    // Ver detalles de ruta (AJAX - retorna JSON)
    public function routeDetails($id)
    {
        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 400);
            return;
        }

        // Obtener ruta con detalles completos
        $db = Database::getInstance();
        $sql = "
            SELECT r.*,
                   t.nombre as transporte_nombre,
                   t.tipo as transporte_tipo,
                   t.capacidad as transporte_capacidad,
                   CONCAT(e.nombre, ' ', e.apellido) as conductor_nombre
            FROM rutas r
            LEFT JOIN transportes t ON r.transporte_id = t.id
            LEFT JOIN empleados e ON r.conductor_id = e.id
            WHERE r.id = :id
        ";

        $route = $db->fetch($sql, ['id' => $id]);

        if (!$route) {
            $this->json(['success' => false, 'message' => 'Ruta no encontrada'], 404);
            return;
        }

        // Decodificar campos JSON
        $route['dias_operacion'] = json_decode($route['dias_operacion'] ?? '[]', true) ?: [];
        $route['horarios'] = json_decode($route['horarios'] ?? '[]', true) ?: [];
        $route['paradas_intermedias'] = json_decode($route['paradas_intermedias'] ?? '[]', true) ?: [];

        $this->json(['success' => true, 'route' => $route]);
    }

    // Eliminar ruta
    public function deleteRoute($id)
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        if (!$id || !is_numeric($id)) {
            $this->json(['success' => false, 'message' => 'ID inválido'], 400);
            return;
        }

        $route = $this->routeModel->find($id);
        if (!$route) {
            $this->json(['success' => false, 'message' => 'Ruta no encontrada'], 404);
            return;
        }

        $result = $this->routeModel->delete($id);

        if ($result) {
            $this->json(['success' => true, 'message' => 'Ruta eliminada correctamente']);
        } else {
            $this->json(['success' => false, 'message' => 'Error al eliminar ruta'], 500);
        }
    }
    
    // TRANSPORTE MANAGEMENT
    public function transport()
    {
        require_once __DIR__ . '/../models/Bus.php';
        $busModel = new Bus();
        
        // Obtener filtros
        $search = $this->getInput('search');
        $type = $this->getInput('type');
        $status = $this->getInput('status');
        $page = (int)$this->getInput('page', 1);
        
        // Construir condiciones
        $conditions = [];
        if ($search) {
            // Simple search in name
            $sql = "SELECT * FROM transportes WHERE nombre LIKE :search";
            $params = ['search' => "%{$search}%"];
            
            if ($type) {
                $sql .= " AND tipo = :type";
                $params['type'] = $type;
            }
            
            if ($status !== null && $status !== '') {
                $sql .= " AND activo = :status";
                $params['status'] = (int)$status;
            }
            
            $sql .= " ORDER BY nombre ASC";
            $transports = $this->db->fetchAll($sql, $params);
        } else {
            if ($type) {
                $conditions['tipo'] = $type;
            }
            if ($status !== null && $status !== '') {
                $conditions['activo'] = (int)$status;
            }
            
            $transports = $busModel->findAll($conditions, 'nombre ASC');
        }
        
        // Simple pagination
        $itemsPerPage = 10;
        $totalItems = count($transports);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $offset = ($page - 1) * $itemsPerPage;
        $transports = array_slice($transports, $offset, $itemsPerPage);
        
        // Format transports for display
        $formattedTransports = array_map(function($transport) {
            $comodidades = [];
            if (!empty($transport['comodidades'])) {
                $comodidadesArray = json_decode($transport['comodidades'], true);
                $comodidades = is_array($comodidadesArray) ? $comodidadesArray : [];
            }
            
            return array_merge($transport, [
                'comodidades_array' => $comodidades,
                'comodidades_text' => implode(', ', $comodidades),
                'tipo_formatted' => $this->getTransportTypeLabel($transport['tipo']),
                'status_class' => $transport['activo'] ? 'success' : 'secondary',
                'status_text' => $transport['activo'] ? 'Activo' : 'Inactivo'
            ]);
        }, $transports);
        
        $this->view('admin/transport', [
            'title' => 'Gestión de Transporte',
            'transports' => $formattedTransports,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total' => $totalItems,
                'has_prev' => $page > 1,
                'has_next' => $page < $totalPages
            ],
            'search' => $search,
            'type_filter' => $type,
            'status_filter' => $status,
            'transport_types' => $this->getTransportTypes()
        ]);
    }
    
    // Helper methods for transport
    private function getTransportTypes()
    {
        require_once __DIR__ . '/../models/Bus.php';
        return [
            Bus::TYPE_BUS => 'Autobús',
            Bus::TYPE_VAN => 'Van/Shuttle',
            Bus::TYPE_PLANE => 'Avión',
            Bus::TYPE_OTHER => 'Otro'
        ];
    }
    
    private function getTransportTypeLabel($type)
    {
        $types = $this->getTransportTypes();
        return $types[$type] ?? ucfirst($type);
    }

    // CRUD methods for transport
    public function addTransport()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        try {
            require_once __DIR__ . '/../models/Bus.php';
            $busModel = new Bus();

            // Validar datos requeridos
            $nombre = trim($this->getInput('nombre'));
            $tipo = trim($this->getInput('tipo'));
            $capacidad = (int)$this->getInput('capacidad');
            $activo = (int)$this->getInput('activo', 1);

            if (empty($nombre)) {
                $this->json(['success' => false, 'message' => 'El nombre es requerido'], 400);
                return;
            }

            if (empty($tipo)) {
                $this->json(['success' => false, 'message' => 'El tipo es requerido'], 400);
                return;
            }

            if ($capacidad < 1) {
                $this->json(['success' => false, 'message' => 'La capacidad debe ser mayor a 0'], 400);
                return;
            }

            // Procesar comodidades (viene como JSON string)
            $comodidades = $this->getInput('comodidades');
            if (!empty($comodidades)) {
                // Ya viene como JSON string del frontend
                $comodidadesDecoded = json_decode($comodidades, true);
                if (!is_array($comodidadesDecoded)) {
                    $comodidades = json_encode([]);
                }
            } else {
                $comodidades = json_encode([]);
            }

            // Insertar en la base de datos
            $data = [
                'nombre' => $nombre,
                'tipo' => $tipo,
                'capacidad' => $capacidad,
                'comodidades' => $comodidades,
                'activo' => $activo
            ];

            $transportId = $busModel->create($data);

            if ($transportId) {
                $this->json(['success' => true, 'message' => 'Transporte agregado correctamente', 'id' => $transportId]);
            } else {
                $this->json(['success' => false, 'message' => 'Error al agregar el transporte'], 500);
            }

        } catch (Exception $e) {
            error_log("Error adding transport: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    public function editTransport($id)
    {
        require_once __DIR__ . '/../models/Bus.php';
        $busModel = new Bus();

        $transport = $busModel->find($id);

        if (!$transport) {
            $this->view('errors/404', ['message' => 'Transporte no encontrado']);
            return;
        }

        // Decodificar comodidades
        $comodidades = [];
        if (!empty($transport['comodidades'])) {
            $comodidadesDecoded = json_decode($transport['comodidades'], true);
            $comodidades = is_array($comodidadesDecoded) ? $comodidadesDecoded : [];
        }

        $this->view('admin/transport_edit', [
            'title' => 'Editar Transporte',
            'transport' => $transport,
            'comodidades' => $comodidades,
            'transport_types' => $this->getTransportTypes()
        ]);
    }

    public function updateTransport($id)
    {
        // Limpiar cualquier output buffer
        if (ob_get_level()) {
            ob_clean();
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        try {
            require_once __DIR__ . '/../models/Bus.php';
            $busModel = new Bus();

            // Verificar que existe
            $transport = $busModel->find($id);
            if (!$transport) {
                $this->json(['success' => false, 'message' => 'Transporte no encontrado'], 404);
                return;
            }

            // Validar datos
            $nombre = trim($this->getInput('nombre'));
            $tipo = trim($this->getInput('tipo'));
            $capacidad = (int)$this->getInput('capacidad');
            $activo = (int)$this->getInput('activo', 1);

            if (empty($nombre)) {
                $this->json(['success' => false, 'message' => 'El nombre es requerido'], 400);
                return;
            }

            if (empty($tipo)) {
                $this->json(['success' => false, 'message' => 'El tipo es requerido'], 400);
                return;
            }

            if ($capacidad < 1) {
                $this->json(['success' => false, 'message' => 'La capacidad debe ser mayor a 0'], 400);
                return;
            }

            // Procesar comodidades
            $comodidades = $this->getInput('comodidades');
            if (!empty($comodidades)) {
                $comodidadesDecoded = json_decode($comodidades, true);
                if (!is_array($comodidadesDecoded)) {
                    $comodidades = json_encode([]);
                }
            } else {
                $comodidades = json_encode([]);
            }

            // Actualizar
            $data = [
                'nombre' => $nombre,
                'tipo' => $tipo,
                'capacidad' => $capacidad,
                'comodidades' => $comodidades,
                'activo' => $activo
            ];

            $updated = $busModel->update($id, $data);

            if ($updated !== false) {
                $this->json(['success' => true, 'message' => 'Transporte actualizado correctamente']);
            } else {
                error_log("Transport update returned false for ID: {$id}");
                $this->json(['success' => false, 'message' => 'No se realizaron cambios o error al actualizar'], 500);
            }

        } catch (Exception $e) {
            error_log("Error updating transport ID {$id}: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $this->json(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    public function deleteTransportPost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        try {
            require_once __DIR__ . '/../models/Bus.php';
            $busModel = new Bus();

            $transportId = (int)$this->getInput('transport_id');

            if (!$transportId) {
                $this->json(['success' => false, 'message' => 'ID de transporte no válido'], 400);
                return;
            }

            // Verificar que existe
            $transport = $busModel->find($transportId);
            if (!$transport) {
                $this->json(['success' => false, 'message' => 'Transporte no encontrado'], 404);
                return;
            }

            // Verificar si está en uso en alguna ruta
            $inUse = $this->db->fetchOne("SELECT COUNT(*) as count FROM rutas WHERE transporte_id = ?", [$transportId]);
            if ($inUse && $inUse['count'] > 0) {
                $this->json(['success' => false, 'message' => 'No se puede eliminar: el transporte está asignado a ' . $inUse['count'] . ' ruta(s)'], 400);
                return;
            }

            // Eliminar
            $deleted = $busModel->delete($transportId);

            if ($deleted) {
                $this->json(['success' => true, 'message' => 'Transporte eliminado correctamente']);
            } else {
                $this->json(['success' => false, 'message' => 'Error al eliminar el transporte'], 500);
            }

        } catch (Exception $e) {
            error_log("Error deleting transport: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    public function toggleTransportStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        try {
            require_once __DIR__ . '/../models/Bus.php';
            $busModel = new Bus();

            $transportId = (int)$this->getInput('transport_id');
            $active = (int)$this->getInput('active');

            if (!$transportId) {
                $this->json(['success' => false, 'message' => 'ID de transporte no válido'], 400);
                return;
            }

            // Verificar que existe
            $transport = $busModel->find($transportId);
            if (!$transport) {
                $this->json(['success' => false, 'message' => 'Transporte no encontrado'], 404);
                return;
            }

            // Actualizar estado
            $updated = $busModel->update($transportId, ['activo' => $active]);

            if ($updated) {
                $this->json(['success' => true, 'message' => 'Estado actualizado correctamente']);
            } else {
                $this->json(['success' => false, 'message' => 'Error al actualizar el estado'], 500);
            }

        } catch (Exception $e) {
            error_log("Error toggling transport status: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    public function bulkActionTransport()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        try {
            require_once __DIR__ . '/../models/Bus.php';
            $busModel = new Bus();

            $action = $this->getInput('action');
            $transportIdsJson = $this->getInput('transport_ids');
            $transportIds = json_decode($transportIdsJson, true);

            if (!is_array($transportIds) || empty($transportIds)) {
                $this->json(['success' => false, 'message' => 'No se seleccionaron transportes'], 400);
                return;
            }

            $successCount = 0;
            $failCount = 0;

            foreach ($transportIds as $id) {
                $id = (int)$id;
                if ($id < 1) continue;

                switch ($action) {
                    case 'activate':
                        $result = $busModel->update($id, ['activo' => 1]);
                        break;
                    case 'deactivate':
                        $result = $busModel->update($id, ['activo' => 0]);
                        break;
                    case 'delete':
                        // Verificar que no esté en uso
                        $inUse = $this->db->fetchOne("SELECT COUNT(*) as count FROM rutas WHERE transporte_id = ?", [$id]);
                        if ($inUse && $inUse['count'] > 0) {
                            $failCount++;
                            continue 2;
                        }
                        $result = $busModel->delete($id);
                        break;
                    default:
                        $this->json(['success' => false, 'message' => 'Acción no válida'], 400);
                        return;
                }

                if ($result) {
                    $successCount++;
                } else {
                    $failCount++;
                }
            }

            $message = "Operación completada: {$successCount} exitosos";
            if ($failCount > 0) {
                $message .= ", {$failCount} fallidos";
            }

            $this->json(['success' => true, 'message' => $message, 'success_count' => $successCount, 'fail_count' => $failCount]);

        } catch (Exception $e) {
            error_log("Error in bulk transport action: " . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    public function exportTransport()
    {
        try {
            require_once __DIR__ . '/../models/Bus.php';
            $busModel = new Bus();

            $transports = $busModel->findAll([], 'nombre ASC');

            // Preparar CSV
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="transportes_' . date('Y-m-d') . '.csv"');

            $output = fopen('php://output', 'w');

            // BOM para UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Encabezados
            fputcsv($output, ['ID', 'Nombre', 'Tipo', 'Capacidad', 'Comodidades', 'Estado', 'Fecha Creación']);

            // Datos
            foreach ($transports as $transport) {
                $comodidades = '';
                if (!empty($transport['comodidades'])) {
                    $comodidadesArray = json_decode($transport['comodidades'], true);
                    if (is_array($comodidadesArray)) {
                        $comodidades = implode(', ', $comodidadesArray);
                    }
                }

                fputcsv($output, [
                    $transport['id'],
                    $transport['nombre'],
                    $this->getTransportTypeLabel($transport['tipo']),
                    $transport['capacidad'],
                    $comodidades,
                    $transport['activo'] ? 'Activo' : 'Inactivo',
                    $transport['created_at']
                ]);
            }

            fclose($output);
            exit;

        } catch (Exception $e) {
            error_log("Error exporting transports: " . $e->getMessage());
            echo "Error al exportar transportes";
        }
    }

    // REPORTES
    public function reports()
    {
        // Redirigir a Dashboard
        $this->redirect("admin/dashboard", "Los reportes ahora están integrados en el Dashboard", "info");
    }

    // Helper: Subir imagen de tour
    private function uploadTourImage($file)
    {
        // Validar archivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Formato de imagen no permitido'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'La imagen excede el tamaño máximo de 5MB'];
        }

        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'product_' . time() . '_' . uniqid() . '.' . $extension;
        $uploadDir = __DIR__ . '/../../public/uploads/tours/';
        $uploadPath = $uploadDir . $filename;

        // Crear directorio si no existe
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $url = Config::getBaseUrl() . 'uploads/tours/' . $filename;
            return ['success' => true, 'url' => $url, 'filename' => $filename];
        }

        return ['success' => false, 'message' => 'Error al subir la imagen'];
    }

    // Subir foto de empleado
    private function uploadStaffPhoto($file)
    {
        // Validar archivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Formato de imagen no permitido'];
        }

        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'La imagen excede el tamaño máximo de 2MB'];
        }

        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'staff_' . time() . '_' . uniqid() . '.' . $extension;
        $uploadDir = __DIR__ . '/../../public/uploads/staff/';
        $uploadPath = $uploadDir . $filename;

        // Crear directorio si no existe
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => true, 'filename' => $filename];
        }

        return ['success' => false, 'message' => 'Error al subir la foto'];
    }

    // Configuración del sistema
    public function settings()
    {
        try {
            // Obtener configuraciones actuales
            $configs = $this->db->fetchAll("SELECT * FROM configuraciones ORDER BY clave");

            // Obtener la imagen de portada actual
            $heroImage = $this->db->fetch("SELECT valor FROM configuraciones WHERE clave = 'hero_image'");
            $currentHeroImage = $heroImage ? $heroImage['valor'] : 'images/hero-travel.jpg';

            // Obtener tipo de hero
            $heroTypeRow = $this->db->fetch("SELECT valor FROM configuraciones WHERE clave = 'hero_type'");
            $heroType = $heroTypeRow ? $heroTypeRow['valor'] : 'image';

            $this->view('admin/settings', [
                'title' => 'Configuración del Sistema',
                'configs' => $configs,
                'heroImage' => $currentHeroImage,
                'heroType' => $heroType
            ]);
        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                throw $e;
            }

            $this->view('admin/settings', [
                'title' => 'Configuración del Sistema',
                'configs' => [],
                'heroImage' => 'images/hero-travel.jpg',
                'heroType' => 'image',
                'error' => 'Error al cargar configuraciones'
            ]);
        }
    }

    // Subir imagen de portada
    public function uploadHeroImage()
    {
        $this->requireAdmin();

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Helpers::setFlashMessage('error', 'Método no permitido');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
                exit;
            }

            if (!isset($_FILES['hero_image']) || $_FILES['hero_image']['error'] !== UPLOAD_ERR_OK) {
                Helpers::setFlashMessage('error', 'Error al subir la imagen');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
                exit;
            }

            $file = $_FILES['hero_image'];

            // Validar tipo de archivo
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $fileType = mime_content_type($file['tmp_name']);

            if (!in_array($fileType, $allowedTypes)) {
                Helpers::setFlashMessage('error', 'Tipo de archivo no permitido. Solo se permiten JPG, PNG y WebP');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
                exit;
            }

            // Validar tamaño (máximo 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                Helpers::setFlashMessage('error', 'La imagen es demasiado grande. Máximo 5MB');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
                exit;
            }

            // Crear directorio si no existe
            $uploadDir = __DIR__ . '/../../public/assets/images/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generar nombre único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'hero-' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;

            // Mover archivo
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                Helpers::setFlashMessage('error', 'Error al guardar la imagen');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
                exit;
            }

            // Guardar la configuración en la base de datos
            $imagePath = 'images/uploads/' . $filename;

            // Verificar si existe la configuración
            $exists = $this->db->fetch("SELECT id FROM configuraciones WHERE clave = 'hero_image'");

            if ($exists) {
                // Actualizar
                $this->db->update('configuraciones', [
                    'valor' => $imagePath,
                    'descripcion' => 'Imagen de portada de la página de inicio'
                ], 'clave = :clave', ['clave' => 'hero_image']);
            } else {
                // Insertar
                $this->db->insert('configuraciones', [
                    'clave' => 'hero_image',
                    'valor' => $imagePath,
                    'descripcion' => 'Imagen de portada de la página de inicio',
                    'tipo' => 'texto'
                ]);
            }

            Helpers::setFlashMessage('success', 'Imagen de portada actualizada correctamente');

        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                Helpers::setFlashMessage('error', 'Error: ' . $e->getMessage());
            } else {
                Helpers::setFlashMessage('error', 'Error al subir la imagen');
            }
        }

        header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
        exit;
    }

    // Subir video de portada
    public function uploadHeroVideo()
    {
        $this->requireAdmin();

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Helpers::setFlashMessage('error', 'Método no permitido');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
                exit;
            }

            if (!isset($_FILES['hero_video']) || $_FILES['hero_video']['error'] !== UPLOAD_ERR_OK) {
                Helpers::setFlashMessage('error', 'Error al subir el video');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
                exit;
            }

            $file = $_FILES['hero_video'];

            // Validar tipo de archivo
            $allowedTypes = ['video/mp4', 'video/webm'];
            $fileType = mime_content_type($file['tmp_name']);

            if (!in_array($fileType, $allowedTypes)) {
                Helpers::setFlashMessage('error', 'Tipo de archivo no permitido. Solo se permiten MP4 y WebM');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
                exit;
            }

            // Validar tamaño (máximo 50MB)
            if ($file['size'] > 50 * 1024 * 1024) {
                Helpers::setFlashMessage('error', 'El video es demasiado grande. Máximo 50MB');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
                exit;
            }

            // Crear directorio si no existe
            $uploadDir = __DIR__ . '/../../public/assets/videos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generar nombre único
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'hero-' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;

            // Mover archivo
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                Helpers::setFlashMessage('error', 'Error al guardar el video');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
                exit;
            }

            // Guardar configuraciones
            $videoPath = 'videos/' . $filename;

            // Actualizar/insertar hero_image con la ruta del video
            $exists = $this->db->fetch("SELECT id FROM configuraciones WHERE clave = 'hero_image'");
            if ($exists) {
                $this->db->update('configuraciones', [
                    'valor' => $videoPath,
                    'descripcion' => 'Video de portada de la página de inicio'
                ], 'clave = :clave', ['clave' => 'hero_image']);
            } else {
                $this->db->insert('configuraciones', [
                    'clave' => 'hero_image',
                    'valor' => $videoPath,
                    'descripcion' => 'Video de portada de la página de inicio',
                    'tipo' => 'texto'
                ]);
            }

            // Guardar tipo de hero
            $existsType = $this->db->fetch("SELECT id FROM configuraciones WHERE clave = 'hero_type'");
            if ($existsType) {
                $this->db->update('configuraciones', [
                    'valor' => 'video'
                ], 'clave = :clave', ['clave' => 'hero_type']);
            } else {
                $this->db->insert('configuraciones', [
                    'clave' => 'hero_type',
                    'valor' => 'video',
                    'descripcion' => 'Tipo de hero (image/video/youtube)',
                    'tipo' => 'texto'
                ]);
            }

            // Guardar opciones de video
            $autoplay = isset($_POST['video_autoplay']) ? '1' : '0';
            $loop = isset($_POST['video_loop']) ? '1' : '0';

            $this->db->query("INSERT INTO configuraciones (clave, valor, descripcion, tipo) VALUES ('hero_video_autoplay', ?, 'Autoplay del video hero', 'boolean') ON DUPLICATE KEY UPDATE valor = ?", [$autoplay, $autoplay]);
            $this->db->query("INSERT INTO configuraciones (clave, valor, descripcion, tipo) VALUES ('hero_video_loop', ?, 'Loop del video hero', 'boolean') ON DUPLICATE KEY UPDATE valor = ?", [$loop, $loop]);

            Helpers::setFlashMessage('success', 'Video de portada actualizado correctamente');

        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                Helpers::setFlashMessage('error', 'Error: ' . $e->getMessage());
            } else {
                Helpers::setFlashMessage('error', 'Error al subir el video');
            }
        }

        header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
        exit;
    }

    // Subir imagen para configuración de empresa
    public function uploadConfigImage()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Helpers::isPost()) {
            echo json_encode(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'No se recibió ninguna imagen']);
            return;
        }

        $file = $_FILES['image'];
        $fieldKey = $_POST['field_key'] ?? 'config_image';

        // Validar tipo de archivo
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido. Use JPG, PNG, GIF o WebP']);
            return;
        }

        // Validar tamaño (5MB)
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'La imagen es muy grande. Máximo 5MB']);
            return;
        }

        // Determinar carpeta de destino según el campo
        $folder = 'images';
        if (strpos($fieldKey, 'hero') !== false) {
            $folder = 'images/hero';
        } elseif (strpos($fieldKey, 'story') !== false || strpos($fieldKey, 'history') !== false) {
            $folder = 'images/about';
        } elseif (strpos($fieldKey, 'team') !== false) {
            $folder = 'images/team';
        }

        // Crear carpeta si no existe
        $uploadPath = __DIR__ . '/../../public/assets/' . $folder;
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Generar nombre único
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $fieldKey . '_' . time() . '.' . $extension;
        $filepath = $uploadPath . '/' . $filename;

        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Guardar ruta relativa desde assets/ (sin incluir 'assets/')
            $relativePath = $folder . '/' . $filename;
            echo json_encode([
                'success' => true,
                'message' => 'Imagen subida correctamente',
                'path' => $relativePath,
                'filename' => $filename
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al mover el archivo']);
        }
    }

    // Guardar URL externa para hero
    public function saveHeroUrl()
    {
        $this->requireAdmin();

        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Helpers::setFlashMessage('error', 'Método no permitido');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
                exit;
            }

            $heroUrl = $_POST['hero_url'] ?? '';
            $heroPoster = $_POST['hero_poster'] ?? '';

            if (empty($heroUrl)) {
                Helpers::setFlashMessage('error', 'La URL es requerida');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
                exit;
            }

            // Validar que sea una URL válida
            if (!filter_var($heroUrl, FILTER_VALIDATE_URL)) {
                Helpers::setFlashMessage('error', 'URL no válida');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
                exit;
            }

            // Determinar el tipo (youtube u otra)
            $heroType = (strpos($heroUrl, 'youtube.com') !== false || strpos($heroUrl, 'youtu.be') !== false) ? 'youtube' : 'url';

            // Guardar hero_image con la URL
            $exists = $this->db->fetch("SELECT id FROM configuraciones WHERE clave = 'hero_image'");
            if ($exists) {
                $this->db->update('configuraciones', [
                    'valor' => $heroUrl,
                    'descripcion' => 'URL del video de portada'
                ], 'clave = :clave', ['clave' => 'hero_image']);
            } else {
                $this->db->insert('configuraciones', [
                    'clave' => 'hero_image',
                    'valor' => $heroUrl,
                    'descripcion' => 'URL del video de portada',
                    'tipo' => 'texto'
                ]);
            }

            // Guardar tipo
            $existsType = $this->db->fetch("SELECT id FROM configuraciones WHERE clave = 'hero_type'");
            if ($existsType) {
                $this->db->update('configuraciones', [
                    'valor' => $heroType
                ], 'clave = :clave', ['clave' => 'hero_type']);
            } else {
                $this->db->insert('configuraciones', [
                    'clave' => 'hero_type',
                    'valor' => $heroType,
                    'descripcion' => 'Tipo de hero (image/video/youtube)',
                    'tipo' => 'texto'
                ]);
            }

            // Guardar poster si existe
            if (!empty($heroPoster)) {
                $existsPoster = $this->db->fetch("SELECT id FROM configuraciones WHERE clave = 'hero_poster'");
                if ($existsPoster) {
                    $this->db->update('configuraciones', [
                        'valor' => $heroPoster
                    ], 'clave = :clave', ['clave' => 'hero_poster']);
                } else {
                    $this->db->insert('configuraciones', [
                        'clave' => 'hero_poster',
                        'valor' => $heroPoster,
                        'descripcion' => 'Imagen de respaldo para el video hero',
                        'tipo' => 'texto'
                    ]);
                }
            }

            Helpers::setFlashMessage('success', 'URL del hero actualizada correctamente');

        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                Helpers::setFlashMessage('error', 'Error: ' . $e->getMessage());
            } else {
                Helpers::setFlashMessage('error', 'Error al guardar la URL');
            }
        }

        header('Location: ' . Config::getBaseUrl() . '?route=admin/settings');
        exit;
    }

    // Perfil del administrador
    public function profile()
    {
        try {
            $userId = $this->auth->getCurrentUser()['id'];
            $user = $this->db->fetch("SELECT * FROM usuarios WHERE id = :id", ['id' => $userId]);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Actualizar perfil
                $data = [
                    'nombre' => Helpers::sanitizeString($_POST['nombre']),
                    'email' => Helpers::sanitizeString($_POST['email']),
                    'telefono' => isset($_POST['telefono']) ? Helpers::sanitizeString($_POST['telefono']) : null
                ];

                // Si se proporcionó nueva contraseña
                if (!empty($_POST['password'])) {
                    $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                }

                $this->db->update('usuarios', $data, 'id = :id', ['id' => $userId]);

                Helpers::setFlashMessage('success', 'Perfil actualizado correctamente');
                header('Location: ' . Config::getBaseUrl() . '?route=admin/profile');
                exit;
            }

            $this->view('admin/profile', [
                'title' => 'Mi Perfil',
                'user' => $user
            ]);
        } catch (Exception $e) {
            if (Config::isDevelopment()) {
                throw $e;
            }

            Helpers::setFlashMessage('error', 'Error al cargar el perfil');
            header('Location: ' . Config::getBaseUrl() . '?route=admin');
            exit;
        }
    }

    /**
     * Manejar subida de imagen para rutas
     */
    private function handleRouteImageUpload($file)
    {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Tipo de archivo no permitido. Solo JPG, PNG y WebP.'];
        }

        if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
            return ['success' => false, 'message' => 'La imagen es demasiado grande. Máximo 5MB.'];
        }

        $uploadDir = __DIR__ . '/../../public/assets/images/routes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'route-' . time() . '-' . uniqid() . '.' . $extension;
        $uploadPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return ['success' => true, 'path' => 'images/routes/' . $filename];
        }

        return ['success' => false, 'message' => 'Error al guardar la imagen.'];
    }

    /**
     * Listar fechas de disponibilidad de un tour (AJAX)
     */
    public function listAvailability()
    {
        $tourId = $this->getInput('tour_id');

        if (!$tourId) {
            $this->json(['success' => false, 'message' => 'ID de tour requerido'], 400);
            return;
        }

        try {
            $dates = $this->db->fetchAll(
                "SELECT * FROM disponibilidad
                 WHERE tour_id = :tour_id
                 ORDER BY fecha_salida ASC",
                ['tour_id' => $tourId]
            );

            $this->json(['success' => true, 'dates' => $dates]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al cargar fechas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear una nueva fecha de disponibilidad (AJAX)
     */
    public function createAvailability()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        // Obtener datos JSON
        $jsonData = file_get_contents('php://input');
        $data = json_decode($jsonData, true);

        if (!$data) {
            $this->json(['success' => false, 'message' => 'Datos inválidos'], 400);
            return;
        }

        // Validación
        if (empty($data['tour_id']) || empty($data['fecha_salida']) || empty($data['cupos_disponibles'])) {
            $this->json(['success' => false, 'message' => 'Faltan campos requeridos'], 400);
            return;
        }

        try {
            // Verificar que no exista ya esa fecha para ese tour
            $existing = $this->db->fetch(
                "SELECT id FROM disponibilidad
                 WHERE tour_id = :tour_id
                 AND fecha_salida = :fecha_salida",
                [
                    'tour_id' => $data['tour_id'],
                    'fecha_salida' => $data['fecha_salida']
                ]
            );

            if ($existing) {
                $this->json(['success' => false, 'message' => 'Ya existe una fecha de disponibilidad para ese día'], 400);
                return;
            }

            // Insertar nueva fecha
            $insertData = [
                'tour_id' => (int)$data['tour_id'],
                'fecha_salida' => $data['fecha_salida'],
                'fecha_regreso' => $data['fecha_regreso'] ?? $data['fecha_salida'],
                'cupos_disponibles' => (int)$data['cupos_disponibles'],
                'cupos_reservados' => 0,
                'precio_especial' => !empty($data['precio_especial']) ? (float)$data['precio_especial'] : null,
                'activo' => 1
            ];

            $id = $this->db->insert('disponibilidad', $insertData);

            if ($id) {
                $this->json(['success' => true, 'message' => 'Fecha agregada exitosamente', 'id' => $id]);
            } else {
                $this->json(['success' => false, 'message' => 'Error al insertar la fecha'], 500);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Eliminar una fecha de disponibilidad (AJAX)
     */
    public function deleteAvailability()
    {
        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        $id = $this->getInput('id');

        if (!$id) {
            $this->json(['success' => false, 'message' => 'ID requerido'], 400);
            return;
        }

        try {
            // Verificar que no tenga reservas
            $availability = $this->db->fetch(
                "SELECT cupos_reservados FROM disponibilidad WHERE id = :id",
                ['id' => $id]
            );

            if (!$availability) {
                $this->json(['success' => false, 'message' => 'Fecha no encontrada'], 404);
                return;
            }

            if ($availability['cupos_reservados'] > 0) {
                $this->json(['success' => false, 'message' => 'No se puede eliminar una fecha con reservas activas'], 400);
                return;
            }

            // Eliminar
            $result = $this->db->delete('disponibilidad', 'id = :id', ['id' => $id]);

            if ($result) {
                $this->json(['success' => true, 'message' => 'Fecha eliminada exitosamente']);
            } else {
                $this->json(['success' => false, 'message' => 'Error al eliminar la fecha'], 500);
            }
        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    // ==========================================
    // AUDIT LOG
    // ==========================================

    /**
     * Ver registros de auditoría
     */
    public function audit()
    {
        try {
            $isEnglish = $this->db->columnExists('audit_log', 'user_id');
            $actionCol = $isEnglish ? 'action' : 'accion';
            $tableCol = $isEnglish ? 'table_name' : 'modulo';
            $recordCol = $isEnglish ? 'record_id' : 'registro_id';
            $userIdCol = $isEnglish ? 'user_id' : 'usuario_id';
            $oldCol = $isEnglish ? 'old_values' : 'datos_anteriores';
            $newCol = $isEnglish ? 'new_values' : 'datos_nuevos';

            $userEmailCol = null;
            if ($this->db->columnExists('audit_log', 'user_email')) {
                $userEmailCol = 'user_email';
            } elseif ($this->db->columnExists('audit_log', 'usuario_email')) {
                $userEmailCol = 'usuario_email';
            }

            // Parámetros de filtro
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $perPage = 50;
            $offset = ($page - 1) * $perPage;

            $action = $_GET['action'] ?? '';
            $user = $_GET['user'] ?? '';
            $dateFrom = $_GET['date_from'] ?? '';
            $dateTo = $_GET['date_to'] ?? '';

            // Construir query con filtros
            $where = ['1=1'];
            $params = [];

            if (!empty($action)) {
                $where[] = "al.{$actionCol} LIKE :action";
                $params['action'] = "%{$action}%";
            }

            if (!empty($user)) {
                $userClauses = [];
                if ($userEmailCol) {
                    $userClauses[] = "al.{$userEmailCol} LIKE :user";
                }
                $userClauses[] = "u.email LIKE :user";
                $userClauses[] = "u.nombre LIKE :user";
                if (ctype_digit($user)) {
                    $userClauses[] = "al.{$userIdCol} = :user_id";
                    $params['user_id'] = (int)$user;
                }

                $where[] = '(' . implode(' OR ', $userClauses) . ')';
                $params['user'] = "%{$user}%";
            }

            if (!empty($dateFrom)) {
                $where[] = 'al.created_at >= :date_from';
                $params['date_from'] = $dateFrom . ' 00:00:00';
            }

            if (!empty($dateTo)) {
                $where[] = 'al.created_at <= :date_to';
                $params['date_to'] = $dateTo . ' 23:59:59';
            }

            $whereClause = implode(' AND ', $where);
            $join = "LEFT JOIN usuarios u ON al.{$userIdCol} = u.id";

            // Total de registros
            $totalSql = "SELECT COUNT(*) as total FROM audit_log al {$join} WHERE {$whereClause}";
            $total = $this->db->fetchColumn($totalSql, $params);
            $totalPages = ceil($total / $perPage);

            // Obtener logs
            $emailExpr = $userEmailCol ? "COALESCE(al.{$userEmailCol}, u.email)" : "u.email";
            $sql = "
                SELECT
                    al.id,
                    al.created_at,
                    al.ip_address,
                    al.user_agent,
                    al.{$actionCol} as action,
                    al.{$tableCol} as table_name,
                    al.{$recordCol} as record_id,
                    al.{$oldCol} as old_values,
                    al.{$newCol} as new_values,
                    al.{$userIdCol} as user_id,
                    {$emailExpr} as user_email,
                    u.nombre as user_name
                FROM audit_log al
                {$join}
                WHERE {$whereClause}
                ORDER BY al.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}
            ";

            $logs = $this->db->fetchAll($sql, $params);

            // Estadísticas
            $stats = [
                'total_logs' => $this->db->fetchColumn("SELECT COUNT(*) FROM audit_log"),
                'today_logs' => $this->db->fetchColumn("SELECT COUNT(*) FROM audit_log WHERE DATE(created_at) = CURDATE()"),
                'unique_users' => $this->db->fetchColumn("SELECT COUNT(DISTINCT {$userIdCol}) FROM audit_log WHERE {$userIdCol} IS NOT NULL"),
                'actions_by_type' => $this->db->fetchAll("
                    SELECT {$actionCol} as action, COUNT(*) as count
                    FROM audit_log
                    GROUP BY {$actionCol}
                    ORDER BY count DESC
                    LIMIT 10
                ")
            ];

            $this->view('admin/audit', [
                'logs' => $logs,
                'stats' => $stats,
                'pagination' => [
                    'page' => $page,
                    'total_pages' => $totalPages,
                    'total' => $total,
                    'per_page' => $perPage
                ],
                'filters' => [
                    'action' => $action,
                    'user' => $user,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo
                ]
            ]);

        } catch (Exception $e) {
            error_log('Error al cargar audit logs: ' . $e->getMessage());
            Helpers::setFlashMessage('error', 'Error al cargar audit logs: ' . $e->getMessage());
            $this->redirect('admin');
        }
    }

    /**
     * Configuración de WhatsApp
     */
    public function whatsappSettings()
    {
        // Verificar autenticación de admin
        if (!$this->auth->isLoggedIn() || !$this->auth->isAdmin()) {
            $this->redirect('admin/login');
            return;
        }

        try {
            // Obtener configuración actual
            $config = $this->db->fetchOne("SELECT * FROM whatsapp_config WHERE id = 1");

            // Si no existe, crear configuración por defecto
            if (!$config) {
                $this->db->insert('whatsapp_config', [
                    'phone_number' => '502XXXXXXXX',
                    'welcome_message' => 'Hola! Me interesa conocer más sobre sus tours en Guatemala.',
                    'button_text' => 'Chatea con nosotros',
                    'button_position' => 'bottom-right',
                    'is_active' => 1,
                    'business_hours_only' => 0
                ]);
                $config = $this->db->fetchOne("SELECT * FROM whatsapp_config WHERE id = 1");
            }

            $this->view('admin/settings/whatsapp', [
                'title' => 'Configuración de WhatsApp',
                'config' => $config
            ]);

        } catch (Exception $e) {
            error_log('Error al cargar configuración: ' . $e->getMessage());
            Helpers::setFlashMessage('error', 'Error al cargar configuración: ' . $e->getMessage());
            $this->redirect('admin');
        }
    }

    /**
     * Guardar configuración de WhatsApp
     */
    public function saveWhatsappSettings()
    {
        // Verificar autenticación de admin
        if (!$this->auth->isLoggedIn() || !$this->auth->isAdmin()) {
            $this->json(['success' => false, 'message' => 'No autorizado'], 403);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }

        try {
            $phoneNumber = $_POST['phone_number'] ?? '';
            $welcomeMessage = $_POST['welcome_message'] ?? '';
            $buttonText = $_POST['button_text'] ?? 'Chatea con nosotros';
            $buttonPosition = $_POST['button_position'] ?? 'bottom-right';
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $businessHoursOnly = isset($_POST['business_hours_only']) ? 1 : 0;
            $businessHoursStart = $_POST['business_hours_start'] ?? '08:00';
            $businessHoursEnd = $_POST['business_hours_end'] ?? '18:00';
            $businessDays = isset($_POST['business_days']) ? implode(',', $_POST['business_days']) : 'mon,tue,wed,thu,fri';

            // Validación
            if (empty($phoneNumber)) {
                $this->json(['success' => false, 'message' => 'El número de WhatsApp es requerido'], 400);
                return;
            }

            // Limpiar número (remover espacios, guiones, etc.)
            $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

            if (strlen($phoneNumber) < 8) {
                $this->json(['success' => false, 'message' => 'Número de WhatsApp inválido'], 400);
                return;
            }

            // Actualizar o insertar configuración
            $config = $this->db->fetchOne("SELECT * FROM whatsapp_config WHERE id = 1");

            $data = [
                'phone_number' => $phoneNumber,
                'welcome_message' => $welcomeMessage,
                'button_text' => $buttonText,
                'button_position' => $buttonPosition,
                'is_active' => $isActive,
                'business_hours_only' => $businessHoursOnly,
                'business_hours_start' => $businessHoursStart . ':00',
                'business_hours_end' => $businessHoursEnd . ':00',
                'business_days' => $businessDays,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($config) {
                // Actualizar
                $this->db->update('whatsapp_config', $data, 'id = 1');
            } else {
                // Insertar
                $this->db->insert('whatsapp_config', $data);
            }

            $this->json([
                'success' => true,
                'message' => 'Configuración guardada exitosamente'
            ]);

        } catch (Exception $e) {
            $this->json([
                'success' => false,
                'message' => 'Error al guardar configuración: ' . $e->getMessage()
            ], 500);
        }
    }

    // ========================================
    // PAYMENT GATEWAYS CONFIGURATION
    // ========================================

    /**
     * Configurar pasarelas de pago para un tour
     */
    public function tourPaymentGateways($productId = null)
    {
        if (!$productId) {
            $this->redirect('admin/tours', 'Tour no encontrado', 'error');
            return;
        }

        $product = $this->tourModel->find($productId);
        if (!$product) {
            $this->redirect('admin/tours', 'Tour no encontrado', 'error');
            return;
        }

        // Si es POST, procesar actualización
        if (Helpers::isPost()) {
            $this->updateTourPaymentGateways($productId);
            return;
        }

        // Obtener pasarelas actuales del tour
        $currentGateways = json_decode($product['payment_gateways_enabled'] ?? '[]', true);
        if (empty($currentGateways)) {
            // Default: Stripe y RNPL
            $currentGateways = ['stripe', 'rnpl'];
        }

        // Obtener todas las pasarelas disponibles
        $availableGateways = \App\Core\PaymentGatewayFactory::getEnabledGateways();

        // Obtener información de cada pasarela
        $gatewayInfo = [];
        foreach ($availableGateways as $gateway) {
            $info = \App\Core\PaymentGatewayFactory::getGatewayInfo($gateway);
            if ($info) {
                $gatewayInfo[$gateway] = $info;
            }
        }

        $this->view('admin/tours/payment-gateways', [
            'title' => 'Configurar Métodos de Pago - ' . $product['nombre'],
            'product' => $product,
            'current_gateways' => $currentGateways,
            'available_gateways' => $availableGateways,
            'gateway_info' => $gatewayInfo,
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }

    /**
     * Actualizar pasarelas de pago de un tour
     */
    private function updateTourPaymentGateways($productId)
    {
        $this->validateCsrf();

        $selectedGateways = $_POST['payment_gateways'] ?? [];

        // Validar que al menos una pasarela esté seleccionada
        if (empty($selectedGateways)) {
            $this->redirect(
                'admin/tours/payment-gateways/' . $productId,
                'Debes seleccionar al menos un método de pago',
                'error'
            );
            return;
        }

        // Validar que las pasarelas seleccionadas sean válidas
        $availableGateways = \App\Core\PaymentGatewayFactory::getEnabledGateways();
        $validGateways = array_intersect($selectedGateways, $availableGateways);

        if (count($validGateways) !== count($selectedGateways)) {
            $this->redirect(
                'admin/tours/payment-gateways/' . $productId,
                'Algunas pasarelas seleccionadas no están disponibles',
                'error'
            );
            return;
        }

        // Actualizar tour
        try {
            $this->db->update('tours', [
                'payment_gateways_enabled' => json_encode(array_values($selectedGateways))
            ], 'id = :id', ['id' => $productId]);

            $this->redirect(
                'admin/tours/edit/' . $productId,
                'Métodos de pago actualizados correctamente',
                'success'
            );
        } catch (Exception $e) {
            $this->redirect(
                'admin/tours/payment-gateways/' . $productId,
                'Error al actualizar métodos de pago: ' . $e->getMessage(),
                'error'
            );
        }
    }

    /**
     * Configuración global de pasarelas de pago (credenciales)
     */
    public function paymentSettings()
    {
        // Obtener todas las configuraciones de la base de datos
        $settings = $this->db->fetchAll(
            "SELECT * FROM payment_settings ORDER BY gateway, setting_key"
        );

        // Organizar por gateway
        $gatewaySettings = [];
        foreach ($settings as $setting) {
            $gatewaySettings[$setting['gateway']][$setting['setting_key']] = $setting['setting_value'];
        }

        // Si no hay configuraciones, crear valores por defecto
        if (empty($gatewaySettings)) {
            $config = require __DIR__ . '/../../config.local.php';

            $gatewaySettings = [
                'stripe' => [
                    'publishable_key' => $config['STRIPE_PUBLISHABLE_KEY'] ?? '',
                    'secret_key' => $config['STRIPE_SECRET_KEY'] ?? '',
                    'webhook_secret' => $config['STRIPE_WEBHOOK_SECRET'] ?? '',
                    'enabled' => 'true'
                ],
                'paggo' => [
                    'api_key' => $config['PAGGO_API_KEY'] ?? '',
                    'base_url' => $config['PAGGO_BASE_URL'] ?? 'https://api-staging.paggoapp.com',
                    'link_expiration_hours' => $config['PAGGO_LINK_EXPIRATION_HOURS'] ?? '48',
                    'enabled' => $config['PAGGO_ENABLED'] ? 'true' : 'false'
                ],
                'recurrente' => [
                    'public_key' => $config['RECURRENTE_PUBLIC_KEY'] ?? '',
                    'secret_key' => $config['RECURRENTE_SECRET_KEY'] ?? '',
                    'webhook_secret' => $config['RECURRENTE_WEBHOOK_SECRET'] ?? '',
                    'base_url' => $config['RECURRENTE_BASE_URL'] ?? 'https://app.recurrente.com/api',
                    'default_currency' => $config['RECURRENTE_DEFAULT_CURRENCY'] ?? 'USD',
                    'enabled' => $config['RECURRENTE_ENABLED'] ? 'true' : 'false'
                ],
                'rnpl' => [
                    'api_key' => $config['RNPL_API_KEY'] ?? '',
                    'enabled' => isset($config['RNPL_ENABLED']) ? ($config['RNPL_ENABLED'] ? 'true' : 'false') : 'false'
                ]
            ];
        }

        $this->view('admin/settings/payment-settings', [
            'title' => 'Configuración de Pasarelas de Pago',
            'gateway_settings' => $gatewaySettings,
            'csrf_token' => Helpers::generateCsrfToken()
        ]);
    }

    /**
     * Actualizar configuración de pasarelas de pago
     */
    public function updatePaymentSettings()
    {
        $this->validateCsrf();

        if (!Helpers::isPost()) {
            $this->redirect('admin/settings/payments', 'Método no permitido', 'error');
            return;
        }

        try {
            $gateways = ['stripe', 'paggo', 'recurrente', 'rnpl'];

            // Preparar statement para insert/update
            $stmt = $this->db->getConnection()->prepare(
                "INSERT INTO payment_settings (gateway, setting_key, setting_value)
                 VALUES (:gateway, :key, :value)
                 ON DUPLICATE KEY UPDATE setting_value = :value2"
            );

            foreach ($gateways as $gateway) {
                $prefix = strtoupper($gateway) . '_';

                foreach ($_POST as $key => $value) {
                    // Solo procesar campos que pertenecen a este gateway
                    if (strpos($key, $prefix) === 0) {
                        // Remover el prefijo del gateway para obtener el setting_key
                        $settingKey = strtolower(str_replace($prefix, '', $key));

                        // Sanitizar valor
                        $sanitizedValue = is_array($value) ? json_encode($value) : trim($value);

                        // Ejecutar inserción/actualización
                        $stmt->execute([
                            'gateway' => $gateway,
                            'key' => $settingKey,
                            'value' => $sanitizedValue,
                            'value2' => $sanitizedValue
                        ]);
                    }
                }
            }

            // Limpiar caché de configuración si existe
            if (function_exists('opcache_reset')) {
                opcache_reset();
            }

            // Limpiar caché de Config
            \App\Core\Config::clearCache();

            $this->redirect(
                'admin/settings/payments',
                'Configuración actualizada correctamente. Las credenciales ahora están guardadas en la base de datos.',
                'success'
            );

        } catch (Exception $e) {
            $this->redirect(
                'admin/settings/payments',
                'Error al actualizar configuración: ' . $e->getMessage(),
                'error'
            );
        }
    }


    // ==========================================
    // GESTIÓN DE PUNTOS DE ENCUENTRO
    // ==========================================

    public function listMeetingPoints()
    {
        $tourId = $this->getInput('tour_id');
        if (!$tourId) {
            $this->json(['error' => 'ID de tour requerido'], 400);
            return;
        }

        $points = $this->tourMeetingPointModel->getByTourId($tourId);
        $this->json($points);
    }

    public function createMeetingPoint()
    {
        if (!Helpers::isPost()) {
            $this->json(['error' => 'Método no permitido'], 405);
            return;
        }

        $tourId = $this->getInput('tour_id');
        $type = $this->getInput('type', 'meeting_point');
        $title = $this->getInput('title');
        $address = $this->getInput('address');
        $mapLink = $this->getInput('map_link');
        $description = $this->getInput('description');
        
        $data = [
            'tour_id' => $tourId,
            'type' => $type,
            'title' => $title,
            'address' => $address,
            'map_link' => $mapLink,
            'description' => $description
        ];

        if (empty($tourId)) {
            $this->json(['error' => 'ID de tour requerido'], 400);
            return;
        }
        
        if ($type === 'hotel_pickup' && empty($title)) {
            $data['title'] = 'Recogida en Hotel';
        }

        if (empty($data['title'])) {
            $this->json(['error' => 'El título es requerido'], 400);
            return;
        }

        try {
            $id = $this->tourMeetingPointModel->create($data);
            $this->json(['success' => true, 'id' => $id, 'message' => 'Punto de encuentro agregado']);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteMeetingPoint()
    {
        if (!Helpers::isPost()) {
            $this->json(['error' => 'Método no permitido'], 405);
            return;
        }

        $id = $this->getInput('id');
        if (!$id) {
            $this->json(['error' => 'ID requerido'], 400);
            return;
        }

        try {
            $this->tourMeetingPointModel->delete($id);
            $this->json(['success' => true, 'message' => 'Punto de encuentro eliminado']);
        } catch (Exception $e) {
            $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
