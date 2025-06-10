<?php
declare(strict_types=1);
/**
 * Endpoint para gestión de usuarios (CRUD)
 * RESTful para operaciones sobre entidad Usuario.
 */

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';
require_once __DIR__ . '/apiClasses/usuario.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);



$authorization = new Authorization();
$authorization->comprobarToken();

$GLOBALS['authorization'] = $authorization;

$request = json_decode(file_get_contents("php://input"), true);

$usuario = new Usuario();

$id = $_GET['id'] ?? null;
$route = $_GET['route'] ?? null;

if ($authorization->token_valido) {
    try {
        $permisos = $authorization->permises;
        $es_superadmin = $authorization->usuario['rol'] === 'superadmin';
        $es_admin = $authorization->usuario['rol'] === 'admin';
        $es_admin_empresa = $authorization->usuario['rol'] === 'admin_empresa';
        $id_empresa_token = $authorization->usuario['id_empresa'];

        switch ($_SERVER['REQUEST_METHOD']) {
            case ApiUtils::GET:
                if (
                    ($permisos['gestionar_usuarios_globales'] ?? 0) === 1 ||
                    ($permisos['ver_usuarios_empresa'] ?? 0) === 1
                ) {
                    $usuario->get();
                } else {
                    $usuario->status = false;
                    $usuario->message = 'No tienes permisos para ver los usuarios';
                }
                break;

            case ApiUtils::POST:
                if (
                    ($permisos['gestionar_usuarios_globales'] ?? 0) === 1 ||
                    ($permisos['gestionar_usuarios_empresa'] ?? 0) === 1
                ) {
                    if ($es_admin_empresa && $request['id_empresa'] != $id_empresa_token) {
                        $usuario->status = false;
                        $usuario->message = 'No puedes crear usuarios fuera de tu empresa';
                    } elseif ($es_admin_empresa && !in_array($request['id_rol'], [3, 4])) {
                        $usuario->status = false;
                        $usuario->message = 'No puedes asignar ese rol';
                    } else {
                        $usuario->create($request);
                    }
                } else {
                    $usuario->message = ADD_USER_NOT_PERMISION;
                }
                break;

            case ApiUtils::PUT:
                if ($route === Usuario::ROUTE_PROFILE) {
                    $usuario->updateProfile($request, $authorization->token);
                } elseif (
                    ($permisos['gestionar_usuarios_globales'] ?? 0) === 1 ||
                    ($permisos['gestionar_usuarios_empresa'] ?? 0) === 1
                ) {
                    if ($es_admin_empresa && $request['id_empresa'] != $id_empresa_token) {
                        $usuario->status = false;
                        $usuario->message = 'No puedes editar usuarios fuera de tu empresa';
                    } elseif ($es_admin_empresa && !in_array($request['id_rol'], [3, 4])) {
                        $usuario->status = false;
                        $usuario->message = 'No puedes cambiar a un rol superior al tuyo';
                    } else {
                        $usuario->update($request);
                    }
                } else {
                    $usuario->message = EDIT_USER_NOT_PERMISION;
                }
                break;

            case ApiUtils::DELETE:
                if (
                    ($permisos['gestionar_usuarios_globales'] ?? 0) === 1 ||
                    ($permisos['gestionar_usuarios_empresa'] ?? 0) === 1
                ) {
                    $usuario->delete($id);
                } else {
                    $usuario->message = DELETE_USER_NOT_PERMISION;
                }
                break;

            default:
                $usuario->message = 'Método no soportado';
        }

    } catch (Exception $e) {
        $usuario->status = false;
        $usuario->message = 'Error inesperado en el endpoint de usuario';
        $usuario->data = $e->getMessage();
    }

} else {
    $usuario->status = false;
    $usuario->message = NO_TOKEN_MESSAGE;
}

$api_utils->response($usuario->status, $usuario->message, $usuario->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
