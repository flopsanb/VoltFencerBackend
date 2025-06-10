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

class Auth extends Conexion {

    public $status = false;
    public $message = null;
    public $data = null;

    private $datos_usuarios;
    const SEED = "An[oojlHxsBnqD=FwiP[k[L3YRv@ei|M=}|SZ}~qynM~Gc8p3x0L1Yxs[dtB";

    public function __construct() {
        parent::__construct();
    }

    public function doLogin($user, $password) {
        $this->getDatosUsuarios($user, $password);

        if ($this->datos_usuarios && $this->datos_usuarios["usuario"] === $user) {
            if ((int) $this->datos_usuarios["habilitado"] === 1) {
                try {
                    $random = rand(1000000, 9999999);
                    $token_user = $this->datos_usuarios["id_usuario"] . $this->datos_usuarios["usuario"] . $random;

                    $authorization = new Authorization();
                    $authorization->encryptToken($token_user, self::SEED);

                    $stmt = $this->conexion->prepare("UPDATE usuarios SET token_sesion = :token WHERE id_usuario = :id_usuario");
                    $stmt->bindParam(":token", $authorization->token_encrypt);
                    $stmt->bindParam(":id_usuario", $this->datos_usuarios["id_usuario"]);
                    $stmt->execute();

                    $this->data = [
                        "usuario" => $this->datos_usuarios["usuario"],
                        "id_usuario" => $this->datos_usuarios["id_usuario"],
                        "id_rol" => $this->datos_usuarios["id_rol"],
                        "rol" => $this->datos_usuarios["nombre_rol"],
                        "token" => $authorization->token_encrypt,
                        "nombre_publico" => $this->datos_usuarios["nombre_publico"],
                        "id_empresa" => $this->datos_usuarios["id_empresa"],
                        "email" => $this->datos_usuarios["email"]
                    ];

                    $this->status = true;
                    $this->message = 'Login correcto';

                } catch (PDOException $e) {
                    $this->message = 'Error al guardar el token: ' . $e->getMessage();
                }
            } else {
                $this->message = 'Usuario inhabilitado';
                $this->data = ['habilitado' => 0];
            }
        } else {
            $this->message = 'Credenciales incorrectas';
        }

        error_log("Intento de login: {$this->message}");
        $this->closeConnection();
    }

    private function getDatosUsuarios($user, $password) {
        try {
            $hash = md5($password);

            $stmt = $this->conexion->prepare(
                "SELECT u.*, r.nombre_rol 
                 FROM usuarios u 
                 INNER JOIN roles r ON u.id_rol = r.id_rol 
                 WHERE u.usuario = :user AND u.pass_user = :pass"
            );
            $stmt->bindParam(":user", $user);
            $stmt->bindParam(":pass", $hash);
            $stmt->execute();

            $this->datos_usuarios = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->message = 'Error al consultar usuario: ' . $e->getMessage();
        }
    }

    public function checkUsuario($token) {
        try {
            $stmt = $this->conexion->prepare(
                "SELECT u.usuario, u.id_usuario, u.id_rol, u.id_empresa, r.nombre_rol, p.* 
                 FROM usuarios u 
                 JOIN roles r ON u.id_rol = r.id_rol 
                 JOIN permisos_rol p ON p.id_rol = u.id_rol 
                 WHERE u.token_sesion = :token"
            );
            $stmt->bindParam(":token", $token);
            $stmt->execute();

            $permisos = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($permisos) {
                $this->status = true;
                $this->data = $permisos;
                $this->message = 'Token válido';
            } else {
                $this->message = 'Token inválido o sin permisos';
            }

        } catch (PDOException $e) {
            $this->message = 'Error al verificar permisos: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    public function checkTokenPassword($token) {
        try {
            $stmt = $this->conexion->prepare(
                "SELECT usuario, UNIX_TIMESTAMP(token_passwd_expira) AS token_expira 
                 FROM usuarios WHERE token_passwd = :token"
            );
            $stmt->bindParam(":token", $token);
            $stmt->execute();

            $datos = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($datos && time() < $datos['token_expira']) {
                $this->status = true;
                $this->message = 'Token válido';
            } else {
                $this->message = 'Token expirado o inválido';
            }

        } catch (PDOException $e) {
            $this->message = 'Error al validar el token: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    public function comprobarUsuario($usuario) {
        try {
            $stmt = $this->conexion->prepare("SELECT id_usuario FROM usuarios WHERE usuario = :usuario");
            $stmt->bindParam(":usuario", $usuario);
            $stmt->execute();

            if ($stmt->fetch()) {
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
