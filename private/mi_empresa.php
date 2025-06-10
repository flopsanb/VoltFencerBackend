<?php
declare(strict_types=1);

/**
 * Endpoint para gestión de la empresa del usuario
 * Permite consultar y actualizar información de la empresa propia,
 * con verificación de permisos específicos.
 */

require_once __DIR__ . '/apiClasses/empresa.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);
$api_utils->displayErrors();

$authorization = new Authorization();
$authorization->comprobarToken();

$empresa = new Empresa();
$request = json_decode(file_get_contents("php://input"), true);
$data = null;

if ($authorization->token_valido) {
    try {
        $id_empresa_usuario = $authorization->permises['id_empresa'] ?? null;

        if (!$id_empresa_usuario) {
            $empresa->status = false;
            $empresa->message = 'ID de empresa no disponible en el token';
            http_response_code(400);
        } else {
            switch ($_SERVER['REQUEST_METHOD']) {

                case ApiUtils::GET:
                    $authorization->havePermision(ApiUtils::GET, 'mi_empresa');
                    if ($authorization->have_permision) {
                        $data = $empresa->getById($id_empresa_usuario);
                    } else {
                        $empresa->status = false;
                        $empresa->message = 'No tienes permiso para ver tu empresa';
                        http_response_code(403);
                    }
                    break;

                case ApiUtils::POST:
                    $empresa->status = false;
                    $empresa->message = 'No tienes permiso para crear empresa desde este endpoint';
                    http_response_code(405);
                    break;

                case ApiUtils::PUT:
                    $authorization->havePermision(ApiUtils::PUT, 'mi_empresa');
                    if ($authorization->have_permision) {
                        $request['id_empresa'] = $id_empresa_usuario;
                        $empresa->update($request);
                    } else {
                        $empresa->status = false;
                        $empresa->message = 'No tienes permiso para editar tu empresa';
                        http_response_code(403);
                    }
                    break;

                case ApiUtils::DELETE:
                    $empresa->status = false;
                    $empresa->message = 'No tienes permiso para eliminar empresa desde este endpoint';
                    http_response_code(405);
                    break;

                default:
                    $empresa->status = false;
                    $empresa->message = 'Método no soportado en este endpoint';
                    http_response_code(405);
                    break;
            }
        }

    } catch (Exception $e) {
        $empresa->status = false;
        $empresa->message = 'Error inesperado en mi_empresa.php';
        $empresa->data = $e->getMessage();
        http_response_code(500);
    }

} else {
    $empresa->status = false;
    $empresa->message = defined('NO_TOKEN_MESSAGE') ? NO_TOKEN_MESSAGE : 'Token inválido.';
    http_response_code(401);
}

$response = $api_utils->response($status, $message, $data, $permises);
echo json_encode($response, JSON_PRETTY_PRINT);

exit;
