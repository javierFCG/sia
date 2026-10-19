<?php
// estudiante_boleta.php - Ver boleta de calificaciones CON LOGO
session_start();
require_once 'config.php';
require_once 'funciones.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] === 'Administrador') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Obtener LOGO de la universidad
$sql_logo = "SELECT valor FROM configuracion WHERE clave = 'logo_universidad'";
$res_logo = $conn->query($sql_logo);
$ruta_logo = ($res_logo->num_rows > 0) ? $res_logo->fetch_assoc()['valor'] : 'uploads/logo_universidad.png';

// Obtener períodos con inscripciones del estudiante
$sql_periodos = "SELECT DISTINCT p.id, p.nombre, p.fecha_inicio, p.fecha_fin 
                 FROM inscripciones i 
                 JOIN periodos p ON i.periodo_id = p.id 
                 WHERE i.usuario_id = ? 
                 ORDER BY p.fecha_inicio DESC";
$stmt = $conn->prepare($sql_periodos);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$periodos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$periodo_seleccionado = isset($_GET['periodo_id']) ? (int)$_GET['periodo_id'] : 0;

if ($periodo_seleccionado == 0 && count($periodos) > 0) {
    $periodo_seleccionado = $periodos[0]['id'];
}

$inscripcion_id = 0;
$carrera_nombre = '';
$periodo_nombre = '';
$materias = [];

