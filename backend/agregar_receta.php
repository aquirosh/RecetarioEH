<?php
require_once 'db.php'; // Ruta unificada para la conexión
session_start(); // Añadimos soporte para sesiones

// Función para validar datos
function validarDatos($datos)
{
    $errores = [];

    // Validación de campos requeridos
    $camposRequeridos = ['title', 'category', 'prep_time', 'cook_time', 'ingredients', 'preparation_steps'];
    foreach ($camposRequeridos as $campo) {
        if (empty(trim($datos[$campo]))) {
            $errores[] = "El campo " . ucfirst(str_replace('_', ' ', $campo)) . " es obligatorio.";
        }
    }

    // Validación de campos numéricos
    if (!empty($datos['prep_time']) && (!is_numeric($datos['prep_time']) || $datos['prep_time'] < 0)) {
        $errores[] = "El tiempo de preparación debe ser un número positivo.";
    }

    if (!empty($datos['cook_time']) && (!is_numeric($datos['cook_time']) || $datos['cook_time'] < 0)) {
        $errores[] = "El tiempo de cocción debe ser un número positivo.";
    }

    // Validación de URL de imagen (si se proporciona)
    if (!empty($datos['image_url']) && !filter_var($datos['image_url'], FILTER_VALIDATE_URL)) {
        $errores[] = "La URL de la imagen no es válida.";
    }

    return $errores;
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
    'image_url' => ''
];

// Si se envió el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir y sanitizar datos
    foreach ($datosFormulario as $campo => $valor) {
        $datosFormulario[$campo] = isset($_POST[$campo]) ? trim(htmlspecialchars($_POST[$campo])) : '';
    }

    // Validar datos
    $errores = validarDatos($datosFormulario);

    // Si no hay errores, guardar en la base de datos
    if (empty($errores)) {
        $sql = "INSERT INTO recipes (title, category, portions, prep_time_minutes, cook_time_minutes, ingredients, preparation_steps, image_url, created_at)
                VALUES (:title, :category, :portions, :prep_time, :cook_time, :ingredients, :preparation_steps, :image_url, NOW())";

        $stmt = $pdo->prepare($sql);

        try {
            $stmt->execute([
                ':title' => $datosFormulario['title'],
                ':category' => $datosFormulario['category'],
                ':portions' => $datosFormulario['portions'],
                ':prep_time' => (int) $datosFormulario['prep_time'],
                ':cook_time' => (int) $datosFormulario['cook_time'],
                ':ingredients' => $datosFormulario['ingredients'],
                ':preparation_steps' => $datosFormulario['preparation_steps'],
                ':image_url' => $datosFormulario['image_url']
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
            header("Location: receta.php?id=$recetaId");
            exit;

        } catch (PDOException $e) {
            $errores[] = "Error al agregar receta: " . $e->getMessage();
        }
    }
}

// Cargar categorías desde la base de datos
$categorias = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT category FROM recipes ORDER BY category");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categorias[] = $row['category'];
    }
} catch (PDOException $e) {
    // Manejar error silenciosamente
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Receta | Recetario QH</title>
    <link rel="stylesheet" href="../css/styles.css">
    
</head>

<body>
    <nav>
        <div class="container">
            <ul>
                <li><a href="../index.html">Home</a></li>
                <li><a href="../recetas.html" class="active">Recetas</a></li>
                <li><a href="../categorias.html">Categorías</a></li>
            </ul>
        </div>
    </nav>

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
                        ¡Receta agregada exitosamente! <a href="receta.php?id=<?php echo $recetaId; ?>">Ver receta</a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="agregar_receta.php">
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
                                    <option value="<?php echo htmlspecialchars($categoria); ?>" <?php echo ($datosFormulario['category'] === $categoria) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <option value="nueva">+ Añadir nueva categoría</option>
                        </select>
                        <div id="nuevaCategoriaContainer" class="nueva-categoria-container" style="display: none;">
                            <input type="text" id="nuevaCategoria" class="form-control"
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
                        <label for="cook_time">Tiempo de cocción (minutos) *</label>
                        <input type="number" id="cook_time" name="cook_time" class="form-control"
                            value="<?php echo $datosFormulario['cook_time']; ?>" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="ingredients">Ingredientes *</label>
                        <div class="ingredientes-tips">
                            <h4>
                                <svg class="tip-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                Consejos para ingresar ingredientes
                            </h4>
                            <p>Escribe cada ingrediente en una línea separada con el formato: <br>
                                "Cantidad - Ingrediente" (ejemplo: "2 tazas - Harina")</p>
                        </div>
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
                                Consejos para los pasos
                            </h4>
                            <p>Escribe cada paso en una línea separada. No es necesario numerarlos, ¡lo haremos por ti
                                automáticamente!</p>
                        </div>


                        <textarea id="preparation_steps" name="preparation_steps" class="form-control" required><?php echo $datosFormulario['preparation_steps']; ?></textarea>
</div>

<div class="form-group">
    <label for="image_url">URL de la Imagen (opcional)</label>
    <input type="url" id="image_url" name="image_url" class="form-control"
        value="<?php echo $datosFormulario['image_url']; ?>">
    <p class="help-text">Proporciona una URL directa de la imagen si deseas ilustrar tu receta.</p>
</div>

<div class="form-actions">
    <button type="submit" class="btn-primary">Publicar Receta</button>
    <a href="../recetas.html" class="btn-secondary">Cancelar</a>
</div>
</form>
</div>
</div>
</div>
</main>

<footer>
    <div class="container">
        <p>&copy; <?php echo date("Y"); ?> Recetario QH. Todos los derechos reservados.</p>
    </div>
</footer>

<script>
// Mostrar campo para nueva categoría si elige "nueva"
document.getElementById('category').addEventListener('change', function() {
    var nuevaCategoriaContainer = document.getElementById('nuevaCategoriaContainer');
    if (this.value === 'nueva') {
        nuevaCategoriaContainer.style.display = 'block';
    } else {
        nuevaCategoriaContainer.style.display = 'none';
    }
});

// Asignar nueva categoría al seleccionar
document.getElementById('btnAgregarCategoria').addEventListener('click', function() {
    var nuevaCategoria = document.getElementById('nuevaCategoria').value.trim();
    if (nuevaCategoria) {
        var categorySelect = document.getElementById('category');
        var nuevaOption = document.createElement('option');
        nuevaOption.value = nuevaCategoria;
        nuevaOption.text = nuevaCategoria;
        nuevaOption.selected = true;
        categorySelect.add(nuevaOption);
        document.getElementById('nuevaCategoriaContainer').style.display = 'none';
    }
});
</script>

</body>
</html>
