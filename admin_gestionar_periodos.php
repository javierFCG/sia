<?php
// admin_gestionar_periodos.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$sql = "SELECT id, nombre, fecha_inicio, fecha_fin, estado FROM periodos ORDER BY fecha_inicio DESC";
$result = $conn->query($sql);
$periodos = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Períodos - ITSZ</title>
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
        .btn-crear { background: var(--dorado); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-crear:hover { background: #c78a2a; transform: translateY(-2px); }

        table { width: 100%; border-collapse: collapse; background: white; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--gris); font-size: 14px; }
        th { background: var(--azul); color: white; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        tr:hover { background: #f8f5f0; }
        .estado-activo { background: #d4edda; color: #155724; font-weight: 600; padding: 4px 10px; border-radius: 20px; display: inline-block; font-size: 12px; }
        .estado-inactivo { background: #fff3cd; color: #856404; font-weight: 600; padding: 4px 10px; border-radius: 20px; display: inline-block; font-size: 12px; }
        .estado-cerrado { background: #f8d7da; color: #721c24; font-weight: 600; padding: 4px 10px; border-radius: 20px; display: inline-block; font-size: 12px; }
        .action-btns { display: flex; gap: 8px; flex-wrap: wrap; }
        .btn-edit { background: var(--dorado); color: var(--blanco); padding: 6px 14px; border: none; border-radius: var(--radius); font-weight: 600; text-decoration: none; font-size: 12px; display: inline-block; transition: all 0.3s; }
        .btn-edit:hover { background: #c78a2a; transform: translateY(-2px); }
        .btn-delete { background: var(--rojo); color: var(--blanco); padding: 6px 14px; border: none; border-radius: var(--radius); font-weight: 600; text-decoration: none; font-size: 12px; display: inline-block; transition: all 0.3s; }
        .btn-delete:hover { background: #6b080c; transform: translateY(-2px); }

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
            table { font-size: 12px; }
            th, td { padding: 8px 10px; }
            .action-btns { flex-direction: column; }
            .action-btns a { text-align: center; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
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
        <div><h1><i class='bx bxs-calendar'></i> Gestión de Períodos</h1><p>Define los períodos de inscripción académica</p></div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="admin_crear_periodo.php" class="btn-crear"><i class='bx bxs-plus-circle'></i> Nuevo Período</a>
            <a href="admin_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
        </div>
    </div>

    <?php if (count($periodos) > 0): ?>
        <table>
            <thead><tr><th>Nombre</th><th>Fecha Inicio</th><th>Fecha Fin</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($periodos as $periodo): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($periodo['nombre']); ?></strong></td>
                    <td><?php echo date('d/m/Y', strtotime($periodo['fecha_inicio'])); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($periodo['fecha_fin'])); ?></td>
                    <td><span class="estado-<?php echo strtolower($periodo['estado']); ?>"><?php echo $periodo['estado']; ?></span></td>
                    <td>
                        <div class="action-btns">
                            <a href="admin_editar_periodo.php?id=<?php echo $periodo['id']; ?>" class="btn-edit"><i class='bx bxs-edit'></i> Editar</a>
                            <a href="admin_eliminar_periodo.php?id=<?php echo $periodo['id']; ?>" class="btn-delete" onclick="return confirm('¿Eliminar este período?')"><i class='bx bxs-trash'></i> Eliminar</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state"><i class='bx bx-calendar-x'></i><p>No hay períodos configurados</p></div>
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