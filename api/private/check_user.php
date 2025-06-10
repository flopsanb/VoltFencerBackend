<?php
/**
 * Verificación de existencia de nombre de usuario
 * 
 * Verifica si un nombre de usuario ya existe en la base de datos.
 * Se usa para validar la disponibilidad durante el registro.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.1
 */

// Requiere dependencias necesarias
require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../api_utils.php';

// Inicializa utilidades de API
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);
$api_utils->displayErrors(); // Desactiva en producción

// Decodifica datos JSON del cuerpo de la petición
$request = json_decode(file_get_contents("php://input"), true);
$usuario = $request["usuario"] ?? null;

// Instancia de autenticación
$auth = new Auth();

// Verifica existencia del usuario
if ($usuario) {
    $auth->comprobarUsuario($usuario);
    $api_utils->response($auth->status, $auth->message);
} else {
    $api_utils->response(false, "Usuario no proporcionado");
}

// Respuesta final
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
