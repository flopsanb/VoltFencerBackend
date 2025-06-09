<?php
/**
 * Endpoint para gestión de estado de conexión de usuarios
 * 
 * Permite registrar actividad (ping de conexión) y obtener
 * lista de usuarios conectados, según su última actividad.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.1
 */

require_once(__DIR__ . '/apiClasses/estado_conexion.php');
require_once(__DIR__ . '/../conn.php');
require_once(__DIR__ . '/../api_utils.php');

/**
 * Inicialización del entorno
 */
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);
$api_utils->displayErrors(); // Comenta esta línea en producción

/**
 * Validación del token
 */
$authorization = new Authorization();
$authorization->comprobarToken();

/**
 * Instancias necesarias
 */
$request = json_decode(file_get_contents("php://input"), true);
$conexion = new EstadoConexion();
$id_usuario = $authorization->id_usuario ?? null;

/**
 * Lógica de procesamiento
 */
if ($authorization->token_valido && $id_usuario) {
    try {
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case ApiUtils::POST:
                $conexion->registrarActividad($id_usuario);
                break;

            case ApiUtils::GET:
                $conexion->getConectados();
                break;

            default:
                $conexion->status = false;
                $conexion->message = 'Método no soportado.';
                break;
        }

    } catch (Exception $e) {
        $conexion->status = false;
        $conexion->message = 'Error inesperado en el endpoint de conexión';
        $conexion->data = $e->getMessage();
    }

} else {
    $conexion->status = false;
    $conexion->message = NO_TOKEN_MESSAGE;
}

/**
 * Envío de respuesta
 */
$api_utils->response($conexion->status, $conexion->message, $conexion->data);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
