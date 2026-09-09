
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    cuenta VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol VARCHAR(50) NOT NULL DEFAULT 'alumno',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE edificios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    pisos INT DEFAULT 1
);

CREATE TABLE salones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    edificio_id INT NOT NULL,
    FOREIGN KEY (edificio_id) REFERENCES edificios(id) ON DELETE CASCADE
);
CREATE TABLE zonas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL
);

CREATE TABLE incidencias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(255) NOT NULL,
    descripcion TEXT,
    tipo VARCHAR(100) DEFAULT 'General',
    prioridad VARCHAR(50) DEFAULT 'Media',
    estado VARCHAR(50) DEFAULT 'Abierta',
    edificio_id INT,
    salon_id INT,
    zona_id INT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (edificio_id) REFERENCES edificios(id) ON DELETE SET NULL,
    FOREIGN KEY (salon_id) REFERENCES salones(id) ON DELETE SET NULL,
    FOREIGN KEY (zona_id) REFERENCES zonas(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL
);

CREATE TABLE encuesta_respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    sexo ENUM('Mujer', 'Hombre', 'No binario', 'Prefiero no decirlo') NOT NULL,
    edad TINYINT UNSIGNED NOT NULL,
    pregunta_1 VARCHAR(40) NOT NULL,
    pregunta_2 VARCHAR(40) NOT NULL,
    pregunta_3 VARCHAR(40) NOT NULL,
    pregunta_4 VARCHAR(40) NOT NULL,
    pregunta_5 VARCHAR(40) NOT NULL,
    pregunta_6 VARCHAR(40) NOT NULL,
    abierta_1 VARCHAR(500) NOT NULL,
    abierta_2 VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL
);
