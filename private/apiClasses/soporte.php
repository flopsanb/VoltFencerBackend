<?php
/**
 * Clase para gestión de tickets de soporte
 * Envía tickets por email mediante PHPMailer usando variables de entorno con getenv().
 * 
 * @author Francisco
 * @version 1.3
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

class Soporte {
    public $status = false;
    public $message = null;
    public $data = null;

    private $auth;

    public function __construct($auth) {
        $this->auth = $auth;
    }

    /**
     * Crea un ticket de soporte enviando un email con los detalles del ticket.
     * 
     * @param array $request Datos del ticket: 'asunto', 'mensaje', 'email'.
     * 
     * @return void
     */
    public function crearTicket($request) {
        try {
            $titulo  = $request['asunto']  ?? null;
            $mensaje = $request['mensaje'] ?? null;
            $email   = $request['email']   ?? null;

            if (!$titulo || !$mensaje) {
                $this->message = 'Faltan campos obligatorios: título o mensaje';
                return;
            }

            $usuario = $this->auth->usuario;

            $mail = new PHPMailer(true);
            $mail->SMTPDebug  = 2;
            $mail->Debugoutput = 'error_log';
            $mail->isSMTP();
            $mail->Host       = getenv('MAIL_HOST');
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('MAIL_USERNAME');
            $mail->Password   = getenv('MAIL_PASSWORD');
            $mail->SMTPSecure = 'tls';
            $mail->Port       = getenv('MAIL_PORT');

            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';

            $mail->setFrom(getenv('MAIL_FROM'), getenv('MAIL_FROM_NAME'));
            $mail->addAddress(getenv('MAIL_TO'), getenv('MAIL_TO_NAME'));

            $mail->isHTML(true);
            $mail->Subject = "🎫 Nuevo Ticket de Soporte: $titulo";

            $fecha = date('d/m/Y H:i:s');
            $contenido = "
                <h2 style='color: #0044cc;'>📨 Nuevo Ticket de Soporte</h2>
                <p><strong>Fecha:</strong> $fecha</p>
                <p><strong>Usuario:</strong> {$usuario['usuario']}</p>
                <p><strong>Nombre público:</strong> {$usuario['nombre_publico']}</p>
                <p><strong>Rol:</strong> {$usuario['rol']}</p>
                <p><strong>ID de empresa:</strong> {$usuario['id_empresa']}</p>
                <p><strong>Email:</strong> $email</p>
                <hr>
                <h3 style='color:#cc0000;'>📝 Título:</h3>
                <p>$titulo</p>
                <h3>📋 Mensaje:</h3>
                <div style='padding:10px; border-left:3px solid #ccc; background:#f9f9f9; white-space:pre-line;'>"
                . htmlspecialchars($mensaje) . "</div>
                <br>
                <p style='font-size:12px; color:#999;'>Sistema automático de soporte VoltFencer.</p>
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
