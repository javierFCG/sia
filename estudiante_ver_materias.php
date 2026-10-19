<?php
// estudiante_ver_materias.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] === 'Administrador') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['inscripcion_id']) || !is_numeric($_GET['inscripcion_id'])) {
    die("ID de Inscripción no válido.");
}
$inscripcion_id = $_GET['inscripcion_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$sql_info = "SELECT p.nombre AS periodo_nombre, c.nombre AS carrera_nombre, i.fecha_inscripcion 
             FROM inscripciones i 
             JOIN periodos p ON i.periodo_id = p.id 
             JOIN carreras c ON i.carrera_id = c.id 
             WHERE i.id = ? AND i.usuario_id = ?";
$stmt_info = $conn->prepare($sql_info);
$stmt_info->bind_param("ii", $inscripcion_id, $user_id);
$stmt_info->execute();
$inscripcion_info = $stmt_info->get_result()->fetch_assoc();
$stmt_info->close();

if (!$inscripcion_info) {
    die("Error: Inscripción no encontrada o no pertenece a este usuario.");
}

$sql_materias = "SELECT m.nombre, m.clave, m.creditos, m.semestre, m.horario 
                 FROM inscripciones_detalle id 
                 JOIN materias m ON id.materia_id = m.id 
                 WHERE id.inscripcion_id = ? 
                 ORDER BY m.semestre ASC, m.nombre ASC";
$stmt_materias = $conn->prepare($sql_materias);
$stmt_materias->bind_param("i", $inscripcion_id);
$stmt_materias->execute();
$materias = $stmt_materias->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_materias->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Materias Inscritas</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            background: linear-gradient(90deg, #c9d6ff, #e2e2e2);
            display: flex;
            flex-direction: column;
            padding: 50px;
            align-items: center;
            min-height: 100vh;
        }
        .main-content {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            max-width: 800px;
            width: 100%;
        }
        .info-header {
            background: #f0f4ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 5px solid #5279e2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        th {
            background-color: #5279e2;
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .badge-sem {
            background: #e8f6f3;
            color: #1abc9c;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .btn-volver {
            display: inline-block;
            padding: 10px 20px;
            background: #5279e2;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 25px;
            transition: all 0.3s;
        }
        .btn-volver:hover { background: #4a69bd; transform: translateY(-2px); }
        .empty-state { text-align: center; padding: 40px; color: #999; }
    </style>
</head>
<body>
    <div class="main-content">
        <h1>📚 Mis Materias Inscritas</h1>
        
        <div class="info-header">
            <p><strong>Período:</strong> <?php echo htmlspecialchars($inscripcion_info['periodo_nombre']); ?></p>
            <p><strong>Carrera:</strong> <?php echo htmlspecialchars($inscripcion_info['carrera_nombre']); ?></p>
            <p><strong>Fecha de Inscripción:</strong> <?php echo date('d/m/Y', strtotime($inscripcion_info['fecha_inscripcion'])); ?></p>
        </div>
        
        <hr>

        <?php if (!empty($materias)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Sem.</th>
                        <th>Clave</th>
                        <th>Materia</th>
                        <th>Créditos</th>
                        <th>Horario</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materias as $materia): ?>
                        <tr>
                            <td><span class="badge-sem"><?php echo $materia['semestre']; ?>°</span></td>
                            <td><?php echo htmlspecialchars($materia['clave']); ?></td>
                            <td><strong><?php echo htmlspecialchars($materia['nombre']); ?></strong></td>
                            <td><?php echo $materia['creditos']; ?></td>
                            <td><?php echo htmlspecialchars($materia['horario'] ?: 'Por asignar'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>No se registraron materias para esta inscripción.</p>
            </div>
        <?php endif; ?>

        <a href="estudiante_periodos_inscritos.php" class="btn-volver">⬅️ Volver a Mis Períodos</a>
    </div>
</body>
</html>