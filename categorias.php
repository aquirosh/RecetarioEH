<?php
require_once 'backend/db.php'; // Conexión a la base de datos

// Iniciar sesión al comienzo
session_start();

// Verificar si es una página administrativa
$isAdminPage = isset($_POST['accion']) || isset($_GET['editar']) || isset($_GET['eliminar']);

// Inicializar variables
$isAuthenticated = false;
$currentUser = null;

// Solo requerir autenticación si es una acción administrativa
if ($isAdminPage) {
    try {
        require_once 'backend/protected.php'; // Protección de autenticación
        $isAuthenticated = true;
        
        // Obtener información del usuario desde la sesión
        if (isset($_SESSION['user_id'])) {
            $currentUser = [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? '',
                'nombre' => $_SESSION['nombre'] ?? '',
                'email' => $_SESSION['email'] ?? ''
            ];
        }
    } catch (Exception $e) {
        // Si hay error con protected.php, redirigir al login
        header("Location: login.php");
        exit;
    }
} else {
    // Para páginas públicas, verificar autenticación sin requerir
    $isAuthenticated = isset($_SESSION['user_id']);
    if ($isAuthenticated) {
        $currentUser = [
            'id' => $_SESSION['user_id'] ?? '',
            'username' => $_SESSION['username'] ?? '',
            'nombre' => $_SESSION['nombre'] ?? '',
            'email' => $_SESSION['email'] ?? ''
        ];
    }
}

// Inicializar variables
$categorias = [];
$mensaje = null;
$tipo_mensaje = null;
$categoria_editar = null;

// Comprobar si hay un mensaje en la sesión
if (isset($_SESSION['mensaje']) && isset($_SESSION['tipo_mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = $_SESSION['tipo_mensaje'];
    // Limpiar mensajes de la sesión
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}

// Procesar formulario para añadir nueva categoría (SOLO SI ESTÁ AUTENTICADO)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'crear_categoria') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $color = isset($_POST['color']) ? trim($_POST['color']) : '#ff5400';
    
    // Validar datos
    $errores = [];
    if (empty($nombre)) {
        $errores[] = "El nombre de la categoría es obligatorio.";
    }
    
    // Validar formato de color
    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
        $color = '#ff5400'; // Color por defecto si el formato es inválido
    }
    
    if (empty($errores)) {
        try {
            // Verificar si la categoría ya existe
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nombre");
            $stmt->execute([':nombre' => $nombre]);
            if ($stmt->rowCount() > 0) {
                $mensaje = "La categoría '$nombre' ya existe.";
                $tipo_mensaje = "error";
            } else {
                // Insertar nueva categoría
                $stmt = $pdo->prepare("INSERT INTO categorias (nombre, descripcion, color) VALUES (:nombre, :descripcion, :color)");
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':descripcion' => $descripcion,
                    ':color' => $color
                ]);
                
                $mensaje = "Categoría creada con éxito.";
                $tipo_mensaje = "success";
            }
        } catch (PDOException $e) {
            $mensaje = "Error al crear la categoría: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = "error";
    }
}

// Procesar formulario para editar categoría existente (SOLO SI ESTÁ AUTENTICADO)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'editar_categoria') {
    $id = (int)($_POST['id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $color = isset($_POST['color']) ? trim($_POST['color']) : '#ff5400';
    
    // Validar datos
    $errores = [];
    if (empty($nombre)) {
        $errores[] = "El nombre de la categoría es obligatorio.";
    }
    
    // Validar formato de color
    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
        $color = '#ff5400'; // Color por defecto si el formato es inválido
    }
    
    if (empty($errores)) {
        try {
            // Verificar si la categoría ya existe (excluyendo la actual)
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nombre AND id != :id");
            $stmt->execute([
                ':nombre' => $nombre,
                ':id' => $id
            ]);
            if ($stmt->rowCount() > 0) {
                $mensaje = "Ya existe otra categoría con el nombre '$nombre'.";
                $tipo_mensaje = "error";
            } else {
                // Actualizar la categoría
                $stmt = $pdo->prepare("UPDATE categorias SET nombre = :nombre, descripcion = :descripcion, color = :color WHERE id = :id");
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':descripcion' => $descripcion,
                    ':color' => $color,
                    ':id' => $id
                ]);
                
                $mensaje = "Categoría actualizada con éxito.";
                $tipo_mensaje = "success";
            }
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar la categoría: " . $e->getMessage();
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = implode("<br>", $errores);
        $tipo_mensaje = "error";
    }
}

