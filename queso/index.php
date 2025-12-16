<?php
$db = json_decode(file_get_contents ('credenciales.txt'),true);
function conectarBd($db){
    try {
        $conn = new PDO("mysql:host={$db['host']};dbname={$db['db']};charset=utf8",
        $db['username'], $db['password']);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } 
    catch (PDOException $exception) {
        exit($exception->getMessage());
    }
}
$dbCon = conectarBd($db);


if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $sql = "SELECT * from Reservas";
    $resultado = $dbCon->query($sql);
    $miArrayPrin = array();
    $miArray = array();
    while ($registro = $resultado->fetch()) {
        $miArray["nombre"]=$registro["nombre"];
        $miArray["apellido1"]=$registro["apellido1"];
        $miArray["apellido2"]=$registro["apellido2"];
        $miArray["dni"]=$registro["dni"];
        $miArray["telefono"]=$registro["telefono"];
        $miArray["edad"]=$registro["edad"];
        $miArray["fecha_entrada"]=$registro["fecha_entrada"];
        $miArray["fecha_salida"]=$registro["fecha_salida"];
        $miArray["precio"]=$registro["precio"];
        $miArray["empresa"]=$registro["empresa"];
        $miArray["num_habitaciones"]=$registro["num_habitaciones"];
        $miArrayPrin[$registro["id"]] = $miArray;
    }
    echo json_encode($miArrayPrin);
}

$input =json_decode(file_get_contents('archivo.json'), true);
    $sql = "INSERT INTO Reservas (nombre, apellido1, apellido2, dni, telefono, edad, fecha_entrada, fecha_salida, precio, empresa, num_habitaciones) 
    VALUES ('{$input['nombre']}', '{$input['apellido1']}', '{$input['apellido2']}', '{$input['dni']}', '{$input['telefono']}', '{$input['edad']}', '{$input['fecha_entrada']}', '{$input['fecha_salida']}', '{$input['precio']}', '{$input['empresa']}', '{$input['num_habitaciones']}')";
    echo $sql;
    $dbCon->exec($sql);
    $aluId = $dbCon->lastInsertId();
    if($aluId) {
        $input['id'] = $aluId;
        header("HTTP/1.1 200 OK");
        echo json_encode($input);
        exit();
    }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $input =json_decode(file_get_contents('php://input'), true);
    $sql = "INSERT INTO Reservas (nombre, apellido1, apellido2, dni, telefono, edad, fecha_entrada, fecha_salida, precio, empresa, num_habitaciones) 
    VALUES ('{$input['nombre']}', '{$input['apellido1']}', '{$input['apellido2']}', '{$input['dni']}', '{$input['telefono']}', '{$input['edad']}', '{$input['fecha_entrada']}', '{$input['fecha_salida']}', '{$input['precio']}', '{$input['empresa']}', '{$input['num_habitaciones']}')";
    echo $sql;
    $dbCon->exec($sql);
    $aluId = $dbCon->lastInsertId();
    if($aluId) {
        $input['id'] = $aluId;
        header("HTTP/1.1 200 OK");
        echo json_encode($input);
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'DELETE'){
    $input =json_decode(file_get_contents('php://input'), true);
    $sql = $dbCon->prepare("DELETE FROM reservas where id=:id");
    $sql->bindValue(':id', $input['id']);
    $sql->execute();
    header("HTTP/1.1 200 OK");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $postId = $input['id'];
    $telefono = $input['telefono'];
    $edad = $input['edad'];
    $dni = $input['dni'];
    $precio = $input['precio'];
    $nombre = $input['nombre'];
    $sql = "
    UPDATE reservas
    SET telefono='{$telefono}',
    edad='{$edad}',
    dni='{$dni}',
    precio='{$precio}',
    nombre='{$nombre}',
    WHERE id={$postId}
    ";
    echo $sql;
    $dbCon->exec($sql);
    header("HTTP/1.1 200 OK");
    exit();
}
?>