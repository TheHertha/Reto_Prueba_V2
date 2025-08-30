<?php
session_start();
require_once 'config.php';

try {
    // Delete remember token if exists
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $sql = "DELETE FROM tokens WHERE token = :token";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['token' => $token]);
        setcookie('remember_token', '', time() - 3600, '/', '', true, true);
    }
} catch (PDOException $e) {
    error_log("Error en logout: " . $e->getMessage());
}

// Destroy session
session_unset();
session_destroy();

header("Location: login.php");
exit;
?>