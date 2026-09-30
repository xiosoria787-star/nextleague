-- =====================================================================
-- MIGRACION: agrega las columnas nuevas sin borrar los datos existentes.
-- Corré esto en phpMyAdmin (pestaña SQL) SOLO SI ya habías importado
-- nextleague.sql antes de esta actualización.
-- Si vas a crear la base desde cero, no hace falta este archivo:
-- alcanza con importar nextleague.sql, que ya viene con estas columnas.
-- =====================================================================

USE nextleague;

ALTER TABLE usuarios
    ADD COLUMN biografia VARCHAR(280) NULL DEFAULT NULL AFTER foto_perfil,
    ADD COLUMN token_recuperacion VARCHAR(64) NULL DEFAULT NULL AFTER rol,
    ADD COLUMN token_recuperacion_vence DATETIME NULL DEFAULT NULL AFTER token_recuperacion;

ALTER TABLE torneos
    ADD COLUMN moneda ENUM('UYU','USD') NOT NULL DEFAULT 'UYU' AFTER premio;
