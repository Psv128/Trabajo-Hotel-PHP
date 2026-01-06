<?php
require 'helpers.php';
$db = conectarBd();

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email'], $data['password'])) {
    http_response_code(400);
    echo json_encode(["error" => "Datos incompletos"]);
    exit;
}

$stmt = $db->prepare("SELECT id, email, password, rol FROM usuarios WHERE email = :email");
$stmt->execute([':email' => $data['email']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && password_verify($data['password'], $user['password'])) {

    // token simple
    $token = base64_encode($user['id'] . '|' . time());

    echo json_encode([
        "success" => true,
        "token" => $token,
        "usuario" => [
            "id" => $user['id'],
            "email" => $user['email'],
            "rol" => $user['rol']
        ]
    ]);
} else {
    http_response_code(401);
    echo json_encode(["error" => "Credenciales incorrectas"]);
}
