<?php
namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Helpers\AuditLogger;
use App\Helpers\NotificationHelper;
use Exception;

/**
 * ContentBlockController
 *
 * Gestiona bloques de contenido editables para el CMS del Admin Premium
 * Permite editar textos, imágenes y contenido de distintas secciones del sitio
 */
class ContentBlockController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
    }

    /**
     * Listar todos los bloques agrupados por sección
     */
    public function list()
    {
        try {
            $search = $this->getInput('search');
            $section = $this->getInput('section');

            $sql = "SELECT * FROM content_blocks WHERE 1=1";
            $params = [];

            if ($search) {
                $sql .= " AND (titulo LIKE :search OR contenido LIKE :search OR seccion LIKE :search)";
                $params['search'] = '%' . $search . '%';
            }

            if ($section) {
                $sql .= " AND seccion = :section";
                $params['section'] = $section;
            }

            $sql .= " ORDER BY seccion ASC, orden ASC, id ASC";

            $blocks = $this->db->fetchAll($sql, $params);

            // Agrupar por sección
            $groupedBlocks = [];
            foreach ($blocks as $block) {
                $sec = $block['seccion'] ?? 'general';
                if (!isset($groupedBlocks[$sec])) {
                    $groupedBlocks[$sec] = [];
                }
                $groupedBlocks[$sec][] = $block;
            }

            // Obtener lista de secciones únicas
            $sections = $this->db->fetchAll("SELECT DISTINCT seccion FROM content_blocks ORDER BY seccion ASC");

            $this->view('admin/content_blocks/list', [
                'title' => 'Bloques de Contenido',
                'blocks' => $blocks,
                'groupedBlocks' => $groupedBlocks,
                'sections' => $sections,
                'currentSection' => $section,
                'search' => $search
            ]);

        } catch (Exception $e) {
            $this->handleValidationErrors('Error al cargar bloques: ' . $e->getMessage(), 'admin');
        }
    }

    /**
     * Editar un bloque específico
     */
    public function edit($id)
    {
        try {
            $block = $this->db->fetch("SELECT * FROM content_blocks WHERE id = :id", ['id' => $id]);

            if (!$block) {
                if (Helpers::isAjax()) {
                    $this->json(['success' => false, 'message' => 'Bloque no encontrado'], 404);
                } else {
                    $this->redirect('admin/content-blocks', 'Bloque no encontrado', 'error');
                }
                return;
            }

            // Si es petición AJAX, devolver JSON
            if (Helpers::isAjax()) {
                $this->json($block);
                return;
            }

            // Obtener secciones disponibles
            $sections = $this->db->fetchAll("SELECT DISTINCT seccion FROM content_blocks ORDER BY seccion ASC");

            $this->view('admin/content_blocks/edit', [
                'title' => 'Editar Bloque: ' . $block['titulo'],
                'block' => $block,
                'sections' => $sections
            ]);

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            } else {
                $this->redirect('admin/content-blocks', 'Error al cargar bloque: ' . $e->getMessage(), 'error');
            }
        }
    }

    /**
     * Actualizar bloque (AJAX)
     */
    public function update()
    {
        $this->validateCsrf();

        if (!Helpers::isPost()) {
            $this->json(['success' => false, 'message' => 'Método no permitido'], 405);
        }

        try {
            $id = $this->getInput('id');
            $titulo = $this->sanitizeInput($this->getInput('titulo'));
            $contenido = $this->getInput('contenido'); // No sanitizar HTML del editor
            $seccion = $this->sanitizeInput($this->getInput('seccion'));
            $tipo = $this->sanitizeInput($this->getInput('tipo', 'texto'));
            $imagen = $this->sanitizeInput($this->getInput('imagen'));
            $activo = (int)$this->getInput('activo', 1);
            $orden = (int)$this->getInput('orden', 0);
            $configuracion = $this->getInput('configuracion');

            // Validaciones
            $errors = [];
            if (empty($titulo)) {
                $errors[] = 'El título es requerido';
            }
            if (empty($seccion)) {
                $errors[] = 'La sección es requerida';
            }

            if (!empty($errors)) {
                $this->json(['success' => false, 'errors' => $errors], 400);
            }

            // Obtener datos anteriores para auditoría
            $datosAnteriores = $this->db->fetch("SELECT * FROM content_blocks WHERE id = :id", ['id' => $id]);

            // Preparar datos
            $data = [
                'titulo' => $titulo,
                'contenido' => $contenido,
                'seccion' => $seccion,
                'tipo' => $tipo,
                'imagen' => $imagen,
                'activo' => $activo,
                'orden' => $orden
            ];

            // Si hay configuración JSON
            if ($configuracion) {
                if (is_array($configuracion)) {
                    $data['configuracion'] = json_encode($configuracion, JSON_UNESCAPED_UNICODE);
                } else {
                    $data['configuracion'] = $configuracion;
                }
            }

            // Actualizar
            $updated = $this->db->update('content_blocks', $data, 'id = :id', ['id' => $id]);

            if ($updated) {
                // Registrar en audit log
                AuditLogger::log(
                    'editar',
                    'content_blocks',
                    $id,
                    $titulo,
                    $datosAnteriores,
                    $data
                );

                $this->json([
                    'success' => true,
                    'message' => 'Bloque actualizado correctamente',
                    'block' => array_merge(['id' => $id], $data)
                ]);
            } else {
                $this->json(['success' => false, 'message' => 'No se realizaron cambios'], 400);
            }

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Crear nuevo bloque
     */
    public function create()
    {
        if (Helpers::isPost()) {
            $this->validateCsrf();

            try {
                $titulo = $this->sanitizeInput($this->getInput('titulo'));
                $contenido = $this->getInput('contenido');
                $seccion = $this->sanitizeInput($this->getInput('seccion'));
                $tipo = $this->sanitizeInput($this->getInput('tipo', 'texto'));
                $imagen = $this->sanitizeInput($this->getInput('imagen'));
                $activo = (int)$this->getInput('activo', 1);
                $orden = (int)$this->getInput('orden', 0);
                $configuracion = $this->getInput('configuracion');

                // Validaciones
                $errors = [];
                if (empty($titulo)) {
                    $errors[] = 'El título es requerido';
                }
                if (empty($seccion)) {
                    $errors[] = 'La sección es requerida';
                }

                if (!empty($errors)) {
                    $this->handleValidationErrors($errors);
                    return;
                }

                // Preparar datos
                $data = [
                    'titulo' => $titulo,
                    'contenido' => $contenido,
                    'seccion' => $seccion,
                    'tipo' => $tipo,
                    'imagen' => $imagen,
                    'activo' => $activo,
                    'orden' => $orden
                ];

                if ($configuracion) {
                    if (is_array($configuracion)) {
                        $data['configuracion'] = json_encode($configuracion, JSON_UNESCAPED_UNICODE);
                    } else {
                        $data['configuracion'] = $configuracion;
                    }
                }

                // Insertar
                $newId = $this->db->insert('content_blocks', $data);

                // Registrar en audit log
                AuditLogger::log(
                    'crear',
                    'content_blocks',
                    $newId,
                    $titulo,
                    null,
                    $data
                );

                $this->redirect('admin/content-blocks/edit/' . $newId, 'Bloque creado correctamente', 'success');

            } catch (Exception $e) {
                $this->handleValidationErrors('Error al crear bloque: ' . $e->getMessage());
            }
        }

        // Mostrar formulario
        $sections = $this->db->fetchAll("SELECT DISTINCT seccion FROM content_blocks ORDER BY seccion ASC");

        $this->view('admin/content_blocks/create', [
            'title' => 'Crear Bloque de Contenido',
            'sections' => $sections
        ]);
    }

    /**
     * Eliminar bloque
     */
    public function delete($id)
    {
        $this->validateCsrf();

        try {
            $block = $this->db->fetch("SELECT * FROM content_blocks WHERE id = :id", ['id' => $id]);

            if (!$block) {
                if (Helpers::isAjax()) {
                    $this->json(['success' => false, 'message' => 'Bloque no encontrado'], 404);
                } else {
                    $this->redirect('admin/content-blocks', 'Bloque no encontrado', 'error');
                }
                return;
            }

            // Eliminar
            $deleted = $this->db->delete('content_blocks', 'id = :id', ['id' => $id]);

            if ($deleted) {
                // Registrar en audit log
                AuditLogger::log(
                    'eliminar',
                    'content_blocks',
                    $id,
                    $block['titulo'],
                    $block,
                    null
                );

                if (Helpers::isAjax()) {
                    $this->json(['success' => true, 'message' => 'Bloque eliminado correctamente']);
                } else {
                    $this->redirect('admin/content-blocks', 'Bloque eliminado correctamente', 'success');
                }
            } else {
                if (Helpers::isAjax()) {
                    $this->json(['success' => false, 'message' => 'Error al eliminar bloque'], 500);
                } else {
                    $this->redirect('admin/content-blocks', 'Error al eliminar bloque', 'error');
                }
            }

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
            } else {
                $this->redirect('admin/content-blocks', 'Error al eliminar: ' . $e->getMessage(), 'error');
            }
        }
    }

    /**
     * API: Obtener bloques de una sección específica
     */
    public function getBySection($section)
    {
        try {
            $blocks = $this->db->fetchAll(
                "SELECT * FROM content_blocks WHERE seccion = :section AND activo = 1 ORDER BY orden ASC",
                ['section' => $section]
            );

            $this->json([
                'success' => true,
                'section' => $section,
                'blocks' => $blocks,
                'count' => count($blocks)
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error al obtener bloques: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Toggle activo
     */
    public function toggleActive()
    {
        try {
            // Leer JSON del body
            $jsonData = json_decode(file_get_contents('php://input'), true);

            // Validar CSRF
            $csrfToken = $jsonData['csrf_token'] ?? '';
            if (!Helpers::validateCsrfToken($csrfToken)) {
                $this->json(['success' => false, 'message' => 'Token CSRF inválido'], 403);
                return;
            }

            $id = $jsonData['id'] ?? null;

            if (!$id) {
                $this->json(['success' => false, 'message' => 'ID no proporcionado'], 400);
                return;
            }

            $block = $this->db->fetch("SELECT activo, titulo FROM content_blocks WHERE id = :id", ['id' => $id]);

            if (!$block) {
                $this->json(['success' => false, 'message' => 'Bloque no encontrado'], 404);
                return;
            }

            $newStatus = $block['activo'] ? 0 : 1;

            $this->db->update('content_blocks', ['activo' => $newStatus], 'id = :id', ['id' => $id]);

            // Registrar en audit log
            AuditLogger::log(
                'editar',
                'content_blocks',
                $id,
                $block['titulo'],
                ['activo' => $block['activo']],
                ['activo' => $newStatus]
            );

            $this->json([
                'success' => true,
                'activo' => $newStatus,
                'message' => $newStatus ? 'Bloque activado' : 'Bloque desactivado'
            ]);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
}
