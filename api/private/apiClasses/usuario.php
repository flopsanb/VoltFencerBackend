<?php
/**
 * Clase para gestión de usuarios
 * Permite operaciones CRUD y modificación de perfil según permisos.
 * 
 * @author Francisco Lopez
 * @version 1.2
 */

require_once __DIR__ . '/interfaces/crud.php';
require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/auth.php';

class Usuario extends Conexion implements crud {

    public $status = false;
    public $message = null;
    public $data = [];

    const ROUTE = 'usuarios';
    const ROUTE_PROFILE = 'profile';

    function __construct() {
        parent::__construct();
    }

    private function validarRolEmpresa($id_empresa, $id_rol) {
        $auth = $GLOBALS['authorization']->permises;
        return $auth['id_rol'] === 3 && ($id_empresa != $auth['id_empresa'] || !in_array($id_rol, [3, 4]));
    }

    public function get() {
        try {
            $authorization = $GLOBALS['authorization'];
            $permises = $authorization->permises;
            $id_rol = $permises['id_rol'] ?? null;
            $id_empresa_token = $permises['id_empresa'] ?? null;
            $id_empresa_get = $_GET['id_empresa'] ?? null;
            $usar_empresa = $id_empresa_token;

            if (in_array($id_rol, [1, 2]) && $id_empresa_get !== null) {
                $usar_empresa = $id_empresa_get;
            }

            $sqlBase = "SELECT u.id_usuario, u.usuario, u.email, u.id_rol, u.observaciones,
                        r.nombre_rol AS rol, u.id_empresa, e.nombre_empresa, 
                        u.nombre_publico, u.habilitado
                        FROM usuarios u
                        INNER JOIN roles r ON u.id_rol = r.id_rol
                        LEFT JOIN empresas e ON u.id_empresa = e.id_empresa";

            if (!in_array($id_rol, [1, 2]) || $id_empresa_get !== null) {
                $sqlBase .= " WHERE u.id_empresa = :id_empresa";
            }

            $sqlBase .= " ORDER BY u.id_usuario ASC";
            $stmt = $this->conexion->prepare($sqlBase);

            if (!in_array($id_rol, [1, 2]) || $id_empresa_get !== null) {
                $stmt->bindParam(':id_empresa', $usar_empresa);
            }

            if ($stmt->execute()) {
                $this->status = true;
                $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
        }
        $this->closeConnection();
    }

    public function create($data) {
        $auth = $GLOBALS['authorization']->permises;

        $usuario = $data['usuario'] ?? null;
        $password = isset($data['password']) ? md5($data['password']) : null;
        $email = $data['email'] ?? null;
        $nombre_publico = $data['nombre_publico'] ?? null;
        $id_rol = $data['id_rol'] ?? null;
        $id_empresa = $data['id_empresa'] ?? null;
        $observaciones = $data['observaciones'] ?? '';

        if ($this->validarRolEmpresa($id_empresa, $id_rol)) {
            $this->status = false;
            $this->message = 'No puedes crear usuarios fuera de tu empresa o con rol superior';
            return;
        }

        try {
            if ($usuario && $password && $email && $id_rol && $id_empresa) {
                $stmt = $this->conexion->prepare("INSERT INTO usuarios 
                    (usuario, pass_user, email, id_rol, id_empresa, nombre_publico, habilitado, observaciones) 
                    VALUES (:usuario, :password, :email, :id_rol, :id_empresa, :nombre_publico, 1, :observaciones)");

                $stmt->bindParam(":usuario", $usuario);
                $stmt->bindParam(":password", $password);
                $stmt->bindParam(":email", $email);
                $stmt->bindParam(":id_rol", $id_rol);
                $stmt->bindParam(":id_empresa", $id_empresa);
                $stmt->bindParam(":nombre_publico", $nombre_publico);
                $stmt->bindParam(":observaciones", $observaciones);

                if ($stmt->execute()) {
                    $this->status = true;
                    $this->message = 'Usuario creado correctamente';
                    $this->getUserById($this->conexion->lastInsertId());
                } else {
                    $this->status = false;
                    $this->message = 'Error al crear el usuario';
                }
            } else {
                $this->status = false;
                $this->message = 'Faltan campos obligatorios';
            }
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = 'PDO: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    public function update($data) {
        $auth = $GLOBALS['authorization']->permises;
        $id_rol_token = $auth['id_rol'];
        $id_empresa_token = $auth['id_empresa'];

        $id_usuario = $data['id_usuario'];
        $usuario = $data['usuario'];
        $email = $data['email'];
        $observaciones = $data['observaciones'];
        $nombre_publico = $data['nombre_publico'];
        $id_rol = $data['id_rol'];
        $id_empresa = $data['id_empresa'];
        $habilitado = $data['habilitado'] ? 1 : 0;

        if ($this->validarRolEmpresa($id_empresa, $id_rol)) {
            $this->status = false;
            $this->message = 'No puedes editar usuarios fuera de tu empresa o con rol superior';
            return;
        }

        $passwordQuery = '';
        $password = null;
        if (!empty($data['password'])) {
            $passwordQuery = ', pass_user = :password';
            $password = md5($data['password']);
        }

        try {
            $stmt = $this->conexion->prepare("UPDATE usuarios SET
                id_rol = :id_rol,
                id_empresa = :id_empresa,
                usuario = :usuario,
                email = :email,
                nombre_publico = :nombre_publico,
                habilitado = :habilitado,
                observaciones = :observaciones
                $passwordQuery
                WHERE id_usuario = :id_usuario");

            $stmt->bindParam(":id_rol", $id_rol);
            $stmt->bindParam(":id_empresa", $id_empresa);
            $stmt->bindParam(":usuario", $usuario);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":nombre_publico", $nombre_publico);
            $stmt->bindParam(":habilitado", $habilitado);
            $stmt->bindParam(":observaciones", $observaciones);
            $stmt->bindParam(":id_usuario", $id_usuario);
            if ($password) {
                $stmt->bindParam(":password", $password);
            }

            if ($stmt->execute()) {
                $this->status = true;
                $this->message = 'Usuario actualizado correctamente';
                $this->getUserById($id_usuario);
            } else {
                $this->status = false;
                $this->message = 'Error al actualizar';
            }
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

    public function updateProfile($data, $token) {
        $usuario = $data['correoUsuario'];
        $nombre_publico = $data['nombrePublico'];

        $passwordQuery = '';
        $password = null;
        if (!empty($data['nuevaPassword']) && $data['nuevaPassword'] === $data['confirmarNuevaPassword']) {
            $password = md5($data['nuevaPassword']);
            $passwordQuery = ', pass_user = :password';
        }

        try {
            $stmt = $this->conexion->prepare("UPDATE usuarios SET
                usuario = :usuario,
                nombre_publico = :nombre_publico
                $passwordQuery
                WHERE token_sesion = :token");

            $stmt->bindParam(":usuario", $usuario);
            $stmt->bindParam(":nombre_publico", $nombre_publico);
            $stmt->bindParam(":token", $token);
            if ($password) {
                $stmt->bindParam(":password", $password);
            }

            if ($stmt->execute()) {
                $this->status = true;
                $this->message = 'Perfil actualizado correctamente';
            } else {
                $this->status = false;
                $this->message = 'No se pudo actualizar el perfil';
            }
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

    public function delete($id) {
        $auth = $GLOBALS['authorization']->permises;
        $id_rol_token = $auth['id_rol'];
        $id_empresa_token = $auth['id_empresa'];

        try {
            if ($id_rol_token === 3) {
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

            $stmt = $this->conexion->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                $this->status = true;
                $this->message = 'Usuario eliminado correctamente';
                $this->data = $id;
            } else {
                $this->status = false;
                $this->message = 'Error al eliminar el usuario';
            }
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

    private function getUserById($id_usuario) {
        try {
            $stmt = $this->conexion->prepare("SELECT u.id_usuario, u.usuario, u.email, u.id_rol, r.nombre_rol AS rol, 
                                              u.id_empresa, e.nombre_empresa, u.observaciones, 
                                              u.nombre_publico, u.habilitado
                                              FROM usuarios u
                                              INNER JOIN roles r ON u.id_rol = r.id_rol
                                              LEFT JOIN empresas e ON u.id_empresa = e.id_empresa
                                              WHERE u.id_usuario = :id_usuario");
            $stmt->bindParam(":id_usuario", $id_usuario);
            $stmt->execute();
            $this->data = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = $e->getMessage();
        }
    }
}
