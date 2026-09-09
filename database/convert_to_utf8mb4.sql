-- Convierte tablas a utf8mb4 (ejecutar con precaución, hacer backup antes)
ALTER TABLE usuarios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE edificios CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE salones CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE incidencias CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
