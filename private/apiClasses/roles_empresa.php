<?php
declare(strict_types=1);

/**
 * Clase para manejar roles disponibles para admins de empresa.
 * Solo devuelve los roles 3 y 4.
 */

class RolesEmpresa {
    public bool $status = false;
    public string $message = '';
    public mixed $data = null;

    private PDO $pdo;

    public const ROUTE = 'roles_empresa';

    public function __construct(private Authorization $auth) {
        $conn = new Conexion();
        $this->pdo = $conn->pdo;
    }

    public function get(): void {
        try {
            $stmt = $this->pdo->prepare("SELECT id_rol, nombre_rol FROM roles WHERE id_rol IN (3, 4)");
            $stmt->execute();
            $this->data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->status = true;
            $this->message = 'Roles cargados correctamente.';
        } catch (PDOException $e) {
            $this->status = false;
            $this->message = 'Error al obtener roles.';
            $this->data = $e->getMessage();
        }
    }
}
