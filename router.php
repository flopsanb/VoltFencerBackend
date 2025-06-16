<?php
// router.php

// Solo aplica si se ejecuta con el servidor embebido de PHP
if (php_sapi_name() === 'cli-server') {
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    $fullPath = __DIR__ . $path;

    // Si el archivo existe, lo servimos directamente
    if (is_file($fullPath)) {
        return false;
    }
}

// Redirige todo a tu archivo principal (index.php)
require_once __DIR__ . '/index.php';
