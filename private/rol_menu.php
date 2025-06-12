<?php
declare(strict_types=1);

/**
 * Endpoint para gestión de relaciones entre roles y menús
 * CRUD completo con control de token y permisos
 * 
 * @author Francisco
 * @version 1.4
 */

require_once __DIR__ . '/apiClasses/rol_menu.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

// Init
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);

$authorization = new Authorization();
$authorization->comprobarToken();

$rol_menu = new RolMenu();
$request = json_decode(file_get_contents("php://input"), true);
$id = $_GET['id'] ?? null;

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
            $authorization->havePermision(ApiUtils::GET, RolMenu::ROUTE);
            if ($authorization->have_permision) {
                $rol_menu->get();
                http_response_code(200);
            } else {
                http_response_code(403);
                $rol_menu->status = false;
                $rol_menu->message = 'No tienes permiso para ver los menús de rol.';
            }
            break;

        case ApiUtils::POST:
            $authorization->havePermision(ApiUtils::POST, RolMenu::ROUTE);
            if ($authorization->have_permision) {
                $rol_menu->create($request);
                http_response_code($rol_menu->status ? 200 : 400);
            } else {
                http_response_code(403);
                $rol_menu->status = false;
                $rol_menu->message = ADD_ROL_MENU_NOT_PERMISION;
            }
            break;

        case ApiUtils::PUT:
            $authorization->havePermision(ApiUtils::PUT, RolMenu::ROUTE);
            if ($authorization->have_permision) {
                $rol_menu->update($request);
                http_response_code($rol_menu->status ? 200 : 400);
            } else {
                http_response_code(403);
                $rol_menu->status = false;
                $rol_menu->message = EDIT_ROL_MENU_NOT_PERMISION;
            }
            break;

        case ApiUtils::DELETE:
            $authorization->havePermision(ApiUtils::DELETE, RolMenu::ROUTE);
            if ($authorization->have_permision) {
                $rol_menu->delete($id);
                http_response_code($rol_menu->status ? 200 : 400);
            } else {
                http_response_code(403);
                $rol_menu->status = false;
                $rol_menu->message = DELETE_ROL_MENU_NOT_PERMISION;
            }
            break;

        default:
            http_response_code(405);
            $rol_menu->status = false;
            $rol_menu->message = 'Método HTTP no soportado';
    }

} catch (Exception $e) {
    http_response_code(500);
    $rol_menu->status = false;
    $rol_menu->message = 'Error inesperado en el endpoint de rol_menu';
    $rol_menu->data = $e->getMessage();
}

$api_utils->response($rol_menu->status, $rol_menu->message, $rol_menu->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
