<?php
// Configuración de la base de datos
$host = 'localhost';
$username = 'root';
$password = 'Sk2Lo9R+k3.yz';//
$database = 'retofitcat21';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error de conexión: " . $e->getMessage());
    die("No se pudo conectar a la base de datos. Por favor, intenta de nuevo más tarde.");
}

// Allowed values for dropdowns
$ALLOWED_COUNTRIES = ['canada' => 'Canadá', 'mexico' => 'México', 'us' => 'Estados Unidos'];
$ALLOWED_GENDERS = ['masculino' => 'Masculino', 'femenino' => 'Femenino', 'otro' => 'Otro'];
$ALLOWED_COACHES = ['coach1' => 'Coach 1', 'coach2' => 'Coach 2'];

// Rate limiting settings
define('MAX_REGISTRATION_ATTEMPTS', 5);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 15 * 60); // 15 minutes in seconds

// Función para subir imágenes
function subirImagen($archivo) {
    $directorioDestino = "Uploads/"; // Asegúrate de que la carpeta en el servidor también sea "Uploads" con U mayúscula
    
    if (!file_exists($directorioDestino)) {
        mkdir($directorioDestino, 0777, true);
    }
    
    $nombreArchivo = basename($archivo["name"]);
    $tipoArchivo = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
    
    // Validar si es imagen
    $check = getimagesize($archivo["tmp_name"]);
    if ($check === false) {
        return false;
    }
    
    // Limitar tamaño a 5MB
    if ($archivo["size"] > 5000000) {
        return false;
    }
    
    // Tipos permitidos
    if (!in_array($tipoArchivo, ["jpg", "png", "jpeg", "gif"])) {
        return false;
    }
    
    // Generar nombre único
    $nombreUnico = time() . '_' . $nombreArchivo;
    $rutaCompleta = $directorioDestino . $nombreUnico;
    
    if (move_uploaded_file($archivo["tmp_name"], $rutaCompleta)) {
        return $nombreUnico;
    }
    
    return false;
}

// Función para formatear fecha
function formatearFecha($fecha) {
    try {
        $fechaObj = new DateTime($fecha);
        return $fechaObj->format('d/m/Y');
    } catch (Exception $e) {
        error_log("Error al formatear fecha: " . $e->getMessage());
        return $fecha;
    }
}

// Función para formatear hora
function formatearHora($hora) {
    try {
        $horaObj = new DateTime($hora);
        return $horaObj->format('H:i');
    } catch (Exception $e) {
        error_log("Error al formatear hora: " . $e->getMessage());
        return $hora;
    }
}
?>
