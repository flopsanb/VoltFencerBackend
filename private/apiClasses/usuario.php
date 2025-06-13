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

    public function create($data) {
        $permises = $this->authorization->permises;
        $id_rol_token = $permises['id_rol'] ?? null;
        $id_empresa_token = $permises['id_empresa'] ?? null;

        $usuario = $data['usuario'] ?? null;
        $password = isset($data['password']) ? md5($data['password']) : null;
        $email = $data['email'] ?? null;
        $nombre_publico = $data['nombre_publico'] ?? null;
        $id_rol = $data['id_rol'] ?? null;
        $id_empresa = $data['id_empresa'] ?? null;
        $observaciones = $data['observaciones'] ?? '';

        if ($id_rol_token == 3) {
            if ($id_empresa != $id_empresa_token || !in_array($id_rol, [3, 4])) {
                $this->status = false;
                $this->message = 'No puedes crear usuarios fuera de tu empresa o con rol superior';
                return;
            }
        }

        try {
            if (!empty($usuario) && !empty($password) && !empty($email) && isset($id_rol) && isset($id_empresa)) {
                $sql = $this->conexion->prepare("INSERT INTO usuarios 
                    (usuario, pass_user, email, id_rol, id_empresa, nombre_publico, habilitado, observaciones) 
                    VALUES (:usuario, :password, :email, :id_rol, :id_empresa, :nombre_publico, 1, :observaciones)");

                $sql->bindParam(":usuario", $usuario);
                $sql->bindParam(":password", $password);
                $sql->bindParam(":email", $email);
                $sql->bindParam(":id_rol", $id_rol);
                $sql->bindParam(":id_empresa", $id_empresa);
                $sql->bindParam(":nombre_publico", $nombre_publico);
                $sql->bindParam(":observaciones", $observaciones);

                if ($sql->execute()) {
                    $this->status = true;
                    $this->message = ADD_USER_OK;
                    $this->getUserById($this->conexion->lastInsertId());
                } else {
                    $this->status = false;
                    $this->message = ADD_USER_KO;
                }
            } else {
                $this->status = false;
                $this->message = 'Faltan campos obligatorios';
            }
        } catch (PDOException $error) {
            $this->status = false;
            $this->message = 'PDOException: ' . $error->getMessage();
        }

        $this->closeConnection();
    }

    public function update($data) {
        $permises = $this->authorization->permises;
        $id_rol_token = $permises['id_rol'] ?? null;
        $id_empresa_token = $permises['id_empresa'] ?? null;

        $id_usuario = $data['id_usuario'];
        $usuario = $data['usuario'];
        $email = $data['email'];
        $observaciones = $data['observaciones'];
        $nombre_publico = $data['nombre_publico'];
        $id_rol = $data['id_rol'];
        $id_empresa = $data['id_empresa'] ?? null;
        $habilitado = $data['habilitado'] ? 1 : 0;

        if ($id_rol_token == 3) {
            if ($id_empresa != $id_empresa_token || !in_array($id_rol, [3, 4])) {
                $this->status = false;
                $this->message = 'No puedes editar usuarios fuera de tu empresa o con rol superior';
                return;
            }
        }

        $contraSQL = "";
        if (!empty($data['password'])) {
            $password = md5($data['password']);
            $contraSQL = ", pass_user = :password ";
        }

        try {
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

            if (!empty($data['password'])) {
                $sql->bindParam(":password", $password);
            }

            if ($sql->execute()) {
                $this->status = true;
                $this->message = EDIT_USER_OK;
                $this->getUserById($id_usuario);
            } else {
                $this->message = EDIT_USER_KO;
            }
        } catch(PDOException $error) {
            $this->message = $error->getMessage();
        }
        $this->closeConnection();
    }

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

    public function existsUsuario(string $usuario, ?int $exclude_id = null): bool {
        try {
            $sql = "SELECT COUNT(*) as total FROM usuarios WHERE usuario = :usuario";
            if ($exclude_id !== null) {
                $sql .= " AND id_usuario != :exclude_id";
            }

            $stmt = $this->conexion->prepare($sql);
            $stmt->bindParam(':usuario', $usuario);
            if ($exclude_id !== null) {
                $stmt->bindParam(':exclude_id', $exclude_id, PDO::PARAM_INT);
            }

            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] > 0;

        } catch (PDOException $e) {
            $this->message = $e->getMessage();
            return false;
        }
    }

}
?>
