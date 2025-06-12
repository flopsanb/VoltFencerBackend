<?php
declare(strict_types=1);

/**
 * Endpoint para consulta de registros de log
 * Requiere token válido y permiso de lectura
 * 
 * Solo se pueden ver logs de empresa si no tienes permisos globales.
 * 
 * @author Francisco
 * @version 2.0
 */

require_once __DIR__ . '/apiClasses/log.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::GET);

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== ApiUtils::GET) {
    http_response_code(405);
    $api_utils->response(false, 'Método no permitido');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

$authorization = new Authorization();
$authorization->comprobarToken();

$log = new Log($authorization);

if (!$authorization->token_valido) {
    http_response_code(401);
    $api_utils->response(false, NO_TOKEN_MESSAGE);
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

// Verificar permiso de lectura de logs
$authorization->havePermision(ApiUtils::GET, Log::ROUTE);

if (!$authorization->have_permision) {
    http_response_code(403);
    $api_utils->response(false, 'No tienes permiso para consultar logs');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

try {
    $log->get();
    http_response_code(200);
} catch (Throwable $e) {
    http_response_code(500);
    $log->status = false;
    $log->message = "Error al recuperar los logs";
    $log->data = $e->getMessage();
}

$api_utils->response($log->status, $log->message, $log->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
