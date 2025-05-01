<?php
require_once 'backend/db.php'; // Conexión a la base de datos
session_start(); // Soporte para mensajes de sesión

// Inicializar variables
$categorias = [];
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

// Procesar formulario para añadir nueva categoría
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion']) && $_POST['accion'] == 'crear_categoria') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $color = isset($_POST['color']) ? trim($_POST['color']) : '#' . dechex(rand(0x000000, 0xFFFFFF));
    
    // Validar datos
    $errores = [];
    if (empty($nombre)) {
        $errores[] = "El nombre de la categoría es obligatorio.";
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

// Eliminar categoría si se solicita
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
    <title>Categorías | Eugenie Herrero</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/categorias.css">
</head>
<body>
    <nav>
        <button class="menu-button">☰</button>
        <ul>
          <li><a href="index.php">Home</a></li>
          <li><a href="recetas.php">Recetas</a></li>
          <li><a href="categorias.php" class="active">Categorias</a></li>
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
                <h1>Categorías</h1>
                <p>Explora y organiza tus recetas por categoría</p>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Formulario para agregar nueva categoría -->
            <div class="nueva-categoria-container">
                <h3>Añadir Nueva Categoría</h3>
                <form class="nueva-categoria-form" method="POST" action="categorias.php">
                    <input type="hidden" name="accion" value="crear_categoria">
                    
                    <div class="form-group">
                        <label for="nombre">Nombre*</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción (opcional)</label>
                        <input type="text" id="descripcion" name="descripcion">
                    </div>
                    
                    <div class="form-group">
                        <label for="color">Color</label>
                        <div style="display: flex; align-items: center;">
                            <span class="color-preview" id="colorPreview" style="background-color: #ff9a9e;"></span>
                            <input type="hidden" id="color" name="color" value="#ff9a9e">
                        </div>
                        <div id="colorOptions">
                            <div class="color-option selected" data-color="#ff9a9e" style="background-color: #ff9a9e;"></div>
                            <div class="color-option" data-color="#a18cd1" style="background-color: #a18cd1;"></div>
                            <div class="color-option" data-color="#fad0c4" style="background-color: #fad0c4;"></div>
                            <div class="color-option" data-color="#84fab0" style="background-color: #84fab0;"></div>
                            <div class="color-option" data-color="#8fd3f4" style="background-color: #8fd3f4;"></div>
                            <div class="color-option" data-color="#d4fc79" style="background-color: #d4fc79;"></div>
                            <div class="color-option" data-color="#fccb90" style="background-color: #fccb90;"></div>
                            <div class="color-option" data-color="#a6c0fe" style="background-color: #a6c0fe;"></div>
                        </div>
                    </div>
                    
                    <button type="submit">Crear Categoría</button>
                </form>
            </div>
            
            <?php if (empty($categorias)): ?>
                <div class="categorias-vacio">
                    <p>Aún no hay categorías disponibles. Agrega una categoría usando el formulario de arriba.</p>
                </div>
            <?php else: ?>
                <div class="categorias-grid">
                    <?php foreach ($categorias as $categoria): ?>
                        <div class="categoria-card">
                            <div class="categoria-header" style="background-color: <?php echo htmlspecialchars($categoria['color']); ?>">
                                <div class="categoria-acciones">
                                    <?php if ($categoria['recetas_count'] == 0): ?>
                                    <a href="categorias.php?eliminar=<?php echo $categoria['id']; ?>" class="btn-accion" title="Eliminar categoría" onclick="return confirm('¿Estás seguro de eliminar esta categoría?')">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <h3><?php echo htmlspecialchars($categoria['nombre']); ?></h3>
                                <span class="categoria-count">
                                    <?php echo $categoria['recetas_count']; ?> receta<?php echo ($categoria['recetas_count'] != 1) ? 's' : ''; ?>
                                </span>
                            </div>
                            <div class="categoria-body">
                                <?php if (!empty($categoria['descripcion'])): ?>
                                    <p><?php echo htmlspecialchars($categoria['descripcion']); ?></p>
                                <?php endif; ?>
                                <a href="recetas.php?categoria=<?php echo urlencode($categoria['nombre']); ?>" class="btn-ver-recetas">
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
                        <li><a href="categorias.php">Categorias</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Asegurar que el navbar esté fijo
            const nav = document.querySelector('nav');
            nav.style.position = 'fixed';
            nav.style.top = '0';
            nav.style.left = '0';
            nav.style.width = '100%';
            nav.style.zIndex = '1000';
            
            // Ajustar padding del body
            document.body.style.paddingTop = '60px';
            
            // Manejo del menú responsivo
            const menuButton = document.querySelector('.menu-button');
            const navUl = document.querySelector('nav ul');
            
            menuButton.addEventListener('click', function() {
                navUl.classList.toggle('show');
            });
            
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
            
            // Selección de color
            const colorOptions = document.querySelectorAll('#colorOptions .color-option');
            const colorInput = document.querySelector('#color');
            const colorPreview = document.querySelector('#colorPreview');
            
            colorOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const color = this.getAttribute('data-color');
                    colorInput.value = color;
                    colorPreview.style.backgroundColor = color;
                    
                    // Quitar selección previa
                    document.querySelector('#colorOptions .selected').classList.remove('selected');
                    // Añadir selección actual
                    this.classList.add('selected');
                });
            });
        });
    </script>
    <script src="js/menu.js"></script>
    <script src="js/recipe-cards.js"></script>
</body>
</html>