<?php
/**
 * Conexión y Autorización para API REST adaptado para entorno online
 * 
 * Incluye:
 * - Uso de variables de entorno para DB
 * - Corrección de deprecated property
 * - Mejoras para evitar problemas con session_start
 * 
 * @author  Francisco Lopez
 * @version 2.0
 */

// Solo activa sesión si no está ya activa (evita errores en Railway)
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 3600);
    session_start();
}
setlocale(LC_ALL, 'es_ES');

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/api_utils.php';

class Conexion {
    private static $DB_HOST;
    private static $DB_USERNAME;
    private static $DB_PASSWORD;
    private static $DB_NAME;
    private static $DB_PORT;

    public $conexion;
    public $id_usuario;

    function __construct () {
        self::$DB_HOST     = getenv('DB_HOST')     ?: 'localhost';
        self::$DB_USERNAME = getenv('DB_USER')     ?: 'root';
        self::$DB_PASSWORD = getenv('DB_PASSWORD') ?: 'root';
        self::$DB_NAME     = getenv('DB_NAME')     ?: 'gestion_proyectos';
        self::$DB_PORT     = getenv('DB_PORT')     ?: '3306';

        $this->conectar();
        $this->setVariablesSession();
    }

    private function conectar() {
        $this->conexion = new PDO(
            "mysql:host=" . self::$DB_HOST . ";dbname=" . self::$DB_NAME . ";port=" . self::$DB_PORT,
            self::$DB_USERNAME,
            self::$DB_PASSWORD,
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8")
        );
        $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    private function setVariablesSession() {
        if (isset($_SESSION['id_usuario'])) {
            $this->id_usuario = $_SESSION['id_usuario'];
        }
    }

    public function closeConnection() {
        $this->conexion = null;
    }

    public function trimIfString($value) {
        return is_string($value) ? trim($value) : $value;
    }
}

class Authorization extends Conexion {
    public $id_usuario = null;
    public $token_valido = false;
    public $token = null;
    public $token_encrypt;
    public $is_admin = false;
    public $permises = null;
    private $id_rol = null;
    public $have_permision = false;
    public $usuario = null;

    function __construct () {
        parent::__construct();
    }

    private function getAuthorizationHeader() {
        if (isset($_SERVER['Authorization'])) return trim($_SERVER["Authorization"]);
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) return trim($_SERVER["HTTP_AUTHORIZATION"]);
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) return trim($headers["Authorization"]);
        }
        return null;
    }

    private function getBearerToken() {
        $headers = $this->getAuthorizationHeader();
        if (!empty($headers) && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            $this->token = $matches[1];
        }
    }

    public function comprobarToken() {
        $this->getBearerToken();
        $datos = $this->obtenerPermisosDelUsuario($this->token);

        if ($datos) {
            $this->id_usuario = $datos["id_usuario"];
            $this->token_valido = true;
            $this->permises = $datos;
            $this->usuario = [
                "id_usuario"     => $datos["id_usuario"],
                "usuario"        => $datos["usuario"],
                "nombre_publico" => $datos["nombre_publico"] ?? $datos["usuario"],
                "rol"            => $datos["nombre_rol"],
                "id_empresa"     => $datos["id_empresa"]
            ];
        } else {
            $this->token_valido = false;
        }
    }

    private function obtenerPermisosDelUsuario($token) {
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
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getIdRol() {
        $sql = $this->conexion->prepare("SELECT id_rol FROM usuarios WHERE token_sesion = :token");
        $sql->bindParam(":token", $this->token);
        $sql->execute();
        $this->id_rol = $sql->fetch(PDO::FETCH_ASSOC)["id_rol"];
    }

    public function isAdmin() {
        $this->getIdRol();
        $this->is_admin = ($this->id_rol === '1' || $this->id_rol === 1);
    }

    public function havePermision($method, $route) {
        $this->isAdmin();
        $api_utils = new ApiUtils();

        if ($this->is_admin) {
            $this->have_permision = true;
            return;
        }

        $map = [
            'usuarios' => [
                'POST'   => ['gestionar_usuarios_globales', 'gestionar_usuarios_empresa'],
                'PUT'    => ['gestionar_usuarios_globales', 'gestionar_usuarios_empresa'],
                'DELETE'=> ['gestionar_usuarios_globales', 'gestionar_usuarios_empresa'],
                'GET'    => ['gestionar_usuarios_globales', 'gestionar_usuarios_empresa']
            ],
            'empresas' => [
                'POST'   => ['crear_empresas'],
                'PUT'    => ['crear_empresas'],
                'DELETE'=> ['crear_empresas'],
                'GET'    => ['crear_empresas']
            ],
            'proyectos' => [
                'POST'   => ['crear_proyectos'],
                'PUT'    => ['crear_proyectos'],
                'DELETE'=> ['borrar_proyectos'],
                'GET'    => ['crear_proyectos']
            ],
            'mi_empresa' => [
                'GET'  => ['ver_usuarios_empresa'],
                'PUT'  => ['gestionar_usuarios_empresa']
            ]
        ];

        $method = strtoupper($method);
        $permisos_necesarios = $map[$route][$method] ?? [];

        foreach ($permisos_necesarios as $permiso) {
            if (isset($this->permises[$permiso]) && $this->permises[$permiso] == 1) {
                $this->have_permision = true;
                break;
            }
        }

        error_log("[🔐 PERMISO] $method $route => " . ($this->have_permision ? "✅" : "❌"));
    }

    public function havePermissionByTipo($tipo_permiso) {
        return isset($this->permises[$tipo_permiso]) && $this->permises[$tipo_permiso] == 1;
    }

    public function getPermision($route) {
        $this->isAdmin();
        $this->permises = [
            "add" => $this->is_admin,
            "edit" => $this->is_admin,
            "delete" => $this->is_admin
        ];
    }

    public function encryptToken($string, $seed) {
        $result = '';
        for($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($seed, ($i % strlen($seed))-1, 1);
            $char = chr(ord($char) + ord($keychar));
            $result .= $char;
        }
        $this->token_encrypt = base64_encode($result);
    }
}
