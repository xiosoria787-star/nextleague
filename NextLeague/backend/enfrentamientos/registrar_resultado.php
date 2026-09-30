<?php
// Recibe: id_enfrentamiento, resultado_local, resultado_visitante
//
// Registra el resultado de un partido. Si el partido YA tenía un resultado
// cargado, esto funciona como una CORRECCIÓN: revierte el efecto que había
// tenido el resultado viejo sobre la tabla de posiciones (liga/suizo) antes
// de aplicar el nuevo, para no duplicar puntos si se corrige un error.
//
// En eliminación directa no se permite corregir un resultado si la ronda
// siguiente ya se jugó, porque el ganador ya avanzó y no tendría sentido
// (habría que deshacer partidos posteriores en cadena).
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

$es_correccion = $enf["estado"] === "finalizado";

if ($es_correccion && $enf["formato"] === "eliminacion_directa") {
    $siguiente = obtener_siguiente_enfrentamiento($conexion, (int)$enf["id_torneo"], (int)$enf["id_enfrentamiento"], (int)$enf["ronda"]);
    if ($siguiente && $siguiente["estado"] === "finalizado") {
        responder(false, "No se puede corregir este resultado porque la ronda siguiente ya se jugó.");
    }
}

$id_ganador = null;
if ($resultado_local > $resultado_visitante) $id_ganador = $enf["id_equipo_local"];
elseif ($resultado_visitante > $resultado_local) $id_ganador = $enf["id_equipo_visitante"];

mysqli_begin_transaction($conexion);
try {
    if ($es_correccion && $enf["formato"] !== "eliminacion_directa") {
        // Deshace el efecto que tuvo el resultado anterior antes de aplicar el nuevo.
        actualizar_tabla_posiciones($conexion, (int)$enf["id_torneo"], (int)$enf["id_equipo_local"], (int)$enf["id_equipo_visitante"], (int)$enf["resultado_local"], (int)$enf["resultado_visitante"], -1);
    }

    $stmt = mysqli_prepare($conexion, "UPDATE enfrentamientos SET resultado_local = ?, resultado_visitante = ?, id_ganador = ?, estado = 'finalizado' WHERE id_enfrentamiento = ?");
    mysqli_stmt_bind_param($stmt, "iiii", $resultado_local, $resultado_visitante, $id_ganador, $id_enfrentamiento);
    mysqli_stmt_execute($stmt);

    if ($enf["formato"] === "eliminacion_directa") {
        avanzar_ganador_bracket($conexion, (int)$enf["id_torneo"], (int)$enf["id_enfrentamiento"], (int)$enf["ronda"], (int)$id_ganador);
    } else {
        actualizar_tabla_posiciones($conexion, (int)$enf["id_torneo"], (int)$enf["id_equipo_local"], (int)$enf["id_equipo_visitante"], $resultado_local, $resultado_visitante, 1);
    }

    mysqli_commit($conexion);
    $accion_auditoria = $es_correccion ? "corregir" : "cargar";
    Auditoria::registrar($conexion, (int)$_SESSION["id_usuario"], $accion_auditoria, "resultado", $id_enfrentamiento, "Resultado $resultado_local-$resultado_visitante");
    responder(true, $es_correccion ? "Resultado corregido correctamente." : "Resultado guardado correctamente.");
} catch (Exception $e) {
    mysqli_rollback($conexion);
    responder(false, "No se pudo guardar el resultado.");
}

