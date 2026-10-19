<?php
// admin_editar_materia.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['carrera_id']) || !is_numeric($_GET['carrera_id'])) {
    die("IDs no válidos.");
}

$materia_id = $_GET['id'];
$carrera_id = $_GET['carrera_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$stmt_c = $conn->prepare("SELECT nombre FROM carreras WHERE id = ?");
$stmt_c->bind_param("i", $carrera_id);
$stmt_c->execute();
$carrera_info = $stmt_c->get_result()->fetch_assoc();
$nombre_carrera = $carrera_info['nombre'];
$stmt_c->close();

$stmt_m = $conn->prepare("SELECT nombre, clave, creditos, semestre, horario, descripcion, docente_asignado FROM materias WHERE id = ? AND carrera_id = ?");
$stmt_m->bind_param("ii", $materia_id, $carrera_id);
$stmt_m->execute();
$result = $stmt_m->get_result();

if ($result->num_rows === 0) { die("Materia no encontrada."); }
$materia = $result->fetch_assoc();
$stmt_m->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Materia</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); font-family: 'Segoe UI', sans-serif; margin: 0; padding: 40px; min-height: 100vh; display: flex; flex-direction: column; align-items: center; }
        .main-container { background: #ffffff; padding: 40px; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); max-width: 700px; width: 100%; box-sizing: border-box; }
        h1 { color: #2c3e50; font-size: 1.8rem; display: flex; align-items: center; gap: 10px; }
        .carrera-badge { display: inline-block; background: #eaf2f8; color: #2980b9; padding: 8px 15px; border-radius: 20px; font-size: 0.9rem; font-weight: 600; margin-bottom: 25px; border: 1px solid #d6eaf8; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .input-group { margin-bottom: 20px; }
        .input-group.full-width { grid-column: span 2; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        .input-group input, .input-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-family: inherit; font-size: 1rem; }
        .input-group input:focus, .input-group textarea:focus { border-color: #3498db; outline: none; }
        .btn-submit { background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); width: 100%; padding: 15px; border: none; border-radius: 10px; color: white; font-weight: bold; font-size: 1.1rem; cursor: pointer; transition: transform 0.2s; margin-top: 10px; display: flex; justify-content: center; align-items: center; gap: 10px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(52,152,219,0.3); }
        .back-link { display: block; margin-top: 25px; color: #7f8c8d; text-decoration: none; font-weight: 600; text-align: center; }
        .back-link:hover { color: #2c3e50; }
    </style>
</head>
<body>
    <div class="main-container">
        <h1><i class='bx bxs-edit'></i> Editar Materia</h1>
        <div class="carrera-badge">
            <i class='bx bxs-graduation'></i> Carrera: <?php echo htmlspecialchars($nombre_carrera); ?>
        </div>

        <form action="admin_actualizar_materia.php" method="POST">
            <input type="hidden" name="materia_id" value="<?php echo $materia_id; ?>">
            <input type="hidden" name="carrera_id" value="<?php echo $carrera_id; ?>">

            <div class="form-grid">
                <div class="input-group full-width">
                    <label for="nombre">Nombre de la Materia</label>
                    <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($materia['nombre']); ?>" required>
                </div>

                <div class="input-group">
                    <label for="clave">Clave</label>
                    <input type="text" id="clave" name="clave" value="<?php echo htmlspecialchars($materia['clave']); ?>" required>
                </div>

                <div class="input-group">
                    <label for="semestre">Semestre</label>
                    <input type="number" id="semestre" name="semestre" value="<?php echo $materia['semestre']; ?>" required min="1" max="12">
                </div>

                <div class="input-group">
                    <label for="creditos">Créditos</label>
                    <input type="number" id="creditos" name="creditos" value="<?php echo $materia['creditos']; ?>" required min="1" max="15">
                </div>

                <div class="input-group full-width">
                    <label for="horario"><i class='bx bx-time'></i> Horario</label>
                    <input type="text" id="horario" name="horario" value="<?php echo htmlspecialchars($materia['horario']); ?>" placeholder="Ej: Lunes y Miércoles 8:00 - 10:00">
                </div>
                
                <div class="input-group full-width">
                    <label for="docente"><i class='bx bx-user'></i> Docente Asignado</label>
                    <input type="text" id="docente" name="docente" value="<?php echo htmlspecialchars($materia['docente_asignado']); ?>" placeholder="Nombre del profesor">
                </div>

                <div class="input-group full-width">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($materia['descripcion']); ?></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class='bx bxs-save'></i> Guardar Cambios
            </button>
        </form>
        
        <a href="admin_gestionar_materias.php?carrera_id=<?php echo htmlspecialchars($carrera_id); ?>" class="back-link">
            <i class='bx bx-arrow-back'></i> Cancelar y Volver
        </a>
    </div>
</body>
</html>