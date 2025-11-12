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

// ACEPTAMOS SOLO 'user' (Participante) y 'coach'
if (!in_array($role, ['user', 'coach'], true)) {
    $_SESSION['error'] = "Debes seleccionar un rol válido.";
    header("Location: register.php");
    exit;
}

$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
$password = $_POST['password'];
$nombre = trim($_POST['nombre']);
$apellidoP = trim($_POST['apellidoPaterno']);
$apellidoM = trim($_POST['apellidoMaterno'] ?? '');
$fechaNacimiento = $_POST['fechaNacimiento'];
$genero = $_POST['genero'];
$pais = $_POST['pais'];
$telefono = preg_replace('/[^0-9]/', '', $_POST['telefono']);
$idHerbalife = trim($_POST['idHerbalife'] ?? '');

// VALIDACIONES BÁSICAS
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Correo electrónico inválido.";
    header("Location: register.php");
    exit;
}
if (strlen($password) < 8) {
    $_SESSION['error'] = "La contraseña debe tener al menos 8 caracteres.";
    header("Location: register.php");
    exit;
}
if (empty($nombre) || empty($apellidoP) || empty($fechaNacimiento) || empty($genero) || empty($pais) || empty($telefono)) {
    $_SESSION['error'] = "Todos los campos obligatorios deben completarse.";
    header("Location: register.php");
    exit;
}

try {
    $pdo->beginTransaction();

    // VERIFICAR DUPLICADOS
    $sql = "SELECT id FROM usuarios WHERE email = :email";
    $params = [':email' => $email];
    if (!empty($idHerbalife)) {
        $sql .= " OR id_herbalife = :id_herbalife";
        $params[':id_herbalife'] = $idHerbalife;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    if ($stmt->rowCount() > 0) {
        throw new Exception("El correo o ID Herbalife ya está registrado.");
    }

    // VALIDAR COACH (solo para Participantes = 'user')
    if ($role === 'user') {
        if (empty($seleccionCouch)) {
            throw new Exception("Debes seleccionar un Coach.");
        }
        $stmt = $pdo->prepare("SELECT id FROM coaches WHERE name = ?");
        $stmt->execute([$seleccionCouch]);
        if ($stmt->rowCount() === 0) {
            throw new Exception("El Coach seleccionado no existe.");
        }
    } else {
        $seleccionCouch = null;
    }

    // INSERTAR USUARIO (¡PERFECTO CON 'rol' Y VALORES 'user'/'coach'!)
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO usuarios (
            email, contrasena, nombre, apellido_paterno, apellido_materno,
            fecha_nacimiento, genero, pais, telefono, id_herbalife,
            rol, seleccion_couch, fecha_registro
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
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
        $idHerbalife ?: null,
        $role,           // 'user' o 'coach' → ¡COMPATIBLE CON TU ENUM!
        $seleccionCouch
    ]);

    $user_id = $pdo->lastInsertId();

    // SI ES COACH → AGREGAR A TABLA COACHES
    if ($role === 'coach') {
        $coach_name = trim("$nombre $apellidoP " . ($apellidoM ? $apellidoM : ''));
        $stmt = $pdo->prepare("INSERT IGNORE INTO coaches (name, user_id) VALUES (?, ?)");
        $stmt->execute([$coach_name, $user_id]);
    }

    $pdo->commit();

    // ÉXITO
    $_SESSION['user_id'] = $user_id;
    $_SESSION['nombre'] = $nombre;
    $_SESSION['role'] = $role;  // 'user' o 'coach'
    $_SESSION['success'] = "¡Cuenta creada con éxito, " . ($role === 'user' ? 'Participante' : 'Coach') . "!";

    // Limpiar intentos
    unset($_SESSION[$attempt_key]);

    header("Location: prize_wheel.php");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    error_log("Error en registro: " . $e->getMessage());
    header("Location: register.php");
    exit;
}
?>