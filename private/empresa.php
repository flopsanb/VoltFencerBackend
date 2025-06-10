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
$api_utils->displayErrors(); // ⚠️ Desactiva en producción

$authorization = new Authorization();
$authorization->comprobarToken();

$request = json_decode(file_get_contents("php://input"), true);
$empresa = new Empresa();
$id = $_GET['id'] ?? null;

if ($authorization->token_valido) {
    try {
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case ApiUtils::GET:
                if ($id && is_numeric($id)) {
                    $empresa->getById((int)$id);
                } else {
                    $empresa->get();
                }
                http_response_code(200);
                break;

            case ApiUtils::POST:
                if (!empty($authorization->permises['crear_empresas'])) {
                    $empresa->create($request);
                    http_response_code(201);
                } else {
                    $empresa->status = false;
                    $empresa->message = 'No tienes permiso para crear empresas.';
                    http_response_code(403);
                }
                break;

            case ApiUtils::PUT:
                if (!empty($authorization->permises['crear_empresas'])) {
                    $empresa->update($request);
                    http_response_code(200);
                } else {
                    $empresa->status = false;
                    $empresa->message = 'No tienes permiso para modificar empresas.';
                    http_response_code(403);
                }
                break;

            case ApiUtils::DELETE:
                if (!empty($authorization->permises['crear_empresas'])) {
                    if ($id && is_numeric($id)) {
                        $empresa->delete($id);
                        http_response_code(200);
                    } else {
                        $empresa->status = false;
                        $empresa->message = 'ID no válido para eliminar empresa.';
                        http_response_code(400);
                    }
                } else {
                    $empresa->status = false;
                    $empresa->message = 'No tienes permiso para eliminar empresas.';
                    http_response_code(403);
                }
                break;

            default:
                $empresa->status = false;
                $empresa->message = 'Método no soportado.';
                http_response_code(405);
        }

    } catch (Exception $e) {
        $empresa->status = false;
        $empresa->message = 'Error inesperado en el endpoint de empresa';
        $empresa->data = $e->getMessage();
        http_response_code(500);
    }

} else {
    $empresa->status = false;
    $empresa->message = NO_TOKEN_MESSAGE;
    http_response_code(401);
}

// Respuesta final
$api_utils->response($empresa->status, $empresa->message, $empresa->data, $authorization->permises);
echo json_encode($api_utils->response, JSON_PRETTY_PRINT);
