
# Encuesta de Experiencia Universitaria - FES Aragón

Aplicación web para capturar una encuesta con datos demográficos, seis preguntas cerradas y dos abiertas. Las respuestas se almacenan en MySQL y el administrador puede consultar frecuencia y porcentaje por pregunta.

Cómo usar (resumen):
- Importar `database/schema.sql` y `database/seed.sql` en MySQL (o usar `docker compose up -d --build`). La semilla crea 98 respuestas iniciales.
- Si ya existía una instalación anterior y faltan tablas nuevas, detenerla con `docker compose down -v` y volver a ejecutar `docker compose up -d --build`. El parámetro `-v` borra los datos locales de MySQL y hace que los scripts se ejecuten desde cero.
- Para reemplazar los registros actuales por los 98 registros del cuestionario original, ejecutar el script `database/restore_hobbies.sql` contra MySQL.
- Si ya levantaste Docker y solo faltan los registros, ejecuta `Get-Content database/restore_hobbies.sql | docker compose exec -T mysql mysql -uroot -proot -Dfesaragon`. El comando termina mostrando `98`.
- Abrir `http://localhost:8080/` o `http://localhost:8080/login.php`; el sistema entra automáticamente a la encuesta sin pedir cuenta, correo ni contraseña.
- El resultado individual aparece al enviar la encuesta; el panel general de resultados queda disponible para usuarios con rol `admin`.

Estructura principal:
- public/: páginas de acceso, encuesta y resultados
- src/: funciones de autenticación
- templates/: encabezado y pie de página reutilizables
- database/: esquema y datos iniciales
