<?php
// admin_gestionar_horarios.php - Gestión de horarios con docentes asignados
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['grupo_id']) || !is_numeric($_GET['grupo_id'])) {
    die("ID de grupo no especificado.");
}

$grupo_id = (int)$_GET['grupo_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }
$conn->set_charset("utf8mb4");

// ============================================================
// OBTENER INFORMACIÓN DEL GRUPO
// ============================================================
$stmt = $conn->prepare("SELECT g.*, c.nombre as carrera_nombre FROM grupos g JOIN carreras c ON g.carrera_id = c.id WHERE g.id = ?");
$stmt->bind_param("i", $grupo_id);
$stmt->execute();
$grupo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$grupo) { die("Grupo no encontrado."); }

// Obtener períodos activos
$periodos = $conn->query("SELECT id, nombre FROM periodos WHERE estado = 'Activo'")->fetch_all(MYSQLI_ASSOC);

// Obtener docentes
$docentes = $conn->query("
    SELECT u.Id, u.Nombre, u.especialidad
    FROM usuarios u
    WHERE u.Rol = 'Docente'
    ORDER BY u.Nombre
")->fetch_all(MYSQLI_ASSOC);

// Obtener materias de la carrera
$stmt = $conn->prepare("SELECT id, nombre, clave, creditos, semestre, docente_asignado FROM materias WHERE carrera_id = ? ORDER BY semestre, nombre");
$stmt->bind_param("i", $grupo['carrera_id']);
$stmt->execute();
$materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];

// ============================================================
// ✅ NUEVA FUNCIÓN: CAMBIAR DOCENTE DE UN HORARIO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_docente'])) {
    $horario_id = (int)$_POST['horario_id'];
    $nuevo_docente_id = (int)$_POST['nuevo_docente_id'];
    
    $stmt = $conn->prepare("UPDATE horarios SET docente_id = ? WHERE id = ? AND grupo_id = ?");
    $stmt->bind_param("iii", $nuevo_docente_id, $horario_id, $grupo_id);
    
    if ($stmt->execute()) {
        if ($nuevo_docente_id > 0) {
            $mensaje = "✅ Docente reasignado correctamente";
        } else {
            $mensaje = "✅ Docente desasignado. El horario queda sin docente.";
        }
        $mensaje_tipo = "success";
    } else {
        $mensaje = "❌ Error al reasignar: " . $stmt->error;
        $mensaje_tipo = "error";
    }
    $stmt->close();
    
    header("Location: admin_gestionar_horarios.php?grupo_id=" . $grupo_id . "&mensaje=" . urlencode($mensaje) . "&tipo=" . $mensaje_tipo);
    exit();
}

// ============================================================
// ✅ NUEVA FUNCIÓN: DESASIGNAR DOCENTE (PONER EN 0)
// ============================================================
if (isset($_GET['desasignar_docente']) && is_numeric($_GET['desasignar_docente'])) {
    $horario_id = (int)$_GET['desasignar_docente'];
    
    $stmt = $conn->prepare("UPDATE horarios SET docente_id = 0 WHERE id = ? AND grupo_id = ?");
    $stmt->bind_param("ii", $horario_id, $grupo_id);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Docente desasignado. El horario ya no aparecerá en su panel.";
        $mensaje_tipo = "success";
    } else {
        $mensaje = "❌ Error al desasignar: " . $stmt->error;
        $mensaje_tipo = "error";
    }
    $stmt->close();
    
    header("Location: admin_gestionar_horarios.php?grupo_id=" . $grupo_id . "&mensaje=" . urlencode($mensaje) . "&tipo=" . $mensaje_tipo);
    exit();
}

