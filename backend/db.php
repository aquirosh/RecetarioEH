<?php
$host = "localhost";
$dbname = "recetario";
$user = "recetario_admin";
$password = "Pass123";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "Conexión exitosa a la base de datos 'tienda' ✔️";
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}
?>

