<?php
/**
 * Verificación de token para restablecer contraseña
 * 
 * Este endpoint valida que el token de recuperación de contraseña sea válido.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.2
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

// Inicialización
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);
$api_utils->displayErrors();

$request = json_decode(file_get_contents("php://input"), true);
$auth = new Auth();

try {
    if (!isset($request['token']) || empty($request['token'])) {
        throw new Exception('Token no proporcionado');
    }

    $auth->checkTokenPassword($request['token']);

} catch (Exception $e) {
    $auth->status = false;
    $auth->message = $e->getMessage();
}

$api_utils->response($auth->status, $auth->message, $auth->data ?? null);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
