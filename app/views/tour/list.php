<?php

use App\Core\Config;
// Vista obsoleta: redirige permanentemente al listado enriquecido
header('Location: ' . Config::getBaseUrl() . '?route=tours', true, 301);
exit;
?>
