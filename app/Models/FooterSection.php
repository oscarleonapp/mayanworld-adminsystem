<?php
namespace App\Models;

use App\Core\Model;
use Exception;

/**
 * Modelo FooterSection
 *
 * Gestiona las secciones configurables del footer
 * Soporta múltiples tipos: company_info, links, contact, social, newsletter, custom
 *
 * @version 1.0.0
 * @date 2025-11-05
 */
class FooterSection extends Model
{
    protected $table = 'footer_sections';
    protected $fillable = [
        'title',
        'type',
        'content',
        'column_position',
        'orden',
        'visible'
    ];

    /**
     * Tipos válidos de sección
     */
    const TYPE_COMPANY_INFO = 'company_info';
    const TYPE_LINKS = 'links';
    const TYPE_CONTACT = 'contact';
    const TYPE_SOCIAL = 'social';
    const TYPE_NEWSLETTER = 'newsletter';
    const TYPE_CUSTOM = 'custom';

    /**
     * Obtener todas las secciones visibles agrupadas por columna
     *
     * @return array Secciones agrupadas por column_position
     */
    public function getSectionsByColumn()
    {
        $sections = $this->db->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE visible = TRUE
             ORDER BY column_position ASC, orden ASC"
        );

        $grouped = [];
        foreach ($sections as $section) {
            $column = $section['column_position'];
            if (!isset($grouped[$column])) {
                $grouped[$column] = [];
            }

            // Decodificar JSON content
            $section['content_decoded'] = !empty($section['content'])
                ? json_decode($section['content'], true)
                : [];

            $grouped[$column][] = $section;
        }

