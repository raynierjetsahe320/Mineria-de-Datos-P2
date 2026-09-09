<?php
$conexion = mysqli_connect(
    "mysql",
    "root",
    "root",
    "fesaragon"
);

if(!$conexion){
    die("Error de conexion");
}
// asegurar UTF-8
mysqli_set_charset($conexion, 'utf8mb4');

