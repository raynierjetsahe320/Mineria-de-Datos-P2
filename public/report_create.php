<?php
session_start();
require_once __DIR__ . '/../src/auth.php';
ensure_logged_in();
$user = current_user();
global $conexion;

$id = intval($_GET['id'] ?? 0);
$titulo = '';$descripcion='';$edificio_id='';$salon_id='';$zona_id='';$prioridad='Media';$tipo='General';$estado='Abierta';
if($id){
    $res = mysqli_query($conexion, "SELECT * FROM incidencias WHERE id=$id LIMIT 1");
    if($res && mysqli_num_rows($res)>0){
        $row=mysqli_fetch_assoc($res);
        if($user['rol']!=='admin' && $row['user_id'] != $user['id']){ header('Location: report_list.php'); exit; }
        $titulo=$row['titulo']; $descripcion=$row['descripcion']; $edificio_id=$row['edificio_id']; $salon_id=$row['salon_id']; $zona_id=$row['zona_id']; $prioridad=$row['prioridad']; $tipo=$row['tipo']; $estado=$row['estado'];
    }
}

// cargar edificios
// cargar edificios y zonas
$edificios = mysqli_query($conexion, "SELECT * FROM edificios ORDER BY id");
$zonas = mysqli_query($conexion, "SELECT * FROM zonas WHERE nombre NOT LIKE 'A-%' ORDER BY nombre");

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $titulo = mysqli_real_escape_string($conexion, $_POST['titulo']);
    $descripcion = mysqli_real_escape_string($conexion, $_POST['descripcion']);
    // lugar unificado: formato e:{id} o z:{id}
    $lugar = $_POST['lugar'] ?? '';
    $edificio_id = 0; $zona_id = 0;
    if(strpos($lugar, 'e:') === 0) $edificio_id = intval(substr($lugar,2));
    if(strpos($lugar, 'z:') === 0) $zona_id = intval(substr($lugar,2));
    $salon_id = intval($_POST['salon_id'] ?? 0);
    $prioridad = mysqli_real_escape_string($conexion, $_POST['prioridad']);
    $tipo = mysqli_real_escape_string($conexion, $_POST['tipo']);

    // preparar valores NULLables para FK
    $salon_sql = $salon_id > 0 ? $salon_id : 'NULL';
    $edif_sql = $edificio_id > 0 ? $edificio_id : 'NULL';
    $zona_sql = $zona_id > 0 ? $zona_id : 'NULL';

    if($id){
        mysqli_query($conexion, "UPDATE incidencias SET titulo='$titulo', descripcion='$descripcion', edificio_id=$edif_sql, salon_id=$salon_sql, zona_id=$zona_sql, prioridad='$prioridad', tipo='$tipo', updated_at=NOW() WHERE id=$id");
    } else {
        $uid = intval($user['id']);
        mysqli_query($conexion, "INSERT INTO incidencias(titulo, descripcion, tipo, prioridad, estado, edificio_id, salon_id, zona_id, user_id) VALUES('$titulo','$descripcion','$tipo','$prioridad','Abierta',$edif_sql,$salon_sql,$zona_sql,$uid)");
    }
    header('Location: report_list.php'); exit;
}

require_once __DIR__ . '/../templates/header.php';

?>
<h2><?php echo $id ? 'Editar reporte' : 'Crear reporte'; ?></h2>
<form method="POST">
    <label>Título<br><input type="text" name="titulo" value="<?php echo htmlspecialchars($titulo); ?>" required></label><br>
    <label>Descripción<br><textarea name="descripcion" required><?php echo htmlspecialchars($descripcion); ?></textarea></label><br>
    <label>Lugar<br>
        <select name="lugar">
            <option value="">-- Selecciona edificio o zona --</option>
            <?php
            // primeros: edificios (prefijo E:)
            mysqli_data_seek($edificios, 0);
            while($e = mysqli_fetch_assoc($edificios)):
                $val = 'e:'.$e['id'];
                $sel = ($edificio_id && $edificio_id == $e['id']) ? 'selected' : '';
            ?>
                <option value="<?php echo $val; ?>" <?php echo $sel; ?>>E: <?php echo htmlspecialchars(fix_encoding($e['nombre'])); ?></option>
            <?php endwhile; ?>
            <?php
            // luego: zonas (prefijo Z:)
            mysqli_data_seek($zonas, 0);
            while($z = mysqli_fetch_assoc($zonas)):
                $val = 'z:'.$z['id'];
                $sel = ($zona_id && $zona_id == $z['id']) ? 'selected' : '';
            ?>
                <option value="<?php echo $val; ?>" <?php echo $sel; ?>>Z: <?php echo htmlspecialchars(fix_encoding($z['nombre'])); ?></option>
            <?php endwhile; ?>
        </select>
    </label><br>
    <label>Prioridad<br>
        <select name="prioridad">
            <option <?php if($prioridad=='Baja') echo 'selected'; ?>>Baja</option>
            <option <?php if($prioridad=='Media') echo 'selected'; ?>>Media</option>
            <option <?php if($prioridad=='Alta') echo 'selected'; ?>>Alta</option>
        </select>
    </label><br>
    <label>Tipo<br><input type="text" name="tipo" value="<?php echo htmlspecialchars($tipo); ?>"></label><br>
    <button class="btn" type="submit">Guardar</button>
</form>

<?php
// panel del mapa: combinamos edificios + zonas en un solo listado, excluyendo canchas/torres/estacionamiento/plaza
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
