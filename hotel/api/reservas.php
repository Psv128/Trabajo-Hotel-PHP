<?php
require 'helpers.php';
$db = conectarBd();

// ===========================
// AUTENTICACIÓN POR TOKEN
// ===========================
$headers = getallheaders();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(["error" => "Token requerido"]);
    exit;
}

// Token tipo: 'Bearer <base64>'
list(, $encoded) = explode(' ', $headers['Authorization']);
$userId = base64_decode($encoded);

// ===========================
// FUNCIONES AUXILIARES
// ===========================

function obtenerMeses($inicio, $fin) {
    $meses = [];
    $fecha = new DateTime($inicio);
    $fechaFin = new DateTime($fin);
    while ($fecha <= $fechaFin) {
        $meses[] = [
            'anio' => (int)$fecha->format('Y'),
            'mes' => (int)$fecha->format('n')
        ];
        $fecha->modify('first day of next month');
    }
    return $meses;
}

function comprobarDisponibilidad($db, $inicio, $fin) {
    $meses = obtenerMeses($inicio, $fin);
    foreach ($meses as $m) {
        $stmt = $db->prepare(
            "SELECT reservas_actuales, reservas_maximas
             FROM disponibilidad_mensual
             WHERE anio = :anio AND mes = :mes
             FOR UPDATE"
        );
        $stmt->execute($m);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || $row['reservas_actuales'] >= $row['reservas_maximas']) {
            throw new Exception("No hay disponibilidad en {$m['mes']}/{$m['anio']}");
        }
    }
    return $meses;
}

function actualizarDisponibilidad($db, $meses, $incremento = 1) {
    foreach ($meses as $m) {
        $stmt = $db->prepare(
            "UPDATE disponibilidad_mensual
             SET reservas_actuales = reservas_actuales + :inc
             WHERE anio = :anio AND mes = :mes"
        );
        $stmt->execute([
            ':inc' => $incremento,
            ':anio' => $m['anio'],
            ':mes' => $m['mes']
        ]);
    }
}

// ===========================
// MÉTODOS API
// ===========================

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Listar reservas del usuario
        $stmt = $db->prepare("SELECT * FROM reservas WHERE id_creador = :u ORDER BY fecha_inicio");
        $stmt->execute([':u' => $userId]);
        echo json_encode(["success" => true, "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);

    if ($method === 'POST') {
        // Crear reserva
        if (!isset($data['fecha_inicio'], $data['fecha_salida'], $data['nombre'], $data['apellidos'], $data['numero_habitaciones'])) {
            throw new Exception("Datos incompletos");
        }

        $db->beginTransaction();

        // Comprobar disponibilidad
        $meses = comprobarDisponibilidad($db, $data['fecha_inicio'], $data['fecha_salida']);

        // Insertar reserva
        $stmt = $db->prepare(
            "INSERT INTO reservas (nombre, apellidos, numero_habitaciones, fecha_inicio, fecha_salida, id_creador)
             VALUES (:n, :a, :h, :fi, :fs, :u)"
        );
        $stmt->execute([
            ':n' => $data['nombre'],
            ':a' => $data['apellidos'],
            ':h' => $data['numero_habitaciones'],
            ':fi' => $data['fecha_inicio'],
            ':fs' => $data['fecha_salida'],
            ':u' => $userId
        ]);

        // Actualizar disponibilidad
        actualizarDisponibilidad($db, $meses, 1);

        $db->commit();
        echo json_encode(["success" => true, "id" => $db->lastInsertId()]);
        exit;
    }

    if ($method === 'PUT') {
        // Actualizar reserva
        parse_str($_SERVER['QUERY_STRING'], $query);
        $id = $query['id'] ?? null;
        if (!$id || !isset($data['fecha_inicio'], $data['fecha_salida'])) {
            throw new Exception("Datos incompletos o id inválido");
        }

        $db->beginTransaction();

        // Obtener reserva actual
        $stmt = $db->prepare("SELECT fecha_inicio, fecha_salida FROM reservas WHERE id = :id AND id_creador = :u FOR UPDATE");
        $stmt->execute([':id' => $id, ':u' => $userId]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$reserva) throw new Exception("Reserva no encontrada");

        // Reducir disponibilidad de meses antiguos
        $meses_antiguos = obtenerMeses($reserva['fecha_inicio'], $reserva['fecha_salida']);
        actualizarDisponibilidad($db, $meses_antiguos, -1);

        // Comprobar disponibilidad nueva
        $meses_nuevos = comprobarDisponibilidad($db, $data['fecha_inicio'], $data['fecha_salida']);

        // Actualizar reserva
        $stmt = $db->prepare(
            "UPDATE reservas SET fecha_inicio = :fi, fecha_salida = :fs WHERE id = :id AND id_creador = :u"
        );
        $stmt->execute([
            ':fi' => $data['fecha_inicio'],
            ':fs' => $data['fecha_salida'],
            ':id' => $id,
            ':u' => $userId
        ]);

        // Incrementar disponibilidad nueva
        actualizarDisponibilidad($db, $meses_nuevos, 1);

        $db->commit();
        echo json_encode(["success" => true]);
        exit;
    }

    if ($method === 'DELETE') {
        // Borrar reserva
        parse_str($_SERVER['QUERY_STRING'], $query);
        $id = $query['id'] ?? null;
        if (!$id) throw new Exception("ID de reserva requerido");

        $db->beginTransaction();

        $stmt = $db->prepare("SELECT fecha_inicio, fecha_salida FROM reservas WHERE id = :id AND id_creador = :u FOR UPDATE");
        $stmt->execute([':id' => $id, ':u' => $userId]);
        $reserva = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$reserva) throw new Exception("Reserva no encontrada");

        // Reducir disponibilidad
        $meses = obtenerMeses($reserva['fecha_inicio'], $reserva['fecha_salida']);
        actualizarDisponibilidad($db, $meses, -1);

        // Borrar reserva
        $stmt = $db->prepare("DELETE FROM reservas WHERE id = :id AND id_creador = :u");
        $stmt->execute([':id' => $id, ':u' => $userId]);

        $db->commit();
        echo json_encode(["success" => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(["error" => $e->getMessage()]);
}
