<?php
// estudiante_inscripcion.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] === 'Administrador') {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$sql = "SELECT id, nombre, fecha_inicio, fecha_fin FROM periodos WHERE estado = 'Activo' ORDER BY fecha_inicio DESC";
$result = $conn->query($sql);
$periodos_activos = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripción - ITSZ</title>
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

        .container { max-width: 800px; margin: 20px auto; padding: 0 20px; }

        .page-header {
            background: white; padding: 20px 25px; border-radius: var(--radius); box-shadow: var(--shadow);
            margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
        }
        .page-header h1 { color: var(--azul); font-size: 24px; }
        .page-header h1 i { color: var(--guinda); }
        .page-header p { color: var(--gris-oscuro); font-size: 14px; margin-top: 5px; }
        .btn-volver { background: var(--gris-oscuro); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-volver:hover { background: #b0b0b0; transform: translateY(-2px); }

        .periodo-card {
            background: white; border-radius: var(--radius); padding: 25px; box-shadow: var(--shadow);
            margin-bottom: 20px; border-left: 5px solid var(--azul); transition: all 0.3s;
        }
        .periodo-card:hover { box-shadow: var(--shadow-hover); transform: translateX(5px); }
        .periodo-card h2 { color: var(--azul); font-size: 20px; margin-top: 0; margin-bottom: 5px; }
        .periodo-card h2 i { color: var(--dorado); }
        .periodo-card .fechas { color: var(--gris-oscuro); font-size: 14px; margin-bottom: 15px; }
        .periodo-card .fechas i { color: var(--guinda); margin-right: 5px; }
        .periodo-card .estado {
            display: inline-block; background: #d4edda; color: #155724; padding: 4px 12px;
            border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 15px;
        }
        .periodo-card .btn-inscribir {
            display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco); border-radius: var(--radius); text-decoration: none; font-weight: 600;
            transition: all 0.3s; border: none; cursor: pointer;
        }
        .periodo-card .btn-inscribir:hover { transform: translateY(-2px); box-shadow: var(--shadow-hover); }

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

        @media (max-width: 768px) {
            .header-itsz .container { flex-direction: column; align-items: center; text-align: center; }
            .header-itsz .logo-container { flex-direction: column; }
            .header-itsz nav { justify-content: center; }
            .header-itsz nav a { font-size: 11px; padding: 4px 8px; }
            .page-header { flex-direction: column; text-align: center; }
            .periodo-card { padding: 20px; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            .periodo-card .btn-inscribir { display: block; text-align: center; }
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
            <h1><i class='bx bxs-edit'></i> Proceso de Inscripción</h1>
            <p>Hola, <strong><?php echo htmlspecialchars($username); ?></strong>. Selecciona el período de inscripción activo.</p>
        </div>
        <a href="estudiante_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver al Panel</a>
    </div>

    <?php if (count($periodos_activos) > 0): ?>
        <?php foreach ($periodos_activos as $periodo): ?>
            <div class="periodo-card">
                <h2><i class='bx bxs-calendar'></i> <?php echo htmlspecialchars($periodo['nombre']); ?></h2>
                <div class="fechas">
                    <i class='bx bx-calendar'></i> Vigencia: 
                    <strong><?php echo date('d/m/Y', strtotime($periodo['fecha_inicio'])); ?></strong> 
                    al 
                    <strong><?php echo date('d/m/Y', strtotime($periodo['fecha_fin'])); ?></strong>
                </div>
                <div class="estado">✅ Activo</div>
                <a href="estudiante_carrera.php?periodo_id=<?php echo $periodo['id']; ?>" class="btn-inscribir">
                    <i class='bx bxs-right-arrow'></i> Iniciar Inscripción
                </a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bx-calendar-x'></i>
            <h3>No hay períodos de inscripción activos</h3>
            <p>En este momento no hay períodos de inscripción disponibles.</p>
            <p style="font-size: 14px; margin-top: 10px;">Contacta a la administración para más información.</p>
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