<?php
// Recibe: id_torneo
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

requerir_sesion();
$datos = leer_datos();
$id_torneo = (int)($datos["id_torneo"] ?? 0);
$id_usuario = (int)$_SESSION["id_usuario"];

$stmt = mysqli_prepare($conexion, "SELECT estado, id_organizador FROM torneos WHERE id_torneo = ?");
mysqli_stmt_bind_param($stmt, "i", $id_torneo);
mysqli_stmt_execute($stmt);
$torneo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$torneo) responder(false, "Torneo no encontrado.");

// El organizador de ESTE torneo no puede inscribirse a jugarlo, pero sí
// puede participar en torneos que organizan otras personas.
if ((int)$torneo["id_organizador"] === $id_usuario) {
    responder(false, "Sos el organizador de este torneo, no podés inscribirte a jugarlo.");
}

if ($torneo["estado"] !== "pendiente") responder(false, "Las inscripciones ya estan cerradas.");

$stmt = mysqli_prepare($conexion, "SELECT id_participante FROM participantes WHERE id_torneo = ? AND id_usuario = ?");
mysqli_stmt_bind_param($stmt, "ii", $id_torneo, $id_usuario);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    responder(false, "Ya estas inscripto en este torneo.");
}
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conexion, "INSERT INTO participantes (id_torneo, id_usuario) VALUES (?, ?)");
mysqli_stmt_bind_param($stmt, "ii", $id_torneo, $id_usuario);

if (mysqli_stmt_execute($stmt)) {
    if (($_SESSION["rol"] ?? "") === "publico") {
        $stmt2 = mysqli_prepare($conexion, "UPDATE usuarios SET rol = 'participante' WHERE id_usuario = ?");
        mysqli_stmt_bind_param($stmt2, "i", $id_usuario);
        mysqli_stmt_execute($stmt2);
        $_SESSION["rol"] = "participante";
    }
    responder(true, "Inscripcion realizada correctamente.");
} else {
    responder(false, "No se pudo completar la inscripcion.");
}
