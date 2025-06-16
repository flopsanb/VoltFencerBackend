<?php
/**
 * Clase PermisosRol
 * 
 * Esta clase representa el controlador lógico para gestionar los permisos que tiene cada rol dentro de la aplicación.
 * Forma parte del sistema de control de acceso y permite definir, consultar, modificar y eliminar las capacidades
 * funcionales asociadas a cada rol (como crear empresas, gestionar usuarios o modificar proyectos).
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.1
 */

require_once __DIR__ . '/interfaces/crud.php';
require_once __DIR__ . '/../../conn.php';

class PermisosRol extends Conexion implements crud {

    public $status = false;
    public $message = NULL;
    public $data = NULL;
    private $auth;

    // Ruta identificadora de este controlador
    const ROUTE = 'permisos-rol';

    /**
     * Constructor de la clase
     * Inicializa la conexión a la base de datos y guarda la instancia de autenticación
     */
    function __construct ($auth){
        parent::__construct();
        $this->auth = $auth;
    }


    /**
     * Método para obtener todos los permisos de rol
     * 
     * Realiza una consulta a la tabla `permisos_rol` y devuelve todos los registros ordenados por `id_rol`.
     * 
     * @return void
     */
    public function get() {
        try {
            $sql = $this->conexion->prepare("SELECT * FROM permisos_rol ORDER BY id_rol ASC");
            $sql->execute();
            $this->data = $sql->fetchAll(PDO::FETCH_ASSOC);
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }
        $this->closeConnection();
    }

    /**
     * Método para crear un nuevo conjunto de permisos para un rol
     * 
     * Inserta un nuevo registro en la tabla `permisos_rol` con los datos proporcionados.
     * 
     * @param array $data Datos del permiso a crear, debe incluir 'id_rol' y los permisos específicos.
     * @return void
     */
    public function create($data) {
        try {
            $sql = $this->conexion->prepare("INSERT INTO permisos_rol (
                id_rol, crear_empresas, gestionar_usuarios_globales, gestionar_permisos_globales,
                crear_proyectos, borrar_proyectos, deshabilitar_proyectos,
                gestionar_usuarios_empresa, gestionar_permisos_empresa
            ) VALUES (
                :id_rol, :crear_empresas, :gestionar_usuarios_globales, :gestionar_permisos_globales,
                :crear_proyectos, :borrar_proyectos, :deshabilitar_proyectos,
                :gestionar_usuarios_empresa, :gestionar_permisos_empresa
            )");

            $sql->execute([
                ':id_rol' => $data['id_rol'],
                ':crear_empresas' => $data['crear_empresas'],
                ':gestionar_usuarios_globales' => $data['gestionar_usuarios_globales'],
                ':gestionar_permisos_globales' => $data['gestionar_permisos_globales'],
                ':crear_proyectos' => $data['crear_proyectos'],
                ':borrar_proyectos' => $data['borrar_proyectos'],
                ':deshabilitar_proyectos' => $data['deshabilitar_proyectos'],
                ':gestionar_usuarios_empresa' => $data['gestionar_usuarios_empresa'],
                ':gestionar_permisos_empresa' => $data['gestionar_permisos_empresa']
            ]);

            $this->status = true;
            $this->message = 'Permisos creados correctamente';
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }
        $this->closeConnection();
    }

    /**
     * Método para actualizar los permisos de un rol existente
     * 
     * Actualiza los permisos asociados a un rol específico en la tabla `permisos_rol`.
     * 
     * @param array $data Datos del permiso a actualizar, debe incluir 'id_rol' y los permisos específicos.
     * @return void
     */
    public function update($data) {
        try {
            $sql = $this->conexion->prepare("UPDATE permisos_rol SET 
                crear_empresas = :crear_empresas,
                gestionar_usuarios_globales = :gestionar_usuarios_globales,
                gestionar_permisos_globales = :gestionar_permisos_globales,
                crear_proyectos = :crear_proyectos,
                borrar_proyectos = :borrar_proyectos,
                deshabilitar_proyectos = :deshabilitar_proyectos,
                gestionar_usuarios_empresa = :gestionar_usuarios_empresa,
                gestionar_permisos_empresa = :gestionar_permisos_empresa
                WHERE id_rol = :id_rol");

            $sql->execute([
                ':id_rol' => $data['id_rol'],
                ':crear_empresas' => $data['crear_empresas'],
                ':gestionar_usuarios_globales' => $data['gestionar_usuarios_globales'],
                ':gestionar_permisos_globales' => $data['gestionar_permisos_globales'],
                ':crear_proyectos' => $data['crear_proyectos'],
                ':borrar_proyectos' => $data['borrar_proyectos'],
                ':deshabilitar_proyectos' => $data['deshabilitar_proyectos'],
                ':gestionar_usuarios_empresa' => $data['gestionar_usuarios_empresa'],
                ':gestionar_permisos_empresa' => $data['gestionar_permisos_empresa']
            ]);

            $this->status = true;
            $this->message = 'Permisos actualizados correctamente';
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }
        $this->closeConnection();
    }

    /**
     * Método para eliminar los permisos de un rol
     * 
     * Elimina el registro de permisos asociado a un rol específico en la tabla `permisos_rol`.
     * 
     * @param int $id ID del rol cuyos permisos se desean eliminar.
     * @return void
     */
    public function delete($id) {
        try {
            $sql = $this->conexion->prepare("DELETE FROM permisos_rol WHERE id_rol = :id_rol");
            $sql->bindParam(':id_rol', $id, PDO::PARAM_INT);
            $sql->execute();
            $this->status = true;
            $this->message = 'Permisos eliminados correctamente';
        } catch (PDOException $e) {
            $this->message = $e->getMessage();
        }
        $this->closeConnection();
    }

    /**
     * Método para obtener los permisos de un rol específico por su ID
     * 
     * Realiza una consulta a la tabla `permisos_rol` para obtener los permisos asociados a un rol dado.
     * 
     * @param int $id ID del rol cuyos permisos se desean consultar.
     * @return void
     */
    public function getById(int $id): void {
        try {
            $stmt = $this->conexion->prepare("SELECT * FROM permisos_rol WHERE id_rol = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->status = true;
            $this->data = $result;
            $this->message = count($result) > 0 ? 'Permisos cargados correctamente.' : 'El rol no tiene permisos asignados.';
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = 'Error al obtener permisos';
            $this->data = $e->getMessage();
        }

        $this->closeConnection();
    }
}
?>
