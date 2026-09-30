<?php
// Recibe: id_usuario (POST) + foto (archivo, multipart/form-data)
// Si el usuario ya tenia una foto, se borra el archivo viejo del disco
// despues de guardar el nuevo con exito (para no perder la foto si algo falla).
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

$id_usuario = (int)($_POST["id_usuario"] ?? 0);
requerir_ser_dueno($id_usuario);

$carpeta = __DIR__ . "/../uploads/perfiles/";
$tipos = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];

// ── Guardar la foto vieja para borrarla despues ──
$stmt = mysqli_prepare($conexion, "SELECT foto_perfil FROM usuarios WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$fila = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$foto_anterior = $fila["foto_perfil"] ?? null;

$nombre_nuevo = guardar_archivo("foto", $carpeta, $tipos, 3 * 1024 * 1024, "usuario_" . $id_usuario);

$stmt = mysqli_prepare($conexion, "UPDATE usuarios SET foto_perfil = ? WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "si", $nombre_nuevo, $id_usuario);

if (!mysqli_stmt_execute($stmt)) {
    borrar_archivo_si_existe($carpeta . $nombre_nuevo); // limpia el archivo recien subido
    responder(false, "No se pudo actualizar la foto de perfil.");
}
mysqli_stmt_close($stmt);

// Recien ahora borramos la foto anterior (el reemplazo ya se confirmo en la BD).
borrar_archivo_si_existe($carpeta . $foto_anterior);

$_SESSION["foto_perfil"] = $nombre_nuevo; // asi el navbar la muestra ya, sin tener que re-loguearse

responder(true, "Foto de perfil actualizada.", ["foto_perfil" => $nombre_nuevo]);
