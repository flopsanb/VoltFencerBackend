<?php
declare(strict_types=1);

/**
 * Endpoint para gestión de permisos por rol
 * 
 * Este script implementa un endpoint RESTful para operaciones CRUD
 * sobre los permisos asignados a los diferentes roles del sistema.
 * 
 * @author  Francisco Lopez
 * @version 1.3
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

$permiso = new PermisosRol();

if (!$authorization->token_valido) {
    $permiso->status = false;
    $permiso->message = NO_TOKEN_MESSAGE;
} else {
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case ApiUtils::GET:
                // Solo permitir si se pasa ID y el usuario tiene derecho a verlo
                if ($id && ctype_digit($id)) {
                    if (
                        (int)$id === (int)$authorization->usuario['id_rol'] || 
                        ($authorization->permises['gestionar_usuarios_globales'] ?? 0) === 1
                    ) {
                        $permiso->getById((int)$id);
                    } else {
                        $permiso->status = false;
                        $permiso->message = 'No tienes permiso para ver estos permisos.';
                    }
                } else {
                    $permiso->status = false;
                    $permiso->message = 'ID no proporcionado o inválido.';
                }
                break;

            case ApiUtils::POST:
                $authorization->havePermision(ApiUtils::POST, PermisosRol::ROUTE);
                if ($authorization->have_permision) {
                    $permiso->create($request);
                } else {
                    $permiso->message = 'No tienes permiso para crear permisos';
                }
                break;

            case ApiUtils::PUT:
                $authorization->havePermision(ApiUtils::PUT, PermisosRol::ROUTE);
                if ($authorization->have_permision) {
                    $permiso->update($request);
                } else {
                    $permiso->message = 'No tienes permiso para actualizar permisos';
                }
                break;

            case ApiUtils::DELETE:
                $authorization->havePermision(ApiUtils::DELETE, PermisosRol::ROUTE);
                if ($authorization->have_permision) {
                    $permiso->delete($id);
                } else {
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
}

// Devolver todo siempre igual para el front
$api_utils->response($permiso->status, $permiso->message, $permiso->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
