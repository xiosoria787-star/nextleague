-- =====================================================================
-- MIGRACION v6: sistema de solicitudes para unirse a un equipo.
-- Corré esto si ya tenías la base creada. En una instalación nueva no
-- hace falta: nextleague.sql ya la incluye.
-- =====================================================================

USE nextleague;

CREATE TABLE IF NOT EXISTS equipo_solicitudes (
    id_solicitud     INT AUTO_INCREMENT PRIMARY KEY,
    id_equipo        INT NOT NULL,
    id_usuario       INT NOT NULL,
    estado           ENUM('pendiente','aceptada','rechazada') NOT NULL DEFAULT 'pendiente',
    fecha_solicitud  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_respuesta  DATETIME NULL,
    CONSTRAINT fk_solicitud_equipo  FOREIGN KEY (id_equipo)  REFERENCES equipos(id_equipo)   ON DELETE CASCADE,
    CONSTRAINT fk_solicitud_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;
