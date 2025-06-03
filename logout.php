<?php
// logout.php - Script para cerrar sesión
require_once 'backend/db.php';
require_once 'backend/auth.php';

$auth = new Auth($pdo);
$auth->logout();

// Redirigir al login con mensaje de éxito
header("Location: login.php?logged_out=1");
exit;
?>