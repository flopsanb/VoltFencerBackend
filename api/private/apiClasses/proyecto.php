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

require_once(__DIR__ . '/interfaces/crud.php');
require_once(__DIR__ . '/../conn.php');

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
            $auth = $GLOBALS['authorization']->permises ?? [];
            $id_rol = $auth['id_rol'] ?? null;
            $id_empresa = $auth['id_empresa'] ?? null;

            $sql = "SELECT p.*, e.nombre_empresa FROM proyectos p 
                    JOIN empresas e ON p.id_empresa = e.id_empresa";

            if (in_array($id_rol, [3, 4])) {
                $sql .= " WHERE p.id_empresa = :id_empresa AND p.habilitado = 1";
                if ($id_rol == 4) $sql .= " AND p.visible = 1";
            }

            $sql .= " ORDER BY p.id_proyecto ASC";
            $stmt = $this->conexion->prepare($sql);

            if (in_array($id_rol, [3, 4])) {
                $stmt->bindParam(':id_empresa', $id_empresa, PDO::PARAM_INT);
            }

            $stmt->execute();
            $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->status = true;

        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

    public function create($data) {
        try {
            $sql = $this->conexion->prepare("
                INSERT INTO proyectos 
                (nombre_proyecto, id_empresa, iframe_proyecto, visible, habilitado) 
                VALUES 
                (:nombre_proyecto, :id_empresa, :iframe_proyecto, :visible, :habilitado)");

            $sql->execute([
                ':nombre_proyecto' => $data['nombre_proyecto'],
                ':id_empresa' => $data['id_empresa'],
                ':iframe_proyecto' => $data['iframe_proyecto'],
                ':visible' => $data['visible'],
                ':habilitado' => $data['habilitado'],
            ]);

            $this->status = true;
            $this->message = 'Proyecto creado correctamente';

        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

    public function update($data) {
        try {
            $auth = $GLOBALS['authorization']->permises ?? [];
            $id_rol = $auth['id_rol'] ?? null;
            $id_empresa_user = $auth['id_empresa'] ?? null;

            if (!in_array($id_rol, [1, 2])) {
                // Validación de empresa propietaria
                $check = $this->conexion->prepare("SELECT id_empresa FROM proyectos WHERE id_proyecto = :id");
                $check->execute([':id' => $data['id_proyecto']]);
                $proyecto = $check->fetch(PDO::FETCH_ASSOC);

                if (!$proyecto || $proyecto['id_empresa'] != $id_empresa_user) {
                    $this->message = 'No puedes editar proyectos que no son de tu empresa.';
                    return;
                }

                // No puede tocar habilitado
                $data['habilitado'] = 1;
            }

            $sql = $this->conexion->prepare("
                UPDATE proyectos SET 
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
            $auth = $GLOBALS['authorization']->permises ?? [];
            $id_rol = $auth['id_rol'] ?? null;
            $id_empresa_user = $auth['id_empresa'] ?? null;

            if (!in_array($id_rol, [1, 2])) {
                $check = $this->conexion->prepare("SELECT id_empresa FROM proyectos WHERE id_proyecto = :id");
                $check->execute([':id' => $id]);
                $proyecto = $check->fetch(PDO::FETCH_ASSOC);

                if (!$proyecto || $proyecto['id_empresa'] != $id_empresa_user) {
                    $this->message = 'No puedes eliminar proyectos que no son de tu empresa.';
                    return;
                }
            }

            $sql = $this->conexion->prepare("DELETE FROM proyectos WHERE id_proyecto = :id");
            $sql->execute([':id' => $id]);

            $this->status = true;
            $this->message = 'Proyecto eliminado correctamente';

        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }
}
?>
