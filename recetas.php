<?php
require_once 'backend/db.php'; // Conexión a la base de datos
session_start(); // Soporte para mensajes de sesión

// Verificar si el usuario está autenticado
$isAuthenticated = isset($_SESSION['user_id']);
$currentUser = $isAuthenticated ? [
    'username' => $_SESSION['username'] ?? '',
    'nombre' => $_SESSION['nombre'] ?? ''
] : null;

// Inicializar variables
$recetas = [];
$categorias = [];
$mensaje = null;
$tipo_mensaje = null;
$mostrarCategorias = true; // Por defecto, mostrar categorías
$categoriaSeleccionada = null;

// Comprobar si hay un mensaje en la sesión
if (isset($_SESSION['mensaje']) && isset($_SESSION['tipo_mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = $_SESSION['tipo_mensaje'];
    // Limpiar mensajes de la sesión
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}

// Verificar si se ha seleccionado una categoría
if (isset($_GET['categoria']) && !empty($_GET['categoria'])) {
    $categoriaSeleccionada = htmlspecialchars(trim($_GET['categoria']));
    $mostrarCategorias = false; // Mostrar recetas en su lugar

    // Obtener recetas de la categoría seleccionada
    try {
        $sql = "SELECT r.*, c.color as categoria_color 
                FROM recetas r
                LEFT JOIN categorias c ON r.categoria_id = c.id
                WHERE c.nombre = :categoria
                ORDER BY r.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':categoria' => $categoriaSeleccionada]);
        $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $mensaje = "Error al cargar las recetas: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
} elseif (isset($_GET['ver_todas']) && $_GET['ver_todas'] == 'true') {
    $mostrarCategorias = false; // Mostrar todas las recetas

    // Obtener todas las recetas
    try {
        $sql = "SELECT r.*, c.color as categoria_color, c.nombre as categoria_nombre
                FROM recetas r
                LEFT JOIN categorias c ON r.categoria_id = c.id
                ORDER BY r.id DESC";
        $stmt = $pdo->query($sql);
        $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $mensaje = "Error al cargar las recetas: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
} else {
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
}

// Función para formatear el tiempo total
function formatearTiempo($minutos)
{
    if ($minutos < 60) {
        return $minutos . ' min';
    }

    $horas = floor($minutos / 60);
    $min = $minutos % 60;

    if ($min == 0) {
        return $horas . ' h';
    }

    return $horas . ' h ' . $min . ' min';
}

// Función para verificar si una imagen existe
function imagenExiste($path) {
    if (empty($path)) return false;
    
    // Si es una URL externa
    if (filter_var($path, FILTER_VALIDATE_URL)) {
        $headers = @get_headers($path);
        return $headers && strpos($headers[0], '200') !== false;
    }
    
    // Si es un archivo local
    return file_exists($path);
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $mostrarCategorias ? "Categorías" : "Recetas"; ?> | Recetario</title>

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

        .categoria-card {
            background-color: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
            cursor: pointer;
        }
        
        .categoria-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        
        .categoria-header {
            position: relative;
            height: 180px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            background-position: center;
            background-size: cover;
            padding: 20px;
        }
        
        .categoria-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0.2), rgba(0,0,0,0.7));
            z-index: 1;
        }
        
        .categoria-header h3, 
        .categoria-header .categoria-count {
            position: relative;
            z-index: 2;
            color: white;
        }
        
        .categoria-header h3 {
            font-size: 24px;
            margin: 0 0 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }
        
        .categoria-header .categoria-count {
            padding: 5px 12px;
            background-color: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 14px;
        }
        
        .categoria-body {
            padding: 15px;
            text-align: center;
        }
        
        .categoria-card-link {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 3;
        }

        /* Botones de administración solo para usuarios autenticados */
        .admin-section {
            display: <?php echo $isAuthenticated ? 'block' : 'none'; ?>;
        }

        @media (max-width: 768px) {
            .user-welcome, .login-link {
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
                <a href="logout.php" class="login-link">Cerrar Sesión</a></li>
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
            
            <?php if ($isAuthenticated): ?>
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
            <div class="message <?php echo $tipo_mensaje; ?>-message">
                <?php echo $mensaje; ?>
            </div>
        </div>
    <?php endif; ?>

    <header>
        <div class="container">
            <div class="header-content">
                <?php if ($mostrarCategorias): ?>
                    <h1>Categorías de Recetas</h1>
                    <p>Explora y organiza tus recetas por categoría - Haz clic en una categoría para ver sus recetas</p>
                    <div class="view-options">
                        <a href="recetas.php?ver_todas=true" class="btn-ver-todas">Ver todas las recetas</a>
                    </div>
                <?php else: ?>
                    <h1>Todas las Recetas</h1>
                    <?php if ($categoriaSeleccionada): ?>
                        <h3>Categoría: <?php echo $categoriaSeleccionada; ?> <a href="recetas.php"
                                class="btn-quitar-filtro">(Ver todas las categorías)</a></h3>
                    <?php else: ?>
                        <div class="view-options">
                            <a href="recetas.php" class="btn-ver-categorias">Ver por categorías</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Botones para crear receta y categoria (solo para usuarios autenticados) -->
            <?php if ($isAuthenticated): ?>
                <div class="agregar-receta-container">
                    <a href="backend/agregar_receta.php" class="btn-agregar-receta">Agregar Receta</a>
                    <a href="categorias.php" class="btn-agregar-receta">Agregar Categoria</a>
                </div>
            <?php endif; ?>
  
            <?php if ($mostrarCategorias): ?>
                <!-- Mostrar Categorías -->
                <?php if (empty($categorias)): ?>
                    <div class="categorias-vacio">
                        <p>Aún no hay categorías disponibles. <?php echo $isAuthenticated ? 'Puedes agregar una nueva categoría en la página de Categorías.' : 'Inicia sesión para agregar categorías.'; ?></p>
                        <?php if ($isAuthenticated): ?>
                            <a href="categorias.php" class="btn-agregar-receta">Ir a Categorías</a>
                        <?php else: ?>
                            <a href="login.php" class="btn-agregar-receta">Iniciar Sesión</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="categorias-grid">
                        <?php foreach ($categorias as $categoria): ?>
                            <?php
                            // Obtener la primera imagen de receta para esta categoría (si existe)
                            $imagenReceta = null;
                            $imagenUrl = '';
                            
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT image_path
                                    FROM recetas
                                    WHERE categoria_id = :categoria_id
                                    AND image_path IS NOT NULL
                                    ORDER BY id DESC
                                    LIMIT 1
                                ");
                                $stmt->execute([':categoria_id' => $categoria['id']]);
                                $imagenReceta = $stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($imagenReceta && !empty($imagenReceta['image_path']) && imagenExiste($imagenReceta['image_path'])) {
                                    $imagenUrl = $imagenReceta['image_path'];
                                }
                            } catch (PDOException $e) {
                                // Error silencioso
                            }
                            
                            // Establecer el fondo (color o imagen)
                            $bgStyle = '';
                            if (!empty($imagenUrl)) {
                                $bgStyle = 'background-image: url(\'' . htmlspecialchars($imagenUrl) . '\');';
                            } else {
                                $bgStyle = 'background-color: ' . htmlspecialchars($categoria['color']) . ';';
                            }
                            ?>
                            
                            <div class="categoria-card">
                                <a href="recetas.php?categoria=<?php echo urlencode($categoria['nombre']); ?>" class="categoria-card-link"></a>
                                
                                <div class="categoria-header" style="<?php echo $bgStyle; ?>">
                                    <h3><?php echo htmlspecialchars($categoria['nombre']); ?></h3>
                                    <span class="categoria-count">
                                        <?php echo $categoria['recetas_count']; ?>
                                        receta<?php echo ($categoria['recetas_count'] != 1) ? 's' : ''; ?>
                                    </span>
                                </div>
                                
                                <div class="categoria-body">
                                    <?php if (!empty($categoria['descripcion'])): ?>
                                        <p><?php echo htmlspecialchars($categoria['descripcion']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Mostrar Recetas -->
                <?php if (empty($recetas)): ?>
                    <div style="background-color: white; border-radius: 12px; padding: 2rem; text-align: center; margin-top: 1rem; box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);">
                        <p style="color: #636e72; margin-bottom: 1.5rem;">
                            <?php if ($categoriaSeleccionada): ?>
                                No hay recetas disponibles en la categoría "<?php echo $categoriaSeleccionada; ?>".
                            <?php else: ?>
                                Aún no hay recetas en el recetario.
                            <?php endif; ?>
                        </p>
                        <?php if ($isAuthenticated): ?>
                            <a href="backend/agregar_receta.php" class="btn-agregar-receta">Agregar Primera Receta</a>
                        <?php else: ?>
                            <a href="login.php" class="btn-agregar-receta">Iniciar Sesión para Agregar Recetas</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px;">
                        <?php foreach ($recetas as $receta): ?>
                            <a href="receta.php?id=<?php echo $receta['id']; ?>" style="text-decoration: none;">
                                <div style="background-color: #ff5400; border-radius: 4px; display: flex; height: 76px; cursor: pointer; overflow: hidden; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                                    <div style="width: 76px; height: 76px; flex-shrink: 0;">
                                        <?php if (!empty($receta['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($receta['image_path']); ?>"
                                                alt="<?php echo htmlspecialchars($receta['title']); ?>"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php elseif (!empty($receta['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($receta['image_url']); ?>"
                                                alt="<?php echo htmlspecialchars($receta['title']); ?>"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php else: ?>
                                            <img src="img/placeholder-receta.jpg"
                                                alt="<?php echo htmlspecialchars($receta['title']); ?>"
                                                style="width: 100%; height: 100%; object-fit: cover;">
                                        <?php endif; ?>
                                    </div>

                                    <div style="padding: 10px; display: flex; align-items: center; flex-grow: 1; position: relative; z-index: 5;">
                                        <h3 style="font-size: 16px; margin: 0; padding: 0; color: white; font-weight: 600; line-height: 1.2; font-family: 'Montserrat', sans-serif; display: block; visibility: visible; opacity: 1; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);">
                                            <?php echo htmlspecialchars($receta['title']); ?>
                                        </h3>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
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
                        <?php if ($isAuthenticated): ?>
                            <li><a href="categorias.php">Categorías</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Asegurar que el navbar esté fijo
            const nav = document.querySelector('nav');
            nav.style.position = 'fixed';
            nav.style.top = '0';
            nav.style.left = '0';
            nav.style.width = '100%';
            nav.style.zIndex = '1000';

            // Ajustar padding del body
            document.body.style.paddingTop = '60px';

            // Ocultar mensaje después de 5 segundos
            const mensajeContainer = document.querySelector('.mensaje-container');
            if (mensajeContainer) {
                setTimeout(function () {
                    mensajeContainer.style.opacity = '0';
                    setTimeout(function () {
                        mensajeContainer.style.display = 'none';
                    }, 1000);
                }, 5000);
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
        });
    </script>
    <script src="js/menu.js"></script>
</body>

</html>