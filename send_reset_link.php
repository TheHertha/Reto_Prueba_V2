<?php
session_start();

// Debug: Verify config.php exists
if (!file_exists('config.php')) {
    $_SESSION['error'] = 'Error: config.php not found in ' . __DIR__;
    header('Location: recuperar.php');
    exit;
}

require_once 'config.php';

// Debug: Verify $pdo is defined
if (!isset($pdo)) {
    $_SESSION['error'] = 'Error: Database connection not initialized in config.php';
    header('Location: recuperar.php');
    exit;
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Error de seguridad. Intente de nuevo.';
    header('Location: recuperar.php');
    exit;
}

// Validate email
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Correo electrónico inválido.';
    header('Location: recuperar.php');
    exit;
}

try {
    // Check if email exists in users table
    $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE email = ?');
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'No se encontró una cuenta con ese correo.';
        header('Location: recuperar.php');
        exit;
    }

    // Generate unique token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Store token in password_reset_tokens table
    $stmt = $pdo->prepare('INSERT INTO password_reset_tokens (email, token, created_at, expires_at) VALUES (?, ?, NOW(), ?)');
    $stmt->execute([$email, $token, $expires_at]);

    // Send email (Option 1: Using mail())
    $reset_link = "http://localhost/cat21/reset_password.php?token=$token";
    $subject = 'Recuperación de Contraseña';
    $message = "Hola,\n\nPara restablecer tu contraseña, haz clic en el siguiente enlace:\n$reset_link\n\nEste enlace expira en 1 hora.\n\nSi no solicitaste esto, ignora este correo.";
    $headers = 'From: no-reply@localhost' . "\r\n" .
               'Reply-To: no-reply@localhost' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();

    if (mail($email, $subject, $message, $headers)) {
        $_SESSION['success'] = 'Se ha enviado un enlace de recuperación a tu correo.';
    } else {
        $_SESSION['error'] = 'Error al enviar el correo. Intenta de nuevo.';
    }

    /*
    // Option 2: Using PHPMailer (uncomment and configure for production)
    require 'vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@gmail.com';
        $mail->Password = 'your_app_password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('your_email@gmail.com', 'CAT 21');
        $mail->addAddress($email);
        $mail->isHTML(false);
        $mail->Subject = 'Recuperación de Contraseña';
        $mail->Body = "Hola,\n\nPara restablecer tu contraseña, haz clic en el siguiente enlace:\n$reset_link\n\nEste enlace expira en 1 hora.\n\nSi no solicitaste esto, ignora este correo.";

        $mail->send();
        $_SESSION['success'] = 'Se ha enviado un enlace de recuperación a tu correo.';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error al enviar el correo: ' . $mail->ErrorInfo;
    }
    */

    header('Location: recuperar.php');
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = 'Error del servidor: ' . $e->getMessage();
    header('Location: recuperar.php');
    exit;
}
?>