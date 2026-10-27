<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function money(float $n): string { return '$' . number_format($n, 2) . ' MXN'; }
function signed(): bool { return isset($_SESSION['user_id']); }
function flash(string $message, string $type='ok'): void { $_SESSION['flash']=['message'=>$message,'type'=>$type]; }
function jump(string $url): void { header('Location: '.$url, true, 303); exit; }
function csrf(): string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function qty(): int { return array_sum($_SESSION['cart'] ?? []); }
$page = (string)($_GET['page'] ?? 'inicio');
$valid = ['inicio','catalogo','acceso','registro','carrito','pedidos','contacto'];
if (!in_array($page,$valid,true)) $page='inicio';
try { $pdo=db(); }
catch (PDOException $exception) { http_response_code(503); echo '<h2>No se pudo conectar con MySQL</h2><p>Importa <code>database.sql</code> en phpMyAdmin y revisa <code>config.php</code>.</p>'; exit; }
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) { http_response_code(403); exit('Solicitud inválida.'); }
    $action=(string)($_POST['action'] ?? '');
    try {
        if ($action==='registro') {
            $name=trim((string)($_POST['nombre']??'')); $email=strtolower(trim((string)($_POST['correo']??'')));
            $pass=(string)($_POST['password']??'');
            if (mb_strlen($name)<2 || mb_strlen($name)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($pass)<8 || strlen($pass)>72) throw new RuntimeException('Revisa tu nombre, correo y contraseña (mínimo 8 caracteres).');
            $stmt=$pdo->prepare('INSERT INTO usuarios (nombre,correo,password_hash) VALUES (?,?,?)');
            try { $stmt->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT)]); }
            catch (PDOException $ex) { if ($ex->getCode()==='23000') throw new RuntimeException('Ese correo ya está registrado.'); throw $ex; }
            session_regenerate_id(true); $_SESSION['user_id']=(int)$pdo->lastInsertId(); $_SESSION['user_name']=$name;
            flash('¡Cuenta creada! Ya puedes realizar tu pedido de demostración.'); jump('index.php?page=catalogo');
        }
        if ($action==='login') {
            $email=strtolower(trim((string)($_POST['correo']??''))); $pass=(string)($_POST['password']??'');
            $stmt=$pdo->prepare('SELECT id,nombre,password_hash FROM usuarios WHERE correo=?'); $stmt->execute([$email]); $u=$stmt->fetch();
            if (!$u || !password_verify($pass,$u['password_hash'])) throw new RuntimeException('Correo o contraseña incorrectos.');
            session_regenerate_id(true); $_SESSION['user_id']=(int)$u['id']; $_SESSION['user_name']=$u['nombre'];
            flash('Bienvenido de nuevo, '.$u['nombre'].'.'); jump('index.php?page=catalogo');
        }
        if ($action==='logout') { $_SESSION=[]; session_regenerate_id(true); $_SESSION['csrf']=bin2hex(random_bytes(32)); flash('Sesión cerrada.'); jump('index.php'); }
        if ($action==='add') {
            $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT); $amount=filter_var($_POST['cantidad']??1,FILTER_VALIDATE_INT);
            if (!$id || !$amount || $amount<1 || $amount>10) throw new RuntimeException('Cantidad no válida.');
            $s=$pdo->prepare('SELECT stock FROM productos WHERE id=?'); $s->execute([$id]); $p=$s->fetch();
            if (!$p) throw new RuntimeException('El producto no existe.');
            $new=($_SESSION['cart'][$id]??0)+$amount;
            if ($new>(int)$p['stock']) throw new RuntimeException('No hay suficiente inventario.');
            $_SESSION['cart'][$id]=$new; flash('Producto añadido al carrito.'); jump('index.php?page=carrito');
        }
        if ($action==='update') {
            $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT); $amount=filter_var($_POST['cantidad']??null,FILTER_VALIDATE_INT);
            if (!$id || $amount===false || $amount===null || $amount<0 || $amount>20) throw new RuntimeException('Cantidad no válida.');
            $s=$pdo->prepare('SELECT stock FROM productos WHERE id=?'); $s->execute([$id]); $stock=$s->fetchColumn();
            if ($stock===false || $amount>(int)$stock) throw new RuntimeException('Stock insuficiente.');
            if ($amount===0) unset($_SESSION['cart'][$id]); else $_SESSION['cart'][$id]=$amount;
            flash('Carrito actualizado.'); jump('index.php?page=carrito');
        }
        if ($action==='checkout') {
            if (!signed()) { flash('Inicia sesión para guardar tu pedido.','error'); jump('index.php?page=acceso'); }
            $cart=$_SESSION['cart']??[]; if (!$cart) throw new RuntimeException('El carrito está vacío.');
            $pdo->beginTransaction(); $items=[]; $total=0.0;
            $s=$pdo->prepare('SELECT id,nombre,precio,stock FROM productos WHERE id=? FOR UPDATE');
            foreach ($cart as $id=>$amount) {
                if (!is_int($amount) || $amount<1) throw new RuntimeException('Cantidad no válida.');
                $s->execute([(int)$id]); $p=$s->fetch();
                if (!$p || (int)$p['stock']<$amount) throw new RuntimeException('Inventario insuficiente para tu pedido.');
                $items[]=['id'=>(int)$id,'qty'=>$amount,'price'=>(float)$p['precio']]; $total+=(float)$p['precio']*$amount;
            }
            $s=$pdo->prepare('INSERT INTO pedidos(usuario_id,total) VALUES (?,?)'); $s->execute([$_SESSION['user_id'],$total]); $orderId=(int)$pdo->lastInsertId();
            $detail=$pdo->prepare('INSERT INTO detalle_pedido(pedido_id,producto_id,cantidad,precio_unitario) VALUES (?,?,?,?)');
            $stock=$pdo->prepare('UPDATE productos SET stock=stock-? WHERE id=?');
            foreach ($items as $item) { $detail->execute([$orderId,$item['id'],$item['qty'],$item['price']]); $stock->execute([$item['qty'],$item['id']]); }
            $pdo->commit(); $_SESSION['cart']=[];
            flash('Pedido de demostración #'.$orderId.' guardado en MySQL. No se realizó ningún cobro.'); jump('index.php?page=pedidos');
        }
        if ($action==='contacto') {
            $name=trim((string)($_POST['nombre']??'')); $email=trim((string)($_POST['correo']??''));
            $subject=trim((string)($_POST['asunto']??'')); $msg=trim((string)($_POST['mensaje']??''));
            if (mb_strlen($name)<2 || mb_strlen($name)>100 || !filter_var($email,FILTER_VALIDATE_EMAIL) || mb_strlen($subject)<3 || mb_strlen($subject)>140 || mb_strlen($msg)<10 || mb_strlen($msg)>3000) throw new RuntimeException('Completa correctamente todos los datos del formulario.');
            $s=$pdo->prepare('INSERT INTO mensajes(nombre,correo,asunto,mensaje,usuario_id) VALUES (?,?,?,?,?)');
            $s->execute([$name,$email,$subject,$msg,$_SESSION['user_id']??null]); flash('Mensaje enviado y guardado en la base de datos.'); jump('index.php?page=contacto');
        }
        throw new RuntimeException('Acción no reconocida.');
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash($ex instanceof RuntimeException ? $ex->getMessage() : 'No se pudo completar la operación.','error');
        jump('index.php?page='.rawurlencode($page));
    }
}
$flash=$_SESSION['flash']??null; unset($_SESSION['flash']);
$filter=(string)($_GET['categoria']??'Todos');
if (!in_array($filter,['Todos','Hombre','Mujer','Accesorios'],true)) $filter='Todos';
if ($page==='catalogo' || $page==='inicio') {
    if ($page==='catalogo' && $filter!=='Todos') { $s=$pdo->prepare('SELECT * FROM productos WHERE categoria=? ORDER BY id');$s->execute([$filter]);$products=$s->fetchAll(); }
    else $products=$pdo->query('SELECT * FROM productos ORDER BY id')->fetchAll();
}
$cartRows=[]; $total=0.0;
if ($page==='carrito' && !empty($_SESSION['cart'])) {
    $s=$pdo->prepare('SELECT * FROM productos WHERE id=?');
    foreach ($_SESSION['cart'] as $id=>$count) { $s->execute([(int)$id]); if ($p=$s->fetch()) { $p['cantidad']=$count; $cartRows[]=$p; $total+=(float)$p['precio']*$count; } }
}
$orders=[];
if ($page==='pedidos' && signed()) { $s=$pdo->prepare('SELECT id,total,estado,creado_en FROM pedidos WHERE usuario_id=? ORDER BY id DESC');$s->execute([$_SESSION['user_id']]);$orders=$s->fetchAll(); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="description" content="Tienda escolar inspirada en Gymshark. Proyecto PHP y MySQL."><title>GYMSHARK / Training Store — Demo académica</title><link rel="stylesheet" href="assets/style.css"><script src="assets/app.js" defer></script></head><body>
<div class="topbar">PROYECTO ESCOLAR · CATÁLOGO Y PRECIOS DE DEMOSTRACIÓN · SIN PAGOS REALES</div>
<header class="site-header"><a class="brand" href="index.php" aria-label="Gymshark inicio"><span class="mark">G<span class="spark">✦</span></span>GYMSHARK<span class="brand-dot">.</span></a><nav class="desktop-nav" aria-label="Navegación principal"><a href="index.php" class="<?= $page==='inicio'?'active':'' ?>">INICIO</a><a href="index.php?page=catalogo" class="<?= $page==='catalogo'?'active':'' ?>">COLECCIÓN</a><a href="index.php?page=catalogo&amp;categoria=Hombre">HOMBRE</a><a href="index.php?page=catalogo&amp;categoria=Mujer">MUJER</a><a href="index.php?page=contacto" class="<?= $page==='contacto'?'active':'' ?>">CONTACTO</a></nav><div class="header-actions"><?php if(signed()): ?><a class="account" href="index.php?page=pedidos">Hola, <?=e(explode(' ',(string)$_SESSION['user_name'])[0])?></a><form method="post" class="inline"><?=csrf()?><input type="hidden" name="action" value="logout"><button class="text-button" type="submit">Salir</button></form><?php else: ?><a class="account" href="index.php?page=acceso">Ingresar</a><?php endif; ?><a class="cart-link" href="index.php?page=carrito" aria-label="Ver carrito">BOLSA <span><?=qty()?></span></a><button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false">☰</button></div></header>
<div class="mobile-nav" id="mobile-nav"><a href="index.php">Inicio</a><a href="index.php?page=catalogo">Colección completa</a><a href="index.php?page=catalogo&amp;categoria=Hombre">Hombre</a><a href="index.php?page=catalogo&amp;categoria=Mujer">Mujer</a><a href="index.php?page=catalogo&amp;categoria=Accesorios">Accesorios</a><a href="index.php?page=contacto">Contacto</a></div>
<?php if ($flash): ?><div class="notice <?= $flash['type']==='error'?'bad':'' ?>" role="status"><?=e($flash['message'])?><button aria-label="Cerrar aviso" onclick="this.parentElement.remove()">×</button></div><?php endif; ?>
<main>
<?php if ($page==='inicio'): ?>
<section class="hero"><div class="hero-bg"></div><div class="hero-content"><div class="eyebrow"><span class="line"></span> THE NEW STANDARD / 2026</div><h1>BUILT FOR<br><em>THE WORK.</em></h1><p>Disciplina, movimiento y una nueva energía. Descubre una experiencia de compra deportiva diseñada para quienes no se detienen.</p><a class="btn primary" href="index.php?page=catalogo">EXPLORAR COLECCIÓN <span>↗</span></a><div class="hero-footer"><span>MOVE WITH PURPOSE</span><span>01 / 03</span></div></div><div class="hero-vertical">TRAIN DIFFERENT. EVERY DAY.</div></section>
<section class="statement"><span class="overline">NO EXCUSES. JUST PROGRESS.</span><h2>EL ESFUERZO<br>TIENE <em>ESTILO.</em></h2><p>Esenciales de entrenamiento diseñados para acompañarte en cada repetición. Esta es una tienda demostrativa para una práctica escolar.</p></section>
<section class="feature-grid"><a class="feature feature-men" href="index.php?page=catalogo&amp;categoria=Hombre"><div><span>01 / COLLECTION</span><h3>HOMBRE</h3><strong>DESCUBRIR ↗</strong></div></a><a class="feature feature-women" href="index.php?page=catalogo&amp;categoria=Mujer"><div><span>02 / COLLECTION</span><h3>MUJER</h3><strong>DESCUBRIR ↗</strong></div></a></section>
<section class="catalog-section"><div class="section-top"><div><span class="overline dark">SELECCIÓN DE TEMPORADA</span><h2>FAVORITOS <em>DEL GYM.</em></h2></div><a class="underline-link" href="index.php?page=catalogo">VER TODO EL CATÁLOGO ↗</a></div><div class="product-grid"><?php foreach(array_slice($products,0,4) as $p): ?><?php include __DIR__.'/product-card.php'; ?><?php endforeach; ?></div></section>
<section class="quote-strip"><span>STRONGER EVERY DAY</span><span>✳</span><span>STRONGER EVERY DAY</span><span>✳</span></section>
<?php elseif ($page==='catalogo'): ?>
<section class="page-intro"><span class="overline dark">GEAR UP. SHOW UP.</span><h1>LA <em>COLECCIÓN.</em></h1><p>Prendas y accesorios para cada objetivo. Productos y precios de ejemplo en MXN.</p></section><section class="catalog-section inside"><div class="filters" role="navigation" aria-label="Filtrar productos"><?php foreach(['Todos','Hombre','Mujer','Accesorios'] as $c): ?><a class="filter <?= $filter===$c?'selected':'' ?>" href="index.php?page=catalogo&amp;categoria=<?=urlencode($c)?>"><?=e($c)?></a><?php endforeach; ?></div><div class="product-grid"><?php foreach($products as $p): ?><?php include __DIR__.'/product-card.php'; ?><?php endforeach; ?></div></section>
<?php elseif ($page==='registro' || $page==='acceso'): ?>
<section class="auth-layout"><div class="auth-image"><div><span class="overline">MEMBERS ONLY</span><h2>YOUR<br>BEST<br><em>STARTS<br>HERE.</em></h2></div></div><div class="auth-panel"><span class="overline dark">TU CUENTA / GYMSHARK DEMO</span><h1><?= $page==='registro'?'CREA TU <em>CUENTA.</em>':'BIENVENIDO <em>DE NUEVO.</em>' ?></h1><p><?= $page==='registro'?'Regístrate para guardar tus pedidos de práctica.':'Accede para revisar tus pedidos de demostración.' ?></p><form method="post" class="form-stack"><?=csrf()?><input type="hidden" name="action" value="<?= $page==='registro'?'registro':'login' ?>"><?php if ($page==='registro'): ?><label>Nombre completo<input name="nombre" type="text" autocomplete="name" maxlength="100" placeholder="Tu nombre" required></label><?php endif; ?><label>Correo electrónico<input name="correo" type="email" autocomplete="email" maxlength="190" placeholder="nombre@correo.com" required></label><label>Contraseña<input name="password" type="password" minlength="8" maxlength="72" autocomplete="<?= $page==='registro'?'new-password':'current-password' ?>" placeholder="Mínimo 8 caracteres" required></label><button class="btn primary full" type="submit"><?= $page==='registro'?'REGISTRARME ↗':'INICIAR SESIÓN ↗' ?></button></form><p class="auth-switch"><?= $page==='registro'?'¿Ya tienes cuenta? <a href="index.php?page=acceso">Inicia sesión</a>':'¿Aún no tienes cuenta? <a href="index.php?page=registro">Regístrate aquí</a>' ?></p></div></section>
<?php elseif ($page==='carrito'): ?>
<section class="simple-page"><span class="overline dark">CHECKOUT / DEMO</span><h1>TU <em>BOLSA.</em></h1><?php if (!$cartRows): ?><div class="empty"><h2>Tu bolsa está vacía.</h2><p>Encuentra tus favoritos y comienza una nueva rutina.</p><a href="index.php?page=catalogo" class="btn primary">IR A LA COLECCIÓN ↗</a></div><?php else: ?><div class="checkout-grid"><div class="cart-list"><?php foreach($cartRows as $p): ?><article class="cart-item"><img src="<?=e($p['imagen'])?>" alt="<?=e($p['nombre'])?>"><div class="cart-info"><span class="overline dark"><?=e($p['categoria'])?></span><h3><?=e($p['nombre'])?></h3><p><?=money((float)$p['precio'])?></p><form method="post" class="quantity-form"><?=csrf()?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><label>Cant. <input type="number" min="0" max="<?= min(20,(int)$p['stock']) ?>" name="cantidad" value="<?= (int)$p['cantidad'] ?>" required></label><button type="submit">ACTUALIZAR</button><small>0 para quitar</small></form></div><b><?=money((float)$p['precio']*$p['cantidad'])?></b></article><?php endforeach; ?></div><aside class="summary"><span class="overline dark">RESUMEN</span><h2>TU PEDIDO</h2><div class="summary-row"><span>Artículos</span><strong><?=qty()?></strong></div><div class="summary-row"><span>Envío</span><strong>No incluido</strong></div><div class="summary-total"><span>TOTAL DEMO</span><strong><?=money($total)?></strong></div><form method="post"><?=csrf()?><input type="hidden" name="action" value="checkout"><button class="btn primary full" type="submit"><?=signed()?'GUARDAR PEDIDO ↗':'INGRESAR Y CONTINUAR ↗'?></button></form><p>Simulación académica. No existe pasarela de pago ni envío real.</p></aside></div><?php endif; ?></section>
<?php elseif ($page==='pedidos'): ?>
<section class="simple-page"><span class="overline dark">MI CUENTA</span><h1>MIS <em>PEDIDOS.</em></h1><?php if(!signed()): ?><div class="empty"><p>Inicia sesión para ver el historial.</p><a class="btn primary" href="index.php?page=acceso">INGRESAR ↗</a></div><?php elseif (!$orders): ?><div class="empty"><h2>Todavía no tienes pedidos.</h2><a class="btn primary" href="index.php?page=catalogo">VER PRODUCTOS ↗</a></div><?php else: ?><div class="orders"><?php foreach($orders as $o): ?><div class="order"><div><span class="overline dark">PEDIDO #<?= (int)$o['id'] ?></span><h3><?=e($o['creado_en'])?></h3></div><div><span class="demo-tag">DEMOSTRACIÓN</span><strong><?=money((float)$o['total'])?></strong></div></div><?php endforeach; ?></div><?php endif; ?></section>
<?php elseif ($page==='contacto'): ?>
<section class="contact-layout"><div class="contact-copy"><span class="overline">LET'S TALK / CONTACTO</span><h1>HABLEMOS<br><em>DEL CAMBIO.</em></h1><p>¿Tienes preguntas sobre el catálogo de demostración? Escríbenos. El formulario guarda los datos directamente en una tabla SQL.</p><span class="contact-ornament">✳</span></div><div class="contact-form"><span class="overline dark">FORMULARIO CON MYSQL</span><h2>ENVÍA UN MENSAJE.</h2><form method="post" class="form-stack"><?=csrf()?><input type="hidden" name="action" value="contacto"><label>Nombre<input name="nombre" maxlength="100" placeholder="Tu nombre" autocomplete="name" value="<?=e($_SESSION['user_name']??'')?>" required></label><label>Correo<input type="email" name="correo" maxlength="190" autocomplete="email" placeholder="correo@ejemplo.com" required></label><label>Asunto<input name="asunto" maxlength="140" placeholder="¿En qué te ayudamos?" required></label><label>Mensaje<textarea name="mensaje" minlength="10" maxlength="3000" rows="5" placeholder="Escribe tu mensaje aquí…" required></textarea></label><button class="btn primary full" type="submit">ENVIAR MENSAJE ↗</button></form></div></section>
<?php endif; ?>
</main><footer class="footer"><div class="footer-upper"><div><a href="index.php" class="brand footer-brand">GYMSHARK<span class="brand-dot">.</span></a><p>BUILT FOR THE WORK.<br>Diseño y desarrollo web.</p></div><div><h4>EXPLORA</h4><a href="index.php?page=catalogo">Colección</a><a href="index.php?page=catalogo&amp;categoria=Hombre">Hombre</a><a href="index.php?page=catalogo&amp;categoria=Mujer">Mujer</a></div><div><h4>TU CUENTA</h4><a href="index.php?page=acceso">Iniciar sesión</a><a href="index.php?page=registro">Crear cuenta</a><a href="index.php?page=pedidos">Mis pedidos</a></div><div><h4>INFORMACIÓN</h4><a href="index.php?page=contacto">Contacto</a><a href="https://www.gymshark.com/es-US" target="_blank" rel="noopener noreferrer">Sitio oficial ↗</a></div></div><div class="footer-bottom"><span>PROYECTO ESCOLAR · NO AFILIADO A GYMSHARK · SIN VENTAS REALES</span><span>OSCAR DANIEL MONTOYA RESENDIS · 5°J · 0876</span><span>© <?=date('Y')?> DEMO ACADÉMICA</span></div></footer>
</body></html>
