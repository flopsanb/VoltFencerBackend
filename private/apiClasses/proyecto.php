<?php
/**
 * Clase para gestión de proyectos
 * 
 * CRUD de proyectos con control de acceso por rol:
 * - Admin global: accede y gestiona todo
 * - Admin empresa: solo su empresa, sin poder deshabilitar
 * - Empleado: solo ve visibles y habilitados de su empresa
 * 
 * @author Francisco Lopez Sanchez
 * @version 1.1
 */

require_once __DIR__ . '/interfaces/crud.php';
require_once __DIR__ . '/../../conn.php';

class Proyecto extends Conexion implements crud {

    public $status = false;
    public $message = null;
    public $data = null;
    const ROUTE = 'proyectos';

    function __construct() {
        parent::__construct();
    }

    public function get() {
        try {
            $authorization = $GLOBALS['authorization'];
            $datos = $authorization->permises;
            $id_rol = $datos['id_rol'] ?? null;
            $id_empresa = $datos['id_empresa'] ?? null;

            $sqlBase = "SELECT p.*, e.nombre_empresa FROM proyectos p
                        JOIN empresas e ON p.id_empresa = e.id_empresa";

            if (in_array($id_rol, [3, 4])) {
                // admin_empresa o empleado_empresa
                $sqlBase .= " WHERE p.id_empresa = :id_empresa AND p.habilitado = 1";
                if ($id_rol == 4) {
                    // empleados solo ven visibles
                    $sqlBase .= " AND p.visible = 1";
                }
            }

            $sqlBase .= " ORDER BY p.id_proyecto ASC";
            $sql = $this->conexion->prepare($sqlBase);

            if (in_array($id_rol, [3, 4])) {
                $sql->bindParam(':id_empresa', $id_empresa);
            }

            $sql->execute();
            $this->data = $sql->fetchAll(PDO::FETCH_ASSOC);
            $this->status = true;

        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }


    public function create($data) {
        try {
            $sql = $this->conexion->prepare("INSERT INTO proyectos (nombre_proyecto, id_empresa, iframe_proyecto, visible, habilitado) VALUES (:nombre_proyecto, :id_empresa, :iframe_proyecto, :visible, :habilitado)");

            $sql->bindParam(':nombre_proyecto', $data['nombre_proyecto'], PDO::PARAM_STR);
            $sql->bindParam(':id_empresa', $data['id_empresa'], PDO::PARAM_INT);
            $sql->bindParam(':iframe_proyecto', $data['iframe_proyecto'], PDO::PARAM_STR);
            $sql->bindParam(':visible', $data['visible'], PDO::PARAM_INT);
            $sql->bindParam(':habilitado', $data['habilitado'], PDO::PARAM_INT);
            $sql->execute();

            $this->status = true;
            $this->message = 'Proyecto creado correctamente';
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }
        $this->closeConnection();
    }

    public function update($data) {
        try {
            $authorization = $GLOBALS['authorization'];
            $permises = $authorization->permises;
            $id_rol = $permises['id_rol'] ?? null;
            $id_empresa_user = $permises['id_empresa'] ?? null;

            // Si no es superadmin ni admin, comprobar que el proyecto es suyo
            if (!in_array($id_rol, [1, 2])) {
                $sqlCheck = $this->conexion->prepare("SELECT id_empresa FROM proyectos WHERE id_proyecto = :id_proyecto");
                $sqlCheck->bindParam(':id_proyecto', $data['id_proyecto'], PDO::PARAM_INT);
                $sqlCheck->execute();
                $proyecto = $sqlCheck->fetch(PDO::FETCH_ASSOC);

                if (!$proyecto || $proyecto['id_empresa'] != $id_empresa_user) {
                    $this->status = false;
                    $this->message = 'No puedes editar proyectos que no son de tu empresa.';
                    return;
                }

                // Además, si es admin_empresa, no puede cambiar el campo `habilitado`
                $data['habilitado'] = 1; // Fuerzas a que no lo pueda cambiar
            }

            $sql = $this->conexion->prepare("UPDATE proyectos SET 
                nombre_proyecto = :nombre_proyecto,
                id_empresa = :id_empresa,
                iframe_proyecto = :iframe_proyecto,
                visible = :visible,
                habilitado = :habilitado
                WHERE id_proyecto = :id_proyecto");

            $sql->execute([
                ':id_proyecto' => $data['id_proyecto'],
                ':nombre_proyecto' => $data['nombre_proyecto'],
                ':id_empresa' => $data['id_empresa'],
                ':iframe_proyecto' => $data['iframe_proyecto'],
                ':visible' => $data['visible'],
                ':habilitado' => $data['habilitado'],
            ]);

            $this->status = true;
            $this->message = 'Proyecto actualizado correctamente';

        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }


    public function delete($id) {
        try {
            $authorization = $GLOBALS['authorization'];
            $permises = $authorization->permises;
            $id_rol = $permises['id_rol'] ?? null;
            $id_empresa_user = $permises['id_empresa'] ?? null;

            if (!in_array($id_rol, [1, 2])) {
                $sqlCheck = $this->conexion->prepare("SELECT id_empresa FROM proyectos WHERE id_proyecto = :id_proyecto");
                $sqlCheck->bindParam(':id_proyecto', $id, PDO::PARAM_INT);
                $sqlCheck->execute();
                $proyecto = $sqlCheck->fetch(PDO::FETCH_ASSOC);

                if (!$proyecto || $proyecto['id_empresa'] != $id_empresa_user) {
                    $this->status = false;
                    $this->message = 'No puedes eliminar proyectos que no son de tu empresa.';
                    return;
                }
            }

            $sql = $this->conexion->prepare("DELETE FROM proyectos WHERE id_proyecto = :id_proyecto");
            $sql->bindParam(':id_proyecto', $id, PDO::PARAM_INT);
            $sql->execute();

            $this->status = true;
            $this->message = 'Proyecto eliminado correctamente';

        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

}

?>
