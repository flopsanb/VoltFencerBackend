<?php
declare(strict_types=1);

/**
 * Endpoint para gestión de proyectos
 * CRUD RESTful con validación de token y permisos
 * 
 * @author  Francisco Lopez
 * @version 1.5
 */

require_once __DIR__ . '/apiClasses/proyecto.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);

$authorization = new Authorization();
$authorization->comprobarToken();

$proyecto = new Proyecto($authorization);
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
            $authorization->havePermision(ApiUtils::GET, Proyecto::ROUTE);
            if ($authorization->have_permision) {
                $proyecto->get();
                http_response_code($proyecto->status ? 200 : 400);
            } else {
                http_response_code(403);    // Sin permisos
                $proyecto->status = false;
                $proyecto->message = 'No tienes permiso para ver proyectos.';
            }
            break;

        case ApiUtils::POST:
            $authorization->havePermision(ApiUtils::POST, Proyecto::ROUTE);
            if ($authorization->have_permision) {
                $proyecto->create($request);
                http_response_code($proyecto->status ? 200 : 400);
            } else {
                http_response_code(403);    // Sin permisos
                $proyecto->status = false;
                $proyecto->message = 'No tienes permiso para crear proyectos.';
            }
            break;

        case ApiUtils::PUT:
            $authorization->havePermision(ApiUtils::PUT, Proyecto::ROUTE);
            if ($authorization->have_permision) {
                $proyecto->update($request);
                http_response_code($proyecto->status ? 200 : 400);
            } else {
                http_response_code(403);    // Sin permisos
                $proyecto->status = false;
                $proyecto->message = 'No tienes permiso para modificar proyectos.';
            }
            break;

        case ApiUtils::DELETE:
            $authorization->havePermision(ApiUtils::DELETE, Proyecto::ROUTE);
            if ($authorization->have_permision) {
                $proyecto->delete($id);
                http_response_code($proyecto->status ? 200 : 400);
            } else {
                http_response_code(403);    // Sin permisos
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
    http_response_code(500);    // Error interno del servidor
    $proyecto->status = false;
    $proyecto->message = 'Error inesperado en el endpoint de proyecto';
    $proyecto->data = $e->getMessage();
}

$api_utils->response($proyecto->status, $proyecto->message, $proyecto->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);