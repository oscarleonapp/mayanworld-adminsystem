<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Core\Config;
use App\Models\EmployeeType;
use Exception;

class EmployeeTypeController extends BaseController
{
    private $typeModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->typeModel = new EmployeeType();
    }

    /**
     * Listar tipos de empleado
     */
    public function list()
    {
        try {
            $types = $this->typeModel->getAll();

            // Contar empleados por tipo
            foreach ($types as &$type) {
                $type['empleados_count'] = $this->typeModel->countEmployeesByType($type['slug']);
            }

            $this->view('admin/staff/types', [
                'title' => 'Tipos de Empleados',
                'types' => $types
            ]);

        } catch (Exception $e) {
            $this->view('admin/staff/types', [
                'title' => 'Tipos de Empleados',
                'types' => [],
                'error' => 'Error al cargar tipos de empleado'
            ]);
        }
    }

    /**
     * Crear tipo de empleado
     */
    public function create()
    {
        if (!Helpers::isPost()) {
            $this->redirect('admin/staff/types');
            return;
        }

        $this->validateCsrf();

        try {
            $nombre = $this->sanitizeInput($this->getInput('nombre'));
            $slug = $this->sanitizeInput($this->getInput('slug'));
            $descripcion = $this->sanitizeInput($this->getInput('descripcion'));

            $errors = [];
            if (empty($nombre)) {
                $errors[] = 'El nombre es requerido';
            }

            // Auto-generar slug si está vacío
            if (empty($slug)) {
                $slug = $this->typeModel->generateSlug($nombre);
            } else {
                $slug = $this->typeModel->generateSlug($slug);
            }

            if ($this->typeModel->slugExists($slug)) {
                $errors[] = 'El identificador (slug) ya está en uso';
            }

            if (!empty($errors)) {
                $this->handleValidationErrors($errors);
                $this->redirect('admin/staff/types', implode('. ', $errors), 'error');
                return;
            }

            $data = [
                'nombre' => $nombre,
                'slug' => $slug,
                'descripcion' => $descripcion,
                'activo' => 1,
                'orden' => $this->typeModel->getNextOrder()
            ];

            $this->typeModel->create($data);

            if (Helpers::isAjax()) {
                $this->json(['success' => true, 'message' => 'Tipo de empleado creado correctamente']);
                return;
            }

            $this->redirect('admin/staff/types', 'Tipo de empleado creado correctamente', 'success');

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error al crear tipo: ' . $e->getMessage()], 500);
                return;
            }
            $this->redirect('admin/staff/types', 'Error al crear tipo de empleado', 'error');
        }
    }

    /**
     * Editar tipo de empleado
     */
    public function edit($id)
    {
        if (Helpers::isAjax() && !Helpers::isPost()) {
            // Devolver datos del tipo para el modal de edición
            $type = $this->typeModel->find($id);
            if (!$type) {
                $this->json(['success' => false, 'message' => 'Tipo no encontrado'], 404);
                return;
            }
            $this->json($type);
            return;
        }

        if (!Helpers::isPost()) {
            $this->redirect('admin/staff/types');
            return;
        }

        $this->validateCsrf();

        try {
            $type = $this->typeModel->find($id);
            if (!$type) {
                $this->redirect('admin/staff/types', 'Tipo no encontrado', 'error');
                return;
            }

            $nombre = $this->sanitizeInput($this->getInput('nombre'));
            $slug = $this->sanitizeInput($this->getInput('slug'));
            $descripcion = $this->sanitizeInput($this->getInput('descripcion'));

            $errors = [];
            if (empty($nombre)) {
                $errors[] = 'El nombre es requerido';
            }

            if (empty($slug)) {
                $slug = $this->typeModel->generateSlug($nombre);
            } else {
                $slug = $this->typeModel->generateSlug($slug);
            }

            if ($this->typeModel->slugExists($slug, $id)) {
                $errors[] = 'El identificador (slug) ya está en uso';
            }

            if (!empty($errors)) {
                $this->redirect('admin/staff/types', implode('. ', $errors), 'error');
                return;
            }

            $oldSlug = $type['slug'];

            $data = [
                'nombre' => $nombre,
                'slug' => $slug,
                'descripcion' => $descripcion
            ];

            $this->typeModel->update($id, $data);

            // Si cambió el slug, actualizar empleados que usen este tipo
            if ($oldSlug !== $slug) {
                $this->db->query(
                    "UPDATE empleados SET tipo_empleado = :new_slug WHERE tipo_empleado = :old_slug",
                    ['new_slug' => $slug, 'old_slug' => $oldSlug]
                );
            }

            if (Helpers::isAjax()) {
                $this->json(['success' => true, 'message' => 'Tipo actualizado correctamente']);
                return;
            }

            $this->redirect('admin/staff/types', 'Tipo de empleado actualizado correctamente', 'success');

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
                return;
            }
            $this->redirect('admin/staff/types', 'Error al actualizar tipo de empleado', 'error');
        }
    }

    /**
     * Eliminar tipo de empleado
     */
    public function delete($id)
    {
        if (!Helpers::isPost()) {
            $this->redirect('admin/staff/types');
            return;
        }

        $this->validateCsrf();

        try {
            $type = $this->typeModel->find($id);
            if (!$type) {
                $this->redirect('admin/staff/types', 'Tipo no encontrado', 'error');
                return;
            }

            // Verificar que no haya empleados usando este tipo
            $count = $this->typeModel->countEmployeesByType($type['slug']);
            if ($count > 0) {
                $msg = "No se puede eliminar: hay {$count} empleado(s) con este tipo asignado";
                if (Helpers::isAjax()) {
                    $this->json(['success' => false, 'message' => $msg], 400);
                    return;
                }
                $this->redirect('admin/staff/types', $msg, 'error');
                return;
            }

            $this->typeModel->delete($id);

            if (Helpers::isAjax()) {
                $this->json(['success' => true, 'message' => 'Tipo eliminado correctamente']);
                return;
            }

            $this->redirect('admin/staff/types', 'Tipo de empleado eliminado correctamente', 'success');

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
                return;
            }
            $this->redirect('admin/staff/types', 'Error al eliminar tipo de empleado', 'error');
        }
    }

    /**
     * Toggle estado activo/inactivo
     */
    public function toggleActive()
    {
        if (!Helpers::isPost()) {
            $this->redirect('admin/staff/types');
            return;
        }

        try {
            $id = $this->getInput('id');
            $type = $this->typeModel->find($id);

            if (!$type) {
                if (Helpers::isAjax()) {
                    $this->json(['success' => false, 'message' => 'Tipo no encontrado'], 404);
                    return;
                }
                $this->redirect('admin/staff/types', 'Tipo no encontrado', 'error');
                return;
            }

            $this->typeModel->toggleActive($id);

            if (Helpers::isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Estado actualizado correctamente',
                    'new_status' => !$type['activo']
                ]);
                return;
            }

            $this->redirect('admin/staff/types', 'Estado actualizado correctamente', 'success');

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
                return;
            }
            $this->redirect('admin/staff/types', 'Error al actualizar estado', 'error');
        }
    }
}
