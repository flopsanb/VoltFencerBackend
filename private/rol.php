<?php
declare(strict_types=1);

/**
 * Endpoint para gestión de roles
 * 
 * Este script implementa un endpoint RESTful para operaciones CRUD
 * sobre los roles del sistema. Requiere token de autorización válido
 * y permisos específicos para cada operación.
 * 
 * @author  Francisco Lopez
 * @version 1.2
 */

require_once __DIR__ . '/apiClasses/rol.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);



$authorization = new Authorization();
$authorization->comprobarToken();

$request = json_decode(file_get_contents("php://input"), true);

$rol = new Rol();
$id = $_GET["id"] ?? null;

if ($authorization->token_valido) {
    try {
        switch ($_SERVER['REQUEST_METHOD']) {

            case ApiUtils::GET:
                $rol->get();
                $authorization->getPermision(Rol::ROUTE);
                break;

            case ApiUtils::POST:
                $authorization->havePermision(ApiUtils::POST, Rol::ROUTE);
                if ($authorization->have_permision) {
                    $rol->create($request);
                } else {
                    $rol->message = ADD_ROL_NOT_PERMISION;
                }
                break;

            case ApiUtils::PUT:
                $authorization->havePermision(ApiUtils::PUT, Rol::ROUTE);
                if ($authorization->have_permision) {
                    $rol->update($request);
                } else {
                    $rol->message = EDIT_ROL_NOT_PERMISION;
                }
                break;

            case ApiUtils::DELETE:
                $authorization->havePermision(ApiUtils::DELETE, Rol::ROUTE);
                if ($authorization->have_permision) {
                    $rol->delete($id);
                } else {
                    $rol->message = DELETE_ROL_NOT_PERMISION;
                }
                break;

            default:
                $rol->message = "Método no soportado";
                break;
        }
    } catch (Exception $e) {
        $rol->status = false;
        $rol->message = "Error inesperado en el endpoint de rol";
        $rol->data = $e->getMessage();
    }

} else {
    $rol->status = false;
    $rol->message = NO_TOKEN_MESSAGE;
}

$api_utils->response($rol->status, $rol->message, $rol->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
