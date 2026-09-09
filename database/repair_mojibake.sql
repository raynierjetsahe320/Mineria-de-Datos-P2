-- Intenta reparar columnas que están con bytes latin1 interpretados como utf8
-- HAZ BACKUP ANTES
UPDATE usuarios SET nombre = CONVERT(BINARY(CONVERT(nombre USING latin1)) USING utf8mb4) WHERE nombre LIKE '%Ã%';
UPDATE usuarios SET correo = CONVERT(BINARY(CONVERT(correo USING latin1)) USING utf8mb4) WHERE correo LIKE '%Ã%';
UPDATE edificios SET nombre = CONVERT(BINARY(CONVERT(nombre USING latin1)) USING utf8mb4) WHERE nombre LIKE '%Ã%';
UPDATE salones SET nombre = CONVERT(BINARY(CONVERT(nombre USING latin1)) USING utf8mb4) WHERE nombre LIKE '%Ã%';
UPDATE incidencias SET titulo = CONVERT(BINARY(CONVERT(titulo USING latin1)) USING utf8mb4) WHERE titulo LIKE '%Ã%';
UPDATE incidencias SET descripcion = CONVERT(BINARY(CONVERT(descripcion USING latin1)) USING utf8mb4) WHERE descripcion LIKE '%Ã%';
