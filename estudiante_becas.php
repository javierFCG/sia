<?php
// estudiante_becas.php - Solicitar becas para estudiantes
session_start();
require_once 'config.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] === 'Administrador') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nombre_usuario = $_SESSION['username'];
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($conn->connect_error) { die("Error de conexión"); }

$mensaje = "";
$mensaje_tipo = "";

// Solicitar beca
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['solicitar_beca'])) {
    $beca_id = (int)$_POST['beca_id'];
    $observaciones = $_POST['observaciones'] ?? '';
    
    // Verificar que no haya solicitado antes
    $check = $conn->prepare("SELECT id FROM becas_solicitudes WHERE estudiante_id = ? AND beca_id = ?");
    $check->bind_param("ii", $user_id, $beca_id);
    $check->execute();
    if ($check->get_result()->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO becas_solicitudes (estudiante_id, beca_id, observaciones) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $beca_id, $observaciones);
        $stmt->execute();
        $stmt->close();
        $mensaje = "✅ Solicitud enviada exitosamente";
        $mensaje_tipo = "success";
    } else {
        $mensaje = "⚠️ Ya has solicitado esta beca";
        $mensaje_tipo = "warning";
    }
    $check->close();
}

// Obtener becas activas
$becas = $conn->query("SELECT * FROM becas WHERE activo = 1 AND CURDATE() BETWEEN fecha_inicio AND fecha_fin ORDER BY nombre")->fetch_all(MYSQLI_ASSOC);

