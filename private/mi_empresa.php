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



$authorization = new Authorization();
$authorization->comprobarToken();

$request = json_decode(file_get_contents("php://input"), true);

$empresa = new Empresa();

if ($authorization->token_valido) {
    try {
        $id_empresa_usuario = $authorization->permises['id_empresa'] ?? null;

        switch ($_SERVER['REQUEST_METHOD']) {
            case ApiUtils::GET:
                $authorization->havePermision('GET', 'mi_empresa');
                if ($authorization->have_permision) {
                    $empresa->getById($id_empresa_usuario);
                } else {
                    $empresa->message = 'No tienes permiso para ver tu empresa';
                }
                break;

            case ApiUtils::POST:
                $empresa->message = 'No tienes permiso para crear empresa desde este endpoint';
                break;

            case ApiUtils::PUT:
                $authorization->havePermision('PUT', 'mi_empresa');
                if ($authorization->have_permision) {
                    $request['id_empresa'] = $id_empresa_usuario;
                    $empresa->update($request);
                } else {
                    $empresa->message = 'No tienes permiso para editar tu empresa';
                }
                break;

            case ApiUtils::DELETE:
                $empresa->message = 'No tienes permiso para eliminar empresa desde este endpoint';
                break;

            default:
                $empresa->message = 'Método no soportado en este endpoint';
                break;
        }
    } catch (Exception $e) {
        $empresa->status = false;
        $empresa->message = 'Error inesperado en mi_empresa.php';
        $empresa->data = $e->getMessage();
    }
} else {
    $empresa->status = false;
    $empresa->message = NO_TOKEN_MESSAGE;
}

$api_utils->response($empresa->status, $empresa->message, $empresa->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
