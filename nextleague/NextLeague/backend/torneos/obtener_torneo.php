<?php
// GET obtener_torneo.php?id=3
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

$id_torneo = (int)($_GET["id"] ?? 0);
if (!$id_torneo) {
    responder(false, "Falta el id del torneo.");
}

$consulta = "SELECT t.*, j.nombre AS juego, j.categoria, u.nombre_usuario AS organizador
             FROM torneos t
             JOIN juegos j ON j.id_juego = t.id_juego
             JOIN usuarios u ON u.id_usuario = t.id_organizador
             WHERE t.id_torneo = ?";
$stmt = mysqli_prepare($conexion, $consulta);
mysqli_stmt_bind_param($stmt, "i", $id_torneo);
mysqli_stmt_execute($stmt);
$torneo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$torneo) {
    responder(false, "Torneo no encontrado.");
}

responder(true, "", ["torneo" => $torneo]);
