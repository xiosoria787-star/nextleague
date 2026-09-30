<?php
// Recibe (multipart/form-data): tournamentName, id_juego, tournamentType,
// teams, award, startDate, endDate, description, subir-imagen (logo), subir-reglas (pdf)
// Cualquier usuario logueado puede crear un torneo; al hacerlo pasa a ser "organizador"
// (si todavia era publico/participante) y queda como dueño de ese torneo.
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    responder(false, "Metodo no permitido.");
}

requerir_sesion();
$id_organizador = (int)$_SESSION["id_usuario"];

if ($_SESSION["rol"] === "participante") {
    responder(false, "Tu cuenta está registrada como participante. Para crear torneos necesitás una cuenta de organizador.");
}

$nombre       = trim($_POST["tournamentName"] ?? "");
$id_juego     = (int)($_POST["id_juego"] ?? 0);
$formato      = $_POST["tournamentType"] ?? "";
$num_equipos  = (int)($_POST["teams"] ?? 0);
$premio       = (float)($_POST["award"] ?? 0);
$moneda       = $_POST["moneda"] ?? "UYU";
$fecha_inicio = $_POST["startDate"] ?? "";
$fecha_fin    = $_POST["endDate"] ?? "";
$descripcion  = trim($_POST["description"] ?? "");

$formatos_validos = ["liga", "eliminacion_directa", "sistema_suizo"];

if (strlen($nombre) < 3) responder(false, "El nombre del torneo es obligatorio.");
if (!$id_juego) responder(false, "Selecciona un juego/disciplina.");
if (!in_array($formato, $formatos_validos, true)) responder(false, "Formato de torneo invalido.");
if ($num_equipos < 2) responder(false, "El torneo necesita al menos 2 equipos.");
if (!$fecha_inicio || !$fecha_fin) responder(false, "Completa las fechas del torneo.");
if (strtotime($fecha_fin) < strtotime($fecha_inicio)) responder(false, "La fecha de fin no puede ser anterior a la de inicio.");

$carpeta_logos  = __DIR__ . "/../uploads/torneos/logos/";
$carpeta_reglas = __DIR__ . "/../uploads/torneos/reglas/";

$logo   = guardar_archivo("subir-imagen", $carpeta_logos, ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"], 3 * 1024 * 1024, "torneo", true);
$reglas = guardar_archivo("subir-reglas", $carpeta_reglas, ["application/pdf" => "pdf"], 5 * 1024 * 1024, "reglas", true);

$consulta = "INSERT INTO torneos (nombre, id_juego, id_organizador, formato, num_equipos, premio, fecha_inicio, fecha_fin, descripcion, logo, reglas_pdf, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendiente')";
$stmt = mysqli_prepare($conexion, $consulta);
mysqli_stmt_bind_param($stmt, "siisidsssss",
    $nombre, $id_juego, $id_organizador, $formato, $num_equipos, $premio,
    $fecha_inicio, $fecha_fin, $descripcion, $logo, $reglas
);

if (!mysqli_stmt_execute($stmt)) {
    borrar_archivo_si_existe($carpeta_logos . $logo);
    borrar_archivo_si_existe($carpeta_reglas . $reglas);
    responder(false, "No se pudo crear el torneo.");
}

$id_torneo = mysqli_insert_id($conexion);

// Las cuentas viejas ("publico", de antes de que el registro pidiera elegir
// un rol) pasan a organizador automáticamente al crear su primer torneo.
if ($_SESSION["rol"] === "publico") {
    $stmt2 = mysqli_prepare($conexion, "UPDATE usuarios SET rol = 'organizador' WHERE id_usuario = ?");
    mysqli_stmt_bind_param($stmt2, "i", $id_organizador);
    mysqli_stmt_execute($stmt2);
    $_SESSION["rol"] = "organizador";
}

Auditoria::registrar($conexion, $id_organizador, "crear", "torneo", $id_torneo, "Torneo \"$nombre\" creado");
responder(true, "Torneo creado exitosamente.", ["id_torneo" => $id_torneo]);
