<?php
// backend/protected.php - Guard para proteger páginas administrativas
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$auth = new Auth($pdo);

// Verificar autenticación y expiración de sesión
if (!$auth->isAuthenticated() || !$auth->checkSessionExpiry()) {
    // Guardar la URL actual para redirigir después del login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Redirigir al login
    $loginPath = (strpos($_SERVER['REQUEST_URI'], '/backend/') !== false) ? '../login.php' : 'login.php';
    header("Location: $loginPath");
    exit;
}

// Obtener información del usuario para usar en las páginas
$currentUser = $auth->getCurrentUser();
?>

