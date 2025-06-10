<?php
/**
 * Endpoint para gestión de estado de conexión de usuarios
 * 
 * Permite registrar actividad (ping de conexión) y obtener
 * lista de usuarios conectados.
 */

require_once __DIR__ . '/apiClasses/estado_conexion.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

// Inicialización
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);
$api_utils->displayErrors(); // ⚠️ Comentar en producción

// Validación de token
$authorization = new Authorization();
$authorization->comprobarToken();
$id_usuario = $authorization->id_usuario ?? null;

// Instancia de lógica
$conexion = new EstadoConexion();

// Procesamiento
if ($authorization->token_valido && $id_usuario) {
    try {
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case ApiUtils::POST:
                $conexion->registrarActividad($id_usuario);
                http_response_code(200);
                break;

            case ApiUtils::GET:
                $conexion->getConectados();
                http_response_code(200);
                break;

            default:
                $conexion->status = false;
                $conexion->message = 'Método no soportado.';
                http_response_code(405);
                break;
        }

    } catch (Exception $e) {
        $conexion->status = false;
        $conexion->message = 'Error inesperado en el endpoint de conexión';
        $conexion->data = $e->getMessage();
        http_response_code(500);
    }

} else {
    $conexion->status = false;
    $conexion->message = NO_TOKEN_MESSAGE;
    http_response_code(401);
}

// Respuesta
$response = $api_utils->response($status, $message, $data, $permises);
echo json_encode($response, JSON_PRETTY_PRINT);

