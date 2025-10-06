<?php
require_once 'conexion.php';

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header('Location: index.php'); exit;
}
$id = (int)$_GET['id'];

$stmt = $con->prepare("DELETE FROM clientes WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();

header('Location: index.php');
exit;
?>