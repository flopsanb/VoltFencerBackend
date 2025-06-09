<?php
/**
 * Clase para gestión de permisos por rol
 * 
 * CRUD para configuración de permisos asociados a roles en el sistema.
 * Permite definir qué acciones puede realizar cada rol, tanto globales
 * como por empresa.
 * 
 * @author  Francisco Lopez Sanchez
 * @version 1.1
 */

require_once(__DIR__ . '/interfaces/crud.php');
require_once(__DIR__ . '/../conn.php');

class PermisosRol extends Conexion implements crud {

    public $status = false;
    public $message = null;
    public $data = null;

    const ROUTE = 'permisos-rol';

    function __construct() {
        parent::__construct();
    }

    public function get() {
        try {
            $stmt = $this->conexion->prepare("SELECT * FROM permisos_rol ORDER BY id_rol ASC");
            $stmt->execute();
            $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->status = true;
        } catch (PDOException $e) {
            $this->message = "Error al obtener permisos: " . $e->getMessage();
        }
        $this->closeConnection();
    }

    public function create($data) {
        try {
            if (!isset($data['id_rol'])) {
                throw new Exception('ID de rol no proporcionado');
            }

            // Comprobar si ya existen permisos para ese rol
            $check = $this->conexion->prepare("SELECT COUNT(*) FROM permisos_rol WHERE id_rol = :id_rol");
            $check->execute([':id_rol' => $data['id_rol']]);
            if ($check->fetchColumn() > 0) {
                throw new Exception('Los permisos para este rol ya existen.');
            }

            $stmt = $this->conexion->prepare("
                INSERT INTO permisos_rol (
                    id_rol, crear_empresas, gestionar_usuarios_globales, gestionar_permisos_globales,
                    crear_proyectos, borrar_proyectos, deshabilitar_proyectos,
                    gestionar_usuarios_empresa, gestionar_permisos_empresa
                ) VALUES (
                    :id_rol, :crear_empresas, :gestionar_usuarios_globales, :gestionar_permisos_globales,
                    :crear_proyectos, :borrar_proyectos, :deshabilitar_proyectos,
                    :gestionar_usuarios_empresa, :gestionar_permisos_empresa
                )
            ");

            $stmt->execute([
                ':id_rol' => $data['id_rol'],
                ':crear_empresas' => $data['crear_empresas'] ?? 0,
                ':gestionar_usuarios_globales' => $data['gestionar_usuarios_globales'] ?? 0,
                ':gestionar_permisos_globales' => $data['gestionar_permisos_globales'] ?? 0,
                ':crear_proyectos' => $data['crear_proyectos'] ?? 0,
                ':borrar_proyectos' => $data['borrar_proyectos'] ?? 0,
                ':deshabilitar_proyectos' => $data['deshabilitar_proyectos'] ?? 0,
                ':gestionar_usuarios_empresa' => $data['gestionar_usuarios_empresa'] ?? 0,
                ':gestionar_permisos_empresa' => $data['gestionar_permisos_empresa'] ?? 0,
            ]);

            $this->status = true;
            $this->message = 'Permisos creados correctamente';

        } catch (Exception $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

    public function update($data) {
        try {
            if (!isset($data['id_rol'])) {
                throw new Exception('ID de rol no proporcionado');
            }

            $stmt = $this->conexion->prepare("
                UPDATE permisos_rol SET 
                    crear_empresas = :crear_empresas,
                    gestionar_usuarios_globales = :gestionar_usuarios_globales,
                    gestionar_permisos_globales = :gestionar_permisos_globales,
                    crear_proyectos = :crear_proyectos,
                    borrar_proyectos = :borrar_proyectos,
                    deshabilitar_proyectos = :deshabilitar_proyectos,
                    gestionar_usuarios_empresa = :gestionar_usuarios_empresa,
                    gestionar_permisos_empresa = :gestionar_permisos_empresa
                WHERE id_rol = :id_rol
            ");

            $stmt->execute([
                ':id_rol' => $data['id_rol'],
                ':crear_empresas' => $data['crear_empresas'] ?? 0,
                ':gestionar_usuarios_globales' => $data['gestionar_usuarios_globales'] ?? 0,
                ':gestionar_permisos_globales' => $data['gestionar_permisos_globales'] ?? 0,
                ':crear_proyectos' => $data['crear_proyectos'] ?? 0,
                ':borrar_proyectos' => $data['borrar_proyectos'] ?? 0,
                ':deshabilitar_proyectos' => $data['deshabilitar_proyectos'] ?? 0,
                ':gestionar_usuarios_empresa' => $data['gestionar_usuarios_empresa'] ?? 0,
                ':gestionar_permisos_empresa' => $data['gestionar_permisos_empresa'] ?? 0,
            ]);

            $this->status = true;
            $this->message = 'Permisos actualizados correctamente';

        } catch (Exception $e) {
            $this->message = $e->getMessage();
        }

        $this->closeConnection();
    }

    public function delete($id) {
        try {
            $stmt = $this->conexion->prepare("DELETE FROM permisos_rol WHERE id_rol = :id_rol");
            $stmt->bindParam(':id_rol', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->status = true;
            $this->message = 'Permisos eliminados correctamente';

        } catch (PDOException $e) {
            $this->message = "Error al eliminar permisos: " . $e->getMessage();
        }

        $this->closeConnection();
    }
}
?>
