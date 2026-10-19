<?php
// admin_asignar_horario_estudiante.php - Asignar horarios a estudiantes
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['usuario_id']) || !is_numeric($_GET['usuario_id'])) {
    die("ID de usuario no válido.");
}

$usuario_id = (int)$_GET['usuario_id'];
$mensaje = "";
$mensaje_tipo = "";

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Obtener información del estudiante
$stmt = $conn->prepare("SELECT Nombre, Correo, grupo, semestre FROM usuarios WHERE Id = ?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$estudiante = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$estudiante) {
    die("Estudiante no encontrado.");
}

// Obtener inscripciones del estudiante
$sql_insc = "SELECT i.id, i.periodo_id, p.nombre as periodo, i.carrera_id, c.nombre as carrera 
             FROM inscripciones i 
             JOIN periodos p ON i.periodo_id = p.id 
             JOIN carreras c ON i.carrera_id = c.id 
             WHERE i.usuario_id = ? 
             ORDER BY p.fecha_inicio DESC";
$stmt = $conn->prepare($sql_insc);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$inscripciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Procesar asignación de horario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_horario'])) {
    $materia_id = (int)$_POST['materia_id'];
    $grupo_id = (int)$_POST['grupo_id'];
    $periodo_id = (int)$_POST['periodo_id'];
    $docente_id = (int)$_POST['docente_id'];
    $dia_semana = $_POST['dia_semana'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $aula = $_POST['aula'];
    
    // Verificar si ya existe un horario para esta materia en este período
    $stmt = $conn->prepare("SELECT id FROM horarios WHERE materia_id = ? AND periodo_id = ? AND grupo_id = ?");
    $stmt->bind_param("iii", $materia_id, $periodo_id, $grupo_id);
    $stmt->execute();
    $existe = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    
    if ($existe) {
        // Actualizar
        $stmt = $conn->prepare("UPDATE horarios SET docente_id = ?, dia_semana = ?, hora_inicio = ?, hora_fin = ?, aula = ? 
                                WHERE materia_id = ? AND periodo_id = ? AND grupo_id = ?");
        $stmt->bind_param("issssiii", $docente_id, $dia_semana, $hora_inicio, $hora_fin, $aula, $materia_id, $periodo_id, $grupo_id);
    } else {
        // Insertar
        $stmt = $conn->prepare("INSERT INTO horarios (materia_id, grupo_id, docente_id, dia_semana, hora_inicio, hora_fin, aula, periodo_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiissssi", $materia_id, $grupo_id, $docente_id, $dia_semana, $hora_inicio, $hora_fin, $aula, $periodo_id);
    }
    
    if ($stmt->execute()) {
        $mensaje = "✅ Horario asignado correctamente.";
        $mensaje_tipo = "success";
    } else {
        $mensaje = "❌ Error al asignar horario: " . $stmt->error;
        $mensaje_tipo = "error";
    }
    $stmt->close();
}

// Obtener grupos disponibles
$grupos = $conn->query("SELECT g.*, c.nombre as carrera_nombre 
                        FROM grupos g 
                        JOIN carreras c ON g.carrera_id = c.id 
                        ORDER BY c.nombre, g.nombre")->fetch_all(MYSQLI_ASSOC);

// Obtener docentes
$docentes = $conn->query("SELECT Id, Nombre FROM usuarios WHERE Rol = 'Docente' ORDER BY Nombre")->fetch_all(MYSQLI_ASSOC);

// Obtener materias del estudiante
$materias_estudiante = [];
$periodo_actual = 0;
if (!empty($inscripciones)) {
    $periodo_actual = $inscripciones[0]['periodo_id'];
    $sql_mats = "SELECT idet.id as detalle_id, m.id as materia_id, m.nombre, m.clave 
                 FROM inscripciones_detalle idet 
                 JOIN materias m ON idet.materia_id = m.id 
                 WHERE idet.inscripcion_id = ?";
    $stmt = $conn->prepare($sql_mats);
    $stmt->bind_param("i", $inscripciones[0]['id']);
    $stmt->execute();
    $materias_estudiante = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Horario - ITSZ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --azul: #1B396A;
            --guinda: #620F31;
            --dorado: #D99A32;
            --blanco: #FFFFFF;
            --gris: #F1F1F1;
            --gris-oscuro: #D9D9D9;
            --texto-oscuro: #333333;
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 30px rgba(0,0,0,0.15);
            --radius: 12px;
            --radius-lg: 20px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .card {
            background: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 24px 30px;
            border-bottom: 4px solid var(--dorado);
        }

        .card-header h1 {
            color: var(--blanco);
            font-size: 24px;
            font-weight: 800;
        }

        .card-header h1 i {
            color: var(--dorado);
            margin-right: 12px;
        }

        .card-header p {
            color: rgba(255,255,255,0.85);
            font-size: 14px;
            margin-top: 4px;
        }

        .card-body {
            padding: 30px;
        }

        .info-estudiante {
            background: var(--gris);
            padding: 16px 20px;
            border-radius: var(--radius);
            margin-bottom: 24px;
            border-left: 4px solid var(--dorado);
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }

        .info-estudiante .item .label {
            font-size: 11px;
            text-transform: uppercase;
            color: var(--texto-oscuro);
            opacity: 0.6;
            font-weight: 600;
        }

        .info-estudiante .item .value {
            font-weight: 700;
            color: var(--azul);
            font-size: 15px;
        }

        .mensaje {
            padding: 14px 20px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            border-left: 5px solid transparent;
        }

        .mensaje.success {
            background: #d4edda;
            color: #155724;
            border-left-color: #27ae60;
        }

        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border-left-color: #e74c3c;
        }

        .form-horario {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-horario .full {
            grid-column: 1 / -1;
        }

        .form-horario label {
            display: block;
            font-weight: 600;
            color: var(--azul);
            margin-bottom: 4px;
            font-size: 13px;
        }

        .form-horario select, .form-horario input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            font-size: 14px;
            font-family: inherit;
            transition: 0.2s;
            background: var(--blanco);
        }

        .form-horario select:focus, .form-horario input:focus {
            border-color: var(--azul);
            outline: none;
            box-shadow: 0 0 0 3px rgba(27, 57, 106, 0.1);
        }

        .btn-asignar {
            padding: 14px 32px;
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-asignar:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .btn-volver {
            display: inline-block;
            padding: 10px 24px;
            background: var(--gris);
            color: var(--texto-oscuro);
            text-decoration: none;
            border-radius: var(--radius);
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-volver:hover {
            background: var(--gris-oscuro);
        }

        .horario-actual {
            margin-top: 24px;
            border-top: 2px solid var(--gris);
            padding-top: 20px;
        }

        .horario-actual h3 {
            color: var(--azul);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .horario-actual h3 i {
            color: var(--dorado);
        }

        .horario-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 12px;
        }

        .horario-item {
            background: var(--gris);
            padding: 14px 18px;
            border-radius: var(--radius);
            border-left: 4px solid var(--azul);
        }

        .horario-item .materia {
            font-weight: 700;
            color: var(--azul);
        }

        .horario-item .detalle {
            font-size: 13px;
            color: var(--texto-oscuro);
            margin-top: 4px;
        }

        .horario-item .detalle i {
            color: var(--dorado);
            margin-right: 4px;
        }

        @media (max-width: 600px) {
            .form-horario {
                grid-template-columns: 1fr;
            }
            .form-horario .full {
                grid-column: 1;
            }
            .card-body { padding: 20px; }
            .card-header { padding: 18px 20px; }
            .info-estudiante { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1><i class="fas fa-calendar-plus"></i> Asignar Horario</h1>
            <p>Configura los horarios de las materias del estudiante</p>
        </div>

        <div class="card-body">
            <!-- Información del estudiante -->
            <div class="info-estudiante">
                <div class="item">
                    <span class="label">Estudiante</span>
                    <span class="value"><i class="fas fa-user"></i> <?php echo htmlspecialchars($estudiante['Nombre']); ?></span>
                </div>
                <div class="item">
                    <span class="label">Correo</span>
                    <span class="value"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($estudiante['Correo']); ?></span>
                </div>
                <div class="item">
                    <span class="label">Grupo</span>
                    <span class="value"><i class="fas fa-users"></i> <?php echo htmlspecialchars($estudiante['grupo'] ?? 'Sin asignar'); ?></span>
                </div>
                <div class="item">
                    <span class="label">Semestre</span>
                    <span class="value"><i class="fas fa-graduation-cap"></i> <?php echo $estudiante['semestre'] ?? 'N/A'; ?>°</span>
                </div>
            </div>

            <?php if ($mensaje): ?>
                <div class="mensaje <?php echo $mensaje_tipo; ?>">
                    <i class="fas <?php echo $mensaje_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($materias_estudiante)): ?>
                <div style="text-align: center; padding: 40px; color: var(--texto-oscuro); opacity: 0.6;">
                    <i class="fas fa-book" style="font-size: 48px; display: block; margin-bottom: 12px;"></i>
                    <p>Este estudiante no tiene materias inscritas.</p>
                    <p style="font-size: 14px;">Primero debe realizar su inscripción de materias.</p>
                </div>
            <?php else: ?>
                <!-- Formulario de asignación -->
                <form method="POST" class="form-horario">
                    <div class="full">
                        <label><i class="fas fa-book"></i> Materia</label>
                        <select name="materia_id" required>
                            <option value="">Selecciona una materia...</option>
                            <?php foreach ($materias_estudiante as $m): ?>
                                <option value="<?php echo $m['materia_id']; ?>">
                                    <?php echo htmlspecialchars($m['clave']); ?> - <?php echo htmlspecialchars($m['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label><i class="fas fa-users"></i> Grupo</label>
                        <select name="grupo_id" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($grupos as $g): ?>
                                <option value="<?php echo $g['id']; ?>">
                                    <?php echo htmlspecialchars($g['nombre']); ?> (<?php echo htmlspecialchars($g['carrera_nombre']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label><i class="fas fa-user-tie"></i> Docente</label>
                        <select name="docente_id" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($docentes as $d): ?>
                                <option value="<?php echo $d['Id']; ?>">
                                    <?php echo htmlspecialchars($d['Nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label><i class="fas fa-calendar-day"></i> Día</label>
                        <select name="dia_semana" required>
                            <option value="">Selecciona...</option>
                            <?php foreach ($dias_semana as $dia): ?>
                                <option value="<?php echo $dia; ?>"><?php echo $dia; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label><i class="fas fa-clock"></i> Hora Inicio</label>
                        <input type="time" name="hora_inicio" required>
                    </div>

                    <div>
                        <label><i class="fas fa-clock"></i> Hora Fin</label>
                        <input type="time" name="hora_fin" required>
                    </div>

                    <div>
                        <label><i class="fas fa-door-open"></i> Aula</label>
                        <input type="text" name="aula" placeholder="Ej: A-101" required>
                    </div>

                    <div>
                        <label><i class="fas fa-calendar"></i> Período</label>
                        <select name="periodo_id" required>
                            <?php foreach ($inscripciones as $insc): ?>
                                <option value="<?php echo $insc['periodo_id']; ?>" <?php echo ($insc['periodo_id'] == $periodo_actual) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($insc['periodo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="full" style="display: flex; gap: 12px; flex-wrap: wrap;">
                        <button type="submit" name="asignar_horario" class="btn-asignar">
                            <i class="fas fa-save"></i> Asignar Horario
                        </button>
                        <a href="admin_gestionar_usuarios.php" class="btn-volver">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>