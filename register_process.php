<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
require_once 'config.php';
session_start();

// === VALIDACIÓN AJAX EN TIEMPO REAL (SIN ARCHIVOS EXTRA) ===
if (isset($_GET['check_email'])) {
    $email = trim($_GET['check_email']);
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    echo json_encode(['exists' => $stmt->rowCount() > 0]);
    exit;
}

if (isset($_GET['check_id'])) {
    $id = trim($_GET['check_id']);
    if ($id === '') {
        echo json_encode(['exists' => false]);
        exit;
    }
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id_herbalife = ? AND id_herbalife != ''");
    $stmt->execute([$id]);
    echo json_encode(['exists' => $stmt->rowCount() > 0]);
    exit;
}

// === REGISTRO NORMAL ===
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
    $_SESSION['error'] = "Error de seguridad (CSRF).";
    header("Location: register.php");
    exit;
}

// === RECIBIR DATOS DEL FORMULARIO ===
$rol = trim($_POST['rol'] ?? '');
$seleccionCouch = trim($_POST['seleccionCouch'] ?? '');

if (!in_array($rol, ['user', 'coach'], true)) {
    $_SESSION['error'] = "Debes seleccionar si eres Participante o Coach.";
    header("Location: register.php");
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? '';
$nombre = trim($_POST['nombre'] ?? '');
$apellidoP = trim($_POST['apellidoPaterno'] ?? '');
$apellidoM = trim($_POST['apellidoMaterno'] ?? '');
$fechaNacimiento = $_POST['fechaNacimiento'] ?? '';
$genero = $_POST['genero'] ?? '';
$pais = $_POST['pais'] ?? '';
$telefono = preg_replace('/[^0-9]/', '', $_POST['telefono'] ?? '');
$idHerbalife = trim($_POST['idHerbalife'] ?? '');

// === VALIDACIONES BÁSICAS ===
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Correo electrónico inválido.";
    header("Location: register.php"); exit;
}
if (strlen($password) < 8) {
    $_SESSION['error'] = "La contraseña debe tener al menos 8 caracteres.";
    header("Location: register.php"); exit;
}
if (empty($nombre) || empty($apellidoP) || empty($fechaNacimiento) || empty($genero) || empty($pais) || empty($telefono)) {
    $_SESSION['error'] = "Todos los campos obligatorios deben completarse.";
    header("Location: register.php"); exit;
}

try {
    $pdo->beginTransaction();

    // === VERIFICAR EMAIL DUPLICADO ===
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        throw new Exception("Este correo ya está registrado.");
    }

    // === VERIFICAR ID HERBALIFE DUPLICADO (si se ingresó) ===
    if (!empty($idHerbalife)) {
        $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE id_herbalife = ?");
        $stmt->execute([$idHerbalife]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Este ID Herbalife ya está registrado.");
        }
    }

    // === VALIDAR COACH (solo para user) ===
    if ($rol === 'user') {
        if (empty($seleccionCouch)) {
            throw new Exception("Debes seleccionar un Coach.");
        }
        $stmt = $pdo->prepare("SELECT id FROM coaches WHERE name = ?");
        $stmt->execute([$seleccionCouch]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("El Coach seleccionado no existe.");
        }
    } else {
        $seleccionCouch = null; // ← CLAVE: NULL para Coach
    }

    // === INSERTAR USUARIO ===
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (
            email, contrasena, nombre, apellido_paterno, apellido_materno,
            fecha_nacimiento, genero, pais, telefono, id_herbalife,
            seleccion_couch, rol, habilitado
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1
        )
    ");
    $stmt->execute([
        $email,
        $hashed,
        $nombre,
        $apellidoP,
        $apellidoM ?: null,
        $fechaNacimiento,
        $genero,
        $pais,
        $telefono,
        !empty($idHerbalife) ? $idHerbalife : null,
        $seleccionCouch,
        $rol
    ]);

    $user_id = $pdo->lastInsertId();

    // === SI ES COACH → AGREGAR A TABLA coaches ===
    if ($rol === 'coach') {
        $coach_name = trim("$nombre $apellidoP " . ($apellidoM ?: ''));
        $stmt = $pdo->prepare("INSERT IGNORE INTO coaches (name, user_id) VALUES (?, ?)");
        $stmt->execute([$coach_name, $user_id]);
    }

    $pdo->commit();

    // === ÉXITO ===
    $_SESSION['user_id'] = $user_id;
    $_SESSION['nombre'] = $nombre;
    $_SESSION['rol'] = $rol;
    $_SESSION['success'] = "¡Cuenta creada con éxito, " . ($rol === 'user' ? 'Participante' : 'Coach') . "!";

    unset($_SESSION[$attempt_key]);
    header("Location: prize_wheel.php");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    error_log("Error en registro: " . $e->getMessage() . " | IP: $ip_address");
    header("Location: register.php");
    exit;
}
?>