// $signo: 1 para aplicar el resultado, -1 para deshacerlo (al corregir un error).
function actualizar_tabla_posiciones($conexion, $id_torneo, $local, $visitante, $goles_local, $goles_visitante, $signo) {
    foreach ([$local, $visitante] as $id_equipo) {
        $stmt = mysqli_prepare($conexion, "INSERT IGNORE INTO tabla_posiciones (id_torneo, id_equipo) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $id_torneo, $id_equipo);
        mysqli_stmt_execute($stmt);
    }

    if ($goles_local > $goles_visitante) {
        sumar_stats($conexion, $id_torneo, $local, 3 * $signo, $signo, 1 * $signo, 0, 0, $goles_local * $signo, $goles_visitante * $signo);
        sumar_stats($conexion, $id_torneo, $visitante, 0, $signo, 0, 0, 1 * $signo, $goles_visitante * $signo, $goles_local * $signo);
    } elseif ($goles_visitante > $goles_local) {
        sumar_stats($conexion, $id_torneo, $visitante, 3 * $signo, $signo, 1 * $signo, 0, 0, $goles_visitante * $signo, $goles_local * $signo);
        sumar_stats($conexion, $id_torneo, $local, 0, $signo, 0, 0, 1 * $signo, $goles_local * $signo, $goles_visitante * $signo);
    } else {
        sumar_stats($conexion, $id_torneo, $local, 1 * $signo, $signo, 0, 1 * $signo, 0, $goles_local * $signo, $goles_visitante * $signo);
        sumar_stats($conexion, $id_torneo, $visitante, 1 * $signo, $signo, 0, 1 * $signo, 0, $goles_visitante * $signo, $goles_local * $signo);
    }
}

// $pj es siempre +1 o -1 (según $signo); pg/pe/pp indican cuál de los tres
// se incrementa para este equipo en este partido.
function sumar_stats($conexion, $id_torneo, $id_equipo, $pts, $pj, $pg, $pe, $pp, $gf, $gc) {
    $consulta = "UPDATE tabla_posiciones
                 SET puntos = puntos + ?, pj = pj + ?, pg = pg + ?, pe = pe + ?, pp = pp + ?, gf = gf + ?, gc = gc + ?
                 WHERE id_torneo = ? AND id_equipo = ?";
    $stmt = mysqli_prepare($conexion, $consulta);
    mysqli_stmt_bind_param($stmt, "iiiiiiiii", $pts, $pj, $pg, $pe, $pp, $gf, $gc, $id_torneo, $id_equipo);
    mysqli_stmt_execute($stmt);
}

// Busca, dentro del bracket, el partido de la ronda siguiente al que avanza
// el ganador de $id_enfrentamiento (sin importar si ya tiene ganador o no).
function obtener_siguiente_enfrentamiento($conexion, $id_torneo, $id_enfrentamiento, $ronda) {
    $stmt = mysqli_prepare($conexion, "SELECT posicion FROM llaves WHERE id_torneo = ? AND id_enfrentamiento = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id_torneo, $id_enfrentamiento);
    mysqli_stmt_execute($stmt);
    $llave = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$llave) return null;

    $ronda_siguiente = $ronda + 1;
    $posicion_siguiente = intdiv((int)$llave["posicion"], 2);

    $stmt = mysqli_prepare($conexion, "SELECT e.id_enfrentamiento, e.estado FROM llaves l
        JOIN enfrentamientos e ON e.id_enfrentamiento = l.id_enfrentamiento
        WHERE l.id_torneo = ? AND l.ronda = ? AND l.posicion = ?");
    mysqli_stmt_bind_param($stmt, "iii", $id_torneo, $ronda_siguiente, $posicion_siguiente);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

// Coloca al ganador de $id_enfrentamiento en el partido de la ronda siguiente
// que le corresponde. Si se llama dos veces (por una corrección), sobreescribe
// el equipo que había quedado antes en ese lugar con el nuevo ganador.
function avanzar_ganador_bracket($conexion, $id_torneo, $id_enfrentamiento, $ronda, $id_ganador) {
    $stmt = mysqli_prepare($conexion, "SELECT posicion FROM llaves WHERE id_torneo = ? AND id_enfrentamiento = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id_torneo, $id_enfrentamiento);
    mysqli_stmt_execute($stmt);
    $llave = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$llave) return;

    $posicion = (int)$llave["posicion"];
    $ronda_siguiente = $ronda + 1;
    $posicion_siguiente = intdiv($posicion, 2);
    $campo = ($posicion % 2 === 0) ? "id_equipo_local" : "id_equipo_visitante";

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
