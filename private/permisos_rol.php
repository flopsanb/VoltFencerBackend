<?php
declare(strict_types=1);

/**
 * Endpoint para gestión de permisos por rol
 * 
 * Este script implementa un endpoint RESTful para operaciones CRUD
 * sobre los permisos asignados a los diferentes roles del sistema.
 * 
 * @author  Francisco Lopez
 * @version 1.2
 */

require_once __DIR__ . '/apiClasses/permisos_rol.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);
$api_utils->displayErrors();

$authorization = new Authorization();
$authorization->comprobarToken();

$permiso = new PermisosRol();
$id = $_GET['id'] ?? null;
$request = json_decode(file_get_contents("php://input"), true);

if ($authorization->token_valido) {
    try {
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {

            case ApiUtils::GET:
                $permiso->data = $permiso->get();
                $authorization->getPermision(PermisosRol::ROUTE);
                break;

            case ApiUtils::POST:
                $authorization->havePermision(ApiUtils::POST, PermisosRol::ROUTE);
                if ($authorization->have_permision) {
                    $permiso->create($request);
                } else {
                    $permiso->status = false;
                    $permiso->message = 'No tienes permiso para crear permisos';
                    http_response_code(403);
                }
                break;

            case ApiUtils::PUT:
                $authorization->havePermision(ApiUtils::PUT, PermisosRol::ROUTE);
                if ($authorization->have_permision) {
                    $permiso->update($request);
                } else {
                    $permiso->status = false;
                    $permiso->message = 'No tienes permiso para actualizar permisos';
                    http_response_code(403);
                }
                break;

            case ApiUtils::DELETE:
                $authorization->havePermision(ApiUtils::DELETE, PermisosRol::ROUTE);
                if ($authorization->have_permision) {
                    $permiso->delete($id);
                } else {
                    $permiso->status = false;
                    $permiso->message = 'No tienes permiso para eliminar permisos';
                    http_response_code(403);
                }
                break;

            default:
                $permiso->status = false;
                $permiso->message = 'Método no soportado';
                http_response_code(405);
                break;
        }

    } catch (Exception $e) {
        $permiso->status = false;
        $permiso->message = 'Error inesperado en el endpoint de permisos_rol';
        $permiso->data = $e->getMessage();
        http_response_code(500);
    }

} else {
    $permiso->status = false;
    $permiso->message = defined('NO_TOKEN_MESSAGE') ? NO_TOKEN_MESSAGE : 'Token inválido o ausente.';
    http_response_code(401);
}

$response = $api_utils->response($status, $message, $data, $permises);
echo json_encode($response, JSON_PRETTY_PRINT);

exit;
