<?php
/**
 * Clase para gestión de empresas
 * 
 * Implementa operaciones CRUD para entidades de tipo Empresa.
 * Aplica restricciones basadas en los permisos del usuario autenticado.
 * 
 * @author Francisco
 * @version 1.3
 */

require_once __DIR__ . '/interfaces/crud.php';
require_once __DIR__ . '/../../conn.php';

class Empresa extends Conexion implements crud {

    public $status = false;
    public $message = null;
    public $data = null;
    const ROUTE = 'empresas';

    private $permisos;

    public function __construct(array $permisos = []) {
        parent::__construct();
        $this->permisos = $permisos;
    }

    /**
     * Devuelve todas las empresas (o solo la suya si es admin_empresa o empleado)
     */
    public function get() {
        try {
            $id_rol = $this->permisos['id_rol'] ?? null;
            $id_empresa = $this->permisos['id_empresa'] ?? null;

            $query = "SELECT id_empresa, nombre_empresa, empleados_totales, proyectos_totales, logo_url FROM empresas";
            
            if (in_array($id_rol, [3, 4])) {
                $query .= " WHERE id_empresa = :id_empresa";
            }

            $query .= " ORDER BY id_empresa ASC";

            $sql = $this->conexion->prepare($query);

            if (in_array($id_rol, [3, 4])) {
                $sql->bindParam(':id_empresa', $id_empresa, PDO::PARAM_INT);
            }

            $sql->execute();
            $this->data = $sql->fetchAll(PDO::FETCH_ASSOC);
            $this->status = true;

        } catch (PDOException $e) {
            $this->status = false;
            $this->message = 'Error al obtener empresas: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    /**
     * Devuelve una empresa por ID
     */
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
            $this->status = false;
            $this->message = 'Error al buscar empresa: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    /**
     * Crea una nueva empresa
     */
    public function create($data) {
        try {
            $sql = $this->conexion->prepare("INSERT INTO empresas (nombre_empresa, logo_url) VALUES (:nombre_empresa, :logo_url)");
            $sql->bindParam(':nombre_empresa', $data['nombre_empresa'], PDO::PARAM_STR);
            $sql->bindParam(':logo_url', $data['logo_url'], PDO::PARAM_STR);
            $sql->execute();

            $this->status = true;
            $this->message = 'Empresa creada correctamente';

        } catch (PDOException $e) {
            $this->status = false;
            $this->message = 'Error al crear empresa: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    /**
     * Actualiza una empresa existente
     */
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
            $this->status = false;
            $this->message = 'Error al actualizar empresa: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    /**
     * Elimina una empresa por ID
     */
    public function delete($id) {
        try {
            $sql = $this->conexion->prepare("DELETE FROM empresas WHERE id_empresa = :id_empresa");
            $sql->bindParam(':id_empresa', $id, PDO::PARAM_INT);
            $sql->execute();

            $this->status = true;
            $this->message = 'Empresa eliminada correctamente';

        } catch (PDOException $e) {
            $this->status = false;
            $this->message = 'Error al eliminar empresa: ' . $e->getMessage();
        }

        $this->closeConnection();
    }
}
