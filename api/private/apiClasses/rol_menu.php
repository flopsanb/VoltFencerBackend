<?php
/**
 * Clase para gestión de relaciones entre roles y menús
 * 
 * Esta clase implementa operaciones CRUD para administrar la asignación
 * de opciones de menú a los diferentes roles del sistema. Permite configurar
 * qué elementos del menú son accesibles para cada rol y qué operaciones
 * (añadir, editar, eliminar) pueden realizarse en cada opción.
 * 
 * @author  [Francisco Lopez Sanchez]
 * @version 1.1
 */

require_once(__DIR__ . '/interfaces/crud.php');
require_once(__DIR__ . '/../conn.php');

class RolMenu extends Conexion implements crud {

    public $status = false;
    public $message = null;
    public $data = null;

    const ROUTE = 'roles-menu';

    function __construct() {
        parent::__construct();
    }

    public function get() {
        try {
            $sql = $this->conexion->prepare("SELECT * FROM sgi_vista_rol_menu");
            $sql->execute();
            $this->data = $sql->fetchAll(PDO::FETCH_ASSOC);
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

    public function create($data) {
        if (!$this->validarCamposBase($data)) return;

        try {
            $sql = $this->conexion->prepare("
                INSERT INTO sgi_rol_menu 
                    (id_opcion_menu, id_grupo_menu, id_rol, permiso_post, permiso_put, permiso_delete, observaciones) 
                VALUES 
                    (:id_opcion, :id_grupo, :id_rol, :permiso_post, :permiso_put, :permiso_delete, :observaciones)");

            $this->bindParametrosBase($sql, $data);
            $resultado = $sql->execute();

            if ($resultado) {
                $this->status = true;
                $this->message = ADD_ROL_MENU_OK;
                $this->getRolMenuById($this->conexion->lastInsertId());
            } else {
                $this->message = ADD_ROL_MENU_KO;
            }

        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

    public function update($data) {
        if (empty($data['id_rol_menu']) || !$this->validarCamposBase($data)) {
            $this->message = 'Faltan campos obligatorios.';
            return;
        }

        try {
            $sql = $this->conexion->prepare("
                UPDATE sgi_rol_menu SET
                    id_opcion_menu = :id_opcion,
                    id_grupo_menu = :id_grupo,
                    id_rol = :id_rol,
                    permiso_post = :permiso_post,
                    permiso_put = :permiso_put,
                    permiso_delete = :permiso_delete,
                    observaciones = :observaciones
                WHERE id_rol_menu = :id_rol_menu");

            $sql->bindParam(":id_rol_menu", $data['id_rol_menu'], PDO::PARAM_INT);
            $this->bindParametrosBase($sql, $data);

            $resultado = $sql->execute();

            if ($resultado) {
                $this->status = true;
                $this->message = EDIT_ROL_MENU_OK;
                $this->getRolMenuById($data['id_rol_menu']);
            } else {
                $this->message = EDIT_ROL_MENU_KO;
            }

        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

    public function delete($id) {
        if (empty($id)) {
            $this->message = 'ID de relación no proporcionado.';
            return;
        }

        try {
            $sql = $this->conexion->prepare("DELETE FROM sgi_rol_menu WHERE id_rol_menu = :id_rol_menu");
            $sql->bindParam(":id_rol_menu", $id, PDO::PARAM_INT);

            if ($sql->execute()) {
                $this->status = true;
                $this->message = DELETE_ROL_MENU_OK;
                $this->data = $id;
            } else {
                $this->message = DELETE_ROL_MENU_KO;
            }

        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

    private function getRolMenuById($id_rol_menu) {
        try {
            $sql = $this->conexion->prepare("SELECT * FROM sgi_vista_rol_menu WHERE id_rol_menu = :id_rol_menu");
            $sql->bindParam(":id_rol_menu", $id_rol_menu, PDO::PARAM_INT);
            $sql->execute();
            $this->data = $sql->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }
    }

    private function validarCamposBase($data) {
        return isset($data['id_rol'], $data['id_opcion'], $data['id_grupo']);
    }

    private function bindParametrosBase(&$sql, $data) {
        $sql->bindParam(":id_opcion", $data['id_opcion'], PDO::PARAM_INT);
        $sql->bindParam(":id_grupo", $data['id_grupo'], PDO::PARAM_INT);
        $sql->bindParam(":id_rol", $data['id_rol'], PDO::PARAM_INT);
        $sql->bindParam(":permiso_post", $data['add'], PDO::PARAM_INT);
        $sql->bindParam(":permiso_put", $data['edit'], PDO::PARAM_INT);
        $sql->bindParam(":permiso_delete", $data['delete'], PDO::PARAM_INT);
        $sql->bindValue(":observaciones", $data['observaciones'] ?? '', PDO::PARAM_STR);
    }
}
?>
