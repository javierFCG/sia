<?php
// estudiante_carrera.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] === 'Administrador') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id === 0) {
    header("Location: Login.html");
    exit();
}

if (!isset($_GET['periodo_id']) || !is_numeric($_GET['periodo_id'])) {
    die("ID de Período no válido.");
}
$periodo_id = (int)$_GET['periodo_id'];

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Verificar si ya tiene inscripción en este período
if (!isset($_GET['cambiar'])) {
    $stmt_check = $conn->prepare("SELECT carrera_id FROM inscripciones WHERE usuario_id = ? AND periodo_id = ?");
    $stmt_check->bind_param("ii", $user_id, $periodo_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $carrera_inscrita = $result_check->fetch_assoc()['carrera_id'];
        header("Location: estudiante_materias.php?periodo_id={$periodo_id}&carrera_id={$carrera_inscrita}");
        exit();
    }
    $stmt_check->close();
}

$sql = "SELECT id, nombre, clave, duracion_semestres FROM carreras ORDER BY nombre ASC";
$result_carreras = $conn->query($sql);
$carreras = $result_carreras ? $result_carreras->fetch_all(MYSQLI_ASSOC) : [];
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selección de Carrera - ITSZ</title>
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

        .carrera-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px;
        }
        .carrera-card {
            background: white; border-radius: var(--radius-lg); padding: 25px; box-shadow: var(--shadow);
            text-align: center; border-top: 5px solid var(--azul); transition: all 0.3s;
        }
        .carrera-card:hover {
            transform: translateY(-8px); box-shadow: var(--shadow-hover);
        }
        .carrera-card .icono { font-size: 40px; color: var(--guinda); margin-bottom: 10px; display: block; }
        .carrera-card h3 { color: var(--azul); font-size: 20px; margin-bottom: 5px; }
        .carrera-card .clave { color: var(--guinda); font-weight: 600; font-size: 14px; }
        .carrera-card .duracion { color: var(--gris-oscuro); font-size: 14px; margin: 8px 0; }
        .carrera-card .btn-select {
            display: inline-block; padding: 12px 25px; background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco); border-radius: var(--radius); text-decoration: none; font-weight: 600;
            transition: all 0.3s; width: 100%; box-sizing: border-box; border: none; cursor: pointer;
        }
        .carrera-card .btn-select:hover {
            transform: translateY(-2px); box-shadow: var(--shadow-hover);
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

        @media (max-width: 768px) {
            .header-itsz .container { flex-direction: column; align-items: center; text-align: center; }
            .header-itsz .logo-container { flex-direction: column; }
            .header-itsz nav { justify-content: center; }
            .header-itsz nav a { font-size: 11px; padding: 4px 8px; }
            .page-header { flex-direction: column; text-align: center; }
            .carrera-grid { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            .carrera-grid { grid-template-columns: 1fr; }
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
            <h1><i class='bx bxs-graduation'></i> Selecciona tu Carrera</h1>
            <p>Estás inscribiendo para el período activo. Selecciona la carrera que cursarás.</p>
        </div>
        <a href="estudiante_inscripcion.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver a Períodos</a>
    </div>

    <?php if (count($carreras) > 0): ?>
        <div class="carrera-grid">
            <?php foreach ($carreras as $carrera): ?>
                <div class="carrera-card">
                    <span class="icono"><i class='bx bxs-graduation'></i></span>
                    <h3><?php echo htmlspecialchars($carrera['nombre']); ?></h3>
                    <div class="clave">Clave: <?php echo htmlspecialchars($carrera['clave']); ?></div>
                    <div class="duracion">Duración: <?php echo $carrera['duracion_semestres']; ?> semestres</div>
                    <a href="estudiante_materias.php?periodo_id=<?php echo $periodo_id; ?>&carrera_id=<?php echo $carrera['id']; ?>" 
                       class="btn-select">
                        <i class='bx bxs-right-arrow'></i> Seleccionar y Continuar
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bxs-book-x'></i>
            <h3>No hay carreras disponibles</h3>
            <p>En este momento no hay carreras registradas en el sistema.</p>
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