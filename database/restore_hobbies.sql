-- Reemplaza las respuestas existentes por los 98 registros del cuestionario original.
-- Ejecutar solo cuando se quiera reiniciar la encuesta.
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE encuesta_respuestas;
SET FOREIGN_KEY_CHECKS = 1;

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

SELECT COUNT(*) AS total_respuestas FROM encuesta_respuestas;
