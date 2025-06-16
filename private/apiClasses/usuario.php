<?php
/**
 * Clase para gestión de usuarios
 * Permite operaciones CRUD según permisos.
 * 
 * @author Francisco Lopez
 * @version 1.4
 */

require_once __DIR__ . '/interfaces/crud.php';
require_once __DIR__ . '/../../conn.php';
require_once __DIR__ . '/auth.php';

class Usuario extends Conexion implements crud {

    public $status = false;
    public $message = NULL;
    public $data = NULL;
    private $authorization;


    const ROUTE = 'usuarios';

    function __construct($authorization) {
        parent::__construct();
        $this->authorization = $authorization;
    }

    /**
     * Obtiene todos los usuarios según el rol del usuario autenticado.
     * 
     * - Rol 1 (Administrador): Acceso a todos los usuarios de todas las empresas.
     * - Rol 2 (Gestor): Acceso a todos los usuarios de todas las empresas.
     * - Rol 3 (Usuario limitado): Acceso solo a usuarios de su propia empresa.
     * - Rol 4 (Usuario externo): Acceso a usuarios visibles de su propia empresa.
     */
    public function get() {
        try {
            $permises = $this->authorization->permises;
            $id_rol = $permises['id_rol'] ?? null;
            $id_empresa_token = $permises['id_empresa'] ?? null;

            $id_empresa_get = $_GET['id_empresa'] ?? null;
            $usar_empresa = $id_empresa_token;

            if (in_array($id_rol, [1, 2]) && $id_empresa_get !== null) {
                $usar_empresa = $id_empresa_get;
            }

            $sqlBase = "SELECT u.id_usuario, u.usuario, u.email, u.id_rol, u.observaciones, r.nombre_rol AS rol, u.id_empresa, e.nombre_empresa, u.observaciones, u.nombre_publico, u.habilitado
                        FROM usuarios u
                        INNER JOIN roles r ON u.id_rol = r.id_rol
                        LEFT JOIN empresas e ON u.id_empresa = e.id_empresa";

            if (!in_array($id_rol, [1, 2]) || $id_empresa_get !== null) {
                $sqlBase .= " WHERE u.id_empresa = :id_empresa";
            }

            $sqlBase .= " ORDER BY u.id_usuario ASC";
            $sql = $this->conexion->prepare($sqlBase);

            if (!in_array($id_rol, [1, 2]) || $id_empresa_get !== null) {
                $sql->bindParam(":id_empresa", $usar_empresa);
            }

            if ($sql->execute()) {
                $this->status = true;
                $this->data = $sql->fetchAll(PDO::FETCH_ASSOC);
            }

        } catch(PDOException $error) {
            $this->message = $error->getMessage();
        }
        $this->closeConnection();
    }

