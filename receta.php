<?php
require_once 'backend/db.php'; // Ruta unificada para la conexión
session_start(); // Añadimos soporte para sesiones

// Inicializar variables
$receta = null;
$error = null;
$mensaje = null;
$tipo_mensaje = null;

// Comprobar si hay un mensaje en la sesión
if (isset($_SESSION['mensaje']) && isset($_SESSION['tipo_mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = $_SESSION['tipo_mensaje'];
    // Limpiar mensajes de la sesión
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}

// Verificar si se ha proporcionado un ID válido
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $recetaId = (int)$_GET['id'];
    
    try {
        // Consultar la receta por ID
        $stmt = $pdo->prepare("SELECT * FROM recetas WHERE id = :id");
        $stmt->execute([':id' => $recetaId]);
        $receta = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$receta) {
            $error = "No se encontró la receta con ID: $recetaId";
        }
    } catch (PDOException $e) {
        $error = "Error al consultar la base de datos: " . $e->getMessage();
    }
} else {
    $error = "ID de receta no válido o no proporcionado.";
}

// Función para formatear los ingredientes
function formatearIngredientes($ingredientes) {
    $lineas = explode("\n", $ingredientes);
    $html = '<ul class="ingredientes-lista">';
    
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if (!empty($linea)) {
            $html .= '<li>' . htmlspecialchars($linea) . '</li>';
        }
    }
    
    $html .= '</ul>';
    return $html;
}

// Función para formatear los pasos de preparación
function formatearPasos($pasos) {
    $lineas = explode("\n", $pasos);
    $html = '<ol class="pasos-lista">';
    
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if (!empty($linea)) {
            $html .= '<li>' . htmlspecialchars($linea) . '</li>';
        }
    }
    
    $html .= '</ol>';
    return $html;
}

// Función para calcular el tiempo total
function calcularTiempoTotal($preparacion, $coccion) {
    return $preparacion + $coccion;
}

// Función para formatear minutos en formato legible
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
    <title><?php echo $receta ? htmlspecialchars($receta['title']) : 'Receta no encontrada'; ?> | Recetario</title>

    <link rel="icon" href="img/recetario.png" type="image/png">
    <link rel="shortcut icon" href="img/recetario.png" type="image/png">

    <link rel="stylesheet" href="css/receta.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/search.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Crimson+Pro:wght@400;600&display=swap" rel="stylesheet">
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

    <main>
        <div class="container">
            <?php if ($error): ?>
                <div class="error-container">
                    <h2>Error</h2>
                    <p><?php echo $error; ?></p>
                    <a href="recetas.php" class="btn btn-primary">Volver a Recetas</a>
                </div>
            <?php elseif ($receta): ?>
                <div class="receta-detalle">
                    <div class="receta-header">
                        <div class="receta-titulo">
                            <h1><?php echo htmlspecialchars($receta['title']); ?></h1>
                            <span class="categoria-badge"><?php echo htmlspecialchars($receta['category']); ?></span>
                        </div>
                        <div class="receta-acciones">
                            <a href="backend/editar_receta.php?id=<?php echo $receta['id']; ?>" class="btn btn-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                </svg>
                                Editar
                            </a>
                            <button class="btn btn-danger" onclick="confirmarEliminar(<?php echo $receta['id']; ?>)">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                                Eliminar
                            </button>
                        </div>
                    </div>

                    <div class="receta-layout">
                        <!-- Imagen e información rápida de la receta -->
                        <div class="receta-info-superior">
                            <!-- Imagen de la receta -->
                            <?php if (!empty($receta['image_path'])): ?>
                                <div class="receta-imagen">
                                    <img src="<?php echo htmlspecialchars($receta['image_path']); ?>" alt="<?php echo htmlspecialchars($receta['title']); ?>">
                                </div>
                            <?php elseif (!empty($receta['image_url'])): ?>
                                <div class="receta-imagen">
                                    <img src="<?php echo htmlspecialchars($receta['image_url']); ?>" alt="<?php echo htmlspecialchars($receta['title']); ?>">
                                </div>
                            <?php else: ?>
                                <div class="receta-imagen receta-imagen-placeholder">
                                    <div class="placeholder-content">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 6V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v2"></path>
                                            <path d="M18 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2z"></path>
                                            <rect x="10" y="10" width="4" height="4"></rect>
                                        </svg>
                                        <p>Sin imagen</p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Información rápida sobre la receta -->
                            <div class="receta-info-general">
                                <div class="receta-info-rapida">
                                    <div class="info-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <div>
                                            <span class="info-label">Prep.</span>
                                            <span class="info-valor"><?php echo formatearTiempo($receta['prep_time_minutes']); ?></span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        <div>
                                            <span class="info-label">Cocción</span>
                                            <span class="info-valor"><?php echo formatearTiempo($receta['cook_time_minutes']); ?></span>
                                        </div>
                                    </div>
                                    <div class="info-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <line x1="12" y1="8" x2="12" y2="12"></line>
                                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                        </svg>
                                        <div>
                                            <span class="info-label">Total</span>
                                            <span class="info-valor"><?php echo formatearTiempo(calcularTiempoTotal($receta['prep_time_minutes'], $receta['cook_time_minutes'])); ?></span>
                                        </div>
                                    </div>
                                    <?php if (!empty($receta['portions'])): ?>
                                    <div class="info-item">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="9" cy="7" r="4"></circle>
                                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                            <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                        </svg>
                                        <div>
                                            <span class="info-label">Porciones</span>
                                            <span class="info-valor"><?php echo htmlspecialchars($receta['portions']); ?></span>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Ingredientes y preparación en columnas paralelas -->
                        <div class="receta-contenido-paralelo">
                            <!-- Columna de ingredientes -->
                            <div class="receta-seccion ingredientes-seccion">
                                <h2>Ingredientes</h2>
                                <?php echo formatearIngredientes($receta['ingredients']); ?>
                            </div>
                            
                            <!-- Columna de preparación -->
                            <div class="receta-seccion preparacion-seccion">
                                <h2>Preparación</h2>
                                <?php echo formatearPasos($receta['preparation_steps']); ?>
                            </div>
                        </div>
                    </div>

                    <div class="receta-footer">
                        <a href="recetas.php" class="btn btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="19" y1="12" x2="5" y2="12"></line>
                                <polyline points="12 19 5 12 12 5"></polyline>
                            </svg>
                            Volver a Recetas
                        </a>
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
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Función para confirmar eliminación
        function confirmarEliminar(id) {
            if (confirm('¿Estás seguro de que deseas eliminar esta receta? Esta acción no se puede deshacer.')) {
                window.location.href = 'backend/eliminar_receta.php?id=' + id;
            }
        }

        // Manejo del menú responsivo
        document.addEventListener('DOMContentLoaded', function() {
            const menuButton = document.querySelector('.menu-button');
            const navUl = document.querySelector('nav ul');
            
            menuButton.addEventListener('click', function() {
                navUl.classList.toggle('show');
            });
        });
    </script>
    <script src="js/menu.js"></script>
    <script src="js/recipe-cards.js"></script>
</body>
</html>