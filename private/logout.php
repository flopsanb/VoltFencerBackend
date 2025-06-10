<?php
/**
 * Endpoint para cierre de sesión de usuarios
 * Invalida el token de autenticación y registra la salida en logs.
 */

require_once __DIR__ . '/apiClasses/log.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);
$api_utils->displayErrors();

$authorization = new Authorization();
$authorization->comprobarToken();

$request = json_decode(file_get_contents("php://input"), true);
$data = null;

if (!$authorization->token_valido) {
    http_response_code(401);
    $api_utils->response(false, NO_TOKEN_MESSAGE, null);
    $response = $api_utils->response($status, $message, $data, $permises);
echo json_encode($response, JSON_PRETTY_PRINT);

    exit;
}

$usuario_token = $authorization->usuario['usuario'] ?? null;
$usuario_post = $request['user'] ?? null;

if (!$usuario_post || $usuario_post !== $usuario_token) {
    http_response_code(403);
    $api_utils->response(false, 'Usuario no coincide con el token');
    $response = $api_utils->response($status, $message, $data, $permises);
echo json_encode($response, JSON_PRETTY_PRINT);

    exit;
}

$conn = new Conexion();

try {
    // Invalida token
    $stmt = $conn->conexion->prepare("UPDATE usuarios SET token_sesion = NULL WHERE usuario = :usuario");
    $stmt->bindParam(':usuario', $usuario_post);
    $stmt->execute();

    // Matar sesión PHP
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_unset();
    session_destroy();

    // Registro log
    $log = new Log();
    $log->generateLog(1, "Logout con éxito", $usuario_post);

    http_response_code(200);
    $api_utils->response(true, 'Logout completado correctamente');

} catch (Exception $e) {
    http_response_code(500);
    $api_utils->response(false, 'Error al cerrar sesión', $e->getMessage());
}

echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
