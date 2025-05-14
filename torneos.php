<?php
header('Content-Type: application/json'); // Siempre devolvemos JSON
include 'conexion.php'; // Incluimos la conexión a la base de datos

// Verificamos el método de la solicitud
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Obtener detalles de un torneo específico
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM torneos WHERE id = $id";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                $torneo = $result->fetch_assoc();
                $torneo['categorias_pruebas'] = $torneo['categorias_pruebas']; // Aseguramos que esta propiedad esté incluida
                $torneo['convocatoria'] = file_get_contents("torneos/" . preg_replace('/[^a-zA-Z0-9]/', '_', $torneo['nombre']) . "/convocatoria.html");
                $torneo['inscripciones'] = file_get_contents("torneos/" . preg_replace('/[^a-zA-Z0-9]/', '_', $torneo['nombre']) . "/inscripciones.html");
                $torneo['listados'] = file_get_contents("torneos/" . preg_replace('/[^a-zA-Z0-9]/', '_', $torneo['nombre']) . "/listados.html");
                echo json_encode($torneo);
            } else {
                echo json_encode(array("error" => "Torneo no encontrado"));
            }
        } else {
            // Obtener todos los torneos
            $sql = "SELECT * FROM torneos";
            $result = $conn->query($sql);

            $torneos = array();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $torneo = array(
                        'id' => $row['id'],
                        'title' => $row['nombre'],
                        'start' => $row['fecha_inicio'],
                        'end' => date('Y-m-d H:i:s', strtotime($row['fecha_inicio'] . ' + ' . $row['duracion'] . ' days')),
                        'backgroundColor' => $row['color'],
                        'borderColor' => $row['color'],
                        'extendedProps' => array(
                            'icon' => 'uploads/' . $row['logo'],
                            'url' => 'detalle_torneo.html?id=' . $row['id']
                        )
                    );
                    array_push($torneos, $torneo);
                }
            }
            echo json_encode($torneos);
        }
        break;

    case 'POST':
        // Guardar un nuevo torneo
        $nombre = $_POST['nombre'];
        $fecha_inicio = $_POST['fecha_inicio'];
        $duracion = $_POST['duracion'];
        $logo = $_FILES['logo']['name'];
        $color = $_POST['color'];
        $fecha_apertura_inscripciones = $_POST['fecha_apertura_inscripciones'];
        $fecha_cierre_inscripciones = $_POST['fecha_cierre_inscripciones'];
        $limite_participantes = $_POST['limite_participantes'] === 'Sí' ? 1 : 0;
        $max_participantes = $_POST['max_participantes'];
        $pruebas_validas = implode(', ', $_POST['pruebas_validas']);
        $ciudad = $_POST['ciudad'];
        $piscina = $_POST['piscina'];
        $direccion = $_POST['direccion'];
        $categorias_pruebas = $_POST['categorias_pruebas']; // Asegurarse de recibir este campo correctamente

        // Crear la carpeta del torneo
        $torneo_dir = "torneos/" . preg_replace('/[^a-zA-Z0-9]/', '_', $nombre);
        if (!is_dir($torneo_dir)) {
            mkdir($torneo_dir, 0777, true);
        }

        // Mover el archivo subido a una carpeta
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Crear la carpeta si no existe
        }
        $target_file = $target_dir . basename($_FILES["logo"]["name"]);
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            // Copiar y modificar las plantillas de las páginas del torneo en la carpeta del torneo
            $plantillas = ['convocatoria.html', 'inscripciones.html', 'listados.html'];
            foreach ($plantillas as $plantilla) {
                $contenido = file_get_contents("plantillas/$plantilla");
                $contenido = str_replace(
                    ['{nombre_torneo}', '{fecha_apertura_inscripciones}', '{fecha_cierre_inscripciones}', '{categorias}', '{pruebas_validas}', '{color}', '{logo}', '{fecha_inicio}', '{duracion}', '{ciudad}', '{piscina}', '{direccion}', '{limite_participantes}', '{max_participantes}'],
                    [$nombre, $fecha_apertura_inscripciones, $fecha_cierre_inscripciones, $categorias_pruebas, $pruebas_validas, $color, '/uploads/' . $logo, $fecha_inicio, $duracion, $ciudad, $piscina, $direccion, $limite_participantes ? 'Sí' : 'No', $max_participantes],
                    $contenido
                );
                file_put_contents("$torneo_dir/$plantilla", $contenido);
            }

            // Insertar en la base de datos
            $sql = "INSERT INTO torneos (nombre, fecha_inicio, duracion, logo, color, fecha_apertura_inscripciones, fecha_cierre_inscripciones, limite_participantes, max_participantes, pruebas_validas, ciudad, piscina, direccion, categorias_pruebas) VALUES ('$nombre', '$fecha_inicio', $duracion, '$logo', '$color', '$fecha_apertura_inscripciones', '$fecha_cierre_inscripciones', $limite_participantes, $max_participantes, '$pruebas_validas', '$ciudad', '$piscina', '$direccion', '$categorias_pruebas')";

            if ($conn->query($sql) === TRUE) {
                echo json_encode(array("message" => "Torneo creado exitosamente"));
            } else {
                echo json_encode(array("error" => "Error al crear el torneo: " . $conn->error));
            }
        } else {
            echo json_encode(array("error" => "Error al subir el archivo del logo"));
        }
        break;

    case 'DELETE':
        // Leer el cuerpo de la solicitud DELETE
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'];
    
        // Obtener el nombre del torneo para eliminar la carpeta
        $sql = "SELECT nombre FROM torneos WHERE id=$id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $nombre_torneo = $row['nombre'];
            $torneo_dir = "torneos/" . preg_replace('/[^a-zA-Z0-9]/', '_', $nombre_torneo);
        }
    
        // Eliminar el torneo de la base de datos
        $sql = "DELETE FROM torneos WHERE id=$id";
    
        if ($conn->query($sql) === TRUE) {
            // Eliminar la carpeta del torneo
            if (is_dir($torneo_dir)) {
                array_map('unlink', glob("$torneo_dir/*.*"));
                rmdir($torneo_dir);
            }
            echo json_encode(array("message" => "Torneo eliminado exitosamente"));
        } else {
            echo json_encode(array("error" => "Error al eliminar el torneo: " . $conn->error));
        }
        break;
}

$conn->close();
?>