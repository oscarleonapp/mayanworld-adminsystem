<?php
use App\Core\Config;
// Redirección permanente al detalle unificado con reserva y calendario
$id = isset($product['id']) ? $product['id'] : ($_GET['id'] ?? null);
if ($id) {
    header('Location: ' . Config::getBaseUrl() . '?route=tour/' . urlencode($id), true, 301);
    exit;
}
header('Location: ' . Config::getBaseUrl() . '?route=tours', true, 302);
exit;
?>

