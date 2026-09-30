<?php
// Recibe: id_usuario
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

$datos = leer_datos();
$id_usuario = (int)($datos["id_usuario"] ?? 0);
requerir_ser_dueno($id_usuario);

$carpeta = __DIR__ . "/../uploads/perfiles/";

$stmt = mysqli_prepare($conexion, "SELECT foto_perfil FROM usuarios WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$foto_actual = $fila["foto_perfil"] ?? null;
if (!$foto_actual) {
    responder(false, "Este usuario no tiene foto de perfil.");
}

$stmt = mysqli_prepare($conexion, "UPDATE usuarios SET foto_perfil = NULL WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
if (!mysqli_stmt_execute($stmt)) {
    responder(false, "No se pudo eliminar la foto.");
}
mysqli_stmt_close($stmt);

borrar_archivo_si_existe($carpeta . $foto_actual);

$_SESSION["foto_perfil"] = null;

responder(true, "Foto de perfil eliminada.");
