<?php
// admin_reporte_detalle_inscripcion.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['inscripcion_id']) || !is_numeric($_GET['inscripcion_id'])) {
    die("ID inválido.");
}
$inscripcion_id = (int)$_GET['inscripcion_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$sql_info = "SELECT u.Nombre as alumno, c.nombre as carrera, p.nombre as periodo, i.carrera_id 
             FROM inscripciones i 
             JOIN usuarios u ON i.usuario_id = u.Id 
             JOIN carreras c ON i.carrera_id = c.id 
             JOIN periodos p ON i.periodo_id = p.id 
             WHERE i.id = ?";
$stmt = $conn->prepare($sql_info);
$stmt->bind_param("i", $inscripcion_id);
$stmt->execute();
$info = $stmt->get_result()->fetch_assoc();

if (!$info) { die("Inscripción no encontrada."); }

$sql_detalle = "SELECT idet.id as detalle_id, m.id as materia_id, m.nombre, m.clave, m.creditos, m.semestre, m.horario 
                FROM inscripciones_detalle idet 
                JOIN materias m ON idet.materia_id = m.id 
                WHERE idet.inscripcion_id = ? 
                ORDER BY m.semestre ASC, m.nombre ASC";
$stmt_det = $conn->prepare($sql_detalle);
$stmt_det->bind_param("i", $inscripcion_id);
$stmt_det->execute();
$materias = $stmt_det->get_result()->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de Inscripción</title>
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            padding: 40px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .main-container {
            background: #ffffff;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            max-width: 1000px;
            width: 100%;
            box-sizing: border-box;
        }
        h1 { color: #2c3e50; font-size: 2rem; }
        .info-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 5px solid #3498db;
            margin-bottom: 30px;
        }
        .info-box p { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        th { background-color: #2c3e50; color: white; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; }
        tr:hover { background-color: #f8f9fa; }
        .btn-add {
            background: #2ecc71;
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(46,204,113,0.3); }
        .btn-delete-item {
            background: #fdedec;
            color: #c0392b;
            padding: 5px 10px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 3px;
            transition: all 0.2s;
        }
        .btn-delete-item:hover { background: #fadbd8; }
        .footer-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #34495e;
            text-decoration: none;
            font-weight: 600;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: bold;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
        .btn-danger:hover { background: #c0392b; transform: translateY(-2px); }
        .empty-state { text-align: center; padding: 40px; color: #999; }
    </style>
</head>
<body>
    <div class="main-container">
        <h1><i class='bx bxs-id-card'></i> Detalle de Inscripción</h1>
        
        <div class="info-box">
            <p><strong>Estudiante:</strong> <?php echo htmlspecialchars($info['alumno']); ?></p>
            <p><strong>Carrera:</strong> <?php echo htmlspecialchars($info['carrera']); ?></p>
            <p><strong>Período:</strong> <?php echo htmlspecialchars($info['periodo']); ?></p>
        </div>

        <div style="margin-bottom: 20px;">
            <a href="agregar_materia_carga.php?id_inscripcion=<?php echo $inscripcion_id; ?>" class="btn-add">
                <i class='bx bx-plus'></i> Agregar Materia
            </a>
        </div>

        <?php if (count($materias) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th style="text-align:center;">Sem.</th>
                        <th>Clave</th>
                        <th>Materia</th>
                        <th style="text-align:center;">Créd.</th>
                        <th>Horario</th>
                        <th style="text-align:center;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materias as $m): ?>
                        <tr>
                            <td style="text-align:center; font-weight:bold;"><?php echo $m['semestre']; ?></td>
                            <td><?php echo htmlspecialchars($m['clave']); ?></td>
                            <td><strong><?php echo htmlspecialchars($m['nombre']); ?></strong></td>
                            <td style="text-align:center;"><?php echo $m['creditos']; ?></td>
                            <td><?php echo htmlspecialchars($m['horario'] ?: 'Por asignar'); ?></td>
                            <td style="text-align:center;">
                                <a href="admin_eliminar_detalle_inscripcion.php?detalle_id=<?php echo $m['detalle_id']; ?>&inscripcion_id=<?php echo $inscripcion_id; ?>&tipo=materia" 
                                   class="btn-delete-item" onclick="return confirm('¿Quitar esta materia?')">
                                    <i class='bx bx-trash'></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>El alumno no tiene materias inscritas.</p>
            </div>
        <?php endif; ?>

        <div class="footer-nav">
            <a href="admin_reporte_inscritos.php" class="btn-back"><i class='bx bx-chevron-left'></i> Volver al Reporte</a>
            <a href="admin_eliminar_detalle_inscripcion.php?inscripcion_id=<?php echo $inscripcion_id; ?>&tipo=inscripcion_completa" 
               class="btn-danger" onclick="return confirm('⚠️ ¿Anular toda la inscripción?')">
                <i class='bx bx-error'></i> Anular Inscripción
            </a>
        </div>
    </div>
</body>
</html>