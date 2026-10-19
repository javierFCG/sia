<?php
// admin_calificaciones.php
session_start();
require_once 'config.php';
require_once 'funciones.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$mensaje = "";
$mensaje_tipo = "";

if (isset($_GET['accion']) && $_GET['accion'] == 'eliminar' && isset($_GET['detalle_id'])) {
    $detalle_id = (int)$_GET['detalle_id'];
    $stmt = $conn->prepare("UPDATE inscripciones_detalle SET calificacion = NULL WHERE id = ?");
    $stmt->bind_param("i", $detalle_id);
    if ($stmt->execute()) {
        $mensaje = "✅ Calificación eliminada correctamente";
        $mensaje_tipo = "success";
    } else {
        $mensaje = "❌ Error al eliminar la calificación";
        $mensaje_tipo = "error";
    }
    $stmt->close();
}

$periodos_sql = "SELECT id, nombre FROM periodos WHERE calificaciones_abiertas = 1 OR estado = 'Activo' ORDER BY id DESC";
$periodos_result = $conn->query($periodos_sql);
$periodos = $periodos_result->fetch_all(MYSQLI_ASSOC);

$carreras_result = $conn->query("SELECT id, nombre FROM carreras ORDER BY nombre ASC");
$carreras = $carreras_result->fetch_all(MYSQLI_ASSOC);

$filtro_periodo = isset($_GET['periodo_id']) ? (int)$_GET['periodo_id'] : 0;
$filtro_carrera = isset($_GET['carrera_id']) ? (int)$_GET['carrera_id'] : 0;
$filtro_busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

$sql_estudiantes = "SELECT DISTINCT 
    u.Id as usuario_id,
    u.Nombre as estudiante,
    u.Correo,
    i.id as inscripcion_id,
    i.carrera_id,
    i.periodo_id,
    (SELECT COUNT(*) FROM inscripciones_detalle WHERE inscripcion_id = i.id) as total_materias,
    (SELECT COUNT(*) FROM inscripciones_detalle WHERE inscripcion_id = i.id AND calificacion IS NOT NULL) as calificadas
FROM inscripciones i
JOIN usuarios u ON i.usuario_id = u.Id
WHERE 1=1";

if ($filtro_periodo > 0) {
    $sql_estudiantes .= " AND i.periodo_id = $filtro_periodo";
}
if ($filtro_carrera > 0) {
    $sql_estudiantes .= " AND i.carrera_id = $filtro_carrera";
}
if (!empty($filtro_busqueda)) {
    $bus = $conn->real_escape_string($filtro_busqueda);
    $sql_estudiantes .= " AND (u.Nombre LIKE '%$bus%' OR u.Correo LIKE '%$bus%')";
}
$sql_estudiantes .= " ORDER BY u.Nombre ASC";

$estudiantes_result = $conn->query($sql_estudiantes);
$estudiantes = $estudiantes_result->fetch_all(MYSQLI_ASSOC);

// Si hay edición de calificaciones
$inscripcion_editar = isset($_GET['inscripcion_id']) && isset($_GET['accion']) && $_GET['accion'] == 'editar';
$info_estudiante = null;
$materias = [];

