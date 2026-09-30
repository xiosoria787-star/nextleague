<?php
// Recibe: email
// Genera un token de recuperación válido por 30 minutos.
//
// NOTA PARA LA ENTREGA: XAMPP no manda emails reales sin configurar un
// servidor SMTP aparte (ej: PHPMailer + Gmail). Para poder demostrar el
// flujo completo en la demo, este endpoint devuelve el link directo en
// la respuesta en vez de mandarlo por correo. En una puesta en producción
// real, ese link se mandaría por email y NUNCA se devolvería en la API.
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

$datos = leer_datos();
$email = trim($datos["email"] ?? "");

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    responder(false, "Ingresá un correo válido.");
}

$stmt = mysqli_prepare($conexion, "SELECT id_usuario FROM usuarios WHERE correo = ? AND activo = 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$usuario = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

// Por seguridad, siempre respondemos "éxito" exista o no la cuenta,
// para no confirmarle a un atacante qué correos están registrados.
if (!$usuario) {
    responder(true, "Si el correo existe, se generó un link de recuperación.");
}

$token = bin2hex(random_bytes(32));
$vence = date('Y-m-d H:i:s', strtotime('+30 minutes'));

$stmt = mysqli_prepare($conexion, "UPDATE usuarios SET token_recuperacion = ?, token_recuperacion_vence = ? WHERE id_usuario = ?");
mysqli_stmt_bind_param($stmt, "ssi", $token, $vence, $usuario["id_usuario"]);
mysqli_stmt_execute($stmt);

responder(true, "Si el correo existe, se generó un link de recuperación.", [
    "link_demo" => "../home/restablecer.html?token=" . $token
]);
