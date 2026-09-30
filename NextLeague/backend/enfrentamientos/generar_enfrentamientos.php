<?php
// Recibe: id_torneo
// Solo el organizador dueño del torneo (o un admin) puede generar el calendario.
// - liga: todos contra todos, una vuelta (metodo del circulo).
// - eliminacion_directa: arma el bracket completo (con "descansos" si los
//   equipos no son potencia de 2) usando la tabla "llaves" para poder
//   avanzar ganadores automaticamente al cargar resultados.
// - sistema_suizo: genera la ronda 1 emparejando al azar. Las rondas
//   siguientes se generan llamando de nuevo a este mismo endpoint una vez
//   que todos los partidos de la ronda anterior tengan resultado: empareja
//   por puntos (los que van punteando entre si).
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

requerir_sesion();
$datos = leer_datos();
$id_torneo = (int)($datos["id_torneo"] ?? 0);

$stmt = mysqli_prepare($conexion, "SELECT id_organizador, formato, estado FROM torneos WHERE id_torneo = ?");
mysqli_stmt_bind_param($stmt, "i", $id_torneo);
mysqli_stmt_execute($stmt);
$torneo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$torneo) responder(false, "Torneo no encontrado.");

$es_admin = ($_SESSION["rol"] ?? "") === "administrador";
if (!$es_admin && (int)$torneo["id_organizador"] !== (int)$_SESSION["id_usuario"]) {
    http_response_code(403);
    responder(false, "Solo el organizador puede generar el calendario.");
}

$stmt = mysqli_query($conexion, "SELECT id_equipo FROM equipos WHERE id_torneo = $id_torneo ORDER BY id_equipo");
$equipos = [];
while ($fila = mysqli_fetch_assoc($stmt)) $equipos[] = (int)$fila["id_equipo"];

if (count($equipos) < 2) {
    responder(false, "Necesitas al menos 2 equipos inscriptos para generar el calendario.");
}

mysqli_begin_transaction($conexion);
try {
    if ($torneo["formato"] === "liga") {
        generar_liga($conexion, $id_torneo, $equipos);
    } elseif ($torneo["formato"] === "eliminacion_directa") {
        generar_eliminacion_directa($conexion, $id_torneo, $equipos);
    } else {
        generar_ronda_suiza($conexion, $id_torneo, $equipos);
    }

    $stmt = mysqli_prepare($conexion, "UPDATE torneos SET estado = 'en_curso' WHERE id_torneo = ? AND estado = 'pendiente'");
    mysqli_stmt_bind_param($stmt, "i", $id_torneo);
    mysqli_stmt_execute($stmt);

    mysqli_commit($conexion);
    responder(true, "Calendario generado correctamente.");
} catch (Exception $e) {
    mysqli_rollback($conexion);
    responder(false, "No se pudo generar el calendario: " . $e->getMessage());
}

