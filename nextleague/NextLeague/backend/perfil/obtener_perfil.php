<?php
// GET obtener_perfil.php?id=5
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

$id_usuario = (int)($_GET["id"] ?? 0);
if (!$id_usuario) {
    responder(false, "Falta el id de usuario.");
}

$consulta = "SELECT id_usuario, nombre_usuario, correo, foto_perfil, biografia, rol, fecha_registro
             FROM usuarios WHERE id_usuario = ? AND activo = 1";
$stmt = mysqli_prepare($conexion, $consulta);
mysqli_stmt_bind_param($stmt, "i", $id_usuario);
mysqli_stmt_execute($stmt);
$usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$usuario) {
    responder(false, "Usuario no encontrado.");
}

responder(true, "", ["usuario" => $usuario]);
