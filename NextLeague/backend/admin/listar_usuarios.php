<?php
// GET listar_usuarios.php
// Solo el administrador puede ver el listado completo de usuarios.
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

requerir_rol(["administrador"]);

$consulta = "SELECT id_usuario, nombre_usuario, correo, rol, activo, fecha_registro,
                    (SELECT COUNT(*) FROM torneos t WHERE t.id_organizador = u.id_usuario) AS torneos_creados
             FROM usuarios u
             ORDER BY fecha_registro DESC";
$resultado = mysqli_query($conexion, $consulta);

$usuarios = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $usuarios[] = $fila;
}

responder(true, "", ["usuarios" => $usuarios]);
