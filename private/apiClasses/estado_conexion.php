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

class EstadoConexion extends Conexion
{
    public $status = false;
    public $message = null;
    public $data = null;
    
    public function __construct()
    {
        parent::__construct();
    }

    public function registrarActividad($id_usuario)
    {
        try {
            $sql = $this->conexion->prepare("REPLACE INTO estado_conexion (id_usuario, last_seen) VALUES (:id_usuario, NOW())");
            $sql->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
            $sql->execute();

            $this->status = true;
            $this->message = 'Actividad registrada correctamente';
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

    public function getConectados()
    {
        try {
            $sql = $this->conexion->prepare("SELECT id_usuario FROM estado_conexion WHERE last_seen >= (NOW() - INTERVAL 3 MINUTE)");
            $sql->execute();
            $this->data = $sql->fetchAll(PDO::FETCH_COLUMN);

            $this->status = true;
            $this->message = 'Usuarios conectados obtenidos correctamente';
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }
}
