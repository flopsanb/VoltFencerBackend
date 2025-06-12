<?php
declare(strict_types=1);

/**
 * Endpoint para gestión de roles del sistema
 * 
 * CRUD completo con control de permisos y token obligatorio.
 * 
 * @author Francisco
 * @version 1.4
 */

require_once __DIR__ . '/apiClasses/rol.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

// Inicialización
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);

$authorization = new Authorization();
$authorization->comprobarToken();

$rol = new Rol($authorization);
$id = $_GET["id"] ?? null;
$request = json_decode(file_get_contents("php://input"), true);

if (!$authorization->token_valido) {
    http_response_code(401);
    $api_utils->response(false, NO_TOKEN_MESSAGE);
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case ApiUtils::GET:
            $authorization->havePermision(ApiUtils::GET, Rol::ROUTE);
            if ($authorization->have_permision) {
                $rol->get();
                http_response_code(200);
            } else {
                http_response_code(403);
                $rol->status = false;
                $rol->message = 'No tienes permiso para ver los roles.';
            }
            break;

        case ApiUtils::POST:
            $authorization->havePermision(ApiUtils::POST, Rol::ROUTE);
            if ($authorization->have_permision) {
                $rol->create($request);
                http_response_code($rol->status ? 200 : 400);
            } else {
                http_response_code(403);
                $rol->status = false;
                $rol->message = ADD_ROL_NOT_PERMISION;
            }
            break;

        case ApiUtils::PUT:
            $authorization->havePermision(ApiUtils::PUT, Rol::ROUTE);
            if ($authorization->have_permision) {
                $rol->update($request);
                http_response_code($rol->status ? 200 : 400);
            } else {
                http_response_code(403);
                $rol->status = false;
                $rol->message = EDIT_ROL_NOT_PERMISION;
            }
            break;

        case ApiUtils::DELETE:
            $authorization->havePermision(ApiUtils::DELETE, Rol::ROUTE);
            if ($authorization->have_permision) {
                $rol->delete($id);
                http_response_code($rol->status ? 200 : 400);
            } else {
                http_response_code(403);
                $rol->status = false;
                $rol->message = DELETE_ROL_NOT_PERMISION;
            }
            break;

        default:
            http_response_code(405);
            $rol->status = false;
            $rol->message = 'Método HTTP no soportado';
    }

} catch (Exception $e) {
    http_response_code(500);
    $rol->status = false;
    $rol->message = 'Error inesperado en el endpoint de rol';
    $rol->data = $e->getMessage();
}

$api_utils->response($rol->status, $rol->message, $rol->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
