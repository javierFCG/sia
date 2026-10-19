<?php
// carga.php - Carga Académica con tabla de horarios semanal
session_start();
require_once 'config.php';

// Forzar UTF-8 en la salida HTML
header('Content-Type: text/html; charset=utf-8');

if (!isset($_SESSION['username'])) {
    header("Location: Login.html");
    exit();
}

$rol_actual = $_SESSION['role'];
$user_id = (int)$_SESSION['user_id'];

// ============================================================
// CONEXIÓN ÚNICA CON UTF-8 (usando getDB de config.php)
// ============================================================
$conn = getDB();

// Buscar inscripción activa o usar la proporcionada
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    if ($rol_actual === 'Administrador') {
        die("Error: ID de inscripción no proporcionado.");
    }
    
    $sql_buscar = "SELECT id FROM inscripciones WHERE usuario_id = ? ORDER BY fecha_inscripcion DESC LIMIT 1";
    $stmt_b = $conn->prepare($sql_buscar);
    $stmt_b->bind_param("i", $user_id);
    $stmt_b->execute();
    $result_b = $stmt_b->get_result();
    if ($result_b->num_rows > 0) {
        $row_b = $result_b->fetch_assoc();
        $inscripcion_id = (int)$row_b['id'];
    } else {
        die("No tienes una inscripción activa.");
    }
    $stmt_b->close();
} else {
    $inscripcion_id = (int)$_GET['id'];
}

// --- Lógica ELIMINAR (Solo Admin) ---
if (isset($_GET['accion']) && $_GET['accion'] == 'eliminar' && isset($_GET['id_detalle'])) {
    if ($rol_actual !== 'Administrador') {
        die("Acción no permitida.");
    }
    $id_del = (int)$_GET['id_detalle'];
    $stmt = $conn->prepare("DELETE FROM inscripciones_detalle WHERE id = ? AND inscripcion_id = ?");
    $stmt->bind_param("ii", $id_del, $inscripcion_id);
    if ($stmt->execute()) {
        header("Location: carga.php?id=" . $inscripcion_id);
        exit();
    }
}

// Obtener datos de la inscripción
$sql_header = "SELECT u.Id AS matricula, u.Nombre AS alumno, u.foto_perfil, c.nombre AS carrera, p.nombre AS periodo, i.fecha_inscripcion, i.usuario_id, u.grupo
               FROM inscripciones i 
               JOIN usuarios u ON i.usuario_id = u.Id 
               JOIN carreras c ON i.carrera_id = c.id 
               JOIN periodos p ON i.periodo_id = p.id 
               WHERE i.id = ?";
$stmt = $conn->prepare($sql_header);
$stmt->bind_param("i", $inscripcion_id);
$stmt->execute();
$encabezado = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$encabezado) {
    die("Inscripción no encontrada.");
}

$puede_eliminar = ($rol_actual === 'Administrador') || ($encabezado['usuario_id'] == $user_id);
if ($rol_actual !== 'Administrador' && $encabezado['usuario_id'] != $user_id) {
    die("Acceso denegado: Esta inscripción no te pertenece.");
}

$ruta_foto_est = !empty($encabezado['foto_perfil']) ? $encabezado['foto_perfil'] : 'uploads/default_usuario.png';

$sql_logo = "SELECT valor FROM configuracion WHERE clave = 'logo_universidad'";
$res_logo = $conn->query($sql_logo);
$ruta_logo = ($res_logo && $res_logo->num_rows > 0) ? $res_logo->fetch_assoc()['valor'] : 'uploads/logo_universidad.png';

// Obtener período activo
$periodo_activo = $conn->query("SELECT id FROM periodos WHERE estado = 'Activo' LIMIT 1")->fetch_assoc();
$periodo_id = $periodo_activo ? (int)$periodo_activo['id'] : 0;
$grupo = $encabezado['grupo'] ?? '';

// ============================================================
// 🔍 DETECTAR NOMBRE REAL DE LA COLUMNA DEL DÍA EN `horarios`
// ============================================================
$columna_dia = 'dia_semana';
$check_col = $conn->query("SHOW COLUMNS FROM horarios LIKE 'dia'");
if ($check_col && $check_col->num_rows > 0) {
    $columna_dia = 'dia';
} else {
    $check_col2 = $conn->query("SHOW COLUMNS FROM horarios LIKE 'dia_semana'");
    if ($check_col2 && $check_col2->num_rows > 0) {
        $columna_dia = 'dia_semana';
    }
}

