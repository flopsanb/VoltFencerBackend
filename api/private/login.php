<?php
/**
 * Endpoint para autenticación de usuarios
 * 
 * Procesa las credenciales recibidas y devuelve un token si son válidas.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.1
 */

require_once(__DIR__ . '/apiClasses/auth.php');
require_once(__DIR__ . '/../api_utils.php');

/**
 * Inicialización de utilidades de API
 * 
 * Solo se aceptan peticiones POST para este endpoint.
 */
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);
$api_utils->displayErrors(); // Activar solo en desarrollo

/**
 * Procesamiento y validación de entrada
 */
$request = json_decode(file_get_contents('php://input'), true);

if (!isset($request['username']) || !isset($request['password'])) {
    $api_utils->response(false, 'Faltan credenciales');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

$username = trim($request['username']);
$password = trim($request['password']);

if ($username === '' || $password === '') {
    $api_utils->response(false, 'Usuario o contraseña vacíos');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

// Registro de depuración (sin mostrar contraseña en producción)
error_log("🔐 Intento de login para: $username");

/**
 * Proceso de autenticación
 */
$auth = new Auth();
$auth->doLogin($username, $password);

/**
 * Respuesta
 */
$api_utils->response($auth->status, $auth->message, $auth->data);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
