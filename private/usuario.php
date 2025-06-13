<?php
declare(strict_types=1);

/**
 * Endpoint RESTful para gestión de usuarios (CRUD)
 * Control de permisos refinado y validaciones adicionales
 * 
 * @author Francisco
 * @version 1.6
 */

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';
require_once __DIR__ . '/apiClasses/usuario.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);

$authorization = new Authorization();
$authorization->comprobarToken();

if (!$authorization->token_valido) {
    http_response_code(401);
    $api_utils->response(false, NO_TOKEN_MESSAGE);
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

$usuario = new Usuario($authorization);
$request = json_decode(file_get_contents("php://input"), true);
$id      = $_GET['id'] ?? null;
$route   = $_GET['route'] ?? null;

try {
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case ApiUtils::GET:
            $authorization->havePermision(ApiUtils::GET, Usuario::ROUTE);
            if ($authorization->have_permision) {
                $usuario->get($_GET);
                http_response_code(200);
            } else {
                http_response_code(403);
                $usuario->status = false;
                $usuario->message = VIEW_USER_NOT_PERMISION;
            }
            break;

        case ApiUtils::POST:
            $authorization->havePermision(ApiUtils::POST, Usuario::ROUTE);
            if ($authorization->have_permision) {
                // Validaciones antes de crear
                if ($usuario->existsUsuario($request['usuario'])) {
                    http_response_code(409);
                    $api_utils->response(false, 'El nombre de usuario ya está registrado.');
                    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
                    exit;
                }

                if (!empty($request['email']) && $usuario->existsEmail($request['email'])) {
                    http_response_code(409);
                    $api_utils->response(false, 'El correo electrónico ya está registrado.');
                    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
                    exit;
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
            $authorization->havePermision(ApiUtils::PUT, Usuario::ROUTE);
            if ($route === Usuario::ROUTE_PROFILE) {
                $usuario->updateProfile($request, $authorization->token);
                http_response_code(200);
                break;
            }
            if ($authorization->have_permision) {
                // Validaciones antes de editar
                $id_usuario = $request['id_usuario'] ?? null;

                if ($usuario->existsUsuario($request['usuario'], $id_usuario)) {
                    http_response_code(409);
                    $api_utils->response(false, 'El nombre de usuario ya está registrado.');
                    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
                    exit;
                }

                if (!empty($request['email']) && $usuario->existsEmail($request['email'], $id_usuario)) {
                    http_response_code(409);
                    $api_utils->response(false, 'El correo electrónico ya está registrado.');
                    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
                    exit;
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
            $authorization->havePermision(ApiUtils::DELETE, Usuario::ROUTE);
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
            $usuario->message = 'Método HTTP no soportado.';
            break;
    }

} catch (Throwable $e) {
    http_response_code(500);
    $usuario->status = false;
    $usuario->message = 'Error inesperado en el endpoint de usuario';
    $usuario->data = $e->getMessage();
}

$api_utils->response($usuario->status, $usuario->message, $usuario->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
