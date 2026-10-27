-- Recuperación del catálogo para proyecto escolar Gymshark.
-- No elimina tablas, usuarios, mensajes ni pedidos.
USE gymshark_escuela;

INSERT INTO productos (id,nombre,categoria,descripcion,precio,stock,imagen,etiqueta) VALUES
(1,'Camiseta Training Essential','Hombre','Camiseta ligera para sesiones de entrenamiento.',549,22,'https://images.unsplash.com/photo-1503342217505-b0a15ec3261c?w=900&auto=format&fit=crop&q=85','MÁS VENDIDO'),
(2,'Sudadera Everyday Oversized','Hombre','Corte amplio y textura cómoda para el día a día.',1199,17,'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=900&auto=format&fit=crop&q=85','NUEVO'),
(3,'Shorts Performance','Hombre','Libertad de movimiento y tejido ligero.',699,30,'https://images.unsplash.com/photo-1517438476312-10d79c077509?w=900&auto=format&fit=crop&q=85',''),
(4,'Leggings Seamless','Mujer','Diseño deportivo de cintura alta y ajuste cómodo.',999,24,'https://images.unsplash.com/photo-1506629905607-d9b297d9ce3b?w=900&auto=format&fit=crop&q=85','POPULAR'),
(5,'Top Active Support','Mujer','Top de entrenamiento versátil con estilo minimalista.',649,15,'https://images.unsplash.com/photo-1518611012118-696072aa579a?w=900&auto=format&fit=crop&q=85',''),
(6,'Joggers Studio','Mujer','Pantalones deportivos con tacto suave.',1099,19,'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=900&auto=format&fit=crop&q=85','NUEVO'),
(7,'Bolsa de gimnasio Active','Accesorios','Bolsa compacta para guardar tus accesorios deportivos.',799,12,'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=900&auto=format&fit=crop&q=85',''),
(8,'Botella de entrenamiento','Accesorios','Botella reutilizable de estilo deportivo.',349,35,'https://images.unsplash.com/photo-1602143407151-7111542de6e8?w=900&auto=format&fit=crop&q=85','')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), categoria=VALUES(categoria), descripcion=VALUES(descripcion), precio=VALUES(precio), imagen=VALUES(imagen), etiqueta=VALUES(etiqueta);

