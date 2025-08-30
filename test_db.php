<?php
// test_db.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'retofitcat21';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexión exitosa!";
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>