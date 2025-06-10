<?php
/**
 * Verificación de existencia de nombre de usuario
 * 
 * Verifica si un nombre de usuario ya existe en la base de datos.
 * Se usa para validar la disponibilidad durante el registro.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.2
 */

// Requiere dependencias necesarias
require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../api_utils.php';

// Inicializa utilidades de API
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);
$api_utils->displayErrors(); // Desactiva en producción

$request = json_decode(file_get_contents("php://input"), true);
$usuario = $request["usuario"] ?? null;

$auth = new Auth();

try {
    if ($usuario && is_string($usuario) && trim($usuario) !== '') {
        $auth->comprobarUsuario($usuario);
        http_response_code(200);
        $api_utils->response($auth->status, $auth->message, $auth->data ?? null);
    } else {
        http_response_code(400);
        $api_utils->response(false, "Usuario no proporcionado o inválido");
    }
} catch (Exception $e) {
    http_response_code(500);
    $api_utils->response(false, "Error al comprobar el usuario", $e->getMessage());
}

// Respuesta final
$response = $api_utils->response($status, $message, $data, $permises);
echo json_encode($response, JSON_PRETTY_PRINT);

