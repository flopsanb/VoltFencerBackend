<?php
/**
 * Verificación de existencia de nombre de usuario
 * 
 * Verifica si un nombre de usuario ya existe en la base de datos.
 * Pensado para el formulario de registro.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 2.0
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../api_utils.php';

// Inicializa utilidades de API
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);

// Procesamiento de entrada
$request = json_decode(file_get_contents("php://input"), true);
$usuario = isset($request["usuario"]) && is_string($request["usuario"]) ? trim($request["usuario"]) : null;

$auth = new Auth();

try {
    if (!$usuario) {
        http_response_code(400);
        $api_utils->response(false, "Usuario no proporcionado o inválido");
    } else {
        $auth->comprobarUsuario($usuario);
        http_response_code(200);
        $api_utils->response($auth->status, $auth->message, $auth->data ?? null);
    }
} catch (Exception $e) {
    error_log("[❌ ERROR CHECK_USER] " . $e->getMessage());
    http_response_code(500);
    $api_utils->response(false, "Error interno al verificar el usuario");
}

// Enviar respuesta final
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);