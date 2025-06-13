<?php
/**
 * Endpoint para cierre de sesión de usuarios
 * Invalida el token de autenticación y registra la salida en logs.
 * 
 * @author Francisco López
 * @version 2.1
 */

require_once __DIR__ . '/apiClasses/log.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);

$authorization = new Authorization();
$authorization->comprobarToken();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode($api_utils->response(false, 'Método no permitido'), JSON_PRETTY_PRINT);
    exit;
}

$request_raw = file_get_contents("php://input");
$request = json_decode($request_raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode($api_utils->response(false, 'JSON mal formado'), JSON_PRETTY_PRINT);
    exit;
}

if (!$authorization->token_valido) {
    http_response_code(401);
    echo json_encode($api_utils->response(false, NO_TOKEN_MESSAGE), JSON_PRETTY_PRINT);
    exit;
}

$usuario_token = $authorization->usuario['usuario'] ?? null;
$usuario_post = $request['user'] ?? null;

if (empty($usuario_post) || $usuario_post !== $usuario_token) {
    http_response_code(403);
    echo json_encode($api_utils->response(false, 'El usuario del token no coincide con el proporcionado.'), JSON_PRETTY_PRINT);
    exit;
}

try {
    $conn = new Conexion();

    $stmt = $conn->conexion->prepare("UPDATE usuarios SET token_sesion = NULL WHERE usuario = :usuario");
    $stmt->bindParam(':usuario', $usuario_post);
    $stmt->execute();

    if (session_status() !== PHP_SESSION_NONE) {
        session_unset();
        session_destroy();
    }

    $log = new Log();
    $log->generateLog(1, "Logout con éxito", $usuario_post);

    http_response_code(200);
    $api_utils->response(true, 'Sesión cerrada correctamente');

} catch (Throwable $e) {
    http_response_code(500);
    $api_utils->response(false, 'Error interno al cerrar sesión. Intenta de nuevo.');
}

echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
