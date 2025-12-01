<?php
// Ejecuta este script una sola vez para crear la tabla de reportes de soporte.

$con = new mysqli(
    "localhost",
    "u138076177_chacharito",
    "3spWifiPruev@",
    "u138076177_pw"
);

if ($con->connect_error) {
    die("Error de conexión: " . $con->connect_error);
}
$con->set_charset("utf8mb4");

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS reportes_soporte (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reportado_por VARCHAR(120) NOT NULL,
    contacto VARCHAR(150) NULL,
    asunto VARCHAR(160) NOT NULL,
    detalle TEXT NOT NULL,
    captura LONGBLOB NULL,
    captura_nombre VARCHAR(255) NULL,
    captura_mime VARCHAR(120) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;

if ($con->query($sql) === true) {
    echo "Tabla reportes_soporte verificada/creada.\n";
} else {
    echo "Error al crear la tabla: " . $con->error . "\n";
}
