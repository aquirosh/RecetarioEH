<?php
require_once 'backend/db.php'; // Conexión a la base de datos
session_start(); // Soporte para mensajes de sesión

// Inicializar variables
$resultados = [];
$termino_busqueda = '';
$mensaje = null;
$tipo_mensaje = null;
$total_resultados = 0;

// Comprobar si hay un mensaje en la sesión
if (isset($_SESSION['mensaje']) && isset($_SESSION['tipo_mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = $_SESSION['tipo_mensaje'];
    // Limpiar mensajes de la sesión
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}

// Verificar si se ha realizado una búsqueda
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $termino_busqueda = htmlspecialchars(trim($_GET['q']));
    
    try {
        // Buscar SOLO en recetas (título, ingredientes)
        $sql = "SELECT r.*, c.nombre as categoria_nombre, c.color as categoria_color 
                FROM recetas r
                LEFT JOIN categorias c ON r.categoria_id = c.id
                WHERE r.title LIKE :termino 
                   OR r.ingredients LIKE :termino 
                ORDER BY r.title ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':termino' => '%' . $termino_busqueda . '%']);
        $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ya no buscamos en categorías
        $categorias = [];
        
        // Combinar resultados (ahora solo recetas)
        $resultados = [
            'recetas' => $recetas,
            'categorias' => [] // Array vacío de categorías
        ];
        
        $total_resultados = count($recetas);
        
        if ($total_resultados == 0) {
            $mensaje = "No se encontraron recetas para: \"$termino_busqueda\"";
            $tipo_mensaje = "error";
        }
        
    } catch (PDOException $e) {
        $mensaje = "Error al realizar la búsqueda: " . $e->getMessage();
        $tipo_mensaje = "error";
    }
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

