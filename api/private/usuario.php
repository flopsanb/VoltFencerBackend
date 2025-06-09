<?php
/**
 * Endpoint para gestión de usuarios (CRUD)
 * RESTful para operaciones sobre entidad Usuario.
 */

require_once(__DIR__ . '/../conn.php');
require_once(__DIR__ . '/../api_utils.php');
require_once(__DIR__ . '/../apiClasses/usuario.php');

// CORS & Preflight
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);
$api_utils->displayErrors();

// Validación token
$authorization = new Authorization();
$authorization->comprobarToken();
$GLOBALS['authorization'] = $authorization;

// Input JSON
$request = json_decode(file_get_contents('php://input'), true);

// Instancia usuario
$usuario = new Usuario();
$id     = $_GET['id']    ?? null;
$route  = $_GET['route'] ?? null;

// Iniciar flags
$status  = true;
$message = '';
$data    = null;

if ($authorization->token_valido) {
    try {
        $permises            = $authorization->permises;
        $user_info           = $authorization->usuario;
        $es_superadmin       = ($user_info['rol'] === 'superadmin');
        $es_admin_empresa    = ($user_info['rol'] === 'admin_empresa');
        $id_empresa_token    = $user_info['id_empresa'];
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case ApiUtils::GET:
                if (($permises['gestionar_usuarios_globales'] ?? 0) === 1 ||
                    ($permises['ver_usuarios_empresa'] ?? 0) === 1) {
                    $data = $usuario->get();
                } else {
                    $status  = false;
                    $message = 'No tienes permisos para ver los usuarios';
                }
                break;

            case ApiUtils::POST:
                if (($permises['gestionar_usuarios_globales'] ?? 0) === 1 ||
                    ($permises['gestionar_usuarios_empresa'] ?? 0) === 1) {

                    if ($es_admin_empresa && ($request['id_empresa'] != $id_empresa_token)) {
                        $status  = false;
                        $message = 'No puedes crear usuarios fuera de tu empresa';
                    } elseif ($es_admin_empresa && !in_array($request['id_rol'], [3, 4])) {
                        $status  = false;
                        $message = 'No puedes asignar ese rol';
                    } else {
                        $data = $usuario->create($request);
                    }

                } else {
                    $status  = false;
                    $message = 'No tienes permiso para crear usuarios';
                }
                break;

            case ApiUtils::PUT:
                if ($route === Usuario::ROUTE_PROFILE) {
                    $data = $usuario->updateProfile($request, $authorization->token);
                } elseif (($permises['gestionar_usuarios_globales'] ?? 0) === 1 ||
                          ($permises['gestionar_usuarios_empresa'] ?? 0) === 1) {

                    if ($es_admin_empresa && ($request['id_empresa'] != $id_empresa_token)) {
                        $status  = false;
                        $message = 'No puedes editar usuarios fuera de tu empresa';
                    } elseif ($es_admin_empresa && !in_array($request['id_rol'], [3, 4])) {
                        $status  = false;
                        $message = 'No puedes cambiar a un rol superior al tuyo';
                    } else {
                        $data = $usuario->update($request);
                    }

                } else {
                    $status  = false;
                    $message = 'No tienes permiso para editar usuarios';
                }
                break;

            case ApiUtils::DELETE:
                if (($permises['gestionar_usuarios_globales'] ?? 0) === 1 ||
                    ($permises['gestionar_usuarios_empresa'] ?? 0) === 1) {
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
    $message = NO_TOKEN_MESSAGE;
}

// Responder JSON limpio
$api_utils->response($status, $message, $data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
exit;
