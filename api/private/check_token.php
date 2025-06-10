<?php
/**
 * Verificación de token de autenticación
 * 
 * Este script valida el token recibido y devuelve la información del usuario si es válido.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.1
 */

require_once __DIR__ . '/apiClasses/auth.php';
require_once __DIR__ . '/../api_utils.php';

// Inicializa las utilidades
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);
$api_utils->displayErrors(); // Desactiva esto en producción

// 🔥 CORTA si es una petición preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Decodifica el token desde la petición
$request = json_decode(file_get_contents("php://input"), true);
$token = $request["token"] ?? null;

// Instancia de autenticación
$auth = new Auth();

// Verificación
if ($token) {
    $auth->checkUsuario($token);
    $api_utils->response($auth->status, $auth->message, $auth->data);
} else {
    $api_utils->response(false, "Token no proporcionado");
}

// Muestra la respuesta
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
