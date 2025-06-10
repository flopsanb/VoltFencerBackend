<?php
/**
 * Endpoint para consulta de registros de log
 */

require_once __DIR__ . '/apiClasses/log.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::GET);
$api_utils->displayErrors(); // Quitar en producción

// Verificamos método
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    $api_utils->response(false, 'Método no permitido');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

// Autorización
$authorization = new Authorization();
$authorization->comprobarToken();

// Instanciamos log
$log = new Log();

if ($authorization->token_valido) {
    try {
        // Comprobación explícita de permiso
        $authorization->havePermision(ApiUtils::GET, 'logs');

        if ($authorization->have_permision) {
            $log->get(); // En el futuro podrías pasar filtros aquí
            http_response_code(200);
        } else {
            $log->status = false;
            $log->message = "No tienes permiso para consultar logs.";
            http_response_code(403);
        }
    } catch (Exception $e) {
        $log->status = false;
        $log->message = "Error al recuperar los logs";
        $log->data = $e->getMessage();
        http_response_code(500);
    }
} else {
    $log->status = false;
    $log->message = NO_TOKEN_MESSAGE;
    http_response_code(401);
}

// Respuesta
$api_utils->response($log->status, $log->message, $log->data, $authorization->permises ?? []);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
