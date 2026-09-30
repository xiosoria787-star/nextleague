<?php
// GET listar_torneos_admin.php
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

requerir_rol(["administrador"]);

$consulta = "SELECT t.id_torneo, t.nombre, t.formato, t.estado, t.fecha_creacion,
                    j.nombre AS juego, u.nombre_usuario AS organizador,
                    (SELECT COUNT(*) FROM equipos e WHERE e.id_torneo = t.id_torneo) AS equipos_inscriptos
             FROM torneos t
             JOIN juegos j ON j.id_juego = t.id_juego
             JOIN usuarios u ON u.id_usuario = t.id_organizador
             ORDER BY t.fecha_creacion DESC";
$resultado = mysqli_query($conexion, $consulta);

$torneos = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $torneos[] = $fila;
}

responder(true, "", ["torneos" => $torneos]);
