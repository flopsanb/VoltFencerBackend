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



$authorization = new Authorization();
$authorization->comprobarToken();

$proyecto = new Proyecto();
$id = $_GET['id'] ?? null;
$request = json_decode(file_get_contents("php://input"), true);

if (!$authorization->token_valido) {
    $proyecto->status = false;
    $proyecto->message = NO_TOKEN_MESSAGE;
} else {
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case ApiUtils::GET:
                $proyecto->get();
                break;

            case ApiUtils::POST:
                if ((int)($authorization->permises['crear_proyectos'] ?? 0) === 1) {
                    $proyecto->create($request);
                } else {
                    $proyecto->message = 'No tienes permiso para crear proyectos.';
                }
                break;

            case ApiUtils::PUT:
                if ((int)($authorization->permises['gestionar_usuarios_empresa'] ?? 0) === 1 ||
                    (int)($authorization->permises['gestionar_usuarios_globales'] ?? 0) === 1) {
                    $proyecto->update($request);
                } else {
                    $proyecto->message = 'No tienes permiso para modificar proyectos.';
                }
                break;

            case ApiUtils::DELETE:
                if ((int)($authorization->permises['borrar_proyectos'] ?? 0) === 1) {
                    $proyecto->delete($id);
                } else {
                    $proyecto->message = 'No tienes permiso para eliminar proyectos.';
                }
                break;

            default:
                $proyecto->message = 'Método no soportado.';
        }
    } catch (Exception $e) {
        $proyecto->status = false;
        $proyecto->message = 'Error inesperado en el endpoint de proyecto';
        $proyecto->data = $e->getMessage();
    }
}

// Devolver la respuesta con los permisos tal cual llegan
$api_utils->response($proyecto->status, $proyecto->message, $proyecto->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
