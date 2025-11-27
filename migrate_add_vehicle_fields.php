<?php
require_once 'conexion.php';

// Migración: añadir columnas para datos del vehículo y foto de la placa
$sql = "ALTER TABLE clientes \
  ADD COLUMN IF NOT EXISTS placa VARCHAR(50) DEFAULT NULL, \
  ADD COLUMN IF NOT EXISTS modelo VARCHAR(100) DEFAULT NULL, \
  ADD COLUMN IF NOT EXISTS color VARCHAR(50) DEFAULT NULL, \
  ADD COLUMN IF NOT EXISTS telefono_contacto VARCHAR(50) DEFAULT NULL, \
  ADD COLUMN IF NOT EXISTS notas_incidente TEXT DEFAULT NULL, \
  ADD COLUMN IF NOT EXISTS foto_placa LONGBLOB DEFAULT NULL";

if ($con->query($sql) === TRUE) {
    echo "Migración aplicada: columnas añadidas (si no existían).\n";
} else {
    echo "Error al aplicar migración: " . $con->error . "\n";
}

echo "Nota: Si ves errores sobre 'IF NOT EXISTS' en ALTER TABLE, puede que tu versión de MySQL no lo soporte; en ese caso ejecuta manualmente las ALTER TABLE por columnas.\n";

// Mostrar instrucciones rápidas
echo "\nSQL sugerido:\n" . "ALTER TABLE clientes ADD COLUMN placa VARCHAR(50) DEFAULT NULL;\n";
echo "ALTER TABLE clientes ADD COLUMN modelo VARCHAR(100) DEFAULT NULL;\n";
echo "ALTER TABLE clientes ADD COLUMN color VARCHAR(50) DEFAULT NULL;\n";
echo "ALTER TABLE clientes ADD COLUMN telefono_contacto VARCHAR(50) DEFAULT NULL;\n";
echo "ALTER TABLE clientes ADD COLUMN notas_incidente TEXT DEFAULT NULL;\n";
echo "ALTER TABLE clientes ADD COLUMN foto_placa LONGBLOB DEFAULT NULL;\n";

?>
