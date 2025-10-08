<?php
require_once 'config.php';
header('Content-Type: application/json');

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$idHerbalife = isset($_POST['idHerbalife']) ? trim($_POST['idHerbalife']) : '';

$response = ['exists' => false];

if ($email) {
    $sql = "SELECT id FROM usuarios WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    if ($stmt->rowCount() > 0) {
        $response['exists'] = true;
    }
} elseif ($idHerbalife) {
    $sql = "SELECT id FROM usuarios WHERE id_herbalife = :id_herbalife";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id_herbalife' => $idHerbalife]);
    if ($stmt->rowCount() > 0) {
        $response['exists'] = true;
    }
}

echo json_encode($response);
exit;
?>