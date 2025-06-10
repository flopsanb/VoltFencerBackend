<?php
/**
 * Clase para gestión de tickets de soporte
 * Envía tickets por email mediante PHPMailer.
 * 
 * @author 
 * @version 1.1
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../../vendor/autoload.php';

class Soporte {
    public $status = false;
    public $message = null;
    public $data = null;

    public function crearTicket($request) {
        try {
            // Validación de campos obligatorios
            $titulo = trim($request['asunto'] ?? '');
            $mensaje = trim($request['mensaje'] ?? '');
            $email = trim($request['email'] ?? '');

            if (!$titulo || !$mensaje || !$email) {
                $this->message = 'Faltan campos obligatorios: asunto, mensaje o email';
                return;
            }

            // Usuario autenticado
            $usuario = $GLOBALS['authorization']->usuario ?? [];
            $nombreUsuario = $usuario['nombre_publico'] ?? 'Usuario Anónimo';
            $usuarioSistema = $usuario['usuario'] ?? 'Desconocido';
            $rol = $usuario['rol'] ?? 'Desconocido';
            $empresa = $usuario['id_empresa'] ?? 'Sin empresa';

            // Inicializar PHPMailer
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'voltfencer@gmail.com';
            $mail->Password = 'hlgclqidhdmdddmo';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';

            // Cabeceras
            $mail->setFrom($email, $nombreUsuario);
            $mail->addAddress('voltfencer@gmail.com', 'Soporte VoltFencer');

            $mail->isHTML(true);
            $mail->Subject = "🎫 Nuevo Ticket de Soporte: " . htmlspecialchars($titulo);

            $fecha = date('d/m/Y H:i:s');
            $contenido = "
                <h2 style='color:#0044cc;'>📨 Nuevo Ticket de Soporte</h2>
                <p><strong>Fecha:</strong> $fecha</p>
                <p><strong>Usuario:</strong> " . htmlspecialchars($usuarioSistema) . "</p>
                <p><strong>Nombre público:</strong> " . htmlspecialchars($nombreUsuario) . "</p>
                <p><strong>Rol:</strong> " . htmlspecialchars($rol) . "</p>
                <p><strong>ID de empresa:</strong> " . htmlspecialchars($empresa) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                <hr>
                <h3 style='color:#cc0000;'>📝 Asunto:</h3>
                <p>" . htmlspecialchars($titulo) . "</p>
                <h3>📋 Mensaje:</h3>
                <div style='padding:10px; border-left:3px solid #ccc; background:#f9f9f9; white-space:pre-line;'>
                    " . nl2br(htmlspecialchars($mensaje)) . "
                </div>
                <br>
                <p style='font-size:12px; color:#999;'>Este mensaje ha sido generado automáticamente por el sistema de soporte de VoltFencer.</p>
            ";

            $mail->Body = $contenido;

            $mail->send();

            $this->status = true;
            $this->message = 'Ticket de soporte enviado correctamente';

        } catch (Exception $e) {
            $this->message = 'Error al enviar el ticket: ' . $e->getMessage();
        }
    }
}
