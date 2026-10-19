<?php
// carga_horarios.php - Visualización de horarios para estudiantes (ACTUALIZACIÓN EN TIEMPO REAL)
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] === 'Administrador') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Obtener información del estudiante
$stmt = $conn->prepare("SELECT Nombre, grupo, semestre FROM usuarios WHERE Id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$usuario) {
    die("Usuario no encontrado.");
}

if (empty($usuario['grupo'])) {
    die("No tienes un grupo asignado. Contacta al administrador.");
}

$grupo_nombre = $usuario['grupo'];
$nombre_estudiante = $usuario['Nombre'];
$semestre = $usuario['semestre'] ?? 1;

// Obtener el período activo
$periodo_activo = $conn->query("SELECT id FROM periodos WHERE estado = 'Activo' LIMIT 1")->fetch_assoc();
$periodo_id = $periodo_activo ? $periodo_activo['id'] : 0;

if ($periodo_id == 0) {
    die("No hay un período activo. Contacta al administrador.");
}

// Obtener TODOS los horarios del grupo para el período activo
$sql_horarios = "SELECT h.*, m.nombre as materia_nombre, m.clave, u.Nombre as docente_nombre, g.nombre as grupo_nombre
                FROM horarios h 
                JOIN materias m ON h.materia_id = m.id 
                LEFT JOIN usuarios u ON h.docente_id = u.Id 
                JOIN grupos g ON h.grupo_id = g.id 
                WHERE g.nombre = ? AND h.periodo_id = ?
                ORDER BY h.dia_semana, h.hora_inicio";

$stmt = $conn->prepare($sql_horarios);
$stmt->bind_param("si", $grupo_nombre, $periodo_id);
$stmt->execute();
$horarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();

// Organizar horarios por día
$horarios_por_dia = [];
$dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
foreach ($dias as $dia) {
    $horarios_por_dia[$dia] = array_filter($horarios, function($h) use ($dia) {
        return $h['dia_semana'] === $dia;
    });
}

$total_horarios = count($horarios);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Horario · ITSZ</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* ... (mismos estilos que antes) ... */
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

        .container { max-width: 1100px; margin: 0 auto; }

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

        .card-header h1 i {
            color: var(--dorado);
            margin-right: 12px;
        }

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

        .card-header .btn-volver:hover {
            background: rgba(255,255,255,0.25);
        }

        .card-body { padding: 24px 30px; }

        .info-estudiante {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
            padding: 16px 20px;
            background: var(--gris);
            border-radius: var(--radius);
            border-left: 4px solid var(--dorado);
        }

        .info-estudiante .item .label {
            font-size: 10px;
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

        .tabla-horario {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .tabla-horario th {
            background: var(--azul);
            color: var(--blanco);
            padding: 10px 8px;
            text-align: center;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
        }

        .tabla-horario td {
            padding: 8px 6px;
            text-align: center;
            border-bottom: 1px solid var(--gris-oscuro);
            vertical-align: middle;
            min-height: 50px;
        }

        .tabla-horario .hora-col {
            background: var(--gris);
            font-weight: 700;
            color: var(--azul);
            min-width: 60px;
            font-size: 12px;
        }

        .tabla-horario .sin-clase {
            color: var(--gris-oscuro);
            font-size: 12px;
        }

        .tabla-horario .materia-cell {
            font-weight: 700;
            color: var(--azul);
            font-size: 12px;
        }

        .tabla-horario .docente-cell {
            font-size: 10px;
            color: var(--texto-oscuro);
            opacity: 0.7;
        }

        .tabla-horario .aula-cell {
            font-size: 10px;
            background: var(--gris);
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
        }

        .debug-info {
            background: #fff3cd;
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-size: 13px;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .footer-acciones {
            margin-top: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-descargar {
            padding: 10px 24px;
            background: var(--verde-exito, #27ae60);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-descargar:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        @media (max-width: 768px) {
            .tabla-horario { font-size: 11px; }
            .tabla-horario th, .tabla-horario td { padding: 4px 3px; }
            .tabla-horario .hora-col { min-width: 45px; }
            .card-body { padding: 16px; }
            .card-header { padding: 14px 16px; }
            .card-header h1 { font-size: 18px; }
            .info-estudiante { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 480px) {
            .info-estudiante { grid-template-columns: 1fr; }
            .tabla-horario { font-size: 10px; }
            .tabla-horario .materia-cell { font-size: 10px; }
        }

        @media print {
            body { background: white; padding: 0; }
            .card { box-shadow: none; }
            .card-header { background: var(--azul) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .tabla-horario th { background: var(--azul) !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .btn-descargar, .btn-volver { display: none; }
            .debug-info { display: none; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1><i class="fas fa-calendar-week"></i> Mi Horario</h1>
            <a href="estudiante_panel.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="card-body">
            <!-- Información del estudiante -->
            <div class="info-estudiante">
                <div class="item">
                    <span class="label">Estudiante</span>
                    <span class="value"><i class="fas fa-user"></i> <?php echo htmlspecialchars($nombre_estudiante); ?></span>
                </div>
                <div class="item">
                    <span class="label">Grupo</span>
                    <span class="value"><i class="fas fa-users"></i> <?php echo htmlspecialchars($grupo_nombre); ?></span>
                </div>
                <div class="item">
                    <span class="label">Semestre</span>
                    <span class="value"><i class="fas fa-graduation-cap"></i> <?php echo $semestre; ?>°</span>
                </div>
                <div class="item">
                    <span class="label">Materias</span>
                    <span class="value"><i class="fas fa-book"></i> <?php echo $total_horarios; ?></span>
                </div>
            </div>

            <!-- Información de depuración -->
            <div class="debug-info">
                <i class="fas fa-info-circle"></i> 
                <strong>Horarios encontrados:</strong> <?php echo $total_horarios; ?> 
                (Grupo: <?php echo htmlspecialchars($grupo_nombre); ?>)
            </div>

            <!-- Tabla de horarios -->
            <div id="contenido-pdf">
                <table class="tabla-horario">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <?php foreach ($dias as $dia): ?>
                                <th><?php echo substr($dia, 0, 3); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $horas = [];
                        for ($h = 7; $h <= 21; $h++) {
                            $horas[] = sprintf("%02d:00", $h);
                        }
                        
                        foreach ($horas as $hora):
                            $hora_obj = DateTime::createFromFormat('H:i', $hora);
                        ?>
                            <tr>
                                <td class="hora-col"><?php echo $hora; ?></td>
                                <?php foreach ($dias as $dia): 
                                    $clase_encontrada = null;
                                    foreach ($horarios_por_dia[$dia] as $h) {
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
                                            <div class="materia-cell"><?php echo htmlspecialchars($clase_encontrada['materia_nombre']); ?></div>
                                            <div class="docente-cell">
                                                <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($clase_encontrada['docente_nombre'] ?? 'Sin asignar'); ?>
                                            </div>
                                            <div style="margin-top: 3px;">
                                                <span class="aula-cell"><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($clase_encontrada['aula'] ?? 'Por asignar'); ?></span>
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

            <!-- Footer -->
            <div class="footer-acciones">
                <button onclick="descargarPDF()" class="btn-descargar">
                    <i class="fas fa-file-pdf"></i> Descargar PDF
                </button>
                <div style="margin-left: auto; font-size: 11px; color: var(--texto-oscuro); opacity: 0.5;">
                    <i class="fas fa-info-circle"></i> Horario actualizado en tiempo real
                </div>
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
            filename: 'Horario_<?php echo $grupo_nombre; ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
        };
        html2pdf().set(opciones).from(elemento).save();
    }
</script>

</body>
</html>