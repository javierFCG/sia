<?php
// admin_reporte_inscritos.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$filtro_periodo = isset($_GET['periodo_id']) ? $_GET['periodo_id'] : '';
$filtro_carrera = isset($_GET['carrera_id']) ? $_GET['carrera_id'] : '';
$filtro_busqueda = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';

$periodos_list = $conn->query("SELECT id, nombre FROM periodos ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
$carreras_list = $conn->query("SELECT id, nombre FROM carreras ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);

$sql = "SELECT i.id as inscripcion_id, u.Nombre as estudiante, u.Correo, c.nombre as carrera, p.nombre as periodo, i.fecha_inscripcion 
        FROM inscripciones i 
        JOIN usuarios u ON i.usuario_id = u.Id 
        JOIN carreras c ON i.carrera_id = c.id 
        JOIN periodos p ON i.periodo_id = p.id 
        WHERE 1=1";

if ($filtro_periodo) $sql .= " AND i.periodo_id = " . (int)$filtro_periodo;
if ($filtro_carrera) $sql .= " AND i.carrera_id = " . (int)$filtro_carrera;
if ($filtro_busqueda) {
    $bus = $conn->real_escape_string($filtro_busqueda);
    $sql .= " AND (u.Nombre LIKE '%$bus%' OR u.Correo LIKE '%$bus%')";
}
$sql .= " ORDER BY i.fecha_inscripcion DESC";

$result = $conn->query($sql);
$inscripciones = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Inscritos - ITSZ</title>
    <link rel="icon" href="assets/img/favicon.ico">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
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

        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }

        .page-header {
            background: white; padding: 20px 25px; border-radius: var(--radius); box-shadow: var(--shadow);
            margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
        }
        .page-header h1 { color: var(--azul); font-size: 24px; }
        .page-header h1 i { color: var(--guinda); }
        .page-header p { color: var(--gris-oscuro); font-size: 14px; margin-top: 5px; }
        .btn-volver { background: var(--gris-oscuro); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-volver:hover { background: #b0b0b0; transform: translateY(-2px); }

        .filter-bar {
            background: white; padding: 20px; border-radius: var(--radius); box-shadow: var(--shadow);
            margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 15px; align-items: flex-end;
            border-left: 5px solid var(--azul);
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

        table { width: 100%; border-collapse: collapse; background: white; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--gris); font-size: 14px; vertical-align: middle; }
        th { background: var(--azul); color: white; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tr:hover { background: #f8f5f0; }

        .badge-carrera { background: #eaf2f8; color: var(--azul); padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        
        .btn-action { padding: 6px 12px; border: none; border-radius: var(--radius); font-weight: 600; font-size: 12px; color: white; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: all 0.3s; }
        .btn-action:hover { transform: translateY(-2px); }
        .btn-detail { background: var(--azul); }
        .btn-detail:hover { background: var(--guinda); }
        .btn-delete { background: var(--rojo); }
        .btn-delete:hover { background: #6b080c; }

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
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            .btn-action { display: block; text-align: center; margin: 3px 0; }
            table { font-size: 11px; }
            th, td { padding: 6px 8px; }
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
        <div><h1><i class='bx bxs-report'></i> Reporte de Inscritos</h1><p>Visualiza y gestiona las inscripciones de los estudiantes</p></div>
        <a href="admin_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
    </div>

    <form method="GET" class="filter-bar">
        <div class="filter-group">
            <label>Período:</label>
            <select name="periodo_id">
                <option value="">Todos</option>
                <?php foreach ($periodos_list as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo ($filtro_periodo == $p['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Carrera:</label>
            <select name="carrera_id">
                <option value="">Todas</option>
                <?php foreach ($carreras_list as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($filtro_carrera == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nombre']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-group">
            <label>Buscar:</label>
            <input type="text" name="busqueda" placeholder="Nombre o correo..." value="<?php echo htmlspecialchars($filtro_busqueda); ?>">
        </div>
        <button type="submit" class="btn-filter"><i class='bx bx-search-alt-2'></i> Filtrar</button>
        <?php if ($filtro_periodo || $filtro_carrera || $filtro_busqueda): ?>
            <a href="admin_reporte_inscritos.php" class="btn-reset"><i class='bx bx-x'></i> Limpiar</a>
        <?php endif; ?>
    </form>

    <?php if (count($inscripciones) > 0): ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Correo</th>
                        <th>Carrera</th>
                        <th>Período</th>
                        <th>Fecha</th>
                        <th style="text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inscripciones as $ins): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($ins['estudiante']); ?></strong></td>
                        <td><?php echo htmlspecialchars($ins['Correo']); ?></td>
                        <td><span class="badge-carrera"><i class='bx bxs-graduation'></i> <?php echo htmlspecialchars($ins['carrera']); ?></span></td>
                        <td><i class='bx bxs-calendar'></i> <?php echo htmlspecialchars($ins['periodo']); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($ins['fecha_inscripcion'])); ?></td>
                        <td style="text-align:center; white-space: nowrap;">
                            <a href="admin_reporte_detalle_inscripcion.php?inscripcion_id=<?php echo $ins['inscripcion_id']; ?>" class="btn-action btn-detail">
                                <i class='bx bxs-edit'></i> Editar
                            </a>
                            <a href="admin_eliminar_detalle_inscripcion.php?inscripcion_id=<?php echo $ins['inscripcion_id']; ?>&tipo=inscripcion_completa" 
                               class="btn-action btn-delete" onclick="return confirm('¿Eliminar esta inscripción?')">
                                <i class='bx bxs-trash'></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div style="margin-top: 15px; text-align: right; color: var(--gris-oscuro); font-size: 13px;">
                <i class='bx bxs-user'></i> Total: <strong><?php echo count($inscripciones); ?></strong> inscripciones
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bx-file-blank'></i>
            <p>No hay inscripciones registradas</p>
            <p style="font-size: 14px;">Ajusta los filtros para encontrar más resultados.</p>
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
</body>
</html>