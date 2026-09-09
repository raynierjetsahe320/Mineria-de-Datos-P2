<?php
// Funciones simples de autenticación y utilidades
require_once __DIR__ . '/../config/database.php';

function find_user_by_email($email){
    global $conexion;
    $email_safe = mysqli_real_escape_string($conexion, $email);
    $res = mysqli_query($conexion, "SELECT * FROM usuarios WHERE correo='$email_safe' LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) return mysqli_fetch_assoc($res);
    return null;
}

function find_user_by_account($cuenta){
    global $conexion;
    $cuenta_safe = mysqli_real_escape_string($conexion, $cuenta);
    $res = mysqli_query($conexion, "SELECT * FROM usuarios WHERE cuenta='$cuenta_safe' LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) return mysqli_fetch_assoc($res);
    return null;
}

function create_survey_user($cuenta){
    global $conexion;
    $cuenta_safe = mysqli_real_escape_string($conexion, $cuenta);
    $correo = mysqli_real_escape_string($conexion, $cuenta . '@encuesta.local');
    $password = mysqli_real_escape_string($conexion, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT));
    $sql = "INSERT INTO usuarios(nombre, correo, cuenta, password, rol) VALUES ('Participante', '$correo', '$cuenta_safe', '$password', 'alumno')";
    if(mysqli_query($conexion, $sql)) return mysqli_insert_id($conexion);
    return 0;
}

function get_anonymous_survey_user(){
    global $conexion;
    $correo = mysqli_real_escape_string($conexion, 'anonimo@encuesta.local');
    $res = mysqli_query($conexion, "SELECT id FROM usuarios WHERE correo='$correo' LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) return intval(mysqli_fetch_assoc($res)['id']);

    $password = mysqli_real_escape_string($conexion, password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT));
    $sql = "INSERT INTO usuarios(nombre, correo, cuenta, password, rol) VALUES ('Participante anónimo', '$correo', 'ANONIMO', '$password', 'alumno')";
    if(mysqli_query($conexion, $sql)) return mysqli_insert_id($conexion);
    return 0;
}

function register_user($nombre, $correo, $cuenta, $password){
    global $conexion;
    $nombre = mysqli_real_escape_string($conexion, $nombre);
    $correo = mysqli_real_escape_string($conexion, $correo);
    $cuenta = mysqli_real_escape_string($conexion, $cuenta);
    // almacenar password con hash
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $hash = mysqli_real_escape_string($conexion, $hash);
    $sql = "INSERT INTO usuarios(nombre, correo, cuenta, password) VALUES ('$nombre','$correo','$cuenta','$hash')";
    return mysqli_query($conexion, $sql);
}

function ensure_logged_in(){
    if(!isset($_SESSION['user_id'])){
        header('Location: login.php');
        exit;
    }
}

function current_user(){
    global $conexion;
    if(!isset($_SESSION['user_id'])) return null;
    $id = intval($_SESSION['user_id']);
    $res = mysqli_query($conexion, "SELECT * FROM usuarios WHERE id=$id LIMIT 1");
    if($res && mysqli_num_rows($res) > 0) return mysqli_fetch_assoc($res);
    return null;
}

// Corrige mojibake común (ej. "BaÃ±o" -> "Baño") usando mapas simples
function fix_encoding(string $s): string {
    if($s === null) return '';
    $map = [
        'Ã¡' => 'á', 'Ã©' => 'é', 'Ã­' => 'í', 'Ã³' => 'ó', 'Ãº' => 'ú',
        'Ã±' => 'ñ', 'Ã‘' => 'Ñ', 'Ã‰' => 'É', 'Ã' => 'Á',
        'Ã–' => 'Ö', 'Ã–' => 'Ö', 'â' => '-', 'â' => '—',
        'â' => '"', 'â' => '"', 'â' => "'", 'Â°' => '°'
    ];
    return strtr($s, $map);
}
