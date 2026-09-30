<?php
// Recibe: id_solicitud, accion ("aceptar" o "rechazar")
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

requerir_sesion();
$datos = leer_datos();
$id_solicitud = (int)($datos["id_solicitud"] ?? 0);
$accion = $datos["accion"] ?? "";

if (!in_array($accion, ["aceptar", "rechazar"], true)) {
    responder(false, "Acción inválida.");
}

$stmt = mysqli_prepare($conexion, "SELECT s.id_equipo, s.id_usuario, s.estado, e.id_capitan, e.id_torneo, e.nombre AS nombre_equipo
                                    FROM equipo_solicitudes s
                                    JOIN equipos e ON e.id_equipo = s.id_equipo
                                    WHERE s.id_solicitud = ?");
mysqli_stmt_bind_param($stmt, "i", $id_solicitud);
mysqli_stmt_execute($stmt);
$solicitud = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$solicitud) {
    responder(false, "Solicitud no encontrada.");
}
if ((int)$solicitud["id_capitan"] !== (int)$_SESSION["id_usuario"]) {
    http_response_code(403);
    responder(false, "Solo el capitán del equipo puede responder esta solicitud.");
}
if ($solicitud["estado"] !== "pendiente") {
    responder(false, "Esta solicitud ya fue respondida.");
}

mysqli_begin_transaction($conexion);
try {
    $estado_nuevo = $accion === "aceptar" ? "aceptada" : "rechazada";
    $stmt = mysqli_prepare($conexion, "UPDATE equipo_solicitudes SET estado = ?, fecha_respuesta = NOW() WHERE id_solicitud = ?");
    mysqli_stmt_bind_param($stmt, "si", $estado_nuevo, $id_solicitud);
    mysqli_stmt_execute($stmt);

    if ($accion === "aceptar") {
        // Antes de sumarlo, confirmamos que no se haya anotado a otro
        // equipo del mismo torneo mientras la solicitud estaba pendiente.
        $stmt = mysqli_prepare($conexion, "SELECT em.id_equipo FROM equipo_miembros em
                                            JOIN equipos e ON e.id_equipo = em.id_equipo
                                            WHERE e.id_torneo = ? AND em.id_usuario = ?");
        mysqli_stmt_bind_param($stmt, "ii", $solicitud["id_torneo"], $solicitud["id_usuario"]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            mysqli_stmt_close($stmt);
            mysqli_rollback($conexion);
            responder(false, "Este usuario ya se anotó a otro equipo de este torneo mientras tanto.");
        }
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conexion, "INSERT INTO equipo_miembros (id_equipo, id_usuario) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $solicitud["id_equipo"], $solicitud["id_usuario"]);
        mysqli_stmt_execute($stmt);
    }

    mysqli_commit($conexion);
    Auditoria::registrar($conexion, (int)$_SESSION["id_usuario"], $accion, "solicitud_equipo", $id_solicitud, "Solicitud a \"{$solicitud['nombre_equipo']}\" " . $estado_nuevo);
    responder(true, $accion === "aceptar" ? "Solicitud aceptada. El jugador ya es parte del equipo." : "Solicitud rechazada.");
} catch (Exception $e) {
    mysqli_rollback($conexion);
    responder(false, "No se pudo procesar la solicitud.");
}
