<?php
// Recibe: id_equipo
// No suma al usuario directo: crea una solicitud pendiente que el
// capitán del equipo tiene que aceptar o rechazar.
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

requerir_sesion();
$datos = leer_datos();
$id_equipo = (int)($datos["id_equipo"] ?? 0);
$id_usuario = (int)$_SESSION["id_usuario"];

$stmt = mysqli_prepare($conexion, "SELECT e.id_torneo, e.id_capitan, t.estado FROM equipos e JOIN torneos t ON t.id_torneo = e.id_torneo WHERE e.id_equipo = ?");
mysqli_stmt_bind_param($stmt, "i", $id_equipo);
mysqli_stmt_execute($stmt);
$equipo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$equipo) {
    responder(false, "Equipo no encontrado.");
}
if ($equipo["estado"] !== "pendiente") {
    responder(false, "Las inscripciones de este torneo ya estan cerradas.");
}
if ((int)$equipo["id_capitan"] === $id_usuario) {
    responder(false, "Ya sos el capitán de este equipo.");
}

// No podés mandar una solicitud si ya sos parte de otro equipo en el mismo torneo.
$stmt = mysqli_prepare($conexion, "SELECT em.id_equipo FROM equipo_miembros em
                                    JOIN equipos e ON e.id_equipo = em.id_equipo
                                    WHERE e.id_torneo = ? AND em.id_usuario = ?");
mysqli_stmt_bind_param($stmt, "ii", $equipo["id_torneo"], $id_usuario);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    responder(false, "Ya formas parte de un equipo en este torneo.");
}
mysqli_stmt_close($stmt);

// Evita mandar la misma solicitud varias veces mientras siga pendiente.
$stmt = mysqli_prepare($conexion, "SELECT id_solicitud FROM equipo_solicitudes WHERE id_equipo = ? AND id_usuario = ? AND estado = 'pendiente'");
mysqli_stmt_bind_param($stmt, "ii", $id_equipo, $id_usuario);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    mysqli_stmt_close($stmt);
    responder(false, "Ya tenés una solicitud pendiente para este equipo.");
}
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conexion, "INSERT INTO equipo_solicitudes (id_equipo, id_usuario) VALUES (?, ?)");
mysqli_stmt_bind_param($stmt, "ii", $id_equipo, $id_usuario);

if (mysqli_stmt_execute($stmt)) {
    responder(true, "Solicitud enviada. El capitán del equipo tiene que aceptarla.");
} else {
    responder(false, "No se pudo enviar la solicitud.");
}
