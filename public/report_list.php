<?php
session_start();
require_once __DIR__ . '/../src/auth.php';
ensure_logged_in();
$user = current_user();

global $conexion;
// manejar cambio de estado (solo admin)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_state']) && $user['rol'] === 'admin'){
  $new = mysqli_real_escape_string($conexion, $_POST['new_state'] ?? '');
  $rid = intval($_POST['report_id'] ?? 0);
  if($rid > 0 && in_array($new, ['Abierta','En proceso','Pendiente','Cerrada'])){
    mysqli_query($conexion, "UPDATE incidencias SET estado='".$new."' WHERE id=$rid");
  }
  header('Location: report_list.php'); exit;
}

require_once __DIR__ . '/../templates/header.php';
if($user['rol'] === 'admin'){
  // compute a single 'lugar' column prefixed with E: or Z:
  $q = mysqli_query($conexion, "SELECT i.*, u.nombre as autor, e.nombre as edificio, z.nombre as zona, CASE WHEN z.nombre IS NOT NULL AND z.nombre<>'' THEN CONCAT('Z: ', z.nombre) WHEN e.nombre IS NOT NULL AND e.nombre<>'' THEN CONCAT('E: ', e.nombre) ELSE '' END as lugar FROM incidencias i LEFT JOIN usuarios u ON u.id=i.user_id LEFT JOIN edificios e ON e.id=i.edificio_id LEFT JOIN zonas z ON z.id=i.zona_id ORDER BY i.created_at DESC");
} else {
  $uid = intval($user['id']);
  $q = mysqli_query($conexion, "SELECT i.*, e.nombre as edificio, z.nombre as zona, CASE WHEN z.nombre IS NOT NULL AND z.nombre<>'' THEN CONCAT('Z: ', z.nombre) WHEN e.nombre IS NOT NULL AND e.nombre<>'' THEN CONCAT('E: ', e.nombre) ELSE '' END as lugar FROM incidencias i LEFT JOIN edificios e ON e.id=i.edificio_id LEFT JOIN zonas z ON z.id=i.zona_id WHERE i.user_id=$uid ORDER BY i.created_at DESC");
}

?>
<h2>Reportes</h2>
<a class="btn" href="report_create.php">Nuevo reporte</a>
<a class="btn" href="change_password.php">Cambiar contraseña</a>
<table class="table">
  <thead><tr><th>ID</th><th>Título</th><th>Lugar</th><th>Prioridad</th><th>Estado</th><th>Autor</th><th>Acciones</th></tr></thead>
  <tbody>
    <?php while($row = mysqli_fetch_assoc($q)): ?>
      <tr>
        <td><?php echo $row['id']; ?></td>
  <td><?php echo htmlspecialchars(fix_encoding($row['titulo'])); ?></td>
  <td><?php echo htmlspecialchars(fix_encoding($row['lugar'] ?? ($row['zona'] ?? $row['edificio'] ?? ''))); ?></td>
  <td><?php echo htmlspecialchars(fix_encoding($row['prioridad'])); ?></td>
  <td>
    <?php if($user['rol'] === 'admin'): ?>
      <form method="POST" style="display:inline">
        <input type="hidden" name="change_state" value="1">
        <input type="hidden" name="report_id" value="<?php echo intval($row['id']); ?>">
        <select name="new_state" onchange="this.form.submit()">
          <?php $states = ['Abierta','En proceso','Pendiente','Cerrada'];
            foreach($states as $s){
              $sel = ($row['estado']==$s) ? 'selected' : ''; echo "<option value=\"$s\" $sel>$s</option>";
            }
          ?>
        </select>
      </form>
    <?php else: ?>
      <?php echo htmlspecialchars(fix_encoding($row['estado'])); ?>
    <?php endif; ?>
  </td>
  <td><?php echo htmlspecialchars(fix_encoding($row['autor'] ?? $user['nombre'])); ?></td>
        <td>
          <a href="report_view.php?id=<?php echo $row['id']; ?>">Ver</a>
          <?php if($user['rol'] === 'admin' || $row['user_id'] == $user['id']): ?> |
            <a href="report_create.php?id=<?php echo $row['id']; ?>">Editar</a> |
            <a href="report_delete.php?id=<?php echo $row['id']; ?>" onclick="return confirm('Eliminar?')">Borrar</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
</table>

<?php
// panel mapa y lista: combinamos edificios + zonas en un solo listado, excluyendo canchas/torres/estacionamiento/plaza
$rb = mysqli_query($conexion, "SELECT tipo, id, nombre FROM (
  SELECT 'E' as tipo, e.id as id, e.nombre as nombre FROM edificios e
  WHERE e.nombre NOT LIKE '%Cancha%' AND e.nombre NOT LIKE '%Torres%' AND e.nombre NOT LIKE '%Estacionamiento%' AND e.nombre NOT LIKE '%Plaza%'
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
