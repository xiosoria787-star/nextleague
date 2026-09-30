<?php
// GET listar_auditoria.php
require_once __DIR__ . "/../includes/sesion.php";
require_once __DIR__ . "/../includes/ayudantes.php";
require_once __DIR__ . "/../config/conexion.php";

requerir_rol(["administrador"]);

$registros = Auditoria::listarRecientes($conexion, 100);

responder(true, "", ["registros" => $registros]);
