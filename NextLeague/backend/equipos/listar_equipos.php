<?php
// GET listar_equipos.php?id_torneo=3
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

$id_torneo = (int)($_GET["id_torneo"] ?? 0);

$consulta = "SELECT e.id_equipo, e.nombre, e.logo, e.id_capitan, u.nombre_usuario AS capitan,
                    (SELECT COUNT(*) FROM equipo_miembros m WHERE m.id_equipo = e.id_equipo) AS cantidad_miembros
             FROM equipos e
             LEFT JOIN usuarios u ON u.id_usuario = e.id_capitan
             WHERE e.id_torneo = ?
             ORDER BY e.nombre";
$stmt = mysqli_prepare($conexion, $consulta);
mysqli_stmt_bind_param($stmt, "i", $id_torneo);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$equipos = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $equipos[] = $fila;
}

echo json_encode(["exito" => true, "equipos" => $equipos]);
