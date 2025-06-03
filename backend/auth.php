<?php
// backend/auth.php - Sistema de autenticación mejorado

class Auth {
    private $pdo;
    private $sessionTimeout = 3600; // 1 hora por defecto
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        
        // Iniciar sesión solo si no está ya iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    // Verificar si el usuario está autenticado
    public function isAuthenticated() {
        return isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }
    
    // Verificar credenciales y hacer login
    public function login($username, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT id_perfil, nombre, username, email, password FROM perfil WHERE username = :username OR email = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && $this->verifyPassword($password, $user['password'])) {
                // Crear sesión
                $_SESSION['user_id'] = $user['id_perfil'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nombre'] = $user['nombre'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['login_time'] = time();
                $_SESSION['last_activity'] = time(); // Para compatibilidad con protected.php
                
                return ['success' => true, 'message' => 'Login exitoso'];
            } else {
                return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
            }
        } catch (PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error de conexión'];
        }
    }
    
    // Cerrar sesión
    public function logout() {
        // Limpiar todas las variables de sesión
        $_SESSION = array();
        
        // Destruir la cookie de sesión si existe
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destruir la sesión
        session_destroy();
    }
    
    // Verificar contraseña 
    private function verifyPassword($inputPassword, $storedPassword) {
        // Si la contraseña almacenada parece ser un hash (empieza con $2y$)
        if (substr($storedPassword, 0, 4) === '$2y$') {
            return password_verify($inputPassword, $storedPassword);
        } else {
            // Comparación directa para contraseñas legacy (texto plano)
            // ADVERTENCIA: Esto no es seguro en producción
            return $inputPassword === $storedPassword;
        }
    }
    
    // Método requerido por protected.php - Verificar expiración de sesión
    public function checkSessionExpiry() {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return true;
        }
        
        // Verificar si la sesión ha expirado
        if (time() - $_SESSION['last_activity'] > $this->sessionTimeout) {
            $this->logout();
            return false;
        }
        
        // Actualizar tiempo de última actividad
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    // Middleware para proteger páginas (método adicional)
    public function requireAuth($redirectTo = 'login.php') {
        if (!$this->isAuthenticated() || !$this->checkSessionExpiry()) {
            header("Location: $redirectTo");
            exit;
        }
    }
    
    // Obtener información del usuario actual
    public function getCurrentUser() {
        if ($this->isAuthenticated()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'nombre' => $_SESSION['nombre'],
                'email' => $_SESSION['email'],
                'login_time' => $_SESSION['login_time'] ?? time(),
                'last_activity' => $_SESSION['last_activity'] ?? time()
            ];
        }
        return null;
    }
    
    // Método legacy para compatibilidad
    public function checkSessionExpiry_legacy($maxInactiveTime = 3600) {
        return $this->checkSessionExpiry();
    }
    
    // Hashear contraseña para nuevos usuarios
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    // Renovar sesión
    public function renewSession() {
        if ($this->isAuthenticated()) {
            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }
    
    // Obtener tiempo restante de sesión
    public function getTimeRemaining() {
        if (!isset($_SESSION['last_activity'])) {
            return 0;
        }
        
        $elapsed = time() - $_SESSION['last_activity'];
        $remaining = $this->sessionTimeout - $elapsed;
        
        return max(0, $remaining);
    }
    
    // Verificar si el usuario tiene permisos específicos
    public function hasPermission($permission) {
        // Implementar según tus necesidades
        return $this->isAuthenticated();
    }
}
?>