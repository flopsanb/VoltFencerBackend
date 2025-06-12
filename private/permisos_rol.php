<?php
declare(strict_types=1);

/**
 * Endpoint para gestión de permisos por rol
 * 
 * Este script implementa un endpoint RESTful para operaciones CRUD
 * sobre los permisos asignados a los diferentes roles del sistema.
 * 
 * @author  Francisco Lopez
 * @version 1.4
 */

require_once __DIR__ . '/apiClasses/permisos_rol.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);

$authorization = new Authorization();
$authorization->comprobarToken();

$request = json_decode(file_get_contents("php://input"), true);
$id = $_GET['id'] ?? null;

$permiso = new PermisosRol($authorization);

if (!$authorization->token_valido) {
    http_response_code(401);
    $permiso->status = false;
    $permiso->message = NO_TOKEN_MESSAGE;
} else {
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case ApiUtils::GET:
                $authorization->havePermision(ApiUtils::GET, PermisosRol::ROUTE);
                if ($authorization->have_permision) {
                    if ($id && ctype_digit($id)) {
                        $permiso->getById((int)$id);
                        http_response_code($permiso->status ? 200 : 400);
                    } else {
                        http_response_code(400);
                        $permiso->status = false;
                        $permiso->message = 'ID no proporcionado o inválido.';
                    }
                } else {
                    http_response_code(403);
                    $permiso->status = false;
                    $permiso->message = 'No tienes permiso para ver estos permisos.';
                }
                break;

            case ApiUtils::POST:
                $authorization->havePermision(ApiUtils::POST, PermisosRol::ROUTE);
                if ($authorization->have_permision) {
                    $permiso->create($request);
                    http_response_code($permiso->status ? 200 : 400);
                } else {
                    http_response_code(403);
                    $permiso->status = false;
                    $permiso->message = 'No tienes permiso para crear permisos';
                }
                break;

            case ApiUtils::PUT:
                $authorization->havePermision(ApiUtils::PUT, PermisosRol::ROUTE);
                if ($authorization->have_permision) {
                    $permiso->update($request);
                    http_response_code($permiso->status ? 200 : 400);
                } else {
                    http_response_code(403);
                    $permiso->status = false;
                    $permiso->message = 'No tienes permiso para actualizar permisos';
                }
                break;

            case ApiUtils::DELETE:
                $authorization->havePermision(ApiUtils::DELETE, PermisosRol::ROUTE);
                if ($authorization->have_permision) {
                    $permiso->delete($id);
                    http_response_code($permiso->status ? 200 : 400);
                } else {
                    http_response_code(403);
                    $permiso->status = false;
                    $permiso->message = 'No tienes permiso para eliminar permisos';
                }
                break;

            default:
                http_response_code(405);
                $permiso->status = false;
                $permiso->message = 'Método no soportado';
                break;
        }

    } catch (Exception $e) {
        http_response_code(500);
        $permiso->status = false;
        $permiso->message = 'Error inesperado en el endpoint de permisos_rol';
        $permiso->data = $e->getMessage();
    }
}

// Devolver todo siempre igual para el front
$api_utils->response($permiso->status, $permiso->message, $permiso->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
