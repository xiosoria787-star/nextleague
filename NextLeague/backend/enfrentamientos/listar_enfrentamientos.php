<?php
// GET listar_enfrentamientos.php?id_torneo=3          (todos los partidos de un torneo)
// GET listar_enfrentamientos.php?proximos=1            (próximos partidos, para el home)
// GET listar_enfrentamientos.php?recientes=1           (últimos partidos ya jugados, para el home)
// En proximos/recientes se puede sumar &id_juego=2 para filtrar por deporte.
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

$id_torneo = (int)($_GET["id_torneo"] ?? 0);
$proximos  = isset($_GET["proximos"]);
$recientes = isset($_GET["recientes"]);
$id_juego  = (int)($_GET["id_juego"] ?? 0);

$base = "SELECT e.id_enfrentamiento, e.id_torneo, e.ronda, e.fecha_hora,
                e.resultado_local, e.resultado_visitante, e.estado, e.id_ganador,
                el.nombre AS equipo_local, el.logo AS logo_local,
                ev.nombre AS equipo_visitante, ev.logo AS logo_visitante,
                t.nombre AS torneo, j.nombre AS juego, t.id_juego
         FROM enfrentamientos e
         LEFT JOIN equipos el ON el.id_equipo = e.id_equipo_local
         LEFT JOIN equipos ev ON ev.id_equipo = e.id_equipo_visitante
         JOIN torneos t ON t.id_torneo = e.id_torneo
         JOIN juegos j ON j.id_juego = t.id_juego";

$filtro_juego = $id_juego ? " AND t.id_juego = $id_juego" : "";

if ($proximos) {
    $consulta = $base . " WHERE e.estado != 'finalizado' AND e.id_equipo_local IS NOT NULL AND e.id_equipo_visitante IS NOT NULL"
                . $filtro_juego . " ORDER BY e.fecha_hora IS NULL, e.fecha_hora ASC LIMIT 6";
    $resultado = mysqli_query($conexion, $consulta);
} elseif ($recientes) {
    $consulta = $base . " WHERE e.estado = 'finalizado'" . $filtro_juego . " ORDER BY e.id_enfrentamiento DESC LIMIT 6";
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
