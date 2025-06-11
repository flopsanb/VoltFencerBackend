<?php
declare(strict_types=1);
/**
 * Endpoint RESTful para gestión de usuarios (CRUD)
 */

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';
require_once __DIR__ . '/apiClasses/usuario.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);

$authorization = new Authorization();
$authorization->comprobarToken();

$usuario = new Usuario();
$request = json_decode(file_get_contents("php://input"), true);
$id      = $_GET['id'] ?? null;
$route   = $_GET['route'] ?? null;

if (!$authorization->token_valido) {
    http_response_code(401);
    $api_utils->response(false, NO_TOKEN_MESSAGE);
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

try {
    $permisos         = $authorization->permises;
    $rol_usuario      = $authorization->usuario['rol'] ?? '';
    $id_empresa_token = $authorization->usuario['id_empresa'] ?? null;

    switch ($_SERVER['REQUEST_METHOD']) {
        case ApiUtils::GET:
            if (
                ($permisos['gestionar_usuarios_globales'] ?? 0) === 1 ||
                ($permisos['ver_usuarios_empresa'] ?? 0) === 1
            ) {
                $usuario->get();
            } else {
                $usuario->status  = false;
                $usuario->message = 'No tienes permisos para ver los usuarios';
            }
            break;

        case ApiUtils::POST:
            if (
                ($permisos['gestionar_usuarios_globales'] ?? 0) === 1 ||
                ($permisos['gestionar_usuarios_empresa'] ?? 0) === 1
            ) {
                if ($rol_usuario === 'admin_empresa') {
                    if (!isset($request['id_empresa']) || $request['id_empresa'] != $id_empresa_token) {
                        $usuario->status = false;
                        $usuario->message = 'No puedes crear usuarios fuera de tu empresa';
                        break;
                    }
                    if (!isset($request['id_rol']) || !in_array((int)$request['id_rol'], [3, 4])) {
                        $usuario->status = false;
                        $usuario->message = 'No puedes asignar ese rol';
                        break;
                    }
                }
                $usuario->create($request);
            } else {
                $usuario->status = false;
                $usuario->message = ADD_USER_NOT_PERMISION;
            }
            break;

        case ApiUtils::PUT:
            if ($route === Usuario::ROUTE_PROFILE) {
                $usuario->updateProfile($request, $authorization->token);
                break;
            }

            if (
                ($permisos['gestionar_usuarios_globales'] ?? 0) === 1 ||
                ($permisos['gestionar_usuarios_empresa'] ?? 0) === 1
            ) {
                if ($rol_usuario === 'admin_empresa') {
                    if (!isset($request['id_empresa']) || $request['id_empresa'] != $id_empresa_token) {
                        $usuario->status = false;
                        $usuario->message = 'No puedes editar usuarios fuera de tu empresa';
                        break;
                    }
                    if (!isset($request['id_rol']) || !in_array((int)$request['id_rol'], [3, 4])) {
                        $usuario->status = false;
                        $usuario->message = 'No puedes cambiar a un rol superior al tuyo';
                        break;
                    }
                }
                $usuario->update($request);
            } else {
                $usuario->status = false;
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
                $usuario->status = false;
                $usuario->message = DELETE_USER_NOT_PERMISION;
            }
            break;

        default:
            http_response_code(405);
            $usuario->status = false;
            $usuario->message = 'Método no permitido';
    }

} catch (Throwable $e) {
    http_response_code(500);
    $usuario->status = false;
    $usuario->message = 'Error interno en la gestión de usuarios';
    $usuario->data = null; // No mostrar trazas
}

// Respuesta final
$api_utils->response(
    $usuario->status,
    $usuario->message,
    $usuario->data ?? null,
    $authorization->permises
);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
