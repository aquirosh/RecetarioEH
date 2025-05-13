<?php
require_once 'db.php'; // Conexión a la base de datos
session_start(); // Iniciamos sesión para manejar mensajes

// Inicialización de variables
$receta = null;
$categorias = [];
$error = null;
$mensaje = null;
$tipo_mensaje = null;

// Función para subir imagen
function subirImagen($file) {
    // Verificar si se subió un archivo
    if ($file['error'] == UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => null, 'filename' => null];
    }
    
    // Verificar errores de carga
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errores = [
            UPLOAD_ERR_INI_SIZE => "El archivo excede el tamaño máximo permitido por el servidor.",
            UPLOAD_ERR_FORM_SIZE => "El archivo excede el tamaño máximo permitido por el formulario.",
            UPLOAD_ERR_PARTIAL => "El archivo se subió parcialmente.",
            UPLOAD_ERR_NO_TMP_DIR => "No se encuentra el directorio temporal.",
            UPLOAD_ERR_CANT_WRITE => "Error al escribir el archivo.",
            UPLOAD_ERR_EXTENSION => "Una extensión PHP detuvo la carga."
        ];
        $mensajeError = isset($errores[$file['error']]) ? $errores[$file['error']] : "Error desconocido al subir el archivo.";
        return ['success' => false, 'error' => $mensajeError, 'filename' => null];
    }
    
    // Validar tipo de archivo
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => "Tipo de archivo no permitido. Sólo se aceptan imágenes JPG, PNG, GIF y WEBP.", 'filename' => null];
    }
    
    // Validar tamaño (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB en bytes
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => "La imagen excede el tamaño máximo permitido (5MB).", 'filename' => null];
    }
    
    // Crear directorio si no existe
    $uploadDir = '../uploads/recetas/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            return ['success' => false, 'error' => "No se pudo crear el directorio para guardar las imágenes.", 'filename' => null];
        }
    }
    
    // Generar nombre único para el archivo
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('receta_') . '.' . $extension;
    $targetFile = $uploadDir . $filename;
    
    // Intentar mover el archivo
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return ['success' => true, 'error' => null, 'filename' => 'uploads/recetas/' . $filename];
    } else {
        return ['success' => false, 'error' => "Error al guardar la imagen en el servidor.", 'filename' => null];
    }
}

// Comprobar si hay un mensaje en la sesión
if (isset($_SESSION['mensaje']) && isset($_SESSION['tipo_mensaje'])) {
    $mensaje = $_SESSION['mensaje'];
    $tipo_mensaje = $_SESSION['tipo_mensaje'];
    // Limpiar mensajes de la sesión
    unset($_SESSION['mensaje']);
    unset($_SESSION['tipo_mensaje']);
}

