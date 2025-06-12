<?php
declare(strict_types=1);

/**
 * Endpoint RESTful para gestión de usuarios (CRUD)
 * Control de permisos refinado y validaciones adicionales
 * 
 * @author Francisco
 * @version 1.5
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
    $method           = $_SERVER['REQUEST_METHOD'];
    $id_rol_token     = $authorization->usuario['id_rol'] ?? null;
    $id_empresa_token = $authorization->usuario['id_empresa'] ?? null;

    // Verificación de permisos antes de procesar
    $authorization->havePermision($method, Usuario::ROUTE);

    switch ($method) {
        case ApiUtils::GET:
            if ($authorization->have_permision) {
                $usuario->get();
                http_response_code(200);
            } else {
                http_response_code(403);
                $usuario->status = false;
                $usuario->message = VIEW_USER_NOT_PERMISION;
            }
            break;

        case ApiUtils::POST:
            if ($authorization->have_permision) {
                if ((int)$id_rol_token === 3) {
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
                http_response_code($usuario->status ? 200 : 400);
            } else {
                http_response_code(403);
                $usuario->status = false;
                $usuario->message = ADD_USER_NOT_PERMISION;
            }
            break;

        case ApiUtils::PUT:
            if ($route === Usuario::ROUTE_PROFILE) {
                $usuario->updateProfile($request, $authorization->token);
                http_response_code(200);
                break;
            }

            if ($authorization->have_permision) {
                if ((int)$id_rol_token === 3) {
                    if (!isset($request['id_empresa']) || $request['id_empresa'] != $id_empresa_token) {
                        $usuario->status = false;
                        $usuario->message = 'No puedes editar usuarios fuera de tu empresa';
                        break;
                    }
                    if (!isset($request['id_rol']) || !in_array((int)$request['id_rol'], [3, 4])) {
                        $usuario->status = false;
                        $usuario->message = 'No puedes asignar un rol superior al tuyo';
                        break;
                    }
                }
                $usuario->update($request);
                http_response_code($usuario->status ? 200 : 400);
            } else {
                http_response_code(403);
                $usuario->status = false;
                $usuario->message = EDIT_USER_NOT_PERMISION;
            }
            break;

        case ApiUtils::DELETE:
            if ($authorization->have_permision) {
                $usuario->delete($id);
                http_response_code($usuario->status ? 200 : 400);
            } else {
                http_response_code(403);
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
    $usuario->data = null;
}

$api_utils->response(
    $usuario->status,
    $usuario->message,
    $usuario->data ?? null,
    $authorization->permises
);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);