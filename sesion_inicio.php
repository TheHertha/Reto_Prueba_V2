<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Error de seguridad. Intenta de nuevo.";
        header("Location: login.php");
        exit;
    }

    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
    $remember = isset($_POST['recordar']);

    try {
        $sql = "SELECT id, nombre, contrasena, rol FROM usuarios WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user && $user['contrasena'] === $password) {
            // Regenerate session ID
            session_regenerate_id(true);

            // Store user data in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nombre'] = $user['nombre'];
            $_SESSION['rol'] = $user['rol'];
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
            $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];

            // Handle "Remember Me"
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60);
                $sql = "INSERT INTO tokens (usuario_id, token, ip_address, user_agent, expires_at) VALUES (:usuario_id, :token, :ip_address, :user_agent, :expires_at)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'usuario_id' => $user['id'],
                    'token' => $token,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'expires_at' => $expires_at
                ]);

                setcookie('remember_token', $token, [
                    'expires' => time() + 30 * 24 * 60 * 60,
                    'path' => '/',
                    'secure' => false, // Set to true in production with HTTPS
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);
            }

            header("Location: inicio.php");
            exit;
        } else {
            $_SESSION['error'] = "Correo o contraseña incorrectos.";
            header("Location: login.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error en login: " . $e->getMessage());
        $_SESSION['error'] = "Error interno del servidor.";
        header("Location: login.php");
        exit;
    }
} else {
    // Check for existing token
    if (isset($_COOKIE['remember_token'])) {
        try {
            $token = $_COOKIE['remember_token'];
            $sql = "SELECT usuario_id, ip_address, user_agent, expires_at FROM tokens WHERE token = :token";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['token' => $token]);
            $token_data = $stmt->fetch();

            if ($token_data && strtotime($token_data['expires_at']) > time()) {
                $sql = "SELECT id, nombre, rol FROM usuarios WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['id' => $token_data['usuario_id']]);
                $user = $stmt->fetch();

                if ($user) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['nombre'] = $user['nombre'];
                    $_SESSION['rol'] = $user['rol'];
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];

                    header("Location: inicio.php");
                    exit;
                }
            }
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        } catch (PDOException $e) {
            error_log("Error en token check: " . $e->getMessage());
        }
    }
    $_SESSION['error'] = "Acceso no permitido.";
    header("Location: login.php");
    exit;
}
?>