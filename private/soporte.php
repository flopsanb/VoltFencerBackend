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
$api_utils->displayErrors();

error_log("[📩] Petición recibida en soporte.php");

$authorization = new Authorization();
$authorization->comprobarToken();
$GLOBALS['authorization'] = $authorization;

$inputRaw = file_get_contents("php://input");
error_log("[📦] JSON crudo recibido: " . $inputRaw);

$request = json_decode($inputRaw, true);
error_log("[🔍] JSON decodificado: " . json_encode($request));

$soporte = new Soporte();

if ($authorization->token_valido) {
    error_log("[🔐] Token válido. Procesando...");
    try {
        if ($_SERVER['REQUEST_METHOD'] === ApiUtils::POST) {
            error_log("[➡️] Método POST recibido");
            $soporte->crearTicket($request);
        } else {
            $soporte->status = false;
            $soporte->message = 'Método no soportado';
            error_log("[🚫] Método no soportado: " . $_SERVER['REQUEST_METHOD']);
            http_response_code(405);
        }
    } catch (Exception $e) {
        $soporte->status = false;
        $soporte->message = 'Error inesperado en el endpoint de soporte';
        $soporte->data = $e->getMessage();
        error_log("[💥] Excepción atrapada: " . $e->getMessage());
        http_response_code(500);
    }
} else {
    $soporte->status = false;
    $soporte->message = defined('NO_TOKEN_MESSAGE') ? NO_TOKEN_MESSAGE : 'Token inválido o ausente';
    error_log("[⛔] Token inválido. Acceso denegado.");
    http_response_code(401);
}

$response = $api_utils->response($status, $message, $data, $permises);
echo json_encode($response, JSON_PRETTY_PRINT);

exit;
