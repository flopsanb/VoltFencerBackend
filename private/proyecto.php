<?php
declare(strict_types=1);

/**
 * Endpoint para gestión de proyectos
 * 
 * Este script implementa un endpoint RESTful para operaciones CRUD
 * sobre entidades de tipo Proyecto, validando tokens y permisos.
 * 
 * @author  Francisco Lopez
 * @version 1.2
 */

require_once __DIR__ . '/apiClasses/proyecto.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);
$api_utils->displayErrors();

$authorization = new Authorization();
$authorization->comprobarToken();

$proyecto = new Proyecto();
$id       = $_GET['id'] ?? null;
$request  = json_decode(file_get_contents("php://input"), true);

if (!$authorization->token_valido) {
    $proyecto->status  = false;
    $proyecto->message = defined('NO_TOKEN_MESSAGE') ? NO_TOKEN_MESSAGE : 'Token inválido o ausente.';
    http_response_code(401);

} else {
    try {
        $method    = $_SERVER['REQUEST_METHOD'];
        $permises  = $authorization->permises;

        switch ($method) {
            case ApiUtils::GET:
                $proyecto->data = $proyecto->get();
                break;

            case ApiUtils::POST:
                if (!empty($permises['crear_proyectos'])) {
                    $proyecto->create($request);
                } else {
                    $proyecto->status  = false;
                    $proyecto->message = 'No tienes permiso para crear proyectos.';
                    http_response_code(403);
                }
                break;

            case ApiUtils::PUT:
                if (!empty($permises['gestionar_usuarios_empresa']) || !empty($permises['gestionar_usuarios_globales'])) {
                    $proyecto->update($request);
                } else {
                    $proyecto->status  = false;
                    $proyecto->message = 'No tienes permiso para modificar proyectos.';
                    http_response_code(403);
                }
                break;

            case ApiUtils::DELETE:
                if (!empty($permises['borrar_proyectos'])) {
                    $proyecto->delete($id);
                } else {
                    $proyecto->status  = false;
                    $proyecto->message = 'No tienes permiso para eliminar proyectos.';
                    http_response_code(403);
                }
                break;

            default:
                $proyecto->status  = false;
                $proyecto->message = 'Método no soportado.';
                http_response_code(405);
        }

    } catch (Exception $e) {
        $proyecto->status  = false;
        $proyecto->message = 'Error inesperado en el endpoint de proyecto';
        $proyecto->data    = $e->getMessage();
        http_response_code(500);
    }
}

$api_utils->response(
    $proyecto->status,
    $proyecto->message,
    $proyecto->data ?? null,
    $authorization->permises ?? []
);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
exit;
