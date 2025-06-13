<?php
declare(strict_types=1);

/**
 * Endpoint para autenticación de usuarios
 * Procesa las credenciales y devuelve token si son válidas
 * 
 * @author Francisco
 * @version 2.1
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== ApiUtils::POST) {
    http_response_code(405);
    echo json_encode($api_utils->response(false, 'Método no permitido. Solo POST'), JSON_PRETTY_PRINT);
    exit;
}

// Leer y parsear JSON
$request_raw = file_get_contents("php://input");
$request = json_decode($request_raw, true);

// Validar JSON
if (json_last_error() !== JSON_ERROR_NONE || !is_array($request)) {
    http_response_code(400);
    echo json_encode($api_utils->response(false, 'Formato JSON inválido o mal formado'), JSON_PRETTY_PRINT);
    exit;
}

// Validar credenciales mínimas
function credencialesInvalidas(array $req): bool {
    return (
        empty($req['username']) || !is_string($req['username']) ||
        empty($req['password']) || !is_string($req['password'])
    );
}

if (credencialesInvalidas($request)) {
    http_response_code(400);
    echo json_encode($api_utils->response(false, 'Credenciales incompletas o inválidas'), JSON_PRETTY_PRINT);
    exit;
}

$username = trim($request['username']);
$password = trim($request['password']);

try {
    $auth = new Auth();
    $auth->doLogin($username, $password);

    http_response_code($auth->status ? 200 : 401);
    $api_utils->response($auth->status, $auth->message, $auth->data);

} catch (Throwable $e) {
    http_response_code(500);
    $api_utils->response(false, 'Error interno en el login');
}

echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
