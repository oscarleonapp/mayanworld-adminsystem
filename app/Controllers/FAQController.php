<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use Exception;

// Load helper classes manually (no autoloader)
require_once __DIR__ . '/../Helpers/AuditLogger.php';

use App\Helpers\AuditLogger;

class FAQController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    /**
     * Listar FAQs con filtros
     */
    public function list()
    {
        $page = (int)$this->getInput('page', 1);
        $categoria = $this->getInput('categoria');
        $activo = $this->getInput('activo');
        $search = $this->getInput('search');

        // Construir query con filtros
        $where = [];
        $params = [];

        if ($categoria) {
            $where[] = "categoria = :categoria";
            $params['categoria'] = $categoria;
        }

        if ($activo !== null && $activo !== '') {
            $where[] = "activo = :activo";
            $params['activo'] = (int)$activo;
        }

        if ($search) {
            $where[] = "(pregunta LIKE :search OR respuesta LIKE :search)";
            $params['search'] = "%{$search}%";
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Paginación
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        // Obtener FAQs
        $sql = "SELECT * FROM faqs
                {$whereClause}
                ORDER BY orden ASC, created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $faqs = $this->db->fetchAll($sql, $params);

        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM faqs {$whereClause}";
        $total = $this->db->fetch($countSql, $params)['total'];
        $totalPages = ceil($total / $perPage);

        // Obtener categorías únicas
        $categorias = $this->getCategories();

        // Agrupar FAQs por categoría para la vista
        $faqsPorCategoria = [];
        foreach ($faqs as $faq) {
            $cat = $faq['categoria'] ?? 'Sin categoría';
            if (!isset($faqsPorCategoria[$cat])) {
                $faqsPorCategoria[$cat] = [];
            }
            $faqsPorCategoria[$cat][] = $faq;
        }

        $this->view('admin/faqs/list', [
            'title' => 'Gestión de FAQs',
            'faqs' => $faqs,
            'faqsPorCategoria' => $faqsPorCategoria,
            'categorias' => $categorias,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'filters' => [
                'categoria' => $categoria,
                'activo' => $activo,
                'search' => $search
            ]
        ]);
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $categorias = $this->getCategories();

        $this->view('admin/faqs/form', [
            'title' => 'Crear FAQ',
            'action' => 'create',
            'faq' => null,
            'categorias' => $categorias
        ]);
    }

    /**
     * Guardar nueva FAQ
     */
    public function store()
    {
        try {
            $this->validateCsrf();

            $data = [
                'categoria' => $this->sanitizeInput($this->getInput('categoria')),
                'pregunta' => $this->sanitizeInput($this->getInput('pregunta')),
                'respuesta' => $this->getInput('respuesta'), // No sanitizar HTML de respuesta
                'orden' => (int)$this->getInput('orden', 0),
                'activo' => (int)$this->getInput('activo', 1)
            ];

            // Validar datos
            $errors = $this->validateFAQ($data);
            if (!empty($errors)) {
                $this->json(['success' => false, 'message' => implode(', ', $errors)]);
                return;
            }

            // Crear FAQ
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $faqId = $this->db->insert('faqs', $data);

            // Registrar en audit log (opcional)
            try {
                AuditLogger::log(
                    'crear',
                    'faqs',
                    $faqId,
                    $data['pregunta'],
                    null,
                    $data
                );
            } catch (Exception $e) {
                // Audit log no disponible, continuar
            }

            $this->json(['success' => true, 'message' => 'FAQ creada exitosamente', 'id' => $faqId]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al crear FAQ: ' . $e->getMessage()]);
        }
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit($id)
    {
        $faq = $this->db->fetch("SELECT * FROM faqs WHERE id = :id", ['id' => $id]);

        if (!$faq) {
            $this->json(['success' => false, 'message' => 'FAQ no encontrada'], 404);
            return;
        }

        // Devolver JSON para el modal
        $this->json($faq);
    }

    /**
     * Actualizar FAQ
     */
    public function update($id)
    {
        try {
            $this->validateCsrf();

            // Obtener ID del formulario si viene como POST
            if (!$id) {
                $id = $this->getInput('id');
            }

            // Obtener datos anteriores
            $faqAnterior = $this->db->fetch("SELECT * FROM faqs WHERE id = :id", ['id' => $id]);

            if (!$faqAnterior) {
                $this->json(['success' => false, 'message' => 'FAQ no encontrada'], 404);
                return;
            }

            $data = [
                'categoria' => $this->sanitizeInput($this->getInput('categoria')),
                'pregunta' => $this->sanitizeInput($this->getInput('pregunta')),
                'respuesta' => $this->getInput('respuesta'),
                'orden' => (int)$this->getInput('orden', 0),
                'activo' => (int)$this->getInput('activo', 1),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Validar datos
            $errors = $this->validateFAQ($data);
            if (!empty($errors)) {
                $this->json(['success' => false, 'message' => implode(', ', $errors)]);
                return;
            }

            // Actualizar FAQ
            $this->db->update('faqs', $data, 'id = :id', ['id' => $id]);

            // Registrar en audit log (opcional)
            try {
                AuditLogger::log(
                    'editar',
                    'faqs',
                    $id,
                    $data['pregunta'],
                    $faqAnterior,
                    $data
                );
            } catch (Exception $e) {
                // Audit log no disponible, continuar
            }

            $this->json(['success' => true, 'message' => 'FAQ actualizada exitosamente']);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al actualizar FAQ: ' . $e->getMessage()]);
        }
    }

    /**
     * Eliminar FAQ
     */
    public function delete($id)
    {
        $this->validateCsrf();

        $faq = $this->db->fetch("SELECT * FROM faqs WHERE id = :id", ['id' => $id]);

        if (!$faq) {
            $this->json(['success' => false, 'message' => 'FAQ no encontrada'], 404);
            return;
        }

        try {
            $this->db->delete('faqs', 'id = :id', ['id' => $id]);

            // Registrar en audit log (opcional)
            try {
                AuditLogger::log(
                    'eliminar',
                    'faqs',
                    $id,
                    $faq['pregunta'],
                    $faq,
                    null
                );
            } catch (Exception $e) {
                // Audit log no disponible, continuar
            }

            if (Helpers::isAjax()) {
                $this->json(['success' => true, 'message' => 'FAQ eliminada exitosamente']);
            } else {
                $this->redirect('admin/faqs', 'FAQ eliminada exitosamente', 'success');
            }

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error al eliminar FAQ'], 500);
            } else {
                $this->redirect('admin/faq/list', 'Error al eliminar FAQ', 'error');
            }
        }
    }

    /**
     * Actualizar orden (drag & drop) - AJAX
     */
    public function updateOrder()
    {
        $this->validateCsrf();

        $order = $this->getInput('order', []); // Array de [id => orden]

        if (empty($order) || !is_array($order)) {
            $this->json(['success' => false, 'message' => 'Datos inválidos'], 400);
            return;
        }

        try {
            $this->db->beginTransaction();

            foreach ($order as $id => $position) {
                $this->db->update(
                    'faqs',
                    ['orden' => (int)$position, 'updated_at' => date('Y-m-d H:i:s')],
                    'id = :id',
                    ['id' => (int)$id]
                );
            }

            $this->db->commit();

            // Registrar en audit log (opcional)
            try {
                AuditLogger::log('editar', 'faqs', null, 'Reordenar FAQs', null, ['order' => $order]);
            } catch (Exception $e) {
                // Audit log no disponible, continuar
            }

            $this->json(['success' => true, 'message' => 'Orden actualizado exitosamente']);

        } catch (Exception $e) {
            $this->db->rollBack();
            $this->json(['success' => false, 'message' => 'Error al actualizar orden'], 500);
        }
    }

    /**
     * Activar/desactivar FAQ - AJAX
     */
    public function toggleActive($id)
    {
        $this->validateCsrf();

        $faq = $this->db->fetch("SELECT * FROM faqs WHERE id = :id", ['id' => $id]);

        if (!$faq) {
            $this->json(['success' => false, 'message' => 'FAQ no encontrada'], 404);
            return;
        }

        try {
            $nuevoEstado = $faq['activo'] ? 0 : 1;

            $this->db->update(
                'faqs',
                ['activo' => $nuevoEstado, 'updated_at' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $id]
            );

            // Registrar en audit log (opcional)
            try {
                AuditLogger::log(
                    'editar',
                    'faqs',
                    $id,
                    $faq['pregunta'],
                    ['activo' => $faq['activo']],
                    ['activo' => $nuevoEstado]
                );
            } catch (Exception $e) {
                // Audit log no disponible, continuar
            }

            $this->json([
                'success' => true,
                'message' => $nuevoEstado ? 'FAQ activada' : 'FAQ desactivada',
                'activo' => $nuevoEstado
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al cambiar estado'], 500);
        }
    }

    /**
     * API pública para obtener FAQs (frontend)
     */
    public function getPublicFAQs()
    {
        $categoria = $this->getInput('categoria');

        $where = "activo = 1";
        $params = [];

        if ($categoria) {
            $where .= " AND categoria = :categoria";
            $params['categoria'] = $categoria;
        }

        $sql = "SELECT id, categoria, pregunta, respuesta, orden
                FROM faqs
                WHERE {$where}
                ORDER BY orden ASC, created_at DESC";

        $faqs = $this->db->fetchAll($sql, $params);

        // Incrementar visitas
        if (!empty($faqs)) {
            foreach ($faqs as $faq) {
                $this->db->execute(
                    "UPDATE faqs SET visitas = visitas + 1 WHERE id = :id",
                    ['id' => $faq['id']]
                );
            }
        }

        $this->json([
            'success' => true,
            'faqs' => $faqs,
            'total' => count($faqs)
        ]);
    }

    /**
     * Marcar FAQ como útil - AJAX (público o autenticado)
     */
    public function markAsUseful($id)
    {
        $util = $this->getInput('util'); // 'si' | 'no'

        if (!in_array($util, ['si', 'no'])) {
            $this->json(['success' => false, 'message' => 'Valor inválido'], 400);
            return;
        }

        $faq = $this->db->fetch("SELECT * FROM faqs WHERE id = :id", ['id' => $id]);

        if (!$faq) {
            $this->json(['success' => false, 'message' => 'FAQ no encontrada'], 404);
            return;
        }

        try {
            $campo = $util === 'si' ? 'util_si' : 'util_no';

            $this->db->execute(
                "UPDATE faqs SET {$campo} = {$campo} + 1 WHERE id = :id",
                ['id' => $id]
            );

            $this->json([
                'success' => true,
                'message' => 'Gracias por tu feedback'
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al registrar feedback'], 500);
        }
    }

    /**
     * Obtener categorías únicas
     */
    public function getCategories()
    {
        $categorias = $this->db->fetchAll(
            "SELECT DISTINCT categoria FROM faqs WHERE categoria IS NOT NULL ORDER BY categoria"
        );

        return array_column($categorias, 'categoria');
    }

    /**
     * Validar datos de FAQ
     */
    private function validateFAQ($data)
    {
        $errors = [];

        if (empty($data['categoria'])) {
            $errors[] = 'La categoría es requerida';
        }

        if (empty($data['pregunta'])) {
            $errors[] = 'La pregunta es requerida';
        } elseif (strlen($data['pregunta']) < 10) {
            $errors[] = 'La pregunta debe tener al menos 10 caracteres';
        }

        if (empty($data['respuesta'])) {
            $errors[] = 'La respuesta es requerida';
        } elseif (strlen($data['respuesta']) < 20) {
            $errors[] = 'La respuesta debe tener al menos 20 caracteres';
        }

        if (!is_numeric($data['orden']) || $data['orden'] < 0) {
            $errors[] = 'El orden debe ser un número positivo';
        }

        return $errors;
    }
}
