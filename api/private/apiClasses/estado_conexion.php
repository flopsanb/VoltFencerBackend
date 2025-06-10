<?php
/**
 * Clase para gestión de estados de conexión de usuarios
 * 
 * Esta clase implementa funcionalidades para registrar y consultar
 * la actividad en tiempo real de los usuarios en el sistema.
 * Permite conocer qué usuarios están actualmente conectados
 * basándose en su última actividad registrada.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.1
 */

require_once __DIR__ . '/../conn.php';
require_once __DIR__ . '/interfaces/crud.php';

class EstadoConexion extends Conexion
{
    public $status = false;
    public $message = null;
    public $data = null;

    public function __construct() {
        parent::__construct();
    }

    /**
     * Registra la actividad de un usuario
     * 
     * @param int $id_usuario
     * @return void
     */
    public function registrarActividad($id_usuario) {
        try {
            if (!$id_usuario || !is_numeric($id_usuario)) {
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
     * Obtiene los usuarios actualmente conectados (últimos 3 minutos)
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
            $this->message = 'Error al obtener los usuarios conectados: ' . $e->getMessage();
        }

        $this->closeConnection();
    }
}
