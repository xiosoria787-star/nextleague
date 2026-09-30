<?php
// GET listar_llaves.php?id_torneo=3
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

$id_torneo = (int)($_GET["id_torneo"] ?? 0);

$consulta = "SELECT l.ronda, l.posicion,
                    e.id_enfrentamiento, e.resultado_local, e.resultado_visitante, e.estado,
                    e.id_equipo_local, e.id_equipo_visitante,
                    el.nombre AS equipo_local, ev.nombre AS equipo_visitante,
                    e.id_ganador
             FROM llaves l
             JOIN enfrentamientos e ON e.id_enfrentamiento = l.id_enfrentamiento
             LEFT JOIN equipos el ON el.id_equipo = e.id_equipo_local
             LEFT JOIN equipos ev ON ev.id_equipo = e.id_equipo_visitante
             WHERE l.id_torneo = ?
             ORDER BY l.ronda, l.posicion";
$stmt = mysqli_prepare($conexion, $consulta);
mysqli_stmt_bind_param($stmt, "i", $id_torneo);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$llaves = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $llaves[] = $fila;
}

echo json_encode(["exito" => true, "llaves" => $llaves]);
