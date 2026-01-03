<?php
// init.php — arranque único para la app de vehículos
if (defined('APP_INIT')) {
  return;
}
define('APP_INIT', true);

// Zona horaria local (Santo Domingo)
date_default_timezone_set('America/Santo_Domingo');

require __DIR__ . '/conexion.php';
require __DIR__ . '/helpers.php';
require __DIR__ . '/layout.php';
