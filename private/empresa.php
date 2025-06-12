<?php
declare(strict_types=1);

/**
 * Endpoint general para CRUD de empresas (usado por admin o superadmin).
 * Se accede a todos los registros si se tiene permiso global.
 * 
 * @author Francisco López
 * @version 2.0
 */

require_once __DIR__ . '/apiClasses/empresa.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

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

$request = json_decode(file_get_contents("php://input"), true);
$empresa = new Empresa($authorization);
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

try {
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case ApiUtils::GET:
            $authorization->havePermision(ApiUtils::GET, Empresa::ROUTE);
            if ($authorization->have_permision) {
                if ($id) {
                    $empresa->getById($id);
                } else {
                    $empresa->get();
                }
                http_response_code(200);
            } else {
                http_response_code(403);
                $empresa->status = false;
                $empresa->message = 'No tienes permiso para ver empresas.';
            }
            break;

        case ApiUtils::POST:
            $authorization->havePermision(ApiUtils::POST, Empresa::ROUTE);
            if ($authorization->have_permision) {
                $empresa->create($request);
                http_response_code($empresa->status ? 200 : 400);
            } else {
                http_response_code(403);
                $empresa->status = false;
                $empresa->message = 'No tienes permiso para crear empresas.';
            }
            break;

        case ApiUtils::PUT:
            $authorization->havePermision(ApiUtils::PUT, Empresa::ROUTE);
            if ($authorization->have_permision) {
                $empresa->update($request);
                http_response_code($empresa->status ? 200 : 400);
            } else {
                http_response_code(403);
                $empresa->status = false;
                $empresa->message = 'No tienes permiso para modificar empresas.';
            }
            break;

        case ApiUtils::DELETE:
            $authorization->havePermision(ApiUtils::DELETE, Empresa::ROUTE);
            if ($authorization->have_permision) {
                $empresa->delete($id);
                http_response_code($empresa->status ? 200 : 400);
            } else {
                http_response_code(403);
                $empresa->status = false;
                $empresa->message = 'No tienes permiso para eliminar empresas.';
            }
            break;

        default:
            http_response_code(405);
            $empresa->status = false;
            $empresa->message = 'Método HTTP no soportado.';
            break;
    }

} catch (Throwable $e) {
    http_response_code(500);
    $empresa->status = false;
    $empresa->message = 'Error inesperado en el endpoint de empresas.';
    $empresa->data = $e->getMessage();
}

$api_utils->response($empresa->status, $empresa->message, $empresa->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
