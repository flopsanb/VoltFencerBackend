<?php
/**
 * Endpoint para consulta de registros de log
 * 
 * Este script permite obtener los registros de actividad del sistema.
 * Solo accesible si el usuario tiene token válido y permiso para ver logs.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.1
 */

require_once(__DIR__ . '/apiClasses/log.php');
require_once(__DIR__ . '/../conn.php');
require_once(__DIR__ . '/../api_utils.php');

/**
 * Inicialización
 */
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::GET);
$api_utils->displayErrors(); // Activar solo si estás en desarrollo

/**
 * Validación de token de sesión
 */
$authorization = new Authorization();
$authorization->comprobarToken();

/**
 * Instancia de la clase Log
 */
$log = new Log();

/**
 * Procesamiento condicional si token es válido
 */
if ($authorization->token_valido) {
    try {
        /**
         * Verificación de permisos explícita (opcional pero recomendable)
         * Por ejemplo: solo superadmin puede consultar todos los logs
         */
        $authorization->havePermision(ApiUtils::GET, 'logs');
        if ($authorization->have_permision) {
            $log->get(); // Puede recibir filtros en el futuro
        } else {
            $log->status = false;
            $log->message = "No tienes permiso para consultar logs.";
        }
    } catch (Exception $e) {
        $log->status = false;
        $log->message = "Error al recuperar los logs";
        $log->data = $e->getMessage();
    }
} else {
    $log->status = false;
    $log->message = NO_TOKEN_MESSAGE;
}

/**
 * Respuesta final
 */
$api_utils->response($log->status, $log->message, $log->data, $authorization->permises ?? []);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
