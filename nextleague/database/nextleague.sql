-- =====================================================================
-- NEXTLEAGUE - Sistema de Gestion de Torneos
-- Script de creacion de base de datos
-- Motor: MySQL / MariaDB
-- =====================================================================

CREATE DATABASE IF NOT EXISTS nextleague
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE nextleague;

-- =====================================================================
-- 1. USUARIOS
-- Guarda a todos los usuarios de la plataforma. El rol define el nivel
-- de acceso (administrador, organizador, participante, publico).
-- La foto_perfil guarda solo el NOMBRE del archivo, no la imagen en si;
-- el archivo real vive en el servidor (backend/uploads/perfiles).
-- =====================================================================
CREATE TABLE usuarios (
    id_usuario        INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario    VARCHAR(50)  NOT NULL UNIQUE,
    correo            VARCHAR(100) NOT NULL UNIQUE,
    contrasena_hash   VARCHAR(255) NOT NULL,
    foto_perfil       VARCHAR(255) NULL DEFAULT NULL,
    rol               ENUM('administrador','organizador','participante','publico') NOT NULL DEFAULT 'publico',
    activo            TINYINT(1) NOT NULL DEFAULT 1,
    fecha_registro    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- 2. JUEGOS / DISCIPLINAS
-- Cubre deportes tradicionales, disciplinas mentales y videojuegos.
-- =====================================================================
CREATE TABLE juegos (
    id_juego     INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL,
    categoria    ENUM('deportivo','mental','electronico') NOT NULL,
    icono        VARCHAR(255) NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 3. TORNEOS
-- =====================================================================
CREATE TABLE torneos (
    id_torneo        INT AUTO_INCREMENT PRIMARY KEY,
    nombre           VARCHAR(150) NOT NULL,
    id_juego         INT NOT NULL,
    id_organizador   INT NOT NULL,
    formato          ENUM('liga','eliminacion_directa','sistema_suizo') NOT NULL,
    num_equipos      INT NOT NULL,
    premio           DECIMAL(10,2) DEFAULT 0,
    fecha_inicio     DATE NOT NULL,
    fecha_fin        DATE NOT NULL,
    descripcion      TEXT,
    logo             VARCHAR(255) NULL,
    reglas_pdf       VARCHAR(255) NULL,
    estado           ENUM('pendiente','en_curso','finalizado','cancelado') NOT NULL DEFAULT 'pendiente',
    fecha_creacion   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_torneo_juego       FOREIGN KEY (id_juego)       REFERENCES juegos(id_juego)       ON DELETE RESTRICT,
    CONSTRAINT fk_torneo_organizador FOREIGN KEY (id_organizador) REFERENCES usuarios(id_usuario)   ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================================
-- 4. EQUIPOS
-- =====================================================================
CREATE TABLE equipos (
    id_equipo    INT AUTO_INCREMENT PRIMARY KEY,
    id_torneo    INT NOT NULL,
    nombre       VARCHAR(100) NOT NULL,
    logo         VARCHAR(255) NULL,
    id_capitan   INT NULL,
    CONSTRAINT fk_equipo_torneo   FOREIGN KEY (id_torneo)  REFERENCES torneos(id_torneo)   ON DELETE CASCADE,
    CONSTRAINT fk_equipo_capitan  FOREIGN KEY (id_capitan) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 5. MIEMBROS DE EQUIPO (N:M usuarios <-> equipos)
-- =====================================================================
CREATE TABLE equipo_miembros (
    id_equipo    INT NOT NULL,
    id_usuario   INT NOT NULL,
    fecha_union  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_equipo, id_usuario),
    CONSTRAINT fk_miembro_equipo  FOREIGN KEY (id_equipo)  REFERENCES equipos(id_equipo)   ON DELETE CASCADE,
    CONSTRAINT fk_miembro_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 6. PARTICIPANTES (inscripcion a un torneo; individual o por equipo)
-- =====================================================================
CREATE TABLE participantes (
    id_participante   INT AUTO_INCREMENT PRIMARY KEY,
    id_torneo         INT NOT NULL,
    id_usuario        INT NULL,
    id_equipo         INT NULL,
    fecha_inscripcion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_participante_torneo  FOREIGN KEY (id_torneo)  REFERENCES torneos(id_torneo)   ON DELETE CASCADE,
    CONSTRAINT fk_participante_usuario FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE,
    CONSTRAINT fk_participante_equipo  FOREIGN KEY (id_equipo)  REFERENCES equipos(id_equipo)   ON DELETE CASCADE,
    CONSTRAINT chk_participante_tipo CHECK (id_usuario IS NOT NULL OR id_equipo IS NOT NULL)
) ENGINE=InnoDB;

-- =====================================================================
-- 7. ENFRENTAMIENTOS (partidos / matches)
-- Sirve tanto para liga como para eliminacion directa y sistema suizo.
-- =====================================================================
CREATE TABLE enfrentamientos (
    id_enfrentamiento   INT AUTO_INCREMENT PRIMARY KEY,
    id_torneo           INT NOT NULL,
    ronda               INT NOT NULL,
    id_equipo_local      INT NULL,
    id_equipo_visitante  INT NULL,
    fecha_hora           DATETIME NULL,
    resultado_local       INT NULL,
    resultado_visitante   INT NULL,
    id_ganador            INT NULL,
    estado                ENUM('pendiente','en_curso','finalizado') NOT NULL DEFAULT 'pendiente',
    CONSTRAINT fk_enf_torneo   FOREIGN KEY (id_torneo)          REFERENCES torneos(id_torneo) ON DELETE CASCADE,
    CONSTRAINT fk_enf_local    FOREIGN KEY (id_equipo_local)     REFERENCES equipos(id_equipo) ON DELETE SET NULL,
    CONSTRAINT fk_enf_visita   FOREIGN KEY (id_equipo_visitante) REFERENCES equipos(id_equipo) ON DELETE SET NULL,
    CONSTRAINT fk_enf_ganador  FOREIGN KEY (id_ganador)          REFERENCES equipos(id_equipo) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 8. TABLA DE POSICIONES (para formato liga y sistema suizo)
-- =====================================================================
CREATE TABLE tabla_posiciones (
    id_torneo   INT NOT NULL,
    id_equipo   INT NOT NULL,
    puntos      INT NOT NULL DEFAULT 0,
    pj          INT NOT NULL DEFAULT 0,  -- partidos jugados
    pg          INT NOT NULL DEFAULT 0,  -- partidos ganados
    pe          INT NOT NULL DEFAULT 0,  -- partidos empatados
    pp          INT NOT NULL DEFAULT 0,  -- partidos perdidos
    gf          INT NOT NULL DEFAULT 0,  -- a favor
    gc          INT NOT NULL DEFAULT 0,  -- en contra
    PRIMARY KEY (id_torneo, id_equipo),
    CONSTRAINT fk_pos_torneo FOREIGN KEY (id_torneo) REFERENCES torneos(id_torneo) ON DELETE CASCADE,
    CONSTRAINT fk_pos_equipo FOREIGN KEY (id_equipo) REFERENCES equipos(id_equipo) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 9. LLAVES / BRACKET (para eliminacion directa)
-- =====================================================================
CREATE TABLE llaves (
    id_llave           INT AUTO_INCREMENT PRIMARY KEY,
    id_torneo          INT NOT NULL,
    ronda              INT NOT NULL,
    posicion           INT NOT NULL,
    id_enfrentamiento  INT NULL,
    CONSTRAINT fk_llave_torneo FOREIGN KEY (id_torneo)         REFERENCES torneos(id_torneo)         ON DELETE CASCADE,
    CONSTRAINT fk_llave_enf    FOREIGN KEY (id_enfrentamiento) REFERENCES enfrentamientos(id_enfrentamiento) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- INDICES DE APOYO
-- =====================================================================
CREATE INDEX idx_torneos_estado        ON torneos(estado);
CREATE INDEX idx_enfrentamientos_torneo ON enfrentamientos(id_torneo, ronda);
CREATE INDEX idx_equipos_torneo        ON equipos(id_torneo);

-- =====================================================================
-- DATOS DE EJEMPLO (opcional, para probar rapido)
-- =====================================================================
INSERT INTO juegos (nombre, categoria) VALUES
('Futbol 5', 'deportivo'),
('Ajedrez', 'mental'),
('Valorant', 'electronico');
