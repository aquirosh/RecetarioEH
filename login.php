<?php
require_once 'backend/db.php';
require_once 'backend/auth.php';

$auth = new Auth($pdo);

// Si ya está autenticado, redirigir al index
if ($auth->isAuthenticated()) {
    header("Location: index.php");
    exit;
}

$error = null;
$success = null;

// Procesar formulario de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = "Por favor, completa todos los campos.";
    } else {
        $result = $auth->login($username, $password);
        if ($result['success']) {
            header("Location: index.php");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión | Recetario</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🍳 Recetario</h1>
            <p>Inicia sesión para gestionar tus recetas</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <form class="login-form" method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Usuario o Email</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                       required autofocus>
            </div>
            
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            
            <button type="submit" class="btn-login">
                Iniciar Sesión
            </button>
        </form>
        
        <div class="login-footer">
            <a href="index.php" class="back-link">← Volver al recetario</a>
        </div>
    </div>

    <script>
        // Auto-focus en el campo de usuario si está vacío
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            if (!usernameField.value) {
                usernameField.focus();
            }
        });
    </script>
</body>
</html>