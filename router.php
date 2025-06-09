<?php
// router.php

// Si el archivo solicitado existe, dejar que PHP lo maneje directamente
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $fullPath = __DIR__ . $path;
    if (is_file($fullPath)) {
        return false;
    }
}

// En cualquier otro caso, redirigir al punto de entrada (index.php, api.php, lo que uses)
require_once __DIR__ . '/index.php';
