<?php
/**
 * Endpoint para gestión de empresas
 * 
 * CRUD sobre entidades Empresa con validación de token y permisos.
 */

require_once __DIR__ . '/apiClasses/empresa.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

// Inicialización
$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);
$api_utils->displayErrors();

$authorization = new Authorization();
$authorization->comprobarToken();

$request = json_decode(file_get_contents("php://input"), true);

$empresa = new Empresa();
$id = $_GET['id'] ?? null;

if ($authorization->token_valido) {
    try {
        error_log("MÉTODO RECIBIDO: " . $_SERVER['REQUEST_METHOD']);
        switch ($_SERVER['REQUEST_METHOD']) {

            case ApiUtils::GET:
                if (isset($_GET['id'])) {
                    $empresa->getById(intval($_GET['id']));
                } else {
                    $empresa->get();
                }
                break;

            case ApiUtils::POST:
                if ((int)($authorization->permises['crear_empresas'] ?? 0) === 1) {
                    $empresa->create($request);
                } else {
                    $empresa->message = 'No tienes permiso para crear empresas.';
                }
                break;

            case ApiUtils::PUT:
                if ((int)($authorization->permises['crear_empresas'] ?? 0) === 1) {
                    $empresa->update($request);
                } else {
                    $empresa->message = 'No tienes permiso para modificar empresas.';
                }
                break;

            case ApiUtils::DELETE:
                if ((int)($authorization->permises['crear_empresas'] ?? 0) === 1) {
                    $empresa->delete($id);
                } else {
                    $empresa->message = 'No tienes permiso para eliminar empresas.';
                }
                break;


            default:
                $empresa->message = 'Método no soportado.';
                break;
        }
    } catch (Exception $e) {
        $empresa->status = false;
        $empresa->message = 'Error inesperado en el endpoint de empresa';
        $empresa->data = $e->getMessage();
    }
} else {
    $empresa->status = false;
    $empresa->message = NO_TOKEN_MESSAGE;
}

$api_utils->response($empresa->status, $empresa->message, $empresa->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);

?>
