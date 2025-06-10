<?php
// index.php - Router principal para el backend VoltFencer

// Mostrar errores (se desactiva en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Obtener la ruta solicitada
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// Normalizar (quitar `/api/` ya que no es necesario en Render)
$normalizedPath = preg_replace('#^/api/#', '', ltrim($path, '/'));

// Ruta completa del archivo dentro de /private
$targetFile = __DIR__ . "/private/" . $normalizedPath;

// Comprobar si el archivo existe y cargarlo
if (is_file($targetFile)) {
    require_once $targetFile;
    exit();
} else {
    // Respuesta 404 si el archivo no existe
    header('Content-Type: application/json');
    http_response_code(404);
    echo json_encode([
        "ok" => false,
        "message" => "Archivo no encontrado: $normalizedPath"
    ]);
    exit();
}