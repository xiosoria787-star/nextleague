<?php
// Recibe: id_usuario, nombre_usuario, correo, password_nueva (opcional)
// SOLO el dueño de la sesion (o un admin) puede editar el perfil.
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

$datos = leer_datos();
$id_usuario = (int)($datos["id_usuario"] ?? 0);

requerir_ser_dueno($id_usuario); // <- punto clave: corta si no es el dueño

$nombre_usuario = trim($datos["nombre_usuario"] ?? "");
$correo         = trim($datos["correo"] ?? "");
$password_nueva = $datos["password_nueva"] ?? "";
$biografia      = trim($datos["biografia"] ?? "");

if (strlen($biografia) > 280) {
    responder(false, "La biografía no puede superar los 280 caracteres.");
}

if (strlen($nombre_usuario) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $nombre_usuario)) {
    responder(false, "Nombre de usuario invalido.");
}
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    responder(false, "Correo invalido.");
}

$consulta = "SELECT id_usuario FROM usuarios WHERE (nombre_usuario = ? OR correo = ?) AND id_usuario != ?";
$stmt = mysqli_prepare($conexion, $consulta);
mysqli_stmt_bind_param($stmt, "ssi", $nombre_usuario, $correo, $id_usuario);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    responder(false, "Ese usuario o correo ya esta en uso.");
}
mysqli_stmt_close($stmt);

if (!empty($password_nueva)) {
    if (strlen($password_nueva) < 8) {
        responder(false, "La nueva contrasena debe tener al menos 8 caracteres.");
    }
    if (!preg_match('/[A-Z]/', $password_nueva) || !preg_match('/[0-9]/', $password_nueva) || !preg_match('/[^a-zA-Z0-9]/', $password_nueva)) {
        responder(false, "La nueva contraseña debe incluir mayúscula, número y carácter especial.");
    }
    $hash = password_hash($password_nueva, PASSWORD_DEFAULT);
    $consulta = "UPDATE usuarios SET nombre_usuario = ?, correo = ?, contrasena_hash = ?, biografia = ? WHERE id_usuario = ?";
    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "ssssi", $nombre_usuario, $correo, $hash, $biografia, $id_usuario);
} else {
    $consulta = "UPDATE usuarios SET nombre_usuario = ?, correo = ?, biografia = ? WHERE id_usuario = ?";
    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "sssi", $nombre_usuario, $correo, $biografia, $id_usuario);
}

if (mysqli_stmt_execute($stmt)) {
    $_SESSION["nombre_usuario"] = $nombre_usuario;
    Auditoria::registrar($conexion, $id_usuario, "editar", "usuario", $id_usuario, "Edición de perfil propio");
    responder(true, "Perfil actualizado correctamente.");
} else {
    responder(false, "No se pudo actualizar el perfil.");
}
