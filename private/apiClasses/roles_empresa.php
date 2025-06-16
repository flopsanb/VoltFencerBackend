<?php
declare(strict_types=1);

/**
 * Clase para gestión de roles asignables por administradores de empresa.
 * Devuelve únicamente los roles 3 (admin_empresa) y 4 (empleado_empresa).
 * 
 * @author Francisco Lopez
 * @version 1.0
 */

require_once __DIR__ . '/../../conn.php';

class RolesEmpresa extends Conexion {

    public $status = false;
    public $message = null;
    public $data = null;

    /**
     * Instancia de autorización para controlar permisos de acceso
     * 
     * @var Authorization
     */
    private $auth;

    const ROUTE = 'roles_empresa';

    /**
     * Constructor de la clase
     * 
     * @param Authorization $auth Instancia de autorización para controlar permisos
     */
    public function __construct($auth) {
        parent::__construct();
        $this->auth = $auth;
    }

    /**
     * Obtiene los roles asignables a empleados de empresa.
     * 
     * Solo devuelve los roles 3 (admin_empresa) y 4 (empleado_empresa).
     * 
     * @return void
     */
    public function get() {
        try {
            $sql = $this->conexion->prepare("SELECT id_rol, nombre_rol FROM roles WHERE id_rol IN (3, 4)");
            $sql->execute();

            $this->data = $sql->fetchAll(PDO::FETCH_ASSOC);
            $this->status = true;
            $this->message = 'Roles cargados correctamente.';
        } catch (PDOException $e) {
            $this->message = 'Error al obtener roles: ' . $e->getMessage();
        }

        $this->closeConnection();
    }
}