// ============================================================
// PROCESAR AGREGAR HORARIO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_horario'])) {
    $materia_id = (int)$_POST['materia_id'];
    $docente_id = (int)$_POST['docente_id'];
    $dia_semana = $_POST['dia_semana'];
    $hora_inicio = $_POST['hora_inicio'];
    $hora_fin = $_POST['hora_fin'];
    $aula = $_POST['aula'];
    $periodo_id = (int)$_POST['periodo_id'];
    
    if ($hora_inicio >= $hora_fin) {
        $mensaje = "❌ La hora de inicio debe ser menor que la hora de fin.";
        header("Location: admin_gestionar_horarios.php?grupo_id=" . $grupo_id . "&error=" . urlencode($mensaje));
        exit();
    }
    
    // Verificar conflictos de horario para el docente
    if ($docente_id > 0) {
        $stmt = $conn->prepare("SELECT COUNT(*) as conflicto FROM horarios 
                                WHERE docente_id = ? AND dia_semana = ? 
                                AND periodo_id = ? 
                                AND ((hora_inicio < ? AND hora_fin > ?) OR (hora_inicio < ? AND hora_fin > ?) OR (hora_inicio >= ? AND hora_fin <= ?))");
        $stmt->bind_param("issiiiiii", $docente_id, $dia_semana, $periodo_id, $hora_fin, $hora_inicio, $hora_fin, $hora_inicio, $hora_inicio, $hora_fin);
        $stmt->execute();
        $conflicto = $stmt->get_result()->fetch_assoc()['conflicto'];
        $stmt->close();
        
        if ($conflicto > 0) {
            $mensaje = "⚠️ El docente ya tiene una clase en ese horario. Verifica los conflictos.";
            header("Location: admin_gestionar_horarios.php?grupo_id=" . $grupo_id . "&error=" . urlencode($mensaje));
            exit();
        }
    }
    
    // Insertar nuevo horario
    $stmt = $conn->prepare("INSERT INTO horarios (materia_id, grupo_id, docente_id, dia_semana, hora_inicio, hora_fin, aula, periodo_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiissssi", $materia_id, $grupo_id, $docente_id, $dia_semana, $hora_inicio, $hora_fin, $aula, $periodo_id);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Horario agregado correctamente";
        $mensaje_tipo = "success";
    } else {
        $mensaje = "❌ Error al guardar: " . $stmt->error;
        $mensaje_tipo = "error";
    }
    $stmt->close();
    
    header("Location: admin_gestionar_horarios.php?grupo_id=" . $grupo_id . "&mensaje=" . urlencode($mensaje) . "&tipo=" . $mensaje_tipo);
    exit();
}

// ============================================================
// PROCESAR ELIMINAR HORARIO
// ============================================================
if (isset($_GET['eliminar_horario']) && is_numeric($_GET['eliminar_horario'])) {
    $id_eliminar = (int)$_GET['eliminar_horario'];
    
    $stmt = $conn->prepare("DELETE FROM horarios WHERE id = ? AND grupo_id = ?");
    $stmt->bind_param("ii", $id_eliminar, $grupo_id);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Horario eliminado correctamente";
        $mensaje_tipo = "success";
    } else {
        $mensaje = "❌ Error al eliminar: " . $stmt->error;
        $mensaje_tipo = "error";
    }
    $stmt->close();
    
    header("Location: admin_gestionar_horarios.php?grupo_id=" . $grupo_id . "&mensaje=" . urlencode($mensaje) . "&tipo=" . $mensaje_tipo);
    exit();
}

// ============================================================
// OBTENER HORARIOS DEL GRUPO
// ============================================================
$sql = "SELECT h.*, m.nombre as materia_nombre, m.clave, m.creditos, m.docente_asignado as materia_docente,
        u.Nombre as docente_nombre, u.especialidad
        FROM horarios h 
        JOIN materias m ON h.materia_id = m.id 
        LEFT JOIN usuarios u ON h.docente_id = u.Id 
        WHERE h.grupo_id = ? 
        ORDER BY FIELD(h.dia_semana, 'Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'), h.hora_inicio";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $grupo_id);
$stmt->execute();
$horarios = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Detectar conflictos entre horarios
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
                    'materia2' => $h2['materia_nombre'],
                    'dia' => $h1['dia_semana'],
                    'hora1' => date('H:i', strtotime($h1['hora_inicio'])) . '-' . date('H:i', strtotime($h1['hora_fin'])),
                    'hora2' => date('H:i', strtotime($h2['hora_inicio'])) . '-' . date('H:i', strtotime($h2['hora_fin']))
                ];
            }
        }
    }
}

// Organizar horarios por día para la tabla semanal
$horarios_por_dia = [];
foreach ($dias_semana as $dia) {
    $horarios_por_dia[$dia] = array_filter($horarios, function($h) use ($dia) {
        return $h['dia_semana'] === $dia;
    });
}

// Generar horas desde 7:00 hasta 21:00
$horas = [];
for ($h = 7; $h <= 21; $h++) {
    $horas[] = sprintf("%02d:00", $h);
}

