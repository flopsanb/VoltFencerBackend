<?php
/**
 * Endpoint para comprobar duplicados de usuario o email.
 */

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';
require_once __DIR__ . '/apiClasses/usuario.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);

$authorization = new Authorization();
$authorization->comprobarToken();

if (!$authorization->token_valido) {
    http_response_code(401);
    $api_utils->response(false, NO_TOKEN_MESSAGE);
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

$usuario = new Usuario($authorization);

try {
    if ($_SERVER['REQUEST_METHOD'] !== ApiUtils::GET) {
        http_response_code(405);
        $usuario->status = false;
        $usuario->message = 'Método no permitido.';
    } else {
        $param_usuario    = $_GET['usuario'] ?? null;
        $param_email      = $_GET['email'] ?? null;
        $exclude_id       = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

        if ($param_usuario) {
            $usuario->status = true;
            $usuario->data   = ['exists' => $usuario->existsUsuario(trim($param_usuario), $exclude_id)];
        } elseif ($param_email) {
            $usuario->status = true;
            $usuario->data   = ['exists' => $usuario->existsEmail(trim($param_email), $exclude_id)];
        } else {
            http_response_code(400);
            $usuario->status = false;
            $usuario->message = 'Debes proporcionar "usuario" o "email" como parámetro.';
        }
    }

} catch (Throwable $e) {
    http_response_code(500);
    $usuario->status  = false;
    $usuario->message = 'Error inesperado en validación de duplicados';
    $usuario->data    = $e->getMessage();
}

$api_utils->response($usuario->status, $usuario->message, $usuario->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
