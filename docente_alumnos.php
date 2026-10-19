<?php
// docente_alumnos.php - Lista de alumnos por materia
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Docente') {
    header("Location: index.php");
    exit();
}

if (!isset($_GET['materia_id']) || !is_numeric($_GET['materia_id'])) {
    die("ID de materia no válido.");
}

$materia_id = (int)$_GET['materia_id'];
$docente = $_SESSION['username'];
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Verificar materia
$stmt = $conn->prepare("SELECT nombre FROM materias WHERE id = ? AND docente_asignado = ?");
$stmt->bind_param("is", $materia_id, $docente);
$stmt->execute();
$materia = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$materia) { die("No tienes permiso para ver esta materia."); }

$sql = "SELECT DISTINCT u.Id, u.Nombre, u.Correo
        FROM inscripciones_detalle idet
        JOIN inscripciones i ON idet.inscripcion_id = i.id
        JOIN usuarios u ON i.usuario_id = u.Id
        WHERE idet.materia_id = ?
        ORDER BY u.Nombre ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $materia_id);
$stmt->execute();
$alumnos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Alumnos - ITSZ</title>
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

        .container { max-width: 1000px; margin: 20px auto; padding: 0 20px; }

        .page-header {
            background: white; padding: 20px 25px; border-radius: var(--radius); box-shadow: var(--shadow);
            margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
        }
        .page-header h1 { color: var(--azul); font-size: 24px; }
        .page-header h1 i { color: var(--guinda); }
        .page-header .sub { color: var(--gris-oscuro); font-size: 14px; margin-top: 5px; }
        .page-header .sub strong { color: var(--azul); }
        .btn-back { background: var(--gris-oscuro); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-back:hover { background: #b0b0b0; transform: translateY(-2px); }

        .badge-total {
            background: var(--azul); color: var(--blanco); padding: 6px 18px;
            border-radius: 50px; font-size: 14px; font-weight: 600; display: inline-block;
            margin-bottom: 15px;
        }
        .badge-total i { color: var(--dorado); margin-right: 5px; }

        .table-container {
            background: white; border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden;
        }
        table {
            width: 100%; border-collapse: collapse;
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
        .col-numero {
            text-align: center; font-weight: 600; color: var(--gris-oscuro);
            width: 50px;
        }
        .col-nombre { font-weight: 600; color: var(--azul); }
        .col-email { color: var(--gris-oscuro); }

        .empty-state {
            text-align: center; padding: 60px 20px; background: white; border-radius: var(--radius);
            box-shadow: var(--shadow); border-left: 5px solid var(--gris-oscuro);
        }
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
            table { font-size: 12px; }
            th, td { padding: 8px 10px; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            table { font-size: 11px; }
            th, td { padding: 6px 8px; }
            .col-numero { width: 30px; }
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
            <a href="docente_panel.php"><i class='bx bxs-chalkboard'></i> Panel</a>
            <a href="mensajes.php"><i class='bx bxs-envelope'></i> Mensajes</a>
            <a href="logout.php" class="btn-logout"><i class='bx bx-log-out'></i> Salir</a>
        </nav>
    </div>
</header>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class='bx bxs-user'></i> Lista de Alumnos</h1>
            <div class="sub">Materia: <strong><?php echo htmlspecialchars($materia['nombre']); ?></strong></div>
        </div>
        <a href="docente_panel.php" class="btn-back"><i class='bx bx-arrow-back'></i> Volver</a>
    </div>

    <?php if (count($alumnos) > 0): ?>
        <div style="text-align: right; margin-bottom: 15px;">
            <span class="badge-total">
                <i class='bx bxs-user'></i> Total: <?php echo count($alumnos); ?> alumnos
            </span>
        </div>

        <div class="table-container">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th class="col-numero">#</th>
                            <th>Nombre</th>
                            <th>Correo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($alumnos as $a): ?>
                            <tr>
                                <td class="col-numero"><?php echo $i++; ?></td>
                                <td class="col-nombre"><i class='bx bxs-user-circle' style="color: var(--guinda);"></i> <?php echo htmlspecialchars($a['Nombre']); ?></td>
                                <td class="col-email"><i class='bx bxs-envelope' style="color: var(--azul);"></i> <?php echo htmlspecialchars($a['Correo']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bxs-user-x'></i>
            <h3>No hay alumnos inscritos</h3>
            <p>Esta materia aún no tiene alumnos inscritos.</p>
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