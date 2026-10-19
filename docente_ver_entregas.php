<?php
// docente_ver_entregas.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Docente') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) die("Error de conexión: " . $conn->connect_error);

if (!isset($_GET['id_tarea'])) {
    die("Error: No se especificó la tarea.");
}
$id_tarea = intval($_GET['id_tarea']);

// Procesar calificación si el docente la envía
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calificar'])) {
    $id_entrega = intval($_POST['id_entrega']);
    $calificacion = floatval($_POST['calificacion']);
    $comentario = $conn->real_escape_string($_POST['comentario']);
    
    $stmt = $conn->prepare("UPDATE entregas_tareas SET calificacion = ?, comentario_docente = ? WHERE id = ?");
    $stmt->bind_param("dsi", $calificacion, $comentario, $id_entrega);
    $stmt->execute();
    $stmt->close();
    echo "<p style='color: green;'>✅ Calificación guardada.</p>";
}

// ==========================================
// 📌 CORRECCIÓN AQUÍ: Eliminamos el JOIN con 'usuarios'
// Ahora mostramos solo el ID_estudiante de la tabla entregas_tareas
// ==========================================
$sql = "SELECT * FROM entregas_tareas WHERE id_tarea = $id_tarea";
$entregas = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Entregas - Docente</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 20px; background: #f4f7f6; }
        .card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid #ddd; text-align: left; }
        input[type="number"] { width: 60px; padding: 5px; }
        textarea { width: 100%; height: 40px; margin-top: 5px; }
        .btn { background: #28a745; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; }
        .btn:hover { background: #218838; }
        .btn-back { display: inline-block; margin-bottom: 20px; color: #2c3e50; text-decoration: none; font-weight: bold; }
        .empty { text-align: center; color: #888; padding: 40px; }
    </style>
</head>
<body>
    <a href="docente_tareas.php?materia_id=<?php echo isset($_GET['materia_id']) ? $_GET['materia_id'] : 0; ?>" class="btn-back">⬅ Volver a Tareas</a>
    <h2>📂 Entregas de la Tarea #<?php echo $id_tarea; ?></h2>
    
    <?php if ($entregas && $entregas->num_rows > 0): ?>
        <?php while($row = $entregas->fetch_assoc()): ?>
            <div class="card">
                <h3>👤 Estudiante ID: <?php echo htmlspecialchars($row['id_estudiante']); ?></h3>
                <p><strong>Archivo:</strong> <a href="<?php echo $row['ruta_archivo']; ?>" target="_blank" style="color:#3498db;"><?php echo $row['nombre_archivo']; ?></a></p>
                <p><strong>Fecha:</strong> <?php echo $row['fecha_entrega']; ?></p>
                
                <form method="POST">
                    <input type="hidden" name="id_entrega" value="<?php echo $row['id']; ?>">
                    <label>Calificación (0-10):</label>
                    <input type="number" name="calificacion" step="0.1" min="0" max="10" value="<?php echo $row['calificacion']; ?>">
                    <br><br>
                    <label>Comentario:</label>
                    <textarea name="comentario"><?php echo htmlspecialchars($row['comentario_docente']); ?></textarea>
                    <br><br>
                    <button type="submit" name="calificar" class="btn">Guardar Calificación</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty">
            <i class='bx bxs-inbox' style="font-size: 40px; display:block; margin-bottom:10px;"></i>
            Ningún estudiante ha subido esta tarea aún.
        </div>
    <?php endif; ?>
</body>
</html>