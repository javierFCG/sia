<?php
// agregar_materia_carga.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username'])) {
    header("Location: Login.html");
    exit();
}

if (!isset($_GET['id_inscripcion']) || !is_numeric($_GET['id_inscripcion'])) {
    die("Error: Faltan datos de la inscripción.");
}

$inscripcion_id = (int)$_GET['id_inscripcion'];
$error = "";

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Conexión fallida: " . $conn->connect_error); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['materia_id']) && !empty($_POST['materia_id'])) {
        $materia_id = (int)$_POST['materia_id'];
        
        $sql_insert = "INSERT INTO inscripciones_detalle (inscripcion_id, materia_id) VALUES (?, ?)";
        $stmt_ins = $conn->prepare($sql_insert);
        $stmt_ins->bind_param("ii", $inscripcion_id, $materia_id);
        
        if ($stmt_ins->execute()) {
            header("Location: carga.php?id=" . $inscripcion_id);
            exit();
        } else {
            $error = "Error al guardar: " . $conn->error;
        }
    } else {
        $error = "Por favor selecciona una materia.";
    }
}

// Info del Alumno
$sql_info = "SELECT u.Nombre, c.id AS carrera_id, c.nombre AS carrera_nombre 
             FROM inscripciones i 
             JOIN usuarios u ON i.usuario_id = u.Id 
             JOIN carreras c ON i.carrera_id = c.id 
             WHERE i.id = ?";
$stmt = $conn->prepare($sql_info);
$stmt->bind_param("i", $inscripcion_id);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();

if (!$info) { die("Inscripción no encontrada."); }
$carrera_id = $info['carrera_id'];

// Materias Disponibles
$sql_disponibles = "SELECT m.id, m.nombre, m.creditos, m.semestre, m.horario 
                    FROM materias m 
                    LEFT JOIN inscripciones_detalle idet ON m.id = idet.materia_id AND idet.inscripcion_id = ? 
                    WHERE m.carrera_id = ? AND idet.id IS NULL 
                    ORDER BY m.semestre ASC, m.nombre ASC";
$stmt_m = $conn->prepare($sql_disponibles);
$stmt_m->bind_param("ii", $inscripcion_id, $carrera_id);
$stmt_m->execute();
$result_materias = $stmt_m->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Materia</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .main-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
            box-sizing: border-box;
        }
        .header-title { text-align: center; margin-bottom: 30px; }
        .header-title h2 { margin: 0; color: #2c3e50; font-size: 1.8rem; }
        .header-title p { margin: 5px 0 0; color: #7f8c8d; }
        .info-box {
            background: #eaf2f8;
            border-left: 5px solid #3498db;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        label { display: block; margin-bottom: 10px; font-weight: 600; color: #555; }
        select {
            width: 100%;
            padding: 12px;
            margin-bottom: 25px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            background: #fff;
        }
        select:focus { border-color: #3498db; outline: none; }
        .btn-container { display: flex; gap: 15px; }
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform 0.2s;
        }
        .btn-save {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            color: white;
            box-shadow: 0 4px 10px rgba(46,204,113,0.3);
        }
        .btn-save:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(46,204,113,0.4); }
        .btn-cancel {
            background: #e74c3c;
            color: white;
            box-shadow: 0 4px 10px rgba(231,76,60,0.3);
        }
        .btn-cancel:hover { background: #c0392b; transform: translateY(-2px); }
        .alert {
            padding: 15px;
            background: #fdedec;
            color: #c0392b;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fadbd8;
            text-align: center;
        }
        .empty-state { text-align: center; padding: 20px; color: #999; }
    </style>
</head>
<body>

    <div class="main-card">
        <div class="header-title">
            <h2><i class='bx bxs-book-add'></i> Agregar Materia</h2>
            <p>Selecciona una asignatura para inscribir</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert"><i class='bx bxs-error-circle'></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="info-box">
            <strong>Alumno:</strong> <?php echo htmlspecialchars($info['Nombre']); ?><br>
            <span style="font-size: 0.9em; color: #555;"><?php echo htmlspecialchars($info['carrera_nombre']); ?></span>
        </div>

        <form method="POST">
            <label for="materia">Listado de Materias Disponibles:</label>
            <select name="materia_id" id="materia" required>
                <option value="">-- Selecciona una opción --</option>
                <?php if ($result_materias->num_rows > 0) {
                    while($row = $result_materias->fetch_assoc()) {
                        $texto_opcion = "[Sem " . $row['semestre'] . "] " . $row['nombre'] . " (" . $row['creditos'] . " cr) - " . ($row['horario'] ?: 'S/H');
                        echo "<option value='" . $row['id'] . "'>$texto_opcion</option>";
                    }
                } else { ?>
                    <option value="" disabled>✅ ¡Todo inscrito! No hay más materias disponibles.</option>
                <?php } ?>
            </select>

            <div class="btn-container">
                <a href="carga.php?id=<?php echo $inscripcion_id; ?>" class="btn btn-cancel">
                    <i class='bx bx-x'></i> Cancelar
                </a>
                <button type="submit" class="btn btn-save">
                    <i class='bx bx-save'></i> Guardar
                </button>
            </div>
        </form>
    </div>

</body>
</html>
<?php $conn->close(); ?>