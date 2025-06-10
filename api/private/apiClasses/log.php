<?php
/**
 * Clase para gestión de registros de actividad (logs)
 * 
 * Esta clase implementa funcionalidades para registrar y consultar
 * eventos importantes del sistema. Permite mantener un historial
 * de acciones realizadas por los usuarios para fines de auditoría,
 * seguridad y seguimiento de actividades.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.1
 */

require_once __DIR__ . '/../conn.php';

class Log extends Conexion {

    public $status = false;
    public $message = null;
    public $data = null;

    function __construct() {
        parent::__construct();
    }

    /**
     * Recupera todos los registros de log
     * 
     * @return void
     */
    public function get() {
        try {
            $stmt = $this->conexion->prepare("SELECT * FROM sgi_vista_logs ORDER BY id_log DESC");
            $stmt->execute();
            $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = 'Error al recuperar los logs: ' . $e->getMessage();
        }
        $this->closeConnection();
    }

    /**
     * Genera un nuevo registro de log
     * 
     * @param int $id_tipo_log
     * @param string $contenido
     * @param string|null $usuario
     * @return bool
     */
    public function generateLog($id_tipo_log, $contenido, $usuario = null) {
        try {
            if (!$id_tipo_log || !$contenido) return false;

            if ($usuario === null && isset($_SESSION['id_usuario'])) {
                $stmt = $this->conexion->prepare("SELECT usuario FROM sgi_usuarios WHERE id_usuario = :id_usuario");
                $stmt->bindParam(":id_usuario", $_SESSION['id_usuario'], PDO::PARAM_INT);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $usuario = $result['usuario'] ?? 'desconocido';
            }

            // Obtener IP real sin clase externa
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
?>
