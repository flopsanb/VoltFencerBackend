<?php
/**
 * Verificación de token de autenticación
 * 
 * Este script valida el token recibido y devuelve la información del usuario si es válido.
 * Seguridad reforzada: validación estricta, control de errores y respuesta mínima.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 2.1
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);

// Permitir OPTIONS por si lo lanza el navegador (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$auth = new Auth();

try {
    // Leer cuerpo y validar JSON
    $raw_input = file_get_contents("php://input");
    $request = json_decode($raw_input, true);

    if (json_last_error() !== JSON_ERROR_NONE || !is_array($request)) {
        http_response_code(400);
        $api_utils->response(false, "Formato JSON inválido");
        echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
        exit;
    }

    $token = isset($request["token"]) && is_string($request["token"]) ? trim($request["token"]) : null;

    if (!$token) {
        http_response_code(400);
        $api_utils->response(false, "Token no proporcionado o inválido");
    } else {
        $auth->checkUsuario($token);
        http_response_code($auth->status ? 200 : 401);
        $api_utils->response($auth->status, $auth->message, $auth->data ?? null);
    }

} catch (Throwable $e) {
    http_response_code(500);
    $api_utils->response(false, "Error interno al verificar el token", $e->getMessage());
}

// Enviar respuesta
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
