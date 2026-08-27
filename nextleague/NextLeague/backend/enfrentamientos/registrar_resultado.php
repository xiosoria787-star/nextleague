<?php
// Recibe: id_enfrentamiento, resultado_local, resultado_visitante
// Al guardar el resultado: marca el partido como finalizado, calcula el
// ganador, y segun el formato del torneo actualiza la tabla de posiciones
// (liga / sistema_suizo) o avanza al ganador en el bracket (eliminacion_directa).
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

requerir_sesion();
$datos = leer_datos();

$id_enfrentamiento = (int)($datos["id_enfrentamiento"] ?? 0);
$resultado_local = (int)($datos["resultado_local"] ?? -1);
$resultado_visitante = (int)($datos["resultado_visitante"] ?? -1);

if ($resultado_local < 0 || $resultado_visitante < 0) {
    responder(false, "Ingresa un resultado valido para ambos equipos.");
}

$consulta = "SELECT e.*, t.formato, t.id_organizador
             FROM enfrentamientos e
             JOIN torneos t ON t.id_torneo = e.id_torneo
             WHERE e.id_enfrentamiento = ?";
$stmt = mysqli_prepare($conexion, $consulta);
mysqli_stmt_bind_param($stmt, "i", $id_enfrentamiento);
mysqli_stmt_execute($stmt);
$enf = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$enf) responder(false, "Enfrentamiento no encontrado.");
if (!$enf["id_equipo_local"] || !$enf["id_equipo_visitante"]) responder(false, "Este partido todavia no tiene ambos equipos definidos.");

$es_admin = ($_SESSION["rol"] ?? "") === "administrador";
if (!$es_admin && (int)$enf["id_organizador"] !== (int)$_SESSION["id_usuario"]) {
    http_response_code(403);
    responder(false, "Solo el organizador del torneo puede cargar resultados.");
}

if ($enf["formato"] === "eliminacion_directa" && $resultado_local === $resultado_visitante) {
    responder(false, "En eliminacion directa el partido no puede terminar empatado.");
}

$id_ganador = null;
if ($resultado_local > $resultado_visitante) $id_ganador = $enf["id_equipo_local"];
elseif ($resultado_visitante > $resultado_local) $id_ganador = $enf["id_equipo_visitante"];

mysqli_begin_transaction($conexion);
try {
    $stmt = mysqli_prepare($conexion, "UPDATE enfrentamientos SET resultado_local = ?, resultado_visitante = ?, id_ganador = ?, estado = 'finalizado' WHERE id_enfrentamiento = ?");
    mysqli_stmt_bind_param($stmt, "iiii", $resultado_local, $resultado_visitante, $id_ganador, $id_enfrentamiento);
    mysqli_stmt_execute($stmt);

    if ($enf["formato"] === "eliminacion_directa") {
        avanzar_ganador_bracket($conexion, (int)$enf["id_torneo"], (int)$enf["ronda"], (int)$id_ganador);
    } else {
        actualizar_tabla_posiciones($conexion, (int)$enf["id_torneo"], (int)$enf["id_equipo_local"], (int)$enf["id_equipo_visitante"], $resultado_local, $resultado_visitante);
    }

    mysqli_commit($conexion);
    responder(true, "Resultado guardado correctamente.");
} catch (Exception $e) {
    mysqli_rollback($conexion);
    responder(false, "No se pudo guardar el resultado.");
}

function actualizar_tabla_posiciones($conexion, $id_torneo, $local, $visitante, $goles_local, $goles_visitante) {
    foreach ([$local, $visitante] as $id_equipo) {
        $stmt = mysqli_prepare($conexion, "INSERT IGNORE INTO tabla_posiciones (id_torneo, id_equipo) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $id_torneo, $id_equipo);
        mysqli_stmt_execute($stmt);
    }

    if ($goles_local > $goles_visitante) {
        sumar_stats($conexion, $id_torneo, $local, 3, 1, 0, 0, $goles_local, $goles_visitante);
        sumar_stats($conexion, $id_torneo, $visitante, 0, 0, 0, 1, $goles_visitante, $goles_local);
    } elseif ($goles_visitante > $goles_local) {
        sumar_stats($conexion, $id_torneo, $visitante, 3, 1, 0, 0, $goles_visitante, $goles_local);
        sumar_stats($conexion, $id_torneo, $local, 0, 0, 0, 1, $goles_local, $goles_visitante);
    } else {
        sumar_stats($conexion, $id_torneo, $local, 1, 0, 1, 0, $goles_local, $goles_visitante);
        sumar_stats($conexion, $id_torneo, $visitante, 1, 0, 1, 0, $goles_visitante, $goles_local);
    }
}

function sumar_stats($conexion, $id_torneo, $id_equipo, $pts, $pg, $pe, $pp, $gf, $gc) {
    $consulta = "UPDATE tabla_posiciones
                 SET puntos = puntos + ?, pj = pj + 1, pg = pg + ?, pe = pe + ?, pp = pp + ?, gf = gf + ?, gc = gc + ?
                 WHERE id_torneo = ? AND id_equipo = ?";
    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "iiiiiiii", $pts, $pg, $pe, $pp, $gf, $gc, $id_torneo, $id_equipo);
    mysqli_stmt_execute($stmt);
}

function avanzar_ganador_bracket($conexion, $id_torneo, $ronda, $id_ganador) {
    // Buscamos la posicion del partido recien resuelto dentro de esa ronda
    $stmt = mysqli_prepare($conexion, "SELECT l.posicion FROM llaves l
        JOIN enfrentamientos e ON e.id_enfrentamiento = l.id_enfrentamiento
        WHERE l.id_torneo = ? AND l.ronda = ? AND e.id_ganador = ? ORDER BY l.id_llave DESC LIMIT 1");
    mysqli_stmt_bind_param($stmt, "iii", $id_torneo, $ronda, $id_ganador);
    mysqli_stmt_execute($stmt);
    $llave = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$llave) return;

    $ronda_siguiente = $ronda + 1;
    $posicion_siguiente = intdiv((int)$llave["posicion"], 2);
    $campo = ((int)$llave["posicion"] % 2 === 0) ? "id_equipo_local" : "id_equipo_visitante";

    $stmt = mysqli_prepare($conexion, "SELECT id_enfrentamiento FROM llaves WHERE id_torneo = ? AND ronda = ? AND posicion = ?");
    mysqli_stmt_bind_param($stmt, "iii", $id_torneo, $ronda_siguiente, $posicion_siguiente);
    mysqli_stmt_execute($stmt);
    $siguiente = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($siguiente) {
        $stmt = mysqli_prepare($conexion, "UPDATE enfrentamientos SET $campo = ? WHERE id_enfrentamiento = ?");
        mysqli_stmt_bind_param($stmt, "ii", $id_ganador, $siguiente["id_enfrentamiento"]);
        mysqli_stmt_execute($stmt);
    } else {
        // No hay ronda siguiente: este era el partido final, el torneo termino
        $stmt = mysqli_prepare($conexion, "UPDATE torneos SET estado = 'finalizado' WHERE id_torneo = ?");
        mysqli_stmt_bind_param($stmt, "i", $id_torneo);
        mysqli_stmt_execute($stmt);
    }
}
