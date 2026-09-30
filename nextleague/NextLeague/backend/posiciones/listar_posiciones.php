<?php
// GET listar_posiciones.php?id_torneo=3
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

$id_torneo = (int)($_GET["id_torneo"] ?? 0);

$consulta = "SELECT p.*, e.nombre AS equipo, e.logo
             FROM tabla_posiciones p
             JOIN equipos e ON e.id_equipo = p.id_equipo
             WHERE p.id_torneo = ?
             ORDER BY p.puntos DESC, (p.gf - p.gc) DESC, p.gf DESC";
$stmt = mysqli_prepare($conexion, $consulta);
mysqli_stmt_bind_param($stmt, "i", $id_torneo);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$posiciones = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $posiciones[] = $fila;
}

echo json_encode(["exito" => true, "posiciones" => $posiciones]);
