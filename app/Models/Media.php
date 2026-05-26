<?php
namespace App\Models;

use App\Core\Model;
use App\Core\Config;
use App\Helpers\ImageOptimizer;
use Exception;

/**
 * Modelo Media
 *
 * Gestiona la biblioteca de medios (imágenes y archivos)
 * Maneja upload, optimización, variantes y tracking de uso
 *
 * @version 1.0.0
 * @date 2025-11-05
 */
class Media extends Model
{
    protected $table = 'media_library';
    protected $fillable = [
        'filename',
        'original_filename',
        'path',
        'url',
        'mime_type',
        'file_size',
        'width',
        'height',
        'alt_text',
        'title',
        'description',
        'folder',
        'uploaded_by'
    ];

    /**
     * Upload y procesar archivo
     *
     * @param array $file Array $_FILES
     * @param array $options Opciones de upload
     * @return array|false Array con datos del archivo o false
     */
    public function upload($file, $options = [])
    {
        $defaults = [
            'folder' => 'general',
            'uploaded_by' => null,
            'optimize' => true,
            'generate_variants' => true,
            'alt_text' => '',
            'title' => '',
            'description' => ''
        ];

        $options = array_merge($defaults, $options);

        // Validar archivo
        $validation = $this->validateUpload($file);
        if ($validation !== true) {
            return ['error' => $validation];
        }

        try {
            // Preparar directorios
            $uploadDir = $this->prepareUploadDirectory($options['folder']);

            // Generar nombre único
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $uniqueName = uniqid() . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . '/' . $uniqueName;

            // Mover archivo
            if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
                return ['error' => 'Error al subir el archivo'];
            }

            // Obtener información del archivo
            $fileInfo = $this->getFileInfo($uploadPath);

            // Optimizar si es imagen
            if ($options['optimize'] && $this->isImage($fileInfo['mime_type'])) {
                ImageOptimizer::optimize($uploadPath);
                $fileInfo = $this->getFileInfo($uploadPath); // Actualizar info
            }

            // Insertar en BD
            $mediaData = [
                'filename' => $uniqueName,
                'original_filename' => $file['name'],
                'path' => $uploadPath,
                'url' => $this->getPublicUrl($uploadPath),
                'mime_type' => $fileInfo['mime_type'],
                'file_size' => $fileInfo['size'],
                'width' => $fileInfo['width'],
                'height' => $fileInfo['height'],
                'alt_text' => $options['alt_text'],
                'title' => $options['title'] ?: $file['name'],
                'description' => $options['description'],
                'folder' => $options['folder'],
                'uploaded_by' => $options['uploaded_by']
            ];

            $mediaId = $this->db->insert($this->table, $mediaData);

            if (!$mediaId) {
                @unlink($uploadPath);
                return ['error' => 'Error al guardar en base de datos'];
            }

            // Generar variantes si es imagen
            if ($options['generate_variants'] && $this->isImage($fileInfo['mime_type'])) {
                $basename = pathinfo($uniqueName, PATHINFO_FILENAME);
                $variants = ImageOptimizer::generateVariants($uploadPath, $uploadDir, $basename);

                foreach ($variants as $variantType => $variantData) {
                    if ($variantType === 'webp') {
                        $this->saveVariant($mediaId, 'webp', $variantData);
                    } else {
                        $this->saveVariant($mediaId, $variantType, $variantData);
                    }
                }
            }

            $mediaData['id'] = $mediaId;
            return $mediaData;

        } catch (Exception $e) {
            if (isset($uploadPath) && file_exists($uploadPath)) {
                @unlink($uploadPath);
            }
            return ['error' => 'Error en el proceso: ' . $e->getMessage()];
        }
    }

    /**
     * Validar archivo antes de subir
     *
     * @param array $file Array $_FILES
     * @return true|string true si es válido, mensaje de error si no
     */
    private function validateUpload($file)
    {
        // Verificar errores de PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Error al subir el archivo (código: ' . $file['error'] . ')';
        }

        // Verificar tamaño
        $maxSize = $this->getConfig('max_upload_size', 10485760); // 10MB default
        if ($file['size'] > $maxSize) {
            $maxSizeMB = round($maxSize / 1048576, 2);
            return "El archivo excede el tamaño máximo permitido ({$maxSizeMB}MB)";
        }

        // Verificar tipo
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedImages = explode(',', $this->getConfig('allowed_image_types', 'jpg,jpeg,png,gif,webp'));
        $allowedFiles = explode(',', $this->getConfig('allowed_file_types', 'pdf,doc,docx'));
        $allowed = array_merge($allowedImages, $allowedFiles);

        if (!in_array($extension, $allowed)) {
            return "Tipo de archivo no permitido: {$extension}";
        }

        return true;
    }

    /**
     * Preparar directorio de upload
     *
     * @param string $folder Carpeta lógica
     * @return string Ruta del directorio
     */
    private function prepareUploadDirectory($folder)
    {
        $basePath = '../public/uploads/media';

        // Organizar por fecha si está habilitado
        $organizeByDate = $this->getConfig('organize_by_date', 'yes');
        if ($organizeByDate === 'yes') {
            $year = date('Y');
            $month = date('m');
            $uploadDir = "{$basePath}/{$folder}/{$year}/{$month}";
        } else {
            $uploadDir = "{$basePath}/{$folder}";
        }

        // Crear directorios si no existen
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        return $uploadDir;
    }

    /**
     * Obtener información de un archivo
     *
     * @param string $filePath Ruta del archivo
     * @return array Info del archivo
     */
    private function getFileInfo($filePath)
    {
        $info = [
            'size' => filesize($filePath),
            'mime_type' => mime_content_type($filePath),
            'width' => null,
            'height' => null
        ];

        // Si es imagen, obtener dimensiones
        if ($this->isImage($info['mime_type'])) {
            $imageInfo = ImageOptimizer::getImageInfo($filePath);
            if ($imageInfo) {
                $info['width'] = $imageInfo['width'];
                $info['height'] = $imageInfo['height'];
            }
        }

        return $info;
    }

    /**
     * Verificar si es una imagen
     *
     * @param string $mimeType Tipo MIME
     * @return bool
     */
    private function isImage($mimeType)
    {
        return strpos($mimeType, 'image/') === 0;
    }

    /**
     * Obtener URL pública del archivo
     *
     * @param string $path Ruta del archivo
     * @return string URL pública
     */
    private function getPublicUrl($path)
    {
        $baseUrl = Config::getBaseUrl();
        $relativePath = str_replace('../public/', '', $path);
        return $baseUrl . $relativePath;
    }

    /**
     * Guardar variante en BD
     *
     * @param int $mediaId ID del archivo original
     * @param string $variantType Tipo de variante
     * @param array $variantData Datos de la variante
     * @return int|false ID de la variante
     */
    private function saveVariant($mediaId, $variantType, $variantData)
    {
        $data = [
            'media_id' => $mediaId,
            'variant_type' => $variantType,
            'filename' => $variantData['filename'],
            'path' => $variantData['path'],
            'url' => $this->getPublicUrl($variantData['path']),
            'width' => $variantData['width'],
            'height' => $variantData['height'],
            'file_size' => $variantData['file_size']
        ];

        return $this->db->insert('media_variants', $data);
    }

    /**
     * Obtener configuración
     *
     * @param string $key Clave de configuración
     * @param mixed $default Valor por defecto
     * @return mixed Valor de configuración
     */
    private function getConfig($key, $default = null)
    {
        $value = $this->db->fetchColumn(
            "SELECT config_value FROM media_config WHERE config_key = :key",
            ['key' => $key]
        );

        return $value !== false ? $value : $default;
    }

    /**
     * Buscar archivos con filtros
     *
     * @param array $filters Filtros de búsqueda
     * @param int $page Página actual
     * @param int $perPage Items por página
     * @return array Resultados paginados
     */
    public function search($filters = [], $page = 1, $perPage = 24)
    {
        $where = ['1=1'];
        $params = [];

        // Filtro por carpeta
        if (!empty($filters['folder'])) {
            $where[] = 'folder = :folder';
            $params['folder'] = $filters['folder'];
        }

        // Filtro por tipo MIME
        if (!empty($filters['mime_type'])) {
            $where[] = 'mime_type LIKE :mime_type';
            $params['mime_type'] = $filters['mime_type'] . '%';
        }

        // Filtro por búsqueda de texto
        if (!empty($filters['search'])) {
            $where[] = "MATCH(filename, original_filename, alt_text, title, description) AGAINST(:search IN NATURAL LANGUAGE MODE)";
            $params['search'] = $filters['search'];
        }

        // Filtro por archivos usados/no usados
        if (isset($filters['used'])) {
            if ($filters['used'] === 'yes') {
                $where[] = 'used_count > 0';
            } elseif ($filters['used'] === 'no') {
                $where[] = 'used_count = 0';
            }
        }

        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;

        // Obtener total
        $total = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE {$whereClause}",
            $params
        );

        // Obtener resultados
        $items = $this->db->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE {$whereClause}
             ORDER BY created_at DESC
             LIMIT :offset, :limit",
            array_merge($params, ['offset' => $offset, 'limit' => $perPage])
        );

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtener variantes de un archivo
     *
     * @param int $mediaId ID del archivo
     * @return array Variantes
     */
    public function getVariants($mediaId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM media_variants WHERE media_id = :media_id",
            ['media_id' => $mediaId]
        );
    }

    /**
     * Eliminar archivo y sus variantes
     *
     * @param int $id ID del archivo
     * @return bool Éxito de la operación
     */
    public function deleteMedia($id)
    {
        $media = $this->find($id);
        if (!$media) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            // Eliminar archivo físico
            if (file_exists($media['path'])) {
                @unlink($media['path']);
            }

            // Eliminar variantes físicas
            $variants = $this->getVariants($id);
            foreach ($variants as $variant) {
                if (file_exists($variant['path'])) {
                    @unlink($variant['path']);
                }
            }

            // Eliminar registros de BD (CASCADE elimina variantes y uso)
            $this->delete($id);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Actualizar metadata de un archivo
     *
     * @param int $id ID del archivo
     * @param array $data Datos a actualizar
     * @return bool Éxito de la operación
     */
    public function updateMetadata($id, $data)
    {
        $allowed = ['alt_text', 'title', 'description', 'folder'];
        $updateData = [];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->update($id, $updateData);
    }

    /**
     * Obtener uso de un archivo
     *
     * @param int $mediaId ID del archivo
     * @return array Registros de uso
     */
    public function getUsage($mediaId)
    {
        return $this->db->fetchAll(
            "SELECT * FROM media_usage WHERE media_id = :media_id ORDER BY created_at DESC",
            ['media_id' => $mediaId]
        );
    }

    /**
     * Registrar uso de un archivo
     *
     * @param int $mediaId ID del archivo
     * @param string $entityType Tipo de entidad (product, banner, etc)
     * @param int $entityId ID de la entidad
     * @param string $fieldName Nombre del campo
     * @return int|false ID del registro o false
     */
    public function trackUsage($mediaId, $entityType, $entityId, $fieldName = null)
    {
        $data = [
            'media_id' => $mediaId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'field_name' => $fieldName
        ];

        // Usar INSERT IGNORE para evitar duplicados
        return $this->db->insert('media_usage', $data);
    }

    /**
     * Remover registro de uso
     *
     * @param int $mediaId ID del archivo
     * @param string $entityType Tipo de entidad
     * @param int $entityId ID de la entidad
     * @return bool Éxito de la operación
     */
    public function untrackUsage($mediaId, $entityType, $entityId)
    {
        return $this->db->delete(
            'media_usage',
            'media_id = :media_id AND entity_type = :entity_type AND entity_id = :entity_id',
            [
                'media_id' => $mediaId,
                'entity_type' => $entityType,
                'entity_id' => $entityId
            ]
        );
    }

    /**
     * Obtener carpetas disponibles
     *
     * @return array Lista de carpetas con conteo
     */
    public function getFolders()
    {
        return $this->db->fetchAll(
            "SELECT folder, COUNT(*) as count
             FROM {$this->table}
             GROUP BY folder
             ORDER BY folder"
        );
    }

    /**
     * Obtener estadísticas de la biblioteca
     *
     * @return array Estadísticas
     */
    public function getStats()
    {
        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'total_images' => 0,
            'total_documents' => 0,
            'used_files' => 0,
            'unused_files' => 0
        ];

        $result = $this->db->fetchOne(
            "SELECT
                COUNT(*) as total_files,
                SUM(file_size) as total_size,
                SUM(CASE WHEN mime_type LIKE 'image/%' THEN 1 ELSE 0 END) as total_images,
                SUM(CASE WHEN mime_type NOT LIKE 'image/%' THEN 1 ELSE 0 END) as total_documents,
                SUM(CASE WHEN used_count > 0 THEN 1 ELSE 0 END) as used_files,
                SUM(CASE WHEN used_count = 0 THEN 1 ELSE 0 END) as unused_files
             FROM {$this->table}"
        );

        if ($result) {
            $stats = array_merge($stats, $result);
        }

        return $stats;
    }

    /**
     * Eliminar múltiples archivos
     *
     * @param array $ids Array de IDs
     * @return array Resultado con éxitos y fallos
     */
    public function bulkDelete($ids)
    {
        $success = 0;
        $failed = 0;

        foreach ($ids as $id) {
            if ($this->deleteMedia($id)) {
                $success++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'total' => count($ids)
        ];
    }
}
