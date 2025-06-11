<?php
declare(strict_types=1);

/**
 * Endpoint para gestión de proyectos
 * CRUD RESTful con validación de token y permisos
 * 
 * @author  Francisco Lopez
 * @version 1.3
 */

require_once __DIR__ . '/apiClasses/proyecto.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);

$authorization = new Authorization();
$authorization->comprobarToken();

$proyecto = new Proyecto();
$request = json_decode(file_get_contents("php://input"), true);
$id = $_GET['id'] ?? null;

if (!$authorization->token_valido) {
    http_response_code(401);
    $api_utils->response(false, NO_TOKEN_MESSAGE);
    echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $permises = $authorization->permises;

    switch ($method) {
        case ApiUtils::GET:
            $proyecto->get();
            http_response_code(200);
            break;

        case ApiUtils::POST:
            if ((int)($permises['crear_proyectos'] ?? 0) === 1) {
                $proyecto->create($request);
                http_response_code($proyecto->status ? 200 : 400);
            } else {
                http_response_code(403);
                $proyecto->status = false;
                $proyecto->message = 'No tienes permiso para crear proyectos.';
            }
            break;

        case ApiUtils::PUT:
            if ((int)($permises['gestionar_usuarios_empresa'] ?? 0) === 1 ||
                (int)($permises['gestionar_usuarios_globales'] ?? 0) === 1) {
                $proyecto->update($request);
                http_response_code($proyecto->status ? 200 : 400);
            } else {
                http_response_code(403);
                $proyecto->status = false;
                $proyecto->message = 'No tienes permiso para modificar proyectos.';
            }
            break;

        case ApiUtils::DELETE:
            if ((int)($permises['borrar_proyectos'] ?? 0) === 1) {
                $proyecto->delete($id);
                http_response_code($proyecto->status ? 200 : 400);
            } else {
                http_response_code(403);
                $proyecto->status = false;
                $proyecto->message = 'No tienes permiso para eliminar proyectos.';
            }
            break;

        default:
            http_response_code(405);
            $proyecto->status = false;
            $proyecto->message = 'Método HTTP no soportado.';
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    $proyecto->status = false;
    $proyecto->message = 'Error inesperado en el endpoint de proyecto';
    $proyecto->data = $e->getMessage();
}

$api_utils->response($proyecto->status, $proyecto->message, $proyecto->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
