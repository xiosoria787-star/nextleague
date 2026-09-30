<?php
// Recibe: email, password
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

$datos = leer_datos();
$email    = trim($datos["email"] ?? "");
$password = $datos["password"] ?? "";

if (!$email || !$password) {
    responder(false, "Completa correo y contrasena.");
}

$consulta = "SELECT id_usuario, nombre_usuario, correo, contrasena_hash, foto_perfil, rol
             FROM usuarios WHERE correo = ? AND activo = 1";
$stmt = mysqli_prepare($conexion, $consulta);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$usuario || !password_verify($password, $usuario["contrasena_hash"])) {
    responder(false, "Correo o contrasena incorrectos.");
}

$_SESSION["id_usuario"]     = $usuario["id_usuario"];
$_SESSION["nombre_usuario"] = $usuario["nombre_usuario"];
$_SESSION["rol"]            = $usuario["rol"];
$_SESSION["foto_perfil"]    = $usuario["foto_perfil"];

responder(true, "Sesion iniciada correctamente.", [
    "usuario" => [
        "id_usuario"     => $usuario["id_usuario"],
        "nombre_usuario" => $usuario["nombre_usuario"],
        "correo"         => $usuario["correo"],
        "foto_perfil"    => $usuario["foto_perfil"],
        "rol"            => $usuario["rol"]
    ]
]);
