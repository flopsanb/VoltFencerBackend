<?php
/**
 * Clase para gestión de roles
 * 
 * Esta clase implementa operaciones CRUD para los roles del sistema.
 * Permite la creación, consulta, actualización y eliminación de roles,
 * con validaciones de seguridad para proteger roles críticos como Superadmin.
 * 
 * @author  [Francisco Lopez Sanchez]
 * @version 1.0
 */

require_once 'interfaces/crud.php';
require_once __DIR__ . '/../../conn.php';

class Rol extends Conexion implements crud {

    public $status = false;
    public $message = NULL;
    public $data = NULL;
    const ROUTE = 'roles';

    function __construct () {
        parent::__construct();
    }

    public function get() {
        try {
            $sql = $this->conexion->prepare("SELECT * FROM roles ORDER BY nombre_rol");
            $sql->execute();

            $this->data = $sql->fetchAll(PDO::FETCH_ASSOC);
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

    public function create($data) {
        $rol = $data['nombre_rol'] ?? null;

        if ($rol && strtolower($rol) !== 'superadmin') {
            try {
                $sql = $this->conexion->prepare("INSERT INTO roles (nombre_rol) VALUES (:rol)");
                $sql->bindParam(":rol", $rol, PDO::PARAM_STR);

                if ($sql->execute()) {
                    $this->status = true;
                    $this->message = ADD_ROL_OK;
                    $this->getRolById($this->conexion->lastInsertId());
                } else {
                    $this->message = ADD_ROL_KO;
                }
            } catch (PDOException $e) {
                $this->message = $e->getMessage();
            }
        } else {
            $this->message = 'No se puede crear el rol Superadmin.';
        }

        $this->closeConnection();
    }

    public function update($data) {
        $authorization = $GLOBALS['authorization'];
        $id_rol_actual = $authorization->permises['id_rol'] ?? null;

        $id = $data['id_rol'] ?? null;
        $rol = $data['nombre_rol'] ?? null;

        if (!$id || !$rol) {
            $this->message = 'Datos incompletos.';
            return;
        }

        // Evitar modificar el rol superadmin
        if (strtolower($rol) === 'superadmin' || $id == 1) {
            $this->message = 'No puedes modificar el rol Superadmin.';
            return;
        }

        // Solo superadmin y admin pueden modificar roles
        if (!in_array($id_rol_actual, [1, 2])) {
            $this->message = 'No tienes permisos para modificar roles.';
            return;
        }

        try {
            $sql = $this->conexion->prepare("UPDATE roles SET nombre_rol = :rol WHERE id_rol = :id_rol AND id_rol != 1");
            $sql->bindParam(":rol", $rol, PDO::PARAM_STR);
            $sql->bindParam(":id_rol", $id, PDO::PARAM_INT);

            if ($sql->execute()) {
                $this->status = true;
                $this->message = EDIT_ROL_OK;
                $this->getRolById($id);
            } else {
                $this->message = EDIT_ROL_KO;
            }
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

    public function delete($id) {
        $authorization = $GLOBALS['authorization'];
        $id_rol_actual = $authorization->permises['id_rol'] ?? null;

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

            if ($sql->execute()) {
                $this->status = true;
                $this->message = DELETE_ROL_OK;
                $this->data = $id;
            } else {
                $this->message = DELETE_ROL_KO;
            }
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
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
            $this->message = $e->getMessage();
        }
    }
}
?>
