<?php
// logs_sistema.php - Ver logs del sistema
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }

// Filtrar logs
$filtro = isset($_GET['filtro']) ? trim($_GET['filtro']) : '';
$sql = "SELECT * FROM logs_sistema";
if (!empty($filtro)) {
    $filtro = $conn->real_escape_string($filtro);
    $sql .= " WHERE usuario_nombre LIKE '%$filtro%' OR accion LIKE '%$filtro%' OR detalle LIKE '%$filtro%' OR ip LIKE '%$filtro%'";
}
$sql .= " ORDER BY fecha DESC LIMIT 500";
$logs = $conn->query($sql);

$logs_data = [];
if ($logs && $logs->num_rows > 0) {
    $logs_data = $logs->fetch_all(MYSQLI_ASSOC);
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs del Sistema - ITSZ</title>
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
            margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 15px; align-items: center;
            border-left: 5px solid var(--azul);
        }
        .filter-bar input[type="text"] {
            flex: 1; padding: 10px 15px; border: 2px solid var(--gris-oscuro); border-radius: var(--radius);
            font-size: 14px; min-width: 200px; background: var(--gris); transition: all 0.3s;
        }
        .filter-bar input[type="text"]:focus { border-color: var(--azul); outline: none; background: var(--blanco); box-shadow: 0 0 0 3px rgba(27,57,106,0.1); }
        .btn-filter { background: var(--azul); color: var(--blanco); padding: 10px 25px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-filter:hover { background: var(--guinda); transform: translateY(-2px); }
        .btn-clear { background: var(--gris-oscuro); color: var(--blanco); padding: 10px 25px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.3s; }
        .btn-clear:hover { background: #b0b0b0; transform: translateY(-2px); }

        table {
            width: 100%; border-collapse: collapse; background: white; border-radius: var(--radius);
            overflow: hidden; box-shadow: var(--shadow);
        }
        th, td {
            padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--gris);
            font-size: 14px; vertical-align: middle;
        }
        th {
            background: var(--azul); color: white; font-weight: 600;
            text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;
        }
        tr:hover { background: #f8f5f0; }

        .badge-accion {
            padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block;
        }
        .badge-accion.login { background: #d4edda; color: #155724; }
        .badge-accion.logout { background: #f8d7da; color: #721c24; }
        .badge-accion.inscripcion { background: #cce5ff; color: #004085; }
        .badge-accion.calificacion { background: #fff3cd; color: #856404; }
        .badge-accion.usuario { background: #d6d8db; color: #383d41; }
        .badge-accion.sistema { background: #e8d5f5; color: #6c3483; }
        .badge-accion.beca { background: #fdebd0; color: #a04000; }
        .badge-accion.evento { background: #d5f5e3; color: #1a7a42; }

        .ip-badge {
            background: var(--gris); padding: 2px 10px; border-radius: 12px;
            font-size: 12px; font-family: monospace;
        }

        .empty-state {
            text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius);
            box-shadow: var(--shadow); border-left: 5px solid var(--gris-oscuro);
        }
        .empty-state i { font-size: 70px; color: var(--gris-oscuro); margin-bottom: 15px; display: block; }
        .empty-state h3 { color: var(--azul); font-size: 22px; margin-bottom: 10px; }
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

        .total-registros {
            margin-top: 15px; text-align: right; color: var(--gris-oscuro); font-size: 13px;
        }

        @media (max-width: 768px) {
            .header-itsz .container { flex-direction: column; align-items: center; text-align: center; }
            .header-itsz .logo-container { flex-direction: column; }
            .header-itsz nav { justify-content: center; }
            .header-itsz nav a { font-size: 11px; padding: 4px 8px; }
            .page-header { flex-direction: column; text-align: center; }
            .filter-bar { flex-direction: column; align-items: stretch; }
            .filter-bar input[type="text"] { min-width: 100%; }
            table { font-size: 12px; }
            th, td { padding: 8px 10px; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            table { font-size: 11px; }
            th, td { padding: 6px 8px; }
            .badge-accion { font-size: 10px; padding: 2px 8px; }
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
        <div><h1><i class='bx bxs-history'></i> Logs del Sistema</h1><p>Registro de todas las acciones realizadas por los usuarios</p></div>
        <a href="admin_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
    </div>

    <!-- Barra de búsqueda -->
    <form method="GET" class="filter-bar">
        <input type="text" name="filtro" placeholder="Buscar por usuario, acción, detalle o IP..." value="<?php echo htmlspecialchars($filtro); ?>">
        <button type="submit" class="btn-filter"><i class='bx bx-search'></i> Buscar</button>
        <?php if (!empty($filtro)): ?>
            <a href="logs_sistema.php" class="btn-clear"><i class='bx bx-x'></i> Limpiar</a>
        <?php endif; ?>
    </form>

    <!-- Tabla de logs -->
    <?php if (count($logs_data) > 0): ?>
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Acción</th>
                        <th>Detalle</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs_data as $log): 
                        $accion_clase = strtolower(explode(' ', $log['accion'])[0]);
                        $accion_clase = in_array($accion_clase, ['login','logout','inscripcion','calificacion','usuario','sistema','beca','evento']) ? $accion_clase : 'sistema';
                    ?>
                        <tr>
                            <td style="white-space: nowrap;"><?php echo date('d/m/Y H:i:s', strtotime($log['fecha'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($log['usuario_nombre'] ?? 'Sistema'); ?></strong></td>
                            <td>
                                <span class="badge-accion <?php echo $accion_clase; ?>">
                                    <?php echo htmlspecialchars($log['accion']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['detalle']); ?></td>
                            <td><span class="ip-badge"><?php echo htmlspecialchars($log['ip']); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="total-registros">
            <i class='bx bx-data'></i> Mostrando <strong><?php echo count($logs_data); ?></strong> registros
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bx-data'></i>
            <h3>No hay logs registrados</h3>
            <p>Los logs del sistema se generan automáticamente cuando los usuarios realizan acciones en la plataforma.</p>
            <?php if (!empty($filtro)): ?>
                <p style="margin-top: 10px;">Intenta con otro término de búsqueda o <a href="logs_sistema.php" style="color: var(--azul); font-weight: 600;">limpia el filtro</a>.</p>
            <?php endif; ?>
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