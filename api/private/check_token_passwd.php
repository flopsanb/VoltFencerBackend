<?php
/**
 * Verificación de token para restablecer contraseña
 * 
 * Este endpoint valida que el token de recuperación de contraseña sea válido.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.1
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

// Inicialización
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);
$api_utils->displayErrors(); // ¡DESACTIVA en producción!

// Procesa la petición
$request = json_decode(file_get_contents("php://input"), true);
$token = $request['token'] ?? null;

$auth = new Auth();

try {
    if (!$token) {
        throw new Exception('Token no proporcionado');
    }

    $auth->checkTokenPassword($token);

} catch (Exception $e) {
    $auth->status = false;
    $auth->message = $e->getMessage();
}

// Respuesta
$api_utils->response($auth->status, $auth->message, $auth->data ?? null);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
