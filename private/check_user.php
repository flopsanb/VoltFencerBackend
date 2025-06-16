<?php
/**
 * Verificación de existencia de nombre de usuario
 * 
 * Verifica si un nombre de usuario ya existe en la base de datos.
 * Pensado para el formulario de registro o recuperación de contraseña.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 2.1
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);     // Solo se acepta método POST por seguridad

$auth = new Auth();         // Clase que contiene la lógica de verificación de existencia de usuario

try {
    // Leer y validar el JSON
    $raw_input = file_get_contents("php://input");  // Se obtiene el JSON enviado por el cliente
    $request = json_decode($raw_input, true);       // Se decodifica como array

    // Verificación de que el contenido es un JSON válido
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Formato JSON inválido");
    }

    // Se extrae el campo y se limpia de espacios
    $usuario = isset($request["usuario"]) ? trim($request["usuario"]) : null;

    // Se valida que exista y que tenga al menos 4 caracteres
    if (!$usuario || strlen($usuario) < 4) {
        throw new Exception("El nombre de usuario es obligatorio y debe tener al menos 4 caracteres");
    }

    // Se consulta si el usuario ya existe en el sistema
    $auth->comprobarUsuario($usuario);

    // Si no hay errores, se responde con código 200 (OK)
    http_response_code(200);
    $api_utils->response($auth->status, $auth->message, $auth->data ?? null);

} catch (Exception $e) {
    http_response_code(400);    // Error de cliente (Bad Request)
    $api_utils->response(false, $e->getMessage());
}

// Respuesta final al cliente en formato JSON estructurado
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
