<?php
// Recibe: id_torneo, nombre. El usuario logueado que crea el equipo
// queda como capitan y como primer miembro.
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

requerir_sesion();
$datos = leer_datos();

$id_torneo = (int)($datos["id_torneo"] ?? 0);
$nombre    = trim($datos["nombre"] ?? "");
$id_capitan = (int)$_SESSION["id_usuario"];

if (strlen($nombre) < 2) {
    responder(false, "El nombre del equipo es obligatorio.");
}

$stmt = mysqli_prepare($conexion, "SELECT estado, num_equipos, id_organizador, (SELECT COUNT(*) FROM equipos WHERE id_torneo = ?) AS actuales FROM torneos WHERE id_torneo = ?");
mysqli_stmt_bind_param($stmt, "ii", $id_torneo, $id_torneo);
mysqli_stmt_execute($stmt);
$torneo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$torneo) {
    responder(false, "Torneo no encontrado.");
}

// El organizador de ESTE torneo no puede anotarse a jugar en el (tiene que
// administrarlo, no competir). En cambio, sí puede anotarse en torneos
// organizados por otras personas, como cualquier otro participante.
if ((int)$torneo["id_organizador"] === $id_capitan) {
    responder(false, "Sos el organizador de este torneo, no podés anotarte a jugar en él.");
}

if ($torneo["estado"] !== "pendiente") {
    responder(false, "Las inscripciones de este torneo ya estan cerradas.");
}
if ($torneo["actuales"] >= $torneo["num_equipos"]) {
    responder(false, "El torneo ya alcanzo el cupo maximo de equipos.");
}

// Un mismo usuario no puede tener más de un equipo dentro del mismo torneo.
$stmt = mysqli_prepare($conexion, "SELECT em.id_equipo FROM equipo_miembros em
                                    JOIN equipos e ON e.id_equipo = em.id_equipo
                                    WHERE e.id_torneo = ? AND em.id_usuario = ?");
mysqli_stmt_bind_param($stmt, "ii", $id_torneo, $id_capitan);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    responder(false, "Ya formas parte de un equipo en este torneo.");
}
mysqli_stmt_close($stmt);

mysqli_begin_transaction($conexion);
try {
    $stmt = mysqli_prepare($conexion, "INSERT INTO equipos (id_torneo, nombre, id_capitan) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isi", $id_torneo, $nombre, $id_capitan);
    mysqli_stmt_execute($stmt);
    $id_equipo = mysqli_insert_id($conexion);

    $stmt = mysqli_prepare($conexion, "INSERT INTO equipo_miembros (id_equipo, id_usuario) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ii", $id_equipo, $id_capitan);
    mysqli_stmt_execute($stmt);

    $stmt = mysqli_prepare($conexion, "INSERT INTO participantes (id_torneo, id_equipo) VALUES (?, ?)");
    mysqli_stmt_bind_param($stmt, "ii", $id_torneo, $id_equipo);
    mysqli_stmt_execute($stmt);

    mysqli_commit($conexion);
    Auditoria::registrar($conexion, $id_capitan, "crear", "equipo", $id_equipo, "Equipo \"$nombre\" creado");
    responder(true, "Equipo creado. Ya estas inscripto en el torneo.", ["id_equipo" => $id_equipo]);
} catch (Exception $e) {
    mysqli_rollback($conexion);
    responder(false, "No se pudo crear el equipo.");
}