// Función para resaltar el término de búsqueda en un texto
function resaltar($texto, $termino) {
    if (empty($termino)) return $texto;
    
    $termino = preg_quote($termino, '/');
    return preg_replace("/($termino)/i", '<span class="resaltado">$1</span>', $texto);
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
    <title>Buscar | Recetario</title>
    <link rel="icon" href="img/recetario.png" type="image/png">
    <link rel="shortcut icon" href="img/recetario.png" type="image/png">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/search.css">
    <link rel="stylesheet" href="css/categorias.css">
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
        <div class="nav-search-container">
            <form action="search.php" method="GET" class="nav-search-form">
                <input type="text" name="q" placeholder="Buscar..." class="nav-search-input">
                <button type="submit" class="nav-search-button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </form>
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
                <li><a href="search.php">Búsqueda</a></li>
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
                <h1>Buscar Recetas</h1>
                <div class="search-bar-container">
                    <form action="search.php" method="GET" class="search-form">
                        <input type="text" name="q" id="search-input" class="search-input" 
                               placeholder="Buscar recetas por título o ingredientes..." 
                               value="<?php echo $termino_busqueda; ?>" required>
                        <button type="submit" class="search-button">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                        </button>
                    </form>
                </div>
                <?php if (!empty($termino_busqueda)): ?>
                    <p class="search-results-summary">
                        <?php if ($total_resultados > 0): ?>
                            Se encontraron <?php echo $total_resultados; ?> recetas para: 
                            <strong>"<?php echo $termino_busqueda; ?>"</strong>
                        <?php else: ?>
                            No se encontraron recetas para: <strong>"<?php echo $termino_busqueda; ?>"</strong>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <?php if (!empty($termino_busqueda) && $total_resultados > 0): ?>
                
                <!-- Resultados de Recetas -->
                <section class="search-section">
                    <h2 class="section-title">Recetas encontradas</h2>
                    <div class="search-results-grid">
                        <?php foreach ($resultados['recetas'] as $receta): ?>
                            <a href="receta.php?id=<?php echo $receta['id']; ?>" class="receta-search-item">
                                <div class="receta-search-image">
                                    <?php if (!empty($receta['image_path']) && imagenExiste($receta['image_path'])): ?>
                                        <img src="<?php echo htmlspecialchars($receta['image_path']); ?>" alt="<?php echo htmlspecialchars($receta['title']); ?>">
                                    <?php elseif (!empty($receta['image_url']) && imagenExiste($receta['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($receta['image_url']); ?>" alt="<?php echo htmlspecialchars($receta['title']); ?>">
                                    <?php else: ?>
                                        <img src="img/placeholder-receta.jpg" alt="<?php echo htmlspecialchars($receta['title']); ?>">
                                    <?php endif; ?>
                                    <div class="recipe-time">
                                        <?php echo formatearTiempo($receta['prep_time_minutes'] + $receta['cook_time_minutes']); ?>
                                    </div>
                                </div>
                                
                                <div class="receta-search-content">
                                    <h3><?php echo resaltar(htmlspecialchars($receta['title']), $termino_busqueda); ?></h3>
                                    
                                    <div class="receta-search-meta">
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
                                    
                                    <?php
                                    // Buscar el término en los ingredientes para mostrar contexto
                                    $ingredientes = $receta['ingredients'];
                                    $hayCoincidencia = stripos($ingredientes, $termino_busqueda) !== false;
                                    
                                    if ($hayCoincidencia):
                                        // Obtener el contexto alrededor de la coincidencia
                                        $posicion = stripos($ingredientes, $termino_busqueda);
                                        $inicio = max(0, $posicion - 40);
                                        $longitud = strlen($termino_busqueda) + 80; // Mostrar 40 caracteres antes y después
                                        
                                        $contexto = substr($ingredientes, $inicio, $longitud);
                                        // Agregar puntos suspensivos si no empezamos desde el principio
                                        if ($inicio > 0) $contexto = '...' . $contexto;
                                        // Agregar puntos suspensivos si no llegamos al final
                                        if ($inicio + $longitud < strlen($ingredientes)) $contexto .= '...';
                                    ?>
                                        <div class="search-highlight">
                                            <p><?php echo resaltar(htmlspecialchars($contexto), $termino_busqueda); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <span class="categoria-tag" style="background-color: <?php echo !empty($receta['categoria_color']) ? $receta['categoria_color'] : 'var(--primary)'; ?>">
                                        <?php echo !empty($receta['categoria_nombre']) ? 
                                            htmlspecialchars($receta['categoria_nombre']) : 
                                            htmlspecialchars($receta['category']); ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                
            <?php elseif (!empty($termino_busqueda)): ?>
                <div class="no-results">
                    <div class="no-results-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            <line x1="8" y1="11" x2="14" y2="11"></line>
                        </svg>
                    </div>
                    <h2>No se encontraron recetas</h2>
                    <p>No encontramos recetas que coincidan con "<strong><?php echo $termino_busqueda; ?></strong>"</p>
                    <div class="no-results-suggestions">
                        <h3>Sugerencias:</h3>
                        <ul>
                            <li>Verifica la ortografía de las palabras</li>
                            <li>Intenta con términos más generales</li>
                            <li>Prueba con ingredientes específicos</li>
                            <li>Busca por nombre de receta</li>
                        </ul>
                    </div>
                    <a href="recetas.php" class="btn">Ver todas las recetas</a>
                </div>
            <?php else: ?>
                <div class="search-info">
                    <div class="search-info-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </div>
                    <h2>Busca recetas por título o ingredientes</h2>
                    <p>Encuentra fácilmente la receta que estás buscando con nuestra herramienta de búsqueda</p>
                    <div class="search-examples">
                        <h3>Ejemplos de búsqueda:</h3>
                        <div class="search-examples-grid">
                            <a href="search.php?q=pan" class="search-example-item">
                                <span class="search-example-term">pan</span>
                            </a>
                            <a href="search.php?q=pollo" class="search-example-item">
                                <span class="search-example-term">pollo</span>
                            </a>
                            <a href="search.php?q=postre" class="search-example-item">
                                <span class="search-example-term">postre</span>
                            </a>
                            <a href="search.php?q=huevos" class="search-example-item">
                                <span class="search-example-term">huevos</span>
                            </a>
                        </div>
                    </div>
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
                        <li><a href="search.php">Búsqueda</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="js/menu.js"></script>
    <script src="js/search.js"></script>
</body>
</html>