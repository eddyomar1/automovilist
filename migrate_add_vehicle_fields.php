<?php
// Ejecuta este script una sola vez para agregar nuevas columnas a clientes:
//  - codigo_inquilino (VARCHAR)
//  - foto_cedula_frente (LONGBLOB)
//  - foto_cedula_atras (LONGBLOB)

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

function ensureColumn(mysqli $con, string $table, string $column, string $definition): bool{
  $escapedTable  = $con->real_escape_string($table);
  $escapedColumn = $con->real_escape_string($column);
  $res = $con->query("SHOW COLUMNS FROM `{$escapedTable}` LIKE '{$escapedColumn}'");
  if ($res && $res->num_rows > 0) {
    return true;
  }
  return (bool)$con->query("ALTER TABLE `{$escapedTable}` ADD COLUMN {$definition}");
}

$ok1 = ensureColumn($con, 'clientes', 'codigo_inquilino', "VARCHAR(50) NULL AFTER correo");
$ok2 = ensureColumn($con, 'clientes', 'foto_cedula_frente', "LONGBLOB NULL AFTER foto_placa");
$ok3 = ensureColumn($con, 'clientes', 'foto_cedula_atras',  "LONGBLOB NULL AFTER foto_cedula_frente");

if ($ok1 && $ok2 && $ok3) {
  echo "Migración completada.\n";
} else {
  echo "Revisa la migración, alguna columna no se pudo crear.\n";
}
