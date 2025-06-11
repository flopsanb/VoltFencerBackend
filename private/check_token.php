<?php
/**
 * Verificación de token de autenticación
 * 
 * Este script valida el token recibido y devuelve la información del usuario si es válido.
 * Seguridad reforzada: validación estricta, control de errores y respuesta mínima.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 2.0
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../api_utils.php';

// Inicialización de utilidades
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);

// OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Leer cuerpo de la petición
$request = json_decode(file_get_contents("php://input"), true);
$token = isset($request["token"]) && is_string($request["token"]) ? trim($request["token"]) : null;

$auth = new Auth();

try {
    if (!$token) {
        http_response_code(400);
        $api_utils->response(false, "Token no proporcionado o inválido");
    } else {
        $auth->checkUsuario($token);

        if ($auth->status) {
            http_response_code(200);
            $api_utils->response(true, $auth->message, $auth->data);
        } else {
            http_response_code(401);
            $api_utils->response(false, $auth->message);
        }
    }
} catch (Exception $e) {
    error_log("[❌ ERROR CHECK_TOKEN] " . $e->getMessage());
    http_response_code(500);
    $api_utils->response(false, "Error interno al verificar el token");
}

// Enviar respuesta
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);