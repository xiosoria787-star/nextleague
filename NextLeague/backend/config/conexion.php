<?php
// =====================================================================
// CONEXION A LA BASE DE DATOS
//
// IMPORTANTE PARA EL SERVIDOR: como el servidor actualiza el código
// automáticamente cada vez que se sube un cambio a GitHub, cualquier
// dato escrito directamente en ESTE archivo se pisaría con los valores
// de localhost en cada actualización. Por eso las credenciales reales
// del servidor NO van acá.
//
// En vez de eso, si existe el archivo conexion.local.php (al lado de
// este), se usan los valores de ahí. Ese archivo NO se sube a GitHub
// (está en .gitignore), así que sobrevive intacto a cada actualización
// automática. En localhost, como ese archivo no existe, se usan los
// valores de acá abajo directamente.
// =====================================================================

$host       = "localhost";
$usuario_bd = "root";
$clave_bd   = "";
$nombre_bd  = "nextleague";

$config_local = __DIR__ . "/conexion.local.php";
if (file_exists($config_local)) {
    require $config_local; // puede sobreescribir $host, $usuario_bd, $clave_bd, $nombre_bd
}

// En PHP 8.1+ mysqli tira una excepcion si la conexion falla en vez de
// devolver false, asi que bajamos el modo de reporte para poder mostrar
// nuestro propio mensaje de error en JSON en vez de una pantalla blanca.
mysqli_report(MYSQLI_REPORT_OFF);

$conexion = mysqli_connect($host, $usuario_bd, $clave_bd, $nombre_bd);

if (!$conexion) {
    http_response_code(500);
    die(json_encode([
        "exito"   => false,
        "mensaje" => "No se pudo conectar a la base de datos: " . mysqli_connect_error()
    ]));
}

mysqli_set_charset($conexion, "utf8mb4");
