<?php
require_once 'db.php'; // Ruta unificada para la conexión
session_start(); // Añadimos soporte para sesiones

// Función para validar datos
function validarDatos($datos)
{
    $errores = [];

    // Validación de campos requeridos - MODIFICADO: Se quitó cook_time de los campos requeridos
    $camposRequeridos = ['title', 'category', 'prep_time', 'ingredients', 'preparation_steps'];
    foreach ($camposRequeridos as $campo) {
        if (empty(trim($datos[$campo]))) {
            $errores[] = "El campo " . ucfirst(str_replace('_', ' ', $campo)) . " es obligatorio.";
        }
    }

    // Validación de campos numéricos
    if (!empty($datos['prep_time']) && (!is_numeric($datos['prep_time']) || $datos['prep_time'] < 0)) {
        $errores[] = "El tiempo de preparación debe ser un número positivo.";
    }

    // MODIFICADO: Permitir 0 para tiempo de cocción
    if ($datos['cook_time'] === '') {
        // Si está vacío, establecerlo como 0
        $datos['cook_time'] = 0;
    } else if (!is_numeric($datos['cook_time']) || $datos['cook_time'] < 0) {
        $errores[] = "El tiempo de cocción debe ser un número positivo o cero.";
    }

    return $errores;
}

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

// Inicializar variables
$errores = [];
$exito = false;
$recetaId = null;
$datosFormulario = [
    'title' => '',
    'category' => '',
    'portions' => '',
    'prep_time' => '',
    'cook_time' => '',
    'ingredients' => '',
    'preparation_steps' => '',
    'image_path' => ''
];

// Si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir y sanitizar datos
    foreach ($datosFormulario as $campo => $valor) {
        if ($campo !== 'image_path') { // No procesar image_path aquí
            $datosFormulario[$campo] = isset($_POST[$campo]) ? trim(htmlspecialchars($_POST[$campo])) : '';
        }
    }

    // Obtener categoria_id
    $categoria_id = isset($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;

    // Si es una categoría nueva, insertarla primero y obtener su ID
    if ($datosFormulario['category'] === 'nueva' && !empty($_POST['nuevaCategoria'])) {
        $nuevaCategoria = trim(htmlspecialchars($_POST['nuevaCategoria']));
        if (!empty($nuevaCategoria)) {
            try {
                // Verificar si la categoría ya existe
                $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nombre");
                $stmt->execute([':nombre' => $nuevaCategoria]);
                if ($stmt->rowCount() > 0) {
                    // Si existe, obtener su ID
                    $categoria_id = $stmt->fetchColumn();
                    $datosFormulario['category'] = $nuevaCategoria;
                } else {
                    // Si no existe, crearla
                    $stmt = $pdo->prepare("INSERT INTO categorias (nombre) VALUES (:nombre)");
                    $stmt->execute([':nombre' => $nuevaCategoria]);
                    $categoria_id = $pdo->lastInsertId();
                    $datosFormulario['category'] = $nuevaCategoria;
                }
            } catch (PDOException $e) {
                $errores[] = "Error al crear nueva categoría: " . $e->getMessage();
            }
        } else {
            $errores[] = "El nombre de la nueva categoría no puede estar vacío.";
        }
    }
    // Si la categoría seleccionada no tiene ID asociado, buscarla o crearla
    elseif (!$categoria_id && !empty($datosFormulario['category']) && $datosFormulario['category'] !== 'nueva') {
        try {
            // Verificar si la categoría existe
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nombre");
            $stmt->execute([':nombre' => $datosFormulario['category']]);
            if ($stmt->rowCount() > 0) {
                $categoria_id = $stmt->fetchColumn();
            } else {
                // Si no existe, crearla
                $stmt = $pdo->prepare("INSERT INTO categorias (nombre) VALUES (:nombre)");
                $stmt->execute([':nombre' => $datosFormulario['category']]);
                $categoria_id = $pdo->lastInsertId();
            }
        } catch (PDOException $e) {
            $errores[] = "Error al procesar la categoría: " . $e->getMessage();
        }
    }

    // Validar datos
    $errores = array_merge($errores, validarDatos($datosFormulario));

    // Procesar imagen si se subió
    $imagenResultado = subirImagen($_FILES['imagen']);
    if (!$imagenResultado['success'] && $imagenResultado['error'] !== null) {
        $errores[] = $imagenResultado['error'];
    } else if ($imagenResultado['success']) {
        $datosFormulario['image_path'] = $imagenResultado['filename'];
    }

    // Si no hay errores, guardar en la base de datos
    if (empty($errores)) {
        $sql = "INSERT INTO recetas (title, category, categoria_id, portions, prep_time_minutes, cook_time_minutes, ingredients, preparation_steps, image_path)
                VALUES (:title, :category, :categoria_id, :portions, :prep_time, :cook_time, :ingredients, :preparation_steps, :image_path)";

        $stmt = $pdo->prepare($sql);

        try {
            $stmt->execute([
                ':title' => $datosFormulario['title'],
                ':category' => $datosFormulario['category'],
                ':categoria_id' => $categoria_id,
                ':portions' => $datosFormulario['portions'],
                ':prep_time' => (int) $datosFormulario['prep_time'],
                ':cook_time' => (int) $datosFormulario['cook_time'],
                ':ingredients' => $datosFormulario['ingredients'],
                ':preparation_steps' => $datosFormulario['preparation_steps'],
                ':image_path' => $datosFormulario['image_path']
            ]);

            $recetaId = $pdo->lastInsertId();
            $exito = true;

            // Limpiar formulario después de éxito
            foreach ($datosFormulario as $campo => $valor) {
                $datosFormulario[$campo] = '';
            }

            // Almacenar mensaje en sesión y redirigir
            $_SESSION['mensaje'] = "¡Receta agregada exitosamente!";
            $_SESSION['tipo_mensaje'] = "success";
            header("Location: ../receta.php?id=$recetaId");
            exit;

        } catch (PDOException $e) {
            $errores[] = "Error al agregar receta: " . $e->getMessage();
        }
    }
}

