<?php
require_once 'db.php'; // Conexión a la base de datos
require_once 'protected.php'; 
session_start(); // Iniciamos sesión para poder guardar mensajes

// Verificar si se recibió un ID válido
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $recetaId = (int)$_GET['id'];
    
    try {
        // Primero verificamos si la receta existe
        $stmtVerificar = $pdo->prepare("SELECT * FROM recetas WHERE id = :id");
        $stmtVerificar->execute([':id' => $recetaId]);
        $receta = $stmtVerificar->fetch(PDO::FETCH_ASSOC);
        
        if (!$receta) {
            // La receta no existe
            $_SESSION['mensaje'] = "No se encontró la receta con ID: $recetaId";
            $_SESSION['tipo_mensaje'] = "error";
            header("Location: ../recetas.php");
            exit;
        }
        
        // Si existe, procedemos a eliminar
        $stmt = $pdo->prepare("DELETE FROM recetas WHERE id = :id");
        $resultado = $stmt->execute([':id' => $recetaId]);
        
        if ($resultado) {
            // Si se eliminó correctamente
            $_SESSION['mensaje'] = "La receta '" . htmlspecialchars($receta['title']) . "' se ha eliminado correctamente.";
            $_SESSION['tipo_mensaje'] = "success";
            
            // Si hay una imagen local asociada, la eliminamos
            if (!empty($receta['image_path']) && file_exists('../' . $receta['image_path'])) {
                unlink('../' . $receta['image_path']);
            }
        } else {
            // Si hubo un error al eliminar
            $_SESSION['mensaje'] = "Error al eliminar la receta.";
            $_SESSION['tipo_mensaje'] = "error";
        }
        
    } catch (PDOException $e) {
        // Capturamos cualquier error de base de datos
        $_SESSION['mensaje'] = "Error de base de datos: " . $e->getMessage();
        $_SESSION['tipo_mensaje'] = "error";
    }
    
} else {
    // Si no se proporcionó un ID válido
    $_SESSION['mensaje'] = "ID de receta no válido o no proporcionado.";
    $_SESSION['tipo_mensaje'] = "error";
}

// Redirigimos a la página de recetas
header("Location: ../recetas.php");
exit;
?>