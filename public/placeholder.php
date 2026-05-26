<?php
/**
 * Placeholder Image Generator
 *
 * Genera imágenes placeholder dinámicamente cuando no hay imagen de tour
 *
 * Uso:
 * <img src="placeholder.php?w=400&h=300&text=Sin+Imagen" alt="placeholder">
 *
 * Parámetros:
 * - w: ancho (default: 400)
 * - h: alto (default: 300)
 * - text: texto a mostrar (default: "Sin Imagen")
 * - bg: color de fondo hex sin # (default: "e2e8f0")
 * - color: color de texto hex sin # (default: "64748b")
 */

// Obtener parámetros
$width = isset($_GET['w']) ? (int)$_GET['w'] : 400;
$height = isset($_GET['h']) ? (int)$_GET['h'] : 300;
$text = isset($_GET['text']) ? urldecode($_GET['text']) : 'Sin Imagen';
$bgColor = isset($_GET['bg']) ? $_GET['bg'] : 'e2e8f0';
$textColor = isset($_GET['color']) ? $_GET['color'] : '64748b';

// Validar dimensiones
$width = max(50, min(2000, $width));
$height = max(50, min(2000, $height));

// Convertir hex a RGB
function hexToRgb($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return [$r, $g, $b];
}

// Crear imagen
$image = imagecreatetruecolor($width, $height);

// Colores
list($bgR, $bgG, $bgB) = hexToRgb($bgColor);
list($txtR, $txtG, $txtB) = hexToRgb($textColor);

$background = imagecolorallocate($image, $bgR, $bgG, $bgB);
$textColorImg = imagecolorallocate($image, $txtR, $txtG, $txtB);

// Rellenar fondo
imagefilledrectangle($image, 0, 0, $width, $height, $background);

// Agregar texto
$fontSize = max(12, min($width, $height) / 20);
$font = 5; // Built-in font

// Calcular posición centrada del texto
$textWidth = imagefontwidth($font) * strlen($text);
$textHeight = imagefontheight($font);
$x = ($width - $textWidth) / 2;
$y = ($height - $textHeight) / 2;

imagestring($image, $font, $x, $y, $text, $textColorImg);

// Agregar dimensiones en la esquina
$dimensions = $width . 'x' . $height;
imagestring($image, 2, 5, $height - 15, $dimensions, $textColorImg);

// Headers
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400'); // Cache 24 horas

// Output
imagepng($image);
imagedestroy($image);
