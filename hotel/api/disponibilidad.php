<?php
require 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$inicio = $_GET['inicio'] ?? null;
$fin = $_GET['fin'] ?? null;

if (!$inicio || !$fin || $inicio >= $fin) {
    http_response_code(400);
    echo json_encode(['error' => 'Fechas incorrectas']);
    exit;
}

if ($inicio < '2026-01-01' || $fin > '2026-12-31') {
    echo json_encode([
        'disponible' => false,
        'mensaje' => 'Solo se permiten reservas en 2026'
    ]);
    exit;
}

$db = conectarBd();

/* =========================
   FUNCIÓN MESES ENTRE FECHAS
   ========================= */
function obtenerMeses($inicio, $fin) {
    $meses = [];
    $fecha = new DateTime($inicio);
    $fechaFin = new DateTime($fin);

    while ($fecha <= $fechaFin) {
        $meses[] = [
            'anio' => (int)$fecha->format('Y'),
            'mes'  => (int)$fecha->format('n')
        ];
        $fecha->modify('first day of next month');
    }
    return $meses;
}

$meses = obtenerMeses($inicio, $fin);

foreach ($meses as $m) {
    $stmt = $db->prepare(
        "SELECT reservas_actuales, reservas_maximas
         FROM disponibilidad_mensual
         WHERE anio = :anio AND mes = :mes"
    );
    $stmt->execute($m);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || $row['reservas_actuales'] >= $row['reservas_maximas']) {
        echo json_encode([
            'disponible' => false,
            'mensaje' => "No hay disponibilidad en {$m['mes']}/{$m['anio']}"
        ]);
        exit;
    }
}

echo json_encode([
    'disponible' => true,
    'mensaje' => 'Hay disponibilidad en el rango seleccionado'
]);
