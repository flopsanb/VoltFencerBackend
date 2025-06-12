<?php
/**
 * Clase para gestión de registros de actividad (logs)
 * 
 * Permite registrar eventos importantes del sistema, mantener trazabilidad
 * de acciones realizadas por los usuarios y auditar comportamientos clave.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.2
 */

require_once __DIR__ . '/../../conn.php';

class Log extends Conexion {

    public $status = false;
    public $message = NULL;
    public $data = NULL;
    private $auth;

    function __construct ($auth){
        parent::__construct();
        $this->auth = $auth;
    }

    public function get() {
        $sql = $this->conexion->prepare("SELECT * FROM logs ORDER BY id_log DESC");
        $exito = $sql->execute();
        if ($exito){
            $this->status = true;
            $this->data = $sql->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->closeConnection();
    }

    public function generateLog($id_tipo_log, $contenido, $usuario = null) {
        
        if($usuario == null) {
            $sql = $this->conexion->prepare("SELECT * FROM usuarios WHERE id_usuario = :id_usuario");
            $sql->bindParam(":id_usuario", $_SESSION['id_usuario']);
            $sql->execute();
            $usuario = $sql->fetch(PDO::FETCH_ASSOC)['usuario'];
        }
        
        $utils = new Utils();

        $ip = $utils->getRealIP();
        $contenido .= " - $ip";

        $stmt = $this->conexion->prepare("INSERT INTO logs (usuario, id_tipo_log, contenido) VALUES (:usuario, :id_tipo_log, :contenido)");
        $stmt->bindParam(":usuario", $usuario);
        $stmt->bindParam(":id_tipo_log", $id_tipo_log);
        $stmt->bindParam(":contenido", $contenido);
        $exito = $stmt->execute();
        return $exito;
        $stmt->close();
    }
}