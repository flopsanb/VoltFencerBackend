<?php
/**
 * Clase para gestión de empresas
 * 
 * Implementa operaciones CRUD para entidades de tipo Empresa.
 * Incluye validaciones según el rol del usuario.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.2
 */

require_once __DIR__ . '/interfaces/crud.php';
require_once __DIR__ . '/../../conn.php';

class Empresa extends Conexion implements crud {

    public $status = false;
    public $message = null;
    public $data = null;
    const ROUTE = 'empresas';

    function __construct () {
        parent::__construct();
    }

    public function get() {
        try {
            $authorization = $GLOBALS['authorization'];
            $datos = $authorization->permises;
            $id_rol = $datos['id_rol'] ?? null;
            $id_empresa = $datos['id_empresa'] ?? null;

            $sqlBase = "SELECT id_empresa, nombre_empresa, empleados_totales, proyectos_totales, logo_url FROM empresas";

            if ($id_rol == 3 || $id_rol == 4) {
                $sqlBase .= " WHERE id_empresa = :id_empresa";
            }

            $sqlBase .= " ORDER BY id_empresa ASC";
            $sql = $this->conexion->prepare($sqlBase);

            if ($id_rol == 3 || $id_rol == 4) {
                $sql->bindParam(":id_empresa", $id_empresa);
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
            $sql = $this->conexion->prepare("INSERT INTO empresas (nombre_empresa, logo_url) VALUES (:nombre_empresa, :logo_url)");
            $sql->bindParam(':nombre_empresa', $data['nombre_empresa'], PDO::PARAM_STR);
            $sql->bindParam(':logo_url', $data['logo_url'], PDO::PARAM_STR);
            $sql->execute();

            $this->status = true;
            $this->message = 'Empresa creada correctamente';
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }
        $this->closeConnection();
    }

    public function update($data) {
        try {
            $sql = $this->conexion->prepare("UPDATE empresas SET nombre_empresa = :nombre_empresa, logo_url = :logo_url WHERE id_empresa = :id_empresa");
            $sql->bindParam(':id_empresa', $data['id_empresa'], PDO::PARAM_INT);
            $sql->bindParam(':nombre_empresa', $data['nombre_empresa'], PDO::PARAM_STR);
            $sql->bindParam(':logo_url', $data['logo_url'], PDO::PARAM_STR);
            $sql->execute();

            $this->status = true;
            $this->message = 'Empresa actualizada correctamente';
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }
        $this->closeConnection();
    }

    public function delete($id) {
        try {
            $sql = $this->conexion->prepare("DELETE FROM empresas WHERE id_empresa = :id_empresa");
            $sql->bindParam(':id_empresa', $id, PDO::PARAM_INT);
            $sql->execute();
            $this->status = true;
            $this->message = 'Empresa eliminada correctamente';
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }
        $this->closeConnection();
    }

    public function getById($id_empresa) {
        try {
            $sql = $this->conexion->prepare("SELECT id_empresa, nombre_empresa, empleados_totales, proyectos_totales, logo_url FROM empresas WHERE id_empresa = :id_empresa");
            $sql->bindParam(':id_empresa', $id_empresa, PDO::PARAM_INT);
            $sql->execute();

            $this->data = $sql->fetch(PDO::FETCH_ASSOC);
            $this->status = true;

            if (!$this->data) {
                $this->status = false;
                $this->message = 'Empresa no encontrada';
            }

        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }
        $this->closeConnection();
    }
}
?>
