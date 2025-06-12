<?php
/**
 * Clase de autenticación y gestión de sesiones
 * 
 * Implementa login, verificación de tokens y recuperación de permisos.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.2
 */

require_once __DIR__ . '/../../conn.php';

class Auth extends Conexion  {

    public  $status = false;
    public  $message = NULL;
    public  $data = NULL;
    private $datos_usuarios;

    function __construct (){
        parent::__construct();
    }

    public function doLogin($user, $password) {
        $user = trim($user);
        $password = trim($password);

        error_log("[🔐 Login] Intentando login para: $user");

        if (!$user || !$password) {
            $this->message = "Usuario o contraseña no proporcionados";
            error_log("[⚠️ Login] Faltan credenciales");
            return;
        }

        $this->getDatosUsuarios($user, $password);

        if ($this->datos_usuarios && $this->datos_usuarios["usuario"] === $user) {
            error_log("[✅ Login] Usuario encontrado en la BBDD");

            if ((int)$this->datos_usuarios["habilitado"] === 1) {
                try {
                    $token = bin2hex(random_bytes(32));

                    $sql = $this->conexion->prepare("UPDATE usuarios SET token_sesion = :token_sesion WHERE id_usuario = :id_usuario");
                    $sql->bindParam(":token_sesion", $token);
                    $sql->bindParam(":id_usuario", $this->datos_usuarios["id_usuario"]);
                    $sql->execute();

                    $this->data = [
                        "usuario"        => $this->datos_usuarios["usuario"],
                        "id_usuario"     => $this->datos_usuarios["id_usuario"],
                        "id_rol"         => $this->datos_usuarios["id_rol"],
                        "rol"            => $this->datos_usuarios["nombre_rol"] ?? null,
                        "token"          => $token,
                        "nombre_publico" => $this->datos_usuarios["nombre_publico"],
                        "id_empresa"     => $this->datos_usuarios["id_empresa"],
                        "email"          => $this->datos_usuarios["email"]
                    ];
                    $this->status = true;

                    error_log("[✅ Login] Login exitoso para: $user");

                } catch (Throwable $error) {
                    $this->message = 'Error al guardar el token';
                    error_log("[❌ Login] Error guardando token: " . $error->getMessage());
                }

            } else {
                $this->message = 'Usuario inhabilitado';
                $this->data = ['habilitado' => 0];
                error_log("[⚠️ Login] Usuario inhabilitado: $user");
            }
        } else {
            $this->message = 'Credenciales incorrectas';
            error_log("[❌ Login] Credenciales incorrectas para: $user");
        }

        $this->closeConnection();
    }

    private function getDatosUsuarios($user, $password) {
        $password_hashed = md5($password);
        error_log("[🔍 SQL] Buscando usuario: $user con password hash: $password_hashed");

        try {
            $sql = $this->conexion->prepare("SELECT * FROM usuarios WHERE usuario = :user AND pass_user = :pass");
            $sql->bindParam(":user", $user);
            $sql->bindParam(":pass", $password_hashed);
            $sql->execute();

            $this->datos_usuarios = $sql->fetch(PDO::FETCH_ASSOC);
            error_log("[📦 SQL] Resultado: " . json_encode($this->datos_usuarios));

        } catch (PDOException $error) {
            $this->message = 'Error al consultar usuario: ' . $error->getMessage();
            error_log("[❌ SQL] Error consultando usuario: " . $error->getMessage());
        }
    }

    public function checkUsuario($token) {
        try {
            $sql = $this->conexion->prepare("SELECT u.usuario, r.nombre_rol, p.* FROM usuarios u JOIN roles r ON u.id_rol = r.id_rol JOIN permisos_rol p ON p.id_rol = u.id_rol WHERE u.token_sesion = :token");
            $sql->bindParam(":token", $token);
            $sql->execute();

            $permisos = $sql->fetch(PDO::FETCH_ASSOC);

            if ($permisos) {
                $this->status = true;
                $this->data = $permisos;
            } else {
                $this->message = 'Token inválido o sin permisos';
            }

        } catch (PDOException $error) {
            $this->message = 'Error al verificar permisos: ' . $error->getMessage();
        }

        $this->closeConnection();
    }

    public function checkTokenPassword($token) {
        try {
            $sql = $this->conexion->prepare("SELECT usuario, UNIX_TIMESTAMP(token_passwd_expira) AS token_expira FROM usuarios WHERE token_passwd = :token");
            $sql->bindParam(":token", $token);
            $sql->execute();
            $datos = $sql->fetch(PDO::FETCH_ASSOC);

            if ($datos && time() < $datos['token_expira']) {
                $this->status = true;
            } else {
                $this->message = 'Token expirado o inválido';
            }

        } catch (PDOException $error) {
            $this->message = 'Error al validar el token: ' . $error->getMessage();
        }

        $this->closeConnection();
    }

    public function comprobarUsuario($usuario) {
        try {
            $sql = $this->conexion->prepare("SELECT id_usuario FROM usuarios WHERE usuario = :usuario");
            $sql->bindParam(":usuario", $usuario);
            $sql->execute();

            $resultado = $sql->fetch(PDO::FETCH_ASSOC);

            if ($resultado) {
                $this->status = true;
                $this->message = 'Usuario válido';
            } else {
                $this->message = 'El usuario no existe';
            }

        } catch (PDOException $e) {
            $this->message = 'Error al comprobar usuario: ' . $e->getMessage();
        }

        $this->closeConnection();
    }
}
?>
