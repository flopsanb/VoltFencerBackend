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

// Instancia de clase utilitaria para configurar cabeceras y respuestas
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::POST);     // Solo se aceptarán peticiones HTTP de tipo POST

// Si el método no es POST, se devuelve un código 405 (Method Not Allowed)
if ($_SERVER['REQUEST_METHOD'] !== ApiUtils::POST) {
    http_response_code(405);
    $api_utils->response(false, 'Método no permitido. Solo se acepta POST');
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

// Se instancia el sistema de autorización
$authorization = new Authorization();
$authorization->comprobarToken();       // Valida el token extraído del encabezado Authorization

// Se crea una instancia del módulo de soporte, con acceso al usuario autenticado
$soporte = new Soporte($authorization);

// Si el token es inválido, se deniega el acceso y se devuelve un código 401 (Unauthorized)
if (!$authorization->token_valido) {
    http_response_code(401);
    $api_utils->response(false, NO_TOKEN_MESSAGE);
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

// Decodificación de los datos que vengan en formato JSON
$request = json_decode(file_get_contents("php://input"), true);

try {
    // Validación del contenido JSON recibido
    if (!$request || !is_array($request)) {

        // Si los datos están mal formateados o vacíos, se devuelve un error 400
        $soporte->status = false;
        $soporte->message = '❌ Datos inválidos. No se pudo procesar el ticket.';
        http_response_code(400);

    } else {

        // Si los datos son correctos, se intenta registrar el ticket
        $soporte->crearTicket($request);

        // La respuesta dependerá del estado final del proceso de creación
        http_response_code($soporte->status ? 200 : 400);
    }
} catch (Exception $e) {
    // En caso de error no controlado, se devuelve error 500
    http_response_code(500);
    $soporte->status = false;
    $soporte->message = '💥 Error inesperado al crear el ticket';
    $soporte->data = $e->getMessage();
}

// Respuesta final en formato JSON
$api_utils->response($soporte->status, $soporte->message, $soporte->data);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
