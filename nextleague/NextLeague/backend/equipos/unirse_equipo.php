<?php
// Recibe: id_equipo
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

requerir_sesion();
$datos = leer_datos();
$id_equipo = (int)($datos["id_equipo"] ?? 0);
$id_usuario = (int)$_SESSION["id_usuario"];

$stmt = mysqli_prepare($conexion, "SELECT id_equipo FROM equipo_miembros WHERE id_equipo = ? AND id_usuario = ?");
mysqli_stmt_bind_param($stmt, "ii", $id_equipo, $id_usuario);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    responder(false, "Ya sos parte de este equipo.");
}
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conexion, "INSERT INTO equipo_miembros (id_equipo, id_usuario) VALUES (?, ?)");
mysqli_stmt_bind_param($stmt, "ii", $id_equipo, $id_usuario);

if (mysqli_stmt_execute($stmt)) {
    responder(true, "Te uniste al equipo correctamente.");
} else {
    responder(false, "No se pudo unir al equipo.");
}
