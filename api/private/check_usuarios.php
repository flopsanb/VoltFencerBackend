<?php
/**
 * Verificación de usuarios autenticados
 * 
 * Valida el token de autenticación y devuelve información
 * del usuario autenticado si el token es válido.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.2
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

// Inicialización
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::GET);
$api_utils->displayErrors();

$authorization = new Authorization();
$authorization->comprobarToken();

$auth = new Auth();

try {
    if ($authorization->token_valido) {
        $ruta = $_GET['ruta'] ?? '';
        $auth->checkUsuario($authorization->token, $ruta);
        http_response_code(200);
    } else {
        $auth->status = false;
        $auth->message = NO_TOKEN_MESSAGE;
        $auth->data = null;
        http_response_code(401);
    }

} catch (Exception $e) {
    $auth->status = false;
    $auth->message = 'Error al comprobar el usuario';
    $auth->data = $e->getMessage();
    http_response_code(500);
}

// Respuesta final
$api_utils->response(
    $auth->status,
    $auth->message,
    $auth->data,
    $authorization->permises ?? []
);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
