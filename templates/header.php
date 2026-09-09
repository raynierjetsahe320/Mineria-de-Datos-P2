<?php
if(session_status() === PHP_SESSION_NONE) session_start();
// asegurar que la respuesta sea UTF-8
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/../src/auth.php';
$user = current_user();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="/css/style.css">
  <title>FES Aragón - Plataforma</title>
  <style>
    /* Fallback mínimo para que la página se vea bien si el CSS externo no carga */
    :root{font-family:Inter, system-ui, Arial, sans-serif;color:#0f172a}
    body{margin:0;background:#f7fbff}
    .topbar{background:linear-gradient(90deg,#2563eb,#1e3a8a);color:#fff;padding:12px}
    .topbar .container{display:flex;justify-content:space-between;align-items:center}
    .container{max-width:1100px;margin:0 auto;padding:18px}
    a{color:#2563eb;text-decoration:underline}
    h2{color:#1e3a8a}
    .btn{background:#2563eb;color:#fff;padding:6px 10px;border-radius:6px;text-decoration:none}
    .table{width:100%;border-collapse:collapse}
    .table th,.table td{padding:8px;border-bottom:1px solid #e6eefc;text-align:left}
    .alert{background:#fff4f4;color:#9b1c1c;padding:8px;border-radius:6px}
  </style>
</head>
<body>
<header class="topbar">
  <div class="container">
    <div class="brand">FES Aragón</div>
    <nav>
      <?php if($user): ?>
  <span class="muted">Hola, <?php echo htmlspecialchars(fix_encoding($user['nombre'])); ?></span>
        <?php if($user['rol'] === 'admin'): ?><a href="resultados.php">Resultados</a><a href="historial_abiertas.php">Historial</a><?php endif; ?>
      <?php else: ?>
        <a href="login.php">Iniciar sesión</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="container">
