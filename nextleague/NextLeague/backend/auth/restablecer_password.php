<?php
// Recibe: token, password_nueva
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

$datos = leer_datos();
$token = trim($datos["token"] ?? "");
$password_nueva = $datos["password_nueva"] ?? "";

if (!$token) {
    responder(false, "Falta el token de recuperación.");
}

// ── Mismos requisitos de seguridad que en el registro ──
if (strlen($password_nueva) < 8) responder(false, "La contraseña debe tener al menos 8 caracteres.");
if (!preg_match('/[A-Z]/', $password_nueva)) responder(false, "Debe incluir al menos una letra mayúscula.");
if (!preg_match('/[0-9]/', $password_nueva)) responder(false, "Debe incluir al menos un número.");
if (!preg_match('/[^a-zA-Z0-9]/', $password_nueva)) responder(false, "Debe incluir al menos un carácter especial.");

$stmt = mysqli_prepare($conexion, "SELECT id_usuario FROM usuarios WHERE token_recuperacion = ? AND token_recuperacion_vence > NOW()");
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$usuario) {
    responder(false, "El link venció o no es válido. Pedí uno nuevo.");
}

$hash = password_hash($password_nueva, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conexion, "UPDATE usuarios SET contrasena_hash = ?, token_recuperacion = NULL, token_recuperacion_vence = NULL WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "si", $hash, $usuario["id_usuario"]);

if (mysqli_stmt_execute($stmt)) {
    responder(true, "Contraseña actualizada. Ya podés iniciar sesión.");
} else {
    responder(false, "No se pudo actualizar la contraseña.");
}
