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

    public function __construct() {
        $this->setHeaders(self::ALL_HEADERS);
        $this->handlePreflight();
    }

    /**
     * Establece los headers necesarios para la API.
     */
    public function setHeaders($method) {
        header("Access-Control-Allow-Origin: https://volt.onrender.com");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 1000");
        header("Access-Control-Allow-Headers: X-Requested-With, Content-Type, Origin, Cache-Control, Pragma, Authorization, Accept, Accept-Encoding");
        header("Content-Type: application/json; charset=utf-8");

        if ($method === self::ALL_HEADERS) {
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        } else {
            header("Access-Control-Allow-Methods: {$method}, OPTIONS");
        }
    }
    
    /**
     * Maneja automáticamente las peticiones OPTIONS (preflight CORS).
     */
    public function handlePreflight() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }

    /**
     * Activa la visualización de errores (modo desarrollo).
     */
    public function displayErrors() {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
    }

    /**
     * Devuelve un objeto de respuesta formateado.
     */
    public function response($status, $message, $data = null, $permises = null) {
        $this->response = [
            'ok'       => $status,
            'message'  => $message,
            'data'     => $data,
            'permises' => $permises
        ];
    }
}

?>
