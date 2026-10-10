CREATE DATABASE IF NOT EXISTS app_comentarios
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE app_comentarios;

-- Dos tablas separadas (D-04). La tabla de respuestas NO se referencia a sí misma.
-- DDL solamente: sin lógica y sin datos de ejemplo.
CREATE TABLE comentarios (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    idArticulo    INT NOT NULL,
    autor         VARCHAR(40) NOT NULL,
    contenido     VARCHAR(500) NOT NULL,
    fechaCreacion DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE respuestas (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    idComentarioPadre INT NOT NULL,
    autor             VARCHAR(40) NOT NULL,
    contenido         VARCHAR(500) NOT NULL,
    fechaCreacion     DATETIME NOT NULL,
    CONSTRAINT fk_respuestas_comentario
        FOREIGN KEY (idComentarioPadre) REFERENCES comentarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
