<?php
declare(strict_types=1);

/**
 * Endpoint general para CRUD de empresas (usado por admin o superadmin).
 * Se accede a todos los registros si se tiene permiso global.
 */

require_once __DIR__ . '/apiClasses/empresa.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);

$authorization = new Authorization();
$authorization->comprobarToken();

$request = json_decode(file_get_contents("php://input"), true);
$empresa = new Empresa();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$authorization->token_valido) {
    $empresa->status = false;
    $empresa->message = NO_TOKEN_MESSAGE;
} else {
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case ApiUtils::GET:
                if ($id) {
                    $empresa->getById($id);
                } else {
                    $empresa->get($authorization->permises);  // Pasa permisos para filtrar si es empresa limitada
                }
                break;

            case ApiUtils::POST:
                if ((int)($authorization->permises['crear_empresas'] ?? 0) === 1) {
                    $empresa->create($request);
                } else {
                    $empresa->status = false;
                    $empresa->message = 'No tienes permiso para crear empresas.';
                }
                break;

            case ApiUtils::PUT:
                if ((int)($authorization->permises['crear_empresas'] ?? 0) === 1) {
                    $empresa->update($request);
                } else {
                    $empresa->status = false;
                    $empresa->message = 'No tienes permiso para modificar empresas.';
                }
                break;

            case ApiUtils::DELETE:
                if ((int)($authorization->permises['crear_empresas'] ?? 0) === 1) {
                    $empresa->delete($id);
                } else {
                    $empresa->status = false;
                    $empresa->message = 'No tienes permiso para eliminar empresas.';
                }
                break;

            default:
                $empresa->status = false;
                $empresa->message = 'Método no soportado.';
        }
    } catch (Exception $e) {
        $empresa->status = false;
        $empresa->message = 'Error inesperado';
        $empresa->data = $e->getMessage();
    }
}

$api_utils->response($empresa->status, $empresa->message, $empresa->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
