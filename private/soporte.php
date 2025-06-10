<?php
declare(strict_types=1);

/**
 * Endpoint para gestión de tickets de soporte
 * 
 * RESTful endpoint para la creación de tickets de soporte técnico.
 * Solo permite POST y requiere token de autenticación válido.
 * 
 * @author Francisco
 * @version 1.2
 */

require_once __DIR__ . '/apiClasses/soporte.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);

$authorization = new Authorization();
$authorization->comprobarToken();

$GLOBALS['authorization'] = $authorization;

// Decodificamos
$inputRaw = file_get_contents("php://input");

$request = json_decode($inputRaw, true);

$soporte = new Soporte();

if ($authorization->token_valido) {
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case ApiUtils::POST:
                $soporte->crearTicket($request);
                break;
            default:
                $soporte->status = false;
                $soporte->message = 'Método no soportado';
        }
    } catch (Exception $e) {
        $soporte->status = false;
        $soporte->message = 'Error inesperado en el endpoint de soporte';
        $soporte->data = $e->getMessage();
    }
} else {
    $soporte->status = false;
    $soporte->message = NO_TOKEN_MESSAGE;
}

$api_utils->response($soporte->status, $soporte->message, $soporte->data);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
