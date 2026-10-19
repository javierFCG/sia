<?php
// docente_panel.php - Panel Docente CON HORARIOS
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Docente') {
    header("Location: index.php");
    exit();
}

$docente_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Obtener materias asignadas con información de grupo y horario
$sql_materias = "SELECT DISTINCT 
                    m.id, 
                    m.nombre, 
                    m.clave, 
                    m.semestre, 
                    m.horario, 
                    c.nombre as carrera,
                    (SELECT GROUP_CONCAT(DISTINCT CONCAT(g.nombre, ' (', h.dia_semana, ' ', TIME_FORMAT(h.hora_inicio, '%H:%i'), '-', TIME_FORMAT(h.hora_fin, '%H:%i'), ')') SEPARATOR ', ')
                     FROM horarios h 
                     JOIN grupos g ON h.grupo_id = g.id 
                     WHERE h.materia_id = m.id AND h.docente_id = ?) as grupos_horarios
                 FROM materias m 
                 JOIN carreras c ON m.carrera_id = c.id 
                 WHERE m.docente_asignado = ? 
                 ORDER BY m.semestre ASC, m.nombre ASC";
$stmt = $conn->prepare($sql_materias);
$stmt->bind_param("is", $docente_id, $username);
$stmt->execute();
$materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Obtener horarios del docente para el panel de horarios
$sql_horarios = "SELECT h.*, m.nombre as materia_nombre, m.clave, g.nombre as grupo_nombre
                FROM horarios h 
                JOIN materias m ON h.materia_id = m.id 
                JOIN grupos g ON h.grupo_id = g.id 
                WHERE h.docente_id = ? 
                ORDER BY h.dia_semana, h.hora_inicio";

