<?php
/**
 * Clase Empresa: Gestión de entidades empresariales
 *
 * Esta clase implementa el conjunto de operaciones CRUD sobre el modelo de empresa,
 * aplicando restricciones según el rol del usuario autenticado. Utiliza inyección
 * de dependencias para verificar los permisos mediante una instancia de autorización.
 * 
 * Está pensada para entornos multiempresa, donde se permite a usuarios globales
 * gestionar todas las empresas, y a usuarios limitados (por empresa) acceder solo
 * a su propia entidad.
 * 
 * @author Francisco
 * @version 1.5
 */

require_once __DIR__ . '/interfaces/crud.php';
require_once __DIR__ . '/../../conn.php';

class Empresa extends Conexion implements crud {

    public $status = false;
    public $message = null;
    public $data = null;

    private $auth; // Instancia de Authorization

    // Ruta identificadora de esta entidad
    const ROUTE = 'empresas';

    public function __construct($auth) {
        parent::__construct();
        $this->auth = $auth;
    }

    /**
     * Devuelve todas las empresas o solo la propia si es rol limitado
     */
    public function get() {
        try {
            $id_rol = $this->auth->permises['id_rol'] ?? null;
            $id_empresa = $this->auth->permises['id_empresa'] ?? null;

            $query = "SELECT id_empresa, nombre_empresa, empleados_totales, proyectos_totales, logo_url FROM empresas";

            // Aplicar filtro para roles limitados
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
            $sql = $this->conexion->prepare("
                SELECT id_empresa, nombre_empresa, empleados_totales, proyectos_totales, logo_url 
                FROM empresas 
                WHERE id_empresa = :id_empresa
            ");
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
     * Crea una nueva empresa (solo admins globales deberían poder)
     */
    public function create($data) {
        try {
            $id_rol = $this->auth->permises['id_rol'] ?? null;
            if (!in_array($id_rol, [1, 2])) {
                $this->status = false;
                $this->message = 'No tienes permisos para crear empresas.';
                return;
            }

            $sql = $this->conexion->prepare("
                INSERT INTO empresas (nombre_empresa, logo_url) 
                VALUES (:nombre_empresa, :logo_url)
            ");
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
     * Actualiza una empresa existente (solo puede modificar su empresa si es rol limitado)
     */
    public function update($data) {
        try {
            $id_rol = $this->auth->permises['id_rol'] ?? null;
            $id_empresa_user = $this->auth->permises['id_empresa'] ?? null;

            if (in_array($id_rol, [3, 4]) && $data['id_empresa'] != $id_empresa_user) {
                $this->status = false;
                $this->message = 'No puedes modificar otra empresa que no sea la tuya.';
                return;
            }

            $sql = $this->conexion->prepare("
                UPDATE empresas 
                SET nombre_empresa = :nombre_empresa, logo_url = :logo_url 
                WHERE id_empresa = :id_empresa
            ");
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
     * Elimina una empresa por ID (solo admins globales deberían poder hacerlo)
     */
    public function delete($id) {
        try {
            $id_rol = $this->auth->permises['id_rol'] ?? null;

            if (in_array($id_rol, [3, 4])) {
                $this->status = false;
                $this->message = 'No puedes eliminar empresas con tu rol.';
                return;
            }

            $sql = $this->conexion->prepare("
                DELETE FROM empresas WHERE id_empresa = :id_empresa
            ");
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
