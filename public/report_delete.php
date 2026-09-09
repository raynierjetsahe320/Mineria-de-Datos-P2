<?php
session_start();
require_once __DIR__ . '/../src/auth.php';
ensure_logged_in();
$user = current_user();
global $conexion;

$id = intval($_GET['id'] ?? 0);
if(!$id){ header('Location: report_list.php'); exit; }
$res = mysqli_query($conexion, "SELECT * FROM incidencias WHERE id=$id LIMIT 1");
if(!$res || mysqli_num_rows($res)==0){ header('Location: report_list.php'); exit; }
$r = mysqli_fetch_assoc($res);
if($user['rol']!=='admin' && $r['user_id'] != $user['id']){ header('Location: report_list.php'); exit; }
mysqli_query($conexion, "DELETE FROM incidencias WHERE id=$id");
header('Location: report_list.php'); exit;
