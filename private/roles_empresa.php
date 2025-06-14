<?php
declare(strict_types=1);

/**
 * Endpoint para obtener roles disponibles para usuarios de empresa.
 * Solo devuelve los roles 3 y 4 si se tiene el permiso 'ver_roles_mi_empresa'.
 * 
 * @author Paco
 * @version 1.0
 */

require_once __DIR__ . '/apiClasses/roles_empresa.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/../api_utils.php';

$api_utils = new ApiUtils();
$api_utils->setHeaders(ApiUtils::ALL_HEADERS);

$authorization = new Authorization();
$authorization->comprobarToken();

$rolesEmpresa = new RolesEmpresa($authorization);
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
            $authorization->havePermision(ApiUtils::GET, RolesEmpresa::ROUTE);
            if ($authorization->have_permision) {
                $rolesEmpresa->get();
                http_response_code($rolesEmpresa->status ? 200 : 400);
            } else {
                http_response_code(403);
                $rolesEmpresa->status = false;
                $rolesEmpresa->message = 'No tienes permiso para ver roles de tu empresa.';
            }
            break;

        default:
            http_response_code(405);
            $rolesEmpresa->status = false;
            $rolesEmpresa->message = 'Método HTTP no soportado.';
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    $rolesEmpresa->status = false;
    $rolesEmpresa->message = 'Error inesperado en el endpoint de roles_empresa';
    $rolesEmpresa->data = $e->getMessage();
}

$api_utils->response($rolesEmpresa->status, $rolesEmpresa->message, $rolesEmpresa->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
