<?php
// GET listar_enfrentamientos.php?id_torneo=3   (todos los partidos del torneo)
// GET listar_enfrentamientos.php?proximos=1     (proximos partidos de todos los torneos, para el home)
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

$id_torneo = (int)($_GET["id_torneo"] ?? 0);
$proximos  = isset($_GET["proximos"]);

$base = "SELECT e.id_enfrentamiento, e.id_torneo, e.ronda, e.fecha_hora,
                e.resultado_local, e.resultado_visitante, e.estado, e.id_ganador,
                el.nombre AS equipo_local, el.logo AS logo_local,
                ev.nombre AS equipo_visitante, ev.logo AS logo_visitante,
                t.nombre AS torneo
         FROM enfrentamientos e
         LEFT JOIN equipos el ON el.id_equipo = e.id_equipo_local
         LEFT JOIN equipos ev ON ev.id_equipo = e.id_equipo_visitante
         JOIN torneos t ON t.id_torneo = e.id_torneo";

if ($proximos) {
    $consulta = $base . " WHERE e.estado != 'finalizado' AND e.id_equipo_local IS NOT NULL AND e.id_equipo_visitante IS NOT NULL
                 ORDER BY e.fecha_hora IS NULL, e.fecha_hora ASC LIMIT 6";
    $resultado = mysqli_query($conexion, $consulta);
} else {
    $consulta = $base . " WHERE e.id_torneo = ? ORDER BY e.ronda, e.id_enfrentamiento";
    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "i", $id_torneo);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
}

$enfrentamientos = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $enfrentamientos[] = $fila;
}

echo json_encode(["exito" => true, "enfrentamientos" => $enfrentamientos]);
