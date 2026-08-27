<?php
// =====================================================================
// MANEJO DE SESION Y PERMISOS
// Se incluye en cada endpoint que necesite saber quien esta logueado.
// =====================================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Content-Type: application/json; charset=UTF-8");

function esta_logueado() {
    return isset($_SESSION["id_usuario"]);
}

// Corta la ejecucion y responde en JSON si NO hay sesion iniciada.
function requerir_sesion() {
    if (!esta_logueado()) {
        http_response_code(401);
        echo json_encode(["exito" => false, "mensaje" => "Debes iniciar sesion para realizar esta accion."]);
        exit;
    }
}

// Corta la ejecucion si el usuario logueado NO es el dueño del recurso
// (id_usuario_objetivo), salvo que sea administrador.
function requerir_ser_dueno($id_usuario_objetivo) {
    requerir_sesion();
    $es_admin = ($_SESSION["rol"] ?? "") === "administrador";
    if (!$es_admin && (int)$_SESSION["id_usuario"] !== (int)$id_usuario_objetivo) {
        http_response_code(403);
        echo json_encode(["exito" => false, "mensaje" => "No tenes permiso para realizar esta accion."]);
        exit;
    }
}

// Corta la ejecucion si el rol del usuario logueado no esta en la lista permitida.
function requerir_rol(array $roles_permitidos) {
    requerir_sesion();
    if (!in_array($_SESSION["rol"] ?? "", $roles_permitidos, true)) {
        http_response_code(403);
        echo json_encode(["exito" => false, "mensaje" => "No tenes permisos suficientes para esta accion."]);
        exit;
    }
}
