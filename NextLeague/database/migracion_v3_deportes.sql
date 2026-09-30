-- =====================================================================
-- MIGRACION v3: amplia el catalogo de deportes/juegos a 30+ disciplinas.
-- Corré esto en phpMyAdmin (pestaña SQL) si ya tenías la base creada.
-- Si vas a crear la base desde cero, no hace falta: nextleague.sql ya
-- viene con este catálogo completo.
-- =====================================================================

USE nextleague;

-- Evita duplicados si la migración se corre más de una vez.
ALTER TABLE juegos ADD UNIQUE KEY uk_juegos_nombre (nombre);

INSERT IGNORE INTO juegos (nombre, categoria) VALUES
('Fútbol', 'deportivo'),
('Futsal', 'deportivo'),
('Básquetbol', 'deportivo'),
('Vóleibol', 'deportivo'),
('Handball', 'deportivo'),
('Rugby', 'deportivo'),
('Tenis', 'deportivo'),
('Tenis de mesa', 'deportivo'),
('Bádminton', 'deportivo'),
('Pádel', 'deportivo'),
('Hockey sobre césped', 'deportivo'),
('Béisbol', 'deportivo'),
('Sóftbol', 'deportivo'),
('Cricket', 'deportivo'),
('Boxeo', 'deportivo'),
('MMA', 'deportivo'),
('Judo', 'deportivo'),
('Karate', 'deportivo'),
('Taekwondo', 'deportivo'),
('Atletismo', 'deportivo'),
('Natación', 'deportivo'),
('Ciclismo', 'deportivo'),
('Golf', 'deportivo'),
('Levantamiento de pesas', 'deportivo'),
('Gimnasia', 'deportivo'),
('Waterpolo', 'deportivo'),
('Damas', 'mental'),
('Truco', 'mental'),
('Póker', 'mental'),
('Dominó', 'mental'),
('League of Legends', 'electronico'),
('Counter-Strike 2', 'electronico'),
('FIFA / EA Sports FC', 'electronico'),
('Fortnite', 'electronico'),
('Rocket League', 'electronico');
