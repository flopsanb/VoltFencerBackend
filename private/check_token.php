<?php
/**
 * Verificación de token para restablecer contraseña
 * 
 * Este endpoint valida que el token de recuperación de contraseña sea válido.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.3
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

// Inicialización
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);

// Leer cuerpo de la petición y validar JSON
$raw_input = file_get_contents("php://input");
$request = json_decode($raw_input, true);

$auth = new Auth();

try {
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($request)) {
        http_response_code(400);
        $api_utils->response(false, 'Formato JSON inválido');
        echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
        exit;
    }

    $token = $request['token'] ?? null;

    if (!$token || !is_string($token) || trim($token) === '') {
        http_response_code(400);
        $api_utils->response(false, 'Token no proporcionado o inválido');
        echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
        exit;
    }

    $auth->checkTokenPassword(trim($token));
    http_response_code($auth->status ? 200 : 401);

} catch (Throwable $e) {
    http_response_code(500);
    $auth->status = false;
    $auth->message = 'Error interno al verificar token';
    $auth->data = $e->getMessage();
}

$api_utils->response($auth->status, $auth->message, $auth->data ?? null);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
