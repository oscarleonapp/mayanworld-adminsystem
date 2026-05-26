<?php
namespace App\Helpers;

use App\Core\Database;
use App\Core\Config;
use Exception;

/**
 * ImageOptimizer
 *
 * Helper para optimización y procesamiento de imágenes
 * - Redimensionamiento automático
 * - Generación de variantes (thumbnail, small, medium, large)
 * - Conversión a WebP
 * - Compresión con calidad configurable
 *
 * @version 1.0.0
 * @date 2025-11-05
 */
class ImageOptimizer
{
    private static $config = null;

    /**
     * Tamaños predefinidos para variantes
     */
    const SIZES = [
        'thumbnail' => 150,
        'small' => 300,
        'medium' => 768,
        'large' => 1200
    ];

    /**
     * Inicializar configuración
     */
    private static function init()
    {
        if (self::$config !== null) {
            return;
        }

        // Cargar configuración desde BD si existe
        try {
            $db = Database::getInstance();
            $configs = $db->fetchAll("SELECT config_key, config_value FROM media_config");
            self::$config = [];
            foreach ($configs as $row) {
                self::$config[$row['config_key']] = $row['config_value'];
            }
        } catch (Exception $e) {
            // Usar configuración por defecto si falla
            self::$config = [
                'jpeg_quality' => 85,
                'create_webp' => 'yes',
                'auto_optimize_images' => 'yes',
                'thumbnail_size' => 150,
                'small_size' => 300,
                'medium_size' => 768,
                'large_size' => 1200
            ];
        }
    }

