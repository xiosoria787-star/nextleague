<?php
// =====================================================================
// FUNCIONES DE APOYO REUTILIZABLES
// =====================================================================

require_once __DIR__ . "/../models/Auditoria.php";

// Lee el body como JSON; si no vino como JSON, cae a $_POST.
function leer_datos() {
    $datos = json_decode(file_get_contents("php://input"), true);
    return $datos ?: $_POST;
}

// Guarda un archivo subido ($_FILES[$campo]) validando tipo y tamaño.
// Devuelve el nombre generado, o null si no se subio nada (y $obligatorio = false).
function guardar_archivo($campo, $carpeta_destino, $tipos_permitidos, $tamano_maximo, $prefijo, $obligatorio = true) {
    if (!isset($_FILES[$campo]) || $_FILES[$campo]["error"] !== UPLOAD_ERR_OK) {
        if ($obligatorio) {
            responder(false, "Falta el archivo requerido: $campo.");
        }
        return null;
    }

    $archivo = $_FILES[$campo];
    $tipo_real = mime_content_type($archivo["tmp_name"]);

    if (!isset($tipos_permitidos[$tipo_real])) {
        responder(false, "Formato de archivo no permitido para $campo.");
    }
    if ($archivo["size"] > $tamano_maximo) {
        responder(false, "El archivo $campo supera el tamaño máximo permitido.");
    }

    if (!is_dir($carpeta_destino)) {
        mkdir($carpeta_destino, 0755, true);
    }

    $extension = $tipos_permitidos[$tipo_real];
    $nombre_nuevo = $prefijo . "_" . uniqid() . "." . $extension;
    $ruta_destino = $carpeta_destino . $nombre_nuevo;

    if (!move_uploaded_file($archivo["tmp_name"], $ruta_destino)) {
        responder(false, "No se pudo guardar el archivo $campo en el servidor.");
    }

    return $nombre_nuevo;
}

// Borra un archivo del servidor si existe (usado al reemplazar/eliminar fotos, logos, etc).
function borrar_archivo_si_existe($ruta) {
    if ($ruta && file_exists($ruta)) {
        unlink($ruta);
    }
}

// Responde en JSON y corta la ejecucion.
function responder($exito, $mensaje, $extra = []) {
    echo json_encode(array_merge(["exito" => $exito, "mensaje" => $mensaje], $extra));
    exit;
}
