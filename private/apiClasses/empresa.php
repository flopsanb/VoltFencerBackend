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

    public function __construct() {
        parent::__construct();
    }

    public function get() {
        try {
            $authorization = $GLOBALS['authorization'] ?? null;
            $id_rol = $authorization->permises['id_rol'] ?? null;
            $id_empresa = $authorization->permises['id_empresa'] ?? null;

            $query = "SELECT id_empresa, nombre_empresa, empleados_totales, proyectos_totales, logo_url FROM empresas";
            if (in_array($id_rol, [3, 4])) {
                $query .= " WHERE id_empresa = :id_empresa";
            }
            $query .= " ORDER BY id_empresa ASC";

            $stmt = $this->conexion->prepare($query);

            if (in_array($id_rol, [3, 4])) {
                $stmt->bindParam(':id_empresa', $id_empresa, PDO::PARAM_INT);
            }

            $stmt->execute();
            $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->status = true;

        } catch (PDOException $e) {
            $this->message = 'Error al obtener empresas: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    public function create($data) {
        try {
            if (empty($data['nombre_empresa']) || empty($data['logo_url'])) {
                $this->message = 'Datos incompletos para crear empresa.';
                return;
            }

            $stmt = $this->conexion->prepare(
                "INSERT INTO empresas (nombre_empresa, logo_url) 
                 VALUES (:nombre_empresa, :logo_url)"
            );
            $stmt->bindParam(':nombre_empresa', $data['nombre_empresa'], PDO::PARAM_STR);
            $stmt->bindParam(':logo_url', $data['logo_url'], PDO::PARAM_STR);
            $stmt->execute();

            $this->status = true;
            $this->message = 'Empresa creada correctamente';

        } catch (PDOException $e) {
            $this->message = 'Error al crear empresa: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    public function update($data) {
        try {
            if (empty($data['id_empresa']) || empty($data['nombre_empresa']) || empty($data['logo_url'])) {
                $this->message = 'Datos incompletos para actualizar empresa.';
                return;
            }

            $stmt = $this->conexion->prepare(
                "UPDATE empresas 
                 SET nombre_empresa = :nombre_empresa, logo_url = :logo_url 
                 WHERE id_empresa = :id_empresa"
            );
            $stmt->bindParam(':id_empresa', $data['id_empresa'], PDO::PARAM_INT);
            $stmt->bindParam(':nombre_empresa', $data['nombre_empresa'], PDO::PARAM_STR);
            $stmt->bindParam(':logo_url', $data['logo_url'], PDO::PARAM_STR);
            $stmt->execute();

            $this->status = true;
            $this->message = 'Empresa actualizada correctamente';

        } catch (PDOException $e) {
            $this->message = 'Error al actualizar empresa: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    public function delete($id) {
        try {
            if (!is_numeric($id)) {
                $this->message = 'ID inválido';
                return;
            }

            $stmt = $this->conexion->prepare(
                "DELETE FROM empresas 
                 WHERE id_empresa = :id_empresa"
            );
            $stmt->bindParam(':id_empresa', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->status = true;
            $this->message = 'Empresa eliminada correctamente';

        } catch (PDOException $e) {
            $this->message = 'Error al eliminar empresa: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    public function getById($id_empresa) {
        try {
            if (!is_numeric($id_empresa)) {
                $this->message = 'ID inválido';
                return;
            }

            $stmt = $this->conexion->prepare(
                "SELECT id_empresa, nombre_empresa, empleados_totales, proyectos_totales, logo_url 
                 FROM empresas 
                 WHERE id_empresa = :id_empresa"
            );
            $stmt->bindParam(':id_empresa', $id_empresa, PDO::PARAM_INT);
            $stmt->execute();

            $this->data = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->status = (bool) $this->data;

            if (!$this->data) {
                $this->message = 'Empresa no encontrada';
            }

        } catch (PDOException $e) {
            $this->message = 'Error al obtener empresa: ' . $e->getMessage();
        }

        $this->closeConnection();
    }
}