// Obtener mis solicitudes
$mis_solicitudes = $conn->query("SELECT b.nombre, bs.estado, bs.fecha_solicitud, bs.observaciones 
                                 FROM becas_solicitudes bs 
                                 JOIN becas b ON bs.beca_id = b.id 
                                 WHERE bs.estudiante_id = $user_id 
                                 ORDER BY bs.fecha_solicitud DESC")->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Becas - ITSZ</title>
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
        .page-header p { color: var(--gris-oscuro); font-size: 14px; margin-top: 5px; }
        .btn-volver { background: var(--gris-oscuro); color: var(--blanco); padding: 10px 20px; border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; font-size: 14px; }
        .btn-volver:hover { background: #b0b0b0; transform: translateY(-2px); }

        .mensaje { padding: 15px; border-radius: var(--radius); margin-bottom: 20px; font-weight: 600; }
        .mensaje.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .mensaje.warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .mensaje.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .beca-card {
            background: white; border-radius: var(--radius); padding: 25px; box-shadow: var(--shadow);
            margin-bottom: 20px; border-left: 5px solid var(--azul); transition: all 0.3s;
        }
        .beca-card:hover { box-shadow: var(--shadow-hover); transform: translateX(5px); }
        .beca-card .beca-header {
            display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;
        }
        .beca-card .beca-header h3 { color: var(--azul); font-size: 20px; margin: 0; }
        .beca-card .beca-header .porcentaje {
            font-size: 28px; font-weight: 700; color: #27ae60; background: #d4edda; padding: 5px 15px; border-radius: var(--radius);
        }
        .beca-card .desc { color: var(--gris-oscuro); font-size: 14px; margin: 8px 0; }
        .beca-card .detalles {
            display: flex; flex-wrap: wrap; gap: 15px; font-size: 13px; color: var(--gris-oscuro); margin: 8px 0;
        }
        .beca-card .detalles i { color: var(--azul); margin-right: 3px; }
        .beca-card .detalles .cupo { background: var(--gris); padding: 2px 10px; border-radius: 12px; }
        .beca-card .btn-solicitar {
            padding: 10px 25px; background: linear-gradient(135deg, var(--azul) 0%, var(--guinda) 100%);
            color: var(--blanco); border: none; border-radius: var(--radius); font-weight: 600; cursor: pointer;
            transition: all 0.3s; margin-top: 10px;
        }
        .beca-card .btn-solicitar:hover { transform: translateY(-2px); box-shadow: var(--shadow-hover); }
        .beca-card .btn-solicitar:disabled {
            background: var(--gris-oscuro); cursor: not-allowed; transform: none; box-shadow: none;
        }
        .beca-card .ya-solicitado {
            display: inline-block; background: #fff3cd; color: #856404; padding: 6px 15px; border-radius: var(--radius); font-weight: 600; font-size: 14px;
        }

        .solicitudes-table {
            width: 100%; border-collapse: collapse; background: white; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);
        }
        .solicitudes-table th, .solicitudes-table td {
            padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--gris); font-size: 14px;
        }
        .solicitudes-table th { background: var(--azul); color: white; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        .solicitudes-table tr:hover { background: #f8f5f0; }
        .solicitudes-table .badge-pendiente { background: #fff3cd; color: #856404; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .solicitudes-table .badge-aprobada { background: #d4edda; color: #155724; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .solicitudes-table .badge-rechazada { background: #f8d7da; color: #721c24; padding: 3px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }

        .section-title {
            color: var(--azul); font-size: 20px; margin: 25px 0 15px; display: flex; align-items: center; gap: 10px;
        }
        .section-title i { color: var(--guinda); }

        .empty-state {
            text-align: center; padding: 40px; background: white; border-radius: var(--radius); box-shadow: var(--shadow);
            border-left: 5px solid var(--gris-oscuro);
        }
        .empty-state i { font-size: 50px; color: var(--gris-oscuro); margin-bottom: 15px; display: block; }
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
            .beca-card .beca-header { flex-direction: column; }
            .solicitudes-table { font-size: 12px; }
            .solicitudes-table th, .solicitudes-table td { padding: 8px 10px; }
        }
        @media (max-width: 480px) {
            .header-itsz .logo-container img { height: 30px; }
            .beca-card .btn-solicitar { display: block; text-align: center; width: 100%; }
            .solicitudes-table { font-size: 11px; }
            .solicitudes-table th, .solicitudes-table td { padding: 6px 8px; }
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
            <h1><i class='bx bxs-award'></i> Solicitud de Becas</h1>
            <p>Bienvenido, <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong></p>
        </div>
        <a href="estudiante_panel.php" class="btn-volver"><i class='bx bx-arrow-back'></i> Volver</a>
    </div>

    <?php if ($mensaje): ?>
        <div class="mensaje <?php echo $mensaje_tipo; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <!-- Becas disponibles -->
    <?php if (count($becas) > 0): ?>
        <div class="section-title"><i class='bx bxs-gift'></i> Becas Disponibles</div>
        <?php foreach ($becas as $b): 
            // Verificar si ya solicitó esta beca
            $ya_solicitada = false;
            foreach ($mis_solicitudes as $s) {
                if ($s['nombre'] == $b['nombre']) {
                    $ya_solicitada = true;
                    break;
                }
            }
        ?>
            <div class="beca-card">
                <div class="beca-header">
                    <h3><?php echo htmlspecialchars($b['nombre']); ?></h3>
                    <span class="porcentaje"><?php echo $b['porcentaje']; ?>%</span>
                </div>
                <div class="desc"><?php echo htmlspecialchars($b['descripcion']); ?></div>
                <div class="detalles">
                    <span><i class='bx bxs-user'></i> Cupo: <span class="cupo"><?php echo $b['cupo']; ?> estudiantes</span></span>
                    <span><i class='bx bxs-calendar'></i> <?php echo date('d/m/Y', strtotime($b['fecha_inicio'])); ?> - <?php echo date('d/m/Y', strtotime($b['fecha_fin'])); ?></span>
                </div>
                <?php if ($ya_solicitada): ?>
                    <span class="ya-solicitado"><i class='bx bxs-check-circle'></i> Ya solicitaste esta beca</span>
                <?php else: ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="beca_id" value="<?php echo $b['id']; ?>">
                        <button type="submit" name="solicitar_beca" class="btn-solicitar">
                            <i class='bx bxs-send'></i> Solicitar
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <i class='bx bxs-award'></i>
            <p>No hay becas disponibles en este momento.</p>
        </div>
    <?php endif; ?>

    <!-- Mis solicitudes -->
    <?php if (count($mis_solicitudes) > 0): ?>
        <div class="section-title"><i class='bx bxs-file'></i> Mis Solicitudes</div>
        <div style="overflow-x: auto;">
            <table class="solicitudes-table">
                <thead>
                    <tr>
                        <th>Beca</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mis_solicitudes as $s): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($s['nombre']); ?></strong></td>
                            <td>
                                <span class="badge-<?php echo strtolower($s['estado']); ?>">
                                    <?php echo $s['estado']; ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($s['fecha_solicitud'])); ?></td>
                            <td><?php echo htmlspecialchars($s['observaciones'] ?: '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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