<?php
require_once 'backend/db.php'; // Conexión a la base de datos
session_start(); // Iniciar sesión para verificar autenticación

// Verificar si el usuario está autenticado
$isAuthenticated = isset($_SESSION['user_id']);
$currentUser = $isAuthenticated ? [
    'username' => $_SESSION['username'] ?? '',
    'nombre' => $_SESSION['nombre'] ?? ''
] : null;

// Función para formatear el tiempo total
function formatearTiempo($minutos) {
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

// Obtener las 3 recetas más recientes
$recetasRecientes = [];
try {
    $sql = "SELECT r.*, c.color as categoria_color 
            FROM recetas r
            LEFT JOIN categorias c ON r.categoria_id = c.id
            ORDER BY r.id DESC
            LIMIT 3";
    $stmt = $pdo->query($sql);
    $recetasRecientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejar error silenciosamente
}

// Obtener categorías populares (las que tienen más recetas)
$categoriasPopulares = [];
try {
    $sql = "SELECT c.id, c.nombre, c.color, COUNT(r.id) as recetas_count 
            FROM categorias c 
            LEFT JOIN recetas r ON c.id = r.categoria_id
            GROUP BY c.id, c.nombre 
            ORDER BY recetas_count DESC
            LIMIT 4";
    $stmt = $pdo->query($sql);
    $categoriasPopulares = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejar error silenciosamente
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recetario Eugenie Herrero</title>

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

        .side-menu ul li:not(.menu-divider) a {
            display: block;
        }

        /* Estilo condicional para mostrar/ocultar botones de administración */
        .admin-section {
            display: <?php echo $isAuthenticated ? 'block' : 'none'; ?>;
        }

        /* ESTILOS MEJORADOS PARA CATEGORÍAS SIN OVERLAY DE COLOR */
        .category-card {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            height: 180px;
            display: block;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
        }

        .category-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .category-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .category-card:hover .category-image img {
            transform: scale(1.1);
        }

        /* Overlay oscuro sutil para mejor legibilidad del texto */
        .category-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0.2), rgba(0,0,0,0.7));
            z-index: 1;
        }

        .category-card h3 {
            position: absolute;
            bottom: 20px;
            left: 20px;
            color: white;
            font-size: 20px;
            z-index: 2;
            margin: 0;
            transition: transform 0.3s ease;
            text-shadow: 0 2px 4px rgba(0,0,0,0.5);
        }

        .category-count {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(255,255,255,0.9);
            color: #333;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            z-index: 2;
            backdrop-filter: blur(10px);
        }

        .category-card:hover h3 {
            transform: translateY(-5px);
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
                    
                <?php else: ?>
                    <li class="menu-divider"></li>
                    <li><a href="login.php">Iniciar Sesión</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    
    <header>
        <div class="container">
            <div class="header-content">
                <h1>Recetario</h1>
                <h3>Las mejores recetas de Eugenie Herrero</h3>
                
                <?php if ($isAuthenticated): ?>
                    <div class="admin-section" style="margin-top: 20px;">
                        <a href="backend/agregar_receta.php" class="btn-agregar-receta" style="margin-right: 10px;">Agregar Receta</a>
                        <a href="categorias.php" class="btn-agregar-receta">Gestionar Categorías</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <section class="recent-recipes">
            <div class="container">
                <h2 class="section-title">Recetas Recientes</h2>
                
                <?php if (empty($recetasRecientes)): ?>
                    <div class="no-data-message">
                        <p>Aún no hay recetas disponibles. <?php echo $isAuthenticated ? '¡Añade tu primera receta!' : '¡Inicia sesión para agregar recetas!'; ?></p>
                        <?php if ($isAuthenticated): ?>
                            <a href="backend/agregar_receta.php" class="btn-agregar-receta">Agregar Receta</a>
                        <?php else: ?>
                            <a href="login.php" class="btn-agregar-receta">Iniciar Sesión</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="recipes-container">
                        <?php foreach ($recetasRecientes as $receta): ?>
                            <div class="recipe-card" onclick="window.location.href='receta.php?id=<?php echo $receta['id']; ?>'">
                                <div class="recipe-image">
                                    <a href="receta.php?id=<?php echo $receta['id']; ?>">
                                        <?php if (!empty($receta['image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($receta['image_path']); ?>" alt="<?php echo htmlspecialchars($receta['title']); ?>">
                                        <?php elseif (!empty($receta['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($receta['image_url']); ?>" alt="<?php echo htmlspecialchars($receta['title']); ?>">
                                        <?php else: ?>
                                            <img src="img/placeholder-receta.jpg" alt="<?php echo htmlspecialchars($receta['title']); ?>">
                                        <?php endif; ?>
                                    </a>
                                    <div class="recipe-time">
                                        <?php echo formatearTiempo($receta['prep_time_minutes'] + $receta['cook_time_minutes']); ?>
                                    </div>
                                </div>
                                
                                <div class="recipe-content">
                                    <a href="receta.php?id=<?php echo $receta['id']; ?>" class="recipe-header-link">
                                        <h3><?php echo htmlspecialchars($receta['title']); ?></h3>
                                    </a>
                                    
                                    <div class="recipe-meta">
                                        <span class="difficulty">
                                            <?php 
                                            $dificultad = 'Media';
                                            if (isset($receta['difficulty'])) {
                                                $dificultad = $receta['difficulty'];
                                            } elseif (($receta['prep_time_minutes'] + $receta['cook_time_minutes']) < 30) {
                                                $dificultad = 'Fácil';
                                            } elseif (($receta['prep_time_minutes'] + $receta['cook_time_minutes']) > 60) {
                                                $dificultad = 'Difícil';
                                            }
                                            echo htmlspecialchars($dificultad);
                                            ?>
                                        </span>
                                        
                                        <?php if (!empty($receta['portions'])): ?>
                                            <span><?php echo htmlspecialchars($receta['portions']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="recipe-desc">
                                        <?php 
                                        if (!empty($receta['description'])) {
                                            echo htmlspecialchars(substr($receta['description'], 0, 120)) . (strlen($receta['description']) > 120 ? '...' : '');
                                        } else {
                                            // Si no hay descripción, mostrar los primeros ingredientes
                                            $ingredientes = !empty($receta['ingredients']) ? $receta['ingredients'] : '';
                                            $ingredientesArray = explode("\n", $ingredientes);
                                            $primeros = array_slice($ingredientesArray, 0, 2);
                                            echo !empty($primeros) ? htmlspecialchars(implode(", ", $primeros)) . '...' : 'Receta casera deliciosa';
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="recipe-tags">
                                        <span class="tag categoria-tag" style="background-color: <?php echo !empty($receta['categoria_color']) ? $receta['categoria_color'] : 'var(--primary)'; ?>">
                                            <?php echo htmlspecialchars($receta['category']); ?>
                                        </span>
                                    </div>
                                    
                                    <a href="receta.php?id=<?php echo $receta['id']; ?>" class="recipe-link-big">Ver receta</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="view-more">
                        <a href="recetas.php" class="btn">Ver más recetas</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
        <section class="categories">
            <div class="container">
                <h2 class="section-title">Categorías Populares</h2>
                
                <?php if (empty($categoriasPopulares)): ?>
                    <div class="no-data-message">
                        <p>Aún no hay categorías disponibles. <?php echo $isAuthenticated ? '¡Crea la primera categoría!' : '¡Inicia sesión para crear categorías!'; ?></p>
                        <?php if ($isAuthenticated): ?>
                            <a href="categorias.php" class="btn-agregar-receta">Ir a Categorías</a>
                        <?php else: ?>
                            <a href="login.php" class="btn-agregar-receta">Iniciar Sesión</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="categories-container">
                        <?php foreach ($categoriasPopulares as $categoria): ?>
                            <a href="recetas.php?categoria=<?php echo urlencode($categoria['nombre']); ?>" class="category-card">
                                
                                <!-- Imagen para la categoría (intentamos encontrar una imagen de receta de esta categoría) -->
                                <div class="category-image">
                                    <?php
                                    // Intentar encontrar una imagen para esta categoría
                                    try {
                                        $imgSql = "SELECT image_path FROM recetas WHERE categoria_id = :categoria_id AND image_path IS NOT NULL LIMIT 1";
                                        $imgStmt = $pdo->prepare($imgSql);
                                        $imgStmt->execute([':categoria_id' => $categoria['id']]);
                                        $imagenReceta = $imgStmt->fetchColumn();
                                    } catch (PDOException $e) {
                                        $imagenReceta = null;
                                    }
                                    ?>
                                    
                                    <?php if (!empty($imagenReceta)): ?>
                                        <img src="<?php echo htmlspecialchars($imagenReceta); ?>" alt="<?php echo htmlspecialchars($categoria['nombre']); ?>">
                                    <?php else: ?>
                                        <!-- Imagen de placeholder para la categoría -->
                                        <img src="img/placeholder-categoria.jpg" alt="<?php echo htmlspecialchars($categoria['nombre']); ?>">
                                    <?php endif; ?>
                                </div>
                                
                                <span class="category-count">
                                    <?php echo $categoria['recetas_count']; ?> receta<?php echo ($categoria['recetas_count'] != 1) ? 's' : ''; ?>
                                </span>
                                <h3><?php echo htmlspecialchars($categoria['nombre']); ?></h3>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="view-more">
                        <a href="recetas.php" class="btn">Ver todas las categorías</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
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

    <script src="js/menu.js"></script>
    <script src="js/recipe-cards.js"></script>

    <script>
        // Handle logout confirmation
        document.addEventListener('DOMContentLoaded', function() {
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

</body>

</html>