// ============================================================
// 🎯 CONSULTA PRINCIPAL: Buscar horarios por materia_id + periodo_id
// ============================================================
$sql_materias = "SELECT 
                    idet.id AS id_detalle, 
                    m.id AS materia_id,
                    m.nombre AS materia, 
                    m.clave, 
                    m.creditos, 
                    m.semestre,
                    m.docente_asignado,
                    h.{$columna_dia} AS dia_semana,
                    h.hora_inicio,
                    h.hora_fin,
                    h.aula,
                    u.Nombre AS docente_nombre
                 FROM inscripciones_detalle idet 
                 JOIN materias m ON idet.materia_id = m.id 
                 LEFT JOIN horarios h ON h.materia_id = m.id 
                     AND h.periodo_id = ?
                 LEFT JOIN usuarios u ON h.docente_id = u.Id
                 WHERE idet.inscripcion_id = ? 
                 ORDER BY m.semestre ASC, m.nombre ASC";

$stmt_m = $conn->prepare($sql_materias);
$stmt_m->bind_param("ii", $periodo_id, $inscripcion_id);
$stmt_m->execute();
$result_materias = $stmt_m->get_result();
$materias = $result_materias->fetch_all(MYSQLI_ASSOC);
$stmt_m->close();

$total_creditos = array_sum(array_column($materias, 'creditos'));

// Contar materias con horario
$materias_con_horario = 0;
foreach ($materias as $m) {
    if (!empty($m['dia_semana']) && !empty($m['hora_inicio'])) {
        $materias_con_horario++;
    }
}

// Organizar horarios por día para la tabla semanal
$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
$horarios_por_dia = [];
foreach ($dias_semana as $dia) {
    $horarios_por_dia[$dia] = array_filter($materias, function($m) use ($dia) {
        return !empty($m['dia_semana']) && strcasecmp(trim($m['dia_semana']), $dia) === 0;
    });
}

// Generar horas desde 7:00 hasta 21:00
$horas = [];
for ($h = 7; $h <= 21; $h++) {
    $horas[] = sprintf("%02d:00", $h);
}

$conn->close();

