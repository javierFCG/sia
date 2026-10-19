<?php
// docente_horario.php - Panel de horarios para docentes
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Docente') {
    header("Location: index.php");
    exit();
}

$docente_id = $_SESSION['user_id'];
$docente_nombre = $_SESSION['username'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Obtener horarios del docente
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

$conn->close();

// Organizar horarios por día
$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$horarios_por_dia = [];
foreach ($dias_semana as $dia) {
    $horarios_por_dia[$dia] = array_filter($horarios, function($h) use ($dia) {
        return $h['dia_semana'] === $dia;
    });
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

// Generar horas desde 7:00 hasta 21:00
$horas = [];
for ($h = 7; $h <= 21; $h++) {
    $horas[] = sprintf("%02d:00", $h);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Horario · Docente · ITSZ</title>
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

        .card-header .btn-volver {
            padding: 8px 20px;
            background: rgba(255,255,255,0.15);
            color: var(--blanco);
            text-decoration: none;
            border-radius: var(--radius);
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.2);
            transition: 0.3s;
        }
        .card-header .btn-volver:hover { background: rgba(255,255,255,0.25); }

        .card-body { padding: 24px 30px; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: var(--gris);
            padding: 14px 18px;
            border-radius: var(--radius);
            border-left: 4px solid var(--azul);
            text-align: center;
        }

        .stat-card .number {
            font-size: 22px;
            font-weight: 700;
            color: var(--azul);
        }

        .stat-card .label {
            font-size: 10px;
            text-transform: uppercase;
            color: var(--texto-oscuro);
            opacity: 0.6;
            font-weight: 600;
        }

        .stat-card.conflictos { border-left-color: var(--rojo); }
        .stat-card.conflictos .number { color: var(--rojo); }

        /* ===== CONFLICTOS ===== */
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

        /* ===== TABLA HORARIO ===== */
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

        .btn-solicitar-ajuste {
            display: inline-block;
            padding: 8px 20px;
            background: var(--dorado);
            color: var(--azul);
            border: none;
            border-radius: var(--radius);
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            text-decoration: none;
            font-size: 13px;
        }
        .btn-solicitar-ajuste:hover { background: var(--dorado-intenso, #F0A51A); transform: translateY(-2px); }

        @media (max-width: 768px) {
            .card-body { padding: 20px; }
            .card-header { padding: 16px 20px; }
            .card-header h1 { font-size: 18px; }
            .tabla-horario { font-size: 10px; }
            .tabla-horario th, .tabla-horario td { padding: 3px 2px; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1><i class="fas fa-calendar-week"></i> Mi Horario</h1>
            <a href="docente_panel.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card-body">
            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="number"><?php echo count($horarios); ?></div>
                    <div class="label">Clases asignadas</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo count(array_unique(array_column($horarios, 'grupo_nombre'))); ?></div>
                    <div class="label">Grupos</div>
                </div>
                <div class="stat-card <?php echo count($conflictos) > 0 ? 'conflictos' : ''; ?>">
                    <div class="number"><?php echo count($conflictos); ?></div>
                    <div class="label">Conflictos detectados</div>
                </div>
            </div>

            <!-- Alertas de conflicto -->
            <?php if (count($conflictos) > 0): ?>
                <?php foreach ($conflictos as $c): ?>
                    <div class="alert-conflicto">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="texto">
                            <strong>⚠️ Conflicto de horario:</strong> 
                            <strong><?php echo $c['materia1']; ?></strong> (Grupo <?php echo $c['grupo1']; ?>) 
                            y <strong><?php echo $c['materia2']; ?></strong> (Grupo <?php echo $c['grupo2']; ?>) 
                            coinciden el <strong><?php echo $c['dia']; ?></strong> a las 
                            <strong><?php echo $c['hora1']; ?></strong> y <strong><?php echo $c['hora2']; ?></strong>.
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Tabla de horarios -->
            <div id="contenido-pdf">
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
            </div>

            <!-- Botones de acción -->
            <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap;">
                <button onclick="descargarPDF()" class="btn-solicitar-ajuste" style="background: var(--verde-exito, #27ae60); color: var(--blanco);">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </button>
                <a href="docente_solicitar_ajuste.php" class="btn-solicitar-ajuste">
                    <i class="fas fa-edit"></i> Solicitar ajuste de horario
                </a>
                <span style="margin-left: auto; font-size: 11px; color: var(--texto-oscuro); opacity: 0.5;">
                    <i class="fas fa-info-circle"></i> Los conflictos se detectan automáticamente
                </span>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    function descargarPDF() {
        const elemento = document.getElementById('contenido-pdf');
        const opciones = {
            margin: [0.3, 0.3, 0.3, 0.3],
            filename: 'Horario_Docente.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
        };
        html2pdf().set(opciones).from(elemento).save();
    }
</script>

</body>
</html>