        return $grouped;
    }

    /**
     * Obtener todas las secciones de una columna específica
     *
     * @param int $columnPosition Número de columna (1-4)
     * @param bool $visibleOnly Solo secciones visibles
     * @return array Lista de secciones
     */
    public function getSectionsByColumnPosition($columnPosition, $visibleOnly = false)
    {
        $whereClause = "column_position = :column_position";
        $params = ['column_position' => $columnPosition];

        if ($visibleOnly) {
            $whereClause .= " AND visible = TRUE";
        }

        $sections = $this->db->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE {$whereClause}
             ORDER BY orden ASC",
            $params
        );

        // Decodificar JSON content
        foreach ($sections as &$section) {
            $section['content_decoded'] = !empty($section['content'])
                ? json_decode($section['content'], true)
                : [];
        }

        return $sections;
    }

    /**
     * Agregar nueva sección
     *
     * @param array $data Datos de la sección
     * @return int|false ID de la sección creada o false en error
     */
    public function addSection($data)
    {
        // Validar datos
        $validation = $this->validateSection($data);
        if ($validation !== true) {
            return false;
        }

        // Codificar content a JSON si es array
        if (isset($data['content']) && is_array($data['content'])) {
            $data['content'] = json_encode($data['content'], JSON_UNESCAPED_UNICODE);
        }

        // Obtener siguiente orden en la columna
        if (!isset($data['orden'])) {
            $maxOrden = $this->db->fetchColumn(
                "SELECT COALESCE(MAX(orden), 0) FROM {$this->table}
                 WHERE column_position = :column_position",
                ['column_position' => $data['column_position']]
            );
            $data['orden'] = $maxOrden + 1;
        }

        return $this->db->insert($this->table, $data);
    }

    /**
     * Actualizar sección existente
     *
     * @param int $id ID de la sección
     * @param array $data Datos a actualizar
     * @return bool Éxito de la operación
     */
    public function updateSection($id, $data)
    {
        // Validar datos
        $validation = $this->validateSection($data, $id);
        if ($validation !== true) {
            return false;
        }

        // Codificar content a JSON si es array
        if (isset($data['content']) && is_array($data['content'])) {
            $data['content'] = json_encode($data['content'], JSON_UNESCAPED_UNICODE);
        }

        return $this->db->update(
            $this->table,
            $data,
            'id = :id',
            ['id' => $id]
        );
    }

    /**
     * Eliminar sección
     *
     * @param int $id ID de la sección
     * @return bool Éxito de la operación
     */
    public function deleteSection($id)
    {
        return $this->db->delete($this->table, 'id = :id', ['id' => $id]);
    }

    /**
     * Reordenar secciones dentro de una columna
     *
     * @param int $columnPosition Número de columna
     * @param array $orderedIds Array de IDs en el orden deseado
     * @return bool Éxito de la operación
     */
    public function reorderSections($columnPosition, $orderedIds)
    {
        $this->db->beginTransaction();

        try {
            foreach ($orderedIds as $orden => $id) {
                $this->db->update(
                    $this->table,
                    ['orden' => $orden + 1, 'column_position' => $columnPosition],
                    'id = :id',
                    ['id' => $id]
                );
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Mover sección a otra columna
     *
     * @param int $id ID de la sección
     * @param int $newColumn Nueva columna (1-4)
     * @param int|null $newOrden Nuevo orden (null = al final)
     * @return bool Éxito de la operación
     */
    public function moveToColumn($id, $newColumn, $newOrden = null)
    {
        if ($newColumn < 1 || $newColumn > 4) {
            return false;
        }

        // Si no se especifica orden, agregar al final
        if ($newOrden === null) {
            $maxOrden = $this->db->fetchColumn(
                "SELECT COALESCE(MAX(orden), 0) FROM {$this->table}
                 WHERE column_position = :column_position",
                ['column_position' => $newColumn]
            );
            $newOrden = $maxOrden + 1;
        }

        return $this->db->update(
            $this->table,
            ['column_position' => $newColumn, 'orden' => $newOrden],
            'id = :id',
            ['id' => $id]
        );
    }

    /**
     * Alternar visibilidad de una sección
     *
     * @param int $id ID de la sección
     * @return bool Éxito de la operación
     */
    public function toggleVisibility($id)
    {
        $section = $this->find($id);
        if (!$section) {
            return false;
        }

        $newVisible = !$section['visible'];
        return $this->db->update(
            $this->table,
            ['visible' => $newVisible],
            'id = :id',
            ['id' => $id]
        );
    }

    /**
     * Duplicar una sección
     *
     * @param int $id ID de la sección a duplicar
     * @return int|false ID de la nueva sección o false en error
     */
    public function duplicateSection($id)
    {
        $section = $this->find($id);
        if (!$section) {
            return false;
        }

        unset($section['id']);
        $section['title'] = $section['title'] . ' (Copia)';
        $section['visible'] = false; // Ocultar copia por defecto

        return $this->addSection($section);
    }

    /**
     * Validar datos de sección
     *
     * @param array $data Datos a validar
     * @param int|null $id ID para actualización (null para creación)
     * @return true|string true si es válido, mensaje de error si no
     */
    public function validateSection($data, $id = null)
    {
        // Validar título
        if (isset($data['title'])) {
            if (empty($data['title']) || strlen($data['title']) > 100) {
                return 'El título es requerido y debe tener máximo 100 caracteres';
            }
        } elseif ($id === null) {
            return 'El título es requerido';
        }

        // Validar tipo
        if (isset($data['type'])) {
            $validTypes = [
                self::TYPE_COMPANY_INFO,
                self::TYPE_LINKS,
                self::TYPE_CONTACT,
                self::TYPE_SOCIAL,
                self::TYPE_NEWSLETTER,
                self::TYPE_CUSTOM
            ];

            if (!in_array($data['type'], $validTypes)) {
                return 'Tipo de sección inválido';
            }
        } elseif ($id === null) {
            return 'El tipo es requerido';
        }

        // Validar columna
        if (isset($data['column_position'])) {
            if ($data['column_position'] < 1 || $data['column_position'] > 4) {
                return 'La columna debe estar entre 1 y 4';
            }
        }

        return true;
    }

    /**
     * Obtener configuración global del footer
     *
     * @param string|null $key Clave específica o null para todas
     * @return array|string Array de configuraciones o valor específico
     */
    public function getFooterConfig($key = null)
    {
        if ($key !== null) {
            $result = $this->db->fetchColumn(
                "SELECT config_value FROM footer_config WHERE config_key = :key",
                ['key' => $key]
            );
            return $result !== false ? $result : null;
        }

        $configs = $this->db->fetchAll("SELECT config_key, config_value FROM footer_config");
        $result = [];
        foreach ($configs as $config) {
            $result[$config['config_key']] = $config['config_value'];
        }
        return $result;
    }

    /**
     * Actualizar configuración global del footer
     *
     * @param string $key Clave de configuración
     * @param string $value Valor
     * @return bool Éxito de la operación
     */
    public function updateFooterConfig($key, $value)
    {
        return $this->db->update(
            'footer_config',
            ['config_value' => $value],
            'config_key = :key',
            ['key' => $key]
        );
    }

    /**
     * Verificar si el footer dinámico está habilitado
     *
     * @return bool
     */
    public function isEnabled()
    {
        $enabled = $this->getFooterConfig('enabled');
        return $enabled === 'yes';
    }
}
