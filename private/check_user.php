<?php
/**
 * Verificación de existencia de nombre de usuario
 * 
 * Verifica si un nombre de usuario ya existe en la base de datos.
 * Pensado para el formulario de registro o recuperación de contraseña.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 2.1
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);

$auth = new Auth();

try {
    // Leer y validar el JSON
    $raw_input = file_get_contents("php://input");
    $request = json_decode($raw_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Formato JSON inválido");
    }

    $usuario = isset($request["usuario"]) ? trim($request["usuario"]) : null;

    if (!$usuario || strlen($usuario) < 3) {
        throw new Exception("El nombre de usuario es obligatorio y debe tener al menos 3 caracteres");
    }

    $auth->comprobarUsuario($usuario);

    http_response_code(200);
    $api_utils->response($auth->status, $auth->message, $auth->data ?? null);

} catch (Exception $e) {
    http_response_code(400);
    $api_utils->response(false, $e->getMessage());
}

echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
