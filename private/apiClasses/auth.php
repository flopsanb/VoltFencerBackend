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

        if (!$user || !$password) {
            $this->message = "Usuario o contraseña no proporcionados";
            return;
        }

        $this->getDatosUsuarios($user, $password);

        if ($this->datos_usuarios && $this->datos_usuarios["usuario"] === $user) {
            if ((int)$this->datos_usuarios["habilitado"] === 1) {
                try {
                    // 🔐 Nuevo token fuerte con random_bytes
                    $token = bin2hex(random_bytes(32)); // 64 caracteres, puro arte

                    // 🧠 Guardamos el token
                    $sql = $this->conexion->prepare("
                        UPDATE usuarios 
                        SET token_sesion = :token_sesion 
                        WHERE id_usuario = :id_usuario
                    ");
                    $sql->bindParam(":token_sesion", $token);
                    $sql->bindParam(":id_usuario", $this->datos_usuarios["id_usuario"]);
                    $sql->execute();

                    $this->data = [
                        "usuario"        => $this->datos_usuarios["usuario"],
                        "id_usuario"     => $this->datos_usuarios["id_usuario"],
                        "id_rol"         => $this->datos_usuarios["id_rol"],
                        "rol"            => $this->datos_usuarios["nombre_rol"],
                        "token"          => $token,
                        "nombre_publico" => $this->datos_usuarios["nombre_publico"],
                        "id_empresa"     => $this->datos_usuarios["id_empresa"],
                        "email"          => $this->datos_usuarios["email"]
                    ];
                    $this->status = true;

                } catch (Throwable $error) {
                    $this->message = 'Error al guardar el token';
                }

            } else {
                $this->message = 'Usuario inhabilitado';
                $this->data = ['habilitado' => 0];
            }
        } else {
            $this->message = 'Credenciales incorrectas';
        }

        $this->closeConnection();
    }


    private function getDatosUsuarios($user, $password) {
        $password_hashed = md5($password);

        try {
            $sql = $this->conexion->prepare("
                SELECT u.*, r.nombre_rol 
                FROM usuarios u 
                INNER JOIN roles r ON u.id_rol = r.id_rol 
                WHERE u.usuario = :user AND u.pass_user = :pass
            ");
            $sql->bindParam(":user", $user);
            $sql->bindParam(":pass", $password_hashed);
            $sql->execute();

            $this->datos_usuarios = $sql->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $error) {
            $this->message = 'Error al consultar usuario: ' . $error->getMessage();
        }
    }

    public function checkUsuario($token) {
        try {
            $sql = $this->conexion->prepare("
                SELECT u.usuario, r.nombre_rol, p.*
                FROM usuarios u 
                JOIN roles r ON u.id_rol = r.id_rol 
                JOIN permisos_rol p ON p.id_rol = u.id_rol
                WHERE u.token_sesion = :token
            ");
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
            $sql = $this->conexion->prepare("
                SELECT usuario, UNIX_TIMESTAMP(token_passwd_expira) AS token_expira 
                FROM usuarios 
                WHERE token_passwd = :token
            ");
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