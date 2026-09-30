<?php
// Recibe: username, email, password, rol ('organizador' o 'participante')
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

$datos = leer_datos();
$username = trim($datos["username"] ?? "");
$email    = trim($datos["email"] ?? "");
$password = $datos["password"] ?? "";
$rol      = $datos["rol"] ?? "";

if (strlen($username) < 3 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    responder(false, "Nombre de usuario invalido. Usa al menos 3 caracteres (letras, numeros, guion bajo).");
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responder(false, "Correo invalido.");
}

// ── Requisitos de seguridad de la contraseña ──
if (strlen($password) < 8) {
    responder(false, "La contrasena debe tener al menos 8 caracteres.");
}
if (!preg_match('/[A-Z]/', $password)) {
    responder(false, "La contrasena debe incluir al menos una letra mayúscula.");
}
if (!preg_match('/[0-9]/', $password)) {
    responder(false, "La contrasena debe incluir al menos un número.");
}
if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
    responder(false, "La contrasena debe incluir al menos un carácter especial (ej: !@#$%).");
}

// ── El rol es excluyente: o creás torneos, o participás en ellos ──
if (!in_array($rol, ["organizador", "participante"], true)) {
    responder(false, "Elegí si querés crear torneos o participar en ellos.");
}

$consulta = "SELECT id_usuario FROM usuarios WHERE nombre_usuario = ? OR correo = ?";
$stmt = mysqli_prepare($conexion, $consulta);
mysqli_stmt_bind_param($stmt, "ss", $username, $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    responder(false, "Ese usuario o correo ya esta registrado.");
}
mysqli_stmt_close($stmt);

$hash = password_hash($password, PASSWORD_DEFAULT);

$insercion = "INSERT INTO usuarios (nombre_usuario, correo, contrasena_hash, rol) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conexion, $insercion);
mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hash, $rol);

if (mysqli_stmt_execute($stmt)) {
    Auditoria::registrar($conexion, mysqli_insert_id($conexion), "crear", "usuario", mysqli_insert_id($conexion), "Registro de cuenta nueva ($rol)");
    responder(true, "Cuenta creada exitosamente.");
} else {
    responder(false, "Ocurrio un error al registrar.");
}
