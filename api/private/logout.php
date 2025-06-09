<?php
/**
 * Endpoint para cierre de sesión de usuarios
 * 
 * Invalida el token de autenticación y registra la salida en logs.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.1
 */

require_once(__DIR__ . '/apiClasses/log.php');
require_once(__DIR__ . '/../conn.php');
require_once(__DIR__ . '/../api_utils.php');

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST); // Solo se permite POST
$api_utils->displayErrors();

$request = json_decode(file_get_contents("php://input"), true);

if (!isset($request['user'])) {
    $api_utils->response(false, 'Usuario no especificado');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

$usuario = $request['user'];
$conn = new Conexion();

try {
    // Invalida el token de sesión
    $stmt = $conn->conexion->prepare("UPDATE usuarios SET token_sesion = NULL WHERE usuario = :usuario");
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();

    // Destruye la sesión PHP
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_unset();
    session_destroy();

    // Registra en el log el logout
    $log = new Log();
    $log->generateLog(1, "Logout con éxito", $usuario);

    $api_utils->response(true, 'Logout completado correctamente');

} catch (Exception $e) {
    $api_utils->response(false, 'Error al cerrar sesión', $e->getMessage());
}

echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
