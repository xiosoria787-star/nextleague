<?php
// Recibe: id_usuario
// No se borra al usuario de la base (rompería el historial de torneos,
// resultados y auditoría que dejó); se lo desactiva. Un usuario inactivo
// no puede iniciar sesión (ver auth/login.php).
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

requerir_rol(["administrador"]);
$datos = leer_datos();
$id_usuario = (int)($datos["id_usuario"] ?? 0);

if ((int)$_SESSION["id_usuario"] === $id_usuario) {
    responder(false, "No podés desactivar tu propia cuenta.");
}

$stmt = mysqli_prepare($conexion, "SELECT activo FROM usuarios WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$usuario) {
    responder(false, "Usuario no encontrado.");
}

$nuevo_estado = $usuario["activo"] ? 0 : 1;
$stmt = mysqli_prepare($conexion, "UPDATE usuarios SET activo = ? WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "ii", $nuevo_estado, $id_usuario);

if (mysqli_stmt_execute($stmt)) {
    $accion = $nuevo_estado ? "activar" : "desactivar";
    Auditoria::registrar($conexion, (int)$_SESSION["id_usuario"], $accion, "usuario", $id_usuario);
    responder(true, $nuevo_estado ? "Usuario reactivado." : "Usuario desactivado.");
} else {
    responder(false, "No se pudo actualizar el usuario.");
}
