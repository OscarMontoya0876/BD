# GYMSHARK / TRAINING STORE — proyecto escolar

Sitio de venta **de demostración**, inspirado en la estética de Gymshark. No es un sitio oficial, ni está afiliado con Gymshark. Los nombres, imágenes y precios son ilustrativos. Los productos no se cobran ni se envían.

## Tecnologías
- HTML5 + CSS3 + JavaScript (diseño adaptable)
- PHP 8.1 o superior (formularios, sesiones, autenticación, carrito)
- MySQL o MariaDB (tablas SQL)
- PDO, consultas preparadas, CSRF, contraseñas con `password_hash` y `password_verify`

## Instalar usando XAMPP en Windows
1. Instala XAMPP y abre **XAMPP Control Panel**.
2. Enciende los módulos **Apache** y **MySQL**.
3. Extrae la carpeta `gymshark_tienda` en `C:\xampp\htdocs\` (sin otra carpeta intermedia).
4. Abre `http://localhost/phpmyadmin/` en tu navegador.
5. En la pestaña **Importar**, selecciona `gymshark_tienda/database.sql` y ejecuta la importación. Se creará la base `gymshark_escuela` con 5 tablas y 8 productos de prueba.
6. Revisa `config.php`. En un XAMPP estándar, usuario `root` sin contraseña; si lo configuraste diferente, actualiza los valores.
7. Abre **http://localhost/gymshark_tienda/** en el navegador.

**Importante:** no abrir `index.php` como archivo local (`file://`). Debe ejecutarse con Apache y PHP. Las fotografías de ejemplo y las fuentes se cargan por Internet.

## Cómo demostrar al profesor
1. Entra a **Crear cuenta** y registra un nombre, correo y contraseña de al menos 8 caracteres. Los datos se guardan en `usuarios`; la contraseña se guarda cifrada mediante hash.
2. Cierra sesión e **inicia sesión** para comprobar que el registro funciona.
3. Filtra productos, presiona **Añadir +**, cambia cantidades en **Bolsa** y guarda un pedido de prueba. Se registrará en `pedidos` y `detalle_pedido` y cambiará el stock en `productos`.
4. Abre **Mis pedidos** para ver el historial del usuario autenticado.
5. Abre **Contacto**, envía un mensaje y comprueba en phpMyAdmin que aparece en la tabla `mensajes`.

## Esquema SQL
- `usuarios`: registro e inicio de sesión
- `productos`: inventario, descripción, precio y fotografía
- `pedidos`: historial de pedidos vinculados al usuario
- `detalle_pedido`: productos y cantidades por pedido
- `mensajes`: formulario de contacto conectado a base de datos

## Seguridad y alcance
- SQL con parámetros enlazados; se evita inyectar consultas desde los formularios.
- Contraseñas con hash, nunca texto plano.
- Token CSRF para cada operación POST.
- HTML escapado al imprimir datos del servidor.
- Transacciones y bloqueos de filas durante el registro de pedidos.
- Este proyecto es una **maqueta funcional para aprendizaje local**: NO incluye pagos, comprobación de email, recuperación de contraseña, panel administrativo ni gestión real de envíos. Para publicarse comercialmente necesitaría HTTPS, protecciones adicionales, textos legales, autorización de marca y un procesador de pagos seguro.

## Datos de la tarea
Tema: Tienda Gymshark de demostración. Materia: desarrollo web y bases de datos SQL.

## Compatibilidad con PHP en XAMPP
Se eliminaron los tipos `mixed` y `never` de index.php para ofrecer compatibilidad con PHP 7.4, 8.0 y posteriores. Si actualizas un proyecto anterior, reemplaza `index.php` por el incluido aquí. No borres las tablas de MySQL.
