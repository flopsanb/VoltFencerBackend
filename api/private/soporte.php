<?php
/**
 * Endpoint para gestión de tickets de soporte
 * 
 * RESTful endpoint para la creación de tickets de soporte técnico.
 * Solo permite POST y requiere token de autenticación válido.
 * 
 * @author Francisco
 * @version 1.1
 */

require_once(__DIR__ . '/apiClasses/soporte.php');
require_once(__DIR__ . '/../conn.php');
require_once(__DIR__ . '/../api_utils.php');

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
        switch ($_SERVER['REQUEST_METHOD']) {
            case ApiUtils::POST:
                error_log("[➡️] Método POST recibido");
                $soporte->crearTicket($request);
                break;
            default:
                $soporte->status = false;
                $soporte->message = 'Método no soportado';
                error_log("[🚫] Método no soportado: " . $_SERVER['REQUEST_METHOD']);
        }
    } catch (Exception $e) {
        $soporte->status = false;
        $soporte->message = 'Error inesperado en el endpoint de soporte';
        $soporte->data = $e->getMessage();
        error_log("[💥] Excepción atrapada: " . $e->getMessage());
    }
} else {
    $soporte->status = false;
    $soporte->message = NO_TOKEN_MESSAGE;
    error_log("[⛔] Token inválido. Acceso denegado.");
}

$api_utils->response($soporte->status, $soporte->message, $soporte->data);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
?>