$conn->close();

$mensaje = $_GET['mensaje'] ?? '';
$mensaje_tipo = $_GET['tipo'] ?? 'success';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios - ITSZ</title>
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
            --naranja: #f39c12;
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
            margin-bottom: 24px;
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

        .info-grupo {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
            padding: 16px 20px;
            background: var(--gris);
            border-radius: var(--radius);
            border-left: 4px solid var(--dorado);
        }
        .info-grupo .item .label { font-size: 10px; text-transform: uppercase; color: var(--texto-oscuro); opacity: 0.6; font-weight: 600; }
        .info-grupo .item .value { font-weight: 700; color: var(--azul); font-size: 15px; }

        .mensaje {
            padding: 12px 18px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 4px solid transparent;
        }
        .mensaje.success { background: #d4edda; color: #155724; border-left-color: var(--verde-exito); }
        .mensaje.error { background: #f8d7da; color: #721c24; border-left-color: var(--rojo); }

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

        .btn-agregar {
            padding: 10px 24px;
            background: var(--verde-exito);
            color: var(--blanco);
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-agregar:hover { background: #219a52; transform: translateY(-2px); }

        .horario-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-top: 16px;
        }

        @media (max-width: 992px) {
            .horario-section { grid-template-columns: 1fr; }
        }

        .horario-grid {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .horario-card {
            background: var(--blanco);
            border-radius: var(--radius);
            padding: 14px 18px;
            border: 1px solid var(--gris-oscuro);
            border-left: 4px solid var(--azul);
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: all 0.3s;
        }
        .horario-card.sin-docente {
            border-left-color: var(--naranja);
            background: #fffbf0;
        }
        .horario-card:hover {
            border-color: var(--azul);
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }

        .horario-card .info {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 16px;
        }
        .horario-card .info .materia { font-weight: 700; color: var(--azul); font-size: 14px; }
        .horario-card .info .clave { font-size: 11px; color: var(--texto-oscuro); opacity: 0.6; }
        .horario-card .info .dia { background: var(--azul); color: var(--blanco); padding: 2px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .horario-card .info .hora { font-size: 13px; color: var(--texto-oscuro); }
        .horario-card .info .aula { background: var(--gris); padding: 2px 10px; border-radius: 12px; font-size: 11px; }
        .horario-card .info .docente { font-size: 12px; color: var(--texto-oscuro); }
        .horario-card .info .docente.sin-asignar { color: var(--naranja); font-weight: 600; }

        /* ===== NUEVO: BOTONES DE ACCIÓN ===== */
        .horario-card .acciones {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            padding-top: 8px;
            border-top: 1px dashed var(--gris-oscuro);
        }

        .btn-accion-h {
            padding: 6px 14px;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            font-weight: 600;
            font-size: 11px;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--blanco);
        }
        .btn-accion-h:hover { transform: translateY(-2px); }

        .btn-cambiar-docente { background: var(--azul); }
        .btn-cambiar-docente:hover { background: var(--guinda); }

        .btn-desasignar-docente { background: var(--naranja); }
        .btn-desasignar-docente:hover { background: #d35400; }

        .btn-eliminar-horario { background: var(--rojo); }
        .btn-eliminar-horario:hover { background: #6b080c; }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: var(--texto-oscuro);
            opacity: 0.6;
        }
        .empty-state i { font-size: 40px; display: block; margin-bottom: 10px; }

        .titulo-horario {
            font-size: 16px;
            font-weight: 700;
            color: var(--azul);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .titulo-horario i { color: var(--dorado); }

        .tabla-horario {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        .tabla-horario th {
            background: var(--azul);
            color: var(--blanco);
            padding: 8px 6px;
            text-align: center;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 10px;
            border: 1px solid var(--azul);
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
        .tabla-horario .docente-cell { font-size: 9px; color: #666; }
        .tabla-horario .aula-cell { font-size: 9px; background: var(--gris); padding: 1px 6px; border-radius: 8px; display: inline-block; color: #666; }
        .tabla-horario .horario-clase { background: #e8f0fe; border-radius: 4px; padding: 4px 6px; }
        .tabla-horario .sin-docente-cell { color: var(--naranja); font-weight: 600; }

        /* ===== MODALES ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(27, 57, 106, 0.5);
            backdrop-filter: blur(3px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active { display: flex; }

        .modal-content {
            background: var(--blanco);
            padding: 32px;
            border-radius: var(--radius-lg);
            max-width: 520px;
            width: 90%;
            box-shadow: 0 25px 60px rgba(0,0,0,0.25);
            animation: modalFadeIn 0.25s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.95) translateY(15px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-content h3 { color: var(--azul); font-size: 1.2rem; font-weight: 700; margin-bottom: 4px; }
        .modal-content .modal-sub { color: var(--texto-oscuro); font-size: 0.85rem; margin-bottom: 18px; opacity: 0.7; }

        .modal-content .input-group { margin-bottom: 14px; }
        .modal-content .input-group label {
            display: block;
            font-weight: 600;
            color: var(--azul);
            margin-bottom: 4px;
            font-size: 0.8rem;
        }
        .modal-content .input-group select,
        .modal-content .input-group input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--gris-oscuro);
            border-radius: var(--radius);
            font-size: 0.9rem;
            background: var(--gris);
            font-family: 'Inter', sans-serif;
        }
        .modal-content .input-group select:focus,
        .modal-content .input-group input:focus {
            border-color: var(--azul);
            outline: none;
            background: var(--blanco);
        }
        .modal-content .input-group .help-text {
            font-size: 0.7rem;
            color: var(--texto-oscuro);
            opacity: 0.6;
            margin-top: 4px;
        }

        .modal-acciones {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 6px;
        }
        .btn-cancelar-modal {
            background: var(--gris);
            color: var(--texto-oscuro);
            padding: 10px 24px;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-cancelar-modal:hover { background: var(--gris-oscuro); }

        .btn-submit-modal {
            background: var(--azul);
            color: var(--blanco);
            padding: 10px 28px;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-submit-modal:hover { background: var(--guinda); transform: translateY(-2px); }

        .btn-submit-warning {
            background: var(--naranja);
        }
        .btn-submit-warning:hover { background: #d35400; }

        .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

        .info-horario-modal {
            background: #e8f0fe;
            padding: 12px 16px;
            border-radius: var(--radius);
            border-left: 4px solid var(--azul);
            margin-bottom: 16px;
            font-size: 13px;
        }
        .info-horario-modal strong { color: var(--azul); }

        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 16px 24px;
            border-radius: var(--radius);
            color: var(--blanco);
            font-weight: 600;
            box-shadow: var(--shadow-lg);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.5s ease;
            z-index: 3000;
            max-width: 350px;
        }
        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.success { background: var(--verde-exito); }
        .toast.error { background: var(--rojo); }
        .toast.warning { background: var(--naranja); }
        .toast i { margin-right: 10px; }

        @media (max-width: 768px) {
            .card-body { padding: 20px; }
            .card-header { padding: 16px 20px; }
            .card-header h1 { font-size: 18px; }
            .info-grupo { grid-template-columns: 1fr 1fr; }
            .row-2 { grid-template-columns: 1fr; gap: 0; }
            .toast { bottom: 15px; right: 15px; left: 15px; max-width: none; }
        }

        @media (max-width: 480px) {
            .info-grupo { grid-template-columns: 1fr; }
            .modal-content { padding: 24px; }
            .modal-acciones { flex-direction: column; }
            .modal-acciones button { width: 100%; justify-content: center; }
            .horario-card .acciones { flex-direction: column; }
            .btn-accion-h { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>

<div id="toast" class="toast">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage">Mensaje</span>
</div>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h1><i class="fas fa-calendar-plus"></i> Gestión de Horarios</h1>
            <a href="admin_gestionar_grupos.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver a Grupos
            </a>
        </div>

        <div class="card-body">
            <?php if ($mensaje): ?>
                <div class="mensaje <?php echo $mensaje_tipo; ?>">
                    <i class="fas <?php echo $mensaje_tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($mensaje); ?>
                </div>
            <?php endif; ?>

            <?php if (count($conflictos) > 0): ?>
                <?php foreach ($conflictos as $c): ?>
                    <div class="alert-conflicto">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="texto">
                            <strong>⚠️ Conflicto:</strong>
                            <strong><?php echo $c['materia1']; ?></strong> y
                            <strong><?php echo $c['materia2']; ?></strong>
                            coinciden el <strong><?php echo $c['dia']; ?></strong> a las
                            <strong><?php echo $c['hora1']; ?></strong> y <strong><?php echo $c['hora2']; ?></strong>.
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="info-grupo">
                <div class="item"><span class="label">Grupo</span><span class="value"><?php echo htmlspecialchars($grupo['nombre']); ?></span></div>
                <div class="item"><span class="label">Carrera</span><span class="value"><?php echo htmlspecialchars($grupo['carrera_nombre']); ?></span></div>
                <div class="item"><span class="label">Turno</span><span class="value"><?php echo $grupo['turno']; ?></span></div>
                <div class="item"><span class="label">Capacidad</span><span class="value"><?php echo $grupo['capacidad']; ?></span></div>
                <div class="item"><span class="label">Horarios</span><span class="value"><?php echo count($horarios); ?></span></div>
            </div>

            <div style="margin-bottom: 16px;">
                <button onclick="abrirModal()" class="btn-agregar">
                    <i class="fas fa-plus-circle"></i> Agregar Horario
                </button>
            </div>

            <div class="horario-section">
                <div>
                    <div style="font-weight: 700; color: var(--azul); margin-bottom: 10px; font-size: 14px;">
                        <i class="fas fa-list"></i> Horarios asignados
                    </div>
                    <?php if (count($horarios) > 0): ?>
                        <div class="horario-grid">
                            <?php foreach ($horarios as $h): 
                                $sin_docente = empty($h['docente_id']) || $h['docente_id'] == 0;
                            ?>
                                <div class="horario-card <?php echo $sin_docente ? 'sin-docente' : ''; ?>">
                                    <div class="info">
                                        <span class="materia"><?php echo htmlspecialchars($h['materia_nombre']); ?></span>
                                        <span class="clave"><?php echo htmlspecialchars($h['clave']); ?></span>
                                        <span class="dia"><?php echo $h['dia_semana']; ?></span>
                                        <span class="hora">
                                            <i class="fas fa-clock"></i> 
                                            <?php echo date('H:i', strtotime($h['hora_inicio'])); ?> - 
                                            <?php echo date('H:i', strtotime($h['hora_fin'])); ?>
                                        </span>
                                        <span class="aula"><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($h['aula'] ?? 'Por asignar'); ?></span>
                                        <span class="docente <?php echo $sin_docente ? 'sin-asignar' : ''; ?>">
                                            <i class="fas fa-user-tie"></i> 
                                            <?php if ($sin_docente): ?>
                                                ⚠️ Sin docente asignado
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($h['docente_nombre']); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <!-- ✅ NUEVOS BOTONES DE ACCIÓN -->
                                    <div class="acciones">
                                        <button type="button" 
                                                class="btn-accion-h btn-cambiar-docente" 
                                                onclick="abrirModalCambiarDocente(
                                                    <?php echo $h['id']; ?>, 
                                                    '<?php echo addslashes(htmlspecialchars($h['materia_nombre'])); ?>',
                                                    '<?php echo addslashes(htmlspecialchars($h['docente_nombre'] ?? '')); ?>'
                                                )">
                                            <i class="fas fa-exchange-alt"></i> Cambiar docente
                                        </button>
                                        
                                        <?php if (!$sin_docente): ?>
                                            <a href="admin_gestionar_horarios.php?grupo_id=<?php echo $grupo_id; ?>&desasignar_docente=<?php echo $h['id']; ?>" 
                                               class="btn-accion-h btn-desasignar-docente"
                                               onclick="return confirm('¿Desasignar al docente <?php echo addslashes($h['docente_nombre']); ?> de este horario?\n\nEl horario quedará sin docente asignado.')">
                                                <i class="fas fa-user-slash"></i> Desasignar docente
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="admin_gestionar_horarios.php?grupo_id=<?php echo $grupo_id; ?>&eliminar_horario=<?php echo $h['id']; ?>" 
                                           class="btn-accion-h btn-eliminar-horario" 
                                           onclick="return confirm('¿Eliminar este horario completamente?')">
                                            <i class="fas fa-trash-alt"></i> Eliminar
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-xmark"></i>
                            <p>No hay horarios configurados</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <div class="titulo-horario">
                        <i class="fas fa-calendar-week"></i> Vista semanal
                    </div>

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
                                        <td class="<?php echo $clase_encontrada ? 'horario-clase' : ''; ?>">
                                            <?php if ($clase_encontrada): ?>
                                                <div class="materia-cell"><?php echo htmlspecialchars($clase_encontrada['materia_nombre']); ?></div>
                                                <div class="docente-cell <?php echo (empty($clase_encontrada['docente_id']) || $clase_encontrada['docente_id'] == 0) ? 'sin-docente-cell' : ''; ?>">
                                                    <?php if (empty($clase_encontrada['docente_id']) || $clase_encontrada['docente_id'] == 0): ?>
                                                        ⚠️ Sin docente
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars($clase_encontrada['docente_nombre']); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <span class="aula-cell"><?php echo htmlspecialchars($clase_encontrada['aula'] ?? 'Por asignar'); ?></span>
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
            </div>
        </div>
    </div>
</div>

<!-- ===== MODAL AGREGAR HORARIO ===== -->
<div class="modal-overlay" id="modalHorario">
    <div class="modal-content">
        <h3><i class="fas fa-clock" style="color: var(--azul); margin-right: 10px;"></i> Agregar Horario</h3>
        <p class="modal-sub">Selecciona la materia y asigna el docente para este grupo</p>
        <form method="POST" id="formHorario">
            <div class="input-group">
                <label>Materia <span style="color: var(--rojo);">*</span></label>
                <select name="materia_id" id="materiaSelect" required onchange="cargarDocenteAsignado()">
                    <option value="">Selecciona una materia...</option>
                    <?php foreach ($materias as $m): ?>
                        <option value="<?php echo $m['id']; ?>" data-docente="<?php echo htmlspecialchars($m['docente_asignado'] ?? ''); ?>">
                            [Sem <?php echo $m['semestre']; ?>] <?php echo htmlspecialchars($m['nombre']); ?> (<?php echo htmlspecialchars($m['clave']); ?>)
                            <?php if (!empty($m['docente_asignado'])): ?>
                                - 👨‍🏫 <?php echo htmlspecialchars($m['docente_asignado']); ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="input-group">
                <label>Docente</label>
                <select name="docente_id" id="docenteSelect">
                    <option value="0">Sin asignar</option>
                    <?php foreach ($docentes as $d): ?>
                        <option value="<?php echo $d['Id']; ?>">
                            <?php echo htmlspecialchars($d['Nombre']); ?>
                            <?php if (!empty($d['especialidad'])): ?>
                                (<?php echo htmlspecialchars($d['especialidad']); ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text" id="docenteInfo">
                    <i class="fas fa-info-circle"></i> 
                    Puedes dejar "Sin asignar" y asignarlo después
                </div>
            </div>

            <div class="row-2">
                <div class="input-group">
                    <label>Día <span style="color: var(--rojo);">*</span></label>
                    <select name="dia_semana" required>
                        <?php foreach ($dias_semana as $dia): ?>
                            <option value="<?php echo $dia; ?>"><?php echo $dia; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label>Aula</label>
                    <input type="text" name="aula" placeholder="Ej: A-101">
                </div>
            </div>

            <div class="row-2">
                <div class="input-group">
                    <label>Hora Inicio <span style="color: var(--rojo);">*</span></label>
                    <input type="time" name="hora_inicio" id="horaInicio" required>
                </div>

                <div class="input-group">
                    <label>Hora Fin <span style="color: var(--rojo);">*</span></label>
                    <input type="time" name="hora_fin" id="horaFin" required>
                </div>
            </div>

            <div class="input-group">
                <label>Período <span style="color: var(--rojo);">*</span></label>
                <select name="periodo_id" required>
                    <?php foreach ($periodos as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['nombre']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-acciones">
                <button type="button" class="btn-cancelar-modal" onclick="cerrarModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" name="guardar_horario" class="btn-submit-modal">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ===== ✅ NUEVO MODAL: CAMBIAR DOCENTE ===== -->
<div class="modal-overlay" id="modalCambiarDocente">
    <div class="modal-content">
        <h3><i class="fas fa-exchange-alt" style="color: var(--azul); margin-right: 10px;"></i> Cambiar Docente</h3>
        <p class="modal-sub">Selecciona el nuevo docente para esta clase</p>
        
        <div class="info-horario-modal">
            <strong>Materia:</strong> <span id="materiaCambiar"></span><br>
            <strong>Docente actual:</strong> <span id="docenteActualCambiar"></span>
        </div>
        
        <form method="POST" id="formCambiarDocente">
            <input type="hidden" name="horario_id" id="horarioIdCambiar">
            
            <div class="input-group">
                <label>Nuevo Docente</label>
                <select name="nuevo_docente_id" required>
                    <option value="0">🔓 Sin asignar (desasignar docente actual)</option>
                    <?php foreach ($docentes as $d): ?>
                        <option value="<?php echo $d['Id']; ?>">
                            <?php echo htmlspecialchars($d['Nombre']); ?>
                            <?php if (!empty($d['especialidad'])): ?>
                                (<?php echo htmlspecialchars($d['especialidad']); ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="help-text">
                    <i class="fas fa-info-circle"></i> 
                    El docente actual será reemplazado por el que elijas. Si eliges "Sin asignar", 
                    la clase quedará sin docente y desaparecerá de su panel.
                </div>
            </div>
            
            <div class="modal-acciones">
                <button type="button" class="btn-cancelar-modal" onclick="cerrarModalCambiar()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" name="cambiar_docente" class="btn-submit-modal">
                    <i class="fas fa-check"></i> Confirmar Cambio
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // ============================================================
    // MODAL AGREGAR HORARIO
    // ============================================================
    function abrirModal() {
        document.getElementById('modalHorario').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModal() {
        document.getElementById('modalHorario').classList.remove('active');
        document.body.style.overflow = '';
    }

    document.getElementById('modalHorario').addEventListener('click', function(e) {
        if (e.target === this) cerrarModal();
    });

    // ============================================================
    // ✅ MODAL CAMBIAR DOCENTE
    // ============================================================
    function abrirModalCambiarDocente(horarioId, materiaNombre, docenteActual) {
        document.getElementById('horarioIdCambiar').value = horarioId;
        document.getElementById('materiaCambiar').textContent = materiaNombre;
        document.getElementById('docenteActualCambiar').textContent = docenteActual || 'Sin asignar';
        document.getElementById('modalCambiarDocente').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function cerrarModalCambiar() {
        document.getElementById('modalCambiarDocente').classList.remove('active');
        document.body.style.overflow = '';
    }

    document.getElementById('modalCambiarDocente').addEventListener('click', function(e) {
        if (e.target === this) cerrarModalCambiar();
    });

    // Cerrar con Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            cerrarModal();
            cerrarModalCambiar();
        }
    });

    // ============================================================
    // CARGAR DOCENTE ASIGNADO AUTOMÁTICAMENTE
    // ============================================================
    function cargarDocenteAsignado() {
        const materiaSelect = document.getElementById('materiaSelect');
        const docenteSelect = document.getElementById('docenteSelect');
        const selectedOption = materiaSelect.options[materiaSelect.selectedIndex];
        const docenteNombre = selectedOption.getAttribute('data-docente');
        
        for (let i = 0; i < docenteSelect.options.length; i++) {
            docenteSelect.options[i].selected = false;
        }
        
        if (docenteNombre && docenteNombre !== '') {
            for (let i = 0; i < docenteSelect.options.length; i++) {
                const optionText = docenteSelect.options[i].text;
                if (optionText.includes(docenteNombre)) {
                    docenteSelect.options[i].selected = true;
                    break;
                }
            }
            document.getElementById('docenteInfo').innerHTML = 
                '<i class="fas fa-check-circle" style="color: var(--verde-exito);"></i> ' +
                '✅ Docente sugerido: <strong>' + docenteNombre + '</strong>';
        } else {
            document.getElementById('docenteInfo').innerHTML = 
                '<i class="fas fa-info-circle"></i> ' +
                'Selecciona un docente o deja "Sin asignar"';
        }
    }

    // ============================================================
    // VALIDAR HORAS
    // ============================================================
    document.getElementById('formHorario').addEventListener('submit', function(e) {
        const horaInicio = document.getElementById('horaInicio').value;
        const horaFin = document.getElementById('horaFin').value;
        
        if (horaInicio >= horaFin) {
            e.preventDefault();
            showToast('⚠️ La hora de inicio debe ser menor que la hora de fin.', 'warning');
            return false;
        }
    });

    // ============================================================
    // TOAST NOTIFICATIONS
    // ============================================================
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMessage = document.getElementById('toastMessage');
        
        toast.className = 'toast ' + type;
        toastMessage.textContent = message;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 4000);
    }

    <?php if ($mensaje): ?>
        showToast('<?php echo addslashes($mensaje); ?>', '<?php echo $mensaje_tipo; ?>');
    <?php endif; ?>
</script>

</body>
</html>