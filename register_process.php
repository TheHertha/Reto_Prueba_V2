<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once 'config.php';
session_start();

$ip_address = $_SERVER['REMOTE_ADDR'];
$attempt_key = 'register_attempts_' . $ip_address;
$attempts = $_SESSION[$attempt_key] ?? 0;

if ($attempts >= 5) {
    $_SESSION['error'] = "Demasiados intentos. Intenta más tarde.";
    header("Location: register.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION['error'] = "Acceso no permitido.";
    header("Location: register.php");
    exit;
}

if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = "Error de seguridad.";
    header("Location: register.php");
    exit;
}

$role = trim($_POST['role'] ?? '');
$seleccionCouch = trim($_POST['seleccionCouch'] ?? '');

if (!in_array($role, ['coach', 'retador'])) {
    $_SESSION['error'] = "Debes seleccionar un rol válido.";
    header("Location: register.php");
    exit;
}

$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];
$nombre = filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
$apellidoP = filter_var($_POST['apellidoPaterno'], FILTER_SANITIZE_STRING);
$apellidoM = filter_var($_POST['apellidoMaterno'] ?? '', FILTER_SANITIZE_STRING);
$fechaNacimiento = $_POST['fechaNacimiento'];
$genero = $_POST['genero'];
$pais = $_POST['pais'];
$telefono = filter_var($_POST['telefono'], FILTER_SANITIZE_STRING);
$idHerbalife = filter_var($_POST['idHerbalife'] ?? '', FILTER_SANITIZE_STRING);

// Validaciones
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Correo inválido.";
    header("Location: register.php");
    exit;
}

// ... resto de validaciones ...

try {
    $pdo->beginTransaction();

    // Verificar duplicados
    $sql = "SELECT id FROM usuarios WHERE email = :email";
    $params = [':email' => $email];
    if (!empty($idHerbalife)) {
        $sql .= " OR id_herbalife = :id_herbalife";
        $params[':id_herbalife'] = $idHerbalife;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->rowCount() > 0) {
        throw new Exception("Correo o ID Herbalife ya registrado.");
    }

    // Validar coach si es retador
    if ($role === 'retador') {
        if (empty($seleccionCouch)) {
            throw new Exception("Debes seleccionar un Coach.");
        }
        $stmt = $pdo->prepare("SELECT id FROM coaches WHERE name = ?");
        $stmt->execute([$seleccionCouch]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("Coach seleccionado no válido.");
        }
    } else {
        $seleccionCouch = null;
    }

    // Insertar usuario
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO usuarios (email, contrasena, nombre, apellido_paterno, apellido_materno, fecha_nacimiento, genero, pais, telefono, id_herbalife, role, seleccion_couch, fecha_registro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$email, $hashed, $nombre, $apellidoP, $apellidoM ?: null, $fechaNacimiento, $genero, $pais, $telefono, $idHerbalife ?: null, $role, $seleccionCouch]);

    $user_id = $pdo->lastInsertId();

    // Si es coach → agregarlo
    if ($role === 'coach') {
        $coach_name = trim("$nombre $apellidoP " . ($apellidoM ?: ''));
        $stmt = $pdo->prepare("INSERT IGNORE INTO coaches (name, user_id) VALUES (?, ?)");
        $stmt->execute([$coach_name, $user_id]);
    }

    $pdo->commit();

    $_SESSION['user_id'] = $user_id;
    $_SESSION['nombre'] = $nombre;
    $_SESSION['role'] = $role;
    $_SESSION['success'] = "¡Bienvenido, " . ucfirst($role) . "!";

    header("Location: prize_wheel.php");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    header("Location: register.php");
    exit;
}
?>