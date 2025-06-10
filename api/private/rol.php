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
$api_utils->displayErrors();

$authorization = new Authorization();
$authorization->comprobarToken();

$request = json_decode(file_get_contents("php://input"), true);
$id      = $_GET["id"] ?? null;
$rol     = new Rol();

if ($authorization->token_valido) {
    try {
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case ApiUtils::GET:
                $rol->data = $rol->get();  // Asignar resultado
                $authorization->getPermision(Rol::ROUTE);
                break;

            case ApiUtils::POST:
                $authorization->havePermision(ApiUtils::POST, Rol::ROUTE);
                if ($authorization->have_permision) {
                    $rol->create($request);
                } else {
                    $rol->status  = false;
                    $rol->message = defined('ADD_ROL_NOT_PERMISION') ? ADD_ROL_NOT_PERMISION : 'No tienes permiso para añadir roles.';
                    http_response_code(403);
                }
                break;

            case ApiUtils::PUT:
                $authorization->havePermision(ApiUtils::PUT, Rol::ROUTE);
                if ($authorization->have_permision) {
                    $rol->update($request);
                } else {
                    $rol->status  = false;
                    $rol->message = defined('EDIT_ROL_NOT_PERMISION') ? EDIT_ROL_NOT_PERMISION : 'No tienes permiso para editar roles.';
                    http_response_code(403);
                }
                break;

            case ApiUtils::DELETE:
                $authorization->havePermision(ApiUtils::DELETE, Rol::ROUTE);
                if ($authorization->have_permision) {
                    $rol->delete($id);
                } else {
                    $rol->status  = false;
                    $rol->message = defined('DELETE_ROL_NOT_PERMISION') ? DELETE_ROL_NOT_PERMISION : 'No tienes permiso para eliminar roles.';
                    http_response_code(403);
                }
                break;

            default:
                $rol->status  = false;
                $rol->message = "Método no soportado";
                http_response_code(405);
        }

    } catch (Exception $e) {
        $rol->status  = false;
        $rol->message = "Error inesperado en el endpoint de rol";
        $rol->data    = $e->getMessage();
        http_response_code(500);
    }

} else {
    $rol->status  = false;
    $rol->message = defined('NO_TOKEN_MESSAGE') ? NO_TOKEN_MESSAGE : 'Token inválido o ausente.';
    http_response_code(401);
}

$api_utils->response($rol->status, $rol->message, $rol->data ?? null, $authorization->permises ?? []);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
exit;