// Obtener las categorías disponibles
try {
    $stmtCategorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre ASC");
    $categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al obtener categorías: " . $e->getMessage();
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

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_receta'])) {
    // Validar y recoger los datos del formulario
    $titulo = trim($_POST['titulo'] ?? '');
    $categoria = trim($_POST['category'] ?? '');
    $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
    $ingredientes = trim($_POST['ingredientes'] ?? '');
    $preparacion = trim($_POST['preparacion'] ?? '');
    $tiempo_prep = (int)($_POST['tiempo_prep'] ?? 0);
    $tiempo_coccion = ($_POST['tiempo_coccion'] === '') ? 0 : (int)$_POST['tiempo_coccion']; // MODIFICADO: Asignar 0 si está vacío
    $porciones = ($_POST['porciones'] ?? '');
    $recetaId = (int)($_POST['receta_id'] ?? 0);
    
    // Array para almacenar errores de validación
    $errores = [];
    
    // Validaciones básicas
    if (empty($titulo)) {
        $errores[] = "El título de la receta es obligatorio.";
    }
    
    if (empty($categoria)) {
        $errores[] = "La categoría es obligatoria.";
    }
    
    if (empty($ingredientes)) {
        $errores[] = "La lista de ingredientes es obligatoria.";
    }
    
    if (empty($preparacion)) {
        $errores[] = "Los pasos de preparación son obligatorios.";
    }
    
    if ($tiempo_prep <= 0) {
        $errores[] = "El tiempo de preparación debe ser mayor a 0.";
    }
    
    // MODIFICADO: Eliminamos esta validación ya que el tiempo de cocción puede ser 0
    // if ($tiempo_coccion < 0) {
    //     $errores[] = "El tiempo de cocción no puede ser negativo.";
    // }
    
    // Si es una categoría nueva, insertarla primero y obtener su ID
    if ($categoria === 'nueva' && !empty($_POST['nuevaCategoria'])) {
        $nuevaCategoria = trim(htmlspecialchars($_POST['nuevaCategoria']));
        if (!empty($nuevaCategoria)) {
            try {
                // Verificar si la categoría ya existe
                $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nombre");
                $stmt->execute([':nombre' => $nuevaCategoria]);
                if ($stmt->rowCount() > 0) {
                    // Si existe, obtener su ID
                    $categoria_id = $stmt->fetchColumn();
                    $categoria = $nuevaCategoria;
                } else {
                    // Si no existe, crearla
                    $stmt = $pdo->prepare("INSERT INTO categorias (nombre) VALUES (:nombre)");
                    $stmt->execute([':nombre' => $nuevaCategoria]);
                    $categoria_id = $pdo->lastInsertId();
                    $categoria = $nuevaCategoria;
                }
            } catch (PDOException $e) {
                $errores[] = "Error al crear nueva categoría: " . $e->getMessage();
            }
        } else {
            $errores[] = "El nombre de la nueva categoría no puede estar vacío.";
        }
    }
    // Si la categoría seleccionada no tiene ID asociado, buscarla o crearla
    elseif (!$categoria_id && !empty($categoria) && $categoria !== 'nueva') {
        try {
            // Verificar si la categoría existe
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nombre");
            $stmt->execute([':nombre' => $categoria]);
            if ($stmt->rowCount() > 0) {
                $categoria_id = $stmt->fetchColumn();
            } else {
                // Si no existe, crearla
                $stmt = $pdo->prepare("INSERT INTO categorias (nombre) VALUES (:nombre)");
                $stmt->execute([':nombre' => $categoria]);
                $categoria_id = $pdo->lastInsertId();
            }
        } catch (PDOException $e) {
            $errores[] = "Error al procesar la categoría: " . $e->getMessage();
        }
    }
    
    // Procesar la imagen si se ha subido una nueva
    $imagen_actual = $receta['image_path'] ?? '';
    
    // Si se subió una nueva imagen
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] != UPLOAD_ERR_NO_FILE) {
        $imagenResultado = subirImagen($_FILES['imagen']);
        if (!$imagenResultado['success'] && $imagenResultado['error'] !== null) {
            $errores[] = $imagenResultado['error'];
        } else if ($imagenResultado['success']) {
            // Si hay una imagen anterior, la eliminamos
            if (!empty($imagen_actual) && file_exists('../' . $imagen_actual)) {
                unlink('../' . $imagen_actual);
            }
            $imagen_actual = $imagenResultado['filename'];
        }
    }
    
    // Si no hay errores, actualizar la receta
    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE recetas SET 
                    title = :titulo,
                    category = :categoria,
                    categoria_id = :categoria_id,
                    portions = :porciones,
                    prep_time_minutes = :tiempo_prep,
                    cook_time_minutes = :tiempo_coccion,
                    ingredients = :ingredientes,
                    preparation_steps = :preparacion,
                    image_path = :imagen_path
                WHERE id = :id
            ");
            
            $resultado = $stmt->execute([
                ':titulo' => $titulo,
                ':categoria' => $categoria,
                ':categoria_id' => $categoria_id,
                ':porciones' => $porciones,
                ':tiempo_prep' => $tiempo_prep,
                ':tiempo_coccion' => $tiempo_coccion,
                ':ingredientes' => $ingredientes,
                ':preparacion' => $preparacion,
                ':imagen_path' => $imagen_actual,
                ':id' => $recetaId
            ]);
            
            if ($resultado) {
                $_SESSION['mensaje'] = "La receta se ha actualizado correctamente.";
                $_SESSION['tipo_mensaje'] = "success";
                header("Location: ../receta.php?id=" . $recetaId);
                exit;
            } else {
                $error = "Error al actualizar la receta.";
            }
        } catch (PDOException $e) {
            $error = "Error de base de datos: " . $e->getMessage();
        }
    } else {
        // Si hay errores, los mostramos
        $error = "Por favor corrige los siguientes errores:<ul>";
        foreach ($errores as $err) {
            $error .= "<li>" . htmlspecialchars($err) . "</li>";
        }
        $error .= "</ul>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Receta | Recetario QH</title>

    <link rel="icon" href="img/recetario.png" type="image/png">
    <link rel="shortcut icon" href="img/recetario.png" type="image/png">

    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/search.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Crimson+Pro:wght@400;600&display=swap" rel="stylesheet">
    <style>
        .image-preview-container {
            margin-top: 10px;
            max-width: 300px;
        }
        #imagePreview {
            max-width: 100%;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .file-input-container {
            position: relative;
            overflow: hidden;
            margin-top: 10px;
        }
        .file-input-container input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }
        .file-input-button {
            display: inline-block;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 8px 16px;
            cursor: pointer;
            color: #333;
        }
        .file-name {
            margin-left: 10px;
            font-size: 0.9em;
            color: #666;
        }
        
        /* Estilos para la selección de categoría */
        .nueva-categoria-container {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        
        .btn-agregar-categoria {
            background-color: var(--secondary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.2s;
        }
        
        .btn-agregar-categoria:hover {
            background-color: var(--primary);
            transform: translateY(-2px);
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
            <a href="../index.php" class="nav-brand">Recetario</a>
        </div>
        <div class="nav-search-container">
        <form action="../search.php" method="GET" class="nav-search-form">
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
                <li><a href="../index.php">Inicio</a></li>
                <li><a href="agregar_receta.php">Agregar Recetas</a></li>
                <li><a href="../recetas.php">Recetas</a></li>
                <li><a href="../categorias.php">Agregar Categorias</a></li>
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
                <div class="message error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($receta): ?>
                <div class="form-container">
                    <div class="form-header">
                        <h2>Editar Receta</h2>
                        <p>Modifica los detalles de tu receta</p>
                    </div>
                    
                    <form action="editar_receta.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="receta_id" value="<?php echo $receta['id']; ?>">
                        
                        <div class="form-group">
                            <label for="titulo" class="required-field">Título de la Receta</label>
                            <input type="text" id="titulo" name="titulo" class="form-control" value="<?php echo htmlspecialchars($receta['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="category" class="required-field">Categoría</label>
                            <select id="category" name="category" class="form-control" required>
                                <option value="">Seleccionar categoría</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['nombre']); ?>" 
                                            <?php echo ($receta['category'] === $cat['nombre']) ? 'selected' : ''; ?>
                                            data-id="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="nueva">+ Añadir nueva categoría</option>
                            </select>
                            <input type="hidden" name="categoria_id" id="categoria_id" value="<?php echo $receta['categoria_id']; ?>">
                            <div id="nuevaCategoriaContainer" class="nueva-categoria-container" style="display: none;">
                                <input type="text" id="nuevaCategoria" name="nuevaCategoria" class="form-control"
                                    placeholder="Nombre de nueva categoría">
                                <button type="button" id="btnAgregarCategoria"
                                    class="btn-agregar-categoria">Agregar</button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="porciones">Porciones</label>
                            <input type="text" id="porciones" name="porciones" class="form-control" value="<?php echo htmlspecialchars($receta['portions']); ?>" placeholder="Ej: 4 personas">
                        </div>
                        
                        <div class="form-group">
                            <label for="tiempo_prep" class="required-field">Tiempo de Preparación (minutos)</label>
                            <input type="number" id="tiempo_prep" name="tiempo_prep" class="form-control" value="<?php echo $receta['prep_time_minutes']; ?>" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="tiempo_coccion">Tiempo de Cocción (minutos)</label>
                            <input type="number" id="tiempo_coccion" name="tiempo_coccion" class="form-control" value="<?php echo $receta['cook_time_minutes']; ?>" min="0">
                            <p class="help-text">Usar 0 para recetas sin cocción (como ensaladas)</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="ingredientes" class="required-field">Ingredientes</label>
                            <div class="ingredientes-tips">
                                <h4>
                                    <svg class="tip-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                    Consejos para los ingredientes
                                </h4>
                                <p>Escribe un ingrediente por línea. Incluye la cantidad y la unidad (ej: 200g de harina).</p>
                            </div>
                            <textarea id="ingredientes" name="ingredientes" class="form-control" rows="10" required><?php echo htmlspecialchars($receta['ingredients']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="preparacion" class="required-field">Pasos de Preparación</label>
                            <div class="preparacion-tips">
                                <h4>
                                    <svg class="tip-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="16" x2="12" y2="12"></line>
                                        <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                    </svg>
                                    Consejos para la preparación
                                </h4>
                                <p>Escribe cada paso en una línea nueva. Sé claro y específico con las instrucciones.</p>
                            </div>
                            <textarea id="preparacion" name="preparacion" class="form-control" rows="12" required><?php echo htmlspecialchars($receta['preparation_steps']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="imagen">Imagen de la Receta</label>
                            <?php if (!empty($receta['image_path'])): ?>
                                <div class="image-preview-container" style="margin-bottom:10px;">
                                    <p>Imagen actual:</p>
                                    <img id="imagePreview" src="<?php echo '../' . htmlspecialchars($receta['image_path']); ?>" alt="Imagen actual">
                                </div>
                            <?php else: ?>
                                <p><strong>No hay imagen actualmente.</strong></p>
                                <div class="image-preview-container" style="display:none;">
                                    <img id="imagePreview" src="#" alt="Vista previa de la imagen">
                                </div>
                            <?php endif; ?>
                            
                            <div class="file-input-container">
                                <div class="file-input-button">Seleccionar Imagen</div>
                                <input type="file" id="imagen" name="imagen" accept="image/*">
                                <span class="file-name" id="fileName">
                                    <?php echo !empty($receta['image_path']) ? 'Mantener imagen actual' : 'No se ha seleccionado archivo'; ?>
                                </span>
                            </div>
                            <p class="help-text">Si no seleccionas una nueva imagen, se mantendrá la imagen actual. Formatos aceptados: JPG, PNG, GIF, WEBP. Tamaño máximo: 5MB.</p>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="editar_receta" class="btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                    <polyline points="7 3 7 8 15 8"></polyline>
                                </svg>
                                Guardar Cambios
                            </button>
                            <a href="../receta.php?id=<?php echo $receta['id']; ?>" class="btn-secondary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;">
                                    <line x1="19" y1="12" x2="5" y2="12"></line>
                                    <polyline points="12 19 5 12 12 5"></polyline>
                                </svg>
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="error-container">
                    <h2>Error</h2>
                    <p>No se pudo cargar la receta para editar.</p>
                    <a href="../recetas.php" class="btn btn-primary">Volver a Recetas</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <a href="../index.php">Recetario</a>
                    <p>Cocina casera para todos los días</p>
                </div>
                
                <div class="footer-links">
                    <h3>Navegación</h3>
                    <ul>
                        <li><a href="../index.php">Inicio</a></li>
                        <li><a href="../recetas.php">Recetas</a></li>
                        <li><a href="../categorias.php">Categorias</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Manejar el campo id de la categoría
            document.getElementById('category').addEventListener('change', function () {
                var nuevaCategoriaContainer = document.getElementById('nuevaCategoriaContainer');
                var categoriaId = this.options[this.selectedIndex].getAttribute('data-id');
                document.getElementById('categoria_id').value = categoriaId || '';
                
                if (this.value === 'nueva') {
                    nuevaCategoriaContainer.style.display = 'flex';
                    document.getElementById('nuevaCategoria').focus();
                } else {
                    nuevaCategoriaContainer.style.display = 'none';
                }
            });

            // Asignar nueva categoría al seleccionar
            document.getElementById('btnAgregarCategoria').addEventListener('click', function () {
                var nuevaCategoria = document.getElementById('nuevaCategoria').value.trim();
                if (nuevaCategoria) {
                    var categorySelect = document.getElementById('category');
                    var nuevaOption = document.createElement('option');
                    nuevaOption.value = nuevaCategoria;
                    nuevaOption.text = nuevaCategoria;
                    nuevaOption.selected = true;
                    categorySelect.add(nuevaOption, categorySelect.length - 1);
                    document.getElementById('nuevaCategoriaContainer').style.display = 'none';
                }
            });

            // Mostrar vista previa de la imagen
            document.getElementById('imagen').addEventListener('change', function() {
                const file = this.files[0];
                const fileName = document.getElementById('fileName');
                const imagePreview = document.getElementById('imagePreview');
                const previewContainer = imagePreview.parentElement;
                
                if (file) {
                    fileName.textContent = file.name;
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        previewContainer.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                } else {
                    <?php if (!empty($receta['image_path'])): ?>
                    fileName.textContent = 'Mantener imagen actual';
                    <?php else: ?>
                    fileName.textContent = 'No se ha seleccionado archivo';
                    previewContainer.style.display = 'none';
                    <?php endif; ?>
                }
            });
            
            // Permitir usar Enter para agregar nueva categoría
            document.getElementById('nuevaCategoria').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('btnAgregarCategoria').click();
                }
            });
        });
    </script>
    <script src="../js/menu.js"></script>
</body>
</html>