<?php
session_start();
require_once __DIR__ . '/../src/auth.php';
ensure_logged_in();
$user = current_user();
require_once __DIR__ . '/../templates/header.php';
global $conexion;

$errors = [];
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if($current === '' || $new === '' || $confirm === ''){
        $errors[] = 'Todos los campos son obligatorios.';
    } elseif($new !== $confirm){
        $errors[] = 'La nueva contraseña y su confirmación no coinciden.';
    } else {
        // verificar contraseña actual
        $res = mysqli_query($conexion, "SELECT password FROM usuarios WHERE id=".intval($user['id'])." LIMIT 1");
        $row = mysqli_fetch_assoc($res);
        $stored = $row['password'] ?? '';
        $ok = false;
        if(password_verify($current, $stored)) $ok = true;
        if(!$ok && $stored === $current) $ok = true; // legacy plain text

        if(!$ok){
            $errors[] = 'La contraseña actual es incorrecta.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $esc = mysqli_real_escape_string($conexion, $hash);
            mysqli_query($conexion, "UPDATE usuarios SET password='$esc' WHERE id=".intval($user['id']));
            echo '<div class="alert">Contraseña actualizada correctamente.</div>';
        }
    }
}
?>
<h2>Cambiar contraseña</h2>
<?php if(!empty($errors)): ?><div class="alert"><?php echo implode('<br>', $errors); ?></div><?php endif; ?>
<form method="POST" action="change_password.php">
    <label>Contraseña actual<br><input type="password" name="current_password" required></label><br>
    <label>Nueva contraseña<br><input type="password" name="new_password" required></label><br>
    <label>Confirmar nueva contraseña<br><input type="password" name="confirm_password" required></label><br>
    <button class="btn" type="submit">Actualizar contraseña</button>
</form>
<?php require_once __DIR__ . '/../templates/footer.php'; ?>