<?php
/**
 * Endpoint para gestión de relaciones entre roles y menús
 * 
 * Este script implementa un endpoint RESTful para operaciones CRUD
 * sobre la asignación de menús a roles. Valida el token y verifica
 * permisos antes de realizar cualquier acción.
 * 
 * @author  Francisco Lopez
 * @version 1.1
 */

require_once __DIR__ . '/apiClasses/rol_menu.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);
$api_utils->displayErrors();

$authorization = new Authorization();
$authorization->comprobarToken();

$request = json_decode(file_get_contents("php://input"), true);
$id = $_GET['id'] ?? null;

$rol_menu = new RolMenu();

if ($authorization->token_valido) {
    try {
        switch ($_SERVER['REQUEST_METHOD']) {

            case ApiUtils::GET:
                $rol_menu->get();
                $authorization->getPermision(RolMenu::ROUTE);
                break;

            case ApiUtils::POST:
                $authorization->havePermision(ApiUtils::POST, RolMenu::ROUTE);
                if ($authorization->have_permision) {
                    $rol_menu->create($request);
                } else {
                    $rol_menu->status = false;
                    $rol_menu->message = ADD_ROL_MENU_NOT_PERMISION;
                }
                break;

            case ApiUtils::PUT:
                $authorization->havePermision(ApiUtils::PUT, RolMenu::ROUTE);
                if ($authorization->have_permision) {
                    $rol_menu->update($request);
                } else {
                    $rol_menu->status = false;
                    $rol_menu->message = EDIT_ROL_MENU_NOT_PERMISION;
                }
                break;

            case ApiUtils::DELETE:
                $authorization->havePermision(ApiUtils::DELETE, RolMenu::ROUTE);
                if ($authorization->have_permision) {
                    $rol_menu->delete($id);
                } else {
                    $rol_menu->status = false;
                    $rol_menu->message = DELETE_ROL_MENU_NOT_PERMISION;
                }
                break;

            default:
                $rol_menu->status = false;
                $rol_menu->message = 'Método no soportado';
                break;
        }

    } catch (Exception $e) {
        $rol_menu->status = false;
        $rol_menu->message = 'Error inesperado en el endpoint de rol_menu';
        $rol_menu->data = $e->getMessage();
    }

} else {
    $rol_menu->status = false;
    $rol_menu->message = NO_TOKEN_MESSAGE;
}

$api_utils->response($rol_menu->status, $rol_menu->message, $rol_menu->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
?>
