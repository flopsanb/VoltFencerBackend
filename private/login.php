<?php
declare(strict_types=1);

/**
 * Endpoint para autenticación de usuarios
 * Procesa las credenciales y devuelve token si son válidas
 * 
 * @author Francisco
 * @version 2.0
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);

// Solo permitir método POST
if ($_SERVER['REQUEST_METHOD'] !== ApiUtils::POST) {
    http_response_code(405);
    $api_utils->response(false, 'Método no permitido. Solo POST');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

// Leer entrada y validar JSON
$request_raw = file_get_contents("php://input");
$request = json_decode($request_raw, true);

if (json_last_error() !== JSON_ERROR_NONE || !is_array($request)) {
    http_response_code(400);
    $api_utils->response(false, 'Formato JSON inválido o mal formado');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

// Validar credenciales mínimas
function credencialesInvalidas(array $req): bool {
    return (
        !isset($req['username']) || !is_string($req['username']) || trim($req['username']) === '' ||
        !isset($req['password']) || !is_string($req['password']) || trim($req['password']) === ''
    );
}

if (credencialesInvalidas($request)) {
    http_response_code(400);
    $api_utils->response(false, 'Credenciales incompletas o inválidas');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

$username = trim($request['username']);
$password = trim($request['password']);

try {
    // Ejecutar login
    $auth = new Auth();
    $auth->doLogin($username, $password);

    if ($auth->status) {
        http_response_code(200);
    } else {
        http_response_code(401);
    }

    $api_utils->response($auth->status, $auth->message, $auth->data, null);

} catch (Throwable $e) {
    error_log('[❌ ERROR LOGIN] ' . $e->getMessage());
    http_response_code(500);
    $api_utils->response(false, 'Error interno en el login');
}

echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
