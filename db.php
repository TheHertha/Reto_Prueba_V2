<?php
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = 'Sk2Lo9R+k3.y';
$DB_NAME = 'retofitcat21';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    http_response_code(500);+
    die('Error de conexión a la base de datos: ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
?>
