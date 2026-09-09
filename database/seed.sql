
-- Usuarios: admin y algunos alumnos de ejemplo
INSERT INTO usuarios(nombre, correo, cuenta, password, rol)
VALUES
('Administrador','admin@aragon.unam.mx','000000001','Admin2026','admin'),
('Alumno Uno','alumno1@comunidad.fes.aragon','202012345','Alumno123','alumno'),
('Alumno Dos','alumno2@comunidad.fes.aragon','202098765','Alumno123','alumno');


-- Edificios (E:)
INSERT INTO edificios(nombre, pisos)
VALUES
('A1',3),('A2',3),('A3',3),('A4',3),('A5',3),('A6',3),('A7',3),('A8',3),('A9',3),('A10',3),('A11',3),('A12',3),
('Anexo Derecho',2),('DUACyD',4),('Salón Usos Múltiples',1),('Gimnasio',1),('Adquisiciones',1),('Centro de Cómputo',1),
('Centro de Lenguas Extranjeras (CELE)',1),('Centro Tecnológico',1),('Biblioteca',1),('Servicio Médico y Comedor',1),
('Módulo de Extensión Universitaria',1),('Clínica Odontológica Izcalla',1),('Edificio de Gobierno',1),
('Laboratorio L-1',1),('Laboratorio L-2',1),('Laboratorio L-3',1),('Laboratorio L-4',1);



-- Salones (generados sencillos: Sala 101..105 por edificio)
INSERT INTO salones(nombre, edificio_id)
SELECT CONCAT('Sala ', FLOOR(100 + n)) , e.id
FROM (
	SELECT 1 n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
) nums
CROSS JOIN edificios e
WHERE n <= 5;

-- Incidencias de ejemplo (algunas creadas por alumnos)
-- Zonas principales donde pueden generarse reportes
-- Zonas (Z:)
INSERT INTO zonas(nombre) VALUES
('Pesas y Regaderas'),
('Instalaciones Académicas y Equipo Audiovisual'),
('Esculturas');

-- Incidencias de ejemplo (algunas creadas por alumnos), ahora con zona_id
INSERT INTO incidencias(titulo, descripcion, tipo, prioridad, estado, edificio_id, salon_id, zona_id, user_id)
VALUES
('Baño sin agua','El lavabo no tiene suministro de agua en el segundo piso','Servicios','Alta','Abierta',1,1,1,2),
('Luz dañada','Falta iluminación en el pasillo principal','Iluminación','Media','En proceso',2,2,2,3),
('Aire acondicionado','El equipo no enfría en el aula 203','Climatización','Alta','Abierta',5,3,3,2),
('Puerta dañada','La cerradura del aula 104 no funciona','Seguridad','Media','Abierta',3,4,1,3),
('Equipo de cómputo','PC sin arranque en laboratorio 2','Equipamiento','Alta','Pendiente',5,7,2,2);

-- 98 respuestas iniciales para la encuesta (la revisión puede agregar 2 más)
INSERT INTO encuesta_respuestas
	(user_id, sexo, edad, pregunta_1, pregunta_2, pregunta_3, pregunta_4, pregunta_5, pregunta_6, abierta_1, abierta_2)
WITH RECURSIVE numeros AS (
	SELECT 1 AS n
	UNION ALL
	SELECT n + 1 FROM numeros WHERE n < 98
)
SELECT
	NULL,
	CASE MOD(n, 4) WHEN 0 THEN 'Mujer' WHEN 1 THEN 'Hombre' WHEN 2 THEN 'No binario' ELSE 'Prefiero no decirlo' END,
	20 + MOD(n, 14),
	CASE MOD(n, 4) WHEN 0 THEN 'Muy satisfecho' WHEN 1 THEN 'Satisfecho' WHEN 2 THEN 'Poco satisfecho' ELSE 'Nada satisfecho' END,
	CASE MOD(n, 4) WHEN 0 THEN 'Aulas' WHEN 1 THEN 'Biblioteca' WHEN 2 THEN 'Áreas deportivas' ELSE 'Laboratorios' END,
	CASE MOD(n, 4) WHEN 0 THEN 'Redes sociales' WHEN 1 THEN 'Carteles' WHEN 2 THEN 'Docentes' ELSE 'Compañeros' END,
	CASE MOD(n, 4) WHEN 0 THEN 'Seguridad' WHEN 1 THEN 'Conectividad' WHEN 2 THEN 'Áreas verdes' ELSE 'Servicios escolares' END,
	CASE MOD(n, 4) WHEN 0 THEN 'Cada semana' WHEN 1 THEN 'Cada mes' WHEN 2 THEN 'Ocasionalmente' ELSE 'Nunca' END,
	CASE MOD(n, 4) WHEN 0 THEN 'Antes de clases' WHEN 1 THEN 'Entre clases' WHEN 2 THEN 'Después de clases' ELSE 'Fin de semana' END,
	CONCAT('Mejoraría el campus con más espacios de estudio, respuesta ', n, '.'),
	CONCAT('Me gustaría contar con talleres y actividades nuevas, propuesta ', n, '.')
FROM numeros;