if ($periodo_seleccionado > 0) {
    $sql_insc = "SELECT i.id, c.nombre as carrera, p.nombre as periodo 
                 FROM inscripciones i 
                 JOIN carreras c ON i.carrera_id = c.id 
                 JOIN periodos p ON i.periodo_id = p.id 
                 WHERE i.usuario_id = ? AND i.periodo_id = ?";
    $stmt = $conn->prepare($sql_insc);
    $stmt->bind_param("ii", $user_id, $periodo_seleccionado);
    $stmt->execute();
    $insc = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($insc) {
        $inscripcion_id = $insc['id'];
        $carrera_nombre = $insc['carrera'];
        $periodo_nombre = $insc['periodo'];
        
        $sql_materias = "SELECT m.nombre, m.clave, m.creditos, idet.calificacion 
                         FROM inscripciones_detalle idet 
                         JOIN materias m ON idet.materia_id = m.id 
                         WHERE idet.inscripcion_id = ? 
                         ORDER BY m.nombre ASC";
        $stmt = $conn->prepare($sql_materias);
        $stmt->bind_param("i", $inscripcion_id);
        $stmt->execute();
        $materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// Calcular estadísticas
$total_creditos = 0;
$creditos_aprobados = 0;
$materias_aprobadas = 0;
$materias_reprobadas = 0;
$materias_pendientes = 0;
$suma_calificaciones = 0;
$total_calificadas = 0;

foreach ($materias as $m) {
    $total_creditos += $m['creditos'];
    if ($m['calificacion'] !== null && $m['calificacion'] !== '') {
        $total_calificadas++;
        $suma_calificaciones += $m['calificacion'];
        if ($m['calificacion'] >= 70) {
            $creditos_aprobados += $m['creditos'];
            $materias_aprobadas++;
        } else {
            $materias_reprobadas++;
        }
    } else {
        $materias_pendientes++;
    }
}

$promedio_general = $total_calificadas > 0 ? round($suma_calificaciones / $total_calificadas, 2) : 0;
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Boleta - ITSZ</title>
    <link rel="icon" href="assets/img/favicon.ico">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --azul: #1B396A;
            --guinda: #620F31;
            --rojo: #860A0F;
            --dorado: #D99A32;
            --blanco: #FFFFFF;
            --gris: #F1F1F1;
            --gris-oscuro: #D9D9D9;
            --shadow: 0 4px 15px rgba(0,0,0,0.1);
            --shadow-hover: 0 8px 25px rgba(0,0,0,0.15);
            --radius: 12px;
            --radius-lg: 20px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', 'Poppins', sans-serif; }
        body { background: var(--gris); color: #333; }

        .header-itsz {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            padding: 8px 0; box-shadow: var(--shadow); position: sticky; top: 0; z-index: 1000;
        }
        .header-itsz .container {
            max-width: 1200px; margin: 0 auto; padding: 0 20px;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;
        }
        .header-itsz .logo-container { display: flex; align-items: center; gap: 12px; }
        .header-itsz .logo-container img { height: 45px; width: auto; }
        .header-itsz .logo-container .titulo { color: var(--blanco); font-size: 13px; line-height: 1.2; }
        .header-itsz .logo-container .titulo strong { font-size: 16px; display: block; color: var(--dorado); }
        .header-itsz nav { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .header-itsz nav a { color: var(--blanco); font-size: 13px; font-weight: 500; padding: 6px 14px; border-radius: var(--radius); transition: all 0.3s; }
        .header-itsz nav a:hover { background: rgba(255,255,255,0.15); transform: translateY(-2px); }
        .header-itsz nav .btn-logout { background: var(--rojo); color: var(--blanco); padding: 6px 16px; border-radius: var(--radius); font-weight: 600; }
        .header-itsz nav .btn-logout:hover { background: #6b080c; }

        .container { max-width: 1000px; margin: 20px auto; padding: 0 20px; }

        .page-header {
            background: white; padding: 20px 25px; border-radius: var(--radius); box-shadow: var(--shadow);
            margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
        }
        .page-header h1 { color: var(--azul); font-size: 24px; }
        .page-header h1 i { color: var(--guinda); }
        .page-header p { color: var(--gris-oscuro); font-size: 14px; margin-top: 5px; }
        .btn-volver { background: var(--gris-oscuro); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-volver:hover { background: #b0b0b0; transform: translateY(-2px); }
        .btn-descargar { background: #8e44ad; color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; transition: all 0.3s; font-size: 14px; text-decoration: none; display: inline-block; }
        .btn-descargar:hover { background: #7d3c98; transform: translateY(-2px); }

        .selector-periodo {
            background: white; padding: 15px 20px; border-radius: var(--radius); box-shadow: var(--shadow);
            margin-bottom: 25px; display: flex; flex-wrap: wrap; align-items: center; gap: 15px;
            border-left: 5px solid var(--azul);
        }
        .selector-periodo label { font-weight: 600; color: var(--azul); font-size: 14px; }
        .selector-periodo select {
            padding: 10px 15px; border: 2px solid var(--gris-oscuro); border-radius: var(--radius);
            font-size: 14px; flex: 1; min-width: 200px; background: var(--blanco); transition: all 0.3s;
        }
        .selector-periodo select:focus { border-color: var(--azul); outline: none; }
        .btn-ver { background: var(--azul); color: var(--blanco); padding: 10px 25px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-ver:hover { background: var(--guinda); transform: translateY(-2px); }

        .boleta {
            background: white; border-radius: var(--radius); box-shadow: var(--shadow); padding: 35px 40px;
            border: 2px solid var(--azul);
        }
        .boleta-header {
            display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid var(--azul);
            padding-bottom: 15px; margin-bottom: 20px;
        }
        .boleta-header .logo-container { flex: 0 0 100px; }
        .boleta-header .logo-container img { max-height: 70px; max-width: 100px; }
        .boleta-header .titulo-container { flex: 1; text-align: center; }
        .boleta-header .titulo-container h2 { color: var(--azul); font-size: 22px; text-transform: uppercase; letter-spacing: 2px; margin: 0; }
        .boleta-header .titulo-container p { color: var(--gris-oscuro); font-size: 12px; margin: 3px 0 0; }
        .boleta-header .espacio { flex: 0 0 100px; }

        .boleta-info {
            display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;
            padding: 15px; background: #f8f5f0; border-radius: var(--radius); border-left: 4px solid var(--azul);
        }
        .boleta-info-item { display: flex; flex-direction: column; }
        .boleta-info-item .label { font-size: 10px; text-transform: uppercase; color: var(--gris-oscuro); font-weight: 600; letter-spacing: 0.5px; }
        .boleta-info-item .value { font-size: 16px; font-weight: 700; color: var(--azul); }

        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--gris-oscuro); font-size: 13px; vertical-align: middle; }
        th { background: var(--azul); color: var(--blanco); font-weight: 600; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        tr:hover { background: #f8f5f0; }

        .calificacion-aprobada { color: #27ae60; font-weight: 700; }
        .calificacion-reprobada { color: var(--rojo); font-weight: 700; }
        .calificacion-pendiente { color: var(--dorado); font-weight: 600; }

        .boleta-footer {
            margin-top: 20px; padding-top: 20px; border-top: 3px solid var(--azul);
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;
        }
        .boleta-footer-item { text-align: center; padding: 12px; background: #f8f5f0; border-radius: var(--radius); }
        .boleta-footer-item .label { font-size: 10px; text-transform: uppercase; color: var(--gris-oscuro); font-weight: 600; letter-spacing: 0.5px; }
        .boleta-footer-item .value { font-size: 22px; font-weight: 700; color: var(--azul); }
        .boleta-footer-item .value.aprobado { color: #27ae60; }
        .boleta-footer-item .value.reprobado { color: var(--rojo); }
        .boleta-footer-item .value.pendiente { color: var(--dorado); }

        .empty-state { text-align: center; padding: 60px; background: white; border-radius: var(--radius); box-shadow: var(--shadow); }
        .empty-state i { font-size: 60px; color: var(--gris-oscuro); margin-bottom: 15px; display: block; }
        .empty-state h3 { color: var(--azul); font-size: 20px; margin-bottom: 10px; }
        .empty-state p { color: var(--gris-oscuro); font-size: 15px; }

        .footer-itsz {
            background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco); padding: 20px; text-align: center; margin-top: 40px;
        }
        .footer-itsz .container { max-width: 1200px; margin: 0 auto; }
        .footer-itsz .social-links { display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin: 10px 0; }
        .footer-itsz .social-links a { color: var(--blanco); font-size: 18px; transition: all 0.3s; }
        .footer-itsz .social-links a:hover { color: var(--dorado); transform: translateY(-3px); }
        .footer-itsz hr { border-color: rgba(255,255,255,0.15); margin: 10px 0; }
        .footer-itsz .copy { font-size: 11px; opacity: 0.8; }

        @media (max-width: 768px) {
            .header-itsz .container { flex-direction: column; align-items: center; text-align: center; }
            .header-itsz .logo-container { flex-direction: column; }
            .header-itsz nav { justify-content: center; }
            .header-itsz nav a { font-size: 11px; padding: 4px 8px; }
            .page-header { flex-direction: column; text-align: center; }
            .selector-periodo { flex-direction: column; align-items: stretch; }
            .selector-periodo select { min-width: 100%; }
            .boleta { padding: 20px; }
            .boleta-header { flex-direction: column; text-align: center; }
            .boleta-header .logo-container { margin-bottom: 10px; }
            .boleta-header .espacio { display: none; }
            .boleta-info { grid-template-columns: 1fr; }
            .boleta-footer { grid-template-columns: 1fr; }
            table { font-size: 12px; }
            th, td { padding: 6px 8px; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            .boleta { padding: 15px; }
            table { font-size: 10px; }
            th, td { padding: 4px 5px; }
            .boleta-footer-item .value { font-size: 18px; }
        }
    </style>
</head>
<body>
<header class="header-itsz">
    <div class="container">
        <div class="logo-container">
            <img src="assets/img/logo_itsz.png" alt="ITSZ Logo" onerror="this.src='uploads/logo_universidad.png'">
            <div class="titulo"><strong>Tecnológico Superior de Zongolica</strong><small>Sistema Integral Académico</small></div>
        </div>
        <nav>
            <a href="index.php"><i class='bx bx-home'></i> Inicio</a>
            <a href="estudiante_panel.php"><i class='bx bxs-graduation'></i> Panel</a>
            <a href="mensajes.php"><i class='bx bxs-envelope'></i> Mensajes</a>
            <a href="logout.php" class="btn-logout"><i class='bx bx-log-out'></i> Salir</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class='bx bxs-file-pdf'></i> Mi Boleta de Calificaciones</h1>
            <p>Consulta tus calificaciones por período académico</p>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="estudiante_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
            <?php if ($periodo_seleccionado > 0 && count($materias) > 0): ?>
                <button onclick="descargarPDF()" class="btn-descargar"><i class='bx bxs-download'></i> Descargar PDF</button>
            <?php endif; ?>
        </div>
    </div>

    <form method="GET" class="selector-periodo">
        <label for="periodo_id"><i class='bx bxs-calendar'></i> Seleccionar Período:</label>
        <select name="periodo_id" id="periodo_id">
            <?php foreach ($periodos as $p): ?>
                <option value="<?php echo $p['id']; ?>" <?php echo ($periodo_seleccionado == $p['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($p['nombre']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-ver"><i class='bx bx-search'></i> Ver Boleta</button>
    </form>

    <?php if (count($periodos) == 0): ?>
        <div class="empty-state">
            <i class='bx bx-calendar-x'></i>
            <h3>No tienes inscripciones registradas</h3>
            <p>Realiza tu primera inscripción para ver tus calificaciones.</p>
            <a href="estudiante_inscripcion.php" class="btn-ver" style="display:inline-block; margin-top:15px;">
                <i class='bx bxs-edit'></i> Ir a Inscripción
            </a>
        </div>
    <?php elseif ($periodo_seleccionado > 0 && count($materias) == 0): ?>
        <div class="empty-state">
            <i class='bx bxs-book-open'></i>
            <h3>No hay materias inscritas en este período</h3>
            <p>No tienes materias registradas para el período seleccionado.</p>
        </div>
    <?php elseif ($periodo_seleccionado > 0 && count($materias) > 0): ?>
        <div id="contenido-pdf" class="boleta">
            <!-- Header -->
            <div class="boleta-header">
                <div class="logo-container">
                    <?php if (file_exists($ruta_logo)): ?>
                        <img src="<?php echo $ruta_logo; ?>" alt="Logo ITSZ">
                    <?php endif; ?>
                </div>
                <div class="titulo-container">
                    <h2>Boleta de Calificaciones</h2>
                    <p>Sistema Integral Académico (SIA) | Documento Oficial</p>
                    <p>Fecha de emisión: <?php echo date('d/m/Y H:i'); ?></p>
                </div>
                <div class="espacio"></div>
            </div>

            <!-- Información -->
            <div class="boleta-info">
                <div class="boleta-info-item">
                    <span class="label">Estudiante</span>
                    <span class="value"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                </div>
                <div class="boleta-info-item">
                    <span class="label">Carrera</span>
                    <span class="value"><?php echo htmlspecialchars($carrera_nombre); ?></span>
                </div>
                <div class="boleta-info-item">
                    <span class="label">Período</span>
                    <span class="value"><?php echo htmlspecialchars($periodo_nombre); ?></span>
                </div>
                <div class="boleta-info-item">
                    <span class="label">Promedio General</span>
                    <span class="value" style="color: <?php echo $promedio_general >= 70 ? '#27ae60' : ($promedio_general > 0 ? 'var(--rojo)' : 'var(--gris-oscuro)'); ?>;">
                        <?php echo $promedio_general > 0 ? number_format($promedio_general, 2) : 'N/A'; ?>
                    </span>
                </div>
            </div>

            <!-- Tabla -->
            <table>
                <thead>
                    <tr>
                        <th>Clave</th>
                        <th>Materia</th>
                        <th style="text-align:center;">Créditos</th>
                        <th style="text-align:center;">Calificación</th>
                        <th style="text-align:center;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($materias as $m): 
                        $estado = obtenerEstadoCalificacion($m['calificacion']);
                        $letra = obtenerCalificacionLetra($m['calificacion']);
                        $clase_calif = 'calificacion-pendiente';
                        if ($m['calificacion'] !== null) {
                            $clase_calif = $m['calificacion'] >= 70 ? 'calificacion-aprobada' : 'calificacion-reprobada';
                        }
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($m['clave']); ?></td>
                            <td><strong><?php echo htmlspecialchars($m['nombre']); ?></strong></td>
                            <td style="text-align:center;"><?php echo $m['creditos']; ?></td>
                            <td style="text-align:center; font-weight:700;" class="<?php echo $clase_calif; ?>">
                                <?php echo $m['calificacion'] !== null ? number_format($m['calificacion'], 1) : '—'; ?>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($m['calificacion'] !== null): ?>
                                    <span style="padding:2px 12px; border-radius:12px; font-size:11px; font-weight:600; 
                                        <?php echo $m['calificacion'] >= 70 ? 'background:#d4edda; color:#155724;' : 'background:#f8d7da; color:#721c24;'; ?>">
                                        <?php echo $estado; ?> (<?php echo $letra; ?>)
                                    </span>
                                <?php else: ?>
                                    <span style="padding:2px 12px; border-radius:12px; font-size:11px; font-weight:600; background:#fff3cd; color:#856404;">
                                        Pendiente
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Footer -->
            <div class="boleta-footer">
                <div class="boleta-footer-item">
                    <span class="label">Total Créditos</span>
                    <span class="value"><?php echo $total_creditos; ?></span>
                </div>
                <div class="boleta-footer-item">
                    <span class="label">Aprobadas</span>
                    <span class="value aprobado"><?php echo $materias_aprobadas; ?></span>
                </div>
                <div class="boleta-footer-item">
                    <span class="label">Reprobadas</span>
                    <span class="value reprobado"><?php echo $materias_reprobadas; ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<footer class="footer-itsz">
    <div class="container">
        <p><strong>Tecnológico Superior de Zongolica</strong><br>Carretera a la Compañía S/N, Zongolica, Veracruz</p>
        <div class="social-links">
            <a href="#"><i class='bx bxl-facebook'></i></a>
            <a href="#"><i class='bx bxl-twitter'></i></a>
            <a href="#"><i class='bx bxl-instagram'></i></a>
            <a href="#"><i class='bx bxl-youtube'></i></a>
        </div>
        <hr><p class="copy">&copy; <?php echo date('Y'); ?> Tecnológico Superior de Zongolica - Todos los derechos reservados</p>
    </div>
</footer>

<script>
    function descargarPDF() {
        const elemento = document.getElementById('contenido-pdf');
        const opciones = {
            margin: [0.3, 0.3, 0.3, 0.3],
            filename: 'Boleta_Calificaciones_<?php echo date('Ymd'); ?>.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        html2pdf().set(opciones).from(elemento).save();
    }
</script>
</body>
</html>