<?php
/**
 * Verificación de token de autenticación
 * 
 * Este script valida el token recibido y devuelve la información del usuario si es válido.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.2
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../api_utils.php';

// Inicializa las utilidades
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);

// Response 200 si es una petición preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Decodifica el cuerpo de la petición
$request = json_decode(file_get_contents("php://input"), true);
$token = $request["token"] ?? null;

$auth = new Auth();

try {
    if ($token && is_string($token) && trim($token) !== '') {
        $auth->checkUsuario($token);
        http_response_code($auth->status ? 200 : 401);
        $api_utils->response($auth->status, $auth->message, $auth->data);
    } else {
        http_response_code(400);
        $api_utils->response(false, "Token no proporcionado o inválido");
    }
} catch (Exception $e) {
    http_response_code(500);
    $api_utils->response(false, "Error al verificar el token", $e->getMessage());
}

// Muestra la respuesta
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);

