<?php
require_once 'backend/db.php'; // Conexión a la base de datos
session_start(); // Soporte para mensajes de sesión

// Inicializar variables
$recetas = [];
$mensaje = null;
$tipo_mensaje = null;
$filtroCategoria = isset($_GET['categoria']) ? trim($_GET['categoria']) : '';

// Comprobar si hay un mensaje en la sesión
if (isset($_SESSION['mensaje']) && isset($_SESSION['tipo_mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = $_SESSION['tipo_mensaje'];
    // Limpiar mensajes de la sesión
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}

// Construir consulta SQL con posibles filtros
$sql = "SELECT r.*, c.color as categoria_color 
        FROM recetas r
        LEFT JOIN categorias c ON r.categoria_id = c.id";
$params = [];

// Aplicar filtro por categoría si está presente
if (!empty($filtroCategoria)) {
    $sql .= " WHERE r.category = :categoria";
    $params[':categoria'] = $filtroCategoria;
}

$sql .= " ORDER BY r.id DESC"; // Ordenar por más recientes primero

// Intentar obtener las recetas de la base de datos
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje = "Error al cargar las recetas: " . $e->getMessage();
    $tipo_mensaje = "error";
}

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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recetas | Eugenie Herrero</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Estilos específicos para la página de recetas */
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
        
        .recipe-image a {
            display: block;
            height: 100%;
        }
        
        .recipe-time {
            z-index: 2;
        }
        
        .tag.categoria-tag {
            background-color: var(--primary);
            color: white;
        }
        
        /* Mensaje de recetas vacío */
        .recetas-vacio {
            background-color: white;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            margin-top: 1rem;
            box-shadow: var(--card-shadow);
        }
        
        .recetas-vacio p {
            color: var(--light-text);
            margin-bottom: 1.5rem;
        }
        
        /* Botón para quitar filtro */
        .btn-quitar-filtro {
            color: var(--primary);
            text-decoration: none;
            margin-left: 8px;
            font-size: 0.9em;
            padding: 4px 8px;
            border-radius: 4px;
            background-color: var(--light-accent);
            transition: all 0.3s ease;
        }
        
        .btn-quitar-filtro:hover {
            background-color: var(--primary);
            color: white;
        }
    </style>
</head>
<body>
    <nav>
        <button class="menu-button">☰</button>
        <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="recetas.php" class="active">Recetas</a></li>
            <li><a href="categorias.php">Categorías</a></li>
        </ul>
    </nav>
    
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
                <h1>Recetas</h1>
                <?php if (!empty($filtroCategoria)): ?>
                    <h3>Categoría: <?php echo htmlspecialchars($filtroCategoria); ?> <a href="recetas.php" class="btn-quitar-filtro">(Ver todas)</a></h3>
                <?php else: ?>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Botón para crear receta -->
            <div class="agregar-receta-container">
                <a href="backend/agregar_receta.php" class="btn-agregar-receta">Agregar Receta</a>
            </div>
            
            <!-- Listado de recetas en formato de tarjetas -->
            <?php if (empty($recetas)): ?>
                <div class="recetas-vacio">
                    <p>Aún no hay recetas en el recetario.</p>
                </div>
            <?php else: ?>
                <div class="recipes-container">
                    <?php foreach ($recetas as $receta): ?>
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
                                    
                                    <?php if (!empty($receta['tags'])): 
                                        $tags = explode(',', $receta['tags']);
                                        $count = 0;
                                        foreach($tags as $tag):
                                            if ($count < 2 && trim($tag) != ''): // Limitar a 2 etiquetas 
                                                $count++;
                                    ?>
                                            <span class="tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                    <?php 
                                            endif;
                                        endforeach;
                                    endif; ?>
                                </div>
                                
                                <a href="receta.php?id=<?php echo $receta['id']; ?>" class="recipe-link-big">Ver receta</a>
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
                        <li><a href="categorias.php">Categorias</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Manejar evento click en tarjetas de recetas
            const recipeCards = document.querySelectorAll('.recipe-card');
            recipeCards.forEach(card => {
                card.addEventListener('click', function(e) {
                    // Evitar navegación si se hace clic en un enlace
                    if (e.target.tagName === 'A' || e.target.closest('a')) {
                        e.stopPropagation();
                        return;
                    }
                    
                    // Obtener el enlace de esta tarjeta
                    const link = this.querySelector('.recipe-link-big').getAttribute('href');
                    window.location.href = link;
                });
            });
            
            // Manejo del menú responsivo
            const menuButton = document.querySelector('.menu-button');
            if (menuButton) {
                const navUl = document.querySelector('nav ul');
                
                menuButton.addEventListener('click', function() {
                    navUl.classList.toggle('show');
                });
            }
            
            // Ocultar mensaje después de 5 segundos
            const mensajeContainer = document.querySelector('.mensaje-container');
            if (mensajeContainer) {
                setTimeout(function() {
                    mensajeContainer.style.opacity = '0';
                    setTimeout(function() {
                        mensajeContainer.style.display = 'none';
                    }, 1000);
                }, 5000);
            }
        });
    </script>
    <script src="js/menu.js"></script>
    <script src="js/recipe-cards.js"></script>
</body>
</html>