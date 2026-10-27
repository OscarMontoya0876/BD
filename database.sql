CREATE DATABASE IF NOT EXISTS gymshark_escuela CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gymshark_escuela;

CREATE TABLE IF NOT EXISTS usuarios (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 correo VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS productos (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(140) NOT NULL,
 categoria ENUM('Hombre','Mujer','Accesorios') NOT NULL,
 descripcion VARCHAR(250) NOT NULL,
 precio DECIMAL(10,2) NOT NULL,
 stock INT UNSIGNED NOT NULL DEFAULT 0,
 imagen VARCHAR(500) NOT NULL,
 etiqueta VARCHAR(40) NOT NULL DEFAULT ''
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS pedidos (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT UNSIGNED NOT NULL,
 total DECIMAL(10,2) NOT NULL,
 estado ENUM('demostracion','cancelado') NOT NULL DEFAULT 'demostracion',
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS detalle_pedido (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 pedido_id INT UNSIGNED NOT NULL,
 producto_id INT UNSIGNED NOT NULL,
 cantidad INT UNSIGNED NOT NULL,
 precio_unitario DECIMAL(10,2) NOT NULL,
 FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
 FOREIGN KEY (producto_id) REFERENCES productos(id)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS mensajes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 correo VARCHAR(190) NOT NULL,
 asunto VARCHAR(140) NOT NULL,
 mensaje TEXT NOT NULL,
 usuario_id INT UNSIGNED NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO productos (id,nombre,categoria,descripcion,precio,stock,imagen,etiqueta) VALUES
(1,'Camiseta Training Essential','Hombre','Camiseta ligera para sesiones de entrenamiento.',549,22,'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?w=900&auto=format&fit=crop&q=85','MÁS VENDIDO'),
(2,'Sudadera Everyday Oversized','Hombre','Corte amplio y textura cómoda para el día a día.',1199,17,'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=900&auto=format&fit=crop&q=85','NUEVO'),
(3,'Shorts Performance','Hombre','Libertad de movimiento y tejido ligero.',699,30,'https://images.unsplash.com/photo-1517438476312-10d79c077509?w=900&auto=format&fit=crop&q=85',''),
(4,'Leggings Seamless','Mujer','Diseño deportivo de cintura alta y ajuste cómodo.',999,24,'https://images.unsplash.com/photo-1506629905607-d9b297d9ce3b?w=900&auto=format&fit=crop&q=85','POPULAR'),
(5,'Top Active Support','Mujer','Top de entrenamiento versátil con estilo minimalista.',649,15,'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=900&auto=format&fit=crop&q=85',''),
(6,'Joggers Studio','Mujer','Pantalones deportivos con tacto suave.',1099,19,'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=900&auto=format&fit=crop&q=85','NUEVO'),
(7,'Bolsa de gimnasio Active','Accesorios','Bolsa compacta para guardar tus accesorios deportivos.',799,12,'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=900&auto=format&fit=crop&q=85',''),
(8,'Botella de entrenamiento','Accesorios','Botella reutilizable de estilo deportivo.',349,35,'https://images.unsplash.com/photo-1602143407151-7111542de6e8?w=900&auto=format&fit=crop&q=85','')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre);
