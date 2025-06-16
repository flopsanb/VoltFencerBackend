<?php
/**
 * Esta clase gestiona el estado de conexión de los usuarios en la aplicación.
 * Su función principal es registrar en tiempo real cuándo un usuario ha tenido actividad
 * y permitir consultar qué usuarios se encuentran conectados recientemente.
 * 
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

    /**
     * registrarActividad(): Actualiza o inserta el último acceso del usuario
     * 
     * Este método utiliza el comando SQL `REPLACE INTO` para registrar el instante
     * exacto en el que un usuario ha interactuado con la aplicación.
     * 
     * - Si el usuario ya existe en la tabla `estado_conexion`, se actualiza su campo `last_seen`.
     * - Si no existe, se crea un nuevo registro.
     * 
     * @param int $id_usuario  Identificador único del usuario activo
     */
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

    /**
     * getConectados(): Devuelve los usuarios activos en los últimos minutos
     * 
     * Consulta todos los usuarios cuya última actividad (`last_seen`) haya sido dentro
     * de los últimos 3 minutos. Esta ventana temporal se puede ajustar según necesidad.
     * 
     * El método devuelve únicamente los `id_usuario`, como un array de enteros.
     */
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
