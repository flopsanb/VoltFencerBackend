<?php
/**
 * Verificación de token de autenticación
 * 
 * Este script permite validar la autenticidad de un token de sesión enviado por el cliente.
 * Su principal finalidad es comprobar que un usuario sigue autenticado y obtener sus datos,
 * protegiendo al mismo tiempo la integridad de la sesión mediante controles estrictos.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 2.1
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);     // Solo se acepta POST para mayor seguridad

// Si el navegador envía una petición OPTIONS (preflight), se responde sin más lógica
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$auth = new Auth();     // Clase que contiene la lógica de verificación de tokens

try {
    // Leer cuerpo y validar JSON
    $raw_input = file_get_contents("php://input");      // Se obtiene el JSON enviado por el cliente
    $request = json_decode($raw_input, true);           // Se decodifica como array

    // Si el contenido no es JSON válido, se devuelve error 400
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($request)) {
        http_response_code(400);
        $api_utils->response(false, "Formato JSON inválido");
        echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
        exit;
    }

    // Se obtiene el valor del token (se espera que venga como string no vacío)
    $token = isset($request["token"]) && is_string($request["token"]) ? trim($request["token"]) : null;

    // Si no se proporciona un token válido, se devuelve error 400
    if (!$token) {
        http_response_code(400);
        $api_utils->response(false, "Token no proporcionado o inválido");
    } else {

        // Si el token es válido, se comprueba en la base de datos el usuario asociado
        $auth->checkUsuario($token);

        // Se responde con estado 200 si es válido, o 401 si es inválido o ha expirado
        http_response_code($auth->status ? 200 : 401);
        $api_utils->response($auth->status, $auth->message, $auth->data ?? null);
    }

} catch (Throwable $e) {
    http_response_code(500);        // Error interno del servidor
    $api_utils->response(false, "Error interno al verificar el token", $e->getMessage());
}

// Envío de la respuesta final al cliente en formato JSON
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
