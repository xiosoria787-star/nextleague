<?php
// Recibe: id_torneo. Solo el organizador dueño del torneo o un administrador
// pueden eliminarlo. Borra tambien el logo y el pdf de reglas del servidor.
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

requerir_sesion();
$datos = leer_datos();
$id_torneo = (int)($datos["id_torneo"] ?? 0);

$stmt = mysqli_prepare($conexion, "SELECT id_organizador, nombre, logo, reglas_pdf FROM torneos WHERE id_torneo = ?");
mysqli_stmt_bind_param($stmt, "i", $id_torneo);
mysqli_stmt_execute($stmt);
$torneo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$torneo) {
    responder(false, "Torneo no encontrado.");
}

$es_admin = ($_SESSION["rol"] ?? "") === "administrador";
if (!$es_admin && (int)$torneo["id_organizador"] !== (int)$_SESSION["id_usuario"]) {
    http_response_code(403);
    responder(false, "No tenes permiso para eliminar este torneo.");
}

$stmt = mysqli_prepare($conexion, "DELETE FROM torneos WHERE id_torneo = ?");
mysqli_stmt_bind_param($stmt, "i", $id_torneo);
if (!mysqli_stmt_execute($stmt)) {
    responder(false, "No se pudo eliminar el torneo.");
}

borrar_archivo_si_existe(__DIR__ . "/../uploads/torneos/logos/" . $torneo["logo"]);
borrar_archivo_si_existe(__DIR__ . "/../uploads/torneos/reglas/" . $torneo["reglas_pdf"]);

Auditoria::registrar($conexion, (int)$_SESSION["id_usuario"], "eliminar", "torneo", $id_torneo, "Torneo \"{$torneo['nombre']}\" eliminado");
responder(true, "Torneo eliminado.");
