<?php
require_once __DIR__ . "/../includes/sesion.php";

if (!esta_logueado()) {
    echo json_encode(["exito" => true, "logueado" => false]);
    exit;
}

echo json_encode([
    "exito"    => true,
    "logueado" => true,
    "usuario"  => [
        "id_usuario"     => $_SESSION["id_usuario"],
        "nombre_usuario" => $_SESSION["nombre_usuario"],
        "rol"            => $_SESSION["rol"],
        "foto_perfil"    => $_SESSION["foto_perfil"] ?? null
    ]
]);
