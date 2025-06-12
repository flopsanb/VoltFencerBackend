<?php
declare(strict_types=1);

/**
 * Conexión y Autorización para API REST adaptado para entorno online
 * 
 * Incluye:
 * - Uso de variables de entorno para DB
 * - Corrección de deprecated property
 * - Mejoras para evitar problemas con session_start
 * 
 *  * - Usa .env en local
 * - Usa getenv() en producción (Railway, Render, etc.)
 * 
 * @author  Francisco Lopez
 * @version 2.1
 */

// Cargar .env si existe
$env_path = __DIR__ . '/.env';
if (file_exists($env_path)) {
    $env = parse_ini_file($env_path);
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', '3600');
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

    public function __construct() {
        self::$DB_HOST     = getenv('DB_HOST')     ?: 'localhost';
        self::$DB_USERNAME = getenv('DB_USER')     ?: 'root';
        self::$DB_PASSWORD = getenv('DB_PASSWORD') ?: 'root';
        self::$DB_NAME     = getenv('DB_NAME')     ?: 'gestion_proyectos';
        self::$DB_PORT     = getenv('DB_PORT')     ?: '3306';

        $this->conectar();
        $this->setVariablesSession();
    }

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
            error_log("[❌ ERROR DB] " . $e->getMessage());
            http_response_code(500);
            echo json_encode(["ok" => false, "message" => "Error interno del servidor"]);
            exit;
        }
    }

    private function setVariablesSession(): void {
        if (isset($_SESSION['id_usuario'])) {
            $this->id_usuario = $_SESSION['id_usuario'];
        }
    }

    public function closeConnection(): void {
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
    public $is_admin = false;
    public $permises = null;
    public $have_permision = false;
    public $usuario = null;
    private $id_rol = null;

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

    private function getBearerToken(): void {
        $header = $this->getAuthorizationHeader();
        if (!empty($header) && preg_match('/Bearer\s(\S+)/', $header, $matches)) {
            $this->token = $matches[1];
        }
    }

    public function comprobarToken(): void {
        $this->getBearerToken();

        if (empty($this->token)) {
            $this->token_valido = false;
            return;
        }

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
            $this->id_rol = $datos["id_rol"];
            $this->is_admin = ($this->id_rol == 1);
        } else {
            $this->token_valido = false;
        }
    }

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

    public function havePermision(string $method, string $route): void {
        $this->have_permision = false;

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
            'permisos-rol' => 'permisos_globales',
            'proyectos'    => 'proyectos',
            'logs'         => 'logs_empresa'
        ];

        $accion = $acciones[strtoupper($method)] ?? null;
        $ruta = $ruta[$route] ?? $route;

        if (!$accion || !$ruta) return;

        $campo = "{$accion}_{$ruta}";

        if (isset($this->permises[$campo]) && $this->permises[$campo] == 1) {
            $this->have_permision = true;
        }
    }

    public function havePermissionByTipo(string $tipo_permiso): bool {
        return isset($this->permises[$tipo_permiso]) && $this->permises[$tipo_permiso] == 1;
    }
}
