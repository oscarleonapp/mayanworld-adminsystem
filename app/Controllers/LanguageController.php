<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Helpers;
use App\Core\Config;
use App\Models\Language;
use Exception;

class LanguageController extends BaseController
{
    private $langModel;

    public function __construct()
    {
        parent::__construct();
        $this->requireAdmin();
        $this->langModel = new Language();
    }

    public function list()
    {
        try {
            $languages = $this->langModel->getAll();

            foreach ($languages as &$lang) {
                $lang['empleados_count'] = $this->langModel->countEmployeesByLanguage($lang['nombre']);
            }

            $this->view('admin/staff/languages', [
                'title' => 'Idiomas',
                'languages' => $languages
            ]);

        } catch (Exception $e) {
            $this->view('admin/staff/languages', [
                'title' => 'Idiomas',
                'languages' => [],
                'error' => 'Error al cargar idiomas'
            ]);
        }
    }

    public function create()
    {
        if (!Helpers::isPost()) {
            $this->redirect('admin/staff/languages');
            return;
        }

        $this->validateCsrf();

        try {
            $nombre = $this->sanitizeInput($this->getInput('nombre'));
            $codigo = $this->sanitizeInput($this->getInput('codigo'));

            $errors = [];
            if (empty($nombre)) {
                $errors[] = 'El nombre es requerido';
            }

            if (empty($codigo)) {
                $codigo = $this->langModel->generateSlug($nombre);
            } else {
                $codigo = $this->langModel->generateSlug($codigo);
            }

            if ($this->langModel->codigoExists($codigo)) {
                $errors[] = 'El codigo ya esta en uso';
            }

            if (!empty($errors)) {
                $this->redirect('admin/staff/languages', implode('. ', $errors), 'error');
                return;
            }

            $data = [
                'nombre' => $nombre,
                'codigo' => $codigo,
                'activo' => 1,
                'orden' => $this->langModel->getNextOrder()
            ];

            $this->langModel->create($data);

            if (Helpers::isAjax()) {
                $this->json(['success' => true, 'message' => 'Idioma creado correctamente']);
                return;
            }

            $this->redirect('admin/staff/languages', 'Idioma creado correctamente', 'success');

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
                return;
            }
            $this->redirect('admin/staff/languages', 'Error al crear idioma', 'error');
        }
    }

    public function edit($id)
    {
        if (Helpers::isAjax() && !Helpers::isPost()) {
            $lang = $this->langModel->find($id);
            if (!$lang) {
                $this->json(['success' => false, 'message' => 'Idioma no encontrado'], 404);
                return;
            }
            $this->json($lang);
            return;
        }

        if (!Helpers::isPost()) {
            $this->redirect('admin/staff/languages');
            return;
        }

        $this->validateCsrf();

        try {
            $lang = $this->langModel->find($id);
            if (!$lang) {
                $this->redirect('admin/staff/languages', 'Idioma no encontrado', 'error');
                return;
            }

            $nombre = $this->sanitizeInput($this->getInput('nombre'));
            $codigo = $this->sanitizeInput($this->getInput('codigo'));

            $errors = [];
            if (empty($nombre)) {
                $errors[] = 'El nombre es requerido';
            }

            if (empty($codigo)) {
                $codigo = $this->langModel->generateSlug($nombre);
            } else {
                $codigo = $this->langModel->generateSlug($codigo);
            }

            if ($this->langModel->codigoExists($codigo, $id)) {
                $errors[] = 'El codigo ya esta en uso';
            }

            if (!empty($errors)) {
                $this->redirect('admin/staff/languages', implode('. ', $errors), 'error');
                return;
            }

            $oldNombre = $lang['nombre'];

            $data = [
                'nombre' => $nombre,
                'codigo' => $codigo
            ];

            $this->langModel->update($id, $data);

            // Si cambio el nombre, actualizar empleados que usen este idioma
            if ($oldNombre !== $nombre) {
                $employees = $this->db->fetchAll(
                    "SELECT id, idiomas FROM empleados WHERE idiomas LIKE :search",
                    ['search' => '%' . $oldNombre . '%']
                );
                foreach ($employees as $emp) {
                    $updated = str_replace($oldNombre, $nombre, $emp['idiomas']);
                    $this->db->update('empleados', ['idiomas' => $updated], 'id = :id', ['id' => $emp['id']]);
                }
            }

            if (Helpers::isAjax()) {
                $this->json(['success' => true, 'message' => 'Idioma actualizado correctamente']);
                return;
            }

            $this->redirect('admin/staff/languages', 'Idioma actualizado correctamente', 'success');

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
                return;
            }
            $this->redirect('admin/staff/languages', 'Error al actualizar idioma', 'error');
        }
    }

    public function delete($id)
    {
        if (!Helpers::isPost()) {
            $this->redirect('admin/staff/languages');
            return;
        }

        $this->validateCsrf();

        try {
            $lang = $this->langModel->find($id);
            if (!$lang) {
                $this->redirect('admin/staff/languages', 'Idioma no encontrado', 'error');
                return;
            }

            $count = $this->langModel->countEmployeesByLanguage($lang['nombre']);
            if ($count > 0) {
                $msg = "No se puede eliminar: hay {$count} empleado(s) con este idioma asignado";
                if (Helpers::isAjax()) {
                    $this->json(['success' => false, 'message' => $msg], 400);
                    return;
                }
                $this->redirect('admin/staff/languages', $msg, 'error');
                return;
            }

            $this->langModel->delete($id);

            if (Helpers::isAjax()) {
                $this->json(['success' => true, 'message' => 'Idioma eliminado correctamente']);
                return;
            }

            $this->redirect('admin/staff/languages', 'Idioma eliminado correctamente', 'success');

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
                return;
            }
            $this->redirect('admin/staff/languages', 'Error al eliminar idioma', 'error');
        }
    }

    public function toggleActive()
    {
        if (!Helpers::isPost()) {
            $this->redirect('admin/staff/languages');
            return;
        }

        try {
            $id = $this->getInput('id');
            $lang = $this->langModel->find($id);

            if (!$lang) {
                if (Helpers::isAjax()) {
                    $this->json(['success' => false, 'message' => 'Idioma no encontrado'], 404);
                    return;
                }
                $this->redirect('admin/staff/languages', 'Idioma no encontrado', 'error');
                return;
            }

            $this->langModel->toggleActive($id);

            if (Helpers::isAjax()) {
                $this->json([
                    'success' => true,
                    'message' => 'Estado actualizado correctamente',
                    'new_status' => !$lang['activo']
                ]);
                return;
            }

            $this->redirect('admin/staff/languages', 'Estado actualizado correctamente', 'success');

        } catch (Exception $e) {
            if (Helpers::isAjax()) {
                $this->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
                return;
            }
            $this->redirect('admin/staff/languages', 'Error al actualizar estado', 'error');
        }
    }
}
