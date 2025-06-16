<?php
/**
 * Clase Auth: gestión de autenticación y validación de tokens
 * 
 * Esta clase centraliza la lógica de login de usuarios, verificación de tokens de sesión,
 * validación de recuperación de contraseña y comprobación de existencia de usuario.
 * Hereda de la clase `Conexion` para reutilizar el sistema de conexión a base de datos
 * y garantiza que las operaciones estén correctamente encapsuladas y protegidas frente a errores.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.2
 */

require_once __DIR__ . '/../../conn.php';

class Auth extends Conexion  {

    public  $status = false;
    public  $message = NULL;
    public  $data = NULL;

    // Variable privada para almacenar temporalmente los datos del usuario autenticado
    private $datos_usuarios;

    /**
     * Constructor que inicializa la conexión a base de datos
     */
    function __construct (){
        parent::__construct();
    }

    /**
     * Método de autenticación (login)
     * 
     * Valida credenciales, comprueba si el usuario está habilitado y genera un nuevo token de sesión.
     * El token se guarda en la base de datos y se devuelve al cliente junto con los datos clave del usuario.
     */
    public function doLogin($user, $password) {
        $user = trim($user);
        $password = trim($password);

        // Validación de campos vacíos
        if (!$user || !$password) {
            $this->message = "Usuario o contraseña no proporcionados";
            return;
        }

        // Obtención de los datos del usuario en base a credenciales
        $this->getDatosUsuarios($user, $password);

        // Si el usuario existe y coincide
        if ($this->datos_usuarios && $this->datos_usuarios["usuario"] === $user) {

            // Comprobar si está habilitado
            if ((int)$this->datos_usuarios["habilitado"] === 1) {
                try {
                    // Generar un token aleatorio
                    $token = bin2hex(random_bytes(32));

                    // Guardar el token en base de datos
                    $sql = $this->conexion->prepare("UPDATE usuarios SET token_sesion = :token_sesion WHERE id_usuario = :id_usuario");
                    $sql->bindParam(":token_sesion", $token);
                    $sql->bindParam(":id_usuario", $this->datos_usuarios["id_usuario"]);
                    $sql->execute();

                    // Preparar datos a enviar al frontend tras login exitoso
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

                } catch (Throwable $error) {
                    $this->message = 'Error al guardar el token';
                }

            } else {
                 // Usuario existente pero deshabilitado
                $this->message = 'Usuario inhabilitado';
                $this->data = ['habilitado' => 0];
            }
        } else {
            // Usuario no encontrado o credenciales incorrectas
            $this->message = 'Credenciales incorrectas';
        }

        $this->closeConnection();
    }

    /**
     * Método privado para obtener los datos del usuario autenticado
     * 
     * Verifica el usuario y contraseña (MD5) en la base de datos y guarda el resultado en memoria.
     */
    private function getDatosUsuarios($user, $password) {
        $password_hashed = md5($password);  // Encriptación básica

        try {
            $sql = $this->conexion->prepare("SELECT * FROM usuarios WHERE usuario = :user AND pass_user = :pass");
            $sql->bindParam(":user", $user);
            $sql->bindParam(":pass", $password_hashed);
            $sql->execute();

            $this->datos_usuarios = $sql->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $error) {
            $this->message = 'Error al consultar usuario: ' . $error->getMessage();
        }
    }

    /**
     * Verifica el token de sesión enviado desde el frontend
     * 
     * Devuelve los permisos completos asociados al rol del usuario autenticado si el token es válido.
     */
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
    
    /**
     * Verifica si un nombre de usuario ya existe en la base de datos
     * 
     * Utilizado principalmente en formularios de registro o recuperación de cuenta.
     */
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
