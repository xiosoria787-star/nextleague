-- =====================================================================
-- MIGRACION v5: crea el usuario administrador inicial.
-- Corré esto si ya tenías la base creada. En una instalación nueva no
-- hace falta: nextleague.sql ya lo incluye.
--
-- Usuario: admin@nextleague.com
-- Contraseña: Admin123!
-- =====================================================================

USE nextleague;

INSERT INTO usuarios (nombre_usuario, correo, contrasena_hash, rol)
SELECT 'admin', 'admin@nextleague.com', '$2y$10$Sav18ZWDPzVAI5fc4my9QOJpOkh13BK6kY8ddmcSI/rYxTwCcxxSe', 'administrador'
WHERE NOT EXISTS (SELECT 1 FROM usuarios WHERE correo = 'admin@nextleague.com');
