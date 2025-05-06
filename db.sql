CREATE DATABASE IF NOT EXISTS recetario
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'recetario_admin'@'localhost' IDENTIFIED BY 'Pass123';

GRANT ALL PRIVILEGES
  ON recetario.*
  TO 'recetario_admin'@'localhost';

FLUSH PRIVILEGES;

USE recetario;

CREATE TABLE IF NOT EXISTS perfil (
  id_perfil INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(100) NOT NULL,
  username   VARCHAR(50)  NOT NULL,
  email      VARCHAR(100) NOT NULL,
  password   VARCHAR(255) NOT NULL,
  fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO perfil (nombre, username, email, password)
VALUES ('Admin', 'admin', 'admin@example.com', 'Pass123');


CREATE TABLE IF NOT EXISTS recetas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(255) NOT NULL,
    portions VARCHAR(50),
    prep_time_minutes INT,
    cook_time_minutes INT,
    ingredients TEXT,
    preparation_steps TEXT,
    image_path VARCHAR(255)
);
ALTER TABLE recetas AUTO_INCREMENT =1;

CREATE TABLE IF NOT EXISTS categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL UNIQUE,
  descripcion TEXT,
  color VARCHAR(20) DEFAULT '#f7934c',
  fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE categorias AUTO_INCREMENT =1;

-- Añadir clave foránea a la tabla recetas
ALTER TABLE recetas 
ADD COLUMN categoria_id INT,
ADD CONSTRAINT fk_recetas_categorias 
  FOREIGN KEY (categoria_id) 
  REFERENCES categorias(id) 
  ON DELETE SET NULL 
  ON UPDATE CASCADE;
