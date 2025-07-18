<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verifica que los valores se reciban correctamente
    $name = htmlspecialchars($_POST["name"]);
    $email = htmlspecialchars($_POST["email"]);
    $message = htmlspecialchars($_POST["message"]);

    // Crea la instancia de PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // Cambié esto a Gmail SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'cfmjoyas@gmail.com';  // Dirección de correo de Gmail
        $mail->Password = getenv('GMAIL_PASSWORD') ?: 'flqh anuv qwvv joot'; // Usa una contraseña de aplicación si tienes habilitada la verificación en dos pasos
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;  // Usamos el puerto 587 para TLS

        // Configuración del correo
        $mail->setFrom($email, $name);
        $mail->addAddress('cfmjoyas@gmail.com'); // El correo al que enviarás los mensajes
        $mail->Subject = "Nuevo mensaje de contacto";
        $mail->Body = "Nombre: $name\nCorreo: $email\n\nMensaje:\n$message";

        // Enviar el correo
        $mail->send();
        echo "¡Mensaje enviado con éxito!";
    } catch (Exception $e) {
        echo "Error al enviar el mensaje: {$mail->ErrorInfo}";
    }
} else {
    echo "Método no permitido.";
}
?>
