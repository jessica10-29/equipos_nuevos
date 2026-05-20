CREATE DATABASE inventario_equipos;
USE inventario_equipos;

CREATE TABLE equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_caja VARCHAR(10) NOT NULL,
    marca_pc VARCHAR(100) NOT NULL,
    nombre_pc VARCHAR(100) NOT NULL,
    modelo_pc VARCHAR(100) NOT NULL,
    serial_pc VARCHAR(100) NOT NULL,
    modelo_cargador VARCHAR(100) NOT NULL,
    serial_cargador VARCHAR(100) NOT NULL,
    estado ENUM('Disponible','Asignado') DEFAULT 'Disponible'
);
