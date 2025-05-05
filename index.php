<?php
require_once 'backend/db.php'; // Conexión a la base de datos

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
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/categorias.css">
    <style>
        /* Estilos para mejorar las tarjetas */
        .recipe-card {
            cursor: pointer;
        }
        
        .recipe-link-big {
            display: block;
            width: 100%;
            background-color: var(--primary);
            color: white;
            text-align: center;
            padding: 12px 0;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 15px;
            transition: all 0.3s ease;
        }
        
        .recipe-link-big:hover {
            background-color: var(--dark-accent);
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .recipe-header-link {
            text-decoration: none;
            color: var(--primary);
        }
        
        .recipe-header-link:hover h3 {
            color: var(--accent);
        }
        
        .tag.categoria-tag {
            background-color: var(--primary);
            color: white;
        }
        
        /* Estilos mejorados para categorías */
        .categoria-card {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            height: 180px;
            display: block;
            text-decoration: none;
        }
        
        .categoria-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
        }
        
        .categoria-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .categoria-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .categoria-card:hover .categoria-image img {
            transform: scale(1.1);
        }
        
        .categoria-color-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            opacity: 0.7;
        }
        
        .categoria-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1), rgba(0,0,0,0.7));
            z-index: 1;
        }
        
        .categoria-card h3 {
            position: absolute;
            bottom: 20px;
            left: 20px;
            color: white;
            font-size: 20px;
            z-index: 2;
            margin: 0;
            transition: transform 0.3s ease;
        }
        
        .categoria-count {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(255,255,255,0.2);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            z-index: 2;
        }
        
        .categoria-card:hover h3 {
            transform: translateY(-5px);
        }
        
        /* Mensaje cuando no hay datos */
        .no-data-message {
            text-align: center;
            padding: 2rem;
            background-color: white;
            border-radius: 12px;
            margin: 2rem 0;
            box-shadow: var(--card-shadow);
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
    <div class="placeholder-container">
        <!-- Empty container to balance the grid layout -->
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
        <ul>
            <li><a href="index.php">Inicio</a></li>
            <li><a href="backend/agregar_receta.php">Agregar Recetas</a></li>
            <li><a href="recetas.php">Recetas</a></li>
            <li><a href="categorias.php">Agregar Categorias</a></li>
            <li><a href="categorias.php">Categorias</a></li>
        </ul>
    </div>
</div>
    
    <header>
        <div class="container">
            <div class="header-content">
                <h1>Recetario</h1>
                <h3>Las mejores recetas de Eugenie Herrero</h3>
            </div>
        </div>
    </header>

    <main>
        <section class="recent-recipes">
            <div class="container">
                <h2 class="section-title">Recetas Recientes</h2>
                
                <?php if (empty($recetasRecientes)): ?>
                    <div class="no-data-message">
                        <p>Aún no hay recetas disponibles. ¡Añade tu primera receta!</p>
                        <a href="backend/agregar_receta.php" class="btn-agregar-receta">Agregar Receta</a>
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
                        <p>Aún no hay categorías disponibles. ¡Crea la primera categoría!</p>
                        <a href="categorias.php" class="btn-agregar-receta">Ir a Categorías</a>
                    </div>
                <?php else: ?>
                    <div class="categories-container">
                        <?php foreach ($categoriasPopulares as $categoria): ?>
                            <a href="recetas.php?categoria=<?php echo urlencode($categoria['nombre']); ?>" class="categoria-card">
                                <div class="categoria-color-overlay" style="background-color: <?php echo $categoria['color']; ?>;"></div>
                                
                                <!-- Imagen para la categoría (intentamos encontrar una imagen de receta de esta categoría) -->
                                <div class="categoria-image">
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
                                
                                <span class="categoria-count">
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
                        <li><a href="categorias.php">Categorias</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="js/menu.js"></script>
    <script src="js/recipe-cards.js"></script>

</body>

</html>