// ── LIGA: todos contra todos (metodo del circulo) ──
function generar_liga($conexion, $id_torneo, $equipos) {
    if (count($equipos) % 2 !== 0) $equipos[] = null; // "descansa" si es impar
    $n = count($equipos);
    $rondas = $n - 1;
    $mitad = $n / 2;

    for ($r = 1; $r <= $rondas; $r++) {
        for ($i = 0; $i < $mitad; $i++) {
            $local = $equipos[$i];
            $visitante = $equipos[$n - 1 - $i];
            if ($local !== null && $visitante !== null) {
                $stmt = mysqli_prepare($conexion, "INSERT INTO enfrentamientos (id_torneo, ronda, id_equipo_local, id_equipo_visitante) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "iiii", $id_torneo, $r, $local, $visitante);
                mysqli_stmt_execute($stmt);
            }
        }
        // Rotar todos menos el primero
        $fijo = $equipos[0];
        $resto = array_slice($equipos, 1);
        array_unshift($resto, array_pop($resto));
        $equipos = array_merge([$fijo], $resto);
    }

    // Inicializar la tabla de posiciones en cero para cada equipo
    foreach (array_filter($equipos) as $id_equipo) {
        $stmt = mysqli_prepare($conexion, "INSERT IGNORE INTO tabla_posiciones (id_torneo, id_equipo) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $id_torneo, $id_equipo);
        mysqli_stmt_execute($stmt);
    }
}

// ── ELIMINACION DIRECTA: bracket con byes, usando la tabla llaves ──
// Reparte los "byes" (pases libres) de forma que nunca queden dos byes
// enfrentados entre si en la ronda 1 (si eso pasara, ese cruce fantasma
// no tendria ganador y el bracket quedaria trabado sin poder avanzar).
function generar_eliminacion_directa($conexion, $id_torneo, $equipos) {
    shuffle($equipos);
    $n = count($equipos);
    $tamano_bracket = 2 ** ceil(log($n, 2)); // proxima potencia de 2
    $cantidad_byes = $tamano_bracket - $n;

    $equipos_con_bye = array_slice($equipos, 0, $cantidad_byes);
    $equipos_sin_bye = array_slice($equipos, $cantidad_byes);

    $pares = [];
    foreach ($equipos_con_bye as $equipo) {
        $pares[] = [$equipo, null]; // partido "fantasma": pasa directo de ronda
    }
    for ($i = 0; $i < count($equipos_sin_bye); $i += 2) {
        $pares[] = [$equipos_sin_bye[$i], $equipos_sin_bye[$i + 1]];
    }

    $ronda = 1;
    $posicion = 0;

    foreach ($pares as [$local, $visitante]) {
        $stmt = mysqli_prepare($conexion, "INSERT INTO enfrentamientos (id_torneo, ronda, id_equipo_local, id_equipo_visitante, estado) VALUES (?, ?, ?, ?, ?)");
        $estado = ($local === null || $visitante === null) ? "finalizado" : "pendiente";
        mysqli_stmt_bind_param($stmt, "iiiis", $id_torneo, $ronda, $local, $visitante, $estado);
        mysqli_stmt_execute($stmt);
        $id_enf = mysqli_insert_id($conexion);

        // Si hubo bye, el que si existe pasa directo como ganador
        if ($estado === "finalizado") {
            $ganador = $local ?? $visitante;
            $stmt2 = mysqli_prepare($conexion, "UPDATE enfrentamientos SET id_ganador = ? WHERE id_enfrentamiento = ?");
            mysqli_stmt_bind_param($stmt2, "ii", $ganador, $id_enf);
            mysqli_stmt_execute($stmt2);
        }

        $stmt = mysqli_prepare($conexion, "INSERT INTO llaves (id_torneo, ronda, posicion, id_enfrentamiento) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiii", $id_torneo, $ronda, $posicion, $id_enf);
        mysqli_stmt_execute($stmt);

        $posicion++;
    }

    // Crear las rondas siguientes vacias (se completan al avanzar ganadores)
    $partidos_en_ronda = $tamano_bracket / 2;
    $ronda++;
    while ($partidos_en_ronda > 1) {
        $partidos_en_ronda = intdiv($partidos_en_ronda, 2);
        for ($p = 0; $p < $partidos_en_ronda; $p++) {
            $stmt = mysqli_prepare($conexion, "INSERT INTO enfrentamientos (id_torneo, ronda) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ii", $id_torneo, $ronda);
            mysqli_stmt_execute($stmt);
            $id_enf = mysqli_insert_id($conexion);

            $stmt = mysqli_prepare($conexion, "INSERT INTO llaves (id_torneo, ronda, posicion, id_enfrentamiento) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "iiii", $id_torneo, $ronda, $p, $id_enf);
            mysqli_stmt_execute($stmt);
        }
        $ronda++;
    }

    // Si algun bye de la ronda 1 ya define un pasaje directo, lo empujamos a la ronda 2
    avanzar_byes_iniciales($conexion, $id_torneo);
}

function avanzar_byes_iniciales($conexion, $id_torneo) {
    $res = mysqli_query($conexion, "SELECT * FROM llaves WHERE id_torneo = $id_torneo AND ronda = 1 ORDER BY posicion");
    while ($fila = mysqli_fetch_assoc($res)) {
        $stmt = mysqli_prepare($conexion, "SELECT id_ganador FROM enfrentamientos WHERE id_enfrentamiento = ?");
        mysqli_stmt_bind_param($stmt, "i", $fila["id_enfrentamiento"]);
        mysqli_stmt_execute($stmt);
        $enf = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if ($enf && $enf["id_ganador"]) {
            avanzar_ganador($conexion, $id_torneo, 1, (int)$fila["posicion"], (int)$enf["id_ganador"]);
        }
    }
}

// Ubica al ganador de (ronda, posicion) en el partido correspondiente de la ronda siguiente
function avanzar_ganador($conexion, $id_torneo, $ronda, $posicion, $id_ganador) {
    $ronda_siguiente = $ronda + 1;
    $posicion_siguiente = intdiv($posicion, 2);
    $campo = ($posicion % 2 === 0) ? "id_equipo_local" : "id_equipo_visitante";

    $stmt = mysqli_prepare($conexion, "SELECT id_enfrentamiento FROM llaves WHERE id_torneo = ? AND ronda = ? AND posicion = ?");
    mysqli_stmt_bind_param($stmt, "iii", $id_torneo, $ronda_siguiente, $posicion_siguiente);
    mysqli_stmt_execute($stmt);
    $llave = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$llave) return; // era la final, no hay ronda siguiente

    $stmt = mysqli_prepare($conexion, "UPDATE enfrentamientos SET $campo = ? WHERE id_enfrentamiento = ?");
    mysqli_stmt_bind_param($stmt, "ii", $id_ganador, $llave["id_enfrentamiento"]);
    mysqli_stmt_execute($stmt);
}

// ── SISTEMA SUIZO: arma la siguiente ronda emparejando por puntos ──
function generar_ronda_suiza($conexion, $id_torneo, $equipos) {
    $res = mysqli_query($conexion, "SELECT MAX(ronda) AS ultima FROM enfrentamientos WHERE id_torneo = $id_torneo");
    $ultima_ronda = (int)(mysqli_fetch_assoc($res)["ultima"] ?? 0);

    if ($ultima_ronda > 0) {
        $pendientes = mysqli_fetch_assoc(mysqli_query($conexion,
            "SELECT COUNT(*) AS c FROM enfrentamientos WHERE id_torneo = $id_torneo AND ronda = $ultima_ronda AND estado != 'finalizado'"))["c"];
        if ($pendientes > 0) {
            throw new Exception("Todavia hay partidos sin resultado en la ronda actual.");
        }
    }

    $nueva_ronda = $ultima_ronda + 1;

    if ($ultima_ronda === 0) {
        // Ronda 1: emparejamiento al azar
        shuffle($equipos);
        foreach (array_filter($equipos) as $id_equipo) {
            $stmt = mysqli_prepare($conexion, "INSERT IGNORE INTO tabla_posiciones (id_torneo, id_equipo) VALUES (?, ?)");
            mysqli_stmt_bind_param($stmt, "ii", $id_torneo, $id_equipo);
            mysqli_stmt_execute($stmt);
        }
    } else {
        // Rondas siguientes: ordenar por puntos y emparejar adyacentes
        $res = mysqli_query($conexion, "SELECT id_equipo FROM tabla_posiciones WHERE id_torneo = $id_torneo ORDER BY puntos DESC, gf DESC");
        $equipos = [];
        while ($fila = mysqli_fetch_assoc($res)) $equipos[] = (int)$fila["id_equipo"];
    }

    if (count($equipos) % 2 !== 0) $equipos[] = null;

    for ($i = 0; $i < count($equipos); $i += 2) {
        $local = $equipos[$i];
        $visitante = $equipos[$i + 1];
        if ($local === null || $visitante === null) continue; // el que descansa no juega esta ronda
        $stmt = mysqli_prepare($conexion, "INSERT INTO enfrentamientos (id_torneo, ronda, id_equipo_local, id_equipo_visitante) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiii", $id_torneo, $nueva_ronda, $local, $visitante);
        mysqli_stmt_execute($stmt);
    }
}
