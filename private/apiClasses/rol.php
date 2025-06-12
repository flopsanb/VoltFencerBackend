<?php
/**
 * Clase para gestión de roles
 * 
 * Esta clase implementa operaciones CRUD para los roles del sistema.
 * Con control de acceso y protección al rol Superadmin (id=1).
 * 
 * @author  Francisco Lopez
 * @version 1.1
 */

require_once 'interfaces/crud.php';
require_once __DIR__ . '/../../conn.php';

class Rol extends Conexion implements crud {

    public $status = false;
    public $message = null;
    public $data = null;

    private $auth;

    const ROUTE = 'roles';

    public function __construct($auth) {
        parent::__construct();
        $this->auth = $auth;
    }

    public function get() {
        try {
            $id_rol_actual = $this->auth->permises['id_rol'] ?? null;

            if (!$id_rol_actual) {
                $this->status = false;
                $this->message = 'No se pudo determinar el rol del usuario.';
                return;
            }

            $sql = $this->conexion->prepare("SELECT * FROM roles WHERE id_rol >= :mi_rol ORDER BY nombre_rol");
            $sql->bindParam(':mi_rol', $id_rol_actual, PDO::PARAM_INT);
            $sql->execute();

            $this->data = $sql->fetchAll(PDO::FETCH_ASSOC);
            $this->status = true;

        } catch (PDOException $e) {
            $this->message = 'Error al obtener roles: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    public function create($data) {
        $rol = $data['nombre_rol'] ?? null;

        if ($rol && strtolower($rol) !== 'superadmin') {
            try {
                $sql = $this->conexion->prepare("INSERT INTO roles (nombre_rol) VALUES (:rol)");
                $sql->bindParam(":rol", $rol, PDO::PARAM_STR);
                $sql->execute();

                $this->status = true;
                $this->message = ADD_ROL_OK;
                $this->getRolById($this->conexion->lastInsertId());

            } catch (PDOException $e) {
                $this->message = 'Error al crear rol: ' . $e->getMessage();
            }
        } else {
            $this->message = 'No se puede crear el rol Superadmin.';
        }

        $this->closeConnection();
    }

    public function update($data) {
        $id = $data['id_rol'] ?? null;
        $rol = $data['nombre_rol'] ?? null;
        $id_rol_actual = $this->auth->permises['id_rol'] ?? null;

        if (!$id || !$rol) {
            $this->message = 'Datos incompletos.';
            return;
        }

        if (strtolower($rol) === 'superadmin' || $id == 1) {
            $this->message = 'No puedes modificar el rol Superadmin.';
            return;
        }

        if (!in_array($id_rol_actual, [1, 2])) {
            $this->message = 'No tienes permisos para modificar roles.';
            return;
        }

        try {
            $sql = $this->conexion->prepare("UPDATE roles SET nombre_rol = :rol WHERE id_rol = :id_rol AND id_rol != 1");
            $sql->bindParam(":rol", $rol, PDO::PARAM_STR);
            $sql->bindParam(":id_rol", $id, PDO::PARAM_INT);
            $sql->execute();

            $this->status = true;
            $this->message = EDIT_ROL_OK;
            $this->getRolById($id);

        } catch (PDOException $e) {
            $this->message = 'Error al modificar rol: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    public function delete($id) {
        $id_rol_actual = $this->auth->permises['id_rol'] ?? null;

        if (!$id) {
            $this->message = 'ID de rol no proporcionado.';
            return;
        }

        if ($id == 1) {
            $this->message = 'No se puede eliminar el rol Superadmin.';
            return;
        }

        if (!in_array($id_rol_actual, [1, 2])) {
            $this->message = 'No tienes permisos para eliminar roles.';
            return;
        }

        try {
            $sql = $this->conexion->prepare("DELETE FROM roles WHERE id_rol = :id_rol AND id_rol != 1");
            $sql->bindParam(":id_rol", $id, PDO::PARAM_INT);
            $sql->execute();

            $this->status = true;
            $this->message = DELETE_ROL_OK;
            $this->data = $id;

        } catch (PDOException $e) {
            $this->message = 'Error al eliminar rol: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    private function getRolById($id_rol) {
        try {
            $sql = $this->conexion->prepare("SELECT * FROM roles WHERE id_rol = :id_rol");
            $sql->bindParam(":id_rol", $id_rol, PDO::PARAM_INT);
            $sql->execute();
            $this->data = $sql->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $this->message = 'Error al obtener el rol por ID: ' . $e->getMessage();
        }
    }
}
