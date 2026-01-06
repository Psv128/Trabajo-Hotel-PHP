<?php
function conectarBd() {
    $db = json_decode(file_get_contents(__DIR__ . '/../credenciales.txt'), true);
    return new PDO(
        "mysql:host={$db['host']};dbname={$db['db']};charset=utf8mb4",
        $db['username'],
        $db['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