$stmt = $conn->prepare($sql_horarios);
$stmt->bind_param("i", $docente_id);
$stmt->execute();
$horarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Total de alumnos
$total_alumnos = 0;
foreach ($materias as $m) {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT i.usuario_id) as total 
                            FROM inscripciones_detalle id 
                            JOIN inscripciones i ON id.inscripcion_id = i.id 
                            WHERE id.materia_id = ?");
    $stmt->bind_param("i", $m['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_alumnos += $row['total'];
    $stmt->close();
}

// Detectar conflictos de horario
$conflictos = [];
foreach ($horarios as $i => $h1) {
    foreach ($horarios as $j => $h2) {
        if ($i >= $j) continue;
        if ($h1['dia_semana'] === $h2['dia_semana']) {
            $inicio1 = strtotime($h1['hora_inicio']);
            $fin1 = strtotime($h1['hora_fin']);
            $inicio2 = strtotime($h2['hora_inicio']);
            $fin2 = strtotime($h2['hora_fin']);
            if ($inicio1 < $fin2 && $inicio2 < $fin1) {
                $conflictos[] = [
                    'materia1' => $h1['materia_nombre'],
                    'grupo1' => $h1['grupo_nombre'],
                    'materia2' => $h2['materia_nombre'],
                    'grupo2' => $h2['grupo_nombre'],
                    'dia' => $h1['dia_semana'],
                    'hora1' => date('H:i', strtotime($h1['hora_inicio'])) . '-' . date('H:i', strtotime($h1['hora_fin'])),
                    'hora2' => date('H:i', strtotime($h2['hora_inicio'])) . '-' . date('H:i', strtotime($h2['hora_fin']))
                ];
            }
        }
    }
}

// Organizar horarios por día para la tabla semanal
$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$horarios_por_dia = [];
foreach ($dias_semana as $dia) {
    $horarios_por_dia[$dia] = array_filter($horarios, function($h) use ($dia) {
        return $h['dia_semana'] === $dia;
    });
}

// Generar horas
$horas = [];
for ($h = 7; $h <= 21; $h++) {
    $horas[] = sprintf("%02d:00", $h);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Docente · ITSZ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --azul: #1B396A;
            --guinda: #620F31;
            --rojo: #860A0F;
            --dorado: #D99A32;
            --blanco: #FFFFFF;
            --gris: #F1F1F1;
            --gris-oscuro: #D9D9D9;
            --texto-oscuro: #333333;
            --verde-exito: #27ae60;
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

        .container { max-width: 1200px; margin: 0 auto; }

        .card {
            background: var(--blanco);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 20px 30px;
            border-bottom: 4px solid var(--dorado);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .card-header h1 {
            color: var(--blanco);
            font-size: 22px;
            font-weight: 800;
        }
        .card-header h1 i { color: var(--dorado); margin-right: 12px; }

        .card-header .btn-salir {
            padding: 8px 20px;
            background: var(--rojo);
            color: var(--blanco);
            text-decoration: none;
            border-radius: var(--radius);
            font-weight: 600;
            transition: 0.3s;
        }
        .card-header .btn-salir:hover { background: #6b080c; transform: translateY(-2px); }

        .card-body { padding: 24px 30px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--gris);
            padding: 18px 20px;
            border-radius: var(--radius);
            border-left: 4px solid var(--azul);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .stat-card .info .label {
            font-size: 10px;
            text-transform: uppercase;
            color: var(--texto-oscuro);
            opacity: 0.6;
            font-weight: 600;
        }

        .stat-card .info .number {
            font-size: 28px;
            font-weight: 700;
            color: var(--azul);
        }

        .stat-card .icon {
            font-size: 28px;
            color: var(--dorado);
        }

        .stat-card.conflictos { border-left-color: var(--rojo); }
        .stat-card.conflictos .number { color: var(--rojo); }

        /* ===== ALERTA DE CONFLICTOS ===== */
        .alert-conflicto {
            background: #fde8e8;
            border-left: 4px solid var(--rojo);
            padding: 12px 18px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-conflicto i { color: var(--rojo); font-size: 18px; }
        .alert-conflicto .texto { font-size: 13px; color: #721c24; }

        /* ===== MATERIAS ===== */
        .materias-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .materia-card {
            background: var(--blanco);
            padding: 20px;
            border-radius: var(--radius);
            border: 1px solid var(--gris-oscuro);
            border-left: 4px solid var(--azul);
            transition: all 0.3s;
        }
        .materia-card:hover { border-color: var(--azul); box-shadow: var(--shadow); transform: translateY(-3px); }

        .materia-card h3 {
            color: var(--azul);
            font-size: 17px;
            margin-bottom: 2px;
        }
        .materia-card .clave {
            color: var(--texto-oscuro);
            font-size: 12px;
            opacity: 0.6;
            text-transform: uppercase;
        }
        .materia-card .info {
            margin: 10px 0;
            font-size: 13px;
            color: var(--texto-oscuro);
        }
        .materia-card .info strong { color: var(--azul); }
        .materia-card .horarios {
            font-size: 12px;
            color: var(--guinda);
            background: #fde8e8;
            padding: 6px 12px;
            border-radius: var(--radius);
            margin: 8px 0;
        }
        .materia-card .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }

        .btn-action {
            padding: 6px 16px;
            border-radius: var(--radius);
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            color: var(--blanco);
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-action:hover { transform: translateY(-2px); }

        .btn-calif { background: var(--verde-exito); }
        .btn-calif:hover { background: #219a52; }

        .btn-alumnos { background: var(--azul); }
        .btn-alumnos:hover { background: var(--guinda); }

        .btn-tareas { background: var(--naranja, #f39c12); }
        .btn-tareas:hover { background: #d35400; }

        /* ===== TABLA DE HORARIOS ===== */
        .horario-section {
            margin-top: 24px;
            border-top: 2px solid var(--gris-oscuro);
            padding-top: 20px;
        }

        .horario-section .titulo {
            font-size: 18px;
            font-weight: 700;
            color: var(--azul);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .horario-section .titulo i { color: var(--dorado); }

        .tabla-horario {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .tabla-horario th {
            background: var(--azul);
            color: var(--blanco);
            padding: 8px 6px;
            text-align: center;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
        }

        .tabla-horario td {
            padding: 6px 4px;
            text-align: center;
            border: 1px solid var(--gris-oscuro);
            vertical-align: middle;
            min-height: 40px;
        }

        .tabla-horario .hora-col {
            background: var(--gris);
            font-weight: 700;
            color: var(--azul);
            min-width: 50px;
            font-size: 10px;
        }

        .tabla-horario .sin-clase { color: #ccc; font-size: 11px; }
        .tabla-horario .materia-cell { font-weight: 700; color: var(--azul); font-size: 10px; }
        .tabla-horario .grupo-cell {
            font-size: 9px;
            background: var(--guinda);
            color: var(--blanco);
            padding: 1px 8px;
            border-radius: 8px;
            display: inline-block;
        }
        .tabla-horario .horario-clase { background: #e8f0fe; border-radius: 4px; padding: 4px 6px; }

        .btn-ver-horario {
            padding: 8px 20px;
            background: var(--dorado);
            color: var(--azul);
            border: none;
            border-radius: var(--radius);
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
        }
        .btn-ver-horario:hover { background: #c8a23d; transform: translateY(-2px); }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--texto-oscuro);
            opacity: 0.6;
        }
        .empty-state i { font-size: 48px; display: block; margin-bottom: 12px; }

        @media (max-width: 768px) {
            .card-body { padding: 20px; }
            .card-header { padding: 16px 20px; }
            .card-header h1 { font-size: 18px; }
            .materias-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .tabla-horario { font-size: 10px; }
            .tabla-horario th, .tabla-horario td { padding: 3px 2px; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .btn-action { font-size: 11px; padding: 4px 12px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <div>
                <h1><i class="fas fa-chalkboard-teacher"></i> Panel Docente</h1>
                <p style="color: rgba(255,255,255,0.85); font-size: 13px;">Bienvenido, <strong><?php echo htmlspecialchars($username); ?></strong></p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="mensajes.php" class="btn-action" style="background: var(--azul); padding: 8px 18px;">
                    <i class="fas fa-envelope"></i> Mensajes
                </a>
                <a href="logout.php" class="btn-salir">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>

        <div class="card-body">
            <!-- ===== ESTADÍSTICAS ===== -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="info">
                        <span class="label">Materias Asignadas</span>
                        <span class="number"><?php echo count($materias); ?></span>
                    </div>
                    <span class="icon"><i class="fas fa-book-open"></i></span>
                </div>
                <div class="stat-card">
                    <div class="info">
                        <span class="label">Total Alumnos</span>
                        <span class="number"><?php echo $total_alumnos; ?></span>
                    </div>
                    <span class="icon"><i class="fas fa-users"></i></span>
                </div>
                <div class="stat-card <?php echo count($conflictos) > 0 ? 'conflictos' : ''; ?>">
                    <div class="info">
                        <span class="label">Conflictos de Horario</span>
                        <span class="number"><?php echo count($conflictos); ?></span>
                    </div>
                    <span class="icon"><i class="fas fa-exclamation-triangle"></i></span>
                </div>
            </div>

            <!-- ===== CONFLICTOS ===== -->
            <?php if (count($conflictos) > 0): ?>
                <?php foreach ($conflictos as $c): ?>
                    <div class="alert-conflicto">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="texto">
                            <strong>⚠️ Conflicto:</strong> 
                            <strong><?php echo $c['materia1']; ?></strong> (<?php echo $c['grupo1']; ?>) y 
                            <strong><?php echo $c['materia2']; ?></strong> (<?php echo $c['grupo2']; ?>) 
                            coinciden el <strong><?php echo $c['dia']; ?></strong> a las 
                            <strong><?php echo $c['hora1']; ?></strong> y <strong><?php echo $c['hora2']; ?></strong>.
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- ===== MIS MATERIAS ===== -->
            <h2 style="color: var(--azul); font-size: 18px; margin-bottom: 16px;">
                <i class="fas fa-graduation-cap"></i> Mis Materias
            </h2>

            <?php if (count($materias) > 0): ?>
                <div class="materias-grid">
                    <?php foreach ($materias as $m): ?>
                        <div class="materia-card">
                            <h3><?php echo htmlspecialchars($m['nombre']); ?></h3>
                            <div class="clave"><?php echo htmlspecialchars($m['clave']); ?></div>
                            <div class="info">
                                <strong>Semestre:</strong> <?php echo $m['semestre']; ?>°<br>
                                <strong>Carrera:</strong> <?php echo htmlspecialchars($m['carrera']); ?>
                            </div>
                            <?php if (!empty($m['grupos_horarios'])): ?>
                                <div class="horarios">
                                    <i class="fas fa-clock"></i> 
                                    <?php echo htmlspecialchars($m['grupos_horarios']); ?>
                                </div>
                            <?php else: ?>
                                <div class="horarios" style="background: #fff3cd; color: #856404;">
                                    <i class="fas fa-clock"></i> Sin horario asignado
                                </div>
                            <?php endif; ?>
                            <div class="actions">
                                <a href="docente_calificaciones.php?materia_id=<?php echo $m['id']; ?>" class="btn-action btn-calif">
                                    <i class="fas fa-edit"></i> Calificaciones
                                </a>
                                <a href="docente_alumnos.php?materia_id=<?php echo $m['id']; ?>" class="btn-action btn-alumnos">
                                    <i class="fas fa-users"></i> Alumnos
                                </a>
                                <a href="docente_tareas.php?materia_id=<?php echo $m['id']; ?>" class="btn-action btn-tareas">
                                    <i class="fas fa-tasks"></i> Tareas
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-book"></i>
                    <p>No tienes materias asignadas.</p>
                </div>
            <?php endif; ?>

            <!-- ===== TABLA DE HORARIOS ===== -->
            <div class="horario-section">
                <div class="titulo">
                    <i class="fas fa-calendar-week"></i> Mi Horario Semanal
                    <?php if (count($horarios) > 0): ?>
                        <span style="font-size: 12px; font-weight: 400; color: var(--texto-oscuro); opacity: 0.6;">
                            (<?php echo count($horarios); ?> clases)
                        </span>
                    <?php endif; ?>
                </div>

                <?php if (count($horarios) > 0): ?>
                    <table class="tabla-horario">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <?php foreach ($dias_semana as $dia): ?>
                                    <th><?php echo substr($dia, 0, 3); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($horas as $hora):
                                $hora_obj = DateTime::createFromFormat('H:i', $hora);
                            ?>
                                <tr>
                                    <td class="hora-col"><?php echo $hora; ?></td>
                                    <?php foreach ($dias_semana as $dia):
                                        $clase_encontrada = null;
                                        foreach ($horarios_por_dia[$dia] as $h) {
                                            if (empty($h['hora_inicio']) || empty($h['hora_fin'])) continue;
                                            $hora_inicio = DateTime::createFromFormat('H:i:s', $h['hora_inicio']);
                                            $hora_fin = DateTime::createFromFormat('H:i:s', $h['hora_fin']);
                                            if ($hora_obj >= $hora_inicio && $hora_obj < $hora_fin) {
                                                $clase_encontrada = $h;
                                                break;
                                            }
                                        }
                                    ?>
                                        <td>
                                            <?php if ($clase_encontrada): ?>
                                                <div class="horario-clase">
                                                    <div class="materia-cell"><?php echo htmlspecialchars($clase_encontrada['materia_nombre']); ?></div>
                                                    <div style="margin-top: 2px;">
                                                        <span class="grupo-cell">
                                                            <i class="fas fa-users"></i> <?php echo htmlspecialchars($clase_encontrada['grupo_nombre']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="sin-clase">—</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="margin-top: 16px; display: flex; gap: 12px; flex-wrap: wrap;">
                        <a href="docente_horario.php" class="btn-ver-horario">
                            <i class="fas fa-expand"></i> Ver horario completo
                        </a>
                        <span style="margin-left: auto; font-size: 11px; color: var(--texto-oscuro); opacity: 0.5;">
                            <i class="fas fa-info-circle"></i> Los conflictos se detectan automáticamente
                        </span>
                    </div>

                <?php else: ?>
                    <div class="empty-state" style="padding: 20px;">
                        <i class="fas fa-calendar"></i>
                        <p>No tienes horarios asignados.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>