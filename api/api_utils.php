<?php
declare(strict_types=1);

/**
 * ApiUtils - Clase de utilidades para APIs REST
 * 
 * Proporciona métodos para configuración CORS, gestión de errores,
 * y respuestas estándar para endpoints REST.
 * 
 * @author  Francisco Lopez
 * @version 1.2
 */

require_once __DIR__ . '/text.php';

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
    public function setHeaders(string $method): void {
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

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    /**
     * Muestra errores detallados en desarrollo
     */
    public function displayErrors(): void {
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
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
    public function response(bool $status, string $message, $data = null, $permises = null): void {
        $this->response = [
            'ok'       => $status,
            'message'  => $message,
            'data'     => $data ?? [],
            'permises' => $permises ?? []
        ];
    }
}
