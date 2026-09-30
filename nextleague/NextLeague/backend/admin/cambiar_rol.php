<?php
// Recibe: id_usuario, rol_nuevo
// El administrador general es el único que puede reasignar roles.
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

requerir_rol(["administrador"]);
$datos = leer_datos();

$id_usuario = (int)($datos["id_usuario"] ?? 0);
$rol_nuevo = $datos["rol_nuevo"] ?? "";

$roles_validos = ["administrador", "organizador", "participante", "publico"];
if (!in_array($rol_nuevo, $roles_validos, true)) {
    responder(false, "Rol invalido.");
}

// Un administrador no puede quitarse el rol a si mismo por error y quedar
// sin nadie que administre el sistema (validacion simple y razonable).
if ((int)$_SESSION["id_usuario"] === $id_usuario && $rol_nuevo !== "administrador") {
    responder(false, "No podés quitarte tu propio rol de administrador.");
}

$stmt = mysqli_prepare($conexion, "UPDATE usuarios SET rol = ? WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "si", $rol_nuevo, $id_usuario);

if (mysqli_stmt_execute($stmt)) {
    Auditoria::registrar($conexion, (int)$_SESSION["id_usuario"], "editar", "usuario", $id_usuario, "Rol cambiado a \"$rol_nuevo\"");
    responder(true, "Rol actualizado correctamente.");
} else {
    responder(false, "No se pudo actualizar el rol.");
}
