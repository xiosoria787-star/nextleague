-- =====================================================================
-- MIGRACION v4: agrega la tabla de auditoria (historial de cambios),
-- pedida explícitamente por la letra del proyecto.
-- Corré esto si ya tenías la base creada. En una instalación nueva no
-- hace falta: nextleague.sql ya la incluye.
-- =====================================================================

USE nextleague;

CREATE TABLE IF NOT EXISTS auditoria (
    id_auditoria    INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario      INT NULL,
    accion          VARCHAR(50) NOT NULL,
    entidad         VARCHAR(50) NOT NULL,
    id_entidad      INT NULL,
    detalle         VARCHAR(255) NULL,
    fecha           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_auditoria_fecha ON auditoria(fecha DESC);
