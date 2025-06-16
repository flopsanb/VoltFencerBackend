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

$empresa = new Empresa($authorization);
$id_empresa_usuario = $authorization->permises['id_empresa'] ?? null;

// Verificación de token y empresa válida
if (!$authorization->token_valido || !$id_empresa_usuario) {
    http_response_code(401);
    echo json_encode($api_utils->response(false, NO_TOKEN_MESSAGE), JSON_PRETTY_PRINT);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $request = json_decode(file_get_contents("php://input"), true) ?? [];

    switch ($method) {
        case ApiUtils::GET:
            $authorization->havePermision(ApiUtils::GET, 'mi_empresa');
            if ($authorization->have_permision) {
                $empresa->getById((int)$id_empresa_usuario);
                http_response_code($empresa->status ? 200 : 400);
            } else {
                http_response_code(403);    // Sin permisos
                $empresa->status = false;
                $empresa->message = 'No tienes permiso para ver tu empresa.';
            }
            break;

        case ApiUtils::PUT:
            $authorization->havePermision(ApiUtils::PUT, 'mi_empresa');
            if ($authorization->have_permision) {
                $request['id_empresa'] = (int)$id_empresa_usuario;
                $empresa->update($request);
                http_response_code($empresa->status ? 200 : 400);
            } else {
                http_response_code(403);    // Sin permisos
                $empresa->status = false;
                $empresa->message = 'No tienes permiso para modificar tu empresa.';
            }
            break;

        default:
            http_response_code(405);
            $empresa->status = false;
            $empresa->message = 'Método no soportado.';
    }

} catch (Exception $e) {
    http_response_code(500);    // Error interno del servidor
    $empresa->status = false;
    $empresa->message = 'Error inesperado.';
    $empresa->data = $e->getMessage();
}

// Siempre responder con el mismo formato
$api_utils->response($empresa->status, $empresa->message, $empresa->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
