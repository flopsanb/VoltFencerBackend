<?php
declare(strict_types=1);
/**
 * Endpoint para gestión de usuarios (CRUD)
 * RESTful para operaciones sobre entidad Usuario.
 */

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';
require_once __DIR__ . '/../apiClasses/usuario.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);
$api_utils->displayErrors();

$authorization = new Authorization();
$authorization->comprobarToken();
$GLOBALS['authorization'] = $authorization;

$request = json_decode(file_get_contents('php://input'), true);

$usuario = new Usuario();
$id      = $_GET['id']    ?? null;
$route   = $_GET['route'] ?? null;

$status  = true;
$message = '';
$data    = null;

if ($authorization->token_valido) {
    try {
        $permises         = $authorization->permises;
        $user_info        = $authorization->usuario;
        $es_superadmin    = ($user_info['rol'] === 'superadmin');
        $es_admin_empresa = ($user_info['rol'] === 'admin_empresa');
        $id_empresa_token = $user_info['id_empresa'];
        $method           = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case ApiUtils::GET:
                if (($permises['gestionar_usuarios_globales'] ?? 0) || ($permises['ver_usuarios_empresa'] ?? 0)) {
                    $data = $usuario->get();
                } else {
                    $status  = false;
                    $message = 'No tienes permisos para ver los usuarios';
                }
                break;

            case ApiUtils::POST:
                if (($permises['gestionar_usuarios_globales'] ?? 0) || ($permises['gestionar_usuarios_empresa'] ?? 0)) {
                    if ($es_admin_empresa) {
                        if ($request['id_empresa'] != $id_empresa_token) {
                            $status  = false;
                            $message = 'No puedes crear usuarios fuera de tu empresa';
                            break;
                        }
                        if (!in_array($request['id_rol'], [3, 4])) {
                            $status  = false;
                            $message = 'No puedes asignar ese rol';
                            break;
                        }
                    }
                    $data = $usuario->create($request);
                } else {
                    $status  = false;
                    $message = 'No tienes permiso para crear usuarios';
                }
                break;

            case ApiUtils::PUT:
                if ($route === Usuario::ROUTE_PROFILE) {
                    $data = $usuario->updateProfile($request, $authorization->token);
                    break;
                }

                if (($permises['gestionar_usuarios_globales'] ?? 0) || ($permises['gestionar_usuarios_empresa'] ?? 0)) {
                    if ($es_admin_empresa) {
                        if ($request['id_empresa'] != $id_empresa_token) {
                            $status  = false;
                            $message = 'No puedes editar usuarios fuera de tu empresa';
                            break;
                        }
                        if (!in_array($request['id_rol'], [3, 4])) {
                            $status  = false;
                            $message = 'No puedes cambiar a un rol superior al tuyo';
                            break;
                        }
                    }
                    $data = $usuario->update($request);
                } else {
                    $status  = false;
                    $message = 'No tienes permiso para editar usuarios';
                }
                break;

            case ApiUtils::DELETE:
                if (($permises['gestionar_usuarios_globales'] ?? 0) || ($permises['gestionar_usuarios_empresa'] ?? 0)) {
                    $data = $usuario->delete($id);
                } else {
                    $status  = false;
                    $message = 'No tienes permiso para eliminar usuarios';
                }
                break;

            default:
                $status  = false;
                $message = 'Método no soportado';
        }

    } catch (Exception $e) {
        $status  = false;
        $message = 'Error inesperado en endpoint usuario';
        $data    = $e->getMessage();
    }

} else {
    $status  = false;
    $message = defined('NO_TOKEN_MESSAGE') ? NO_TOKEN_MESSAGE : 'Token no válido o ausente';
    http_response_code(401);
}

// Código de respuesta HTTP adecuado
if (!$status && http_response_code() === 200) {
    http_response_code(403);
}

// Respuesta final
$api_utils->response($status, $message, $data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
exit;
