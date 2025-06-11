<?php
/**
 * Endpoint para autenticación de usuarios
 * Procesa las credenciales y devuelve token si son válidas
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);

// Permitir solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $api_utils->response(false, 'Método no permitido');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

// Leer y validar JSON
$request_raw = file_get_contents('php://input');
$request = json_decode($request_raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $api_utils->response(false, 'Formato JSON inválido');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

// Validar campos
function credencialesInvalidas($req) {
    return (
        !isset($req['username']) || !is_string($req['username']) || trim($req['username']) === '' ||
        !isset($req['password']) || !is_string($req['password']) || trim($req['password']) === ''
    );
}

if (credencialesInvalidas($request)) {
    http_response_code(400);
    $api_utils->response(false, 'Credenciales inválidas');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

$username = trim($request['username']);
$password = trim($request['password']);

try {
    // Autenticación
    $auth = new Auth();
    $auth->doLogin($username, $password);

    // Respuesta según éxito
    http_response_code($auth->status ? 200 : 401);
    $api_utils->response($auth->status, $auth->message, $auth->data, null);
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    // Error en el login
    error_log("[❌ ERROR LOGIN] " . $e->getMessage());
    http_response_code(500);
    $api_utils->response(false, 'Error interno del servidor');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
}