    /**
     * Optimizar imagen principal
     *
     * @param string $filePath Ruta al archivo
     * @param array $options Opciones de optimización
     * @return bool Éxito de la operación
     */
    public static function optimize($filePath, $options = [])
    {
        self::init();

        if (!file_exists($filePath)) {
            return false;
        }

        $defaults = [
            'quality' => (int)self::$config['jpeg_quality'],
            'max_width' => null,
            'max_height' => null,
            'maintain_aspect' => true
        ];

        $options = array_merge($defaults, $options);

        try {
            $imageInfo = getimagesize($filePath);
            if (!$imageInfo) {
                return false;
            }

            list($width, $height, $type) = $imageInfo;

            // Cargar imagen según tipo
            $image = self::loadImage($filePath, $type);
            if (!$image) {
                return false;
            }

            // Redimensionar si se especifica
            if ($options['max_width'] || $options['max_height']) {
                $image = self::resize($image, $width, $height, $options);
            }

            // Guardar imagen optimizada
            $saved = self::saveImage($image, $filePath, $type, $options['quality']);
            imagedestroy($image);

            return $saved;
        } catch (Exception $e) {
            error_log("Error optimizando imagen: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generar todas las variantes de una imagen
     *
     * @param string $originalPath Ruta de la imagen original
     * @param string $outputDir Directorio para variantes
     * @param string $basename Nombre base (sin extensión)
     * @return array Array de variantes generadas
     */
    public static function generateVariants($originalPath, $outputDir, $basename)
    {
        self::init();

        $variants = [];

        if (!file_exists($originalPath)) {
            return $variants;
        }

        $imageInfo = getimagesize($originalPath);
        if (!$imageInfo) {
            return $variants;
        }

        list($origWidth, $origHeight, $type) = $imageInfo;
        $extension = self::getExtension($type);

        // Cargar imagen original
        $originalImage = self::loadImage($originalPath, $type);
        if (!$originalImage) {
            return $variants;
        }

        // Generar cada variante
        foreach (self::SIZES as $variantName => $maxSize) {
            // Solo generar si la imagen original es más grande
            if ($origWidth > $maxSize || $origHeight > $maxSize) {
                $variantFilename = "{$basename}_{$variantName}.{$extension}";
                $variantPath = $outputDir . '/' . $variantFilename;

                $resized = self::resize($originalImage, $origWidth, $origHeight, [
                    'max_width' => $maxSize,
                    'max_height' => $maxSize,
                    'maintain_aspect' => true
                ]);

                if ($resized) {
                    $saved = self::saveImage($resized, $variantPath, $type, (int)self::$config['jpeg_quality']);
                    if ($saved) {
                        $variantInfo = getimagesize($variantPath);
                        $variants[$variantName] = [
                            'filename' => $variantFilename,
                            'path' => $variantPath,
                            'width' => $variantInfo[0],
                            'height' => $variantInfo[1],
                            'file_size' => filesize($variantPath)
                        ];
                    }
                    imagedestroy($resized);
                }
            }
        }

        imagedestroy($originalImage);

        // Generar versión WebP si está habilitado
        if (self::$config['create_webp'] === 'yes' && function_exists('imagewebp')) {
            $webpVariants = self::generateWebP($originalPath, $outputDir, $basename);
            if ($webpVariants) {
                $variants['webp'] = $webpVariants;
            }
        }

        return $variants;
    }

    /**
     * Generar versión WebP de la imagen
     *
     * @param string $originalPath Ruta de la imagen original
     * @param string $outputDir Directorio de salida
     * @param string $basename Nombre base
     * @return array|null Información de la variante WebP
     */
    public static function generateWebP($originalPath, $outputDir, $basename)
    {
        if (!function_exists('imagewebp')) {
            return null;
        }

        $imageInfo = getimagesize($originalPath);
        if (!$imageInfo) {
            return null;
        }

        list($width, $height, $type) = $imageInfo;

        $image = self::loadImage($originalPath, $type);
        if (!$image) {
            return null;
        }

        $webpFilename = "{$basename}.webp";
        $webpPath = $outputDir . '/' . $webpFilename;

        $saved = imagewebp($image, $webpPath, (int)self::$config['jpeg_quality']);
        imagedestroy($image);

        if ($saved && file_exists($webpPath)) {
            return [
                'filename' => $webpFilename,
                'path' => $webpPath,
                'width' => $width,
                'height' => $height,
                'file_size' => filesize($webpPath)
            ];
        }

        return null;
    }

    /**
     * Redimensionar imagen manteniendo proporción
     *
     * @param resource $image Recurso de imagen GD
     * @param int $currentWidth Ancho actual
     * @param int $currentHeight Alto actual
     * @param array $options Opciones de redimensionamiento
     * @return resource Nueva imagen redimensionada
     */
    private static function resize($image, $currentWidth, $currentHeight, $options)
    {
        $maxWidth = $options['max_width'] ?? null;
        $maxHeight = $options['max_height'] ?? null;
        $maintainAspect = $options['maintain_aspect'] ?? true;

        if (!$maxWidth && !$maxHeight) {
            return $image;
        }

        // Calcular nuevas dimensiones
        if ($maintainAspect) {
            $ratio = $currentWidth / $currentHeight;

            if ($maxWidth && $maxHeight) {
                // Ajustar al lado más pequeño
                if ($currentWidth > $currentHeight) {
                    $newWidth = $maxWidth;
                    $newHeight = (int)($maxWidth / $ratio);
                } else {
                    $newHeight = $maxHeight;
                    $newWidth = (int)($maxHeight * $ratio);
                }
            } elseif ($maxWidth) {
                $newWidth = min($maxWidth, $currentWidth);
                $newHeight = (int)($newWidth / $ratio);
            } else {
                $newHeight = min($maxHeight, $currentHeight);
                $newWidth = (int)($newHeight * $ratio);
            }
        } else {
            $newWidth = $maxWidth ?? $currentWidth;
            $newHeight = $maxHeight ?? $currentHeight;
        }

        // Crear nueva imagen
        $resized = imagecreatetruecolor($newWidth, $newHeight);

        // Preservar transparencia para PNG y GIF
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);

        // Redimensionar con resampling de alta calidad
        imagecopyresampled(
            $resized, $image,
            0, 0, 0, 0,
            $newWidth, $newHeight,
            $currentWidth, $currentHeight
        );

        return $resized;
    }

    /**
     * Cargar imagen desde archivo
     *
     * @param string $filePath Ruta del archivo
     * @param int $type Tipo de imagen (IMAGETYPE_*)
     * @return resource|false Recurso de imagen o false
     */
    private static function loadImage($filePath, $type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filePath);
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filePath);
            case IMAGETYPE_GIF:
                return imagecreatefromgif($filePath);
            case IMAGETYPE_WEBP:
                return function_exists('imagecreatefromwebp')
                    ? imagecreatefromwebp($filePath)
                    : false;
            default:
                return false;
        }
    }

    /**
     * Guardar imagen a archivo
     *
     * @param resource $image Recurso de imagen
     * @param string $filePath Ruta de destino
     * @param int $type Tipo de imagen
     * @param int $quality Calidad (0-100)
     * @return bool Éxito de la operación
     */
    private static function saveImage($image, $filePath, $type, $quality = 85)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return imagejpeg($image, $filePath, $quality);
            case IMAGETYPE_PNG:
                // PNG usa compresión 0-9 (inverso de calidad)
                $compression = (int)(9 - ($quality / 100) * 9);
                return imagepng($image, $filePath, $compression);
            case IMAGETYPE_GIF:
                return imagegif($image, $filePath);
            case IMAGETYPE_WEBP:
                return function_exists('imagewebp')
                    ? imagewebp($image, $filePath, $quality)
                    : false;
            default:
                return false;
        }
    }

    /**
     * Obtener extensión del archivo según tipo de imagen
     *
     * @param int $type Tipo de imagen (IMAGETYPE_*)
     * @return string Extensión
     */
    private static function getExtension($type)
    {
        switch ($type) {
            case IMAGETYPE_JPEG:
                return 'jpg';
            case IMAGETYPE_PNG:
                return 'png';
            case IMAGETYPE_GIF:
                return 'gif';
            case IMAGETYPE_WEBP:
                return 'webp';
            default:
                return 'jpg';
        }
    }

    /**
     * Verificar si un archivo es una imagen válida
     *
     * @param string $filePath Ruta del archivo
     * @return bool
     */
    public static function isValidImage($filePath)
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $imageInfo = @getimagesize($filePath);
        return $imageInfo !== false;
    }

    /**
     * Obtener información de una imagen
     *
     * @param string $filePath Ruta del archivo
     * @return array|null Array con width, height, type, mime
     */
    public static function getImageInfo($filePath)
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) {
            return null;
        }

        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'type' => $imageInfo[2],
            'mime' => $imageInfo['mime']
        ];
    }

    /**
     * Crear thumbnail cuadrado (crop al centro)
     *
     * @param string $originalPath Ruta de la imagen original
     * @param string $outputPath Ruta de salida
     * @param int $size Tamaño del thumbnail
     * @return bool Éxito de la operación
     */
    public static function createSquareThumbnail($originalPath, $outputPath, $size = 150)
    {
        $imageInfo = @getimagesize($originalPath);
        if (!$imageInfo) {
            return false;
        }

        list($width, $height, $type) = $imageInfo;

        $image = self::loadImage($originalPath, $type);
        if (!$image) {
            return false;
        }

        // Calcular crop al centro
        $cropSize = min($width, $height);
        $cropX = ($width - $cropSize) / 2;
        $cropY = ($height - $cropSize) / 2;

        // Crear thumbnail
        $thumb = imagecreatetruecolor($size, $size);

        // Preservar transparencia
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, $size, $size, $transparent);

        // Crop y resize
        imagecopyresampled(
            $thumb, $image,
            0, 0, $cropX, $cropY,
            $size, $size,
            $cropSize, $cropSize
        );

        self::init();
        $saved = self::saveImage($thumb, $outputPath, $type, (int)self::$config['jpeg_quality']);

        imagedestroy($image);
        imagedestroy($thumb);

        return $saved;
    }
}
