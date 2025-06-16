<?php
declare(strict_types=1);

/**
 * Archivo de conexión y autorización para la API RESTful.
 *
 * Funcionalidades incluidas:
 * - Carga dinámica de configuración mediante variables de entorno (.env).
 * - Gestión de sesiones de usuario.
 * - Implementación de una clase de conexión a base de datos mediante PDO.
 * - Sistema completo de autenticación basado en token Bearer.
 * - Control de permisos por ruta y método HTTP, vinculado a roles configurados.
 * 
 * @author  Francisco Lopez
 * @version 2.1
 **/

// Cargar .env si existe
require_once __DIR__ . '/vendor/autoload.php';
use Dotenv\Dotenv;

// Cargar el .env si existe
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Inicia sesión solo si aún no ha sido iniciada (evita problemas de cabeceras)
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', '3600');
    session_start();
}
// Configura localización regional en español
setlocale(LC_ALL, 'es_ES');

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/api_utils.php';

// ------------------------------------------------------
// Clase principal para gestionar la conexión a la BBDD
// ------------------------------------------------------
class Conexion {
    // Propiedades estáticas de configuración de conexión
    private static $DB_HOST;
    private static $DB_USERNAME;
    private static $DB_PASSWORD;
    private static $DB_NAME;
    private static $DB_PORT;

    public $conexion;       // Instancia PDO
    public $id_usuario;     // ID de usuario actual (si hay sesión iniciada)

    public function __construct() {
        // Asigna los valores de conexión desde variables de entorno o valores por defecto
        self::$DB_HOST     = getenv('DB_HOST')     ?: 'localhost';
        self::$DB_USERNAME = getenv('DB_USER')     ?: 'root';
        self::$DB_PASSWORD = getenv('DB_PASSWORD') ?: 'root';
        self::$DB_NAME     = getenv('DB_NAME')     ?: 'gestion_proyectos';
        self::$DB_PORT     = getenv('DB_PORT')     ?: '3306';

        // Ejecuta la conexión a la base de datos
        $this->conectar();
        $this->setVariablesSession();
    }

    /**
     * Establece conexión segura con la base de datos utilizando PDO.
     */
    private function conectar(): void {
        try {
            $this->conexion = new PDO(
                "mysql:host=" . self::$DB_HOST . ";dbname=" . self::$DB_NAME . ";port=" . self::$DB_PORT,
                self::$DB_USERNAME,
                self::$DB_PASSWORD,
                [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]
            );
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["ok" => false, "message" => "Error interno del servidor"]);
            exit;
        }
    }

    /**
     * Recupera el ID del usuario desde la sesión (si existe).
     */
    private function setVariablesSession(): void {
        if (isset($_SESSION['id_usuario'])) {
            $this->id_usuario = $_SESSION['id_usuario'];
        }
    }

    /**
     * Cierra la conexión liberando el objeto PDO.
     */
    public function closeConnection(): void {
        $this->conexion = null;
    }

    /**
     * Elimina espacios en blanco si el valor es una cadena.
     */
    public function trimIfString($value) {
        return is_string($value) ? trim($value) : $value;
    }
}

/**
 * Clase Authorization
 * 
 * Hereda de la clase Conexion y se encarga de gestionar la autenticación y
 * autorización de los usuarios mediante tokens tipo Bearer.
 * 
 * Funcionalidades principales:
 * - Verificación y validación del token de sesión.
 * - Extracción de permisos asociados al rol del usuario.
 * - Comprobación de si un usuario tiene permiso para realizar una acción concreta.
 */
class Authorization extends Conexion {
    // Atributos públicos para facilitar el acceso desde otros scripts
    public $id_usuario = null;
    public $token_valido = false;
    public $token = null;
    public $is_admin = false;
    public $permises = null;
    public $have_permision = false;
    public $usuario = null;

    // Atributo privado que guarda el ID de rol del usuario
    private $id_rol = null;

    /**
     * Constructor de la clase Authorization.
     * Hereda la conexión a la base de datos desde Conexion.
     */
    public function __construct() {
        parent::__construct();
    }