if ($inscripcion_editar) {
    $inscripcion_id = (int)$_GET['inscripcion_id'];
    $sql_info = "SELECT u.Nombre as estudiante, u.Correo, c.nombre as carrera, p.nombre as periodo 
                 FROM inscripciones i 
                 JOIN usuarios u ON i.usuario_id = u.Id 
                 JOIN carreras c ON i.carrera_id = c.id 
                 JOIN periodos p ON i.periodo_id = p.id 
                 WHERE i.id = ?";
    $stmt = $conn->prepare($sql_info);
    $stmt->bind_param("i", $inscripcion_id);
    $stmt->execute();
    $info_estudiante = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    $sql_materias = "SELECT idet.id as detalle_id, m.nombre, m.clave, m.creditos, idet.calificacion
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
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Calificaciones - ITSZ</title>
    <link rel="icon" href="assets/img/favicon.ico">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --azul: #1B396A; --guinda: #620F31; --rojo: #860A0F; --dorado: #D99A32;
            --blanco: #FFFFFF; --gris: #F1F1F1; --gris-oscuro: #D9D9D9;
            --shadow: 0 4px 15px rgba(0,0,0,0.1); --shadow-hover: 0 8px 25px rgba(0,0,0,0.15);
            --radius: 12px; --radius-lg: 20px;
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

        .container { max-width: 1400px; margin: 20px auto; padding: 0 20px; }

        .page-header {
            background: white; padding: 20px 25px; border-radius: var(--radius); box-shadow: var(--shadow);
            margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
        }
        .page-header h1 { color: var(--azul); font-size: 24px; }
        .page-header h1 i { color: var(--guinda); }
        .page-header p { color: var(--gris-oscuro); font-size: 14px; margin-top: 5px; }
        .btn-volver { background: var(--gris-oscuro); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-volver:hover { background: #b0b0b0; transform: translateY(-2px); }
        .btn-gestionar { background: var(--azul); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-gestionar:hover { background: var(--guinda); transform: translateY(-2px); }
        .btn-guardar { background: var(--dorado); color: var(--blanco); padding: 12px 30px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; transition: all 0.3s; font-size: 16px; }
        .btn-guardar:hover { background: #c78a2a; transform: translateY(-2px); box-shadow: var(--shadow-hover); }

        .filter-bar {
            background: white; padding: 20px; border-radius: var(--radius); box-shadow: var(--shadow);
            margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;
        }
        .filter-group { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 150px; }
        .filter-group label { font-size: 13px; font-weight: 600; color: var(--azul); }
        .filter-group select, .filter-group input {
            padding: 10px 12px; border: 2px solid var(--gris-oscuro); border-radius: var(--radius); font-size: 14px; background: white; transition: all 0.3s;
        }
        .filter-group select:focus, .filter-group input:focus { border-color: var(--azul); outline: none; }
        .btn-filter { background: var(--azul); color: var(--blanco); padding: 10px 25px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; height: 42px; transition: all 0.3s; }
        .btn-filter:hover { background: var(--guinda); transform: translateY(-2px); }
        .btn-reset { background: var(--rojo); color: var(--blanco); padding: 10px 25px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; height: 42px; transition: all 0.3s; }
        .btn-reset:hover { background: #6b080c; transform: translateY(-2px); }

        .mensaje { padding: 15px; border-radius: var(--radius); margin-bottom: 20px; font-weight: 600; }
        .mensaje.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        table { width: 100%; border-collapse: collapse; background: white; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--gris); font-size: 14px; vertical-align: middle; }
        th { background: var(--azul); color: white; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tr:hover { background: #f8f5f0; }
        .badge-pendiente { background: #fff3cd; color: #856404; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-completado { background: #d4edda; color: #155724; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .btn-ver { padding: 6px 12px; background: var(--azul); color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 12px; display: inline-flex; align-items: center; gap: 3px; transition: all 0.3s; }
        .btn-ver:hover { background: var(--guinda); transform: translateY(-2px); }
        .btn-eliminar-calif { padding: 6px 12px; background: var(--rojo); color: white; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 12px; display: inline-flex; align-items: center; gap: 3px; transition: all 0.3s; }
        .btn-eliminar-calif:hover { background: #6b080c; transform: translateY(-2px); }

        .input-calif { width: 80px; padding: 6px; border: 2px solid var(--gris-oscuro); border-radius: 4px; text-align: center; font-size: 14px; background: white; }
        .input-calif:focus { border-color: var(--azul); outline: none; }

        .edicion-box { margin-top: 40px; border-top: 2px solid var(--gris-oscuro); padding-top: 30px; }
        .edicion-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
        .edicion-header h2 { color: var(--azul); font-size: 22px; }
        .edicion-header h2 i { color: var(--guinda); }
        .edicion-header p { color: var(--gris-oscuro); font-size: 14px; }

        .empty-state { text-align: center; padding: 60px; background: white; border-radius: var(--radius); box-shadow: var(--shadow); }
        .empty-state i { font-size: 50px; color: var(--gris-oscuro); margin-bottom: 15px; display: block; }
        .empty-state p { color: var(--gris-oscuro); font-size: 16px; }

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
            .filter-bar { flex-direction: column; align-items: stretch; }
            table { font-size: 12px; }
            th, td { padding: 8px 10px; }
            .edicion-header { flex-direction: column; text-align: center; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            .input-calif { width: 60px; font-size: 12px; }
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
            <a href="admin_panel.php"><i class='bx bxs-dashboard'></i> Panel</a>
            <a href="mensajes.php"><i class='bx bxs-envelope'></i> Mensajes</a>
            <a href="logout.php" class="btn-logout"><i class='bx bx-log-out'></i> Salir</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <div><h1><i class='bx bxs-edit-alt'></i> Gestión de Calificaciones</h1><p>Administra las calificaciones de los estudiantes por período</p></div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="admin_gestionar_periodos.php" class="btn-gestionar"><i class='bx bx-calendar'></i> Gestionar Períodos</a>
            <a href="admin_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
        </div>
    </div>

    <?php if ($mensaje): ?>
        <div class="mensaje <?php echo $mensaje_tipo; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form method="GET" class="filter-bar">
        <div class="filter-group">
            <label>Período:</label>
            <select name="periodo_id">
                <option value="0">Todos</option>
                <?php foreach ($periodos as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo ($filtro_periodo == $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Carrera:</label>
            <select name="carrera_id">
                <option value="0">Todas</option>
                <?php foreach ($carreras as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($filtro_carrera == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Buscar Estudiante:</label>
            <input type="text" name="busqueda" placeholder="Nombre o correo..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
        </div>
        <button type="submit" class="btn-filter"><i class='bx bx-search'></i> Filtrar</button>
        <?php if ($filtro_periodo || $filtro_carrera || $filtro_busqueda): ?>
            <a href="admin_calificaciones.php" class="btn-reset"><i class='bx bx-x'></i> Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (count($estudiantes) > 0): ?>
        <div style="overflow-x: auto;">
            <table>
                <thead><tr><th>Estudiante</th><th>Correo</th><th>Período</th><th>Carrera</th><th style="text-align:center;">Materias</th><th style="text-align:center;">Calificadas</th><th style="text-align:center;">Acción</th></tr></thead>
                <tbody>
                    <?php foreach ($estudiantes as $est): 
                        $periodo_nombre = obtenerNombrePeriodo($est['periodo_id']);
                        $carrera_nombre = obtenerNombreCarrera($est['carrera_id']);
                        $progreso = $est['total_materias'] > 0 ? round(($est['calificadas'] / $est['total_materias']) * 100) : 0;
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($est['estudiante']); ?></strong></td>
                            <td><?php echo htmlspecialchars($est['Correo']); ?></td>
                            <td><?php echo htmlspecialchars($periodo_nombre); ?></td>
                            <td><?php echo htmlspecialchars($carrera_nombre); ?></td>
                            <td style="text-align:center;"><?php echo $est['total_materias']; ?></td>
                            <td style="text-align:center;">
                                <span class="<?php echo $progreso == 100 ? 'badge-completado' : 'badge-pendiente'; ?>">
                                    <?php echo $est['calificadas']; ?>/<?php echo $est['total_materias']; ?>
                                </span>
                            </td>
                            <td style="text-align:center;">
                                <a href="admin_calificaciones.php?inscripcion_id=<?php echo $est['inscripcion_id']; ?>&accion=editar" class="btn-ver"><i class='bx bxs-edit'></i> Editar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state"><i class='bx bx-user-x'></i><p>No hay estudiantes inscritos en los períodos con calificaciones abiertas.</p></div>
    <?php endif; ?>

    <?php if ($inscripcion_editar && $info_estudiante): ?>
        <div class="edicion-box">
            <div class="edicion-header">
                <div>
                    <h2><i class='bx bxs-user'></i> <?php echo htmlspecialchars($info_estudiante['estudiante']); ?></h2>
                    <p><?php echo htmlspecialchars($info_estudiante['carrera']); ?> | <?php echo htmlspecialchars($info_estudiante['periodo']); ?></p>
                </div>
                <a href="admin_calificaciones.php?periodo_id=<?php echo $filtro_periodo; ?>&carrera_id=<?php echo $filtro_carrera; ?>&busqueda=<?php echo urlencode($filtro_busqueda); ?>" class="btn-volver" style="margin:0; padding:10px 20px; font-size:14px;"><i class='bx bx-arrow-back'></i> Volver</a>
            </div>

            <form action="admin_guardar_calificacion.php" method="POST">
                <input type="hidden" name="inscripcion_id" value="<?php echo $inscripcion_id; ?>">
                <div style="overflow-x: auto;">
                    <table>
                        <thead><tr><th>Clave</th><th>Materia</th><th style="text-align:center;">Créditos</th><th style="text-align:center;">Calificación</th><th style="text-align:center;">Estado</th><th style="text-align:center;">Acción</th></tr></thead>
                        <tbody>
                            <?php foreach ($materias as $m): 
                                $estado = obtenerEstadoCalificacion($m['calificacion']);
                                $letra = obtenerCalificacionLetra($m['calificacion']);
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($m['clave']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($m['nombre']); ?></strong></td>
                                    <td style="text-align:center;"><?php echo $m['creditos']; ?></td>
                                    <td style="text-align:center;">
                                        <input type="number" name="calificacion[<?php echo $m['detalle_id']; ?>]" value="<?php echo $m['calificacion'] !== null ? $m['calificacion'] : ''; ?>" min="0" max="100" step="0.5" class="input-calif">
                                    </td>
                                    <td style="text-align:center;">
                                        <?php if ($m['calificacion'] !== null): ?>
                                            <span style="padding:3px 10px; border-radius:12px; font-size:12px; font-weight:600; <?php echo ($m['calificacion'] >= 70) ? 'background:#d4edda; color:#155724;' : 'background:#f8d7da; color:#721c24;'; ?>"><?php echo $estado; ?> (<?php echo $letra; ?>)</span>
                                        <?php else: ?>
                                            <span class="badge-pendiente">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <a href="admin_eliminar_calificacion.php?detalle_id=<?php echo $m['detalle_id']; ?>&inscripcion_id=<?php echo $inscripcion_id; ?>" class="btn-eliminar-calif" onclick="return confirm('¿Eliminar esta calificación?')"><i class='bx bx-trash'></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-guardar"><i class='bx bxs-save'></i> Guardar Todas las Calificaciones</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<footer class="footer-itsz">
    <div class="container">
        <p><strong>Tecnológico Superior de Zongolica</strong><br>Carretera a la Compañía S/N, Zongolica, Veracruz</p>
        <div class="social-links"><a href="#"><i class='bx bxl-facebook'></i></a><a href="#"><i class='bx bxl-twitter'></i></a><a href="#"><i class='bx bxl-instagram'></i></a><a href="#"><i class='bx bxl-youtube'></i></a></div>
        <hr><p class="copy">&copy; <?php echo date('Y'); ?> Tecnológico Superior de Zongolica - Todos los derechos reservados</p>
    </div>
</footer>
</body>
</html>