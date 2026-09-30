<?php
// GET listar_solicitudes.php?id_equipo=3
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

requerir_sesion();
$id_equipo = (int)($_GET["id_equipo"] ?? 0);

$stmt = mysqli_prepare($conexion, "SELECT id_capitan FROM equipos WHERE id_equipo = ?");
mysqli_stmt_bind_param($stmt, "i", $id_equipo);
mysqli_stmt_execute($stmt);
$equipo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$equipo) {
    responder(false, "Equipo no encontrado.");
}
if ((int)$equipo["id_capitan"] !== (int)$_SESSION["id_usuario"]) {
    http_response_code(403);
    responder(false, "Solo el capitán del equipo puede ver sus solicitudes.");
}

$stmt = mysqli_prepare($conexion, "SELECT s.id_solicitud, s.fecha_solicitud, u.id_usuario, u.nombre_usuario
                                    FROM equipo_solicitudes s
                                    JOIN usuarios u ON u.id_usuario = s.id_usuario
                                    WHERE s.id_equipo = ? AND s.estado = 'pendiente'
                                    ORDER BY s.fecha_solicitud");
mysqli_stmt_bind_param($stmt, "i", $id_equipo);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$solicitudes = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $solicitudes[] = $fila;
}

responder(true, "", ["solicitudes" => $solicitudes]);
