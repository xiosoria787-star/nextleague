<?php
// GET listar_torneos.php?estado=en_curso  (opcional)
// GET listar_torneos.php?destacado=1      (trae solo el torneo mas reciente activo)
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

$estado = $_GET["estado"] ?? null;
$limite = isset($_GET["destacado"]) ? 1 : 50;

$consulta = "SELECT t.id_torneo, t.nombre, t.formato, t.num_equipos, t.premio, t.moneda,
                    t.fecha_inicio, t.fecha_fin, t.logo, t.estado, t.id_juego,
                    j.nombre AS juego, j.categoria,
                    (SELECT COUNT(*) FROM equipos e WHERE e.id_torneo = t.id_torneo) AS equipos_inscriptos
             FROM torneos t
             JOIN juegos j ON j.id_juego = t.id_juego";

$params = [];
$tipos = "";
if ($estado) {
    $consulta .= " WHERE t.estado = ?";
    $params[] = $estado;
    $tipos .= "s";
}
$consulta .= " ORDER BY t.fecha_creacion DESC LIMIT " . (int)$limite;

$stmt = mysqli_prepare($conexion, $consulta);
if ($tipos) {
    mysqli_stmt_bind_param($stmt, $tipos, ...$params);
}
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

$torneos = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $torneos[] = $fila;
}

echo json_encode(["exito" => true, "torneos" => $torneos]);
