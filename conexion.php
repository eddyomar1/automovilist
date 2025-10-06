<?php
// conexion.php — Conexión MySQLi (según tu bloque)

$con = new mysqli(
    "localhost",
    "u138076177_chacharito",
    "3spWifiPruev@",
    "u138076177_pw"
);

if ($con->connect_error) {
    die("Error de conexión: " . $con->connect_error);
}

// Asegura UTF-8
$con->set_charset("utf8mb4");
