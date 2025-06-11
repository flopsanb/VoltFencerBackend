<?php
declare(strict_types=1);

/**
 * Endpoint para consultar y modificar SOLO la empresa del usuario autenticado.
 * Permite GET y PUT con control de permisos y verificación de token.
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
$id_empresa_usuario = $authorization->permises['id_empresa'] ?? null;

if (!$authorization->token_valido || !$id_empresa_usuario) {
    $empresa->status = false;
    $empresa->message = NO_TOKEN_MESSAGE;
} else {
    try {
        switch ($_SERVER['REQUEST_METHOD']) {
            case ApiUtils::GET:
                $authorization->havePermision(ApiUtils::GET, 'mi_empresa');
                if ($authorization->have_permision) {
                    $empresa->getById((int)$id_empresa_usuario);
                } else {
                    $empresa->status = false;
                    $empresa->message = 'No tienes permiso para ver tu empresa';
                }
                break;

            case ApiUtils::PUT:
                $authorization->havePermision(ApiUtils::PUT, 'mi_empresa');
                if ($authorization->have_permision) {
                    $request['id_empresa'] = (int)$id_empresa_usuario;
                    $empresa->update($request);
                } else {
                    $empresa->status = false;
                    $empresa->message = 'No tienes permiso para modificar tu empresa';
                }
                break;

            default:
                $empresa->status = false;
                $empresa->message = 'Método no soportado';
        }
    } catch (Exception $e) {
        $empresa->status = false;
        $empresa->message = 'Error inesperado';
        $empresa->data = $e->getMessage();
    }
}

$api_utils->response($empresa->status, $empresa->message, $empresa->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
