<?php
/**
 * ApiUtils - Clase de utilidades para APIs REST
 * 
 * Proporciona métodos para configuración CORS, gestión de errores,
 * y respuestas estándar para endpoints REST.
 * 
 * @author  Francisco Lopez
 * @version 1.1
 */

require_once(__DIR__ . '/text.php');

class ApiUtils {
    const GET         = 'GET';
    const POST        = 'POST';
    const PUT         = 'PUT';
    const DELETE      = 'DELETE';
    const ALL_HEADERS = 'ALL_HEADERS';

    public $response;

    /**
     * Establece los headers necesarios para la API (CORS + JSON).
     *
     * @param string $method Método permitido o ALL_HEADERS
     */
    public function setHeaders($method) {
        header("Access-Control-Allow-Origin: https://voltfencerfrontend.onrender.com");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");
        header("Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With, Accept, Origin");
        header("Content-Type: application/json; charset=utf-8");

        if ($method === self::ALL_HEADERS) {
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        } else {
            header("Access-Control-Allow-Methods: {$method}, OPTIONS");
        }

        // Si la petición es OPTIONS, cortamos aquí (muy común en despliegues en la nube)
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    /**
     * Muestra errores detallados en desarrollo
     */
    public function displayErrors() {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }

    /**
     * Estructura una respuesta JSON para la API
     * 
     * @param bool $status
     * @param string $message
     * @param mixed $data
     * @param mixed $permises
     */
    public function response($status, $message, $data = null, $permises = null) {
        $this->response = [
            'ok'       => $status,
            'message'  => $message,
            'data'     => $data ?? [],
            'permises' => $permises ?? []
        ];
    }
}
?>
