<?php
declare(strict_types=1);

/**
 * Endpoint para gestión de tickets de soporte
 * 
 * Solo permite POST autenticado para crear un nuevo ticket.
 * 
 * @author Francisco
 * @version 1.3
 */

require_once __DIR__ . '/apiClasses/soporte.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

// Inicializar utilidades
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);

// Validar método HTTP
if ($_SERVER['REQUEST_METHOD'] !== ApiUtils::POST) {
    http_response_code(405);
    $api_utils->response(false, 'Método no permitido. Solo se acepta POST');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

// Autenticación
$authorization = new Authorization();
$authorization->comprobarToken();

$soporte = new Soporte($authorization);

// Validar token
if (!$authorization->token_valido) {
    http_response_code(401);
    $api_utils->response(false, NO_TOKEN_MESSAGE);
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

// Decodificación del body
$request = json_decode(file_get_contents("php://input"), true);

try {
    if (!$request || !is_array($request)) {
        $soporte->status = false;
        $soporte->message = '❌ Datos inválidos. No se pudo procesar el ticket.';
        http_response_code(400);
    } else {
        $soporte->crearTicket($request);
        http_response_code($soporte->status ? 200 : 400);
    }
} catch (Exception $e) {
    http_response_code(500);
    $soporte->status = false;
    $soporte->message = '💥 Error inesperado al crear el ticket';
    $soporte->data = $e->getMessage();
}

// Respuesta final
$api_utils->response($soporte->status, $soporte->message, $soporte->data);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
