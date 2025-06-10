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

require_once __DIR__ . '/../conn.php';

class Log extends Conexion {

    public $status = false;
    public $message = null;
    public $data = null;

    public function __construct() {
        parent::__construct();
    }

    /**
     * Recupera todos los registros de log (solo lectura, sin filtros)
     * 
     * @return void
     */
    public function get() {
        try {
            $stmt = $this->conexion->prepare("SELECT * FROM sgi_vista_logs ORDER BY id_log DESC");
            $stmt->execute();
            $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->status = true;
            $this->message = 'Logs recuperados correctamente';
        } catch (PDOException $e) {
            $this->message = 'Error al recuperar los logs: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    /**
     * Genera un nuevo log en la tabla `sgi_logs`
     * 
     * @param int         $id_tipo_log  Tipo del evento (FK)
     * @param string      $contenido    Descripción del evento
     * @param string|null $usuario      Usuario (se puede obtener desde sesión)
     * 
     * @return bool  True si se guarda correctamente, false en caso contrario
     */
    public function generateLog($id_tipo_log, $contenido, $usuario = null) {
        try {
            if (empty($id_tipo_log) || empty($contenido)) {
                error_log("[❌] generateLog: Falta tipo o contenido del log");
                return false;
            }

            // Si no se proporciona usuario, intenta sacarlo desde sesión
            if (!$usuario && isset($_SESSION['id_usuario'])) {
                $stmt = $this->conexion->prepare("SELECT usuario FROM sgi_usuarios WHERE id_usuario = :id_usuario");
                $stmt->bindParam(":id_usuario", $_SESSION['id_usuario'], PDO::PARAM_INT);
                $stmt->execute();
                $res = $stmt->fetch(PDO::FETCH_ASSOC);
                $usuario = $res['usuario'] ?? 'desconocido';
            }

            // IP simplificada, sin dependencias externas
            $ip = $_SERVER['HTTP_CLIENT_IP'] 
                ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
                ?? $_SERVER['REMOTE_ADDR'] 
                ?? 'IP desconocida';

            $contenidoConIP = $contenido . ' - ' . $ip;

            $stmt = $this->conexion->prepare("
                INSERT INTO sgi_logs (usuario, id_tipo_log, contenido)
                VALUES (:usuario, :id_tipo_log, :contenido)
            ");
            $stmt->bindParam(":usuario", $usuario, PDO::PARAM_STR);
            $stmt->bindParam(":id_tipo_log", $id_tipo_log, PDO::PARAM_INT);
            $stmt->bindParam(":contenido", $contenidoConIP, PDO::PARAM_STR);

            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("[❌] Error al generar log: " . $e->getMessage());
            return false;
        }
    }
}