function formatearHorario($dia, $hora_inicio, $hora_fin, $aula) {
    if (empty($dia) || empty($hora_inicio) || empty($hora_fin)) {
        return null;
    }
    $inicio = date('H:i', strtotime($hora_inicio));
    $fin = date('H:i', strtotime($hora_fin));
    return [
        'texto' => $inicio . '-' . $fin,
        'aula' => $aula
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carga Académica - <?php echo htmlspecialchars($encabezado['alumno']); ?></title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: #e0e0e0;
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }

        .botones-container {
            width: 100%;
            max-width: 1100px;
            margin-bottom: 20px;
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            color: white;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            box-shadow: 0 3px 6px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.2); }
        .btn-add { background: #2980b9; }
        .btn-add:hover { background: #3498db; }
        .btn-descargar { background: #8e44ad; }
        .btn-descargar:hover { background: #9b59b6; }
        .btn-back { background: #7f8c8d; }
        .btn-back:hover { background: #95a5a6; }

        .debug-aviso {
            max-width: 1100px;
            background: #FFF8E1;
            border-left: 4px solid #D99A32;
            padding: 12px 18px;
            margin-bottom: 15px;
            border-radius: 6px;
            font-size: 13px;
            color: #856404;
            width: 100%;
        }
        .debug-aviso strong { color: #620F31; }
        .debug-aviso code { background: #FFF; padding: 2px 6px; border-radius: 3px; }

        .hoja {
            background: white;
            width: 1100px;
            padding: 35px 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
            box-sizing: border-box;
        }

        .hoja-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid #1B396A;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .hoja-header .logo-img { max-height: 65px; max-width: 110px; }
        .hoja-header .titulo { text-align: center; flex: 1; }
        .hoja-header .titulo h1 { font-size: 20px; text-transform: uppercase; color: #1B396A; letter-spacing: 1px; }
        .hoja-header .titulo p { font-size: 11px; color: #7f8c8d; margin-top: 2px; }
        .hoja-header .foto { height: 80px; width: 70px; object-fit: cover; border: 1px solid #ddd; padding: 3px; border-radius: 4px; }

        .info-alumno {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 6px 30px;
            background: #f8f9fa;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #D99A32;
        }
        .info-alumno .campo .label { font-size: 9px; text-transform: uppercase; color: #888; font-weight: 600; display: block; }
        .info-alumno .campo .valor { font-size: 13px; font-weight: 600; color: #1B396A; }

        .tabla-materias {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 12px;
        }
        .tabla-materias th {
            background: #1B396A;
            color: white;
            padding: 8px 10px;
            text-align: center;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #1B396A;
        }
        .tabla-materias td {
            padding: 8px 10px;
            border: 1px solid #ddd;
            color: #333;
            vertical-align: middle;
            text-align: center;
        }
        .tabla-materias .col-sem { font-weight: 700; background: #f4f4f4; width: 5%; }
        .tabla-materias .col-clave { width: 10%; }
        .tabla-materias .col-materia { text-align: left; font-weight: 600; width: 22%; }
        .tabla-materias .col-docente { width: 15%; font-size: 11px; }
        .tabla-materias .col-horario { width: 20%; font-size: 11px; text-align: left; }
        .tabla-materias .col-cred { width: 5%; font-weight: 700; }
        .tabla-materias .col-accion { width: 6%; }
        .tabla-materias .badge-horario {
            display: inline-block;
            background: #e8f0fe;
            color: #1B396A;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
        }
        .tabla-materias .badge-aula {
            display: inline-block;
            background: #f1f1f1;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 9px;
            color: #666;
            margin-top: 2px;
        }
        .tabla-materias .sin-horario { color: #999; font-style: italic; font-size: 10px; }
        .btn-del { background: #e74c3c; color: white; padding: 3px 10px; font-size: 10px; border-radius: 4px; text-decoration: none; font-weight: 600; transition: 0.2s; }
        .btn-del:hover { background: #c0392b; }

        .titulo-horario {
            font-size: 16px;
            font-weight: 700;
            color: #1B396A;
            margin: 20px 0 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .titulo-horario i { color: #D99A32; }

        .tabla-horario {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 15px;
        }
        .tabla-horario th {
            background: #1B396A;
            color: white;
            padding: 8px 6px;
            text-align: center;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            border: 1px solid #1B396A;
        }
        .tabla-horario td {
            padding: 6px 4px;
            text-align: center;
            border: 1px solid #ddd;
            vertical-align: middle;
            min-height: 40px;
        }
        .tabla-horario .hora-col {
            background: #f8f9fa;
            font-weight: 700;
            color: #1B396A;
            min-width: 50px;
            font-size: 10px;
        }
        .tabla-horario .sin-clase {
            color: #ccc;
            font-size: 11px;
        }
        .tabla-horario .materia-cell {
            font-weight: 700;
            color: #1B396A;
            font-size: 10px;
        }
        .tabla-horario .docente-cell {
            font-size: 9px;
            color: #666;
        }
        .tabla-horario .aula-cell {
            font-size: 9px;
            background: #f1f1f1;
            padding: 1px 6px;
            border-radius: 8px;
            display: inline-block;
            color: #666;
        }
        .tabla-horario .horario-clase {
            background: #e8f0fe;
            border-radius: 4px;
            padding: 4px 6px;
        }

        .total-creditos {
            text-align: right;
            padding: 10px 16px;
            background: #f4f4f4;
            border-radius: 4px;
            margin: 5px 0 10px;
            font-weight: 700;
            font-size: 14px;
            border: 1px solid #ddd;
        }
        .total-creditos span { color: #1B396A; font-size: 17px; }

        .firmas {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #1B396A;
        }
        .firmas .firma { text-align: center; }
        .firmas .firma .linea { border-top: 1px solid #333; padding-top: 8px; font-weight: 700; font-size: 13px; color: #1B396A; }
        .firmas .firma .sub { font-size: 10px; color: #888; font-weight: 400; margin-top: 4px; }

        .hoja-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 12px;
        }

        @media (max-width: 768px) {
            .hoja { width: 100%; padding: 15px; }
            .hoja-header { flex-direction: column; text-align: center; gap: 10px; }
            .hoja-header .foto { height: 60px; width: 50px; }
            .info-alumno { grid-template-columns: 1fr 1fr; gap: 4px; }
            .tabla-materias { font-size: 10px; }
            .tabla-materias th, .tabla-materias td { padding: 4px 6px; }
            .tabla-horario { font-size: 9px; }
            .tabla-horario th, .tabla-horario td { padding: 3px 2px; }
            .firmas { grid-template-columns: 1fr; gap: 15px; }
            .botones-container { flex-direction: column; align-items: center; }
            .btn { width: 100%; justify-content: center; }
        }

        @media print {
            body { background: white; padding: 0; }
            .botones-container, .debug-aviso { display: none !important; }
            .hoja { box-shadow: none; padding: 20px 25px; width: 100%; }
            .tabla-materias th { background: #1B396A !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .tabla-horario th { background: #1B396A !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .tabla-horario .hora-col { background: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .total-creditos { background: #f4f4f4 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .info-alumno { background: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="botones-container" id="menu-acciones">
        <?php if ($puede_eliminar): ?>
            <a href="agregar_materia_carga.php?id_inscripcion=<?php echo $inscripcion_id; ?>" class="btn btn-add">
                <i class="fas fa-plus-circle"></i> Agregar Materia
            </a>
        <?php endif; ?>
        <a href="index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Inicio</a>
        <button onclick="descargarPDF()" class="btn btn-descargar">
            <i class="fas fa-file-pdf"></i> Descargar PDF
        </button>
    </div>

    <!-- Debug: solo aparece si hay materias sin horario -->
    <?php if (count($materias) > 0 && $materias_con_horario === 0): ?>
        <div class="debug-aviso">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Debug:</strong> Tienes <?php echo count($materias); ?> materias inscritas, pero
            <strong>ninguna tiene horario asignado</strong> en la tabla <code>horarios</code> para el período activo (ID: <?php echo $periodo_id; ?>).
            <br><br>
            Verifica en phpMyAdmin que la tabla <code>horarios</code> tenga registros con:
            <code>materia_id</code> que coincida con las materias inscritas y <code>periodo_id=<?php echo $periodo_id; ?></code>.
        </div>
    <?php endif; ?>

    <div id="contenido-pdf" class="hoja">

        <div class="hoja-header">
            <div>
                <?php if (file_exists($ruta_logo)): ?>
                    <img src="<?php echo $ruta_logo; ?>" class="logo-img" alt="Logo">
                <?php endif; ?>
            </div>
            <div class="titulo">
                <h1>Carga Académica</h1>
                <p>Sistema Integral Académico (SIA) | Documento Oficial</p>
                <p style="font-size: 9px; color: #999;">Fecha de emisión: <?php echo date('d/m/Y H:i'); ?></p>
            </div>
            <div>
                <?php if (file_exists($ruta_foto_est)): ?>
                    <img src="<?php echo $ruta_foto_est; ?>" class="foto" alt="Foto">
                <?php else: ?>
                    <div class="foto" style="display:flex;align-items:center;justify-content:center;background:#eee;font-size:10px;color:#999;text-align:center;">Sin Foto</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="info-alumno">
            <div class="campo">
                <span class="label">Nombre del Alumno</span>
                <span class="valor"><?php echo htmlspecialchars($encabezado['alumno'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="campo">
                <span class="label">Matrícula / ID</span>
                <span class="valor"><?php echo htmlspecialchars($encabezado['matricula'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="campo">
                <span class="label">Carrera</span>
                <span class="valor"><?php echo htmlspecialchars($encabezado['carrera'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="campo">
                <span class="label">Período Escolar</span>
                <span class="valor"><?php echo htmlspecialchars($encabezado['periodo'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="campo">
                <span class="label">Grupo</span>
                <span class="valor"><?php echo htmlspecialchars($encabezado['grupo'] ?? 'Sin asignar', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="campo">
                <span class="label">Total Créditos</span>
                <span class="valor"><?php echo $total_creditos; ?></span>
            </div>
        </div>

        <?php if (count($materias) > 0): ?>
            <table class="tabla-materias">
                <thead>
                    <tr>
                        <th class="col-sem">Sem</th>
                        <th class="col-clave">Clave</th>
                        <th class="col-materia">Materia</th>
                        <th class="col-docente">Docente</th>
                        <th class="col-horario">Horario</th>
                        <th class="col-cred">Créd</th>
                        <?php if ($puede_eliminar): ?>
                            <th class="col-accion">X</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($materias as $row): 
                        $docente = !empty($row['docente_nombre']) ? $row['docente_nombre'] : ($row['docente_asignado'] ?? 'Por asignar');
                        $horario = formatearHorario($row['dia_semana'], $row['hora_inicio'], $row['hora_fin'], $row['aula']);
                    ?>
                        <tr>
                            <td class="col-sem"><?php echo $row['semestre'] ?? '1'; ?></td>
                            <td class="col-clave"><?php echo htmlspecialchars($row['clave'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="col-materia"><?php echo htmlspecialchars($row['materia'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="col-docente"><?php echo htmlspecialchars($docente, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="col-horario">
                                <?php if ($horario): ?>
                                    <span class="badge-horario">
                                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars($row['dia_semana'], ENT_QUOTES, 'UTF-8') . ' ' . $horario['texto']; ?>
                                    </span>
                                    <?php if (!empty($horario['aula'])): ?>
                                        <span class="badge-aula"><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($horario['aula'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="sin-horario">Por asignar</span>
                                <?php endif; ?>
                            </td>
                            <td class="col-cred"><?php echo $row['creditos']; ?></td>
                            <?php if ($puede_eliminar): ?>
                                <td class="col-accion">
                                    <a href="carga.php?id=<?php echo $inscripcion_id; ?>&accion=eliminar&id_detalle=<?php echo $row['id_detalle']; ?>" 
                                       class="btn-del" onclick="return confirm('¿Eliminar materia de la carga?');">
                                        <i class="fas fa-times"></i>
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-creditos">
                TOTAL DE CRÉDITOS: <span><?php echo $total_creditos; ?></span>
            </div>

            <div class="titulo-horario">
                <i class="fas fa-calendar-week"></i> Horario Semanal
            </div>

            <table class="tabla-horario">
                <thead>
                    <tr>
                        <th>Hora</th>
                        <?php foreach ($dias_semana as $dia): ?>
                            <th><?php echo mb_substr($dia, 0, 3, 'UTF-8'); ?></th>
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
                                foreach ($horarios_por_dia[$dia] as $m) {
                                    if (empty($m['hora_inicio']) || empty($m['hora_fin'])) continue;
                                    
                                    $hi = strlen($m['hora_inicio']) > 5 ? substr($m['hora_inicio'], 0, 5) : $m['hora_inicio'];
                                    $hf = strlen($m['hora_fin']) > 5 ? substr($m['hora_fin'], 0, 5) : $m['hora_fin'];
                                    
                                    $hora_inicio_obj = DateTime::createFromFormat('H:i', $hi);
                                    $hora_fin_obj    = DateTime::createFromFormat('H:i', $hf);
                                    
                                    if ($hora_inicio_obj && $hora_fin_obj) {
                                        if ($hora_obj >= $hora_inicio_obj && $hora_obj < $hora_fin_obj) {
                                            $clase_encontrada = $m;
                                            break;
                                        }
                                    }
                                }
                            ?>
                                <td>
                                    <?php if ($clase_encontrada): ?>
                                        <div class="horario-clase">
                                            <div class="materia-cell"><?php echo htmlspecialchars($clase_encontrada['materia'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                            <div class="docente-cell">
                                                <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($clase_encontrada['docente_nombre'] ?? 'Sin asignar', ENT_QUOTES, 'UTF-8'); ?>
                                            </div>
                                            <div style="margin-top: 2px;">
                                                <span class="aula-cell"><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($clase_encontrada['aula'] ?? 'Por asignar', ENT_QUOTES, 'UTF-8'); ?></span>
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

        <?php else: ?>
            <p style="text-align:center; padding:30px; color:#999;">
                <i class="fas fa-book" style="font-size:24px; display:block; margin-bottom:10px;"></i>
                No hay materias inscritas en este período.
            </p>
        <?php endif; ?>

        <div class="firmas">
            <div class="firma">
                <div class="linea"><?php echo htmlspecialchars($encabezado['alumno'], ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="sub">Firma del Estudiante</div>
            </div>
            <div class="firma">
                <div class="linea">Depto. de Servicios Escolares</div>
                <div class="sub">Sello y Firma de Autorización</div>
            </div>
        </div>

        <div class="hoja-footer">
            <p>Documento generado automáticamente por el Sistema Integral Académico (SIA)</p>
            <p>Tecnológico Superior de Zongolica &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>

    <script>
        function descargarPDF() {
            const elemento = document.getElementById('contenido-pdf');
            const opciones = {
                margin: [0.3, 0.3, 0.3, 0.3],
                filename: 'Carga_Academica_<?php echo $encabezado['matricula']; ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, scrollY: 0 },
                jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
            };

            const acciones = document.querySelectorAll('.col-accion');
            acciones.forEach(el => el.style.display = 'none');

            html2pdf().set(opciones).from(elemento).save().then(() => {
                acciones.forEach(el => el.style.display = 'table-cell');
            });
        }

        window.onbeforeprint = function() {
            document.querySelectorAll('.col-accion').forEach(el => el.style.display = 'none');
        };
        window.onafterprint = function() {
            document.querySelectorAll('.col-accion').forEach(el => el.style.display = 'table-cell');
        };
    </script>

</body>
</html>