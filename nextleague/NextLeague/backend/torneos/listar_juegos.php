<?php
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

$resultado = mysqli_query($conexion, "SELECT id_juego, nombre, categoria FROM juegos ORDER BY categoria, nombre");
$juegos = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $juegos[] = $fila;
}

echo json_encode(["exito" => true, "juegos" => $juegos]);
