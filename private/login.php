<?php
/**
 * Endpoint para autenticación de usuarios
 * Procesa las credenciales y devuelve token si son válidas
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    $api_utils->response(false, 'Método no permitido');
    $response = $api_utils->response($status, $message, $data, $permises);
echo json_encode($response, JSON_PRETTY_PRINT);

    exit;
}

// Procesamiento de entrada
$request = json_decode(file_get_contents('php://input'), true);

if (
    !isset($request['username']) || !is_string($request['username']) || trim($request['username']) === '' ||
    !isset($request['password']) || !is_string($request['password']) || trim($request['password']) === ''
) {
    http_response_code(400);
    $api_utils->response(false, 'Credenciales inválidas');
    $response = $api_utils->response($status, $message, $data, $permises);
    echo json_encode($response, JSON_PRETTY_PRINT);

    exit;
}

$username = trim($request['username']);
$password = trim($request['password']);

// Autenticación
$auth = new Auth();
$auth->doLogin($username, $password);

// Definir código de respuesta según estado
http_response_code($auth->status ? 200 : 401);

// Respuesta
$response = $api_utils->response($status, $message, $data, $permises);
echo json_encode($response, JSON_PRETTY_PRINT);

