<?php
$servername = "localhost"; // Cambia esto si tu servidor no es localhost
$username = "root"; // Cambia esto si tienes un usuario diferente
$password = ""; // Cambia esto si tienes una contraseña configurada
$database = "liga_natacion"; // Nombre de tu base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $database);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