// Preparar para editar categoría si se solicita (SOLO SI ESTÁ AUTENTICADO)
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id = (int)$_GET['editar'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $categoria_editar = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$categoria_editar) {
            $mensaje = "La categoría solicitada no existe.";
            $tipo_mensaje = "error";
        } else {
            // Limpiar los datos antes de mostrarlos
            $categoria_editar['nombre'] = isset($categoria_editar['nombre']) ? strip_tags($categoria_editar['nombre']) : '';
            $categoria_editar['descripcion'] = isset($categoria_editar['descripcion']) ? strip_tags($categoria_editar['descripcion']) : '';
        }
    } catch (PDOException $e) {
        $mensaje = "Error al cargar la categoría: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Eliminar categoría si se solicita (SOLO SI ESTÁ AUTENTICADO)
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    try {
        // Verificar si hay recetas usando esta categoría
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM recetas WHERE categoria_id = :id");
        $stmt->execute([':id' => $id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $mensaje = "No se puede eliminar la categoría porque está siendo utilizada por $count receta(s).";
            $tipo_mensaje = "error";
        } else {
            // Eliminar categoría
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            $mensaje = "Categoría eliminada con éxito.";
            $tipo_mensaje = "success";
        }
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar la categoría: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Obtener todas las categorías con conteo de recetas
try {
    $sql = "SELECT c.id, c.nombre, c.descripcion, c.color, COUNT(r.id) as recetas_count 
            FROM categorias c 
            LEFT JOIN recetas r ON c.id = r.categoria_id
            GROUP BY c.id, c.nombre 
            ORDER BY c.nombre";
    $stmt = $pdo->query($sql);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje = "Error al cargar las categorías: " . $e->getMessage();
    $tipo_mensaje = "error";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isAuthenticated ? 'Gestionar Categorías' : 'Categorías'; ?> | Recetario</title>

    <link rel="icon" href="img/recetario.png" type="image/png">
    <link rel="shortcut icon" href="img/recetario.png" type="image/png">

    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/categorias.css">
    
    <style>
        /* Estilos adicionales para el navbar con autenticación */
        .user-container {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
        }

        .user-welcome {
            color: white;
            font-size: 14px;
            font-weight: 600;
        }

        .login-link {
            color: white;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 6px 12px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .login-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .user-info {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background-color: rgba(255, 255, 255, 0.1);
            margin-bottom: 10px;
        }

        .user-avatar {
            font-size: 24px;
            margin-right: 10px;
        }

        .user-details strong {
            display: block;
            color: white;
            font-size: 16px;
        }

        .user-details small {
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
        }

        .menu-divider {
            padding: 8px 20px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 5px;
        }

        /* Mensaje de acceso restringido */
        .access-restricted {
            background: linear-gradient(135deg, #e3f2fd, #f3e5f5);
            border: 2px solid #2196f3;
            border-radius: 12px;
            padding: 2.5rem;
            margin: 2rem 0;
            text-align: center;
            box-shadow: 0 8px 24px rgba(33, 150, 243, 0.1);
        }

        .access-restricted h3 {
            color: #1976d2;
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .access-restricted p {
            color: #555;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .user-welcome {
                font-size: 12px;
            }
            
            .user-container {
                padding-right: 5px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="menu-container">
            <button class="menu-button" id="openMenu">☰</button>
        </div>
        <div class="brand-container">
            <a href="index.php" class="nav-brand">Recetario</a>
        </div>
        <div class="user-container">
            <?php if ($isAuthenticated): ?>
                <a href="logout.php" class="login-link">Cerrar Sesión</a>
            <?php else: ?>
                <a href="login.php" class="login-link">Iniciar Sesión</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Side Menu -->
    <div class="menu-overlay" id="menuOverlay"></div>
    <div class="side-menu" id="sideMenu">
        <div class="side-menu-content">
            <div class="menu-header">
                <h3>Recetario</h3>
                <button class="close-menu" id="closeMenu">×</button>
            </div>
            
            <?php if ($isAuthenticated && $currentUser): ?>
                <div class="user-info">
                    <div class="user-avatar">👤</div>
                    <div class="user-details">
                        <strong><?php echo htmlspecialchars($currentUser['nombre'] ?: $currentUser['username']); ?></strong>
                        <small>Administrador</small>
                    </div>
                </div>
            <?php endif; ?>
            
            <ul>
                <li><a href="index.php">Inicio</a></li>
                
                <?php if ($isAuthenticated): ?>
                    <li class="menu-divider">Administración</li>
                    <li><a href="backend/agregar_receta.php">Agregar Recetas</a></li>
                    <li><a href="categorias.php">Gestionar Categorías</a></li>
                <?php endif; ?>
                
                <li class="menu-divider">Navegación</li>
                <li><a href="recetas.php">Recetas</a></li>
                
                <?php if ($isAuthenticated): ?>
                    <li class="menu-divider"></li>
                    <li><a href="logout.php">Cerrar Sesión</a></li>
                <?php else: ?>
                    <li class="menu-divider"></li>
                    <li><a href="login.php">Iniciar Sesión</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <?php if ($mensaje): ?>
    <div class="mensaje-container">
        <div class="message <?php echo htmlspecialchars($tipo_mensaje); ?>-message">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
    </div>
    <?php endif; ?>
    
    <header>
        <div class="container">
            <div class="header-content">
                <h1><?php echo $isAuthenticated ? 'Gestionar Categorías' : 'Categorías'; ?></h1>
                <p><?php echo $isAuthenticated ? 'Organiza y administra las categorías de recetas' : 'Explora las categorías de recetas disponibles'; ?></p>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Formulario para agregar/editar categoría (SOLO PARA USUARIOS AUTENTICADOS) -->
            <?php if ($isAuthenticated): ?>
                <div class="nueva-categoria-container">
                    <h3><?php echo ($categoria_editar) ? 'Editar Categoría' : 'Añadir Nueva Categoría'; ?></h3>
                    <form class="nueva-categoria-form" method="POST" action="categorias.php">
                        <input type="hidden" name="accion" value="<?php echo ($categoria_editar) ? 'editar_categoria' : 'crear_categoria'; ?>">
                        
                        <?php if ($categoria_editar): ?>
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($categoria_editar['id']); ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="nombre">Nombre*</label>
                            <input type="text" id="nombre" name="nombre" value="<?php echo ($categoria_editar && isset($categoria_editar['nombre'])) ? htmlspecialchars($categoria_editar['nombre']) : ''; ?>" required placeholder="Ej: Postres, Platos principales...">
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción (opcional)</label>
                            <input type="text" id="descripcion" name="descripcion" value="<?php echo ($categoria_editar && isset($categoria_editar['descripcion'])) ? htmlspecialchars($categoria_editar['descripcion']) : ''; ?>" placeholder="Breve descripción de la categoría...">
                        </div>
                        
                        <div class="form-group" style="grid-column: span 2;">
                            <label for="color-picker">Color</label>
                            <div class="color-input-container">
                                <input type="color" id="color-picker" name="color" value="<?php echo ($categoria_editar && isset($categoria_editar['color'])) ? htmlspecialchars($categoria_editar['color']) : '#ff5400'; ?>">
                                <span class="color-value" id="colorHexValue"><?php echo ($categoria_editar && isset($categoria_editar['color'])) ? htmlspecialchars($categoria_editar['color']) : '#ff5400'; ?></span>
                            </div>
                            <p class="color-help-text">Selecciona un color para identificar visualmente la categoría</p>
                        </div>
                        
                        <div class="form-buttons">
                            <button type="submit" class="btn-primary">
                                <?php echo ($categoria_editar) ? 'Actualizar Categoría' : 'Crear Categoría'; ?>
                            </button>
                            <?php if ($categoria_editar): ?>
                            <a href="categorias.php" class="btn-secondary">Cancelar</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="access-restricted">
                    <h3>Acceso Administrativo</h3>
                    <p>Para gestionar categorías necesitas iniciar sesión como administrador.</p>
                    <a href="login.php" class="btn-primary">Iniciar Sesión</a>
                </div>
            <?php endif; ?>
            
            <?php if (empty($categorias)): ?>
                <div class="categorias-vacio">
                    <h3>No hay categorías disponibles</h3>
                    <p><?php echo $isAuthenticated ? '¡Agrega la primera categoría usando el formulario de arriba!' : 'Las categorías aparecerán aquí una vez que sean creadas.'; ?></p>
                </div>
            <?php else: ?>
                <div class="categorias-grid">
                    <?php foreach ($categorias as $categoria): ?>
                        <div class="categoria-card">
                            <div class="categoria-header" style="background: linear-gradient(135deg, <?php echo htmlspecialchars($categoria['color'] ?? '#ff5400'); ?>, <?php echo htmlspecialchars($categoria['color'] ?? '#ff5400'); ?>aa);">
                                <span class="categoria-count">
                                    <?php echo (int)($categoria['recetas_count'] ?? 0); ?> receta<?php echo ((int)($categoria['recetas_count'] ?? 0) != 1) ? 's' : ''; ?>
                                </span>
                                
                                <?php if ($isAuthenticated): ?>
                                <div class="categoria-acciones">
                                    <a href="categorias.php?editar=<?php echo (int)($categoria['id'] ?? 0); ?>" class="btn-accion btn-editar" title="Editar categoría">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                        </svg>
                                    </a>
                                    <?php if ((int)($categoria['recetas_count'] ?? 0) == 0): ?>
                                    <a href="categorias.php?eliminar=<?php echo (int)($categoria['id'] ?? 0); ?>" class="btn-accion btn-eliminar" title="Eliminar categoría" onclick="return confirm('¿Estás seguro de eliminar esta categoría?')">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                
                                <h3><?php echo htmlspecialchars($categoria['nombre'] ?? 'Sin nombre'); ?></h3>
                            </div>
                            <div class="categoria-body">
                                <?php if (!empty($categoria['descripcion'])): ?>
                                    <p><?php echo htmlspecialchars($categoria['descripcion']); ?></p>
                                <?php endif; ?>
                                <a href="recetas.php?categoria=<?php echo urlencode($categoria['nombre'] ?? ''); ?>" class="btn-ver-recetas">
                                    Ver recetas
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <a href="index.php">Recetario</a>
                    <p>Cocina casera para todos los días</p>
                </div>
                
                <div class="footer-links">
                    <h3>Navegación</h3>
                    <ul>
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="recetas.php">Recetas</a></li>
                        <li><a href="categorias.php">Categorías</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; 2025 Recetario - Todos los derechos reservados</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Asegurar que el navbar esté fijo
            const nav = document.querySelector('nav');
            if (nav) {
                nav.style.position = 'fixed';
                nav.style.top = '0';
                nav.style.left = '0';
                nav.style.width = '100%';
                nav.style.zIndex = '1000';
            }
            
            // Ajustar padding del body
            document.body.style.paddingTop = '60px';
            
            // Ocultar mensaje después de 5 segundos con animación
            const mensajeContainer = document.querySelector('.mensaje-container');
            if (mensajeContainer) {
                setTimeout(function() {
                    mensajeContainer.style.opacity = '0';
                    mensajeContainer.style.transform = 'translateY(-20px)';
                    setTimeout(function() {
                        mensajeContainer.style.display = 'none';
                    }, 500);
                }, 5000);
            }
            
            // Actualizar el valor hexadecimal al cambiar el color
            const colorPicker = document.getElementById('color-picker');
            const colorHexValue = document.getElementById('colorHexValue');
            
            if (colorPicker && colorHexValue) {
                colorPicker.addEventListener('input', function() {
                    colorHexValue.textContent = this.value.toUpperCase();
                });
            }

            // Handle logout confirmation
            const logoutLink = document.querySelector('a[href="logout.php"]');
            if (logoutLink) {
                logoutLink.addEventListener('click', function(e) {
                    if (!confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                        e.preventDefault();
                    }
                });
            }

            // Animación de entrada para las tarjetas
            const cards = document.querySelectorAll('.categoria-card');
            cards.forEach((card, index) => {
                if (card) {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(30px)';
                    setTimeout(() => {
                        card.style.transition = 'all 0.5s ease';
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, index * 100);
                }
            });

            // Efecto en el formulario
            const formContainer = document.querySelector('.nueva-categoria-container');
            if (formContainer) {
                formContainer.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.02)';
                });
                formContainer.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            }
        });
    </script>
    <script src="js/menu.js"></script>
    <script src="js/recipe-cards.js"></script>
</body>
</html>