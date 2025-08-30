<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once 'config.php';
session_start();

// Define allowed values for validation
$ALLOWED_GENDERS = [
    'Masculino' => 'Masculino',
    'Femenino' => 'Femenino',
    'Otro' => 'Otro'
];
$ALLOWED_COUNTRIES = [
    'Argentina' => 'Argentina',
    'Bolivia' => 'Bolivia',
    'Canadá' => 'Canadá',
    'Colombia' => 'Colombia',
    'Costa Rica' => 'Costa Rica',
    'Ecuador' => 'Ecuador',
    'El Salvador' => 'El Salvador',
    'Estados Unidos' => 'Estados Unidos',
    'Guatemala' => 'Guatemala',
    'Italia' => 'Italia',
    'México' => 'México',
    'Perú' => 'Perú'
];

// Fetch coaches from database
try {
    $sql = "SELECT name FROM coaches";
    $stmt = $pdo->query($sql);
    $ALLOWED_COACHES = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'name', 'name');
} catch (Exception $e) {
    error_log("Error al obtener coaches: " . $e->getMessage());
    $_SESSION['error'] = "Error al cargar lista de coaches. Intenta de nuevo.";
    header("Location: register.php");
    exit;
}

// Rate limiting
$ip_address = $_SERVER['REMOTE_ADDR'];
$attempt_key = 'register_attempts_' . $ip_address;
$attempts = isset($_SESSION[$attempt_key]) ? $_SESSION[$attempt_key] : 0;
define('MAX_REGISTRATION_ATTEMPTS', 5); // Define if not already set

if ($attempts >= MAX_REGISTRATION_ATTEMPTS) {
    $_SESSION['error'] = "Demasiados intentos de registro. Intenta de nuevo más tarde.";
    header("Location: register.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Error de seguridad. Intenta de nuevo.";
        header("Location: register.php");
        exit;
    }

    // Sanitize and validate inputs
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);
    $nombre = filter_var($_POST['nombre'], FILTER_SANITIZE_STRING);
    $apellidoP = filter_var($_POST['apellidoPaterno'], FILTER_SANITIZE_STRING);
    $apellidoM = filter_var($_POST['apellidoMaterno'], FILTER_SANITIZE_STRING);
    $fechaNacimiento = $_POST['fechaNacimiento'];
    $genero = $_POST['genero'];
    $pais = $_POST['pais'];
    $telefono = filter_var($_POST['telefono'], FILTER_SANITIZE_STRING);
    $idHerbalife = filter_var($_POST['idHerbalife'], FILTER_SANITIZE_STRING);
    $seleccionCouch = filter_var($_POST['seleccionCouch'], FILTER_SANITIZE_STRING);

    // Server-side validation
    try {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Correo inválido.");
        }
        if (strlen($password) < 8 || !preg_match('/[a-zA-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            throw new Exception("La contraseña debe tener al menos 8 caracteres, incluyendo una letra y un número.");
        }
        if (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $nombre) || !preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $apellidoP) || !preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $apellidoM)) {
            throw new Exception("El nombre y apellidos solo deben contener letras y espacios.");
        }
        if (!preg_match('/^[0-9]{10,15}$/', $telefono)) {
            throw new Exception("El número de teléfono debe tener entre 10 y 15 dígitos.");
        }
        if (!array_key_exists($genero, $ALLOWED_GENDERS)) {
            throw new Exception("Género no válido.");
        }
        if (!array_key_exists($pais, $ALLOWED_COUNTRIES)) {
            throw new Exception("País no válido.");
        }
        if (!array_key_exists($seleccionCouch, $ALLOWED_COACHES)) {
            throw new Exception("Coach no válido.");
        }

        // Check for duplicate email or id_herbalife
        $sql = "SELECT id FROM usuarios WHERE email = :email OR id_herbalife = :id_herbalife";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email, 'id_herbalife' => $idHerbalife]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("El correo o ID de Herbalife ya está registrado.");
        }

        // Insert user with plain text password
        $sql = "INSERT INTO usuarios (email, contrasena, nombre, apellido_paterno, apellido_materno, fecha_nacimiento, genero, pais, telefono, id_herbalife, seleccion_couch, fecha_registro)
                VALUES (:email, :contrasena, :nombre, :apellido_paterno, :apellido_materno, :fecha_nacimiento, :genero, :pais, :telefono, :id_herbalife, :seleccion_couch, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'email' => $email,
            'contrasena' => $password, // Store plain text password
            'nombre' => $nombre,
            'apellido_paterno' => $apellidoP,
            'apellido_materno' => $apellidoM,
            'fecha_nacimiento' => $fechaNacimiento,
            'genero' => $genero,
            'pais' => $pais,
            'telefono' => $telefono,
            'id_herbalife' => $idHerbalife,
            'seleccion_couch' => $seleccionCouch
        ]);

        // Auto-login
        $user_id = $pdo->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user_id;
        $_SESSION['nombre'] = $nombre;
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['ip_address'] = $ip_address;

        // Create "Remember Me" token
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60);
        $sql = "INSERT INTO tokens (usuario_id, token, ip_address, user_agent, expires_at) VALUES (:usuario_id, :token, :ip_address, :user_agent, :expires_at)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'usuario_id' => $user_id,
            'token' => $token,
            'ip_address' => $ip_address,
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

        $_SESSION['success'] = "Registro exitoso. ¡Bienvenido!";
        header("Location: prize_wheel.php");
        exit;
    } catch (Exception $e) {
        error_log("Error al registrar: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header("Location: register.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Acceso no permitido.";
    header("Location: register.php");
    exit;
}
?>