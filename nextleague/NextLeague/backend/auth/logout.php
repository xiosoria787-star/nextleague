<?php
require_once __DIR__ . "/../includes/sesion.php";

$_SESSION = [];
session_destroy();

echo json_encode(["exito" => true, "mensaje" => "Sesion cerrada."]);
