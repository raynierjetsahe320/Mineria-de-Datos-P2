<?php
session_start();
require_once __DIR__ . '/../src/auth.php';
ensure_logged_in();
$user = current_user();
require_once __DIR__ . '/../templates/header.php';
global $conexion;

$id = intval($_GET['id'] ?? 0);
if(!$id){ header('Location: report_list.php'); exit; }
$res = mysqli_query($conexion, "SELECT i.*, u.nombre as autor, e.nombre as edificio, s.nombre as salon, z.nombre as zona FROM incidencias i LEFT JOIN usuarios u ON u.id=i.user_id LEFT JOIN edificios e ON e.id=i.edificio_id LEFT JOIN salones s ON s.id=i.salon_id LEFT JOIN zonas z ON z.id=i.zona_id WHERE i.id=$id LIMIT 1");
if(!$res || mysqli_num_rows($res)==0){ echo '<div class="alert">Reporte no encontrado</div>'; require_once __DIR__.'/../templates/footer.php'; exit; }
$r = mysqli_fetch_assoc($res);

?>
<h2>Reporte #<?php echo $r['id']; ?> - <?php echo htmlspecialchars($r['titulo']); ?></h2>
<p><strong>Autor:</strong> <?php echo htmlspecialchars(fix_encoding($r['autor'])); ?></p>
<p><strong>Lugar:</strong> <?php
    $l = $r['zona'] ?? null;
    if($l && $l!==''){
        echo htmlspecialchars('Z: '.fix_encoding($l));
    } else {
        echo htmlspecialchars('E: '.fix_encoding($r['edificio'] ?? ''));
    }
?></p>
<p><strong>Salón:</strong> <?php echo htmlspecialchars(fix_encoding($r['salon'])); ?></p>
<p><strong>Prioridad:</strong> <?php echo htmlspecialchars(fix_encoding($r['prioridad'])); ?></p>
<p><strong>Estado:</strong> <?php echo htmlspecialchars(fix_encoding($r['estado'])); ?></p>
<p><strong>Descripción:</strong><br><?php echo nl2br(htmlspecialchars(fix_encoding($r['descripcion']))); ?></p>

<?php if($user['rol']==='admin' || $r['user_id']==$user['id']): ?>
    <a class="btn" href="report_create.php?id=<?php echo $r['id']; ?>">Editar</a>
    <a class="btn" href="report_delete.php?id=<?php echo $r['id']; ?>" onclick="return confirm('¿Eliminar reporte?')">Eliminar</a>
<?php endif; ?>

<?php
// mostrar mapa del campus y lista combinada: edificios + zonas (excluyendo canchas/torres/estacionamiento/plaza y A-*)
$rb = mysqli_query($conexion, "SELECT tipo, id, nombre FROM (
    SELECT 'E' as tipo, e.id as id, e.nombre as nombre FROM edificios e
    WHERE e.nombre NOT LIKE 'A-%' AND e.nombre NOT LIKE '%Cancha%' AND e.nombre NOT LIKE '%Torres%' AND e.nombre NOT LIKE '%Estacionamiento%' AND e.nombre NOT LIKE '%Plaza%'
    UNION ALL
    SELECT 'Z' as tipo, z.id as id, z.nombre as nombre FROM zonas z
    WHERE z.nombre NOT LIKE 'A-%' AND z.nombre NOT LIKE '%Cancha%' AND z.nombre NOT LIKE '%Torres%' AND z.nombre NOT LIKE '%Estacionamiento%' AND z.nombre NOT LIKE '%Plaza%'
) t ORDER BY FIELD(tipo,'E','Z'), id");
?>
<div class="panel" style="margin-top:18px">
    <h3 class="panel-title">Mapa del campus</h3>
    <div style="display:flex;gap:18px;align-items:flex-start">
        <div style="flex:1">
            <img src="assets/campus-map.png" alt="Mapa campus" style="max-width:100%;border-radius:8px;box-shadow:var(--shadow)">
        </div>
        <div style="width:320px;background:var(--card);padding:12px;border-radius:8px;box-shadow:var(--shadow)">
            <h4>Edificios</h4>
            <ol>
            <?php while($bb = mysqli_fetch_assoc($rb)): ?>
                <li><?php echo htmlspecialchars(fix_encoding(($bb['tipo']==='Z' ? 'Z: ' : 'E: ').$bb['nombre'])); ?></li>
            <?php endwhile; ?>
            </ol>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
