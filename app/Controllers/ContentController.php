<?php

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Auth;
use App\Core\Helpers;
use App\Models\Content;
use Exception;

class ContentController extends BaseController
{
    private $contentModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->contentModel = new Content();
        
        // Verificar que sea empleado para acceder
        if (!$this->auth->isEmployee()) {
            $this->redirect('home');
        }
    }
    
    // Panel principal del CMS
    public function index()
    {
        $this->auth->requirePermission('contenido.leer');
        
        $sections = $this->contentModel->getAllSections();
        $recentChanges = $this->contentModel->getRecentChanges(10);
        $stats = $this->contentModel->getContentStats();
        
        $this->view('admin/content/index', [
            'sections' => $sections,
            'recentChanges' => $recentChanges,
            'stats' => $stats
        ]);
    }
    
    // Editar sección específica
    public function edit_section($sectionName)
    {
        $this->auth->requirePermission('contenido.actualizar');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSectionUpdate($sectionName);
            return;
        }
        
        $sectionContent = $this->contentModel->getSectionContent($sectionName);
        $sectionConfig = $this->getSectionConfig($sectionName);
        
        $this->view('admin/content/edit_section', [
            'sectionName' => $sectionName,
            'content' => $sectionContent,
            'config' => $sectionConfig
        ]);
    }
    
    private function handleSectionUpdate($sectionName)
    {
        $this->validateCSRF();
        
        $user = $this->auth->getCurrentUser();
        $updates = [];
        
        // Procesar cada elemento de la sección
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'content_') === 0) {
                $contentId = str_replace('content_', '', $key);
                
                if (is_numeric($contentId)) {
                    // Actualizar contenido existente
                    $this->contentModel->updateContent($contentId, [
                        'contenido' => $value,
                        'modificado_por' => $user['id']
                    ]);
                } else {
                    // Nuevo contenido
                    $field = str_replace('content_', '', $key);
                    $updates[] = [
                        'seccion' => $sectionName,
                        'tipo' => $this->getFieldType($field),
                        'titulo' => ucwords(str_replace('_', ' ', $field)),
                        'contenido' => $value,
                        'creado_por' => $user['id'],
                        'modificado_por' => $user['id'],
                        'activo' => 1,
                        'publicado' => isset($_POST['publish']) ? 1 : 0
                    ];
                }
            }
        }
        
        // Procesar uploads de imágenes
        if (!empty($_FILES)) {
            $this->handleImageUploads($sectionName, $user['id']);
        }
        
        // Insertar nuevo contenido
        foreach ($updates as $data) {
            $this->contentModel->create($data);
        }
        
        // Log de actividad
        $this->logActivity('contenido_actualizado', 'contenido', 
            "Sección '$sectionName' actualizada");
        
        $this->setFlashMessage('success', 'Contenido actualizado exitosamente');
        
        if (isset($_POST['publish'])) {
            $this->publishSection($sectionName);
            $this->setFlashMessage('success', 'Contenido publicado exitosamente');
        }
        
        $this->redirect("admin/content/edit-section/$sectionName");
    }
    
    // Editor WYSIWYG para contenido HTML
    public function editor($id = null)
    {
        $this->auth->requirePermission('contenido.actualizar');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleEditorSave($id);
            return;
        }
        
        $content = null;
        if ($id) {
            $content = $this->contentModel->find($id);
            if (!$content) {
                $this->setFlashMessage('error', 'Contenido no encontrado');
                $this->redirect('admin/content');
                return;
            }
        }
        
        $this->view('admin/content/editor', [
            'content' => $content,
            'sections' => $this->contentModel->getAvailableSections()
        ]);
    }
    
    private function handleEditorSave($id)
    {
        $this->validateCSRF();
        
        $user = $this->auth->getCurrentUser();
        $data = [
            'seccion' => $_POST['seccion'] ?? '',
            'tipo' => $_POST['tipo'] ?? 'texto',
            'titulo' => $_POST['titulo'] ?? '',
            'contenido' => $_POST['contenido'] ?? '',
            'contenido_html' => $_POST['contenido_html'] ?? '',
            'orden' => (int)($_POST['orden'] ?? 0),
            'activo' => isset($_POST['activo']) ? 1 : 0,
            'publicado' => isset($_POST['publicado']) ? 1 : 0,
            'modificado_por' => $user['id']
        ];
        
        if ($id) {
            // Actualizar contenido existente
            $this->contentModel->update($id, $data);
            $message = 'Contenido actualizado exitosamente';
        } else {
            // Crear nuevo contenido
            $data['creado_por'] = $user['id'];
            $id = $this->contentModel->create($data);
            $message = 'Contenido creado exitosamente';
        }
        
        $this->logActivity('contenido_editado', 'contenido', 
            "Contenido '{$data['titulo']}' " . ($id ? 'actualizado' : 'creado'));
        
        $this->setFlashMessage('success', $message);
        $this->redirect("admin/content/editor/$id");
    }
    
    // Galería de medios
    public function media()
    {
        $this->auth->requirePermission('contenido.leer');
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        
        $conditions = [];
        if ($type) {
            $conditions['tipo'] = $type;
        }
        
        $pagination = $this->contentModel->getMediaFiles($page, 20, $conditions);
        
        if (Helpers::isAjax()) {
            $this->jsonResponse([
                'success' => true,
                'data' => $pagination['data'],
                'pagination' => $pagination
            ]);
        } else {
            $this->view('admin/content/media', [
                'media' => $pagination['data'],
                'pagination' => $pagination,
                'currentType' => $type
            ]);
        }
    }
    
    // Subir archivos multimedia
    public function upload()
    {
        $this->auth->requirePermission('contenido.crear');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        $this->validateCSRF();
        
        if (empty($_FILES['files'])) {
            $this->jsonResponse(['success' => false, 'message' => 'No se seleccionaron archivos'], 400);
            return;
        }
        
        $uploadResults = [];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'application/pdf'];
        $maxSize = 10 * 1024 * 1024; // 10MB
        
        $files = $_FILES['files'];
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;
        
        for ($i = 0; $i < $fileCount; $i++) {
            $fileName = is_array($files['name']) ? $files['name'][$i] : $files['name'];
            $fileTmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $fileType = is_array($files['type']) ? $files['type'][$i] : $files['type'];
            $fileSize = is_array($files['size']) ? $files['size'][$i] : $files['size'];
            $fileError = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            
            if ($fileError !== UPLOAD_ERR_OK) {
                $uploadResults[] = ['success' => false, 'filename' => $fileName, 'error' => 'Error en la subida'];
                continue;
            }
            
            if (!in_array($fileType, $allowedTypes)) {
                $uploadResults[] = ['success' => false, 'filename' => $fileName, 'error' => 'Tipo de archivo no permitido'];
                continue;
            }
            
            if ($fileSize > $maxSize) {
                $uploadResults[] = ['success' => false, 'filename' => $fileName, 'error' => 'Archivo demasiado grande'];
                continue;
            }
            
            // Generar nombre único
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $uniqueName = uniqid() . '_' . time() . '.' . $extension;
            $uploadPath = '../public/assets/uploads/content/' . $uniqueName;
            
            // Crear directorio si no existe
            $uploadDir = dirname($uploadPath);
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            if (move_uploaded_file($fileTmpName, $uploadPath)) {
                // Guardar en base de datos
                $user = $this->auth->getCurrentUser();
                $mediaId = $this->contentModel->create([
                    'seccion' => 'media',
                    'tipo' => strpos($fileType, 'image/') === 0 ? 'imagen' : 'archivo',
                    'titulo' => pathinfo($fileName, PATHINFO_FILENAME),
                    'imagen_url' => 'assets/uploads/content/' . $uniqueName,
                    'configuracion' => json_encode([
                        'original_name' => $fileName,
                        'file_type' => $fileType,
                        'file_size' => $fileSize
                    ]),
                    'creado_por' => $user['id'],
                    'activo' => 1
                ]);
                
                $uploadResults[] = [
                    'success' => true,
                    'filename' => $fileName,
                    'url' => '/assets/uploads/content/' . $uniqueName,
                    'id' => $mediaId
                ];
            } else {
                $uploadResults[] = ['success' => false, 'filename' => $fileName, 'error' => 'Error al mover archivo'];
            }
        }
        
        $this->jsonResponse([
            'success' => true,
            'results' => $uploadResults
        ]);
    }
    
    // Vista previa del sitio con cambios
    public function preview($section = null)
    {
        $this->auth->requirePermission('contenido.leer');
        
        if ($section) {
            $content = $this->contentModel->getSectionContent($section, false); // Incluir no publicado
            $this->view('site/preview', [
                'section' => $section,
                'content' => $content
            ]);
        } else {
            // Vista previa general del sitio
            $allContent = $this->contentModel->getAllContent(false);
            $this->view('site/preview', [
                'content' => $allContent
            ]);
        }
    }
    
    // Publicar cambios
    public function publish()
    {
        $this->auth->requirePermission('contenido.publicar');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Método no permitido'], 405);
            return;
        }
        
        $this->validateCSRF();
        
        $section = $_POST['section'] ?? '';
        $contentIds = $_POST['content_ids'] ?? [];
        
        if (empty($contentIds)) {
            $this->jsonResponse(['success' => false, 'message' => 'No se seleccionó contenido'], 400);
            return;
        }
        
        try {
            $user = $this->auth->getCurrentUser();
            $publishedCount = 0;
            
            foreach ($contentIds as $id) {
                $result = $this->contentModel->update($id, [
                    'publicado' => 1,
                    'fecha_publicacion' => date('Y-m-d H:i:s'),
                    'modificado_por' => $user['id']
                ]);
                
                if ($result) $publishedCount++;
            }
            
            $this->logActivity('contenido_publicado', 'contenido', 
                "Publicados $publishedCount elementos" . ($section ? " de la sección '$section'" : ''));
            
            $this->jsonResponse([
                'success' => true,
                'message' => "Se publicaron $publishedCount elementos exitosamente"
            ]);
            
        } catch (Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Error al publicar contenido: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Configuración del sitio
    public function settings()
    {
        $this->auth->requirePermission('contenido.configurar');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleSettingsUpdate();
            return;
        }
        
        $settings = $this->contentModel->getWebsiteSettings();
        
        $this->view('admin/content/settings', [
            'settings' => $settings
        ]);
    }
    
    private function handleSettingsUpdate()
    {
        $this->validateCSRF();
        
        $user = $this->auth->getCurrentUser();
        $settings = [
            'site_name' => $_POST['site_name'] ?? '',
            'site_description' => $_POST['site_description'] ?? '',
            'contact_email' => $_POST['contact_email'] ?? '',
            'contact_phone' => $_POST['contact_phone'] ?? '',
            'address' => $_POST['address'] ?? '',
            'social_facebook' => $_POST['social_facebook'] ?? '',
            'social_instagram' => $_POST['social_instagram'] ?? '',
            'social_twitter' => $_POST['social_twitter'] ?? '',
            'social_whatsapp' => $_POST['social_whatsapp'] ?? '',
            'google_analytics' => $_POST['google_analytics'] ?? '',
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
        ];
        
        foreach ($settings as $key => $value) {
            $this->contentModel->updateSetting($key, $value, $user['id']);
        }
        
        $this->logActivity('configuracion_actualizada', 'contenido', 'Configuración del sitio actualizada');
        $this->setFlashMessage('success', 'Configuración actualizada exitosamente');
        $this->redirect('admin/content/settings');
    }
    
    private function getSectionConfig($sectionName)
    {
        $configs = [
            'home_hero' => [
                'title' => 'Hero Principal',
                'fields' => ['titulo', 'subtitulo', 'boton_texto', 'imagen_fondo']
            ],
            'about_us' => [
                'title' => 'Acerca de Nosotros', 
                'fields' => ['titulo', 'descripcion', 'mision', 'vision', 'valores']
            ],
            'services' => [
                'title' => 'Servicios',
                'fields' => ['titulo', 'descripcion', 'servicios_lista']
            ],
            'contact' => [
                'title' => 'Contacto',
                'fields' => ['titulo', 'direccion', 'telefono', 'email', 'horarios']
            ]
        ];
        
        return $configs[$sectionName] ?? ['title' => ucwords(str_replace('_', ' ', $sectionName)), 'fields' => []];
    }
    
    private function getFieldType($field)
    {
        $imageFields = ['imagen', 'fondo', 'logo', 'banner'];
        foreach ($imageFields as $imgField) {
            if (strpos($field, $imgField) !== false) {
                return 'imagen';
            }
        }
        
        if (in_array($field, ['descripcion', 'contenido', 'texto_largo'])) {
            return 'texto';
        }
        
        return 'texto';
    }
    
    private function handleImageUploads($sectionName, $userId)
    {
        foreach ($_FILES as $fieldName => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                
                if (!in_array($file['type'], $allowedTypes)) {
                    continue;
                }
                
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $uniqueName = $sectionName . '_' . $fieldName . '_' . time() . '.' . $extension;
                $uploadPath = '../public/assets/uploads/content/' . $uniqueName;
                
                $uploadDir = dirname($uploadPath);
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    $this->contentModel->create([
                        'seccion' => $sectionName,
                        'tipo' => 'imagen',
                        'titulo' => str_replace('_', ' ', $fieldName),
                        'imagen_url' => 'assets/uploads/content/' . $uniqueName,
                        'creado_por' => $userId,
                        'activo' => 1
                    ]);
                }
            }
        }
    }
    
    private function publishSection($sectionName)
    {
        $this->contentModel->publishSection($sectionName);
    }
    
    private function logActivity($action, $module, $description)
    {
        $user = $this->auth->getCurrentUser();
        $this->contentModel->logActivity([
            'usuario_id' => $user['id'],
            'accion' => $action,
            'modulo' => $module,
            'descripcion' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}