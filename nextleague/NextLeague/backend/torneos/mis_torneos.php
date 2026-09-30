<?php
// GET mis_torneos.php?tipo=creados   -> torneos donde el usuario es organizador
// GET mis_torneos.php?tipo=inscripciones -> torneos donde el usuario participa
//     (ya sea de forma individual, o porque integra un equipo anotado)
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

requerir_sesion();
$id_usuario = (int)$_SESSION["id_usuario"];
$tipo = $_GET["tipo"] ?? "creados";

if ($tipo === "creados") {
    $consulta = "SELECT t.id_torneo, t.nombre, t.formato, t.num_equipos, t.premio, t.moneda,
                        t.fecha_inicio, t.fecha_fin, t.logo, t.estado, t.id_juego,
                        j.nombre AS juego, j.categoria,
                        (SELECT COUNT(*) FROM equipos e WHERE e.id_torneo = t.id_torneo) AS equipos_inscriptos
                 FROM torneos t
                 JOIN juegos j ON j.id_juego = t.id_juego
                 WHERE t.id_organizador = ?
                 ORDER BY t.fecha_creacion DESC";
    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "i", $id_usuario);
} else {
    // Inscripciones: individuales (participantes.id_usuario) o por ser
    // miembro de un equipo anotado (equipo_miembros -> equipos -> torneo).
    $consulta = "SELECT DISTINCT t.id_torneo, t.nombre, t.formato, t.num_equipos, t.premio, t.moneda,
                        t.fecha_inicio, t.fecha_fin, t.logo, t.estado, t.id_juego,
                        j.nombre AS juego, j.categoria,
                        (SELECT COUNT(*) FROM equipos e WHERE e.id_torneo = t.id_torneo) AS equipos_inscriptos
                 FROM torneos t
                 JOIN juegos j ON j.id_juego = t.id_juego
                 WHERE t.id_torneo IN (
                     SELECT id_torneo FROM participantes WHERE id_usuario = ?
                     UNION
                     SELECT e.id_torneo FROM equipo_miembros em
                     JOIN equipos e ON e.id_equipo = em.id_equipo
                     WHERE em.id_usuario = ?
                 )
                 ORDER BY t.fecha_creacion DESC";
    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "ii", $id_usuario, $id_usuario);
}

mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$torneos = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $torneos[] = $fila;
}

responder(true, "", ["torneos" => $torneos]);
