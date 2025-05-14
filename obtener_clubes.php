<?php
include 'db_connection.php'; // Asegúrate de que este archivo esté configurado correctamente

header('Content-Type: text/html; charset=utf-8');

// Consulta para obtener los nombres de los clubes
$query = "SELECT id, nombre FROM clubes";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Generar las opciones del select con el id como valor
        echo '<option value="' . htmlspecialchars($row['id'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($row['nombre'], ENT_QUOTES, 'UTF-8') . '</option>';
    }
} else {
    echo '<option value="">No hay clubes registrados</option>';
}

$conn->close(); // Cierra la conexión a la base de datos
?>
