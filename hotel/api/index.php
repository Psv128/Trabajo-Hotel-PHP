<?php
header("Content-Type: application/json");

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$uri = explode('/', trim($path, '/'));
$recurso = $uri[count($uri) - 1];

switch ($recurso) {
    case 'login':
        require 'auth.php';
        break;

    case 'reservas':
        require 'reservas.php';
        break;

    case 'disponibilidad':
        require 'disponibilidad.php';
        break;

    default:
        http_response_code(404);
        echo json_encode(["error" => "Endpoint no encontrado"]);
}
