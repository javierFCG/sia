<?php
// estudiante_calendario.php - Ver calendario para estudiantes
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] === 'Administrador') {
    header("Location: index.php");
    exit();
}

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

// Obtener eventos futuros y actuales
$eventos = $conn->query("SELECT * FROM eventos WHERE fecha_fin >= CURDATE() ORDER BY fecha_inicio ASC")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario Académico - ITSZ</title>
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

        .container { max-width: 900px; margin: 20px auto; padding: 0 20px; }

        .page-header {
            background: white; padding: 20px 25px; border-radius: var(--radius); box-shadow: var(--shadow);
            margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;
        }
        .page-header h1 { color: var(--azul); font-size: 24px; }
        .page-header h1 i { color: var(--guinda); }
        .page-header p { color: var(--gris-oscuro); font-size: 14px; margin-top: 5px; }
        .btn-volver { background: var(--gris-oscuro); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-volver:hover { background: #b0b0b0; transform: translateY(-2px); }

        .evento-item {
            background: white; border-radius: var(--radius); padding: 20px 25px; box-shadow: var(--shadow);
            margin-bottom: 15px; border-left: 5px solid var(--azul); transition: all 0.3s;
        }
        .evento-item:hover { box-shadow: var(--shadow-hover); transform: translateX(5px); }
        .evento-item .evento-header {
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;
        }
        .evento-item .evento-header h3 { color: var(--azul); font-size: 18px; margin: 0; }
        .evento-item .fecha {
            font-size: 13px; color: var(--gris-oscuro); margin: 5px 0; display: flex; align-items: center; gap: 5px; flex-wrap: wrap;
        }
        .evento-item .fecha i { color: var(--guinda); }
        .evento-item .desc { color: #555; font-size: 14px; margin-top: 8px; }
        .evento-item .tipo {
            display: inline-block; padding: 3px 14px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-top: 8px;
        }
        .evento-item .tipo-academico { background: #cce5ff; color: #004085; }
        .evento-item .tipo-examen { background: #f8d7da; color: #721c24; }
        .evento-item .tipo-vacaciones { background: #d4edda; color: #155724; }
        .evento-item .tipo-entrega { background: #fff3cd; color: #856404; }
        .evento-item .tipo-otro { background: #d6d8db; color: #383d41; }

        .evento-actual { border-left-color: #27ae60; background: #f0fff4; }
        .evento-proximo { border-left-color: var(--dorado); background: #fffbf0; }

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
            .evento-item .evento-header { flex-direction: column; align-items: flex-start; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            .evento-item { padding: 15px; }
            .evento-item .fecha { font-size: 12px; }
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
            <h1><i class='bx bxs-calendar-event'></i> Calendario Académico</h1>
            <p>Eventos importantes del período académico</p>
        </div>
        <a href="estudiante_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
    </div>

    <?php if (count($eventos) > 0): ?>
        <h3 style="color: var(--azul); margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
            <i class='bx bxs-calendar' style="color: var(--guinda);"></i> Próximos Eventos
        </h3>
        <?php foreach ($eventos as $e): 
            $hoy = date('Y-m-d H:i:s');
            $clase = '';
            $tipo_clase = 'tipo-' . strtolower($e['tipo']);
            if ($e['fecha_inicio'] <= $hoy && $e['fecha_fin'] >= $hoy) $clase = 'evento-actual';
            elseif ($e['fecha_inicio'] > $hoy) $clase = 'evento-proximo';
        ?>
            <div class="evento-item <?php echo $clase; ?>" style="border-left-color: <?php echo $e['color'] ?? '#1B396A'; ?>;">
                <div class="evento-header">
                    <h3><i class='bx bxs-calendar-check' style="color: <?php echo $e['color'] ?? '#1B396A'; ?>;"></i> <?php echo htmlspecialchars($e['titulo']); ?></h3>
                    <span class="tipo <?php echo $tipo_clase; ?>">
                        <i class='bx bx-tag'></i> <?php echo $e['tipo']; ?>
                    </span>
                </div>
                <div class="fecha">
                    <i class='bx bxs-calendar'></i> 
                    <strong>Inicio:</strong> <?php echo date('d/m/Y H:i', strtotime($e['fecha_inicio'])); ?>
                    <span style="margin-left: 10px;">
                        <i class='bx bxs-calendar'></i> 
                        <strong>Fin:</strong> <?php echo date('d/m/Y H:i', strtotime($e['fecha_fin'])); ?>
                    </span>
                </div>
                <?php if ($e['descripcion']): ?>
                    <div class="desc"><?php echo nl2br(htmlspecialchars($e['descripcion'])); ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div style="margin-top: 15px; text-align: right; color: var(--gris-oscuro); font-size: 13px;">
            <i class='bx bxs-calendar'></i> Total: <strong><?php echo count($eventos); ?></strong> eventos programados
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bxs-calendar-x'></i>
            <h3>No hay eventos programados</h3>
            <p>En este momento no hay eventos académicos programados.</p>
            <p style="font-size: 14px; margin-top: 10px;">Revisa más tarde para conocer las actividades.</p>
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