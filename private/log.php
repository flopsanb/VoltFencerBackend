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
    $response = $api_utils->response($status, $message, $data, $permises);
echo json_encode($response, JSON_PRETTY_PRINT);

    exit;
}

// Autorización
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::GET);
$api_utils->displayErrors();

$authorization = new Authorization();
$authorization->comprobarToken();

$log = new Log();

if ($authorization->token_valido) {
    try {
        $log->get();
    } catch (Exception $e) {
        $log->status = false;
        $log->message = "Error al recuperar los logs";
        $log->data = $e->getMessage();
    }
} else {
    $log->status = false;
    $log->message = NO_TOKEN_MESSAGE;
}

$api_utils->response($log->status, $log->message, $log->data);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);