    private function getAuthorizationHeader(): ?string {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) return trim($_SERVER["HTTP_AUTHORIZATION"]);
        if (isset($_SERVER['Authorization'])) return trim($_SERVER["Authorization"]);
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) return trim($_SERVER["REDIRECT_HTTP_AUTHORIZATION"]);

        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'authorization') return trim($value);
            }
        }
        return null;
    }

    /**
     * Extrae el token Bearer de la cabecera Authorization.
     * El token se guarda en la propiedad $token.
     */
    private function getBearerToken(): void {
        $header = $this->getAuthorizationHeader();
        if (!empty($header) && preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            $this->token = $matches[1];
        }
    }

    /**
     * Comprueba si el token recibido es válido y recupera los permisos del usuario.
     * En caso afirmativo, rellena los atributos relacionados con la sesión.
     */
    public function comprobarToken(): void {
        $this->getBearerToken();

        if (empty($this->token)) {
            $this->token_valido = false;
            return;
        }

        // Busca los permisos del usuario con el token extraído
        $datos = $this->obtenerPermisosDelUsuario($this->token);

        if ($datos) {
            $this->id_usuario = $datos["id_usuario"];
            $this->token_valido = true;
            $this->permises = $datos;

            // Se almacena información del usuario
            $this->usuario = [
                "id_usuario"     => $datos["id_usuario"],
                "usuario"        => $datos["usuario"],
                "nombre_publico" => $datos["nombre_publico"] ?? $datos["usuario"],
                "rol"            => $datos["nombre_rol"],
                "id_empresa"     => $datos["id_empresa"]
            ];
            $this->id_rol = $datos["id_rol"];
            $this->is_admin = ($this->id_rol == 1);     // Solo el superadmin tiene ID 1
        } else {
            $this->token_valido = false;
        }
    }

    /**
     * Consulta en la base de datos si el token pertenece a un usuario
     * y recupera los permisos asociados al rol de ese usuario.
     *
     * @param string $token Token de sesión en formato Bearer.
     * @return array|null Array de datos del usuario y permisos, o null si no existe.
     */
    private function obtenerPermisosDelUsuario(string $token): ?array {
        $stmt = $this->conexion->prepare("
            SELECT u.id_usuario, u.nombre_publico, u.id_rol, u.id_empresa, u.usuario, r.nombre_rol,
                   p.*
            FROM usuarios u
            JOIN roles r ON u.id_rol = r.id_rol
            JOIN permisos_rol p ON p.id_rol = u.id_rol
            WHERE u.token_sesion = :token
        ");
        $stmt->bindParam(":token", $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Evalúa si el usuario tiene permiso para realizar una acción sobre una ruta concreta,
     * combinando el método HTTP y la ruta accedida.
     *
     * @param string $method Método HTTP usado (GET, POST, PUT, DELETE).
     * @param string $route Ruta o recurso solicitado (por ejemplo, "usuarios").
     */
    public function havePermision(string $method, string $route): void {
        $this->have_permision = false;

        // El superadmin (rol ID 1) tiene acceso total sin necesidad de comprobación
        if ($this->is_admin) {
            $this->have_permision = true;
            return;
        }

        $acciones = [
            'POST'   => 'crear',
            'PUT'    => 'gestionar',
            'DELETE' => 'borrar',
            'GET'    => 'ver'
        ];

        $ruta = [
            'mi_empresa'   => 'mi_empresa',            
            'empresas'     => 'empresas',
            'usuarios'     => 'usuarios',
            'roles-menu'   => 'permisos_globales',
            'roles'   => 'permisos_globales',
            'permisos-rol' => 'permisos_globales',
            'proyectos'    => 'proyectos',
            'logs'         => 'logs_empresa',
            'roles_empresa' => 'roles_mi_empresa',
        ];

        // Traduce el método y la ruta en el campo específico de permisos
        $accion = $acciones[$method] ?? null;
        $ruta = $ruta[$route] ?? $route;

        if (!$accion || !$ruta) return;

        $campo = "{$accion}_{$ruta}";

        // Verifica si el permiso está habilitado para ese usuario
        if (isset($this->permises[$campo]) && $this->permises[$campo] == 1) {
            $this->have_permision = true;
        }
    }
}