// Cargar categorías desde la base de datos
$categorias = [];
try {
    $stmt = $pdo->query("SELECT id, nombre FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejar error silenciosamente
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Receta | Eugenie Herrero</title>
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        /* Estilos para el navbar fijo */
        nav {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            z-index: 1000 !important;
            height: 60px !important;
            box-sizing: border-box !important;
        }
        
        body {
            padding-top: 60px !important;
        }
        
        .image-preview-container {
            margin-top: 10px;
            max-width: 300px;
        }
        #imagePreview {
            max-width: 100%;
            display: none;
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
            <li><a href="backend/agregar_receta.php">Agregar Recetas</a></li>
            <li><a href="../recetas.php">Recetas</a></li>
            <li><a href="../categorias.php">Categorias</a></li>
        </ul>
    </div>
</div>

    <header>
        <div class="container">
            <div class="header-content">
                <h1>Agregar Nueva Receta</h1>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="form-container">
                <div class="form-header">
                    <h2>Añadir receta al recetario</h2>
                    <p>Completa todos los campos marcados con asterisco (*)</p>
                </div>

                <?php if (!empty($errores)): ?>
                    <div class="message error-message">
                        <ul>
                            <?php foreach ($errores as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($exito): ?>
                    <div class="message success-message">
                        ¡Receta agregada exitosamente! <a href="../receta.php?id=<?php echo $recetaId; ?>">Ver receta</a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="agregar_receta.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="title">Título de la Receta *</label>
                        <input type="text" id="title" name="title" class="form-control"
                            value="<?php echo $datosFormulario['title']; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Categoría *</label>
                        <select id="category" name="category" class="form-control" required>
                            <option value="">Selecciona una categoría</option>
                            <?php if (!empty($categorias)): ?>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo htmlspecialchars($categoria['nombre']); ?>" 
                                        <?php echo ($datosFormulario['category'] === $categoria['nombre']) ? 'selected' : ''; ?>
                                        data-id="<?php echo $categoria['id']; ?>">
                                        <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <option value="nueva">+ Añadir nueva categoría</option>
                        </select>
                        <input type="hidden" name="categoria_id" id="categoria_id" value="">
                        <div id="nuevaCategoriaContainer" class="nueva-categoria-container" style="display: none;">
                            <input type="text" id="nuevaCategoria" name="nuevaCategoria" class="form-control"
                                placeholder="Nombre de nueva categoría">
                            <button type="button" id="btnAgregarCategoria"
                                class="btn-agregar-categoria">Agregar</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="portions">Porciones</label>
                        <input type="text" id="portions" name="portions" class="form-control"
                            value="<?php echo $datosFormulario['portions']; ?>" placeholder="Ej: 4 personas">
                        <p class="help-text">Indica para cuántas personas está pensada esta receta</p>
                    </div>

                    <div class="form-group">
                        <label for="prep_time">Tiempo de preparación (minutos) *</label>
                        <input type="number" id="prep_time" name="prep_time" class="form-control"
                            value="<?php echo $datosFormulario['prep_time']; ?>" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="cook_time">Tiempo de cocción (minutos)</label>
                        <input type="number" id="cook_time" name="cook_time" class="form-control"
                            value="<?php echo $datosFormulario['cook_time']; ?>" min="0">
                        <p class="help-text">Usar 0 para recetas sin cocción (como ensaladas)</p>
                    </div>

                    <div class="form-group">
                        <label for="ingredients">Ingredientes *</label>
                        <textarea id="ingredients" name="ingredients" class="form-control"
                            required><?php echo $datosFormulario['ingredients']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="preparation_steps">Pasos de Preparación *</label>
                        <div class="preparacion-tips">
                            <h4>
                                <svg class="tip-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                Los pasos se numeran automáticamente
                            </h4>
                        </div>
                        <textarea id="preparation_steps" name="preparation_steps" class="form-control"
                            required><?php echo $datosFormulario['preparation_steps']; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="imagen">Imagen de la Receta</label>
                        <div class="file-input-container">
                            <div class="file-input-button">Seleccionar Imagen</div>
                            <input type="file" id="imagen" name="imagen" accept="image/*">
                            <span class="file-name" id="fileName">No se ha seleccionado archivo</span>
                        </div>
                        <p class="help-text">Formatos aceptados: JPG, PNG, GIF, WEBP. Tamaño máximo: 5MB</p>
                        <div class="image-preview-container">
                            <img id="imagePreview" src="#" alt="Vista previa de la imagen">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Publicar Receta</button>
                        <a href="../recetas.php" class="btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-logo">
                    <a href="../index.php">Recetario QH</a>
                    <p>Cocina casera para todos los días</p>
                </div>
                
                <div class="footer-links">
                    <h3>Navegación</h3>
                    <ul>
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="../recetas.php">Recetas</a></li>
                        <li><a href="../categorias.php">Categorías</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; <?php echo date("Y"); ?> Recetario QH. Todos los derechos reservados.</p>
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
        });
        
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
            
            if (file) {
                fileName.textContent = file.name;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                fileName.textContent = 'No se ha seleccionado archivo';
                imagePreview.style.display = 'none';
            }
        });
        
        // Permitir usar Enter para agregar nueva categoría
        document.getElementById('nuevaCategoria').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('btnAgregarCategoria').click();
            }
        });
    </script>
    <script src="../js/menu.js"></script>
    <script src="../js/recipe-cards.js"></script>
</body>
</html>