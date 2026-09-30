<?php
// =====================================================================
// MODELO: AUDITORIA
// Guarda un historial de las acciones que modifican datos importantes,
// tal como lo exige la letra del proyecto ("deberá quedar registrado
// cualquier cambio que se realice en la información de la Base de Datos").
// =====================================================================
class Auditoria {

    // $accion: verbo corto en infinitivo/pasado (ej: "crear", "editar", "eliminar", "corregir").
    // $entidad: nombre de la tabla o concepto afectado (ej: "torneo", "usuario", "resultado").
    // $detalle: texto breve legible para mostrar en el panel de administración.
    public static function registrar($conexion, $id_usuario, $accion, $entidad, $id_entidad = null, $detalle = null) {
        $stmt = mysqli_prepare($conexion, "INSERT INTO auditoria (id_usuario, accion, entidad, id_entidad, detalle) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "issis", $id_usuario, $accion, $entidad, $id_entidad, $detalle);
        mysqli_stmt_execute($stmt);
    }

    // Devuelve los últimos $limite registros, con el nombre de usuario para mostrar.
    public static function listarRecientes($conexion, $limite = 50) {
        $limite = (int)$limite;
        $consulta = "SELECT a.id_auditoria, a.accion, a.entidad, a.id_entidad, a.detalle, a.fecha,
                            u.nombre_usuario
                     FROM auditoria a
                     LEFT JOIN usuarios u ON u.id_usuario = a.id_usuario
                     ORDER BY a.fecha DESC
                     LIMIT $limite";
        $resultado = mysqli_query($conexion, $consulta);
        $registros = [];
        while ($fila = mysqli_fetch_assoc($resultado)) {
            $registros[] = $fila;
        }
        return $registros;
    }
}
