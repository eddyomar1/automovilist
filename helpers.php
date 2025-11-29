<?php
// helpers.php — utilidades comunes para automovilist

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function body(string $key, $default=''){
  return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

function has_file(string $field): bool{
  return isset($_FILES[$field]) && is_array($_FILES[$field]) && $_FILES[$field]['error'] !== UPLOAD_ERR_NO_FILE;
}
