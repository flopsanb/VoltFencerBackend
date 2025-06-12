<?php
declare(strict_types=1);

/**
 * Endpoint para gestión de proyectos
 * CRUD RESTful con validación de token y permisos
 * 
 * @author  Francisco Lopez
 * @version 1.5
 */

require_once __DIR__ . '/apiClasses/proyecto.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);

error_log("[🛠️ PROYECTO] Iniciando endpoint...");

$authorization = new Authorization();
$authorization->comprobarToken();

$proyecto = new Proyecto($authorization);
$request = json_decode(file_get_contents("php://input"), true);
$id = $_GET['id'] ?? null;

if (!$authorization->token_valido) {
    error_log("[❌ PROYECTO] Token inválido o ausente.");
    http_response_code(401);
    $api_utils->response(false, NO_TOKEN_MESSAGE);
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $permises = $authorization->permises;

    error_log("[📥 PROYECTO] Método: $method");
    error_log("[📦 PROYECTO] Request: " . json_encode($request));
    error_log("[🔑 PROYECTO] ID (GET param): " . var_export($id, true));
    error_log("[🔐 PROYECTO] Permisos: " . json_encode($permises));

    switch ($method) {
        case ApiUtils::GET:
            $authorization->havePermision(ApiUtils::GET, Proyecto::ROUTE);
            if ($authorization->have_permision) {
                error_log("[✅ PROYECTO] Permiso GET concedido");
                $proyecto->get();
                http_response_code(200);
            } else {
                error_log("[⛔ PROYECTO] Permiso GET denegado");
                http_response_code(403);
                $proyecto->status = false;
                $proyecto->message = 'No tienes permiso para ver proyectos.';
            }
            break;

        case ApiUtils::POST:
            $authorization->havePermision(ApiUtils::POST, Proyecto::ROUTE);
            if ($authorization->have_permision) {
                error_log("[✅ PROYECTO] Permiso POST concedido");
                $proyecto->create($request);
                http_response_code($proyecto->status ? 200 : 400);
            } else {
                error_log("[⛔ PROYECTO] Permiso POST denegado");
                http_response_code(403);
                $proyecto->status = false;
                $proyecto->message = 'No tienes permiso para crear proyectos.';
            }
            break;

        case ApiUtils::PUT:
            $authorization->havePermision(ApiUtils::PUT, Proyecto::ROUTE);
            if ($authorization->have_permision) {
                error_log("[✅ PROYECTO] Permiso PUT concedido");
                $proyecto->update($request);
                http_response_code($proyecto->status ? 200 : 400);
            } else {
                error_log("[⛔ PROYECTO] Permiso PUT denegado");
                http_response_code(403);
                $proyecto->status = false;
                $proyecto->message = 'No tienes permiso para modificar proyectos.';
            }
            break;

        case ApiUtils::DELETE:
            $authorization->havePermision(ApiUtils::DELETE, Proyecto::ROUTE);
            if ($authorization->have_permision) {
                error_log("[✅ PROYECTO] Permiso DELETE concedido");
                $proyecto->delete($id);
                http_response_code($proyecto->status ? 200 : 400);
            } else {
                error_log("[⛔ PROYECTO] Permiso DELETE denegado");
                http_response_code(403);
                $proyecto->status = false;
                $proyecto->message = 'No tienes permiso para eliminar proyectos.';
            }
            break;

        default:
            error_log("[❗ PROYECTO] Método HTTP no soportado: $method");
            http_response_code(405);
            $proyecto->status = false;
            $proyecto->message = 'Método HTTP no soportado.';
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    $proyecto->status = false;
    $proyecto->message = 'Error inesperado en el endpoint de proyecto';
    $proyecto->data = $e->getMessage();
    error_log("[🔥 ERROR PROYECTO] Excepción: " . $e->getMessage());
}

$api_utils->response($proyecto->status, $proyecto->message, $proyecto->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
error_log("[📤 PROYECTO] Respuesta: " . json_encode($api_utils->response, JSON_PRETTY_PRINT));