    /**
     * Obtiene un usuario por su ID.
     * 
     * @param int $id_usuario ID del usuario a recuperar.
     */
    public function create($data) {
        $permises = $this->authorization->permises;
        $id_rol_token = $permises['id_rol'] ?? null;
        $id_empresa_token = $permises['id_empresa'] ?? null;

        $usuario         = $data['usuario'] ?? null;
        $password        = isset($data['password']) ? md5($data['password']) : null;
        $email           = $data['email'] ?? null;
        $nombre_publico  = $data['nombre_publico'] ?? null;
        $id_rol          = $data['id_rol'] ?? null;
        $id_empresa      = $data['id_empresa'] ?? null;
        $habilitado      = isset($data['habilitado']) ? (int)$data['habilitado'] : 1;
        $observaciones   = $data['observaciones'] ?? '';

        // Restricción para admin_empresa
        if ($id_rol_token === 3) {
            if ($id_empresa != $id_empresa_token || !in_array($id_rol, [3, 4])) {
                $this->status = false;
                $this->message = '❌ No puedes crear usuarios fuera de tu empresa o con rol superior.';
                return;
            }
        }

        try {
            // Validación de campos obligatorios
            if (
                empty($usuario) ||
                empty($password) ||
                empty($email) ||
                empty($nombre_publico) ||
                !isset($id_rol) ||
                !isset($id_empresa)
            ) {
                $this->status = false;
                $this->message = '❌ Faltan campos obligatorios.';
                return;
            }

            // Comprobación de duplicados
            $check = $this->conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = :usuario OR email = :email");
            $check->bindParam(":usuario", $usuario);
            $check->bindParam(":email", $email);
            $check->execute();

            if ($check->fetchColumn() > 0) {
                $this->status = false;
                $this->message = '❌ Ya existe un usuario o correo electrónico con esos datos.';
                return;
            }

            $sql = $this->conexion->prepare("INSERT INTO usuarios 
                (usuario, pass_user, email, id_rol, id_empresa, nombre_publico, habilitado, observaciones) 
                VALUES (:usuario, :password, :email, :id_rol, :id_empresa, :nombre_publico, :habilitado, :observaciones)");

            $sql->bindParam(":usuario", $usuario);
            $sql->bindParam(":password", $password);
            $sql->bindParam(":email", $email);
            $sql->bindParam(":id_rol", $id_rol);
            $sql->bindParam(":id_empresa", $id_empresa);
            $sql->bindParam(":nombre_publico", $nombre_publico);
            $sql->bindParam(":habilitado", $habilitado, PDO::PARAM_INT);
            $sql->bindParam(":observaciones", $observaciones);

            if ($sql->execute()) {
                $this->status = true;
                $this->message = ADD_USER_OK;
                $this->getUserById($this->conexion->lastInsertId());
            } else {
                $this->status = false;
                $this->message = '❌ Error al insertar el usuario.';
            }

        } catch (PDOException $error) {
            $this->status = false;
            $this->message = '❌ PDOException: ' . $error->getMessage();
        }

        $this->closeConnection();
    }

    /**
     * Crea un nuevo usuario.
     * 
     * @param array $data Datos del usuario a crear.
     */
    public function update($data) {
        $permises = $this->authorization->permises;
        $id_rol_token = $permises['id_rol'] ?? null;
        $id_empresa_token = $permises['id_empresa'] ?? null;

        $id_usuario      = $data['id_usuario'] ?? null;
        $usuario         = $data['usuario'] ?? null;
        $email           = $data['email'] ?? null;
        $observaciones   = $data['observaciones'] ?? '';
        $nombre_publico  = $data['nombre_publico'] ?? null;
        $id_rol          = $data['id_rol'] ?? null;
        $id_empresa      = $data['id_empresa'] ?? null;
        $habilitado      = isset($data['habilitado']) ? (int)$data['habilitado'] : 1;
        $password        = isset($data['password']) && trim($data['password']) !== '' ? md5($data['password']) : null;

        // Validación básica
        if (!$id_usuario || !$usuario || !$email || !$nombre_publico || !isset($id_rol) || !isset($id_empresa)) {
            $this->status = false;
            $this->message = '❌ Faltan campos obligatorios.';
            return;
        }

        // Restricción de admin_empresa
        if ($id_rol_token == 3) {
            if ($id_empresa != $id_empresa_token || !in_array($id_rol, [3, 4])) {
                $this->status = false;
                $this->message = '❌ No puedes editar usuarios fuera de tu empresa o con rol superior.';
                return;
            }
        }

        try {
            // Comprobación de duplicados
            $check = $this->conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE (usuario = :usuario OR email = :email) AND id_usuario != :id");
            $check->bindParam(":usuario", $usuario);
            $check->bindParam(":email", $email);
            $check->bindParam(":id", $id_usuario);
            $check->execute();

            if ($check->fetchColumn() > 0) {
                $this->status = false;
                $this->message = '❌ Ya existe otro usuario o email igual.';
                return;
            }

            $contraSQL = $password !== null ? ", pass_user = :password" : "";

            $sql = $this->conexion->prepare("UPDATE usuarios SET
                id_rol = :id_rol,
                id_empresa = :id_empresa,
                usuario = :usuario,
                email = :email,
                nombre_publico = :nombre_publico,
                habilitado = :habilitado,
                observaciones = :observaciones
                $contraSQL
                WHERE id_usuario = :id_usuario");

            $sql->bindParam(":id_rol", $id_rol);
            $sql->bindParam(":id_empresa", $id_empresa);
            $sql->bindParam(":usuario", $usuario);
            $sql->bindParam(":email", $email);
            $sql->bindParam(":nombre_publico", $nombre_publico);
            $sql->bindParam(":habilitado", $habilitado);
            $sql->bindParam(":observaciones", $observaciones);
            $sql->bindParam(":id_usuario", $id_usuario);

            if ($password !== null) {
                $sql->bindParam(":password", $password);
            }

            if ($sql->execute()) {
                $this->status = true;
                $this->message = EDIT_USER_OK;
                $this->getUserById($id_usuario);
            } else {
                $this->status = false;
                $this->message = EDIT_USER_KO;
            }
        } catch (PDOException $error) {
            $this->status = false;
            $this->message = '❌ PDOException: ' . $error->getMessage();
        }

        $this->closeConnection();
    }

    /**
     * Elimina un usuario por su ID.
     * 
     * @param int $id ID del usuario a eliminar.
     */
    public function delete($id) {
        $permises = $this->authorization->permises;
        $id_rol_token = $permises['id_rol'] ?? null;
        $id_empresa_token = $permises['id_empresa'] ?? null;

        try {
            if ($id_rol_token == 3) {
                $stmt = $this->conexion->prepare("SELECT id_empresa FROM usuarios WHERE id_usuario = :id");
                $stmt->bindParam(":id", $id);
                $stmt->execute();
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$usuario || $usuario['id_empresa'] != $id_empresa_token) {
                    $this->status = false;
                    $this->message = 'No puedes borrar usuarios de otra empresa';
                    return;
                }
            }

            $sql = $this->conexion->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
            $sql->bindParam(":id", $id);
            if ($sql->execute()) {
                $this->status = true;
                $this->message = DELETE_USER_OK;
                $this->data = $id;
            } else {
                $this->message = DELETE_USER_KO;
            }
        } catch(PDOException $error) {
            $this->message = $error->getMessage();
        }
        $this->closeConnection();
    }

    /**
     * Obtiene un usuario por su ID.
     * 
     * @param int $id_usuario ID del usuario a recuperar.
     */
    private function getUserById($id_usuario) {
        try {
            $sql = $this->conexion->prepare("SELECT u.id_usuario, u.usuario, u.email, u.id_rol, r.nombre_rol AS rol, 
                                                    u.id_empresa, e.nombre_empresa, u.observaciones, 
                                                    u.nombre_publico, u.habilitado
                                             FROM usuarios u 
                                             INNER JOIN roles r ON u.id_rol = r.id_rol
                                             LEFT JOIN empresas e ON u.id_empresa = e.id_empresa
                                             WHERE u.id_usuario = :id_usuario");
            $sql->bindParam(":id_usuario", $id_usuario);
            if ($sql->execute()) {
                $this->data = $sql->fetch(PDO::FETCH_ASSOC);
            }
        } catch(PDOException $error) {
            $this->message = $error->getMessage();
        }
    }
}
?>
