<?php
// admin_dashboard.php - Panel de Control con Estadísticas
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión: " . $conn->connect_error); }

// Estadísticas Generales
$stats = [];

// Total de usuarios
$result = $conn->query("SELECT COUNT(*) as total FROM usuarios");
$stats['usuarios'] = $result ? $result->fetch_assoc()['total'] : 0;

// Total de estudiantes
$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE Rol = 'Estudiante'");
$stats['estudiantes'] = $result ? $result->fetch_assoc()['total'] : 0;

// Total de docentes
$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE Rol = 'Docente'");
$stats['docentes'] = $result ? $result->fetch_assoc()['total'] : 0;

// Total de carreras
$result = $conn->query("SELECT COUNT(*) as total FROM carreras");
$stats['carreras'] = $result ? $result->fetch_assoc()['total'] : 0;

// Total de materias
$result = $conn->query("SELECT COUNT(*) as total FROM materias");
$stats['materias'] = $result ? $result->fetch_assoc()['total'] : 0;

// Inscripciones por período
$result = $conn->query("SELECT p.nombre, COUNT(i.id) as total 
                        FROM periodos p 
                        LEFT JOIN inscripciones i ON p.id = i.periodo_id 
                        GROUP BY p.id 
                        ORDER BY p.fecha_inicio DESC 
                        LIMIT 5");
$stats['inscripciones_periodo'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Distribución por carrera
$result = $conn->query("SELECT c.nombre, COUNT(i.id) as total 
                        FROM carreras c 
                        LEFT JOIN inscripciones i ON c.id = i.carrera_id 
                        GROUP BY c.id 
                        ORDER BY total DESC");
$stats['carreras_dist'] = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Calificaciones
$result = $conn->query("SELECT 
    AVG(calificacion) as promedio,
    COUNT(CASE WHEN calificacion >= 70 THEN 1 END) as aprobados,
    COUNT(CASE WHEN calificacion < 70 AND calificacion IS NOT NULL THEN 1 END) as reprobados,
    COUNT(CASE WHEN calificacion IS NULL THEN 1 END) as pendientes
    FROM inscripciones_detalle");
$stats['calificaciones'] = $result ? $result->fetch_assoc() : ['promedio'=>0, 'aprobados'=>0, 'reprobados'=>0, 'pendientes'=>0];

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ITSZ</title>
    <link rel="icon" href="assets/img/favicon.ico">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --azul: #1B396A;
            --guinda: #620F31;
            --rojo: #860A0F;
            --dorado: #D99A32;
            --dorado-intenso: #F0A51A;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: white; padding: 25px 20px; border-radius: var(--radius); box-shadow: var(--shadow);
            text-align: center; transition: all 0.3s; border-left: 4px solid var(--azul);
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-hover); }
        .stat-card .icon { font-size: 28px; color: var(--guinda); margin-bottom: 8px; display: block; }
        .stat-card .number { font-size: 32px; font-weight: 700; color: var(--azul); }
        .stat-card .label { font-size: 13px; color: var(--gris-oscuro); margin-top: 3px; }
        .stat-card.verde { border-left-color: #27ae60; }
        .stat-card.verde .number { color: #27ae60; }
        .stat-card.verde .icon { color: #27ae60; }
        .stat-card.dorado { border-left-color: var(--dorado); }
        .stat-card.dorado .number { color: var(--dorado); }
        .stat-card.dorado .icon { color: var(--dorado); }
        .stat-card.rojo { border-left-color: var(--rojo); }
        .stat-card.rojo .number { color: var(--rojo); }
        .stat-card.rojo .icon { color: var(--rojo); }

        .charts-grid {
            display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 25px;
        }
        .chart-card {
            background: white; padding: 25px; border-radius: var(--radius); box-shadow: var(--shadow);
        }
        .chart-card h3 { color: var(--azul); margin-bottom: 15px; font-size: 16px; display: flex; align-items: center; gap: 8px; }
        .chart-card h3 i { color: var(--guinda); }
        .chart-card canvas { max-height: 250px; max-width: 100%; }

        .calificaciones-stats {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-top: 15px;
        }
        .calif-item {
            text-align: center; padding: 15px; border-radius: var(--radius); background: var(--gris);
            transition: all 0.3s;
        }
        .calif-item:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
        .calif-item .num { font-size: 28px; font-weight: 700; }
        .calif-item .num.aprobado { color: #27ae60; }
        .calif-item .num.reprobado { color: var(--rojo); }
        .calif-item .num.pendiente { color: var(--dorado); }
        .calif-item .num.promedio { color: var(--azul); }
        .calif-item .label { font-size: 13px; color: var(--gris-oscuro); margin-top: 3px; }

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
            .charts-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            .stats-grid { grid-template-columns: 1fr; }
            .calificaciones-stats { grid-template-columns: 1fr 1fr; }
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
        <div><h1><i class='bx bxs-dashboard'></i> Dashboard</h1><p>Panel de control y estadísticas del sistema</p></div>
        <a href="admin_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
    </div>

    <!-- Tarjetas de Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <span class="icon"><i class='bx bxs-user'></i></span>
            <div class="number"><?php echo $stats['usuarios']; ?></div>
            <div class="label">Total Usuarios</div>
        </div>
        <div class="stat-card verde">
            <span class="icon"><i class='bx bxs-graduation'></i></span>
            <div class="number"><?php echo $stats['estudiantes']; ?></div>
            <div class="label">Estudiantes</div>
        </div>
        <div class="stat-card dorado">
            <span class="icon"><i class='bx bxs-chalkboard'></i></span>
            <div class="number"><?php echo $stats['docentes']; ?></div>
            <div class="label">Docentes</div>
        </div>
        <div class="stat-card">
            <span class="icon"><i class='bx bxs-book'></i></span>
            <div class="number"><?php echo $stats['carreras']; ?></div>
            <div class="label">Carreras</div>
        </div>
        <div class="stat-card rojo">
            <span class="icon"><i class='bx bxs-book-open'></i></span>
            <div class="number"><?php echo $stats['materias']; ?></div>
            <div class="label">Materias</div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="charts-grid">
        <div class="chart-card">
            <h3><i class='bx bx-line-chart'></i> Inscripciones por Período</h3>
            <canvas id="chartPeriodos"></canvas>
        </div>
        <div class="chart-card">
            <h3><i class='bx bx-pie-chart-alt'></i> Distribución por Carrera</h3>
            <canvas id="chartCarreras"></canvas>
        </div>
    </div>

    <!-- Resumen de Calificaciones -->
    <div class="chart-card">
        <h3><i class='bx bx-stats'></i> Resumen de Calificaciones</h3>
        <div class="calificaciones-stats">
            <div class="calif-item">
                <div class="num promedio"><?php echo number_format($stats['calificaciones']['promedio'] ?? 0, 2); ?></div>
                <div class="label">Promedio General</div>
            </div>
            <div class="calif-item">
                <div class="num aprobado"><?php echo $stats['calificaciones']['aprobados'] ?? 0; ?></div>
                <div class="label">Aprobados</div>
            </div>
            <div class="calif-item">
                <div class="num reprobado"><?php echo $stats['calificaciones']['reprobados'] ?? 0; ?></div>
                <div class="label">Reprobados</div>
            </div>
            <div class="calif-item">
                <div class="num pendiente"><?php echo $stats['calificaciones']['pendientes'] ?? 0; ?></div>
                <div class="label">Pendientes</div>
            </div>
        </div>
    </div>
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
    // Gráfico de Períodos
    const ctx1 = document.getElementById('chartPeriodos').getContext('2d');
    const dataPeriodos = <?php echo json_encode($stats['inscripciones_periodo']); ?>;
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: dataPeriodos.map(d => d.nombre || 'Sin período'),
            datasets: [{
                label: 'Inscripciones',
                data: dataPeriodos.map(d => d.total || 0),
                backgroundColor: ['#1B396A', '#620F31', '#D99A32', '#2ecc71', '#e74c3c'],
                borderColor: '#1B396A',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { 
                y: { 
                    beginAtZero: true,
                    ticks: { stepSize: 1 }
                }
            }
        }
    });

    // Gráfico de Carreras
    const ctx2 = document.getElementById('chartCarreras').getContext('2d');
    const dataCarreras = <?php echo json_encode($stats['carreras_dist']); ?>;
    const colores = ['#1B396A', '#620F31', '#D99A32', '#2ecc71', '#e74c3c', '#3498db', '#1abc9c'];
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: dataCarreras.map(d => d.nombre || 'Sin carrera'),
            datasets: [{
                data: dataCarreras.map(d => d.total || 0),
                backgroundColor: colores.slice(0, dataCarreras.length || 1),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            plugins: { 
                legend: { 
                    position: 'bottom',
                    labels: { 
                        padding: 15,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            },
            cutout: '55%'
        }
    });
</script>
</body>
</html>