<?php
// =====================================================================
// CONEXION A LA BASE DE DATOS
// Ajustar host / usuario / clave / nombre_bd segun el servidor.
// =====================================================================

$host       = "localhost";
$usuario_bd = "root";
$clave_bd   = "";
$nombre_bd  = "nextleague";

$conexion = mysqli_connect($host, $usuario_bd, $clave_bd, $nombre_bd);

if (!$conexion) {
    http_response_code(500);
    die(json_encode([
        "exito"   => false,
        "mensaje" => "No se pudo conectar a la base de datos."
    ]));
}

mysqli_set_charset($conexion, "utf8mb4");
