<?php
/**
 * Clase para gestión de estados de conexión de usuarios
 * 
 * Permite registrar actividad en tiempo real y consultar
 * qué usuarios están actualmente activos.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.2
 */

require_once __DIR__ . '/../../conn.php';
require_once __DIR__ . '/interfaces/crud.php';

class EstadoConexion extends Conexion {

    public $status = false;
    public $message = null;
    public $data = null;

    public function __construct() {
        parent::__construct();
    }

    /**
     * Registra la última actividad del usuario (ping keep-alive)
     * 
     * @param int $id_usuario
     * @return void
     */
    public function registrarActividad($id_usuario) {
        try {
            if (!is_numeric($id_usuario) || $id_usuario <= 0) {
                $this->message = 'ID de usuario inválido';
                return;
            }

            $stmt = $this->conexion->prepare("
                REPLACE INTO estado_conexion (id_usuario, last_seen)
                VALUES (:id_usuario, NOW())
            ");
            $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $stmt->execute();

            $this->status = true;
            $this->message = 'Actividad registrada correctamente';

        } catch (PDOException $e) {
            $this->message = 'Error al registrar la actividad: ' . $e->getMessage();
        }

        $this->closeConnection();
    }

    /**
     * Obtiene IDs de usuarios conectados en los últimos 3 minutos
     * 
     * @return void
     */
    public function getConectados() {
        try {
            $stmt = $this->conexion->prepare("
                SELECT id_usuario 
                FROM estado_conexion 
                WHERE last_seen >= (NOW() - INTERVAL 3 MINUTE)
            ");
            $stmt->execute();

            $this->data = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $this->status = true;
            $this->message = 'Usuarios conectados obtenidos correctamente';

        } catch (PDOException $e) {
            $this->message = 'Error al obtener usuarios conectados: ' . $e->getMessage();
        }

        $this->closeConnection();
    }
}
