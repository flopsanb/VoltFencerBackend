<?php
/**
 * Endpoint para gestión de permisos por rol
 * 
 * Este script implementa un endpoint RESTful para operaciones CRUD
 * sobre los permisos asignados a los diferentes roles del sistema.
 * 
 * @author  Francisco Lopez
 * @version 1.1
 */

require_once(__DIR__ . '/apiClasses/permisos_rol.php');
require_once(__DIR__ . '/../conn.php');
require_once(__DIR__ . '/../api_utils.php');

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
        switch ($_SERVER['REQUEST_METHOD']) {

            case ApiUtils::GET:
                $permiso->get();
                $authorization->getPermision(PermisosRol::ROUTE);
                break;

            case ApiUtils::POST:
                $authorization->havePermision(ApiUtils::POST, PermisosRol::ROUTE);
                if ($authorization->have_permision) {
                    $permiso->create($request);
                } else {
                    $permiso->status = false;
                    $permiso->message = 'No tienes permiso para crear permisos';
                }
                break;

            case ApiUtils::PUT:
                $authorization->havePermision(ApiUtils::PUT, PermisosRol::ROUTE);
                if ($authorization->have_permision) {
                    $permiso->update($request);
                } else {
                    $permiso->status = false;
                    $permiso->message = 'No tienes permiso para actualizar permisos';
                }
                break;

            case ApiUtils::DELETE:
                $authorization->havePermision(ApiUtils::DELETE, PermisosRol::ROUTE);
                if ($authorization->have_permision) {
                    $permiso->delete($id);
                } else {
                    $permiso->status = false;
                    $permiso->message = 'No tienes permiso para eliminar permisos';
                }
                break;

            default:
                $permiso->status = false;
                $permiso->message = 'Método no soportado';
                break;
        }

    } catch (Exception $e) {
        $permiso->status = false;
        $permiso->message = 'Error inesperado en el endpoint de permisos_rol';
        $permiso->data = $e->getMessage();
    }

} else {
    $permiso->status = false;
    $permiso->message = NO_TOKEN_MESSAGE;
}

$api_utils->response($permiso->status, $permiso->message, $permiso->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
