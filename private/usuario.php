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

// Validación del token de sesión mediante la clase Authorization
$authorization = new Authorization();
$authorization->comprobarToken();

// Si el token no es válido, se aborta la operación con código 401 (No autorizado)
if (!$authorization->token_valido) {
    http_response_code(401);
    $api_utils->response(false, NO_TOKEN_MESSAGE);
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

// Instancia del modelo Usuario, con autorización inyectada
$usuario = new Usuario($authorization);

// Se recopilan parámetros de entrada y método HTTP utilizado
$request = json_decode(file_get_contents("php://input"), true);
$id      = $_GET['id'] ?? null;
$route   = $_GET['route'] ?? null;

try {
    // Detección del método HTTP (GET, POST, PUT, DELETE)
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        // Obtener usuarios (listar o por ID)
        case ApiUtils::GET:
            $authorization->havePermision(ApiUtils::GET, Usuario::ROUTE);
            if ($authorization->have_permision) {
                $usuario->get($_GET);
                http_response_code(200);
            } else {
                http_response_code(403);    // Sin permisos
                $usuario->status = false;
                $usuario->message = VIEW_USER_NOT_PERMISION;
            }
            break;

        // Crear nuevo usuario
        case ApiUtils::POST:
            $authorization->havePermision(ApiUtils::POST, Usuario::ROUTE);
            if ($authorization->have_permision) {
                $usuario->create($request);
                http_response_code($usuario->status ? 200 : 400);
            } else {
                http_response_code(403);    // Sin permisos
                $usuario->status = false;
                $usuario->message = ADD_USER_NOT_PERMISION;
            }
            break;
        
        // Actualizar usuario existente
        case ApiUtils::PUT:
            $authorization->havePermision(ApiUtils::PUT, Usuario::ROUTE);
            if ($authorization->have_permision) {
                $usuario->update($request);
                http_response_code($usuario->status ? 200 : 400);
            } else {
                http_response_code(403);    // Sin permisos
                $usuario->status = false;
                $usuario->message = EDIT_USER_NOT_PERMISION;
            }
            break;

        // Borrar usuario existente
        case ApiUtils::DELETE:
            $authorization->havePermision(ApiUtils::DELETE, Usuario::ROUTE);
            if ($authorization->have_permision) {
                $usuario->delete($id);
                http_response_code($usuario->status ? 200 : 400);
            } else {
                http_response_code(403);    // Sin permisos
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
    http_response_code(500);    // Error interno del servidor
    $usuario->status = false;
    $usuario->message = 'Error inesperado en el endpoint de usuario';
    $usuario->data = $e->getMessage();
}

// Envío de la respuesta en formato JSON
$api_utils->response($usuario->status, $usuario->message, $usuario->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
